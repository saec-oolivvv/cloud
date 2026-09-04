<?php
$user = $user ?? [];
$tenant = $tenant ?? [];
$billing = $billing ?? [];
?>

<div class="page-header">
    <div>
        <h1>Facturation — <?= htmlspecialchars($tenant['name'] ?? '') ?></h1>
        <div class="breadcrumb">
            <a href="/admin/tenants">Tenants</a>
            <span>/</span>
            <a href="/admin/tenants/<?= $tenant['id'] ?? '' ?>"><?= htmlspecialchars($tenant['name'] ?? '') ?></a>
            <span>/</span>
            <span>Facturation</span>
        </div>
    </div>
    <div class="header-actions">
        <span class="badge badge-<?= ($billing['billing_status'] ?? 'trial') === 'active' ? 'success' : 'warning' ?>">
            <?= htmlspecialchars(ucfirst($billing['billing_status'] ?? 'trial')) ?>
        </span>
    </div>
</div>

<!-- Billing Overview KPIs -->
<div class="kpi-grid" data-stagger>
    <div class="kpi-card blue">
        <div class="kpi-icon" style="background: rgba(37, 99, 235, 0.12); color: var(--blue-400);">
            <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <div class="kpi-label">Plan</div>
        <div class="kpi-value"><?= htmlspecialchars(ucfirst($tenant['plan'] ?? 'custom')) ?></div>
        <div class="kpi-variation"><?= htmlspecialchars($billing['billing_cycle'] ?? 'monthly') ?></div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--cyan-400);">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="kpi-label">Prix de base</div>
        <div class="kpi-value"><?= number_format($billing['base_price'] ?? 0, 2) ?>€</div>
        <div class="kpi-variation">/ mois</div>
    </div>
    <div class="kpi-card emerald">
        <div class="kpi-icon" style="background: rgba(16, 188, 129, 0.12); color: var(--emerald-400);">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div class="kpi-label">Utilisateurs inclus</div>
        <div class="kpi-value"><?= $billing['included_users'] ?? 0 ?></div>
        <div class="kpi-variation">dans le plan</div>
    </div>
    <div class="kpi-card amber">
        <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--amber-400);">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
        </div>
        <div class="kpi-label">Utilisateurs actuels</div>
        <div class="kpi-value"><?= $billing['current_users'] ?? 0 ?></div>
        <div class="kpi-variation">+<?= $billing['extra_users'] ?? 0 ?> extra</div>
    </div>
</div>

<!-- Cost Breakdown KPIs -->
<div class="kpi-grid" data-stagger>
    <div class="kpi-card rose">
        <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.12); color: var(--rose-500);">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="kpi-label">Coût utilisateurs extra</div>
        <div class="kpi-value"><?= number_format($billing['monthly_extra_cost'] ?? 0, 2) ?>€</div>
        <div class="kpi-variation"><?= $billing['extra_users'] ?? 0 ?> × <?= number_format($billing['extra_user_price'] ?? 0, 2) ?>€</div>
    </div>
    <div class="kpi-card emerald">
        <div class="kpi-icon" style="background: rgba(16, 188, 129, 0.12); color: var(--emerald-400);">
            <svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        </div>
        <div class="kpi-label">Total mensuel</div>
        <div class="kpi-value"><?= number_format($billing['total_monthly'] ?? 0, 2) ?>€</div>
        <div class="kpi-variation">/ mois</div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-icon" style="background: rgba(37, 99, 235, 0.12); color: var(--blue-400);">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="kpi-label">Total annuel</div>
        <div class="kpi-value"><?= number_format(($billing['total_monthly'] ?? 0) * 12, 2) ?>€</div>
        <div class="kpi-variation">/ an</div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--cyan-400);">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="kpi-label">Cycle</div>
        <div class="kpi-value"><?= htmlspecialchars(ucfirst($billing['billing_cycle'] ?? 'monthly')) ?></div>
        <div class="kpi-variation"><?= $billing['billing_cycle'] === 'yearly' ? 'Facturé annuellement' : 'Facturé mensuellement' ?></div>
    </div>
</div>

<!-- Billing Management -->
<div class="settings-section" style="margin-bottom: var(--space-6);">
    <div class="settings-section-header">
        <div class="settings-section-icon blue">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <div>
            <div class="settings-section-title">Gestion de la facturation</div>
            <div class="settings-section-sub">Configurez les paramètres de facturation et les utilisateurs supplémentaires</div>
        </div>
    </div>
    <div class="settings-section-body">
        <div class="form-group">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                <div>
                    <label class="form-label">Utilisateurs supplémentaires autorisés</label>
                    <input type="number" id="extraUsersInput" class="form-input" min="0" value="<?= $billing['extra_users'] ?? 0 ?>">
                </div>
                <div>
                    <label class="form-label">Prix par user extra (€/mois)</label>
                    <input type="number" step="0.01" id="extraUserPriceInput" class="form-input" value="<?= number_format($billing['extra_user_price'] ?? 0, 2) ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
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
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <button class="btn btn-primary" onclick="saveBilling()">Sauvegarder</button>
        </div>
        <div id="billingPreview" style="background: var(--bg-secondary); padding: var(--space-4); border-radius: var(--radius-md); font-family: var(--font-mono); font-size: 13px; color: var(--text-secondary); margin-top: var(--space-4);"></div>
    </div>
</div>

<!-- Current Users Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Utilisateurs actuels (<?= $billing['current_users'] ?? 0 ?>)</span>
        <a href="/admin/tenants/<?= $tenant['id'] ?>/users" class="btn btn-outline btn-sm">Gérer</a>
    </div>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Dernière connexion</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $db = Saec\Core\Database::getInstance();
                    $users = $db->fetchAll("SELECT id, email, role, active, last_login FROM users WHERE tenant_id = ? ORDER BY created_at DESC", [$tenant['id']]);
                    foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['email']) ?></strong></td>
                    <td>
                        <span class="badge badge-<?= $u['role'] === 'admin' ? 'warning' : 'neutral' ?>">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td style="color: var(--text-muted); font-size: 12px;">
                        <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Jamais' ?>
                    </td>
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

<!-- Footer -->
<div style="margin-top: var(--space-6); padding-top: var(--space-4); border-top: 1px solid var(--border-subtle); display: flex; justify-content: space-between; flex-wrap: wrap; gap: var(--space-2); font-size: 12px; color: var(--text-muted);">
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
    if (data.success) {
        SaecToast.success('Facturation mise à jour');
        setTimeout(() => location.reload(), 800);
    } else {
        SaecToast.error(data.error || 'Erreur lors de la sauvegarde');
    }
}
</script>
