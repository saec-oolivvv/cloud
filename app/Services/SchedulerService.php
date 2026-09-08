<?php

declare(strict_types=1);

namespace Saec\Services;

use Saec\Core\Database;

/**
 * Scheduler Service — Planificateur de backups et syncs
 * 
 * Gère les tâches planifiées:
 * - Backups automatiques
 * - Sync périodique des mounts
 * - Nettoyage des anciens backups
 * - Notifications
 */
class SchedulerService
{
    private static ?SchedulerService $instance = null;
    private Database $db;
    private BackupService $backup;
    private MountService $mount;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->backup = BackupService::getInstance();
        $this->mount = MountService::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ═══════════════════════════════════════════════════════════
    // SCHEDULE CRUD
    // ═══════════════════════════════════════════════════════════

    /**
     * Créer un schedule
     */
    public function createSchedule(array $data): int
    {
        $nextRun = $this->calculateNextRun(
            $data['frequency'] ?? 'daily',
            $data['time_of_day'] ?? '02:00:00',
            $data['day_of_week'] ?? null,
            $data['day_of_month'] ?? null
        );

        return $this->db->insert('storage_backup_schedules', [
            'provider_id' => $data['provider_id'],
            'tenant_id' => $data['tenant_id'] ?? null,
            'name' => $data['name'] ?? 'Auto Backup',
            'type' => $data['type'] ?? 'full',
            'frequency' => $data['frequency'] ?? 'daily',
            'time_of_day' => $data['time_of_day'] ?? '02:00:00',
            'day_of_week' => $data['day_of_week'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'retention_days' => $data['retention_days'] ?? 30,
            'is_active' => $data['is_active'] ?? 1,
            'next_run_at' => $nextRun,
        ]);
    }

    /**
     * Mettre à jour un schedule
     */
    public function updateSchedule(int $id, array $data): bool
    {
        $updates = [];
        $fields = ['name', 'type', 'frequency', 'time_of_day', 'day_of_week', 'day_of_month', 'retention_days', 'is_active', 'provider_id', 'tenant_id'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }

        // Recalculer next_run si fréquence modifiée
        if (isset($updates['frequency']) || isset($updates['time_of_day']) || isset($updates['day_of_week']) || isset($updates['day_of_month'])) {
            $schedule = $this->getSchedule($id);
            if ($schedule) {
                $updates['next_run_at'] = $this->calculateNextRun(
                    $updates['frequency'] ?? $schedule['frequency'],
                    $updates['time_of_day'] ?? $schedule['time_of_day'],
                    $updates['day_of_week'] ?? $schedule['day_of_week'],
                    $updates['day_of_month'] ?? $schedule['day_of_month']
                );
            }
        }

        if (empty($updates)) return true;

        $this->db->execute(
            "UPDATE storage_backup_schedules SET " . implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($updates))) . " WHERE id = ?",
            array_merge(array_values($updates), [$id])
        );

