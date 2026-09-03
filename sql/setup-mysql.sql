-- ══════════════════════════════════════════════════════════════
-- SAEC Cloud — Instructions SQL
-- Exécuter sur le serveur MySQL (192.168.0.201)
-- ══════════════════════════════════════════════════════════════

-- 1. Créer la base de données
CREATE DATABASE IF NOT EXISTS saec_cloud
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- 2. Générer un mot de passe aléatoire (à noter pour .env)
-- Exécutez cette requête pour générer le password :
SELECT CONCAT(
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1),
    SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()', FLOOR(RAND() * 72) + 1, 1)
) AS generated_password;

-- OU plus simple avec SHA2 :
SELECT SHA2(RAND(), 256) AS generated_password;

-- 3. Créer l'utilisateur restreint à l'IP du NAS Synology
-- Remplacez 'VOTRE_MOT_DE_PASSE' par le password généré ci-dessus
CREATE USER 'saec_cloud'@'192.168.0.201'
    IDENTIFIED BY 'VOTRE_MOT_DE_PASSE'
    PASSWORD EXPIRE NEVER
    FAILED_LOGIN_ATTEMPTS 5
    PASSWORD_LOCK_TIME 2;

-- 4. Accorder les privilèges uniquement sur la base SAEC
GRANT ALL PRIVILEGES ON saec_cloud.* TO 'saec_cloud'@'192.168.0.201';

-- 5. Révoquer tout accès depuis d'autres IPs
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'saec_cloud'@'%';

-- 6. Appliquer les changements
FLUSH PRIVILEGES;

-- 7. Vérifier les grants
SHOW GRANTS FOR 'saec_cloud'@'192.168.0.201';

-- ══════════════════════════════════════════════════════════════
-- VÉRIFICATION
-- ══════════════════════════════════════════════════════════════

-- Vérifier que l'utilisateur ne peut se connecter que depuis l'IP autorisée
SELECT user, host FROM mysql.user WHERE user = 'saec_cloud';

-- Vérifier que la base existe
SHOW DATABASES LIKE 'saec_cloud';
