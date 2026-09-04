<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * S3 Storage Adapter
 * 
 * Compatible avec: Amazon S3, Cloudflare R2, MinIO, DigitalOcean Spaces,
 * Wasabi, Backblaze B2, Scaleway, OVH, Linode, Vultr, Hetzner.
 * 
 * Utilise l'API S3 native via curl (sans SDK externe).
 */
class S3Adapter extends AbstractAdapter
{
    private string $endpoint;
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $region;
    private bool $pathStyle;
    private bool $useSSL;
    private int $port;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->endpoint = $config['endpoint'] ?? 's3.amazonaws.com';
        $this->accessKey = $config['access_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->bucket = $config['bucket'] ?? '';
        $this->region = $config['region'] ?? 'us-east-1';
        $this->pathStyle = $config['path_style'] ?? false;
        $this->useSSL = $config['use_ssl'] ?? true;
        $this->port = $config['port'] ?? ($this->useSSL ? 443 : 80);
    }

    protected function connect(): void
    {
        if (empty($this->accessKey) || empty($this->secretKey)) {
            throw new \RuntimeException("S3 credentials not configured");
        }
        if (empty($this->bucket)) {
            throw new \RuntimeException("S3 bucket not configured");
        }
        $this->connected = true;
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $this->connect();
            $result = $this->s3Request('GET', '/');
            $latency = (int)((microtime(true) - $start) * 1000);

            if ($result['status'] === 200) {
                return [
                    'success' => true,
                    'message' => 'Connected to bucket: ' . $this->bucket,
                    'latency_ms' => $latency,
                    'details' => [
                        'bucket' => $this->bucket,
                        'region' => $this->region,
                        'endpoint' => $this->endpoint,
                        'path_style' => $this->pathStyle,
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => 'HTTP ' . $result['status'] . ': ' . substr($result['body'], 0, 200),
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
            'type' => 's3',
            'name' => $this->name,
            'region' => $this->region,
            'endpoint' => $this->endpoint,
            'version' => null,
            'bucket' => $this->bucket,
        ];
    }

    public function list(string $path = ''): array
    {
        $this->ensureConnected();
        $prefix = rtrim($path, '/') . '/';

        $result = $this->s3Request('GET', '/', [
            'prefix' => $prefix,
            'delimiter' => '/',
        ]);

        if ($result['status'] !== 200) {
            $this->recordError('list', "HTTP {$result['status']}");
            return [];
        }

        $items = [];
        $xml = simplexml_load_string($result['body']);
        if (!$xml) return [];

        // CommonPrefixes (dossiers)
        if (isset($xml->CommonPrefixes)) {
            foreach ($xml->CommonPrefixes as $prefix) {
                $name = basename(rtrim((string)$prefix->Prefix, '/'));
                $items[] = [
                    'name' => $name,
                    'path' => ltrim((string)$prefix->Prefix, '/'),
                    'type' => 'folder',
                    'size' => 0,
                    'modified' => 0,
                    'mime' => null,
                ];
            }
        }

        // Contents (fichiers)
        if (isset($xml->Contents)) {
            foreach ($xml->Contents as $obj) {
                $key = (string)$obj->Key;
                if ($key === rtrim($prefix, '/')) continue; // Skip folder marker

                $items[] = [
                    'name' => basename($key),
                    'path' => $key,
                    'type' => 'file',
                    'size' => (int)$obj->Size,
                    'modified' => strtotime((string)$obj->LastModified),
                    'mime' => $this->guessMimeType(basename($key)),
                ];
            }
        }

        // Trier
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

        $result = $this->s3Request('GET', '/' . $this->cleanPath($path));

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

        $headers = [
            'Content-Type' => $meta['mime'] ?? $this->guessMimeType(basename($path)),
            'Content-Length' => strlen($content),
        ];

        // Metadata S3
        if (isset($meta['cache_control'])) {
            $headers['Cache-Control'] = $meta['cache_control'];
        }

        $result = $this->s3Request('PUT', '/' . $this->cleanPath($path), null, $headers, $content);

        if ($result['status'] !== 200) {
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

        $result = $this->s3Request('DELETE', '/' . $this->cleanPath($path));

        if ($result['status'] !== 204 && $result['status'] !== 404) {
            $this->recordError('delete', "HTTP {$result['status']}: {$path}");
            return false;
        }

        $this->metrics['deletes']++;
        return true;
    }

    public function mkdir(string $path): bool
    {
        // S3 n'a pas de concept de dossier — on crée un objet vide avec /
        $this->ensureConnected();

        $key = rtrim($this->cleanPath($path), '/') . '/';
        $result = $this->s3Request('PUT', '/' . $key, null, [
            'Content-Type' => 'application/x-directory',
            'Content-Length' => '0',
        ], '');

        return $result['status'] === 200;
    }

    public function rename(string $oldPath, string $newPath): bool
    {
        if (!$this->copy($oldPath, $newPath)) return false;
        return $this->delete($oldPath);
    }

    public function copy(string $source, string $destination): bool
    {
        $this->ensureConnected();

        $srcKey = '/' . $this->cleanPath($source);
        $dstKey = '/' . $this->cleanPath($destination);

        $headers = [
            'x-amz-copy-source' => $this->getBucketUrl() . $srcKey,
        ];

        $result = $this->s3Request('PUT', $dstKey, null, $headers);

        return $result['status'] === 200;
    }

    public function exists(string $path): bool
    {
        $this->ensureConnected();

        $result = $this->s3Request('HEAD', '/' . $this->cleanPath($path));
        return $result['status'] === 200;
    }

    public function size(string $path): int
    {
        $this->ensureConnected();

        $result = $this->s3Request('HEAD', '/' . $this->cleanPath($path));

        if ($result['status'] !== 200) return 0;

        $contentLength = $result['headers']['content-length'] ?? '0';
        return (int) $contentLength;
    }

    public function lastModified(string $path): int
    {
        $this->ensureConnected();

        $result = $this->s3Request('HEAD', '/' . $this->cleanPath($path));

        if ($result['status'] !== 200) return 0;

        $lastModified = $result['headers']['last-modified'] ?? '';
        return $lastModified ? strtotime($lastModified) : 0;
    }

    public function mimeType(string $path): ?string
    {
        $this->ensureConnected();

        $result = $this->s3Request('HEAD', '/' . $this->cleanPath($path));

        if ($result['status'] !== 200) return null;

        return $result['headers']['content-type'] ?? null;
    }

    public function usedSpace(): int
    {
        $this->ensureConnected();

        $result = $this->s3Request('GET', '/', ['versions' => '']);

        if ($result['status'] !== 200) return 0;

        $xml = simplexml_load_string($result['body']);
        if (!$xml || !isset($xml->Version)) return 0;

        $total = 0;
        foreach ($xml->Version as $version) {
            $total += (int)$version->Size;
        }

        return $total;
    }

    public function freeSpace(): int
    {
        return -1; // Inconnu pour S3
    }

    public function totalSpace(): int
    {
        return -1; // Inconnu pour S3
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
        $this->ensureConnected();

        $key = $this->cleanPath($path);
        $expires = time() + $expiresInSecondes;

        $stringToSign = "GET\n\n\n{$expires}\n/" . $this->bucket . "/" . $key;
        $signature = hash_hmac('sha1', $stringToSign, $this->secretKey, true);
        $encodedSignature = urlencode(base64_encode($signature));

        $protocol = $this->useSSL ? 'https' : 'http';

        if ($this->pathStyle) {
            return "{$protocol}://{$this->endpoint}:{$this->port}/{$this->bucket}/{$key}"
                . "?AWSAccessKeyId={$this->accessKey}"
                . "&Expires={$expires}"
                . "&Signature={$encodedSignature}";
        }

        return "{$protocol}://{$this->bucket}.{$this->endpoint}:{$this->port}/{$key}"
            . "?AWSAccessKeyId={$this->accessKey}"
            . "&Expires={$expires}"
            . "&Signature={$encodedSignature}";
    }

    public function writeStream(string $path, $stream, int $size): bool
    {
        $this->ensureConnected();
        $this->validatePath($path);

        $content = stream_get_contents($stream);
        return $this->write($path, $content);
    }

    public function readStream(string $path)
    {
        $this->ensureConnected();

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
            'signedUrl' => true,
            'streaming' => true,
            'versioning' => true,
            'encryption' => false,
            'compression' => false,
        ];

        return $supported[$feature] ?? false;
    }

    // ═══════════════════════════════════════════════════════════
    // S3 API Implementation
    // ═══════════════════════════════════════════════════════════

    /**
     * Exécuter une requête S3
     */
    private function s3Request(string $method, string $uri, array $queryParams = [], array $headers = [], ?string $body = null): array
    {
        $url = $this->getBucketUrl() . $uri;

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        // AWS Signature V4
        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');
        $amzHeaders = [];

        // Header Content-Type par défaut
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/octet-stream';
        }

        if (!empty($body)) {
            $headers['Content-MD5'] = base64_encode(md5($body, true));
        }

        // Trier les headers
        ksort($headers);

        // Construire le canonical headers
        $canonicalHeaders = '';
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            if (str_starts_with($lowerKey, 'x-amz-')) {
                $amzHeaders[$lowerKey] = $value;
            }
            $canonicalHeaders .= strtolower($key) . ':' . trim($value) . "\n";
        }

        // Canonical query string
        $canonicalQueryString = '';
        if (!empty($queryParams)) {
            ksort($queryParams);
            $pairs = [];
            foreach ($queryParams as $key => $value) {
                $pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
            $canonicalQueryString = implode('&', $pairs);
        }

        // Payload hash
        $payloadHash = hash('sha256', $body ?? '');

        // Canonical request
        $canonicalRequest = implode("\n", [
            $method,
            $uri,
            $canonicalQueryString,
            $canonicalHeaders,
            implode(';', array_keys($amzHeaders)),
            $payloadHash,
        ]);

        // String to sign
        $scope = "{$dateShort}/{$this->region}/s3/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $date,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        // Signing key
        $kDate = hash_hmac('sha256', $dateShort, "AWS4{$this->secretKey}", true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        // Signature
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // Authorization header
        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$scope}, "
            . "SignedHeaders=" . implode(';', array_map('strtolower', array_keys($amzHeaders))) . ", "
            . "Signature={$signature}";

        // Headers finaux
        $requestHeaders = [
            "host: {$this->getHost()}",
            "date: {$date}",
            "authorization: {$authHeader}",
        ];

        foreach ($headers as $key => $value) {
            $requestHeaders[] = strtolower($key) . ': ' . $value;
        }

        foreach ($amzHeaders as $key => $value) {
            $requestHeaders[] = "{$key}: {$value}";
        }

        // cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($body !== null && $method !== 'GET' && $method !== 'HEAD') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        curl_close($ch);

        // Parser les response headers
        $parsedHeaders = [];
        if ($response !== false) {
            // Extraire Content-Type et Content-Length
            if (preg_match('/Content-Type:\s*(.+)/i', $responseHeaders, $m)) {
                $parsedHeaders['content-type'] = trim($m[1]);
            }
        }

        return [
            'status' => $httpCode,
            'body' => $response ?: '',
            'headers' => $parsedHeaders,
        ];
    }

    /**
     * URL du bucket
     */
    private function getBucketUrl(): string
    {
        $protocol = $this->useSSL ? 'https' : 'http';

        if ($this->pathStyle) {
            return "{$protocol}://{$this->endpoint}:{$this->port}";
        }

        return "{$protocol}://{$this->bucket}.{$this->endpoint}:{$this->port}";
    }

    /**
     * Host (pour le header Host)
     */
    private function getHost(): string
    {
        if ($this->pathStyle) {
            return $this->endpoint;
        }
        return "{$this->bucket}.{$this->endpoint}";
    }

    /**
     * Obtenir les buckets accessibles
     */
    public function listBuckets(): array
    {
        $this->ensureConnected();

        $result = $this->s3Request('GET', '/');
        if ($result['status'] !== 200) {
            return [];
        }

        $xml = simplexml_load_string($result['body']);
        if (!$xml || !isset($xml->Buckets->Bucket)) {
            return [];
        }

        $buckets = [];
        foreach ($xml->Buckets->Bucket as $bucket) {
            $buckets[] = [
                'name' => (string)$bucket->Name,
                'created' => (string)$bucket->CreationDate,
            ];
        }

        return $buckets;
    }
}
