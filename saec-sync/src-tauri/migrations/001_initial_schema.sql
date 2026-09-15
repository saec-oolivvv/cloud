-- Initial schema for file index
CREATE TABLE IF NOT EXISTS file_index (
    path TEXT PRIMARY KEY,
    is_dir BOOLEAN NOT NULL DEFAULT 0,
    size INTEGER NOT NULL DEFAULT 0,
    modified TEXT NOT NULL, -- ISO 8601 datetime
    checksum TEXT NOT NULL DEFAULT '',
    synced BOOLEAN NOT NULL DEFAULT 0,
    local_modified TEXT, -- ISO 8601 datetime when local file was last modified
    remote_modified TEXT, -- ISO 8601 datetime when remote file was last modified
    remote_checksum TEXT NOT NULL DEFAULT ''
);

CREATE INDEX IF NOT EXISTS idx_file_index_synced ON file_index(synced);
CREATE INDEX IF NOT EXISTS idx_file_index_is_dir ON file_index(is_dir);
CREATE INDEX IF NOT EXISTS idx_file_index_path_prefix ON file_index(path);

-- Metadata table for index version and info
CREATE TABLE IF NOT EXISTS index_metadata (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

INSERT OR IGNORE INTO index_metadata (key, value) VALUES 
    ('version', '1'),
    ('created_at', datetime('now')),
    ('last_full_scan', '');