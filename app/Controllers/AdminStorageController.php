<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;
use Saec\Services\StorageService;
use Saec\Services\BackupService;
use Saec\Services\MountService;
use Saec\Services\SchedulerService;

/**
 * Admin Storage Controller — Gestion de l'arm octopus Storage
 */
class AdminStorageController extends Controller
{
    private StorageService $storage;
    private BackupService $backup;
    private MountService $mount;
    private SchedulerService $scheduler;

    public function __construct()
    {
        $this->storage = StorageService::getInstance();
        $this->backup = BackupService::getInstance();
        $this->mount = MountService::getInstance();
        $this->scheduler = SchedulerService::getInstance();
    }

    // ═══════════════════════════════════════════════════════════
    // OVERVIEW
    // ═══════════════════════════════════════════════════════════

    public function index(): void
    {
        $user = $this->requireAdmin();

        $data = [
            'user' => $user,
            'stats' => $this->storage->getStats(),
            'schedulerStats' => $this->scheduler->getStats(),
            'pageTitle' => 'Storage — Overview',
        ];

        $this->view('admin/storage/index', $data);
    }

    // ═══════════════════════════════════════════════════════════
    // PROVIDERS
    // ═══════════════════════════════════════════════════════════

    public function providers(): void
    {
        $user = $this->requireAdmin();

        $data = [
            'user' => $user,
            'providers' => $this->storage->listProviders(),
            'supportedTypes' => \Saec\Services\Storage\AdapterFactory::getSupportedTypes(),
            'pageTitle' => 'Storage — Providers',
        ];

        $this->view('admin/storage/providers', $data);
    }

