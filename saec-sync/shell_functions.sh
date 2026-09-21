# SAEC Sync macOS Development Shell Functions
# Add these to your ~/.zshrc or ~/.bash_profile

# === Development ===
saec-dev() {
  echo "Starting SAEC Sync in development mode..."
  echo "Make sure you have Xcode command line tools installed:"
  echo "  Run: xcode-select --install"
  cargo tauri dev
}

saec-dev-log() {
  echo "Showing Tauri dev logs (run in another terminal):"
  echo "  tail -f ~/Library/Logs/me.saec.sync/saec-sync.log"
  # Alternative: cargo tauri dev --debug
}

# === Build Targets (macOS only) ===
saec-build-mac-arm64() {
  echo "Building macOS Apple Silicon (ARM64) DMG..."
  echo "Prerequisites:"
  echo "  - Xcode command line tools: xcode-select --install"
  echo "  - Rust target: rustup target add aarch64-apple-darwin"
  echo ""
  echo "Building..."
  cargo tauri build --target aarch64-apple-darwin --bundles dmg
}

saec-build-mac-intel() {
  echo "Building macOS Intel (x86_64) DMG..."
  echo "Prerequisites:"
  echo "  - Xcode command line tools: xcode-select --install"
  echo "  - Rust target: rustup target add x86_64-apple-darwin"
  echo ""
  echo "Building..."
  cargo tauri build --target x86_64-apple-darwin --bundles dmg
}

saec-build-mac-universal() {
  echo "Building macOS Universal (ARM64 + Intel) DMG..."
  echo "Prerequisites:"
  echo "  - Xcode command line tools: xcode-select --install"
  echo "  - Rust targets: rustup target add aarch64-apple-darwin x86_64-apple-darwin"
  echo ""
  echo "Building universal binary first..."
  cargo tauri build --target universal-apple-darwin
  echo ""
  echo "Creating universal DMG..."
  # Tauri handles universal bundles automatically when both targets are installed
  cargo tauri bundle --target universal-apple-darwin
}

saec-build-mac-all() {
  echo "Building all macOS bundles (ARM64, Intel, Universal)..."
  saec-build-mac-arm64
  saec-build-mac-intel
  saec-build-mac-universal
}

# === Clean & Reset ===
saec-clean() {
  echo "Cleaning all build artifacts..."
  cargo clean
  rm -rf dist node_modules/.vite src-tauri/target target
  # Clean Tauri-specific caches
  rm -rf src-tauri/src-tauri.target
  echo "Done."
}

saec-reset() {
  echo "Full reset: clean + reinstall dependencies..."
  saec-clean
  npm ci
  echo "Dependencies reinstalled."
  echo "Run 'saec-dev' to start development."
}

# === Git Helpers ===
saec-git-status() {
  echo "=== Git Status ==="
  git status --short -b
  echo ""
  echo "=== Recent Commits ==="
  git log --oneline -10
}

