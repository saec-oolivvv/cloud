<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;
use Saec\Core\Security;

class AdminController extends Controller
{
    // ═══════════════════════════════════════════════════
    // INDEX — Toutes les données de référence SaaS
    // ═══════════════════════════════════════════════════
    public function dashboard(): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        // ── Tenants ──
        $tenants = $db->fetch("SELECT COUNT(*) as c FROM tenants WHERE deleted_at IS NULL");
        $tenantsActive = $db->fetch("SELECT COUNT(*) as c FROM tenants WHERE active = 1 AND deleted_at IS NULL");
        $tenantsExpired = $db->fetch("SELECT COUNT(*) as c FROM tenants WHERE end_date < CURDATE() AND active = 1 AND deleted_at IS NULL");
        $tenantsExpiring = $db->fetch("SELECT COUNT(*) as c FROM tenants WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND active = 1");

        // ── Users ──
        $users = $db->fetch("SELECT COUNT(*) as c FROM users WHERE active = 1");
        $usersAdmin = $db->fetch("SELECT COUNT(*) as c FROM users WHERE role = 'admin' AND active = 1");
        $usersToday = $db->fetch("SELECT COUNT(*) as c FROM users WHERE created_at >= CURDATE()");

        // ── Files ──
        $files = $db->fetch("SELECT COUNT(*) as c FROM files WHERE deleted_at IS NULL");
        $storageUsed = $db->fetch("SELECT COALESCE(SUM(size), 0) as total FROM files WHERE deleted_at IS NULL");
        $storageQuota = $db->fetch("SELECT COALESCE(SUM(storage_quota), 0) as total FROM tenants WHERE deleted_at IS NULL");
        $filesToday = $db->fetch("SELECT COUNT(*) as c FROM files WHERE created_at >= CURDATE() AND deleted_at IS NULL");
        $filesDeleted = $db->fetch("SELECT COUNT(*) as c FROM files WHERE deleted_at IS NOT NULL");

        // ── Shares ──
        $shares = $db->fetch("SELECT COUNT(*) as c FROM shares WHERE is_active = 1");
        $sharesExpired = $db->fetch("SELECT COUNT(*) as c FROM shares WHERE expires_at < NOW() AND is_active = 1");

