<?php
$user = $user ?? [];
$stats = $stats ?? [
    'total_files' => 0, 'total_size' => 0, 'total_shares' => 0,
    'storage_quota' => 10737418240, 'users' => 0, 'folders' => 0,
    'api_calls_today' => 0, 'uptime' => '99.9%',
];
$recent_activity = $recent_activity ?? [];
$system_status = $system_status ?? ['api' => 'online', 'db' => 'online', 'storage' => 'online', 'cdn' => 'online'];
$pct = $stats['storage_quota'] > 0 ? round(($stats['total_size'] / $stats['storage_quota']) * 100) : 0;

$actionIcons = [
    'download' => ['icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>', 'color' => 'var(--cyan-400)', 'bg' => 'rgba(6,182,212,0.1)'],
    'login' => ['icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>', 'color' => 'var(--blue-500)', 'bg' => 'rgba(37,99,235,0.1)'],
    'upload' => ['icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>', 'color' => 'var(--emerald-400)', 'bg' => 'rgba(16,185,129,0.1)'],
    'share' => ['icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>', 'color' => 'var(--purple-500)', 'bg' => 'rgba(139,92,246,0.1)'],
];
?>

<!-- Page Header -->
<div class="page-header animate-fade-in">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </span>
            <?= t('dashboard.title') ?>
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <span style="color:var(--text-muted);">Bonjour, <?= htmlspecialchars($user['email'] ?? '') ?></span>
        </div>
    </div>
    <div class="header-actions">
        <span class="badge badge-success">
            <span style="width:6px; height:6px; border-radius:50%; background:var(--emerald-400); animation:pulse 2s infinite;"></span>
            Système nominal
        </span>
    </div>
</div>

<!-- KPI Grid -->
<section class="kpi-grid animate-slide-up">
    <div class="kpi-card blue">
        <div class="kpi-icon" style="background:rgba(37,99,235,0.12); color:var(--blue-500);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="kpi-label"><?= t('dashboard.storage_used') ?></div>
        <div class="kpi-value" style="color:var(--blue-400);"><?= $this->formatSize($stats['total_size']) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            / <?= $this->formatSize($stats['storage_quota']) ?>
            <span class="kpi-variation positive"> <?= $pct ?>%</span>
        </div>
        <div style="height:4px; background:var(--bg-secondary); border-radius:2px; margin-top:var(--space-3); overflow:hidden;">
            <div style="height:100%; width:<?= min($pct, 100) ?>%; background:linear-gradient(90deg, var(--cyan-400), var(--blue-500)); border-radius:2px;"></div>
        </div>
    </div>

    <div class="kpi-card cyan">
        <div class="kpi-icon" style="background:rgba(6,182,212,0.12); color:var(--cyan-400);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="kpi-label">Fichiers</div>
        <div class="kpi-value"><?= number_format($stats['total_files']) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <?= number_format($stats['folders'] ?? 0) ?> dossiers
        </div>
    </div>

    <div class="kpi-card emerald">
        <div class="kpi-icon" style="background:rgba(16,185,129,0.12); color:var(--emerald-400);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
        </div>
        <div class="kpi-label">Partages</div>
        <div class="kpi-value"><?= number_format($stats['total_shares']) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            liens actifs
        </div>
    </div>

    <div class="kpi-card amber">
        <div class="kpi-icon" style="background:rgba(245,158,11,0.12); color:var(--amber-400);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="kpi-label">Uptime</div>
        <div class="kpi-value" style="color:var(--emerald-400);"><?= $stats['uptime'] ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            30 derniers jours
        </div>
    </div>
</section>

