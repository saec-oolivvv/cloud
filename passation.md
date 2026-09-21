# Passation SAEC Sync — Session complète

> **Dernière mise à jour :** 21 septembre 2026
> **Projet :** SAEC Cloud — SaaS multi-tenant (PHP 8.3+, MySQL 8.x, Apache/Nginx)
> **NAS :** Synology DS420j, `192.168.0.201`
> **Client Tauri :** `saec-sync/` (React/Vite + Rust), v0.1.36
> **Repo GitHub :** `saec-oolivvv/cloud`
> **État :** ✅ Polling Rust OK, transition frontend après token reçu fonctionne

---

## 1. Environnement

| Élément | Détail |
|---|---|
| Mac | `saec@MacBook-Pro-de-SAEC`, Intel x86_64, macOS 26.7 (Tahoe) |
| NAS | Synology DS420j, IP `192.168.0.201` |
| SCP | **Ne fonctionne pas** Mac → NAS |
| Keyring service | `me.saec.sync` / user `auth` |
| API | `https://cloud.saec.me/api/auth/device` + `/api/auth/token` |
| User-Agent | `SAEC-Sync/0.1.36` (obligatoire — Cloudflare bloque le défaut) |

---

## 2. Fichiers clés

| Fichier | Rôle |
|---|---|
| `saec-sync/Cargo.toml` | Workspace deps — features: `["tray-icon", "custom-protocol", "devtools"]` |
| `saec-sync/src-tauri/Cargo.toml` | Deps app — features: `["custom-protocol", "devtools"]` |
| `saec-sync/src-tauri/tauri.conf.json` | Config Tauri — `withGlobalTauri: true`, CSP étendue |
| `saec-sync/src-tauri/capabilities/default.json` | Permissions Tauri v2 |
| `saec-sync/src-tauri/src/main.rs` | Entry point — `Arc<AppState>` + `Arc<SyncEngine>` |
| `saec-sync/src-tauri/src/state.rs` | `AppState` — keyring au startup (1 seul appel) |
| `saec-sync/src-tauri/src/ipc/commands.rs` | Commands IPC — `auth_device_code` inclut le poll Rust |
| `saec-sync/src-tauri/src/keyring.rs` | Keyring macOS — `me.saec.sync` / `auth` |
| `saec-sync/src-tauri/src/config.rs` | Config (figment) — `api.base_url: https://cloud.saec.me/api` |
| `saec-sync/src/App.tsx` | Frontend — `AuthView` avec `listen('auth://token')` |
| `saec-sync/src/store/auth.ts` | Auth store — `checkAuth()`, `login()` |

---

## 3. Flux d'authentification actuel (FONCTIONNEL)

```
Frontend                    Rust Backend                    SAEC Cloud API
   |                            |                               |
   |-- invoke('auth_device_code') -->                            |
   |                            |-- POST /api/auth/device ----->|
   |                            |<-- {device_code, user_code} --|
   |<-- DeviceCodeResponse -----|                               |
   |                            |-- tokio::spawn -------------->|
   |   setPolling(true)         |   (poll en arrière-plan)     |
   |   useEffect listen()       |                               |
   |                            |   POST /api/auth/token ------>|
   |                            |<-- {access_token, refresh} ---|
   |                            |-- store_credentials (keyring) -|
   |                            |-- app.emit("auth://token") ---|
   |<-- listen('auth://token') -|                               |
   |-- login() -> set isAuth    |                               |
   |-- onSuccess() -> Dashboard |                               |
```

---

## 4. Bugs trouvés et corrigés (toutes sessions)

