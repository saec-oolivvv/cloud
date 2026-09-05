<?php

declare(strict_types=1);

namespace Saec\Core;

class Session
{
    private static int $REGENERATE_INTERVAL = 300; // 5 minutes

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 86400,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None',
            ]);
            session_start();

            // Regenerate session ID periodically to prevent fixation
            $lastRegen = self::get('_last_regenerate', 0);
            if (time() - $lastRegen > self::$REGENERATE_INTERVAL) {
                session_regenerate_id(true);
                self::set('_last_regenerate', time());
            }

            // Validate session fingerprint
            if (self::has('_fingerprint')) {
                $currentFingerprint = self::generateFingerprint();
                if (!hash_equals(self::get('_fingerprint', ''), $currentFingerprint)) {
                    // Session hijacked — destroy
                    self::destroy();
                    header('Location: /login');
                    exit;
                }
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
