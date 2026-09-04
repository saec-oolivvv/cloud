# Cloud Integration Plan — SAEC Cloud

## Overview
Admin panel integration with cloud/remote storage providers for:
1. **Backups** — Periodic snapshots (DB + encrypted files) to remote storage
2. **Remote Folders** — Mount distant folders as virtual filesystems for tenant clients

---

## Supported Providers

### Object Storage (S3-compatible)
| Provider | Adapter | Notes |
|----------|---------|-------|
| Amazon S3 | `S3Adapter` | Native AWS SDK |
| Cloudflare R2 | `S3Adapter` | S3-compatible, region=auto |
| MinIO (self-hosted) | `S3Adapter` | pathStyle=true |
| DigitalOcean Spaces | `S3Adapter` | Endpoint-based |
| Wasabi | `S3Adapter` | Endpoint-based |
| Backblaze B2 | `S3Adapter` | S3-compatible endpoint |

### Remote File Systems
| Provider | Adapter | Notes |
|----------|---------|-------|
| SFTP (SSH) | `SftpAdapter` | phpseclib3 |
| FTP/SFTP | `FtpAdapter` | Native PHP FTP |
| WebDAV | `WebdavAdapter` | sabre/dav |

### Cloud Provider APIs
| Provider | Adapter | Notes |
|----------|---------|-------|
| Google Drive | `GdriveAdapter` | Google API PHP Client |
| Dropbox | `DropboxAdapter` | Dropbox API v2 |
| OneDrive | `OnedriveAdapter` | Microsoft Graph API |

### Self-hosted Solutions
| Provider | Adapter | Notes |
|----------|---------|-------|
| Nextcloud | `WebdavAdapter` | WebDAV-compatible |
| ownCloud | `WebdavAdapter` | WebDAV-compatible |

---

## Database Schema

### `storage_providers`
```sql
CREATE TABLE storage_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('s3','sftp','ftp','webdav','gdrive','dropbox','onedrive') NOT NULL,
    config JSON NOT NULL COMMENT 'Provider-specific credentials/settings',
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    last_sync_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
);
```

### `storage_backups`
```sql
CREATE TABLE storage_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    tenant_id INT NULL COMMENT 'NULL = system backup',
    type ENUM('full','incremental','files_only','db_only') NOT NULL,
    status ENUM('pending','running','completed','failed') DEFAULT 'pending',
    file_path VARCHAR(500) NOT NULL COMMENT 'Path on remote storage',
    file_size BIGINT DEFAULT 0,
    file_checksum VARCHAR(64) NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES storage_providers(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### `storage_mounts`
```sql
CREATE TABLE storage_mounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    tenant_id INT NOT NULL,
    remote_path VARCHAR(500) NOT NULL COMMENT 'Path on remote storage',
    local_alias VARCHAR(255) NOT NULL COMMENT 'Display name in files manager',
    mount_options JSON NULL COMMENT 'Read-only, sync interval, etc.',
    is_active TINYINT(1) DEFAULT 1,
    last_sync_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES storage_providers(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

---

## Architecture

### Storage Adapter Interface
```php
interface StorageAdapter {
    public function testConnection(): bool;
    public function list(string $path = ''): array;
    public function read(string $path): string;
    public function write(string $path, string $content): bool;
    public function delete(string $path): bool;
    public function mkdir(string $path): bool;
    public function exists(string $path): bool;
    public function size(string $path): int;
    public function getUsedSpace(): int;
    public function getFreeSpace(): int;
}
```

### Backup Service
```php
class BackupService {
    public function createSnapshot(int $tenantId, string $type): int;
    public function backupDatabase(int $backupId, string $providerId): void;
    public function backupFiles(int $backupId, string $providerId, int $tenantId): void;
    public function restore(int $backupId): bool;
    public function listBackups(int $tenantId): array;
    public function deleteBackup(int $backupId): bool;
}
```

### Mount Service
```php
class MountService {
    public function mount(int $providerId, int $tenantId, string $remotePath, string $alias): int;
    public function unmount(int $mountId): bool;
    public function sync(int $mountId): array;
    public function listMounts(int $tenantId): array;
    public function browse(int $mountId, string $path): array;
}
```

---

## Admin UI Pages

### 1. `/admin/storage` — Storage Providers
- List all configured providers with status
- Add/Edit/Delete provider modals
- Test connection button
- Provider type selector (S3, SFTP, FTP, WebDAV, GDrive, Dropbox, OneDrive)
- Dynamic form based on provider type

### 2. `/admin/storage/backups` — Backup Management
- List all backups with status
- Manual backup trigger button
- Backup schedule configuration
- Restore from backup
- Delete old backups

### 3. `/admin/storage/mounts` — Remote Mounts
- List all tenant mounts
- Mount/Unmount remote folders
- Sync status and history
- Per-tenant mount management

---

## Implementation Phases

### Phase 1: Core Infrastructure
1. Database migration (3 new tables)
2. Storage adapter interface + base class
3. S3 adapter (covers R2, MinIO, DO Spaces, Wasabi, B2)
4. SFTP adapter
5. Admin controller + routes

### Phase 2: Admin UI
1. Storage providers page
2. Add/Edit provider modal
3. Test connection functionality
4. Provider status dashboard

### Phase 3: Backup System
1. Backup service class
2. Database dump utility
3. File archive utility (tar.gz + AES encryption)
4. Backup scheduler
5. Restore functionality

### Phase 4: Remote Mounts
1. Mount service class
2. Remote folder browsing
3. Sync engine
4. Per-tenant mount management

### Phase 5: Additional Providers
1. FTP adapter
2. WebDAV adapter (Nextcloud, ownCloud)
3. Google Drive adapter
4. Dropbox adapter
5. OneDrive adapter

---

## Security Considerations
- All credentials encrypted at rest (AES-256-GCM)
- Provider configs stored in `config` column as encrypted JSON
- Backup files encrypted before upload
- SFTP/FTP connections use TLS
- API tokens stored securely
- Audit logs for all storage operations

---

## Dependencies (no Composer)
All adapters will use:
- Native PHP extensions (curl, openssl, json)
- PHP built-in classes (DateTime, etc.)
- Custom implementations for each protocol

No external dependencies needed — pure PHP implementation.
