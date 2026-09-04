<?php
$errors = $errors ?? [];
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'en';
$availableLangs = $lang ? Saec\Core\Translation::getAvailable() : [];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('auth.login') ?> — SAEC Cloud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; overflow:hidden; }
        .auth-bg {
            position:fixed; inset:0; z-index:0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(37,99,235,0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(6,182,212,0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 60% 80%, rgba(16,185,129,0.04) 0%, transparent 50%),
                var(--bg-primary);
        }
        .auth-grid {
            position:fixed; inset:0; z-index:0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .auth-status {
            position:fixed; top:0; left:0; right:0; z-index:1;
            padding:8px var(--space-8);
            background:rgba(0,0,0,0.3); border-bottom:1px solid var(--border-subtle);
            font-family:var(--font-mono); font-size:11px; color:var(--text-muted);
            display:flex; justify-content:space-between; letter-spacing:0.05em;
        }
        .auth-status .dot { display:inline-block; width:6px; height:6px; border-radius:50%; background:var(--emerald-400); margin-right:6px; animation:pulse 2s infinite; }
    </style>
</head>
<body>
    <!-- Background -->
    <div class="auth-bg"></div>
    <div class="auth-grid"></div>

    <!-- Status Bar -->
    <div class="auth-status">
        <span><span class="dot"></span>SAEC_CLOUD // STATUS: NOMINAL</span>
        <span>LOCATION: LONDON · AES-256-GCM · TLS 1.3</span>
    </div>

    <!-- Language Selector -->
    <form method="GET" action="/login" style="position:fixed; top:36px; right:var(--space-8); z-index:2;">
        <select name="lang" onchange="this.form.submit()" style="
            padding:4px 8px; border-radius:var(--radius-sm);
            border:1px solid var(--border-subtle); background:var(--bg-secondary);
            color:var(--text-primary); font-size:12px; cursor:pointer;
            font-family:inherit; backdrop-filter:blur(8px);
        ">
            <?php foreach ($availableLangs as $code => $info): ?>
            <option value="<?= $code ?>" <?= $code === $currentLang ? 'selected' : '' ?>>
                <?= $info['flag'] ?> <?= $info['name'] ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Auth Box -->
    <div class="auth-box" style="z-index:1;">
        <!-- Brand -->
        <div style="text-align:center; margin-bottom:var(--space-6);">
            <div style="width:64px; height:64px; border-radius:var(--radius-xl); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center; margin:0 auto var(--space-4); box-shadow:0 8px 24px rgba(37,99,235,0.2);">
                <img src="/img/logo.png" alt="SAEC" style="height:36px; width:auto; filter:brightness(10);">
            </div>
            <div class="auth-title">SAEC Cloud</div>
            <div class="auth-subtitle"><?= t('app.tagline') ?></div>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div style="padding:var(--space-3) var(--space-4); border-radius:var(--radius-md); background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); margin-bottom:var(--space-4); animation:scaleIn 0.2s ease-out;">
            <?php foreach ($errors as $error): ?>
            <div style="font-size:13px; color:var(--rose-500); display:flex; align-items:center; gap:var(--space-2);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px; flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Welcome -->
        <h2 style="font-size:14px; font-weight:500; margin-bottom:var(--space-5); color:var(--text-secondary); text-align:center;">
            <?= t('auth.welcome_back') ?>
        </h2>

        <!-- Form -->
        <form method="POST" action="/login" style="display:flex; flex-direction:column; gap:var(--space-4);">
            <?= Saec\Core\Session::csrfField() ?>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="email"><?= t('auth.email') ?></label>
                <input type="email" id="email" name="email" class="form-input"
                       placeholder="admin@saec.me" required autocomplete="email" autofocus
                       style="padding:var(--space-3) var(--space-4); font-size:14px;">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="password"><?= t('auth.password') ?></label>
                <input type="password" id="password" name="password" class="form-input"
                       placeholder="••••••••" required autocomplete="current-password"
                       style="padding:var(--space-3) var(--space-4); font-size:14px;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:var(--space-2); padding:var(--space-3); font-size:14px; font-weight:600;">
                <?= t('auth.login_button') ?>
            </button>
        </form>

        <!-- Footer -->
        <div style="margin-top:var(--space-6); text-align:center; font-size:12px; color:var(--text-muted);">
            <?= t('app.copyright') ?>
        </div>
    </div>

    <script>
    document.querySelector('form[action="/login"]')?.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; animation:spin 1s linear infinite;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Connexion...';
        }
    });
    </script>
    <style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
</body>
</html>
