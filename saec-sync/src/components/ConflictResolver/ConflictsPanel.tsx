import { AlertTriangle, ChevronDown, ChevronUp, Upload, Download, Copy, X, Check, AlertCircle, Clock, File, Folder, MoreVertical } from 'lucide-react'
import { useState } from 'react'
import { useSyncStore } from '../../store/sync'
import { ConflictInfo } from '../../store/sync'
import { formatBytes, formatRelativeTime, cn } from '../../utils/format'

export function ConflictsPanel() {
  const { conflicts, resolveConflict, removeConflict } = useSyncStore()
  const [expandedIds, setExpandedIds] = useState<Set<string>>(new Set())

  const pendingConflicts = conflicts.filter(c => c.status === 'pending')

  if (pendingConflicts.length === 0) {
    return (
      <div className="card">
        <div className="card-header">
          <h2 className="text-lg font-semibold text-saec-900 dark:text-saec-100 flex items-center gap-2">
            <AlertTriangle className="w-5 h-5 text-saec-400" />
            Conflits
            <span className="badge-neutral">0</span>
          </h2>
        </div>
        <div className="card-body">
          <div className="empty-state">
            <AlertTriangle className="empty-state-icon text-green-500" />
            <h3 className="empty-state-title">Aucun conflit</h3>
            <p className="empty-state-description">
              Tous les fichiers sont synchronisés sans conflit.
            </p>
          </div>
        </div>
      </div>
    )
  }

  const resolvedConflicts = conflicts.filter(c => c.status === 'resolved')
  const resolvingConflicts = conflicts.filter(c => c.status === 'resolving')

  return (
    <div className="card">
      <div className="card-header">
        <h2 className="text-lg font-semibold text-saec-900 dark:text-saec-100 flex items-center gap-2">
          <AlertTriangle className="w-5 h-5 text-orange-500" />
          Conflits détectés
          <span className="badge-warning">{pendingConflicts.length}</span>
        </h2>
      </div>

      <div className="card-body">
        <div className="space-y-4">
          {pendingConflicts.map((conflict) => (
            <ConflictCard
              key={conflict.id}
              conflict={conflict}
              isExpanded={expandedIds.has(conflict.id)}
              onToggleExpand={() => {
                setExpandedIds(prev => {
                  const next = new Set(prev)
                  if (next.has(conflict.id)) next.delete(conflict.id)
                  else next.add(conflict.id)
                  return next
                })
              }}
              onResolve={(resolution) => resolveConflict(conflict.id, resolution)}
              onDismiss={() => removeConflict(conflict.id)}
            />
          ))}

          {(resolvingConflicts.length > 0 || resolvedConflicts.length > 0) && (
            <details className="group">
              <summary className="flex items-center gap-2 text-sm font-medium text-saec-500 dark:text-saec-400 cursor-pointer p-2 rounded-lg hover:bg-saec-100 dark:hover:bg-saec-800">
                <ChevronDown className="w-4 h-4 transition-transform group-open:rotate-180" />
                <span>Historique ({resolvingConflicts.length + resolvedConflicts.length})</span>
              </summary>
              <div className="mt-2 space-y-2 ml-6 border-l-2 border-saec-200 dark:border-saec-800 pl-4">
                {[...resolvingConflicts, ...resolvedConflicts].map((conflict) => (
                  <ResolvedConflictRow key={conflict.id} conflict={conflict} />
                ))}
              </div>
            </details>
          )}
        </div>
      </div>
    </div>
  )
}

interface ConflictCardProps {
  conflict: ConflictInfo
  isExpanded: boolean
  onToggleExpand: () => void
  onResolve: (resolution: 'keep_local' | 'keep_remote' | 'keep_both') => void
  onDismiss: () => void
}