saec-git-diff() {
  if [ $# -eq 0 ]; then
    git diff --stat
  else
    git diff "$@"
  fi
}

saec-git-log() {
  git log --oneline --decorate --graph -${1:-10}
}

# === Auth Testing Helpers ===
saec-auth-test-info() {
  echo "=== Auth Flow Test Information ==="
  echo "To test device-code flow manually:"
  echo ""
  echo "1. Request device code:"
  echo "   curl -X POST https://cloud.saec.me/api/auth/device \\"
  echo "     -H \"Content-Type: application/json\" \\"
  echo "     -d '{\"client_id\":\"saec-sync-desktop\",\"scope\":\"sync.read sync.write\"}'"
  echo ""
  echo "2. Poll for token (replace DEVICE_CODE):"
  echo "   while true; do"
  echo "     response=\$(curl -s -X POST https://cloud.saec.me/api/auth/token \\"
  echo "       -H \"Content-Type: application/json\" \\"
  echo "       -d '{\"grant_type\":\"urn:ietf:params:oauth:grant-type:device_code\",\"device_code\":\"DEVICE_CODE\",\"client_id\":\"saec-sync-desktop\"}')"
  echo "     if echo \"\$response\" | grep -q \"access_token\"; then"
  echo "       echo \"Success! Token received.\""
  echo "       break"
  echo "     elif echo \"\$response\" | grep -q \"authorization_pending\"; then"
  echo "       echo \"Waiting for user authorization...\""
  echo "       sleep 5"
  echo "     else"
  echo "       echo \"Error or timeout: \$response\""
  echo "       break"
  echo "     fi"
  echo "   done"
}

saec-auth-reset-keyring() {
  echo "Clearing SAEC Sync credentials from macOS Keychain..."
  if command -v security &> /dev/null; then
    security delete-generic-password -s "me.saec.sync" -a "auth" 2>/dev/null
    if [ $? -eq 0 ]; then
      echo "Credentials cleared from keychain."
    else
      echo "No credentials found or error clearing."
    fi
  else
    echo "Error: 'security' command not found. Are you on macOS?"
  fi
}

# === Debug & Info ===
saec-info() {
  echo "=== SAEC Sync Info (macOS) ==="
  echo "Project root: $(pwd)"
  echo "macOS version: $(sw_vers -productVersion)"
  echo "Xcode CLI tools: $(xcode-select -p 2>/dev/null || echo \"Not installed\")"
  echo "Node version: $(node --version)"
  echo "npm version: $(npm --version)"
  echo "Rust version: $(rustc --version)"
  echo "Cargo version: $(cargo --version)"
  echo ""
  echo "Installed Rust targets:"
  rustup target list --installed | grep -E "darwin"
  echo ""
  echo "Tauri config bundles:"
  grep -A10 '"bundle"' src-tauri/tauri.conf.json
  echo ""
  echo "Available shell functions:"
  grep "^[a-z_][a-zA-Z0-9_]*()" "$0" | grep -v "^#" | head -20
}

saec-deps() {
  echo "=== Checking for dependency updates ==="
  echo "npm packages (run 'npm outdated' for full list):"
  npm outdated 2>/dev/null | head -10 || echo "Install npm-check-updates: npm install -g npm-check-updates"
  echo ""
  echo "Cargo dependencies (install cargo-install-update first):"
  cargo install cargo-update 2>/dev/null && cargo outdated 2>/dev/null | head -10 || echo "Run 'cargo install cargo-update' then 'cargo outdated'"
}

# === Troubleshooting Helpers ===
saec-fix-objc2-error() {
  echo "Fix for 'objc2-exception-helper' build errors on macOS:"
  echo ""
  echo "1. Update Rust toolchain:"
  echo "   rustup update"
  echo ""
  echo "2. Clean build artifacts:"
  echo "   cargo clean"
  echo ""
  echo "3. Set compiler to clang explicitly:"
  echo "   export CC=/usr/bin/clang"
  echo "   export CXX=/usr/bin/clang++"
  echo "   export MACOSX_DEPLOYMENT_TARGET=10.13"
  echo ""
  echo "4. Retry build:"
  echo "   cargo tauri build --target aarch64-apple-darwin --bundles dmg"
  echo ""
  echo "5. If still failing, try installing older macOS SDK via:"
  echo "   sudo xcode-select --switch /Applications/Xcode.app/Contents/Developer"
  echo ""
  echo "Note: These exports are temporary for the current shell session."
}

# === Convenience Aliases ===
alias saec='cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd'  # Always return to project root
alias saec-logs='tail -f ~/Library/Logs/me.saec.sync/saec-sync.log 2>/dev/null || echo "Log file not found - run saec-dev first"'
alias saec-watch='while true; do clear; saec-git-status; echo "---"; saec-info | head -10; sleep 10; done'

# === Platform Detection & Auto-load ===
is_macos() { [[ "$(uname)" == "Darwin" ]; }

# Auto-help when sourced
if [ "$0" = "${BASH_SOURCE[0]}" ]; then
  echo "SAEC Sync macOS shell functions loaded."
  echo "Type 'saec-info' to see system info or 'saec-dev' to start development."
else
  echo "SAEC Sync macOS shell functions loaded."
fi