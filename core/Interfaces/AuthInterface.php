<?php

declare(strict_types=1);

namespace Saec\Core\Interfaces;

interface AuthInterface
{
    public function login(string $email, string $password, string $ip, string $userAgent): ?array;
    public function logout(int $userId): void;
    public function getCurrentUser(): ?array;
    public function isLoggedIn(): bool;
    public function isAdmin(): bool;
    public function requireAuth(): array;
    public function requireAdmin(): array;
    public function createToken(int $userId, int $tenantId, string $role): string;
    public function validateToken(string $token): ?array;
    public function refreshToken(): ?string;
}
