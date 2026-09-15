import { Menu, Sun, Moon, Monitor, Bell, ChevronDown, LogOut, User, Download, Upload, AlertTriangle } from 'lucide-react'
import { useState, useRef, useEffect } from 'react'
import { useUIStore } from '../store'
import { useSyncStore } from '../store/sync'
import { useAuthStore } from '../store/auth'
import { invoke } from '@tauri-apps/api/core'

export function Header() {
  const { theme, setTheme, sidebarOpen, toggleSidebar, notifications, removeNotification } = useUIStore()
  const { status, startSync, pauseSync, resumeSync, forceSync, conflicts } = useSyncStore()
  const { isAuthenticated, logout } = useAuthStore()
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [notificationsOpen, setNotificationsOpen] = useState(false)
  const userMenuRef = useRef<HTMLDivElement>(null)
  const notificationsRef = useRef<HTMLDivElement>(null)

  // Close dropdowns on outside click
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (userMenuRef.current && !userMenuRef.current.contains(event.target as Node)) {
        setUserMenuOpen(false)
      }
      if (notificationsRef.current && !notificationsRef.current.contains(event.target as Node)) {
        setNotificationsOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const pendingConflicts = conflicts.filter(c => c.status === 'pending').length

  const handleThemeChange = (newTheme: 'light' | 'dark' | 'system') => {
    setTheme(newTheme)
  }

  const handleSyncAction = async () => {
    switch (status.state) {
      case 'syncing':
        await pauseSync()
        break
      case 'paused':
        await resumeSync()
        break
      case 'stopped':
      case 'error':
        await startSync()
        break
      default:
        await forceSync()
    }
  }

  const getSyncButtonContent = () => {
    switch (status.state) {
      case 'syncing':
        return (
          <>
            <span className="animate-spin">⟳</span>
            <span>Pause</span>
          </>
        )
      case 'paused':
        return (
          <>
            <Upload className="w-4 h-4" />
            <span>Reprendre</span>
          </>
        )
      case 'error':
        return (
          <>
            <AlertTriangle className="w-4 h-4" />
            <span>Réessayer</span>
          </>
        )
      default:
        return (
          <>
            <Download className="w-4 h-4" />
            <span>Synchroniser</span>
          </>
        )
    }
  }

  return (
    <header className="h-16 bg-white/80 dark:bg-saec-950/80 backdrop-blur-lg border-b border-saec-200 dark:border-saec-800 flex items-center justify-between px-4 sticky top-0 z-30">
      <div className="flex items-center gap-4">
        <button
          onClick={toggleSidebar}
          className="btn-ghost p-2 lg:hidden"
          aria-label={sidebarOpen ? 'Fermer la barre latérale' : 'Ouvrir la barre latérale'}
        >
          <Menu className="w-5 h-5" />
        </button>

        <div className="hidden sm:flex items-center gap-2 border-r border-saec-200 dark:border-saec-800 pr-4">
          <button
            onClick={handleSyncAction}
            disabled={status.state === 'starting' || status.state === 'scanning'}
            className="btn-primary"
            aria-label={status.state === 'syncing' ? 'Mettre en pause la synchronisation' : 'Démarrer la synchronisation'}
          >
            {getSyncButtonContent()}
          </button>
        </div>
      </div>

      <div className="flex items-center gap-2">
        {/* Theme Toggle */}
        <div className="relative" ref={userMenuRef}>
          <button
            onClick={() => setUserMenuOpen(!userMenuOpen)}
            className="btn-ghost p-2 rounded-lg"
            aria-label="Changer le thème"
            aria-expanded={userMenuOpen}
          >
            {theme === 'dark' ? <Moon className="w-5 h-5" /> : theme === 'light' ? <Sun className="w-5 h-5" /> : <Monitor className="w-5 h-5" />}
          </button>
          {userMenuOpen && (
            <div className="dropdown z-50 py-1">
              <button
                onClick={() => handleThemeChange('light')}
                className={`dropdown-item w-full justify-start ${theme === 'light' ? 'bg-saec-500/10 text-saec-600' : ''}`}
              >
                <Sun className="w-4 h-4" />
                <span>Clair</span>
              </button>
              <button
                onClick={() => handleThemeChange('dark')}
                className={`dropdown-item w-full justify-start ${theme === 'dark' ? 'bg-saec-500/10 text-saec-600' : ''}`}
              >
                <Moon className="w-4 h-4" />
                <span>Sombre</span>
              </button>
              <button
                onClick={() => handleThemeChange('system')}
                className={`dropdown-item w-full justify-start ${theme === 'system' ? 'bg-saec-500/10 text-saec-600' : ''}`}
              >
                <Monitor className="w-4 h-4" />
                <span>Système</span>
              </button>
            </div>
          )}
        </div>

        {/* Notifications */}
        {notifications.length > 0 && (
          <div className="relative" ref={notificationsRef}>
            <button
              onClick={() => setNotificationsOpen(!notificationsOpen)}
              className="btn-ghost p-2 rounded-lg relative"
              aria-label={`Notifications (${notifications.length})`}
              aria-expanded={notificationsOpen}
            >
              <Bell className="w-5 h-5" />
              <span className="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-xs font-medium flex items-center justify-center">
                {notifications.length > 9 ? '9+' : notifications.length}
              </span>
            </button>
            {notificationsOpen && (
              <div className="dropdown z-50 w-80 max-h-96 overflow-y-auto">
                <div className="p-3 border-b border-saec-200 dark:border-saec-800 flex items-center justify-between">
                  <h3 className="font-medium text-saec-900 dark:text-saec-100">Notifications</h3>
                  <button
                    onClick={() => notifications.forEach(n => removeNotification(n.id))}
                    className="text-xs text-saec-500 hover:text-saec-700"
                  >
                    Tout effacer
                  </button>
                </div>
                <div className="py-2">
                  {notifications.slice().reverse().map((notification) => (
                    <div
                      key={notification.id}
                      className={`px-3 py-2 hover:bg-saec-100 dark:hover:bg-saec-800 border-l-3 ${
                        notification.type === 'success' ? 'border-green-500' :
                        notification.type === 'error' ? 'border-red-500' :
                        notification.type === 'warning' ? 'border-yellow-500' :
                        'border-blue-500'
                      }`}
                    >
                      <p className="font-medium text-sm text-saec-900 dark:text-saec-100">{notification.title}</p>
                      {notification.message && (
                        <p className="text-xs text-saec-500 dark:text-saec-400 mt-0.5">{notification.message}</p>
                      )}
                      <p className="text-xs text-saec-400 dark:text-saec-500 mt-1">
                        {new Date(notification.timestamp).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                      </p>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}

        {/* Conflicts Alert */}
        {pendingConflicts > 0 && (
          <button
            className="btn-ghost p-2 rounded-lg relative text-orange-600 dark:text-orange-400"
            aria-label={`${pendingConflicts} conflit(s) en attente`}
          >
            <AlertTriangle className="w-5 h-5" />
            <span className="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-orange-500 text-white text-xs font-medium flex items-center justify-center">
              {pendingConflicts > 9 ? '9+' : pendingConflicts}
            </span>
          </button>
        )}

        {/* User Menu */}
        {isAuthenticated && (
          <div className="relative" ref={userMenuRef}>
            <button
              onClick={() => setUserMenuOpen(!userMenuOpen)}
              className="btn-ghost p-2 rounded-lg flex items-center gap-2"
              aria-label="Menu utilisateur"
              aria-expanded={userMenuOpen}
            >
              <div className="w-8 h-8 bg-saec-100 dark:bg-saec-800 rounded-full flex items-center justify-center">
                <User className="w-4 h-4 text-saec-600 dark:text-saec-400" />
              </div>
              <span className="hidden sm:block text-sm font-medium text-saec-700 dark:text-saec-300">
                Connecté
              </span>
              <ChevronDown className="w-4 h-4 hidden sm:block" />
            </button>
            {userMenuOpen && (
              <div className="dropdown z-50 w-56">
                <div className="p-3 border-b border-saec-200 dark:border-saec-800">
                  <p className="text-sm font-medium text-saec-900 dark:text-saec-100">Compte</p>
                  <p className="text-xs text-saec-500 dark:text-saec-400 truncate">SAEC Cloud</p>
                </div>
                <button className="dropdown-item w-full justify-start text-red-600 dark:text-red-400" onClick={async () => { await logout(); setUserMenuOpen(false); }}>
                  <LogOut className="w-4 h-4" />
                  <span>Déconnexion</span>
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </header>
  )
}