<?php
$user = $user ?? [];
$stats = $stats ?? [];
$tenantDetails = $tenantDetails ?? [];
$recentAudit = $recentAudit ?? [];
$expiringTenants = $expiring_tenants ?? [];
$system = $system ?? [];
?>

<!-- Page Header -->
<div class="page-header">
    <h1><?= t('admin.title') ?></h1>
    <div class="header-actions">
        <span class="badge badge-success">● Online</span>
    </div>
</div>

<!-- KPI Cards -->
<section class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label"><?= t('admin.tenants') ?></div>
        <div class="kpi-value"><?= (int)($stats['tenants'] ?? 0) ?></div>
        <div style="font-size:13px; color:var(--text-secondary);">
            <span class="kpi-delta positive">↑ <?= (int)($stats['tenants_active'] ?? 0) ?></span> active
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label"><?= t('admin.users') ?></div>
        <div class="kpi-value"><?= (int)($stats['users'] ?? 0) ?></div>
        <div style="font-size:13px; color:var(--text-secondary);">
            <?= (int)($stats['users_admin'] ?? 0) ?> admins
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label"><?= t('dashboard.total_files') ?></div>
        <div class="kpi-value"><?= (int)($stats['files'] ?? 0) ?></div>
        <div style="font-size:13px; color:var(--text-secondary);">
            <?= $this->formatSize($stats['storage_used'] ?? 0) ?> stored
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label"><?= t('admin.audit') ?></div>
        <div class="kpi-value"><?= (int)($stats['login_attempts_24h'] ?? 0) ?></div>
        <div style="font-size:13px; color:var(--text-secondary);">
            <?= (int)($stats['login_failed_24h'] ?? 0) ?> failed (24h)
        </div>
    </div>
</section>

<!-- Dashboard Grid -->
<section class="dashboard-grid">
    <!-- Tenants -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><?= t('admin.tenants') ?></span>
            <a href="/admin/tenants" class="card-action">View all →</a>
        </div>
        <?php if (!empty($tenantDetails)): ?>
        <div class="file-list" style="border:none;">
            <?php foreach (array_slice($tenantDetails, 0, 5) as $t): ?>
            <div class="file-row">
                <div class="row-icon" style="background:rgba(59,130,246,0.1); color:var(--blue-400);">🏢</div>
                <div class="row-info">
                    <div class="row-name"><?= htmlspecialchars($t['name']) ?></div>
                    <div class="row-meta">
                        <?= (int)($t['user_count'] ?? 0) ?> users · <?= $this->formatSize($t['storage_used'] ?? 0) ?>
                    </div>
                </div>
                <div class="row-size">
                    <?php if ($t['end_date'] && strtotime($t['end_date']) < time()): ?>
                    <span class="badge badge-danger">Expired</span>
                    <?php elseif ($t['end_date'] && strtotime($t['end_date']) < strtotime('+30 days')): ?>
                    <span class="badge badge-warning">Expiring</span>
                    <?php else: ?>
                    <span class="badge badge-success">Active</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:var(--space-8); color:var(--text-muted);">No tenants</div>
        <?php endif; ?>
    </div>

    <!-- Recent Audit -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><?= t('admin.audit') ?></span>
            <a href="/admin/audit" class="card-action">View all →</a>
        </div>
        <?php if (!empty($recentAudit)): ?>
        <div class="activity-list">
            <?php foreach (array_slice($recentAudit, 0, 6) as $log): ?>
            <div class="activity-item">
                <div class="act-icon">
                    <?php
                    $icons = ['file.uploaded' => '⬆', 'file.downloaded' => '⬇', 'file.deleted' => '🗑',
                              'share.created' => '🔗', 'share.revoked' => '❌', 'tenant.created' => '🏢',
                              'user.created' => '👤', 'module.enabled' => '🧩', 'module.disabled' => '🧩'];
                    echo $icons[$log['action']] ?? '📋';
                    ?>
                </div>
                <div class="act-content">
                    <div class="act-action"><?= htmlspecialchars($log['action']) ?></div>
                    <div class="act-detail"><?= htmlspecialchars($log['email'] ?? 'system') ?></div>
                </div>
                <div class="act-time"><?= date('H:i', strtotime($log['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:var(--space-8); color:var(--text-muted);">No audit logs</div>
        <?php endif; ?>
    </div>
</section>

<!-- System Status -->
<section class="card" style="margin-bottom:0;">
    <div class="card-header">
        <span class="card-title">System</span>
        <span class="badge badge-success">OK</span>
    </div>
    <div class="status-grid">
        <div class="status-item">
            <span class="dot-green" style="width:8px; height:8px; border-radius:50%; background:var(--emerald-400);"></span>
            <span class="status-label">Database</span>
            <span class="status-value"><?= $system['db_status'] ?? 'OK' ?></span>
        </div>
        <div class="status-item">
            <span class="dot-green" style="width:8px; height:8px; border-radius:50%; background:var(--emerald-400);"></span>
            <span class="status-label">PHP</span>
            <span class="status-value"><?= $system['php_version'] ?? PHP_VERSION ?></span>
        </div>
        <div class="status-item">
            <span class="dot-green" style="width:8px; height:8px; border-radius:50%; background:var(--emerald-400);"></span>
            <span class="status-label">Disk</span>
            <span class="status-value"><?= $this->formatSize($system['disk_free'] ?? 0) ?> free</span>
        </div>
        <div class="status-item">
            <span class="dot-green" style="width:8px; height:8px; border-radius:50%; background:var(--emerald-400);"></span>
            <span class="status-label">Sessions</span>
            <span class="status-value"><?= (int)($stats['active_sessions'] ?? 0) ?> active</span>
        </div>
    </div>
</section>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span><?= t('app.footer_stack') ?></span>
</div>
