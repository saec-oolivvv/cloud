# SAEC CLOUD — MÉMOIRE DE CONTEXTE COMPLÈTE

> Dernière mise à jour: 2026-09-03
> Version: 1.0.0-alpha

---

## 1. IDENTITÉ DU PROJET

**Nom:** SAEC Cloud
**Type:** SaaS multi-tenant de stockage sécurisé (type Dropbox/Nextcloud)
**Propriétaire:** SAEC Ltd. — Bespoke Systems Engineering
**Domaine:** `cloud.saec.me`
**Stack:** PHP 8.3+, MySQL 8.x, Apache 2.4, Synology DS420j NAS

---

## 2. INFRASTRUCTURE

| Composant | Valeur |
|---|---|
| NAS Web | Synology DS420j (192.168.0.201) |
| NAS MySQL | Synology (192.168.0.133) |
| Base de données | `saec_cloud` |
| User MySQL | `saec_app` |
| Pass MySQL | `DUEVlJKQAYZ/5vmaF8Xw3ETG` |
| Admin email | `admin@saec.me` |
| Admin pass | `changeme` |
| Email | `cloud@saec.me` via MailerSend |
| DNS | Cloudflare (proxy orange) |
| SSL | Cloudflare Full Strict |

---

## 3. ARCHITECTURE

### Structure MVC
```
cloud/
├── public/          ← DocumentRoot (index.php)
│   ├── css/app.css
│   ├── js/app.js
│   ├── img/         ← logo.png, favicons
│   └── deploy.php   ← Auto-destruct
├── app/
│   ├── Controllers/ ← Auth, File, Folder, Share, Admin, Dashboard
│   ├── Views/       ← PHP templates
│   │   ├── layouts/main.php
│   │   ├── auth/
│   │   ├── files/
│   │   ├── shares/
│   │   ├── admin/
│   │   └── dashboard/
│   └── Middleware/   ← Auth, Admin
├── core/            ← Services partagés
│   ├── Database.php
│   ├── Encryption.php (AES-256-GCM)
│   ├── Security.php
│   ├── Session.php
│   ├── JWTHandler.php
│   ├── Router.php
│   ├── Validator.php
│   ├── Mailer.php
│   ├── Container.php (DI)
│   ├── ModuleManager.php
│   ├── AbstractModule.php
│   ├── ModularRouter.php
│   └── Interfaces/
│       ├── DatabaseInterface.php
│       ├── EncryptionInterface.php
│       ├── SecurityInterface.php
│       ├── AuthInterface.php
│       ├── AuditInterface.php
│       ├── ModuleInterface.php
│       └── ContainerInterface.php
├── Modules/         ← Modules isolés
│   ├── Auth/
│   ├── Storage/
│   ├── Folders/
│   ├── Sharing/
│   ├── Admin/
│   ├── Sync/
│   └── Audit/
├── config/routes.php
├── storage/
│   ├── x*.conf      ← Config (644)
│   ├── keys/master.key (644)
│   ├── uploads/{tenant_id}/
│   └── logs/error.log
├── sql/
│   ├── 01-create-database.sql
│   ├── 02-create-tables.sql
│   ├── 03-seed-data.sql
│   └── 04-tenant-scheduling.sql
└── VERSION          ← 1.0.0-alpha
```

### Architecture Modulaire
- **Core:** Services partagés (Database, Auth, Encryption, Security, Audit)
- **Modules:** Chaque module est isolé, injecte le core via DI Container
- **Interfaces:** Contrats pour chaque service (swap possible)
- **Container:** Dependency Injection automatique
- **ModuleManager:** Charge, boot, et gère les modules

### Règles Modulaires
1. Un module N'IMPORTERA JAMAIS directement un autre module
2. Un module utilise UNIQUEMENT les interfaces du core
3. Ajouter un module ne CASSE jamais existant
4. Chaque module a: Controllers/, Services/, Views/, routes.php
5. Le ModuleManager gère l'ordre de boot selon les dépendances

---

## 4. SÉCURITÉ

### Chiffrement
- **Fichiers:** AES-256-GCM (clé par fichier, stockée en DB)
- **Passwords:** Argon2ID
- **JWT:** HS256 avec secret rotatif
- **Transport:** TLS 1.3 (Cloudflare)

