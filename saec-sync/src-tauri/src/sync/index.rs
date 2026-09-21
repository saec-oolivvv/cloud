use crate::{AppError, AppResult};
use sqlx::{Pool, Sqlite, Row, sqlite::SqlitePoolOptions};
use std::path::{Path, PathBuf};
use std::sync::Arc;
use tokio::sync::Mutex;
use chrono::{DateTime, Utc};
use blake3;
use tracing::{debug, info};
use urlencoding::encode;

pub struct FileIndex {
    pool: Arc<Mutex<Option<Pool<Sqlite>>>>,
    db_path: PathBuf,
}

impl FileIndex {
    pub fn new() -> Self {
        Self {
            pool: Arc::new(Mutex::new(None)),
            db_path: PathBuf::new(),
        }
    }

    pub async fn load_or_create(&mut self, local_root: &Path) -> AppResult<()> {
        self.db_path = local_root.join(".saec-sync").join("index.db");
        
        if let Some(parent) = self.db_path.parent() {
            tokio::fs::create_dir_all(parent).await?;
        }

        let db_url = format!("sqlite://{}?mode=rwc", encode(&self.db_path.to_string_lossy()));

        let pool = SqlitePoolOptions::new()
            .max_connections(5)
            .connect(&db_url)
            .await?;

        // Run migrations
        sqlx::migrate!("./migrations").run(&pool).await.map_err(|e| AppError::Database(e.into()))?;

        *self.pool.lock().await = Some(pool);
        
        info!("File index loaded: {}", self.db_path.display());
        Ok(())
    }

    pub async fn full_scan(&self, local_root: &Path) -> AppResult<()> {
        let pool = self.pool.lock().await;
        let pool = pool.as_ref().ok_or_else(|| AppError::Internal("Database not initialized".to_string()))?;

        info!("Starting full scan of {}", local_root.display());

        // Clear existing index
        sqlx::query("DELETE FROM file_index").execute(pool).await?;

        let count = self.scan_dir(local_root, pool).await?;

        info!("Full scan complete: {} files indexed", count);
        Ok(())
    }

    async fn scan_dir(&self, root: &Path, pool: &Pool<Sqlite>) -> AppResult<u64> {
        let mut count = 0;
        let mut stack = vec![root.to_path_buf()];

        while let Some(dir) = stack.pop() {
            let mut entries = tokio::fs::read_dir(&dir).await?;

            while let Some(entry) = entries.next_entry().await? {
                let path = entry.path();
                let relative = path.strip_prefix(root).unwrap().to_path_buf();
                let relative_str = relative.to_string_lossy().to_string();

                if path.is_dir() {
                    sqlx::query(
                        "INSERT OR REPLACE INTO file_index (path, is_dir, size, modified, checksum, synced) 
                         VALUES (?, 1, 0, ?, '', 1)"
                    )
                    .bind(&relative_str)
                    .bind(chrono::Utc::now())
                    .execute(pool).await?;

                    stack.push(path);
                } else {
                    let metadata = entry.metadata().await?;
                    let modified: DateTime<Utc> = metadata.modified()?.into();

                    let content = tokio::fs::read(&path).await?;
                    let checksum = blake3::hash(&content).to_hex().to_string();

                    sqlx::query(
                        "INSERT OR REPLACE INTO file_index (path, is_dir, size, modified, checksum, synced) 
                         VALUES (?, 0, ?, ?, ?, 1)"
                    )
                    .bind(&relative_str)
                    .bind(metadata.len() as i64)
                    .bind(modified)
                    .bind(&checksum)
                    .execute(pool).await?;

                    count += 1;

                    if count % 1000 == 0 {
                        debug!("Indexed {} files", count);
                    }
                }
            }
        }
        Ok(count)
    }

    pub async fn update_local(&self, path: &str, size: u64, modified: DateTime<Utc>, checksum: Option<String>) -> AppResult<()> {
        let pool = self.pool.lock().await;
        let pool = pool.as_ref().ok_or_else(|| AppError::Internal("Database not initialized".to_string()))?;

        sqlx::query(
            "INSERT OR REPLACE INTO file_index (path, is_dir, size, modified, checksum, synced, local_modified) 
             VALUES (?, 0, ?, ?, ?, 0, ?)"
        )
        .bind(path)
        .bind(size as i64)
        .bind(modified)
        .bind(checksum.unwrap_or_default())
        .bind(modified)
        .execute(pool).await?;

        Ok(())
    }

