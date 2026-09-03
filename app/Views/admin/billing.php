<?php
$user = $user ?? [];
$tenant = $tenant ?? [];
$billing = $billing ?? [];
?>

<div class="page-header">
    <div>
        <h1>💳 Facturation — <?= htmlspecialchars($tenant['name'] ?? '') ?></h1>
        <div class="breadcrumb" style="font-size:13px; color:var(--text-secondary);">
            <a href="/admin/tenants" style="color:var(--text-secondary);">Tenants</a> /
            <a href="/admin/tenants/<?= $tenant['id'] ?? '' ?>" style="color:var(--text-secondary);"><?= htmlspecialchars($tenant['name'] ?? '') ?></a> /
            <span>Facturation</span>
        </div>
    </div>
    <div class="header-actions">
        <span class="badge badge-<?= $billing['billing_status'] === 'active' ? 'success' : 'warning' ?>">
            <?= htmlspecialchars(ucfirst($billing['billing_status'] ?? 'trial')) ?>
        </span>
    </div>
</div>

<!-- Billing Summary -->
<div class="kpi-grid" style="margin-bottom:var(--space-6);">
    <div class="kpi-card blue">
        <div class="kpi-label">Plan</div>
        <div class="kpi-value" style="color:var(--blue-400);"><?= htmlspecialchars(ucfirst($tenant['plan'] ?? 'custom')) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <?= $billing['billing_cycle'] ?? 'monthly' ?>
        </div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-label">Prix de base</div>
        <div class="kpi-value"><?= number_format($billing['base_price'] ?? 0, 2) ?>€</div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">/ mois</div>
    </div>
    <div class="kpi-card emerald">
        <div class="kpi-label">Utilisateurs inclus</div>
        <div class="kpi-value"><?= $billing['included_users'] ?? 0 ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">dans le plan</div>
    </div>
    <div class="kpi-card amber">
        <div class="kpi-label">Utilisateurs actuels</div>
        <div class="kpi-value" style="color:var(--amber-400);"><?= $billing['current_users'] ?? 0 ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            +<?= $billing['extra_users'] ?? 0 ?> extra
        </div>
    </div>
</div>

<!-- Extra Users Cost -->
<div class="kpi-grid" style="margin-bottom:var(--space-6);">
    <div class="kpi-card" style="border-left-color: var(--rose-500);">
        <div class="kpi-label">Coût utilisateurs extra</div>
        <div class="kpi-value" style="color:var(--rose-500);"><?= number_format($billing['monthly_extra_cost'] ?? 0, 2) ?>€</div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <?= $billing['extra_users'] ?? 0 ?> × <?= number_format($billing['extra_user_price'] ?? 0, 2) ?>€
        </div>
    </div>
    <div class="kpi-card" style="border-left-color: var(--emerald-400);">
        <div class="kpi-label">Total mensuel</div>
        <div class="kpi-value" style="color:var(--emerald-400);"><?= number_format($billing['total_monthly'] ?? 0, 2) ?>€</div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">/ mois</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Total annuel</div>
        <div class="kpi-value"><?= number_format(($billing['total_monthly'] ?? 0) * 12, 2) ?>€</div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">/ an</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Cycle</div>
        <div class="kpi-value"><?= htmlspecialchars(ucfirst($billing['billing_cycle'] ?? 'monthly')) ?></div>
        <div style="margin-top:6px; font-size:12px; color:var(--text-secondary);">
            <?= $billing['billing_cycle'] === 'yearly' ? 'Facturé annuellement' : 'Facturé mensuellement' ?>
        </div>
    </div>
</div>