### Protection
- Rate limiting: 5 tentatives / 15min, lockout auto
- IP blacklist (auto + manuelle)
- Session tracking (user_sessions)
- CSRF tokens sur tous formulaires
- API tokens 64 bits par user
- Audit log complet (toute action critique)

### Headers Sécurité
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), camera=(), microphone=()
HSTS: max-age=31536000; includeSubDomains
CSP: strict (self, fonts, cloudflare only)
```

---

## 5. BASE DE DONNÉES

### Tables
| Table | Description |
|---|---|
| tenants | Organisations (quotas, dates, features) |
| users | Utilisateurs (email, password, role, 2FA) |
| files | Fichiers (chiffrés, metadata, folder_id) |
| folders | Dossiers (arborescent, path, parent_id) |
| shares | Partages (lien token, expiry, password) |
| file_versions | Historique versions |
| notifications | Notifications users |
| audit_logs | Journal d'audit (immutable) |
| encryption_keys | Clés de chiffrement par tenant |
| api_tokens | Tokens API 64 bits |
| login_attempts | Tentatives connexion |
| ip_blacklist | IP bannies |
| user_sessions | Sessions actives |
| password_resets | Tokens reset password |
| system_config | Config globale |
| migrations | Historique migrations |

---

## 6. BRANDING

### Palette (identique saec.me)
```css
--navy-950: #040918    /* fond principal */
--navy-900: #0a0f1e    /* header/footer */
--accent: #00ff88      /* vert SAEC */
--accent-purple: #a855f7
--accent-cyan: #06b6d4
--accent-pink: #ec4899
--text-primary: #ffffff
--text-secondary: rgba(255,255,255,0.7)
--text-muted: rgba(255,255,255,0.4)
--border: rgba(255,255,255,0.08)
```

### Typography
- Body: Inter (Google Fonts)
- Code: JetBrains Mono
- Icons: FontAwesome 6.5

### Éléments Visuels
- Glow blobs animés (fond)
- Grid pattern subtil
- Status bar en haut: `SAEC_CLOUD // STATUS: NOMINAL // LOCATION: LONDON`
- Header sticky avec logo + nav
- Scanline verte sous header
- Cards avec glow border au hover
- Footer: `© 2026 SAEC Ltd. — Bespoke Systems Engineering`

---

## 7. TARIFICATION (À CONSTRUIRE)

### 3 Options Prévues

| Feature | Starter | Professional | Enterprise |
|---|---|---|---|
| Stockage | 10 GB | 100 GB | Illimité |
| Users max | 5 | 25 | Illimité |
| Fichiers max | 500 | 5 000 | Illimité |
| Taille max/fichier | 1 GB | 10 GB | 50 GB |
| Partages externes | ✓ | ✓ | ✓ |
| Chiffrement E2E | ✓ | ✓ | ✓ |
| Dossiers team | ✗ | ✓ | ✓ |
| Versions fichiers | 7 jours | 30 jours | Illimité |
| Corbeille | 30 jours | 30 jours | 90 jours |
| Support | Email | Prioritaire | Dédié |
| API access | ✗ | ✓ | ✓ |
| Audit logs | 7 jours | 90 jours | Illimité |
| Branding custom | ✗ | ✗ | ✓ |
| SLA | ✗ | 99.9% | 99.99% |
| **Prix/mois** | **€9** | **€29** | **Sur devis** |

### Flux de Souscription
1. User visite `cloud.saec.me/pricing`
2. Sélectionne une option
3. Remplit le questionnaire (nom, entreprise, besoins)
4. Soumet → email envoyé à `cloud@saec.me`
5. Admin traite la demande
6. Admin crée le tenant + user
7. Email d'invitation envoyé au user
8. **Future:** Intégration Stripe/PayPal pour paiement direct

---

## 8. FONCTIONNALITÉS

