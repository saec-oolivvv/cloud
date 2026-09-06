<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * Dropbox Storage Adapter
 * 
 * Utilise l'API Dropbox v2 via curl.
 * Requiert: access_token.
 */
class DropboxAdapter extends AbstractAdapter
{
    private string $accessToken;
    private string $refreshToken;
    private string $appKey;
    private string $appSecret;
    private string $rootDir;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->accessToken = $config['access_token'] ?? '';
        $this->refreshToken = $config['refresh_token'] ?? '';
        $this->appKey = $config['app_key'] ?? '';
        $this->appSecret = $config['app_secret'] ?? '';
        $this->rootDir = $config['root_dir'] ?? '';
    }

    protected function connect(): void
    {
        if (empty($this->accessToken) && empty($this->refreshToken)) {
            throw new \RuntimeException("Dropbox non connecté. Utilisez le flow OAuth2.");
        }
        if (empty($this->accessToken) && !empty($this->refreshToken)) {
            $this->refreshAccessToken();
        }
        $this->connected = true;
    }

    public function refreshAccessToken(): bool
    {
        if (empty($this->refreshToken) || empty($this->appKey) || empty($this->appSecret)) {
            throw new \RuntimeException("Refresh token ou credentials manquants");
        }

        $ch = curl_init('https://api.dropbox.com/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'client_id' => $this->appKey,
                'client_secret' => $this->appSecret,
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $data = json_decode($response, true);
            throw new \RuntimeException("Refresh token failed: " . ($data['error_description'] ?? $response));
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'];

        // Persister le nouveau token en DB
        $db = \Saec\Core\Database::getInstance();
        $db->execute(
            "UPDATE storage_providers SET config = ? WHERE name = ?",
            [json_encode(array_merge($this->getConfig(), ['access_token' => $this->accessToken])), $this->name]
        );

        return true;
    }

    private function getConfig(): array
    {
        return [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
            'refresh_token' => $this->refreshToken,
            'access_token' => $this->accessToken,
            'root_dir' => $this->rootDir,
        ];
    }

    private function resolvePath(string $path): string
    {
        $path = ltrim($path, '/');
        if (!empty($this->rootDir)) {
            $path = rtrim($this->rootDir, '/') . '/' . $path;
        }
        return '/' . ltrim($path, '/');
    }

    private function apiRequest(string $endpoint, array $args = [], ?string $body = null, bool $retry = true): array
    {
        $this->connect();

        $url = "https://api.dropboxapi.com/2/{$endpoint}";

        $ch = curl_init($url);
        if ($body !== null) {
            $payload = $body;
        } elseif (!empty($args)) {
            $payload = json_encode($args);
        } else {
            $payload = 'null';
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Auto-refresh sur 401
        if ($httpCode === 401 && $retry && !empty($this->refreshToken)) {
            try {
                $this->refreshAccessToken();
                return $this->apiRequest($endpoint, $args, $body, false);
            } catch (\Throwable $e) {
                // Fall through to return error
            }
        }

        return [
            'status' => $httpCode,
            'body' => json_decode($response, true) ?? $response,
        ];
    }

    private function contentRequest(string $endpoint, string $body): array
    {
        $this->connect();

        $url = "https://content.dropboxapi.com/2/{$endpoint}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/octet-stream',
                'Dropbox-API-Arg: ' . json_encode(['path' => $this->resolvePath($endpoint === 'download' ? '' : '')]),
            ],
            CURLOPT_TIMEOUT => 60,
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => $response,
        ];
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $result = $this->apiRequest('users/get_current_account');
            $latency = (int)((microtime(true) - $start) * 1000);

            if ($result['status'] === 200) {
                $name = $result['body']['name']['display_name'] ?? 'Unknown';
                return [
                    'success' => true,
                    'message' => "Connected as: {$name}",
                    'latency_ms' => $latency,
                ];
            }

            return [
                'success' => false,
                'message' => "HTTP {$result['status']}: " . ($result['body']['error_summary'] ?? ''),
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
            'type' => 'dropbox',
            'name' => $this->name,
            'region' => null,
            'endpoint' => 'api.dropboxapi.com',
            'version' => 'v2',
        ];
    }

    public function list(string $path = ''): array
    {
        $this->ensureConnected();
        $dropboxPath = $this->resolvePath($path);

        $result = $this->apiRequest('files/list_folder', [
            'path' => $dropboxPath,
            'include_media_info' => false,
            'include_deleted' => false,
        ]);

        if ($result['status'] !== 200) return [];

        $items = [];
        foreach ($result['body']['entries'] ?? [] as $entry) {
            $isFolder = $entry['.tag'] === 'folder';
            $name = basename($entry['path_display']);

            $items[] = [
                'name' => $name,
                'path' => ltrim($entry['path_display'], '/'),
                'type' => $isFolder ? 'folder' : 'file',
                'size' => $isFolder ? 0 : (int) ($entry['size'] ?? 0),
                'modified' => isset($entry['server_modified']) ? strtotime($entry['server_modified']) : 0,
                'mime' => $isFolder ? null : $this->guessMimeType($name),
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
        $dropboxPath = $this->resolvePath($path);

        $ch = curl_init('https://content.dropboxapi.com/2/files/download');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Dropbox-API-Arg: ' . json_encode(['path' => $dropboxPath]),
            ],
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
        $dropboxPath = $this->resolvePath($path);

        $args = [
            'path' => $dropboxPath,
            'mode' => 'overwrite',
            'autorename' => false,
        ];

        $ch = curl_init('https://content.dropboxapi.com/2/files/upload');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/octet-stream',
                'Dropbox-API-Arg: ' . json_encode($args),
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => $content,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
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
        $dropboxPath = $this->resolvePath($path);

        $result = $this->apiRequest('files/delete_v2', ['path' => $dropboxPath]);

        if ($result['status'] === 200) {
            $this->metrics['deletes']++;
            return true;
        }

        $this->recordError('delete', "HTTP {$result['status']}: {$path}");
        return false;
    }

    public function mkdir(string $path): bool
    {
        $this->ensureConnected();
        $dropboxPath = $this->resolvePath($path);

        $result = $this->apiRequest('files/create_folder_v2', ['path' => $dropboxPath]);
        return $result['status'] === 200;
    }

    public function rename(string $oldPath, string $newPath): bool
    {
        $this->ensureConnected();
        $old = $this->resolvePath($oldPath);
        $new = $this->resolvePath($newPath);

        $result = $this->apiRequest('files/move_v2', [
            'from_path' => $old,
            'to_path' => $new,
            'autorename' => false,
        ]);

        return $result['status'] === 200;
    }

    public function copy(string $source, string $destination): bool
    {
        $this->ensureConnected();
        $src = $this->resolvePath($source);
        $dst = $this->resolvePath($destination);

        $result = $this->apiRequest('files/copy_v2', [
            'from_path' => $src,
            'to_path' => $dst,
            'autorename' => false,
        ]);

        return $result['status'] === 200;
    }

    public function exists(string $path): bool
    {
        $this->ensureConnected();
        $dropboxPath = $this->resolvePath($path);

        $result = $this->apiRequest('files/get_metadata', [
            'path' => $dropboxPath,
        ]);

        return $result['status'] === 200;
    }

    public function size(string $path): int
    {
        $this->ensureConnected();
        $dropboxPath = $this->resolvePath($path);

        $result = $this->apiRequest('files/get_metadata', [
            'path' => $dropboxPath,
        ]);

        if ($result['status'] !== 200) return 0;
        return (int) ($result['body']['size'] ?? 0);
    }

    public function lastModified(string $path): int
    {
        $this->ensureConnected();
        $dropboxPath = $this->resolvePath($path);

        $result = $this->apiRequest('files/get_metadata', [
            'path' => $dropboxPath,
        ]);

        if ($result['status'] !== 200) return 0;
        return strtotime($result['body']['server_modified'] ?? '') ?: 0;
    }

    public function mimeType(string $path): ?string
    {
        $this->ensureConnected();
        return $this->guessMimeType(basename($path));
    }

    public function usedSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('users/get_space_usage');
        if ($result['status'] !== 200) return 0;
        return (int) ($result['body']['used'] ?? 0);
    }

    public function freeSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('users/get_space_usage');
        if ($result['status'] !== 200) return -1;

        $allocated = $result['body']['allocation'] ?? [];
        if (isset($allocated['allocated'])) {
            return (int) $allocated['allocated'] - (int) ($result['body']['used'] ?? 0);
        }

        return -1;
    }

    public function totalSpace(): int
    {
        $this->ensureConnected();
        $result = $this->apiRequest('users/get_space_usage');
        if ($result['status'] !== 200) return -1;

        $allocated = $result['body']['allocation'] ?? [];
        return (int) ($allocated['allocated'] ?? -1);
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
        $dropboxPath = $this->resolvePath($path);

        $result = $this->apiRequest('sharing/create_shared_link_with_settings', [
            'path' => $dropboxPath,
            'settings' => [
                'requested_visibility' => 'public',
            ],
        ]);

        if ($result['status'] === 200) {
            return $result['body']['url'] ?? null;
        }

        // If link already exists, get it
        if (isset($result['body']['error']['shared_link_already_exists'])) {
            $result = $this->apiRequest('sharing/list_shared_links', [
                'path' => $dropboxPath,
                'direct_only' => true,
            ]);

            if ($result['status'] === 200 && !empty($result['body']['links'])) {
                return $result['body']['links'][0]['url'] ?? null;
            }
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
}
