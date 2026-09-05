<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * WebDAV Storage Adapter
 * 
 * Compatible avec: Nextcloud, ownCloud, serveurs WebDAV.
 * Utilise curl pour les requetes HTTP WebDAV.
 */
class WebdavAdapter extends AbstractAdapter
{
    private string $endpoint;
    private string $username;
    private string $password;
    private string $rootDir;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->endpoint = rtrim($config['endpoint'] ?? '', '/');
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->rootDir = $config['root_dir'] ?? '';
    }

    protected function connect(): void
    {
        if (empty($this->endpoint)) {
            throw new \RuntimeException("WebDAV endpoint not configured");
        }
        $this->connected = true;
    }

    private function resolveUrl(string $path = ''): string
    {
        $base = rtrim($this->endpoint, '/');
        if (!empty($this->rootDir)) {
            $base .= '/' . ltrim($this->rootDir, '/');
        }
        if (!empty($path)) {
            $base .= '/' . ltrim($path, '/');
        }
        return $base;
    }

    private function request(string $method, string $path, ?string $body = null, array $headers = []): array
    {
        $url = $this->resolveUrl($path);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERPWD => "{$this->username}:{$this->password}",
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADER => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        return [
            'status' => $httpCode,
            'body' => $responseBody,
            'headers' => $this->parseHeaders($responseHeaders),
        ];
    }

    private function parseHeaders(string $raw): array
    {
        $headers = [];
        $lines = explode("\r\n", $raw);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }
        return $headers;
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $this->connect();
            $result = $this->request('PROPFIND', '', '<?xml version="1.0" encoding="utf-8"?><propfind xmlns="DAV:"><prop><displayname/></prop></propfind>', [
                'Depth' => '0',
            ]);
            $latency = (int)((microtime(true) - $start) * 1000);

            if ($result['status'] >= 200 && $result['status'] < 300) {
                return [
                    'success' => true,
                    'message' => "Connected to {$this->endpoint}",
                    'latency_ms' => $latency,
                ];
            }

            return [
                'success' => false,
                'message' => "HTTP {$result['status']}: " . substr($result['body'], 0, 200),
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
            'type' => 'webdav',
            'name' => $this->name,
            'region' => null,
            'endpoint' => $this->endpoint,
            'version' => null,
        ];
    }

    public function list(string $path = ''): array
    {
        $this->ensureConnected();

        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<propfind xmlns="DAV:">'
            . '<prop><displayname/><getcontentlength/><getlastmodified/><resourcetype/></prop>'
            . '</propfind>';

        $result = $this->request('PROPFIND', $path, $body, ['Depth' => '1']);

        if ($result['status'] < 200 || $result['status'] >= 300) {
            return [];
        }

        return $this->parseMultiStatus($result['body'], $path);
    }

    private function parseMultiStatus(string $xml, string $parentPath): array
    {
        $items = [];
        $xmlObj = simplexml_load_string($xml);
        if (!$xmlObj) return [];

        $xmlObj->registerXPathNamespace('d', 'DAV:');
        $responses = $xmlObj->xpath('//d:response');

        if (!$responses) return [];

        foreach ($responses as $response) {
            $href = (string) $response->xpath('d:href')[0];
            $href = urldecode($href);

            $relativePath = $href;
            $parentHref = $this->resolveUrl($parentPath);
            if (strpos($href, $parentHref) === 0) {
                $relativePath = substr($href, strlen($parentHref));
            }
            $relativePath = trim($relativePath, '/');

            if (empty($relativePath)) continue;

            $displayName = (string) $response->xpath('.//d:displayname')[0] ?? basename($relativePath);
            $contentLength = (int) ((string) $response->xpath('.//d:getcontentlength')[0] ?? 0);
            $lastModified = strtotime((string) $response->xpath('.//d:getlastmodified')[0] ?? '') ?: 0;
            $resourceType = $response->xpath('.//d:resourcetype')[0];
            $isCollection = $resourceType && $resourceType->xpath('d:collection');

            $items[] = [
                'name' => $displayName,
                'path' => ltrim($parentPath . '/' . $relativePath, '/'),
                'type' => $isCollection ? 'folder' : 'file',
                'size' => $isCollection ? 0 : $contentLength,
                'modified' => $lastModified,
                'mime' => $isCollection ? null : $this->guessMimeType($displayName),
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
        $result = $this->request('GET', $path);

        if ($result['status'] !== 200) {
            throw new \RuntimeException("Failed to read: {$path} (HTTP {$result['status']})");
        }

        $this->metrics['reads']++;
        $this->metrics['bytes_read'] += strlen($result['body']);
        return $result['body'];
    }

    public function write(string $path, string $content, array $meta = []): bool
    {
        $this->ensureConnected();
        $this->validatePath($path);
        $this->ensureParentDir($path);

        $headers = ['Content-Type' => $meta['mime'] ?? 'application/octet-stream'];
        $result = $this->request('PUT', $path, $content, $headers);

        if ($result['status'] < 200 || $result['status'] >= 300) {
            $this->recordError('write', "HTTP {$result['status']}: {$path}");
            return false;
        }

        $this->metrics['writes']++;
        $this->metrics['bytes_written'] += strlen($content);
        return true;
    }

    public function delete(string $path): bool
    {
        $this->ensureConnected();
        $result = $this->request('DELETE', $path);

        $success = $result['status'] === 204 || $result['status'] === 200;
        if ($success) {
            $this->metrics['deletes']++;
        } else {
            $this->recordError('delete', "HTTP {$result['status']}: {$path}");
        }
        return $success;
    }

    public function mkdir(string $path): bool
    {
        $this->ensureConnected();
        $result = $this->request('MKCOL', $path);
        return $result['status'] === 201 || $result['status'] === 405; // 405 = already exists
    }

    public function rename(string $oldPath, string $newPath): bool
    {
        $this->ensureConnected();
        $result = $this->request('MOVE', $oldPath, null, [
            'Destination' => $this->resolveUrl($newPath),
            'Overwrite' => 'T',
        ]);

        return $result['status'] >= 200 && $result['status'] < 300;
    }

    public function copy(string $source, string $destination): bool
    {
        $this->ensureConnected();
        $result = $this->request('COPY', $source, null, [
            'Destination' => $this->resolveUrl($destination),
            'Overwrite' => 'T',
        ]);

        return $result['status'] >= 200 && $result['status'] < 300;
    }

    public function exists(string $path): bool
    {
        $this->ensureConnected();
        $result = $this->request('HEAD', $path);
        return $result['status'] >= 200 && $result['status'] < 300;
    }

    public function size(string $path): int
    {
        $this->ensureConnected();
        $result = $this->request('HEAD', $path);

        if ($result['status'] < 200 || $result['status'] >= 300) return 0;

        return (int) ($result['headers']['content-length'] ?? 0);
    }

    public function lastModified(string $path): int
    {
        $this->ensureConnected();
        $result = $this->request('HEAD', $path);

        if ($result['status'] < 200 || $result['status'] >= 300) return 0;

        return strtotime($result['headers']['last-modified'] ?? '') ?: 0;
    }

    public function mimeType(string $path): ?string
    {
        $this->ensureConnected();
        $result = $this->request('HEAD', $path);

        if ($result['status'] < 200 || $result['status'] >= 300) return null;

        return $result['headers']['content-type'] ?? null;
    }

    public function usedSpace(): int
    {
        $this->ensureConnected();

        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<propfind xmlns="DAV:"><prop><quota-used-bytes/></prop></propfind>';

        $result = $this->request('PROPFIND', '', $body, ['Depth' => '0']);

        if ($result['status'] < 200 || $result['status'] >= 300) return 0;

        $xml = simplexml_load_string($result['body']);
        if (!$xml) return 0;

        $xml->registerXPathNamespace('d', 'DAV:');
        $quota = $xml->xpath('//d:quota-used-bytes');
        return $quota ? (int) (string) $quota[0] : 0;
    }

    public function freeSpace(): int
    {
        $this->ensureConnected();

        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<propfind xmlns="DAV:"><prop><quota-available-bytes/></prop></propfind>';

        $result = $this->request('PROPFIND', '', $body, ['Depth' => '0']);

        if ($result['status'] < 200 || $result['status'] >= 300) return -1;

        $xml = simplexml_load_string($result['body']);
        if (!$xml) return -1;

        $xml->registerXPathNamespace('d', 'DAV:');
        $quota = $xml->xpath('//d:quota-available-bytes');
        return $quota ? (int) (string) $quota[0] : -1;
    }

    public function totalSpace(): int
    {
        $used = $this->usedSpace();
        $free = $this->freeSpace();
        if ($used < 0 || $free < 0) return -1;
        return $used + $free;
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
        return null; // WebDAV doesn't support signed URLs
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
        if ($dir === '.' || $dir === '/') return;

        if (!$this->exists($dir)) {
            $this->ensureParentDir($dir);
            $this->mkdir($dir);
        }
    }
}
