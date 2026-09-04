<?php
$user = $user ?? [];
$stats = $stats ?? [];
$tenantDetails = $tenantDetails ?? [];
$recentAudit = $recentAudit ?? [];
$expiringTenants = $expiring_tenants ?? [];
$system = $system ?? [];

$actionIcons = [
    'auth.login' => ['icon' => '🔐', 'color' => 'var(--blue-500)'],
    'auth.logout' => ['icon' => '🚪', 'color' => 'var(--text-muted)'],
    'file.uploaded' => ['icon' => '📤', 'color' => 'var(--emerald-400)'],
    'file.downloaded' => ['icon' => '📥', 'color' => 'var(--cyan-400)'],
    'file.deleted' => ['icon' => '🗑', 'color' => 'var(--rose-500)'],
    'folder.created' => ['icon' => '📁', 'color' => 'var(--amber-400)'],
    'share.created' => ['icon' => '🔗', 'color' => 'var(--purple-500)'],
    'tenant.created' => ['icon' => '🏢', 'color' => 'var(--blue-500)'],
    'user.created' => ['icon' => '👤', 'color' => 'var(--emerald-400)'],
];

$diskPct = ($system['disk_total'] ?? 1) > 0 ? round((1 - ($system['disk_free'] ?? 0) / $system['disk_total']) * 100) : 0;
?>

<!-- Page Header -->
<div class="page-header animate-fade-in">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--purple-500), var(--blue-500));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <?= t('admin.title') ?>
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <a href="/dashboard" style="color:var(--text-secondary);">Accueil</a> / <span>Administration</span>
        </div>
    </div>
    <div class="header-actions">
        <span class="badge badge-success">
            <span style="width:6px; height:6px; border-radius:50%; background:var(--emerald-400); animation:pulse 2s infinite;"></span>
            Online
        </span>
    </div>
</div>

