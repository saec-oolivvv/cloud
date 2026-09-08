<?php

declare(strict_types=1);

namespace Saec\Core;

class Security
{
    private static int $maxLoginAttempts = 5;
    private static int $lockoutMinutes = 15;
    private static int $rateLimitWindow = 60;
    private static int $maxRequestsPerMinute = 60;

    // ── Tokens 64 bits ──
    public static function generateToken64(): string
    {
        $bytes = random_bytes(8);
        return bin2hex($bytes);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function createApiToken(int $userId, string $name, ?int $expiresInDays = null): array
    {
        $db = Database::getInstance();
        $token = self::generateToken64();
        $tokenHash = self::hashToken($token);
        $prefix = substr($token, 0, 8);

        $expiresAt = null;
        if ($expiresInDays) {
            $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInDays * 86400));
        }

        $db->execute(
            "INSERT INTO api_tokens (user_id, token_hash, token_prefix, name, expires_at) 
             VALUES (?, ?, ?, ?, ?)",
            [$userId, $tokenHash, $prefix, $name, $expiresAt]
        );

        return [
            'token' => $token,
            'prefix' => $prefix,
            'expires_at' => $expiresAt,
        ];
    }

    public static function validateApiToken(string $token): ?array
    {
        $db = Database::getInstance();
        $tokenHash = self::hashToken($token);

        $row = $db->fetch(
            "SELECT t.id, t.user_id, t.name, t.scopes, t.expires_at,
                    u.email, u.role, u.tenant_id, u.active
             FROM api_tokens t
             JOIN users u ON t.user_id = u.id
             WHERE t.token_hash = ? AND t.is_active = 1",
            [$tokenHash]
        );

        if (!$row) return null;
        if (!$row['active']) return null;
        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) return null;

        $db->execute(
            "UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?",
            [$row['id']]
        );

        return $row;
    }

    // ── Rate Limiting ──
    public static function checkRateLimit(string $key): bool
    {
        $db = Database::getInstance();

        $count = $db->fetch(
            "SELECT COUNT(*) as cnt FROM login_attempts 
             WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$key, self::$rateLimitWindow]
        );

        return ($count['cnt'] ?? 0) < self::$maxRequestsPerMinute;
    }

    // ── Login Attempts ──
    public static function recordLoginAttempt(string $email, string $ip, bool $success, ?string $reason = null): void
    {
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO login_attempts (email, ip_address, success, failure_reason) VALUES (?, ?, ?, ?)",
            [$email, $ip, $success ? 1 : 0, $reason]
        );
    }

    public static function getLoginFailures(string $email, int $minutes = 15): int
    {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT COUNT(*) as cnt FROM login_attempts 
             WHERE email = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$email, $minutes]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public static function isLockedOut(string $email): bool
    {
        return self::getLoginFailures($email, self::$lockoutMinutes) >= self::$maxLoginAttempts;
    }

    // ── IP Blacklist ──
    public static function isIPBlacklisted(string $ip): bool
    {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT id FROM ip_blacklist 
             WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())",
            [$ip]
        );
        return (bool) $row;
    }

    public static function blacklistIP(string $ip, string $reason, ?int $bannedBy = null, ?int $expiresInMinutes = null): void
    {
        $db = Database::getInstance();
        $expiresAt = $expiresInMinutes ? date('Y-m-d H:i:s', time() + ($expiresInMinutes * 60)) : null;

        $db->execute(
            "INSERT INTO ip_blacklist (ip_address, reason, banned_by, expires_at) 
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE reason=VALUES(reason), expires_at=VALUES(expires_at)",
            [$ip, $reason, $bannedBy, $expiresAt]
        );
    }

    // ── Session Management ──
    public static function createSession(int $userId, string $ip, string $userAgent): string
    {
        $db = Database::getInstance();
        $sessionId = bin2hex(random_bytes(32));

        $db->execute(
            "INSERT INTO user_sessions (id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)",
            [$sessionId, $userId, $ip, $userAgent]
        );

        return $sessionId;
    }

    public static function validateSession(string $sessionId, string $ip): bool
    {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT id FROM user_sessions 
             WHERE id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
            [$sessionId]
        );

        if (!$row) return false;

        $db->execute(
            "UPDATE user_sessions SET last_activity = NOW(), ip_address = ? WHERE id = ?",
            [$ip, $sessionId]
        );

        return true;
    }

    public static function destroySession(string $sessionId): void
    {
        $db = Database::getInstance();
        $db->execute("DELETE FROM user_sessions WHERE id = ?", [$sessionId]);
    }

    public static function destroyAllUserSessions(int $userId): void
    {
        $db = Database::getInstance();
        $db->execute("DELETE FROM user_sessions WHERE user_id = ?", [$userId]);
    }

    // ── Password Reset ──
    public static function createPasswordReset(int $userId): string
    {
        $db = Database::getInstance();
        $token = self::generateToken64();
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $db->execute(
            "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$userId, $token, $expiresAt]
        );

        return $token;
    }

    public static function validatePasswordReset(string $token): ?int
    {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT user_id FROM password_resets 
             WHERE token = ? AND used = 0 AND expires_at > NOW()",
            [$token]
        );
        return $row ? (int) $row['user_id'] : null;
    }

    public static function usePasswordReset(string $token): void
    {
        $db = Database::getInstance();
        $db->execute("UPDATE password_resets SET used = 1 WHERE token = ?", [$token]);
    }

    // ── Email Verification ──
    public static function createEmailVerification(int $userId): string
    {
        $db = Database::getInstance();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24h

        $db->execute(
            "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$userId, $token, $expiresAt]
        );

        return $token;
    }

    public static function validateEmailVerification(string $token): ?int
    {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT user_id FROM email_verifications 
             WHERE token = ? AND used = 0 AND expires_at > NOW()",
            [$token]
        );
        return $row ? (int) $row['user_id'] : null;
    }

    public static function verifyEmail(int $userId): void
    {
        $db = Database::getInstance();
        $db->execute("UPDATE users SET email_verified_at = NOW() WHERE id = ?", [$userId]);
        $db->execute("UPDATE email_verifications SET used = 1 WHERE user_id = ?", [$userId]);
    }

    public static function isEmailVerified(int $userId): bool
    {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT email_verified_at FROM users WHERE id = ?",
            [$userId]
        );
        return $row && !empty($row['email_verified_at']);
    }

    // ── Sanitize ──
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function generateCSRFToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = self::generateToken64();
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public static function verifyCSRFToken(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
