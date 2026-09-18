# Passation de compilation MSI SAEC‑Sync

---

## 1. État actuel
- **Script** : `build-windows-msi.ps1` (racine du repo) orchestre la construction complète :
  1. Vérification des prérequis (Rust, Cargo, Node, npm, WiX 3.14, MSBuild).
  2. Nettoyage des dossiers de build précédents.
  3. Build frontend (React/Vite) dans `saec-sync/`.
  4. Build Tauri ciblant `x86_64-pc-windows-msvc` avec l’option `-- --msi`.
  5. Recherche de l’artifact `.msi` généré.
  6. (Optionnel) Upload sur une release GitHub.

- **Artifact existant** : `SAEC-Sync-Windows-MSI/SAEC Sync_0.1.0_x64_en-US.msi` (version 0.1.0), tandis que la config cible **0.1.36** (cf. `tauri.conf.json` et `build-windows-msi.ps1`).

- **Logs d’erreur** (d’après `saec-sync/src-tauri/build.log`) :
  - Échec du *frontend build* (npm `run build`) dans certains environnements (dépendances manquantes).
  - Erreurs à la compilation Rust (`cargo tauri build`) liées à cibles Windows non configurées ou à clés de signature manquantes.

## 2. Problèmes rencontrés
| Domaine | Description |
|---|---|
| **Prérequis** | WiX 3.14 introuvable ; MSBuild absent sans “Visual Studio Build Tools” (charge de travail C++). |
| **Frontend** | `npm ci` / `npm run build` peut échouer si `node_modules` corrompus ou version de Node incompatible. |
| **Tauri / MSI** | `cargo tauri build --target x86_64-pc-windows-msvc -- --msi` nécessite une toolchain Rust cible Windows (`rustup target add …`) et une clé de signature (`TAURI_PRIVATE_KEY`). |
| **Versioning** | MSI existant porte la version 0.1.0 ; il faut aligner sur 0.1.36. |

## 3. Succès
- Script complet et documenté avec vérifications automatisées.
- Structure de configuration cohérente (tauri.conf.json, build.rs, Cargo.toml).
- Artifact MSI déjà produit (0.1.0) pouvant servir de base après adjustment de version.

## 4. Objectifs & Problématique
- **Objectif** : produire un installeur Windows MSI à jour (version 0.1.36) prêt à être distribué, avec vérification des prérequis, build frontend + build Tauri, et upload sur GitHub Release.
- **Problématique** : coordonner les trois couches (frontend, Rust/Tauri, Windows installer (WiX)) pour obtenir une sortie MSI reproductible sansmachine Windows locale. Les verrous sont l’environnement d’exécution (Rust target, WiX, clé de signature) et la cohérence des numéros de version.

## 5. Fichiers clés
| Fichier | Rôle |
|---|---|
| `build-windows-msi.ps1` | Script d’automation complet. |
| `saec-sync/src-tauri/tauri.conf.json` | Configuration Tauri : nom, version, cibles de bundle (inclut *msi*). |
| `saec-sync/src-tauri/build.rs` | Entrée Rust minimale pour le build Tauri. |
| `saec-sync/src-tauri/Cargo.toml` | Dépendances Rust, profils de release, cibles de build. |
| `saec-sync/src-tauri/tauri.conf.json` -> `bundle.targets` | Liste des formats de bundle : `"deb", "rpm", "appimage", "msi"`. |
| `SAEC-Sync-Windows-MSI/SAEC Sync_0.1.0_x64_en-US.msi` | Artifact MSI existant (version 0.1.0). |
| `saec-sync/build.log` | Logs de dernière exécution (d’éventuelles erreurs). |

## 6. Prochaines étapes (à exécuter sur une machine Windows ou en CI)
1. Installer les prérequis : `rustup target add x86_64-pc-windows-msvc`, WiX 3.14, Visual Studio Build Tools.
2. Définir la variable `TAURI_PRIVATE_KEY` (ou générer une clé).
3. Aligner la version dans `tauri.conf.json`, `build-windows-msi.ps1` et l’artifact existant.
4. Lancer `.\build-windows-msi.ps1 -CleanBuild` en PowerShell admin.
5. Corriger les erreurs (nettoyer node_modules, inspecter `build.log`).
6. Tester l’installeur généré.
7. Upload sur GitHub Release (via script ou `gh release upload`).

---

## 7. Meilleure solution sans machine Windows disponible
Comme aucune machine physique Windows n’est à disposition, la solution idéale et sécurisée consiste à **déplacer la compilation MSI vers une pipeline CI hébergée en ligne**, spécifiquement **GitHub Actions** utilisant le runner Windows‑latest fourni par GitHub. Cette approche présente les avantages suivants :

