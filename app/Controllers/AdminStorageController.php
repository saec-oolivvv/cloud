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
        parent::__construct();
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

    public function getProvider(string $id): void
    {
        $this->requireAdmin();
        try {
            $provider = $this->storage->getProvider((int)$id);
            $this->json($provider);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function createProvider(): void
    {
        $user = $this->requireAdmin();

        try {
            $config = json_decode($_POST['config'] ?? '{}', true) ?? [];
            error_log("[CREATE PROVIDER] type={$_POST['type'] ?? ''} name={$_POST['name'] ?? ''} config=" . json_encode($config));

            $providerId = $this->storage->createProvider([
                'name' => $_POST['name'] ?? '',
                'type' => $_POST['type'] ?? '',
                'config' => $config,
                'is_active' => (int)($_POST['is_active'] ?? 1),
                'is_default' => (int)($_POST['is_default'] ?? 0),
                'created_by' => $user['id'] ?? null,
            ]);

            $this->json(['success' => true, 'provider_id' => $providerId]);
        } catch (\Throwable $e) {
            error_log("[CREATE PROVIDER] Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateProvider(string $id): void
    {
        $this->requireAdmin();

        try {
            $data = $_POST;
            if (isset($data['config']) && is_string($data['config'])) {
                $data['config'] = json_decode($data['config'], true) ?? [];
            }
            $this->storage->updateProvider((int)$id, $data);
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
        try {
            $result = $this->storage->testProvider((int)$id);
            $this->json($result);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // DROPBOX OAUTH2
    // ═══════════════════════════════════════════════════════════

    public function dropboxAuthorize(string $id): void
    {
        $user = $this->requireAdmin();
        $provider = $this->storage->getProvider((int)$id);
        $config = json_decode($provider['config'], true) ?? [];

        $appKey = $config['app_key'] ?? '';
        if (empty($appKey)) {
            $this->withError('App key Dropbox manquante');
            $this->redirect('/admin/storage/providers');
            return;
        }

        $redirectUri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/admin/storage/providers/dropbox-callback';
        $state = bin2hex(random_bytes(16));

        // Stocker le state + provider_id en session
        $_SESSION['dropbox_oauth_state'] = $state;
        $_SESSION['dropbox_provider_id'] = $id;

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $appKey,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'files.metadata.read files.metadata.write files.content.read files.content.write sharing.read',
        ]);

        $this->redirect("https://www.dropbox.com/oauth2/authorize?{$params}");
    }

    public function dropboxCallback(): void
    {
        $user = $this->requireAdmin();

        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $error = $_GET['error'] ?? '';

        if ($error) {
            $this->withError("Erreur Dropbox: {$error}");
            $this->redirect('/admin/storage/providers');
            return;
        }

        if ($state !== ($_SESSION['dropbox_oauth_state'] ?? '')) {
            $this->withError('State invalide — CSRF détecté');
            $this->redirect('/admin/storage/providers');
            return;
        }

        $providerId = $_SESSION['dropbox_provider_id'] ?? null;
        unset($_SESSION['dropbox_oauth_state'], $_SESSION['dropbox_provider_id']);

        if (!$providerId || empty($code)) {
            $this->withError('Paramètres manquants');
            $this->redirect('/admin/storage/providers');
            return;
        }

        $provider = $this->storage->getProvider((int)$providerId);
        $config = json_decode($provider['config'], true) ?? [];

        // Échange code → tokens
        $ch = curl_init('https://api.dropboxapi.com/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'grant_type' => 'authorization_code',
                'client_id' => $config['app_key'],
                'client_secret' => $config['app_secret'],
                'redirect_uri' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/admin/storage/providers/dropbox-callback',
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode !== 200 || empty($data['access_token'])) {
            $this->withError("Échec échange token: " . ($data['error_description'] ?? 'Erreur inconnue'));
            $this->redirect('/admin/storage/providers');
            return;
        }

        // Sauvegarder les tokens
        $config['access_token'] = $data['access_token'];
        $config['refresh_token'] = $data['refresh_token'] ?? $config['refresh_token'] ?? '';

        $db = \Saec\Core\Database::getInstance();
        $db->execute(
            "UPDATE storage_providers SET config = ?, last_error = NULL WHERE id = ?",
            [json_encode($config), $providerId]
        );

        $this->withSuccess('Dropbox connecté avec succès');
        $this->redirect('/admin/storage/providers');
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
