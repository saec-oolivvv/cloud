<?php
/**
 * SAEC Cloud — Schema Migration (one-shot, self-destruct)
 *
 * Usage: php public/migrate-schema.php
 * DELETE after running: rm public/migrate-schema.php
 */

declare(strict_types=1);

// Disable browser access — CLI only
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only. Delete this file.');
}

$basePath = dirname(__DIR__);
if (!file_exists($basePath . '/core/Database.php')) {
    // Attempt from public/ context
    $basePath = dirname($basePath);
}
require_once $basePath . '/core/Database.php';

use Saec\Core\Database;

echo "=== SAEC Cloud Schema Migration ===\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();

    $migrations = [
        // ── files ──
        'files.folder_id'       => "ALTER TABLE files ADD COLUMN folder_id INT UNSIGNED NULL AFTER user_id",
        'files.file_key'        => "ALTER TABLE files ADD COLUMN file_key TEXT NULL AFTER checksum",
        'files.deleted_at'      => "ALTER TABLE files ADD COLUMN deleted_at TIMESTAMP NULL AFTER size",
        'files.updated_at'      => "ALTER TABLE files ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        'files.version'         => "ALTER TABLE files ADD COLUMN version INT DEFAULT 1 AFTER checksum",

        // ── audit_logs ──
        'audit_logs.resource_type' => "ALTER TABLE audit_logs ADD COLUMN resource_type VARCHAR(50) NULL AFTER action",
        'audit_logs.resource_id'   => "ALTER TABLE audit_logs ADD COLUMN resource_id INT UNSIGNED NULL AFTER resource_type",
        'audit_logs.user_agent'    => "ALTER TABLE audit_logs ADD COLUMN user_agent TEXT NULL AFTER ip_address",
        'audit_logs.metadata'      => "ALTER TABLE audit_logs ADD COLUMN metadata JSON NULL AFTER user_agent",

        // ── users ──
        'users.email_verified_at'         => "ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL AFTER active",
        'users.email_verification_token'  => "ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at",
        'users.last_login'                => "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER active",
        'users.last_login_ip'             => "ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(45) NULL AFTER last_login",
        'users.last_active_at'            => "ALTER TABLE users ADD COLUMN last_active_at TIMESTAMP NULL AFTER last_login_ip",
    ];

    $added = 0;
    $skipped = 0;

    foreach ($migrations as $col => $sql) {
        [$table, $column] = explode('.', $col);
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $check->execute([$table, $column]);
        if ($check->fetchColumn() > 0) {
            echo "  [skip] {$col} already exists\n";
            $skipped++;
            continue;
        }
        echo "  [add]  {$col}... ";
        $pdo->exec($sql);
        echo "OK\n";
        $added++;
    }

    // ── Tables ──
    $tables = [
        "CREATE TABLE IF NOT EXISTS email_verifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_token (token),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_token (token),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            success TINYINT(1) DEFAULT 0,
            failure_reason VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_ip (ip_address),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS trusted_devices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            device_fingerprint VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    echo "  [ok]  Tables ensured (email_verifications, password_reset_tokens, login_attempts, trusted_devices)\n";

    // ── Mark existing users as verified ──
    $stmt = $pdo->exec("UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL AND active = 1");
    echo "  [ok]  Existing active users marked as email-verified\n";

    echo "\n=== Done: {$added} columns added, {$skipped} skipped ===\n";

    // ── Self-destruct ──
    echo "\nSelf-destructing...\n";
    unlink(__FILE__);
    echo "Deleted: " . __FILE__ . "\n";

} catch (\Throwable $e) {
    echo "\nERROR: {$e->getMessage()}\n";
    echo "File kept for debugging. Delete manually: rm " . __FILE__ . "\n";
    exit(1);
}
