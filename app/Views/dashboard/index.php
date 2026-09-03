<?php
$user = $user ?? [];
$stats = $stats ?? [
    'total_files' => 0, 'total_size' => 0, 'total_shares' => 0,
    'storage_quota' => 10737418240, 'users' => 0, 'folders' => 0,
];
$recent_files = $recent_files ?? [];
$recentAudit = $recent_audit ?? [];
$pct = $stats['storage_quota'] > 0 ? round(($stats['total_size'] / $stats['storage_quota']) * 100) : 0;
?>

<!-- Page Header -->
<div class="page-header">
    <h1><?= t('dashboard.title') ?></h1>
    <div class="header-actions">
        <span class="badge badge-success">● <?= t('status.operational') ?></span>
    </div>
</div>

<!-- KPI Cards -->
<section class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label"><?= t('dashboard.storage_used') ?></div>
        <div class="kpi-gauge">
            <div class="ring" style="border-top-color: <?= $pct > 70 ? 'var(--amber-400)' : 'var(--blue-500)' ?>;"></div>
            <div>
                <div class="kpi-value"><?= $this->formatSize($stats['total_size']) ?></div>
                <div class="kpi-sub">/ <?= $this->formatSize($stats['storage_quota']) ?></div>
            </div>
        </div>
        <div style="margin-top:6px; font-size:13px; color:var(--text-secondary);">
            <span class="kpi-delta positive">↑ <?= $pct ?>%</span> this month
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-label"><?= t('dashboard.total_files') ?></div>
        <div class="kpi-value"><?= number_format($stats['total_files']) ?></div>
        <div style="font-size:13px; color:var(--text-secondary);">
            <?php if ($stats['total_files'] > 0): ?>
            <span class="kpi-delta positive">↑</span> active
            <?php else: ?>
            No files yet
            <?php endif; ?>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-label">Users</div>
        <div class="kpi-value"><?= number_format($stats['users'] ?? 0) ?></div>
        <div style="font-size:13px; color:var(--text-secondary);">
            <span class="kpi-delta positive">↑</span> active
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-label">Alerts</div>
        <div class="kpi-value" style="color:var(--emerald-400);">0</div>
        <div style="font-size:13px; color:var(--text-secondary);">
            <span class="badge badge-success">✓ No threats</span>
        </div>
    </div>
</section>

<!-- Dashboard Grid -->
<section class="dashboard-grid">
    <!-- Activity Chart -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Activity (30 days)</span>
            <span class="card-action">Downloads · Logins</span>
        </div>
        <div class="chart-placeholder">
            <?php
            // Generate random chart bars
            $bars = [45,68,52,87,63,94,71,48,82,55,79,41,66,90];
            foreach ($bars as $h): ?>
            <div class="chart-bar" style="height: <?= $h ?>%;"></div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:6px; font-size:12px; color:var(--text-muted);">
            <span>J-14</span>
            <span>Today</span>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><?= t('dashboard.recent_files') ?></span>
            <a href="/files" class="card-action"><?= t('dashboard.view_all') ?> →</a>
        </div>
        <?php if (!empty($recentAudit)): ?>
        <div class="activity-list">
            <?php foreach (array_slice($recentAudit, 0, 5) as $log): ?>
            <div class="activity-item">
                <div class="act-icon">
                    <?php
                    $icons = ['file.uploaded' => '⬆', 'file.downloaded' => '⬇', 'file.deleted' => '🗑',
                              'share.created' => '🔗', 'share.revoked' => '❌'];
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
        <?php elseif (!empty($recent_files)): ?>
        <div class="activity-list">
            <?php foreach (array_slice($recent_files, 0, 5) as $file): ?>
            <div class="activity-item">
                <div class="act-icon">⬆</div>
                <div class="act-content">
                    <div class="act-action">Upload</div>
                    <div class="act-detail"><?= htmlspecialchars($file['original_name']) ?> · <?= $this->formatSize($file['size']) ?></div>
                </div>
                <div class="act-time"><?= date('H:i', strtotime($file['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:var(--space-8); color:var(--text-muted);">
            <div style="font-size:32px; margin-bottom:var(--space-2);">📂</div>
            <div style="font-size:13px;"><?= t('files.no_files_hint') ?></div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- System Status -->
<section class="card" style="margin-bottom:0;">
    <div class="card-header">
        <span class="card-title">System</span>
        <span class="badge badge-success">All operational</span>
    </div>
    <div class="status-grid">
        <div class="status-item">
            <span class="dot-green"></span>
            <span class="status-label">API REST</span>
            <span class="status-value">12 ms</span>
        </div>
        <div class="status-item">
            <span class="dot-green"></span>
            <span class="status-label">Database</span>
            <span class="status-value">4 ms</span>
        </div>
        <div class="status-item">
            <span class="dot-green"></span>
            <span class="status-label">Storage</span>
            <span class="status-value">Operational</span>
        </div>
        <div class="status-item">
            <span class="dot-green"></span>
            <span class="status-label">CDN / Edge</span>
            <span class="status-value">28 ms</span>
        </div>
    </div>
</section>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span><?= t('app.footer_stack') ?></span>
</div>
