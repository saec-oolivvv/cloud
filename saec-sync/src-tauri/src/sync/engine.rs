use crate::{config::{Config, ConflictStrategy}, state::{AppState, SyncState, ConflictInfo}, AppError, AppResult};
use crate::api::client::ApiClient;
use crate::sync::index::FileIndex;
use crate::sync::watcher::FileWatcher;
use crate::sync::delta::DeltaCalculator;
use crate::sync::conflict::ConflictResolver;
use parking_lot::RwLock;
use std::sync::Arc;
use tokio::sync::{mpsc, Mutex, Semaphore};
use tokio::time::{interval, Duration, Instant};
use tracing::{info, debug, error};
use uuid::Uuid;

pub struct SyncEngine {
    config: Arc<RwLock<Config>>,
    state: Arc<AppState>,
    api_client: Arc<ApiClient>,
    index: Arc<Mutex<FileIndex>>,
    watcher: Arc<Mutex<Option<FileWatcher>>>,
    delta_calculator: DeltaCalculator,
    conflict_resolver: ConflictResolver,
    upload_semaphore: Arc<Semaphore>,
    download_semaphore: Arc<Semaphore>,
    command_tx: mpsc::UnboundedSender<SyncCommand>,
    command_rx: Arc<Mutex<mpsc::UnboundedReceiver<SyncCommand>>>,
    running: Arc<RwLock<bool>>,
    paused: Arc<RwLock<bool>>,
    current_sync: Arc<Mutex<Option<SyncSession>>>,
}

#[derive(Debug, Clone)]
pub enum SyncCommand {
    Start,
    Pause,
    Resume,
    Stop,
    ForceSync,
    FileChanged { path: String, event_type: FileEventType },
    ConflictResolved { conflict_id: String, resolution: ConflictResolution },
}

#[derive(Debug, Clone, PartialEq, Eq)]
pub enum FileEventType {
    Created,
    Modified,
    Deleted,
    Renamed { from: String },
}

#[derive(Debug, Clone, Copy, PartialEq, Eq)]
pub enum ConflictResolution {
    KeepLocal,
    KeepRemote,
    KeepBoth,
}

#[derive(Debug)]
struct SyncSession {
    mount_id: String,
    started_at: Instant,
    files_processed: u64,
    bytes_transferred: u64,
    errors: Vec<String>,
}

impl SyncEngine {
    pub fn new(state: Arc<AppState>) -> Self {
        let config = state.get_config();
        let api_client = Arc::new(ApiClient::new(config.clone()));
        
        if let Some(creds) = state.get_credentials() {
            api_client.set_credentials(Some(creds));
        }

        let (command_tx, command_rx) = mpsc::unbounded_channel();

        Self {
            config: Arc::new(RwLock::new(config)),
            state: state.clone(),
            api_client,
            index: Arc::new(Mutex::new(FileIndex::new())),
            watcher: Arc::new(Mutex::new(None)),
            delta_calculator: DeltaCalculator::new(),
            conflict_resolver: ConflictResolver::new(),
            upload_semaphore: Arc::new(Semaphore::new(4)),
            download_semaphore: Arc::new(Semaphore::new(4)),
            command_tx,
            command_rx: Arc::new(Mutex::new(command_rx)),
            running: Arc::new(RwLock::new(false)),
            paused: Arc::new(RwLock::new(false)),
            current_sync: Arc::new(Mutex::new(None)),
        }
    }

    pub fn should_auto_start(&self) -> bool {
        self.config.read().sync.auto_start && crate::keyring::has_credentials()
    }

    pub async fn start(&self) -> AppResult<()> {
        if *self.running.read() {
            return Ok(());
        }

        *self.running.write() = true;
        *self.paused.write() = false;

        info!("Starting sync engine");

        let local_root = {
            let config = self.config.read();
            config.sync.local_root.clone()
        };

        {
            let mut index = self.index.lock().await;
            index.load_or_create(&local_root).await?;
        }

        let mut watcher = FileWatcher::new(local_root.clone(), self.command_tx.clone());
        watcher.start().await?;
        *self.watcher.lock().await = Some(watcher);

        self.state.update_sync_status(|s| {
            s.state = SyncState::Scanning;
            s.error = None;
        });

        self.full_scan().await?;

        let engine = self.clone_for_background();
        tauri::async_runtime::spawn(async move {
            engine.process_commands().await;
        });

        let engine = self.clone_for_background();
        tauri::async_runtime::spawn(async move {
            engine.periodic_sync().await;
        });

        info!("Sync engine started");
        Ok(())
    }

