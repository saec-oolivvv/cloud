use crate::sync::engine::{ConflictResolution, ConflictDelta};

#[derive(Clone, Copy)]
pub struct ConflictResolver;

impl ConflictResolver {
    pub fn new() -> Self {
        Self
    }

    pub fn resolve(
        &self,
        conflict: &ConflictDelta,
        resolution: ConflictResolution,
    ) -> ConflictResolutionResult {
        match resolution {
            ConflictResolution::KeepLocal => ConflictResolutionResult {
                action: ResolutionAction::UploadLocal,
                new_path: None,
            },
            ConflictResolution::KeepRemote => ConflictResolutionResult {
                action: ResolutionAction::DownloadRemote,
                new_path: None,
            },
            ConflictResolution::KeepBoth => ConflictResolutionResult {
                action: ResolutionAction::KeepBoth,
                new_path: Some(format!("{}.local.{}", conflict.path, uuid::Uuid::new_v4().simple())),
            },
        }
    }
}

pub struct ConflictResolutionResult {
    pub action: ResolutionAction,
    pub new_path: Option<String>,
}

pub enum ResolutionAction {
    UploadLocal,
    DownloadRemote,
    KeepBoth,
}