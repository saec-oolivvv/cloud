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

        $data = [
            'user' => $user,
            'stats' => [
                'total_files' => (int) ($stats['total_files'] ?? 0),
                'total_size' => (int) ($stats['total_size'] ?? 0),
                'total_shares' => (int) ($shares['total_shares'] ?? 0),
                'storage_quota' => (int) ($tenant['storage_quota'] ?? 10737418240),
            ],
            'recent_files' => $recentFiles,
            'pageTitle' => 'Dashboard',
        ];

        $this->view('dashboard/index', $data);
    }

    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
