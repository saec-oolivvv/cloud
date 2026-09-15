import { RefreshCw as Sync, Upload, Download, FolderGit2, FileText, AlertTriangle, Cloud, HardDrive, TrendingUp, Clock, CheckCircle, AlertCircle, XCircle, Loader2 } from 'lucide-react'
import { useSyncStore } from '../store/sync'
import { useAuthStore } from '../store/auth'
import { formatBytes, formatRelativeTime } from '../utils/format'
import type { SyncStatus, FileTreeNode } from '../store/sync'

export function Dashboard() {
  const { status, conflicts, fileTree } = useSyncStore()
  const { isAuthenticated } = useAuthStore()

  if (!isAuthenticated) return null

  const pendingConflicts = conflicts.filter(c => c.status === 'pending').length
  const totalFiles = fileTree ? countFiles(fileTree) : 0
  const syncedFiles = fileTree ? countFilesByStatus(fileTree, 'synced') : 0

  const stats = [
    {
      label: 'Fichiers synchronisés',
      value: syncedFiles.toLocaleString('fr-FR'),
      icon: CheckCircle,
      color: 'text-green-600 dark:text-green-400',
      bg: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      label: 'En attente',
      value: status.files_pending.toLocaleString('fr-FR'),
      icon: Clock,
      color: 'text-yellow-600 dark:text-yellow-400',
      bg: 'bg-yellow-100 dark:bg-yellow-900/30',
    },
    {
      label: 'Conflits',
      value: pendingConflicts.toLocaleString('fr-FR'),
      icon: AlertTriangle,
      color: pendingConflicts > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400',
      bg: pendingConflicts > 0 ? 'bg-orange-100 dark:bg-orange-900/30' : 'bg-green-100 dark:bg-green-900/30',
    },
    {
      label: 'Total',
      value: totalFiles.toLocaleString('fr-FR'),
      icon: FileText,
      color: 'text-saec-600 dark:text-saec-400',
      bg: 'bg-saec-100 dark:bg-saec-800',
    },
  ]

  return (
    <div className="space-y-6 animate-fade-in" role="region" aria-label="Tableau de bord">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-saec-900 dark:text-saec-100">Tableau de bord</h1>
          <p className="text-saec-500 dark:text-saec-400 mt-1">Vue d'ensemble de la synchronisation</p>
        </div>
        <div className="flex items-center gap-3">
          <StatusIndicator status={status} />
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat) => (
          <StatCard key={stat.label} {...stat} />
        ))}
      </div>

      {/* Sync Status Card */}
      <div className="card">
        <div className="card-header">
          <h2 className="text-lg font-semibold text-saec-900 dark:text-saec-100 flex items-center gap-2">
            <Sync className="w-5 h-5 text-saec-600" />
            État de la synchronisation
          </h2>
        </div>
        <div className="card-body">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <h3 className="text-sm font-medium text-saec-500 dark:text-saec-400 mb-2">État actuel</h3>
              <div className="flex items-center gap-3">
                <StatusBadge state={status.state} />
                <div>
                  <p className="font-medium text-saec-900 dark:text-saec-100 capitalize">{status.state.replace('_', ' ')}</p>
                  {status.error && (
                    <p className="text-sm text-red-600 dark:text-red-400 mt-1">{status.error}</p>
                  )}
                </div>
              </div>
            </div>

            <div>
              <h3 className="text-sm font-medium text-saec-500 dark:text-saec-400 mb-2">Dernière synchronisation</h3>
              <p className="font-medium text-saec-900 dark:text-saec-100">
                {status.last_sync ? formatRelativeTime(status.last_sync) : 'Jamais'}
              </p>
            </div>

            <div>
              <h3 className="text-sm font-medium text-saec-500 dark:text-saec-400 mb-2">Prochaine synchronisation</h3>
              <p className="font-medium text-saec-900 dark:text-saec-100">
                {status.next_sync ? formatRelativeTime(status.next_sync) : '—'}
              </p>
            </div>
          </div>

          {(status.upload_speed > 0 || status.download_speed > 0) && (
            <div className="mt-6 pt-6 border-t border-saec-200 dark:border-saec-800">
              <h3 className="text-sm font-medium text-saec-500 dark:text-saec-400 mb-3">Vitesse de transfert</h3>
              <div className="grid grid-cols-2 gap-4">
                <SpeedMetric
                  label="Upload"
                  speed={status.upload_speed}
                  icon={<Upload className="w-4 h-4" />}
                  color="text-blue-600 dark:text-blue-400"
                />
                <SpeedMetric
                  label="Download"
                  speed={status.download_speed}
                  icon={<Download className="w-4 h-4" />}
                  color="text-green-600 dark:text-green-400"
                />
              </div>
            </div>
          )}

          {status.progress > 0 && status.progress < 100 && (
            <div className="mt-6">
              <div className="flex items-center justify-between text-sm mb-2">
                <span className="text-saec-500 dark:text-saec-400">Progression</span>
                <span className="font-medium text-saec-900 dark:text-saec-100">{Math.round(status.progress)}%</span>
              </div>
              <div className="progress-bar">
                <div
                  className="progress-bar-fill"
                  style={{ width: `${status.progress}%` }}
                />
              </div>
              {status.current_file && (
                <p className="text-xs text-saec-500 dark:text-saec-400 mt-1 truncate">
                  {status.current_file}
                </p>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="card">
        <div className="card-header">
          <h2 className="text-lg font-semibold text-saec-900 dark:text-saec-100">Actions rapides</h2>
        </div>
        <div className="card-body">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <QuickActionButton
              icon={Sync}
              label="Synchroniser maintenant"
              description="Forcer une synchronisation immédiate"
              onClick={() => {}}
            />
            <QuickActionButton
              icon={FolderGit2}
              label="Ouvrir le dossier local"
              description="Accéder aux fichiers synchronisés"
              onClick={() => {}}
            />
            <QuickActionButton
              icon={Cloud}
              label="Ouvrir SAEC Cloud"
              description="Gérer vos fichiers en ligne"
              onClick={() => {}}
              variant="secondary"
            />
          </div>
        </div>
      </div>
    </div>
  )
}

function StatCard({ label, value, icon: Icon, color, bg }: { label: string; value: string; icon: React.ComponentType<{ className?: string }>; color: string; bg: string }) {
  return (
    <div className="card">
      <div className="card-body">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-saec-500 dark:text-saec-400">{label}</p>
            <p className="text-2xl font-bold text-saec-900 dark:text-saec-100 mt-1">{value}</p>
          </div>
          <div className={`${bg} ${color} rounded-xl p-3`}>
            <Icon className="w-6 h-6" />
          </div>
        </div>
      </div>
    </div>
  )
}

function StatusIndicator({ status }: { status: SyncStatus }) {
  const getStateColor = (state: SyncStatus['state']) => {
    switch (state) {
      case 'syncing': return 'bg-green-500 animate-pulse-soft'
      case 'paused': return 'bg-yellow-500'
      case 'error': return 'bg-red-500'
      case 'auth_required': return 'bg-orange-500'
      case 'scanning': return 'bg-blue-500 animate-pulse-soft'
      default: return 'bg-saec-400'
    }
  }

  const getStateLabel = (state: typeof status.state) => {
    switch (state) {
      case 'syncing': return 'Synchronisation en cours'
      case 'paused': return 'En pause'
      case 'error': return 'Erreur'
      case 'auth_required': return 'Authentification requise'
      case 'scanning': return 'Analyse en cours'
      case 'starting': return 'Démarrage'
      default: return 'Arrêté'
    }
  }

  return (
    <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-saec-100 dark:bg-saec-800">
      <span className={`w-2.5 h-2.5 rounded-full ${getStateColor(status.state)}`} />
      <span className="text-sm font-medium text-saec-700 dark:text-saec-300">{getStateLabel(status.state)}</span>
    </div>
  )
}

function StatusBadge({ state }: { state: SyncStatus['state'] }) {
  const colors: Record<SyncStatus['state'], string> = {
    syncing: 'badge-success',
    paused: 'badge-warning',
    error: 'badge-error',
    auth_required: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    scanning: 'badge-info',
    starting: 'badge-info',
    stopped: 'badge-neutral',
  }

  const labels: Record<SyncStatus['state'], string> = {
    syncing: 'Synchronisation',
    paused: 'En pause',
    error: 'Erreur',
    auth_required: 'Auth requise',
    scanning: 'Analyse',
    starting: 'Démarrage',
    stopped: 'Arrêté',
  }

  return (
    <span className={`badge ${colors[state]}`}>
      {labels[state]}
    </span>
  )
}

function SpeedMetric({ label, speed, icon, color }: { label: string; speed: number; icon: React.ReactNode; color: string }) {
  return (
    <div className="flex items-center gap-3 p-3 bg-saec-50 dark:bg-saec-900/50 rounded-lg">
      <div className={`${color} p-2 rounded-lg bg-white dark:bg-saec-900`}>
        {icon}
      </div>
      <div>
        <p className="text-xs text-saec-500 dark:text-saec-400">{label}</p>
        <p className="font-mono font-medium text-saec-900 dark:text-saec-100">
          {formatBytes(speed)}/s
        </p>
      </div>
    </div>
  )
}

function QuickActionButton({ icon: Icon, label, description, onClick, variant = 'primary' }: { icon: React.ElementType; label: string; description: string; onClick: () => void; variant?: 'primary' | 'secondary' }) {
  return (
    <button
      onClick={onClick}
      className={`card p-4 flex items-center gap-4 transition-all hover:shadow-md ${
        variant === 'primary' ? 'hover:border-saec-300 dark:hover:border-saec-700' : 'hover:border-saec-300 dark:hover:border-saec-700'
      }`}
    >
      <div className="w-12 h-12 bg-saec-100 dark:bg-saec-800 rounded-xl flex items-center justify-center">
        <Icon className="w-6 h-6 text-saec-600 dark:text-saec-400" />
      </div>
      <div className="flex-1 text-left">
        <p className="font-medium text-saec-900 dark:text-saec-100">{label}</p>
        <p className="text-sm text-saec-500 dark:text-saec-400">{description}</p>
      </div>
    </button>
  )
}

function countFiles(node: { children?: FileTreeNode[]; is_dir: boolean }): number {
  if (!node.is_dir) return 1
  if (!node.children) return 0
  return node.children.reduce((acc: number, child) => acc + countFiles(child), 0)
}

function countFilesByStatus(node: FileTreeNode, targetStatus: FileTreeNode['status']): number {
  let count = node.status === targetStatus && !node.is_dir ? 1 : 0
  if (node.children) {
    count += node.children.reduce((acc, child) => acc + countFilesByStatus(child, targetStatus), 0)
  }
  return count
}