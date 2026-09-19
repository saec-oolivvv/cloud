<# 
.SYNOPSIS
    Build SAEC Sync DMG macOS installer
.DESCRIPTION
    Complete build script for macOS DMG installer using Tauri v2
    Run as Administrator in PowerShell (on macOS via GitHub Actions or local macOS)
#>

param(
    [string]$Version = "0.1.36",
    [switch]$CleanBuild
)

Write-Host "═══════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  SAEC Sync - DMG Builder v$Version" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════`n" -ForegroundColor Cyan

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
    
    # Xcode tools (productbuild)
    if (!(Test-Path "/usr/bin/productbuild")) {
        $errors += "productbuild non trouvé → installer Xcode command line tools: xcode-select --install"
    } else {
        Write-Host "  ✅ productbuild trouvé" -ForegroundColor Green
    }
    
    # Codesigning identity
    if [-string]::IsNullOrEmpty($env:CODESIGN_IDENTITY) {
        $errors += "CODESIGN_IDENTITY non définie (variable d'environnement)"
    } else {
        Write-Host "  ✅ CODESIGN_IDENTITY configurée" -ForegroundColor Green
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
    $env:CODESIGN_IDENTITY = $env:CODESIGN_IDENTITY
    
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
    $repo = "saec-oolivvv/cloud"
    
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
    
    if ($CleanBuild) { Clean-Build }
    
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