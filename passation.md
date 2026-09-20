# Passation SAEC Sync — Session complète

> **Dernière mise à jour :** 20 septembre 2026
> **Projet :** SAEC Cloud — SaaS multi-tenant (PHP 8.3+, MySQL 8.x, Apache/Nginx)
> **NAS :** Synology DS420j, `192.168.0.201`
> **Client Tauri :** `saec-sync/` (React/Vite + Rust), v0.1.36
> **Repo GitHub :** `saec-oolivvv/cloud`
> **État :** 🔴 BLOCAGE — auth_device_code OK, polling token jamais déclenché

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
| Keyring service | `me.saec.sync` / user `auth` |
| API endpoints | `POST /api/auth/device`, `POST /api/auth/token` |

---

## 2. Fichiers clés

| Fichier | Rôle |
|---|---|
| `saec-sync/Cargo.toml` | Workspace deps (crates.io, sans patch) — features: `["tray-icon", "custom-protocol", "devtools"]` |
| `saec-sync/src-tauri/Cargo.toml` | Deps app, `[profile.release]` — features: `["custom-protocol", "devtools"]` |
| `saec-sync/src-tauri/tauri.conf.json` | Config Tauri (CSP, windows, bundle, `withGlobalTauri: true`) |
| `saec-sync/src-tauri/capabilities/default.json` | Permissions Tauri v2 (IPC, window, event, shell, dialog, opener) |
| `saec-sync/src-tauri/src/main.rs` | Entry point, setup_tray, window management — gère `Arc<AppState>` + `Arc<SyncEngine>` |
| `saec-sync/src-tauri/src/state.rs` | `AppState` — `RwLock<Config>`, `RwLock<Option<StoredCredentials>>`, keyring au startup |
| `saec-sync/src-tauri/src/config.rs` | Config loading (figment) — `api.base_url: https://cloud.saec.me/api` |
| `saec-sync/src-tauri/src/keyring.rs` | Keyring macOS — service `me.saec.sync`, user `auth` |
| `saec-sync/src-tauri/src/ipc/commands.rs` | Commands IPC + `setup_tray()` — toutes les commands Tauri |
| `saec-sync/src/App.tsx` | Frontend React — init chain, AuthView (device code + polling) |
| `saec-sync/src/store/auth.ts` | Auth store — `checkAuth()`, `login()`, `refreshToken()` |
| `install-macos.sh` | Script installation automatique macOS |
| `MEMORY.md` | Mémoire de session (build process + bugs) |
| `passation.md` | Ce document |

---

## 3. Architecture technique détaillée

### Frontend (React/Vite)
- **Framework:** React 18 + TypeScript
- **Build:** Vite 5.4.21, port 1420
- **State:** Zustand + localStorage (persist)
- **Styling:** Tailwind CSS
- **Tauri API:** `@tauri-apps/api ^2.0.0` (resolved 2.11.1)

### Backend (Rust/Tauri)
- **Tauri:** v2.11 (crates.io)
- **Keyring:** `keyring` crate (macOS Keychain)
- **HTTP:** `reqwest` (User-Agent: `SAEC-Sync/0.1.36`)
- **Config:** `figment` (TOML + env)
- **Logging:** `tracing` + `tracing_subscriber`

### Flux d'authentification (device code flow)
```
Frontend                    Rust Backend                    SAEC Cloud API
   |                            |                               |
   |-- auth_device_code ------->|                               |
   |                            |-- POST /api/auth/device ----->|
   |                            |<-- {device_code, user_code} --|
   |<-- DeviceCodeResponse -----|                               |
   |-- setPolling(true)         |                               |
   |-- startPolling()           |                               |
   |   (for loop, 60 iters)    |                               |
   |                            |                               |
   |   [BLOCAGE ICI]           |                               |
   |   invoke('auth_poll_token') n'est JAMAIS appelé            |
   |                            |                               |
   |   [SI appelait]            |                               |
   |                            |-- POST /api/auth/token ------>|
   |                            |<-- {access_token, refresh} ---|
   |                            |-- store_credentials (keyring) -|
   |<-- TokenResponse ---------|                                |
   |-- login() -> set isAuth   |                                |
```

---

## 4. Bugs trouvés et corrigés (toutes sessions)

### 4.1. `manage(sync_engine)` → `manage(Arc::new(sync_engine))`
**Fichier :** `main.rs:33` | **Fix :** Wrapper dans `Arc::new()` | ✅ Corrigé

