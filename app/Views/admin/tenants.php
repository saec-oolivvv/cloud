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
                    <span class="badge badge-info" style="background:rgba(6,182,212,0.15); color:var(--cyan-400); border-color:var(--cyan-400);">
                        <?= htmlspecialchars($t['plan'] ?? 'custom') ?>
                    </span>
                    <?= (int)($t['user_count'] ?? 0) ?> / <?= (int)($t['plan_max_users'] ?? $t['max_users']) ?> users
                    <?php if (($t['extra_users_count'] ?? 0) > 0): ?>
                    · +<?= (int)$t['extra_users_count'] ?> extra (<?= number_format($t['extra_user_price'] ?? 0, 2) ?>€/mois)
                    <?php endif; ?>
                    · <?= (int)($t['file_count'] ?? 0) ?> files · <?= $this->formatSize($t['storage_used'] ?? 0) ?>
                    <?php if ($t['end_date']): ?>
                    · Expires <?= date('d/m/Y', strtotime($t['end_date'])) ?>
                    <?php endif; ?>
                    <?php if (($t['billing_status'] ?? '') !== 'active'): ?>
                    · <span class="badge badge-warning" style="font-size:10px;"><?= htmlspecialchars($t['billing_status']) ?></span>
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
                <a href="/admin/tenants/<?= $t['id'] ?>/billing" class="action-btn" title="Billing">💳</a>
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
                        <label class="form-label">Plan</label>
                        <select name="plan" class="form-input" id="planSelect" onchange="updatePlanDefaults()">
                            <option value="starter">Starter (1 user, 10GB) — 2.00€/extra user</option>
                            <option value="professional" selected>Professional (5 users, 100GB) — 1.50€/extra user</option>
                            <option value="enterprise">Enterprise (15 users, Unlimited) — 1.00€/extra user</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Storage Quota (bytes, 0 = unlimited)</label>
                        <input type="number" name="storage_quota" class="form-input" id="storageQuota" value="107374182400">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max File Size (bytes)</label>
                        <input type="number" name="max_file_size" class="form-input" id="maxFileSize" value="10737418240">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Included Users</label>
                        <input type="number" name="max_users" class="form-input" id="maxUsers" value="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Extra User Price (€/mois)</label>
                        <input type="number" step="0.01" name="extra_user_price" class="form-input" id="extraUserPrice" value="1.50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Billing Cycle</label>
                        <select name="billing_cycle" class="form-input">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
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
const planLimits = {
    starter: { storage_quota: 10737418240, max_file_size: 1073741824, max_users: 1, extra_user_price: 2.00 },
    professional: { storage_quota: 107374182400, max_file_size: 10737418240, max_users: 5, extra_user_price: 1.50 },
    enterprise: { storage_quota: 0, max_file_size: 53687091200, max_users: 15, extra_user_price: 1.00 },
    custom: { storage_quota: 10737418240, max_file_size: 104857600, max_users: 50, extra_user_price: 0.00 }
};

function updatePlanDefaults() {
    const plan = document.getElementById('planSelect').value;
    const limits = planLimits[plan];
    if (limits) {
        document.getElementById('storageQuota').value = limits.storage_quota;
        document.getElementById('maxFileSize').value = limits.max_file_size;
        document.getElementById('maxUsers').value = limits.max_users;
        document.getElementById('extraUserPrice').value = limits.extra_user_price;
    }
}

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
