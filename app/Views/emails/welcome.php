<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a0a; color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0a0a0a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #111111; border-radius: 12px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 24px; border-bottom: 1px solid #222;">
                            <strong style="color: #00ff88;">SAEC</strong> <span style="color: #888888; font-size: 14px;">Cloud</span>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 32px 24px;">
                            <h1 style="color: #00ff88; margin: 0 0 16px 0; font-size: 24px;">Bienvenue sur SAEC Cloud</h1>
                            <p style="color: #ffffff; margin: 0 0 24px 0;">Votre compte a été créé avec succès.</p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a1a; border-radius: 8px; margin: 0 0 24px 0;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <p style="margin: 0 0 8px 0;"><strong style="color: #888888;">Email :</strong> <?= htmlspecialchars($email) ?></p>
                                        <p style="margin: 0;"><strong style="color: #888888;">Mot de passe :</strong> <span style="color: #00ff88; font-family: monospace;"><?= htmlspecialchars($password) ?></span></p>
                                    </td>
                                </tr>
                            </table>
                            
                            <table cellpadding="0" cellspacing="0" style="margin: 0 0 24px 0;">
                                <tr>
                                    <td style="background-color: #00ff88; border-radius: 6px;">
                                        <a href="<?= htmlspecialchars($login_url) ?>" style="display: inline-block; padding: 12px 24px; color: #000000; text-decoration: none; font-weight: bold;">Se connecter</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #888888; font-size: 12px; margin: 0;">Changez votre mot de passe après la première connexion.</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px; border-top: 1px solid #222; text-align: center;">
                            <p style="color: #888888; font-size: 12px; margin: 0;">
                                © 2026 SAEC Ltd. — Bespoke Systems Engineering<br>
                                Sovereign open-source stack
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
