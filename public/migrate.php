<?php
/**
 * SAEC Cloud — Migration DB
 * S'exécute une fois puis se auto-détruit.
 * Usage: https://cloud.saec.me/migrate.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Chargement config
$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) {
    die('Config non trouvée');
}

$config = json_decode(file_get_contents($configFiles[0]), true);
$dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};charset={$config['db']['charset']}";

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;'>";
echo "═══ SAEC Cloud — Migration DB ═══\n\n";

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("USE {$config['db']['name']}");
    echo "[OK] Connexion MySQL\n\n";

    // ── Migration 1: Colonnes manquantes dans files ──
    $cols = $pdo->query("DESCRIBE files")->fetchAll(PDO::FETCH_COLUMN);
    
    $fileCols = [
        'deleted_at'      => "ALTER TABLE files ADD COLUMN deleted_at TIMESTAMP NULL AFTER size",
        'folder_path'     => "ALTER TABLE files ADD COLUMN folder_path VARCHAR(500) DEFAULT '/' AFTER user_id",
        'stored_name'     => "ALTER TABLE files ADD COLUMN stored_name VARCHAR(255) NOT NULL DEFAULT '' AFTER original_name",
        'encryption_key_id' => "ALTER TABLE files ADD COLUMN encryption_key_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER checksum",
        'version'         => "ALTER TABLE files ADD COLUMN version INT DEFAULT 1 AFTER encryption_key_id",
        'updated_at'      => "ALTER TABLE files ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];
    
    echo "── Table: files ──\n";
    foreach ($fileCols as $col => $sql) {
        if (!in_array($col, $cols)) {
            $pdo->exec($sql);
            echo "[+] Colonne '{$col}' ajoutée\n";
        } else {
            echo "[=] Colonne '{$col}' existe déjà\n";
        }
    }

    // ── Migration 2: Tables manquantes ──
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $createTables = [
        'folders' => "
            CREATE TABLE IF NOT EXISTS folders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                name VARCHAR(255) NOT NULL,
                parent_id INT UNSIGNED NULL,
                path VARCHAR(500) NOT NULL DEFAULT '/',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
        
        'shares' => "
            CREATE TABLE IF NOT EXISTS shares (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                file_id INT UNSIGNED NULL,
                folder_id INT UNSIGNED NULL,
                name VARCHAR(255) NOT NULL,
                share_token VARCHAR(64) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NULL,
                max_downloads INT UNSIGNED NULL,
                download_count INT UNSIGNED DEFAULT 0,
                expires_at TIMESTAMP NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
        
        'file_versions' => "
            CREATE TABLE IF NOT EXISTS file_versions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                file_id INT UNSIGNED NOT NULL,
                version INT NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                size BIGINT UNSIGNED NOT NULL,
                checksum VARCHAR(64) NOT NULL,
                uploaded_by INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
        
        'notifications' => "
            CREATE TABLE IF NOT EXISTS notifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
    ];
    
    echo "\n── Tables ──\n";
    foreach ($createTables as $name => $sql) {
        if (!in_array($name, $tables)) {
            $pdo->exec($sql);
            echo "[+] Table '{$name}' créée\n";
        } else {
            echo "[=] Table '{$name}' existe déjà\n";
        }
    }

    echo "\n═══ Migration terminée avec succès ═══\n";
    echo "Ce fichier va se supprimer automatiquement.\n";
    echo "</pre>";

    // Auto-destruction
    unlink(__FILE__);
    exit(0);

} catch (PDOException $e) {
    echo "\n[ERREUR] " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
