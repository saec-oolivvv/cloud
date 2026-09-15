import { RefreshCw as Sync, Pause, AlertTriangle, Upload, Download, ChevronDown, ChevronUp, Settings, AlertCircle, CheckCircle, XCircle, Loader2 } from 'lucide-react'
import { useSyncStore } from '../store/sync'
import { formatBytes, formatRelativeTime } from '../utils/format'
import type { SyncStatus } from '../store/sync'

export function SyncStatusBar() {
  const { status, conflicts, startSync, pauseSync, resumeSync, forceSync } = useSyncStore()
  const typedStatus: SyncStatus = status

  if (typedStatus.state === 'stopped' && typedStatus.files_pending === 0) {
    return null
  }

  const pendingConflicts = conflicts.filter(c => c.status === 'pending').length

  return (
    <div className="bg-saec-100 dark:bg-saec-900 border-b border-saec-200 dark:border-saec-800">
      <div className="max-w-7xl mx-auto px-4">
        <div className="h-10 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <SyncStatusIndicator status={typedStatus} />
            
            {typedStatus.current_file && (
              <span className="text-sm text-saec-600 dark:text-saec-400 truncate max-w-[200px]">
                {typedStatus.current_file}
              </span>
            )}

            {typedStatus.progress > 0 && typedStatus.progress < 100 && (
              <div className="w-48 progress-bar">
                <div
                  className="progress-bar-fill"
                  style={{ width: `${typedStatus.progress}%` }}
                />
              </div>
            )}

            {pendingConflicts > 0 && (
              <span className="badge-warning flex items-center gap-1">
                <AlertTriangle className="w-3 h-3" />
                {pendingConflicts} conflit(s)
              </span>
            )}
          </div>

          <div className="flex items-center gap-3">
{(typedStatus.upload_speed > 0 || typedStatus.download_speed > 0) && (
            <SpeedDisplay
              upload={typedStatus.upload_speed}
              download={typedStatus.download_speed}
              />
            )}

            <SyncControlButtons status={status} onStart={startSync} onPause={pauseSync} onResume={resumeSync} onForce={forceSync} />
          </div>
        </div>
      </div>
    </div>
  )
}

function SyncStatusIndicator({ status }: { status: SyncStatus }) {
  const icons = {
    syncing: <Loader2 className="w-4 h-4 animate-spin text-green-500" />,
    paused: <Pause className="w-4 h-4 text-yellow-500" />,
    error: <AlertCircle className="w-4 h-4 text-red-500" />,
    auth_required: <AlertTriangle className="w-4 h-4 text-orange-500" />,
    scanning: <Loader2 className="w-4 h-4 animate-spin text-blue-500" />,
    starting: <Loader2 className="w-4 h-4 animate-spin text-saec-500" />,
    stopped: <CheckCircle className="w-4 h-4 text-saec-400" />,
  }

  const labels = {
    syncing: 'Synchronisation en cours',
    paused: 'Synchronisation en pause',
    error: 'Erreur de synchronisation',
    auth_required: 'Authentification requise',
    scanning: 'Analyse des fichiers',
    starting: 'Démarrage du moteur',
    stopped: 'Synchronisation arrêtée',
  }

  return (
    <div className="flex items-center gap-2">
      {icons[status.state]}
      <span className="text-sm font-medium text-saec-700 dark:text-saec-300">
        {labels[status.state]}
      </span>
    </div>
  )
}

function SpeedDisplay({ upload, download }: { upload: number; download: number }) {
  return (
    <div className="flex items-center gap-4 text-xs">
      {upload > 0 && (
        <div className="flex items-center gap-1 text-blue-600 dark:text-blue-400">
          <Upload className="w-3 h-3" />
          <span className="font-mono">{formatBytes(upload)}/s ↑</span>
        </div>
      )}
      {download > 0 && (
        <div className="flex items-center gap-1 text-green-600 dark:text-green-400">
          <Download className="w-3 h-3" />
          <span className="font-mono">{formatBytes(download)}/s ↓</span>
        </div>
      )}
    </div>
  )
}

function SyncControlButtons({ 
  status, 
  onStart, 
  onPause, 
  onResume, 
  onForce 
}: { 
  status: SyncStatus
  onStart: () => Promise<void>
  onPause: () => Promise<void>
  onResume: () => Promise<void>
  onForce: () => Promise<void>
}) {
  const isBusy = status.state === 'starting' || status.state === 'scanning'

  return (
    <div className="flex items-center gap-2">
      {status.state === 'syncing' && (
        <button
          onClick={onPause}
          disabled={isBusy}
          className="btn-secondary btn-sm"
          title="Mettre en pause"
        >
          <Pause className="w-4 h-4" />
          <span className="hidden sm:inline">Pause</span>
        </button>
      )}

      {status.state === 'paused' && (
        <button
          onClick={onResume}
          className="btn-primary btn-sm"
          title="Reprendre"
        >
          <Upload className="w-4 h-4" />
          <span className="hidden sm:inline">Reprendre</span>
        </button>
      )}

      {(status.state === 'stopped' || status.state === 'error') && (
        <button
          onClick={onStart}
          disabled={isBusy}
          className="btn-primary btn-sm"
          title="Démarrer la synchronisation"
        >
          <Sync className="w-4 h-4" />
          <span className="hidden sm:inline">Démarrer</span>
        </button>
      )}

      {(status.state === 'syncing' || status.state === 'paused') && (
        <button
          onClick={onForce}
          disabled={isBusy}
          className="btn-ghost btn-sm"
          title="Forcer la synchronisation"
        >
          <Sync className="w-4 h-4" />
        </button>
      )}
    </div>
  )
}