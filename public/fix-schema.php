<?php
/**
 * SAEC Cloud — Schema Fix Runner
 * Exécuter une fois: https://cloud.saec.me/fix-schema.php
 * Auto-destruct après succès.
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) die('Config non trouvée');

$config = json_decode(file_get_contents($configFiles[0]), true);
$dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};charset={$config['db']['charset']}";

echo "<pre style='font-family:monospace;background:#0a0f1e;color:#00ff88;padding:20px;'>";

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("USE {$config['db']['name']}");
    echo "✅ MySQL OK\n\n";

    $sql = file_get_contents(__DIR__ . '/../sql/06-fix-schema-columns.sql');
    // Remove USE and comment lines
    $lines = explode("\n", $sql);
    $clean = [];
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, 'USE ')) continue;
        $clean[] = $line;
    }
    $fullSql = implode("\n", $clean);

    // Execute each statement
    $statements = array_filter(array_map('trim', explode(';', $fullSql)));
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
            echo "  ✅ " . substr($stmt, 0, 60) . "...\n";
        } catch (PDOException $e) {
            echo "  ⚠️  " . substr($stmt, 0, 40) . "... → " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ Schema fix terminé\n";
    echo "Ce fichier va se supprimer.\n</pre>";
    unlink(__FILE__);
} catch (PDOException $e) {
    echo "❌ " . $e->getMessage() . "\n</pre>";
}
