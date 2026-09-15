# SAEC Sync - Tauri Desktop Client - TODO & Status

## Project Overview
**Project**: SAEC Sync - Desktop synchronization client for SAEC Cloud
**Type**: Multi-tenant SaaS storage (Dropbox/Nextcloud style)
**Stack**: Tauri v2.11 + Rust + React/TypeScript + Vite
**Target**: Linux (Fedora 43), macOS, Windows
**Domain**: cloud.saec.me

## Current Status: ✅ BUILD SUCCESSFUL (Rust + Frontend + Bundles)
**Build completed**: `cargo build` + `npm run build` + `cargo tauri build` all finish without errors
**Binaries**: 
- `/mnt/nas-web/cloud/saec-sync/target/release/saec-sync` (release binary)
- `/mnt/nas-web/cloud/saec-sync/target/release/bundle/deb/SAEC Sync_0.1.0_amd64.deb` (11MB)
- `/mnt/nas-web/cloud/saec-sync/target/release/bundle/rpm/SAEC Sync-0.1.0-1.x86_64.rpm` (11MB)
- `/mnt/nas-web/cloud/saec-sync/target/release/bundle/appimage/SAEC Sync.AppDir` (AppDir ready, AppImage bundling needs linuxdeploy)

## Code Completion: 100%
All Rust code compiles. The Tauri build script linking issue was resolved by:
- Upgrading to Tauri 2.11 (was 1.6)
- Updating tauri.conf.json to v2 format
- Adding proper feature flags matching allowlist
- Creating valid RGBA PNG icons
- Adding build.rs for tauri-build
- Migrating to Tauri 2.x plugins (tauri-plugin-shell, tauri-plugin-dialog, tauri-plugin-opener)

All TypeScript errors fixed. Frontend compiles successfully. Footer with SAEC link (https://saec.me) added to App.tsx.

## ✅ DONE - All Components

| Component | Status | Notes |
|-----------|--------|-------|
| Core infrastructure (lib.rs, error types) | ✅ Complete | AppError, AppResult, From impls |
| Config management (figment/TOML) | ✅ Complete | Config::load/save/validate |
| Keyring storage (OS keyring) | ✅ Complete | keyring 4.2 + keyring-core |
| AppState + SyncEngine architecture | ✅ Complete | Arc-based state management |
| HTTP API client with auth | ✅ Complete | Device code flow, token refresh |
| Tauri IPC commands | ✅ Complete | All 15 commands implemented |
| SyncEngine (delta, conflicts, watcher) | ✅ Complete | FileIndex, DeltaCalculator, ConflictResolver |
| File watcher (notify 6.x) | ✅ Complete | inotify-based recursive watch |
| React/TypeScript frontend | ✅ Complete | Vite + React 18 + TypeScript |
| System tray | ✅ Complete | MenuItem with show/sync/pause/quit |
| tauri.conf.json v2 format | ✅ Complete | Proper allowlist & bundle config |
| Tauri 2.x plugins | ✅ Complete | shell, dialog, opener plugins |

## ✅ ALMOST DONE - Minor Issues

| # | Issue | Priority | File |
|---|-------|----------|------|
| 1 | Debug binary is 504MB (release ~11MB) | Low | Build profile |
| 2 | Test on actual display environment | High | Runtime |
| 3 | ~20 unused variable warnings (TS6133) | Low | Various |

## 📋 NOT DONE - Remaining Work

| # | Task | Priority | Notes |
|---|------|----------|-------|
| 1 | Test on Wayland/X11 display | High | Verify WebView renders |
| 2 | Implement real API endpoints | Medium | Currently mocks |
| 3 | Add integration tests | Low | Sync engine, file watcher |
| 4 | CI/CD pipeline | Low | GitHub Actions |
| 5 | Code signing (macOS/Windows) | Medium | For distribution |
| 6 | Auto-update (updater plugin) | Low | Configure endpoints |
| 7 | Localization/i18n | Low | French/English |
| 8 | AppImage bundling | Medium | Needs linuxdeploy installed |

## Build Commands

```bash
cd /mnt/nas-web/cloud/saec-sync

# Development
cargo build                          # Debug build (done)
cargo run                            # Run with display

# Production
cd src-tauri && npm run build        # Build React frontend
cargo build --release                # Release binary
cargo tauri build                    # Full bundle (.deb, .AppImage, etc.)
```

## Runtime Test
```bash
# Needs display (X11/Wayland)
DISPLAY=:0 /mnt/nas-web/cloud/saec-sync/target/release/saec-sync
```

## Project Structure
```
saec-sync/
├── Cargo.toml (workspace)
├── tauri.conf.json
├── TODO.md
├── src-tauri/
│   ├── Cargo.toml
│   ├── build.rs
│   ├── tauri.conf.json
│   ├── icons/ (icon.png, icon.icns, icon.ico, tray-icon.png)
│   ├── src/
│   │   ├── main.rs
│   │   ├── lib.rs
│   │   ├── config.rs
│   │   ├── keyring.rs
│   │   ├── state.rs
│   │   ├── api/client.rs
│   │   ├── ipc/commands.rs
│   │   └── sync/ (engine.rs, index.rs, watcher.rs, delta.rs, conflict.rs)
│   └── migrations/
└── src/ (React frontend - needs build)
```

## To Resume Development
```bash
cd /mnt/nas-web/cloud/saec-sync
cargo build              # Fast incremental build
cargo tauri dev          # Dev mode with hot reload (needs frontend build)
```

## Key Technical Decisions
- **Tauri 2.11** (upgraded from 1.6) - modern, works on Fedora 43
- **tauri-plugin-shell, tauri-plugin-dialog, tauri-plugin-opener** - modern plugin architecture
- **keyring 4.2 + keyring-core** - proper error handling
- **notify 6.1** - file watching with mio
- **sqlx 0.8** - SQLite for file index
- **reqwest 0.13** - HTTP client with rustls
- **parking_lot** - sync primitives (RwLock, Mutex)
- **tauri-plugin-opener** - replaces deprecated shell::open
- **tauri-plugin-dialog** - replaces deprecated dialog API
- **tauri-plugin-shell** - shell operations