<!-- KPI Cards -->
<section class="kpi-grid animate-slide-up">
    <div class="kpi-card blue">
        <div class="kpi-icon" style="background:rgba(37,99,235,0.12); color:var(--blue-500);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="kpi-label"><?= t('admin.tenants') ?></div>
        <div class="kpi-value"><?= (int)($stats['tenants'] ?? 0) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <span class="kpi-variation positive"> <?= (int)($stats['tenants_active'] ?? 0) ?> actifs</span>
            <?php if (($stats['tenants_expired'] ?? 0) > 0): ?>
            · <span class="kpi-variation negative"><?= $stats['tenants_expired'] ?> expirés</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="kpi-card emerald">
        <div class="kpi-icon" style="background:rgba(16,185,129,0.12); color:var(--emerald-400);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div class="kpi-label"><?= t('admin.users') ?></div>
        <div class="kpi-value"><?= (int)($stats['users'] ?? 0) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <?= (int)($stats['users_admin'] ?? 0) ?> admins
        </div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-icon" style="background:rgba(6,182,212,0.12); color:var(--cyan-400);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="kpi-label"><?= t('dashboard.total_files') ?></div>
        <div class="kpi-value"><?= (int)($stats['files'] ?? 0) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <?= $this->formatSize($stats['storage_used'] ?? 0) ?> stockés
        </div>
    </div>
    <div class="kpi-card rose">
        <div class="kpi-icon" style="background:rgba(239,68,68,0.12); color:var(--rose-500);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="kpi-label">Sécurité (24h)</div>
        <div class="kpi-value"><?= (int)($stats['login_attempts_24h'] ?? 0) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <?= (int)($stats['login_failed_24h'] ?? 0) ?> échecs
            <?php if (($stats['ip_banned'] ?? 0) > 0): ?>
            · <?= $stats['ip_banned'] ?> IPs bannies
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Dashboard Grid -->
<section class="dashboard-grid">
    <!-- Tenants -->
    <div class="card">
        <div class="card-header">
            <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; color:var(--blue-500);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Tenants récents
            </span>
            <a href="/admin/tenants" class="btn btn-outline btn-sm">Voir tout →</a>
        </div>
        <?php if (!empty($tenantDetails)): ?>
        <div style="display:flex; flex-direction:column; gap:var(--space-2);">
            <?php foreach (array_slice($tenantDetails, 0, 5) as $t):
                $isActive = $t['active'] && (!$t['end_date'] || strtotime($t['end_date']) >= time());
                $isExpired = $t['end_date'] && strtotime($t['end_date']) < time();
                $isExpiring = $t['end_date'] && strtotime($t['end_date']) >= time() && strtotime($t['end_date']) < strtotime('+30 days');
                if ($isExpired) $badge = '<span class="badge badge-danger" style="font-size:10px;">Expiré</span>';
                elseif ($isExpiring) $badge = '<span class="badge badge-warning" style="font-size:10px;">Expirant</span>';
                else $badge = '<span class="badge badge-success" style="font-size:10px;">Actif</span>';
                $pct = ($t['storage_quota'] ?? 1) > 0 ? min(100, (($t['storage_used'] ?? 0) / $t['storage_quota']) * 100) : 0;
            ?>
            <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3); border-radius:var(--radius-md); border:1px solid var(--border-subtle); transition:all 0.15s;" onmouseenter="this.style.borderColor='var(--border-active)'" onmouseleave="this.style.borderColor='var(--border-subtle)'">
                <div style="width:36px; height:36px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; flex-shrink:0;">
                    <?= strtoupper(substr($t['name'], 0, 2)) ?>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:var(--space-2);">
                        <span style="font-size:13px; font-weight:500; color:var(--text-primary);"><?= htmlspecialchars($t['name']) ?></span>
                        <?= $badge ?>
                    </div>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                        <?= (int)($t['user_count'] ?? 0) ?> users · <?= $this->formatSize($t['storage_used'] ?? 0) ?>
                    </div>
                    <div style="height:3px; background:var(--bg-secondary); border-radius:2px; margin-top:6px; overflow:hidden;">
                        <div style="height:100%; width:<?= round($pct) ?>%; background:<?= $pct > 90 ? 'var(--rose-500)' : ($pct > 70 ? 'var(--amber-400)' : 'linear-gradient(90deg, var(--cyan-400), var(--blue-500))') ?>; border-radius:2px;"></div>
                    </div>
                </div>
                <a href="/admin/tenants/<?= $t['id'] ?>/users" class="btn btn-outline btn-sm" style="flex-shrink:0; font-size:11px;">Gérer</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:var(--space-8); color:var(--text-muted);">Aucun tenant</div>
        <?php endif; ?>
    </div>

    <!-- Recent Audit -->
    <div class="card">
        <div class="card-header">
            <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; color:var(--emerald-400);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Audit
            </span>
            <a href="/admin/audit" class="btn btn-outline btn-sm">Voir tout →</a>
        </div>
        <?php if (!empty($recentAudit)): ?>
        <div style="display:flex; flex-direction:column; gap:var(--space-2);">
            <?php foreach (array_slice($recentAudit, 0, 6) as $log):
                $actData = $actionIcons[$log['action']] ?? ['icon' => '📋', 'color' => 'var(--text-muted)'];
            ?>
            <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-2) var(--space-3); border-radius:var(--radius-sm); transition:background 0.15s;" onmouseenter="this.style.background='var(--bg-card-hover)'" onmouseleave="this.style.background='transparent'">
                <span style="font-size:14px; width:24px; text-align:center;"><?= $actData['icon'] ?></span>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:12px; font-weight:500; color:var(--text-primary);"><?= htmlspecialchars($log['action']) ?></div>
                    <div style="font-size:11px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($log['email'] ?? 'system') ?></div>
                </div>
                <div style="font-size:11px; color:var(--text-muted); white-space:nowrap;"><?= date('H:i', strtotime($log['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:var(--space-8); color:var(--text-muted);">Aucun log</div>
        <?php endif; ?>
    </div>
</section>

<!-- System Status -->
<section class="card" style="margin-bottom:var(--space-6);">
    <div class="card-header">
        <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; color:var(--cyan-400);"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
            Système
        </span>
        <span class="badge badge-success">OK</span>
    </div>
    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:var(--space-3);">
        <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3); background:var(--bg-secondary); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
            <span style="width:8px; height:8px; border-radius:50%; background:var(--emerald-400);"></span>
            <div>
                <div style="font-size:11px; color:var(--text-muted);">Database</div>
                <div style="font-size:13px; font-weight:500; color:var(--text-primary);"><?= $system['db_status'] ?? 'OK' ?></div>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3); background:var(--bg-secondary); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
            <span style="width:8px; height:8px; border-radius:50%; background:var(--emerald-400);"></span>
            <div>
                <div style="font-size:11px; color:var(--text-muted);">PHP</div>
                <div style="font-size:13px; font-weight:500; color:var(--text-primary);"><?= $system['php_version'] ?? PHP_VERSION ?></div>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3); background:var(--bg-secondary); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
            <span style="width:8px; height:8px; border-radius:50%; background:<?= $diskPct > 90 ? 'var(--rose-500)' : ($diskPct > 70 ? 'var(--amber-400)' : 'var(--emerald-400)') ?>;"></span>
            <div>
                <div style="font-size:11px; color:var(--text-muted);">Disk</div>
                <div style="font-size:13px; font-weight:500; color:var(--text-primary);"><?= $this->formatSize($system['disk_free'] ?? 0) ?> libre</div>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3); background:var(--bg-secondary); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
            <span style="width:8px; height:8px; border-radius:50%; background:var(--emerald-400);"></span>
            <div>
                <div style="font-size:11px; color:var(--text-muted);">Sessions</div>
                <div style="font-size:13px; font-weight:500; color:var(--text-primary);"><?= (int)($stats['active_sessions'] ?? 0) ?> actives</div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span style="font-family:var(--font-mono); font-size:11px;"><?= t('app.footer_stack') ?></span>
</div>
