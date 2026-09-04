-- ══════════════════════════════════════════════════════════════
-- SAEC Cloud — Fix Schema: missing columns
-- Exécuter pour corriger les erreurs 500
-- ══════════════════════════════════════════════════════════════

USE saec_cloud;

-- files: colonnes manquantes
SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'files' AND COLUMN_NAME = 'folder_id');
SET @sql = IF(@exist = 0, 'ALTER TABLE files ADD COLUMN folder_id INT UNSIGNED NULL AFTER user_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'files' AND COLUMN_NAME = 'file_key');
SET @sql = IF(@exist = 0, 'ALTER TABLE files ADD COLUMN file_key TEXT NULL AFTER checksum', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'files' AND COLUMN_NAME = 'deleted_at');
SET @sql = IF(@exist = 0, 'ALTER TABLE files ADD COLUMN deleted_at TIMESTAMP NULL AFTER size', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'files' AND COLUMN_NAME = 'updated_at');
SET @sql = IF(@exist = 0, 'ALTER TABLE files ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'files' AND COLUMN_NAME = 'version');
SET @sql = IF(@exist = 0, 'ALTER TABLE files ADD COLUMN version INT DEFAULT 1 AFTER checksum', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- audit_logs: colonnes manquantes
SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'resource_type');
SET @sql = IF(@exist = 0, 'ALTER TABLE audit_logs ADD COLUMN resource_type VARCHAR(50) NULL AFTER action', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'resource_id');
SET @sql = IF(@exist = 0, 'ALTER TABLE audit_logs ADD COLUMN resource_id INT UNSIGNED NULL AFTER resource_type', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'user_agent');
SET @sql = IF(@exist = 0, 'ALTER TABLE audit_logs ADD COLUMN user_agent TEXT NULL AFTER ip_address', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'metadata');
SET @sql = IF(@exist = 0, 'ALTER TABLE audit_logs ADD COLUMN metadata JSON NULL AFTER user_agent', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index manquants
CREATE INDEX IF NOT EXISTS idx_files_folder ON files(folder_id);
CREATE INDEX IF NOT EXISTS idx_files_deleted ON files(deleted_at);

-- Dossiers upload
INSERT IGNORE INTO folders (tenant_id, user_id, name, path) 
SELECT 1, id, '/', '/' FROM users WHERE tenant_id = 1 AND role = 'admin' LIMIT 1;

-- Fix permissions
-- chmod 770 /volume1/web/cloud/storage/uploads/  (sur le NAS)
