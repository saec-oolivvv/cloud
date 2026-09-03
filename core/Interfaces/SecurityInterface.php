<?php

declare(strict_types=1);

namespace Saec\Core\Interfaces;

interface SecurityInterface
{
    // Tokens
    public function generateToken(int $length = 32): string;
    public function hashToken(string $token): string;

    // Rate limiting
    public function checkRateLimit(string $key, int $maxAttempts = 60, int $windowSeconds = 60): bool;
    public function recordAttempt(string $key, bool $success): void;
    public function getFailures(string $key, int $minutes = 15): int;
    public function isLockedOut(string $key, int $maxAttempts = 5, int $lockoutMinutes = 15): bool;

    // IP blacklist
    public function isIPBlacklisted(string $ip): bool;
    public function blacklistIP(string $ip, string $reason, ?int $expiresInMinutes = null): void;

    // CSRF
    public function generateCSRFToken(): string;
    public function verifyCSRFToken(string $token): bool;

    // Sanitize
    public function sanitize(string $input): string;
    public function sanitizeArray(array $input): array;
}
