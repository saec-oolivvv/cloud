-- SAEC Cloud — Database Schema
-- MySQL 8.0+

CREATE DATABASE IF NOT EXISTS saec_cloud
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE saec_cloud;

-- Tenants
CREATE TABLE tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    storage_quota BIGINT UNSIGNED DEFAULT 10737418240,
    max_file_size INT UNSIGNED DEFAULT 104857600,
    max_users INT UNSIGNED DEFAULT 50,
    replication_enabled TINYINT(1) DEFAULT 0,
    replication_remote VARCHAR(500),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_slug (slug),
    INDEX idx_active (active)
) ENGINE=InnoDB;

-- Users
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user', 'viewer') DEFAULT 'user',
    mfa_secret VARCHAR(255),
    mfa_enabled TINYINT(1) DEFAULT 0,
    last_login_at TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_email_tenant (email, tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_email (email),
    INDEX idx_active (active)
) ENGINE=InnoDB;

-- Encryption Keys (per tenant)
CREATE TABLE encryption_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    key_data TEXT NOT NULL,
    version INT DEFAULT 1,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rotated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_active (active)
) ENGINE=InnoDB;

-- Files
CREATE TABLE files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    folder_path VARCHAR(500) DEFAULT '/',
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100),
    size BIGINT UNSIGNED NOT NULL,
    encryption_key_id INT UNSIGNED NOT NULL,
    checksum VARCHAR(64) NOT NULL,
    version INT DEFAULT 1,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (encryption_key_id) REFERENCES encryption_keys(id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_user (user_id),
    INDEX idx_folder (folder_path),
    INDEX idx_deleted (deleted_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- File Versions
CREATE TABLE file_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_id INT UNSIGNED NOT NULL,
    version INT NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    size BIGINT UNSIGNED NOT NULL,
    checksum VARCHAR(64) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_file (file_id),
    INDEX idx_version (version)
) ENGINE=InnoDB;

-- Shares
CREATE TABLE shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    file_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    shared_with INT UNSIGNED,
    permission ENUM('view', 'download', 'edit') DEFAULT 'view',
    link_token VARCHAR(128),
    link_password_hash VARCHAR(255),
    expires_at TIMESTAMP NULL,
    access_count INT DEFAULT 0,
    max_accesses INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (shared_with) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_file (file_id),
    INDEX idx_link_token (link_token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- Audit Logs (append-only)
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    action VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tenant_time (tenant_id, created_at),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- System Config
CREATE TABLE system_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    config_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin tenant
INSERT INTO tenants (name, slug, storage_quota, max_file_size) 
VALUES ('Default', 'default', 10737418240, 104857600);

-- Default admin user (password: changeme)
INSERT INTO users (tenant_id, email, password_hash, role) 
VALUES (1, 'admin@saec.me', '$argon2id$v=19$m=65536,t=4,p=3$d2ViU3VwcG9ydA$HASH_TO_GENERATE', 'admin');
