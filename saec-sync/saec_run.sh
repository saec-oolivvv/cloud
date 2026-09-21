#!/usr/bin/env bash
# saec_run.sh - Script tout-en-un pour développer et construire SAEC Sync sur macOS
# Place ce fichier à la racine du dépôt, rends-le exécutable (chmod +x saec_run.sh)
# Puis lance-le : ./saec_run.sh dev   ou   ./saec_run.sh build

set -euo pipefail

# ---------- CONFIGURATION ----------
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_SUPPORT_DIR="$HOME/Library/Application Support/me.saec.sync/sync/.saec-sync"
# Only clean Vite cache, keep dist but ensure it exists
FRONTEND_CACHE_DIRS=("node_modules/.vite")
# -----------------------------------

log() { echo "[+] $*"; }
warn() { echo "[!] $*" >&2; }
error() { echo "[✖] $*" >&2; exit 1; }

# Vérifie que le fix de course condition est bien appliqué dans src/App.tsx
check_course_fix() {
    local app_tsx="$PROJECT_ROOT/src/App.tsx"
    [[ -f "$app_tsx" ]] || { warn "src/App.tsx introuvable"; return 1; }

    # Les listeners auth://token doivent être enregistrés dans un useEffect
    if ! grep -q "auth://token" "$app_tsx"; then
        warn "Aucun listener auth://token dans src/App.tsx"
        return 1
    fi

    # Extraire le dependency array du useEffect qui contient le listener auth://token
    local deps
    deps="$(awk '
        /auth:\/\/token/ { seen = 1 }
        seen && /\}, \[/ { print; exit }
    ' "$app_tsx")"

    if [[ -z "$deps" ]]; then
        warn "Impossible de localiser le dependency array du useEffect auth://token"
        return 1
    fi

    if [[ "$deps" == *"polling"* ]]; then
        warn "Le dependency array contient encore 'polling' : $deps"
        warn "Le listener serait ré-enregistré à chaque changement de polling → race condition."
        return 1
    fi

    log "✅ Fix de course condition vérifié — dependency array : ${deps//[$'\n']/}"
    return 0
}

# S'assure que le répertoire de la base de données existe et est writable
ensure_db_dir() {
    mkdir -p "$APP_SUPPORT_DIR"
    if [[ ! -w "$APP_SUPPORT_DIR" ]]; then
        error "Le répertoire $APP_SUPPORT_DIR n'est pas writable. Vérifie les permissions."
    fi
    # Test d'écriture réel
    local test_file="$APP_SUPPORT_DIR/.write_test_$$"
    if touch "$test_file" 2>/dev/null && rm -f "$test_file"; then
        log "✅ Répertoire DB prêt et writable : $APP_SUPPORT_DIR"
    else
        error "Impossible d'écrire dans $APP_SUPPORT_DIR. Vérifie les permissions."
    fi
}

# Nettoie les caches frontend pour éviter les problèmes de compilation
clean_frontend_cache() {
    for dir in "${FRONTEND_CACHE_DIRS[@]}"; do
        if [[ -d "$PROJECT_ROOT/$dir" ]]; then
            log "Nettoyage du cache : $PROJECT_ROOT/$dir"
            rm -rf "$PROJECT_ROOT/$dir"
        fi
    done
    # Le chemin frontendDist "../dist" est relatif à src-tauri/ → saec-sync/dist
    # Tauri vérifie son existence au compile-time (generate_context!), pas son contenu.
    mkdir -p "$PROJECT_ROOT/dist"
    if [[ -d "$PROJECT_ROOT/dist" ]]; then
        log "Répertoire dist assuré : $PROJECT_ROOT/dist"
    else
        error "Impossible de créer le répertoire dist : $PROJECT_ROOT/dist"
    fi

    # Pour un build de production, dist doit contenir index.html (sinon npm run build n'a pas tourné)
    if [[ "${1:-}" == "require-assets" && ! -f "$PROJECT_ROOT/dist/index.html" ]]; then
        error "dist/index.html absent — lance 'npm run build' dans $PROJECT_ROOT avant de construire."
    fi
}

# Lance l'application en mode développement
run_dev() {
    log "=== Lancement de SAEC Sync en mode développement ==="
    check_course_fix || error "Le fix de course condition n'est pas appliqué. Corrige src/App.tsx avant de continuer."
    ensure_db_dir
    clean_frontend_cache

    log "Démarrage de cargo tauri dev..."
    log "Après l'autorisation device-code, tu devrais voir :"
    log "  - [cmd] auth_poll SUCCESS — token received"
    log "  - L'écran \"Autorisation en cours\" disparaître"
    log "  - Le tableau de bord apparaître"
    log "Si l'une de ces étapes échoue, vérifie les logs ci-dessus."

    # On ne utilise pas set -e ici car on veut que le script reste actif tant que tauri tourne
    set +e
    cargo tauri dev
    # Quand tauri se termine (ex: erreur ou fermeture), on sort
    exit $?
}

# Construit un DMG universel (Intel + Apple Silicon)
run_build() {
    log "=== Construction du DMG universel ==="
    # Vérifier les prérequis
    if ! command -v xcode-select >/dev/null 2>&1; then
        error "xcode-select introuvable. Installe les outils en ligne de commande Xcode : xcode-select --install"
    fi
    if ! rustup target list --installed | grep -q "aarch64-apple-darwin"; then
        error "Cible Rust aarch64-apple-darwin manquante. Exécute : rustup target add aarch64-apple-darwin x86_64-apple-darwin"
    fi

    ensure_db_dir   # Le DMG embarquera l'état actuel du répertoire DB (souvent vide, c'est OK)
    clean_frontend_cache require-assets

    log "Nettoyage complet avant la construction..."
    cargo clean
    rm -f "$PROJECT_ROOT/src-tauri/target" "$PROJECT_ROOT/target"

    log "Construction du DMG universel (cela peut prendre plusieurs minutes)..."
    if cargo tauri build --target universal-apple-darwin --bundles dmg; then
        log "✅ Construction réussie !"
        log "Le DMG se trouve ici :"
        log "  $PROJECT_ROOT/src-tauri/target/universal-apple-darwin/release/bundle/dmg/SAEC Sync-*.dmg"
    else
        error "Échec de la construction. Consulte les logs ci-dessus pour plus de détails."
    fi
}

# ---------- GESTION DES ARGUMENTS ----------
if [[ $# -eq 0 ]]; then
    echo "Usage : $0 {dev|build}"
    echo "  dev   : Lancer l'application en mode développement (avec vérifications préalables)"
    echo "  build : Construire un DMG universel (nécessite Xcode et les cibles Rust)"
    exit 1
fi

case "$1" in
    dev)
        run_dev
        ;;
    build)
        run_build
        ;;
    *)
        error "Argument inconnu : $1. Utilise 'dev' ou 'build'."
        ;;
esac