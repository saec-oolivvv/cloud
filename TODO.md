# SAEC CLOUD — TODO COMPLET

> SaaS de stockage sécurisé, multi-tenant, chiffrement AES-256-GCM
> Architecture MVC PHP 8.3 · MySQL · Synology DS420j

---

## 🏗️ PHASE 0 — SETUP PROJET

### 0.1 Structure MVC
- [ ] Créer arborescence du projet
- [ ] Setup autoloader PSR-4
- [ ] Router central (GET/POST/PUT/DELETE)
- [ ] Core : Database (PDO), Request, Response, Session
- [ ] Config loader (env + .php)
- [ ] `.env`.example avec variables obligatoires

### 0.2 Branding SAEC
- [ ] Logo SAEC (récupérer depuis saec.me)
- [ ] Palette couleurs :
  - Background : `#0a0a0a`
  - Surface : `#111111`
  - Accent : `#00ff88`
  - Text : `#ffffff`
  - Muted : `#888888`
- [ ] Font : Inter (body) + JetBrains Mono (code/status)
- [ ] CSS custom properties globales
- [ ] Favicons + Apple touch icon
- [ ] Template layout base (header/footer/nav)

### 0.3 Branding Login/Auth Pages
- [ ] Page login dark theme SAEC
- [ ] Page register (si invite par admin)
- [ ] Page forgot password
- [ ] Status monitor animé ("SAEC_CLOUD // STATUS: NOMINAL")
- [ ] Footer : "© 2026 SAEC Ltd. — Bespoke Systems Engineering. Sovereign stack."

