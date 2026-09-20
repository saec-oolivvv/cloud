#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════
# SAEC Sync — macOS Installer (Full Auto)
# Usage: chmod +x install-macos.sh && ./install-macos.sh
# ═══════════════════════════════════════════════════════════════
set -euo pipefail

APP_NAME="SAEC Sync"
APP_BUNDLE="SAEC Sync.app"
VERSION="${1:-0.1.36}"
API_URL="https://cloud.saec.me"
DMG_BASENAME="SAEC-Sync-${VERSION}"
INSTALL_DIR="/Applications"
TMPDIR_INSTALL="/tmp/saec-install"
LOG_FILE="${TMPDIR_INSTALL}/install.log"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

log()  { echo -e "${GREEN}[✓]${NC} $*" | tee -a "$LOG_FILE"; }
warn() { echo -e "${YELLOW}[!]${NC} $*" | tee -a "$LOG_FILE"; }
err()  { echo -e "${RED}[✗]${NC} $*" | tee -a "$LOG_FILE"; }
info() { echo -e "${CYAN}[·]${NC} $*" | tee -a "$LOG_FILE"; }

cleanup() {
    if [[ -d "$TMPDIR_INSTALL" ]]; then
        rm -rf "$TMPDIR_INSTALL"
        info "Nettoyage temp termin."
    fi
}
trap cleanup EXIT

# ─── Header ──────────────────────────────────────────────────
echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}  SAEC Sync — macOS Installer v${VERSION}${NC}"
echo -e "${CYAN}  $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo ""

mkdir -p "$TMPDIR_INSTALL"
touch "$LOG_FILE"

# ─── Checks pr-requis ───────────────────────────────────────
check_prereqs() {
    info "Vrification des pr-requis..."

    # macOS ?
    if [[ "$(uname)" != "Darwin" ]]; then
        err "Ce script est uniquement pour macOS."
        exit 1
    fi
    log "macOS dtect: $(sw_vers -productName) $(sw_vers -productVersion)"

    # Arch
    ARCH="$(uname -m)"
    if [[ "$ARCH" == "arm64" ]]; then
        DMG_ARCH="aarch64-apple-darwin"
        log "Architecture: Apple Silicon (arm64)"
    else
        DMG_ARCH="x86_64-apple-darwin"
        log "Architecture: Intel (x86_64)"
    fi

    # curl
    if ! command -v curl &>/dev/null; then
        err "curl non trouv. Installez-le via Xcode Command Line Tools."
        exit 1
    fi

    # hdiutil (montage DMG)
    if ! command -v hdiutil &>/dev/null; then
        err "hdiutil non trouv.macOS system corrompu."
        exit 1
    fi

    log "Pr-requis OK"
}

# ─── Vrifier si l'app est dj installe ─────────────────────
check_existing() {
    if [[ -d "${INSTALL_DIR}/${APP_BUNDLE}" ]]; then
        EXISTING_VER=$(/usr/libexec/PlistBuddy -c "Print :CFBundleShortVersionString" \
            "${INSTALL_DIR}/${APP_BUNDLE}/Contents/Info.plist" 2>/dev/null || echo "inconnue")
        warn "SAEC Sync dj install (v${EXISTING_VER}) dans ${INSTALL_DIR}"
        echo ""
        read -p "Remplacer ? [O/n] " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Nn]$ ]]; then
            info "Installation annule."
            open "${INSTALL_DIR}/${APP_BUNDLE}" 2>/dev/null || true
            exit 0
        fi
        log "Remplacement de l'application existante..."
    fi
}

