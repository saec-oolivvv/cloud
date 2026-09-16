<# 
.SYNOPSIS
    Build SAEC Sync MSI installer on Windows
.DESCRIPTION
    Complete build script for Windows MSI installer using Tauri v2
    Run as Administrator in PowerShell
#>

param(
    [string]$Version = "0.1.20",
    [switch]$CleanBuild
)

Write-Host "═══════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  SAEC Sync - Windows MSI Builder v$Version" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════`n" -ForegroundColor Cyan

# ─── Vérifications prérequis ───
function Check-Prerequisites {
    Write-Host "🔍 Vérification des prérequis..." -ForegroundColor Yellow
    
    $errors = @()
    
    # Rust
    if (!(Get-Command rustc -ErrorAction SilentlyContinue)) {
        $errors += "Rust non installé → https://rustup.rs/"
    } else {
        Write-Host "  ✅ Rust: $(rustc --version)" -ForegroundColor Green
    }
    
    # Cargo
    if (!(Get-Command cargo -ErrorAction SilentlyContinue)) {
        $errors += "Cargo non installé"
    }
    
    # Node.js
    if (!(Get-Command node -ErrorAction SilentlyContinue)) {
        $errors += "Node.js non installé → https://nodejs.org/"
    } else {
        Write-Host "  ✅ Node.js: $(node --version)" -ForegroundColor Green
    }
    
    # npm
    if (!(Get-Command npm -ErrorAction SilentlyContinue)) {
        $errors += "npm non installé"
    } else {
        Write-Host "  ✅ npm: $(npm --version)" -ForegroundColor Green
    }
    
    # WiX Toolset
    $wixPath = "C:\Program Files (x86)\WiX Toolset v3.14\bin"
    if (!(Test-Path "$wixPath\candle.exe")) {
        $errors += "WiX Toolset v3.14 non trouvé → choco install wixtoolset -y --version 3.14.1"
    } else {
        Write-Host "  ✅ WiX Toolset v3.14 trouvé" -ForegroundColor Green
        $env:PATH += ";$wixPath"
    }
    
    # Visual Studio Build Tools
    $msbuild = Get-Command msbuild -ErrorAction SilentlyContinue
    if (!$msbuild) {
        $errors += "MSBuild non trouvé → Installer 'Visual Studio Build Tools' avec workload 'C++ build tools' + 'Windows 10 SDK'"
    } else {
        Write-Host "  ✅ MSBuild: $($msbuild.Source)" -ForegroundColor Green
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
        "saec-sync\src-tauri\target",
        "saec-sync\node_modules",
        "saec-sync\dist"
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
    
    if (!(Test-Path "dist\index.html")) {
        throw "Build frontend échoué - dist/index.html manquant"
    }
    Write-Host "  ✅ Frontend build OK" -ForegroundColor Green
    Set-Location ..
}

# ─── Build Tauri MSI ───
function Build-MSI {
    Write-Host "🔨 Build Tauri MSI..." -ForegroundColor Yellow
    Set-Location saec-sync\src-tauri
    
    $env:TAURI_PRIVATE_KEY = $env:TAURI_PRIVATE_KEY # Optionnel, pour auto-update
    
    $cmd = "cargo tauri build --target x86_64-pc-windows-msvc -- --msi"
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
        throw "Build MSI échoué (exit code: $exitCode)"
    }
    
    Write-Host "  ✅ Build Tauri OK" -ForegroundColor Green
    Set-Location ..\..
}

# ─── Trouver l'artifact ───
function Find-MSI {
    Write-Host "🔍 Recherche du MSI généré..." -ForegroundColor Yellow
    $msiPaths = @(
        "saec-sync\src-tauri\target\x86_64-pc-windows-msvc\release\bundle\msi\*.msi",
        "saec-sync\src-tauri\target\release\bundle\msi\*.msi"
    )
    
    foreach ($pattern in $msiPaths) {
        $files = Get-ChildItem $pattern -ErrorAction SilentlyContinue
        if ($files) {
            $msi = $files[0].FullName
            Write-Host "  ✅ MSI trouvé: $msi" -ForegroundColor Green
            return $msi
        }
    }
    
    throw "Aucun MSI trouvé dans les dossiers de sortie attendus"
}

# ─── Upload vers GitHub Release ───
function Upload-Release {
    param($MsiPath)
    
    Write-Host "☁️ Upload vers GitHub Release..." -ForegroundColor Yellow
    
    $version = "v0.1.20"
    $repo = "saec-oolivvv/cloud"
    
    # Vérifier si release existe
    $release = gh release view $version --repo saec-oolivvv/cloud 2>$null
    if (-not $release) {
        Write-Host "  Création release $version..." -ForegroundColor Gray
        gh release create $version --repo $repo --title "SAEC Sync $version" --notes "Windows MSI Installer" --draft:$false
    }
    
    Write-Host "  Upload MSI..." -ForegroundColor Gray
    gh release upload $version $MsiPath --repo saec-oolivvv/cloud --clobber
    
    Write-Host "  ✅ Upload OK" -ForegroundColor Green
}

# ═══════════════════════════════════════
# MAIN
# ═══════════════════════════════════════

try {
    Check-Prerequisites
    
    if ($CleanBuild) { Clean-Build }
    
    Build-Frontend
    Build-MSI
    
    $msi = Find-MSI
    Write-Host "`n🎉 MSI généré avec succès !" -ForegroundColor Green
    Write-Host "   Fichier: $msi" -ForegroundColor Cyan
    Write-Host "   Taille: $([math]::Round((Get-Item $MsiPath).Length / 1MB, 1)) MB" -ForegroundColor Cyan
    
    # Proposer upload
    $upload = Read-Host "`nUploader vers GitHub Release ? (o/N)"
    if ($upload -eq 'o') {
        Upload-Release -MsiPath $msi
    }
    
    Write-Host "`n🏁 Terminé !" -ForegroundColor Green
    
} catch {
    Write-Host "`n❌ ERREUR: $_" -ForegroundColor Red
    exit 1
}