### 0.4 Installation Interactive + Autodestruct
- [ ] Script `install.sh` interactif (question/réponse)
- [ ] Script `update.sh` autodestruct (s'exécute puis se supprime)
- [ ] Script `uninstall.sh` autodestruct
- [ ] Prompts : domaine, DB host, DB user, admin email, admin password
- [ ] Validation de chaque entrée avant continuation
- [ ] Génération automatique de `.env` à partir des réponses
- [ ] Couleurs terminal SAEC (vert #00ff88)
- [ ] ASCII art SAEC au démarrage
- [ ] Confirmation finale avant exécution
- [ ] Rollback automatique en cas d'échec

### 0.5 Setup MySQL Sécurisé
- [ ] Script `setup-mysql.sh` pour NAS distant
- [ ] Création base `saec_cloud`
- [ ] Création utilisateur `saec_cloud` avec mot de passe aléatoire (64 chars)
- [ ] Grant uniquement sur `saec_cloud` depuis `192.168.0.201`
- [ ] Affichage du mot de passe généré (à copier dans .env)
- [ ] Instructions de connexion
- [ ] Vérification de la connexion

---

## 🔐 PHASE 1 — MODULE AUTH

### 1.1 Modèle User
- [ ] Table `users` (id, tenant_id, email, password_hash, role, mfa_secret, created_at)
- [ ] CRUD User (admin only)
- [ ] Password hashing : ARGON2ID
- [ ] Rate limiting login (5 essais / 15min)

### 1.2 Authentification
- [ ] Login controller (email + password)
- [ ] JWT generation (RS256, expiry 15min)
- [ ] Refresh tokens (httpOnly cookie, 7j)
- [ ] Logout (invalidation token)
- [ ] MFA optionnel (TOTP via Google Authenticator)
- [ ] Session fixation protection

### 1.3 Middleware
- [ ] AuthMiddleware : vérifie JWT sur chaque requête protégée
- [ ] TenantMiddleware : injecte tenant_id dans contexte
- [ ] AdminMiddleware : vérifie role = admin
- [ ] RateLimiter : limite requêtes par IP

### 1.4 Pages Auth
- [ ] `GET /login` → formulaire login
- [ ] `POST /login` → vérifie credentials
- [ ] `GET /logout` → détruit session
- [ ] `POST /mfa/verify` → vérifie code TOTP

---

## 📁 PHASE 2 — MODULE STORAGE

### 2.1 Modèle File
- [ ] Table `files` (id, tenant_id, user_id, original_name, stored_name, mime_type, size, encryption_key_id, checksum, version, created_at, deleted_at)
- [ ] Table `file_versions` (id, file_id, version, stored_name, size, checksum, created_by, created_at)
- [ ] Table `encryption_keys` (id, tenant_id, key_hash, created_at, rotated_at)

### 2.2 Encryption Engine
- [ ] Classe `Encryption` (AES-256-GCM)
- [ ] Génération clé par fichier (ou par tenant)
- [ ] Stockage clés dans vault local (fichier chiffré) ou DB
- [ ] Chiffrement upload : `encryptFile()`
- [ ] Déchiffrement download : `decryptFile()`
- [ ] Rotation clés (optionnel, planché)

### 2.3 Upload
- [ ] `POST /files/upload` → chunked upload
- [ ] Validation : type MIME, taille max (configurable/tenant)
- [ ] Renommage UUID + extension
- [ ] Calcul checksum SHA-256 après écriture
- [ ] Indexation Meilisearch après upload
- [ ] Audit log : file.uploaded

### 2.4 Download
- [ ] `GET /files/{id}/download` → déchifflé en mémoire, stream
- [ ] Vérification checksum avant envoi
- [ ] Audit log : file.downloaded

### 2.5 Listing & Navigation
- [ ] `GET /files` → liste fichiers du tenant
- [ ] Dossiers virtuels (champ `folder_path` dans table files)
- [ ] Filtrage par type, date, taille
- [ ] Tri (nom, date, taille)
- [ ] Pagination

### 2.6 Versioning
- [ ] Upload nouvelle version → incrémente version
- [ ] `GET /files/{id}/versions` → historique
- [ ] `POST /files/{id}/restore/{version}` → restaure version

### 2.7 Trash
- [ ] `DELETE /files/{id}` → soft delete (deleted_at)
- [ ] `GET /trash` → fichiers supprimés
- [ ] `POST /files/{id}/restore` → restaure du trash
- [ ] Purge automatique après 30 jours (cron)

---

## 🔍 PHASE 3 — MODULE SEARCH

### 3.1 Indexation
- [ ] Setup Meilisearch (Docker ou binaire)
- [ ] Index par tenant (isolation)
- [ ] Index après upload : title, content (extract text), metadata
- [ ] Extraction texte PDF (pdftotext)
- [ ] Extraction texte Word (antiword ou python-docx)

### 3.2 Recherche
- [ ] `GET /search?q=...` → recherche full-text
- [ ] Résultats filtrés par tenant
- [ ] Highlight snippets
- [ ] Filtres : type, date, taille

---

## 👥 PHASE 4 — MODULE SHARING

### 4.1 Modèles
- [ ] Table `shares` (id, file_id, tenant_id, created_by, permission, expires_at, link_token, created_at)
- [ ] Permissions : view, download, edit

### 4.2 Partage interne
- [ ] `POST /files/{id}/share` → partage avec user du tenant
- [ ] `GET /shares` → mes partages
- [ ] `DELETE /shares/{id}` → révoque partage

### 4.3 Partage externe (lien)
- [ ] `POST /files/{id}/share-link` → génère lien temporaire
- [ ] Lien : token aléatoire, TTL configurable
- [ ] `GET /share/{token}` → accès public (si authentifié ou non selon config)
- [ ] Password protection sur lien (optionnel)

---

## 🛡️ PHASE 5 — MODULE ADMIN

### 5.1 API Admin (port 8443, whitelist IP)
- [ ] Auth admin renforcée (mTLS ou IP fixe)
- [ ] Rate limiting strict
- [ ] Audit trail toutes actions admin

### 5.2 Gestion Tenants
- [ ] `POST /admin/tenants` → créer tenant
- [ ] `PUT /admin/tenants/{id}` → modifier (nom, quotas, features)
- [ ] `DELETE /admin/tenants/{id}` → soft delete
- [ ] `GET /admin/tenants/{id}/stats` → usage (espace, users, files)

### 5.3 Gestion Users
- [ ] `POST /admin/tenants/{id}/users` → créer user
- [ ] `PUT /admin/users/{id}` → modifier role, activer/désactiver
- [ ] `DELETE /admin/users/{id}` → supprimer

### 5.4 Gestion Quotas
- [ ] Limite taille fichier par tenant (configurable)
- [ ] Limite espace total par tenant (configurable)
- [ ] Limite nombre users par tenant (configurable)
- [ ] Alertes quota atteint (email admin)

### 5.5 Configuration Globale
- [ ] `GET /admin/config` → voir config
- [ ] `PUT /admin/config` → modifier paramètres globaux
- [ ] Paramètres : max_file_size, storage_quota, replication_enabled, mfa_required

### 5.6 Dashboard Admin
- [ ] Stats globales : tenants, users, storage used, bandwidth
- [ ] Graphiques usage (Chart.js)
- [ ] Derniers audit logs
- [ ] Santé système (disk, RAM, CPU)

---

## 🔄 PHASE 6 — MODULE REPLICATION

### 6.1 Script Replication
- [ ] Script bash `replicate.sh`
- [ ] Rclone sync over SSH
- [ ] Chiffrement pendant transfert
- [ ] Vérification checksum post-sync

### 6.2 Cron
- [ ] Cron job toutes les 5min
- [ ] Log rotation
- [ ] Alertes échec replication (email admin)

### 6.3 Admin Control
- [ ] `POST /admin/replication/trigger` → force sync
- [ ] `GET /admin/replication/status` → dernière sync, état
- [ ] `PUT /admin/replication/config` → activer/désactiver, intervalle

---

## 📊 PHASE 7 — MODULE AUDIT

### 7.1 Logging
- [ ] Table `audit_logs` (id, tenant_id, user_id, action, resource_type, resource_id, ip_address, user_agent, metadata, created_at)
- [ ] Logging automatique via middleware
- [ ] Actions trackées : login, logout, upload, download, delete, share, admin actions

### 7.2 Consultation
- [ ] `GET /admin/audit/{tenant_id}` → logs du tenant
- [ ] Filtres : action, user, date range
- [ ] Export CSV
- [ ] Immutabilité : pas de delete/update sur audit_logs

---

## 🖥️ PHASE 8 — CLIENT WEB

### 8.1 Frontend React
- [ ] Setup React + TypeScript + Vite
- [ ] Design system SAEC (tokens, composants)
- [ ] Routing (React Router)
- [ ] Auth context (JWT + refresh)

### 8.2 Pages
- [ ] `/login` → login form
- [ ] `/dashboard` → overview (storage used, recent files)
- [ ] `/files` → file manager (liste, grille, drag & drop)
- [ ] `/files/{id}` → preview (PDF, images, texte)
- [ ] `/shares` → partages reçus/créés
- [ ] `/settings` → profil, mfa
- [ ] `/admin/*` → panel admin (si role admin)

### 8.3 Composants
- [ ] `FileUploader` (chunked, progress bar)
- [ ] `FileList` (tri, filtres, pagination)
- [ ] `FilePreview` (PDF viewer, image viewer, texte)
- [ ] `ShareModal` (partage user ou lien)
- [ ] `StorageBar` (quota usage visual)
- [ ] `AdminDashboard` (stats, graphs)

### 8.4 Design SAEC
- [ ] Dark theme permanent
- [ ] Status monitor animé header
- [ ] Code snippets décoratifs
- [ ] Monospace accents
- [ ] Footer SAEC branding

---

## 🖥️ PHASE 9 — CLIENT DESKTOP (optionnel, plus tard)

### 9.1 Tauri Setup
- [ ] Setup Tauri 2.0 + React
- [ ] System tray integration
- [ ] Auto-update

### 9.2 Features
- [ ] Sync locale (dossier → serveur)
- [ ] Notification uploads/downloads
- [ ] Mode offline (lecture fichiers locaux)
- [ ] Auto-launch au démarrage

---

## 🌐 PHASE 10 — DNS & INFRA

### 10.1 Cloudflare DNS
- [ ] Zone : `saec.me`
- [ ] Enregistrement A : `cloud.saec.me` → IP NAS (public)
- [ ] Proxy : activé (orange cloud)
- [ ] SSL : Full (strict)
- [ ] HSTS : activé
- [ ] WAF : règles personnalisées

### 10.2 Cloudflare WAF Rules
- [ ] Block non-browser user-agents sur /admin/*
- [ ] Rate limiting : 100 req/min par IP
- [ ] Challenge suspicious patterns
- [ ] Geo-blocking (optionnel)

### 10.3 Synology Setup
- [ ] Web Station activé
- [ ] PHP 8.3 activé
- [ ] Virtual host : `cloud.saec.me`
- [ ] SSL Let's Encrypt (ou Cloudflare origin cert)
- [ ] Firewall NAS : ports 443, 8443 uniquement
- [ ] Fail2Ban installé
- [ ] SSH : port custom, clé only

### 10.4 MySQL (NAS distant — 192.168.0.201)
- [ ] MySQL 8.x installé et actif
- [ ] Script `setup-mysql.sh` exécuté sur le NAS distant
- [ ] Base `saec_cloud` créée avec charset utf8mb4
- [ ] Utilisateur `saec_cloud` créé avec mot de passe aléatoire 64 chars
- [ ] Grant : `ALL PRIVILEGES ON saec_cloud.* TO 'saec_cloud'@'192.168.0.201'`
- [ ] Revoke : accès depuis toute autre IP refusé
- [ ] TLS activé pour connexions distantes
- [ ] Backup quotidien : cron mysqldump + rétention 30 jours
- [ ] Vérification connexion depuis NAS source

---

## 🧪 PHASE 11 — SÉCURITÉ

### 11.1 Checklist Sécurité
- [ ] TLS 1.3 partout
- [ ] HSTS max-age 31536000
- [ ] CSP strict (pas de unsafe-inline)
- [ ] X-Frame-Options: DENY
- [ ] X-Content-Type-Options: nosniff
- [ ] Referrer-Policy: strict-origin-when-cross-origin
- [ ] Permissions-Policy: geolocation=(), camera=()
- [ ] Cookie : Secure, HttpOnly, SameSite=Strict
- [ ] Rate limiting global
- [ ] Brute force protection (Fail2Ban)
- [ ] SQL injection : PDO prepared statements 100%
- [ ] XSS : htmlspecialchars() + CSP
- [ ] CSRF tokens sur tous les formulaires
- [ ] File upload : validation MIME + taille
- [ ] Directory listing désactivé
- [ ] Error pages custom (pas de stack trace)
- [ ] Logs d'erreur vers fichier (pas d'affichage)

### 11.2 Tests
- [ ] Test SQL injection (sqlmap)
- [ ] Test XSS (manually + automated)
- [ ] Test brute force
- [ ] Test file upload malveillant
- [ ] Test privilege escalation
- [ ] Test tenant isolation

---

## 🚀 PHASE 12 — DÉPLOIEMENT

### 12.1 Production
- [ ] Code déployé sur `/volume1/web/cloud/`
- [ ] `.env` configuré (pas de .env.example en prod)
- [ ] permissions : 755 dossiers, 644 fichiers
- [ ] uploads/ : 770 (writable par PHP)
- [ ] logs/ : writable par PHP
- [ ] Cache OPcache activé
- [ ] Compression GZIP activée

### 12.2 Monitoring
- [ ] Prometheus (métriques)
- [ ] Grafana (dashboards)
- [ ] Alertes email (disk, RAM, CPU)
- [ ] Uptime monitoring (UptimeRobot)

### 12.3 Backup
- [ ] Backup MySQL quotidien (mysqldump + cron)
- [ ] Backup fichiers chiffrés (Rclone vers NAS distant)
- [ ] Test restore mensuel
- [ ] Rétention 30 jours

---

## 📝 PHASE 13 — DOCUMENTATION

### 13.1 Admin
- [ ] README.md : setup, config, deployment
- [ ] Guide admin : gestion tenants, users, quotas
- [ ] Guide replication
- [ ] Troubleshooting

### 13.2 User
- [ ] Guide utilisateur (upload, download, share)
- [ ] FAQ
- [ ] Contact support

---

## 🎨 BRANDING SAEC — RÉFÉRENCES

### Palette
```
--bg-primary: #0a0a0a
--bg-surface: #111111
--bg-elevated: #1a1a1a
--accent: #00ff88
--accent-dim: #00cc6a
--text-primary: #ffffff
--text-secondary: #888888
--text-muted: #555555
--border: #222222
--error: #ff4444
--warning: #ffaa00
```

### Typography
```
Body: Inter, sans-serif
Code/Mono: JetBrains Mono, monospace
Headings: Inter Bold
```

### Components
- Status monitor : `SAEC_CLOUD // STATUS: NOMINAL // LOCATION: LONDON`
- Code blocks décoratifs
- Cards dark avec border subtle
- Boutons accent vert
- Inputs dark avec focus glow

### Footer Template
```
© 2026 SAEC Ltd. — Bespoke Systems Engineering.
Premium websites · bespoke SaaS · active security.
Designed, secured and self-hosted · Custom PHP MVC stack
```

---

## ORDRE D'IMPLÉMENTATION

1. **Phase 0** → Setup projet + branding
2. **Phase 1** → Auth (fondation sécurité)
3. **Phase 2** → Storage (core feature)
4. **Phase 7** → Audit (traçabilité)
5. **Phase 4** → Sharing
6. **Phase 5** → Admin
7. **Phase 3** → Search
8. **Phase 6** → Replication
9. **Phase 8** → Client web
10. **Phase 10** → DNS + infra
11. **Phase 11** → Sécurité hardening
12. **Phase 12** → Déploiement prod
13. **Phase 9** → Desktop (optionnel)
14. **Phase 13** → Documentation

---

*Dernière mise à jour : 2026-09-03*
