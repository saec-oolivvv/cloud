import { useState } from 'react'
import { FolderGit2, FileText, AlertTriangle, Settings, ChevronLeft, ChevronRight, RefreshCw as Sync } from 'lucide-react'
import { useUIStore } from '../store'
import { useSyncStore, SyncStatus } from '../store/sync'
import { useAuthStore } from '../store/auth'

interface SidebarProps {
  isOpen: boolean
  onClose: () => void
}

export function Sidebar({ isOpen, onClose }: SidebarProps) {
  const { activeView, setActiveView, sidebarOpen } = useUIStore()
  const { status, conflicts } = useSyncStore()
  const { isAuthenticated } = useAuthStore()

  const navigation = [
    { id: 'dashboard', label: 'Tableau de bord', icon: FolderGit2, badge: null },
    { id: 'files', label: 'Fichiers', icon: FileText, badge: status.files_pending > 0 ? status.files_pending : null },
    { id: 'conflicts', label: 'Conflits', icon: AlertTriangle, badge: conflicts.filter(c => c.status === 'pending').length > 0 ? conflicts.filter(c => c.status === 'pending').length : null },
    { id: 'settings', label: 'Paramètres', icon: Settings, badge: null },
  ]

  if (!isOpen) {
    return (
      <button
        onClick={onClose}
        className="fixed left-4 top-4 z-50 btn-ghost rounded-full p-2 shadow-lg bg-white dark:bg-saec-900"
        aria-label="Ouvrir la barre latérale"
      >
        <ChevronRight className="w-5 h-5" />
      </button>
    )
  }

  return (
    <aside className="fixed left-0 top-0 z-40 h-full w-72 bg-white dark:bg-saec-900 border-r border-saec-200 dark:border-saec-800 flex flex-col transition-transform duration-300 ease-in-out">
      <div className="flex items-center justify-between h-16 px-4 border-b border-saec-200 dark:border-saec-800">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 bg-saec-600 rounded-lg flex items-center justify-center">
            <Sync className="w-5 h-5 text-white" />
          </div>
          <span className="font-semibold text-saec-900 dark:text-saec-100">SAEC Sync</span>
        </div>
        <button
          onClick={onClose}
          className="btn-ghost p-1.5 rounded-lg"
          aria-label="Fermer la barre latérale"
        >
          <ChevronLeft className="w-5 h-5" />
        </button>
      </div>

      <nav className="flex-1 p-4 space-y-1 overflow-y-auto" role="navigation" aria-label="Navigation principale">
        {navigation.map((item) => {
          const Icon = item.icon
          const isActive = activeView === item.id
          return (
            <button
              key={item.id}
              onClick={() => setActiveView(item.id as typeof activeView)}
              disabled={item.id === 'conflicts' && !isAuthenticated}
              className={`sidebar-item w-full text-left ${isActive ? 'sidebar-item-active' : ''} ${!isAuthenticated && item.id === 'conflicts' ? 'opacity-50 cursor-not-allowed' : ''}`}
              aria-current={isActive ? 'page' : undefined}
            >
              <Icon className="w-5 h-5 flex-shrink-0" aria-hidden="true" />
              <span className="truncate">{item.label}</span>
              {item.badge && item.badge > 0 && (
                <span className="ml-auto badge badge-info">{item.badge > 99 ? '99+' : item.badge}</span>
              )}
            </button>
          )
        })}

        {isAuthenticated && status.state !== 'stopped' && (
          <div className="pt-4 mt-4 border-t border-saec-200 dark:border-saec-800">
            <div className="flex items-center gap-2 text-xs text-saec-500 dark:text-saec-400 mb-2">
              <Sync className="w-4 h-4" aria-hidden="true" />
              <span>Statut de synchronisation</span>
            </div>
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="text-saec-600 dark:text-saec-400">État</span>
                <span className={`font-medium capitalize ${getStatusColor(status.state)}`}>
                  {status.state.replace('_', ' ')}
                </span>
              </div>
              {status.last_sync && (
                <div className="flex items-center justify-between text-sm">
                  <span className="text-saec-600 dark:text-saec-400">Dernière sync</span>
                  <span className="font-medium text-saec-900 dark:text-saec-100">
                    {formatRelativeTime(status.last_sync)}
                  </span>
                </div>
              )}
              {status.files_pending > 0 && (
                <div className="flex items-center justify-between text-sm">
                  <span className="text-saec-600 dark:text-saec-400">En attente</span>
                  <span className="font-medium text-saec-900 dark:text-saec-100">
                    {status.files_pending} fichiers
                  </span>
                </div>
              )}
            </div>
          </div>
        )}
      </nav>

      <div className="p-4 border-t border-saec-200 dark:border-saec-800">
        <div className="flex items-center gap-3 text-sm">
          <div className="w-8 h-8 bg-saec-100 dark:bg-saec-800 rounded-full flex items-center justify-center">
            <Sync className="w-4 h-4 text-saec-600 dark:text-saec-400" />
          </div>
          <div className="flex-1 min-w-0">
            <p className="font-medium text-saec-900 dark:text-saec-100 truncate">
              {isAuthenticated ? 'Connecté' : 'Non connecté'}
            </p>
            <p className="text-saec-500 dark:text-saec-400 truncate">
              {isAuthenticated ? 'SAEC Cloud' : 'Cliquez pour vous connecter'}
            </p>
          </div>
        </div>
      </div>
    </aside>
  )
}

function getStatusColor(state: SyncStatus['state']): string {
  switch (state) {
    case 'syncing': return 'text-green-600 dark:text-green-400'
    case 'paused': return 'text-yellow-600 dark:text-yellow-400'
    case 'error': return 'text-red-600 dark:text-red-400'
    case 'auth_required': return 'text-orange-600 dark:text-orange-400'
    case 'scanning': return 'text-blue-600 dark:text-blue-400'
    default: return 'text-saec-500 dark:text-saec-400'
  }
}

function formatRelativeTime(isoString: string): string {
  const date = new Date(isoString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'À l\'instant'
  if (diffMins < 60) return `Il y a ${diffMins} min`
  if (diffHours < 24) return `Il y a ${diffHours} h`
  if (diffDays < 7) return `Il y a ${diffDays} j`
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}