<?php

declare(strict_types=1);

// ══════════════════════════════════════════════════════════════
// SAEC CLOUD — Routes
// ══════════════════════════════════════════════════════════════

use Saec\Controllers\AuthController;
use Saec\Controllers\DashboardController;
use Saec\Controllers\FileController;
use Saec\Controllers\ShareController;
use Saec\Controllers\AdminController;
use Saec\Middleware\AuthMiddleware;
use Saec\Middleware\AdminMiddleware;

// ─────────────────────────────────────────────────────────────
// ROOT → LANDING PAGE
// ─────────────────────────────────────────────────────────────
$router->get('/', function() {
    require __DIR__ . '/../app/Views/landing/index.php';
    exit;
});

// ─────────────────────────────────────────────────────────────
// PUBLIC PAGES (no auth)
// ─────────────────────────────────────────────────────────────
$router->get('/pricing', function() {
    require __DIR__ . '/../app/Views/pricing/index.php';
    exit;
});
$router->get('/subscribe', function() {
    require __DIR__ . '/../app/Views/pricing/subscribe.php';
    exit;
});
$router->get('/privacy', function() {
    require __DIR__ . '/../app/Views/legal/privacy.php';
    exit;
});
$router->get('/terms', function() {
    require __DIR__ . '/../app/Views/legal/terms.php';
    exit;
});

// ─────────────────────────────────────────────────────────────
// AUTH
// ─────────────────────────────────────────────────────────────
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// ─────────────────────────────────────────────────────────────
// DASHBOARD
// ─────────────────────────────────────────────────────────────
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

use Saec\Controllers\FolderController;
use Saec\Controllers\SettingsController;

// ─────────────────────────────────────────────────────────────
// SETTINGS (User Account)
// ─────────────────────────────────────────────────────────────
$router->get('/config', [SettingsController::class, 'index'], [AuthMiddleware::class]);
$router->post('/config/profile', [SettingsController::class, 'updateProfile'], [AuthMiddleware::class]);
$router->post('/config/password', [SettingsController::class, 'changePassword'], [AuthMiddleware::class]);
$router->post('/config/sessions/destroy', [SettingsController::class, 'destroySessions'], [AuthMiddleware::class]);
$router->get('/config/export', [SettingsController::class, 'exportData'], [AuthMiddleware::class]);
$router->post('/config/delete-account', [SettingsController::class, 'deleteAccount'], [AuthMiddleware::class]);

