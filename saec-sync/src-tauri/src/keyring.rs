use crate::{AppError, AppResult};
use keyring::Entry;
use keyring_core::Error as KeyringError;
use serde::{Deserialize, Serialize};

static KEYRING_SERVICE: &str = "me.saec.sync";
static KEYRING_USER: &str = "auth";

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct StoredCredentials {
    pub access_token: String,
    pub refresh_token: String,
    pub expires_at: i64,
    pub tenant_id: Option<String>,
    pub user_email: String,
}

pub struct Keyring;

impl Keyring {
    fn entry() -> Result<Entry, KeyringError> {
        Entry::new(KEYRING_SERVICE, KEYRING_USER)
    }

    pub fn store(creds: &StoredCredentials) -> AppResult<()> {
        let entry = Self::entry()?;
        let json = serde_json::to_string(creds)?;
        entry.set_password(&json)?;
        tracing::debug!("Credentials stored in keyring");
        Ok(())
    }

    pub fn load() -> AppResult<Option<StoredCredentials>> {
        let entry = Self::entry()?;
        match entry.get_password() {
            Ok(json_string) => {
                let json_owned: String = json_string.to_string();
                let creds: StoredCredentials = serde_json::from_str(&json_owned)?;
                Ok(Some(creds))
            }
            Err(KeyringError::NoEntry) => Ok(None),
            Err(e) => Err(AppError::Keyring(e.to_string())),
        }
    }

    pub fn delete() -> AppResult<()> {
        let entry = Self::entry()?;
        entry.delete_credential()?;
        tracing::debug!("Credentials deleted from keyring");
        Ok(())
    }

    pub fn has_credentials() -> bool {
        Self::load().map(|c| c.is_some()).unwrap_or(false)
    }
}

pub fn has_credentials() -> bool {
    Keyring::has_credentials()
}

pub fn store_credentials(
    access_token: String,
    refresh_token: String,
    expires_in: i64,
    tenant_id: Option<String>,
    user_email: String,
) -> AppResult<()> {
    let creds = StoredCredentials {
        access_token,
        refresh_token,
        expires_at: chrono::Utc::now().timestamp() + expires_in,
        tenant_id,
        user_email,
    };
    Keyring::store(&creds)
}

pub fn load_credentials() -> AppResult<Option<StoredCredentials>> {
    Keyring::load()
}

pub fn clear_credentials() -> AppResult<()> {
    Keyring::delete()
}