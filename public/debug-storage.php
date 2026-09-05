<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '0');

$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) { die('Config non trouvée'); }

$config = json_decode(file_get_contents($configFiles[0]), true);
$dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};charset={$config['db']['charset']}";

echo "1. Config OK<br>";

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "2. Autoload OK<br>";
} catch (\Throwable $e) {
    die("2. Autoload FAILED: " . $e->getMessage());
}

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    $pdo->exec("USE {$config['db']['name']}");
    echo "3. DB OK<br>";
} catch (\Throwable $e) {
    die("3. DB FAILED: " . $e->getMessage());
}

// Test StorageService
try {
    require_once __DIR__ . '/../app/Services/StorageService.php';
    require_once __DIR__ . '/../app/Services/Storage/StorageAdapter.php';
    require_once __DIR__ . '/../app/Services/Storage/AbstractAdapter.php';
    require_once __DIR__ . '/../app/Services/Storage/AdapterFactory.php';
    echo "4. Storage files loaded OK<br>";
} catch (\Throwable $e) {
    die("4. Storage files FAILED: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

// Test class instantiation
try {
    $reflection = new ReflectionClass('Saec\Services\StorageService');
    echo "5. StorageService class exists OK<br>";
} catch (\Throwable $e) {
    die("5. StorageService class FAILED: " . $e->getMessage());
}

// Test listProviders query directly
try {
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM storage_providers WHERE deleted_at IS NULL")->fetch();
    echo "6. Query OK: " . $result['cnt'] . " providers<br>";
} catch (\Throwable $e) {
    die("6. Query FAILED: " . $e->getMessage());
}

echo "<br><b>ALL OK - The problem is likely OPcache. Run: touch " . __DIR__ . "/../app/Controllers/AdminStorageController.php</b>";
