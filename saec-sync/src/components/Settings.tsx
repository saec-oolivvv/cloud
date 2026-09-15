import { useState, useEffect } from 'react'
import { FolderGit2, HardDrive, Network, Shield, Bell, Palette, Globe, Save, Loader2, AlertCircle, CheckCircle, Trash2, Key, Eye, EyeOff, Copy, LogOut } from 'lucide-react'
import { useUIStore } from '../store'
import { useSyncStore } from '../store/sync'
import { useAuthStore } from '../store/auth'
import { invoke } from '@tauri-apps/api/core'
import { formatBytes } from '../utils/format'

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

const CONFLICT_STRATEGIES = [
  { value: 'last_write_wins', label: 'Dernière modification gagne', description: 'La version la plus récente écrase l\'autre' },
  { value: 'keep_local', label: 'Garder la version locale', description: 'Toujours privilégier vos fichiers locaux' },
  { value: 'keep_remote', label: 'Garder la version distante', description: 'Toujours privilégier les fichiers du cloud' },
  { value: 'keep_both', label: 'Garder les deux versions', description: 'Renommer et conserver les deux fichiers' },
  { value: 'ask', label: 'Demander à chaque fois', description: 'Afficher une fenêtre pour choisir' },
] as const

const LOG_LEVELS = ['error', 'warn', 'info', 'debug', 'trace'] as const

