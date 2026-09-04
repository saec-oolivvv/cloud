<?php
$user = $user ?? [];
$modules = $modules ?? [];
$plans = $plans ?? ['starter', 'professional', 'enterprise'];
$tenants = $tenants ?? [];
$tenantOverrides = $tenant_overrides ?? [];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--amber-400), var(--rose-500));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
            </span>
            Modules
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <a href="/admin" style="color:var(--text-secondary);">Admin</a> / <span>Modules</span>
        </div>
    </div>
    <div class="header-actions">
        <span class="badge badge-neutral"><?= count($modules) ?> modules</span>
    </div>
</div>

<!-- Legend -->
<div style="display:flex; gap:var(--space-4); margin-bottom:var(--space-6); font-size:12px; color:var(--text-secondary);">
    <span style="display:flex; align-items:center; gap:var(--space-1);"><span style="width:8px; height:8px; border-radius:50%; background:var(--emerald-400);"></span> Actif</span>
    <span style="display:flex; align-items:center; gap:var(--space-1);"><span style="width:8px; height:8px; border-radius:50%; background:var(--amber-400);"></span> Dev</span>
    <span style="display:flex; align-items:center; gap:var(--space-1);"><span style="width:8px; height:8px; border-radius:50%; background:var(--text-muted);"></span> Désactivé</span>
</div>

<!-- Modules Grid -->
<div style="display:flex; flex-direction:column; gap:var(--space-3); margin-bottom:var(--space-6);">
<?php foreach ($modules as $mod):
    $statusColor = $mod['status'] === 'active' ? 'var(--emerald-400)' : ($mod['status'] === 'dev' ? 'var(--amber-400)' : 'var(--text-muted)');
?>
<div class="card" style="padding:0; overflow:hidden;">
    <div style="height:3px; background:linear-gradient(90deg, <?= $statusColor ?>, transparent);"></div>
    <div style="padding:var(--space-4) var(--space-5); display:flex; align-items:center; gap:var(--space-4); flex-wrap:wrap;">
        <!-- Module Icon + Info -->
        <div style="display:flex; align-items:center; gap:var(--space-3); min-width:200px; flex:1;">
            <div style="width:40px; height:40px; border-radius:var(--radius-md); background:<?= $statusColor ?>15; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="<?= htmlspecialchars($mod['icon'] ?? 'fas fa-puzzle-piece') ?>" style="font-size:16px; color:<?= $statusColor ?>;"></i>
            </div>
            <div>
                <div style="font-size:14px; font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($mod['label']) ?></div>
                <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($mod['description'] ?? '') ?></div>
            </div>
        </div>

        <!-- Status -->
        <div>
            <select class="status-select status-<?= $mod['status'] ?>" data-module="<?= htmlspecialchars($mod['name']) ?>" onchange="toggleModule(this)" style="min-width:100px;">
                <option value="active" <?= $mod['status'] === 'active' ? 'selected' : '' ?>>Actif</option>
                <option value="dev" <?= $mod['status'] === 'dev' ? 'selected' : '' ?>>Dev</option>
                <option value="disabled" <?= $mod['status'] === 'disabled' ? 'selected' : '' ?>>Désactivé</option>
            </select>
        </div>

        <!-- Plans -->
        <div style="display:flex; gap:var(--space-2); flex-wrap:wrap;">
            <?php foreach ($plans as $plan): ?>
            <label class="plan-tag <?= in_array($plan, $mod['plans'] ?? []) ? 'active' : '' ?>" style="font-size:11px;">
                <input type="checkbox" data-module="<?= htmlspecialchars($mod['name']) ?>" data-plan="<?= $plan ?>" <?= in_array($plan, $mod['plans'] ?? []) ? 'checked' : '' ?> onchange="togglePlan(this)">
                <?= ucfirst($plan) ?>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Dependencies -->
        <div>
            <?php if (!empty($mod['depends_on'])): ?>
            <div style="display:flex; gap:4px;">
                <?php foreach ($mod['depends_on'] as $dep): ?>
                <span class="badge badge-neutral" style="font-size:10px;"><?= htmlspecialchars($dep) ?></span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <span style="font-size:11px; color:var(--text-muted);">—</span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Per-Tenant Override -->
<div class="card">
    <div class="card-header">
        <span class="card-title" style="display:flex; align-items:center; gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; color:var(--cyan-400);"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Override par Tenant
        </span>
    </div>
    <div style="margin-bottom:var(--space-4);">
        <label class="form-label">Sélectionner un tenant</label>
        <select id="tenantSelect" class="form-input" style="max-width:300px;" onchange="loadTenantModules(this.value)">
            <option value="">— Choisir —</option>
            <?php foreach ($tenants as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="tenantModulesList" style="display:none; display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:var(--space-3);">
        <?php foreach ($modules as $mod): ?>
        <div class="card" style="padding:var(--space-3); display:flex; align-items:center; justify-content:space-between;" data-module="<?= htmlspecialchars($mod['name']) ?>" data-status="<?= $mod['status'] ?>">
            <div style="display:flex; align-items:center; gap:var(--space-2);">
                <i class="<?= htmlspecialchars($mod['icon'] ?? 'fas fa-puzzle-piece') ?>" style="font-size:14px;"></i>
                <strong style="font-size:13px;"><?= htmlspecialchars($mod['label']) ?></strong>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="tm-<?= htmlspecialchars($mod['name']) ?>" data-module="<?= htmlspecialchars($mod['name']) ?>" onchange="toggleTenantModule(this)">
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
        if (!data.success) { alert(data.error || 'Erreur'); location.reload(); }
    } catch(e) { alert('Erreur réseau'); location.reload(); }
}

async function togglePlan(checkbox) {
    const module = checkbox.dataset.module;
    const card = checkbox.closest('.card');
    const checked = Array.from(card.querySelectorAll('.plan-tag input:checked')).map(i => i.dataset.plan);
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
        if (!data.success) alert(data.error || 'Erreur');
        checkbox.closest('.plan-tag').classList.toggle('active', checkbox.checked);
    } catch(e) { alert('Erreur réseau'); }
}

function loadTenantModules(tenantId) {
    const grid = document.getElementById('tenantModulesList');
    if (!tenantId) { grid.style.display = 'none'; return; }
    grid.style.display = 'grid';
    const overrides = <?= json_encode($tenantOverrides) ?>;
    const tenantMods = overrides[tenantId] || {};
    grid.querySelectorAll('.card').forEach(card => {
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
        if (!data.success) alert(data.error || 'Erreur');
    } catch(e) { alert('Erreur réseau'); }
}
</script>
