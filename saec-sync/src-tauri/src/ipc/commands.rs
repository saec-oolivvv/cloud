use crate::{config::Config, keyring::StoredCredentials, state::{AppState, SyncState, SyncStatus, ConflictInfo}, sync::engine::{SyncEngine, ConflictResolution as EngineConflictResolution}};
use tauri::{Manager, tray::TrayIconBuilder, menu::{Menu, MenuItem}, Emitter};
use tauri_plugin_opener::OpenerExt;
use tauri_plugin_dialog::DialogExt;
use std::sync::Arc;

#[tauri::command]
pub async fn get_config(state: tauri::State<'_, Arc<AppState>>) -> Result<Config, String> {
    tracing::info!("[cmd] get_config called");
    Ok(state.get_config())
}

#[tauri::command]
pub async fn get_credentials(state: tauri::State<'_, Arc<AppState>>) -> Result<Option<StoredCredentials>, String> {
    tracing::info!("[cmd] get_credentials called");
    let result = crate::keyring::load_credentials().map_err(|e| {
        tracing::warn!("[cmd] get_credentials keyring error: {}", e);
        e.to_string()
    })?;
    tracing::info!("[cmd] get_credentials result: has_creds={}", result.is_some());
    Ok(result)
}

#[tauri::command]
pub async fn store_credentials(
    access_token: String,
    refresh_token: String,
    expires_in: i64,
    tenant_id: Option<String>,
    user_email: String,
) -> Result<(), String> {
    crate::keyring::store_credentials(access_token, refresh_token, expires_in, tenant_id, user_email)
        .map_err(|e| e.to_string())
}

#[tauri::command]
pub async fn set_config(
    state: tauri::State<'_, Arc<AppState>>,
    config: Config,
) -> Result<(), String> {
    config.validate().map_err(|e| e.to_string())?;
    config.save().map_err(|e| e.to_string())?;
    state.update_config(|c| *c = config);
    Ok(())
}

#[tauri::command]
pub async fn auth_device_code(
    state: tauri::State<'_, Arc<AppState>>,
    app: tauri::AppHandle,
) -> Result<DeviceCodeResponse, String> {
    tracing::info!("[cmd] auth_device_code called");
    let config = state.get_config();
    tracing::info!("[cmd] device_code_url: {}", config.api.device_code_url);
    let client = reqwest::Client::builder()
        .timeout(std::time::Duration::from_secs(config.api.timeout_seconds))
        .user_agent("SAEC-Sync/0.1.36")
        .build()
        .map_err(|e| format!("Failed to create HTTP client: {}", e))?;

    tracing::info!("[cmd] Sending device code request...");
    let response = client
        .post(&config.api.device_code_url)
        .json(&serde_json::json!({
            "client_id": "saec-sync-desktop",
            "scope": "sync.read sync.write"
        }))
        .send()
        .await
        .map_err(|e| {
            tracing::error!("[cmd] Device code request failed: {}", e);
            format!("Request failed: {}", e)
        })?;

    tracing::info!("[cmd] Device code response status: {}", response.status());
    let data: DeviceCodeResponse = response
        .json()
        .await
        .map_err(|e| {
            tracing::error!("[cmd] Device code parse failed: {}", e);
            format!("Invalid response: {}", e)
        })?;

    app.opener().open_url(&data.verification_uri_complete, None::<&str>)
        .map_err(|e| format!("Failed to open browser: {}", e))?;

    Ok(data)
}

#[tauri::command]
pub async fn auth_poll_token(
    state: tauri::State<'_, Arc<AppState>>,
    device_code: String,
) -> Result<TokenResponse, String> {
    let config = state.get_config();
    let client = reqwest::Client::builder()
        .timeout(std::time::Duration::from_secs(config.api.timeout_seconds))
        .user_agent("SAEC-Sync/0.1.36")
        .build()
        .map_err(|e| format!("Failed to create HTTP client: {}", e))?;

    let response = client
        .post(&config.api.token_url)
        .json(&serde_json::json!({
            "grant_type": "urn:ietf:params:oauth:grant-type:device_code",
            "device_code": device_code,
            "client_id": "saec-sync-desktop"
        }))
        .send()
        .await
        .map_err(|e| format!("Request failed: {}", e))?;

    if response.status().is_success() {
        let data: TokenResponse = response
            .json()
            .await
            .map_err(|e| format!("Invalid response: {}", e))?;

        crate::keyring::store_credentials(
            data.access_token.clone(),
            data.refresh_token.clone(),
            data.expires_in as i64,
            data.tenant_id.clone(),
            data.user_email.clone(),
        ).map_err(|e| e.to_string())?;

        state.set_credentials(Some(StoredCredentials {
            access_token: data.access_token.clone(),
            refresh_token: data.refresh_token.clone(),
            expires_at: chrono::Utc::now().timestamp() + data.expires_in as i64,
            tenant_id: data.tenant_id.clone(),
            user_email: data.user_email.clone(),
        }));

        state.update_sync_status(|s| s.state = SyncState::Scanning);

        Ok(data)
    } else {
        let error_value: serde_json::Value = response.json().await.unwrap_or_default();
        let error_desc = error_value.get("error_description")
            .and_then(|v| v.as_str())
            .map(|s| s.to_string())
            .unwrap_or_else(|| "Authentication failed".to_string());
        Err(error_desc)
    }
}

#[tauri::command]
pub async fn auth_logout(state: tauri::State<'_, Arc<AppState>>) -> Result<(), String> {
    crate::keyring::clear_credentials().map_err(|e| e.to_string())?;
    state.set_credentials(None);
    state.update_sync_status(|s| s.state = SyncState::AuthRequired);
    Ok(())
}