    pub async fn remove_local(&self, path: &str) -> AppResult<()> {
        let pool = self.pool.lock().await;
        let pool = pool.as_ref().ok_or_else(|| AppError::Internal("Database not initialized".to_string()))?;

        sqlx::query("DELETE FROM file_index WHERE path = ?")
            .bind(path)
            .execute(pool).await?;

        sqlx::query("DELETE FROM file_index WHERE path LIKE ? || '/%'")
            .bind(path)
            .execute(pool).await?;

        Ok(())
    }

    pub async fn rename_local(&self, from: &str, to: &str) -> AppResult<()> {
        let pool = self.pool.lock().await;
        let pool = pool.as_ref().ok_or_else(|| AppError::Internal("Database not initialized".to_string()))?;

        sqlx::query("UPDATE file_index SET path = ? WHERE path = ?")
            .bind(to)
            .bind(from)
            .execute(pool).await?;

        sqlx::query("UPDATE file_index SET path = REPLACE(path, ?, ?) WHERE path LIKE ? || '/%'")
            .bind(to)
            .bind(from)
            .bind(from)
            .execute(pool).await?;

        Ok(())
    }

    pub async fn apply_remote_state(&self, remote_files: &[crate::api::client::FileInfo]) -> AppResult<()> {
        let pool = self.pool.lock().await;
        let pool = pool.as_ref().ok_or_else(|| AppError::Internal("Database not initialized".to_string()))?;

        let mut tx = pool.begin().await?;

        for file in remote_files {
            sqlx::query(
                "INSERT OR REPLACE INTO file_index (path, is_dir, size, modified, checksum, synced, remote_modified, remote_checksum) 
                 VALUES (?, ?, ?, ?, ?, 1, ?, ?)"
            )
            .bind(&file.path)
            .bind(file.is_dir)
            .bind(file.size as i64)
            .bind(file.modified)
            .bind(file.checksum.as_deref().unwrap_or(""))
            .bind(file.modified)
            .bind(file.checksum.as_deref().unwrap_or(""))
            .execute(&mut *tx).await?;
        }

        tx.commit().await?;
        Ok(())
    }

    pub fn build_tree(&self) -> crate::state::FileTreeNode {
        crate::state::FileTreeNode {
            id: "root".to_string(),
            name: "Sync Root".to_string(),
            path: "".to_string(),
            is_dir: true,
            size: None,
            modified: None,
            checksum: None,
            status: crate::state::FileStatus::Synced,
            children: vec![],
        }
    }

    pub async fn get_all_files(&self) -> AppResult<Vec<IndexedFile>> {
        let pool = self.pool.lock().await;
        let pool = pool.as_ref().ok_or_else(|| AppError::Internal("Database not initialized".to_string()))?;

        let rows = sqlx::query(
            "SELECT path, is_dir, size, modified, checksum, synced, local_modified, remote_modified, remote_checksum 
             FROM file_index ORDER BY path"
        )
        .fetch_all(pool).await?;

        let mut files = Vec::new();
        for row in rows {
            files.push(IndexedFile {
                path: row.get("path"),
                is_dir: row.get("is_dir"),
                size: row.get::<i64, _>("size") as u64,
                modified: row.get("modified"),
                checksum: row.get("checksum"),
                synced: row.get("synced"),
                local_modified: row.try_get("local_modified").ok(),
                remote_modified: row.try_get("remote_modified").ok(),
                remote_checksum: row.try_get("remote_checksum").ok(),
            });
        }

        Ok(files)
    }
}

#[derive(Debug, Clone)]
pub struct IndexedFile {
    pub path: String,
    pub is_dir: bool,
    pub size: u64,
    pub modified: DateTime<Utc>,
    pub checksum: String,
    pub synced: bool,
    pub local_modified: Option<DateTime<Utc>>,
    pub remote_modified: Option<DateTime<Utc>>,
    pub remote_checksum: Option<String>,
}