# Building SAEC Sync Clients

## Current Status (v0.1.7)

| Platform | Artifact | Status |
|----------|----------|--------|
| Linux (Debian/Ubuntu) | `saec-sync_0.1.0_amd64.deb` | ✅ Built & Released |
| Linux (Fedora/RHEL) | `saec-sync-0.1.0-1.x86_64.rpm` | ✅ Built & Released |
| Linux (Binary) | `saec-sync-linux-x64` | ✅ Built & Released |
| Linux AppImage | — | ❌ Not built |
| Windows (MSI) | — | ❌ Needs Windows |
| macOS (DMG) | — | ❌ Needs macOS |

## Release Process (Current)

The `upload-artifacts.yml` workflow:
1. Triggers on tag `v*`
2. Copies artifacts from `artifacts/` folder
2. Creates GitHub Release with assets

**To release**: Add artifacts to `artifacts/` folder, commit, tag, push.

```bash
# Example for v0.1.8
mkdir -p artifacts
cp saec-sync/target/release/saec-sync artifacts/saec-sync-linux-x64
cp saec-sync/target/release/bundle/deb/*.deb artifacts/
cp saec-sync/target/release/bundle/rpm/*.rpm artifacts/
# Add Windows .msi, macOS .dmg, Linux .AppImage when available
git add artifacts/
git commit -m "chore: add artifacts for v0.1.8"
git tag v0.1.8
git push origin master --tags
```

## Building on Each Platform

### Linux (Debian/Ubuntu/Fedora) - Native

```bash
cd saec-sync
npm ci
npm run build
cargo tauri build --target x86_64-unknown-linux-gnu
# Artifacts in src-tauri/target/release/bundle/
```

**Requirements** (Ubuntu 24.04):
```bash
sudo apt-get install -y \
  libwebkit2gtk-4.1-dev libgtk-3-dev libayatana-appindicator3-dev \
  librsvg2-dev libsoup-3.0-dev gstreamer1.0-plugins-base-dev \
  libssl-dev pkg-config file libgirepository1.0-dev \
  libcairo2-dev libpango1.0-dev libatk1.0-dev libgdk-pixbuf-2.0-dev
```

### Windows (MSI + NSIS)

**Option A: GitHub Codespaces (Windows)** - Recommended
1. Open repo in Codespaces → "New codespace" → Select "Windows" (preview)
2. In Codespace terminal:
```powershell
# Install Rust
winget install Rustlang.Rust.GNU
# Or: https://rustup.rs/
# Install Node.js
winget install OpenJS.NodeJS
# Install WiX Toolset
choco install wixtoolset -y --version 3.14.1
# Build
cd saec-sync
npm ci
npm run build
cargo tauri build --target x86_64-pc-windows-msvc -- --msi
# Artifact: src-tauri/target/x86_64-pc-windows-msvc/release/bundle/msi/*.msi
```

**Option B: Local Windows Machine**
- Install: Rust (GNU), Node.js, Visual Studio Build Tools, WiX Toolset v3.14+
- Run same commands in PowerShell

**Option C: Cross-compile from Linux** (NOT RECOMMENDED)
- Requires `mingw-w64`, `wine`, `wixl` - fragile, WebKitGTK linking fails

### macOS (DMG)

**Local macOS Only** (Apple Silicon + Intel Universal)
```bash
# Install Rust
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
# Install Node.js
brew install node
# Build
cd saec-sync
npm ci
npm run build
cargo tauri build --target aarch64-apple-darwin  # Apple Silicon
cargo tauri build --target x86_64-apple-darwin  # Intel
# For Universal: lipo -create -output saec-sync-universal target/aarch64/.../saec-sync target/x86_64/.../saec-sync
# Then: cargo tauri build --target universal-apple-darwin -- --dmg
```

**Requirements**:
- Xcode Command Line Tools
- Apple Developer ID for notarization (required for distribution)

### Linux AppImage

```bash
cd saec-sync
cargo tauri build --target x86_64-unknown-linux-gnu -- --appimage
# Requires: fuse, libfuse2, appimagetool (installed by tauri)
```

## Upload Workflow

The `.github/workflows/upload-artifacts.yml` triggers on tag push:

```bash
# Prepare artifacts folder
mkdir -p artifacts
cp saec-sync/target/release/saec-sync artifacts/saec-sync-linux-x64
cp saec-sync/target/release/bundle/deb/*.deb artifacts/
cp saec-sync/target/release/bundle/rpm/*.rpm artifacts/
cp saec-sync/target/release/bundle/appimage/*.AppImage artifacts/  # if built
cp saec-sync/target/release/bundle/msi/*.msi artifacts/           # if built
cp saec-sync/target/release/bundle/dmg/*.dmg artifacts/           # if built

git add artifacts/
git commit -m "chore: add artifacts for vX.Y.Z"
git tag vX.Y.Z
git push origin master --tags
```

The workflow:
1. Runs on tag push (`v*`)
2. Stages `artifacts/*` 
3. Creates GitHub Release with all files

## Adding to Download Page

The `/client-sync` page auto-fetches from GitHub Releases API. No code changes needed.

## Checklist for Full Release

- [ ] Linux .deb ✅
- [ ] Linux .rpm ✅  
- [ ] Linux binary ✅
- [ ] Linux AppImage ⬜
- [ ] Windows .msi ⬜ (Windows build)
- [ ] Windows .exe (NSIS) ⬜
- [ ] macOS .dmg (ARM64) ⬜
- [ ] macOS .dmg (x64) ⬜
- [ ] macOS Universal DMG ⬜

## Quick Test Commands

```bash
# Test Linux binary
./saec-sync-linux-x64 --version

# Test .deb
sudo apt install ./saec-sync_0.1.0_amd64.deb
saec-sync --version

# Test .rpm
sudo rpm -i saec-sync-0.1.0-1.x86_64.rpm
saec-sync --version
```