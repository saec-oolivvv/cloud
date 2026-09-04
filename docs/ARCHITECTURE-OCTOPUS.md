# SAEC Cloud — Architecture Octopus

## Vision
SaaS multi-tenant avec architecture tentaculaire : chaque feature est un "bras" autonome
qui peut fonctionner indépendamment ou en synergie avec les autres.

```
                        ┌─────────────────────────────────────────┐
                        │           SAEC CLOUD (Core)             │
                        │   Multi-tenant · AES-256 · RGPD        │
                        └─────────────────┬───────────────────────┘
                                          │
            ┌─────────────────────────────┼─────────────────────────────┐
            │                             │                             │
    ┌───────┴───────┐           ┌─────────┴─────────┐         ┌───────┴───────┐
    │  💾 STORAGE   │           │  🔄 BACKUP & SYNC │         │  🌐 REMOTE    │
    │  Octopus Arm  │           │  Octopus Arm      │         │  Octopus Arm  │
    └───────┬───────┘           └─────────┬─────────┘         └───────┬───────┘
            │                             │                             │
    ┌───────┴───────┐           ┌─────────┴─────────┐         ┌───────┴───────┐
    │ Local Storage │           │   Backup Engine   │         │ Mount Manager │
    │ S3/R2/MinIO   │           │   Diff Engine     │         │ Sync Engine   │
    │ SFTP/FTP      │           │   Compress+Encrypt│         │ Conflict Res. │
    │ WebDAV        │           │   Restore Engine  │         │ Browse Engine │
    │ GDrive        │           │   Scheduler       │         │               │
    │ Dropbox       │           │   Retention       │         │               │
    │ OneDrive      │           │   Notifications   │         │               │
    └───────────────┘           └───────────────────┘         └───────────────┘
            │                             │                             │
    ┌───────┴───────┐           ┌─────────┴─────────┐         ┌───────┴───────┐
    │  📁 TENANT    │           │  📊 MONITORING    │         │  🔐 SECURITY  │
    │  Management   │           │  Logs & Metrics   │         │  Vault        │
    └───────────────┘           └───────────────────┘         └───────────────┘
```

---

## 1. 💾 STORAGE OCTOPUS ARM

### Providers supportés
| Catégorie | Provider | Protocol | Usage |
|-----------|----------|----------|-------|
| **Object Storage** | Amazon S3 | S3 API | Backup, hosting principal |
| | Cloudflare R2 | S3 API | Backup, CDN, zero egress |
| | MinIO | S3 API | Self-hosted, dev/test |
| | DigitalOcean Spaces | S3 API | Backup, hosting |
| | Wasabi | S3 API | Backup, pas de egress fee |
| | Backblaze B2 | S3 API | Backup long terme |
| | Scaleway Object Storage | S3 API | Backup EU |
| | OVH Object Storage | S3 API | Backup EU |
| **Remote FS** | SFTP/SSH | SSH/SFTP | VPS, serveurs dédiés |
| | FTP/SFTP | FTP | Hébergeurs classiques |
| | WebDAV | HTTP | Nextcloud, ownCloud |
| | NFS | Mount | Réseau local |
| **Cloud APIs** | Google Drive | REST API | Backup perso, clients |
| | Dropbox | REST API | Backup perso |
| | OneDrive | MS Graph | Backup entreprise |
| | Box.com | REST API | Backup entreprise |
| **Self-hosted** | Nextcloud | WebDAV | Self-hosted |
| | ownCloud | WebDAV | Self-hosted |
| | Seafile | REST API | Self-hosted |
| | Syncthing | REST API | P2P sync |
| **NAS** | Synology DSM | REST API | NAS backup |
| | QNAP QTS | REST API | NAS backup |
| | TrueNAS | REST API | NAS backup |

### Storage Modes
1. **Primary Storage** — Fichiers stockés directement sur le provider
2. **Tiered Storage** — Fichiers chauds locaux, archivés sur cloud
3. **Mirror** — Copie temps réel vers 1+ providers
4. **Cache-Through** — Cache local + sync async vers cloud

### Tenant Storage Options
- **Quota** par tenant (configurable)
- **Redondance** — Mirroring multi-region/provider
- **Encryption** — AES-256-GCM avant upload
- **Compression** — gzip/zstd avant upload
- **Lifecycle** — Auto-transition vers storage froid après N jours

---

## 2. 🔄 BACKUP & SYNC OCTOPUS ARM

### Backup Types
| Type | Description | Fréquence | Taille |
|------|-------------|-----------|--------|
| **Full Snapshot** | DB dump + tous les fichiers | Hebdo/Mensuel | 100% |
| **Incremental** | Depuis le dernier backup | Quotidien | ~5-15% |
| **Differential** | Depuis le dernier full | 2-3x/semaine | ~20-40% |
| **Files Only** | Uniquement les fichiers | Quotidien | Variable |
| **DB Only** | Uniquement la base | Quotidien | Variable |
| **Continuous** | WAL/streaming en temps réel | Continu | Minimal |

