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
                            <h1 style="color: #ffaa00; margin: 0 0 16px 0; font-size: 24px;">⚠ Accès expirant</h1>
                            <p style="color: #ffffff; margin: 0 0 24px 0;">Bonjour,</p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a1a; border-radius: 8px; margin: 0 0 24px 0;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <p style="margin: 0 0 8px 0;"><strong style="color: #888888;">Tenant :</strong> <?= htmlspecialchars($tenant_name) ?></p>
                                        <p style="margin: 0 0 8px 0;"><strong style="color: #888888;">Date d'expiration :</strong> <?= htmlspecialchars($end_date) ?></p>
                                        <p style="margin: 0;"><strong style="color: #ffaa00;">Temps restant :</strong> <?= $days_left ?> jour(s)</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #ffffff; margin: 0 0 24px 0;">Pour prolonger votre accès, contactez-nous :</p>
                            
                            <table cellpadding="0" cellspacing="0" style="margin: 0 0 24px 0;">
                                <tr>
                                    <td style="background-color: #ffaa00; border-radius: 6px;">
                                        <a href="<?= htmlspecialchars($contact_url) ?>" style="display: inline-block; padding: 12px 24px; color: #000000; text-decoration: none; font-weight: bold;">Nous contacter</a>
                                    </td>
                                </tr>
                            </table>
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
