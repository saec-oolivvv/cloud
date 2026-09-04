<?php
/** @var array $profile */
/** @var int $used */
/** @var int $sessionCount */
/** @var array $recentSessions */

$profile = $profile ?? [];
$used = $used ?? 0;
$quota = $profile['storage_quota'] ?? 10737418240;
$csrf = \Saec\Core\Session::csrfToken();
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><?= t('settings.title') ?? 'Paramètres' ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard" style="color:var(--text-secondary);">Accueil</a> / <span>Paramètres</span>
        </div>
    </div>
</div>

<!-- Settings Grid -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:var(--space-6); max-width:900px;">

    <!-- Profile Card -->
    <div class="card" style="grid-column: 1 / -1;">
        <div class="card-header">
            <span class="card-title">
                <i class="fas fa-user" style="color:var(--blue-500); margin-right:var(--space-2);"></i>
                Profil
            </span>
        </div>
        <form id="profileForm" style="display:flex; flex-direction:column; gap:var(--space-4);">
            <input type="hidden" name="_token" value="<?= $csrf ?>">

            <div style="display:flex; align-items:center; gap:var(--space-4); margin-bottom:var(--space-2);">
                <div style="width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700; color:#fff; flex-shrink:0;">
                    <?= strtoupper(substr($profile['email'] ?? 'U', 0, 2)) ?>
                </div>
                <div>
                    <div style="font-size:16px; font-weight:600;"><?= htmlspecialchars($profile['email'] ?? '') ?></div>
                    <div style="font-size:12px; color:var(--text-muted);">
                        Membre depuis <?= date('d/m/Y', strtotime($profile['created_at'] ?? 'now')) ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nom</label>
                <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" placeholder="Votre nom">
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled style="opacity:0.6;">
                <div style="font-size:11px; color:var(--text-muted); margin-top:var(--space-1);">L'email ne peut pas être modifié</div>
            </div>

            <div class="form-group">
                <label class="form-label">Rôle</label>
                <div>
                    <span class="badge <?= ($profile['role'] ?? '') === 'admin' ? 'badge-info' : 'badge-success' ?>">
                        <?= ($profile['role'] ?? '') === 'admin' ? 'Administrateur' : 'Utilisateur' ?>
                    </span>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check" style="font-size:12px;"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    <!-- Password Card -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="fas fa-lock" style="color:var(--amber-400); margin-right:var(--space-2);"></i>
                Mot de passe
            </span>
        </div>
        <form id="passwordForm" style="display:flex; flex-direction:column; gap:var(--space-4);">
            <input type="hidden" name="_token" value="<?= $csrf ?>">

            <div class="form-group">
                <label class="form-label">Mot de passe actuel</label>
                <input type="password" name="current_password" class="form-input" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label class="form-label">Nouveau mot de passe</label>
                <input type="password" name="new_password" class="form-input" required minlength="8" autocomplete="new-password">
                <div style="font-size:11px; color:var(--text-muted); margin-top:var(--space-1);">Minimum 8 caractères</div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirmer</label>
                <input type="password" name="confirm_password" class="form-input" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-warning" style="width:100%;">
                <i class="fas fa-key" style="font-size:12px;"></i>
                Changer le mot de passe
            </button>
        </form>
    </div>

    <!-- Sessions Card -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="fas fa-shield-alt" style="color:var(--emerald-400); margin-right:var(--space-2);"></i>
                Sessions actives
            </span>
            <span class="badge badge-info"><?= $sessionCount ?></span>
        </div>

        <div style="display:flex; flex-direction:column; gap:var(--space-2); margin-bottom:var(--space-4);">
            <?php if (!empty($recentSessions)): ?>
            <?php foreach ($recentSessions as $s): ?>
            <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-2) var(--space-3); background:var(--bg-secondary); border-radius:var(--radius-sm); border:1px solid var(--border-subtle); font-size:12px;">
                <i class="fas fa-desktop" style="color:var(--text-muted); width:16px;"></i>
                <div style="flex:1; min-width:0;">
                    <div style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($s['ip_address'] ?? '') ?></div>
                    <div style="color:var(--text-muted); font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars(substr($s['user_agent'] ?? '', 0, 50)) ?>...
                    </div>
                </div>
                <div style="color:var(--text-muted); white-space:nowrap;">
                    <?= date('d/m H:i', strtotime($s['last_activity'] ?? $s['created_at'] ?? 'now')) ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div style="text-align:center; padding:var(--space-4); color:var(--text-muted); font-size:12px;">
                Aucune session active
            </div>
            <?php endif; ?>
        </div>

        <form id="destroySessionsForm">
            <input type="hidden" name="_token" value="<?= $csrf ?>">
            <button type="submit" class="btn btn-danger" style="width:100%;">
                <i class="fas fa-sign-out-alt" style="font-size:12px;"></i>
                Déconnecter toutes les sessions
            </button>
        </form>
    </div>
</div>

<!-- Storage Info -->
<div class="card" style="max-width:900px; margin-top:var(--space-6);">
    <div class="card-header">
        <span class="card-title">
            <i class="fas fa-database" style="color:var(--cyan-400); margin-right:var(--space-2);"></i>
            Stockage
        </span>
        <span style="font-size:12px; color:var(--text-muted);">
            <?= $this->formatSize($used) ?> / <?= $this->formatSize($quota) ?>
        </span>
    </div>
    <?php $pct = $quota > 0 ? ($used / $quota) * 100 : 0; ?>
    <div class="storage-bar" style="height:8px;">
        <div class="storage-bar-fill <?= $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : '') ?>" style="width: <?= min($pct, 100) ?>%"></div>
    </div>
    <div style="display:flex; justify-content:space-between; margin-top:var(--space-3); font-size:12px; color:var(--text-muted);">
        <span><?= round($pct, 1) ?>% utilisé</span>
        <span><?= $this->formatSize($quota - $used) ?> restant</span>
    </div>
</div>

<!-- Feedback Toast -->
<div id="toast" style="position:fixed; bottom:var(--space-6); right:var(--space-6); padding:var(--space-3) var(--space-5); border-radius:var(--radius-md); font-size:13px; font-weight:500; z-index:2000; display:none; transition:all 0.3s ease; transform:translateY(10px); opacity:0;"></div>

<script>
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.display = 'block';
    toast.style.background = type === 'success' ? 'var(--emerald-400)' : 'var(--rose-500)';
    toast.style.color = '#fff';
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
    setTimeout(() => {
        toast.style.transform = 'translateY(10px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.style.display = 'none', 300);
    }, 3000);
}

document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    const res = await fetch('/config/profile', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) {
        showToast(data.message || 'Profil mis à jour');
    } else {
        showToast(data.error || 'Erreur', 'error');
    }
});

document.getElementById('passwordForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    const res = await fetch('/config/password', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) {
        showToast(data.message || 'Mot de passe changé');
        e.target.reset();
    } else {
        showToast(data.error || 'Erreur', 'error');
    }
});

document.getElementById('destroySessionsForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!confirm('Déconnecter toutes les autres sessions ?')) return;
    const form = new FormData(e.target);
    const res = await fetch('/config/sessions/destroy', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) {
        showToast(data.message || 'Sessions détruites');
    } else {
        showToast(data.error || 'Erreur', 'error');
    }
});
</script>