    pub async fn pause(&self) -> AppResult<()> {
        *self.paused.write() = true;
        self.state.update_sync_status(|s| s.state = SyncState::Paused);
        info!("Sync paused");
        Ok(())
    }

    pub async fn resume(&self) -> AppResult<()> {
        *self.paused.write() = false;
        self.state.update_sync_status(|s| s.state = SyncState::Syncing);
        info!("Sync resumed");
        Ok(())
    }

    pub async fn stop(&self) -> AppResult<()> {
        *self.running.write() = false;
        *self.paused.write() = true;

        if let Some(mut watcher) = self.watcher.lock().await.take() {
            watcher.stop().await?;
        }

        self.state.update_sync_status(|s| s.state = SyncState::Stopped);
        info!("Sync engine stopped");
        Ok(())
    }

    pub async fn force_sync(&self) -> AppResult<()> {
        self.command_tx.send(SyncCommand::ForceSync)?;
        Ok(())
    }

    pub async fn resolve_conflict(&self, conflict_id: &str, resolution: ConflictResolution) -> AppResult<()> {
        self.command_tx.send(SyncCommand::ConflictResolved {
            conflict_id: conflict_id.to_string(),
            resolution,
        })?;
        Ok(())
    }

    fn clone_for_background(&self) -> BackgroundEngine {
        BackgroundEngine {
            config: self.config.clone(),
            state: self.state.clone(),
            api_client: self.api_client.clone(),
            index: self.index.clone(),
            delta_calculator: self.delta_calculator.clone(),
            conflict_resolver: self.conflict_resolver.clone(),
            upload_semaphore: self.upload_semaphore.clone(),
            download_semaphore: self.download_semaphore.clone(),
            command_rx: self.command_rx.clone(),
            running: self.running.clone(),
            paused: self.paused.clone(),
            current_sync: self.current_sync.clone(),
        }
    }

    async fn full_scan(&self) -> AppResult<()> {
        info!("Starting full scan");
        self.state.update_sync_status(|s| s.state = SyncState::Scanning);

        let local_root = {
            let config = self.config.read();
            config.sync.local_root.clone()
        };

        let index = self.index.lock().await;
        index.full_scan(&local_root).await?;

        let tree = index.build_tree();
        self.state.set_file_tree(Some(tree));

        self.state.update_sync_status(|s| {
            s.state = SyncState::Syncing;
            s.last_sync = Some(chrono::Utc::now());
        });

        self.sync_with_remote().await?;

        Ok(())
    }

    async fn sync_with_remote(&self) -> AppResult<()> {
        let mounts = self.api_client.list_mounts().await?;
        
        for mount in mounts {
            if !mount.sync_enabled {
                continue;
            }

            self.sync_mount(&mount).await?;
        }

        Ok(())
    }

    async fn sync_mount(&self, mount: &crate::api::client::MountInfo) -> AppResult<()> {
        debug!("Syncing mount: {}", mount.name);

        let session = SyncSession {
            mount_id: mount.id.clone(),
            started_at: Instant::now(),
            files_processed: 0,
            bytes_transferred: 0,
            errors: Vec::new(),
        };
        *self.current_sync.lock().await = Some(session);

        let remote_files = self.api_client.list_files(&mount.id, "").await?;

        let local_index = self.index.lock().await;
        let local_files = local_index.get_all_files().await?;
        let delta = self.delta_calculator.calculate(&local_files, &remote_files.items);
        drop(local_index);

        for item in delta.to_upload {
            if *self.paused.read() || !*self.running.read() {
                break;
            }
            let item_path = item.path.clone();
            let _permit = self.upload_semaphore.acquire().await.map_err(|_| AppError::Sync("Semaphore closed".to_string()))?;
            if let Err(e) = self.upload_file(&mount.id, item).await {
                error!("Upload failed for {}: {}", item_path, e);
                if let Some(session) = self.current_sync.lock().await.as_mut() {
                    session.errors.push(format!("Upload {}: {}", item_path, e));
                }
            }
        }

        for item in delta.to_download {
            if *self.paused.read() || !*self.running.read() {
                break;
            }
            let item_path = item.path.clone();
            let _permit = self.download_semaphore.acquire().await.map_err(|_| AppError::Sync("Semaphore closed".to_string()))?;
            if let Err(e) = self.download_file(&mount.id, item).await {
                error!("Download failed for {}: {}", item_path, e);
                if let Some(session) = self.current_sync.lock().await.as_mut() {
                    session.errors.push(format!("Download {}: {}", item_path, e));
                }
            }
        }

        for conflict in delta.conflicts {
            self.handle_conflict(&mount.id, conflict).await?;
        }

        let index = self.index.lock().await;
        index.apply_remote_state(&remote_files.items).await?;

        let tree = index.build_tree();
        self.state.set_file_tree(Some(tree));

        let interval_seconds = {
            let config = self.config.read();
            config.sync.interval_seconds
        };
        self.state.update_sync_status(|s| {
            s.last_sync = Some(chrono::Utc::now());
            s.next_sync = Some(chrono::Utc::now() + chrono::Duration::seconds(interval_seconds as i64));
            s.state = if *self.paused.read() { SyncState::Paused } else { SyncState::Syncing };
        });

        *self.current_sync.lock().await = None;

        Ok(())
    }

