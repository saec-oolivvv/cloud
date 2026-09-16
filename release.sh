#!/bin/bash
# SAEC Sync - Script de release automatisé
# Usage: ./release.sh v0.1.19

set -e

VERSION="${1:-}"
if [ -z "$VERSION" ]; then
    echo "Usage: $0 <version> (ex: v0.1.19)"
    exit 1
fi

if [[ ! "$VERSION" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Format version invalide. Utilisez: vX.Y.Z"
    exit 1
fi

echo "🚀 Préparation release $VERSION"

# 1. Créer le dossier de release
RELEASE_DIR="releases/$VERSION"
mkdir -p "$RELEASE_DIR"

# 2. Copier les artifacts existants (Linux)
echo "📦 Copie des artifacts Linux..."
cp releases/v0.1.16/* "$RELEASE_DIR/" 2>/dev/null || true

# 3. Vérifier les artifacts
echo "📋 Artifacts dans $RELEASE_DIR :"
ls -la "$RELEASE_DIR/"

# 4. Build Linux AppImage (si pas déjà fait)
if [ ! -f "$RELEASE_DIR/SAEC Sync-$VERSION.AppImage" ] && [ ! -f "$RELEASE_DIR/saec-sync-linux-x64.AppImage" ]; then
    echo "🔨 Build Linux AppImage..."
    cd saec-sync
    cargo tauri build --target x86_64-unknown-linux-gnu -- --appimage 2>&1 | tail -5
    find src-tauri/target/release/bundle/appimage -name "*.AppImage" -exec cp {} "../$RELEASE_DIR/" \; 2>/dev/null || true
    cd ..
fi

# 4. Commit + tag + push
echo "📝 Commit des artifacts..."
git add "releases/$VERSION/"
git commit -m "chore: add artifacts for $VERSION" || echo "Rien à commiter"
git tag "$VERSION"
git push origin master --tags

echo "✅ Release $VERSION créée !"
echo "📥 Artifacts uploadés sur GitHub Release"
echo "🔗 https://github.com/saec-oolivvv/cloud/releases/tag/$VERSION"
echo ""
echo "📋 Pour ajouter Windows/macOS plus tard :"
echo "   gh release upload $VERSION /path/to/SAEC\\ Sync_*.msi /path/to/SAEC\\ Sync-*.dmg --repo saec-oolivvv/cloud"