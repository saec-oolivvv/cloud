<?php

/**
 * SAEC Cloud — Tenant Scheduler
 * 
 * À exécuter via cron toutes les heures :
 * 0 * * * * php /volume1/web/cloud/scripts/scheduler.php
 * 
 * Fonctions :
 * 1. Désactive les tenants expirés
 * 2. Vérifie les quotas
 * 3. Envoie les alertes
 */

declare(strict_types=1);

// Charger la config
$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) {
    echo "Configuration non trouvée\n";
    exit(1);
}

$config = json_decode(file_get_contents($configFiles[0]), true);

// Connexion DB
try {
    $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    echo "Erreur DB: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Tenant Scheduler démarré\n";

// ══════════════════════════════════════════════════════════════
// 1. DÉSACTIVER LES TENANTS EXPIRÉS
// ══════════════════════════════════════════════════════════════

echo "  → Vérification des tenants expirés...\n";

$stmt = $pdo->prepare("
    UPDATE tenants 
    SET active = 0 
    WHERE end_date < CURDATE() 
    AND auto_deactivate = 1
    AND active = 1
    AND deleted_at IS NULL
");
$stmt->execute();
$deactivated = $stmt->rowCount();

if ($deactivated > 0) {
    echo "  ✓ {$deactivated} tenant(s) désactivé(s) (expiré)\n";
    
    // Log
    $pdo->exec("
        INSERT INTO audit_logs (tenant_id, action, metadata, created_at)
        SELECT id, 'tenant.auto_deactivated', JSON_OBJECT('reason', 'expired'), NOW()
        FROM tenants WHERE active = 0 AND end_date < CURDATE() AND auto_deactivate = 1
    ");
} else {
    echo "  ✓ Aucun tenant expiré\n";
}

// ══════════════════════════════════════════════════════════════
// 2. VÉRIFIER LES QUOTAS
// ══════════════════════════════════════════════════════════════

echo "  → Vérification des quotas...\n";

$tenants = $pdo->query("
    SELECT t.id, t.name, t.max_storage, t.max_users,
           (SELECT COALESCE(SUM(size), 0) FROM files WHERE tenant_id = t.id AND deleted_at IS NULL) as storage_used,
           (SELECT COUNT(*) FROM users WHERE tenant_id = t.id AND active = 1) as users_count
    FROM tenants t
    WHERE t.active = 1 AND t.deleted_at IS NULL
")->fetchAll();

$alerts = 0;
foreach ($tenants as $tenant) {
    // Vérifier stockage
    if ($tenant['max_storage'] > 0) {
        $storagePercent = ($tenant['storage_used'] / $tenant['max_storage']) * 100;
        if ($storagePercent >= 80) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO quota_alerts (tenant_id, alert_type, threshold_percent)
                VALUES (?, 'storage', ?)
            ");
            $stmt->execute([$tenant['id'], (int) $storagePercent]);
            $alerts++;
        }
    }
    
    // Vérifier users
    if ($tenant['max_users'] > 0) {
        $usersPercent = ($tenant['users_count'] / $tenant['max_users']) * 100;
        if ($usersPercent >= 80) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO quota_alerts (tenant_id, alert_type, threshold_percent)
                VALUES (?, 'users', ?)
            ");
            $stmt->execute([$tenant['id'], (int) $usersPercent]);
            $alerts++;
        }
    }
}

echo "  ✓ {$alerts} alerte(s) quota générée(s)\n";

// ══════════════════════════════════════════════════════════════
// 3. ALERTES EXPIRATION PROCHAINE
// ══════════════════════════════════════════════════════════════

echo "  → Vérification des expirations prochaines...\n";

$stmt = $pdo->query("
    SELECT * FROM tenants 
    WHERE end_date IS NOT NULL 
    AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND active = 1
    AND deleted_at IS NULL
");
$expiring = $stmt->fetchAll();

foreach ($expiring as $tenant) {
    $daysLeft = (strtotime($tenant['end_date']) - time()) / 86400;
    echo "  ⚠ Tenant '{$tenant['name']}' expire dans " . ceil($daysLeft) . " jour(s)\n";
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO quota_alerts (tenant_id, alert_type, threshold_percent)
        VALUES (?, 'expiry', ?)
    ");
    $stmt->execute([$tenant['id'], (int) $daysLeft]);
}

echo "  ✓ " . count($expiring) . " tenant(s) expirant bientôt\n";

// ══════════════════════════════════════════════════════════════
// RÉSUMÉ
// ══════════════════════════════════════════════════════════════

echo "[" . date('Y-m-d H:i:s') . "] Scheduler terminé\n";
echo "  - Désactivés: {$deactivated}\n";
echo "  - Alertes: {$alerts}\n";
echo "  - Expirant: " . count($expiring) . "\n";