# ─── Dterminer URL du DMG ──────────────────────────────────
resolve_download_url() {
    info "Recherche du DMG v${VERSION}..."

    # Sources possibles (ordre de priorit)
    # 1. GitHub Release
    # 2. Serveur SAEC
    GITHUB_REPO="saec-oolivvv/cloud"
    GITHUB_URL="https://github.com/${GITHUB_REPO}/releases/download/v${VERSION}/${DMG_BASENAME}.dmg"
    SAEC_URL="${API_URL}/download/saec-sync-${VERSION}.dmg"
    LOCAL_FILE="saec-sync/target/${DMG_ARCH}/release/bundle/dmg/${DMG_BASENAME}.dmg"

    # Vrifier GitHub d'abord
    if curl -fsSL --head "$GITHUB_URL" &>/dev/null; then
        DMG_URL="$GITHUB_URL"
        log "DMG trouv sur GitHub Releases"
        return
    fi

    # Vrifier serveur SAEC
    if curl -fsSL --head "$SAEC_URL" &>/dev/null; then
        DMG_URL="$SAEC_URL"
        log "DMG trouv sur cloud.saec.me"
        return
    fi

    # Fichier local ?
    if [[ -f "$LOCAL_FILE" ]]; then
        DMG_URL="file://$(pwd)/${LOCAL_FILE}"
        log "DMG trouv en local"
        return
    fi

    err "Aucun DMG trouv pour la version ${VERSION}."
    echo ""
    echo "  Sources vrifies:"
    echo "    - ${GITHUB_URL}"
    echo "    - ${SAEC_URL}"
    echo "    - ${LOCAL_FILE}"
    echo ""
    echo "  Options:"
    echo "    1. Build le DMG sur une machine macOS:"
    echo "       cd saec-sync/src-tauri"
    echo "       cargo tauri build --target ${DMG_ARCH} --release"
    echo ""
    echo "    2. Uploadez le DMG sur GitHub Releases:"
    echo "       gh release upload v${VERSION} <chemin.dmg> --repo ${GITHUB_REPO}"
    echo ""
    echo "    3. Placez le DMG dans: ${LOCAL_FILE}"
    echo ""
    exit 1
}