        // ── Security ──
        $loginAttempts = $db->fetch("SELECT COUNT(*) as c FROM login_attempts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $loginFailed = $db->fetch("SELECT COUNT(*) as c FROM login_attempts WHERE success = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $ipBanned = $db->fetch("SELECT COUNT(*) as c FROM ip_blacklist WHERE expires_at IS NULL OR expires_at > NOW()");
        $activeSessions = $db->fetch("SELECT COUNT(*) as c FROM user_sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
        $apiTokens = $db->fetch("SELECT COUNT(*) as c FROM api_tokens WHERE is_active = 1");

        // ── Folders ──
        $folders = $db->fetch("SELECT COUNT(*) as c FROM folders");

        // ── Encryption ──
        $encryptedFiles = $db->fetch("SELECT COUNT(*) as c FROM files WHERE file_key IS NOT NULL AND deleted_at IS NULL");

        // ── Audit (24h) ──
        $auditActions = $db->fetchAll(
            "SELECT action, COUNT(*) as count 
             FROM audit_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY action 
             ORDER BY count DESC 
             LIMIT 10"
        );

        $recentAudit = $db->fetchAll(
            "SELECT al.*, u.email 
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC 
             LIMIT 15"
        );

        // ── Tenant details ──
        $tenantDetails = $db->fetchAll(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM users WHERE tenant_id = t.id AND active = 1) as user_count,
                    (SELECT COUNT(*) FROM files WHERE tenant_id = t.id AND deleted_at IS NULL) as file_count,
                    (SELECT COALESCE(SUM(size), 0) FROM files WHERE tenant_id = t.id AND deleted_at IS NULL) as storage_used,
                    (SELECT COUNT(*) FROM folders WHERE tenant_id = t.id) as folder_count
             FROM tenants t
             WHERE t.deleted_at IS NULL
             ORDER BY t.created_at DESC"
        );

        // ── Expiring tenants ──
        $expiring = $db->fetchAll(
            "SELECT * FROM tenants 
             WHERE end_date IS NOT NULL 
             AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND active = 1 AND deleted_at IS NULL"
        );

        // ── System health ──
        $dbPing = $this->testDbConnection();
        $phpVersion = PHP_VERSION;
        $diskFree = @disk_free_space('/') ?: 0;
        $diskTotal = @disk_total_space('/') ?: 1;
        $diskUsed = $diskTotal - $diskFree;

        $data = [
            'user' => $user,
            'stats' => [
                'tenants' => (int) $tenants['c'],
                'tenants_active' => (int) $tenantsActive['c'],
                'tenants_expired' => (int) $tenantsExpired['c'],
                'tenants_expiring' => (int) $tenantsExpiring['c'],
                'users' => (int) $users['c'],
                'users_admin' => (int) $usersAdmin['c'],
                'users_today' => (int) $usersToday['c'],
                'files' => (int) $files['c'],
                'files_today' => (int) $filesToday['c'],
                'files_deleted' => (int) $filesDeleted['c'],
                'storage_used' => (int) $storageUsed['total'],
                'storage_quota' => (int) $storageQuota['total'],
                'shares' => (int) $shares['c'],
                'shares_expired' => (int) $sharesExpired['c'],
                'folders' => (int) $folders['c'],
                'encrypted_files' => (int) $encryptedFiles['c'],
                'login_attempts_24h' => (int) $loginAttempts['c'],
                'login_failed_24h' => (int) $loginFailed['c'],
                'ip_banned' => (int) $ipBanned['c'],
                'active_sessions' => (int) $activeSessions['c'],
                'api_tokens' => (int) $apiTokens['c'],
            ],
            'tenant_details' => $tenantDetails,
            'expiring_tenants' => $expiring,
            'audit_actions' => $auditActions,
            'recent_audit' => $recentAudit,
            'system' => [
                'db_status' => $dbPing ? 'OK' : 'ERROR',
                'php_version' => $phpVersion,
                'disk_used' => $diskUsed,
                'disk_total' => $diskTotal,
                'disk_pct' => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0,
            ],
            'pageTitle' => 'Administration SaaS',
        ];

        $this->view('admin/dashboard', $data);
    }

    // ═══════════════════════════════════════════════════
    // TENANTS
    // ═══════════════════════════════════════════════════
    public function tenants(): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';

        $where = "t.deleted_at IS NULL";
        $params = [];

        if ($status === 'active') $where .= " AND t.active = 1";
        elseif ($status === 'inactive') $where .= " AND t.active = 0";
        elseif ($status === 'expired') $where .= " AND t.end_date < CURDATE()";
        elseif ($status === 'expiring') $where .= " AND t.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";

        if (!empty($search)) {
            $where .= " AND (t.name LIKE ? OR t.slug LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $tenants = $db->fetchAll(
            "SELECT t.*, 
                    (SELECT COUNT(*) FROM users WHERE tenant_id = t.id AND active = 1) as user_count,
                    (SELECT COUNT(*) FROM files WHERE tenant_id = t.id AND deleted_at IS NULL) as file_count,
                    (SELECT COALESCE(SUM(size), 0) FROM files WHERE tenant_id = t.id AND deleted_at IS NULL) as storage_used,
                    (SELECT COUNT(*) FROM folders WHERE tenant_id = t.id) as folder_count,
                    (SELECT COUNT(*) FROM shares WHERE tenant_id = t.id AND is_active = 1) as share_count
             FROM tenants t
             WHERE {$where}
             ORDER BY t.created_at DESC",
            $params
        );

