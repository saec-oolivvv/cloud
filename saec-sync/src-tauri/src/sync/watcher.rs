use crate::{AppResult, sync::engine::{SyncCommand, FileEventType}};
use notify::{Config as NotifyConfig, Event, EventKind, RecommendedWatcher, RecursiveMode, Watcher};
use std::path::Path;
use tokio::sync::mpsc;
use tracing::{debug, error, info};

pub struct FileWatcher {
    watcher: Option<RecommendedWatcher>,
    root_path: std::path::PathBuf,
    command_tx: mpsc::UnboundedSender<SyncCommand>,
}

impl FileWatcher {
    pub fn new(root_path: std::path::PathBuf, command_tx: mpsc::UnboundedSender<SyncCommand>) -> Self {
        Self {
            watcher: None,
            root_path,
            command_tx,
        }
    }

    pub async fn start(&mut self) -> AppResult<()> {
        let root_path = self.root_path.clone();
        let command_tx = self.command_tx.clone();

        let (tx, mut rx) = mpsc::unbounded_channel::<notify::Result<Event>>();

        let mut watcher = RecommendedWatcher::new(
            move |res| {
                let _ = tx.send(res);
            },
            NotifyConfig::default()
        )?;

        watcher.watch(&root_path, RecursiveMode::Recursive)?;
        
        info!("File watcher started for {}", root_path.display());

        let root_path_clone = root_path.clone();
        tauri::async_runtime::spawn(async move {
            while let Some(res) = rx.recv().await {
                match res {
                    Ok(event) => {
                        if let Err(e) = Self::process_event(&root_path_clone, event, &command_tx).await {
                            error!("Error processing file event: {}", e);
                        }
                    }
                    Err(e) => {
                        error!("Watcher error: {}", e);
                    }
                }
            }
        });

        self.watcher = Some(watcher);
        Ok(())
    }

    pub async fn stop(&mut self) -> AppResult<()> {
        if let Some(watcher) = self.watcher.take() {
            drop(watcher);
            info!("File watcher stopped");
        }
        Ok(())
    }

    async fn process_event(
        root_path: &Path,
        event: Event,
        command_tx: &mpsc::UnboundedSender<SyncCommand>,
    ) -> AppResult<()> {
        // Filter out our own index database changes
        if event.paths.iter().any(|p| p.file_name().map_or(false, |n| n == ".saec-sync")) {
            return Ok(());
        }

        // Filter out temporary files
        if event.paths.iter().any(|p| {
            p.file_name().map_or(false, |n| {
                let n = n.to_string_lossy();
                n.starts_with('.') || n.ends_with(".tmp") || n.ends_with(".temp") || n == "Thumbs.db" || n.starts_with("~$")
            })
        }) {
            return Ok(());
        }

        let event_type = match event.kind {
            EventKind::Create(_) => FileEventType::Created,
            EventKind::Modify(_) => FileEventType::Modified,
            EventKind::Remove(_) => FileEventType::Deleted,
            EventKind::Other => FileEventType::Modified,
            _ => return Ok(()),
        };

        for path in &event.paths {
            let relative = path.strip_prefix(root_path).unwrap_or(path);
            let relative_str = relative.to_string_lossy().to_string();

            debug!("File event: {:?} - {}", event_type, relative_str);

            let _ = command_tx.send(SyncCommand::FileChanged {
                path: relative_str,
                event_type: event_type.clone(),
            });
        }

        Ok(())
    }
}