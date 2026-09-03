<?php
/**
 * SAEC Cloud — Migration Complète: Folders + Tenant Isolation
 * TOUT-en-un. Auto-destruct après succès.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) die('Config non trouvée');

$config = json_decode(file_get_contents($configFiles[0]), true);
$dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};charset={$config['db']['charset']}";

echo "<pre style='font-family:monospace;background:#0a0f1e;color:#00ff88;padding:20px;border:1px solid #00ff88;'>";
echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║  SAEC CLOUD — MIGRATION COMPLÈTE                     ║\n";
echo "║  Folders + Tenant Isolation + Security               ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("USE {$config['db']['name']}");
    echo "✅ Connexion MySQL OK\n\n";

    // ════════════════════════════════════════
    // 1. TABLE FOLDERS
    // ════════════════════════════════════════
    echo "── TABLES ──\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('folders', $tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS folders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                parent_id INT UNSIGNED NULL,
                name VARCHAR(255) NOT NULL,
                path VARCHAR(500) NOT NULL DEFAULT '/',
                color VARCHAR(7) NULL DEFAULT '#00ff88',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_tenant_parent (tenant_id, parent_id),
                INDEX idx_path (tenant_id, path)
            ) ENGINE=InnoDB
        ");
        echo "  [+] folders créée\n";
    } else {
        echo "  [=] folders existe\n";
    }

    // ════════════════════════════════════════
    // 2. COLONNES MANQUANTES
    // ════════════════════════════════════════
    echo "\n── COLONNES ──\n";

    // files.folder_id
    $fileCols = $pdo->query("DESCRIBE files")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('folder_id', $fileCols)) {
        $pdo->exec("ALTER TABLE files ADD COLUMN folder_id INT UNSIGNED NULL AFTER user_id");
        $pdo->exec("ALTER TABLE files ADD INDEX idx_folder (folder_id)");
        echo "  [+] files.folder_id\n";
    }

    if (!in_array('file_key', $fileCols)) {
        $pdo->exec("ALTER TABLE files ADD COLUMN file_key TEXT NULL AFTER checksum");
        echo "  [+] files.file_key\n";
    }

    if (!in_array('deleted_at', $fileCols)) {
        $pdo->exec("ALTER TABLE files ADD COLUMN deleted_at TIMESTAMP NULL AFTER size");
        echo "  [+] files.deleted_at\n";
    }

    if (!in_array('updated_at', $fileCols)) {
        $pdo->exec("ALTER TABLE files ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "  [+] files.updated_at\n";
    }

    // users manquantes
    $userCols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    $userMissing = [
        'last_login' => "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER active",
        'last_login_ip' => "ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(45) NULL AFTER last_login",
        'two_factor_secret' => "ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL AFTER role",
        'two_factor_enabled' => "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0 AFTER two_factor_secret",
    ];
    foreach ($userMissing as $col => $sql) {
        if (!in_array($col, $userCols)) {
            $pdo->exec($sql);
            echo "  [+] users.{$col}\n";
        }
    }

    // ════════════════════════════════════════
    // 3. TABLES SÉCURITÉ
    // ════════════════════════════════════════
    echo "\n── SÉCURITÉ ──\n";

    $securityTables = [
        'api_tokens' => "CREATE TABLE IF NOT EXISTS api_tokens (
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
        ) ENGINE=InnoDB",
        'login_attempts' => "CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            success TINYINT(1) DEFAULT 0,
            failure_reason VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_time (email, created_at),
            INDEX idx_ip_time (ip_address, created_at)
        ) ENGINE=InnoDB",
        'ip_blacklist' => "CREATE TABLE IF NOT EXISTS ip_blacklist (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            reason VARCHAR(255) NOT NULL,
            banned_by INT UNSIGNED NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_ip (ip_address)
        ) ENGINE=InnoDB",
        'user_sessions' => "CREATE TABLE IF NOT EXISTS user_sessions (
            id VARCHAR(64) PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_last_activity (last_activity)
        ) ENGINE=InnoDB",
        'password_resets' => "CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at TIMESTAMP NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token)
        ) ENGINE=InnoDB",
        'encryption_keys' => "CREATE TABLE IF NOT EXISTS encryption_keys (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            key_data TEXT NOT NULL,
            version INT DEFAULT 1,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            rotated_at TIMESTAMP NULL,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
    ];

    foreach ($securityTables as $name => $sql) {
        if (!in_array($name, $tables)) {
            $pdo->exec($sql);
            echo "  [+] {$name}\n";
        }
    }

    // ════════════════════════════════════════
    // 4. RÉPERTOIRES PHYSIQUES
    // ════════════════════════════════════════
    echo "\n── RÉPERTOIRES ──\n";
    $uploadBase = dirname(__DIR__, 1) . '/storage/uploads';
    $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tenants as $tid) {
        $dir = $uploadBase . '/' . $tid;
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
            echo "  [+] uploads/{$tid}/\n";
        }
    }

    // Dossier racine pour chaque tenant
    foreach ($tenants as $tid) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM folders WHERE tenant_id = ? AND parent_id IS NULL");
        $check->execute([$tid]);
        if ($check->fetchColumn() == 0) {
            $admin = $pdo->prepare("SELECT id FROM users WHERE tenant_id = ? AND role = 'admin' LIMIT 1");
            $admin->execute([$tid]);
            $adminId = $admin->fetchColumn() ?: 1;
            $ins = $pdo->prepare("INSERT INTO folders (tenant_id, user_id, name, path) VALUES (?, ?, '/', '/')");
            $ins->execute([$tid, $adminId]);
            echo "  [+] Dossier racine pour tenant #{$tid}\n";
        }
    }

    // ════════════════════════════════════════
    // 5. PERMISSIONS
    // ════════════════════════════════════════
    echo "\n── PERMISSIONS ──\n";
    chmod($uploadBase, 0770);
    echo "  [+] uploads/ → 770\n";

    // Permissions config file
    foreach (glob(dirname(__DIR__, 1) . '/storage/x*.conf') as $f) {
        chmod($f, 0644);
    }
    echo "  [+] config files → 644\n";

    echo "\n╔═══════════════════════════════════════════════════════╗\n";
    echo "║  ✅ MIGRATION COMPLÈTE TERMINÉE                      ║\n";
    echo "╚═══════════════════════════════════════════════════════╝\n";
    echo "Ce fichier va se supprimer automatiquement.\n";
    echo "</pre>";

    unlink(__FILE__);
    exit(0);

} catch (PDOException $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
