<?php
/**
 * SAEC Cloud — Fix file encryption
 * Ajoute file_key aux files + encryption_keys table
 * Auto-destruct après succès.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) die('Config non trouvée');

$config = json_decode(file_get_contents($configFiles[0]), true);
$dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};charset={$config['db']['charset']}";

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;'>";
echo "═══ SAEC Cloud — Fix File Encryption ═══\n\n";

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("USE {$config['db']['name']}");
    echo "[OK] Connexion MySQL\n\n";

    // Ajouter file_key à files
    $cols = $pdo->query("DESCRIBE files")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('file_key', $cols)) {
        $pdo->exec("ALTER TABLE files ADD COLUMN file_key TEXT NULL AFTER checksum");
        echo "[+] Colonne 'file_key' ajoutée à files\n";
    } else {
        echo "[=] Colonne 'file_key' existe déjà\n";
    }

    // Créer encryption_keys si pas présent
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('encryption_keys', $tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS encryption_keys (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT UNSIGNED NOT NULL,
                key_data TEXT NOT NULL,
                version INT DEFAULT 1,
                active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                rotated_at TIMESTAMP NULL,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        echo "[+] Table 'encryption_keys' créée\n";
    } else {
        echo "[=] Table 'encryption_keys' existe déjà\n";
    }

    echo "\n═══ Fix terminé ═══\n";
    echo "Ce fichier va se supprimer automatiquement.\n";
    echo "</pre>";

    unlink(__FILE__);
    exit(0);

} catch (PDOException $e) {
    echo "\n[ERREUR] " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
