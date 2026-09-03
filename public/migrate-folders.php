<?php
/**
 * SAEC Cloud — Migration: Tenant Isolation + Folders
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
echo "═══ SAEC Cloud — Migration: Tenant Isolation + Folders ═══\n\n";

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("USE {$config['db']['name']}");
    echo "[OK] Connexion MySQL\n\n";

    // ── 1. Ajouter folder_id à files ──
    $cols = $pdo->query("DESCRIBE files")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('folder_id', $cols)) {
        $pdo->exec("ALTER TABLE files ADD COLUMN folder_id INT UNSIGNED NULL AFTER user_id");
        $pdo->exec("ALTER TABLE files ADD INDEX idx_folder (folder_id)");
        echo "[+] Colonne 'folder_id' ajoutée à files\n";
    } else {
        echo "[=] Colonne 'folder_id' existe déjà\n";
    }

    // ── 2. Fixer la table folders ──
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('folders', $tables)) {
        $fCols = $pdo->query("DESCRIBE folders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('color', $fCols)) {
            $pdo->exec("ALTER TABLE folders ADD COLUMN color VARCHAR(7) NULL DEFAULT '#00ff88' AFTER path");
            echo "[+] Colonne 'color' ajoutée à folders\n";
        }
    } else {
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
        echo "[+] Table 'folders' créée\n";
    }

    // ── 3. Créer dossiers racine pour chaque tenant existant ──
    $tenants = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tenants as $tid) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM folders WHERE tenant_id = ? AND parent_id IS NULL");
        $check->execute([$tid]);
        if ($check->fetchColumn() == 0) {
            $admin = $pdo->prepare("SELECT id FROM users WHERE tenant_id = ? AND role = 'admin' LIMIT 1");
            $admin->execute([$tid]);
            $adminId = $admin->fetchColumn() ?: 1;
            $ins = $pdo->prepare("INSERT INTO folders (tenant_id, user_id, name, path) VALUES (?, ?, '/', '/')");
            $ins->execute([$tid, $adminId]);
            echo "[+] Dossier racine créé pour tenant #{$tid}\n";
        }
    }

    // ── 4. Créer répertoires physiques ──
    $uploadBase = dirname(__DIR__, 1) . '/storage/uploads';
    foreach ($tenants as $tid) {
        $dir = $uploadBase . '/' . $tid;
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
            echo "[+] Répertoire créé: storage/uploads/{$tid}/\n";
        }
    }

    echo "\n═══ Migration terminée ═══\n";
    echo "Ce fichier va se supprimer automatiquement.\n";
    echo "</pre>";

    unlink(__FILE__);
    exit(0);

} catch (PDOException $e) {
    echo "\n[ERREUR] " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
