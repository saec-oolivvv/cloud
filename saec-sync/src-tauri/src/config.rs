use crate::AppResult;
use directories::ProjectDirs;
use figment::{Figment, providers::{Format, Toml, Env, Serialized}};
use serde::{Deserialize, Serialize};
use std::path::PathBuf;
use toml;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Config {
    pub api: ApiConfig,
    pub sync: SyncConfig,
    pub ui: UiConfig,
    pub advanced: AdvancedConfig,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ApiConfig {
    pub base_url: String,
    pub device_code_url: String,
    pub token_url: String,
    pub timeout_seconds: u64,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SyncConfig {
    pub local_root: PathBuf,
    pub auto_start: bool,
    pub interval_seconds: u64,
    pub bandwidth_limit_kbps: Option<u64>,
    pub max_concurrent_uploads: usize,
    pub max_concurrent_downloads: usize,
    pub chunk_size_bytes: usize,
    pub retry_attempts: u32,
    pub retry_base_delay_ms: u64,
    pub conflict_strategy: ConflictStrategy,
    pub ignored_patterns: Vec<String>,
}

#[derive(Debug, Clone, Copy, PartialEq, Eq, Serialize, Deserialize)]
#[serde(rename_all = "snake_case")]
pub enum ConflictStrategy {
    LastWriteWins,
    KeepLocal,
    KeepRemote,
    KeepBoth,
    Ask,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct UiConfig {
    pub theme: Theme,
    pub language: String,
    pub minimize_to_tray: bool,
    pub close_to_tray: bool,
    pub show_notifications: bool,
    pub start_minimized: bool,
}

#[derive(Debug, Clone, Copy, PartialEq, Eq, Serialize, Deserialize)]
#[serde(rename_all = "lowercase")]
pub enum Theme {
    Light,
    Dark,
    System,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AdvancedConfig {
    pub log_level: String,
    pub enable_telemetry: bool,
    pub database_path: PathBuf,
    pub cache_size_mb: u64,
    pub verify_checksums: bool,
}

impl Default for Config {
    fn default() -> Self {
        let dirs = ProjectDirs::from("me", "saec", "sync")
            .expect("Failed to get project directories");

        let data_dir = dirs.data_dir().to_path_buf();
        let _config_dir = dirs.config_dir().to_path_buf();

        Self {
            api: ApiConfig {
                base_url: "https://cloud.saec.me/api".to_string(),
                device_code_url: "https://cloud.saec.me/api/auth/device".to_string(),
                token_url: "https://cloud.saec.me/api/auth/token".to_string(),
                timeout_seconds: 30,
            },
            sync: SyncConfig {
                local_root: data_dir.join("sync"),
                auto_start: true,
                interval_seconds: 30,
                bandwidth_limit_kbps: None,
                max_concurrent_uploads: 4,
                max_concurrent_downloads: 4,
                chunk_size_bytes: 4 * 1024 * 1024,
                retry_attempts: 3,
                retry_base_delay_ms: 1000,
                conflict_strategy: ConflictStrategy::LastWriteWins,
                ignored_patterns: vec![
                    "*.tmp".to_string(),
                    "*.temp".to_string(),
                    ".DS_Store".to_string(),
                    "Thumbs.db".to_string(),
                    "~$*".to_string(),
                    ".git/**".to_string(),
                    "node_modules/**".to_string(),
                ],
            },
            ui: UiConfig {
                theme: Theme::System,
                language: "fr".to_string(),
                minimize_to_tray: true,
                close_to_tray: true,
                show_notifications: true,
                start_minimized: false,
            },
            advanced: AdvancedConfig {
                log_level: "info".to_string(),
                enable_telemetry: false,
                database_path: data_dir.join("sync.db"),
                cache_size_mb: 500,
                verify_checksums: true,
            },
        }
    }
}

#[derive(Debug, thiserror::Error)]
pub enum ConfigError {
    #[error("Failed to determine config directory: {0}")]
    ConfigDir(String),

    #[error("Failed to parse config: {0}")]
    Parse(String),

    #[error("Failed to write config: {0}")]
    Write(#[from] std::io::Error),

    #[error("Validation error: {0}")]
    Validation(String),
}

impl From<figment::Error> for ConfigError {
    fn from(e: figment::Error) -> Self {
        ConfigError::Parse(e.to_string())
    }
}

impl Config {
    pub fn load() -> AppResult<Self> {
        let dirs = ProjectDirs::from("me", "saec", "sync")
            .ok_or_else(|| ConfigError::ConfigDir("Could not determine config directory".to_string()))?;

        let config_path = dirs.config_dir().join("config.toml");

        let figment = Figment::new()
            .merge(Serialized::defaults(Config::default()))
            .merge(Toml::file(&config_path))
            .merge(Env::prefixed("SAEC_SYNC_").split("_"));

        let config: Config = figment.extract()?;

        config.validate()?;

        std::fs::create_dir_all(&config.sync.local_root)?;
        std::fs::create_dir_all(config.advanced.database_path.parent().unwrap())?;

        Ok(config)
    }

    pub fn save(&self) -> AppResult<()> {
        let dirs = ProjectDirs::from("me", "saec", "sync")
            .ok_or_else(|| ConfigError::ConfigDir("Could not determine config directory".to_string()))?;

        let config_path = dirs.config_dir().join("config.toml");
        std::fs::create_dir_all(config_path.parent().unwrap())?;

        let toml_str = toml::to_string_pretty(self)?;
        std::fs::write(config_path, toml_str)?;

        Ok(())
    }

    pub fn validate(&self) -> Result<(), ConfigError> {
        if self.api.base_url.is_empty() {
            return Err(ConfigError::Validation("API base_url cannot be empty".to_string()));
        }
        if self.sync.interval_seconds < 5 {
            return Err(ConfigError::Validation("Sync interval must be at least 5 seconds".to_string()));
        }
        if self.sync.max_concurrent_uploads == 0 || self.sync.max_concurrent_downloads == 0 {
            return Err(ConfigError::Validation("Max concurrent operations must be > 0".to_string()));
        }
        Ok(())
    }

    pub fn config_path() -> PathBuf {
        ProjectDirs::from("me", "saec", "sync")
            .map(|d| d.config_dir().join("config.toml"))
            .unwrap_or_else(|| PathBuf::from("config.toml"))
    }
}