# ─── Tlcharger le DMG ──────────────────────────────────────
download_dmg() {
    DMG_FILE="${TMPDIR_INSTALL}/${DMG_BASENAME}.dmg"

    # Si source locale, copier
    if [[ "$DMG_URL" == file://* ]]; then
        cp "${DMG_URL#file://}" "$DMG_FILE"
        log "DMG copi en local"
        return
    fi

    info "Tlchargement du DMG..."
    if curl -L --progress-bar -o "$DMG_FILE" "$DMG_URL"; then
        DMG_SIZE=$(du -h "$DMG_FILE" | cut -f1)
        log "DMG tlcharg (${DMG_SIZE})"
    else
        err "chec du tlchargement."
        exit 1
    fi
}

# ─── Monter et installer ─────────────────────────────────────
install_app() {
    info "Montage du DMG..."

    # Monter le DMG
    MOUNT_OUTPUT=$(hdiutil attach -nobrowse -readonly "$DMG_FILE" 2>&1)
    MOUNT_POINT=$(echo "$MOUNT_OUTPUT" | grep -E "/Volumes/SAEC" | awk '{print $NF}')

    if [[ -z "$MOUNT_POINT" ]]; then
        # Fallback: chercher tout volume SAEC
        MOUNT_POINT=$(ls /Volumes/ | grep -i saec | head -1)
        if [[ -z "$MOUNT_POINT" ]]; then
            err "Impossible de monter le DMG."
            echo "$MOUNT_OUTPUT"
            exit 1
        fi
        MOUNT_POINT="/Volumes/${MOUNT_POINT}"
    fi

    log "DMG mont: ${MOUNT_POINT}"

    # Copier l'app
    if [[ -d "${MOUNT_POINT}/${APP_BUNDLE}" ]]; then
        # Supprimer l'ancienne version si elle existe
        if [[ -d "${INSTALL_DIR}/${APP_BUNDLE}" ]]; then
            rm -rf "${INSTALL_DIR}/${APP_BUNDLE}"
            info "Ancienne version supprime"
        fi

        cp -R "${MOUNT_POINT}/${APP_BUNDLE}" "${INSTALL_DIR}/"
        log "Application copie dans ${INSTALL_DIR}"
    else
        err "Bundle '${APP_BUNDLE}' non trouv dans le DMG."
        ls -la "$MOUNT_POINT"
        hdiutil detach "$MOUNT_POINT" 2>/dev/null || true
        exit 1
    fi

    # Dmonter
    hdiutil detach "$MOUNT_POINT" 2>/dev/null || true
    log "DMG dmont"
}

# ─── Configurer l'app ───────────────────────────────────────
configure_app() {
    PLIST="${INSTALL_DIR}/${APP_BUNDLE}/Contents/Info.plist"

    if [[ ! -f "$PLIST" ]]; then
        warn "Info.plist non trouv, configuration ignore."
        return
    fi

    info "Configuration de l'application..."

    # Dfinir le serveur API par daut
    /usr/libexec/PlistBuddy -c "Add :SAECServerURL string ${API_URL}" "$PLIST" 2>/dev/null || \
    /usr/libexec/PlistBuddy -c "Set :SAECServerURL ${API_URL}" "$PLIST" 2>/dev/null

    # Dfinir la version
    /usr/libexec/PlistBuddy -c "Add :SAECClientVersion string ${VERSION}" "$PLIST" 2>/dev/null || \
    /usr/libexec/PlistBuddy -c "Set :SAECClientVersion ${VERSION}" "$PLIST" 2>/dev/null

    # Auto-launch au login (optionnel)
    /usr/libexec/PlistBuddy -c "Add :LSUIElement bool true" "$PLIST" 2>/dev/null || true

    log "Configuration applique (serveur: ${API_URL})"
}

# ─── Permissions ─────────────────────────────────────────────
fix_permissions() {
    info "Correction des permissions..."

    # Retirer la quarantine (Gatekeeper)
    xattr -dr com.apple.quarantine "${INSTALL_DIR}/${APP_BUNDLE}" 2>/dev/null || true
    log "Quarantine retire (xattr -dr com.apple.quarantine)"

    # Permissions excution
    chmod -R 755 "${INSTALL_DIR}/${APP_BUNDLE}"
    log "Permissions excution appliques"
}

# ─── Autorisation ouverture (non-sign) ──────────────────────
handle_gatekeeper() {
    info "Vrification Gatekeeper..."

    # Tester si l'app peut s'ouvrir
    if spctl --assess --type execute "${INSTALL_DIR}/${APP_BUNDLE}" 2>/dev/null; then
        log "Gatekeeper: app accepte"
    else
        warn "Gatekeeper: app non signe"
        echo ""
        echo "  macOS pourrait bloquer l'ouverture. Si c'est le cas:"
        echo "    1. System Preferences > Security & Privacy > General"
        echo "    2. Cliquez 'Open Anyway' pr SAEC Sync"
        echo ""
        echo "  Ou excutez:"
        echo "    sudo spctl --master-disable  (temporaire, dconseill)"
        echo ""
    fi
}

# ─── Lancer l'app ────────────────────────────────────────────
launch_app() {
    echo ""
    read -p "Lancer SAEC Sync maintenant ? [O/n] " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        info "Lancement de SAEC Sync..."
        open "${INSTALL_DIR}/${APP_BUNDLE}"
        log "Application lance"
    fi
}

# ─── Rsum ────────────────────────────────────────────────────
print_summary() {
    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  Installation termine !${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo ""
    echo "  Application:  ${INSTALL_DIR}/${APP_BUNDLE}"
    echo "  Version:      ${VERSION}"
    echo "  Serveur:      ${API_URL}"
    echo "  Architecture: ${ARCH}"
    echo ""
    echo "  Prochaines tapes:"
    echo "    1. Ouvrez SAEC Sync depuis /Applications"
    echo "    2. Connectez-vous avec vos identifiants cloud.saec.me"
    echo "    3. Approuvez l'appareil depuis le navigateur"
    echo "    4. Choisissez les dossiers synchroniser"
    echo ""
    echo "  Logs: ${LOG_FILE}"
    echo ""
}

# ══════════════════════════════════════════════════════════════
# MAIN
# ══════════════════════════════════════════════════════════════
main() {
    check_prereqs
    check_existing
    resolve_download_url
    download_dmg
    install_app
    configure_app
    fix_permissions
    handle_gatekeeper
    launch_app
    print_summary
}

main "$@"