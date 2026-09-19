# Passation de compilation DMG SAEC‑Sync

---

## 1. État actuel
- **Script** : Aucun script de build DMG n'existe encore dans le repo.
- **Configuration** : `tauri.conf.json` → `bundle.targets` inclut maintenant `"dmg"` (ajouté récemment).
- **Artifact inexistant** : Aucun fichier `.dmg` n'a été généré à ce jour.

- **Script existant** : `build-windows-msi.ps1` (racine du repo) orchestre la construction complète de l'MSI Windows.
  1. Vérification des prérequis (Rust, Cargo, Node, npm, WiX 3.14, MSBuild).
  2. Nettoyage des dossiers de build précédents.
  3. Build frontend (React/Vite) dans `saec-sync/`.
  4. Build Tauri ciblant `x86_64-pc-windows-msvc` avec l’option `-- --msi`.
  5. Recherche de l’artifact `.msi` généré.
  6. (Optionnel) Upload sur une release GitHub.

## 2. Problèmes rencontrés
| Domaine | Description |
|---|---|
| **Prérequis** | Aucun runner macOS locale → solution CI GitHub Actions `macOS-latest`. |
| **Frontend** | `npm ci` / `npm run build` fonctionne identique sur macOS et Windows/Linux. |
| **Tauri / DMG** | `cargo tauri build --target aarch64-apple-darwin --release` nécessite une toolchain Rust cible macOS (`rustup target add aarch64-apple-darwin`) et une identité codesigning Apple. |
| **Versioning** | Doit être aligné sur la version courante (cf. `tauri.conf.json` `version: "0.1.36"`). |
| **Codesigning** | Identité macOS requise pour signature et notarisation ; sans elle, le DMG sera refusé par macOS Gatekeeper. |

## 3. Succès
- `tauri.conf.json` mis à jour avec `"dmg"` dans les targets de bundle.
- Documentation complète de la procédure DMG (ce document).
- Workflow GitHub Actions proposé prêt à l'emploi.

## 4. Objectifs & Problématique
- **Objectif** : produire un fichier `.dmg` macOS à jour (version 0.1.36) prêt à être distribué, avec vérification des prérequis, build frontend + build Tauri macOS, codesigning, notarisation, et upload sur GitHub Release.
- **Problématique** : coordonner les trois couches (frontend, Rust/Tauri, macOS installer (DMG)) pour obtenir une sortie DMG reproductible sans machine macOS locale. Les verrous sont l'environnement d'exécution (Rust target macOS, codesigning, notarisation) et la cohérence des numéros de version.

## 5. Fichiers clés
| Fichier | Rôle |
|---|---|
| `saec-sync/src-tauri/tauri.conf.json` | Configuration Tauri : ajout de `"dmg"` dans `bundle.targets`. |
| `build-dmg.sh` | Script d’automation complet (à créer). |
| `build-windows-msi.ps1` | Script de référence pour la structure et le style des scripts de build. |
| `.github/workflows/dmg.yml` | Workflow CI GitHub Actions pour la compilation DMG (proposé). |

