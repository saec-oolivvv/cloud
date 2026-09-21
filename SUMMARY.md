# Summary

## Bug principal de la session : « Unable to find your web assets »

L'erreur bloquait **tout** `cargo tauri dev` et `cargo tauri build` :

```
Error Unable to find your web assets, did you forget to build your web app?
Your frontendDist is set to "dist" (which is `/…/saec-sync/src-tauri/dist`).
```

### Root cause

Dans Tauri v2, `frontendDist` dans `src-tauri/tauri.conf.json` est résolu **relativement au dossier qui contient ce fichier** — donc `src-tauri/`, pas la racine du projet.

| Valeur | Résolu vers | Résultat |
|---|---|---|
| `"dist"` | `saec-sync/src-tauri/dist` | ❌ Vite n'écrit jamais là |
| `"../dist"` | `saec-sync/dist` | ✅ Sortie réelle de `vite build` |

La confusion venait du fait qu'un dossier `dist/` **existe bien** à la racine du projet — mais Tauri ne le regardait pas là.

### Mauvais fix appliqué en premier (annulé)

1. `frontendDist` passé de `"../dist"` à `"dist"`
2. `mkdir -p dist` ajouté dans `saec_run.sh`

Ce combo a créé un dossier vide au mauvais endroit. Tauri ne paniquait plus sur un chemin absent mais échouait plus tard avec `Unable to find your web assets`. Le symptôme a changé, la cause est restée.

### Correction

`frontendDist` remis à `"../dist"`. Le `mkdir -p` est conservé — `generate_context!()` vérifie l'existence du chemin au compile-time — mais il cible maintenant `saec-sync/dist`.

**Leçon :** le message d'erreur de Tauri affiche le chemin absolu résolu entre parenthèses. Le lire avant de toucher à la config.

---

## Bug 2 : SQLite CANTOPEN (code 14) — espace dans le chemin

Erreur macOS au démarrage du SyncEngine :
```
Database error: error returned from database: (code: 14) unable to open database file
```

Chemin : `~/Library/Application Support/me.saec.sync/sync/.saec-sync/index.db` — contient un espace ("Application Support").

`format!("sqlite:{}", path.display())` produit une URL SQLite invalide : l'espace n'est pas percent-encodé.

### Fix

Dans `src-tauri/src/sync/index.rs` :
```rust
use urlencoding::encode;
let db_url = format!("sqlite://{}?mode=rwc", encode(&self.db_path.to_string_lossy()));
```

Ajouté `urlencoding = "2.1"` dans `Cargo.toml` (workspace + member).

---

## Autres corrections

- **Race condition auth** : les listeners `auth://token` / `auth://error` dans `src/App.tsx` sont enregistrés au montage (`useEffect(..., [login, onSuccess])`, sans `polling` dans le dependency array). Sinon le listener était ré-enregistré à chaque changement de `polling` et l'événement émis par le backend Rust arrivait avant l'enregistrement.

- **Ré-authentification à chaque redémarrage** : le token d'accès expirait (1h) et le frontend ne le rafraîchissait pas au démarrage.
  - **Fix Rust** : nouvelle commande `refresh_credentials` dans `src-tauri/src/ipc/commands.rs` — vérifie `expires_at`, appelle `/api/auth/token` avec `refresh_token` si nécessaire, stocke nouveaux tokens dans keyring.
  - **Fix Frontend** : `checkAuth` dans `src/store/auth.ts` appelle maintenant `refresh_credentials` — rafraîchit automatiquement si token expiré.
  - **Résultat** : autorisation permanente tant que l'app n'est pas supprimée (refresh token valide ~30 jours).

---

## `saec_run.sh`

Script d'automatisation dev/build. Changement notable de cette session : `check_course_fix()` ne retourne plus `0` aveuglément. Il extrait maintenant le vrai dependency array du `useEffect` contenant `auth://token` et **échoue** si `polling` y figure encore.

```bash
./saec_run.sh dev    # vérifs + cargo tauri dev
./saec_run.sh build  # vérifs + DMG universel
```

Le DMG sort dans :
```
target/universal-apple-darwin/release/bundle/dmg/SAEC Sync-*.dmg
```

---

## ⚠️ Limitation connue : Synchronisation bloquée côté serveur

Le DMG se construit avec succès, l'authentification est permanente, **mais la synchronisation ne démarre pas** :

| Composant | Statut |
|-----------|--------|
| Authentification | ✅ Fonctionnelle (persistante) |
| Dashboard | ✅ Apparaît |
| Indexation locale (SQLite) | ✅ Fonctionnelle |
| **Synchronisation cloud** | ⚠️ **Client prêt, serveur manquant** |

**Cause** : Le code client dans `src-tauri/src/sync/engine.rs` et `src-tauri/src/api/client.rs` est **entièrement implémenté** :
- `SyncEngine` démarre, crée le watcher, fait le `Full scan` local
- Calcule les deltas (upload/download/conflicts)
- Gère les stratégies de conflit (LastWriteWins, KeepLocal, KeepRemote, KeepBoth)
- Upload/download fichiers avec checksums Blake3

**Mais** : Les endpoints serveur `/api/sync/mounts/{id}/files` et suivants **n'existent pas** sur SAEC Cloud (PHP). Le client appelle ces endpoints et reçoit probablement 404/500.

**Debug logging ajouté** pour confirmer : lance avec `RUST_LOG=saec_sync=debug` — tu verras les appels `GET /sync/mounts/xxx/files` et leur status HTTP.

**Pour activer le sync** : implémenter ces endpoints côté serveur SAEC Cloud (PHP) :
- `GET /api/sync/mounts` ✅ (déjà OK)
- `GET /api/sync/mounts/{id}/files?path=`
- `GET /api/sync/mounts/{id}/files/{path}/content`
- `PUT /api/sync/mounts/{id}/files/{path}` + Header `X-File-Checksum`
- `DELETE /api/sync/mounts/{id}/files/{path}`
- `POST /api/sync/mounts/{id}/files/{path}` + `{"type":"folder"}`

C'est une **tâche côté serveur**, pas un problème de build client.
