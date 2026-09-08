<?php
/** @var string $error */
$error = $error ?? '';
?>

<style>
.totp-verify-wrap { max-width: 400px; margin: 80px auto; padding: 0 24px; }
.totp-verify-card { background: #232632; border: 1px solid #3A4257; border-radius: 16px; padding: 32px; text-align: center; }
.totp-verify-icon { width: 64px; height: 64px; border-radius: 50%; background: rgba(37,99,235,0.12); color: #60A5FA; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 28px; }
.totp-verify-title { color: #F1F5F9; font-size: 20px; font-weight: 700; margin-bottom: 8px; }
.totp-verify-sub { color: #64748B; font-size: 14px; margin-bottom: 24px; }
.totp-verify-input { width: 100%; padding: 14px 16px; border-radius: 8px; border: 1px solid #3A4257; background: #181A20; color: #F1F5F9; font-size: 20px; font-family: 'JetBrains Mono', monospace; text-align: center; letter-spacing: 0.4em; outline: none; transition: all 0.2s; }
.totp-verify-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
.totp-verify-btn { width: 100%; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all 0.2s; margin-top: 16px; background: #2563EB; color: #fff; }
.totp-verify-btn:hover { background: #3B82F6; }
.totp-verify-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.totp-verify-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
</style>

<div class="totp-verify-wrap">
    <div class="totp-verify-card">
        <div class="totp-verify-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:28px; height:28px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div class="totp-verify-title">Double Authentification</div>
        <div class="totp-verify-sub">Entrez le code à 6 chiffres depuis votre application d'authentification</div>

        <?php if (!empty($error)): ?>
        <div class="totp-verify-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/2fa/verify">
            <input type="text" name="code" class="totp-verify-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code" inputmode="numeric" autofocus>
            <button type="submit" class="totp-verify-btn">Vérifier</button>
        </form>
    </div>
</div>
