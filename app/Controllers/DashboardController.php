<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $tenantId = $user['tenant_id'];

        // Stats fichiers
        $stats = $db->fetch(
            "SELECT 
                COUNT(*) as total_files,
                COALESCE(SUM(size), 0) as total_size
             FROM files 
             WHERE tenant_id = ? AND deleted_at IS NULL",
            [$tenantId]
        );

        // Dossiers
        $folders = $db->fetch(
            "SELECT COUNT(*) as total_folders
             FROM folders
             WHERE tenant_id = ?",
            [$tenantId]
        );

        // Quota tenant
        $tenant = $db->fetch(
            "SELECT storage_quota FROM tenants WHERE id = ?",
            [$tenantId]
        );

        // Partages actifs
        $shares = $db->fetch(
            "SELECT COUNT(*) as total_shares 
             FROM shares 
             WHERE tenant_id = ? AND (expires_at IS NULL OR expires_at > NOW())",
            [$tenantId]
        );

        // Derniers fichiers
        $recentFiles = $db->fetchAll(
            "SELECT id, original_name, size, created_at 
             FROM files 
             WHERE tenant_id = ? AND deleted_at IS NULL
             ORDER BY created_at DESC 
             LIMIT 5",
            [$tenantId]
        );

        // Activité récente (audit logs)
        $recentActivity = $db->fetchAll(
            "SELECT action as type, resource_type as detail, created_at
             FROM audit_logs
             WHERE tenant_id = ?
             ORDER BY created_at DESC
             LIMIT 6",
            [$tenantId]
        );

        $data = [
            'user' => $user,
            'stats' => [
                'total_files' => (int) ($stats['total_files'] ?? 0),
                'total_size' => (int) ($stats['total_size'] ?? 0),
                'total_shares' => (int) ($shares['total_shares'] ?? 0),
                'storage_quota' => (int) ($tenant['storage_quota'] ?? 10737418240),
                'folders' => (int) ($folders['total_folders'] ?? 0),
                'uptime' => '99.9%',
            ],
            'recent_files' => $recentFiles,
            'recent_activity' => $recentActivity,
            'system_status' => [
                'api' => 'online',
                'db' => 'online',
                'storage' => 'online',
                'cdn' => 'online',
            ],
            'pageTitle' => 'Dashboard',
        ];

        $this->view('dashboard/index', $data);
    }
}
