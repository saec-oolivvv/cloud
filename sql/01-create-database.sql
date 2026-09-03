-- ══════════════════════════════════════════════════════════════
-- SAEC Cloud — Instructions SQL Complètes
-- Serveur: 192.168.0.201
-- ══════════════════════════════════════════════════════════════

-- 1. CRÉER LA BASE DE DONNÉES
-- ─────────────────────────────
CREATE DATABASE IF NOT EXISTS saec_cloud
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- 2. GÉNÉRER UN MOT DE PASSE ALÉATOIRE (64 caractères)
-- ─────────────────────────────────────────────────────
-- Copiez le résultat et gardez-le en lieu sûr
SELECT SHA2(CONCAT(RAND(), UUID(), NOW()), 256) AS mot_de_passe;

-- OU cette version plus lisible (groupe de 8):
SELECT CONCAT(
    SUBSTRING(SHA2(CONCAT(RAND(), UUID()), 256), 1, 8), '-',
    SUBSTRING(SHA2(CONCAT(RAND(), UUID()), 256), 1, 8), '-',
    SUBSTRING(SHA2(CONCAT(RAND(), UUID()), 256), 1, 8), '-',
    SUBSTRING(SHA2(CONCAT(RAND(), UUID()), 256), 1, 8), '-',
    SUBSTRING(SHA2(CONCAT(RAND(), UUID()), 256), 1, 12)
) AS mot_de_passe_securise;

-- 3. CRÉER L'UTILISATEUR RESTREINT
-- ─────────────────────────────────
-- ⚠️ REMPLACEZ 'VOTRE_MOT_DE_PASSE_ICI' par le password généré ci-dessus
CREATE USER 'saec_cloud'@'192.168.0.201'
    IDENTIFIED BY 'VOTRE_MOT_DE_PASSE_ICI'
    PASSWORD EXPIRE NEVER
    FAILED_LOGIN_ATTEMPTS 5
    PASSWORD_LOCK_TIME 2;

-- 4. ACCORDER LES PRIVILÈGES (UNIQUEMENT sur saec_cloud)
-- ───────────────────────────────────────────────────────
GRANT ALL PRIVILEGES ON saec_cloud.* TO 'saec_cloud'@'192.168.0.201';

-- 5. RÉVOUER TOUT AUTRE ACCÈS
-- ────────────────────────────
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'saec_cloud'@'%';
DELETE FROM mysql.user WHERE User='saec_cloud' AND Host NOT IN ('192.168.0.201');

-- 6. APPLIQUER
-- ─────────────
FLUSH PRIVILEGES;

-- ══════════════════════════════════════════════════════════════
-- VÉRIFICATIONS OBLIGATOIRES
-- ══════════════════════════════════════════════════════════════

-- Vérifier que l'utilisateur existe bien avec la bonne IP
SELECT user, host, password_expired, password_lifetime 
FROM mysql.user 
WHERE user = 'saec_cloud';

-- Vérifier les grants
SHOW GRANTS FOR 'saec_cloud'@'192.168.0.201';

-- Vérifier que la base existe
SHOW DATABASES LIKE 'saec_cloud';

-- Test de connexion (depuis le NAS Synology)
-- mysql -h 192.168.0.201 -u saec_cloud -p saec_cloud
