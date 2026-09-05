<?php
$tenants = $tenants ?? [];
$filters = $filters ?? ['status' => 'all', 'search' => ''];
$csrf = \Saec\Core\Session::csrfToken();
$csrf = $csrf ?? '';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <?= t('admin.tenants') ?>
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <a href="/admin" style="color:var(--text-secondary);">Admin</a> / <span>Tenants</span>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="document.getElementById('createTenantModal').style.display='flex'" style="gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?= t('admin.create_tenant') ?>
        </button>
    </div>
</div>

<!-- KPI Summary -->
<div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:var(--space-4); margin-bottom:var(--space-6);">
    <?php
    $totalTenants = count($tenants);
    $activeCount = 0;
    $expiredCount = 0;
    $expiringCount = 0;
    $totalUsers = 0;
    $totalStorage = 0;
    foreach ($tenants as $t) {
        if ($t['active'] && (!$t['end_date'] || strtotime($t['end_date']) >= time())) $activeCount++;
        if ($t['end_date'] && strtotime($t['end_date']) < time()) $expiredCount++;
        if ($t['end_date'] && strtotime($t['end_date']) >= time() && strtotime($t['end_date']) < strtotime('+30 days')) $expiringCount++;
        $totalUsers += (int)($t['user_count'] ?? 0);
        $totalStorage += (int)($t['storage_used'] ?? 0);
    }
    ?>
    <div class="kpi-card blue">
        <div class="kpi-label">Total Tenants</div>
        <div class="kpi-value"><?= $totalTenants ?></div>
    </div>
    <div class="kpi-card emerald">
        <div class="kpi-label">Actifs</div>
        <div class="kpi-value" style="color:var(--emerald-400);"><?= $activeCount ?></div>
    </div>
    <div class="kpi-card amber">
        <div class="kpi-label">Expirés / Expirant</div>
        <div class="kpi-value" style="color:var(--amber-400);"><?= $expiredCount + $expiringCount ?></div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-label">Stockage total</div>
        <div class="kpi-value"><?= $this->formatSize($totalStorage) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:var(--space-6);">
    <div style="display:flex; gap:var(--space-3); flex-wrap:wrap; align-items:center;">
        <?php
        $statuses = [
            'all' => ['label' => 'Tous', 'icon' => '📋'],
            'active' => ['label' => 'Actifs', 'icon' => '✅'],
            'expired' => ['label' => 'Expirés', 'icon' => '❌'],
            'expiring' => ['label' => 'Expirant', 'icon' => '⏳'],
        ];
        foreach ($statuses as $key => $info): ?>
        <a href="?status=<?= $key ?>" class="btn <?= $filters['status'] === $key ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="gap:var(--space-1);">
            <span style="font-size:12px;"><?= $info['icon'] ?></span>
            <?= $info['label'] ?>
        </a>
        <?php endforeach; ?>
        <form method="GET" style="margin-left:auto; display:flex; gap:var(--space-2);">
            <?php if ($filters['status'] !== 'all'): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filters['status']) ?>">
            <?php endif; ?>
            <div style="position:relative;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); width:14px; height:14px; color:var(--text-muted);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" class="form-input" placeholder="Rechercher un tenant..." style="max-width:240px; padding-left:34px;">
            </div>
            <button type="submit" class="btn btn-outline btn-sm">Rechercher</button>
        </form>
    </div>
</div>

<!-- Tenants List -->
<?php if (empty($tenants)): ?>
<div class="card" style="text-align:center; padding:var(--space-10);">
    <div style="font-size:48px; margin-bottom:var(--space-4); opacity:0.4;">🏢</div>
    <div style="font-size:16px; font-weight:600; margin-bottom:var(--space-2);">Aucun tenant trouvé</div>
    <div style="font-size:13px; color:var(--text-muted);">Créez votre premier tenant pour commencer.</div>
</div>
<?php else: ?>

<!-- Tenant Cards -->
<div style="display:flex; flex-direction:column; gap:var(--space-3);">
<?php foreach ($tenants as $t):
    $isActive = $t['active'] && (!$t['end_date'] || strtotime($t['end_date']) >= time());
    $isExpired = $t['end_date'] && strtotime($t['end_date']) < time();
    $isExpiring = $t['end_date'] && strtotime($t['end_date']) >= time() && strtotime($t['end_date']) < strtotime('+30 days');
    $pct = ($t['storage_quota'] ?? 1) > 0 ? min(100, (($t['storage_used'] ?? 0) / $t['storage_quota']) * 100) : 0;

    if ($isExpired) $statusBadge = '<span class="badge badge-danger">Expiré</span>';
    elseif ($isExpiring) $statusBadge = '<span class="badge badge-warning">Expire bientôt</span>';
    elseif (!$t['active']) $statusBadge = '<span class="badge badge-danger">Inactif</span>';
    else $statusBadge = '<span class="badge badge-success">Actif</span>';

    $planColors = [
        'starter' => 'var(--text-muted)',
        'professional' => 'var(--blue-500)',
        'enterprise' => 'var(--cyan-400)',
        'custom' => 'var(--amber-400)',
    ];
    $planColor = $planColors[$t['plan'] ?? 'custom'] ?? 'var(--text-muted)';