        // Récupère les mounts par tenant pour affichage
        $mountRows = $db->fetchAll(
            "SELECT m.id, m.tenant_id, m.provider_id, m.remote_path, m.local_alias, m.mount_type, m.is_active,
                    p.name AS provider_name, p.type AS provider_type
             FROM storage_mounts m
             JOIN storage_providers p ON p.id = m.provider_id AND p.deleted_at IS NULL
             WHERE m.is_active = 1
             ORDER BY m.tenant_id, m.id"
        );
        $mountsByTenant = [];
        foreach ($mountRows as $m) {
            $mountsByTenant[(int)$m['tenant_id']][] = $m;
        }
        foreach ($tenants as &$t) {
            $t['mounts'] = $mountsByTenant[(int)$t['id']] ?? [];
        }
        unset($t);

$data = [
            'user' => $user,
            'tenants' => $tenants,
            'filters' => ['status' => $status, 'search' => $search],
            'pageTitle' => 'Gestion Tenants',
        ];
        $db = Database::getInstance();
        $data['providers'] = $db->fetchAll("SELECT id, name, type FROM storage_providers WHERE is_active = 1 AND deleted_at IS NULL");
        $this->view('admin/tenants', $data);
    }

    public function createTenant(): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $this->json(['error' => 'Nom requis'], 400);
            return;
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        $existing = $db->fetch("SELECT id FROM tenants WHERE slug = ?", [$slug]);
        if ($existing) {
            $this->json(['error' => 'Ce slug existe déjà'], 409);
            return;
        }

        // Plan-based limits
        $plan = $_POST['plan'] ?? 'custom';
        $planLimits = [
            'starter' => ['max_users' => 1, 'storage_quota' => 10737418240, 'max_file_size' => 1073741824, 'extra_user_price' => 2.00],
            'professional' => ['max_users' => 5, 'storage_quota' => 107374182400, 'max_file_size' => 10737418240, 'extra_user_price' => 1.50],
            'enterprise' => ['max_users' => 15, 'storage_quota' => 0, 'max_file_size' => 53687091200, 'extra_user_price' => 1.00],
            'custom' => ['max_users' => 50, 'storage_quota' => 10737418240, 'max_file_size' => 104857600, 'extra_user_price' => 0.00],
        ];
        $limits = $planLimits[$plan] ?? $planLimits['custom'];

        $tenantId = $db->insert('tenants', [
            'name' => $name,
            'slug' => $slug,
            'plan' => $plan,
            'storage_quota' => (int) ($_POST['storage_quota'] ?? $limits['storage_quota']),
            'max_file_size' => (int) ($_POST['max_file_size'] ?? $limits['max_file_size']),
            'max_users' => (int) ($_POST['max_users'] ?? $limits['max_users']),
            'plan_max_users' => $limits['max_users'],
            'extra_users_count' => 0,
            'extra_user_price' => $limits['extra_user_price'],
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => $_POST['end_date'] ?: null,
            'auto_deactivate' => (int) ($_POST['auto_deactivate'] ?? 1),
            'features' => json_encode($_POST['features'] ?? []),
            'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
            'billing_status' => 'trial',
            'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
        ]);

        // Créer répertoire physique
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/' . $tenantId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0770, true);
        }

        // Dossier racine
        $adminId = $db->fetch("SELECT id FROM users WHERE tenant_id = ? AND role = 'admin' LIMIT 1", [$tenantId]);
        $db->insert('folders', [
            'tenant_id' => $tenantId,
            'user_id' => $adminId['id'] ?? $user['id'],
            'name' => '/',
            'path' => '/',
        ]);

