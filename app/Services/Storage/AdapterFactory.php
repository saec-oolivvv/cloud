<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * Adapter Factory — Crée les adapters selon le type
 * 
 * Pattern Factory pour encapsuler la création des adapters.
 * Supporte: local, s3, sftp, ftp, webdav, gdrive, dropbox, onedrive
 */
class AdapterFactory
{
    private static array $adapters = [
        'local' => LocalAdapter::class,
        's3' => S3Adapter::class,
        'sftp' => SftpAdapter::class,
        'ftp' => FtpAdapter::class,
        'webdav' => WebdavAdapter::class,
        'gdrive' => GdriveAdapter::class,
        'dropbox' => DropboxAdapter::class,
        'onedrive' => OnedriveAdapter::class,
    ];

    /**
     * Créer un adapter à partir d'une config
     * 
     * @param array $config Configuration du provider
     * @return StorageAdapter Instance de l'adapter
     * @throws \RuntimeException Si le type n'est pas supporté
     */
    public static function create(array $config): StorageAdapter
    {
        $type = $config['type'] ?? '';

        if (empty(self::$adapters[$type])) {
            throw new \RuntimeException("Unsupported storage type: {$type}");
        }

        $class = self::$adapters[$type];
        return new $class($config);
    }

    /**
     * Créer un adapter à partir d'un provider ID en DB
     * 
     * @param int $providerId ID du provider dans storage_providers
     * @return StorageAdapter
     */
    public static function fromDatabase(int $providerId): StorageAdapter
    {
        $db = \Saec\Core\Database::getInstance();

        $provider = $db->fetch(
            "SELECT * FROM storage_providers WHERE id = ?",
            [$providerId]
        );

        if (!$provider) {
            throw new \RuntimeException("Provider not found: {$providerId}");
        }

        $config = json_decode($provider['config'], true) ?? [];
        $config['name'] = $provider['name'];
        $config['type'] = $provider['type'];

        return self::create($config);
    }

    /**
     * Lister les types supportés
     */
    public static function getSupportedTypes(): array
    {
        return [
            'local' => [
                'label' => 'Stockage Local',
                'description' => 'Disque dur du serveur',
                'icon' => '💾',
                'fields' => ['root_dir'],
            ],
            's3' => [
                'label' => 'Amazon S3 / Compatible',
                'description' => 'S3, R2, MinIO, DO Spaces, Wasabi, B2, etc.',
                'icon' => '☁️',
                'fields' => ['endpoint', 'access_key', 'secret_key', 'bucket', 'region', 'path_style', 'use_ssl'],
            ],
            'sftp' => [
                'label' => 'SFTP (SSH)',
                'description' => 'Serveur distant via SSH',
                'icon' => '🔐',
                'fields' => ['host', 'port', 'username', 'password', 'private_key', 'root_dir'],
            ],
            'ftp' => [
                'label' => 'FTP/SFTP',
                'description' => 'Serveur FTP classique',
                'icon' => '📁',
                'fields' => ['host', 'port', 'username', 'password', 'root_dir', 'passive', 'ssl'],
            ],
            'webdav' => [
                'label' => 'WebDAV',
                'description' => 'Nextcloud, ownCloud, serveurs WebDAV',
                'icon' => '🌐',
                'fields' => ['endpoint', 'username', 'password', 'root_dir'],
            ],
            'gdrive' => [
                'label' => 'Google Drive',
                'description' => 'Stockage Google Drive',
                'icon' => '🔷',
                'fields' => ['client_id', 'client_secret', 'refresh_token', 'root_dir'],
            ],
            'dropbox' => [
                'label' => 'Dropbox',
                'description' => 'Stockage Dropbox',
                'icon' => '📦',
                'fields' => ['app_key', 'app_secret', 'root_dir'],
            ],
            'onedrive' => [
                'label' => 'OneDrive',
                'description' => 'Microsoft OneDrive',
                'icon' => '🔷',
                'fields' => ['client_id', 'client_secret', 'refresh_token', 'root_dir'],
            ],
        ];
    }

    /**
     * Valider la config d'un provider
     */
    public static function validateConfig(string $type, array $config): array
    {
        $errors = [];
        $types = self::getSupportedTypes();

        if (!isset($types[$type])) {
            return ['Type de provider inconnu'];
        }

        $requiredFields = $types[$type]['fields'] ?? [];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $errors[] = "Le champ '{$field}' est requis";
            }
        }

        // Validations spécifiques
        if ($type === 's3') {
            if (!empty($config['endpoint']) && !filter_var('https://' . $config['endpoint'], FILTER_VALIDATE_URL)) {
                $errors[] = "L'endpoint n'est pas une URL valide";
            }
        }

        if ($type === 'sftp' || $type === 'ftp') {
            if (!empty($config['port']) && ($config['port'] < 1 || $config['port'] > 65535)) {
                $errors[] = "Le port doit être entre 1 et 65535";
            }
        }

        return $errors;
    }
}
