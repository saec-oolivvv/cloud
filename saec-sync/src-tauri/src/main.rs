#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use saec_sync::{
    config::Config,
    ipc::commands,
    state::AppState,
    sync::engine::SyncEngine,
};
use std::sync::Arc;
use tauri::Manager;

fn main() -> anyhow::Result<()> {
    tracing_subscriber::fmt()
        .with_env_filter(tracing_subscriber::EnvFilter::from_default_env())
        .with_target(false)
        .compact()
        .init();

    tracing::info!("Starting SAEC Sync v{}", env!("CARGO_PKG_VERSION"));

    tracing::info!("[main] Loading config...");
    let config = Config::load()?;
    tracing::info!("[main] Config loaded OK");

    tracing::info!("[main] Creating AppState...");
    let app_state = Arc::new(AppState::new(config.clone()));
    tracing::info!("[main] AppState created OK");

    tracing::info!("[main] Creating SyncEngine...");
    let sync_engine = SyncEngine::new(app_state.clone());
    tracing::info!("[main] SyncEngine created OK");

    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_dialog::init())
        .plugin(tauri_plugin_opener::init())
        .manage(app_state)
        .manage(Arc::new(sync_engine))
        .invoke_handler(tauri::generate_handler![
            commands::get_config,
            commands::set_config,
            commands::get_credentials,
            commands::store_credentials,
            commands::auth_device_code,
            commands::auth_poll_token,
            commands::auth_logout,
            commands::sync_start,
            commands::sync_pause,
            commands::sync_resume,
            commands::sync_status,
            commands::sync_force,
            commands::select_folder,
            commands::get_file_tree,
            commands::resolve_conflict,
            commands::get_conflicts,
            commands::open_external,
        ])
        .setup(|app| {
            tracing::info!("[main] Tauri setup starting...");

            if let Err(e) = commands::setup_tray(app.handle().clone()) {
                tracing::warn!("Tray setup failed (non-fatal): {}", e);
            }

            let engine = app.state::<Arc<SyncEngine>>().inner().clone();
            if engine.should_auto_start() {
                tracing::info!("[main] Auto-starting sync engine...");
                tauri::async_runtime::spawn(async move {
                    if let Err(e) = engine.start().await {
                        tracing::error!("Sync engine failed to start: {}", e);
                    }
                });
            }

            if let Some(window) = app.get_webview_window("main") {
                let _ = window.show();
                let _ = window.set_title("SAEC Sync");
            }

            tracing::info!("[main] Tauri setup done");
            Ok(())
        })
        .on_window_event(|window, event| {
            if let tauri::WindowEvent::CloseRequested { api, .. } = event {
                let _ = window.hide();
                api.prevent_close();
            }
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");

    Ok(())
}