### 4.2. CSP trop restrictive (Google Fonts + Vite dev + Cloudflare)
**Fichier :** `tauri.conf.json:30` | **Fix :** CSP étendue avec localhost:1420, ws://, tauri.localhost, cloud.saec.me | ✅ Corrigé

### 4.3. `tray-icon.png` corrompu
**Fix :** Copié `source-icon.png` par-dessus `tray-icon.png` | ✅ Corrigé (mais icone = carré vert, pas logo SAEC)

### 4.4. `setup_tray` panic si tray indisponible
**Fichier :** `main.rs:52` | **Fix :** Wrappé dans `if let Err(e) = ... { tracing::warn! }` — non-fatal | ✅ Corrigé

### 4.5. `window.show()` / `window.set_title()` panic
**Fichier :** `main.rs:66-67` | **Fix :** `let _ = window.show(); let _ = window.set_title(...);` | ✅ Corrigé

### 4.6. `visible: false` dans tauri.conf.json
**Fix :** Changé en `visible: true` | ✅ Corrigé

### 4.7. `trayIcon` cassé dans tauri.conf.json
**Fix :** Supprimé `trayIcon` de la config JSON | ✅ Corrigé

### 4.8. `on_window_event` panic
**Fichier :** `main.rs:74` | **Fix :** `window.hide().unwrap()` → `let _ = window.hide()` | ✅ Corrigé

### 4.9. `get_credentials` / `store_credentials` manquants
**Problème :** Ces commands n'existaient pas dans `main.rs` ni dans `commands.rs` | **Fix :** Ajouté + `generate_handler![]` | ✅ Corrigé

### 4.10. Blocage "Initialisation..."
**Problème :** `checkAuth()` appelait `refreshToken()` sur token expiré → boucle infinie
**Fix :** Supprimer appel `refreshToken()` dans `checkAuth()`, set `isAuthenticated=false` directement | ✅ Corrigé

### 4.11. `SyncEngine::new()` relisait keyring directement
**Fix :** `SyncEngine::new()` lit credentials depuis `state.get_credentials()` au lieu de relire keyring | ✅ Corrigé

### 4.12. Double `store_credentials` dans `login()`
**Fix :** Supprimer double `store_credentials` dans `login()` (déjà fait dans `auth_poll_token`) | ✅ Corrigé

### 4.13. Arc<AppState> mismatch
**Problème :** Toutes les commands utilisaient `tauri::State<'_, AppState>` mais main.rs gérait `Arc<AppState>`
**Fix :** Toutes les commands changées en `tauri::State<'_, Arc<AppState>>` | ✅ Corrigé

### 4.14. Cloudflare bloque reqwest default User-Agent
**Problème :** `reqwest/0.13.x` → 403 Cloudflare
**Fix :** `.user_agent("SAEC-Sync/0.1.36")` ajouté sur TOUS les reqwest clients (auth_device_code, auth_poll_token, refresh_token, ApiClient::new) | ✅ Corrigé

### 4.15. React stale closure sur polling
**Problème :** `pollForToken` capturait l'état `polling` comme `false` (stale closure)
**Fix :** `useRef(false)` + simplification en boucle `for` | ✅ Corrigé

### 4.16. Tauri v2 IPC bridge non injecté
**Problème :** `window.__TAURI_INTERNALS__` undefined → invoke ne fonctionne pas
**Fix :** `withGlobalTauri: true` dans tauri.conf.json + `capabilities/default.json` avec `core:default` | ✅ Corrigé

### 4.17. RUST_LOG pas de fallback
**Problème :** `from_default_env()` silencieux si pas de RUST_LOG défini
**Fix :** Fallback vers `info` si pas de variable d'env | ✅ Corrigé

### 4.18. devtools feature absente
**Fix :** `devtools` ajouté aux features tauri dans workspace + src-tauri Cargo.toml | ✅ Corrigé

### 4.19. `npm run build` nécessaire avant `cargo tauri dev`
**Problème :** `frontendDist: "../dist"` n'existe pas si on supprime dist/
**Fix :** `npm run build` avant `cargo tauri dev`, ou laisser Vite créer le dist | ✅ Corrigé

---

## 5. 🔴 BLOCAGE ACTUEL : polling token jamais déclenché

### Symptôme
1. App se lance ✅
2. Écran "Initialisation..." disparaît ✅
3. Écran de login s'affiche ✅
4. Clic "Se connecter" → `auth_device_code` appelé, 200 OK ✅
5. Navigateur s'ouvre sur `/device?code=XXXX`, utilisateur autorise ✅
6. **`auth_poll_token` n'est JAMAIS appelé** 🔴
7. L'app reste sur "Autorisation en cours" indéfiniment

