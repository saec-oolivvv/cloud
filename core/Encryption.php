<?php

declare(strict_types=1);

namespace Saec\Core;

class Encryption
{
    private string $cipher = 'aes-256-gcm';
    private string $keyPath;

    public function __construct()
    {
        $config = $GLOBALS['SAEC_CONFIG']['security'] ?? [];
        $this->keyPath = $config['encryption_key_path'] ?? __DIR__ . '/../storage/keys/master.key';
    }

    public function encrypt(string $plaintext): string
    {
        $key = $this->getMasterKey();
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encoded): string
    {
        $key = $this->getMasterKey();
        $data = base64_decode($encoded, true);

        if ($data === false || strlen($data) < 28) {
            throw new \RuntimeException('Invalid encrypted data');
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $plaintext;
    }

    public function encryptFile(string $inputPath, string $outputPath): array
    {
        $plaintext = file_get_contents($inputPath);
        if ($plaintext === false) {
            throw new \RuntimeException("Cannot read file: {$inputPath}");
        }

        return $this->encryptFileFromContent($plaintext, $outputPath);
    }

    public function encryptFileFromContent(string $plaintext, string $outputPath): array
    {
        $key = $this->generateFileKey();
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('File encryption failed');
        }

        file_put_contents($outputPath, $iv . $tag . $ciphertext);

        return [
            'key' => base64_encode($key),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'checksum' => hash_file('sha256', $outputPath),
        ];
    }

    public function decryptFile(string $inputPath, string $keyBase64): string
    {
        $key = base64_decode($keyBase64, true);
        $data = file_get_contents($inputPath);

        if ($data === false || strlen($data) < 28) {
            throw new \RuntimeException('Invalid encrypted file');
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('File decryption failed');
        }

        return $plaintext;
    }

    public function generateFileKey(): string
    {
        return random_bytes(32);
    }

    private function getMasterKey(): string
    {
        $dir = dirname($this->keyPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        if (!file_exists($this->keyPath)) {
            $key = random_bytes(32);
            file_put_contents($this->keyPath, $key);
            chmod($this->keyPath, 0600);
        }

        $key = file_get_contents($this->keyPath);
        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException('Invalid master key');
        }

        return $key;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
