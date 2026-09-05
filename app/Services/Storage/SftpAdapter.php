<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\RSA;

/**
 * SFTP Storage Adapter
 * 
 * Connexion SFTP/SSH sécurisée via phpseclib3.
 * Supporte: authentification par mot de passe ou clé privée.
 */
class SftpAdapter extends AbstractAdapter
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private ?string $privateKey;
    private string $rootDir;
    private ?SFTP $sftp;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->host = $config['host'] ?? '';
        $this->port = $config['port'] ?? 22;
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->privateKey = $config['private_key'] ?? null;
        $this->rootDir = $config['root_dir'] ?? '/';
        $this->sftp = null;
    }

    protected function connect(): void
    {
        if ($this->sftp !== null && $this->sftp->isConnected()) {
            return;
        }

        if (empty($this->host)) {
            throw new \RuntimeException("SFTP host not configured");
        }
        if (empty($this->username)) {
            throw new \RuntimeException("SFTP username not configured");
        }

        $this->sftp = new SFTP($this->host, $this->port, 30);

        if (!empty($this->privateKey)) {
            $key = new RSA();
            $key->setPassword($this->password);
            if (!$key->load($this->privateKey)) {
                throw new \RuntimeException("Invalid SFTP private key");
            }
            $this->sftp->login($this->username, $key);
        } else {
            if (!$this->sftp->login($this->username, $this->password)) {
                throw new \RuntimeException("SFTP authentication failed");
            }
        }

        $this->connected = true;
    }

    private function resolvePath(string $path): string
    {
        $path = ltrim($path, '/');
        return rtrim($this->rootDir, '/') . '/' . $path;
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $this->connect();
            $latency = (int)((microtime(true) - $start) * 1000);

            return [
                'success' => true,
                'message' => "Connected to {$this->host}:{$this->port} as {$this->username}",
                'latency_ms' => $latency,
                'details' => [
                    'host' => $this->host,
                    'port' => $this->port,
                    'root_dir' => $this->rootDir,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'latency_ms' => (int)((microtime(true) - $start) * 1000),
            ];
        }
    }

    public function getInfo(): array
    {
        return [
            'type' => 'sftp',
            'name' => $this->name,
            'region' => null,
            'endpoint' => $this->host,
            'version' => null,
        ];
    }

    public function list(string $path = ''): array
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);
        $items = [];

        $listing = $this->sftp->nlist($realPath);
        if ($listing === false) {
            return [];
        }

        foreach ($listing as $item) {
            if ($item === '.' || $item === '..') continue;

            $itemPath = rtrim($realPath, '/') . '/' . $item;
            $stat = $this->sftp->stat($itemPath);
            if (!$stat) continue;

            $isDir = ($stat['type'] ?? 0) === 4;

            $items[] = [
                'name' => $item,
                'path' => ltrim($itemPath, '/'),
                'type' => $isDir ? 'folder' : 'file',
                'size' => $isDir ? 0 : ($stat['size'] ?? 0),
                'modified' => $stat['mtime'] ?? 0,
                'mime' => $isDir ? null : $this->guessMimeType($item),
            ];
        }

        usort($items, fn($a, $b) => $a['type'] !== $b['type']
            ? ($a['type'] === 'folder' ? -1 : 1)
            : strcasecmp($a['name'], $b['name'])
        );

        $this->metrics['reads']++;
        return $items;
    }

    public function read(string $path): string
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);

        $content = $this->sftp->get($realPath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read: {$path}");
        }

        $this->metrics['reads']++;
        $this->metrics['bytes_read'] += strlen($content);
        return $content;
    }

    public function write(string $path, string $content, array $meta = []): bool
    {
        $this->ensureConnected();
        $this->validatePath($path);
        $realPath = $this->resolvePath($path);

        $this->ensureParentDir($realPath);

        $success = $this->sftp->put($realPath, $content);
        if (!$success) {
            $this->recordError('write', "Failed: {$path}");
            return false;
        }

        $this->metrics['writes']++;
        $this->metrics['bytes_written'] += strlen($content);
        return true;
    }

    public function delete(string $path): bool
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);

        $stat = $this->sftp->stat($realPath);
        if (!$stat) return true;

        $isDir = ($stat['type'] ?? 0) === 4;

        if ($isDir) {
            $success = $this->sftp->rmdir($realPath);
        } else {
            $success = $this->sftp->delete($realPath);
        }

        if ($success) {
            $this->metrics['deletes']++;
        } else {
            $this->recordError('delete', "Failed: {$path}");
        }

        return $success;
    }

    public function mkdir(string $path): bool
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);
        return $this->sftp->mkdir($realPath);
    }

    public function rename(string $oldPath, string $newPath): bool
    {
        $this->ensureConnected();
        $old = $this->resolvePath($oldPath);
        $new = $this->resolvePath($newPath);
        return $this->sftp->rename($old, $new);
    }

    public function copy(string $source, string $destination): bool
    {
        $content = $this->read($source);
        return $this->write($destination, $content);
    }

    public function exists(string $path): bool
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);
        return $this->sftp->file_exists($realPath);
    }

    public function size(string $path): int
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);
        return $this->sftp->size($realPath) ?: 0;
    }

    public function lastModified(string $path): int
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);
        $stat = $this->sftp->stat($realPath);
        return $stat['mtime'] ?? 0;
    }

    public function mimeType(string $path): ?string
    {
        $this->ensureConnected();
        return $this->guessMimeType(basename($path));
    }

    public function usedSpace(): int
    {
        $this->ensureConnected();
        $total = 0;
        $this->recursiveSize($this->rootDir, $total);
        return $total;
    }

    private function recursiveSize(string $path, int &$total): void
    {
        $listing = $this->sftp->nlist($path);
        if (!$listing) return;

        foreach ($listing as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = rtrim($path, '/') . '/' . $item;
            $stat = $this->sftp->stat($itemPath);
            if (!$stat) continue;

            if (($stat['type'] ?? 0) === 4) {
                $this->recursiveSize($itemPath, $total);
            } else {
                $total += $stat['size'] ?? 0;
            }
        }
    }

    public function freeSpace(): int
    {
        $this->ensureConnected();
        $stat = $this->sftp->statvfs($this->rootDir);
        if (!$stat) return -1;
        return ($stat['bavail'] ?? 0) * ($stat['frsize'] ?? 0);
    }

    public function totalSpace(): int
    {
        $this->ensureConnected();
        $stat = $this->sftp->statvfs($this->rootDir);
        if (!$stat) return -1;
        return ($stat['blocks'] ?? 0) * ($stat['frsize'] ?? 0);
    }

    public function putContents(array $files): array
    {
        $success = 0;
        $errors = [];

        foreach ($files as $file) {
            if ($this->write($file['path'], $file['content'], $file['meta'] ?? [])) {
                $success++;
            } else {
                $errors[] = ['path' => $file['path'], 'error' => $this->getLastError()];
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }

    public function deleteContents(array $paths): array
    {
        $success = 0;
        $errors = [];

        foreach ($paths as $path) {
            if ($this->delete($path)) {
                $success++;
            } else {
                $errors[] = ['path' => $path, 'error' => $this->getLastError()];
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }

    public function signedUrl(string $path, int $expiresInSecondes = 3600): ?string
    {
        return null; // SFTP doesn't support signed URLs
    }

    public function writeStream(string $path, $stream, int $size): bool
    {
        $content = stream_get_contents($stream);
        return $this->write($path, $content);
    }

    public function readStream(string $path)
    {
        $content = $this->read($path);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        return $stream;
    }

    public function supports(string $feature): bool
    {
        $supported = [
            'read' => true,
            'write' => true,
            'delete' => true,
            'mkdir' => true,
            'rename' => true,
            'copy' => true,
            'exists' => true,
            'size' => true,
            'mimeType' => true,
            'list' => true,
            'signedUrl' => false,
            'streaming' => true,
            'versioning' => false,
            'encryption' => false,
            'compression' => false,
        ];

        return $supported[$feature] ?? false;
    }

    private function ensureParentDir(string $path): void
    {
        $dir = dirname($path);
        if (!$this->sftp->file_exists($dir)) {
            $this->ensureParentDir($dir);
            $this->sftp->mkdir($dir);
        }
    }
}
