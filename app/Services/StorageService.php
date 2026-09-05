<?php

declare(strict_types=1);

namespace Saec\Services;

use Saec\Core\Database;
use Saec\Services\Storage\AdapterFactory;
use Saec\Services\Storage\StorageAdapter;

/**
 * Storage Service — Orchestrateur principal
 * 
 * Point d'entrée unique pour toutes les opérations de storage.
 * Gère: providers, backups, mounts, quotas, métriques.
 */
class StorageService
{
    private static ?StorageService $instance = null;
    private Database $db;
    private array $providers = [];

    private function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ═══════════════════════════════════════════════════════════
    // PROVIDERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Lister tous les providers actifs
     */
    public function listProviders(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, type, is_active, is_default, last_sync_at, last_error, created_at 
             FROM storage_providers WHERE deleted_at IS NULL ORDER BY is_default DESC, name ASC"
        );
    }

    /**
     * Obtenir un provider
     */
    public function getProvider(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM storage_providers WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
    }

    /**
     * Créer un provider
     */
    public function createProvider(array $data): int
    {
        $config = $data['config'] ?? [];
        $config['encryption_key'] = $data['encryption_key'] ?? '';

        // Valider
        $errors = AdapterFactory::validateConfig($data['type'], $config);
        if (!empty($errors)) {
            throw new \RuntimeException(implode(', ', $errors));
        }

        // Chiffrer les credentials
        if (!empty($config['encryption_key'])) {
            foreach (['access_key', 'secret_key', 'password', 'private_key', 'client_secret', 'refresh_token', 'access_token'] as $field) {
                if (!empty($config[$field])) {
                    $config[$field] = $this->encryptConfig($config[$field], $config['encryption_key']);
                }
            }
        }

        // Tester la connexion (optionnel, ne bloque pas la création)
        try {
            $adapter = AdapterFactory::create($data);
            $testResult = $adapter->testConnection();
        } catch (\Throwable $e) {
            $testResult = ['success' => false, 'message' => $e->getMessage()];
        }

        // Si default, désactiver les autres defaults
        if (!empty($data['is_default'])) {
            $this->db->execute(
                "UPDATE storage_providers SET is_default = 0 WHERE is_default = 1"
            );
        }

        $providerId = $this->db->insert('storage_providers', [
            'name' => $data['name'],
            'type' => $data['type'],
            'config' => json_encode($config),
            'is_active' => $data['is_active'] ?? 1,
            'is_default' => $data['is_default'] ?? 0,
            'created_by' => $data['created_by'] ?? null,
            'last_error' => $testResult['success'] ? null : $testResult['message'] ?? null,
        ]);

        // Audit log
        $this->audit('storage.provider.created', [
            'provider_id' => $providerId,
            'type' => $data['type'],
            'name' => $data['name'],
        ]);

        return $providerId;
    }

    /**
     * Mettre à jour un provider
     */
    public function updateProvider(int $id, array $data): bool
    {
        $existing = $this->getProvider($id);
        if (!$existing) {
            throw new \RuntimeException("Provider non trouvé");
        }

        $updates = [];
        if (isset($data['name'])) $updates['name'] = $data['name'];
        if (isset($data['is_active'])) $updates['is_active'] = $data['is_active'];
        if (isset($data['is_default'])) $updates['is_default'] = $data['is_default'];

        // Si config fournie, mettre à jour
        if (isset($data['config'])) {
            $config = $data['config'];
            $config['encryption_key'] = $data['encryption_key'] ?? '';

            $errors = AdapterFactory::validateConfig($existing['type'], $config);
            if (!empty($errors)) {
                throw new \RuntimeException(implode(', ', $errors));
            }

            $updates['config'] = json_encode($config);
        }

        if (!empty($updates)) {
            $this->db->execute(
                "UPDATE storage_providers SET " . implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($updates))) . " WHERE id = ?",
                array_merge(array_values($updates), [$id])
            );
        }

        // Si default, désactiver les autres
        if (!empty($data['is_default'])) {
            $this->db->execute(
                "UPDATE storage_providers SET is_default = 0 WHERE id != ? AND is_default = 1",
                [$id]
            );
        }

        return true;
    }

    /**
     * Supprimer un provider (soft delete)
     */
    public function deleteProvider(int $id): bool
    {
        $this->db->execute(
            "UPDATE storage_providers SET deleted_at = NOW() WHERE id = ?",
            [$id]
        );

        $this->audit('storage.provider.deleted', ['provider_id' => $id]);

        return true;
    }

    /**
     * Tester la connexion d'un provider
     */
    public function testProvider(int $id): array
    {
        $adapter = AdapterFactory::fromDatabase($id);
        $result = $adapter->testConnection();

        // Mettre à jour last_error
        $this->db->execute(
            "UPDATE storage_providers SET last_error = ? WHERE id = ?",
            [$result['success'] ? null : $result['message'], $id]
        );

        return $result;
    }

    /**
     * Obtenir l'adapter pour un provider
     */
    public function getAdapter(int $providerId): StorageAdapter
    {
        if (!isset($this->providers[$providerId])) {
            $this->providers[$providerId] = AdapterFactory::fromDatabase($providerId);
        }
        return $this->providers[$providerId];
    }

    /**
     * Obtenir le provider par défaut
     */
    public function getDefaultProvider(): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM storage_providers WHERE is_default = 1 AND deleted_at IS NULL LIMIT 1"
        );
    }

    // ═══════════════════════════════════════════════════════════
    // STORAGE INFO
    // ═══════════════════════════════════════════════════════════

    /**
     * Statistiques globales de storage
     */
    public function getStats(): array
    {
        $providers = $this->listProviders();
        $activeProviders = array_filter($providers, fn($p) => $p['is_active']);

        $totalProviders = count($providers);
        $activeCount = count($activeProviders);

        // Espace total utilisé (estimation)
        $totalUsed = 0;
        $totalBackups = $this->db->fetch("SELECT COUNT(*) as c FROM storage_backups");
        $totalMounts = $this->db->fetch("SELECT COUNT(*) as c FROM storage_mounts");

        return [
            'total_providers' => $totalProviders,
            'active_providers' => $activeCount,
            'total_backups' => (int)($totalBackups['c'] ?? 0),
            'total_mounts' => (int)($totalMounts['c'] ?? 0),
            'total_used_bytes' => $totalUsed,
            'providers' => $providers,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // TENANT STORAGE
    // ═══════════════════════════════════════════════════════════

    /**
     * Obtenir le storage utilisé par un tenant
     */
    public function getTenantUsage(int $tenantId): array
    {
        $usage = $this->db->fetch(
            "SELECT COALESCE(SUM(size), 0) as used 
             FROM files WHERE tenant_id = ? AND deleted_at IS NULL",
            [$tenantId]
        );

        $tenant = $this->db->fetch(
            "SELECT storage_quota FROM tenants WHERE id = ?",
            [$tenantId]
        );

        $fileCount = $this->db->fetch(
            "SELECT COUNT(*) as c FROM files WHERE tenant_id = ? AND deleted_at IS NULL",
            [$tenantId]
        );

        $quota = (int)($tenant['storage_quota'] ?? 0);

        return [
            'used' => (int)($usage['used'] ?? 0),
            'quota' => $quota,
            'percentage' => $quota > 0 ? round((($usage['used'] ?? 0) / $quota) * 100, 2) : 0,
            'file_count' => (int)($fileCount['c'] ?? 0),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // QUOTA CHECK
    // ═══════════════════════════════════════════════════════════

    /**
     * Vérifier si un tenant peut uploader
     */
    public function canUpload(int $tenantId, int $fileSize): array
    {
        $usage = $this->getTenantUsage($tenantId);

        if ($usage['quota'] > 0 && ($usage['used'] + $fileSize) > $usage['quota']) {
            return [
                'allowed' => false,
                'reason' => "Quota de stockage dépassé ({$usage['used']} / {$usage['quota']} octets)",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Chiffrer une valeur de config
     */
    private function encryptConfig(string $value, string $key): string
    {
        if (empty($key)) return $value;

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Déchiffrer une valeur de config
     */
    public function decryptConfig(string $value, string $key): string
    {
        if (empty($key)) return $value;

        $decoded = base64_decode($value);
        $iv = substr($decoded, 0, 16);
        $tag = substr($decoded, 16, 16);
        $encrypted = substr($decoded, 32);

        return openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }

    /**
     * Audit log
     */
    private function audit(string $action, array $metadata = []): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $tenantId = $_SESSION['tenant_id'] ?? null;

            $this->db->insert('audit_logs', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'metadata' => json_encode($metadata),
            ]);
        } catch (\Throwable $e) {
            error_log("[StorageService] Audit log failed: {$e->getMessage()}");
        }
    }

    /**
     * Format size
     */
    public static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;
        $size = (float)$bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
