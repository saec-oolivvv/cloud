use crate::{config::Config, keyring::StoredCredentials, AppError, AppResult};
use reqwest::{Client, RequestBuilder};
use serde::{Deserialize, Serialize};
use std::sync::Arc;
use std::time::Duration;
use tokio::sync::Mutex;

pub struct ApiClient {
    client: Client,
    config: Config,
    credentials: Arc<Mutex<Option<StoredCredentials>>>,
}

impl ApiClient {
    pub fn new(config: Config) -> Self {
        let client = Client::builder()
            .timeout(Duration::from_secs(config.api.timeout_seconds))
            .user_agent("SAEC-Sync/0.1.36")
            .gzip(true)
            .brotli(true)
            .build()
            .expect("Failed to create HTTP client");

        Self {
            client,
            config,
            credentials: Arc::new(Mutex::new(None)),
        }
    }

    pub fn set_credentials(&self, creds: Option<StoredCredentials>) {
        *self.credentials.blocking_lock() = creds;
    }

    async fn authenticated_request(&self, method: reqwest::Method, path: &str) -> AppResult<RequestBuilder> {
        let creds = self.credentials.lock().await;
        let mut builder = self.client.request(method, format!("{}{}", self.config.api.base_url, path));

        if let Some(creds) = creds.as_ref() {
            if chrono::Utc::now().timestamp() >= creds.expires_at - 60 {
                drop(creds);
                self.refresh_token().await?;
                let creds = self.credentials.lock().await;
                if let Some(creds) = creds.as_ref() {
                    builder = builder.bearer_auth(&creds.access_token);
                }
            } else {
                builder = builder.bearer_auth(&creds.access_token);
            }
        }

        Ok(builder)
    }

    async fn refresh_token(&self) -> AppResult<()> {
        let mut creds = self.credentials.lock().await;
        if let Some(current) = creds.as_ref() {
            let client = Client::builder()
                .user_agent("SAEC-Sync/0.1.36")
                .build()?;
            let response = client
                .post(&self.config.api.token_url)
                .json(&serde_json::json!({
                    "grant_type": "refresh_token",
                    "refresh_token": current.refresh_token,
                    "client_id": "saec-sync-desktop"
                }))
                .send()
                .await?;

            if response.status().is_success() {
                let data: TokenResponse = response.json().await?;
                let new_creds = StoredCredentials {
                    access_token: data.access_token,
                    refresh_token: data.refresh_token,
                    expires_at: chrono::Utc::now().timestamp() + data.expires_in as i64,
                    tenant_id: current.tenant_id.clone(),
                    user_email: current.user_email.clone(),
                };

                crate::keyring::store_credentials(
                    new_creds.access_token.clone(),
                    new_creds.refresh_token.clone(),
                    data.expires_in as i64,
                    new_creds.tenant_id.clone(),
                    new_creds.user_email.clone(),
                )?;

                *creds = Some(new_creds);
                tracing::info!("Token refreshed successfully");
            } else {
                return Err(AppError::Auth("Token refresh failed".to_string()));
            }
        }
        Ok(())
    }

    pub async fn get<T: for<'de> Deserialize<'de>>(&self, path: &str) -> AppResult<T> {
        let request = self.authenticated_request(reqwest::Method::GET, path).await?;
        let response = request.send().await?;
        self.handle_response(response).await
    }

    pub async fn post<T: for<'de> Deserialize<'de>, B: Serialize>(&self, path: &str, body: &B) -> AppResult<T> {
        let request = self.authenticated_request(reqwest::Method::POST, path).await?
            .json(body);
        let response = request.send().await?;
        self.handle_response(response).await
    }

    pub async fn put<T: for<'de> Deserialize<'de>, B: Serialize>(&self, path: &str, body: &B) -> AppResult<T> {
        let request = self.authenticated_request(reqwest::Method::PUT, path).await?
            .json(body);
        let response = request.send().await?;
        self.handle_response(response).await
    }

    pub async fn delete<T: for<'de> Deserialize<'de>>(&self, path: &str) -> AppResult<T> {
        let request = self.authenticated_request(reqwest::Method::DELETE, path).await?;
        let response = request.send().await?;
        self.handle_response(response).await
    }

    async fn handle_response<T: for<'de> Deserialize<'de>>(&self, response: reqwest::Response) -> AppResult<T> {
        let status = response.status();
        if status.is_success() {
            Ok(response.json().await?)
        } else {
            let error = response.error_for_status_ref().err().unwrap();
            Err(AppError::Http(error))
        }
    }

    pub async fn list_mounts(&self) -> AppResult<Vec<MountInfo>> {
        self.get("/sync/mounts").await
    }

    pub async fn list_files(&self, mount_id: &str, path: &str) -> AppResult<FileListResponse> {
        let path = format!("/sync/mounts/{}/files{}", mount_id, path);
        self.get(&path).await
    }

    pub async fn get_file(&self, mount_id: &str, path: &str) -> AppResult<Vec<u8>> {
        let path = format!("/sync/mounts/{}/files/{}/content", mount_id, path);
        let request = self.authenticated_request(reqwest::Method::GET, &path).await?;
        let response = request.send().await?;
        if response.status().is_success() {
            Ok(response.bytes().await?.to_vec())
        } else {
            let error = response.error_for_status_ref().err().unwrap();
            Err(AppError::Http(error))
        }
    }

    pub async fn upload_file(&self, mount_id: &str, path: &str, content: Vec<u8>, checksum: String) -> AppResult<FileInfo> {
        let path = format!("/sync/mounts/{}/files{}", mount_id, path);
        let request = self.authenticated_request(reqwest::Method::PUT, &path).await?
            .header("X-File-Checksum", checksum)
            .body(content);
        let response = request.send().await?;
        self.handle_response(response).await
    }

    pub async fn delete_file(&self, mount_id: &str, path: &str) -> AppResult<()> {
        let path = format!("/sync/mounts/{}/files{}", mount_id, path);
        let request = self.authenticated_request(reqwest::Method::DELETE, &path).await?;
        let response = request.send().await?;
        if response.status().is_success() {
            Ok(())
        } else {
            let error = response.error_for_status_ref().err().unwrap();
            Err(AppError::Http(error))
        }
    }

    pub async fn create_folder(&self, mount_id: &str, path: &str) -> AppResult<FileInfo> {
        let path = format!("/sync/mounts/{}/files{}", mount_id, path);
        let request = self.authenticated_request(reqwest::Method::POST, &path).await?
            .json(&serde_json::json!({ "type": "folder" }));
        let response = request.send().await?;
        self.handle_response(response).await
    }
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct MountInfo {
    pub id: String,
    pub name: String,
    pub provider_type: String,
    pub remote_path: String,
    pub local_alias: String,
    pub mount_type: String,
    pub sync_enabled: bool,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct FileListResponse {
    pub items: Vec<FileInfo>,
    pub has_more: bool,
    pub next_cursor: Option<String>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct FileInfo {
    pub id: String,
    pub name: String,
    pub path: String,
    pub is_dir: bool,
    pub size: u64,
    pub modified: chrono::DateTime<chrono::Utc>,
    pub checksum: Option<String>,
    pub mime_type: Option<String>,
}

#[derive(Debug, Deserialize)]
struct TokenResponse {
    access_token: String,
    refresh_token: String,
    expires_in: u64,
    tenant_id: Option<String>,
    user_email: String,
}