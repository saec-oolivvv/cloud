<?php

declare(strict_types=1);

namespace Saec\Core;

class Mailer
{
    private string $apiKey;
    private string $fromEmail;
    private string $fromName;
    private string $apiUrl = 'https://api.mailersend.com/v1/email';

    public function __construct()
    {
        $config = $GLOBALS['SAEC_CONFIG']['email'] ?? [];
        $this->apiKey = $config['api_key'] ?? '';
        $this->fromEmail = $config['from_email'] ?? 'cloud@saec.me';
        $this->fromName = $config['from_name'] ?? 'SAEC Cloud';
    }

    public function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        if (empty($this->apiKey)) {
            error_log('[Mailer] API key not configured');
            return false;
        }

        $payload = [
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName,
            ],
            'to' => [
                ['email' => $to],
            ],
            'subject' => $subject,
            'html' => $html,
            'text' => $text ?: strip_tags($html),
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("[Mailer] Failed to send to {$to}: HTTP {$httpCode} - {$response}");
        return false;
    }

    public function sendWelcome(string $to, string $name, string $password): bool
    {
        $subject = 'Bienvenue sur SAEC Cloud';
        $html = $this->render('welcome', [
            'name' => $name,
            'email' => $to,
            'password' => $password,
            'login_url' => 'https://cloud.saec.me/login',
        ]);

        return $this->send($to, $subject, $html);
    }

    public function sendPasswordReset(string $to, string $name, string $token): bool
    {
        $subject = 'Réinitialisation de votre mot de passe';
        $html = $this->render('password-reset', [
            'name' => $name,
            'reset_url' => 'https://cloud.saec.me/reset-password?token=' . $token,
            'expires' => '30 minutes',
        ]);

        return $this->send($to, $subject, $html);
    }

    public function sendEmailVerification(string $to, string $name, string $token): bool
    {
        $subject = 'Vérifiez votre adresse email — SAEC Cloud';
        $html = $this->render('email-verification', [
            'name' => $name,
            'verify_url' => 'https://cloud.saec.me/verify-email?token=' . $token,
            'expires' => '24 heures',
        ]);

        return $this->send($to, $subject, $html);
    }

    public function sendShareNotification(string $to, string $sharedBy, string $fileName, string $shareLink): bool
    {
        $subject = $sharedBy . ' a partagé un fichier avec vous';
        $html = $this->render('share-notification', [
            'shared_by' => $sharedBy,
            'file_name' => $fileName,
            'share_link' => $shareLink,
        ]);

        return $this->send($to, $subject, $html);
    }

    public function sendTenantExpiry(string $to, string $tenantName, string $endDate, int $daysLeft): bool
    {
        $subject = "Votre accès SAEC Cloud expire dans {$daysLeft} jour(s)";
        $html = $this->render('tenant-expiry', [
            'tenant_name' => $tenantName,
            'end_date' => $endDate,
            'days_left' => $daysLeft,
            'contact_url' => 'https://saec.me/contact',
        ]);

        return $this->send($to, $subject, $html);
    }

    public function sendQuotaAlert(string $to, string $tenantName, string $type, int $percent): bool
    {
        $subject = "Alerte quota SAEC Cloud — {$type}";
        $html = $this->render('quota-alert', [
            'tenant_name' => $tenantName,
            'type' => $type,
            'percent' => $percent,
        ]);

        return $this->send($to, $subject, $html);
    }

    public function sendNewLoginAlert(string $to, string $name, string $ip, string $userAgent, string $location = ''): bool
    {
        $subject = 'Nouvelle connexion détectée — SAEC Cloud';
        $html = $this->render('new-login', [
            'name' => $name,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'location' => $location,
            'time' => date('d/m/Y à H:i:s'),
            'settings_url' => 'https://cloud.saec.me/config',
        ]);

        return $this->send($to, $subject, $html);
    }

    public function sendBackupSuccess(string $to, string $tenantName, string $backupDate, string $backupSize, string $backupDuration): bool
    {
        $subject = 'Backup réussi — SAEC Cloud';
        $html = $this->render('backup-success', [
            'tenant_name' => $tenantName,
            'backup_date' => $backupDate,
            'backup_size' => $backupSize,
            'backup_duration' => $backupDuration,
        ]);

        return $this->send($to, $subject, $html);
    }

    public function sendBackupFailure(string $to, string $tenantName, string $backupDate, string $errorMessage): bool
    {
        $subject = 'Échec du backup — SAEC Cloud';
        $html = $this->render('backup-failure', [
            'tenant_name' => $tenantName,
            'backup_date' => $backupDate,
            'error_message' => $errorMessage,
        ]);

        return $this->send($to, $subject, $html);
    }

    private function render(string $template, array $data): string
    {
        $templatePath = __DIR__ . '/../app/Views/emails/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            return $this->fallbackTemplate($template, $data);
        }

        extract($data);
        ob_start();
        require $templatePath;
        return ob_get_clean();
    }

    private function fallbackTemplate(string $template, array $data): string
    {
        $appName = 'SAEC Cloud';
        $accent = '#00ff88';
        $bg = '#0a0a0a';
        $surface = '#111111';
        $text = '#ffffff';
        $muted = '#888888';

        $content = '';
        
        switch ($template) {
            case 'welcome':
                $content = "
                    <h1 style='color: {$accent};'>Bienvenue sur {$appName}</h1>
                    <p>Votre compte a été créé avec succès.</p>
                    <div style='background: {$surface}; padding: 16px; border-radius: 8px; margin: 16px 0;'>
                        <p><strong>Email :</strong> {$data['email']}</p>
                        <p><strong>Mot de passe :</strong> {$data['password']}</p>
                    </div>
                    <p><a href='{$data['login_url']}' style='background: {$accent}; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>Se connecter</a></p>
                    <p style='color: {$muted}; font-size: 12px;'>Changez votre mot de passe après la première connexion.</p>
                ";
                break;
                
            case 'password-reset':
                $content = "
                    <h1 style='color: {$accent};'>Réinitialisation du mot de passe</h1>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                    <p><a href='{$data['reset_url']}' style='background: {$accent}; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>Réinitialiser</a></p>
                    <p style='color: {$muted}; font-size: 12px;'>Ce lien expire dans {$data['expires']}.</p>
                ";
                break;

            case 'email-verification':
                $content = "
                    <h1 style='color: {$accent};'>Vérifiez votre email</h1>
                    <p>Bonjour {$data['name']},</p>
                    <p>Merci pour votre inscription sur {$appName}. Veuillez cliquer sur le bouton ci-dessous pour vérifier votre adresse email :</p>
                    <p style='text-align: center; margin: 24px 0;'><a href='{$data['verify_url']}' style='background: {$accent}; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;'>Vérifier mon email</a></p>
                    <p style='color: {$muted}; font-size: 12px;'>Ce lien expire dans {$data['expires']}. Si vous n'avez pas créé de compte, ignorez cet email.</p>
                ";
                break;
                
            case 'share-notification':
                $content = "
                    <h1 style='color: {$accent};'>Fichier partagé</h1>
                    <p><strong>{$data['shared_by']}</strong> a partagé un fichier avec vous.</p>
                    <div style='background: {$surface}; padding: 16px; border-radius: 8px; margin: 16px 0;'>
                        <p><strong>Fichier :</strong> {$data['file_name']}</p>
                    </div>
                    <p><a href='{$data['share_link']}' style='background: {$accent}; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>Accéder au fichier</a></p>
                ";
                break;
                
            case 'tenant-expiry':
                $content = "
                    <h1 style='color: #ffaa00;'>⚠ Accès expirant</h1>
                    <p>Votre accès <strong>{$data['tenant_name']}</strong> expire le <strong>{$data['end_date']}</strong>.</p>
                    <p>Il vous reste <strong>{$data['days_left']} jour(s)</strong>.</p>
                    <p>Contactez-nous pour prolonger : <a href='{$data['contact_url']}'>saec.me/contact</a></p>
                ";
                break;
                
            case 'quota-alert':
                $content = "
                    <h1 style='color: #ff4444;'>⚠ Alerte quota</h1>
                    <p>Votre espace <strong>{$data['type']}</strong> est utilisé à <strong>{$data['percent']}%</strong>.</p>
                    <p>Tenant : {$data['tenant_name']}</p>
                ";
                break;

            case 'new-login':
                $location = $data['location'] ?: 'Inconnue';
                $content = "
                    <h1 style='color: #ffaa00;'>🔒 Nouvelle connexion</h1>
                    <p>Bonjour {$data['name']},</p>
                    <p>Une nouvelle connexion a été détectée sur votre compte SAEC Cloud :</p>
                    <div style='background: {$surface}; padding: 16px; border-radius: 8px; margin: 16px 0;'>
                        <p><strong>IP :</strong> {$data['ip']}</p>
                        <p><strong>Appareil :</strong> " . htmlspecialchars(substr($data['user_agent'], 0, 80)) . "</p>
                        <p><strong>Localisation :</strong> {$location}</p>
                        <p><strong>Date :</strong> {$data['time']}</p>
                    </div>
                    <p>Si ce n'est pas vous, changez immédiatement votre mot de passe et activez la 2FA.</p>
                    <p style='text-align: center; margin: 24px 0;'><a href='{$data['settings_url']}' style='background: #F59E0B; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;'>Gérer la sécurité</a></p>
                ";
                break;
        }

        return $this->baseLayout($content);
    }

    private function baseLayout(string $content): string
    {
        $accent = '#00ff88';
        $bg = '#0a0a0a';
        $surface = '#111111';
        $text = '#ffffff';
        $muted = '#888888';

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; background-color: {$bg}; color: {$text}; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: {$bg}; padding: 40px 20px;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background-color: {$surface}; border-radius: 12px; overflow: hidden;'>
                    <!-- Header -->
                    <tr>
                        <td style='padding: 24px; border-bottom: 1px solid #222;'>
                            <strong style='color: {$accent};'>SAEC</strong> <span style='color: {$muted}; font-size: 14px;'>Cloud</span>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style='padding: 32px 24px;'>
                            {$content}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style='padding: 24px; border-top: 1px solid #222; text-align: center;'>
                            <p style='color: {$muted}; font-size: 12px; margin: 0;'>
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
</html>";
    }
}
