-- Migration 005: Session Fingerprinting + Password History + Anomaly Detection

-- Password history (prevent reuse)
CREATE TABLE IF NOT EXISTS password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Known devices (for anomaly detection)
CREATE TABLE IF NOT EXISTS known_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    fingerprint VARCHAR(64) NOT NULL COMMENT 'SHA256(user_agent + ip_subnet)',
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_trusted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_fingerprint_user (user_id, fingerprint),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add fingerprint column to user_sessions
ALTER TABLE user_sessions ADD COLUMN fingerprint VARCHAR(64) NULL AFTER user_agent;
ALTER TABLE user_sessions ADD COLUMN is_anomaly TINYINT(1) DEFAULT 0 AFTER fingerprint;
