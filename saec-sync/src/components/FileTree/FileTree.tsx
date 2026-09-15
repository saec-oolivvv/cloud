import { useState, useMemo, useCallback } from 'react'
import { ChevronRight, ChevronDown, File, Folder, Cloud, Upload, Download, AlertTriangle, X, Check, MoreVertical, Eye, Download as DownloadIcon, Trash2, Copy, Search } from 'lucide-react'
import { useSyncStore } from '../../store/sync'
import { FileTreeNode } from '../../store/sync'
import { formatBytes, formatRelativeTime, getFileIcon } from '../../utils/format'
import { cn } from '../../utils/format'

interface FileTreeProps {
  mountId?: string
  onFileSelect?: (file: FileTreeNode) => void
}

export function FileTree({ mountId, onFileSelect }: FileTreeProps) {
  const { fileTree, status, selectedFiles, selectFile, clearSelection } = useSyncStore()
  const [expandedPaths, setExpandedPaths] = useState<Set<string>>(new Set(['']))
  const [searchQuery, setSearchQuery] = useState('')
  const [filterStatus, setFilterStatus] = useState<FileTreeNode['status'] | 'all'>('all')

  if (!fileTree) {
    return (
      <div className="card flex-1 flex flex-col">
        <div className="card-header">
          <h2 className="text-lg font-semibold text-saec-900 dark:text-saec-100 flex items-center gap-2">
            <Folder className="w-5 h-5" />
            Arborescence des fichiers
          </h2>
        </div>
        <div className="card-body flex-1 flex items-center justify-center">
          <div className="empty-state">
            <Folder className="empty-state-icon" />
            <h3 className="empty-state-title">Aucun fichier à afficher</h3>
            <p className="empty-state-description">
              La synchronisation n'a pas encore commencé ou aucun dossier n'est configuré.
            </p>
          </div>
        </div>
      </div>
    )
  }

  const filteredTree = useMemo(() => {
    if (!searchQuery && filterStatus === 'all') return fileTree
    return filterTreeNodes(fileTree, searchQuery.toLowerCase(), filterStatus)
  }, [fileTree, searchQuery, filterStatus])

function filterTreeNodes(node: FileTreeNode, query: string, statusFilter: FileTreeNode['status'] | 'all'): FileTreeNode | null {
  const matchesQuery = query === '' || node.name.toLowerCase().includes(query)
  const matchesStatus = statusFilter === 'all' || node.status === statusFilter
  
  if (!node.is_dir) {
    return matchesQuery && matchesStatus ? node : null
  }
  
  const filteredChildren = node.children
    ?.map(child => filterTreeNodes(child, query, statusFilter))
    .filter((child): child is FileTreeNode => child !== null) || []
  
  if (!matchesQuery && !matchesStatus && filteredChildren.length === 0) {
    return null
  }
  
  return { ...node, children: filteredChildren }
}

  const toggleExpand = useCallback((path: string) => {
    setExpandedPaths(prev => {
      const next = new Set(prev)
      if (next.has(path)) {
        next.delete(path)
      } else {
        next.add(path)
      }
      return next
    })
  }, [])

  const handleFileClick = useCallback((file: FileTreeNode, event: React.MouseEvent) => {
    if (event.shiftKey || event.metaKey || event.ctrlKey) {
      selectFile(file.path, !selectedFiles.has(file.path))
    } else {
      clearSelection()
      selectFile(file.path, true)
      onFileSelect?.(file)
    }
  }, [selectedFiles, selectFile, clearSelection, onFileSelect])

  return (
    <div className="card flex-1 flex flex-col">
      {/* Toolbar */}
      <div className="card-header flex flex-col sm:flex-row gap-4">
        <div className="flex items-center gap-2">
          <h2 className="text-lg font-semibold text-saec-900 dark:text-saec-100 flex items-center gap-2">
            <Folder className="w-5 h-5" />
            Arborescence des fichiers
            {status.files_pending > 0 && (
              <span className="badge-info">{status.files_pending}</span>
            )}
          </h2>
        </div>

        <div className="flex items-center gap-2 flex-1 sm:flex-none">
          <div className="relative flex-1 max-w-xs">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-saec-400" />
            <input
              type="text"
              placeholder="Rechercher des fichiers..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="input pl-10 pr-4"
            />
          </div>

          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value as typeof filterStatus)}
            className="input w-auto"
            aria-label="Filtrer par statut"
          >
            <option value="all">Tous les statuts</option>
            <option value="synced">Synchronisés</option>
            <option value="pending_upload">En attente d'upload</option>
            <option value="pending_download">En attente de download</option>
            <option value="conflict">Conflits</option>
            <option value="error">Erreurs</option>
          </select>

          {selectedFiles.size > 0 && (
            <button
              onClick={clearSelection}
              className="btn-ghost btn-sm"
            >
              <X className="w-4 h-4" />
              <span>Effacer ({selectedFiles.size})</span>
            </button>
          )}
        </div>
      </div>

      {/* File Tree */}
      {filteredTree && (
        <>
          <div className="card-body flex-1 overflow-auto p-0">
            <ul className="divide-y divide-saec-200 dark:divide-saec-800" role="tree" aria-label="Fichiers">
              {renderTreeNodes(filteredTree.children || [], '', 0)}
            </ul>
          </div>

          {/* Empty state */}
          {filteredTree.children?.length === 0 && (
        <div className="card-body flex-1 flex items-center justify-center">
          <div className="empty-state">
            <Folder className="empty-state-icon" />
            <h3 className="empty-state-title">Aucun fichier trouvé</h3>
            <p className="empty-state-description">
              {searchQuery ? `Aucun résultat pour « ${searchQuery} »` : 'Le dossier est vide ou aucun fichier ne correspond aux filtres.'}
            </p>
          </div>
        </div>
      )}
      </>
      )}
    </div>
  )
}