<!-- Quick Actions -->
<section style="display:grid; grid-template-columns:repeat(3, 1fr); gap:var(--space-4); margin-bottom:var(--space-6);">
    <a href="/files" class="card" style="text-decoration:none; cursor:pointer; padding:var(--space-5); display:flex; align-items:center; gap:var(--space-4);">
        <div style="width:44px; height:44px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div>
            <div style="font-size:14px; font-weight:600; color:var(--text-primary);">Mes fichiers</div>
            <div style="font-size:12px; color:var(--text-muted);">Gérer et organiser</div>
        </div>
    </a>
    <a href="/shares" class="card" style="text-decoration:none; cursor:pointer; padding:var(--space-5); display:flex; align-items:center; gap:var(--space-4);">
        <div style="width:44px; height:44px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--emerald-400), var(--cyan-400)); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
        </div>
        <div>
            <div style="font-size:14px; font-weight:600; color:var(--text-primary);">Mes partages</div>
            <div style="font-size:12px; color:var(--text-muted);"><?= $stats['total_shares'] ?> liens actifs</div>
        </div>
    </a>
    <a href="/config" class="card" style="text-decoration:none; cursor:pointer; padding:var(--space-5); display:flex; align-items:center; gap:var(--space-4);">
        <div style="width:44px; height:44px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--amber-400), var(--rose-500)); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </div>
        <div>
            <div style="font-size:14px; font-weight:600; color:var(--text-primary);">Paramètres</div>
            <div style="font-size:12px; color:var(--text-muted);">Profil et sécurité</div>
        </div>
    </a>
</section>

<!-- Dashboard Grid -->
<section class="dashboard-grid">
    <!-- System Status -->
    <div class="card">
        <div class="card-header">
            <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; color:var(--emerald-400);"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                État du système
            </span>
            <span class="badge badge-success">En ligne</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3);">
            <?php
            $statusItems = [
                ['label' => 'API REST', 'status' => $system_status['api'] ?? 'online', 'icon' => '⚡'],
                ['label' => 'Base de données', 'status' => $system_status['db'] ?? 'online', 'icon' => '🗄'],
                ['label' => 'Stockage', 'status' => $system_status['storage'] ?? 'online', 'icon' => '💾'],
                ['label' => 'CDN / Edge', 'status' => $system_status['cdn'] ?? 'online', 'icon' => '🌐'],
            ];
            foreach ($statusItems as $item):
                $isOnline = $item['status'] === 'online';
            ?>
            <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3); background:var(--bg-secondary); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                <span style="width:8px; height:8px; border-radius:50%; background:<?= $isOnline ? 'var(--emerald-400)' : 'var(--rose-500)' ?>; animation:pulse 2s infinite;"></span>
                <div style="flex:1;">
                    <div style="font-size:11px; color:var(--text-muted);"><?= $item['label'] ?></div>
                    <div style="font-size:12px; font-weight:500; color:var(--text-primary);"><?= $isOnline ? 'En ligne' : 'Hors ligne' ?></div>
                </div>
                <span style="font-size:14px;"><?= $item['icon'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; color:var(--cyan-400);"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Activité récente
            </span>
        </div>
        <?php if (!empty($recent_activity)): ?>
        <div style="display:flex; flex-direction:column; gap:var(--space-2);">
            <?php foreach (array_slice($recent_activity, 0, 6) as $act):
                $actData = $actionIcons[$act['type'] ?? 'login'] ?? $actionIcons['login'];
            ?>
            <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-2) var(--space-3); border-radius:var(--radius-sm); transition:background 0.15s;" onmouseenter="this.style.background='var(--bg-card-hover)'" onmouseleave="this.style.background='transparent'">
                <div style="width:28px; height:28px; border-radius:var(--radius-sm); background:<?= $actData['bg'] ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:<?= $actData['color'] ?>;">
                    <?= $actData['icon'] ?>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:12px; font-weight:500; color:var(--text-primary);"><?= htmlspecialchars($act['action'] ?? '') ?></div>
                    <div style="font-size:11px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($act['detail'] ?? '') ?></div>
                </div>
                <div style="font-size:11px; color:var(--text-muted); white-space:nowrap;"><?= $act['time'] ?? '' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:var(--space-8); color:var(--text-muted); font-size:13px;">
            Aucune activité récente
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span style="font-family:var(--font-mono); font-size:11px;">AES-256-GCM · <?= t('app.footer_stack') ?></span>
</div>