#[tauri::command]
pub async fn sync_start(engine: tauri::State<'_, Arc<SyncEngine>>) -> Result<(), String> {
    let engine = engine.inner().clone();
    tauri::async_runtime::spawn(async move {
        if let Err(e) = engine.start().await {
            tracing::error!("Sync engine error: {}", e);
        }
    });
    Ok(())
}

#[tauri::command]
pub async fn sync_pause(engine: tauri::State<'_, Arc<SyncEngine>>) -> Result<(), String> {
    engine.pause().await.map_err(|e| e.to_string())?;
    Ok(())
}

#[tauri::command]
pub async fn sync_resume(engine: tauri::State<'_, Arc<SyncEngine>>) -> Result<(), String> {
    engine.resume().await.map_err(|e| e.to_string())?;
    Ok(())
}

#[tauri::command]
pub async fn sync_status(state: tauri::State<'_, Arc<AppState>>) -> Result<SyncStatus, String> {
    tracing::info!("[cmd] sync_status called");
    Ok(state.get_sync_status())
}

#[tauri::command]
pub async fn sync_force(engine: tauri::State<'_, Arc<SyncEngine>>) -> Result<(), String> {
    engine.force_sync().await.map_err(|e| e.to_string())
}

#[tauri::command]
pub async fn select_folder(app: tauri::AppHandle) -> Result<Option<String>, String> {
    let (tx, rx) = tokio::sync::oneshot::channel();
    app.dialog().file().pick_folder(move |folder| {
        let _ = tx.send(folder);
    });
    let folder = rx.await.map_err(|e| format!("Dialog error: {}", e))?;
    Ok(folder.map(|p| p.to_string()))
}

#[tauri::command]
pub async fn get_file_tree(state: tauri::State<'_, Arc<AppState>>) -> Result<Option<crate::state::FileTreeNode>, String> {
    Ok(state.get_file_tree())
}

#[tauri::command]
pub async fn get_conflicts(state: tauri::State<'_, Arc<AppState>>) -> Result<Vec<ConflictInfo>, String> {
    Ok(state.get_conflicts())
}

#[tauri::command]
pub async fn resolve_conflict(
    engine: tauri::State<'_, Arc<SyncEngine>>,
    conflict_id: String,
    resolution: ConflictResolution,
) -> Result<(), String> {
    let engine_resolution = match resolution {
        ConflictResolution::KeepLocal => EngineConflictResolution::KeepLocal,
        ConflictResolution::KeepRemote => EngineConflictResolution::KeepRemote,
        ConflictResolution::KeepBoth => EngineConflictResolution::KeepBoth,
    };
    engine.resolve_conflict(&conflict_id, engine_resolution).await.map_err(|e| e.to_string())?;
    Ok(())
}

#[tauri::command]
pub async fn open_external(app: tauri::AppHandle, url: String) -> Result<(), String> {
    app.opener().open_url(&url, None::<&str>)
        .map_err(|e| format!("Failed to open URL: {}", e))
}

pub fn setup_tray(app: tauri::AppHandle) -> Result<(), String> {
    let tray_menu = tauri::menu::MenuBuilder::new(&app)
        .text("show", "Show")
        .text("sync_now", "Sync Now")
        .text("pause", "Pause Sync")
        .separator()
        .text("settings", "Settings")
        .separator()
        .text("quit", "Quit")
        .build()
        .map_err(|e| e.to_string())?;

    let app_handle = app.clone();
    TrayIconBuilder::new()
        .menu(&tray_menu)
        .on_menu_event(move |app, event| {
            let app_handle = app_handle.clone();
            match event.id().0.as_str() {
                "show" => {
                    if let Some(window) = app.get_webview_window("main") {
                        let _ = window.show();
                        let _ = window.set_focus();
                    }
                }
                "sync_now" => {
                    let app_handle_clone = app_handle.clone();
                    tauri::async_runtime::spawn(async move {
                        let engine = app_handle_clone.state::<Arc<SyncEngine>>();
                        let engine = engine.inner().clone();
                        let _ = engine.force_sync().await;
                    });
                }
                "pause" => {
                    let app_handle_clone = app_handle.clone();
                    tauri::async_runtime::spawn(async move {
                        let engine = app_handle_clone.state::<Arc<SyncEngine>>();
                        let engine = engine.inner().clone();
                        let _ = engine.pause().await;
                    });
                }
                "settings" => {
                    if let Some(window) = app.get_webview_window("main") {
                        let _ = window.show();
                        let _ = window.set_focus();
                        let _ = window.emit("navigate", "settings");
                    }
                }
                "quit" => {
                    app.exit(0);
                }
                _ => {}
            }
        })
        .build(&app)
        .map_err(|e| e.to_string())?;

    Ok(())
}

#[derive(serde::Serialize, serde::Deserialize)]
pub struct DeviceCodeResponse {
    pub device_code: String,
    pub user_code: String,
    pub verification_uri: String,
    pub verification_uri_complete: String,
    pub expires_in: u64,
    pub interval: u64,
}

#[derive(serde::Serialize, serde::Deserialize)]
pub struct TokenResponse {
    pub access_token: String,
    pub refresh_token: String,
    pub token_type: String,
    pub expires_in: u64,
    pub tenant_id: Option<String>,
    pub user_email: String,
}

#[derive(serde::Serialize, serde::Deserialize)]
#[serde(rename_all = "snake_case")]
pub enum ConflictResolution {
    KeepLocal,
    KeepRemote,
    KeepBoth,
}