?>
<div class="card" style="padding:0; overflow:hidden; transition:all 0.2s ease; <?= $isActive ? '' : 'opacity:0.65;' ?>">
    <!-- Top accent bar -->
    <div style="height:3px; background:linear-gradient(90deg, <?= $planColor ?>, transparent);"></div>

    <div style="padding:var(--space-5) var(--space-6); display:flex; align-items:flex-start; gap:var(--space-5); flex-wrap:wrap;">
        <!-- Avatar + Name -->
        <div style="display:flex; align-items:center; gap:var(--space-4); min-width:200px; flex:1;">
            <div style="width:48px; height:48px; border-radius:var(--radius-lg); background:linear-gradient(135deg, <?= $planColor ?>, var(--bg-card)); display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:700; color:#fff; flex-shrink:0; border:1px solid var(--border-subtle);">
                <?= strtoupper(substr($t['name'], 0, 2)) ?>
            </div>
            <div>
                <div style="font-size:16px; font-weight:600; color:var(--text-primary); margin-bottom:2px;">
                    <?= htmlspecialchars($t['name']) ?>
                </div>
                <div style="display:flex; align-items:center; gap:var(--space-2); font-size:12px; color:var(--text-muted);">
                    <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:var(--radius-sm); background:rgba(255,255,255,0.05); border:1px solid var(--border-subtle); color:<?= $planColor ?>; font-weight:500; text-transform:capitalize;">
                        <?= htmlspecialchars($t['plan'] ?? 'custom') ?>
                    </span>
                    <span>·</span>
                    <span>ID #<?= $t['id'] ?></span>
                    <?php if ($t['slug']): ?>
                    <span>·</span>
                    <span style="font-family:var(--font-mono); font-size:11px;"><?= htmlspecialchars($t['slug']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:flex; gap:var(--space-6); align-items:center; flex-wrap:wrap;">
            <!-- Users -->
            <div style="text-align:center; min-width:70px;">
                <div style="font-size:20px; font-weight:700; color:var(--text-primary);"><?= (int)($t['user_count'] ?? 0) ?></div>
                <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted);">Users</div>
                <div style="font-size:10px; color:var(--text-muted);">/ <?= (int)($t['max_users'] ?? 0) ?></div>
            </div>

            <!-- Files -->
            <div style="text-align:center; min-width:70px;">
                <div style="font-size:20px; font-weight:700; color:var(--text-primary);"><?= number_format((int)($t['file_count'] ?? 0)) ?></div>
                <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted);">Fichiers</div>
            </div>

            <!-- Storage -->
            <div style="min-width:140px;">
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
                    <span style="color:var(--text-secondary);"><?= $this->formatSize($t['storage_used'] ?? 0) ?></span>
                    <span style="color:var(--text-muted);"><?= $this->formatSize($t['storage_quota'] ?? 0) ?></span>
                </div>
                <div style="height:5px; background:var(--bg-secondary); border-radius:3px; overflow:hidden;">
                    <div style="height:100%; width:<?= round($pct) ?>%; background:<?= $pct > 90 ? 'var(--rose-500)' : ($pct > 70 ? 'var(--amber-400)' : 'linear-gradient(90deg, var(--cyan-400), var(--blue-500))') ?>; border-radius:3px; transition:width 0.3s;"></div>
                </div>
                <div style="font-size:10px; color:var(--text-muted); margin-top:3px; text-align:right;"><?= round($pct, 1) ?>%</div>
            </div>

            <!-- Extra users cost -->
            <?php if (($t['extra_users_count'] ?? 0) > 0): ?>
            <div style="font-size:11px; color:var(--amber-400); background:rgba(245,158,11,0.1); padding:4px 10px; border-radius:var(--radius-sm); border:1px solid rgba(245,158,11,0.2);">
                +<?= (int)$t['extra_users_count'] ?> extra · <?= number_format($t['extra_user_price'] ?? 0, 2, ',', '') ?>€/mois
            </div>
            <?php endif; ?>

            <!-- Expiry -->
            <?php if ($t['end_date']): ?>
            <div style="text-align:center; min-width:80px;">
                <div style="font-size:12px; font-weight:500; color:<?= $isExpired ? 'var(--rose-500)' : ($isExpiring ? 'var(--amber-400)' : 'var(--text-secondary)') ?>;">
                    <?= date('d/m/Y', strtotime($t['end_date'])) ?>
                </div>
                <div style="font-size:10px; color:var(--text-muted);">Expiration</div>
            </div>
            <?php endif; ?>

            <!-- Status -->
            <div><?= $statusBadge ?></div>
        </div>

        <!-- Actions -->
        <div style="display:flex; gap:var(--space-2); align-items:center; margin-left:auto;">
            <a href="/admin/tenants/<?= $t['id'] ?>/users" class="btn btn-outline btn-sm" title="Gérer les utilisateurs" style="padding:var(--space-2) var(--space-3);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Users
            </a>
            <a href="/admin/tenants/<?= $t['id'] ?>/billing" class="btn btn-outline btn-sm" title="Facturation" style="padding:var(--space-2) var(--space-3);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Billing
            </a>
            <button onclick="extendTenant(<?= $t['id'] ?>)" class="btn btn-outline btn-sm" title="Prolonger" style="padding:var(--space-2) var(--space-3);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Prolonger
            </button>
            <button onclick="editTenant(<?= \$t[id] ?>)" class="btn btn-outline btn-sm" title="Modifier" style="padding:var(--space-2) var(--space-3);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v14"/><path d="M9.5 14l-2.867-8.55a2.5 2.5 0 0 1 1.28-2.115H18a2.5 2.5 0 0 1 2.115 1.28l-8.55 2.867V19a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-2"/><line x1="3" y1="3" x2="21" y2="21"/></svg>Modifier</button>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span><?= $totalTenants ?> tenant<?= $totalTenants > 1 ? 's' : '' ?> · <?= $totalUsers ?> utilisateur<?= $totalUsers > 1 ? 's' : '' ?></span>
