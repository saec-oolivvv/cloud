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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh;">

    <div class="auth-box">
        <!-- Language -->
        <form method="GET" action="/login" style="position:absolute; top:16px; right:16px;">
            <select name="lang" onchange="this.form.submit()" style="
                padding:4px 8px; border-radius:var(--radius-sm);
                border:1px solid var(--border-subtle); background:var(--bg-secondary);
                color:var(--text-primary); font-size:12px; cursor:pointer;
                font-family:inherit;
            ">
                <?php foreach ($availableLangs as $code => $info): ?>
                <option value="<?= $code ?>" <?= $code === $currentLang ? 'selected' : '' ?>>
                    <?= $info['flag'] ?> <?= $info['name'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Brand -->
        <div style="text-align:center; margin-bottom:var(--space-6);">
            <img src="/img/logo.png" alt="SAEC" style="height:48px; margin:0 auto var(--space-3); display:block;">
            <div class="auth-title">SAEC Cloud</div>
            <div class="auth-subtitle"><?= t('app.tagline') ?></div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
            <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h2 style="font-size:15px; font-weight:500; margin-bottom:var(--space-4); color:var(--text-secondary);">
            <?= t('auth.welcome_back') ?>
        </h2>

        <form method="POST" action="/login">
            <?= Saec\Core\Session::csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="email"><?= t('auth.email') ?></label>
                <input type="email" id="email" name="email" class="form-input"
                       placeholder="admin@saec.me" required autocomplete="email" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password"><?= t('auth.password') ?></label>
                <input type="password" id="password" name="password" class="form-input"
                       placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:var(--space-2);">
                <?= t('auth.login_button') ?>
            </button>
        </form>

        <div style="margin-top:var(--space-6); text-align:center; font-size:12px; color:var(--text-muted);">
            <?= t('app.copyright') ?><br>
            <span style="font-family:var(--font-mono); font-size:11px;">AES-256-GCM · TLS 1.3</span>
        </div>
    </div>

    <script>
    document.querySelector('form[action="/login"]')?.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = true; btn.textContent = '...'; }
    });
    </script>
</body>
</html>
