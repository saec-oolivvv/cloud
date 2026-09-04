<?php
$user = $user ?? [];
$config = $config ?? [];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--text-muted), var(--bg-card));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </span>
            <?= t('admin.config') ?>
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <a href="/admin" style="color:var(--text-secondary);">Admin</a> / <span>Configuration</span>
        </div>
    </div>
    <div class="header-actions">
        <span class="badge badge-success">
            <span style="width:6px; height:6px; border-radius:50%; background:var(--emerald-400); display:inline-block;"></span>
            Lecture seule
        </span>
    </div>
</div>

<!-- System Config -->
<div class="card" style="margin-bottom:var(--space-6);">
    <div class="card-header">
        <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; color:var(--blue-500);"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
            Configuration système
        </span>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:var(--space-4);">
        <?php
        $configItems = [
            ['label' => 'Application', 'value' => $config['app']['name'] ?? 'SAEC Cloud', 'icon' => '🏢'],
            ['label' => 'Environnement', 'value' => $config['app']['env'] ?? 'production', 'icon' => '🌐'],
            ['label' => 'Hôte DB', 'value' => $config['database']['host'] ?? '—', 'icon' => '🗄', 'mono' => true],
            ['label' => 'Base de données', 'value' => $config['database']['database'] ?? '—', 'icon' => '💾', 'mono' => true],
            ['label' => 'Taille max fichier', 'value' => $this->formatSize($config['upload']['max_file_size'] ?? 104857600), 'icon' => '📄'],
            ['label' => 'Version PHP', 'value' => PHP_VERSION, 'icon' => '⚡'],
        ];
        foreach ($configItems as $item): ?>
        <div style="padding:var(--space-4); background:var(--bg-secondary); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
            <div style="display:flex; align-items:center; gap:var(--space-2); margin-bottom:var(--space-2);">
                <span style="font-size:14px;"><?= $item['icon'] ?></span>
                <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); font-weight:600;"><?= $item['label'] ?></span>
            </div>
            <div style="font-size:14px; font-weight:500; color:var(--text-primary); <?= ($item['mono'] ?? false) ? 'font-family:var(--font-mono); font-size:12px;' : '' ?>">
                <?= htmlspecialchars($item['value']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Active Modules -->
<div class="card">
    <div class="card-header">
        <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; color:var(--amber-400);"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
            Modules actifs
        </span>
    </div>
    <div style="display:flex; gap:var(--space-2); flex-wrap:wrap;">
        <?php
        try {
            $db = Saec\Core\Database::getInstance();
            $mods = $db->fetchAll("SELECT name, label, status FROM modules ORDER BY sort_order");
            foreach ($mods as $m):
                $color = $m['status'] === 'active' ? 'var(--emerald-400)' : ($m['status'] === 'dev' ? 'var(--amber-400)' : 'var(--text-muted)');
        ?>
        <div style="display:flex; align-items:center; gap:var(--space-2); padding:var(--space-2) var(--space-3); background:var(--bg-secondary); border-radius:var(--radius-sm); border:1px solid var(--border-subtle); font-size:12px;">
            <span style="width:6px; height:6px; border-radius:50%; background:<?= $color ?>;"></span>
            <span style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($m['label']) ?></span>
            <span style="color:var(--text-muted); font-size:10px; text-transform:uppercase;">· <?= $m['status'] ?></span>
        </div>
        <?php endforeach;
        } catch (\Exception $e) {}
        ?>
    </div>
</div>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4);">
    <?= t('app.copyright') ?>
</div>