### Terminé
- [x] MVC structure complète
- [x] Auth (login, logout, JWT, sessions)
- [x] Dashboard user + admin
- [x] Upload fichiers chiffrés AES-256-GCM
- [x] Download déchiffré
- [x] Soft delete + corbeille
- [x] Restore depuis corbeille
- [x] Partages (liens token)
- [x] Rate limiting + IP blacklist
- [x] API tokens 64 bits
- [x] Audit log
- [x] Branding saec.me (logo, favicon, CSS)
- [x] Migration auto-destruct system
- [x] Architecture modulaire (interfaces, DI, modules)
- [x] Tenant isolation (storage/uploads/{tenant_id}/)
- [x] Email verification (confirmation link 24h)
- [x] 2FA TOTP (Google Authenticator / Authy)
- [x] Session fingerprinting (user-agent + IP binding)
- [x] Anomaly detection (login from new location = email alert)
- [x] Password history (prevent reuse of last 5)
- [x] Account lockout after inactivity (configurable per user)
- [x] Trash retention (auto-purge per tenant)
- [x] Audit log retention (configurable per tenant)
- [x] IP whitelist per tenant
- [x] Password reset (email link, 1h expiry)
- [x] Email templates: welcome, password-reset, email-verification, 2fa, share-notification, tenant-expiry, quota-alert, backup-success, backup-failure, new-login
- [x] Notifications system (in-app + email)
- [x] Storage metrics API (per tenant, by type, recent uploads)
- [x] S3 provider presets (AWS, Cloudflare R2, MinIO, DigitalOcean, Wasabi, Backblaze, Scaleway, OVH, Linode, Hetzner)
- [x] Storage providers: Local, S3, SFTP, FTP, WebDAV, Google Drive, Dropbox, OneDrive

### En Cours
- [ ] Migration complète (folders + security)
- [ ] Folders CRUD (arborescent)
- [ ] Page pricing + questionnaire souscription
- [ ] Client sync bidirectionnelle
- [ ] Sync API endpoints
- [ ] Upload direct vers serveur distant (provider attaché au tenant)
- [ ] Drag & drop de dossiers (upload + création sous-dossiers)

### Prévu
- [ ] Paiement Stripe/PayPal
- [ ] Client desktop (Tauri)
- [ ] Recherche full-text (Meilisearch)
- [ ] Replication NAS distant
- [ ] 2FA TOTP
- [ ] Webhooks
- [ ] API publique v2

---

## 9. COMMANDES IMPORTANTES

### Migration
```
https://cloud.saec.me/migrate-complete.php
```

### Deploy (auto-destruct)
```
https://cloud.saec.me/deploy.php
```

### Health check
```
https://cloud.saec.me/health.php
```

### Fix permissions (si Apache plante)
```bash
chmod 755 /mnt/nas-web/cloud/storage
chmod 644 /mnt/nas-web/cloud/storage/x*.conf
chmod 644 /mnt/nas-web/cloud/storage/keys/master.key
```

---

## 10. BUGS CONNUS & FIXES

| Bug | Fix |
|---|---|
| Config non trouvée | Permissions storage/ → 755, config → 644 |
| `deleted_at` unknown | Exécuter migrate-complete.php |
| `created_by` inconnu dans shares | Remplacé par `user_id` |
| `link_token` inconnu | Remplacé par `share_token` |
| Upload permission denied | storage/uploads/{tenant_id}/ → 770 |
| Views minuscules | Renommé en Controllers/, Views/, Middleware/ |
| Controller.php mal placé | Déplacé dans Controllers/ |

---

## 11. NOTES UTILISATEUR

- L'utilisateur est technique mais ne code pas
- Préfère les solutions auto-destruct (pas de .sh, pas de .env)
- Mot de passe admin: `changeme` (doit être changé)
- Le serveur MySQL est séparé (192.168.0.133)
- Cloudflare gère le DNS + SSL + WAF
- Pas de paiement direct pour le moment
- Les demandes de tenant passent par un questionnaire
- Facture envoyée après validation admin

---

## 12. SESSION 2026-09-15 — BUGS & DEMANDES UTILISATEUR (À TRAITER)

> Liste non exhaustive — ne rien perdre. Priorité: HIGH → MEDIUM.