function ConflictCard({ conflict, isExpanded, onToggleExpand, onResolve, onDismiss }: ConflictCardProps) {
  const isLocalNewer = new Date(conflict.local_modified) > new Date(conflict.remote_modified)
  const sizeDiff = conflict.local_size - conflict.remote_size

  return (
    <div className="border border-orange-200 dark:border-orange-800 rounded-xl overflow-hidden bg-orange-50 dark:bg-orange-900/20">
      <button
        onClick={onToggleExpand}
        className="w-full p-4 flex items-center gap-3 hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors text-left"
      >
        <div className="w-10 h-10 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center flex-shrink-0">
          <AlertTriangle className="w-5 h-5 text-orange-600 dark:text-orange-400" />
        </div>

        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <span className="font-medium text-saec-900 dark:text-saec-100 truncate">{conflict.path}</span>
            <span className="badge-warning">Conflit</span>
          </div>
          <p className="text-sm text-saec-500 dark:text-saec-400 mt-1">
            Modifié des deux côtés • {formatRelativeTime(conflict.local_modified)} (local) vs {formatRelativeTime(conflict.remote_modified)} (distant)
          </p>
        </div>

        <div className="flex items-center gap-2">
          <span className="text-xs font-mono text-saec-500 dark:text-saec-400">
            {sizeDiff > 0 ? '+' : ''}{formatBytes(Math.abs(sizeDiff))}
          </span>
          <ChevronDown className={cn('w-4 h-4 text-saec-400 transition-transform', isExpanded && 'rotate-180')} />
        </div>
      </button>

      {isExpanded && (
        <div className="border-t border-orange-200 dark:border-orange-800 p-4 space-y-4 bg-white dark:bg-saec-900">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <ConflictSide
              label="Version locale"
              side="local"
              conflict={conflict}
              isNewer={isLocalNewer}
            />
            <ConflictSide
              label="Version distante"
              side="remote"
              conflict={conflict}
              isNewer={!isLocalNewer}
            />
          </div>

          <div className="flex flex-wrap gap-3 pt-2 border-t border-saec-200 dark:border-saec-800">
            <button
              onClick={() => onResolve('keep_local')}
              className="btn-primary flex-1 sm:flex-none"
            >
              <Upload className="w-4 h-4" />
              Garder la version locale
            </button>
            <button
              onClick={() => onResolve('keep_remote')}
              className="btn-secondary flex-1 sm:flex-none"
            >
              <Download className="w-4 h-4" />
              Garder la version distante
            </button>
            <button
              onClick={() => onResolve('keep_both')}
              className="btn-ghost flex-1 sm:flex-none"
            >
              <Copy className="w-4 h-4" />
              Garder les deux
            </button>
            <button
              onClick={onDismiss}
              className="btn-ghost btn-sm text-saec-500 hover:text-red-600"
              aria-label="Ignorer ce conflit"
            >
              <X className="w-4 h-4" />
              Ignorer
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

interface ConflictSideProps {
  label: string
  side: 'local' | 'remote'
  conflict: ConflictInfo
  isNewer: boolean
}

function ConflictSide({ label, side, conflict, isNewer }: ConflictSideProps) {
  const modified = side === 'local' ? conflict.local_modified : conflict.remote_modified
  const size = side === 'local' ? conflict.local_size : conflict.remote_size
  const checksum = side === 'local' ? conflict.local_checksum : conflict.remote_checksum

  return (
    <div className="p-4 bg-saec-50 dark:bg-saec-800/50 rounded-lg border border-saec-200 dark:border-saec-700">
      <div className="flex items-center gap-2 mb-3">
        <h4 className="font-medium text-saec-900 dark:text-saec-100">{label}</h4>
        {isNewer && <span className="badge-success text-xs">Plus récent</span>}
      </div>

      <dl className="space-y-2 text-sm">
        <div className="flex justify-between">
          <dt className="text-saec-500 dark:text-saec-400">Taille</dt>
          <dd className="font-mono font-medium text-saec-900 dark:text-saec-100">{formatBytes(size)}</dd>
        </div>
        <div className="flex justify-between">
          <dt className="text-saec-500 dark:text-saec-400">Modifié le</dt>
          <dd className="font-medium text-saec-900 dark:text-saec-100">{formatRelativeTime(modified)}</dd>
        </div>
        <div className="flex justify-between">
          <dt className="text-saec-500 dark:text-saec-400">Checksum</dt>
          <dd className="font-mono text-xs text-saec-500 dark:text-saec-400 truncate max-w-[150px]">{checksum.slice(0, 16)}…</dd>
        </div>
      </dl>
    </div>
  )
}

function ResolvedConflictRow({ conflict }: { conflict: ConflictInfo }) {
  const statusColors = {
    pending: 'text-yellow-600 dark:text-yellow-400',
    resolving: 'text-blue-600 dark:text-blue-400',
    resolved: 'text-green-600 dark:text-green-400',
  }

  const statusIcons = {
    pending: <Clock className="w-4 h-4 animate-pulse" />,
    resolving: <Clock className="w-4 h-4 animate-pulse" />,
    resolved: <Check className="w-4 h-4" />,
  }

  return (
    <div className="flex items-center gap-3 py-2">
      <div className={cn('flex-shrink-0', statusColors[conflict.status])}>
        {statusIcons[conflict.status]}
      </div>
      <div className="flex-1 min-w-0">
        <p className="font-medium text-sm text-saec-900 dark:text-saec-100 truncate">{conflict.path}</p>
        <p className="text-xs text-saec-500 dark:text-saec-400">
          Résolu : {conflict.status === 'resolved' ? 'Terminé' : 'En cours...'}
        </p>
      </div>
    </div>
  )
}