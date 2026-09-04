-- Migration 003: Cloud Integration
-- Adds storage_providers, storage_backups, storage_mounts tables

-- Storage providers (S3, SFTP, FTP, WebDAV, GDrive, Dropbox, OneDrive)
CREATE TABLE IF NOT EXISTS storage_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('s3','sftp','ftp','webdav','gdrive','dropbox','onedrive') NOT NULL,
    config JSON NOT NULL COMMENT 'Provider-specific credentials/settings (encrypted)',
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    last_sync_at DATETIME NULL,
    last_error TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup history and scheduling
CREATE TABLE IF NOT EXISTS storage_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    tenant_id INT NULL COMMENT 'NULL = system/global backup',
    type ENUM('full','incremental','files_only','db_only') NOT NULL DEFAULT 'full',
    status ENUM('pending','running','completed','failed') DEFAULT 'pending',
    remote_path VARCHAR(500) NOT NULL COMMENT 'Path on remote storage',
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT DEFAULT 0,
    file_checksum VARCHAR(64) NULL,
    encrypted TINYINT(1) DEFAULT 1,
    compression VARCHAR(20) DEFAULT 'gzip',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    duration_seconds INT NULL,
    error_message TEXT NULL,
    metadata JSON NULL COMMENT 'Backup details (tables, file count, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES storage_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    INDEX idx_backup_tenant (tenant_id),
    INDEX idx_backup_status (status),
    INDEX idx_backup_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remote folder mounts (virtual filesystem)
CREATE TABLE IF NOT EXISTS storage_mounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    tenant_id INT NOT NULL,
    remote_path VARCHAR(500) NOT NULL COMMENT 'Root path on remote storage',
    local_alias VARCHAR(255) NOT NULL COMMENT 'Display name in files manager',
    mount_type ENUM('readonly','readwrite','backup_only') DEFAULT 'readwrite',
    sync_enabled TINYINT(1) DEFAULT 0,
    sync_interval_minutes INT DEFAULT 60,
    last_sync_at DATETIME NULL,
    last_sync_status ENUM('ok','error','syncing') NULL,
    last_sync_message TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES storage_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_mount_tenant (tenant_id),
    INDEX idx_mount_provider (provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup schedule configuration
CREATE TABLE IF NOT EXISTS storage_backup_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    tenant_id INT NULL COMMENT 'NULL = apply to all tenants',
    name VARCHAR(255) NOT NULL DEFAULT 'Auto Backup',
    type ENUM('full','incremental','files_only','db_only') NOT NULL DEFAULT 'full',
    frequency ENUM('hourly','daily','weekly','monthly') DEFAULT 'daily',
    time_of_day TIME DEFAULT '02:00:00',
    day_of_week TINYINT NULL COMMENT '0=Sunday, 6=Saturday (for weekly)',
    day_of_month TINYINT NULL COMMENT '1-31 (for monthly)',
    retention_days INT DEFAULT 30 COMMENT 'Auto-delete backups older than N days',
    is_active TINYINT(1) DEFAULT 1,
    last_run_at DATETIME NULL,
    next_run_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES storage_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
