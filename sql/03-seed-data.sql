-- ══════════════════════════════════════════════════════════════
-- SAEC Cloud — Données par Défaut
-- Exécuter APRÈS 02-create-tables.sql
-- ══════════════════════════════════════════════════════════════

USE saec_cloud;

-- ─────────────────────────────────────────────────────────────
-- TENANT PAR DÉFAUT
-- ─────────────────────────────────────────────────────────────
INSERT INTO tenants (name, slug, storage_quota, max_file_size, max_users)
VALUES ('Default', 'default', 10737418240, 104857600, 50)
ON DUPLICATE KEY UPDATE name = name;

-- Récupérer l'ID du tenant
SET @tenant_id = (SELECT id FROM tenants WHERE slug = 'default');

-- ─────────────────────────────────────────────────────────────
-- UTILISATEUR ADMIN
-- ⚠️ REMPLACEZ le hash par votre mot de passe hashé
-- Générez le hash avec : php -r "echo password_hash('VOTRE_MOT_DE_PASSE', PASSWORD_ARGON2ID);"
-- ─────────────────────────────────────────────────────────────
-- HASH PAR DÉFAUT : 'changeme' (À REMPLACER !)
INSERT INTO users (tenant_id, email, password_hash, role)
VALUES (@tenant_id, 'admin@saec.me', '$argon2id$v=19$m=65536,t=4,p=3$d2ViU3VwcG9ydA$REPLACE_WITH_YOUR_HASH', 'admin')
ON DUPLICATE KEY UPDATE email = email;

-- ─────────────────────────────────────────────────────────────
-- CONFIG SYSTÈME
-- ─────────────────────────────────────────────────────────────
INSERT INTO system_config (config_key, config_value, config_type, description) VALUES
('app_name', 'SAEC Cloud', 'string', 'Nom de l''application'),
('app_url', 'https://cloud.saec.me', 'string', 'URL de l''application'),
('max_file_size', '104857600', 'int', 'Taille max par fichier (bytes) - 100MB'),
('storage_quota_default', '10737418240', 'int', 'Quota par défaut par tenant (bytes) - 10GB'),
('replication_enabled', '0', 'bool', 'Réplication activée'),
('mfa_required', '0', 'bool', 'MFA obligatoire'),
('session_lifetime', '15', 'int', 'Durée session en minutes'),
('refresh_lifetime', '10080', 'int', 'Durée refresh token en minutes (7 jours)'),
('rate_limit_login', '5', 'int', 'Max tentatives login par fenêtre'),
('rate_limit_window', '900', 'int', 'Fenêtre rate limit en secondes (15min)')
ON DUPLICATE KEY UPDATE config_value = config_value;

-- ─────────────────────────────────────────────────────────────
-- MIGRATION INITIALE
-- ─────────────────────────────────────────────────────────────
INSERT INTO migrations (migration, batch)
VALUES ('001_initial_schema', 1)
ON DUPLICATE KEY UPDATE migration = migration;
