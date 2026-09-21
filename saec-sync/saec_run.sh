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

# Vérifie que le fix de course condition est bien appliqué
check_course_fix() {
    local file="$PROJECT_ROOT/src/App.tsx"
    if [[ ! -f "$file" ]]; then
        error "Fichier $file introuvable"
    fi

    # On cherche le useEffect qui enregistre les listeners auth://token et auth://error dans le composant AuthView
    # Il doit contenir listen<TokenResponse>('auth://token' ... ) et listen<string>('auth://error' ... )
    # et ses dépendances doivent être exactement [login, onSuccess] (ou [onSuccess, login])

    # Extraire la section AuthView complète
    local auth_view_start
    auth_view_start=$(grep -n "function AuthView" "$file" | cut -d: -f1)
    if [[ -z "$auth_view_start" ]]; then
        error "Impossible de trouver le composant AuthView dans $file"
        return 1
    fi

    # Extraire depuis le début de AuthView jusqu'à la prochaine fonction ou la fin du fichier
    local auth_view_content
    auth_view_content=$(sed -n "${auth_view_start},\$p" "$file" | sed -n '/^function /,/^function /{//!p}' | sed -n '/^function AuthView/,/^function /p')
    # Si pas de prochaine fonction, prendre jusqu'à la fin
    if [[ -z "$auth_view_content" ]]; then
        auth_view_content=$(sed -n "${auth_view_start},\$p" "$file")
    fi

    # Vérifier que cette section contient les deux listeners
    if ! echo "$auth_view_content" | grep -q "listen<TokenResponse>.*auth:\/\/token"; then
        warn "Le listener auth://token n'est pas trouvé dans la section AuthView"
        return 1
    fi
    if ! echo "$auth_view_content" | grep -q "listen<string>.*auth:\/\/error"; then
        warn "Le listener auth://error n'est pas trouvé dans la section AuthView"
        return 1
    fi

    # Maintenant, trouver la ligne contenant les dépendances du useEffect qui contient ces listeners
    # On cherche la ligne qui contient 'useEffect(() => {' et qui a plus tard les deux listeners
    local useeffect_line
    useeffect_line=$(echo "$auth_view_content" | grep -n "useEffect(() => {" | cut -d: -f1)
    if [[ -z "$useeffect_line" ]]; then
        warn "Impossible de trouver le useEffect dans la section AuthView"
        return 1
    fi

    # Extraire tout depuis ce useEffect jusqu'à la prochaine '})' qui suit
    local useeffect_block
    useeffect_block=$(echo "$auth_view_content" | sed -n "${useeffect_line},\$p" | sed -n '/useEffect(() => {/,/})/p')

    if [[ -z "$useeffect_block" ]]; then
        warn "Impossible d'extraire le bloc useEffect"
        return 1
    fi

    # Maintenant, extraire les dépendances (ce qui est entre [ et ] juste avant la fermeture de useEffect)
    local deps_line
    deps_line=$(echo "$useeffect_block" | grep -E '\s*\[[^]]*\]\s*[),]' | tail -1)

    if [[ -z "$deps_line" ]]; then
        warn "Impossible de trouver les dépendances du useEffect"
        return 1
    fi

    # Nettoyer les espaces et extraire ce qui est entre [ et ]
    deps_line=$(echo "$deps_line" | sed -E 's/.*\[([^]]*)\].*/\1/' | tr -d '[:space:]')

    if [[ "$deps_line" != "login,onSuccess" && "$deps_line" != "onSuccess,login" ]]; then
        warn "Les dépendances du useEffect des listeners semblent incorrectes : [$deps_line]"
        warn "Elles doivent être exactement [login, onSuccess] (ou [onSuccess, login])"
        warn "Édite src/App.tsx et remplace les dépendances par [login, onSuccess]"
        return 1
    fi

    log "✅ Fix de course condition vérifié : les listeners sont enregistrés au montage"
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
    # Ensure dist directory exists for Tauri's frontendDist check
    mkdir -p "$PROJECT_ROOT/dist"
    if [[ -d "$PROJECT_ROOT/dist" ]]; then
        log "Répertoire dist assuré : $PROJECT_ROOT/dist"
    else
        error "Impossible de créer le répertoire dist : $PROJECT_ROOT/dist"
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
    clean_frontend_cache

    log "Nettoyage complet avant la construction..."
    cargo clean
    rm -rf "$PROJECT_ROOT/src-tauri/target" "$PROJECT_ROOT/target"

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