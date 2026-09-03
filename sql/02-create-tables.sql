-- ══════════════════════════════════════════════════════════════
-- SAEC Cloud — Création des Tables
-- Exécuter APRÈS 01-create-database.sql
-- ══════════════════════════════════════════════════════════════

USE saec_cloud;

-- ─────────────────────────────────────────────────────────────
-- TENANTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tenants (
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
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE INDEX idx_tenants_slug ON tenants(slug);
CREATE INDEX idx_tenants_active ON tenants(active);

-- ─────────────────────────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
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
    UNIQUE KEY uk_users_email_tenant (email, tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_users_tenant ON users(tenant_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_active ON users(active);

-- ─────────────────────────────────────────────────────────────
-- CLÉS DE CHIFFREMENT (par tenant)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS encryption_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    key_data TEXT NOT NULL,
    version INT DEFAULT 1,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rotated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_encryption_keys_tenant ON encryption_keys(tenant_id);

-- ─────────────────────────────────────────────────────────────
-- FICHIERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS files (
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
    FOREIGN KEY (encryption_key_id) REFERENCES encryption_keys(id)
) ENGINE=InnoDB;

CREATE INDEX idx_files_tenant ON files(tenant_id);
CREATE INDEX idx_files_user ON files(user_id);
CREATE INDEX idx_files_folder ON files(folder_path);
CREATE INDEX idx_files_deleted ON files(deleted_at);

-- ─────────────────────────────────────────────────────────────
-- VERSIONS DE FICHIERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS file_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_id INT UNSIGNED NOT NULL,
    version INT NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    size BIGINT UNSIGNED NOT NULL,
    checksum VARCHAR(64) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE INDEX idx_file_versions_file ON file_versions(file_id);

-- ─────────────────────────────────────────────────────────────
-- PARTAGES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shares (
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
    FOREIGN KEY (shared_with) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_shares_tenant ON shares(tenant_id);
CREATE INDEX idx_shares_file ON shares(file_id);
CREATE INDEX idx_shares_link_token ON shares(link_token);

-- ─────────────────────────────────────────────────────────────
-- LOGS D'AUDIT (IMMUABLE)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_logs (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_audit_logs_tenant_time ON audit_logs(tenant_id, created_at);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);

-- ─────────────────────────────────────────────────────────────
-- CONFIG SYSTÈME
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    config_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- MIGRATIONS (tracking)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_migrations_migration (migration)
) ENGINE=InnoDB;
