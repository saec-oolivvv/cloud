<?php
/** @var array $user */
/** @var string $secret */
/** @var string $provisioning_uri */
$user = $user ?? [];
$secret = $secret ?? '';
$provisioning_uri = $provisioning_uri ?? '';
?>

<style>
.totp-wrap { max-width: 480px; margin: 40px auto; padding: 0 24px; }
.totp-card { background: #232632; border: 1px solid #3A4257; border-radius: 16px; padding: 32px; }
.totp-title { color: #F1F5F9; font-size: 20px; font-weight: 700; margin-bottom: 8px; }
.totp-sub { color: #64748B; font-size: 14px; margin-bottom: 24px; }
.totp-qr { text-align: center; margin: 24px 0; }
.totp-qr img { border-radius: 12px; background: #fff; padding: 16px; }
.totp-secret { background: #1A1D27; border: 1px solid #3A4257; border-radius: 8px; padding: 12px 16px; font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #22D3EE; word-break: break-all; text-align: center; margin: 16px 0; }
.totp-label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94A3B8; margin-bottom: 6px; }
.totp-input { width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid #3A4257; background: #181A20; color: #F1F5F9; font-size: 16px; font-family: 'JetBrains Mono', monospace; text-align: center; letter-spacing: 0.3em; outline: none; transition: all 0.2s; }
.totp-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
.totp-btn { width: 100%; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all 0.2s; margin-top: 16px; }
.totp-btn-primary { background: #2563EB; color: #fff; }
.totp-btn-primary:hover { background: #3B82F6; }
.totp-steps { margin: 24px 0; }
.totp-step { display: flex; gap: 12px; margin-bottom: 16px; }
.totp-step-num { width: 28px; height: 28px; border-radius: 50%; background: rgba(37,99,235,0.12); color: #60A5FA; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
.totp-step-text { color: #CBD5E1; font-size: 14px; padding-top: 3px; }
.totp-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; display: none; }
</style>

<div class="totp-wrap">
    <div class="totp-card">
        <div class="totp-title">Activer la Double Authentification</div>
        <div class="totp-sub">Scannez le QR code avec Google Authenticator ou Authy</div>

        <div class="totp-steps">
            <div class="totp-step">
                <div class="totp-step-num">1</div>
                <div class="totp-step-text">Installez <strong>Google Authenticator</strong> ou <strong>Authy</strong> sur votre téléphone</div>
            </div>
            <div class="totp-step">
                <div class="totp-step-num">2</div>
                <div class="totp-step-text">Scannez le QR code ci-dessous</div>
            </div>
            <div class="totp-step">
                <div class="totp-step-num">3</div>
                <div class="totp-step-text">Entrez le code à 6 chiffres affiché dans l'app</div>
            </div>
        </div>

        <div class="totp-qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($provisioning_uri) ?>" alt="QR Code 2FA" width="200" height="200">
        </div>

        <div class="totp-label">Clé secrète (si vous ne pouvez pas scanner)</div>
        <div class="totp-secret"><?= htmlspecialchars($secret) ?></div>

        <div class="totp-error" id="totpError"></div>

        <form id="totpForm" onsubmit="verifyCode(event)">
            <label class="totp-label">Code de vérification</label>
            <input type="text" name="code" class="totp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autocomplete="off" inputmode="numeric">
            <button type="submit" class="totp-btn totp-btn-primary" id="submitBtn">Activer la 2FA</button>
        </form>
    </div>
</div>

<script>
function verifyCode(e) {
    e.preventDefault();
    const code = document.querySelector('[name="code"]').value;
    const errorEl = document.getElementById('totpError');
    const btn = document.getElementById('submitBtn');

    btn.textContent = 'Vérification...';
    btn.disabled = true;
    errorEl.style.display = 'none';

    fetch('/2fa/enable', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new URLSearchParams({ code: code })
    })
    .then(r => r.json())
    .then(result => {
        if (result.error) {
            errorEl.textContent = result.error;
            errorEl.style.display = 'block';
            btn.textContent = 'Activer la 2FA';
            btn.disabled = false;
        } else {
            window.location.href = '/config?success=2fa_enabled';
        }
    })
    .catch(err => {
        errorEl.textContent = 'Erreur: ' + err.message;
        errorEl.style.display = 'block';
        btn.textContent = 'Activer la 2FA';
        btn.disabled = false;
    });
}
</script>
