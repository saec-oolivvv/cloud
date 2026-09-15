//! SAEC Sync - Desktop synchronization client for SAEC Cloud

pub mod api;
pub mod config;
pub mod ipc;
pub mod keyring;
pub mod state;
pub mod sync;

use std::sync::Arc;

pub type AppResult<T> = Result<T, AppError>;

#[derive(thiserror::Error, Debug)]
pub enum AppError {
    #[error("Configuration error: {0}")]
    Config(#[from] config::ConfigError),

    #[error("Keyring error: {0}")]
    Keyring(String),

    #[error("Database error: {0}")]
    Database(#[from] sqlx::Error),

    #[error("HTTP error: {0}")]
    Http(#[from] reqwest::Error),

    #[error("IO error: {0}")]
    Io(#[from] std::io::Error),

    #[error("Serialization error: {0}")]
    Serde(#[from] serde_json::Error),

    #[error("Sync error: {0}")]
    Sync(String),

    #[error("Authentication error: {0}")]
    Auth(String),

    #[error("Internal error: {0}")]
    Internal(String),

    #[error("Notify error: {0}")]
    Notify(String),

    #[error("Tokio MPSC send error: {0}")]
    TokioMpscSend(String),

    #[error("Figment error: {0}")]
    Figment(String),

    #[error("Toml error: {0}")]
    Toml(String),

    #[error("Tauri error: {0}")]
    Tauri(String),
}



impl From<notify::Error> for AppError {
    fn from(e: notify::Error) -> Self {
        AppError::Notify(e.to_string())
    }
}

impl From<tokio::sync::mpsc::error::SendError<crate::sync::engine::SyncCommand>> for AppError {
    fn from(e: tokio::sync::mpsc::error::SendError<crate::sync::engine::SyncCommand>) -> Self {
        AppError::TokioMpscSend(e.to_string())
    }
}

impl From<figment::Error> for AppError {
    fn from(e: figment::Error) -> Self {
        AppError::Figment(e.to_string())
    }
}

impl From<keyring_core::Error> for AppError {
    fn from(e: keyring_core::Error) -> Self {
        AppError::Keyring(e.to_string())
    }
}

impl From<toml::ser::Error> for AppError {
    fn from(e: toml::ser::Error) -> Self {
        AppError::Toml(e.to_string())
    }
}

impl From<tauri::Error> for AppError {
    fn from(e: tauri::Error) -> Self {
        AppError::Tauri(e.to_string())
    }
}

impl From<AppError> for String {
    fn from(err: AppError) -> Self {
        err.to_string()
    }
}

pub type SharedState = Arc<parking_lot::RwLock<state::AppState>>;