### Bugs signalés par l'utilisateur
1. **Upload fichiers impossible** — `mkdir(): Permission denied` sur `storage/uploads/{tenant_id}/` (Apache pas writable).
   - Fix partiel fait: `chmod 777 storage/uploads/` + code `mkdir(0777)` dans FileController/FolderController.
   - **EN ATTENTE**: PHP `upload_max_filesize=2M`, `post_max_size=8M` → trop petit (config app → 100MB). À augmenter côté serveur (php.ini ou .user.ini).
2. **Suppression dossier impossible** — `DELETE /folders/{id}` avec body multipart + `parse_str()` sur `php://input` ne parse PAS le multipart → CSRF token introuvable → 403 "Token CSRF invalide".
   - Fix à faire: envoyer token via header `X-CSRF-Token` (déjà supporté côté PHP) au lieu du body FormData.
3. **Dossiers tous niveau 1, pas de sous-dossiers** — à investiguer: création de dossier dans un dossier ≠ sauvegarde `parent_id`.
4. **Upload sans CSRF token** — `xhr.open('POST','/files/upload')` dans `app/Views/files/index.php` (~ligne 658) et `public/js/app.js` (ligne 64) n'envoient PAS de token CSRF. (Controller ne vérifie pas encore le CSRF sur upload → à ajouter).

### Demandes features utilisateur
5. **Upload direct vers serveur distant** — tous fichiers/dossiers créés ou envoyés doivent aller DIRECTEMENT sur le provider storage attaché au tenant (`storage_mounts`). Actuellement upload → local `storage/uploads/{tenant_id}/` uniquement. Le sync direct vers distant est "ne se fait pas".
6. **Drag & drop de dossiers** — dans le SaaS permettre glisser-déposer des dossiers (et idéalement créer les sous-dossiers automatiquement).
7. **Lien menu → download sync client** — ajouter un lien direct dans le menu pour télécharger le programme de synchronisation déjà buildé.
   - Builds existants: `saec-sync/target/release/bundle/deb/SAEC Sync_0.1.0_amd64.deb` (11MB), `rpm/...x86_64.rpm` (11MB), `appimage/SAEC Sync.AppDir/`, binaire `target/release/saec-sync`.
   - Copié vers `public/download/saec-sync-0.1.0-amd64.deb` + `public/download/saec-sync-0.1.0-x86_64.rpm`.
8. **Sync client API endpoints** — le client Tauri (`saec-sync/src-tauri/src/api/client.rs`) appelle: `GET /sync/mounts`, `GET /sync/mounts/{id}/files{path}`, `GET /sync/mounts/{id}/files/{path}/content`, `PUT /sync/mounts/{id}/files{path}` (upload checksum), `DELETE /sync/mounts/{id}/files{path}`, `POST /sync/mounts/{id}/files{path}` (create folder), `POST /api/auth/device` (device code), `POST /api/auth/token` (token). → **AUCUN de ces endpoints n'existe dans `config/routes.php`**. À implémenter côté backend.
9. **Sync bidirectionnelle** — le client se connecte sur `https://cloud.saec.me/api`, token via device-code flow. Config par défaut dans `saec-sync/src-tauri/src/config.rs`.

### Fichiers clés analysés
- `app/Controllers/FileController.php` — upload/download/delete fichiers, chiffrement AES-256-GCM
- `app/Controllers/FolderController.php` — CRUD dossiers + index files
- `app/Views/files/index.php` — file browser (upload modal, drag&drop, folder actions)
- `public/js/app.js` — upload zone (dashboards)
- `app/Services/Storage/StorageAdapter.php` — interface adapters (write/read/mkdir/delete/rename/copy/stream)
- `app/Services/Storage/LocalAdapter.php` — adapter local
- `app/Services/MountService.php` — sync engine mounts (upload/download/deleteRemote/sync)
- `app/Services/StorageService.php` — orchestration providers
- `saec-sync/` — client Tauri (Rust) buildé OK

---

## 13. SESSION 2026-09-15 (SUITE) — NOUVEAUX BUGS & FEATURES OCTOPUS

> Suite de la session — correction des retours utilisateur temps réel.

