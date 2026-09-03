<?php
$user = $user ?? [];
$config = $config ?? [];
?>

<div class="page-header">
    <h1><?= t('admin.config') ?></h1>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">System Configuration</span>
        <span class="badge badge-success">Read-only</span>
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-4);">
        <div>
            <label class="form-label">App Name</label>
            <div style="padding:var(--space-2); background:var(--bg-secondary); border-radius:var(--radius-sm); font-size:13px;">
                <?= htmlspecialchars($config['app']['name'] ?? 'SAEC Cloud') ?>
            </div>
        </div>
        <div>
            <label class="form-label">Environment</label>
            <div style="padding:var(--space-2); background:var(--bg-secondary); border-radius:var(--radius-sm); font-size:13px;">
                <?= htmlspecialchars($config['app']['env'] ?? 'production') ?>
            </div>
        </div>
        <div>
            <label class="form-label">DB Host</label>
            <div style="padding:var(--space-2); background:var(--bg-secondary); border-radius:var(--radius-sm); font-size:13px; font-family:var(--font-mono);">
                <?= htmlspecialchars($config['database']['host'] ?? '—') ?>
            </div>
        </div>
        <div>
            <label class="form-label">DB Name</label>
            <div style="padding:var(--space-2); background:var(--bg-secondary); border-radius:var(--radius-sm); font-size:13px; font-family:var(--font-mono);">
                <?= htmlspecialchars($config['database']['database'] ?? '—') ?>
            </div>
        </div>
        <div>
            <label class="form-label">Max File Size</label>
            <div style="padding:var(--space-2); background:var(--bg-secondary); border-radius:var(--radius-sm); font-size:13px;">
                <?= $this->formatSize($config['upload']['max_file_size'] ?? 104857600) ?>
            </div>
        </div>
        <div>
            <label class="form-label">PHP Version</label>
            <div style="padding:var(--space-2); background:var(--bg-secondary); border-radius:var(--radius-sm); font-size:13px;">
                <?= PHP_VERSION ?>
            </div>
        </div>
    </div>
</div>

<!-- Modules Info -->
<div class="card" style="margin-top:var(--space-4);">
    <div class="card-header">
        <span class="card-title">Active Modules</span>
    </div>
    <div style="display:flex; gap:var(--space-2); flex-wrap:wrap;">
        <?php
        try {
            $db = Saec\Core\Database::getInstance();
            $mods = $db->fetchAll("SELECT name, label, status FROM modules ORDER BY sort_order");
            foreach ($mods as $m): ?>
            <span class="badge badge-<?= $m['status'] === 'active' ? 'success' : ($m['status'] === 'dev' ? 'warning' : 'neutral') ?>">
                <?= htmlspecialchars($m['label']) ?>
            </span>
            <?php endforeach;
        } catch (\Exception $e) {}
        ?>
    </div>
</div>

<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted);">
    <?= t('app.copyright') ?>
</div>
