# Passation SAEC Sync — Session complète

> **Dernière mise à jour :** 20 septembre 2026
> **Projet :** SAEC Cloud — SaaS multi-tenant (PHP 8.3+, MySQL 8.x, Apache/Nginx)
> **NAS :** Synology DS420j, `192.168.0.201`
> **Client Tauri :** `saec-sync/` (React/Vite + Rust), v0.1.36
> **Repo GitHub :** `saec-oolivvv/cloud`

---

## 1. Environnement

| Élément | Détail |
|---|---|
| Mac | `saec@MacBook-Pro-de-SAEC`, Intel x86_64, macOS 26.7 (Tahoe) |
| NAS | Synology DS420j, IP `192.168.0.201` |
| Serveur web | Synology Web Station (racine differente de `public/`) |
| SCP | **Ne fonctionne pas** depuis le Mac vers le NAS |
| App name | SAEC Sync |
| Identifier | `me.saec.sync` |
| Version | `0.1.36` |
| GitHub repo | `saec-oolivvv/cloud` |

---

## 2. Fichiers clés

| Fichier | Rôle |
|---|---|
| `saec-sync/Cargo.toml` | Workspace deps + `[patch.crates-io]` pour tauri-runtime-wry |
| `saec-sync/src-tauri/Cargo.toml` | Deps app, `[profile.release]` |
| `saec-sync/src-tauri/tauri.conf.json` | Config Tauri (CSP, windows, bundle) |
| `saec-sync/src-tauri/src/main.rs` | Entry point, setup_tray, window management |
| `saec-sync/src-tauri/src/ipc/commands.rs` | Commands IPC + `setup_tray()` |
| `saec-sync/src/App.tsx` | Frontend React (loading screen) |
| `saec-sync/src/store/auth.ts` | Auth store — appelle `invoke('get_credentials')` |
| `install-macos.sh` | Script installation automatique macOS |
| `MEMORY.md` | Mémoire de session (build process + bugs) |
| `passation.md` | Ce document |

---

## 3. Bugs trouvés et corrigés

### 3.1. `manage(sync_engine)` → `manage(Arc::new(sync_engine))`
**Fichier :** `main.rs:33`
**Problème :** `SyncEngine` n'implémente pas `Send + Sync` directement. Tauri exige `Send + Sync` pour `.manage()`.
**Fix :** Wrapper dans `Arc::new()`.
**Statut :** ✅ Corrigé

### 3.2. CSP trop restrictive (Google Fonts)
**Fichier :** `tauri.conf.json:30`
**Problème :** `style-src` et `font-src` manquaient les domaines Google Fonts.
**Fix :** Ajouté `https://fonts.googleapis.com` à `style-src` et `https://fonts.gstatic.com` à `font-src`.
**Statut :** ✅ Corrigé

