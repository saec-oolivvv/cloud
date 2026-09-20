import { useEffect, useState } from 'react'
import { invoke } from '@tauri-apps/api/core'
import { listen } from '@tauri-apps/api/event'
import { useAuthStore } from './store/auth'
import { useSyncStore } from './store/sync'
import { useUIStore } from './store/ui'
import { Sidebar } from './components/Sidebar'
import { Header } from './components/Header'
import { Dashboard } from './components/Dashboard'
import { FileTree } from './components/FileTree/FileTree'
import { Settings } from './components/Settings'
import { ConflictsPanel } from './components/ConflictResolver/ConflictsPanel'
import { SyncStatusBar } from './components/SyncStatusBar'
import { Toaster } from './components/ui/Toaster'
import { Loader2, AlertCircle, CheckCircle } from 'lucide-react'

function App() {
  const [initialized, setInitialized] = useState(false)
  const [initError, setInitError] = useState<string | null>(null)
  const { checkAuth, isAuthenticated } = useAuthStore()
  const { fetchStatus, startSync, pauseSync, resumeSync } = useSyncStore()
  const { setSidebarOpen, sidebarOpen } = useUIStore()

  useEffect(() => {
    async function init() {
      try {
        console.log('[init] Starting...')
        console.log('[init] window.__TAURI_INTERNALS__:', typeof (window as any).__TAURI_INTERNALS__)
        console.log('[init] window.__TAURI__:', typeof (window as any).__TAURI__)
        console.log('[init] navigator.userAgent:', navigator.userAgent)

        if (!(window as any).__TAURI_INTERNALS__) {
          console.error('[init] Tauri IPC bridge NOT loaded! This app must run inside Tauri, not a browser.')
          setInitError('Tauri IPC bridge not available. This app must run as a desktop application, not in a browser.')
          return
        }
        
        console.log('[init] Calling get_config...')
        const config = await invoke<Config>('get_config')
        console.log('[init] Config loaded OK')
        
        console.log('[init] Calling checkAuth...')
        await checkAuth()
        console.log('[init] checkAuth OK')
        
        console.log('[init] Calling fetchStatus...')
        await fetchStatus()
        console.log('[init] fetchStatus OK')
        
        const unlistenStatus = await listen<SyncStatus>('sync://status', (event) => {
          useSyncStore.getState().setStatus(event.payload)
        })
        
        const unlistenConflicts = await listen<ConflictInfo[]>('sync://conflicts', (event) => {
          useSyncStore.getState().setConflicts(event.payload)
        })

        const unlistenFileTree = await listen<FileTreeNode>('sync://file-tree', (event) => {
          useSyncStore.getState().setFileTree(event.payload)
        })

        console.log('[init] All done, setting initialized=true')
        setInitialized(true)
        
        return () => {
          unlistenStatus()
          unlistenConflicts()
          unlistenFileTree()
        }
      } catch (error) {
        console.error('[init] FAILED:', error)
        setInitError(error instanceof Error ? error.message : 'Failed to initialize')
      }
    }

    init()
  }, [checkAuth, fetchStatus])

  if (!initialized) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-saec-50 dark:bg-saec-950">
        <div className="flex flex-col items-center gap-4">
          <div className="w-12 h-12 border-4 border-saec-500 border-t-transparent rounded-full animate-spin-slow" />
          <p className="text-saec-600 dark:text-saec-400 font-medium">Initialisation...</p>
        </div>
      </div>
    )
  }

  if (initError) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-saec-50 dark:bg-saec-950 p-8">
        <div className="card max-w-md w-full p-8 text-center">
          <AlertCircle className="w-16 h-16 text-red-500 mx-auto mb-4" />
          <h1 className="text-xl font-semibold text-saec-900 dark:text-saec-100 mb-2">
            Erreur d'initialisation
          </h1>
          <p className="text-saec-500 dark:text-saec-400 mb-6">{initError}</p>
          <button
            onClick={() => window.location.reload()}
            className="btn-primary"
          >
            Réessayer
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-saec-50 dark:bg-saec-950 flex flex-col">
      <Header />
      <div className="flex-1 flex overflow-hidden">
        <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
        <main className="flex-1 flex flex-col overflow-hidden">
          <SyncStatusBar />
          <div className="flex-1 overflow-auto p-6">
            {isAuthenticated ? (
              <>
                <Dashboard />
                <FileTree />
                <ConflictsPanel />
              </>
            ) : (
              <AuthView onSuccess={() => { checkAuth(); fetchStatus(); }} />
            )}
            <Settings />
          </div>
        </main>
      </div>
      <footer className="border-t border-saec-200 dark:border-saec-800 bg-saec-50 dark:bg-saec-900 py-3 px-6">
        <div className="flex items-center justify-between max-w-7xl mx-auto">
          <p className="text-sm text-saec-500 dark:text-saec-400">
            {'© ' + new Date().getFullYear() + ' '}
            <a 
              href="https://saec.me" 
              target="_blank" 
              rel="noopener noreferrer"
              className="text-saec-600 dark:text-saec-400 hover:text-saec-700 dark:hover:text-saec-300 font-medium transition-colors"
            >
              SAEC
            </a>
            {' - Synchronisation Cloud'}
          </p>
          <p className="text-xs text-saec-400 dark:text-saec-500">
            v0.1.0
          </p>
        </div>
      </footer>
      <Toaster />
    </div>
  )
}

