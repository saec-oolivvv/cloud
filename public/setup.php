<?php

/**
 * SAEC Cloud — Setup Admin
 * Auto-destruit après succès
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

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

// ══════════════════════════════════════════════════════════════
// DÉMARRAGE
// ══════════════════════════════════════════════════════════════

echo "\n";
output("  SAEC CLOUD — SETUP ADMIN", 'cyan');
output("  ═══════════════════════════════════════", 'cyan');
echo "\n";

// Vérifier si le script deploy.php existe encore
if (file_exists('deploy.php')) {
    error("Le script deploy.php existe encore. Exécutez-le d'abord.");
    exit(1);
}

// Trouver le fichier de config
$configFiles = glob('storage/x*.conf');
if (empty($configFiles)) {
    error("Fichier de configuration non trouvé");
    exit(1);
}

$configFile = $configFiles[0];
$config = json_decode(file_get_contents($configFile), true);

// ══════════════════════════════════════════════════════════════
// DEMANDER LE NOUVEAU MOT DE PASSE
// ══════════════════════════════════════════════════════════════

info("Nouveau mot de passe admin (min 12 caractères):");
echo "  > ";

$handle = fopen('php://stdin', 'r');
$newPassword = trim(fgets($handle));

if (strlen($newPassword) < 12) {
    error("Le mot de passe doit faire au moins 12 caractères");
    exit(1);
}

info("Confirmez le mot de passe:");
echo "  > ";
$confirmPassword = trim(fgets($handle));

if ($newPassword !== $confirmPassword) {
    error("Les mots de passe ne correspondent pas");
    exit(1);
}

// ══════════════════════════════════════════════════════════════
// METTRE À JOUR L'ADMIN
// ══════════════════════════════════════════════════════════════

info("Mise à jour du mot de passe admin...");

try {
    $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@saec.me'")->execute([$hash]);
    
    success("Mot de passe admin mis à jour");
    
    $pdo = null;
    
} catch (PDOException $e) {
    error("Erreur de base de données");
    exit(1);
}

// ══════════════════════════════════════════════════════════════
// RÉSUMÉ
// ══════════════════════════════════════════════════════════════

echo "\n";
output("  ═══════════════════════════════════════════════════════════════", 'cyan');
output("  ✅ ADMIN CONFIGURÉ", 'green');
output("  ═══════════════════════════════════════════════════════════════", 'cyan');
echo "\n";
output("  Site: https://cloud.saec.me", 'green');
output("  Email: admin@saec.me", 'green');
output("  Pass: [votre nouveau mot de passe]", 'green');
echo "\n";

// ══════════════════════════════════════════════════════════════
// AUTO-DESTRUCTION
// ══════════════════════════════════════════════════════════════

info("Auto-destruction du script...");
unlink(__FILE__);
success("Script supprimé");