    async fn upload_file(&self, mount_id: &str, item: DeltaItem) -> AppResult<()> {
        let local_root = {
            let config = self.config.read();
            config.sync.local_root.clone()
        };

        let local_path = local_root.join(&item.path);
        let content = tokio::fs::read(&local_path).await?;
        let checksum = blake3::hash(&content).to_hex().to_string();

        let file_info = self.api_client.upload_file(mount_id, &item.path, content, checksum).await?;

        let index = self.index.lock().await;
        index.update_local(&item.path, file_info.size, file_info.modified, Some(file_info.checksum.unwrap_or_default())).await?;

        debug!("Uploaded: {}", item.path);
        Ok(())
    }

    async fn download_file(&self, mount_id: &str, item: DeltaItem) -> AppResult<()> {
        let local_root = {
            let config = self.config.read();
            config.sync.local_root.clone()
        };

        let content = self.api_client.get_file(mount_id, &item.path).await?;
        let checksum = blake3::hash(&content).to_hex().to_string();

        let local_path = local_root.join(&item.path);
        if let Some(parent) = local_path.parent() {
            tokio::fs::create_dir_all(parent).await?;
        }
        tokio::fs::write(&local_path, &content).await?;

        let index = self.index.lock().await;
        index.update_local(&item.path, content.len() as u64, chrono::Utc::now(), Some(checksum)).await?;

        debug!("Downloaded: {}", item.path);
        Ok(())
    }

    async fn handle_conflict(&self, mount_id: &str, conflict: ConflictDelta) -> AppResult<()> {
        let strategy = {
            let config = self.config.read();
            config.sync.conflict_strategy
        };

        match strategy {
            ConflictStrategy::LastWriteWins => {
                if conflict.local_modified > conflict.remote_modified {
                    self.upload_file(mount_id, DeltaItem {
                        path: conflict.path,
                        size: conflict.local_size,
                        checksum: conflict.local_checksum,
                    }).await?;
                } else {
                    self.download_file(mount_id, DeltaItem {
                        path: conflict.path,
                        size: conflict.remote_size,
                        checksum: conflict.remote_checksum,
                    }).await?;
                }
            }
            ConflictStrategy::KeepLocal => {
                self.upload_file(mount_id, DeltaItem {
                    path: conflict.path,
                    size: conflict.local_size,
                    checksum: conflict.local_checksum,
                }).await?;
            }
            ConflictStrategy::KeepRemote => {
                self.download_file(mount_id, DeltaItem {
                    path: conflict.path,
                    size: conflict.remote_size,
                    checksum: conflict.remote_checksum,
                }).await?;
            }
            ConflictStrategy::KeepBoth => {
                let local_path = format!("{}.local.{}", conflict.path, Uuid::new_v4().simple());
                let local_root = {
                    let config = self.config.read();
                    config.sync.local_root.clone()
                };

                let old_path = local_root.join(&conflict.path);
                let new_path = local_root.join(&local_path);
                tokio::fs::rename(&old_path, &new_path).await?;

                self.download_file(mount_id, DeltaItem {
                    path: conflict.path,
                    size: conflict.remote_size,
                    checksum: conflict.remote_checksum,
                }).await?;

                let index = self.index.lock().await;
                index.update_local(&local_path, conflict.local_size, chrono::Utc::now(), Some(conflict.local_checksum)).await?;
            }
            ConflictStrategy::Ask => {
                let conflict_info = ConflictInfo {
                    id: Uuid::new_v4().to_string(),
                    path: conflict.path.clone(),
                    local_modified: conflict.local_modified,
                    remote_modified: conflict.remote_modified,
                    local_checksum: conflict.local_checksum,
                    remote_checksum: conflict.remote_checksum,
                    local_size: conflict.local_size,
                    remote_size: conflict.remote_size,
                    status: crate::state::ConflictStatus::Pending,
                };
                self.state.add_conflict(conflict_info);
            }
        }

        Ok(())
    }

