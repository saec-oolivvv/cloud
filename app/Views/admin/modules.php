<?php
$user = $user ?? [];
$modules = $modules ?? [];
$plans = $plans ?? ['starter', 'professional', 'enterprise'];
$tenants = $tenants ?? [];
$tenantOverrides = $tenant_overrides ?? [];
?>

<!-- Page Header -->
<div class="page-header">
    <h1>🧩 Modules</h1>
    <div class="header-actions">
        <span class="badge badge-neutral"><?= count($modules) ?> modules</span>
    </div>
</div>

<!-- Legend -->
<div class="module-legend">
    <span class="legend-item"><span class="dot active"></span> Active</span>
    <span class="legend-item"><span class="dot dev"></span> Dev</span>
    <span class="legend-item"><span class="dot disabled"></span> Disabled</span>
</div>

<!-- Modules Table -->
<div class="card" style="margin-bottom:var(--space-6);">
    <table class="table">
        <thead>
            <tr>
                <th>Module</th>
                <th>Status</th>
                <th>Plans</th>
                <th>Dependencies</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($modules as $mod): ?>
            <tr>
                <td>
                    <div class="module-info">
                        <i class="<?= htmlspecialchars($mod['icon'] ?? 'fas fa-puzzle-piece') ?> module-icon <?= $mod['status'] ?>"></i>
                        <div>
                            <strong style="font-size:13px;"><?= htmlspecialchars($mod['label']) ?></strong>
                            <small><?= htmlspecialchars($mod['description'] ?? '') ?></small>
                        </div>
                    </div>
                </td>
                <td>
                    <select class="status-select status-<?= $mod['status'] ?>"
                            data-module="<?= htmlspecialchars($mod['name']) ?>"
                            onchange="toggleModule(this)">
                        <option value="active" <?= $mod['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="dev" <?= $mod['status'] === 'dev' ? 'selected' : '' ?>>Dev</option>
                        <option value="disabled" <?= $mod['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                    </select>
                </td>
                <td>
                    <div class="plans-tags">
                        <?php foreach ($plans as $plan): ?>
                        <label class="plan-tag <?= in_array($plan, $mod['plans'] ?? []) ? 'active' : '' ?>">
                            <input type="checkbox"
                                   data-module="<?= htmlspecialchars($mod['name']) ?>"
                                   data-plan="<?= $plan ?>"
                                   <?= in_array($plan, $mod['plans'] ?? []) ? 'checked' : '' ?>
                                   onchange="togglePlan(this)">
                            <?= ucfirst($plan) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </td>
                <td>
                    <?php if (!empty($mod['depends_on'])): ?>
                    <div style="display:flex; gap:4px;">
                        <?php foreach ($mod['depends_on'] as $dep): ?>
                        <span class="badge badge-neutral" style="font-size:10px;"><?= htmlspecialchars($dep) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <span style="color:var(--text-muted);">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Per-Tenant Override -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Per-Tenant Override</span>
    </div>
    <div style="margin-bottom:var(--space-4);">
        <label class="form-label">Select Tenant</label>
        <select id="tenantSelect" class="form-input" style="max-width:300px;" onchange="loadTenantModules(this.value)">
            <option value="">— Choose —</option>
            <?php foreach ($tenants as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="tenantModulesList" class="tenant-modules-grid" style="display:none;">
        <?php foreach ($modules as $mod): ?>
        <div class="tenant-module-card" data-module="<?= htmlspecialchars($mod['name']) ?>" data-status="<?= $mod['status'] ?>">
            <div class="tmc-header">
                <i class="<?= htmlspecialchars($mod['icon'] ?? 'fas fa-puzzle-piece') ?>" style="font-size:14px;"></i>
                <strong><?= htmlspecialchars($mod['label']) ?></strong>
            </div>
            <label class="toggle-switch">
                <input type="checkbox"
                       id="tm-<?= htmlspecialchars($mod['name']) ?>"
                       data-module="<?= htmlspecialchars($mod['name']) ?>"
                       onchange="toggleTenantModule(this)">
                <span class="slider"></span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span><?= t('app.footer_stack') ?></span>
</div>

<script>
async function toggleModule(select) {
    const module = select.dataset.module;
    const status = select.value;
    select.className = 'status-select status-' + status;
    try {
        const resp = await fetch('/admin/modules/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `module=${encodeURIComponent(module)}&status=${status}`
        });
        const data = await resp.json();
        if (!data.success) { alert(data.error || 'Error'); location.reload(); }
    } catch(e) { alert('Network error'); location.reload(); }
}

async function togglePlan(checkbox) {
    const module = checkbox.dataset.module;
    const row = checkbox.closest('tr');
    const checked = Array.from(row.querySelectorAll('.plan-tag input:checked')).map(i => i.dataset.plan);
    try {
        const body = new URLSearchParams();
        body.append('module', module);
        checked.forEach(p => body.append('plans[]', p));
        const resp = await fetch('/admin/modules/plans', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        const data = await resp.json();
        if (!data.success) alert(data.error || 'Error');
        checkbox.closest('.plan-tag').classList.toggle('active', checkbox.checked);
    } catch(e) { alert('Network error'); }
}

function loadTenantModules(tenantId) {
    const grid = document.getElementById('tenantModulesList');
    if (!tenantId) { grid.style.display = 'none'; return; }
    grid.style.display = 'grid';
    const overrides = <?= json_encode($tenantOverrides) ?>;
    const tenantMods = overrides[tenantId] || {};
    document.querySelectorAll('.tenant-module-card').forEach(card => {
        const mod = card.dataset.module;
        const status = card.dataset.status;
        const cb = card.querySelector('input[type=checkbox]');
        if (status === 'disabled') { cb.disabled = true; cb.checked = false; }
        else { cb.disabled = false; cb.checked = tenantMods[mod] !== 0; }
    });
}

async function toggleTenantModule(checkbox) {
    const tenantId = document.getElementById('tenantSelect').value;
    const module = checkbox.dataset.module;
    const enabled = checkbox.checked ? 1 : 0;
    try {
        const resp = await fetch('/admin/modules/tenant-toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `tenant_id=${tenantId}&module=${encodeURIComponent(module)}&enabled=${enabled}`
        });
        const data = await resp.json();
        if (!data.success) alert(data.error || 'Error');
    } catch(e) { alert('Network error'); }
}
</script>
