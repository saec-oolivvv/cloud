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

        Security::recordLoginAttempt($email, $ip, true);
        $this->completeLogin($user);
    }

    private function completeLogin(array $user): void
    {
        Session::start();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

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
            "UPDATE users SET last_login = NOW(), last_login_ip = ? WHERE id = ?",
            [$ip, $userData['id']]
        );

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

    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
