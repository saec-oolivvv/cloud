<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;
use Saec\Core\Encryption;
use Saec\Core\Session;
use Saec\Core\JWTHandler;
use Saec\Core\Validator;
use Saec\Core\Mailer;
use Saec\Core\Security;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Session::get('user')) {
            $this->redirect('/dashboard');
            return;
        }

        $pageTitle = 'Connexion';
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // IP blacklist
        if (Security::isIPBlacklisted($ip)) {
            $this->withError('Accès temporairement bloqué');
            $this->redirect('/login');
            return;
        }

        // Rate limit
        if (!Security::checkRateLimit($ip)) {
            $this->withError('Trop de tentatives. Réessayez dans 1 minute.');
            $this->redirect('/login');
            return;
        }

        $token = $_POST['_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            $this->withError('Token invalide');
            $this->redirect('/login');
            return;
        }

        $validator = new Validator($_POST);
        $validator->required('email', 'Email')
                  ->email('email')
                  ->required('password', 'Mot de passe');

        if ($validator->fails()) {
            $errors = $validator->errors();
            require __DIR__ . '/../Views/auth/login.php';
            return;
        }

        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];

        // Lockout check
        if (Security::isLockedOut($email)) {
            $this->withError('Compte temporairement bloqué (trop de tentatives)');
            $this->redirect('/login');
            return;
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, tenant_id, email, password_hash, role, active 
             FROM users 
             WHERE email = ?",
            [$email]
        );

        if (!$user || !$user['active'] || !Encryption::verifyPassword($password, $user['password_hash'])) {
            Security::recordLoginAttempt($email, $ip, false, 'invalid_credentials');

            $failures = Security::getLoginFailures($email);
            $remaining = max(0, 5 - $failures);

            if ($remaining === 0) {
                Security::recordLoginAttempt($email, $ip, false, 'account_locked');
            }

            $errors = ['Identifiants incorrects'];
            require __DIR__ . '/../Views/auth/login.php';
            return;
        }

        // Check email verification
        if (!Security::isEmailVerified($user['id'])) {
            $this->withError('Vérifiez votre email avant de vous connecter. Vérifiez votre boîte de réception.');
            $this->redirect('/login');
            return;
        }

        // Check account lockout after inactivity
        if (Security::isLockedOutByInactivity($user['id'])) {
            $this->withError('Compte verrouillé pour inactivité. Contactez l\'administrateur.');
            $this->redirect('/login');
            return;
        }

        // Check 2FA
        if (Security::isTotpEnabled($user['id'])) {
            Session::set('totp_pending_user_id', $user['id']);
            Security::recordLoginAttempt($email, $ip, true);
            $this->redirect('/2fa/verify');
            return;
        }

        Security::recordLoginAttempt($email, $ip, true);
        $this->completeLogin($user);
    }

    private function completeLogin(array $user): void
    {
        Session::start();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Check fingerprint for anomaly detection
        $fingerprint = Security::generateFingerprint($ua, $ip);
        $isAnomaly = Security::isAnomalyLogin($user['id'], $fingerprint);

        $userData = [
            'id' => (int) $user['id'],
            'tenant_id' => (int) $user['tenant_id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        Session::login($userData);

        $token = JWTHandler::generate(
            $userData['id'],
            $userData['tenant_id'],
            $userData['role']
        );
        Session::set('jwt', $token);

        // Session tracking — random token in cookie + DB
        Session::setSessionToken($userData['id']);

        // Update last login
        $db = Database::getInstance();
        $db->execute(
            "UPDATE users SET last_login = NOW(), last_login_ip = ?, last_active_at = NOW() WHERE id = ?",
            [$ip, $userData['id']]
        );

        // Register device and send alert if anomaly
        Security::registerDevice($user['id'], $fingerprint, $ip, $ua);

        if ($isAnomaly) {
            // Send new login alert email
            try {
                $mailer = new Mailer();
                $mailer->sendNewLoginAlert($user['email'], $user['email'], $ip, $ua);
            } catch (\Throwable $e) {
                error_log("[AuthController] Failed to send new login alert: {$e->getMessage()}");
            }
        }

        $this->logActivity($userData['id'], $userData['tenant_id'], 'auth.login');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $user = Session::get('user');
        if ($user) {
            $this->logActivity($user['id'], $user['tenant_id'], 'auth.logout');
        }

        Session::destroySessionToken();
        Session::destroy();
        $this->redirect('/login');
    }

    public function verifyEmail(): void
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $this->withError('Token de vérification invalide');
            $this->redirect('/login');
            return;
        }

        $userId = Security::validateEmailVerification($token);
        if (!$userId) {
            $this->withError('Lien de vérification invalide ou expiré');
            $this->redirect('/login');
            return;
        }

        Security::verifyEmail($userId);
        $this->withSuccess('Email vérifié avec succès. Vous pouvez vous connecter.');
        $this->redirect('/login');
    }

    public function resendVerification(): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        if (Security::isEmailVerified($user['id'])) {
            $this->withSuccess('Votre email est déjà vérifié.');
            $this->redirect('/dashboard');
            return;
        }

        $token = Security::createEmailVerification($user['id']);
        $mailer = new Mailer();
        $mailer->sendEmailVerification($user['email'], $user['email'], $token);

        $this->withSuccess('Email de vérification renvoyé.');
        $this->redirect('/dashboard');
    }

    // ═══════════════════════════════════════════════════════════
    // 2FA / TOTP
    // ═══════════════════════════════════════════════════════════

    public function totpSetup(): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        // Generate new secret
        $secret = Security::generateTotpSecret();
        $provisioningUri = Security::getTotpProvisioningUri($secret, $user['email']);

        // Store secret temporarily in session (not enabled yet)
        Session::set('totp_pending_secret', $secret);

        $data = [
            'user' => $user,
            'secret' => $secret,
            'provisioning_uri' => $provisioningUri,
            'pageTitle' => 'Double Authentification — Setup',
        ];

        require __DIR__ . '/../Views/auth/totp-setup.php';
    }

    public function totpEnable(): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->json(['error' => 'Non autorisé'], 401);
            return;
        }

        $code = $_POST['code'] ?? '';
        $secret = Session::get('totp_pending_secret');

        if (empty($secret) || empty($code)) {
            $this->json(['error' => 'Code requis'], 400);
            return;
        }

        if (!Security::verifyTotpCode($secret, $code)) {
            $this->json(['error' => 'Code invalide'], 400);
            return;
        }

        // Enable 2FA
        Security::enableTotp($user['id'], $secret);
        Session::remove('totp_pending_secret');

        $this->logActivity($user['id'], $user['tenant_id'], 'auth.2fa_enabled');
        $this->json(['success' => true, 'message' => '2FA activée avec succès']);
    }

    public function totpDisable(): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->json(['error' => 'Non autorisé'], 401);
            return;
        }

        $code = $_POST['code'] ?? '';
        if (empty($code)) {
            $this->json(['error' => 'Code requis'], 400);
            return;
        }

        $secret = Security::getTotpSecret($user['id']);
        if (!$secret || !Security::verifyTotpCode($secret, $code)) {
            $this->json(['error' => 'Code invalide'], 400);
            return;
        }

        Security::disableTotp($user['id']);
        $this->logActivity($user['id'], $user['tenant_id'], 'auth.2fa_disabled');
        $this->json(['success' => true, 'message' => '2FA désactivée']);
    }

    public function totpVerifyForm(): void
    {
        // During login — show TOTP verification form
        $pendingUserId = Session::get('totp_pending_user_id');
        if (!$pendingUserId) {
            $this->redirect('/login');
            return;
        }

        $pageTitle = 'Vérification — 2FA';
        require __DIR__ . '/../Views/auth/totp-verify.php';
    }

    public function totpVerify(): void
    {
        $pendingUserId = Session::get('totp_pending_user_id');
        if (!$pendingUserId) {
            $this->redirect('/login');
            return;
        }

        $code = $_POST['code'] ?? '';
        if (empty($code)) {
            $this->withError('Code requis');
            $this->redirect('/2fa/verify');
            return;
        }

        $secret = Security::getTotpSecret($pendingUserId);
        if (!$secret || !Security::verifyTotpCode($secret, $code)) {
            $this->withError('Code invalide');
            $this->redirect('/2fa/verify');
            return;
        }

        // Code valid — complete login
        Session::remove('totp_pending_user_id');

        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, tenant_id, email, role FROM users WHERE id = ?",
            [$pendingUserId]
        );

        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $this->completeLogin($user);
    }

    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $pageTitle = 'Mot de passe oublié';
            require __DIR__ . '/../Views/auth/forgot-password.php';
            return;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        if (empty($email)) {
            $this->withError('Email requis');
            $this->redirect('/forgot-password');
            return;
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, email FROM users WHERE email = ? AND active = 1",
            [$email]
        );

        $this->withSuccess('Si un compte existe, un email de réinitialisation a été envoyé.');
        $this->redirect('/login');

        if ($user) {
            $token = Security::createPasswordReset($user['id']);

            $mailer = new Mailer();
            $mailer->sendPasswordReset($user['email'], $user['email'], $token);
        }
    }

    public function resetPasswordForm(): void
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $this->redirect('/login');
            return;
        }

        $userId = Security::validatePasswordReset($token);
        if (!$userId) {
            $this->withError('Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/login');
            return;
        }

        $pageTitle = 'Nouveau mot de passe';
        require __DIR__ . '/../Views/auth/reset-password.php';
    }

    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (empty($token) || empty($password)) {
            $this->withError('Champs requis manquants.');
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        if ($password !== $confirm) {
            $this->withError('Les mots de passe ne correspondent pas.');
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        if (strlen($password) < 12) {
            $this->withError('Le mot de passe doit faire au moins 12 caractères.');
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        $userId = Security::validatePasswordReset($token);
        if (!$userId) {
            $this->withError('Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/login');
            return;
        }

        $db = Database::getInstance();
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $db->execute("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $userId]);
        Security::usePasswordReset($token);
        Security::destroyAllUserSessions($userId);

        $this->withSuccess('Mot de passe réinitialisé. Connectez-vous.');
        $this->redirect('/login');
    }

    private function logActivity(int $userId, int $tenantId, string $action): void
    {
        try {
            $db = Database::getInstance();
            $db->insert('audit_logs', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);
        } catch (\Exception $e) {
            // Silencieux
        }
    }
}
