<?php
/**
 * SAEC Cloud — Migration Storage Tables
 * Exécute la migration 003_cloud_integration.sql
 * Usage: https://cloud.saec.me/migrate-storage.php
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
echo "═══ SAEC Cloud — Migration Storage ═══\n\n";

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("USE {$config['db']['name']}");
    echo "[OK] Connexion MySQL\n\n";

    // Lire le fichier de migration
    $migrationFile = __DIR__ . '/../database/migrations/003_cloud_integration.sql';
    if (!file_exists($migrationFile)) {
        throw new \RuntimeException("Fichier de migration non trouvé: {$migrationFile}");
    }

    $sql = file_get_contents($migrationFile);
    
    // Séparer les requêtes (en gérant les comments et lignes vides)
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($q) => !empty($q) && !str_starts_with($q, '--')
    );

    $executed = 0;
    $skipped = 0;

    foreach ($queries as $query) {
        // Ignorer les commentaires
        $query = trim($query);
        if (empty($query) || str_starts_with($query, '--') || str_starts_with($query, '//')) {
            $skipped++;
            continue;
        }

        try {
            $pdo->exec($query);
            $executed++;
            echo "[OK] " . substr($query, 0, 80) . "...\n";
        } catch (PDOException $e) {
            // Si la table existe déjà, on continue
            if (str_contains($e->getMessage(), 'already exists')) {
                $skipped++;
                echo "[=] Table déjà existante\n";
            } else {
                throw $e;
            }
        }
    }

    echo "\n═══ Migration terminée ═══\n";
    echo "Exécutées: {$executed} | Ignorées: {$skipped}\n";
    echo "</pre>";

} catch (\Throwable $e) {
    echo "\n[ERREUR] " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
