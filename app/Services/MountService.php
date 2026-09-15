<?php

declare(strict_types=1);

namespace Saec\Services;

use Saec\Core\Database;
use Saec\Services\Storage\AdapterFactory;
use Saec\Services\Storage\StorageAdapter;

/**
 * Mount Service — Moteur de montage distant
 * 
 * Gère les mounts de dossiers distants:
 * - Browsing (lister, prévisualiser)
 * - Sync (bidirectionnel, push, pull)
 * - Conflict resolution
 */
class MountService
{
    private static ?MountService $instance = null;
    private Database $db;
    private StorageService $storage;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->storage = StorageService::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ═══════════════════════════════════════════════════════════
    // MOUNT CRUD
    // ═══════════════════════════════════════════════════════════

    /**
     * Créer un mount
     */
    public function createMount(array $data): int
    {
        // Vérifier que le provider existe et est actif
        $provider = $this->db->fetch(
            "SELECT * FROM storage_providers WHERE id = ? AND is_active = 1 AND deleted_at IS NULL",
            [$data['provider_id']]
        );

        if (!$provider) {
            throw new \RuntimeException("Provider non trouvé ou inactif");
        }

        // Tester que le remote path existe OU le créer s'il suit le pattern /cloud/tenant_{id}/
        $adapter = $this->storage->getAdapter($data['provider_id']);
        $remotePath = $data['remote_path'] ?? '/';

        // Structure maître: /cloud/tenant_{tenant_id}/
        $tenantRoot = "/cloud/tenant_" . (int) $data['tenant_id'];

        // Si le remote_path commence par le tenantRoot, créer la structure si absente
        if (str_starts_with($remotePath, $tenantRoot)) {
            // Créer le dossier racine tenant
            if (!$adapter->exists($tenantRoot)) {
                $adapter->mkdir($tenantRoot);
            }
            // Créer le sous-dossier demandé
            if (!$adapter->exists($remotePath)) {
                $adapter->mkdir($remotePath);
            }
        } elseif (!$adapter->exists($remotePath)) {
            // Chemin ne suit pas la structure standard et n'existe pas
            throw new \RuntimeException("Le chemin distant n'existe pas: {$remotePath}");
        }

        // Créer le mount
        $mountId = $this->db->insert('storage_mounts', [
            'provider_id' => $data['provider_id'],
            'tenant_id' => $data['tenant_id'],
            'remote_path' => $remotePath,
            'local_alias' => $data['local_alias'] ?? basename($remotePath),
            'mount_type' => $data['mount_type'] ?? 'readwrite',
            'sync_enabled' => $data['sync_enabled'] ?? 0,
            'sync_interval_minutes' => $data['sync_interval_minutes'] ?? 60,
            'is_active' => $data['is_active'] ?? 1,
        ]);

        // Audit
        $this->audit('mount.created', [
            'mount_id' => $mountId,
            'provider_id' => $data['provider_id'],
            'tenant_id' => $data['tenant_id'],
            'remote_path' => $remotePath,
        ]);

        return $mountId;
    }

