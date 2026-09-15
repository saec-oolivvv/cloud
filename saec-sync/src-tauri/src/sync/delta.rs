use crate::sync::engine::{DeltaItem, DeltaResult, ConflictDelta};
use crate::sync::index::IndexedFile;
use crate::api::client::FileInfo;
use std::collections::HashMap;

#[derive(Clone)]
pub struct DeltaCalculator;

impl DeltaCalculator {
    pub fn new() -> Self {
        Self
    }

    pub fn calculate(&self, local: &[IndexedFile], remote: &[FileInfo]) -> DeltaResult {
        let mut local_map: HashMap<String, &IndexedFile> = HashMap::new();
        for file in local {
            if !file.is_dir {
                local_map.insert(file.path.clone(), file);
            }
        }

        let mut remote_map: HashMap<String, &FileInfo> = HashMap::new();
        for file in remote {
            if !file.is_dir {
                remote_map.insert(file.path.clone(), file);
            }
        }

        let mut to_upload = Vec::new();
        let mut to_download = Vec::new();
        let mut conflicts = Vec::new();

        for (path, local_file) in &local_map {
            match remote_map.get(path) {
                Some(remote_file) => {
                    let local_changed = local_file.checksum != remote_file.checksum.as_deref().unwrap_or("");
                    let remote_changed = remote_file.checksum.as_deref().unwrap_or("") != local_file.checksum;

                    if local_changed && remote_changed {
                        conflicts.push(ConflictDelta {
                            path: path.clone(),
                            local_modified: local_file.modified,
                            remote_modified: remote_file.modified,
                            local_size: local_file.size,
                            remote_size: remote_file.size,
                            local_checksum: local_file.checksum.clone(),
                            remote_checksum: remote_file.checksum.clone().unwrap_or_default(),
                        });
                    } else if local_changed {
                        to_upload.push(DeltaItem {
                            path: path.clone(),
                            size: local_file.size,
                            checksum: local_file.checksum.clone(),
                        });
                    } else if remote_changed {
                        to_download.push(DeltaItem {
                            path: path.clone(),
                            size: remote_file.size,
                            checksum: remote_file.checksum.clone().unwrap_or_default(),
                        });
                    }
                }
                None => {
                    to_upload.push(DeltaItem {
                        path: path.clone(),
                        size: local_file.size,
                        checksum: local_file.checksum.clone(),
                    });
                }
            }
        }

        for (path, remote_file) in &remote_map {
            if !local_map.contains_key(path) {
                to_download.push(DeltaItem {
                    path: path.clone(),
                    size: remote_file.size,
                    checksum: remote_file.checksum.clone().unwrap_or_default(),
                });
            }
        }

        DeltaResult {
            to_upload,
            to_download,
            conflicts,
        }
    }
}