- **Pas de dépendance matérielle locale** : les exécutions s’effectuent sur l’infrastructure de GitHub, entièrement gérée.
- **Prérequis installés par défaut** : le runner Windows‑latest inclut déjà les versions compatibles de WiX, MSBuild, le SDK Windows et les outils Visual Studio.
- **Rust target disponible** : l’image inclut Rust avec la cible `x86_64-pc-windows-msvc` pré‑installée (ou facile à ajouter via `rustup target add`).
- **Gestion sécurisée des secrets** : la clé de signature Tauri (`TAURI_PRIVATE_KEY`) peut être stockée en tant que secret GitHub (`TAURI_PRIVATE_KEY`) et injectée pendant l’exécution, jamais en clair dans le repo.
- **Intégration native avec GitHub Releases** : l’action `tauri-apps/tauri-action` (ou les étapes personnalisées du script `build-windows-msi.ps1`) peut produire le MSI, le signer et l’uploader automatiquement sur une release à chaque tag de version.
- **Reproductibilité** : le fichier de workflow (`.github/workflows/msi.yml`) définit précisément les étapes, les versions des outils et les variables d’environnement, garantissant que chaque build part des mêmes paramètres.
- **Traçabilité** : chaque exécution apparaît dans l’onglet *Actions* de GitHub, avec logs détaillés, ce qui facilite le débogage à distance.

### Workflow proposé (`.github/workflows/msi.yml`) :
```yaml
name: Build SAEC Sync Windows MSI

on:
  push:
    tags:
      - 'v*'          # déclenché à chaque nouveau tag (ex: v0.1.36)
  workflow_dispatch:
    inputs:
      version:
        description: 'Version to build'
        required: false
        default: ''

jobs:
  build-msi:
    runs-on: windows-latest

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Install WiX Toolset
        run: choco install wixtoolset -y --version 3.14.1

      - name: Add Rust target (if needed)
        run: rustup target add x86_64-pc-windows-msvc

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'   # version conforme aux exigences du projet

      - name: Install dependencies
        working-directory: saec-sync
        run: npm ci

      - name: Build frontend
        working-directory: saec-sync
        run: npm run build

      - name: Build Tauri MSI
        working-directory: saec-sync/src-tauri
        env:
          TAURI_PRIVATE_KEY: ${{ secrets.TAURI_PRIVATE_KEY }}
        run: |
          cargo tauri build --target x86_64-pc-windows-msvc -- --msi

      - name: Locate generated MSI
        id: find-msi
        run: |
          $msiPaths = @(
            "saec-sync\\src-tauri\\target\\x86_64-pc-windows-msvc\\release\\bundle\\msi\\*.msi",
            "saec-sync\\src-tauri\\target\\release\\bundle\\msi\\*.msi"
          )
          foreach ($pattern in $msiPaths) {
            $files = Get-ChildItem $pattern -ErrorAction SilentlyContinue
            if ($files) {
              echo "msi_path=$($files[0].FullName)" >> $env:GITHUB_OUTPUT
              break
            }
          }

      - name: Create/Update GitHub Release
        uses: softprops/action-gh-release@v2
        with:
          tag_name: ${{ github.ref_name }}
          name: "SAEC Sync ${{ github.ref_name }}"
          files: ${{ steps.find-msi.outputs.msi_path }}
          overwrite: true
          generate_release_notes: true

      - name: Upload MSI (alternative)
        if: false   # désactivé si l'étape précédente suffit
        uses: actions/upload-artifact@v4
        with:
          name: saec-sync-msi
          path: ${{ steps.find-msi.outputs.msi_path }}
```
*Ce workflow peut être stocké dans le dépôt et déclenché à chaque nouveau tag (ex. `git tag v0.1.36 && git push origin v0.1.36`).*

### Points de sécurité
- **Clé privée** ne jamais être commité ; stocker uniquement en secret GitHub.
- Les actions utilisées proviennent de sources officielles (`actions/`, `softprops/action-gh-release`).
- Le workflow s’exécute dans un environnement isolé, ne donnant aucun accès aux ressources du poste de développement local.
- Si une clé de code‑signing tiers est nécessaire, elle peut être fournie via un secret supplémentaire et utilisée uniquement pendant l’étape de build.

## 8. Conclusion
En s’appuyant sur **GitHub Actions avec le runner Windows‑latest**, la compilation MSI de SAEC‑Sync devient totalement indépendante de toute machine Windows locale, tout en conservant la sécurité (secrets gérés), la reproductibilité (définition explicite des étapes) et la facilité d’intégration avec les releases GitHub. Cette solution est idéale pour continuer le projet dans un environnement sans infrastructure Windows physique.

---
*Ce document fait office de “passation de pouvoir” pour qui que ce soit devoir poursuivre ou terminer la compilation MSI de SAEC‑Sync.*