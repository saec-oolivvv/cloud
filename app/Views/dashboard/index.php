<?php
$user = $user ?? [];
$stats = $stats ?? [
    'total_files' => 0, 'total_size' => 0, 'total_shares' => 0,
    'storage_quota' => 10737418240, 'folders' => 0, 'uptime' => '99.9%',
];
$recent_activity = $recent_activity ?? [];
$recent_files = $recent_files ?? [];
$system_status = $system_status ?? ['api' => 'online', 'db' => 'online', 'storage' => 'online', 'cdn' => 'online'];
$pct = $stats['storage_quota'] > 0 ? round(($stats['total_size'] / $stats['storage_quota']) * 100) : 0;

function fBytes(int $b): string {
    if ($b >= 1073741824) return round($b / 1073741824, 1) . ' GB';
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024) return round($b / 1024, 1) . ' KB';
    return $b . ' B';
}
?>

<style>
    .dash-wrap { max-width:1100px; margin:0 auto; padding:32px 24px 48px; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
    @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
    @keyframes glow { 0%,100% { box-shadow:0 0 20px rgba(37,99,235,0.1); } 50% { box-shadow:0 0 30px rgba(37,99,235,0.2); } }
    @keyframes shimmer { 0% { transform:translateX(-100%); } 100% { transform:translateX(100%); } }

    .dash-hero {
        text-align:center; margin-bottom:36px;
        animation: fadeUp 0.4s ease-out;
    }
    .dash-hero h1 {
        font-size:28px; font-weight:800; letter-spacing:-0.03em; margin-bottom:6px;
        background:linear-gradient(135deg, #F1F5F9, #06B6D4);
        -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    }
    .dash-hero p { font-size:14px; color:#64748B; }
    .dash-hero .status-pill {
        display:inline-flex; align-items:center; gap:6px; margin-top:12px;
        padding:5px 14px; border-radius:9999px; font-size:11px; font-weight:600;
        background:rgba(16,185,129,0.08); color:#34D399; border:1px solid rgba(16,185,129,0.2);
    }
    .dash-hero .status-dot { width:6px; height:6px; border-radius:50%; background:#34D399; animation:pulse 2s infinite; }

    /* KPI Cards */
    .kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
    .kpi {
        background:#232632; border:1px solid #3A4257; border-radius:14px; padding:20px;
        transition:all 0.25s; position:relative; overflow:hidden;
    }
    .kpi:hover { border-color:rgba(37,99,235,0.35); transform:translateY(-2px); box-shadow:0 6px 24px rgba(0,0,0,0.3); }
    .kpi::before {
        content:''; position:absolute; top:0; left:0; right:0; height:3px;
        background:linear-gradient(90deg, var(--kpi-c1, #2563EB), var(--kpi-c2, #06B6D4));
    }
    .kpi-icon {
        width:40px; height:40px; border-radius:10px; display:flex; align-items:center;
        justify-content:center; margin-bottom:12px; font-size:18px;
    }
    .kpi-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:#64748B; margin-bottom:4px; }
    .kpi-value { font-size:26px; font-weight:800; letter-spacing:-0.03em; color:#F1F5F9; }
    .kpi-sub { font-size:12px; color:#94A3B8; margin-top:4px; }
    .kpi-bar { height:4px; background:rgba(255,255,255,0.06); border-radius:2px; margin-top:10px; overflow:hidden; }
    .kpi-bar-fill { height:100%; border-radius:2px; transition:width 0.6s cubic-bezier(0.22,1,0.36,1); }

    /* Quick Actions */
    .qa-row { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
    .qa-card {
        display:flex; align-items:center; gap:16px; padding:18px 20px;
        background:#232632; border:1px solid #3A4257; border-radius:14px;
        text-decoration:none; transition:all 0.25s; cursor:pointer;
    }
    .qa-card:hover { border-color:rgba(37,99,235,0.35); transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,0.25); }
    .qa-icon {
        width:44px; height:44px; border-radius:12px; display:flex; align-items:center;
        justify-content:center; flex-shrink:0;
    }
    .qa-card h4 { font-size:14px; font-weight:600; color:#F1F5F9; }
    .qa-card p { font-size:12px; color:#64748B; margin-top:2px; }

    /* Bottom grid */
    .dash-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .d-card {
        background:#232632; border:1px solid #3A4257; border-radius:14px; overflow:hidden;
    }
    .d-head {
        display:flex; align-items:center; justify-content:space-between;
        padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.04);
    }
    .d-head h3 { font-size:14px; font-weight:700; color:#F1F5F9; display:flex; align-items:center; gap:8px; }
    .d-body { padding:16px 20px; }

    .status-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .st-item {
        display:flex; align-items:center; gap:10px; padding:10px 12px;
        background:rgba(255,255,255,0.02); border-radius:10px; border:1px solid rgba(255,255,255,0.04);
    }
    .st-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; animation:pulse 2s infinite; }
    .st-dot.on { background:#34D399; }
    .st-dot.off { background:#F87171; }
    .st-label { font-size:11px; color:#64748B; }
    .st-val { font-size:12px; font-weight:600; color:#F1F5F9; }

    .act-row {
        display:flex; align-items:center; gap:12px; padding:8px 0;
        border-bottom:1px solid rgba(255,255,255,0.03);
    }
    .act-row:last-child { border-bottom:none; }
    .act-icon {
        width:32px; height:32px; border-radius:8px; display:flex; align-items:center;
        justify-content:center; flex-shrink:0; font-size:14px;
    }
    .act-info { flex:1; min-width:0; }
    .act-title { font-size:12px; font-weight:500; color:#F1F5F9; }
    .act-sub { font-size:11px; color:#64748B; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .act-time { font-size:10px; color:#64748B; white-space:nowrap; font-family:'JetBrains Mono',monospace; }

    .file-row {
        display:flex; align-items:center; gap:12px; padding:8px 0;
        border-bottom:1px solid rgba(255,255,255,0.03);
    }
    .file-row:last-child { border-bottom:none; }
    .file-icon {
        width:32px; height:32px; border-radius:8px; display:flex; align-items:center;
        justify-content:center; flex-shrink:0; font-size:14px;
        background:rgba(37,99,235,0.1); color:#60A5FA;
    }
    .file-info { flex:1; min-width:0; }
    .file-name { font-size:12px; font-weight:500; color:#F1F5F9; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .file-meta { font-size:11px; color:#64748B; }
    .file-size { font-size:11px; color:#94A3B8; white-space:nowrap; font-family:'JetBrains Mono',monospace; }

    .dash-footer {
        margin-top:28px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.04);
        display:flex; justify-content:space-between; font-size:11px; color:#64748B;
    }

    @media(max-width:768px) {
        .kpi-row { grid-template-columns:1fr 1fr; }
        .qa-row { grid-template-columns:1fr; }
        .dash-grid { grid-template-columns:1fr; }
    }
</style>

<div class="dash-wrap">

    <div class="dash-hero">
        <h1>Tableau de bord</h1>
        <p>Bonjour, <?= htmlspecialchars($user['email'] ?? 'Utilisateur') ?></p>
        <div class="status-pill"><span class="status-dot"></span> Système nominal</div>
    </div>

    <!-- KPI -->
    <div class="kpi-row" data-stagger>
        <div class="kpi" style="--kpi-c1:#2563EB;--kpi-c2:#06B6D4;">
            <div class="kpi-icon" style="background:rgba(37,99,235,0.1); color:#60A5FA;">
                <i class="fas fa-hard-drive"></i>
            </div>
            <div class="kpi-label">Stockage</div>
            <div class="kpi-value" style="color:#60A5FA;"><?= fBytes($stats['total_size']) ?></div>
            <div class="kpi-sub">/ <?= fBytes($stats['storage_quota']) ?></div>
            <div class="kpi-bar"><div class="kpi-bar-fill" style="width:<?= min($pct,100) ?>%; background:linear-gradient(90deg,#2563EB,#06B6D4);"></div></div>
        </div>
        <div class="kpi" style="--kpi-c1:#06B6D4;--kpi-c2:#22D3EE;">
            <div class="kpi-icon" style="background:rgba(6,182,212,0.1); color:#22D3EE;">
                <i class="fas fa-file"></i>
            </div>
            <div class="kpi-label">Fichiers</div>
            <div class="kpi-value"><?= number_format($stats['total_files']) ?></div>
            <div class="kpi-sub"><?= number_format($stats['folders']) ?> dossiers</div>
        </div>
        <div class="kpi" style="--kpi-c1:#10B981;--kpi-c2:#34D399;">
            <div class="kpi-icon" style="background:rgba(16,185,129,0.1); color:#34D399;">
                <i class="fas fa-share-nodes"></i>
            </div>
            <div class="kpi-label">Partages</div>
            <div class="kpi-value" style="color:#34D399;"><?= number_format($stats['total_shares']) ?></div>
            <div class="kpi-sub">liens actifs</div>
        </div>
        <div class="kpi" style="--kpi-c1:#F59E0B;--kpi-c2:#FBBF24;">
            <div class="kpi-icon" style="background:rgba(245,158,11,0.1); color:#FBBF24;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="kpi-label">Uptime</div>
            <div class="kpi-value" style="color:#34D399;"><?= $stats['uptime'] ?></div>
            <div class="kpi-sub">30 derniers jours</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="qa-row" data-stagger>
        <a href="/files" class="qa-card">
            <div class="qa-icon" style="background:linear-gradient(135deg,#2563EB,#06B6D4);">
                <i class="fas fa-folder-open" style="color:#fff; font-size:18px;"></i>
            </div>
            <div>
                <h4>Mes fichiers</h4>
                <p>Gérer et organiser</p>
            </div>
        </a>
        <a href="/shares" class="qa-card">
            <div class="qa-icon" style="background:linear-gradient(135deg,#10B981,#06B6D4);">
                <i class="fas fa-share-nodes" style="color:#fff; font-size:18px;"></i>
            </div>
            <div>
                <h4>Mes partages</h4>
                <p><?= $stats['total_shares'] ?> liens actifs</p>
            </div>
        </a>
        <a href="/config" class="qa-card">
            <div class="qa-icon" style="background:linear-gradient(135deg,#F59E0B,#EF4444);">
                <i class="fas fa-gear" style="color:#fff; font-size:18px;"></i>
            </div>
            <div>
                <h4>Paramètres</h4>
                <p>Profil et sécurité</p>
            </div>
        </a>
    </div>

    <!-- Bottom Grid -->
    <div class="dash-grid" data-stagger>

        <!-- System Status -->
        <div class="d-card">
            <div class="d-head">
                <h3><i class="fas fa-signal" style="color:#34D399; font-size:13px;"></i> État du système</h3>
                <span style="padding:3px 10px; border-radius:9999px; font-size:10px; font-weight:600; background:rgba(16,185,129,0.1); color:#34D399; border:1px solid rgba(16,185,129,0.2);">En ligne</span>
            </div>
            <div class="d-body">
                <div class="status-grid">
                    <?php
                    $sItems = [
                        ['label' => 'API REST', 'key' => 'api', 'icon' => '⚡'],
                        ['label' => 'Base de données', 'key' => 'db', 'icon' => '🗄'],
                        ['label' => 'Stockage', 'key' => 'storage', 'icon' => '💾'],
                        ['label' => 'CDN / Edge', 'key' => 'cdn', 'icon' => '🌐'],
                    ];
                    foreach ($sItems as $s):
                        $on = ($system_status[$s['key']] ?? 'online') === 'online';
                    ?>
                    <div class="st-item">
                        <span class="st-dot <?= $on ? 'on' : 'off' ?>"></span>
                        <div>
                            <div class="st-label"><?= $s['label'] ?></div>
                            <div class="st-val"><?= $on ? 'En ligne' : 'Hors ligne' ?></div>
                        </div>
                        <span style="margin-left:auto; font-size:14px;"><?= $s['icon'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="d-card">
            <div class="d-head">
                <h3><i class="fas fa-clock-rotate-left" style="color:#06B6D4; font-size:13px;"></i> Activité récente</h3>
            </div>
            <div class="d-body">
                <?php if (!empty($recent_activity)): ?>
                <?php foreach (array_slice($recent_activity, 0, 6) as $i => $act):
                    $aType = $act['type'] ?? 'login';
                    $aColors = [
                        'download' => ['bg' => 'rgba(6,182,212,0.1)', 'fg' => '#22D3EE', 'icon' => 'fa-download'],
                        'upload' => ['bg' => 'rgba(16,185,129,0.1)', 'fg' => '#34D399', 'icon' => 'fa-upload'],
                        'login' => ['bg' => 'rgba(37,99,235,0.1)', 'fg' => '#60A5FA', 'icon' => 'fa-right-to-bracket'],
                        'share' => ['bg' => 'rgba(139,92,246,0.1)', 'fg' => '#A78BFA', 'icon' => 'fa-share-nodes'],
                        'profile.updated' => ['bg' => 'rgba(245,158,11,0.1)', 'fg' => '#FBBF24', 'icon' => 'fa-user-pen'],
                        'password.changed' => ['bg' => 'rgba(239,68,68,0.1)', 'fg' => '#F87171', 'icon' => 'fa-key'],
                    ];
                    $ac = $aColors[$aType] ?? $aColors['login'];
                    $time = $act['created_at'] ?? '';
                    $timeStr = $time ? date('d/m H:i', strtotime($time)) : '';
                ?>
                <div class="act-row" style="animation:fadeUp <?= 0.05 * $i ?>s ease-out;">
                    <div class="act-icon" style="background:<?= $ac['bg'] ?>; color:<?= $ac['fg'] ?>;">
                        <i class="fas <?= $ac['icon'] ?>"></i>
                    </div>
                    <div class="act-info">
                        <div class="act-title"><?= htmlspecialchars($act['type'] ?? '') ?></div>
                        <div class="act-sub"><?= htmlspecialchars($act['detail'] ?? '') ?></div>
                    </div>
                    <div class="act-time"><?= $timeStr ?></div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="text-align:center; padding:24px; color:#64748B; font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:24px; color:#3A4257; display:block; margin-bottom:8px;"></i>
                    Aucune activité récente
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Files -->
        <div class="d-card">
            <div class="d-head">
                <h3><i class="fas fa-file-lines" style="color:#60A5FA; font-size:13px;"></i> Derniers fichiers</h3>
            </div>
            <div class="d-body">
                <?php if (!empty($recent_files)): ?>
                <?php foreach (array_slice($recent_files, 0, 5) as $i => $f): ?>
                <div class="file-row" style="animation:fadeUp <?= 0.05 * $i ?>s ease-out;">
                    <div class="file-icon"><i class="fas fa-file"></i></div>
                    <div class="file-info">
                        <div class="file-name"><?= htmlspecialchars($f['original_name'] ?? '') ?></div>
                        <div class="file-meta"><?= date('d/m/Y H:i', strtotime($f['created_at'] ?? 'now')) ?></div>
                    </div>
                    <div class="file-size"><?= fBytes((int)($f['size'] ?? 0)) ?></div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="text-align:center; padding:24px; color:#64748B; font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:24px; color:#3A4257; display:block; margin-bottom:8px;"></i>
                    Aucun fichier
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Storage Details -->
        <div class="d-card">
            <div class="d-head">
                <h3><i class="fas fa-database" style="color:#22D3EE; font-size:13px;"></i> Détails stockage</h3>
            </div>
            <div class="d-body">
                <div style="margin-bottom:16px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                        <span style="font-size:12px; color:#94A3B8;">Espace utilisé</span>
                        <span style="font-size:12px; color:#F1F5F9; font-family:'JetBrains Mono',monospace;"><?= fBytes($stats['total_size']) ?> / <?= fBytes($stats['storage_quota']) ?></span>
                    </div>
                    <div style="height:8px; background:rgba(255,255,255,0.06); border-radius:9999px; overflow:hidden;">
                        <div style="height:100%; border-radius:9999px; background:linear-gradient(90deg,#2563EB,#06B6D4); width:<?= min($pct,100) ?>%; transition:width 0.6s;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:6px;">
                        <span style="font-size:11px; color:#64748B;"><?= round($pct,1) ?>% utilisé</span>
                        <span style="font-size:11px; color:#64748B;"><?= fBytes($stats['storage_quota'] - $stats['total_size']) ?> restant</span>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div style="padding:12px; background:rgba(255,255,255,0.02); border-radius:10px; border:1px solid rgba(255,255,255,0.04);">
                        <div style="font-size:11px; color:#64748B;">Fichiers</div>
                        <div style="font-size:18px; font-weight:700; color:#F1F5F9; margin-top:2px;"><?= number_format($stats['total_files']) ?></div>
                    </div>
                    <div style="padding:12px; background:rgba(255,255,255,0.02); border-radius:10px; border:1px solid rgba(255,255,255,0.04);">
                        <div style="font-size:11px; color:#64748B;">Dossiers</div>
                        <div style="font-size:18px; font-weight:700; color:#F1F5F9; margin-top:2px;"><?= number_format($stats['folders']) ?></div>
                    </div>
                </div>
                <?php if ($pct > 80): ?>
                <div style="margin-top:12px; padding:10px 12px; border-radius:8px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); font-size:12px; color:#FBBF24; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-triangle-exclamation"></i> Espace bientôt atteint.
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="dash-footer">
        <span>&copy; <?= date('Y') ?> SAEC Cloud. Tous droits réservés.</span>
        <span style="font-family:'JetBrains Mono',monospace; font-size:10px;">AES-256-GCM · Synology</span>
    </div>

</div>