### Nouveaux bugs identifiés
1. **Move folder ne fonctionne pas** — endpoint `POST /folders/{id}/move` manquant + UI drag&drop entre dossiers absente.
2. **Page `/download/` 403 Cloudflare** — route existe, view existe, mais WAF bloque (à investiguer CSP/origin).
3. **Dropbox sync ne push pas** — fichiers restent en local, pas de push vers remote. `MountService.sync()` non appelé en cron.
4. **Architecture remote** — pas de dossier maître `cloud/` sur les remotes. Il faut: `/cloud/tenant_{id}/` pour isolation totale.
5. **Drag & drop dossiers inactif sur tenant** — code existe mais dropzone ne réagit pas (événements non bindés ou CSS z-index).
6. **Move folders UI** — impossible de glisser un dossier dans un autre comme navigateur natif. Pas de HTML5 drag API sur `.fb-folder`.

### Features OCTOPUS à finaliser
7. **Sync engine bidirectionnel** — `MountService.sync()` doit tourner en cron (toutes les 5min) + push/pull temps réel.
8. **Conflict resolution** — last-write-wins / keep both / manual pour sync multi-remote.
9. **Multi-remote routing** — si 2 Dropbox attachés, décider où va quel fichier (tags, règles, taille, type).
10. **Tenant quota sur remote** — enforcement quota pas juste local, mais sur l'espace distant réel.
11. **Remote folder watcher** — inotify/FSEvents/Webhook sur provider distant pour sync temps réel.
12. **Admin UI sync status** — voir status sync par mount (last_sync, pending, errors, bytes).

### UX/UI à améliorer
13. **Move folder drag&drop** — HTML5 drag API entre `.fb-folder` items (dragstart, dragover, drop).
14. **Keyboard shortcuts** — Ctrl+X/C/V, Delete, F2 rename, Enter open, Ctrl+K search.
15. **Breadcrumb click navigation** — click sur path pour remonter.
16. **Inline rename** — double-click ou F2 sur nom fichier/dossier.
17. **Multi-select** — Ctrl+Click, Shift+Click, Ctrl+A, batch actions bar (déjà partiellement fait).
18. **Column view** — option vue en colonnes arborescentes (style Finder).
19. **Global search** — Ctrl+K fuzzy search fichiers/dossiers.
20. **Sync status indicator** — badge sur dossier/fichier (synced, pending, conflict, local-only).

### Prochaines actions immédiates
- [ ] Fix route move folder + drag&drop UI
- [ ] Créer dossier `cloud/` maître sur chaque remote + structure `tenant_{id}/`
- [ ] Cron sync bidirectionnel + MountService.sync() réel
- [ ] Dropbox push test + debug pourquoi pas de sync
- [ ] Drag&drop move folder UI + endpoint move

### Commits récents
- `a4e4e76` — context menu + OCTOPUS download + copy endpoint + Encryption decryptContent
- `7c76287` — OCTOPUS MODE upload direct + deploy.php schema + admin tenant root
- `935bf49` — drag&drop folders upload + backend recursive folder creation + remote push
- `08e4a76` — sync client download page + fix upload folder select + remote push

---

## 14. SESSION 2026-09-15 (FIN) — SYNC API COMPLÈTE + FIX CRITIQUES

> Commit: `7e31abf` — feat(sync): desktop sync API + device code auth + cron sync fix

### Ce qui a été fait

#### Sync Client API (tout nouveau)
- **`SyncController`** (`app/Controllers/SyncController.php`) — API complète pour le client Tauri :
  - `POST /api/auth/device` — génère device_code + user_code (ex: `ABCD-EFGH`)
  - `POST /api/auth/token` — échange device_code ou refresh_token contre JWT access (15min) + refresh (7j)
  - `GET /api/sync/mounts` — liste les mounts actifs du tenant (Bearer JWT)
  - `GET /api/sync/mounts/{id}/files{path}` — lister les fichiers d'un dossier
  - `GET /api/sync/mounts/{id}/files/{path}/content` — télécharger un fichier déchiffré
  - `PUT /api/sync/mounts/{id}/files{path}` — uploader un fichier (chiffré AES-256-GCM avant stockage)
  - `DELETE /api/sync/mounts/{id}/files{path}` — supprimer un fichier
  - `POST /api/sync/mounts/{id}/files{path}` — créer un dossier