        return true;
    }

    /**
     * Supprimer un schedule
     */
    public function deleteSchedule(int $id): bool
    {
        $this->db->execute("DELETE FROM storage_backup_schedules WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Lister les schedules
     */
    public function listSchedules(?int $tenantId = null): array
    {
        $where = "1=1";
        $params = [];

        if ($tenantId) {
            $where .= " AND (s.tenant_id = ? OR s.tenant_id IS NULL)";
            $params[] = $tenantId;
        }

        return $this->db->fetchAll(
            "SELECT s.*, p.name as provider_name
             FROM storage_backup_schedules s
             LEFT JOIN storage_providers p ON s.provider_id = p.id
             WHERE {$where}
             ORDER BY s.next_run_at ASC",
            $params
        );
    }

    /**
     * Obtenir un schedule
     */
    public function getSchedule(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT s.*, p.name as provider_name
             FROM storage_backup_schedules s
             LEFT JOIN storage_providers p ON s.provider_id = p.id
             WHERE s.id = ?",
            [$id]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // EXECUTION
    // ═══════════════════════════════════════════════════════════

    /**
     * Exécuter les tâches planifiées (appelé par cron/CLI)
     */
    public function tick(): array
    {
        $results = [
            'backups_executed' => 0,
            'mounts_synced' => 0,
            'cleanups_done' => 0,
            'trash_purged' => 0,
            'audit_purged' => 0,
            'errors' => [],
        ];

        // 1. Exécuter les backups planifiés
        try {
            $results['backups_executed'] = $this->runScheduledBackups();
        } catch (\Throwable $e) {
            $results['errors'][] = "Backup scheduler: {$e->getMessage()}";
        }

        // 2. Sync les mounts actifs
        try {
            $results['mounts_synced'] = $this->runScheduledSyncs();
        } catch (\Throwable $e) {
            $results['errors'][] = "Mount sync: {$e->getMessage()}";
        }

        // 3. Nettoyer les anciens backups
        try {
            $results['cleanups_done'] = $this->runCleanup();
        } catch (\Throwable $e) {
            $results['errors'][] = "Cleanup: {$e->getMessage()}";
        }

        // 4. Purge trashed files by tenant retention
        try {
            $results['trash_purged'] = $this->runTrashPurge();
        } catch (\Throwable $e) {
            $results['errors'][] = "Trash purge: {$e->getMessage()}";
        }

        // 5. Purge old audit logs by tenant retention
        try {
            $results['audit_purged'] = $this->runAuditPurge();
        } catch (\Throwable $e) {
            $results['errors'][] = "Audit purge: {$e->getMessage()}";
        }

        return $results;
    }

    /**
     * Exécuter les backups planifiés
     */
    private function runScheduledBackups(): int
    {
        $now = date('Y-m-d H:i:s');
        $schedules = $this->db->fetchAll(
            "SELECT * FROM storage_backup_schedules 
             WHERE is_active = 1 AND next_run_at <= ? AND next_run_at IS NOT NULL",
            [$now]
        );

        $count = 0;
        foreach ($schedules as $schedule) {
            try {
                // Exécuter le backup
                $this->backup->createBackup(
                    $schedule['provider_id'],
                    $schedule['tenant_id'],
                    $schedule['type'],
                    ['encrypted' => true, 'compression' => 'gzip']
                );

                // Mettre à jour last_run_at et prochain run
                $nextRun = $this->calculateNextRun(
                    $schedule['frequency'],
                    $schedule['time_of_day'],
                    $schedule['day_of_week'],
                    $schedule['day_of_month']
                );

                $this->db->execute(
                    "UPDATE storage_backup_schedules SET last_run_at = NOW(), next_run_at = ? WHERE id = ?",
                    [$nextRun, $schedule['id']]
                );

                $count++;
            } catch (\Throwable $e) {
                error_log("[Scheduler] Backup failed for schedule {$schedule['id']}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * Sync les mounts actifs
     */
    private function runScheduledSyncs(): int
    {
        $now = date('Y-m-d H:i:s');
        $mounts = $this->db->fetchAll(
            "SELECT * FROM storage_mounts 
             WHERE sync_enabled = 1 AND is_active = 1 
             AND (last_sync_at IS NULL OR DATE_ADD(last_sync_at, INTERVAL sync_interval_minutes MINUTE) <= ?)",
            [$now]
        );

        $count = 0;
        foreach ($mounts as $mount) {
            try {
                $this->mount->sync($mount['id']);
                $count++;
            } catch (\Throwable $e) {
                error_log("[Scheduler] Sync failed for mount {$mount['id']}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * Nettoyer les anciens backups
     */
    private function runCleanup(): int
    {
        $schedules = $this->db->fetchAll(
            "SELECT * FROM storage_backup_schedules WHERE is_active = 1 AND retention_days > 0"
        );

        $count = 0;
        foreach ($schedules as $schedule) {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$schedule['retention_days']} days"));

            $oldBackups = $this->db->fetchAll(
                "SELECT * FROM storage_backups 
                 WHERE provider_id = ? AND created_at < ? AND status = 'completed'",
                [$schedule['provider_id'], $cutoff]
            );

            foreach ($oldBackups as $backup) {
                try {
                    $this->backup->delete($backup['id']);
                    $count++;
                } catch (\Throwable $e) {
                    error_log("[Scheduler] Cleanup failed for backup {$backup['id']}: {$e->getMessage()}");
                }
            }
        }

        return $count;
    }

    /**
     * Purge trashed files by tenant retention policy
     */
    private function runTrashPurge(): int
    {
        $tenants = $this->db->fetchAll(
            "SELECT id, trash_retention_days FROM tenants 
             WHERE trash_retention_days > 0 AND active = 1 AND deleted_at IS NULL"
        );

        $count = 0;
        foreach ($tenants as $tenant) {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$tenant['trash_retention_days']} days"));

            // Permanent delete files in trash older than retention
            $trashedFiles = $this->db->fetchAll(
                "SELECT id, stored_name FROM files 
                 WHERE tenant_id = ? AND deleted_at IS NOT NULL AND deleted_at < ?",
                [$tenant['id'], $cutoff]
            );

            foreach ($trashedFiles as $file) {
                $filePath = dirname(__DIR__, 2) . "/storage/uploads/{$tenant['id']}/{$file['stored_name']}";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $this->db->execute("DELETE FROM files WHERE id = ?", [$file['id']]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Purge old audit logs by tenant retention policy
     */
    private function runAuditPurge(): int
    {
        $tenants = $this->db->fetchAll(
            "SELECT id, audit_retention_days FROM tenants 
             WHERE audit_retention_days > 0 AND active = 1 AND deleted_at IS NULL"
        );

        $count = 0;
        foreach ($tenants as $tenant) {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$tenant['audit_retention_days']} days"));

            $stmt = $this->db->execute(
                "DELETE FROM audit_logs WHERE tenant_id = ? AND created_at < ?",
                [$tenant['id'], $cutoff]
            );
            $count += $stmt->rowCount();
        }

        return $count;
    }

    // ═══════════════════════════════════════════════════════════
    // NEXT RUN CALCULATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Calculer la prochaine exécution
     */
    private function calculateNextRun(string $frequency, string $timeOfDay, ?int $dayOfWeek = null, ?int $dayOfMonth = null): string
    {
        $time = $timeOfDay ?: '02:00:00';
        $now = new \DateTime();

        switch ($frequency) {
            case 'hourly':
                $next = new \DateTime('+1 hour');
                $next->setTime((int)date('H'), 0, 0);
                break;

            case 'daily':
                $next = new \DateTime('tomorrow');
                $parts = explode(':', $time);
                $next->setTime((int)($parts[0] ?? 2), (int)($parts[1] ?? 0), 0);
                break;

            case 'weekly':
                $next = new \DateTime('next ' . $this->dayName($dayOfWeek ?? 0));
                $parts = explode(':', $time);
                $next->setTime((int)($parts[0] ?? 2), (int)($parts[1] ?? 0), 0);
                break;

            case 'monthly':
                $next = new \DateTime('first day of next month');
                $parts = explode(':', $time);
                $next->setTime((int)($parts[0] ?? 2), (int)($parts[1] ?? 0), 0);
                if ($dayOfMonth && $dayOfMonth > 1) {
                    $next->modify("+{$dayOfMonth} days -1 day");
                }
                break;

            default:
                $next = new \DateTime('+1 day');
                $parts = explode(':', $time);
                $next->setTime((int)($parts[0] ?? 2), (int)($parts[1] ?? 0), 0);
        }

        return $next->format('Y-m-d H:i:s');
    }

    /**
     * Nom du jour en français
     */
    private function dayName(int $day): string
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        return $days[$day] ?? 'monday';
    }

    // ═══════════════════════════════════════════════════════════
    // STATS
    // ═══════════════════════════════════════════════════════════

    /**
     * Statistiques du scheduler
     */
    public function getStats(): array
    {
        $totalSchedules = $this->db->fetch(
            "SELECT COUNT(*) as c FROM storage_backup_schedules"
        );

        $activeSchedules = $this->db->fetch(
            "SELECT COUNT(*) as c FROM storage_backup_schedules WHERE is_active = 1"
        );

        $nextRun = $this->db->fetch(
            "SELECT next_run_at FROM storage_backup_schedules WHERE is_active = 1 AND next_run_at IS NOT NULL ORDER BY next_run_at ASC LIMIT 1"
        );

        $recentBackups = $this->db->fetch(
            "SELECT COUNT(*) as c FROM storage_backups WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'completed'"
        );

        $failedBackups = $this->db->fetch(
            "SELECT COUNT(*) as c FROM storage_backups WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'failed'"
        );

        return [
            'total_schedules' => (int)($totalSchedules['c'] ?? 0),
            'active_schedules' => (int)($activeSchedules['c'] ?? 0),
            'next_run' => $nextRun['next_run_at'] ?? null,
            'backups_7d' => (int)($recentBackups['c'] ?? 0),
            'failed_7d' => (int)($failedBackups['c'] ?? 0),
        ];
    }
}
