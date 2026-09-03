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
$storage_color = $pct > 80 ? 'warning' : ($pct > 50 ? 'info' : 'success');
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><?= t('dashboard.title') ?></h1>
    </div>
    <div class="page-header-right">
        <span class="badge badge-success">● Système nominal</span>
    </div>
</div>

<!-- KPI Grid -->
<section class="kpi-grid" style="margin-bottom:var(--space-6);">
    <div class="kpi-card blue" style="background:rgba(37,99,235,0.08); border-color:var(--blue-500);">
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="kpi-label"><?= t('dashboard.storage_used') ?></div>
        <div class="kpi-value" style="color:var(--blue-400);">
            <?= $this->formatSize($stats['total_size']) ?>
        </div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            / <?= $this->formatSize($stats['storage_quota']) ?>
            <span class="kpi-variation positive">↑ <?= $pct ?>%</span>
        </div>
    </div>

    <div class="kpi-card cyan">
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="kpi-label">Fichiers totaux</div>
        <div class="kpi-value"><?= number_format($stats['total_files']) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <span class="kpi-variation positive">↑</span> active
        </div>
    </div>

    <div class="kpi-card emerald">
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2v2M12 20v2M4.93 4.93l2.83 2.83a1 1 0 0 1 0 1.41l-2.83 2.83M13.5 9.5l2.12 2.12a1 1 0 0 1 0 1.41l-2.12 2.12M 9.5 13.5l 2.12 2.12a1 1 0 0 1 0 1.41l-2.12 2.12"/><line x1="1" y1="1" x2="23" y2="23"/></svg></div>
        <div class="kpi-label">Users</div>
        <div class="kpi-value"><?= number_format($stats['users'] ?? 0) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <span class="kpi-variation positive">↑</span> active
        </div>
    </div>

    <div class="kpi-card amber">
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
        <div class="kpi-label">Alertes</div>
        <div class="kpi-value" style="color:var(--amber-400);">0</div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <span class="badge badge-warning">Aucune</span>
        </div>
    </div>
</section>

<!-- Dashboard Grid -->
<section class="dashboard-grid" style="margin-bottom:var(--space-6);">
    <!-- Activity Chart -->
    <div class="card" style="background:rgba(35,38,50,0.8);">
        <div class="card-header">
            <span class="card-title">Activité (30 jours)</span>
            <span class="card-action" style="font-size:11px; color:var(--text-muted);">Voir tout →</span>
        </div>
        <div class="chart-placeholder">
            <div class="chart-title">Téléchargements & Connexions</div>
            <div class="chart-canvas">
                <?php
                // Generate chart data
                $days = ['J-28', 'J-27', 'J-26', 'J-25', 'J-24', 'J-23', 'J-22'];
                $downloads = [45, 68, 52, 87, 63, 94, 71];
                $logins = [38, 56, 49, 72, 54, 81, 65];
                ?>
                <div class="chart-axis">
                    <span>J-28</span><span>J-21</span>
                </div>
                <?php $combined = array_combine($downloads, $logins); foreach ($combined as $download => $login): ?>
                <div style="position:relative;">
                    <div class="chart-bar" style="height: <?= $download ?>%;"></div>
                    <div class="chart-bar" style="height: <?= $login ?>%;" style="background:var(--emerald-400);"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--text-muted); margin-top:6px;">
            <span>Téléchargements</span><span>Connexions</span>
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="card" style="background:rgba(35,38,50,0.8);">
        <div class="card-header">
            <span class="card-title">Activité récente</span>
            <span class="card-action" style="font-size:11px; color:var(--text-muted);">Voir tout →</span>
        </div>
        <div style="overflow-x:auto;">
        <table class="activity-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Utilisateur</th>
                    <th>Fichier</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $activities = [
                    ['download', 'admin@saec.me', 'rapport.pdf', 'il y a 15 min'],
                    ['login', 'john@client.com', '—', 'il y a 2h'],
                    ['upload', 'sarah@saec.me', 'presentation.pptx', 'il y a 4h'],
                    ['share', 'mike@saec.me', 'archive.zip', 'il y a 1j'],
                ];
                foreach ($activities as $act): ?>
                <tr>
                    <td>
                        <span class="act-icon" style="width:20px; height:20px;">
<?php 
$icon = '';
if ($act[0] === 'download') $icon = '<i class="fas fa-download" style="color:var(--cyan-400);"></i>';
elseif ($act[0] === 'login') $icon = '<i class="fas fa-sign-in-alt" style="color:var(--blue-500);"></i>';
elseif ($act[0] === 'upload') $icon = '<i class="fas fa-upload" style="color:var(--blue-500);"></i>';
else $icon = '<i class="fas fa-link" style="color:var(--emerald-400);"></i>';
echo $icon; ?>
                        </span>
                    </td>
                    <td class="act-details">
                        <span class="act-action"><?= htmlspecialchars($act[1]) ?></span>
                        <span class="act-meta"><?= htmlspecialchars($act[2]) ?></span>
                    </td>
                    <td class="act-time"><?= htmlspecialchars($act[3]) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($activities)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; color:var(--text-muted); padding:var(--space-8);">
                        Aucune activité récente
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</section>

<!-- System Status Widget -->
<section class="status-widget" style="margin-bottom:var(--space-6);">
    <div class="status-item">
        <div class="status-dot success"></div>
        <span class="status-label">API REST</span>
        <span class="status-value">En ligne</span>
    </div>
    <div class="status-item">
        <div class="status-dot success"></div>
        <span class="status-label">Base de données</span>
        <span class="status-value"><?= $system_status['db'] ?></span>
    </div>
    <div class="status-item">
        <div class="status-dot <?= $system_status['storage'] === 'over capacity' ? 'warning' : 'success' ?>"></div>
        <span class="status-label">Stockage</span>
        <span class="status-value"><?= $system_status['storage'] ?></span>
    </div>
    <div class="status-item">
        <div class="status-dot success"></div>
        <span class="status-label">CDN / Edge</span>
        <span class="status-value">Latence 28ms</span>
    </div>
</section>

<!-- Storage Trend (small chart) -->
<div style="background:rgba(35,38,50,0.8); border-radius:var(--radius-md); border:1px solid var(--border-subtle); padding:var(--space-5); margin-bottom:var(--space-6);">
    <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:var(--space-3);">Tendance de stockage</div>
    <div style="height:80px; position:relative; overflow:hidden;">
        <div style="position:absolute; left:0; right:0; top:0; bottom:0; background:linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-card) 100%); border-radius:6px;">
            <?php
            $storage_points = [20, 35, 42, 55, 68, 75, 82, 71, 63, 55];
            $max = max($storage_points);
            $height_ratio = 70 / $max;
            foreach ($storage_points as $i => $point): ?>
            <div style="position:absolute; left:calc(10% * <?= $i ?>); width:calc(10% * <?= $i + 1 ?> - calc(10% * <?= $i ?>); background:var(--cyan-400); height:<?= $point * $height_ratio ?>%; border-radius:6px 6px 0 0;"></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>