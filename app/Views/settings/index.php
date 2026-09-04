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

// Determine plan from quota
$plan = 'Starter';
if ($quota >= 107374182400) $plan = 'Professional';
elseif ($quota >= 1073741824000) $plan = 'Enterprise';

function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1024, 1) . ' KB';
}
?>

<style>
    .settings-wrap { max-width: 960px; margin: 0 auto; padding: 32px 24px 48px; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
    @keyframes slideDown { from { opacity:0; max-height:0; } to { opacity:1; max-height:600px; } }
    @keyframes scaleIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }
    @keyframes glow { 0%,100% { box-shadow:0 0 20px rgba(37,99,235,0.1); } 50% { box-shadow:0 0 30px rgba(37,99,235,0.2); } }

    .settings-hero {
        text-align:center; margin-bottom:40px;
        animation: fadeUp 0.4s ease-out;
    }
    .settings-hero h1 {
        font-size:28px; font-weight:800; letter-spacing:-0.03em; margin-bottom:6px;
        background:linear-gradient(135deg, #F1F5F9, #06B6D4);
        -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    }
    .settings-hero p { font-size:14px; color:#64748B; }

    .settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .settings-full { grid-column:1/-1; }

    .s-card {
        background:#232632; border:1px solid #3A4257; border-radius:16px; padding:0;
        transition:border-color 0.2s, box-shadow 0.2s; overflow:hidden;
    }
    .s-card:hover { border-color:rgba(37,99,235,0.3); }
    .s-card.full { grid-column:1/-1; }

    .s-head {
        display:flex; align-items:center; gap:14px; padding:20px 24px;
        border-bottom:1px solid rgba(255,255,255,0.04);
    }
    .s-icon {
        width:44px; height:44px; border-radius:12px; display:flex; align-items:center;
        justify-content:center; flex-shrink:0; font-size:18px;
    }
    .s-icon.blue { background:rgba(37,99,235,0.12); color:#60A5FA; }
    .s-icon.amber { background:rgba(245,158,11,0.12); color:#FBBF24; }
    .s-icon.rose { background:rgba(239,68,68,0.12); color:#F87171; }
    .s-icon.cyan { background:rgba(6,182,212,0.12); color:#22D3EE; }
    .s-icon.emerald { background:rgba(16,185,129,0.12); color:#34D399; }
    .s-icon.purple { background:rgba(139,92,246,0.12); color:#A78BFA; }
    .s-head-text h3 { font-size:15px; font-weight:700; color:#F1F5F9; }
    .s-head-text p { font-size:12px; color:#64748B; margin-top:2px; }
    .s-head-badge {
        margin-left:auto; padding:4px 12px; border-radius:9999px;
        font-size:11px; font-weight:600; letter-spacing:0.02em;
        background:rgba(37,99,235,0.1); color:#60A5FA; border:1px solid rgba(37,99,235,0.2);
    }
    .s-body { padding:24px; }

    .s-fields { display:flex; flex-direction:column; gap:16px; }
    .s-field label {
        display:block; font-size:11px; font-weight:600; text-transform:uppercase;
        letter-spacing:0.06em; color:#94A3B8; margin-bottom:6px;
    }
    .s-input {
        width:100%; padding:10px 14px; border-radius:8px;
        border:1px solid #3A4257; background:#181A20; color:#F1F5F9;
        font-size:14px; font-family:inherit; outline:none; transition:all 0.2s;
    }
    .s-input:focus { border-color:#2563EB; box-shadow:0 0 0 3px rgba(37,99,235,0.12); }
    .s-input::placeholder { color:#64748B; }
    .s-input[disabled] { opacity:0.5; cursor:not-allowed; }
    .s-hint { font-size:11px; color:#64748B; margin-top:4px; }

    .s-btn {
        padding:10px 20px; border-radius:8px; font-size:13px; font-weight:600;
        border:none; cursor:pointer; font-family:inherit; transition:all 0.2s;
        display:inline-flex; align-items:center; gap:8px;
    }
    .s-btn-primary { background:#2563EB; color:#fff; }
    .s-btn-primary:hover { background:#3B82F6; box-shadow:0 4px 12px rgba(37,99,235,0.3); transform:translateY(-1px); }
    .s-btn-warning { background:#F59E0B; color:#000; }
    .s-btn-warning:hover { background:#FBBF24; box-shadow:0 4px 12px rgba(245,158,11,0.3); }
    .s-btn-danger { background:rgba(239,68,68,0.12); color:#F87171; border:1px solid rgba(239,68,68,0.25); }
    .s-btn-danger:hover { background:rgba(239,68,68,0.2); border-color:rgba(239,68,68,0.4); }
    .s-btn-right { float:right; }

    /* Profile avatar */
    .profile-avatar {
        width:72px; height:72px; border-radius:50%;
        background:linear-gradient(135deg, #2563EB, #06B6D4);
        display:flex; align-items:center; justify-content:center;
        font-size:26px; font-weight:800; color:#fff; flex-shrink:0;
        box-shadow:0 6px 20px rgba(37,99,235,0.3); position:relative;
    }
    .profile-avatar::after {
        content:''; position:absolute; inset:-3px; border-radius:50%;
        border:2px solid rgba(37,99,235,0.2); animation:glow 3s infinite;
    }

    /* Sessions */
    .session-row {
        display:flex; align-items:center; gap:12px; padding:10px 0;
        border-bottom:1px solid rgba(255,255,255,0.03);
    }
    .session-row:last-child { border-bottom:none; }
    .session-icon {
        width:36px; height:36px; border-radius:10px; display:flex;
        align-items:center; justify-content:center; font-size:14px;
        background:rgba(255,255,255,0.04); color:#64748B; flex-shrink:0;
    }
    .session-info { flex:1; min-width:0; }
    .session-ip { font-size:13px; font-weight:500; color:#F1F5F9; }
    .session-ua { font-size:11px; color:#64748B; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .session-date { font-size:11px; color:#64748B; white-space:nowrap; font-family:'JetBrains Mono',monospace; }

    /* Storage */
    .storage-track {
        width:100%; height:10px; border-radius:9999px;
        background:rgba(255,255,255,0.06); overflow:hidden; margin-top:4px;
    }
    .storage-fill {
        height:100%; border-radius:9999px;
        background:linear-gradient(90deg, #2563EB, #06B6D4);
        transition:width 0.6s cubic-bezier(0.22,1,0.36,1);
    }
    .storage-fill.warn { background:linear-gradient(90deg, #F59E0B, #EF4444); }
    .storage-stats {
        display:flex; justify-content:space-between; margin-top:8px;
        font-size:12px; color:#64748B;
    }

    /* Danger zone */
    .s-danger { border-color:rgba(239,68,68,0.25) !important; }
    .s-danger .s-head { border-bottom-color:rgba(239,68,68,0.1); }

    /* Toast */
    .s-toast {
        position:fixed; bottom:24px; right:24px; z-index:10000;
        padding:12px 20px; border-radius:10px; font-size:13px; font-weight:500;
        backdrop-filter:blur(12px); animation:scaleIn 0.25s ease-out;
        display:none; align-items:center; gap:8px;
    }
    .s-toast.ok { background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3); color:#34D399; }
    .s-toast.err { background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#F87171; }

    @media(max-width:768px) {
        .settings-grid { grid-template-columns:1fr; }
    }
</style>

<div class="settings-wrap">

    <div class="settings-hero">
        <h1>Paramètres</h1>
        <p>Gérez votre profil, sécurité et préférences</p>
    </div>

    <div class="settings-grid" data-stagger>

        <!-- Profile -->
        <div class="s-card settings-full">
            <div class="s-head">
                <div class="s-icon blue"><i class="fas fa-user"></i></div>
                <div class="s-head-text">
                    <h3>Profil</h3>
                    <p>Informations personnelles</p>
                </div>
            </div>
            <div class="s-body">
                <div style="display:flex; align-items:center; gap:20px; margin-bottom:24px;">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($profile['email'] ?? 'U', 0, 2)) ?>
                    </div>
                    <div>
                        <div style="font-size:18px; font-weight:700; color:#F1F5F9;">
                            <?= htmlspecialchars($profile['name'] ?? 'Utilisateur') ?>
                        </div>
                        <div style="font-size:13px; color:#64748B; margin-top:2px;">
                            <?= htmlspecialchars($profile['email'] ?? '') ?>
                        </div>
                        <div style="display:flex; gap:8px; margin-top:8px;">
                            <span style="padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:600;
                                         background:rgba(37,99,235,0.1); color:#60A5FA; border:1px solid rgba(37,99,235,0.2);">
                                <?= ($profile['role'] ?? '') === 'admin' ? 'Administrateur' : 'Utilisateur' ?>
                            </span>
                            <span style="padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:600;
                                         background:rgba(16,185,129,0.1); color:#34D399; border:1px solid rgba(16,185,129,0.2);">
                                <?= $plan ?>
                            </span>
                            <span style="padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:600;
                                         background:rgba(100,116,139,0.1); color:#94A3B8; border:1px solid rgba(100,116,139,0.2);">
                                Membre depuis <?= date('d/m/Y', strtotime($profile['created_at'] ?? 'now')) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <form id="profileForm" class="s-fields" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= $csrf ?>">
                    <div class="s-field">
                        <label>Nom complet</label>
                        <input type="text" name="name" class="s-input" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" placeholder="Votre nom">
                    </div>
                    <div class="s-field">
                        <label>Adresse email</label>
                        <input type="email" class="s-input" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled>
                        <div class="s-hint">L'email ne peut pas être modifié</div>
                    </div>
                    <div style="text-align:right;">
                        <button type="submit" class="s-btn s-btn-primary">
                            <i class="fas fa-check" style="font-size:12px;"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password -->
        <div class="s-card">
            <div class="s-head">
                <div class="s-icon amber"><i class="fas fa-lock"></i></div>
                <div class="s-head-text">
                    <h3>Mot de passe</h3>
                    <p>Sécurisez votre compte</p>
                </div>
            </div>
            <div class="s-body">
                <form id="passwordForm" class="s-fields" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= $csrf ?>">
                    <div class="s-field">
                        <label>Mot de passe actuel</label>
                        <input type="password" name="current_password" class="s-input" required autocomplete="current-password" placeholder="••••••••">
                    </div>
                    <div class="s-field">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="new_password" class="s-input" required minlength="8" autocomplete="new-password" placeholder="••••••••">
                        <div class="s-hint">Minimum 8 caractères</div>
                    </div>
                    <div class="s-field">
                        <label>Confirmer</label>
                        <input type="password" name="confirm_password" class="s-input" required minlength="8" autocomplete="new-password" placeholder="••••••••">
                    </div>
                    <button type="submit" class="s-btn s-btn-warning" style="width:100%;">
                        <i class="fas fa-key" style="font-size:12px;"></i> Changer le mot de passe
                    </button>
                </form>
            </div>
        </div>

        <!-- Sessions -->
        <div class="s-card">
            <div class="s-head">
                <div class="s-icon rose"><i class="fas fa-shield-halved"></i></div>
                <div class="s-head-text">
                    <h3>Sessions actives</h3>
                    <p>Connexions en cours</p>
                </div>
                <span class="s-head-badge"><?= $sessionCount ?> active<?= $sessionCount > 1 ? 's' : '' ?></span>
            </div>
            <div class="s-body">
                <?php if (!empty($recentSessions)): ?>
                <div style="margin-bottom:20px;">
                    <?php foreach ($recentSessions as $i => $s): ?>
                    <div class="session-row" style="animation:fadeUp <?= 0.1 + $i * 0.05 ?>s ease-out;">
                        <div class="session-icon"><i class="fas fa-desktop"></i></div>
                        <div class="session-info">
                            <div class="session-ip"><?= htmlspecialchars($s['ip_address'] ?? 'N/A') ?></div>
                            <div class="session-ua"><?= htmlspecialchars(substr($s['user_agent'] ?? '', 0, 60)) ?></div>
                        </div>
                        <div class="session-date"><?= date('d/m H:i', strtotime($s['last_activity'] ?? $s['created_at'] ?? 'now')) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:24px; color:#64748B; font-size:13px;">
                    <i class="fas fa-check-circle" style="color:#34D399; margin-right:6px;"></i> Aucune session active
                </div>
                <?php endif; ?>

                <form id="destroySessionsForm" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= $csrf ?>">
                    <button type="submit" class="s-btn s-btn-danger" style="width:100%;">
                        <i class="fas fa-right-from-bracket" style="font-size:12px;"></i> Déconnecter toutes les sessions
                    </button>
                </form>
            </div>
        </div>

        <!-- Storage -->
        <div class="s-card settings-full">
            <div class="s-head">
                <div class="s-icon cyan"><i class="fas fa-database"></i></div>
                <div class="s-head-text">
                    <h3>Stockage</h3>
                    <p>Espace utilisé sur votre compte</p>
                </div>
                <span style="margin-left:auto; font-size:13px; color:#94A3B8; font-family:'JetBrains Mono',monospace;">
                    <?= formatBytes($used) ?> / <?= formatBytes($quota) ?>
                </span>
            </div>
            <div class="s-body">
                <div class="storage-track">
                    <div class="storage-fill <?= $pct > 90 ? 'warn' : '' ?>" style="width:<?= min($pct, 100) ?>%"></div>
                </div>
                <div class="storage-stats">
                    <span><?= round($pct, 1) ?>% utilisé</span>
                    <span><?= formatBytes($quota - $used) ?> restant</span>
                </div>
                <?php if ($pct > 80): ?>
                <div style="margin-top:12px; padding:10px 14px; border-radius:8px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); font-size:12px; color:#FBBF24; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-triangle-exclamation"></i> Espace de stockage bientôt atteint. Considérez un upgrade.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="s-card settings-full s-danger">
            <div class="s-head">
                <div class="s-icon rose"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="s-head-text">
                    <h3 style="color:#F87171;">Zone de danger</h3>
                    <p>Actions irréversibles sur votre compte</p>
                </div>
            </div>
            <div class="s-body" style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:14px; font-weight:600; color:#F1F5F9;">Supprimer le compte</div>
                    <div style="font-size:12px; color:#64748B; margin-top:2px;">Toutes vos données seront définitivement effacées.</div>
                </div>
                <button type="button" class="s-btn s-btn-danger" onclick="if(confirm('Supprimer définitivement votre compte ? Cette action est irréversible.')) { /* TODO */ }">
                    <i class="fas fa-trash" style="font-size:12px;"></i> Supprimer
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Toast -->
<div id="sToast" class="s-toast"></div>

<script>
function sToast(msg, type='ok') {
    const t = document.getElementById('sToast');
    t.className = 's-toast ' + type;
    t.innerHTML = '<i class="fas fa-' + (type==='ok' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg;
    t.style.display = 'flex';
    setTimeout(() => t.style.display = 'none', 3000);
}

async function safeFetch(url, body) {
    try {
        const res = await fetch(url, { method: 'POST', body });
        const text = await res.text();
        try { return JSON.parse(text); } catch(e) { return { error: 'Réponse serveur invalide' }; }
    } catch(e) { return { error: 'Erreur réseau' }; }
}

document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = await safeFetch('/config/profile', new FormData(e.target));
    sToast(data.message || data.error, data.success ? 'ok' : 'err');
});

document.getElementById('passwordForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Envoi...'; }
    const data = await safeFetch('/config/password', new FormData(e.target));
    sToast(data.message || data.error, data.success ? 'ok' : 'err');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-key" style="font-size:12px;"></i> Changer le mot de passe'; }
    if (data.success) e.target.reset();
});

document.getElementById('destroySessionsForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!confirm('Déconnecter toutes les autres sessions ?')) return;
    const data = await safeFetch('/config/sessions/destroy', new FormData(e.target));
    sToast(data.message || data.error, data.success ? 'ok' : 'err');
});
</script>
