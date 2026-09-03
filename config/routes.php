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
// ROOT → REDIRECT
// ─────────────────────────────────────────────────────────────
$router->get('/', function() {
    header('Location: /login');
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

// ─────────────────────────────────────────────────────────────
// FILES
// ─────────────────────────────────────────────────────────────
$router->get('/files', [FolderController::class, 'index'], [AuthMiddleware::class]);
$router->post('/files/upload', [FileController::class, 'upload'], [AuthMiddleware::class]);
$router->get('/files/{id}/download', [FileController::class, 'download'], [AuthMiddleware::class]);
$router->get('/files/{id}/preview', [FileController::class, 'preview'], [AuthMiddleware::class]);
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
// API (pour client desktop futur)
// ─────────────────────────────────────────────────────────────
$router->post('/api/login', [AuthController::class, 'login']);
$router->get('/api/files', [FileController::class, 'index'], [AuthMiddleware::class]);
$router->post('/api/files/upload', [FileController::class, 'upload'], [AuthMiddleware::class]);
$router->get('/api/files/{id}/download', [FileController::class, 'download'], [AuthMiddleware::class]);
