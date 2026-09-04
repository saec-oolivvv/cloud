<?php
/** @var array $profile */
/** @var int $used */
/** @var int $sessionCount */
/** @var array $recentSessions */

$profile = $profile ?? [];
$used = $used ?? 0;
$quota = $profile['storage_quota'] ?? 10737418240;
$csrf = \Saec\Core\Session::csrfToken();
$pct = $quota > 0 ? ($used / $quota) * 100 : 0;
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><?= t('settings.title') ?? 'Paramètres' ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard">Accueil</a> / <span>Paramètres</span>
        </div>
    </div>
</div>

<!-- Settings Grid -->
<div class="settings-grid" data-stagger>

    <!-- Profile Section -->
    <div class="settings-section full-width">
        <div class="settings-section-header">
            <div class="settings-section-icon blue">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <div class="settings-section-title">Profil</div>
                <div class="settings-section-sub">Gérez vos informations personnelles</div>
            </div>
        </div>
        <div class="settings-section-body">
            <form id="profileForm">

                <div style="display:flex; align-items:center; gap:var(--space-4); margin-bottom:var(--space-5);">
                    <div style="width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700; color:#fff; flex-shrink:0; box-shadow:0 4px 20px rgba(37,99,235,0.25);">
                        <?= strtoupper(substr($profile['email'] ?? 'U', 0, 2)) ?>
                    </div>
                    <div>
                        <div style="font-size:16px; font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($profile['email'] ?? '') ?></div>
                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                            Membre depuis <?= date('d/m/Y', strtotime($profile['created_at'] ?? 'now')) ?>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="_token" value="<?= $csrf ?>">

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
                    <span class="badge <?= ($profile['role'] ?? '') === 'admin' ? 'badge-info' : 'badge-success' ?>">
                        <?= ($profile['role'] ?? '') === 'admin' ? 'Administrateur' : 'Utilisateur' ?>
                    </span>
                </div>

                <div class="form-group" style="text-align:right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check" style="font-size:12px;"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Section -->
    <div class="settings-section">
        <div class="settings-section-header">
            <div class="settings-section-icon amber">
                <i class="fas fa-lock"></i>
            </div>
            <div>
                <div class="settings-section-title">Mot de passe</div>
                <div class="settings-section-sub">Sécurisez votre compte</div>
            </div>
        </div>
        <div class="settings-section-body">
            <form id="passwordForm">
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
    </div>

    <!-- Sessions Section -->
    <div class="settings-section">
        <div class="settings-section-header">
            <div class="settings-section-icon rose">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <div class="settings-section-title">Sessions actives</div>
                <div class="settings-section-sub">Gérez vos connexions</div>
            </div>
            <span class="badge badge-info" style="margin-left:auto;"><?= $sessionCount ?></span>
        </div>
        <div class="settings-section-body">
            <?php if (!empty($recentSessions)): ?>
            <div style="display:flex; flex-direction:column; gap:var(--space-2); margin-bottom:var(--space-5);">
                <?php foreach ($recentSessions as $s): ?>
                <div class="session-item">
                    <div class="session-icon" style="background:var(--bg-card); border-radius:var(--radius-sm); color:var(--text-muted);">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <div class="session-info">
                        <div class="session-browser"><?= htmlspecialchars($s['ip_address'] ?? '') ?></div>
                        <div class="session-detail"><?= htmlspecialchars(substr($s['user_agent'] ?? '', 0, 50)) ?>...</div>
                    </div>
                    <div class="session-detail" style="white-space:nowrap;">
                        <?= date('d/m H:i', strtotime($s['last_activity'] ?? $s['created_at'] ?? 'now')) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center; padding:var(--space-6); color:var(--text-muted); font-size:12px;">
                Aucune session active
            </div>
            <?php endif; ?>

            <form id="destroySessionsForm">
                <input type="hidden" name="_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-danger" style="width:100%;">
                    <i class="fas fa-sign-out-alt" style="font-size:12px;"></i>
                    Déconnecter toutes les sessions
                </button>
            </form>
        </div>
    </div>

    <!-- Storage Section -->
    <div class="settings-section full-width">
        <div class="settings-section-header">
            <div class="settings-section-icon cyan">
                <i class="fas fa-database"></i>
            </div>
            <div>
                <div class="settings-section-title">Stockage</div>
                <div class="settings-section-sub">Espace utilisé sur votre compte</div>
            </div>
            <span style="margin-left:auto; font-size:12px; color:var(--text-muted);">
                <?= $this->formatSize($used) ?> / <?= $this->formatSize($quota) ?>
            </span>
        </div>
        <div class="settings-section-body">
            <div class="storage-bar">
                <div class="storage-bar-fill <?= $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : '') ?>" style="width:<?= min($pct, 100) ?>%"></div>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:var(--space-3); font-size:12px; color:var(--text-muted);">
                <span><?= round($pct, 1) ?>% utilisé</span>
                <span><?= $this->formatSize($quota - $used) ?> restant</span>
            </div>
        </div>
    </div>

    <!-- Danger Zone Section -->
    <div class="settings-section full-width" style="border-color:rgba(239,68,68,0.25);">
        <div class="settings-section-header" style="border-bottom-color:rgba(239,68,68,0.15);">
            <div class="settings-section-icon rose">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <div class="settings-section-title" style="color:var(--rose-500);">Zone de danger</div>
                <div class="settings-section-sub">Actions irréversibles sur votre compte</div>
            </div>
        </div>
        <div class="settings-section-body" style="display:flex; align-items:center; justify-content:space-between; gap:var(--space-4);">
            <div>
                <div style="font-size:13px; font-weight:500; color:var(--text-primary);">Supprimer le compte</div>
                <div style="font-size:12px; color:var(--text-muted);">Toutes vos données seront définitivement effacées.</div>
            </div>
            <button type="button" class="btn btn-danger" onclick="if(confirm('Supprimer définitivement votre compte ? Cette action est irréversible.')) { /* TODO: account deletion endpoint */ }">
                <i class="fas fa-trash-alt" style="font-size:12px;"></i>
                Supprimer
            </button>
        </div>
    </div>

</div>

<!-- Toast -->
<div id="toast" class="toast" style="display:none;"></div>

<!-- Footer -->
<div style="text-align:center; padding:var(--space-8) 0 var(--space-4); font-size:11px; color:var(--text-muted);">
    &copy; <?= date('Y') ?> SAEC Cloud. Tous droits réservés.
</div>

<script>
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type;
    toast.style.display = 'flex';
    setTimeout(() => {
        toast.style.display = 'none';
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