try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'tenant.created',
                    'resource_type' => 'tenant',
                    'resource_id' => $tenantId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

        $this->json(['success' => true, 'tenant_id' => $tenantId]);
    }

    public function updateTenant(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        // PUT: $_POST est vide — parser le body
        $input = $_POST;
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $rawBody = file_get_contents('php://input');
            parse_str($rawBody, $parsed);
            $input = $parsed ?: $_POST;
            error_log("[UPDATE TENANT] method=PUT raw=" . substr($rawBody, 0, 200) . " parsed=" . json_encode($input));
        }

        $updates = [];
        $fields = ['name', 'plan', 'storage_quota', 'max_file_size', 'max_users', 'start_date', 'end_date', 'active', 'auto_deactivate', 'extra_user_price'];

        foreach ($fields as $f) {
            if (!array_key_exists($f, $input)) continue;
            $v = $input[$f];
            // Vide => NULL pour les champs date/décimaux
            if ($v === '' || $v === null) {
                if (in_array($f, ['start_date', 'end_date', 'extra_user_price'])) {
                    $updates[$f] = null;
                } elseif (!in_array($f, ['active', 'auto_deactivate'])) {
                    continue; // skip strings vides
                } else {
                    $updates[$f] = 0;
                }
            } else {
                $updates[$f] = $v;
            }
        }

        if (empty($updates)) {
            $this->json(['error' => 'Aucune donnée à modifier'], 400);
            return;
        }

        $db->execute(
            "UPDATE tenants SET " . implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($updates))) . " WHERE id = ?",
            array_merge(array_values($updates), [$id])
        );