### Backup Pipeline
```
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│  Dump   │───▶│Compress │───▶│Encrypt  │───▶│ Upload  │───▶│ Verify  │
│  (DB)   │    │(gzip/zst)│   │(AES-256)│    │(Provider)│   │(Checksum)│
└─────────┘    └─────────┘    └─────────┘    └─────────┘    └─────────┘
                                         │
                                    ┌────┴────┐
                                    │ Archive │
                                    │ (tar)   │
                                    └─────────┘
```

### Backup Schedules
- **Créneau horaire** configurable
- **Rétention** — Auto-delete après N jours
- **Rotation** — Garder les X derniers backups
- **_notifications** — Email/Slack/Webhook sur succès/échec

### Restore Options
- **Full Restore** — Tout restaurer
- **Point-in-time** — Restaurer à un moment donné
- **Selective** — Choisir quels fichiers/tables restaurer
- **Cross-tenant** — Restaurer un tenant dans un autre

### Sync Modes
| Mode | Description | Use Case |
|------|-------------|----------|
| **Push** | Local → Remote | Backup temps réel |
| **Pull** | Remote → Local | Import données |
| **Bidirectional** | Merge bidirectionnel | Collaboration |
| **One-way Mirror** | Copie exacte | Réplication |

### Conflict Resolution
- **Last Write Wins** — Par défaut
- **Manual Review** — Conflits en attente admin
- **Versioning** — Garder les deux versions
- **Custom Rules** — Par extension/taille/dossier

---

## 3. 🌐 REMOTE MOUNT OCTOPUS ARM

### Mount Types
| Type | Description | Permissions |
|------|-------------|-------------|
| **ReadOnly** | Consultation uniquement | Lecture |
| **ReadWrite** | Édition complète | Lecture + Écriture |
| **Backup Only** | Copie de sécurité | Lecture + Copie |
| **Sync** | Synchronisation bidirectionnel | Tous |

### Remote Browsing
- **List** — Lister les fichiers/dossiers
- **Preview** — Prévisualiser les fichiers
- **Download** — Télécharger
- **Upload** — Uploader (si ReadWrite)
- **Delete** — Supprimer (si ReadWrite)
- **Rename** — Renommer (si ReadWrite)
- **Move** — Déplacer (si ReadWrite)

### Mount Scenarios
```
┌─────────────────────────────────────────────────────────┐
│                    TENANT "ACME Corp"                    │
├─────────────────────────────────────────────────────────┤
│  /files/          ← Stockage local (chiffré)           │
│  /remote/s3/      ← Mount S3 bucket (lecture seule)    │
│  /remote/sftp/    ← Mount VPS client (lecture/écriture)│
│  /remote/nextcloud/ ← Mount Nextcloud (sync)           │
│  /backup/         ← Backups automatisés                 │
└─────────────────────────────────────────────────────────┘
```

---

## 4. 📁 TENANT MANAGEMENT OCTOPUS ARM

### Tenant Hierarchy
```
Super Admin
├── Tenant "ACME Corp" (Plan: Enterprise)
│   ├── User: admin@acme.com (role: admin)
│   ├── User: user1@acme.com (role: user)
│   ├── User: user2@acme.com (role: user)
│   ├── Storage: 100GB local + 500GB S3 mount
│   ├── Backups: Daily at 02:00 → R2
│   ├── Mounts: /remote/acme-backup → SFTP
│   └── Modules: [files, shares, versioning]
├── Tenant "Startup XYZ" (Plan: Professional)
│   ├── User: ceo@startup.xyz (role: admin)
│   ├── Storage: 50GB local
│   ├── Backups: Weekly → S3
│   └── Modules: [files, shares]
└── ...
```

### Tenant Features (Per-Plan)
| Feature | Starter | Professional | Enterprise |
|---------|---------|--------------|------------|
| Storage local | 10GB | 100GB | Illimité |
| Mounts remote | 0 | 2 | Illimité |
| Backups auto | Non | Quotidien | Continue |
| Multi-region | Non | Non | Oui |
| Versioning | Non | 30 jours | Illimité |
| API access | Non | Oui | Oui |
| Audit logs | 90 jours | 1 an | Illimité |

---

## 5. 📊 MONITORING OCTOPUS ARM

### Metrics Collectées
- **Storage** — Usage par tenant, par provider, total
- **Backups** — Succès, échecs, taille, durée
- **Mounts** — Sync status, latence, erreurs
- **API** — Requêtes, erreurs, latence
- **Security** — Login, tentatives, IPs suspectes

### Dashboards
- **Overview** — Métriques globales
- **Per-Tenant** — Détails par client
- **Storage** — Répartition, tendances
- **Backups** — Historique, prochains
- **Security** — Alertes, audit

### Notifications
- **Email** — Sur événements critiques
- **Webhook** — Intégration externe
- **Slack/Discord** — Alertes équipe
- **SMS** — Urgences (via provider)

---

## 6. 🔐 SECURITY OCTOPUS ARM

### Encryption Layers
1. **At Rest** — AES-256-GCM pour tous les fichiers
2. **In Transit** — TLS 1.3 pour toutes les connexions
3. **Backup** — Chiffrement séparé avant upload
4. **Provider Config** — Credentials chiffrés en DB

