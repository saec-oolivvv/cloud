export function formatBytes(bytes: number, decimals = 2): string {
  if (bytes === 0) return '0 B'

  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['B', 'Ko', 'Mo', 'Go', 'To', 'Po']

  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
}

export function formatRelativeTime(isoString: string): string {
  const date = new Date(isoString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffSecs = Math.floor(diffMs / 1000)
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffSecs < 30) return 'À l\'instant'
  if (diffMins < 60) return `Il y a ${diffMins} min`
  if (diffHours < 24) return `Il y a ${diffHours} h`
  if (diffDays < 7) return `Il y a ${diffDays} j`
  if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)} sem`

  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
  })
}

export function formatDuration(ms: number): string {
  if (ms < 1000) return `${ms} ms`
  if (ms < 60000) return `${(ms / 1000).toFixed(1)} s`
  if (ms < 3600000) return `${Math.floor(ms / 60000)} min ${Math.floor((ms % 60000) / 1000)} s`
  return `${Math.floor(ms / 3600000)} h ${Math.floor((ms % 3600000) / 60000)} min`
}

export function formatDate(isoString: string, options: Intl.DateTimeFormatOptions = {}): string {
  const date = new Date(isoString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    ...options,
  })
}

export function formatDateTime(isoString: string): string {
  const date = new Date(isoString)
  return date.toLocaleString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function truncate(str: string, length: number): string {
  if (str.length <= length) return str
  return str.slice(0, length - 1) + '…'
}

export function getFileExtension(filename: string): string {
  const lastDot = filename.lastIndexOf('.')
  if (lastDot === -1) return ''
  return filename.slice(lastDot + 1).toLowerCase()
}

export function getFileIcon(filename: string, isDir: boolean): string {
  if (isDir) return 'folder'
  
  const ext = getFileExtension(filename)
  
  const iconMap: Record<string, string> = {
    // Images
    jpg: 'image', jpeg: 'image', png: 'image', gif: 'image', webp: 'image', svg: 'image', ico: 'image',
    // Documents
    pdf: 'file-text', doc: 'file-text', docx: 'file-text', txt: 'file-text', md: 'file-text', rtf: 'file-text',
    // Spreadsheets
    xls: 'file-spreadsheet', xlsx: 'file-spreadsheet', csv: 'file-spreadsheet',
    // Presentations
    ppt: 'file-presentation', pptx: 'file-presentation',
    // Archives
    zip: 'file-archive', rar: 'file-archive', '7z': 'file-archive', tar: 'file-archive', gz: 'file-archive',
    // Code
    js: 'file-code', ts: 'file-code', jsx: 'file-code', tsx: 'file-code', py: 'file-code', rs: 'file-code', go: 'file-code', java: 'file-code',
    json: 'file-json', xml: 'file-code', yaml: 'file-code', yml: 'file-code',
    // Audio/Video
    mp3: 'file-audio', wav: 'file-audio', ogg: 'file-audio', flac: 'file-audio',
    mp4: 'file-video', mov: 'file-video', avi: 'file-video', mkv: 'file-video', webm: 'file-video',
    // Fonts
    ttf: 'file-font', otf: 'file-font', woff: 'file-font', woff2: 'file-font',
  }
  
  return iconMap[ext] || 'file'
}

export function cn(...classes: (string | undefined | null | false)[]): string {
  return classes.filter(Boolean).join(' ')
}