<!-- Manage Extra Users -->
<div class="card" style="margin-bottom:var(--space-6);">
    <div class="card-header">
        <span class="card-title">Gérer utilisateurs supplémentaires</span>
    </div>
    <div class="card-body">
        <div style="display:flex; gap:var(--space-4); flex-wrap:wrap; align-items:flex-end; margin-bottom:var(--space-4);">
            <div style="flex:1; min-width:200px;">
                <label class="form-label">Utilisateurs supplémentaires autorisés</label>
                <input type="number" id="extraUsersInput" class="form-input" min="0" value="<?= $billing['extra_users'] ?? 0 ?>">
            </div>
            <div style="flex:1; min-width:200px;">
                <label class="form-label">Prix par user extra (€/mois)</label>
                <input type="number" step="0.01" id="extraUserPriceInput" class="form-input" value="<?= number_format($billing['extra_user_price'] ?? 0, 2) ?>">
            </div>
            <div>
                <label class="form-label">Statut facturation</label>
                <select id="billingStatusSelect" class="form-input">
                    <option value="trial" <?= ($billing['billing_status'] ?? '') === 'trial' ? 'selected' : '' ?>>Essai</option>
                    <option value="active" <?= ($billing['billing_status'] ?? '') === 'active' ? 'selected' : '' ?>>Actif</option>
                    <option value="past_due" <?= ($billing['billing_status'] ?? '') === 'past_due' ? 'selected' : '' ?>>Impayé</option>
                    <option value="cancelled" <?= ($billing['billing_status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
                </select>
            </div>
            <div>
                <label class="form-label">Cycle</label>
                <select id="billingCycleSelect" class="form-input">
                    <option value="monthly" <?= ($billing['billing_cycle'] ?? '') === 'monthly' ? 'selected' : '' ?>>Mensuel</option>
                    <option value="yearly" <?= ($billing['billing_cycle'] ?? '') === 'yearly' ? 'selected' : '' ?>>Annuel</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="saveBilling()" style="height:fit-content;">Sauvegarder</button>
        </div>
        <div id="billingPreview" style="background:var(--bg-secondary); padding:var(--space-4); border-radius:var(--radius-md); font-family:var(--font-mono); font-size:13px;"></div>
    </div>
</div>

<!-- Current Users List -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Utilisateurs actuels (<?= $billing['current_users'] ?? 0 ?>)</span>
        <a href="/admin/tenants/<?= $tenant['id'] ?>/users" class="btn btn-outline btn-sm">Gérer</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr><th>Email</th><th>Rôle</th><th>Dernière connexion</th><th>Statut</th></tr>
            </thead>
            <tbody>
                <?php
                try {
                    $db = Saec\Core\Database::getInstance();
                    $users = $db->fetchAll("SELECT id, email, role, active, last_login FROM users WHERE tenant_id = ? ORDER BY created_at DESC", [$tenant['id']]);
                    foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['email']) ?></strong></td>
                    <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'warning' : 'neutral' ?>"><?= $u['role'] ?></span></td>
                    <td style="color:var(--text-muted); font-size:12px;"><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Jamais' ?></td>
                    <td>
                        <?php if ($u['active']): ?>
                        <span class="badge badge-success">Actif</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Inactif</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach;
                } catch (\Exception $e) {}
                ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span><?= t('app.footer_stack') ?></span>
</div>

<script>
function updatePreview() {
    const extra = parseInt(document.getElementById('extraUsersInput').value) || 0;
    const price = parseFloat(document.getElementById('extraUserPriceInput').value) || 0;
    const base = <?= $billing['base_price'] ?? 0 ?>;
    const monthlyExtra = extra * price;
    const total = base + monthlyExtra;
    document.getElementById('billingPreview').textContent = 
        'Base: ' + base.toFixed(2) + '€ | Extra: ' + monthlyExtra.toFixed(2) + '€ (' + extra + ' × ' + price.toFixed(2) + '€) | Total: ' + total.toFixed(2) + '€/mois';
}

document.getElementById('extraUsersInput').addEventListener('input', updatePreview);
document.getElementById('extraUserPriceInput').addEventListener('input', updatePreview);
updatePreview();

async function saveBilling() {
    const form = new FormData();
    form.append('extra_users_count', document.getElementById('extraUsersInput').value);
    form.append('extra_user_price', document.getElementById('extraUserPriceInput').value);
    form.append('billing_status', document.getElementById('billingStatusSelect').value);
    form.append('billing_cycle', document.getElementById('billingCycleSelect').value);
    
    const res = await fetch('/admin/tenants/<?= $tenant['id'] ?>/billing', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Error');
}
</script>