function renderTreeNodes(nodes: FileTreeNode[], parentPath: string, depth: number): React.ReactNode {
  if (nodes.length === 0) return null

  return (
    <>
      {nodes.map((node, index) => {
        const fullPath = parentPath ? `${parentPath}/${node.name}` : node.name
        const isExpanded = expandedPathsRef.current?.has(fullPath) ?? false
        const hasChildren = node.is_dir && node.children && node.children.length > 0
        const isSelected = selectedFilesRef.current?.has(fullPath) ?? false

        return (
          <FileTreeNodeComponent
            key={`${fullPath}-${index}`}
            node={node}
            fullPath={fullPath}
            depth={depth}
            isExpanded={isExpanded}
            hasChildren={hasChildren}
            isSelected={isSelected}
            onToggleExpand={() => toggleExpandRef.current?.(fullPath)}
            onClick={(e) => handleFileClickRef.current?.(node, e)}
          />
        )
      })}
    </>
  )
}

// We need to use refs for the callbacks to avoid re-renders
// This is a simplified version - in production, use a proper virtualized list
const expandedPathsRef = { current: new Set<string>() }
const selectedFilesRef = { current: new Set<string>() }
const toggleExpandRef = { current: (() => {}) as (path: string) => void }
const handleFileClickRef = { current: (() => {}) as (node: FileTreeNode, e: React.MouseEvent) => void }

interface FileTreeNodeComponentProps {
  node: FileTreeNode
  fullPath: string
  depth: number
  isExpanded: boolean
  hasChildren: boolean
  isSelected: boolean
  onToggleExpand: () => void
  onClick: (e: React.MouseEvent) => void
}

