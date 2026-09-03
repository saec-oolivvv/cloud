<?php
/**
 * SAEC Cloud — Migration Sécurité
 * Tokens 64 bits, rate limiting, IP ban, session tracking
 * Auto-destruct après succès.
 * Usage: https://cloud.saec.me/migrate-security.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) die('Config non trouvée');

$config = json_decode(file_get_contents($configFiles[0]), true);
$dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};charset={$config['db']['charset']}";

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;'>";
echo "═══ SAEC Cloud — Migration Sécurité ═══\n\n";

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("USE {$config['db']['name']}");
    echo "[OK] Connexion MySQL\n\n";

    // ── 1. API Tokens (64-bit) ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS api_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            token_prefix VARCHAR(8) NOT NULL,
            name VARCHAR(100) NOT NULL DEFAULT 'API Token',
            scopes JSON NULL,
            last_used_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token_hash (token_hash),
            INDEX idx_user_active (user_id, is_active)
        ) ENGINE=InnoDB
    ");
    echo "[+] Table 'api_tokens' créée\n";

    // ── 2. Login attempts (rate limiting) ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            success TINYINT(1) DEFAULT 0,
            failure_reason VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_time (email, created_at),
            INDEX idx_ip_time (ip_address, created_at)
        ) ENGINE=InnoDB
    ");
    echo "[+] Table 'login_attempts' créée\n";

    // ── 3. IP Blacklist ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ip_blacklist (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            reason VARCHAR(255) NOT NULL,
            banned_by INT UNSIGNED NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_ip (ip_address)
        ) ENGINE=InnoDB
    ");
    echo "[+] Table 'ip_blacklist' créée\n";

    // ── 4. Active sessions ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id VARCHAR(64) PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            payload TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_last_activity (last_activity)
        ) ENGINE=InnoDB
    ");
    echo "[+] Table 'user_sessions' créée\n";

    // ── 5. Password reset tokens ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at TIMESTAMP NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_user_time (user_id, created_at)
        ) ENGINE=InnoDB
    ");
    echo "[+] Table 'password_resets' créée\n";

    // ── 6. Colonne 2FA dans users ──
    $userCols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('two_factor_secret', $userCols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL AFTER role");
        $pdo->exec("ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0 AFTER two_factor_secret");
        echo "[+] Colonnes 2FA ajoutées à 'users'\n";
    } else {
        echo "[=] Colonnes 2FA existent déjà\n";
    }

    // ── 7. Colonne last_login_ip dans users ──
    if (!in_array('last_login_ip', $userCols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(45) NULL AFTER last_login");
        echo "[+] Colonne 'last_login_ip' ajoutée\n";
    }

    echo "\n═══ Migration sécurité terminée ═══\n";
    echo "Ce fichier va se supprimer automatiquement.\n";
    echo "</pre>";

    unlink(__FILE__);
    exit(0);

} catch (PDOException $e) {
    echo "\n[ERREUR] " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
