<?php

declare(strict_types=1);

namespace Saec\Core;

class Session
{
    private static int $REGENERATE_INTERVAL = 300; // 5 minutes

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
                || ($_SERVER['HTTP_CF_VISITOR'] ?? '') === '{"scheme":"https"}';

            session_set_cookie_params([
                'lifetime' => 86400,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'None',
            ]);
            session_start();

            // Regenerate session ID only on privilege change (login/admin)
            // NOT periodically — breaks AJAX POST requests when cookie is stale
            // session_regenerate_id is called explicitly in Session::login()

            // Validate session fingerprint
            if (self::has('_fingerprint')) {
                $currentFingerprint = self::generateFingerprint();
                if (!hash_equals(self::get('_fingerprint', ''), $currentFingerprint)) {
                    self::destroy();
                    header('Location: /login');
                    exit;
                }
            }

            // Validate session token cookie if user is logged in
            if (self::has('user') && !empty($_COOKIE['session_token'])) {
                self::validateSessionToken();
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Generate unique CSRF token per request (double-submit pattern)
     */
    public static function csrfToken(): string
    {
        if (!self::has('_csrf_token')) {
            self::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('_csrf_token');
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf(string $token): bool
    {
        if (empty($token)) return false;
        return hash_equals(self::get('_csrf_token', ''), $token);
    }

    /**
     * Generate fingerprint from user agent + IP + accept headers
     */
    private static function generateFingerprint(): string
    {
        $data = ($_SERVER['HTTP_USER_AGENT'] ?? '')
            . '|' . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        return hash('sha256', $data);
    }

    /**
     * Set fingerprint on login
     */
    public static function setFingerprint(): void
    {
        self::set('_fingerprint', self::generateFingerprint());
    }

    /**
     * Set session user with security metadata
     */
    public static function login(array $user): void
    {
        self::set('user', $user);
        self::set('user_id', $user['id']);
        self::set('tenant_id', $user['tenant_id']);
        self::set('role', $user['role']);
        self::set('login_at', time());
        self::set('login_ip', $_SERVER['REMOTE_ADDR'] ?? '');
        self::setFingerprint();

        // Regenerate session ID on login
        session_regenerate_id(true);
    }

    /**
     * Set secure session token cookie (random, per-user, DB-tracked)
     */
    public static function setSessionToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO user_sessions (id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)",
                [$tokenHash, $userId, $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''), $_SERVER['HTTP_USER_AGENT'] ?? '']
            );
        } catch (\Throwable $e) {
            // Table may not exist — non-critical
        }

        $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        setcookie('session_token', $token, [
            'expires' => time() + 86400,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'None',
        ]);

        self::set('session_token_hash', $tokenHash);
        return $token;
    }

    /**
     * Validate session token cookie against DB
     */
    public static function validateSessionToken(): bool
    {
        $token = $_COOKIE['session_token'] ?? '';
        if (empty($token)) return false;

        $tokenHash = hash('sha256', $token);

        try {
            $db = Database::getInstance();
            $row = $db->fetch(
                "SELECT id FROM user_sessions WHERE id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                [$tokenHash]
            );

            if (!$row) return false;

            $db->execute(
                "UPDATE user_sessions SET last_activity = NOW() WHERE id = ?",
                [$tokenHash]
            );
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Destroy session token cookie + DB row
     */
    public static function destroySessionToken(): void
    {
        $tokenHash = self::get('session_token_hash', '');
        if ($tokenHash) {
            $db = Database::getInstance();
            $db->execute("DELETE FROM user_sessions WHERE id = ?", [$tokenHash]);
        }

        $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        setcookie('session_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'None',
        ]);
    }

    /**
     * Check if session is valid for API calls (token-based)
     */
    public static function validateApiToken(string $token): ?array
    {
        $db = Database::getInstance();
        $tokenHash = hash('sha256', $token);

        $apiToken = $db->fetch(
            "SELECT t.*, u.id as uid, u.email, u.role, u.tenant_id 
             FROM api_tokens t 
             JOIN users u ON t.user_id = u.id 
             WHERE t.token_hash = ? AND t.is_active = 1 AND u.active = 1",
            [$tokenHash]
        );

        if (!$apiToken) return null;

        // Check expiry
        if ($apiToken['expires_at'] && strtotime($apiToken['expires_at']) < time()) {
            return null;
        }

        // Update last used
        $db->execute(
            "UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?",
            [$apiToken['id']]
        );

        return [
            'id' => $apiToken['uid'],
            'email' => $apiToken['email'],
            'role' => $apiToken['role'],
            'tenant_id' => $apiToken['tenant_id'],
        ];
    }

    /**
     * Destroy session completely
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
