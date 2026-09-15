use crate::{config::Config, keyring::StoredCredentials};
use parking_lot::RwLock;

pub struct AppState {
    pub config: RwLock<Config>,
    pub credentials: RwLock<Option<StoredCredentials>>,
    pub sync_status: RwLock<SyncStatus>,
    pub file_tree: RwLock<Option<FileTreeNode>>,
    pub conflicts: RwLock<Vec<ConflictInfo>>,
}

#[derive(Debug, Clone, Default, serde::Serialize)]
pub struct SyncStatus {
    pub state: SyncState,
    pub last_sync: Option<chrono::DateTime<chrono::Utc>>,
    pub next_sync: Option<chrono::DateTime<chrono::Utc>>,
    pub files_pending: u64,
    pub bytes_pending: u64,
    pub current_file: Option<String>,
    pub progress: f32,
    pub error: Option<String>,
    pub upload_speed: u64,
    pub download_speed: u64,
}

#[derive(Debug, Clone, Copy, PartialEq, Eq, Default, serde::Serialize)]
#[serde(rename_all = "snake_case")]
pub enum SyncState {
    #[default]
    Stopped,
    Starting,
    Scanning,
    Syncing,
    Paused,
    Error,
    AuthRequired,
}

#[derive(Debug, Clone, serde::Serialize)]
pub struct FileTreeNode {
    pub id: String,
    pub name: String,
    pub path: String,
    pub is_dir: bool,
    pub size: Option<u64>,
    pub modified: Option<chrono::DateTime<chrono::Utc>>,
    pub checksum: Option<String>,
    pub status: FileStatus,
    pub children: Vec<FileTreeNode>,
}

#[derive(Debug, Clone, Copy, PartialEq, Eq, Default, serde::Serialize)]
#[serde(rename_all = "snake_case")]
pub enum FileStatus {
    #[default]
    Synced,
    PendingUpload,
    PendingDownload,
    Conflict,
    Error,
    Ignored,
}

#[derive(Debug, Clone, serde::Serialize)]
pub struct ConflictInfo {
    pub id: String,
    pub path: String,
    pub local_modified: chrono::DateTime<chrono::Utc>,
    pub remote_modified: chrono::DateTime<chrono::Utc>,
    pub local_checksum: String,
    pub remote_checksum: String,
    pub local_size: u64,
    pub remote_size: u64,
    pub status: ConflictStatus,
}

#[derive(Debug, Clone, Copy, PartialEq, Eq, Default, serde::Serialize)]
#[serde(rename_all = "snake_case")]
pub enum ConflictStatus {
    #[default]
    Pending,
    Resolving,
    Resolved,
}

impl AppState {
    pub fn new(config: Config) -> Self {
        let state = Self {
            config: RwLock::new(config),
            credentials: RwLock::new(None),
            sync_status: RwLock::new(SyncStatus::default()),
            file_tree: RwLock::new(None),
            conflicts: RwLock::new(Vec::new()),
        };

        if let Ok(Some(creds)) = crate::keyring::load_credentials() {
            *state.credentials.write() = Some(creds);
        }

        state
    }

    pub fn get_config(&self) -> Config {
        self.config.read().clone()
    }

    pub fn update_config(&self, f: impl FnOnce(&mut Config)) {
        let mut config = self.config.write();
        f(&mut config);
    }

    pub fn get_credentials(&self) -> Option<StoredCredentials> {
        self.credentials.read().clone()
    }

    pub fn set_credentials(&self, creds: Option<StoredCredentials>) {
        *self.credentials.write() = creds;
    }

    pub fn get_sync_status(&self) -> SyncStatus {
        self.sync_status.read().clone()
    }

    pub fn update_sync_status(&self, f: impl FnOnce(&mut SyncStatus)) {
        f(&mut self.sync_status.write());
    }

    pub fn get_file_tree(&self) -> Option<FileTreeNode> {
        self.file_tree.read().clone()
    }

    pub fn set_file_tree(&self, tree: Option<FileTreeNode>) {
        *self.file_tree.write() = tree;
    }

    pub fn get_conflicts(&self) -> Vec<ConflictInfo> {
        self.conflicts.read().clone()
    }

    pub fn set_conflicts(&self, conflicts: Vec<ConflictInfo>) {
        *self.conflicts.write() = conflicts;
    }

    pub fn add_conflict(&self, conflict: ConflictInfo) {
        self.conflicts.write().push(conflict);
    }

    pub fn remove_conflict(&self, id: &str) {
        self.conflicts.write().retain(|c| c.id != id);
    }
}