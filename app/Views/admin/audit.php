<?php
$user = $user ?? [];
$logs = $logs ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$filters = $filters ?? ['action' => '', 'tenant_id' => ''];
$total = $total ?? 0;
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--emerald-400), var(--cyan-400));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <?= t('admin.audit') ?>
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <a href="/admin" style="color:var(--text-secondary);">Admin</a> / <span>Audit</span>
        </div>
    </div>
    <div class="header-actions">
        <span class="badge badge-neutral"><?= number_format($total) ?> entrées</span>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:var(--space-6);">
    <form method="GET" style="display:flex; gap:var(--space-3); flex-wrap:wrap; align-items:flex-end;">
        <div style="flex:1; min-width:150px;">
            <label class="form-label">Action</label>
            <select name="action" class="form-input">
                <option value="">Toutes les actions</option>
                <?php foreach ($actions ?? [] as $a): ?>
                <option value="<?= htmlspecialchars($a['action']) ?>" <?= $filters['action'] === $a['action'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['action']) ?> (<?= $a['count'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="gap:var(--space-1);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filtrer
        </button>
    </form>
</div>

<!-- Logs -->
<?php if (empty($logs)): ?>
<div class="card" style="text-align:center; padding:var(--space-10);">
    <div style="font-size:48px; margin-bottom:var(--space-4); opacity:0.4;">📋</div>
    <div style="font-size:16px; font-weight:600; margin-bottom:var(--space-2);">Aucun log d'audit</div>
    <div style="font-size:13px; color:var(--text-muted);">Les actions seront enregistrées ici.</div>
</div>
<?php else: ?>
<div style="display:flex; flex-direction:column; gap:var(--space-2);">
<?php
$actionColors = [
    'auth.login' => 'var(--blue-500)',
    'auth.logout' => 'var(--text-muted)',
    'file.uploaded' => 'var(--emerald-400)',
    'file.downloaded' => 'var(--cyan-400)',
    'file.deleted' => 'var(--rose-500)',
    'folder.created' => 'var(--amber-400)',
    'share.created' => 'var(--blue-500)',
    'profile.updated' => 'var(--cyan-400)',
    'password.changed' => 'var(--amber-400)',
];
foreach ($logs as $log):
    $color = $actionColors[$log['action']] ?? 'var(--text-muted)';
    $icon = '📋';
    if (str_starts_with($log['action'], 'auth.')) $icon = '🔐';
    elseif (str_starts_with($log['action'], 'file.')) $icon = '📄';
    elseif (str_starts_with($log['action'], 'folder.')) $icon = '📁';
    elseif (str_starts_with($log['action'], 'share.')) $icon = '🔗';
    elseif (str_starts_with($log['action'], 'profile.')) $icon = '👤';
    elseif (str_starts_with($log['action'], 'password.')) $icon = '🔑';
    elseif (str_starts_with($log['action'], 'session.')) $icon = '🖥';
?>
<div class="card" style="padding:var(--space-3) var(--space-4);">
    <div style="display:flex; align-items:center; gap:var(--space-3);">
        <div style="width:32px; height:32px; border-radius:var(--radius-sm); background:<?= $color ?>15; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0;">
            <?= $icon ?>
        </div>
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; gap:var(--space-2); margin-bottom:2px;">
                <span style="font-size:13px; font-weight:500; color:var(--text-primary);"><?= htmlspecialchars($log['action']) ?></span>
                <span style="font-size:11px; color:var(--text-muted);">·</span>
                <span style="font-size:12px; color:var(--text-secondary);"><?= htmlspecialchars($log['email'] ?? '—') ?></span>
            </div>
            <div style="font-size:11px; color:var(--text-muted);">
                <?= htmlspecialchars($log['tenant_name'] ?? '—') ?>
            </div>
        </div>
        <div style="text-align:right; flex-shrink:0;">
            <div style="font-size:12px; color:var(--text-secondary); white-space:nowrap;"><?= date('d/m H:i:s', strtotime($log['created_at'])) ?></div>
            <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono);"><?= htmlspecialchars($log['ip_address'] ?? '') ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination" style="margin-top:var(--space-6);">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>&action=<?= urlencode($filters['action']) ?>" class="btn btn-outline btn-sm">← Précédent</a>
    <?php endif; ?>
    <span style="color:var(--text-muted); font-size:13px;">Page <?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?>
    <a href="?page=<?= $page + 1 ?>&action=<?= urlencode($filters['action']) ?>" class="btn btn-outline btn-sm">Suivant →</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4);">
    <?= t('app.copyright') ?>
</div>
