<?php
$user = $user ?? [];
$logs = $logs ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$filters = $filters ?? ['action' => '', 'tenant_id' => ''];
?>

<div class="page-header">
    <h1><?= t('admin.audit') ?></h1>
    <div class="header-actions">
        <span class="badge badge-neutral"><?= number_format($total ?? 0) ?> entries</span>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:var(--space-4);">
    <form method="GET" style="display:flex; gap:var(--space-3); flex-wrap:wrap; align-items:flex-end;">
        <div>
            <label class="form-label">Action</label>
            <select name="action" class="form-input" style="min-width:150px;">
                <option value="">All</option>
                <?php foreach ($actions ?? [] as $a): ?>
                <option value="<?= htmlspecialchars($a['action']) ?>" <?= $filters['action'] === $a['action'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['action']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    </form>
</div>

<!-- Logs Table -->
<div class="card">
    <?php if (empty($logs)): ?>
    <div style="text-align:center; padding:var(--space-8); color:var(--text-muted);">No audit logs</div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr><th>Time</th><th>Action</th><th>User</th><th>Tenant</th><th>IP</th></tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td style="color:var(--text-muted); font-size:12px; white-space:nowrap;"><?= date('d/m H:i:s', strtotime($log['created_at'])) ?></td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($log['action']) ?></span></td>
                <td><?= htmlspecialchars($log['email'] ?? '—') ?></td>
                <td><?= htmlspecialchars($log['tenant_name'] ?? '—') ?></td>
                <td style="font-family:var(--font-mono); font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination" style="margin-top:var(--space-4);">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>&action=<?= urlencode($filters['action']) ?>" class="btn btn-outline btn-sm">← <?= t('common.previous') ?></a>
    <?php endif; ?>
    <span style="color:var(--text-muted); font-size:13px;"><?= t('common.page') ?> <?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?>
    <a href="?page=<?= $page + 1 ?>&action=<?= urlencode($filters['action']) ?>" class="btn btn-outline btn-sm"><?= t('common.next') ?> →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted);">
    <?= t('app.copyright') ?>
</div>