// Types for Tauri events
interface Config {
  api: {
    base_url: string
    device_code_url: string
    token_url: string
    timeout_seconds: number
  }
  sync: {
    local_root: string
    auto_start: boolean
    interval_seconds: number
    bandwidth_limit_kbps: number | null
    max_concurrent_uploads: number
    max_concurrent_downloads: number
    chunk_size_bytes: number
    retry_attempts: number
    retry_base_delay_ms: number
    conflict_strategy: string
    ignored_patterns: string[]
  }
  ui: {
    theme: string
    language: string
    minimize_to_tray: boolean
    close_to_tray: boolean
    show_notifications: boolean
    start_minimized: boolean
  }
  advanced: {
    log_level: string
    enable_telemetry: boolean
    database_path: string
    cache_size_mb: number
    verify_checksums: boolean
  }
}

interface SyncStatus {
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

interface ConflictInfo {
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

interface FileTreeNode {
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

// Auth View Component
function AuthView({ onSuccess }: { onSuccess: () => void }) {
  const [deviceCode, setDeviceCode] = useState<DeviceCodeResponse | null>(null)
  const [polling, setPolling] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { login } = useAuthStore()

  const handleAuth = async () => {
    try {
      setError(null)
      const response = await invoke<DeviceCodeResponse>('auth_device_code')
      setDeviceCode(response)
      setPolling(true)
    } catch (err) {
      const msg = typeof err === 'string' ? err : err instanceof Error ? err.message : JSON.stringify(err)
      setError(msg)
    }
  }

  useEffect(() => {
    if (!polling) return

    let cancelled = false

    const unlistenToken = listen<TokenResponse>('auth://token', async (event) => {
      if (cancelled) return
      const token = event.payload
      await login(token.access_token, token.refresh_token, token.expires_in, token.tenant_id, token.user_email)
      setPolling(false)
      onSuccess()
    })

    const unlistenError = listen<string>('auth://error', (event) => {
      if (cancelled) return
      setError(event.payload)
      setPolling(false)
      setDeviceCode(null)
    })

    return () => {
      cancelled = true
      unlistenToken.then(fn => fn())
      unlistenError.then(fn => fn())
    }
  }, [polling, login, onSuccess])

  if (deviceCode && polling) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-saec-50 dark:bg-saec-950 p-8">
        <div className="card max-w-md w-full p-8 text-center animate-fade-in">
          <div className="w-16 h-16 bg-saec-100 dark:bg-saec-800 rounded-full flex items-center justify-center mx-auto mb-6">
            <Loader2 className="w-8 h-8 text-saec-600 dark:text-saec-400 animate-spin" />
          </div>
          <h1 className="text-xl font-semibold text-saec-900 dark:text-saec-100 mb-2">
            Autorisation en cours
          </h1>
          <p className="text-saec-500 dark:text-saec-400 mb-6">
            Veuillez autoriser l'application dans votre navigateur.<br />
            Cette fenêtre se fermera automatiquement.
          </p>
          <div className="bg-saec-100 dark:bg-saec-800 rounded-lg p-4 mb-4 text-left">
            <p className="text-sm font-mono text-saec-600 dark:text-saec-400 break-all">
              Code: <strong className="text-saec-900 dark:text-saec-100">{deviceCode.user_code}</strong>
            </p>
          </div>
          <button onClick={() => { setPolling(false); setDeviceCode(null); }} className="btn-secondary">
            Annuler
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-saec-50 dark:bg-saec-950 p-8">
      <div className="card max-w-md w-full p-8 animate-fade-in">
        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-saec-100 dark:bg-saec-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <CheckCircle className="w-8 h-8 text-saec-600 dark:text-saec-400" />
          </div>
          <h1 className="text-2xl font-bold text-saec-900 dark:text-saec-100 mb-2">
            SAEC Sync
          </h1>
          <p className="text-saec-500 dark:text-saec-400">
            Synchronisez vos fichiers avec SAEC Cloud
          </p>
        </div>

        {error && (
          <div className="badge-error mb-6 w-full justify-center" role="alert">
            {error}
          </div>
        )}

        <button
          onClick={handleAuth}
          disabled={polling}
          className="btn-primary w-full"
        >
          <Loader2 className="w-4 h-4 animate-spin" />
          Se connecter avec SAEC Cloud
        </button>

        <p className="mt-6 text-center text-sm text-saec-500 dark:text-saec-400">
          En cliquant sur « Se connecter », vous serez redirigé vers SAEC Cloud<br />
          pour autoriser l'accès à vos fichiers.
        </p>
      </div>
    </div>
  )
}

interface DeviceCodeResponse {
  device_code: string
  user_code: string
  verification_uri: string
  verification_uri_complete: string
  expires_in: number
  interval: number
}

interface TokenResponse {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
  tenant_id: string | null
  user_email: string
}

export default App