### Logs Rust terminal (ce qu'on voit)
```
[cmd] auth_device_code called
[cmd] device_code_url: https://cloud.saec.me/api/auth/device
[cmd] Sending device code request...
[cmd] Device code response status: 200 OK
```
→ Plus rien. Pas de `auth_poll_token`.

### Ce qui ne s'affiche PAS (car jamais appelé)
```
[cmd] auth_poll_token called
```

### Hypothèses
1. **`startPolling()` n'est jamais exécutée** — probable si `invoke('auth_device_code')` retourne mais le `.then()` ou la suite ne s'exécute pas
2. **Vite sert du code stale** — le git pull dit "Already up to date" mais Vite cache l'ancien JS
3. **WebKit Inspector non consulté** — impossible de voir les `console.log` frontend sans ouvrir l'inspecteur

### Ce qui a été essayé
- `useRef` pour éviter stale closure → pas de changement
- Simplification en boucle `for` au lieu de `while` + ref → pas de changement
- `console.log` à chaque étape → non visible sans WebKit Inspector
- `rm -rf node_modules/.vite dist` → port déjà occupé, multiple Vite instances
- Multiple `git pull` → "Already up to date" (commits déjà présents)
- `npm run build` avant `cargo tauri dev` → dist créé mais polling toujours pas appelé

### 🔴 PROBLÈME FONDAMENTAL
Le `invoke('auth_poll_token')` n'atteint JAMAIS le backend Rust. C'est un problème frontend pur. Les raisons possibles :
1. Le JavaScript exécuté par Vite ne contient PAS le code mis à jour (cache Vite)
2. Une erreur JavaScript silencieuse empêche `startPolling` de s'exécuter
3. Le `setTimeout` dans la boucle `for` ne se résout jamais (stale timer)
4. Tauri IPC a un bug avec les `invoke` imbriqués (premier invoke dans un try/catch, second dans un async non-awaited)

---

## 6. Plan de fix détaillé — PROCHAINE ÉTAPE

### Solution recommandée : déplacer le poll vers Rust (elimine tous les problèmes React)

**Pourquoi :**
- Élimine les closures React, le stale state, les timers JS
- Rust gère le poll dans un `tokio::spawn` et émet un event Tauri vers le frontend
- Le frontend ne fait plus que `listen('auth://token')` — pas de `invoke` dans une boucle

**Comment :**

#### Étape 1 : Nouvelle command `auth_start_polling`
Dans `commands.rs`, modifier `auth_device_code` pour :
1. Retourner le `DeviceCodeResponse` au frontend comme avant
2. **Spawn un `tokio::spawn`** qui poll le token en arrière-plan
3. Le poll tourne en boucle avec `tokio::time::sleep(interval)`
4. Quand token reçu : store credentials, émet `app.emit("auth://token", response)`
5. Si expired/denied : émet `app.emit("auth://error", error)`
6. Timeout après 5 minutes

```rust
#[tauri::command]
pub async fn auth_device_code(
    state: tauri::State<'_, Arc<AppState>>,
    app: tauri::AppHandle,
) -> Result<DeviceCodeResponse, String> {
    // ... existing code to get device code ...

    // Spawn background polling task
    let state_inner = state.inner().clone();
    let app_handle = app.clone();
    let device_code_clone = data.device_code.clone();
    let interval = data.interval;
    let token_url = config.api.token_url.clone();

    tauri::async_runtime::spawn(async move {
        let client = reqwest::Client::builder()
            .timeout(std::time::Duration::from_secs(30))
            .user_agent("SAEC-Sync/0.1.36")
            .build()
            .unwrap();

        for i in 0..60 {
            tokio::time::sleep(std::time::Duration::from_secs(interval)).await;
            tracing::info!("[cmd] auth_poll attempt {}", i + 1);

            let response = client.post(&token_url)
                .json(&serde_json::json!({
                    "grant_type": "urn:ietf:params:oauth:grant-type:device_code",
                    "device_code": device_code_clone,
                    "client_id": "saec-sync-desktop"
                }))
                .send().await;

            match response {
                Ok(resp) if resp.status().is_success() => {
                    if let Ok(token_data) = resp.json::<TokenResponse>().await {
                        // Store credentials
                        let _ = crate::keyring::store_credentials(/* ... */);
                        state_inner.set_credentials(Some(/* ... */));
                        let _ = app_handle.emit("auth://token", &token_data);
                        return;
                    }
                }
                Ok(resp) => {
                    let error_value: serde_json::Value = resp.json().await.unwrap_or_default();
                    let error_desc = error_value.get("error_description")
                        .and_then(|v| v.as_str()).unwrap_or("unknown");
                    match error_desc {
                        "authorization_pending" | "slow_down" => continue,
                        "expired_token" | "access_denied" => {
                            let _ = app_handle.emit("auth://error", error_desc);
                            return;
                        }
                        _ => {
                            tracing::warn!("[cmd] auth_poll unknown error: {}", error_desc);
                            continue;
                        }
                    }
                }
                Err(e) => {
                    tracing::warn!("[cmd] auth_poll network error: {}", e);
                    continue;
                }
            }
        }
        let _ = app_handle.emit("auth://error", "Polling timed out");
    });

    Ok(data)
}
```

