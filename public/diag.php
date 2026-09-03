<?php

/**
 * SAEC Cloud — Diagnostic
 * Accessible à : https://cloud.saec.me/diag.php
 * SUPPRIMER EN PRODUCTION
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<pre style='background: #0a0a0a; color: #00ff88; padding: 20px; font-family: monospace;'>";
echo "SAEC CLOUD — DIAGNOSTIC\n";
echo "══════════════════════════════════════════\n\n";

// PHP
echo "1. PHP VERSION\n";
echo "   " . PHP_VERSION . "\n\n";

// Extensions
echo "2. EXTENSIONS\n";
$required = ['pdo', 'pdo_mysql', 'openssl', 'json', 'mbstring', 'fileinfo'];
foreach ($required as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    echo "   {$status} {$ext}\n";
}
echo "\n";

// Config
echo "3. CONFIGURATION\n";
$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) {
    echo "   ✗ Aucun fichier de configuration trouvé\n";
    echo "   → Exécutez deploy.php\n";
} else {
    $configFile = $configFiles[0];
    echo "   ✓ Config: " . basename($configFile) . "\n";
    
    $config = json_decode(file_get_contents($configFile), true);
    if (!$config) {
        echo "   ✗ Config invalide (JSON)\n";
    } else {
        echo "   ✓ DB Host: " . ($config['db']['host'] ?? 'N/A') . "\n";
        echo "   ✓ DB Name: " . ($config['db']['name'] ?? 'N/A') . "\n";
        echo "   ✓ DB User: " . ($config['db']['user'] ?? 'N/A') . "\n";
        
        // Test connexion
        echo "\n4. CONNEXION MySQL\n";
        try {
            $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']}";
            $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            echo "   ✓ Connecté à MySQL\n";
            
            // Vérifier base
            $pdo->exec("USE {$config['db']['name']}");
            echo "   ✓ Base '{$config['db']['name']}' accessible\n";
            
            // Vérifier tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "   ✓ Tables: " . implode(', ', $tables) . "\n";
            
        } catch (PDOException $e) {
            echo "   ✗ Erreur MySQL: " . $e->getMessage() . "\n";
        }
    }
}

// Répertoires
echo "\n5. RÉPERTOIRES\n";
$dirs = [
    'storage' => 'Storage',
    'storage/uploads' => 'Uploads',
    'storage/keys' => 'Keys',
    'storage/logs' => 'Logs',
];
foreach ($dirs as $path => $name) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $status = $writable ? '✓' : ($exists ? '⚠' : '✗');
    echo "   {$status} {$name} ({$path})" . ($writable ? '' : ' [NON-ÉCRITURE]') . "\n";
}

echo "\n══════════════════════════════════════════\n";
echo "FIN DU DIAGNOSTIC\n";
echo "</pre>";