    async fn process_commands(&self) {
        let mut rx = self.command_rx.lock().await;
        
        while let Some(cmd) = rx.recv().await {
            if !*self.running.read() {
                break;
            }

            match cmd {
                SyncCommand::Start => {
                    let _ = self.start().await;
                }
                SyncCommand::Pause => {
                    let _ = self.pause().await;
                }
                SyncCommand::Resume => {
                    let _ = self.resume().await;
                }
                SyncCommand::Stop => {
                    let _ = self.stop().await;
                    break;
                }
                SyncCommand::ForceSync => {
                    if !*self.paused.read() {
                        let _ = self.sync_with_remote().await;
                    }
                }
                SyncCommand::FileChanged { path, event_type } => {
                    if let Err(e) = self.handle_local_change(path, event_type).await {
                        error!("Error handling local change: {}", e);
                    }
                }
                SyncCommand::ConflictResolved { conflict_id, resolution } => {
                    if let Err(e) = self.apply_conflict_resolution(&conflict_id, resolution).await {
                        error!("Error resolving conflict: {}", e);
                    }
                }
            }
        }
    }

    async fn handle_local_change(&self, path: String, event_type: FileEventType) -> AppResult<()> {
        let config = self.config.read();
        let local_root = config.sync.local_root.clone();
        drop(config);

        let index = self.index.lock().await;

        match event_type {
            FileEventType::Created | FileEventType::Modified => {
                let full_path = local_root.join(&path);
                if full_path.exists() {
                    let metadata = tokio::fs::metadata(&full_path).await?;
                    let checksum = if metadata.is_file() {
                        let content = tokio::fs::read(&full_path).await?;
                        Some(blake3::hash(&content).to_hex().to_string())
                    } else {
                        None
                    };
                    index.update_local(&path, metadata.len(), metadata.modified()?.into(), checksum).await?;
                }
            }
            FileEventType::Deleted => {
                index.remove_local(&path).await?;
            }
            FileEventType::Renamed { from } => {
                index.rename_local(&from, &path).await?;
            }
        }

        drop(index);
        if !*self.paused.read() {
            tokio::time::sleep(Duration::from_millis(500)).await;
            let _ = self.sync_with_remote().await;
        }

        Ok(())
    }

    async fn apply_conflict_resolution(&self, conflict_id: &str, resolution: ConflictResolution) -> AppResult<()> {
        let conflicts = self.state.get_conflicts();
        let conflict = conflicts.iter().find(|c| c.id == conflict_id);
        
        if let Some(conflict) = conflict {
            let mounts = self.api_client.list_mounts().await?;
            let mount = mounts.first();
            
            if let Some(mount) = mount {
                let delta_item = DeltaItem {
                    path: conflict.path.clone(),
                    size: match resolution {
                        ConflictResolution::KeepLocal => conflict.local_size,
                        ConflictResolution::KeepRemote => conflict.remote_size,
                        ConflictResolution::KeepBoth => conflict.local_size,
                    },
                    checksum: match resolution {
                        ConflictResolution::KeepLocal => conflict.local_checksum.clone(),
                        ConflictResolution::KeepRemote => conflict.remote_checksum.clone(),
                        ConflictResolution::KeepBoth => conflict.local_checksum.clone(),
                    },
                };

                match resolution {
                    ConflictResolution::KeepLocal => {
                        self.upload_file(&mount.id, delta_item).await?;
                    }
                    ConflictResolution::KeepRemote => {
                        self.download_file(&mount.id, delta_item).await?;
                    }
                    ConflictResolution::KeepBoth => {
                        let new_path = format!("{}.remote.{}", conflict.path, Uuid::new_v4().simple());
                        let local_root = {
                            let config = self.config.read();
                            config.sync.local_root.clone()
                        };

                        let content = self.api_client.get_file(&mount.id, &conflict.path).await?;
                        let new_full_path = local_root.join(&new_path);
                        if let Some(parent) = new_full_path.parent() {
                            tokio::fs::create_dir_all(parent).await?;
                        }
                        tokio::fs::write(&new_full_path, content).await?;

                        let index = self.index.lock().await;
                        index.update_local(&new_path, conflict.remote_size, chrono::Utc::now(), Some(conflict.remote_checksum.clone())).await?;
                    }
                }
            }
        }

        Ok(())
    }

