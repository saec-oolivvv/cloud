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

-- users: colonnes manquantes
SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at');
SET @sql = IF(@exist = 0, 'ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL AFTER active', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verification_token');
SET @sql = IF(@exist = 0, 'ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login');
SET @sql = IF(@exist = 0, 'ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER active', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login_ip');
SET @sql = IF(@exist = 0, 'ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(45) NULL AFTER last_login', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'saec_cloud' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_active_at');
SET @sql = IF(@exist = 0, 'ALTER TABLE users ADD COLUMN last_active_at TIMESTAMP NULL AFTER last_login_ip', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Tables manquantes pour Security
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) DEFAULT 0,
    failure_reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trusted_devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Mark all existing users as verified (migration)
UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL AND active = 1;

-- Fix permissions
-- chmod 770 /volume1/web/cloud/storage/uploads/  (sur le NAS)
