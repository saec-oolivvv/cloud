<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * FTP Storage Adapter
 * 
 * Connexion FTP/FTPS via les fonctions natives PHP.
 * Supporte: FTP passif, SSL/TLS explicite.
 */
class FtpAdapter extends AbstractAdapter
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $rootDir;
    private bool $passive;
    private bool $ssl;
    private ?resource $conn;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->host = $config['host'] ?? '';
        $this->port = $config['port'] ?? 21;
        $this->username = $config['username'] ?? 'anonymous';
        $this->password = $config['password'] ?? '';
        $this->rootDir = $config['root_dir'] ?? '/';
        $this->passive = $config['passive'] ?? true;
        $this->ssl = $config['ssl'] ?? false;
        $this->conn = null;
    }

    protected function connect(): void
    {
        if (is_resource($this->conn)) {
            return;
        }

        if (empty($this->host)) {
            throw new \RuntimeException("FTP host not configured");
        }

        if ($this->ssl) {
            $this->conn = @ftp_ssl_connect($this->host, $this->port, 30);
            if (!$this->conn) {
                $this->conn = @ftp_connect($this->host, $this->port, 30);
            }
        } else {
            $this->conn = @ftp_connect($this->host, $this->port, 30);
        }

        if (!$this->conn) {
            throw new \RuntimeException("FTP connection failed: {$this->host}:{$this->port}");
        }

        if (!@ftp_login($this->conn, $this->username, $this->password)) {
            $this->close();
            throw new \RuntimeException("FTP login failed");
        }

        ftp_pasv($this->conn, $this->passive);
        $this->connected = true;
    }

    private function close(): void
    {
        if (is_resource($this->conn)) {
            ftp_close($this->conn);
            $this->conn = null;
        }
        $this->connected = false;
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
                'message' => "Connected to {$this->host}:{$this->port}",
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
            'type' => 'ftp',
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

        $listing = @ftp_nlist($this->conn, $realPath);
        if ($listing === false) {
            return [];
        }

        foreach ($listing as $item) {
            $basename = basename($item);
            if ($basename === '.' || $basename === '..') continue;

            $itemPath = rtrim($realPath, '/') . '/' . $basename;
            $modified = @ftp_mdtm($this->conn, $itemPath);
            $size = @ftp_size($this->conn, $itemPath);

            // FTP size returns -1 for directories on some servers
            $isDir = $size === -1;
            if ($isDir) {
                $size = 0;
            }

            $items[] = [
                'name' => $basename,
                'path' => ltrim($itemPath, '/'),
                'type' => $isDir ? 'folder' : 'file',
                'size' => $isDir ? 0 : $size,
                'modified' => $modified > 0 ? $modified : 0,
                'mime' => $isDir ? null : $this->guessMimeType($basename),
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

        $temp = tmpfile();
        if (!@ftp_get($this->conn, $temp, $realPath, FTP_BINARY)) {
            fclose($temp);
            throw new \RuntimeException("Failed to read: {$path}");
        }

        rewind($temp);
        $content = stream_get_contents($temp);
        fclose($temp);

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

        $temp = tmpfile();
        fwrite($temp, $content);
        rewind($temp);

        $success = @ftp_put($this->conn, $realPath, $temp, FTP_BINARY);
        fclose($temp);

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

        $size = @ftp_size($this->conn, $realPath);
        $isDir = ($size === -1);

        if ($isDir) {
            $success = @ftp_rmdir($this->conn, $realPath);
        } else {
            $success = @ftp_delete($this->conn, $realPath);
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
        return @ftp_mkdir($this->conn, $realPath) !== false;
    }

    public function rename(string $oldPath, string $newPath): bool
    {
        $this->ensureConnected();
        $old = $this->resolvePath($oldPath);
        $new = $this->resolvePath($newPath);
        return @ftp_rename($this->conn, $old, $new);
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

        $listing = @ftp_nlist($this->conn, dirname($realPath));
        if ($listing === false) return false;

        return in_array($realPath, $listing);
    }

    public function size(string $path): int
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);
        return @ftp_size($this->conn, $realPath);
    }

    public function lastModified(string $path): int
    {
        $this->ensureConnected();
        $realPath = $this->resolvePath($path);
        return @ftp_mdtm($this->conn, $realPath);
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
        $listing = @ftp_nlist($this->conn, $path);
        if (!$listing) return;

        foreach ($listing as $item) {
            $basename = basename($item);
            if ($basename === '.' || $basename === '..') continue;

            $itemPath = rtrim($path, '/') . '/' . $basename;
            $size = @ftp_size($this->conn, $itemPath);

            if ($size === -1) {
                $this->recursiveSize($itemPath, $total);
            } else {
                $total += $size;
            }
        }
    }

    public function freeSpace(): int
    {
        return -1; // FTP doesn't support free space query
    }

    public function totalSpace(): int
    {
        return -1; // FTP doesn't support total space query
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
        return null; // FTP doesn't support signed URLs
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
        $dirs = [];
        $current = $dir;

        while ($current !== '/' && $current !== '.' && !@ftp_nlist($this->conn, $current)) {
            array_unshift($dirs, $current);
            $current = dirname($current);
        }

        foreach ($dirs as $d) {
            @ftp_mkdir($this->conn, $d);
        }
    }
}