</div>

<!-- Modal Create Tenant -->
<div id="createTenantModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:520px;">
        <div style="background:var(--bg-secondary); border-radius:var(--radius-lg); border:1px solid var(--border-subtle); overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,0.5); animation: modalIn 0.2s ease-out;">
            <!-- Modal Header -->
            <div style="padding:var(--space-5) var(--space-6); border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:var(--space-3);">
                    <div style="width:36px; height:36px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--emerald-400), var(--cyan-400)); display:flex; align-items:center; justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:18px; height:18px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    </div>
                    <div>
                        <div style="font-size:15px; font-weight:600;">Nouveau tenant</div>
                        <div style="font-size:11px; color:var(--text-muted);">Créer un nouvel espace client</div>
                    </div>
                </div>
                <button onclick="document.getElementById('createTenantModal').style.display='none'" style="width:32px; height:32px; border-radius:var(--radius-sm); border:none; background:transparent; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px;">×</button>
            </div>

            <!-- Modal Body -->
            <form id="createTenantForm">
                <div style="padding:var(--space-6); display:flex; flex-direction:column; gap:var(--space-4);">

                    <!-- Name -->
                    <div class="form-group">
                        <label class="form-label">Nom du tenant</label>
                        <input type="text" name="name" class="form-input" required placeholder="Ex: Acme Corp" style="font-size:14px;">
                    </div>

                    <!-- Plan -->
                    <div class="form-group">
                        <label class="form-label">Plan</label>
                        <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:var(--space-2);" id="planCards">
                            <?php
                            $plans = [
                                'starter' => ['label' => 'Starter', 'desc' => '1 user · 10 GB', 'price' => '2,00€/user extra', 'color' => 'var(--text-muted)'],
                                'professional' => ['label' => 'Professional', 'desc' => '5 users · 100 GB', 'price' => '1,50€/user extra', 'color' => 'var(--blue-500)'],
                                'enterprise' => ['label' => 'Enterprise', 'desc' => '15 users · Illimité', 'price' => '1,00€/user extra', 'color' => 'var(--cyan-400)'],
                                'custom' => ['label' => 'Custom', 'desc' => 'Paramètres libres', 'price' => 'Gratuit', 'color' => 'var(--amber-400)'],
                            ];
                            foreach ($plans as $key => $p): ?>
                            <label class="plan-option" data-plan="<?= $key ?>" style="display:flex; flex-direction:column; padding:var(--space-3); border:1px solid var(--border-subtle); border-radius:var(--radius-md); cursor:pointer; transition:all 0.15s; background:var(--bg-card);">
                                <input type="radio" name="plan" value="<?= $key ?>" <?= $key === 'professional' ? 'checked' : '' ?> style="display:none;">
                                <div style="display:flex; align-items:center; justify-content:space-between;">
                                    <span style="font-size:13px; font-weight:600; color:var(--text-primary);"><?= $p['label'] ?></span>
                                    <span style="width:8px; height:8px; border-radius:50%; border:2px solid <?= $p['color'] ?>;"></span>
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:2px;"><?= $p['desc'] ?></div>
                                <div style="font-size:10px; color:var(--text-muted); margin-top:4px;"><?= $p['price'] ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quotas -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3);">
                        <div class="form-group">
                            <label class="form-label">Quota stockage (Go)</label>
                            <input type="number" name="storage_quota_gb" class="form-input" id="storageQuota" value="100" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Taille max fichier (Go)</label>
                            <input type="number" name="max_file_size_gb" class="form-input" id="maxFileSize" value="10" min="0">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3);">
                        <div class="form-group">
                            <label class="form-label">Users inclus</label>
                            <input type="number" name="max_users" class="form-input" id="maxUsers" value="5" min="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prix user extra (€/mois)</label>
                            <input type="number" step="0.01" name="extra_user_price" class="form-input" id="extraUserPrice" value="1.50" min="0">
                        </div>
                    </div>

                    <!-- Billing -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3);">
                        <div class="form-group">
                            <label class="form-label">Cycle de facturation</label>
                            <select name="billing_cycle" class="form-input">
                                <option value="monthly">Mensuel</option>
                                <option value="yearly">Annuel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date d'expiration</label>
                            <input type="date" name="end_date" class="form-input">
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div style="padding:var(--space-4) var(--space-6); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:var(--space-3); background:rgba(0,0,0,0.15);">
                    <button type="button" onclick="document.getElementById('createTenantModal').style.display='none'" class="btn btn-ghost" style="font-size:13px;">Annuler</button>
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:var(--space-2) var(--space-5);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Créer le tenant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes modalIn { from { opacity:0; transform:scale(0.95) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
.plan-option:has(input:checked) {
    border-color: var(--blue-500) !important;
    background: rgba(37,99,235,0.08) !important;
    box-shadow: 0 0 0 1px var(--blue-500);
}
.plan-option:hover {
    border-color: var(--border-active);
    background: var(--bg-card-hover);
}
</style>

<script>
const planLimits = {
    starter:     { storage_quota_gb: 10,   max_file_size_gb: 1,   max_users: 1,  extra_user_price: 2.00 },
    professional:{ storage_quota_gb: 100,  max_file_size_gb: 10,  max_users: 5,  extra_user_price: 1.50 },
    enterprise:  { storage_quota_gb: 0,    max_file_size_gb: 50,  max_users: 15, extra_user_price: 1.00 },
    custom:      { storage_quota_gb: 10,   max_file_size_gb: 0.1, max_users: 50, extra_user_price: 0.00 },
};

document.querySelectorAll('.plan-option').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.plan-option').forEach(p => p.style.borderColor = '');
        el.style.borderColor = 'var(--blue-500)';
        el.querySelector('input').checked = true;
        updatePlanDefaults();
    });
});

