<?php
$user = $user ?? [];
$tenants = $tenants ?? [];
$filters = $filters ?? ['status' => 'all', 'search' => ''];
?>

<div class="page-header">
    <h1><?= t('admin.tenants') ?></h1>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="document.getElementById('createTenantModal').style.display='flex'">
            + <?= t('admin.create_tenant') ?>
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:var(--space-4);">
    <div style="display:flex; gap:var(--space-3); flex-wrap:wrap; align-items:center;">
        <?php foreach (['all', 'active', 'expired', 'expiring'] as $s): ?>
        <a href="?status=<?= $s ?>" class="btn <?= $filters['status'] === $s ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
        <form method="GET" style="margin-left:auto; display:flex; gap:var(--space-2);">
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" class="form-input" placeholder="Search..." style="max-width:200px;">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
        </form>
    </div>
</div>

<!-- Tenants List -->
<div class="card">
    <?php if (empty($tenants)): ?>
    <div style="text-align:center; padding:var(--space-8); color:var(--text-muted);">No tenants found</div>
    <?php else: ?>
    <div class="file-list" style="border:none;">
        <?php foreach ($tenants as $t): ?>
        <div class="file-row">
            <div class="row-icon" style="background:rgba(59,130,246,0.1); color:var(--blue-400);">🏢</div>
            <div class="row-info">
                <div class="row-name"><?= htmlspecialchars($t['name']) ?></div>
                <div class="row-meta">
                    <?= (int)($t['user_count'] ?? 0) ?> users · <?= (int)($t['file_count'] ?? 0) ?> files · <?= $this->formatSize($t['storage_used'] ?? 0) ?>
                    <?php if ($t['end_date']): ?>
                    · Expires <?= date('d/m/Y', strtotime($t['end_date'])) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row-size">
                <?php if (!$t['active']): ?>
                <span class="badge badge-danger">Inactive</span>
                <?php elseif ($t['end_date'] && strtotime($t['end_date']) < time()): ?>
                <span class="badge badge-danger">Expired</span>
                <?php elseif ($t['end_date'] && strtotime($t['end_date']) < strtotime('+30 days')): ?>
                <span class="badge badge-warning">Expiring</span>
                <?php else: ?>
                <span class="badge badge-success">Active</span>
                <?php endif; ?>
            </div>
            <div class="row-actions">
                <a href="/admin/tenants/<?= $t['id'] ?>/users" class="action-btn" title="Users">👥</a>
                <span class="action-btn" onclick="extendTenant(<?= $t['id'] ?>)" title="Extend">📅</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4);">
    <?= t('app.copyright') ?>
</div>

<!-- Modal Create Tenant -->
<div id="createTenantModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= t('admin.create_tenant') ?></span>
                <button class="btn btn-ghost" onclick="document.getElementById('createTenantModal').style.display='none'">×</button>
            </div>
            <div class="card-body">
                <form id="createTenantForm">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Storage Quota (bytes)</label>
                        <input type="number" name="storage_quota" class="form-input" value="10737418240">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Users</label>
                        <input type="number" name="max_users" class="form-input" value="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-input">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;"><?= t('common.confirm') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('createTenantForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    const res = await fetch('/admin/tenants', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Error');
});

async function extendTenant(id) {
    const days = prompt('Extend by how many days?', '30');
    if (!days) return;
    const form = new FormData();
    form.append('days', days);
    const res = await fetch(`/admin/tenants/${id}/extend`, { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Error');
}
</script>
