<?php

declare(strict_types=1);

namespace Saec\Core;

class JWTHandler
{
    private static string $secret = '';
    private static int $expiry = 900;
    private static int $refreshExpiry = 604800;

    public static function init(string $secret = ''): void
    {
        self::$secret = $secret ?: bin2hex(random_bytes(32));
    }

    public static function generate(int $userId, int $tenantId, string $role): string
    {
        $now = time();
        $payload = [
            'iss' => 'saec.cloud',
            'sub' => $userId,
            'tenant_id' => $tenantId,
            'role' => $role,
            'iat' => $now,
            'exp' => $now + self::$expiry,
            'jti' => bin2hex(random_bytes(16)),
        ];

        return self::encode($payload);
    }

    public static function generateRefresh(int $userId, int $tenantId): string
    {
        $now = time();
        $payload = [
            'iss' => 'saec.cloud',
            'sub' => $userId,
            'tenant_id' => $tenantId,
            'type' => 'refresh',
            'iat' => $now,
            'exp' => $now + self::$refreshExpiry,
            'jti' => bin2hex(random_bytes(16)),
        ];

        return self::encode($payload);
    }

    public static function decode(string $token): ?object
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            [$header, $payload, $signature] = $parts;

            // Vérifier la signature
            $expectedSig = hash_hmac('sha256', "{$header}.{$payload}", self::$secret, true);
            $expectedSigB64 = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

            if (!hash_equals($expectedSigB64, $signature)) {
                return null;
            }

            // Décoder le payload
            $payloadData = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            if (!$payloadData) {
                return null;
            }

            // Vérifier l'expiration
            if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
                return null;
            }

            return (object) $payloadData;

        } catch (\Exception $e) {
            return null;
        }
    }

    public static function validate(string $token): bool
    {
        $payload = self::decode($token);
        return $payload !== null && isset($payload->sub);
    }

    public static function getUserId(string $token): ?int
    {
        $payload = self::decode($token);
        return $payload ? (int) $payload->sub : null;
    }

    public static function getTenantId(string $token): ?int
    {
        $payload = self::decode($token);
        return $payload ? (int) $payload->tenant_id : null;
    }

    public static function getRole(string $token): ?string
    {
        $payload = self::decode($token);
        return $payload ? $payload->role : null;
    }

    private static function encode(array $payload): string
    {
        $header = self::base64url(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadB64 = self::base64url(json_encode($payload));
        
        $signature = hash_hmac('sha256', "{$header}.{$payloadB64}", self::$secret, true);
        $signatureB64 = self::base64url($signature);

        return "{$header}.{$payloadB64}.{$signatureB64}";
    }

    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
