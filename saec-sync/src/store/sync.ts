import { create } from 'zustand'
import { invoke } from '@tauri-apps/api/core'

export interface SyncStatus {
  state: 'stopped' | 'starting' | 'scanning' | 'syncing' | 'paused' | 'error' | 'auth_required'
  last_sync: string | null
  next_sync: string | null
  files_pending: number
  bytes_pending: number
  current_file: string | null
  progress: number
  error: string | null
  upload_speed: number
  download_speed: number
}

export interface ConflictInfo {
  id: string
  path: string
  local_modified: string
  remote_modified: string
  local_checksum: string
  remote_checksum: string
  local_size: number
  remote_size: number
  status: 'pending' | 'resolving' | 'resolved'
}

export interface FileTreeNode {
  id: string
  name: string
  path: string
  is_dir: boolean
  size: number | null
  modified: string | null
  checksum: string | null
  status: 'synced' | 'pending_upload' | 'pending_download' | 'conflict' | 'error' | 'ignored'
  children: FileTreeNode[]
}

interface SyncState {
  status: SyncStatus
  conflicts: ConflictInfo[]
  fileTree: FileTreeNode | null
  selectedFiles: Set<string>
  fetchStatus: () => Promise<void>
  setStatus: (status: Partial<SyncStatus>) => void
  setConflicts: (conflicts: ConflictInfo[]) => void
  addConflict: (conflict: ConflictInfo) => void
  removeConflict: (id: string) => void
  setFileTree: (tree: FileTreeNode) => void
  startSync: () => Promise<void>
  pauseSync: () => Promise<void>
  resumeSync: () => Promise<void>
  forceSync: () => Promise<void>
  selectFile: (path: string, selected: boolean) => void
  clearSelection: () => void
  resolveConflict: (conflictId: string, resolution: 'keep_local' | 'keep_remote' | 'keep_both') => Promise<void>
}

export const useSyncStore = create<SyncState>((set, get) => ({
  status: {
    state: 'stopped',
    last_sync: null,
    next_sync: null,
    files_pending: 0,
    bytes_pending: 0,
    current_file: null,
    progress: 0,
    error: null,
    upload_speed: 0,
    download_speed: 0,
  },
  conflicts: [],
  fileTree: null,
  selectedFiles: new Set(),

  fetchStatus: async () => {
    try {
      const status = await invoke<SyncStatus>('sync_status')
      set({ status })
    } catch (error) {
      console.error('Failed to fetch sync status:', error)
    }
  },

  setStatus: (statusUpdate) => {
    set((state) => ({
      status: { ...state.status, ...statusUpdate },
    }))
  },

  setConflicts: (conflicts) => set({ conflicts }),

  addConflict: (conflict) => {
    set((state) => ({
      conflicts: [...state.conflicts, conflict],
    }))
  },

  removeConflict: (id) => {
    set((state) => ({
      conflicts: state.conflicts.filter((c) => c.id !== id),
    }))
  },

  setFileTree: (fileTree) => set({ fileTree }),

  startSync: async () => {
    try {
      await invoke('sync_start')
      set((state) => ({
        status: { ...state.status, state: 'syncing', error: null },
      }))
    } catch (error) {
      console.error('Failed to start sync:', error)
      set((state) => ({
        status: { ...state.status, state: 'error', error: String(error) },
      }))
    }
  },

  pauseSync: async () => {
    try {
      await invoke('sync_pause')
      set((state) => ({
        status: { ...state.status, state: 'paused' },
      }))
    } catch (error) {
      console.error('Failed to pause sync:', error)
    }
  },

  resumeSync: async () => {
    try {
      await invoke('sync_resume')
      set((state) => ({
        status: { ...state.status, state: 'syncing' },
      }))
    } catch (error) {
      console.error('Failed to resume sync:', error)
    }
  },

  forceSync: async () => {
    try {
      await invoke('sync_force')
    } catch (error) {
      console.error('Failed to force sync:', error)
    }
  },

  selectFile: (path, selected) => {
    set((state) => {
      const newSelection = new Set(state.selectedFiles)
      if (selected) {
        newSelection.add(path)
      } else {
        newSelection.delete(path)
      }
      return { selectedFiles: newSelection }
    })
  },

  clearSelection: () => set({ selectedFiles: new Set() }),

  resolveConflict: async (conflictId, resolution) => {
    try {
      await invoke('resolve_conflict', {
        conflict_id: conflictId,
        resolution,
      })
      get().removeConflict(conflictId)
    } catch (error) {
      console.error('Failed to resolve conflict:', error)
      throw error
    }
  },
}))