# Passation SAEC Sync — Session complète

> **Dernière mise à jour :** 20 septembre 2026
> **Projet :** SAEC Cloud — SaaS multi-tenant (PHP 8.3+, MySQL 8.x, Apache/Nginx)
> **NAS :** Synology DS420j, `192.168.0.201`
> **Client Tauri :** `saec-sync/` (React/Vite + Rust), v0.1.36
> **Repo GitHub :** `saec-oolivvv/cloud`
> **État :** 🟡 Polling Rust OK, transition frontend après token reçu à débugger

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

## 3. Flux d'authentification actuel (corrigé)

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

---

## 5. État actuel — CE QUI MARCHE

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

### 🟡 À débugger
10. Le frontend reçoit l'event `auth://token` mais **la transition vers le Dashboard ne se fait pas**
11. L'app reste sur l'écran "Autorisation en cours"

---

## 6. 🔴 BLOCAGE ACTUEL : après token reçu, pas de transition

### Symptôme
- Terminal Rust montre `auth_poll SUCCESS — token received` ✅
- Mais l'app reste sur "Autorisation en cours" au lieu de passer au Dashboard
- Pas d'erreur visible dans le terminal Rust

### Hypothèses
1. **`listen('auth://token')` ne reçoit pas l'event** — possible bug format event Tauri v2
2. **`login()` échoue silencieusement** — set state mais pas de re-render
3. **`onSuccess()` ne déclenche pas `checkAuth()`** — le `isAuthenticated` ne passe pas à `true`
4. **Race condition** — le `useEffect` cleanup détruit le listener avant que l'event n'arrive
5. **Le component AuthView est démonté** avant que le listener ne se déclenche

### Ce qu'il faut vérifier prochaine session
1. Ouvrir WebKit Inspector (clic droit → Inspecter → Console) pour voir les logs frontend
2. Vérifier si `listen('auth://token')` est bien actif au moment de l'émission
3. Vérifier si `login()` est appelé
4. Vérifier si `onSuccess()` est appelé
5. Vérifier si `isAuthenticated` passe à `true`

### Pistes de fix possibles
- **Piste A** : Émettre l'event avec un nom sans `://` (ex: `auth-token`) au cas où Tauri filtre les `://`
- **Piste B** : Ajouter un `console.log` dans le listener pour confirmer qu'il reçoit l'event
- **Piste C** : Utiliser `app.emit_all()` au lieu de `app.emit()` pour forcer l'émission à toutes les fenêtres
- **Piste D** : Vérifier que `login()` appelle bien `set({ isAuthenticated: true })` et que le composant parent réagit

---

## 7. Bugs en cours

### 7.1. tao 0.35.3 sur macOS 26 — CRASH RELEASE
- `cargo tauri build` crash en release mode
- Fix dans tao >= 0.36.0 (Tauri 2.12+)
- Workaround: `cargo tauri dev` (debug mode)

### 7.2. Icone tray = carré vert
- Fix: `cp icons/source-icon.png icons/tray-icon.png`
- Status: ✅ Corrigé (fallback, pas le vrai logo)

### 7.3. SCP Mac → NAS ne fonctionne pas
- Workaround: GitHub Releases

---

## 8. Bug tao 0.35.3 — Options

| Option | Faisabilité | Risque |
|---|---|---|
| **A. Fork tao 0.35.4** | ✅ Recommandé | Faible |
| B. Attendre Tauri 2.12 | ✅ Mais inconnu | Aucun |
| C. Tout sur git dev | ❌ Plugins cassés | Élevé |
| D. Tauri 3.0.0-alpha | ⚠️ Possible | Élevé |

---

## 9. Build DMG

```bash
cd saec-sync && rm -rf node_modules dist target && npm ci && npm run build && cargo tauri build
# Output: target/x86_64-apple-darwin/release/bundle/dmg/SAEC Sync_0.1.36_x64.dmg
```

---

## 10. Git commits récents

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

---

## 11. Checklist reprise de session

### Pour débugger la transition post-token
1. `cd /Users/saec/saec-cloud && git stash && git pull`
2. `cd saec-sync && kill $(lsof -ti:1420) 2>/dev/null; cargo tauri dev`
3. Clic "Se connecter" → autoriser dans le navigateur
4. Vérifier terminal Rust : `auth_poll SUCCESS` ✅
5. **Ouvrir WebKit Inspector** (clic droit → Inspecter → Console)
6. Chercher les logs `[auth]` dans la Console
7. Si `listen` ne reçoit rien → **Piste A** (changement nom event)
8. Si `login()` non appelé → vérifier le listener

### Fix probable
Le nom d'event `auth://token` contient `://` — Tauri v2 pourrait filtrer ces caractères.
Essayer `auth-token` au lieu de `auth://token` dans le `emit()` Rust et le `listen()` React.

---

*Ce document est la source de vérité pour toute nouvelle session de développement SAEC Sync.*
