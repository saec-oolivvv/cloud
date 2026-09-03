<?php

/**
 * SAEC Cloud — Health Check
 * Vérifie l'état du système
 */

header('Content-Type: application/json');

$checks = [];
$healthy = true;

// PHP
$checks['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION,
];

// Extensions
$required = ['pdo', 'pdo_mysql', 'openssl', 'json'];
foreach ($required as $ext) {
    $checks['ext_' . $ext] = [
        'status' => extension_loaded($ext) ? 'ok' : 'error',
    ];
    if (!extension_loaded($ext)) $healthy = false;
}

// Répertoires
$dirs = ['storage', 'storage/uploads', 'storage/keys'];
foreach ($dirs as $dir) {
    $checks['dir_' . basename($dir)] = [
        'status' => is_dir($dir) && is_writable($dir) ? 'ok' : 'error',
    ];
    if (!is_dir($dir) || !is_writable($dir)) $healthy = false;
}

// Base de données
$configFiles = glob('storage/x*.conf');
if (!empty($configFiles)) {
    $config = json_decode(file_get_contents($configFiles[0]), true);
    try {
        $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']}";
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $checks['database'] = ['status' => 'ok'];
        $pdo = null;
    } catch (PDOException $e) {
        $checks['database'] = ['status' => 'error', 'message' => 'Connection failed'];
        $healthy = false;
    }
} else {
    $checks['database'] = ['status' => 'error', 'message' => 'Config not found'];
    $healthy = false;
}

// Résultat
http_response_code($healthy ? 200 : 503);
echo json_encode([
    'status' => $healthy ? 'healthy' : 'unhealthy',
    'timestamp' => date('c'),
    'checks' => $checks,
], JSON_PRETTY_PRINT);