    /**
     * Mettre à jour un mount
     */
    public function updateMount(int $id, array $data): bool
    {
        $updates = [];
        if (isset($data['local_alias'])) $updates['local_alias'] = $data['local_alias'];
        if (isset($data['mount_type'])) $updates['mount_type'] = $data['mount_type'];
        if (isset($data['sync_enabled'])) $updates['sync_enabled'] = $data['sync_enabled'];
        if (isset($data['sync_interval_minutes'])) $updates['sync_interval_minutes'] = $data['sync_interval_minutes'];
        if (isset($data['is_active'])) $updates['is_active'] = $data['is_active'];

        if (empty($updates)) return true;

        $this->db->execute(
            "UPDATE storage_mounts SET " . implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($updates))) . " WHERE id = ?",
            array_merge(array_values($updates), [$id])
        );

        return true;
    }

    /**
     * Supprimer un mount
     */
    public function deleteMount(int $id): bool
    {
        $this->db->execute("DELETE FROM storage_mounts WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Lister les mounts d'un tenant
     */
    public function listMounts(int $tenantId): array
    {
        return $this->db->fetchAll(
            "SELECT m.*, p.name as provider_name, p.type as provider_type
             FROM storage_mounts m
             LEFT JOIN storage_providers p ON m.provider_id = p.id
             WHERE m.tenant_id = ?
             ORDER BY m.local_alias ASC",
            [$tenantId]
        );
    }

    /**
     * Lister tous les mounts (admin)
     */
    public function listAllMounts(): array
    {
        return $this->db->fetchAll(
            "SELECT m.*, p.name as provider_name, p.type as provider_type, t.name as tenant_name
             FROM storage_mounts m
             LEFT JOIN storage_providers p ON m.provider_id = p.id
             LEFT JOIN tenants t ON m.tenant_id = t.id
             ORDER BY m.created_at DESC"
        );
    }

    /**
     * Obtenir un mount
     */
    public function getMount(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT m.*, p.name as provider_name, p.type as provider_type
             FROM storage_mounts m
             LEFT JOIN storage_providers p ON m.provider_id = p.id
             WHERE m.id = ?",
            [$id]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // REMOTE BROWSING
    // ═══════════════════════════════════════════════════════════

    /**
     * Lister les fichiers/dossiers distants
     */
    public function browse(int $mountId, string $path = ''): array
    {
        $mount = $this->getMount($mountId);
        if (!$mount) {
            throw new \RuntimeException("Mount non trouvé");
        }

        $adapter = $this->storage->getAdapter($mount['provider_id']);
        $fullPath = rtrim($mount['remote_path'], '/') . '/' . $path;

        return $adapter->list($fullPath);
    }

    /**
     * Télécharger un fichier distant
     */
    public function download(int $mountId, string $remotePath): string
    {
        $mount = $this->getMount($mountId);
        if (!$mount) {
            throw new \RuntimeException("Mount non trouvé");
        }

        $adapter = $this->storage->getAdapter($mount['provider_id']);
        $fullPath = rtrim($mount['remote_path'], '/') . '/' . $remotePath;

        return $adapter->read($fullPath);
    }

    /**
     * Uploader un fichier distant (si ReadWrite)
     */
    public function upload(int $mountId, string $remotePath, string $content, array $meta = []): bool
    {
        $mount = $this->getMount($mountId);
        if (!$mount) {
            throw new \RuntimeException("Mount non trouvé");
        }

        if ($mount['mount_type'] === 'readonly') {
            throw new \RuntimeException("Ce mount est en lecture seule");
        }

        $adapter = $this->storage->getAdapter($mount['provider_id']);
        $fullPath = rtrim($mount['remote_path'], '/') . '/' . $remotePath;

        return $adapter->write($fullPath, $content, $meta);
    }

    /**
     * Supprimer un fichier distant (si ReadWrite)
     */
    public function deleteRemote(int $mountId, string $remotePath): bool
    {
        $mount = $this->getMount($mountId);
        if (!$mount) {
            throw new \RuntimeException("Mount non trouvé");
        }

        if ($mount['mount_type'] === 'readonly') {
            throw new \RuntimeException("Ce mount est en lecture seule");
        }

        $adapter = $this->storage->getAdapter($mount['provider_id']);
        $fullPath = rtrim($mount['remote_path'], '/') . '/' . $remotePath;

        return $adapter->delete($fullPath);
    }

    // ═══════════════════════════════════════════════════════════
    // SYNC ENGINE
    // ═══════════════════════════════════════════════════════════

    /**
     * Synchroniser un mount
     */
    public function sync(int $mountId): array
    {
        $mount = $this->getMount($mountId);
        if (!$mount) {
            throw new \RuntimeException("Mount non trouvé");
        }

        // Mettre à jour le status
        $this->db->execute(
            "UPDATE storage_mounts SET last_sync_status = 'syncing' WHERE id = ?",
            [$mountId]
        );

        try {
            $adapter = $this->storage->getAdapter($mount['provider_id']);
            $remotePath = $mount['remote_path'];
            $localDir = $this->getLocalCacheDir($mountId);

            // Lister les fichiers distants
            $remoteFiles = $this->listAllRemote($adapter, $remotePath);

            // Lister les fichiers locaux (cache)
            $localFiles = $this->listAllLocal($localDir);

            // Détecter les changements
            $changes = $this->detectChanges($remoteFiles, $localFiles);

            // Appliquer les changements
            $result = $this->applyChanges($mount, $adapter, $changes);

            // Mettre à jour le status
            $this->db->execute(
                "UPDATE storage_mounts SET 
                    last_sync_at = NOW(), 
                    last_sync_status = 'ok',
                    last_sync_message = ?
                WHERE id = ?",
                [json_encode($result), $mountId]
            );

            return $result;
        } catch (\Throwable $e) {
            $this->db->execute(
                "UPDATE storage_mounts SET 
                    last_sync_status = 'error',
                    last_sync_message = ?
                WHERE id = ?",
                [$e->getMessage(), $mountId]
            );
            throw $e;
        }
    }

    /**
     * Lister tous les fichiers distants récursivement
     */
    private function listAllRemote(StorageAdapter $adapter, string $path): array
    {
        $items = $adapter->list($path);
        $files = [];

        foreach ($items as $item) {
            if ($item['type'] === 'folder') {
                $subPath = rtrim($path, '/') . '/' . $item['name'];
                $files = array_merge($files, $this->listAllRemote($adapter, $subPath));
            } else {
                $files[] = $item;
            }
        }

        return $files;
    }

    /**
     * Lister tous les fichiers locaux récursivement
     */
    private function listAllLocal(string $dir): array
    {
        if (!is_dir($dir)) return [];

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($dir . '/', '', $file->getRealPath());
                $files[] = [
                    'path' => $relativePath,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'checksum' => hash_file('sha256', $file->getRealPath()),
                ];
            }
        }

        return $files;
    }

    /**
     * Détecter les changements
     */
    private function detectChanges(array $remote, array $local): array
    {
        $remoteMap = [];
        foreach ($remote as $file) {
            $remoteMap[$file['path']] = $file;
        }

        $localMap = [];
        foreach ($local as $file) {
            $localMap[$file['path']] = $file;
        }

        $toUpload = [];      // Fichiers locaux pas sur le remote
        $toDownload = [];    // Fichiers distants pas en local
        $toUpdate = [];      // Fichiers modifiés
        $conflicts = [];     // Fichiers modifiés des deux côtés

        // Chercher les fichiers à uploader
        foreach ($localMap as $path => $localFile) {
            if (!isset($remoteMap[$path])) {
                $toUpload[] = $path;
            } elseif ($localFile['modified'] > ($remoteMap[$path]['modified'] ?? 0)) {
                // Local plus récent — vérifier si le remote a aussi changé
                // Pour l'instant: last-write wins
                $toUpdate[] = $path;
            }
        }

        // Chercher les fichiers à télécharger
        foreach ($remoteMap as $path => $remoteFile) {
            if (!isset($localMap[$path])) {
                $toDownload[] = $path;
            } elseif (($remoteFile['modified'] ?? 0) > ($localMap[$path]['modified'] ?? 0)) {
                // Remote plus récent — déjà géré par toUpdate si local aussi modifié
                if (!in_array($path, $toUpdate)) {
                    $toDownload[] = $path;
                }
            }
        }

        return [
            'upload' => $toUpload,
            'download' => $toDownload,
            'update' => $toUpdate,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Appliquer les changements
     */
    private function applyChanges(array $mount, StorageAdapter $adapter, array $changes): array
    {
        $result = ['uploaded' => 0, 'downloaded' => 0, 'updated' => 0, 'errors' => []];
        $localDir = $this->getLocalCacheDir($mount['id']);
        $remoteBase = rtrim($mount['remote_path'], '/');

        // Uploader les fichiers locaux
        foreach ($changes['upload'] as $path) {
            try {
                $localPath = $localDir . '/' . $path;
                $content = file_get_contents($localPath);
                $adapter->write($remoteBase . '/' . $path, $content);
                $result['uploaded']++;
            } catch (\Throwable $e) {
                $result['errors'][] = ['path' => $path, 'error' => $e->getMessage()];
            }
        }

        // Télécharger les fichiers distants
        foreach ($changes['download'] as $path) {
            try {
                $content = $adapter->read($remoteBase . '/' . $path);
                $localPath = $localDir . '/' . $path;
                $dir = dirname($localPath);
                if (!is_dir($dir)) mkdir($dir, 0770, true);
                file_put_contents($localPath, $content);
                $result['downloaded']++;
            } catch (\Throwable $e) {
                $result['errors'][] = ['path' => $path, 'error' => $e->getMessage()];
            }
        }

        // Mettre à jour les fichiers modifiés
        foreach ($changes['update'] as $path) {
            try {
                // Local est plus récent → upload
                $localPath = $localDir . '/' . $path;
                $content = file_get_contents($localPath);
                $adapter->write($remoteBase . '/' . $path, $content);
                $result['updated']++;
            } catch (\Throwable $e) {
                $result['errors'][] = ['path' => $path, 'error' => $e->getMessage()];
            }
        }

        return $result;
    }

    /**
     * Obtenir le répertoire cache local pour un mount
     */
    private function getLocalCacheDir(int $mountId): string
    {
        $dir = dirname(__DIR__, 2) . "/storage/mounts/{$mountId}";
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        return $dir;
    }

    // ═══════════════════════════════════════════════════════════
    // STATUS
    // ═══════════════════════════════════════════════════════════

    /**
     * Obtenir le status d'un mount
     */
    public function getStatus(int $mountId): array
    {
        $mount = $this->getMount($mountId);
        if (!$mount) return [];

        $adapter = $this->storage->getAdapter($mount['provider_id']);
        $connection = $adapter->testConnection();

        $cacheDir = $this->getLocalCacheDir($mountId);
        $localFileCount = count($this->listAllLocal($cacheDir));

        return [
            'mount' => $mount,
            'connection' => $connection,
            'local_file_count' => $localFileCount,
            'last_sync' => $mount['last_sync_at'],
            'sync_status' => $mount['last_sync_status'],
            'sync_message' => $mount['last_sync_message'],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    private function audit(string $action, array $metadata = []): void
    {
        try {
            $this->db->insert('audit_logs', [
                'tenant_id' => $_SESSION['tenant_id'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'metadata' => json_encode($metadata),
            ]);
        } catch (\Throwable $e) {
            error_log("[MountService] Audit failed: {$e->getMessage()}");
        }
    }
}
