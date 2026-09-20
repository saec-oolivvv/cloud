# Passation SAEC Sync — Session complète

> **Dernière mise à jour :** 20 septembre 2026
> **Projet :** SAEC Cloud — SaaS multi-tenant (PHP 8.3+, MySQL 8.x, Apache/Nginx)
> **NAS :** Synology DS420j, `192.168.0.201`
> **Client Tauri :** `saec-sync/` (React/Vite + Rust), v0.1.36
> **Repo GitHub :** `saec-oolivvv/cloud`
> **État :** ⏸ PAUSE — app bloque sur "Initialisation..." après compilation

---

## 1. Environnement

| Élément | Détail |
|---|---|
| Mac | `saec@MacBook-Pro-de-SAEC`, Intel x86_64, macOS 26.7 (Tahoe) |
| NAS | Synology DS420j, IP `192.168.0.201` |
| Serveur web | Synology Web Station (racine diferente de `public/`) |
| SCP | **Ne fonctionne pas** depuis le Mac vers le NAS |
| App name | SAEC Sync |
| Identifier | `me.saec.sync` |
| Version | `0.1.36` |
| GitHub repo | `saec-oolivvv/cloud` |

---

## 2. Fichiers clés

| Fichier | Rôle |
|---|---|
| `saec-sync/Cargo.toml` | Workspace deps (crates.io, sans patch) |
| `saec-sync/src-tauri/Cargo.toml` | Deps app, `[profile.release]` |
| `saec-sync/src-tauri/tauri.conf.json` | Config Tauri (CSP, windows, bundle) |
| `saec-sync/src-tauri/src/main.rs` | Entry point, setup_tray, window management |
| `saec-sync/src-tauri/src/ipc/commands.rs` | Commands IPC + `setup_tray()` |
| `saec-sync/src/App.tsx` | Frontend React (loading screen + init flow) |
| `saec-sync/src/store/auth.ts` | Auth store — appelle `invoke('get_credentials')` |
| `install-macos.sh` | Script installation automatique macOS |
| `MEMORY.md` | Mémoire de session (build process + bugs) |
| `passation.md` | Ce document |

---

## 3. Bugs trouvés et corrigés

### 3.1. `manage(sync_engine)` → `manage(Arc::new(sync_engine))`
**Fichier :** `main.rs:33`
**Fix :** Wrapper dans `Arc::new()`.
**Statut :** ✅ Corrigé

### 3.2. CSP trop restrictive (Google Fonts)
**Fichier :** `tauri.conf.json:30`
**Fix :** Ajouté `https://fonts.googleapis.com` à `style-src` et `https://fonts.gstatic.com` à `font-src`.
**Statut :** ✅ Corrigé

### 3.3. `tray-icon.png` corrompu
**Fix :** Copié `source-icon.png` par-dessus `tray-icon.png`.
**Statut :** ✅ Corrigé (mais icone = carré vert, pas logo SAEC)

### 3.4. `setup_tray` panic si tray indisponible
**Fichier :** `main.rs:52`
**Fix :** Wrappé dans `if let Err(e) = ... { tracing::warn! }` — non-fatal.
**Statut :** ✅ Corrigé

### 3.5. `window.show()` / `window.set_title()` panic
**Fichier :** `main.rs:66-67`
**Fix :** `let _ = window.show(); let _ = window.set_title(...);`
**Statut :** ✅ Corrigé

### 3.6. `visible: false` dans tauri.conf.json
**Fix :** Changé en `visible: true`.
**Statut :** ✅ Corrigé

### 3.7. `trayIcon` cassé dans tauri.conf.json
**Fix :** Supprimé `trayIcon` de la config JSON.
**Statut :** ✅ Corrigé

### 3.8. `on_window_event` panic
**Fichier :** `main.rs:74`
**Fix :** `window.hide().unwrap()` → `let _ = window.hide()`
**Statut :** ✅ Corrigé

### 3.9. `get_credentials` / `store_credentials` manquants
**Fichier :** `auth.ts` appelle `invoke('get_credentials')` et `invoke('store_credentials')`
**Problème :** Ces commands n'existaient pas dans `main.rs` ni dans `commands.rs`.
**Fix :** Ajouté les deux commands + enregistrement dans `generate_handler![]`.
**Statut :** ✅ Corrigé

### 3.10. ⚠️ App bloque sur "Initialisation..."
**Problème :** Après compilation réussie, l'app s'ouvre mais reste bloquée sur l'écran de chargement "Initialisation..." sans jamais avancer.
**Symptôme :** `cargo tauri dev` compile OK, app se lance, affiche "Initialisation...", puis plus rien.
**Statut :** 🔴 **NON RÉSOLU — en attente d'investigation**

---

## 4. Bug critique : tao 0.35.3 sur macOS 26 (Tahoe)

### Symptôme initial
- `cargo tauri dev` → crash avec "web content process terminated"
- `cargo tauri build --release` → crash silencieux

