<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * OneDrive Storage Adapter
 * 
 * Utilise Microsoft Graph API via curl.
 * Requiert: client_id, client_secret, refresh_token.
 */
class OnedriveAdapter extends AbstractAdapter
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
            throw new \RuntimeException("OneDrive credentials not configured");
        }

        $this->refreshAccessToken();
    }

    private function refreshAccessToken(): void
    {
        $ch = curl_init('https://login.microsoftonline.com/common/oauth2/v2.0/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
                'scope' => 'Files.ReadWrite offline_access',
            ]),
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException("Failed to refresh OneDrive access token");
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 60;
    }

    private function apiRequest(string $method, string $endpoint, ?string $body = null, array $headers = []): array
    {
        $this->connect();

        $url = "https://graph.microsoft.com/v1.0{$endpoint}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge([
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ], $headers),
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => json_decode($response, true) ?? $response,
        ];
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $result = $this->apiRequest('GET', '/me');
            $latency = (int)((microtime(true) - $start) * 1000);

            if ($result['status'] === 200) {
                $name = $result['body']['displayName'] ?? 'Unknown';
                return [
                    'success' => true,
                    'message' => "Connected as: {$name}",
                    'latency_ms' => $latency,
                ];
            }

            return [
                'success' => false,
                'message' => "HTTP {$result['status']}: " . ($result['body']['error']['message'] ?? ''),
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
            'type' => 'onedrive',
            'name' => $this->name,
            'region' => null,
            'endpoint' => 'graph.microsoft.com',
            'version' => 'v1.0',
        ];
    }

    public function list(string $path = ''): array
    {
        $this->ensureConnected();
        $endpoint = $this->resolveEndpoint($path);

        $result = $this->apiRequest('GET', "{$endpoint}/children?\$select=name,size,lastModifiedDateTime,file,folder");

        if ($result['status'] !== 200) return [];

        $items = [];
        foreach ($result['body']['value'] ?? [] as $item) {
            $isFolder = isset($item['folder']);

            $items[] = [
                'name' => $item['name'],
                'path' => ltrim($path . '/' . $item['name'], '/'),
                'type' => $isFolder ? 'folder' : 'file',
                'size' => $isFolder ? 0 : (int) ($item['size'] ?? 0),
                'modified' => isset($item['lastModifiedDateTime']) ? strtotime($item['lastModifiedDateTime']) : 0,
                'mime' => $isFolder ? null : ($item['file']['mimeType'] ?? null),
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
        $endpoint = $this->resolveEndpoint($path);

        $ch = curl_init("https://graph.microsoft.com/v1.0{$endpoint}/content");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->accessToken],
            CURLOPT_TIMEOUT => 60,
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException("Failed to read: {$path} (HTTP {$httpCode})");
        }

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
        $endpoint = $this->resolveEndpoint($parentPath);

        $ch = curl_init("https://graph.microsoft.com/v1.0{$endpoint}/{$fileName}:/content");
        curl_setopt_array($ch, [
            CURLOPT_PUT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/octet-stream',
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => $content,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            $this->metrics['writes']++;
            $this->metrics['bytes_written'] += strlen($content);
            return true;
        }

        $this->recordError('write', "HTTP {$httpCode}: {$path}");
        return false;
    }

    public function delete(string $path): bool
    {
        $this->ensureConnected();
        $endpoint = $this->resolveEndpoint($path);

        $result = $this->apiRequest('DELETE', $endpoint);

        if ($result['status'] === 204 || $result['status'] === 200) {
            $this->metrics['deletes']++;
            return true;
        }

        $this->recordError('delete', "HTTP {$result['status']}: {$path}");
        return false;
    }

    public function mkdir(string $path): bool
    {
        $this->ensureConnected();
        $parts = explode('/', $path);
        $folderName = array_pop($parts);
        $parentPath = implode('/', $parts);
        $endpoint = $this->resolveEndpoint($parentPath);

        $result = $this->apiRequest('POST', "{$endpoint}/children", json_encode([
            'name' => $folderName,
            'folder' => new \stdClass(),
            '@microsoft.graph.conflictBehavior' => 'fail',
        ]));

        return $result['status'] === 201;
    }

    public function rename(string $oldPath, string $newPath): bool
    {
        $this->ensureConnected();
        $endpoint = $this->resolveEndpoint($oldPath);
        $newName = basename($newPath);

        $result = $this->apiRequest('PATCH', $endpoint, json_encode([
            'name' => $newName,
        ]));

        return $result['status'] === 200;
    }

    public function copy(string $source, string $destination): bool
    {
        $this->ensureConnected();
        $srcEndpoint = $this->resolveEndpoint($source);
        $destParts = explode('/', $destination);
        $destName = array_pop($destParts);
        $destParentPath = implode('/', $destParts);
        $destEndpoint = $this->resolveEndpoint($destParentPath);

        $result = $this->apiRequest('POST', "{$srcEndpoint}/copy", json_encode([
            'parentReference' => [
                'path' => '/drive/' . $this->rootDir . $destParentPath,
            ],
            'name' => $destName,
        ]));

        return $result['status'] === 202;
    }

    public function exists(string $path): bool
    {
        $this->ensureConnected();
        $endpoint = $this->resolveEndpoint($path);

        $result = $this->apiRequest('GET', $endpoint);
        return $result['status'] === 200;
    }

    public function size(string $path): int
    {
        $this->ensureConnected();
        $endpoint = $this->resolveEndpoint($path);

        $result = $this->apiRequest('GET', "{$endpoint}?\$select=size");
        if ($result['status'] !== 200) return 0;

        return (int) ($result['body']['size'] ?? 0);
    }

    public function lastModified(string $path): int
    {
        $this->ensureConnected();
        $endpoint = $this->resolveEndpoint($path);

        $result = $this->apiRequest('GET', "{$endpoint}?\$select=lastModifiedDateTime");
        if ($result['status'] !== 200) return 0;

        return strtotime($result['body']['lastModifiedDateTime'] ?? '') ?: 0;
    }

    public function mimeType(string $path): ?string
    {
        $this->ensureConnected();
        $endpoint = $this->resolveEndpoint($path);

        $result = $this->apiRequest('GET', "{$endpoint}?\$select=file");
        if ($result['status'] !== 200) return null;

        return $result['body']['file']['mimeType'] ?? null;
    }

    public function usedSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('GET', '/me/drive');
        if ($result['status'] !== 200) return 0;

        return (int) ($result['body']['quota']['used'] ?? 0);
    }

    public function freeSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('GET', '/me/drive');
        if ($result['status'] !== 200) return -1;

        return (int) ($result['body']['quota']['remaining'] ?? -1);
    }

    public function totalSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('GET', '/me/drive');
        if ($result['status'] !== 200) return -1;

        return (int) ($result['body']['quota']['total'] ?? -1);
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
        $this->ensureConnected();
        $endpoint = $this->resolveEndpoint($path);

        $result = $this->apiRequest('POST', "{$endpoint}/createLink", json_encode([
            'type' => 'view',
            'scope' => 'anonymous',
        ]));

        if ($result['status'] === 200 || $result['status'] === 201) {
            return $result['body']['link']['webUrl'] ?? null;
        }

        return null;
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
            'signedUrl' => true,
            'streaming' => true,
            'versioning' => false,
            'encryption' => false,
            'compression' => false,
        ][$feature] ?? false;
    }

    private function resolveEndpoint(string $path): string
    {
        $path = trim($path, '/');
        if (empty($path)) {
            return "/me/drive/root:/{$this->rootDir}";
        }
        return "/me/drive/root:/{$this->rootDir}/{$path}";
    }
}
