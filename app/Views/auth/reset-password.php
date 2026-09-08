<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — SAEC Cloud</title>
    <link rel="stylesheet" href="/assets/css/saec.css">
    <style>
        .auth-card {
            max-width: 420px; margin: 80px auto; padding: 40px;
            background: #111; border: 1px solid #222; border-radius: 12px;
        }
        .auth-card h1 { margin: 0 0 8px; font-size: 24px; }
        .auth-card p { color: #888; margin: 0 0 24px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; color: #aaa; }
        .form-group input {
            width: 100%; padding: 12px; background: #1a1a1a; border: 1px solid #333;
            border-radius: 6px; color: #fff; font-size: 14px; box-sizing: border-box;
        }
        .form-group input:focus { outline: none; border-color: #00ff88; }
        .btn-primary {
            width: 100%; padding: 12px; background: #00ff88; color: #000;
            border: none; border-radius: 6px; font-weight: 600; cursor: pointer;
            font-size: 14px;
        }
        .btn-primary:hover { background: #00cc6a; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h1>Nouveau mot de passe</h1>
        <p>Choisissez un mot de passe sécurisé (12+ caractères).</p>
        
        <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="background: #331111; border: 1px solid #ff4444; border-radius: 6px; padding: 12px; margin-bottom: 20px; color: #ff4444; font-size: 13px;">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); endif; ?>
        
        <form method="POST" action="/reset-password">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="password" required minlength="12" placeholder="••••••••••••">
            </div>
            <div class="form-group">
                <label>Confirmer</label>
                <input type="password" name="confirm" required minlength="12" placeholder="••••••••••••">
            </div>
            <button type="submit" class="btn-primary">Réinitialiser</button>
        </form>
    </div>
</body>
</html>
