<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * Abstract Storage Adapter — Base class for all adapters
 * 
 * Fournit les fonctionnalités communes:
 * - Chiffrement des credentials
 * - Logging des opérations
 * - Gestion d'erreurs
 * - Validation des paths
 * - Cache mémoire
 */
abstract class AbstractAdapter implements StorageAdapter
{
    protected string $name;
    protected string $type;
    protected array $config;
    protected bool $connected = false;
    protected ?string $lastError = null;
    protected array $metrics = [
        'reads' => 0,
        'writes' => 0,
        'deletes' => 0,
        'bytes_read' => 0,
        'bytes_written' => 0,
        'errors' => 0,
    ];

    /**
     * Chemin racine du stockage local (pour caching)
     */
    protected string $cacheDir = '';

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->name = $config['name'] ?? 'unnamed';
        $this->type = $config['type'] ?? 'unknown';
    }

    /**
     * Initialiser la connexion (implémenté par chaque adapter)
     */
    abstract protected function connect(): void;

    /**
     * Vérifier si la connexion est active
     */
    protected function ensureConnected(): void
    {
        if (!$this->connected) {
            $this->connect();
            $this->connected = true;
        }
    }

    /**
     * Nettoyer un path (supprimer //, ./, ../)
     */
    protected function cleanPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') continue;
            if ($part === '..') {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }
        return implode('/', $parts);
    }

    /**
     * Valider qu'un path est sûr (pas de traversal)
     */
    protected function validatePath(string $path): void
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            throw new \RuntimeException("Invalid path: {$path}");
        }
    }

    /**
     * Logger une opération
     */
    protected function log(string $operation, string $path, ?string $detail = null): void
    {
        $msg = sprintf('[Storage:%s] %s: %s', $this->name, $operation, $path);
        if ($detail) $msg .= " ({$detail})";
        error_log($msg);
    }

    /**
     * Enregistrer une erreur
     */
    protected function recordError(string $operation, string $message): void
    {
        $this->metrics['errors']++;
        $this->lastError = $message;
        $this->log("ERROR:{$operation}", '', $message);
    }

    /**
     * Obtenir les métriques
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Réinitialiser les métriques
     */
    public function resetMetrics(): void
    {
        $this->metrics = [
            'reads' => 0,
            'writes' => 0,
            'deletes' => 0,
            'bytes_read' => 0,
            'bytes_written' => 0,
            'errors' => 0,
        ];
    }

    /**
     * Dernière erreur
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Nom du provider
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Type du provider
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Features supportées par défaut
     */
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
            'streaming' => false,
            'versioning' => false,
            'encryption' => false,
            'compression' => false,
        ];

        return $supported[$feature] ?? false;
    }

    /**
     * Chiffrer des données sensibles
     */
    protected function encrypt(string $data): string
    {
        $key = $this->config['encryption_key'] ?? '';
        if (empty($key)) return $data;

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Déchiffrer des données sensibles
     */
    protected function decrypt(string $data): string
    {
        $key = $this->config['encryption_key'] ?? '';
        if (empty($key)) return $data;

        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $tag = substr($decoded, 16, 16);
        $encrypted = substr($decoded, 32);

        return openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }

    /**
     * Convertir taille en format lisible
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtenir le type MIME d'un fichier par son extension
     */
    protected function guessMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf', 'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip', 'rar' => 'application/x-rar', '7z' => 'application/x-7z',
            'txt' => 'text/plain', 'html' => 'text/html', 'css' => 'text/css',
            'js' => 'application/javascript', 'json' => 'application/json',
            'xml' => 'application/xml', 'csv' => 'text/csv',
            'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4', 'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime', 'wmv' => 'video/x-ms-wmv',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }
}
