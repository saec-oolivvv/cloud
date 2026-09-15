<?php

/**
 * SAEC Cloud — Script de Déploiement
 * Auto-destruit après succès
 */

// Masquer les erreurs en production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ══════════════════════════════════════════════════════════════
// FONCTIONS
// ══════════════════════════════════════════════════════════════

function output(string $text, string $color = 'white'): void {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'cyan' => "\033[36m",
        'white' => "\033[0m",
    ];
    echo ($colors[$color] ?? '') . $text . "\033[0m\n";
}

function success(string $text): void { output("  ✓ {$text}", 'green'); }
function error(string $text): void { output("  ✗ {$text}", 'red'); }
function info(string $text): void { output("  → {$text}", 'cyan'); }
function warning(string $text): void { output("  ⚠ {$text}", 'yellow'); }

// ══════════════════════════════════════════════════════════════
// DÉMARRAGE
// ══════════════════════════════════════════════════════════════

echo "\n";
output("  _____ _____ _____   _____ ____   ____", 'green');
output(" / ____/ ____|  __ \\ / ____/ __ \\ / ___|", 'green');
output("| (___| (___ | |__) | (___| |  | | |", 'green');
output(" \\___ \\\\___ \\|  _  / \\___ \\| |  | | |", 'green');
output(" ____) |___) | | \\ \\  ___) | |__| |___", 'green');
output("|_____/|____/|_|  \\_\\|____/ \\____/|_____|", 'green');
echo "\n";
output("  SAEC CLOUD — DEPLOY", 'cyan');
output("  ═══════════════════════════════════════", 'cyan');
echo "\n";

// ══════════════════════════════════════════════════════════════
// ÉTAPE 1 : VÉRIFICATIONS
// ══════════════════════════════════════════════════════════════

info("Vérifications préliminaires...");

// Vérifier PHP version
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    error("PHP 8.2+ requis. Version actuelle: " . PHP_VERSION);
    exit(1);
}
success("PHP " . PHP_VERSION);

// Vérifier les extensions
$required = ['pdo', 'pdo_mysql', 'openssl', 'json', 'mbstring'];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        error("Extension manquante: {$ext}");
        exit(1);
    }
}
success("Extensions PHP OK");

// Vérifier les répertoires
$baseDir = dirname(__DIR__);
$dirs = ['storage', 'storage/uploads', 'storage/keys', 'storage/logs'];
foreach ($dirs as $dir) {
    $fullPath = $baseDir . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0770, true);
        info("Répertoire créé: {$dir}");
    }
}
success("Répertoires OK");

// ══════════════════════════════════════════════════════════════
// ÉTAPE 2 : FICHIER DE CONFIGURATION
// ══════════════════════════════════════════════════════════════

info("Création du fichier de configuration...");

$storageDir = dirname(__DIR__) . '/storage';
if (!is_dir($storageDir)) mkdir($storageDir, 0770, true);
$configFile = $storageDir . '/x' . bin2hex(random_bytes(8)) . '.conf';

$config = [
    'app' => [
        'name' => 'SAEC Cloud',
        'env' => 'production',
        'url' => 'https://cloud.saec.me',
        'debug' => false,
    ],
    'db' => [
        'host' => '192.168.0.133',
        'port' => 3306,
        'name' => 'saec_cloud',
        'user' => 'saec_app',
        'pass' => 'DUEVlJKQAYZ/5vmaF8Xw3ETG',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'jwt_secret' => bin2hex(random_bytes(32)),
        'encryption_key_path' => dirname(__DIR__) . '/storage/keys/master.key',
    ],
    'upload' => [
        'max_file_size' => 3355443200,  // 3200MB
        'allowed_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
        ],
    ],
    'email' => [
        'api_key' => '',  // ← REMPLACER avec clé MailerSend
        'from_email' => 'cloud@saec.me',
        'from_name' => 'SAEC Cloud',
    ],
];

file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
chmod($configFile, 0644);
success("Configuration créée: {$configFile}");

// ══════════════════════════════════════════════════════════════
// ÉTAPE 3 : CLÉ DE CHIFFREMENT
// ══════════════════════════════════════════════════════════════

info("Génération de la clé de chiffrement...");

