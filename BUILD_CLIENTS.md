# Building SAEC Sync Clients

## Current Status (v0.1.36) - Updated 2026-09-21

| Platform | Artifact | Status |
|----------|----------|--------|
| Linux (Debian/Ubuntu) | `saec-sync_0.1.36_amd64.deb` | ✅ Built & Released |
| Linux (Fedora/RHEL) | `saec-sync-0.1.36-1.x86_64.rpm` | ✅ Built & Released |
| Linux (Binary) | `saec-sync-linux-x64` | ✅ Built & Released |
| Linux AppImage | — | ❌ Not built |
| Windows (MSI) | — | ❌ Needs Windows |
| macOS (DMG) | — | ✅ Built & Released (Universal) |

## Release Process (Current)

The `upload-artifacts.yml` workflow:
1. Triggers on tag `v*`
2. Copies artifacts from `artifacts/` folder
2. Creates GitHub Release with assets

**To release**: Add artifacts to `artifacts/` folder, commit, tag, push.

```bash
# Example for v0.1.37
mkdir -p artifacts
cp saec-sync/target/release/saec-sync artifacts/saec-sync-linux-x64
cp saec-sync/target/release/bundle/deb/*.deb artifacts/
cp saec-sync/target/release/bundle/rpm/*.rpm artifacts/
cp saec-sync/target/release/bundle/dmg/*.dmg artifacts/  # macOS universal
# Add Windows .msi when available
git add artifacts/
git commit -m "chore: add artifacts for v0.1.37"
git tag v0.1.37
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
  libwebkit2gtk-4.1-0 libgtk-3-0 libayatana-appindicator3-1 \
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
cargo tauri build --target universal-apple-darwin --bundles dmg
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
cp saec-sync/target/release/bundle/dmg/*.dmg artifacts/  # if built
cp saec-sync/target/release/bundle/msi/*.msi artifacts/   # if built
cp saec-sync/target/release/bundle/appimage/*.AppImage artifacts/  # if built

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
- [ ] macOS .dmg (ARM64) ✅
- [ ] macOS .dmg (x64) ✅
- [ ] macOS Universal DMG ✅

## Quick Test Commands

```bash
# Test Linux binary
./saec-sync-linux-x64 --version

# Test .deb
sudo apt install ./saec-sync_0.1.36_amd64.deb
saec-sync --version

# Test .rpm
sudo rpm -i saec-sync-0.1.36-1.x86_64.rpm
saec-sync --version

# Test macOS dmg (after mounting)
/Volumes/SAEC\ Sync/SAEC\ Sync.app/Contents/MacOS/saec-sync --version
```

## Recent Fixes (2026-09-21)

1. **Auth Race Condition Fix**: Moved Tauri event listeners (`auth://token`, `auth://error`) to mount-time in `src/App.tsx` (`useEffect(() => { ... }, [login, onSuccess])`) to prevent losing the token event before frontend is ready.

2. **Database Path Fix** (SQLite code 14): The SQLite database path on macOS contains a space (`~/Library/Application Support/...`) which breaks the SQLx connection URL. Fixed by percent-encoding the path with `urlencoding::encode()` before passing to `sqlx::connect()`. Added `urlencoding = "2.1"` dependency.

3. **FrontendDist Path Fix** — root cause corrected:
   - **Wrong fix attempted first**: `frontendDist` changed from `"../dist"` to `"dist"` + `mkdir -p dist` in the script. This was based on a misread of the error.
   - **Actual root cause**: `frontendDist` in Tauri v2 is resolved **relative to the directory containing `tauri.conf.json`** (i.e. `src-tauri/`), not the project root. So `"dist"` resolved to `src-tauri/dist` (never populated — Vite writes to `saec-sync/dist`), producing `Unable to find your web assets`.
   - **Correct value**: `"frontendDist": "../dist"` → resolves to `saec-sync/dist`, the real Vite output.
   - The `mkdir -p` in `saec_run.sh` is kept (Tauri's `generate_context!()` checks the path exists at compile time), but now targets the correct directory.
   - **Takeaway**: Tauri prints the resolved absolute path in parentheses in the error message. Read it before editing the config.

4. **Script Automation**: Created `saec_run.sh` script that automates:
   - Verification of the auth race-condition fix (parses the real `useEffect` dependency array; fails if `polling` is still present)
   - Creation of required directories (`dist`, SQLite DB dir)
   - Cleaning of frontend caches
   - Launching dev mode or building DMG

5. **Persistent Auth Fix**: Added automatic token refresh at startup so users stay authenticated across app restarts.
   - **Rust** (`src-tauri/src/ipc/commands.rs`): New `refresh_credentials` command that checks token expiry and calls `/api/auth/token` with `refresh_token` if needed. Stores new tokens in keyring.
   - **Frontend** (`src/store/auth.ts`): `checkAuth` now calls `refresh_credentials` instead of `get_credentials`. If access token expired, it's transparently refreshed.
   - Result: User stays logged in as long as app is installed (refresh token valid ~30 days).

6. **Sync Debug Logging**: Added comprehensive debug logging to trace sync operations.
   - **Rust** (`src-tauri/src/api/client.rs`): All API calls (GET/PUT/DELETE/POST) log request path and response status.
   - **Rust** (`src-tauri/src/sync/engine.rs`): `sync_mount` logs mount info, remote file count, delta calculation results.
   - Purpose: Diagnose why sync doesn't start — likely missing server endpoints.

## Known Limitations (v0.1.36)

- **Synchronization NOT YET FUNCTIONAL** (client ready, server endpoints missing):
  - Client code in `src-tauri/src/sync/engine.rs` and `api/client.rs` is **fully implemented** for sync operations.
  - `SyncEngine` starts, indexes local files, calculates deltas, handles conflicts.
  - **Blocker**: Server endpoints `/api/sync/mounts/{id}/files` and related endpoints **do not exist** on SAEC Cloud (PHP).
  - Debug logging added to confirm: run with `RUST_LOG=saec_sync=debug` — will show 404/500 on `list_files` calls.
  - To enable sync: implement these endpoints in SAEC Cloud PHP backend:
    - `GET /api/sync/mounts/{id}/files?path=`
    - `GET /api/sync/mounts/{id}/files/{path}/content`
    - `PUT /api/sync/mounts/{id}/files/{path}` + `X-File-Checksum`
    - `DELETE /api/sync/mounts/{id}/files/{path}`
    - `POST /api/sync/mounts/{id}/files/{path}` + `{"type":"folder"}`
  - This is a **server-side implementation task**, not a client build issue.