try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'tenant.updated',
                    'resource_type' => 'tenant',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'metadata' => json_encode(array_keys($updates)),
                ]);
            } catch (\Throwable $e) {}

        $this->json(['success' => true]);
    }

    public function extendTenant(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $days = (int) ($_POST['days'] ?? 30);
        $db->execute(
            "UPDATE tenants SET end_date = DATE_ADD(COALESCE(end_date, CURDATE()), INTERVAL ? DAY) WHERE id = ?",
            [$days, $id]
        );

try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'tenant.extended',
                    'resource_type' => 'tenant',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

        $this->json(['success' => true]);
    }

    // ═══════════════════════════════════════════
    // TENANT STORAGE ASSIGNMENT
    // ═══════════════════════════════════════════

    public function tenantStorage(string $id): void
    {
        $this->requireAdmin();
        $db = Database::getInstance();

        $tenant = $db->fetch("SELECT * FROM tenants WHERE id = ?", [(int)$id]);
        if (!$tenant) { $this->json(['error' => 'Tenant introuvable'], 404); return; }

        $mounts = $db->fetchAll(
            "SELECT m.*, p.name as provider_name, p.type as provider_type
             FROM storage_mounts m
             JOIN storage_providers p ON p.id = m.provider_id
             WHERE m.tenant_id = ? AND p.deleted_at IS NULL",
            [(int)$id]
        );

        $providers = $db->fetchAll(
            "SELECT id, name, type FROM storage_providers WHERE is_active = 1 AND deleted_at IS NULL"
        );

        $this->json([
            'tenant' => $tenant,
            'mounts' => $mounts,
            'providers' => $providers,
        ]);
    }

    public function assignStorage(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $providerId = (int)($_POST['provider_id'] ?? 0);
        $remotePath = trim($_POST['remote_path'] ?? '/');
        $localAlias = trim($_POST['local_alias'] ?? '');
        $mountType = $_POST['mount_type'] ?? 'readwrite';

        if ($providerId <= 0) {
            $this->json(['error' => 'Provider requis'], 400);
            return;
        }

        if (!in_array($mountType, ['readonly', 'readwrite', 'backup_only'])) {
            $this->json(['error' => 'Type de mount invalide'], 400);
            return;
        }

        $provider = $db->fetch("SELECT id, name, type FROM storage_providers WHERE id = ? AND deleted_at IS NULL", [$providerId]);
        if (!$provider) {
            $this->json(['error' => 'Provider introuvable'], 404);
            return;
        }

        if (empty($localAlias)) {
            $localAlias = '/' . $provider['type'] . '-' . $provider['id'];
        }

        $existing = $db->fetch(
            "SELECT id FROM storage_mounts WHERE tenant_id = ? AND provider_id = ? AND remote_path = ?",
            [(int)$id, $providerId, $remotePath]
        );
        if ($existing) {
            $this->json(['error' => 'Ce provider est déjà assigné à ce chemin pour ce tenant'], 400);
            return;
        }

        $db->execute(
            "INSERT INTO storage_mounts (provider_id, tenant_id, remote_path, local_alias, mount_type) VALUES (?, ?, ?, ?, ?)",
            [$providerId, (int)$id, $remotePath, $localAlias, $mountType]
        );

        try {
            $db->insert('audit_logs', [
                'tenant_id' => (int)$id,
                'user_id' => $user['id'],
                'action' => 'tenant.storage_assigned',
                'resource_type' => 'storage_mount',
                'resource_id' => (string)$providerId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        } catch (\Throwable $e) {}

        $this->json(['success' => true, 'message' => "Provider \"{$provider['name']}\" assigné"]);
    }

    public function removeStorage(string $id, string $mountId): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $mount = $db->fetch(
            "SELECT m.*, p.name as provider_name FROM storage_mounts m JOIN storage_providers p ON p.id = m.provider_id WHERE m.id = ? AND m.tenant_id = ?",
            [(int)$mountId, (int)$id]
        );
        if (!$mount) {
            $this->json(['error' => 'Mount introuvable'], 404);
            return;
        }

        $db->execute("DELETE FROM storage_mounts WHERE id = ?", [(int)$mountId]);

        try {
            $db->insert('audit_logs', [
                'tenant_id' => (int)$id,
                'user_id' => $user['id'],
                'action' => 'tenant.storage_removed',
                'resource_type' => 'storage_mount',
                'resource_id' => $mountId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        } catch (\Throwable $e) {}

        $this->json(['success' => true, 'message' => "Mount \"{$mount['provider_name']}\" retiré"]);
    }

    public function tenantUsage(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $tenant = $db->fetch("SELECT * FROM tenants WHERE id = ?", [$id]);
        if (!$tenant) {
            $this->json(['error' => 'Tenant non trouvé'], 404);
            return;
        }

        $usage = $db->fetch(
            "SELECT 
                (SELECT COUNT(*) FROM users WHERE tenant_id = ? AND active = 1) as users,
                (SELECT COUNT(*) FROM files WHERE tenant_id = ? AND deleted_at IS NULL) as files,
                (SELECT COALESCE(SUM(size), 0) FROM files WHERE tenant_id = ? AND deleted_at IS NULL) as storage_used,
                (SELECT COUNT(*) FROM folders WHERE tenant_id = ?) as folders,
                (SELECT COUNT(*) FROM shares WHERE tenant_id = ? AND is_active = 1) as shares",
            [$id, $id, $id, $id, $id]
        );

        $this->json([
            'tenant' => $tenant,
            'usage' => $usage,
        ]);
    }

    // ═══════════════════════════════════════════════════
    // USERS
    // ═══════════════════════════════════════════════════
    public function tenantUsers(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $users = $db->fetchAll(
            "SELECT u.id, u.email, u.role, u.active, u.last_login, u.last_login_ip, u.created_at,
                    (SELECT COUNT(*) FROM files WHERE user_id = u.id AND deleted_at IS NULL) as file_count
             FROM users u
             WHERE u.tenant_id = ?
             ORDER BY u.created_at DESC",
            [$id]
        );

        $tenant = $db->fetch("SELECT * FROM tenants WHERE id = ?", [$id]);

        $data = [
            'user' => $user,
            'users' => $users,
            'tenant' => $tenant,
            'pageTitle' => "Utilisateurs — {$tenant['name']}",
        ];

        $this->view('admin/users', $data);
    }

    public function createUser(string $tenantId): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            $this->json(['error' => 'Email et mot de passe requis'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Email invalide'], 400);
            return;
        }

        if (strlen($password) < 8) {
            $this->json(['error' => 'Mot de passe trop court (min 8 caractères)'], 400);
            return;
        }

        $existing = $db->fetch(
            "SELECT id FROM users WHERE email = ? AND tenant_id = ?",
            [$email, $tenantId]
        );
        if ($existing) {
            $this->json(['error' => 'Cet email est déjà utilisé'], 409);
            return;
        }

        $userId = $db->insert('users', [
            'tenant_id' => $tenantId,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'role' => $role,
            'active' => 1,
        ]);

try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'user.created',
                    'resource_type' => 'user',
                    'resource_id' => $userId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

        $this->json(['success' => true, 'user_id' => $userId]);
    }

    public function updateUser(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $updates = [];
        if (isset($_POST['role'])) $updates['role'] = $_POST['role'];
        if (isset($_POST['active'])) $updates['active'] = (int) $_POST['active'];

        if (!empty($updates)) {
            $db->execute(
                "UPDATE users SET " . implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($updates))) . " WHERE id = ?",
                array_merge(array_values($updates), [$id])
            );
        }

        $this->json(['success' => true]);
    }

    public function deleteUser(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $db->execute("DELETE FROM users WHERE id = ? AND tenant_id != ?", [$id, $user['tenant_id']]);

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'user.deleted',
            'resource_type' => 'user',
            'resource_id' => $id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $this->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════
    // AUDIT
    // ═══════════════════════════════════════════════════
    public function auditLogs(): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $action = $_GET['action'] ?? '';
        $tenantId = $_GET['tenant_id'] ?? '';

        $where = "1=1";
        $params = [];

        if ($action) {
            $where .= " AND al.action = ?";
            $params[] = $action;
        }
        if ($tenantId) {
            $where .= " AND al.tenant_id = ?";
            $params[] = $tenantId;
        }

        $logs = $db->fetchAll(
            "SELECT al.*, u.email, t.name as tenant_name
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             LEFT JOIN tenants t ON al.tenant_id = t.id
             WHERE {$where}
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        $total = $db->fetch(
            "SELECT COUNT(*) as c FROM audit_logs al WHERE {$where}",
            $params
        );

        $actions = $db->fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action");

        $data = [
            'user' => $user,
            'logs' => $logs,
            'actions' => $actions,
            'total' => (int) $total['c'],
            'page' => $page,
            'pages' => (int) ceil($total['c'] / $limit),
            'filters' => ['action' => $action, 'tenant_id' => $tenantId],
            'pageTitle' => 'Audit Logs',
        ];

        $this->view('admin/audit', $data);
    }

    // ═══════════════════════════════════════════════════
    // CONFIG
    // ═══════════════════════════════════════════════════
    public function config(): void
    {
        $user = $this->requireAdmin();
        $config = $GLOBALS['SAEC_CONFIG'];

        $data = [
            'user' => $user,
            'config' => $config,
            'pageTitle' => 'Configuration',
        ];

        $this->view('admin/config', $data);
    }

    public function updateConfig(): void
    {
        $user = $this->requireAdmin();

        // Security: only allow specific fields
        $allowed = ['max_file_size', 'allowed_types'];
        $updates = [];
        foreach ($allowed as $f) {
            if (isset($_POST[$f])) {
                $updates[$f] = $_POST[$f];
            }
        }

        $this->json(['success' => true, 'note' => 'Config update requires file regeneration']);
    }

    // ═══════════════════════════════════════════════════
    // MODULES MANAGEMENT
    // ═══════════════════════════════════════════════════
    public function modules(): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $modules = $db->fetchAll(
            "SELECT * FROM modules ORDER BY sort_order ASC"
        );

        $tenants = $db->fetchAll(
            "SELECT id, name FROM tenants WHERE deleted_at IS NULL ORDER BY name"
        );

        $plans = ['starter', 'professional', 'enterprise'];

        // Parse JSON fields
        foreach ($modules as &$m) {
            $m['plans'] = json_decode($m['plans'] ?? '[]', true) ?? [];
            $m['depends_on'] = json_decode($m['depends_on'] ?? '[]', true) ?? [];
        }
        unset($m);

        // Get per-tenant module overrides
        $overrides = $db->fetchAll("SELECT * FROM tenant_modules");
        $tenantOverrides = [];
        foreach ($overrides as $o) {
            $tenantOverrides[$o['tenant_id']][$o['module_name']] = $o['enabled'];
        }

        $data = [
            'user' => $user,
            'modules' => $modules,
            'tenants' => $tenants,
            'plans' => $plans,
            'tenant_overrides' => $tenantOverrides,
            'pageTitle' => 'Module Management',
        ];

        $this->view('admin/modules', $data);
    }

    public function toggleModule(): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $moduleName = $_POST['module'] ?? '';
        $newStatus = $_POST['status'] ?? '';

        if (!$moduleName || !in_array($newStatus, ['active', 'dev', 'disabled'])) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }

        // Check dependencies
        $module = $db->fetch("SELECT * FROM modules WHERE name = ?", [$moduleName]);
        if (!$module) {
            $this->json(['error' => 'Module not found'], 404);
            return;
        }

        $depends = json_decode($module['depends_on'], true) ?? [];
        if (!empty($depends)) {
            foreach ($depends as $dep) {
                $depModule = $db->fetch("SELECT status FROM modules WHERE name = ?", [$dep]);
                if ($depModule && $depModule['status'] === 'disabled') {
                    $this->json([
                        'error' => "Cannot enable: dependency '{$dep}' is disabled",
                    ], 400);
                    return;
                }
            }
        }

        $db->execute(
            "UPDATE modules SET status = ?, updated_at = NOW() WHERE name = ?",
            [$newStatus, $moduleName]
        );

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'module.' . ($newStatus === 'disabled' ? 'disabled' : 'enabled'),
            'resource_type' => 'module',
            'resource_id' => 0,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'metadata' => json_encode([
                'module' => $moduleName,
                'status' => $newStatus,
            ]),
        ]);

        $this->json(['success' => true]);
    }

    public function updateModulePlans(): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $moduleName = $_POST['module'] ?? '';
        $plans = $_POST['plans'] ?? [];

        if (!$moduleName) {
            $this->json(['error' => 'Module required'], 400);
            return;
        }

        $db->execute(
            "UPDATE modules SET plans = ?, updated_at = NOW() WHERE name = ?",
            [json_encode($plans), $moduleName]
        );

        $this->json(['success' => true]);
    }

    public function toggleTenantModule(): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $tenantId = (int) ($_POST['tenant_id'] ?? 0);
        $moduleName = $_POST['module'] ?? '';
        $enabled = (int) ($_POST['enabled'] ?? 1);

        if (!$tenantId || !$moduleName) {
            $this->json(['error' => 'Missing parameters'], 400);
            return;
        }

        // Check module exists
        $module = $db->fetch("SELECT * FROM modules WHERE name = ?", [$moduleName]);
        if (!$module) {
            $this->json(['error' => 'Module not found'], 404);
            return;
        }

        // Check tenant exists
        $tenant = $db->fetch("SELECT id FROM tenants WHERE id = ? AND deleted_at IS NULL", [$tenantId]);
        if (!$tenant) {
            $this->json(['error' => 'Tenant not found'], 404);
            return;
        }

        // Check plan allows this module
        $tenantPlan = $db->fetch("SELECT features FROM tenants WHERE id = ?", [$tenantId]);
        $tenantFeatures = json_decode($tenantPlan['features'] ?? '[]', true);
        $modulePlans = json_decode($module['plans'], true) ?? [];

        // Upsert tenant_modules
        $existing = $db->fetch(
            "SELECT id FROM tenant_modules WHERE tenant_id = ? AND module_name = ?",
            [$tenantId, $moduleName]
        );

        if ($existing) {
            $db->execute(
                "UPDATE tenant_modules SET enabled = ?, updated_at = NOW() WHERE id = ?",
                [$enabled, $existing['id']]
            );
        } else {
            $db->insert('tenant_modules', [
                'tenant_id' => $tenantId,
                'module_name' => $moduleName,
                'enabled' => $enabled,
            ]);
        }

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => $enabled ? 'module.tenant_enabled' : 'module.tenant_disabled',
            'resource_type' => 'module',
            'resource_id' => $tenantId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'metadata' => json_encode([
                'module' => $moduleName,
                'tenant_id' => $tenantId,
            ]),
        ]);

        $this->json(['success' => true]);
    }

    public function tenantBillingView(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $tenant = $db->fetch("SELECT * FROM tenants WHERE id = ?", [$id]);
        if (!$tenant) {
            http_response_code(404);
            echo "Tenant not found";
            return;
        }

        $userCount = $db->fetch("SELECT COUNT(*) as count FROM users WHERE tenant_id = ? AND active = 1", [$id]);
        $currentUsers = (int) $userCount['count'];
        $includedUsers = (int) ($tenant['plan_max_users'] ?? $tenant['max_users']);
        $extraUsers = max(0, $currentUsers - $includedUsers);
        $extraUserPrice = (float) ($tenant['extra_user_price'] ?? 0);
        $monthlyExtra = $extraUsers * $extraUserPrice;
        $basePrice = match($tenant['plan']) {
            'starter' => 9.00,
            'professional' => 29.00,
            'enterprise' => 0.00,
            default => 0.00,
        };

        $billing = [
            'tenant' => $tenant,
            'current_users' => $currentUsers,
            'included_users' => $includedUsers,
            'extra_users' => $extraUsers,
            'extra_user_price' => $extraUserPrice,
            'monthly_extra_cost' => $monthlyExtra,
            'base_price' => $basePrice,
            'total_monthly' => $basePrice + $monthlyExtra,
            'billing_status' => $tenant['billing_status'] ?? 'trial',
            'billing_cycle' => $tenant['billing_cycle'] ?? 'monthly',
        ];

        $data = [
            'user' => $user,
            'tenant' => $tenant,
            'billing' => $billing,
            'pageTitle' => "Facturation — {$tenant['name']}",
        ];

        $this->view('admin/billing', $data);
    }

    public function tenantBilling(string $id): void
    {
        $user = $this->requireAdmin();
        $db = Database::getInstance();

        $tenant = $db->fetch("SELECT * FROM tenants WHERE id = ?", [$id]);
        if (!$tenant) {
            $this->json(['error' => 'Tenant not found'], 404);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $extraUsers = (int) ($_POST['extra_users_count'] ?? 0);
            $billingStatus = $_POST['billing_status'] ?? 'trial';
            $billingCycle = $_POST['billing_cycle'] ?? 'monthly';

            $db->execute(
                "UPDATE tenants SET extra_users_count = ?, billing_status = ?, billing_cycle = ? WHERE id = ?",
                [$extraUsers, $billingStatus, $billingCycle, $id]
            );

            $db->insert('audit_logs', [
                'tenant_id' => $user['tenant_id'],
                'user_id' => $user['id'],
                'action' => 'tenant.billing_updated',
                'resource_type' => 'tenant',
                'resource_id' => $id,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'metadata' => json_encode([
                    'extra_users_count' => $extraUsers,
                    'billing_status' => $billingStatus,
                    'billing_cycle' => $billingCycle,
                ]),
            ]);

            $this->json(['success' => true]);
            return;
        }

        $userCount = $db->fetch("SELECT COUNT(*) as count FROM users WHERE tenant_id = ? AND active = 1", [$id]);
        $currentUsers = (int) $userCount['count'];
        $includedUsers = (int) ($tenant['plan_max_users'] ?? $tenant['max_users']);
        $extraUsers = max(0, $currentUsers - $includedUsers);
        $extraUserPrice = (float) ($tenant['extra_user_price'] ?? 0);
        $monthlyExtra = $extraUsers * $extraUserPrice;
        $basePrice = match($tenant['plan']) {
            'starter' => 9.00,
            'professional' => 29.00,
            'enterprise' => 0.00,
            default => 0.00,
        };

        $this->json([
            'tenant' => $tenant,
            'current_users' => $currentUsers,
            'included_users' => $includedUsers,
            'extra_users' => $extraUsers,
            'extra_user_price' => $extraUserPrice,
            'monthly_extra_cost' => $monthlyExtra,
            'base_price' => $basePrice,
            'total_monthly' => $basePrice + $monthlyExtra,
        ]);
    }

    // ═══════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════
    private function testDbConnection(): bool
    {
        try {
            $db = Database::getInstance();
            $db->fetch("SELECT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