function updatePlanDefaults() {
    const plan = document.querySelector('input[name="plan"]:checked')?.value;
    const limits = planLimits[plan];
    if (!limits) return;
    document.getElementById('storageQuota').value = limits.storage_quota_gb;
    document.getElementById('maxFileSize').value = limits.max_file_size_gb;
    document.getElementById('maxUsers').value = limits.max_users;
    document.getElementById('extraUserPrice').value = limits.extra_user_price;
}

// Init selected plan
document.querySelector('.plan-option[data-plan="professional"]').click();

document.getElementById('createTenantForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    form.append('_token', '<?= $csrf ?>');
    // Convert GB to bytes
    form.set('storage_quota', (parseInt(form.get('storage_quota_gb')) || 0) * 1073741824);
    form.set('max_file_size', (parseFloat(form.get('max_file_size_gb')) || 0) * 1073741824);
    form.delete('storage_quota_gb');
    form.delete('max_file_size_gb');
    const res = await fetch('/admin/tenants', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Erreur lors de la création');
});

async function extendTenant(id) {
    const days = prompt('Prolonger de combien de jours ?', '30');
    if (!days) return;
    const form = new FormData();
    form.append('days', days);
    form.append('_token', '<?= $csrf ?>');
    const res = await fetch(`/admin/tenants/${id}/extend`, { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Erreur');
}
</script>
