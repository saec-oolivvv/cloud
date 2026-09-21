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

## Autres corrections

- **Race condition auth** : les listeners `auth://token` / `auth://error` dans `src/App.tsx` sont enregistrés au montage (`useEffect(..., [login, onSuccess])`, sans `polling` dans le dependency array). Sinon le listener était ré-enregistré à chaque changement de `polling` et l'événement émis par le backend Rust arrivait avant l'enregistrement.
- **Répertoire SQLite** : `~/Library/Application Support/me.saec.sync/sync/.saec-sync/` doit exister avant le lancement.

---

## `saec_run.sh`

Script d'automatisation dev/build. Changement notable de cette session : `check_course_fix()` ne retourne plus `0` aveuglément. Il extrait maintenant le vrai dependency array du `useEffect` contenant `auth://token` et **échoue** si `polling` y figure encore.

```bash
./saec_run.sh dev    # vérifs + cargo tauri dev
./saec_run.sh build  # vérifs + DMG universel
```

Le DMG sort dans :
```
src-tauri/target/universal-apple-darwin/release/bundle/dmg/SAEC Sync-*.dmg
```