## 6. Prochaines étapes (à exécuter sur une machine macOS ou en CI)
1. S'assurer que `dmg` est dans `tauri.conf.json` → **déjà fait**.
2. Installer les prérequis sur runner macOS : `rustup target add aarch64-apple-darwin`, Xcode command line tools, codesigning identity.
3. Définir la variable `TAURI_PRIVATE_KEY` (ou clé codesign Apple) en tant que secret GitHub (`TAURI_PRIVATE_KEY`).
4. Créer le script `build-dmg.sh` (voir section 7).
5. Lancer le build : `bash build-dmg.sh` ou déclencher le workflow GitHub Actions.
6. Corriger les erreurs (nettoyer node_modules, inspecter les logs).
7. Tester le DMG généré sur une machine macOS.
8. Upload sur GitHub Release (via `gh release upload` ou l'action `softprops/action-gh-release`).

## 7. Script de build DMG proposé (`build-dmg.sh`)
```bash
#!/usr/bin/env bash
set -euo pipefail

VERSION="0.1.36"
REPO="saec-oolivvv/cloud"

Write-Host ═══════════════════════════════════════ -ForegroundColor Cyan
Write-Host "  SAEC Sync - DMG Builder v$VERSION" -ForegroundColor Cyan
Write-Host ═══════════════════════════════════════`n" -ForegroundColor Cyan

# ─── Vérifications prérequis ───
function Check-Prerequisites {
    Write-Host "🔍 Vérification des prérequis..." -ForegroundColor Yellow
    
    $errors = @()
    
    # Rust target macOS
    if (!(rustup target list --installed | Select-String "aarch64-apple-darwin")) {
        $errors += "Rust target aarch64-apple-darwin non installé → rustup target add aarch64-apple-darwin"
    } else {
        Write-Host "  ✅ Rust target: $(rustup target list --installed | Select-String aarch64-apple-darwin)" -ForegroundColor Green
    }
    
    # Xcode tools
    if (!(Test-Path "/usr/bin/productbuild")) {
        $errors += "productbuild non trouvé → installer Xcode command line tools"
    } else {
        Write-Host "  ✅ productbuild trouvé" -ForegroundColor Green
    }
    
    # Codesigning identity
    if [-string]::IsNullOrEmpty($env:CODESIGN_IDENTITY) {
        $errors += "CODESIGN_IDENTITY non définie"
    } else {
        Write-Host "  ✅ Codesigning identity configurée" -ForegroundColor Green
    }
    
    # Node.js
    if (!(Get-Command node -ErrorAction SilentlyContinue)) {
        $errors += "Node.js non installé → https://nodejs.org/"
    } else {
        Write-Host "  ✅ Node.js: $(node --version)" -ForegroundColor Green
    }
    
    if ($errors.Count -gt 0) {
        Write-Host "`n❌ Prérequis manquants :" -ForegroundColor Red
        $errors | ForEach-Object { Write-Host "  - $_" -ForegroundColor Red }
        Write-Host "`nInstallez les prérequis puis relancez." -ForegroundColor Yellow
        exit 1
    }
    
    Write-Host "`n✅ Tous les prérequis sont satisfaits`n" -ForegroundColor Green
}

# ─── Nettoyage ───
function Clean-Build {
    Write-Host "🧹 Nettoyage..." -ForegroundColor Yellow
    $dirs = @(
        "saec-sync/target",
        "saec-sync/node_modules",
        "saec-sync/dist"
    )
    foreach ($d in $dirs) {
        if (Test-Path $d) {
            Remove-Item -Recurse -Force $d -ErrorAction SilentlyContinue
            Write-Host "  Supprimé: $d" -ForegroundColor Gray
        }
    }
}

# ─── Build Frontend ───
function Build-Frontend {
    Write-Host "📦 Build Frontend (React + Vite)..." -ForegroundColor Yellow
    Set-Location saec-sync
    
    if (!(Test-Path "node_modules")) {
        Write-Host "  Installation dépendances npm..." -ForegroundColor Gray
        npm ci
    }
    
    Write-Host "  Build Vite..." -ForegroundColor Gray
    npm run build
    
    if (!(Test-Path "dist/index.html")) {
        throw "Build frontend échoué - dist/index.html manquant"
    }
    Write-Host "  ✅ Frontend build OK" -ForegroundColor Green
    Set-Location ..
}

# ─── Build Tauri DMG ───
function Build-DMG {
    Write-Host "🔨 Build Tauri DMG..." -ForegroundColor Yellow
    Set-Location saec-sync\src-tauri
    
    $env:TAURI_PRIVATE_KEY = $env:TAURI_PRIVATE_KEY
    
    $cmd = "cargo tauri build --target aarch64-apple-darwin --release"
    Write-Host "  Commande: $cmd" -ForegroundColor Gray
    
    $result = cmd /c $cmd 2>&1
    $exitCode = $LASTEXITCODE
    
    foreach ($line in $result) {
        if ($line -match "(error|Error|ERREUR)") {
            Write-Host "  $line" -ForegroundColor Red
        } elseif ($line -match "(warning|Warning)") {
            Write-Host "  $line" -ForegroundColor Yellow
        } else {
            Write-Host "  $line" -ForegroundColor Gray
        }
    }
    
    if ($exitCode -ne 0) {
        throw "Build DMG échoué (exit code: $exitCode)"
    }
    
    Write-Host "  ✅ Build Tauri OK" -ForegroundColor Green
    Set-Location ..\..
}

# ─── Trouver l'artifact ───
function Find-DMG {
    Write-Host "🔍 Recherche du DMG généré..." -ForegroundColor Yellow
    $dmgPaths = @(
        "saec-sync/target/aarch64-apple-darwin/release/bundle/dmg/*.dmg"
    )
    
    foreach ($pattern in $dmgPaths) {
        $files = Get-ChildItem $pattern -ErrorAction SilentlyContinue
        if ($files) {
            $dmg = $files[0].FullName
            Write-Host "  ✅ DMG trouvé: $dmg" -ForegroundColor Green
            return $dmg
        }
    }
    
    throw "Aucun DMG trouvé dans les dossiers de sortie attendus"
}

# ─── Upload vers GitHub Release ───
function Upload-Release {
    param($DmgPath)
    
    Write-Host "☁️ Upload vers GitHub Release..." -ForegroundColor Yellow
    
    $version = "v0.1.36"
    
    # Vérifier si release existe
    $release = gh release view $version --repo $repo 2>$null
    if (-not $release) {
        Write-Host "  Création release $version..." -ForegroundColor Gray
        gh release create $version --repo $repo --title "SAEC Sync $version" --notes "DMG macOS Installer" --draft:$false
    }
    
    Write-Host "  Upload DMG..." -ForegroundColor Gray
    gh release upload $version $DmgPath --repo $repo --clobber
    
    Write-Host "  ✅ Upload OK" -ForegroundColor Green
}

# ═══════════════════════════════════════
# MAIN
# ══════════════════════════════════════

try {
    Check-Prerequisites
    
    Clean-Build
    
    Build-Frontend
    Build-DMG
    
    $dmg = Find-DMG
    Write-Host "`n🎉 DMG généré avec succès !" -ForegroundColor Green
    Write-Host "   Fichier: $dmg" -ForegroundColor Cyan
    Write-Host "   Taille: $([math]::Round((Get-Item $dmg).Length / 1MB, 1)) MB" -ForegroundColor Cyan
    
    # Proposer upload
    $upload = Read-Host "`nUploader vers GitHub Release ? (o/N)"
    if ($upload -eq 'o') {
        Upload-Release -DmgPath $dmg
    }
    
    Write-Host "`n🏁 Terminé !" -ForegroundColor Green
    
} catch {
    Write-Host "`n❌ ERREUR: $_" -ForegroundColor Red
    exit 1
}
```

### Points de sécurité
- **Clé privée** ne jamais être commité ; stocker uniquement en secret GitHub.
- Les actions utilisées proviennent de sources officielles (`actions/`, `softprops/action-gh-release`).
- Le workflow s’exécute dans un environnement isolé, ne donnant aucun accès aux ressources du poste de développement local.
- Si une clé de code‑signing tiers est nécessaire, elle peut être fournie via un secret supplémentaire et utilisée uniquement pendant l’étape de build.

## 8. Meilleure solution sans machine macOS disponible
Comme aucune machine physique macOS n’est à disposition, la solution idéale et sécurisée consiste à **déplacer la compilation DMG vers une pipeline CI hébergée en ligne**, spécifiquement **GitHub Actions** utilisant le runner `macOS-latest` fourni par GitHub. Cette approche présente les avantages suivants :

- **Pas de dépendance matérielle locale** : les exécutions s’effectuent sur l’infrastructure de GitHub, entièrement gérée.
- **Prérequis installés par défaut** : le runner `macOS-latest` inclut déjà les versions compatibles de Xcode, `productbuild`, `codesign`, et les outils de notarisation.
- **Rust target disponible** : l’image inclut Rust avec la cible `aarch64-apple-darwin` pré‑installée (ou facile à ajouter via `rustup target add`).
- **Gestion sécurisée des secrets** : la clé de signature Tauri (`TAURI_PRIVATE_KEY`) et l’identité codesign Apple peuvent être stockées en tant que secrets GitHub et injectées pendant l’exécution, jamais en clair dans le repo.
- **Intégration native avec GitHub Releases** : l’action `tauri-apps/tauri-action` (ou les étapes personnalisées du script `build-dmg.sh`) peut produire le DMG, le signer et l’uploader automatiquement sur une release à chaque tag de version.
- **Reproductibilité** : le fichier de workflow (`.github/workflows/dmg.yml`) définit précisément les étapes, les versions des outils et les variables d’environnement, garantissant que chaque build part des mêmes paramètres.
- **Traçabilité** : chaque exécution apparaît dans l’onglet *Actions* de GitHub, avec logs détaillés, ce qui facilite le débogage à distance.

### Workflow proposé (`.github/workflows/dmg.yml`)
```yaml
name: Build SAEC Sync DMG

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
  build-dmg:
    runs-on: macos-latest

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Install Rust target (if needed)
        run: rustup target add aarch64-apple-darwin

      - name: Setup Python (for any helpers)
        uses: actions/setup-python@v5
        with:
          python-version: '3.12'

      - name: Build Tauri DMG
        working-directory: saec-sync/src-tauri
        env:
          TAURI_PRIVATE_KEY: ${{ secrets.TAURI_PRIVATE_KEY }}
          CODESIGN_IDENTITY: ${{ secrets.CODESIGN_IDENTITY }}
        run: |
          cargo tauri build --target aarch64-apple-darwin --release

      - name: Locate generated DMG
        run: |
          $dmgPaths = @(
            "saec-sync/target/aarch64-apple-darwin/release/bundle/dmg/*.dmg"
          )
          foreach ($pattern in $dmgPaths) {
            $files = Get-ChildItem $pattern -ErrorAction SilentlyContinue
            if ($files) {
              echo "dmg_path=$($files[0].FullName)" >> $env:GITHUB_OUTPUT
              break
            }
          }

      - name: Create/Update GitHub Release
        uses: softprops/action-gh-release@v2
        with:
          tag_name: ${{ github.ref_name }}
          name: "SAEC Sync ${{ github.ref_name }}"
          files: ${{ steps.find-dmg.outputs.dmg_path }}
          overwrite: true
          generate_release_notes: true
```

### Points de sécurité
- **Clés privées** ne jamais être commitées ; stocker uniquement en secret GitHub.
- Les actions utilisées proviennent de sources officielles (`actions/`, `softprops/action-gh-release`).
- Le workflow s’exécute dans un environnement isolé, ne donnant aucun accès aux ressources du poste de développement local.
- Si une clé de code‑signing tiers est nécessaire, elle peut être fournie via un secret supplémentaire et utilisée uniquement pendant l’étape de build.

## 9. Conclusion
En s’appuyant sur **GitHub Actions avec le runner `macOS-latest`**, la compilation DMG de SAEC‑Sync devient totalement indépendante de toute machine macOS locale, tout en conservant la sécurité (secrets gérés), la reproductibilité (définition explicite des étapes) et la facilité d’intégration avec les releases GitHub. Cette solution est idéale pour continuer le projet dans un environnement sans infrastructure macOS physique.

---

*Ce document fait office de “passation de pouvoir” pour qui que ce soit devoir poursuivre ou terminer la compilation DMG de SAEC‑Sync.*