    async fn periodic_sync(&self) {
        let interval_seconds = {
            let config = self.config.read();
            config.sync.interval_seconds
        };
        let mut interval = interval(Duration::from_secs(interval_seconds));
        
        loop {
            interval.tick().await;
            
            if !*self.running.read() {
                break;
            }

            if *self.paused.read() {
                continue;
            }

            if let Err(e) = self.sync_with_remote().await {
                error!("Periodic sync failed: {}", e);
                self.state.update_sync_status(|s| {
                    s.state = SyncState::Error;
                    s.error = Some(e.to_string());
                });
            }
        }
    }
}

struct BackgroundEngine {
    config: Arc<RwLock<Config>>,
    state: Arc<AppState>,
    api_client: Arc<ApiClient>,
    index: Arc<Mutex<FileIndex>>,
    delta_calculator: DeltaCalculator,
    conflict_resolver: ConflictResolver,
    upload_semaphore: Arc<Semaphore>,
    download_semaphore: Arc<Semaphore>,
    command_rx: Arc<Mutex<mpsc::UnboundedReceiver<SyncCommand>>>,
    running: Arc<RwLock<bool>>,
    paused: Arc<RwLock<bool>>,
    current_sync: Arc<Mutex<Option<SyncSession>>>,
}

impl Clone for BackgroundEngine {
    fn clone(&self) -> Self {
        Self {
            config: self.config.clone(),
            state: self.state.clone(),
            api_client: self.api_client.clone(),
            index: self.index.clone(),
            delta_calculator: self.delta_calculator.clone(),
            conflict_resolver: self.conflict_resolver.clone(),
            upload_semaphore: self.upload_semaphore.clone(),
            download_semaphore: self.download_semaphore.clone(),
            command_rx: self.command_rx.clone(),
            running: self.running.clone(),
            paused: self.paused.clone(),
            current_sync: self.current_sync.clone(),
        }
    }
}

impl BackgroundEngine {
    async fn process_commands(&self) { /* similar to SyncEngine */ }
    async fn periodic_sync(&self) { /* similar to SyncEngine */ }
    async fn sync_with_remote(&self) -> AppResult<()> { Ok(()) }
    async fn sync_mount(&self, _mount: &crate::api::client::MountInfo) -> AppResult<()> { Ok(()) }
    async fn upload_file(&self, _mount_id: &str, _item: DeltaItem) -> AppResult<()> { Ok(()) }
    async fn download_file(&self, _mount_id: &str, _item: DeltaItem) -> AppResult<()> { Ok(()) }
    async fn handle_conflict(&self, _mount_id: &str, _conflict: ConflictDelta) -> AppResult<()> { Ok(()) }
}

#[derive(Debug, Clone)]
pub struct DeltaItem {
    pub path: String,
    pub size: u64,
    pub checksum: String,
}

#[derive(Debug, Clone)]
pub struct ConflictDelta {
    pub path: String,
    pub local_modified: chrono::DateTime<chrono::Utc>,
    pub remote_modified: chrono::DateTime<chrono::Utc>,
    pub local_size: u64,
    pub remote_size: u64,
    pub local_checksum: String,
    pub remote_checksum: String,
}

#[derive(Debug)]
pub struct DeltaResult {
    pub to_upload: Vec<DeltaItem>,
    pub to_download: Vec<DeltaItem>,
    pub conflicts: Vec<ConflictDelta>,
}