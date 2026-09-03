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

### En Cours
- [ ] Migration complète (folders + security)
- [ ] Folders CRUD (arborescent)
- [ ] Page pricing + questionnaire souscription
- [ ] Client sync bidirectionnelle
- [ ] Sync API endpoints

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
