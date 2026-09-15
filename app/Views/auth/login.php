<?php
$errors = $errors ?? [];
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'en';
$availableLangs = $lang ? Saec\Core\Translation::getAvailable() : [];
$redirect = $redirect ?? '';
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — SAEC Cloud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <style>
        :root {
            --bg: #0F111A; --bg2: #181A20; --card: #232632;
            --border: #3A4257; --text: #F1F5F9; --text2: #94A3B8; --muted: #64748B;
            --blue: #2563EB; --blue2: #3B82F6; --cyan: #06B6D4; --green: #10B981;
            --rose: #EF4444; --radius: 8px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; background: var(--bg); font-family: 'Inter', sans-serif;
            overflow: hidden; position: relative;
        }
        ::selection { background: var(--blue); color: #fff; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        @keyframes glow { 0%, 100% { box-shadow: 0 0 20px rgba(37,99,235,0.15); } 50% { box-shadow: 0 0 40px rgba(37,99,235,0.25); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        /* ── Background ── */
        .bg-gradient {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(37,99,235,0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(6,182,212,0.07) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(139,92,246,0.05) 0%, transparent 50%);
        }
        .bg-grid {
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
        }

        /* ── Status Bar ── */
        .status-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 10;
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 24px; background: rgba(0,0,0,0.4);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--muted);
            letter-spacing: 0.04em; backdrop-filter: blur(8px);
        }
        .status-bar .left { display: flex; align-items: center; gap: 6px; }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); animation: pulse 2s infinite; }

        /* ── Language ── */
        .lang-select {
            position: fixed; top: 40px; right: 24px; z-index: 10;
        }
        .lang-select select {
            padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border);
            background: rgba(24,26,32,0.8); color: var(--text2); font-size: 12px;
            font-family: inherit; cursor: pointer; backdrop-filter: blur(8px);
            appearance: none;
            background: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 8px center;
            padding-right: 28px;
        }
        .lang-select select option { background: var(--bg); color: var(--text); }

        /* ── Auth Card ── */
        .auth-card {
            position: relative; z-index: 5; width: 100%; max-width: 400px;
            padding: 40px 36px 36px; margin: 0 20px;
            background: var(--card); border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.4), 0 0 80px rgba(37,99,235,0.06);
            animation: scaleIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }

        /* ── Logo ── */
        .logo-wrap {
            display: flex; flex-direction: column; align-items: center; gap: 16px;
            margin-bottom: 32px;
        }
        .logo-icon {
            width: 64px; height: 64px; border-radius: 16px;
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(37,99,235,0.25);
            animation: glow 3s ease-in-out infinite;
            position: relative; overflow: hidden;
        }
        .logo-icon::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.15) 50%, transparent 60%);
            animation: shimmer 3s infinite;
        }
        .logo-icon img { height: 34px; width: auto; position: relative; z-index: 1; }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

        .logo-text { text-align: center; }
        .logo-title {
            font-size: 22px; font-weight: 800; letter-spacing: -0.03em;
            background: linear-gradient(135deg, var(--text), var(--cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .logo-sub { font-size: 13px; color: var(--muted); margin-top: 4px; }

        /* ── Errors ── */
        .error-box {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 14px; border-radius: 8px;
            background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
            margin-bottom: 20px; animation: slideUp 0.3s ease-out;
        }
        .error-box .err-icon { color: var(--rose); flex-shrink: 0; margin-top: 1px; font-size: 14px; }
        .error-box .err-text { font-size: 13px; color: var(--rose); line-height: 1.4; }

        /* ── Welcome ── */
        .welcome { text-align: center; margin-bottom: 24px; }
        .welcome h2 { font-size: 15px; font-weight: 500; color: var(--text2); }

        /* ── Form ── */
        .login-form { display: flex; flex-direction: column; gap: 16px; }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field-label {
            font-size: 12px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--text2);
        }
        .field-input {
            width: 100%; padding: 11px 14px; border-radius: 8px;
            border: 1px solid var(--border); background: var(--bg2);
            color: var(--text); font-size: 14px; font-family: inherit;
            transition: all 0.2s; outline: none;
        }
        .field-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.12); background: var(--bg); }
        .field-input::placeholder { color: var(--muted); }

        .submit-btn {
            width: 100%; padding: 12px; margin-top: 4px; border-radius: 8px;
            background: var(--blue); color: #fff; font-size: 14px; font-weight: 600;
            border: none; cursor: pointer; font-family: inherit;
            transition: all 0.2s cubic-bezier(0.22,1,0.36,1);
            position: relative; overflow: hidden;
        }
        .submit-btn:hover { background: var(--blue2); box-shadow: 0 4px 16px rgba(37,99,235,0.35); transform: translateY(-1px); }
        .submit-btn:active { transform: translateY(0) scale(0.98); }
        .submit-btn[disabled] { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* ── Footer ── */
        .auth-footer {
            margin-top: 28px; text-align: center; font-size: 11px; color: var(--muted);
            padding-top: 20px; border-top: 1px solid var(--border);
        }
        .auth-footer a { color: var(--text2); text-decoration: none; transition: color 0.15s; }
        .auth-footer a:hover { color: var(--blue2); }

        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        @media (max-width: 480px) {
            .auth-card { padding: 32px 24px 28px; }
            .status-bar .right { display: none; }
        }
    </style>
</head>
<body>

    <div class="bg-gradient"></div>
    <div class="bg-grid"></div>

    <!-- Status Bar -->
    <div class="status-bar">
        <div class="left"><span class="status-dot"></span> SAEC_CLOUD // STATUS: NOMINAL</div>
        <div class="right">LOCATION: LONDON · AES-256-GCM · TLS 1.3</div>
    </div>

    <!-- Language -->
    <div class="lang-select">
        <form method="GET" action="/login">
            <select name="lang" onchange="this.form.submit()">
                <?php foreach ($availableLangs as $code => $info): ?>
                <option value="<?= $code ?>" <?= $code === $currentLang ? 'selected' : '' ?>><?= $info['flag'] ?> <?= $info['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Auth Card -->
    <div class="auth-card">
        <div class="logo-wrap">
            <div class="logo-icon">
                <img src="/img/logo.png" alt="SAEC">
            </div>
            <div class="logo-text">
                <div class="logo-title">SAEC Cloud</div>
                <div class="logo-sub">Cloud souverain · Chiffrement AES-256</div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <span class="err-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></span>
            <div>
                <?php foreach ($errors as $error): ?>
                <div class="err-text"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="welcome"><h2>Connexion à votre espace</h2></div>

        <form method="POST" action="/login" class="login-form" id="loginForm">
            <?= Saec\Core\Session::csrfField() ?>
            <?php if ($redirect): ?><input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>"><?php endif; ?>
            <div class="field">
                <label class="field-label" for="email">Adresse email</label>
                <input type="email" id="email" name="email" class="field-input" placeholder="admin@saec.me" required autocomplete="email" autofocus>
            </div>
            <div class="field">
                <label class="field-label" for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="field-input" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" class="submit-btn" id="submitBtn">
                Se connecter
            </button>
        </form>

        <div class="auth-footer">
            © <?= date('Y') ?> SAEC Ltd. — <a href="/">Retour à l'accueil</a>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="animation:spin 1s linear infinite;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Connexion...';
    });
    </script>

</body>
</html>
