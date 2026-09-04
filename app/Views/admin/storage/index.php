<?php
/** @var array $stats */
/** @var array $schedulerStats */
$stats = $stats ?? [];
$schedulerStats = $schedulerStats ?? [];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </span>
            Storage — Octopus
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <a href="/admin" style="color:var(--text-secondary);">Admin</a> / <span>Storage</span>
        </div>
    </div>
    <div class="header-actions">
        <a href="/admin/storage/providers" class="btn btn-outline" style="gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Providers
        </a>
        <a href="/admin/storage/backups" class="btn btn-outline" style="gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Backups
        </a>
        <a href="/admin/storage/mounts" class="btn btn-outline" style="gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Mounts
        </a>
        <a href="/admin/storage/schedules" class="btn btn-primary" style="gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Schedules
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:var(--space-4); margin-bottom:var(--space-6);">
    <div class="kpi-card blue">
        <div class="kpi-label">Providers</div>
        <div class="kpi-value"><?= $stats['total_providers'] ?? 0 ?></div>
        <div class="kpi-sub"><?= $stats['active_providers'] ?? 0 ?> actifs</div>
    </div>
    <div class="kpi-card emerald">
        <div class="kpi-label">Backups (7j)</div>
        <div class="kpi-value" style="color:var(--emerald-400);"><?= $schedulerStats['backups_7d'] ?? 0 ?></div>
        <div class="kpi-sub"><?= $schedulerStats['failed_7d'] ?? 0 ?> échecs</div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-label">Mounts</div>
        <div class="kpi-value"><?= $stats['total_mounts'] ?? 0 ?></div>
    </div>
    <div class="kpi-card amber">
        <div class="kpi-label">Schedules</div>
        <div class="kpi-value" style="color:var(--amber-400);"><?= $schedulerStats['active_schedules'] ?? 0 ?></div>
        <div class="kpi-sub">actifs</div>
    </div>
    <div class="kpi-card rose">
        <div class="kpi-label">Prochain run</div>
        <div class="kpi-value" style="font-size:14px; color:var(--rose-500);">
            <?= $schedulerStats['next_run'] ? date('d/m H:i', strtotime($schedulerStats['next_run'])) : 'Aucun' ?>
        </div>
    </div>
</div>

<!-- Providers List -->
<?php if (!empty($stats['providers'])): ?>
<div class="card" style="margin-bottom:var(--space-6);">
    <div class="card-header">
        <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
            <span class="settings-section-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </span>
            Providers configurés
        </span>
        <a href="/admin/storage/providers" class="btn btn-sm btn-outline">Voir tout</a>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:var(--space-3); padding:var(--space-5);">
        <?php foreach (array_slice($stats['providers'] ?? [], 0, 6) as $provider): ?>
        <div style="padding:var(--space-4); background:var(--bg-secondary); border:1px solid var(--border-subtle); border-radius:var(--radius-lg); transition:all 0.15s;">
            <div style="display:flex; align-items:center; gap:var(--space-3); margin-bottom:var(--space-3);">
                <div style="width:36px; height:36px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center; font-size:16px;">
                    <?php
                    $icons = ['s3' => '☁️', 'sftp' => '🔐', 'ftp' => '📁', 'webdav' => '🌐', 'gdrive' => '🔷', 'dropbox' => '📦', 'onedrive' => '🔷', 'local' => '💾'];
                    echo $icons[$provider['type']] ?? '📁';
                    ?>
                </div>
                <div>
                    <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($provider['name']) ?></div>
                    <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;"><?= $provider['type'] ?></div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:var(--space-2); font-size:12px;">
                <?php if ($provider['is_default']): ?>
                <span style="padding:2px 8px; border-radius:var(--radius-sm); background:rgba(37,99,235,0.1); color:var(--blue-400); border:1px solid rgba(37,99,235,0.2);">Default</span>
                <?php endif; ?>
                <span style="padding:2px 8px; border-radius:var(--radius-sm); background:<?= $provider['is_active'] ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' ?>; color:<?= $provider['is_active'] ? 'var(--emerald-400)' : 'var(--rose-500)' ?>; border:1px solid <?= $provider['is_active'] ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)' ?>;">
                    <?= $provider['is_active'] ? 'Actif' : 'Inactif' ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:var(--space-4);">
    <a href="/admin/storage/providers" class="card" style="padding:var(--space-5); text-decoration:none; border:1px solid var(--border-subtle); transition:all 0.2s; cursor:pointer;" onmouseover="this.style.borderColor='rgba(37,99,235,0.3)'" onmouseout="this.style.borderColor='var(--border-subtle)'">
        <div style="display:flex; align-items:center; gap:var(--space-3); margin-bottom:var(--space-3);">
            <div style="width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center; font-size:18px;">☁️</div>
            <div>
                <div style="font-weight:600; font-size:15px; color:var(--text-primary);">Providers</div>
                <div style="font-size:12px; color:var(--text-muted);">Connecter des sources de stockage</div>
            </div>
        </div>
        <div style="font-size:12px; color:var(--text-muted);">
            S3, R2, MinIO, SFTP, FTP, WebDAV, Google Drive, Dropbox, OneDrive...
        </div>
    </a>

    <a href="/admin/storage/backups" class="card" style="padding:var(--space-5); text-decoration:none; border:1px solid var(--border-subtle); transition:all 0.2s; cursor:pointer;" onmouseover="this.style.borderColor='rgba(16,185,129,0.3)'" onmouseout="this.style.borderColor='var(--border-subtle)'">
        <div style="display:flex; align-items:center; gap:var(--space-3); margin-bottom:var(--space-3);">
            <div style="width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--emerald-400), var(--cyan-400)); display:flex; align-items:center; justify-content:center; font-size:18px;">💾</div>
            <div>
                <div style="font-weight:600; font-size:15px; color:var(--text-primary);">Backups</div>
                <div style="font-size:12px; color:var(--text-muted);">Sauvegarder et restaurer</div>
            </div>
        </div>
        <div style="font-size:12px; color:var(--text-muted);">
            Full, Incremental, Differential, DB-only, File-only
        </div>
    </a>

    <a href="/admin/storage/mounts" class="card" style="padding:var(--space-5); text-decoration:none; border:1px solid var(--border-subtle); transition:all 0.2s; cursor:pointer;" onmouseover="this.style.borderColor='rgba(6,182,212,0.3)'" onmouseout="this.style.borderColor='var(--border-subtle)'">
        <div style="display:flex; align-items:center; gap:var(--space-3); margin-bottom:var(--space-3);">
            <div style="width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--cyan-400), var(--blue-500)); display:flex; align-items:center; justify-content:center; font-size:18px;">🌐</div>
            <div>
                <div style="font-weight:600; font-size:15px; color:var(--text-primary);">Mounts</div>
                <div style="font-size:12px; color:var(--text-muted);">Monter des dossiers distants</div>
            </div>
        </div>
        <div style="font-size:12px; color:var(--text-muted);">
            ReadOnly, ReadWrite, Sync bidirectionnel
        </div>
    </a>
</div>

<style>
.kpi-sub { font-size:11px; color:var(--text-muted); margin-top:2px; }
</style>