export function Settings() {
  const { theme, setTheme, activeView, setActiveView } = useUIStore()
  const { status } = useSyncStore()
  const { isAuthenticated, logout, user } = useAuthStore()
  const [config, setConfig] = useState<Config | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [saveSuccess, setSaveSuccess] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [showToken, setShowToken] = useState(false)
  const [activeTab, setActiveTab] = useState<'general' | 'sync' | 'advanced' | 'account'>('general')

  useEffect(() => {
    loadConfig()
  }, [])

  const loadConfig = async () => {
    try {
      setLoading(true)
      const cfg = await invoke<Config>('get_config')
      setConfig(cfg)
      setError(null)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur de chargement')
    } finally {
      setLoading(false)
    }
  }

  const handleSave = async () => {
    if (!config) return
    try {
      setSaving(true)
      setError(null)
      await invoke('set_config', { config })
      setSaveSuccess(true)
      setTimeout(() => setSaveSuccess(false), 3000)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur de sauvegarde')
    } finally {
      setSaving(false)
    }
  }

  const handleSelectFolder = async () => {
    try {
      const folder = await invoke<string | null>('select_folder')
      if (folder && config) {
        setConfig(prev => prev ? { ...prev, sync: { ...prev.sync, local_root: folder } } : null)
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur de sélection')
    }
  }

  const handleLogout = async () => {
    await logout()
    setActiveTab('account')
  }

  if (loading) {
    return (
      <div className="card flex-1 flex items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-saec-600" />
      </div>
    )
  }

  return (
    <div className="card flex-1 flex flex-col">
      <div className="card-header">
        <h2 className="text-lg font-semibold text-saec-900 dark:text-saec-100">Paramètres</h2>
      </div>

      {error && (
        <div className="mx-4 mt-4 badge-error" role="alert">
          <AlertCircle className="w-4 h-4" />
          {error}
        </div>
      )}

      <div className="flex-1 overflow-y-auto">
        <div className="flex flex-col lg:flex-row">
          {/* Sidebar */}
          <nav className="lg:w-48 flex-shrink-0 border-r border-saec-200 dark:border-saec-800 p-4 space-y-1" role="tablist" aria-label="Catégories de paramètres">
            {[
              { id: 'general', label: 'Général', icon: FolderGit2 },
              { id: 'sync', label: 'Synchronisation', icon: HardDrive },
              { id: 'advanced', label: 'Avancé', icon: Shield },
              { id: 'account', label: 'Compte', icon: Key },
            ].map((tab) => {
              const Icon = tab.icon
              return (
                <button
                  key={tab.id}
                  role="tab"
                  aria-selected={activeTab === tab.id}
                  onClick={() => setActiveTab(tab.id as typeof activeTab)}
                  className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    activeTab === tab.id
                      ? 'bg-saec-500/10 text-saec-600 dark:text-saec-400'
                      : 'text-saec-600 dark:text-saec-400 hover:bg-saec-100 dark:hover:bg-saec-800'
                  }`}
                >
                  <Icon className="w-5 h-5 flex-shrink-0" />
                  {tab.label}
                </button>
              )
            })}
          </nav>

          {/* Content */}
          <div className="flex-1 p-6 space-y-6">
            {activeTab === 'general' && config && (
              <GeneralSettings config={config} onChange={setConfig} theme={theme} onThemeChange={setTheme} />
            )}

            {activeTab === 'sync' && config && (
              <SyncSettings config={config} onChange={setConfig} onSelectFolder={handleSelectFolder} />
            )}

            {activeTab === 'advanced' && config && (
              <AdvancedSettings config={config} onChange={setConfig} />
            )}

            {activeTab === 'account' && (
              <AccountSettings isAuthenticated={isAuthenticated} user={user} onLogout={handleLogout} showToken={showToken} setShowToken={setShowToken} />
            )}
          </div>
        </div>
      </div>

      {/* Footer Actions */}
      <div className="card-footer flex justify-end gap-3 border-t border-saec-200 dark:border-saec-800">
        <button
          onClick={loadConfig}
          className="btn-secondary"
        >
          Annuler
        </button>
        <button
          onClick={handleSave}
          disabled={saving}
          className="btn-primary"
        >
          {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
          {saving ? 'Sauvegarde...' : 'Sauvegarder'}
        </button>
      </div>

      {saveSuccess && (
        <div className="fixed bottom-4 right-4 z-50 animate-slide-up badge-success shadow-lg" role="status">
          <CheckCircle className="w-4 h-4" />
          Paramètres sauvegardés
        </div>
      )}
    </div>
  )
}

function GeneralSettings({ config, onChange, theme, onThemeChange }: { config: Config; onChange: React.Dispatch<React.SetStateAction<Config | null>>; theme: 'light' | 'dark' | 'system'; onThemeChange: (t: 'light' | 'dark' | 'system') => void }) {
  return (
    <div className="space-y-6">
      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Palette className="w-5 h-5" />
          Apparence
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {['light', 'dark', 'system'].map((t) => (
            <label
              key={t}
              className={`p-4 rounded-lg border-2 cursor-pointer transition-all ${
                theme === t
                  ? 'border-saec-500 bg-saec-500/5 dark:bg-saec-500/10'
                  : 'border-saec-200 dark:border-saec-700 hover:border-saec-300 dark:hover:border-saec-600'
              }`}
            >
              <input
                type="radio"
                name="theme"
                value={t}
                checked={theme === t}
                onChange={() => onThemeChange(t as 'light' | 'dark' | 'system')}
                className="sr-only"
              />
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg flex items-center justify-center bg-saec-100 dark:bg-saec-800">
                  {t === 'light' && <span className="text-2xl">☀️</span>}
                  {t === 'dark' && <span className="text-2xl">🌙</span>}
                  {t === 'system' && <span className="text-2xl">💻</span>}
                </div>
                <div>
                  <p className="font-medium text-saec-900 dark:text-saec-100">{t === 'light' ? 'Clair' : t === 'dark' ? 'Sombre' : 'Système'}</p>
                  <p className="text-xs text-saec-500 dark:text-saec-400">
                    {t === 'light' ? 'Forcer le mode clair' : t === 'dark' ? 'Forcer le mode sombre' : 'Suivre le système'}
                  </p>
                </div>
              </div>
            </label>
          ))}
        </div>
      </section>

      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Globe className="w-5 h-5" />
          Langue et région
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="label">Langue</label>
            <select
              value={config.ui.language}
              onChange={(e) => onChange(prev => prev ? { ...prev, ui: { ...prev.ui, language: e.target.value } } : null)}
              className="input"
            >
              <option value="fr">Français</option>
              <option value="en">English</option>
            </select>
          </div>
        </div>
      </section>

      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Bell className="w-5 h-5" />
          Notifications
        </h3>
        <div className="space-y-3">
          <label className="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={config.ui.show_notifications}
              onChange={(e) => onChange(prev => prev ? { ...prev, ui: { ...prev.ui, show_notifications: e.target.checked } } : null)}
              className="w-4 h-4 rounded border-saec-300 text-saec-600 focus:ring-saec-500"
            />
            <span className="text-saec-700 dark:text-saec-300">Afficher les notifications de synchronisation</span>
          </label>
          <label className="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={config.ui.minimize_to_tray}
              onChange={(e) => onChange(prev => prev ? { ...prev, ui: { ...prev.ui, minimize_to_tray: e.target.checked } } : null)}
              className="w-4 h-4 rounded border-saec-300 text-saec-600 focus:ring-saec-500"
            />
            <span className="text-saec-700 dark:text-saec-300">Réduire dans la zone de notification</span>
          </label>
          <label className="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={config.ui.close_to_tray}
              onChange={(e) => onChange(prev => prev ? { ...prev, ui: { ...prev.ui, close_to_tray: e.target.checked } } : null)}
              className="w-4 h-4 rounded border-saec-300 text-saec-600 focus:ring-saec-500"
            />
            <span className="text-saec-700 dark:text-saec-300">Fermer la fenêtre réduit dans la zone de notification</span>
          </label>
          <label className="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={config.ui.start_minimized}
              onChange={(e) => onChange(prev => prev ? { ...prev, ui: { ...prev.ui, start_minimized: e.target.checked } } : null)}
              className="w-4 h-4 rounded border-saec-300 text-saec-600 focus:ring-saec-500"
            />
            <span className="text-saec-700 dark:text-saec-300">Démarrer minimisé au lancement</span>
          </label>
        </div>
      </section>
    </div>
  )
}

function SyncSettings({ config, onChange, onSelectFolder }: { config: Config; onChange: React.Dispatch<React.SetStateAction<Config | null>>; onSelectFolder: () => void }) {
  return (
    <div className="space-y-6">
      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <FolderGit2 className="w-5 h-5" />
          Dossier de synchronisation
        </h3>
        <div className="space-y-4">
          <div>
            <label className="label">Dossier local</label>
            <div className="flex gap-2">
              <input
                type="text"
                value={config.sync.local_root}
                readOnly
                className="input flex-1 bg-saec-100 dark:bg-saec-800"
              />
              <button onClick={onSelectFolder} className="btn-secondary">
                Parcourir
              </button>
            </div>
            <p className="text-xs text-saec-500 dark:text-saec-400 mt-1">
              Tous les fichiers synchronisés seront stockés dans ce dossier.
            </p>
          </div>
        </div>
      </section>

      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <HardDrive className="w-5 h-5" />
          Comportement de synchronisation
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="label">Intervalle de synchronisation (secondes)</label>
            <input
              type="number"
              min="5"
              max="3600"
              value={config.sync.interval_seconds}
              onChange={(e) => onChange(prev => prev ? { ...prev, sync: { ...prev.sync, interval_seconds: parseInt(e.target.value) || 30 } } : null)}
              className="input"
            />
          </div>
          <div>
            <label className="label">Démarrage automatique</label>
            <label className="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={config.sync.auto_start}
                onChange={(e) => onChange(prev => prev ? { ...prev, sync: { ...prev.sync, auto_start: e.target.checked } } : null)}
                className="w-4 h-4 rounded border-saec-300 text-saec-600 focus:ring-saec-500"
              />
              <span className="text-saec-700 dark:text-saec-300">Démarrer la synchronisation au lancement de l'application</span>
            </label>
          </div>
        </div>
      </section>

      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Shield className="w-5 h-5" />
          Gestion des conflits
        </h3>
        <div>
          <label className="label">Stratégie par défaut</label>
          <select
            value={config.sync.conflict_strategy}
            onChange={(e) => onChange(prev => prev ? { ...prev, sync: { ...prev.sync, conflict_strategy: e.target.value } } : null)}
            className="input"
          >
            {CONFLICT_STRATEGIES.map((s) => (
              <option key={s.value} value={s.value}>{s.label}</option>
            ))}
          </select>
          <p className="text-xs text-saec-500 dark:text-saec-400 mt-1">
            {CONFLICT_STRATEGIES.find(s => s.value === config.sync.conflict_strategy)?.description}
          </p>
        </div>
      </section>

      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Network className="w-5 h-5" />
          Performance et bande passante
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label className="label">Uploads simultanés</label>
            <input
              type="number"
              min="1"
              max="16"
              value={config.sync.max_concurrent_uploads}
              onChange={(e) => onChange(prev => prev ? { ...prev, sync: { ...prev.sync, max_concurrent_uploads: parseInt(e.target.value) || 4 } } : null)}
              className="input"
            />
          </div>
          <div>
            <label className="label">Downloads simultanés</label>
            <input
              type="number"
              min="1"
              max="16"
              value={config.sync.max_concurrent_downloads}
              onChange={(e) => onChange(prev => prev ? { ...prev, sync: { ...prev.sync, max_concurrent_downloads: parseInt(e.target.value) || 4 } } : null)}
              className="input"
            />
          </div>
          <div>
            <label className="label">Taille des chunks (Mo)</label>
            <input
              type="number"
              min="1"
              max="100"
              value={config.sync.chunk_size_bytes / 1024 / 1024}
              onChange={(e) => onChange(prev => prev ? { ...prev, sync: { ...prev.sync, chunk_size_bytes: (parseInt(e.target.value) || 4) * 1024 * 1024 } } : null)}
              className="input"
            />
          </div>
          <div>
            <label className="label">Limite bande passante (Kbps, 0 = illimité)</label>
            <input
              type="number"
              min="0"
              value={config.sync.bandwidth_limit_kbps || 0}
              onChange={(e) => onChange(prev => prev ? { ...prev, sync: { ...prev.sync, bandwidth_limit_kbps: parseInt(e.target.value) || null } } : null)}
              className="input"
            />
          </div>
        </div>
      </section>

      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Trash2 className="w-5 h-5" />
          Fichiers ignorés
        </h3>
        <div>
          <textarea
            value={config.sync.ignored_patterns.join('\n')}
            onChange={(e) => onChange(prev => prev ? { ...prev, sync: { ...prev.sync, ignored_patterns: e.target.value.split('\n').map(s => s.trim()).filter(Boolean) } } : null)}
            className="input font-mono text-sm min-h-[100px] resize-y"
            placeholder="*.tmp&#10;*.log&#10;.git/**&#10;node_modules/**"
          />
          <p className="text-xs text-saec-500 dark:text-saec-400 mt-1">
            Un motif par ligne. Supporte les glob patterns (ex: *.tmp, .git/**).
          </p>
        </div>
      </section>
    </div>
  )
}

function AdvancedSettings({ config, onChange }: { config: Config; onChange: React.Dispatch<React.SetStateAction<Config | null>> }) {
  return (
    <div className="space-y-6">
      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Shield className="w-5 h-5" />
          Journalisation et diagnostics
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="label">Niveau de log</label>
            <select
              value={config.advanced.log_level}
              onChange={(e) => onChange(prev => prev ? { ...prev, advanced: { ...prev.advanced, log_level: e.target.value } } : null)}
              className="input"
            >
              {LOG_LEVELS.map((l) => (
                <option key={l} value={l}>{l}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="label">Taille du cache (Mo)</label>
            <input
              type="number"
              min="50"
              max="5000"
              value={config.advanced.cache_size_mb}
              onChange={(e) => onChange(prev => prev ? { ...prev, advanced: { ...prev.advanced, cache_size_mb: parseInt(e.target.value) || 500 } } : null)}
              className="input"
            />
          </div>
        </div>
        <div className="space-y-3 mt-4">
          <label className="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={config.advanced.verify_checksums}
              onChange={(e) => onChange(prev => prev ? { ...prev, advanced: { ...prev.advanced, verify_checksums: e.target.checked } } : null)}
              className="w-4 h-4 rounded border-saec-300 text-saec-600 focus:ring-saec-500"
            />
            <span className="text-saec-700 dark:text-saec-300">Vérifier les checksums après transfert</span>
          </label>
          <label className="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={config.advanced.enable_telemetry}
              onChange={(e) => onChange(prev => prev ? { ...prev, advanced: { ...prev.advanced, enable_telemetry: e.target.checked } } : null)}
              className="w-4 h-4 rounded border-saec-300 text-saec-600 focus:ring-saec-500"
            />
            <span className="text-saec-700 dark:text-saec-300">Envoyer des données d'usage anonymes (amélioration du produit)</span>
          </label>
        </div>
      </section>

      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Key className="w-5 h-5" />
          API et endpoints
        </h3>
        <div className="space-y-4">
          <div>
            <label className="label">URL de base de l'API</label>
            <input
              type="url"
              value={config.api.base_url}
              onChange={(e) => onChange(prev => prev ? { ...prev, api: { ...prev.api, base_url: e.target.value } } : null)}
              className="input font-mono text-sm"
            />
          </div>
          <div>
            <label className="label">Timeout des requêtes (secondes)</label>
            <input
              type="number"
              min="5"
              max="300"
              value={config.api.timeout_seconds}
              onChange={(e) => onChange(prev => prev ? { ...prev, api: { ...prev.api, timeout_seconds: parseInt(e.target.value) || 30 } } : null)}
              className="input"
            />
          </div>
        </div>
      </section>

      <section className="border-t border-saec-200 dark:border-saec-800 pt-6">
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2 text-red-600">
          <Trash2 className="w-5 h-5" />
          Zone de danger
        </h3>
        <div className="space-y-4">
          <div className="p-4 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/20">
            <div className="flex items-center justify-between">
              <div>
                <p className="font-medium text-red-800 dark:text-red-200">Réinitialiser la base de données locale</p>
                <p className="text-sm text-red-600 dark:text-red-400">Supprime l'index des fichiers et force une réanalyse complète au prochain démarrage.</p>
              </div>
              <button className="btn-danger btn-sm">Réinitialiser</button>
            </div>
          </div>
          <div className="p-4 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/20">
            <div className="flex items-center justify-between">
              <div>
                <p className="font-medium text-red-800 dark:text-red-200">Effacer toutes les données</p>
                <p className="text-sm text-red-600 dark:text-red-400">Supprime la configuration, les identifiants et la base de données. Action irréversible.</p>
              </div>
              <button className="btn-danger btn-sm">Tout effacer</button>
            </div>
          </div>
        </div>
      </section>
    </div>
  )
}

function AccountSettings({ isAuthenticated, user, onLogout, showToken, setShowToken }: { isAuthenticated: boolean; user: { access_token: string; user_email: string } | null; onLogout: () => void; showToken: boolean; setShowToken: (s: boolean) => void }) {
  if (!isAuthenticated || !user) {
    return (
      <div className="space-y-6">
        <div className="text-center py-12">
          <Key className="w-16 h-16 text-saec-300 dark:text-saec-700 mx-auto mb-4" />
          <h3 className="text-lg font-semibold text-saec-900 dark:text-saec-100 mb-2">Non connecté</h3>
          <p className="text-saec-500 dark:text-saec-400">Connectez-vous à SAEC Cloud pour accéder aux paramètres du compte.</p>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Key className="w-5 h-5" />
          Informations du compte
        </h3>
        <div className="card p-4">
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 bg-saec-100 dark:bg-saec-800 rounded-full flex items-center justify-center">
              <Key className="w-8 h-8 text-saec-600 dark:text-saec-400" />
            </div>
            <div>
              <p className="font-medium text-saec-900 dark:text-saec-100">Connecté en tant que</p>
              <p className="text-saec-600 dark:text-saec-400">{user.user_email}</p>
            </div>
          </div>
        </div>
      </section>

      <section>
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2">
          <Eye className="w-5 h-5" />
          Jetons d'accès
        </h3>
        <div className="card p-4 space-y-4">
          <div>
            <label className="label">Access Token</label>
            <div className="flex gap-2">
              <input
                type={showToken ? 'text' : 'password'}
                value={user.access_token}
                readOnly
                className="input font-mono text-xs flex-1"
              />
              <button
                onClick={() => setShowToken(!showToken)}
                className="btn-secondary"
              >
                {showToken ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
              <button className="btn-secondary" onClick={() => navigator.clipboard.writeText(user.access_token)}>
                <Copy className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </section>

      <section className="border-t border-saec-200 dark:border-saec-800 pt-6">
        <h3 className="text-sm font-semibold text-saec-900 dark:text-saec-100 mb-4 flex items-center gap-2 text-red-600">
          <Trash2 className="w-5 h-5" />
          Déconnexion
        </h3>
        <button onClick={onLogout} className="btn-danger">
          <LogOut className="w-4 h-4" />
          Se déconnecter de SAEC Cloud
        </button>
        <p className="text-xs text-saec-500 dark:text-saec-400 mt-1">
          Cela supprimera vos identifiants locaux et arrêtera la synchronisation.
        </p>
      </section>
    </div>
  )
}