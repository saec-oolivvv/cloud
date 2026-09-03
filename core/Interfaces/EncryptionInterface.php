<?php

declare(strict_types=1);

namespace Saec\Core\Interfaces;

interface EncryptionInterface
{
    public function encrypt(string $plaintext): string;
    public function decrypt(string $encoded): string;
    public function encryptFile(string $inputPath, string $outputPath): array;
    public function decryptFile(string $inputPath, string $keyBase64): string;
    public function generateKey(): string;
    public static function hashPassword(string $password): string;
    public static function verifyPassword(string $password, string $hash): bool;
}
