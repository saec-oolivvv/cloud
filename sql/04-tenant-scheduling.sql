-- ══════════════════════════════════════════════════════════════
-- SAEC Cloud — Migration: Tenant Scheduling
-- Exécuter sur la base saec_cloud
-- ══════════════════════════════════════════════════════════════

USE saec_cloud;

-- Ajouter les champs de programmation
ALTER TABLE tenants
    ADD COLUMN IF NOT EXISTS start_date DATE NULL,
    ADD COLUMN IF NOT EXISTS end_date DATE NULL,
    ADD COLUMN IF NOT EXISTS auto_deactivate TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS max_storage BIGINT UNSIGNED DEFAULT 10737418240,
    ADD COLUMN IF NOT EXISTS max_bandwidth BIGINT UNSIGNED DEFAULT 107374182400,
    ADD COLUMN IF NOT EXISTS features JSON NULL;

-- Index pour le scheduler
CREATE INDEX idx_tenants_dates ON tenants(start_date, end_date);
CREATE INDEX idx_tenants_active ON tenants(active, deleted_at);

-- Table des quotas utilisés (tracking)
CREATE TABLE IF NOT EXISTS tenant_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    storage_used BIGINT UNSIGNED DEFAULT 0,
    bandwidth_used BIGINT UNSIGNED DEFAULT 0,
    users_count INT UNSIGNED DEFAULT 0,
    files_count INT UNSIGNED DEFAULT 0,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_tenant_period (tenant_id, period_start)
) ENGINE=InnoDB;

-- Table des alertes quotas
CREATE TABLE IF NOT EXISTS quota_alerts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    alert_type ENUM('storage', 'bandwidth', 'users', 'expiry') NOT NULL,
    threshold_percent INT DEFAULT 80,
    notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