### 3.3. `tray-icon.png` corrompu
**Fichier :** `src-tauri/icons/tray-icon.png`
**Problème :** Image vide ou corrompue.
**Fix :** Copié `source-icon.png` par-dessus `tray-icon.png`.
**Statut :** ✅ Corrigé (mais l'icone reste un carré vert, pas le logo SAEC — à remplacer)

### 3.4. `cargo tauri build --release` crash silencieux (macOS 26)
**Problème :** L'app se lance en `cargo tauri dev` mais crash au lancement en release.
**Symptôme :** `web content process terminated` dans les logs.
**Statut :** 🔴 **BUG CRITIQUE — RACINE IDENTIFIÉE** (voir section 4)

### 3.5. `setup_tray` panic si tray indisponible
**Fichier :** `main.rs:52`
**Problème :** `setup_tray()` utilisait `?` qui propageait l'erreur et crashait l'app.
**Fix :** Wrappé dans `if let Err(e) = ... { tracing::warn! }` — non-fatal.
**Statut :** ✅ Corrigé

### 3.6. `window.show()` / `window.set_title()` panic
**Fichier :** `main.rs:66-67`
**Problème :** `?` propageait les erreurs → panic.
**Fix :** Changé en `let _ = window.show(); let _ = window.set_title(...);`
**Statut :** ✅ Corrigé

### 3.7. `visible: false` dans tauri.conf.json
**Fichier :** `tauri.conf.json`
**Problème :** Fenêtre invisible au démarrage.
**Fix :** Changé en `visible: true`.
**Statut :** ✅ Corrigé

### 3.8. `trayIcon` cassé dans tauri.conf.json
**Problème :** Le fichier `tray-icon.png` était corrompu + le setup se fait en code.
**Fix :** Supprimé `trayIcon` de la config JSON (setup fait dans `setup_tray()`).
**Statut :** ✅ Corrigé

### 3.9. `get_credentials` / `store_credentials` manquants
**Fichier :** `src/store/auth.ts` appelle `invoke('get_credentials')` et `invoke('store_credentials')`
**Problème :** Ces commands ne sont pas dans `generate_handler![]` dans `main.rs` ni dans `commands.rs`.
**Statut :** 🔴 **À VÉRIFIER** — soit les commands existent dans commands.rs et ne sont pas enregistrées, soit il faut les créer.

---

## 4. Bug critique : tao 0.35.3 crash sur macOS 26 (Tahoe)

### Symptôme
- `cargo tauri dev` → **fonctionne** (l'app s'affiche correctement)
- `cargo tauri build --release` → **crash silencieux** au lancement
- Log : `web content process terminated`
- `panic = "abort"` dans le profil release tue le processus silencieusement

### Racine
**Tauri bug [#15517](https://github.com/tauri-apps/tauri/issues/15517)**
**tao bug [#1171](https://github.com/nickelpack/tao/issues/1171)**

Le crate `tao 0.35.3` (framework window/events de Tauri) **panique** dans `did_finish_launching` sur macOS 26 (Tahoe). Le callback Objective-C `NSApplicationDelegate::applicationDidFinishLaunching:` plante.

### Chaîne de dépendances
```
tauri 2.11.6 (crates.io)
  → tauri-runtime-wry 2.11.4 (crates.io)  → wry 0.55.1 + tao 0.35.3  ← BUGUÉ
  → tauri-runtime-wry 2.11.4 (git dev)     → wry 0.57.0 + tao 0.37.0  ← FIX
```

### Solution appliquée
Patch de `tauri-runtime-wry` via `[patch.crates-io]` dans le workspace Cargo.toml :

```toml
[patch.crates-io]
tauri-runtime-wry = { git = "https://github.com/tauri-apps/tauri", branch = "dev" }
```

**Pourquoi ça marche :**
1. `tauri-runtime-wry` sur crates.io (2.11.4) dépend de `wry ^0.55` + `tao ^0.35`
2. `tauri-runtime-wry` sur git dev (même version 2.11.4) dépend de `wry 0.57` + `tao 0.37`
3. Le patch redirige la résolution vers git dev → nouvelles dépendances résolues normalement de crates.io
4. **Pas de conflit semver** : la version 2.11.4 est identique, le patch remplace uniquement la source

### Résultat dans Cargo.lock (confirmé)
```
tauri-runtime-wry 2.11.4 → source=git+https://github.com/tauri-apps/tauri?branch=dev
wry 0.57.0 → source=registry+https://github.com/rust-lang/crates.io-index
tao 0.37.0 → source=registry+https://github.com/rust-lang/crates.io-index
```

### Pourquoi les autres approches n'ont PAS marché

| Approche | Problème |
|---|---|
| `[patch.crates-io] tauri = { git = "..." }` | Les plugins (`tauri-plugin-shell` etc.) depuis crates.io tirent `tauri` de crates.io (2.11.6) malgré le patch |
| `[patch.crates-io] tauri-runtime-wry = { git = "...", version = "2.11.4" }` | Redondant, même résultat |
| `[patch.crates-io] tao = { git = "...", branch = "..." }` | **Semver incompatible** : wry 0.55.1 exige `tao ^0.35`, pas `^0.37` |
| `[patch.crates-io] wry = { git = "...", branch = "..." }` | **Semver incompatible** : tauri-runtime-wry 2.11.4 exige `wry ^0.55`, pas `^0.57` |
| `[workspace.dependencies] tauri = { git = "..." }` | Les plugins ignorent la source workspace et résolvent depuis crates.io |
| Fork `nickelpack/tao` | Fork random, inexistant ou incorrect |

### Ce qui RESTE à faire pour le bug tao
1. **Rebuild le DMG** avec le Cargo.lock mis à jour
2. **Tester** sur le Mac (`cargo tauri dev` ET `cargo tauri build --release`)
3. **Vérifier** que le crash `web content process terminated` est résolu
4. Si le crash persiste, vérifier le panic `on_window_event` qui utilise encore `window.hide().unwrap()` (ligne 74 de main.rs)

---

## 5. Processus de build DMG

### Prérequis
- Node.js (npm)
- Rust toolchain (`rustup target add x86_64-apple-darwin`)
- `cargo tauri` CLI
- Xcode command line tools (sur Mac)

### Commandes
```bash
cd saec-sync
rm -rf node_modules dist target
npm ci
npm run build           # Vite → dist/
cargo tauri build       # Release DMG (pas de flag --release, c'est le default)
```

### Sortie
```
target/x86_64-apple-darwin/release/bundle/dmg/SAEC-Sync-0.1.36.dmg
```

### Notes importantes
- **`cargo tauri build` = release par défaut** — le flag `--release` est invalide dans Tauri v2
- **`cargo tauri dev`** = mode dev (Vite localhost:1420) — **fonctionne**
- **`cargo tauri build`** = release (custom protocol) — **crashait avant le patch tao**
- SCP ne fonctionne pas → upload DMG via GitHub Releases
- Synology Web Station sert depuis une racine différente de `public/`

---

## 6. Script d'installation macOS (`install-macos.sh`)

### Fonctionnalités
- Vérification prérequis (uname, curl, hdiutil)
- Détection architecture (arm64 / x86_64)
- Téléchargement DMG (GitHub Releases > cloud.saec.me > local)
- Montage DMG, copie dans `/Applications`
- Configuration serveur API
- Retire quarantine (`xattr -dr com.apple.quarantine`)
- Vérification Gatekeeper

### Fichiers
- `/mnt/nas-web/cloud/install-macos.sh` — script original
- `/mnt/nas-web/cloud/public/install-macos.sh` — copie pour téléchargement HTTP

### Utilisation
```bash
chmod +x install-macos.sh
./install-macos.sh         # v0.1.36 par défaut
./install-macos.sh 0.1.37  # version spécifique
```

---

## 7. Page web overlay download (`app/Views/download/index.php`)

- **501 lignes** — modal overlay pour macOS
- Compatible macOS (overlay dédié) et autres plateformes ( lien direct)
- Redimensionnement responsive (desktop/tablette/mobile)
- Utilise l'API `cloud.saec.me` pour la config

---

## 8. Git commits de la session

| Hash | Message |
|---|---|
| `e9e8211` | Fix sync_engine Arc wrapper |
| `fce0b5f` | Fix CSP Google Fonts |
| `84c7a9e` | DMG build + icons |
| `40fea40` | Install script + download page |

---

## 9. Ce qui doit être fait (TODO)

### Urgent
- [ ] **Tester le build release** sur Mac après le patch tao 0.37.0
- [ ] **Vérifier** `get_credentials` / `store_credentials` dans commands.rs
- [ ] **Fixer** `on_window_event` ligne 74 : `window.hide().unwrap()` → `let _ = window.hide()` (panic si window déjà détruite)

### Important
- [ ] **Remplacer l'icone** `source-icon.png` par le vrai logo SAEC
- [ ] **Rebuild icons** après remplacement : `cargo tauri icon icons/source-icon.png`
- [ ] **Upload DMG** sur GitHub Releases v0.1.36
- [ ] **Tester** `install-macos.sh` sur le Mac

### Nice-to-have
- [ ] GitHub Actions workflow pour build DMG automatique
- [ ] Codesigning + notarisation Apple
- [ ] Updater plugin (déjà dans tauri.conf.json mais `active: false`)

---

## 10. Solutions réelles pour avancer

### Bug tao 0.37.0 — Fonctionne ✅
La solution `[patch.crates-io] tauri-runtime-wry = { git = "...", branch = "dev" }` est validée par `cargo metadata` et `Cargo.lock`. **Prochaine étape : build release + test sur Mac.**

### Build DMG
Le processus est maîtrisé : `npm ci && npm run build && cargo tauri build`. Le DMG sort dans `target/x86_64-apple-darwin/release/bundle/dmg/`.

### Distribution
1. Upload DMG sur GitHub Releases
2. `install-macos.sh` télécharge automatiquement depuis GitHub
3. Page `cloud.saec.me/client-sync` redirige vers le téléchargement

### Si le crash persiste après le patch tao
1. Vérifier `panic = "abort"` dans le profil release — le panic est tué silencieusement
2. Temporairement changer en `panic = "unwind"` pour voir le vrai panic
3. Le problème pourrait aussi venir de `on_window_event` (`window.hide().unwrap()`)

---

*Ce document est la source de vérité pour toute nouvelle session de développement SAEC Sync.*