- **`SyncApiMiddleware`** (`app/Middleware/SyncApiMiddleware.php`) — authentification Bearer JWT pour toutes les calls API
- **`device_codes` table** + **`sync_files` table** — schéma dans `public/migrate-sync.php` (auto-destruct)
- **Page `/device`** — vue d'approbation du device code (avec login redirect conserver le ?code=)
- **Router** — extension: supporte `{name:regex}` pour les paths multi-segments (`/files/{path:.+}/content`)
- **Route de contenu**: le client construit `/files{path}` SANS slash — le routeur matche les deux formes

#### Upload fichiers — fix définitif
- **`.user.ini`** ajouté dans `public/` : `upload_max_filesize=100M`, `post_max_size=110M`, `memory_limit=512M`
- Précédemment bloqué à 2M côté PHP (config app = 100M mais PHP limitait à 2M)
- X-CSRF-Token déjà envoyé côté frontend ; le serveur le vérifie déjà

#### Cron sync — endpoint enfin fonctionnel
- Le tick `/admin/storage/tick` retournait toujours 403 (token cron absent/vides)
- **Cron token généré** (`ZaptSwchIkbjCRTUkezBclN3z3iz401E2UyA_aBjUp4`) et ajouté dans config (keys `cron.secret_token` + `scheduler.cron_token`)
- `MountService.sync()` est désormais appelable via : `GET https://cloud.saec.me/admin/storage/tick?token=ZaptSwchIkbjCRTUkezBclN3z3iz401E2UyA_aBjUp4`
- Planifier un cron job toutes les 5 minutes sur le NAS ou via Cloudflare Cron Trigger

#### CSRF durci
- **`Controller::extractCsrf()`** — helper qui lit le token depuis header, $_POST OU JSON body (forțé pour les call en JSON)
- **`FileController::copy()`** — maintenant vérifie CSRF correctement (avant 403 systématique car frontend envoyait en JSON)
- **`FileController::delete()`** — CSRF ajouté ; frontend mis à jour pour envoyer `X-CSRF-Token` header (plus de body FormData sur DELETE)