### Racine identifiée
**Tauri bug [#15517](https://github.com/tauri-apps/tauri/issues/15517)**
Le crate `tao 0.35.3` panique dans `did_finish_launching` sur macOS 26.
Fix dans `tao >= 0.36.0` (release 2026-07-29).

### Tentatives de patch (toutes échouées)

| Approche | Résultat |
|---|---|
| `[patch.crates-io] tauri-runtime-wry` depuis git dev | ✅ Compile, mais API mismatch avec tauri 2.11.6 crates.io |
| `[patch.crates-io] tauri-runtime + tauri-utils` depuis git dev | ✅ Compile, même API mismatch |
| `[patch.crates-io]` pour TOUT le family tauri depuis git dev | ❌ `tauri` patch ignorée par cargo (crates.io 2.11.6 > git 2.11.5) |
| `tauri` en workspace dependency depuis git direct | ❌ `links = "Tauri"` conflict avec plugins crates.io |
| `[patch.crates-io] tao` depuis fork | ❌ Semver incompatible (`^0.35` vs `0.37`) |

### Pourquoi ça ne peut PAS marcher avec les patches
1. `tao 0.37` n'est pas semver-compatible avec `tao ^0.35` (exigé par `wry 0.55.1`)
2. `wry 0.57` n'est pas semver-compatible avec `wry ^0.55` (exigé par `tauri-runtime-wry 2.11.4`)
3. `tauri-runtime-wry` git a une API différente de `tauri-runtime-wry` crates.io (même version 2.11.4 !)
4. `tauri` git (2.11.5) et `tauri` crates.io (2.11.6) ont `links = "Tauri"` → ne peuvent coexister
5. Les plugins (`tauri-plugin-shell` etc.) depuis crates.io résolvent `tauri` depuis crates.io → conflict

### Solutions réelles possibles

#### Option A : Fork tao avec fix + version 0.35.4 (RECOMMANDÉ)
1. Forker `tauri-apps/tao`
2. Cherry-pick le fix macOS 26 depuis le commit qui a introduit la correction
3. Bumper la version à `0.35.4` (semver-compatible avec `^0.35`)
4. `[patch.crates-io] tao = { git = "https://github.com/saec-fork/tao", branch = "macos-26-fix" }`
5. Ça résout tao 0.35.4 pour `wry 0.55.1` → pas de changement dans wry/tauri-runtime

#### Option B : Attendre Tauri 2.12 stable
- Tauri 2.12.x inclura tao 0.37+ nativement
- `cargo update -p tauri` suffira
- **Estimation :** inconnue, probablement Q4 2026

#### Option C : Tout passer sur git dev (plugins inclus)
- Patcher aussi les plugins depuis `tauri-apps/plugins-workspace` v2 branch
- Mais les plugins-workspace dépendent de `tauri = { version = "2.10" }` → résoudra crates.io
- **Impossible** tant que les plugins ne sont pas patchés avec la bonne source tauri

#### Option D : Utiliser Tauri 3.0.0-alpha
- `tauri 3.0.0-alpha.1` (2026-09-13) utilise probablement tao 0.37+
- Mais c'est un alpha, API potentiellement cassées
- **Risque élevé**

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
- **`cargo tauri dev`** = mode dev (Vite localhost:1420)
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
- Compatible macOS (overlay dédié) et autres plateformes (lien direct)
- Redimensionnement responsive (desktop/tablette/mobile)

---

## 8. Git commits de la session

| Hash | Message |
|---|---|
| `e9e8211` | Fix sync_engine Arc wrapper |
| `fce0b5f` | Fix CSP Google Fonts |
| `84c7a9e` | DMG build + icons |
| `40fea40` | Install script + download page |
| `5ee4a73` | docs: update passation.md |
| `839dabd` | fix: add missing get_credentials + store_credentials commands |
| `31fe12b` | fix: prevent panic in on_window_event hide() |
| `10da022` | fix: patch tauri-runtime + tauri-utils from git dev |
| `0538c91` | fix: patch ALL tauri crates from git dev |
| `c688f07` | fix: tauri from git in workspace deps |
| `f15f4b6` | revert: remove all tauri git patches, back to crates.io |

---

## 9. État actuel du code (après toutes les sessions)

### Ce qui fonctionne ✅
- `cargo tauri dev` compile et lance l'app (sans crash)
- `get_credentials` et `store_credentials` sont enregistrés comme commands
- `on_window_event` ne peut plus panic
- CSP autorise Google Fonts
- Setup tray non-fatal

### Ce qui ne fonctionne PAS 🔴
- **App bloque sur "Initialisation..."** — l'écran de chargement s'affiche mais l'app ne passe jamais à l'étape suivante
- Le bug tao 0.35.3 sur macOS 26 n'est PAS résolu (crash release toujours probable)

### Hypothèses sur le blocage "Initialisation..."
1. `invoke('get_credentials')` pourrait bloquer si le keyring macOS demande une autorisation
2. `invoke('sync_status')` pourrait bloquer si le sync engine n'est pas prêt
3. Un error silencieux dans la chaîne `checkAuth()` → `fetchStatus()` pourrait empêcher `setInitialized(true)`
4. Le `keyring` crate sur macOS utilise le Keychain — accès potentiellement bloquant en dev sans entitlements

---

## 10. Prochaines étapes quand on reprend

### Immédiat (investigation blocage "Initialisation...")
1. Ajouter des `console.log` dans `App.tsx` init() pour voir où ça bloque
2. Ajouter `tracing::info!` dans chaque command Rust pour voir laquelle ne répond pas
3. Vérifier si le keyring fonctionne en dev (test manuel `invoke('get_credentials')`)
4. Tester si `fetchStatus()` hang

### Important
- **Option A recommandée** pour le bug tao : fork tao avec fix + version 0.35.4
- Remplacer l'icone `source-icon.png` par le vrai logo SAEC
- Upload DMG sur GitHub Releases v0.1.36
- Tester `install-macos.sh` sur le Mac

### Nice-to-have
- GitHub Actions workflow pour build DMG
- Codesigning + notarisation Apple
- Updater plugin

---

*Ce document est la source de vérité pour toute nouvelle session de développement SAEC Sync.*