#### Étape 2 : Supprimer `auth_poll_token` command
Plus besoin de cette command. Le poll est géré côté Rust.

#### Étape 3 : Modifier le frontend `AuthView`
```tsx
const handleAuth = async () => {
    try {
        setError(null)
        const response = await invoke<DeviceCodeResponse>('auth_device_code')
        setDeviceCode(response)
        setPolling(true)
        // Plus de startPolling() — le Rust poll en arrière-plan
    } catch (err) { /* ... */ }
}

useEffect(() => {
    if (!polling) return

    const unlistenToken = listen<TokenResponse>('auth://token', async (event) => {
        const token = event.payload
        await login(token.access_token, token.refresh_token, token.expires_in, token.tenant_id, token.user_email)
        setPolling(false)
        onSuccess()
    })

    const unlistenError = listen<string>('auth://error', (event) => {
        setError(event.payload)
        setPolling(false)
        setDeviceCode(null)
    })

    return () => {
        unlistenToken.then(fn => fn())
        unlistenError.then(fn => fn())
    }
}, [polling])
```

#### Étape 4 : Tester
1. `git pull` sur Mac
2. `kill $(lsof -ti:1420)` + `pkill -9 -f vite`
3. `cd saec-sync && npm run build && cargo tauri dev`
4. Clic "Se connecter"
5. Vérifier logs Rust : `[cmd] auth_poll attempt 1`, `2`, `3`...
6. Autoriser dans le navigateur
7. Devrait voir : token reçu → credentials stored → login OK → Dashboard

### Risques de cette approche
- **Sécurité** : Aucun impact — le keyring, le chiffrement, le JWT restent identiques
- **Performance** : Un seul `tokio::spawn` léger (sleep + HTTP request toutes les 5s)
- **Cancellation** : Si l'utilisateur ferme l'app pendant le poll, le spawn se termine naturellement

---

## 7. Bugs en cours (toujours ouverts)

### 7.1. tao 0.35.3 sur macOS 26 (Tahoe) — CRASH RELEASE
- **Erreur:** `panic_cannot_unwind` dans `tao::did_finish_launching`
- **Impact:** `cargo tauri build` crash en release mode
- **Status:** Non résolu — en attente Tauri 2.12+ ou fork tao
- **Workaround:** `cargo tauri dev` (debug mode) fonctionne
- **Voir:** Section 8 pour les options de fix

### 7.2. Icone tray = carré vert
- **Cause:** `cargo tauri icon` produit un fichier trop petit (108 bytes)
- **Fix:** `cp icons/source-icon.png icons/tray-icon.png` après génération
- **Status:** ✅ Corrigé, mais c'est le fallback (pas le vrai logo SAEC)

### 7.3. SCP Mac → NAS ne fonctionne pas
- **Impact:** Impossible de transférer le DMG directement
- **Workaround:** GitHub Releases ou clé USB

### 7.4. WebKit Inspector non accessible (pour diagnostic)
- **Impact:** Impossible de voir les `console.log` frontend
- **Note:** Clic droit → Inspecter dans la fenêtre Tauri dev

---

## 8. Bug tao 0.35.3 — Analyse détaillée

### Racine
Tauri 2.11 utilise `tao 0.35.3` qui panique sur macOS 26 (Tahoe).
Fix dans `tao >= 0.36.0` (release 2026-07-29).

### Pourquoi les patches ne marchent pas
1. `tao 0.37` n'est pas semver-compatible avec `tao ^0.35` (exigé par `wry 0.55.1`)
2. `wry 0.57` n'est pas semver-compatible avec `wry ^0.55` (exigé par `tauri-runtime-wry 2.11.4`)
3. `tauri-runtime-wry` git a une API différente de crates.io (même version 2.11.4 !)
4. `links = "Tauri"` empêche coexistence git + crates.io
5. Les plugins crates.io résolvent `tauri` crates.io → conflict

### Options réelles