function FileTreeNodeComponent({ node, fullPath, depth, isExpanded, hasChildren, isSelected, onToggleExpand, onClick }: FileTreeNodeComponentProps) {
  const statusColors = {
    synced: 'text-green-500',
    pending_upload: 'text-blue-500',
    pending_download: 'text-green-500',
    conflict: 'text-orange-500',
    error: 'text-red-500',
    ignored: 'text-saec-400',
  }

  const statusLabels = {
    synced: 'Synchronisé',
    pending_upload: 'En attente d\'upload',
    pending_download: 'En attente de download',
    conflict: 'Conflit',
    error: 'Erreur',
    ignored: 'Ignoré',
  }

  const statusIcons = {
    synced: <Check className="w-3 h-3" />,
    pending_upload: <Upload className="w-3 h-3 animate-pulse" />,
    pending_download: <DownloadIcon className="w-3 h-3 animate-pulse" />,
    conflict: <AlertTriangle className="w-3 h-3" />,
    error: <X className="w-3 h-3" />,
    ignored: <X className="w-3 h-3" />,
  }

  const iconName = getFileIcon(node.name, node.is_dir)
  const IconComponent = getIconComponent(iconName)

  return (
    <li
      className={cn(
        'file-row',
        isSelected && 'file-row-selected',
        node.is_dir ? 'cursor-pointer' : 'cursor-default',
        depth > 0 && 'pl-8'
      )}
      role="treeitem"
      aria-expanded={hasChildren ? isExpanded : undefined}
      aria-selected={isSelected}
      aria-label={`${node.name}${node.is_dir ? ' (dossier)' : ''}, ${statusLabels[node.status]}`}
      onClick={onClick}
      onDoubleClick={hasChildren ? onToggleExpand : undefined}
    >
      <div className="flex items-center gap-2 flex-1 min-w-0">
        {hasChildren && (
          <button
            onClick={(e) => { e.stopPropagation(); onToggleExpand(); }}
            className="p-1 rounded hover:bg-saec-200 dark:hover:bg-saec-800 flex-shrink-0"
            aria-label={isExpanded ? `Fermer ${node.name}` : `Ouvrir ${node.name}`}
          >
            {isExpanded ? <ChevronDown className="w-4 h-4 text-saec-500" /> : <ChevronRight className="w-4 h-4 text-saec-500" />}
          </button>
        )}

        {!hasChildren && <span className="w-8 flex-shrink-0" />}

        <IconComponent className={cn(
          'w-5 h-5 flex-shrink-0',
          node.is_dir ? 'text-yellow-500' : 'text-saec-400'
        )} aria-hidden="true" />

        <div className="flex-1 min-w-0 truncate">
          <span className="font-medium text-saec-900 dark:text-saec-100">{node.name}</span>
          {!node.is_dir && node.size !== null && node.size !== undefined && (
            <span className="text-xs text-saec-500 dark:text-saec-400 ml-2 font-mono">
              {formatBytes(node.size)}
            </span>
          )}
        </div>

        <span className={cn('flex-shrink-0', statusColors[node.status])}>
          {statusIcons[node.status]}
        </span>

        {!node.is_dir && (
          <div className="flex items-center gap-1">
            <button className="p-1 rounded hover:bg-saec-200 dark:hover:bg-saec-800 text-saec-400 hover:text-saec-600" aria-label="Plus d'options">
              <MoreVertical className="w-4 h-4" />
            </button>
          </div>
        )}
      </div>

      {hasChildren && isExpanded && (
        <ul className="divide-y divide-saec-200 dark:divide-saec-800" role="group">
          {renderTreeNodes(node.children || [], fullPath, depth + 1)}
        </ul>
      )}
    </li>
  )
}

function getIconComponent(name: string): React.ComponentType<{ className?: string }> {
  const icons: Record<string, React.ComponentType<{ className?: string }>> = {
    folder: Folder,
    file: File,
    image: File,
    'file-text': File,
    'file-spreadsheet': File,
    'file-presentation': File,
    'file-archive': File,
    'file-code': File,
    'file-json': File,
    'file-audio': File,
    'file-video': File,
    'file-font': File,
  }
  return icons[name] || File
}