#### Login redirect
- `AuthController::loginForm()` accepte un paramètre `?redirect=` (uniquement chemins internes, pas d'URL externe)
- Le flux `/device?code=XXXX` conserve le code après login 2FA

### Étapes de déploiement (une seule fois)

1. **Exécuter la migration** (table `device_codes` + `sync_files`):
   ```
   https://cloud.saec.me/migrate-sync.php
   ```
   (auto-destruct après exécution)

2. **Appliquer `.user.ini`** — il est déjà dans `public/`. Vérifier que PHP-FPM lit les `.user.ini` :
   ```bash
   # Tester que la config est prise en compte :
   php -i | grep upload_max_filesize
   # Doit afficher 100M (pas 2M)
   ```

3. **Configurer le cron job** sur le NAS (toutes les 5 minutes):
   ```
   */5 * * * * curl -s "https://cloud.saec.me/admin/storage/tick?token=ZaptSwchIkbjCRTUkezBclN3z3iz401E2UyA_aBjUp4" > /dev/null
   ```
   Ou Cloudflare Cron Trigger (workers) pour attaquer l'endpoint directement.

4. **Tester le client sync desktop** — lancer `SAEC Sync` sur l'ordinateur, le code s'affiche et la page de vérification s'ouvre automatiquement dans le navigateur.

### Bugs marqués comme résolus
- [x] Upload fichiers impossible (PHP limit 2M) — `.user.ini` 100M
- [x] Cron sync dead (token absent) — token généré et ajouté en config
- [x] Client sync AUCUN endpoint API — SyncController complet
- [x] CSRF sur copy toujours 403 — extractCsrf() lit JSON body
- [x] Page /device inexistante — page d'approbation créée

### Bugs/en-cours toujours ouverts
- [ ] Page `/download/` 403 Cloudflare — WAF bloque (à investiguer CSP/origin)
- [ ] Architecture remote `/cloud/tenant_{id}/` — les mounts créés ne créent pas automatiquement la structure
- [ ] Dropbox push non testé (mount non configuré sur la DB de test)
- [ ] Conflict resolution avancée dans sync engine (last-write-wins seulement)

### Fichiers ajoutés/modifiés (commit 7e31abf)
- `app/Controllers/SyncController.php` — NOUVEAU (sync API complète)
- `app/Middleware/SyncApiMiddleware.php` — NOUVEAU (auth Bearer JWT)
- `app/Views/sync/verify.php` — NOUVEAU (page approbation device)
- `public/.user.ini` — NOUVEAU (upload_max_filesize=100M)
- `public/migrate-sync.php` — NOUVEAU (auto-destruct, table device_codes + sync_files)
- `core/Router.php` — MODIFIÉ (support regex patterns dans {name:regex})
- `config/routes.php` — MODIFIÉ (routes sync + device flow)
- `app/Controllers/AuthController.php` — MODIFIÉ (login redirect support)
- `app/Controllers/Controller.php` — MODIFIÉ (helper extractCsrf)
- `app/Controllers/FileController.php` — MODIFIÉ (CSRF delete + copy fix)
- `app/Views/files/index.php` — MODIFIÉ (delete CSRF header au lieu de body)
- `app/Views/auth/login.php` — MODIFIÉ (champ hidden redirect)

## 15. DMG — Build macOS

> Target: `dmg` via Tauri bundle — voir `tauri.conf.json` → `bundle.targets`.
> **Statut:** DMG fonctionnel, installé et testé sur macOS Intel (x86_64).

### Prérequis
- Machine macOS (ou CI macOS compatible)
- Rust + cargo-tauri: `curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh -s -- -y && cargo install tauri-cli`
- Target: `rustup target add x86_64-apple-darwin` (Intel) ou `aarch64-apple-darwin` (Apple Silicon)
- Node.js + npm (pour le frontend React/Vite)
- Xcode command line tools: `xcode-select --install`

### Processus de build DMG
```bash
cd saec-sync/src-tauri
# 1. Générer les icônes (AVANT le build)
cargo tauri icon icons/source-icon.png
# 2. Fix tray-icon (cargo tauri icon produit un 108 bytes sur macOS)
cp icons/source-icon.png icons/tray-icon.png
# 3. Build
cargo tauri build --target x86_64-apple-darwin
```
- **Artifact:** `saec-sync/target/x86_64-apple-darwin/release/bundle/dmg/SAEC Sync_0.1.36_x64.dmg`
- **Note:** `--release` n'est PAS nécessaire (Tauri v2 = release par défaut)
- Le build nettoie la .app après création du DMG → monter le DMG pour récupérer la .app

### Installation sur macOS
```bash
hdiutil attach <chemin>.dmg
cp -R /Volumes/SAEC\ Sync/SAEC\ Sync.app /Applications/
hdiutil detach /Volumes/SAEC\ Sync
codesign --force --deep --sign - "/Applications/SAEC Sync.app"
open "/Applications/SAEC Sync.app"
```

### Bugs corrigés (session 2026-09-20)

#### Bug 1: State management — panic `state() called before manage()`
- **Erreur:** `state() called before manage() for Arc<SyncEngine>`
- **Cause:** `SyncEngine` enregistré comme `SyncEngine` mais récupéré comme `Arc<SyncEngine>`
- **Fix:** `.manage(Arc::new(sync_engine))` au lieu de `.manage(sync_engine)` dans `main.rs`
- **Règle:** Toujours wrapper dans `Arc` quand on passe au `.manage()` de Tauri

#### Bug 2: CSP bloque Google Fonts → webview crash
- **Erreur:** `web content process terminated` + app bloquée sur "Initialisation..."
- **Cause:** CSP dans `tauri.conf.json` n'autorisait pas `fonts.googleapis.com` / `fonts.gstatic.com`
- **Fix:** Ajouter au CSP:
  ```
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  font-src 'self' data: https://fonts.gstatic.com;
  ```
- **Règle:** Le frontend React charge Google Fonts → CSP doit les autoriser

#### Bug 3: Icônes corrompues → crash TAO
- **Erreur:** `panic_cannot_unwind` dans `tao::did_finish_launching`
- **Cause:** `tray-icon.png` faisait 108-316 bytes (vide/corrompu)
- **Fix:** Utiliser `cargo tauri icon icons/source-icon.png` PUIS `cp icons/source-icon.png icons/tray-icon.png`
- **Règle:** Toujours vérifier la taille des icônes après génération (>1KB)

#### Bug 4: git pull pas appliqué sur Mac
- **Erreur:** Fix CSP fait sur NAS mais pas sur le Mac local
- **Cause:** Le clone GitHub est un snapshot, pas un lien vers le NAS
- **Fix:** Éditer directement les fichiers sur le Mac ou `git pull` avant rebuild
- **Règle:** Toujours vérifier que le code local est à jour avant de builder

### Icônes
- **Source:** `public/img/favicon-192x192.png` (32KB, 192x192, PNG RGBA)
- **Génération:** `cargo tauri icon icons/source-icon.png` → icon.png, icon.icns, icon.ico
- **tray-icon:** Copier source-icon.png par dessus (le générateur Tauri produit un fichier trop petit)
- **Icons requis:** icon.png (1024x1024), icon.icns (macOS), icon.ico (Windows), tray-icon.png (32x32 template)

### Workflow GitHub Actions proposé (`.github/workflows/dmg.yml`)
```yaml
name: Build SAEC Sync DMG

on:
  push:
    tags:
      - 'v*'          # déclenché à chaque nouveau tag
  workflow_dispatch:
    inputs:
      version:
        description: 'Version to build'
        required: false
        default: ''

jobs:
  build-dmg:
    runs-on: macos-latest

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Install Rust target (if needed)
        run: rustup target add aarch64-apple-darwin

      - name: Setup Python (for any helpers)
        uses: actions/setup-python@v5
        with:
          python-version: '3.12'

      - name: Build Tauri DMG
        working-directory: saec-sync/src-tauri
        env:
          TAURI_PRIVATE_KEY: ${{ secrets.TAURI_PRIVATE_KEY }}
        run: |
          cargo tauri build --target aarch64-apple-darwin --release

      - name: Locate generated DMG
        run: |
          $dmgPaths = @(
            "saec-sync/target/aarch64-apple-darwin/release/bundle/dmg/*.dmg"
          )
          foreach ($pattern in $dmgPaths) {
            $files = Get-ChildItem $pattern -ErrorAction SilentlyContinue
            if ($files) {
              echo "dmg_path=$($files[0].FullName)" >> $env:GITHUB_OUTPUT
              break
            }
          }

      - name: Create/Update GitHub Release
        uses: softprops/action-gh-release@v2
        with:
          tag_name: ${{ github.ref_name }}
          name: "SAEC Sync ${{ github.ref_name }}"
          files: ${{ steps.find-dmg.outputs.dmg_path }}
          overwrite: true
          generate_release_notes: true
```

### Scripts d'installation

#### `install-macos.sh` (cr, excutable)
- **Usage:** `chmod +x install-macos.sh && ./install-macos.sh [VERSION]`
- **Fonctions:**
  - Vrification pr-requis macOS (uname, curl, hdiutil)
  - Dtection architecture (Apple Silicon arm64 / Intel x86_64)
  - Recherche DMG automatique (GitHub Releases > cloud.saec.me > local)
  - Tlchargement progressif avec curl
  - Montage DMG, copie dans /Applications
  - Configuration automatique (serveur API, version)
  - Retire quarantine Gatekeeper
  - Lancement optionnel de l'app
- **Sources DMG:** GitHub Releases, cloud.saec.me, ou local

### Meilleure solution sans machine macOS locale
Comme aucune machine physique macOS n'est à disposition, la solution idéale et sécurisée consiste à **déplacer la compilation DMG vers une pipeline CI hébergée en ligne**, spécifiquement **GitHub Actions** utilisant le runner `macOS-latest`. Cette approche présente les mêmes avantages que la solution Windows MSI (voir passation §7) :
- Pas de dépendance matérielle locale
- Environnement préconfiguré avec Xcode, codesigning tools, etc.
- Rust target `aarch64-apple-darwin` disponible par défaut
- Secrets (TAURI_PRIVATE_KEY) stockés en secret GitHub
- Intégration native avec GitHub Releases
- Reproductibilité totale via workflow YAML
```