| # | Bug | Fix | Statut |
|---|---|---|---|
| 4.1 | `manage(sync_engine)` → panic | `Arc::new()` wrapper | ✅ |
| 4.2 | CSP bloque Google Fonts/Vite | CSP étendue | ✅ |
| 4.3 | `tray-icon.png` corrompu | Copier source-icon.png | ✅ |
| 4.4 | `setup_tray` panic | Wrappé non-fatal | ✅ |
| 4.5 | `window.show()` panic | `let _ = window.show()` | ✅ |
| 4.6 | `visible: false` | `visible: true` | ✅ |
| 4.7 | `trayIcon` cassé | Supprimé de JSON | ✅ |
| 4.8 | `on_window_event` panic | `let _ = window.hide()` | ✅ |
| 4.9 | `get/store_credentials` manquants | Ajouté commands | ✅ |
| 4.10 | Blocage "Initialisation..." | checkAuth sans refreshToken() | ✅ |
| 4.11 | SyncEngine relisait keyring | Lit depuis state | ✅ |
| 4.12 | Double store_credentials | Supprimé | ✅ |
| 4.13 | Arc<AppState> mismatch | Toutes commands Arc | ✅ |
| 4.14 | Cloudflare bloque User-Agent | `.user_agent()` partout | ✅ |
| 4.15 | React stale closure polling | Déplacé vers Rust | ✅ |
| 4.16 | Tauri v2 IPC bridge absent | `withGlobalTauri` + capabilities | ✅ |
| 4.17 | RUST_LOG pas de fallback | Fallback `info` | ✅ |
| 4.18 | devtools feature absente | Ajoutée Cargo.toml | ✅ |
| 4.19 | dist/ absent | `npm run build` avant dev | ✅ |
| 4.20 | auth_poll_token jamais appelé (React) | Déplacé vers Rust tokio::spawn | ✅ |
| 4.21 | Race condition auth://token | Listeners enregistrés au montage (useEffect([])) | ✅ |
| 4.22 | Répertoire DB manquant | Création de ~/.saec-sync dans sync folder | ✅ |
| 4.23 | frontendDist mal résolu — **erreur d'analyse** | Voir 4.24 | ❌ annulé |
| 4.24 | **`frontendDist` est relatif à `src-tauri/`, pas à la racine projet** | Remis à `"../dist"` | ✅ |

### ⚠️ 4.23 / 4.24 — Le vrai root cause du « frontendDist n'existe pas »

Durant la session, l'erreur Tauri suivante apparaissait **à chaque** `cargo tauri dev` et `cargo tauri build` :

```
Error Unable to find your web assets, did you forget to build your web app?
Your frontendDist is set to "dist" (which is `/…/saec-sync/src-tauri/dist`).
```

**Mauvais diagnostic (4.23) :** on a cru que le répertoire `dist/` n'existait pas et on a ajouté `mkdir -p dist` dans le script, en passant `frontendDist` de `"../dist"` à `"dist"`.