$keyFile = $baseDir . '/storage/keys/master.key';
if (!file_exists($keyFile)) {
    $key = random_bytes(32);
    file_put_contents($keyFile, $key);
    chmod($keyFile, 0600);
    success("Clé de chiffrement générée");
} else {
    success("Clé de chiffrement existe déjà");
}

// ══════════════════════════════════════════════════════════════
// ÉTAPE 4 : CONNEXION BASE DE DONNÉES
// ══════════════════════════════════════════════════════════════

info("Test de connexion à la base de données...");

try {
    $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};charset={$config['db']['charset']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    success("Connecté à MySQL");
} catch (PDOException $e) {
    error("Impossible de se connecter à MySQL");
    error("Vérifiez les identifiants dans {$configFile}");
    exit(1);
}

// ══════════════════════════════════════════════════════════════
// ÉTAPE 5 : CRÉATION DES TABLES
// ══════════════════════════════════════════════════════════════

info("Création des tables...");

$pdo->exec("USE {$config['db']['name']}");

$tables = [
    "CREATE TABLE IF NOT EXISTS tenants (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        storage_quota BIGINT UNSIGNED DEFAULT 10737418240,
        max_file_size INT UNSIGNED DEFAULT 104857600,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    
    "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        email VARCHAR(255) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','user','viewer') DEFAULT 'user',
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_email_tenant (email, tenant_id),
        FOREIGN KEY (tenant_id) REFERENCES tenants(id)
    ) ENGINE=InnoDB",
    
    "CREATE TABLE IF NOT EXISTS files (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        folder_id INT UNSIGNED NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100),
        size BIGINT UNSIGNED NOT NULL,
        checksum VARCHAR(64) NOT NULL,
        file_key TEXT NOT NULL,
        version INT DEFAULT 1,
        storage_location VARCHAR(50) DEFAULT 'local',
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL,
        INDEX idx_tenant (tenant_id),
        INDEX idx_folder (folder_id),
        INDEX idx_deleted (deleted_at),
        INDEX idx_storage (storage_location)
    ) ENGINE=InnoDB",
    
    "CREATE TABLE IF NOT EXISTS audit_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED,
        action VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
];

foreach ($tables as $sql) {
    $pdo->exec($sql);
}
success("Tables créées");

// ══════════════════════════════════════════════════════════════
// ÉTAPE 6 : DONNÉES PAR DÉFAUT
// ══════════════════════════════════════════════════════════════

info("Insertion des données par défaut...");

// Tenant par défaut
$pdo->exec("INSERT IGNORE INTO tenants (name, slug) VALUES ('Default', 'default')");
$tenantId = $pdo->query("SELECT id FROM tenants WHERE slug='default'")->fetchColumn();
success("Tenant par défaut (ID: {$tenantId})");

// Admin (mot de passe: changeme)
$hash = password_hash('changeme', PASSWORD_ARGON2ID);
$pdo->prepare("INSERT IGNORE INTO users (tenant_id, email, password_hash, role) VALUES (?, 'admin@saec.me', ?, 'admin')")->execute([$tenantId, $hash]);
success("Admin créé (email: admin@saec.me, pass: changeme)");

// ══════════════════════════════════════════════════════════════
// ÉTAPE 7 : NETTOYAGE
// ══════════════════════════════════════════════════════════════

$pdo = null;

// ══════════════════════════════════════════════════════════════
// RÉSUMÉ
// ══════════════════════════════════════════════════════════════

echo "\n";
output("  ═══════════════════════════════════════════════════════════════", 'cyan');
output("  ✅ DÉPLOIEMENT TERMINÉ", 'green');
output("  ═══════════════════════════════════════════════════════════════", 'cyan');
echo "\n";
output("  Site: https://cloud.saec.me", 'green');
output("  Admin: admin@saec.me / changeme", 'yellow');
echo "\n";
warning("CHANGEZ LE MOT DE PASSE ADMIN IMMÉDIATEMENT !");
echo "\n";

// ══════════════════════════════════════════════════════════════
// AUTO-DESTRUCTION
// ══════════════════════════════════════════════════════════════

info("Auto-destruction du script...");
unlink(__FILE__);
success("Script supprimé");
