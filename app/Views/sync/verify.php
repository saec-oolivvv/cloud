<?php
/** @var array|null $pending */
/** @var string $code */

$csrf = \Saec\Core\Session::csrfToken();
$pending = $pending ?? null;
$code = $code ?? '';
?>
<style>
    .verify-wrap { max-width: 560px; margin: 0 auto; padding: 40px 24px 56px; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
    @keyframes scaleIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }

    .v-card {
        background:#232632; border:1px solid #3A4257; border-radius:16px; padding:32px;
        text-align:center; animation: fadeUp 0.4s ease-out;
    }
    .v-icon { font-size:40px; margin-bottom:12px; }
    .v-title { font-size:22px; font-weight:800; letter-spacing:-0.02em; color:#F1F5F9; margin-bottom:6px; }
    .v-sub { font-size:14px; color:#94A3B8; margin-bottom:24px; }

    .v-code {
        font-family:'JetBrains Mono',monospace; font-size:32px; font-weight:700;
        letter-spacing:0.14em; color:#00FF88; background:rgba(0,255,136,0.06);
        border:1px solid rgba(0,255,136,0.2); border-radius:12px; padding:16px;
        margin-bottom:24px; animation: scaleIn 0.3s ease-out;
    }
    .v-status {
        font-size:13px; padding:10px 16px; border-radius:10px; margin-bottom:16px;
    }
    .v-status.ok { background:rgba(34,197,94,0.1); color:#4ADE80; border:1px solid rgba(34,197,94,0.2); }
    .v-status.warn { background:rgba(245,158,11,0.1); color:#FBBF24; border:1px solid rgba(245,158,11,0.2); }
    .v-status.err { background:rgba(239,68,68,0.1); color:#F87171; border:1px solid rgba(239,68,68,0.2); }

    .v-btn {
        display:inline-block; background:linear-gradient(135deg,#00FF88,#06B6D4);
        color:#040918; font-weight:700; font-size:14px; padding:12px 32px;
        border-radius:12px; border:0; cursor:pointer; transition:transform 0.15s, box-shadow 0.15s;
    }
    .v-btn:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,255,136,0.25); }
    .v-btn:disabled { opacity:0.5; cursor:default; transform:none; }

    .v-input {
        width:100%; background:#1E2732; border:1px solid #3A4257; border-radius:12px;
        color:#E2E8F0; font-family:'JetBrains Mono',monospace; font-size:22px;
        letter-spacing:0.12em; text-align:center; text-transform:uppercase; padding:14px;
        margin-bottom:16px; outline:none;
    }
    .v-input:focus { border-color:#00FF88; }
    .v-note { font-size:12px; color:#64748B; margin-top:18px; }
</style>

<div class="verify-wrap">
    <div class="v-card">
        <div class="v-icon">🔐</div>
        <h1 class="v-title">Autoriser SAEC Sync</h1>
        <p class="v-sub">Saisissez le code affiché dans le client desktop pour connecter cet appareil.</p>

        <?php if ($pending && $pending['status'] === 'pending'): ?>
            <div class="v-status ok">✔ Ce code est valide et en attente</div>
            <div class="v-code"><?= htmlspecialchars($pending['user_code']) ?></div>
            <button class="v-btn" id="approveBtn"
                    onclick="approveDevice('<?= htmlspecialchars($pending['user_code']) ?>')">
                Autoriser cet appareil
            </button>
            <p class="v-note">Vous autorisez le client SAEC Sync à accéder à votre espace cloud (lecture + écriture sync).</p>

        <?php elseif ($code && !$pending): ?>
            <div class="v-status err">✘ Code invalide ou expiré</div>
            <p class="v-sub">Vérifiez le code dans le client et réessayez.</p>

        <?php else: ?>
            <form method="POST" action="/device" id="verifyForm">
                <input type="hidden" name="_token" value="<?= $csrf ?>">
                <input type="text" name="code" class="v-input" placeholder="ABCD-EFGH"
                       maxlength="9" autocomplete="off" autofocus required>
                <button type="submit" class="v-btn">Vérifier le code</button>
            </form>
            <p class="v-note">Le code est affiché dans le client SAEC Sync sur l'appareil à connecter.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function approveDevice(userCode) {
    document.getElementById('approveBtn').disabled = true;
    const fd = new FormData();
    fd.append('_token', '<?= $csrf ?>');
    fd.append('code', userCode);
    fetch('/device', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.querySelector('.v-status').className = 'v-status ok';
                document.querySelector('.v-status').textContent = '✔ Appareil autorisé. Retournez au client sync.';
                document.querySelector('.v-btn').textContent = 'Autorisé ✓';
            } else {
                document.getElementById('approveBtn').disabled = false;
                document.getElementById('approveBtn').textContent = data.error || 'Erreur — réessayer';
            }
        })
        .catch(() => {
            document.getElementById('approveBtn').disabled = false;
            document.getElementById('approveBtn').textContent = 'Erreur réseau — réessayer';
        });
}
</script>