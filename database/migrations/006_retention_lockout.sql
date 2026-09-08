-- Migration 006: Retention policies + Account lockout

-- Tenant retention settings
ALTER TABLE tenants ADD COLUMN trash_retention_days INT DEFAULT 30 COMMENT 'Auto-delete trashed files after N days' AFTER deleted_at;
ALTER TABLE tenants ADD COLUMN audit_retention_days INT DEFAULT 90 COMMENT 'Auto-delete audit logs after N days' AFTER trash_retention_days;

-- Account lockout after inactivity
ALTER TABLE users ADD COLUMN lockout_after_inactive_minutes INT DEFAULT 0 COMMENT '0=disabled, N=lockout after N min inactive' AFTER mfa_enabled;
ALTER TABLE users ADD COLUMN last_active_at TIMESTAMP NULL AFTER last_login_at;

-- IP whitelist per tenant (optional, restrictive mode)
CREATE TABLE IF NOT EXISTS ip_whitelist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    label VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_tenant_ip (tenant_id, ip_address),
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