### Access Control
- **RBAC** — Super Admin > Tenant Admin > User
- **IP Whitelisting** — Par tenant
- **2FA** — TOTP pour tous les rôles
- **API Keys** — Par tenant avec scopes
- **Session Management** — Timeout, concurrent limit

### Compliance
- **RGPD** — Export, suppression, consentement
- **SOC 2** — Audit logs, access control
- **HIPAA** — Encryption, audit (optionnel)

---

## 7. 🔧 PROVIDER ADAPTER ARCHITECTURE

```php
interface StorageAdapter {
    // Connection
    public function testConnection(): array; // [success, message, latency]
    public function getInfo(): array; // [type, version, region, etc.]
    
    // CRUD
    public function list(string $path = ''): array;
    public function read(string $path): string;
    public function write(string $path, string $content, array $meta = []): bool;
    public function delete(string $path): bool;
    public function mkdir(string $path): bool;
    public function rename(string $old, string $new): bool;
    public function copy(string $src, string $dst): bool;
    
    // Info
    public function exists(string $path): bool;
    public function size(string $path): int;
    public function lastModified(string $path): int;
    public function mimeType(string $path): string;
    
    // Space
    public function usedSpace(): int;
    public function freeSpace(): int;
    public function totalSpace(): int;
    
    // Batch
    public function putContents(array $files): array; // [success, errors]
    public function deleteContents(array $paths): array;
}
```

### Adapter Implementations
```
app/
├── Services/
│   └── Storage/
│       ├── StorageAdapter.php (interface)
│       ├── AbstractAdapter.php (base class)
│       ├── LocalAdapter.php
│       ├── S3Adapter.php (Amazon, R2, MinIO, DO, Wasabi, B2)
│       ├── SftpAdapter.php
│       ├── FtpAdapter.php
│       ├── WebdavAdapter.php (Nextcloud, ownCloud)
│       ├── GdriveAdapter.php
│       ├── DropboxAdapter.php
│       ├── OnedriveAdapter.php
│       └── AdapterFactory.php (factory pattern)
├── Services/
│   ├── BackupService.php
│   ├── MountService.php
│   ├── SchedulerService.php
│   └── StorageService.php (orchestrator)
└── Controllers/
    └── AdminStorageController.php
```

---

## 8. 📋 API ENDPOINTS

### Providers
```
GET    /admin/storage/providers              List all
POST   /admin/storage/providers              Create
GET    /admin/storage/providers/{id}         Get details
PUT    /admin/storage/providers/{id}         Update
DELETE /admin/storage/providers/{id}         Delete
POST   /admin/storage/providers/{id}/test    Test connection
POST   /admin/storage/providers/{id}/sync    Force sync
```

### Backups
```
GET    /admin/storage/backups                List all
POST   /admin/storage/backups                Create backup
GET    /admin/storage/backups/{id}           Get details
DELETE /admin/storage/backups/{id}           Delete backup
POST   /admin/storage/backups/{id}/restore   Restore backup
GET    /admin/storage/backups/{id}/download  Download backup
```

### Schedules
```
GET    /admin/storage/schedules              List all
POST   /admin/storage/schedules              Create schedule
PUT    /admin/storage/schedules/{id}         Update schedule
DELETE /admin/storage/schedules/{id}         Delete schedule
POST   /admin/storage/schedules/{id}/run     Run now
```

### Mounts
```
GET    /admin/storage/mounts                 List all
POST   /admin/storage/mounts                 Create mount
PUT    /admin/storage/mounts/{id}            Update mount
DELETE /admin/storage/mounts/{id}            Delete mount
POST   /admin/storage/mounts/{id}/sync       Sync mount
GET    /admin/storage/mounts/{id}/browse     Browse remote
GET    /admin/storage/mounts/{id}/status     Sync status
```

### Tenant Storage
```
GET    /admin/tenants/{id}/storage           Tenant storage config
PUT    /admin/tenants/{id}/storage           Update tenant storage
GET    /admin/tenants/{id}/storage/usage     Usage stats
GET    /admin/tenants/{id}/storage/backups   Tenant backups
```

---

## 9. 🎨 ADMIN UI PAGES

### `/admin/storage` — Storage Overview
- KPI cards: Total providers, total storage used, active backups, mounts
- Provider cards with status indicators
- Quick actions: Add provider, run backup, browse mounts

### `/admin/storage/providers` — Provider Management
- Table with filters (type, status, default)
- Add/Edit modal with dynamic form per provider type
- Test connection button
- Usage stats per provider

### `/admin/storage/backups` — Backup Dashboard
- Calendar view of backups
- List with filters (tenant, type, status)
- Manual backup trigger
- Restore wizard
- Schedule configuration

### `/admin/storage/mounts` — Mount Manager
- Tree view of mounts
- Remote file browser (iframe/modal)
- Sync status and history
- Per-tenant mount management

### `/admin/storage/schedules` — Scheduler
- Timeline view
- Cron expression builder
- Run history
- Notification settings

### `/admin/tenants/{id}/storage` — Tenant Storage Config
- Storage quota
- Mount configuration
- Backup settings
- Usage graph
