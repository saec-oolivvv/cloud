<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * Google Drive Storage Adapter
 * 
 * Utilise l'API Google Drive v3 via curl (sans SDK externe).
 * Requiert: client_id, client_secret, refresh_token.
 */
class GdriveAdapter extends AbstractAdapter
{
    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $rootDir;
    private ?string $accessToken;
    private int $tokenExpiry;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->refreshToken = $config['refresh_token'] ?? '';
        $this->rootDir = $config['root_dir'] ?? 'root';
        $this->accessToken = null;
        $this->tokenExpiry = 0;
    }

    protected function connect(): void
    {
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return;
        }

        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->refreshToken)) {
            throw new \RuntimeException("Google Drive credentials not configured");
        }

        $this->refreshAccessToken();
    }

    private function refreshAccessToken(): void
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ]),
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException("Failed to refresh Google Drive access token");
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 60;
    }

    private function apiRequest(string $method, string $endpoint, ?array $data = null, ?array $queryParams = []): array
    {
        $this->connect();

        $url = "https://www.googleapis.com/drive/v3/{$endpoint}";
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => json_decode($response, true) ?? $response,
        ];
    }

    private function getFileByName(string $parentId, string $name): ?array
    {
        $result = $this->apiRequest('GET', 'files', null, [
            'q' => "'{$parentId}' in parents and name = '" . addslashes($name) . "' and trashed = false",
            'fields' => 'files(id, name, mimeType, size, modifiedTime)',
        ]);

        $files = $result['body']['files'] ?? [];
        return $files[0] ?? null;
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $this->connect();
            $result = $this->apiRequest('GET', 'about', null, ['fields' => 'user']);
            $latency = (int)((microtime(true) - $start) * 1000);

            if ($result['status'] === 200) {
                $user = $result['body']['user']['displayName'] ?? 'Unknown';
                return [
                    'success' => true,
                    'message' => "Connected as: {$user}",
                    'latency_ms' => $latency,
                ];
            }

            return [
                'success' => false,
                'message' => "HTTP {$result['status']}",
                'latency_ms' => $latency,
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
            'type' => 'gdrive',
            'name' => $this->name,
            'region' => null,
            'endpoint' => 'googleapis.com',
            'version' => 'v3',
        ];
    }

    public function list(string $path = ''): array
    {
        $this->ensureConnected();
        $parentId = $this->resolveFolder($path);

        $result = $this->apiRequest('GET', 'files', null, [
            'q' => "'{$parentId}' in parents and trashed = false",
            'fields' => 'files(id, name, mimeType, size, modifiedTime)',
            'orderBy' => 'name',
        ]);

        if ($result['status'] !== 200) return [];

        $items = [];
        foreach ($result['body']['files'] ?? [] as $file) {
            $isFolder = $file['mimeType'] === 'application/vnd.google-apps.folder';

            $items[] = [
                'name' => $file['name'],
                'path' => ltrim($path . '/' . $file['name'], '/'),
                'type' => $isFolder ? 'folder' : 'file',
                'size' => $isFolder ? 0 : (int) ($file['size'] ?? 0),
                'modified' => isset($file['modifiedTime']) ? strtotime($file['modifiedTime']) : 0,
                'mime' => $isFolder ? null : ($file['mimeType'] ?? null),
            ];
        }

        $this->metrics['reads']++;
        return $items;
    }

    public function read(string $path): string
    {
        $this->ensureConnected();
        $file = $this->resolveFile($path);
        if (!$file) throw new \RuntimeException("File not found: {$path}");

        $ch = curl_init("https://www.googleapis.com/drive/v3/files/{$file['id']}?alt=media");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->accessToken],
            CURLOPT_TIMEOUT => 60,
        ]);

        $content = curl_exec($ch);
        curl_close($ch);

        $this->metrics['reads']++;
        $this->metrics['bytes_read'] += strlen($content);
        return $content;
    }

    public function write(string $path, string $content, array $meta = []): bool
    {
        $this->ensureConnected();
        $this->validatePath($path);

        $parts = explode('/', $path);
        $fileName = array_pop($parts);
        $parentPath = implode('/', $parts);
        $parentId = $this->resolveFolder($parentPath);

        // Check if file exists
        $existing = $this->getFileByName($parentId, $fileName);

        if ($existing) {
            // Update existing file
            $ch = curl_init("https://www.googleapis.com/upload/drive/v3/files/{$existing['id']}?uploadType=multipart");
            $boundary = uniqid();
            $body = "--{$boundary}\r\n"
                . "Content-Type: application/json\r\n\r\n"
                . json_encode(['name' => $fileName]) . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: " . ($meta['mime'] ?? 'application/octet-stream') . "\r\n\r\n"
                . $content . "\r\n--{$boundary}--";

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: multipart/related; boundary=' . $boundary,
                ],
                CURLOPT_RETURNTRANSFER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $success = $httpCode === 200;
        } else {
            // Create new file
            $metadata = [
                'name' => $fileName,
                'parents' => [$parentId],
            ];

            $boundary = uniqid();
            $body = "--{$boundary}\r\n"
                . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
                . json_encode($metadata) . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: " . ($meta['mime'] ?? 'application/octet-stream') . "\r\n\r\n"
                . $content . "\r\n--{$boundary}--";

            $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: multipart/related; boundary=' . $boundary,
                ],
                CURLOPT_RETURNTRANSFER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $success = $httpCode === 200;
        }

        if ($success) {
            $this->metrics['writes']++;
            $this->metrics['bytes_written'] += strlen($content);
        } else {
            $this->recordError('write', "Failed: {$path}");
        }

        return $success;
    }

    public function delete(string $path): bool
    {
        $this->ensureConnected();
        $file = $this->resolveFile($path);
        if (!$file) return true;

        $result = $this->apiRequest('DELETE', "files/{$file['id']}");
        if ($result['status'] === 204 || $result['status'] === 200) {
            $this->metrics['deletes']++;
            return true;
        }

        $this->recordError('delete', "Failed: {$path}");
        return false;
    }

    public function mkdir(string $path): bool
    {
        $this->ensureConnected();
        $parts = explode('/', $path);
        $folderName = array_pop($parts);
        $parentPath = implode('/', $parts);
        $parentId = $this->resolveFolder($parentPath);

        $result = $this->apiRequest('POST', 'files', [
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        return $result['status'] === 200;
    }

    public function rename(string $oldPath, string $newPath): bool
    {
        $this->ensureConnected();
        $file = $this->resolveFile($oldPath);
        if (!$file) return false;

        $newName = basename($newPath);
        $result = $this->apiRequest('PATCH', "files/{$file['id']}", ['name' => $newName]);
        return $result['status'] === 200;
    }

    public function copy(string $source, string $destination): bool
    {
        $this->ensureConnected();
        $file = $this->resolveFile($source);
        if (!$file) return false;

        $destParts = explode('/', $destination);
        $destName = array_pop($destParts);
        $destParentPath = implode('/', $destParts);
        $destParent = $this->resolveFolder($destParentPath);

        $result = $this->apiRequest('POST', "files/{$file['id']}/copy", [
            'name' => $destName,
            'parents' => [$destParent],
        ]);

        return $result['status'] === 200;
    }

    public function exists(string $path): bool
    {
        $this->ensureConnected();
        return $this->resolveFile($path) !== null;
    }

    public function size(string $path): int
    {
        $this->ensureConnected();
        $file = $this->resolveFile($path);
        return $file ? (int) ($file['size'] ?? 0) : 0;
    }

    public function lastModified(string $path): int
    {
        $this->ensureConnected();
        $file = $this->resolveFile($path);
        return $file ? strtotime($file['modifiedTime'] ?? '') : 0;
    }

    public function mimeType(string $path): ?string
    {
        $this->ensureConnected();
        $file = $this->resolveFile($path);
        return $file['mimeType'] ?? null;
    }

    public function usedSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('GET', 'about', null, ['fields' => 'storageQuota']);
        if ($result['status'] !== 200) return 0;
        return (int) ($result['body']['storageQuota']['usage'] ?? 0);
    }

    public function freeSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('GET', 'about', null, ['fields' => 'storageQuota']);
        if ($result['status'] !== 200) return -1;
        return (int) ($result['body']['storageQuota']['limit'] ?? -1);
    }

    public function totalSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('GET', 'about', null, ['fields' => 'storageQuota']);
        if ($result['status'] !== 200) return -1;
        return (int) ($result['body']['storageQuota']['limit'] ?? -1);
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
        return null; // Google Drive doesn't support simple signed URLs
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
        return [
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
        ][$feature] ?? false;
    }

    private function resolveFolder(string $path): string
    {
        $path = trim($path, '/');
        if (empty($path)) return $this->rootDir;

        $parts = explode('/', $path);
        $currentId = $this->rootDir;

        foreach ($parts as $part) {
            $file = $this->getFileByName($currentId, $part);
            if (!$file) {
                $result = $this->apiRequest('POST', 'files', [
                    'name' => $part,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$currentId],
                ]);
                if ($result['status'] !== 200) throw new \RuntimeException("Cannot create folder: {$part}");
                $currentId = $result['body']['id'];
            } else {
                $currentId = $file['id'];
            }
        }

        return $currentId;
    }

    private function resolveFile(string $path): ?array
    {
        $parts = explode('/', $path);
        $fileName = array_pop($parts);
        $parentPath = implode('/', $parts);
        $parentId = $this->resolveFolder($parentPath);
        return $this->getFileByName($parentId, $fileName);
    }
}
