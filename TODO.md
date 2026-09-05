# SAEC Cloud — TODO Complet (Architecture Octopus)

> SaaS multi-tenant · Architecture tentaculaire · Chaque feature = bras autonome
> Dernière mise à jour: 2026-09-04

---

## 🔴 CRITIQUE — En cours / Récemment fixé
- [x] Dashboard 500 : `deleted_at` sur table `folders` → fixé
- [x] Password change non fonctionnel : error handler 500 + pas de try/catch → fixé
- [x] Error handler convertissait warnings en 500 → fixé
- [x] Cookie consent banner RGPD → fixé (layout)
- [x] Page Politique de confidentialité (/privacy) → fixé
- [x] Page Conditions d'utilisation (/terms) → fixé
- [x] Export données personnelles (RGPD Art. 20) → fixé (GET /config/export)
- [x] Suppression compte (RGPD Art. 17) → fixé (POST /config/delete-account)
- [x] Mailer path fix : `app/views/emails/` → `app/Views/emails/` (case-sensitive Linux)

---

## 🟠 HAUT — Sécurité & RGPD
- [x] Rate limiting sur changement mot de passe (5/h max) → fixé
- [x] Complexité mot de passe renforcée (12+ chars) → fixé
- [ ] Vérification email nouvel utilisateur (email confirmation link)
- [ ] 2FA / TOTP optionnel (Google Authenticator / Authy)
- [ ] Rétention des logs audit configurables (par tenant)
- [ ] Politique de rétention corbeille (auto-purge après N jours)
- [ ] Export audit logs (CSV/JSON) pour conformité
- [ ] Headers security renforcés (X-Content-Type-Options, X-Frame-Options)
- [ ] Content Security Policy stricte (CSP)
- [ ] Permissions-Policy header
- [ ] Strict-Transport-Security (HSTS)
- [ ] X-XSS-Protection header
- [ ] Referrer-Policy header
- [ ] IP blacklist/whitelist par tenant
- [ ] Brute force protection login (lockout après N tentatives)
- [ ] Session fingerprinting (user-agent + IP binding)
- [ ] Anomaly detection (login from new location = email alert)
- [ ] Password history (interdire réutilisation des N derniers)
- [ ] Account lockout après inactivité (configurable)
- [ ] Audit trail complet (qui a fait quoi, quand, d'où)

---

## 💾 STORAGE OCTOPUS ARM — Hébergement Cloud/Remote

### Providers Object Storage (S3-compatible)
- [ ] **Amazon S3** — Adapter natif, SDK PHP
- [ ] **Cloudflare R2** — S3-compatible, zero egress, region=auto
- [ ] **MinIO** — Self-hosted, pathStyle=true
- [ ] **DigitalOcean Spaces** — Endpoint-based
- [ ] **Wasabi** — Endpoint-based, zero egress fee
- [ ] **Backblaze B2** — S3-compatible endpoint
- [ ] **Scaleway Object Storage** — EU datacenter
- [ ] **OVH Object Storage** — EU datacenter
- [ ] **Linode Object Storage** — S3-compatible
- [ ] **Vultr Object Storage** — S3-compatible
- [ ] **Hetzner Object Storage** — S3-compatible
- [ ] **IBM Cloud Object Storage** — S3-compatible
- [ ] **Seagate Lyve Cloud** — S3-compatible

### Providers Remote File Systems
- [ ] **SFTP/SSH** — phpseclib3, clés SSH/Ed25519
- [ ] **FTP** — natif PHP, passive mode
- [ ] **FTPS** — FTP + SSL/TLS
- [ ] **WebDAV** — sabre/dav, HTTP/HTTPS
- [ ] **NFS** — montage réseau local
- [ ] **CIFS/SMB** — montage Windows/Samba

### Providers Cloud APIs
- [ ] **Google Drive** — Google API PHP Client, OAuth2
- [ ] **Dropbox** — API v2, OAuth2
- [ ] **OneDrive** — Microsoft Graph API, OAuth2
- [ ] **Box.com** — REST API, OAuth2
- [ ] **pCloud** — REST API
- [ ] **Mega.nz** — REST API (protocole propriétaire)
- [ ] **Yandex Disk** — REST API

### Providers Self-hosted
- [ ] **Nextcloud** — WebDAV-compatible
- [ ] **ownCloud** — WebDAV-compatible
- [ ] **Seafile** — REST API
- [ ] **Syncthing** — REST API, P2P sync
- [ ] **FileRun** — WebDAV
- [ ] **Cloudreve** — REST API

### Providers NAS
- [ ] **Synology DSM** — REST API (WebStation, File Station)
- [ ] **QNAP QTS** — REST API (HybridMount)
- [ ] **TrueNAS** — REST API
- [ ] **Unraid** — REST API
- [ ] **OpenMediaVault** — REST API

### Modes de Storage
- [ ] **Primary Storage** — Fichiers stockés directement sur provider
- [ ] **Tiered Storage** — Chauds locaux → froids cloud (lifecycle)
- [ ] **Mirror** — Copie temps réel vers 1+ providers
- [ ] **Cache-Through** — Cache local + sync async cloud
- [ ] **Erasure Coding** — Redondance distribuée (type RAID cloud)

### Configuration Tenant Storage
- [ ] Quota configurable par tenant
- [ ] Redondance multi-region/provider
- [ ] Encryption AES-256-GCM avant upload
- [ ] Compression gzip/zstd avant upload
- [ ] Lifecycle rules (transition vers cold storage)
- [ ] Bandwidth throttling par tenant
- [ ] IOPS limits par tenant

---

## 🎯 STORAGE MULTI-SOURCE PAR TENANT (IDÉES 2026-09-04)

> Chaque client a sa propre config storage. Upload/download = transparent.
> Le tenant ne voit jamais d'où viennent ses fichiers.

### Architecture Multi-Source
- [ ] **Config par tenant** — admin assigne N providers par client (ex: client1=local+dropbox, client2=aws+local)
- [ ] **Routing intelligent upload** — écrit sur tous les providers assignés au tenant simultanément
- [ ] **Routing intelligent download** — lit depuis le provider le plus rapide/disponible
- [ ] **Transparent** — le tenant ne voit aucune différence, fichiers toujours accessibles
- [ ] **Quotas par provider par tenant** — ex: 10Go local + 50Go Dropbox

### Providers Supportés
- [ ] **Local** — stockage NAS (par défaut, obligatoire)
- [ ] **FTP/FTPS** — serveur distant classique
- [ ] **SFTP/SSH** — serveur distant sécurisé
- [ ] **Amazon S3** — SDK PHP natif
- [ ] **Cloudflare R2** — S3-compatible, zero egress
- [ ] **MinIO** — self-hosted S3
- [ ] **Dropbox** — API v2, OAuth2
- [ ] **Google Drive** — Google API PHP Client, OAuth2
- [ ] **OneDrive** — Microsoft Graph API, OAuth2
- [ ] **WebDAV** — Nextcloud, ownCloud, serveurs WebDAV
- [ ] **Backblaze B2** — S3-compatible
- [ ] **Wasabi** — S3-compatible, zero egress
- [ ] **DigitalOcean Spaces** — endpoint-based

### Sécurité Providers Externes
- [ ] **Chiffrement avant upload** — AES-256-GCM tout fichier envoyé externe
- [ ] **Clés API chiffrées en base** — jamais en clair, chiffrement AES master key
- [ ] **Dossier Dropbox sécurisé** — chiffré, lisible uniquement par le client
- [ ] **Token rotation OAuth2** — refresh automatique, jamais expiré
- [ ] **Audit log** — chaque opération storage logguée (upload/download/delete)

### Disponibilité
- [ ] **Cache local** — si provider distant indisponible, lecture depuis cache
- [ ] **Retry automatique** — backoff exponentiel sur échec
- [ ] **Status dashboard admin** — santé de chaque provider (latence, uptime)
- [ ] **Failover automatique** — si provider1 KO → bascule sur provider2

### Configuration Admin
- [ ] **Page admin: Providers** — lister, ajouter, modifier, supprimer providers
- [ ] **Page admin: Tenant Storage** — assigner des providers par tenant
- [ ] **Page admin: Status** — dashboard santé providers + quota utilisation
- [ ] **Migration providers** — transférer fichiers d'un provider à un autre (pull/push)

### Exemple Config Tenant
```
Tenant "ACME Corp" (Plan: Enterprise)
├── Provider 1: Local NAS (principal, 50Go)
├── Provider 2: Dropbox (backup, 200Go)
├── Provider 3: S3 (archivage cold, illimité)
└── Route: upload → local + Dropbox (mirror)
          download → local si dispo, sinon Dropbox
```

---

## 🔄 BACKUP & SYNC OCTOPUS ARM

### Types de Backup
- [ ] **Full Snapshot** — DB dump + tous les fichiers chiffrés
- [ ] **Incremental** — Depuis le dernier backup (Block-level)
- [ ] **Differential** — Depuis le dernier full
- [ ] **Files Only** — Uniquement les fichiers
- [ ] **DB Only** — Uniquement la base MySQL
- [ ] **Continuous (CDC)** — Change Data Capture en temps réel
- [ ] **Point-in-time** — WAL archiving pour restauration à l'instant T

### Pipeline de Backup
```
Dump DB → Compress (gzip/zstd) → Encrypt (AES-256-GCM) → Upload → Verify (checksum)
                              │
                         Archive (tar)
```

- [ ] **Dump DB** — mysqldump / SELECT INTO OUTFILE
- [ ] **Compress** — gzip (rapide) ou zstd (meilleur ratio)
- [ ] **Encrypt** — AES-256-GCM avec clé dédiée par backup
- [ ] **Upload** — Parallèle vers 1+ providers
- [ ] **Verify** — Checksum SHA-256 post-upload
- [ ] **Archive** — tar.gz pour groupement de fichiers

### Schedules de Backup
- [ ] **Créneau horaire** configurable (par défaut 02:00)
- [ ] **Fréquence** — Horaire, quotidien, hebdo, mensuel, custom cron
- [ ] **Rétention** — Auto-delete après N jours (configurable)
- [ ] **Rotation** — Garder les X derniers backups
- [ ] **Fenêtre de maintenance** — Pause pendant maintenance
- [ ] **Notifications** — Email/Webhook/Slack sur succès/échec
- [ ] **Alertes** — Si backup > X heures ou taille inattendue
- [ ] **Retry** — 3 tentatives avec backoff exponentiel

### Restore
- [ ] **Full Restore** — Tout restaurer (DB + fichiers)
- [ ] **Point-in-time** — Restaurer à un moment donné (WAL replay)
- [ ] **Selective** — Choisir quels fichiers/tables restaurer
- [ ] **Cross-tenant** — Restaurer un tenant dans un autre
- [ ] **Dry Run** — Simuler la restauration sans appliquer
- [ ] **Partial** — Restaurer uniquement les fichiers modifiés
- [ ] **Export** — Télécharger le backup pour restauration externe

### Sync
- [ ] **Push** — Local → Remote (backup temps réel)
- [ ] **Pull** — Remote → Local (import données)
- [ ] **Bidirectional** — Merge bidirectionnel
- [ ] **One-way Mirror** — Copie exacte, miroir
- [ ] **Delta Sync** — Transmettre uniquement les changements
- [ ] **Chunked Transfer** — Découpage pour gros fichiers

### Conflict Resolution
- [ ] **Last Write Wins** — Par défaut
- [ ] **Manual Review** — Conflits en attente admin
- [ ] **Versioning** — Garder les deux versions
- [ ] **Custom Rules** — Par extension/taille/dossier
- [ ] **Rename on Conflict** — Ajouter suffixe automatique

---

## 🌐 REMOTE MOUNT OCTOPUS ARM

### Types de Mount
- [ ] **ReadOnly** — Consultation uniquement
- [ ] **ReadWrite** — Édition complète
- [ ] **Backup Only** — Copie de sécurité
- [ ] **Sync** — Synchronisation bidirectionnel
- [ ] **Cache** — Mount avec cache local

### Opérations Distance
- [ ] **Browse** — Lister fichiers/dossiers
- [ ] **Preview** — Prévisualiser (images, PDF, texte)
- [ ] **Download** — Télécharger
- [ ] **Upload** — Uploader (si ReadWrite)
- [ ] **Delete** — Supprimer (si ReadWrite)
- [ ] **Rename** — Renommer (si ReadWrite)
- [ ] **Move** — Déplacer (si ReadWrite)
- [ ] **Mkdir** — Créer dossier (si ReadWrite)
- [ ] **Search** — Rechercher dans le mount
- [ ] **Metadata** — Lire/modifier les métadonnées

### Mount Scenarios
```
┌─────────────────────────────────────────────────────────┐
│                    TENANT "ACME Corp"                    │
├─────────────────────────────────────────────────────────┤
│  /files/          ← Stockage local (chiffré AES-256)   │
│  /remote/s3/      ← Mount S3 bucket (lecture seule)    │
│  /remote/sftp/    ← Mount VPS client (lecture/écriture)│
│  /remote/nextcloud/ ← Mount Nextcloud (sync bidir)     │
│  /backup/         ← Backups automatisés                 │
│  /archive/        ← Archive cold storage (S3 Glacier)  │
└─────────────────────────────────────────────────────────┘
```

### Sync Engine
- [ ] **Real-time** — Inotify/FSEvents → sync immédiat
- [ ] **Polling** — Vérification périodique (configurable)
- [ ] **Webhook** — Push notification du remote
- [ ] **Delta Detection** — Comparaison checksum/timestamp
- [ ] **Queue** — File d'attente pour opérations batch
- [ ] **Retry** — Backoff exponentiel sur échec
- [ ] **Dedup** — Détection doublons par checksum

### Conflict Handling
- [ ] **Strategy configurable** — Par mount
- [ ] **Lock Files** — Verrouillage pendant édition
- [ ] **Version History** — Garder l'historique des versions
- [ ] **Merge Support** — Pour fichiers texte
- [ ] **Alert Admin** — Email sur conflit non résolu

---

## 📁 TENANT MANAGEMENT OCTOPUS ARM

### Hierarchy
```
Super Admin (master)
├── Tenant "ACME Corp" (Plan: Enterprise)
│   ├── User: admin@acme.com (role: admin)
│   ├── User: user1@acme.com (role: user)
│   ├── Storage: 100GB local + 500GB S3 mount
│   ├── Backups: Daily at 02:00 → R2
│   ├── Mounts: /remote/acme-backup → SFTP
│   └── Modules: [files, shares, versioning]
├── Tenant "Startup XYZ" (Plan: Professional)
│   └── ...
```

### Tenant Features (Par Plan)
- [ ] **Starter** — 10GB, 1 user, pas de mount, backup weekly
- [ ] **Professional** — 100GB, 5 users, 2 mounts, backup daily
- [ ] **Enterprise** — Illimité, 15 users, mounts illimités, backup continue
- [ ] **Custom** — Tout configurable

### Tenant CRUD
- [ ] Créer tenant (admin)
- [ ] Modifier tenant (admin)
  - [ ] Nom / raison sociale
  - [ ] Email contact principal
  - [ ] Quota stockage (correction erreurs)
  - [ ] Nombre max users (ajustement)
  - [ ] Plan (upgrade/downgrade)
  - [ ] Date expiration abonnement
  - [ ] Features activées/désactivées
  - [ ] Config storage (changement provider)
  - [ ] Notes internes admin (debug, suivi)
- [ ] Suspendre tenant (admin)
- [ ] Réactiver tenant (admin)
- [ ] Supprimer tenant (admin, soft-delete)
- [ ] Prolonger abonnement (admin)
- [ ] Changer de plan (admin)
- [ ] Exporter données tenant (RGPD)
- [ ] Cloner tenant (admin, pour demo/dev)

### User Management
- [ ] Créer user (admin tenant ou super admin)
- [ ] Modifier user
- [ ] Désactiver user
- [ ] Réinitialiser mot de passe (admin → email)
- [ ] Changer de rôle
- [ ] Supprimer user
- [ ] Lister les sessions actives
- [ ] Révoquer session individuelle

---

## 📊 MONITORING OCTOPUS ARM

### Métriques Collectées
- [ ] **Storage** — Usage par tenant, par provider, total, tendance
- [ ] **Backups** — Succès, échecs, taille, durée, fréquence
- [ ] **Mounts** — Sync status, latence, erreurs, bande passante
- [ ] **API** — Requêtes, erreurs, latence, par endpoint
- [ ] **Security** — Login, tentatives, IPs suspectes, anomalies
- [ ] **Performance** — Uptime, response time, error rate

### Dashboards
- [ ] **Overview** — Métriques globales (KPI cards)
- [ ] **Per-Tenant** — Détails par client
- [ ] **Storage** — Répartition, tendances, projections
- [ ] **Backups** — Historique, prochains, succès/échecs
- [ ] **Security** — Alertes, audit, anomalies
- [ ] **Performance** — Latence, throughput, errors

### Notifications
- [ ] **Email** — Sur événements critiques
- [ ] **Webhook** — Intégration externe (Zapier, IFTTT)
- [ ] **Slack/Discord** — Alertes équipe
- [ ] **SMS** — Urgences (via provider Twilio/Vonage)
- [ ] **Push** — Notification navigateur/mobile

---

## 🔐 SECURITY OCTOPUS ARM

### Encryption
- [ ] **At Rest** — AES-256-GCM tous les fichiers
- [ ] **In Transit** — TLS 1.3 toutes connexions
- [ ] **Backup** — Chiffrement séparé avant upload
- [ ] **Provider Config** — Credentials chiffrés en DB
- [ ] **Client-side** — Zero-knowledge optionnel (futur)
- [ ] **Key Rotation** — Rotation automatique des clés

### Access Control
- [ ] **RBAC** — Super Admin > Tenant Admin > User
- [ ] **IP Whitelisting** — Par tenant
- [ ] **2FA** — TOTP pour tous les rôles
- [ ] **API Keys** — Par tenant avec scopes
- [ ] **Session Management** — Timeout, concurrent limit
- [ ] **OAuth2** — SSO avec providers externes (futur)

### Compliance
- [ ] **RGPD** — Export, suppression, consentement
- [ ] **SOC 2** — Audit logs, access control
- [ ] **HIPAA** — Encryption, audit (optionnel)
- [ ] **ISO 27001** — Politiques de sécurité (futur)
- [ ] **PCI DSS** — Si paiements gérés (futur)

---

## 🔧 EMAIL SYSTEM OCTOPUS ARM

### Emails Transactionnels
- [x] **Welcome email** — Credentials lors création user (MailerSend)
- [ ] **Password reset** — Lien réinitialisation
- [ ] **Email verification** — Confirmation adresse email
- [ ] **2FA codes** — Code TOTP par email (backup)
- [ ] **Share notification** — "X a partagé un fichier avec vous"
- [ ] **Tenant expiry** — "Votre accès expire dans N jours"
- [ ] **Quota alert** — "Votre espace est utilisé à X%"
- [ ] **Backup success/failure** — Notification status backup
- [ ] **New login** — "Nouveau login depuis [device/IP]"
- [ ] **Weekly digest** — Résumé activité hebdomadaire

### Email Templates
- [ ] Template premium dark theme (branding SAEC)
- [ ] Responsive (mobile-friendly)
- [ ] Variables personnalisables par tenant
- [ ] Unsubscribe link (RGPD)
- [ ] Track ouverture / clic (analytics)

### Email Providers
- [x] **MailerSend** — Provider principal
- [ ] **SendGrid** — Backup provider
- [ ] **Amazon SES** — Alternative économique
- [ ] **Mailgun** — Alternative
- [ ] **Postmark** — Alternative

---

## 🟡 DESIGN PREMIUM — Toutes pages

### Public Pages
- [x] Login page → premium ✅
- [x] Landing page → premium ✅
- [x] Pricing page → premium ✅
- [x] Subscribe page → premium ✅
- [x] Privacy page → premium ✅
- [x] Terms page → premium ✅
- [x] 404 page → premium ✅

### Authenticated Pages
- [x] Dashboard user → premium ✅
- [x] Settings/Config → premium ✅
- [ ] Files page → review design (folder modal premium)
- [ ] Shares page → review design

### Admin Pages
- [ ] Admin dashboard → review design
- [ ] Admin tenants → review design
- [ ] Admin users → review design
- [ ] Admin audit → review design
- [ ] Admin config → review design
- [ ] Admin modules → review design
- [ ] Admin billing → review design
- [ ] Admin storage providers → NEW (octopus arm)
- [ ] Admin storage backups → NEW (octopus arm)
- [ ] Admin storage mounts → NEW (octopus arm)
- [ ] Admin storage schedules → NEW (octopus arm)
- [ ] Admin tenant storage config → NEW (octopus arm)

### Design System
- [ ] Consistent spacing/margins
- [ ] Consistent color usage
- [ ] Consistent typography
- [ ] Consistent component styles
- [ ] Micro-animations (hover, click, transitions)
- [ ] Skeleton loading states
- [ ] Empty states (no data)
- [ ] Error states (failed loads)
- [ ] Success states (completed actions)

---

## 🟢 FONCTIONNALITÉS MANQUANTES

### Files Manager
- [ ] Recherche fichiers globale (Ctrl+K)
- [ ] Partage par email (pas juste lien)
- [ ] Notifications temps réel
- [ ] Versioning fichiers (UI)
- [ ] Preview inline (images, PDF, code, vidéo)
- [ ] Drag & drop upload amélioré
- [ ] Multi-sélection fichiers (Ctrl+Click, Shift+Click)
- [ ] Batch actions (supprimer, déplacer, télécharger, zipper)
- [ ] Raccourcis clavier globaux (Ctrl+X, Ctrl+C, Ctrl+V, Del)
- [ ] Cut/Paste fichiers
- [ ] Fichiers récents (derniers accédés)
- [ ] Favoris/épinglés fichiers
- [ ] Tags/labels fichiers
- [ ] Métadonnées fichiers (EXIF, auteur, etc.)
- [ ] Commentaires fichiers
- [ ] Activité fichier (qui a fait quoi, quand)

### Sharing
- [ ] Partage par lien avec password
- [ ] Partage par email avec permissions
- [ ] Partage expiration automatique
- [ ] Partage avec watermark (images/PDF)
- [ ] Partage chiffré (zero-knowledge)
- [ ] Partage temporaire (one-time link)
- [ ] Partage avec upload invité
- [ ] Statistiques de téléchargement
- [ ] Notification sur téléchargement

### Collaboration
- [ ] Commentaires sur fichiers
- [ ] Annotations images
- [ ] Historique versions (UI)
- [ ] Diff entre versions
- [ ] Restauration version spécifique
- [ ] Lock fichiers (verrouillage édition)
- [ ] Activité récente (feed)

### User Experience
- [ ] Dark/Light theme toggle
- [ ] Profil utilisateur éditable (photo, bio)
- [ ] Préférences utilisateur (langue, thème, notifications)
- [ ] Onboarding wizard (première utilisation)
- [ ] Tooltips contextuels
- [ ] Keyboard shortcuts modal (?)
- [ ] Command palette (Ctrl+K)

---

## 🔵 PERFORMANCE

### Frontend
- [ ] Gzip/Brotli compression
- [ ] Cache headers (ETag, Last-Modified, Cache-Control)
- [ ] Lazy loading images
- [ ] Minification CSS/JS
- [ ] CDN pour assets statiques
- [ ] Critical CSS inline
- [ ] Preload ressources critiques
- [ ] Service Worker (offline support)
- [ ] Image optimization (WebP, AVIF)

### Backend
- [ ] Pagination côté serveur pour gros volumes
- [ ] Index SQL sur colonnes fréquemment query
- [ ] Connection pooling MySQL
- [ ] Redis/Memcache pour sessions
- [ ] Query cache
- [ ] Response caching (Varnish/Redis)
- [ ] Connection pooling
- [ ] Async processing (queues)
- [ ] Rate limiting (API)

### Infrastructure
- [ ] HTTP/2 ou HTTP/3
- [ ] TLS 1.3
- [ ] OCSP Stapling
- [ ] Brotli compression
- [ ] Edge caching (Cloudflare)
- [ ] Load balancing (si multi-server)

---

## 🟣 CONCURRENCE — Features à copier/améliorer

### Zero-Knowledge / Encryption
- [ ] Tresorit : zero-knowledge architecture
- [ ] Filen : client-side encryption
- [ ] Proton Drive : encrypted sharing
- [ ] Sync.com : zero-knowledge + sharing

### Storage Features
- [ ] pCloud : lifetime plans
- [ ] Internxt : open-source clients
- [ ] Syncthing : sync local-first
- [ ] Resilio Sync : P2P高性能

### Admin Features
- [ ] Nextcloud : admin audit log complet
- [ ] ownCloud : federation sharing
- [ ] Seafile : library-based organization
- [ ] FileRun : rich preview system

### Business Features
- [ ] Dropbox Business : admin console
- [ ] Box Enterprise : compliance tools
- [ ] Egnyte : governance

---

## 📋 BACKLOG — Features Futures

### Apps & Clients
- [ ] Desktop app (Electron/Tauri)
- [ ] Mobile app (React Native)
- [ ] Browser extension (upload rapide)
- [ ] CLI tool (upload/download/sync)
- [ ] API REST complète (documentée)

### Advanced Features
- [ ] Collaborative editing (docs, spreadsheets)
- [ ] Versioning avancé (diff, restauration, branching)
- [ ] Metadata extraction automatique (EXIF, OCR)
- [ ] AI tagging/indexing (facultatif)
- [ ] Full-text search dans fichiers
- [ ] Audio/Video streaming
- [ ] Image gallery mode
- [ ] PDF viewer inline
- [ ] Code viewer (syntax highlighting)

### Integration
- [ ] WebDAV server (exposer le SaaS comme WebDAV)
- [ ] S3-compatible API (exposer comme S3)
- [ ] FTP server (exposer comme FTP)
- [ ] Rsync-compatible (exposer comme rsync target)

### Automation
- [ ] Webhooks (on upload, on share, on delete)
- [ ] Zapier/IFTTT integration
- [ ] GitHub/GitLab sync
- [ ] CI/CD integration
- [ ] Auto-import from email attachments

### Analytics
- [ ] Storage analytics (tendances, projections)
- [ ] User activity analytics
- [ ] File analytics (most viewed, most shared)
- [ ] Cost analytics (cloud storage spend)

### Compliance & Legal
- [ ] DLP (Data Loss Prevention)
- [ ] Legal hold ( Preservation légale)
- [ ] E-discovery
- [ ] Retention policies avancées
- [ ] Audit report export (PDF)

### Multi-tenancy Avancé
- [ ] Tenant impersonation (admin → tenant view)
- [ ] Tenant cloning (for demo/dev)
- [ ] Tenant migration (move between servers)
- [ ] Tenant federation (share between tenants)
- [ ] White-label (custom branding per tenant)

### Mobile
- [ ] iOS app native
- [ ] Android app native
- [ ] Photo backup automatique
- [ ] Offline sync (selective)
- [ ] Share sheet integration

---

## 🗂️ ARCHITECTURE FILES

### Structure cible
```
app/
├── Controllers/
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── FileController.php
│   ├── FolderController.php
│   ├── ShareController.php
│   ├── SettingsController.php
│   └── Admin/
│       ├── AdminController.php
│       ├── AdminStorageController.php (NEW)
│       └── AdminTenantStorageController.php (NEW)
├── Services/
│   ├── Storage/
│   │   ├── StorageAdapter.php (interface)
│   │   ├── AbstractAdapter.php
│   │   ├── LocalAdapter.php
│   │   ├── S3Adapter.php
│   │   ├── SftpAdapter.php
│   │   ├── FtpAdapter.php
│   │   ├── WebdavAdapter.php
│   │   ├── GdriveAdapter.php
│   │   ├── DropboxAdapter.php
│   │   ├── OnedriveAdapter.php
│   │   └── AdapterFactory.php
│   ├── BackupService.php
│   ├── MountService.php
│   ├── SchedulerService.php
│   ├── EmailService.php
│   └── NotificationService.php
├── Models/
│   ├── StorageProvider.php
│   ├── StorageBackup.php
│   ├── StorageMount.php
│   ├── StorageSchedule.php
│   └── TenantStorage.php
├── Views/
│   ├── admin/
│   │   ├── storage/
│   │   │   ├── providers.php
│   │   │   ├── backups.php
│   │   │   ├── mounts.php
│   │   │   └── schedules.php
│   │   └── tenant-storage.php
│   └── emails/
│       ├── welcome.php
│       ├── password-reset.php
│       ├── backup-success.php
│       ├── backup-failure.php
│       ├── new-login.php
│       └── quota-alert.php
├── Middleware/
│   ├── AuthMiddleware.php
│   ├── AdminMiddleware.php
│   └── StorageMiddleware.php (NEW)
└── Core/
    ├── Database.php
    ├── Encryption.php
    ├── Mailer.php
    ├── Router.php
    └── Session.php

config/
├── routes.php
└── storage.php (NEW - default storage config)

database/
├── migrations/
│   ├── 001_initial.sql
│   ├── 002_*.sql
│   └── 003_cloud_integration.sql (NEW)
└── seeds/

storage/
├── uploads/ (local files)
├── backups/ (local backup cache)
├── temp/ (temporary files)
└── logs/

docs/
├── ARCHITECTURE-OCTOPUS.md
├── CLOUD-INTEGRATION-PLAN.md
└── API.md
```

---

## 📊 MATRICE DE DÉPENDANCES

```
Phase 1: Core Infrastructure
├── DB Schema (3 tables)
├── Storage Adapter Interface
├── S3 Adapter
└── Admin Controller + Routes

Phase 2: Backup Engine
├── Backup Service
├── DB Dump Utility
├── File Archive Utility
├── Compress + Encrypt
└── Backup Scheduler

Phase 3: Mount Engine
├── Mount Service
├── Remote Browsing
├── Sync Engine
└── Conflict Resolution

Phase 4: Admin UI
├── Storage Providers Page
├── Backups Dashboard
├── Mounts Manager
├── Scheduler Config
└── Tenant Storage Config

Phase 5: Additional Providers
├── SFTP Adapter
├── FTP Adapter
├── WebDAV Adapter
├── Google Drive Adapter
├── Dropbox Adapter
└── OneDrive Adapter

Phase 6: Email System
├── Email Service
├── Templates
├── SendGrid/SES Backup
└── Analytics

Phase 7: Monitoring
├── Metrics Collector
├── Dashboards
├── Alerts
└── Notifications

Phase 8: Advanced Features
├── Zero-knowledge encryption
├── Client-side encryption
├── Collaborative editing
├── AI tagging
└── Full-text search
```

---

## ⏱️ ESTIMATIONS

| Phase | Complexity | Estimation |
|-------|-----------|------------|
| Phase 1: Core Infrastructure | Moyen | 2-3 jours |
| Phase 2: Backup Engine | Complexe | 3-5 jours |
| Phase 3: Mount Engine | Complexe | 3-5 jours |
| Phase 4: Admin UI | Moyen | 3-4 jours |
| Phase 5: Additional Providers | Complexe | 4-6 jours |
| Phase 6: Email System | Moyen | 2-3 jours |
| Phase 7: Monitoring | Moyen | 2-3 jours |
| Phase 8: Advanced | Très complexe | 5-10 jours |
| **TOTAL** | | **24-39 jours** |