    public function createProvider(): void
    {
        $user = $this->requireAdmin();

        try {
            $providerId = $this->storage->createProvider([
                'name' => $_POST['name'] ?? '',
                'type' => $_POST['type'] ?? '',
                'config' => $_POST['config'] ?? [],
                'is_active' => (int)($_POST['is_active'] ?? 1),
                'is_default' => (int)($_POST['is_default'] ?? 0),
                'created_by' => $user['id'],
            ]);

            $this->json(['success' => true, 'provider_id' => $providerId]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateProvider(string $id): void
    {
        $this->requireAdmin();

        try {
            $this->storage->updateProvider((int)$id, $_POST);
            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteProvider(string $id): void
    {
        $this->requireAdmin();
        $this->storage->deleteProvider((int)$id);
        $this->json(['success' => true]);
    }

    public function testProvider(string $id): void
    {
        $this->requireAdmin();
        $result = $this->storage->testProvider((int)$id);
        $this->json($result);
    }

    // ═══════════════════════════════════════════════════════════
    // BACKUPS
    // ═══════════════════════════════════════════════════════════

    public function backups(): void
    {
        $user = $this->requireAdmin();

        $data = [
            'user' => $user,
            'backups' => $this->backup->listBackups(null, 100),
            'providers' => $this->storage->listProviders(),
            'pageTitle' => 'Storage — Backups',
        ];

        $this->view('admin/storage/backups', $data);
    }

    public function createBackup(): void
    {
        $this->requireAdmin();

        try {
            $backupId = $this->backup->createBackup(
                (int)($_POST['provider_id'] ?? 0),
                !empty($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : null,
                $_POST['type'] ?? 'full',
                [
                    'encrypted' => (bool)($_POST['encrypted'] ?? true),
                    'compression' => $_POST['compression'] ?? 'gzip',
                ]
            );

            $this->json(['success' => true, 'backup_id' => $backupId]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function restoreBackup(string $id): void
    {
        $this->requireAdmin();

        try {
            $this->backup->restore((int)$id);
            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteBackup(string $id): void
    {
        $this->requireAdmin();
        $this->backup->delete((int)$id);
        $this->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════
    // MOUNTS
    // ═══════════════════════════════════════════════════════════

    public function mounts(): void
    {
        $user = $this->requireAdmin();

        $data = [
            'user' => $user,
            'mounts' => $this->mount->listAllMounts(),
            'providers' => $this->storage->listProviders(),
            'pageTitle' => 'Storage — Mounts',
        ];

        $this->view('admin/storage/mounts', $data);
    }

    public function createMount(): void
    {
        $this->requireAdmin();

        try {
            $mountId = $this->mount->createMount([
                'provider_id' => (int)($_POST['provider_id'] ?? 0),
                'tenant_id' => (int)($_POST['tenant_id'] ?? 0),
                'remote_path' => $_POST['remote_path'] ?? '/',
                'local_alias' => $_POST['local_alias'] ?? '',
                'mount_type' => $_POST['mount_type'] ?? 'readwrite',
                'sync_enabled' => (int)($_POST['sync_enabled'] ?? 0),
                'sync_interval_minutes' => (int)($_POST['sync_interval_minutes'] ?? 60),
            ]);

            $this->json(['success' => true, 'mount_id' => $mountId]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function syncMount(string $id): void
    {
        $this->requireAdmin();

        try {
            $result = $this->mount->sync((int)$id);
            $this->json(['success' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteMount(string $id): void
    {
        $this->requireAdmin();
        $this->mount->deleteMount((int)$id);
        $this->json(['success' => true]);
    }

    public function browseMount(string $id): void
    {
        $this->requireAdmin();

        try {
            $path = $_GET['path'] ?? '';
            $items = $this->mount->browse((int)$id, $path);
            $this->json(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // SCHEDULES
    // ═══════════════════════════════════════════════════════════

    public function schedules(): void
    {
        $user = $this->requireAdmin();

        $data = [
            'user' => $user,
            'schedules' => $this->scheduler->listSchedules(),
            'stats' => $this->scheduler->getStats(),
            'providers' => $this->storage->listProviders(),
            'pageTitle' => 'Storage — Schedules',
        ];

        $this->view('admin/storage/schedules', $data);
    }

    public function createSchedule(): void
    {
        $this->requireAdmin();

        try {
            $scheduleId = $this->scheduler->createSchedule([
                'provider_id' => (int)($_POST['provider_id'] ?? 0),
                'tenant_id' => !empty($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : null,
                'name' => $_POST['name'] ?? 'Auto Backup',
                'type' => $_POST['type'] ?? 'full',
                'frequency' => $_POST['frequency'] ?? 'daily',
                'time_of_day' => $_POST['time_of_day'] ?? '02:00:00',
                'day_of_week' => isset($_POST['day_of_week']) ? (int)$_POST['day_of_week'] : null,
                'day_of_month' => isset($_POST['day_of_month']) ? (int)$_POST['day_of_month'] : null,
                'retention_days' => (int)($_POST['retention_days'] ?? 30),
                'is_active' => (int)($_POST['is_active'] ?? 1),
            ]);

            $this->json(['success' => true, 'schedule_id' => $scheduleId]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateSchedule(string $id): void
    {
        $this->requireAdmin();

        try {
            $this->scheduler->updateSchedule((int)$id, $_POST);
            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteSchedule(string $id): void
    {
        $this->requireAdmin();
        $this->scheduler->deleteSchedule((int)$id);
        $this->json(['success' => true]);
    }

    public function runSchedule(string $id): void
    {
        $this->requireAdmin();

        try {
            $schedule = $this->scheduler->getSchedule((int)$id);
            if (!$schedule) {
                $this->json(['error' => 'Schedule non trouvé'], 404);
                return;
            }

            $backupId = $this->backup->createBackup(
                $schedule['provider_id'],
                $schedule['tenant_id'],
                $schedule['type']
            );

            $this->json(['success' => true, 'backup_id' => $backupId]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // CRON TICK
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint pour le cron (CLI ou HTTP)
     */
    public function tick(): void
    {
        // Vérifier le token secret
        $token = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
        $config = $GLOBALS['SAEC_CONFIG']['cron'] ?? [];
        $expectedToken = $config['secret_token'] ?? '';

        if (empty($expectedToken) || $token !== $expectedToken) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }

        $results = $this->scheduler->tick();

        header('Content-Type: application/json');
        echo json_encode($results);
    }
}
