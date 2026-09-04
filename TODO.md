# SAEC Cloud — TODO Complet

## 🔴 CRITIQUE — En cours
- [x] Dashboard 500 : `deleted_at` sur table `folders` → fixé
- [x] Password change non fonctionnel : error handler 500 + pas de try/catch → fixé
- [x] Error handler convertissait warnings en 500 → fixé
- [ ] Cookie consent banner RGPD obligatoire
- [ ] Page Politique de confidentialité (/privacy)
- [ ] Page Conditions d'utilisation (/terms)
- [ ] Export données personnelles (RGPD Art. 20)
- [ ] Suppression compte (RGPD Art. 17)

## 🟠 HAUT — Sécurité & RGPD
- [ ] Rate limiting sur changement mot de passe
- [ ] Complexité mot de passe renforcée (12+ chars, majuscule, chiffre, symbole)
- [ ] Vérification email nouvel utilisateur
- [ ] 2FA / TOTP optionnel
- [ ] Rétention des logs audit configurables
- [ ] Politique de rétention corbeille (auto-purge)
- [ ] Export audit logs (CSV/JSON)
- [ ] Headers security renforcés (X-Content-Type, Permissions-Policy)
- [ ] Content Security Policy stricte

## 🟡 DESIGN PREMIUM — Toutes pages
- [x] Login page → premium ✅
- [x] Dashboard user → premium ✅
- [x] Settings/Config → premium ✅
- [x] Landing page → premium ✅
- [x] Pricing page → premium ✅
- [x] Subscribe page → premium ✅
- [x] 404 page → premium ✅
- [ ] Files page → review design
- [ ] Shares page → review design
- [ ] Admin dashboard → review design
- [ ] Admin tenants → review design
- [ ] Admin users → review design
- [ ] Admin audit → review design
- [ ] Admin config → review design
- [ ] Admin modules → review design
- [ ] Admin billing → review design

## 🟢 FONCTIONNALITÉS MANQUANTES
- [ ] Recherche fichiers globale (Ctrl+K)
- [ ] Partage par email (pas juste lien)
- [ ] Notifications temps réel
- [ ] Versioning fichiers (UI)
- [ ] Preview inline (images, PDF, code)
- [ ] Drag & drop upload
- [ ] Multi-sélection fichiers
- [ ] Batch actions (supprimer, déplacer, télécharger)
- [ ] Raccourcis clavier globaux
- [ ] Dark/Light theme toggle
- [ ] Profil utilisateur éditable (photo)
- [ ] Deux-factor auth (TOTP)
- [ ] Sessions management (révoquer individuellement)
- [ ] API keys management
- [ ] Webhook support

## 🔵 PERFORMANCE
- [ ] Gzip/Brotli compression
- [ ] Cache headers (ETag, Last-Modified)
- [ ] Lazy loading images
- [ ] Minification CSS/JS
- [ ] CDN pour assets statiques
- [ ] Pagination côté serveur pour gros volumes
- [ ] Index SQL sur colonnes fréquemment query
- [ ] Connection pooling MySQL
- [ ] Redis/Memcache pour sessions

## 🟣 CONCURRENCE — Features à copier
- [ ] Tresorit : zero-knowledge architecture
- [ ] Filen : client-side encryption
- [ ] Proton Drive : encrypted sharing
- [ ] pCloud : lifetime plans
- [ ] Internxt : open-source clients
- [ ] Syncthing : sync local-first

## 📋 BACKLOG
- [ ] Desktop app (Electron/Tauri)
- [ ] Mobile app (React Native)
- [ ] Collaborative editing
- [ ] Versioning avancé (diff, restauration)
- [ ] Metadata extraction (EXIF, etc.)
- [ ] Tags/favoris fichiers
- [ ] Partage chiffré (zero-knowledge)
- [ ] Chiffrement côté client (client-side)
- [ ] Multi-langue complet
- [ ] Accessibilité WCAG 2.1 AA