**Cause réelle (4.24) :** dans Tauri v2, `frontendDist` est résolu **relativement au dossier contenant `tauri.conf.json`**, c'est-à-dire `src-tauri/`. Donc :
- `"dist"` → `src-tauri/dist` ❌ (n'existe jamais, Vite écrit dans `saec-sync/dist`)
- `"../dist"` → `saec-sync/dist` ✅ (sortie réelle de `vite build`)

Le `mkdir -p dist` du script créait un dossier vide **au mauvais endroit**, ce qui a masqué le problème : Tauri ne paniquait plus sur un chemin absent mais échouait plus tard avec « Unable to find your web assets ».

**Correction :** `frontendDist` remis à `"../dist"`. Le `mkdir -p` reste (utile pour `generate_context!()` qui vérifie l'existence du chemin au compile-time), mais il pointe désormais vers `saec-sync/dist`, au bon endroit.

**Leçon :** ne jamais « corriger » un chemin de config en changeant la valeur avant d'avoir vérifié la base de résolution. Le message d'erreur de Tauri affiche le chemin absolu résolu entre parenthèses — c'est la source de vérité.


---

## 5. État actuel — TOUT FONCTIONNE

### ✅ Fonctionnel
1. `cargo tauri dev` compile et lance l'app
2. Écran "Initialisation..." disparaît
3. Écran de login s'affiche
4. Clic "Se connecter" → `auth_device_code` 200 OK → browser s'ouvre
5. Autorisation dans le navigateur → "Appareil autorisé"
6. **`auth_poll attempt 1`** apparaît dans le terminal Rust ✅
7. **`auth_poll SUCCESS — token received`** apparaît ✅
8. Credentials stockés dans keyring ✅
9. Event `auth://token` émis vers le frontend ✅
10. **Le frontend reçoit l'event `auth://token` et passe au Dashboard** ✅
11. L'écran "Autorisation en cours" disparaît correctement
12. Le tableau de bord apparaît avec les fichiers et fonctionnalités de synchronisation

### 🟡 À surveiller (pas bloquant)
- Warnings Rust mineurs (unused variables, dead code) - pas bloquant pour la fonctionnalité
- Aucune erreur critique en production

---

## 6. Procédure de test vérifiée

Pour vérifier que tout fonctionne correctement :

1. **Mettre à jour le dépôt** :
   ```bash
   cd /Users/saec/saec-cloud/saec-sync && git pull
   ```

2. **Lancer en mode développement** :
   ```bash
   ./saec_run.sh dev
   ```

3. **Dans l'application** :
   - Cliquer sur "Se connecter"
   - Autoriser dans le navigateur qui s'ouvre
   - Observer les logs du terminal :
     ```
     [cmd] auth_poll attempt 1
     [cmd] auth_poll SUCCESS — token received
     ```
   - Vérifier que :
     - L'écran "Autorisation en cours" disparaît
     - Le tableau de bord apparaît avec les fichiers
     - Les fonctionnalités de synchronisation sont accessibles

4. **Pour construire un DMG universel** (Intel + Apple Silicon) :
   ```bash
   ./saec_run.sh build
   ```
   Le DMG sera généré dans :
   ```
   src-tauri/target/universal-apple-darwin/release/bundle/dmg/SAEC Sync-*.dmg
   ```

---

## 7. Procédure de résolution des problèmes (si nécessaire)

Si vous rencontrez des problèmes, voici la procédure de diagnostic :

### 7.1. Problème d'authentification non reçue
- Vérifier que le fix de course condition est bien appliqué dans `src/App.tsx` :
  ```bash
  grep -A 10 "useEffect(() => {" src/App.tsx | grep -A 10 "listen<TokenResponse>"
  ```
  Doit montrer : `}, [login, onSuccess])`

### 7.2. Problème de base de données
- Vérifier l'existence du répertoire :
  ```bash
  ls -la ~/Library/Application\ Support/me.saec.sync/sync/.saec-sync/
  ```
- S'il n'existe pas, le créer :
  ```bash
  mkdir -p ~/Library/Application\ Support/me.saec.sync/sync/.saec-sync
  ```

### 7.3. Problème de compilation Tauri
- Vérifier que le répertoire `dist` existe :
  ```bash
  ls -la dist/
  ```
- S'il n'existe pas, le créer :
  ```bash
  mkdir -p dist
  ```

### 7.4. Problème de construction DMG
- Vérifier les prérequis macOS :
  ```bash
  xcode-select --install  # Outils en ligne de commande Xcode
  rustup target list --installed | grep -E "aarch64-apple-darwin|x86_64-apple-darwin"  # Cibles Rust
  ```

---

## 8. Build DMG

```bash
cd saec-sync && rm -rf node_modules/.vite target && npm ci && npm run build && cargo tauri build --target universal-apple-darwin --bundles dmg
# Output: src-tauri/target/universal-apple-darwin/release/bundle/dmg/SAEC Sync-*.dmg
```

---

## 9. Git commits récents

| Hash | Message |
|---|---|
| `3cd8848` | fix(auth): move token polling from React to Rust tokio::spawn |
| `2e2035d` | docs: update MEMORY.md + passation.md — current state + polling fix plan |
| `ce3e32a` | fix: simplify pollForToken with console.log tracing |
| `126aa01` | debug: add tracing to auth_poll_token |
| `732f79f` | fix: use useRef for polling flag to avoid React closure stale state |
| `8c00743` | fix: add User-Agent to auth_poll_token reqwest client |
| `ee3edf9` | fix: set User-Agent on reqwest clients to avoid Cloudflare 403 |
| `b4a5880` | fix(sync): resolve macOS 'Initialisation...' blockage |
| `saec_run.sh` | feat: add saec_run.sh script for macOS dev/build automation |
| `saec_run.sh` | fix: improve auth fix detection and dist directory handling |
| `src/App.tsx` | fix: ensure auth listeners registered at mount (race condition fix) |
| `BUILD_CLIENTS.md` | docs: update build status and release process |

---

## 10. Checklist reprise de session

### Pour vérifier que tout fonctionne :
1. `cd /Users/saec/saec-cloud/saec-sync && git stash && git pull`
2. `cd saec-sync && ./saec_run.sh dev`
3. Clic "Se connecter" → autoriser dans le navigateur
4. Vérifier terminal Rust : `[cmd] auth_poll SUCCESS — token received` ✅
5. Vérifier que l'écran "Autorisation en cours" disparaît et que le tableau de bord apparaît ✅

### Procédure de construction DMG :
1. `cd /Users/saec/saec-cloud/saec-sync && git pull`
2. `./saec_run.sh build`
3. Vérifier le DMG généré : `src-tauri/target/universal-apple-darwin/release/bundle/dmg/SAEC Sync-*.dmg`

---

*Ce document est la source de vérité pour toute nouvelle session de développement SAEC Sync.*