| Option | Faisabilité | Risque |
|---|---|---|
| **A. Fork tao 0.35.4** | ✅ Meilleure option | Faible — cherry-pick le fix, bumper version |
| B. Attendre Tauri 2.12 | ✅ Mais timing inconnu | Aucun — patience |
| C. Tout sur git dev | ❌ Plugins cassés | Élevé — API mismatch |
| D. Tauri 3.0.0-alpha | ⚠️ Possible | Élevé — alpha instable |

### Option A détaillée (recommandée)
1. Forker `tauri-apps/tao`
2. Cherry-pick le fix macOS 26
3. Bumper la version à `0.35.4`
4. `[patch.crates-io] tao = { git = "https://github.com/saec-fork/tao", branch = "macos-26-fix" }`
5. Ça résout tao pour `wry 0.55.1` → pas de changement wry/tauri-runtime

---

## 9. Build DMG

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
npm run build
cargo tauri build
```

### Sortie
```
target/x86_64-apple-darwin/release/bundle/dmg/SAEC Sync_0.1.36_x64.dmg
```

### Installation sur macOS
```bash
hdiutil attach <chemin>.dmg
cp -R /Volumes/SAEC\ Sync/SAEC\ Sync.app /Applications/
hdiutil detach /Volumes/SAEC\ Sync
codesign --force --deep --sign - "/Applications/SAEC Sync.app"
open "/Applications/SAEC Sync.app"
```

### Notes
- **`cargo tauri build` = release par défaut** — pas de flag `--release`
- **`cargo tauri dev`** = mode dev (Vite localhost:1420)
- SCP ne fonctionne pas → upload DMG via GitHub Releases

---

## 10. Script d'installation macOS (`install-macos.sh`)

- Vérification prérequis (uname, curl, hdiutil)
- Détection architecture (arm64 / x86_64)
- Téléchargement DMG (GitHub Releases > cloud.saec.me > local)
- Montage DMG, copie dans `/Applications`
- Configuration serveur API
- Retire quarantine (`xattr -dr com.apple.quarantine`)
- Vérification Gatekeeper

---

## 11. Git commits de la session

| Hash | Message |
|---|---|
| `ce3e32a` | fix: simplify pollForToken with console.log tracing |
| `126aa01` | debug: add tracing to auth_poll_token |
| `732f79f` | fix: use useRef for polling flag to avoid React closure stale state |
| `8c00743` | fix: add User-Agent to auth_poll_token reqwest client |
| `ee3edf9` | fix: set User-Agent on reqwest clients to avoid Cloudflare 403 |
| `573cb3d` | debug: add verbose logging to auth_device_code request |
| `831e0ec` | debug: add tracing to auth_device_code |
| `bbfd9aa` | fix: show actual Tauri error message on auth failure |
| `5e1d6cf` | fix: wrap AppState in Arc for Tauri state management |
| `c4efbd4` | fix: enable devtools feature + default RUST_LOG=info for debugging |
| `55bee73` | diagnostic: check Tauri IPC bridge availability and show error |
| `0ddda86` | fix: add withGlobalTauri to force IPC bridge injection |
| `b3b2e26` | fix: add capabilities + fix CSP for Tauri v2 IPC bridge |
| `b7c12ef` | fix(sync): add localhost:1420 to CSP for Vite dev server |
| `770c993` | docs: update MEMORY.md + passation.md with session 2026-09-20 fix |
| `b4a5880` | fix(sync): resolve macOS 'Initialisation...' blockage |

---

## 12. Checklist reprise de session

### Pour diagnostiquer le blocage polling
1. `cd /Users/saec/saec-cloud/saec-sync && git pull`
2. `kill $(lsof -ti:1420); pkill -9 -f vite`
3. `npm run build && cargo tauri dev`
4. Clic "Se connecter" → autoriser dans le navigateur
5. **Attendre 15 secondes** après autorisation
6. Si pas de `auth_poll_token` dans le terminal Rust → **problème confirmé**
7. Ouvrir WebKit Inspector (clic droit → Inspecter → Console) pour voir les `console.log`

### Pour appliquer le fix (déplacer poll vers Rust)
1. Modifier `commands.rs` — `auth_device_code` spawn un `tokio::spawn` pour le poll
2. Supprimer `auth_poll_token` command du `generate_handler![]`
3. Modifier `App.tsx` — `AuthView` utilise `listen('auth://token')` au lieu de `invoke('auth_poll_token')`
4. Tester le flow complet
5. Commit + push

---

*Ce document est la source de vérité pour toute nouvelle session de développement SAEC Sync.*
