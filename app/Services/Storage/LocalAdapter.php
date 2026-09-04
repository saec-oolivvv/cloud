<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * Local Storage Adapter
 * 
 * Stockage sur le filesystem local du serveur.
 * Utilisé pour: stockage primaire, cache local, backups temporaires.
 */
class LocalAdapter extends AbstractAdapter
{
    private string $rootDir;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->rootDir = rtrim($config['root_dir'] ?? dirname(__DIR__, 3) . '/storage/uploads', '/');
    }

    protected function connect(): void
    {
        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0770, true);
        }
        if (!is_writable($this->rootDir)) {
            throw new \RuntimeException("Root directory not writable: {$this->rootDir}");
        }
        $this->connected = true;
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $this->connect();
            $latency = (int)((microtime(true) - $start) * 1000);

            $writable = is_writable($this->rootDir);
            $readable = is_readable($this->rootDir);

            return [
                'success' => $writable && $readable,
                'message' => $writable ? 'Connected' : 'Directory not writable',
                'latency_ms' => $latency,
                'details' => [
                    'root' => $this->rootDir,
                    'writable' => $writable,
                    'readable' => $readable,
                    'disk_free' => disk_free_space($this->rootDir),
                    'disk_total' => disk_total_space($this->rootDir),
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
            'type' => 'local',
            'name' => $this->name,
            'region' => null,
            'endpoint' => $this->rootDir,
            'version' => PHP_VERSION,
        ];
    }

    public function list(string $path = ''): array
    {
        $this->ensureConnected();
        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);

        if (!is_dir($fullPath)) {
            return [];
        }

        $items = [];
        $handle = opendir($fullPath);
        if (!$handle) return [];

        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') continue;

            $itemPath = ltrim($path . '/' . $entry, '/');
            $itemFull = $fullPath . '/' . $entry;
            $isDir = is_dir($itemFull);

            $items[] = [
                'name' => $entry,
                'path' => $itemPath,
                'type' => $isDir ? 'folder' : 'file',
                'size' => $isDir ? 0 : filesize($itemFull),
                'modified' => filemtime($itemFull),
                'mime' => $isDir ? null : $this->guessMimeType($entry),
            ];
        }
        closedir($handle);

        // Trier: dossiers d'abord, puis par nom
        usort($items, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'folder' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        $this->metrics['reads']++;
        return $items;
    }

    public function read(string $path): string
    {
        $this->ensureConnected();
        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $content = file_get_contents($fullPath);
        $this->metrics['reads']++;
        $this->metrics['bytes_read'] += strlen($content);

        return $content;
    }

    public function write(string $path, string $content, array $meta = []): bool
    {
        $this->ensureConnected();
        $this->validatePath($path);

        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }

        $written = file_put_contents($fullPath, $content, LOCK_EX);
        if ($written === false) {
            $this->recordError('write', "Failed to write: {$path}");
            return false;
        }

        $this->metrics['writes']++;
        $this->metrics['bytes_written'] += $written;

        return true;
    }

    public function delete(string $path): bool
    {
        $this->ensureConnected();
        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);

        if (!file_exists($fullPath)) {
            return true;
        }

        if (is_dir($fullPath)) {
            $success = $this->removeDir($fullPath);
        } else {
            $success = unlink($fullPath);
        }

        if ($success) {
            $this->metrics['deletes']++;
        } else {
            $this->recordError('delete', "Failed to delete: {$path}");
        }

        return $success;
    }

    public function mkdir(string $path): bool
    {
        $this->ensureConnected();
        $this->validatePath($path);

        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);

        if (is_dir($fullPath)) {
            return true;
        }

        $success = mkdir($fullPath, 0770, true);
        if (!$success) {
            $this->recordError('mkdir', "Failed to create: {$path}");
        }

        return $success;
    }

    public function rename(string $oldPath, string $newPath): bool
    {
        $this->ensureConnected();
        $this->validatePath($oldPath);
        $this->validatePath($newPath);

        $old = $this->rootDir . '/' . $this->cleanPath($oldPath);
        $new = $this->rootDir . '/' . $this->cleanPath($newPath);

        $newDir = dirname($new);
        if (!is_dir($newDir)) {
            mkdir($newDir, 0770, true);
        }

        $success = rename($old, $new);
        if (!$success) {
            $this->recordError('rename', "Failed to rename: {$oldPath} → {$newPath}");
        }

        return $success;
    }

    public function copy(string $source, string $destination): bool
    {
        $this->ensureConnected();

        $src = $this->rootDir . '/' . $this->cleanPath($source);
        $dst = $this->rootDir . '/' . $this->cleanPath($destination);

        $dstDir = dirname($dst);
        if (!is_dir($dstDir)) {
            mkdir($dstDir, 0770, true);
        }

        $success = copy($src, $dst);
        if (!$success) {
            $this->recordError('copy', "Failed to copy: {$source} → {$destination}");
        }

        return $success;
    }

    public function exists(string $path): bool
    {
        $this->ensureConnected();
        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);
        return file_exists($fullPath);
    }

    public function size(string $path): int
    {
        $this->ensureConnected();
        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);

        if (!file_exists($fullPath)) return 0;

        if (is_file($fullPath)) {
            return filesize($fullPath);
        }

        // Dossier: calculer taille récursivement
        return $this->dirSize($fullPath);
    }

    public function lastModified(string $path): int
    {
        $this->ensureConnected();
        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);
        return file_exists($fullPath) ? filemtime($fullPath) : 0;
    }

    public function mimeType(string $path): ?string
    {
        $this->ensureConnected();
        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);

        if (!file_exists($fullPath) || is_dir($fullPath)) return null;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($fullPath) ?: $this->guessMimeType(basename($path));
    }

    public function usedSpace(): int
    {
        $this->ensureConnected();
        return $this->dirSize($this->rootDir);
    }

    public function freeSpace(): int
    {
        $this->ensureConnected();
        return (int) disk_free_space($this->rootDir);
    }

    public function totalSpace(): int
    {
        $this->ensureConnected();
        return (int) disk_total_space($this->rootDir);
    }

    public function putContents(array $files): array
    {
        $success = 0;
        $errors = [];

        foreach ($files as $file) {
            $path = $file['path'] ?? '';
            $content = $file['content'] ?? '';
            $meta = $file['meta'] ?? [];

            if ($this->write($path, $content, $meta)) {
                $success++;
            } else {
                $errors[] = ['path' => $path, 'error' => $this->getLastError()];
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
        // Local storage ne supporte pas les signed URLs
        return null;
    }

    public function writeStream(string $path, $stream, int $size): bool
    {
        $this->ensureConnected();
        $this->validatePath($path);

        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }

        $fp = fopen($fullPath, 'wb');
        if (!$fp) return false;

        $written = 0;
        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) break;
            $written += fwrite($fp, $chunk);
        }
        fclose($fp);

        $this->metrics['writes']++;
        $this->metrics['bytes_written'] += $written;

        return $written === $size;
    }

    public function readStream(string $path)
    {
        $this->ensureConnected();
        $fullPath = $this->rootDir . '/' . $this->cleanPath($path);

        if (!file_exists($fullPath)) return null;

        $this->metrics['reads']++;
        return fopen($fullPath, 'rb');
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

    /**
     * Calculer la taille récursive d'un dossier
     */
    private function dirSize(string $dir): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Supprimer récursivement un dossier
     */
    private function removeDir(string $dir): bool
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        return rmdir($dir);
    }
}