// ─────────────────────────────────────────────────────────────
// FILES
// ─────────────────────────────────────────────────────────────
$router->get('/files', [FolderController::class, 'index'], [AuthMiddleware::class]);
$router->post('/files/upload', [FileController::class, 'upload'], [AuthMiddleware::class]);
$router->get('/files/{id}/download', [FileController::class, 'download'], [AuthMiddleware::class]);
$router->get('/files/{id}/preview', [FileController::class, 'preview'], [AuthMiddleware::class]);
$router->get('/files/{id}/view', [FileController::class, 'view'], [AuthMiddleware::class]);
$router->get('/files/{id}/content', [FileController::class, 'getContent'], [AuthMiddleware::class]);
$router->post('/files/{id}/save', [FileController::class, 'saveContent'], [AuthMiddleware::class]);
$router->post('/files/{id}/move', [FileController::class, 'move'], [AuthMiddleware::class]);
$router->delete('/files/{id}', [FileController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/files/{id}/restore', [FileController::class, 'restore'], [AuthMiddleware::class]);
$router->get('/trash', [FileController::class, 'trash'], [AuthMiddleware::class]);

// ─────────────────────────────────────────────────────────────
// FOLDERS
// ─────────────────────────────────────────────────────────────
$router->post('/folders', [FolderController::class, 'create'], [AuthMiddleware::class]);
$router->post('/folders/{id}/rename', [FolderController::class, 'rename'], [AuthMiddleware::class]);
$router->delete('/folders/{id}', [FolderController::class, 'delete'], [AuthMiddleware::class]);

// ─────────────────────────────────────────────────────────────
// SHARES
// ─────────────────────────────────────────────────────────────
$router->get('/shares', [ShareController::class, 'index'], [AuthMiddleware::class]);
$router->post('/files/{id}/share', [ShareController::class, 'share'], [AuthMiddleware::class]);
$router->delete('/shares/{id}', [ShareController::class, 'revoke'], [AuthMiddleware::class]);

// ─────────────────────────────────────────────────────────────
// ADMIN
// ─────────────────────────────────────────────────────────────
$router->get('/admin', [AdminController::class, 'dashboard'], [AdminMiddleware::class]);

// Tenants
$router->get('/admin/tenants', [AdminController::class, 'tenants'], [AdminMiddleware::class]);
$router->post('/admin/tenants', [AdminController::class, 'createTenant'], [AdminMiddleware::class]);
$router->put('/admin/tenants/{id}', [AdminController::class, 'updateTenant'], [AdminMiddleware::class]);
$router->post('/admin/tenants/{id}/extend', [AdminController::class, 'extendTenant'], [AdminMiddleware::class]);
$router->get('/admin/tenants/{id}/usage', [AdminController::class, 'tenantUsage'], [AdminMiddleware::class]);
$router->get('/admin/tenants/{id}/billing', [AdminController::class, 'tenantBillingView'], [AdminMiddleware::class]);
$router->get('/admin/tenants/{id}/billing/api', [AdminController::class, 'tenantBilling'], [AdminMiddleware::class]);
$router->post('/admin/tenants/{id}/billing', [AdminController::class, 'tenantBilling'], [AdminMiddleware::class]);

// Users
$router->get('/admin/tenants/{id}/users', [AdminController::class, 'tenantUsers'], [AdminMiddleware::class]);
$router->post('/admin/tenants/{id}/users', [AdminController::class, 'createUser'], [AdminMiddleware::class]);
$router->put('/admin/users/{id}', [AdminController::class, 'updateUser'], [AdminMiddleware::class]);
$router->delete('/admin/users/{id}', [AdminController::class, 'deleteUser'], [AdminMiddleware::class]);

// Audit & Config
$router->get('/admin/audit', [AdminController::class, 'auditLogs'], [AdminMiddleware::class]);
$router->get('/admin/config', [AdminController::class, 'config'], [AdminMiddleware::class]);
$router->put('/admin/config', [AdminController::class, 'updateConfig'], [AdminMiddleware::class]);

// Modules
$router->get('/admin/modules', [AdminController::class, 'modules'], [AdminMiddleware::class]);
$router->post('/admin/modules/toggle', [AdminController::class, 'toggleModule'], [AdminMiddleware::class]);
$router->post('/admin/modules/plans', [AdminController::class, 'updateModulePlans'], [AdminMiddleware::class]);
$router->post('/admin/modules/tenant-toggle', [AdminController::class, 'toggleTenantModule'], [AdminMiddleware::class]);

// ─────────────────────────────────────────────────────────────
// STORAGE (Octopus Arm)
// ─────────────────────────────────────────────────────────────
use Saec\Controllers\AdminStorageController;

$router->get('/admin/storage', [AdminStorageController::class, 'index'], [AdminMiddleware::class]);

// Providers
$router->get('/admin/storage/providers', [AdminStorageController::class, 'providers'], [AdminMiddleware::class]);
$router->get('/admin/storage/providers/{id}/json', [AdminStorageController::class, 'getProvider'], [AdminMiddleware::class]);
$router->post('/admin/storage/providers', [AdminStorageController::class, 'createProvider'], [AdminMiddleware::class]);
$router->put('/admin/storage/providers/{id}', [AdminStorageController::class, 'updateProvider'], [AdminMiddleware::class]);
$router->delete('/admin/storage/providers/{id}', [AdminStorageController::class, 'deleteProvider'], [AdminMiddleware::class]);
$router->post('/admin/storage/providers/{id}/test', [AdminStorageController::class, 'testProvider'], [AdminMiddleware::class]);

// Backups
$router->get('/admin/storage/backups', [AdminStorageController::class, 'backups'], [AdminMiddleware::class]);
$router->post('/admin/storage/backups', [AdminStorageController::class, 'createBackup'], [AdminMiddleware::class]);
$router->post('/admin/storage/backups/{id}/restore', [AdminStorageController::class, 'restoreBackup'], [AdminMiddleware::class]);
$router->delete('/admin/storage/backups/{id}', [AdminStorageController::class, 'deleteBackup'], [AdminMiddleware::class]);

// Mounts
$router->get('/admin/storage/mounts', [AdminStorageController::class, 'mounts'], [AdminMiddleware::class]);
$router->post('/admin/storage/mounts', [AdminStorageController::class, 'createMount'], [AdminMiddleware::class]);
$router->post('/admin/storage/mounts/{id}/sync', [AdminStorageController::class, 'syncMount'], [AdminMiddleware::class]);
$router->delete('/admin/storage/mounts/{id}', [AdminStorageController::class, 'deleteMount'], [AdminMiddleware::class]);
$router->get('/admin/storage/mounts/{id}/browse', [AdminStorageController::class, 'browseMount'], [AdminMiddleware::class]);

// Schedules
$router->get('/admin/storage/schedules', [AdminStorageController::class, 'schedules'], [AdminMiddleware::class]);
$router->post('/admin/storage/schedules', [AdminStorageController::class, 'createSchedule'], [AdminMiddleware::class]);
$router->put('/admin/storage/schedules/{id}', [AdminStorageController::class, 'updateSchedule'], [AdminMiddleware::class]);
$router->delete('/admin/storage/schedules/{id}', [AdminStorageController::class, 'deleteSchedule'], [AdminMiddleware::class]);
$router->post('/admin/storage/schedules/{id}/run', [AdminStorageController::class, 'runSchedule'], [AdminMiddleware::class]);

// Cron tick (no auth, token-based)
$router->get('/admin/storage/tick', [AdminStorageController::class, 'tick']);

// ─────────────────────────────────────────────────────────────
// API (pour client desktop futur)
// ─────────────────────────────────────────────────────────────
$router->post('/api/login', [AuthController::class, 'login']);
$router->get('/api/files', [FileController::class, 'index'], [AuthMiddleware::class]);
$router->post('/api/files/upload', [FileController::class, 'upload'], [AuthMiddleware::class]);
$router->get('/api/files/{id}/download', [FileController::class, 'download'], [AuthMiddleware::class]);
