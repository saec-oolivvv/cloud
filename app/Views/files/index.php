<?php
/** @var array $user */
/** @var array $files */
/** @var array $folders */
/** @var array $breadcrumb */
/** @var int $used */
/** @var int $quota */
/** @var int|null $parentId */

$user = $user ?? [];
$files = $files ?? [];
$folders = $folders ?? [];
$breadcrumb = $breadcrumb ?? [['name' => 'Fichiers', 'url' => '/files']];
$parentId = $parentId ?? null;
$used = $used ?? 0;
$quota = $quota ?? 10737418240;
$csrf = \Saec\Core\Session::csrfToken();

function getFileIconSvg(string $mime, string $name = ''): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (in_array($ext, ['pdf'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#E53935" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#E53935" stroke-width="1.5"/><text x="24" y="30" text-anchor="middle" fill="#E53935" font-size="11" font-weight="700" font-family="Inter,sans-serif">PDF</text></svg>';
    }
    if (in_array($ext, ['doc', 'docx'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#1565C0" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#1565C0" stroke-width="1.5"/><text x="24" y="30" text-anchor="middle" fill="#1565C0" font-size="10" font-weight="700" font-family="Inter,sans-serif">DOC</text></svg>';
    }
    if (in_array($ext, ['xls', 'xlsx', 'csv'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#2E7D32" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#2E7D32" stroke-width="1.5"/><text x="24" y="30" text-anchor="middle" fill="#2E7D32" font-size="10" font-weight="700" font-family="Inter,sans-serif">XLS</text></svg>';
    }
    if (in_array($ext, ['ppt', 'pptx'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#E65100" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#E65100" stroke-width="1.5"/><text x="24" y="30" text-anchor="middle" fill="#E65100" font-size="10" font-weight="700" font-family="Inter,sans-serif">PPT</text></svg>';
    }
    if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#6A1B9A" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#6A1B9A" stroke-width="1.5"/><text x="24" y="30" text-anchor="middle" fill="#6A1B9A" font-size="10" font-weight="700" font-family="Inter,sans-serif">ZIP</text></svg>';
    }
    if (in_array($ext, ['mp4', 'avi', 'mkv', 'mov', 'webm'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#AD1457" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#AD1457" stroke-width="1.5"/><path d="M20 16l12 8-12 8V16z" fill="#AD1457"/></svg>';
    }
    if (in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'aac'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#00838F" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#00838F" stroke-width="1.5"/><path d="M28 16v16M22 20v10M16 18v14M34 14v18" stroke="#00838F" stroke-width="2" stroke-linecap="round"/></svg>';
    }
    if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'ico'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#1565C0" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#1565C0" stroke-width="1.5"/><circle cx="18" cy="18" r="4" fill="#1565C0" opacity="0.5"/><path d="M6 34l10-10 8 8 6-6 12 10" stroke="#1565C0" stroke-width="1.5" fill="none"/></svg>';
    }
    if (in_array($ext, ['js', 'ts', 'jsx', 'tsx', 'php', 'py', 'rb', 'go', 'rs', 'java', 'c', 'cpp', 'h', 'css', 'scss', 'html', 'xml', 'json', 'yaml', 'yml', 'sh', 'bash'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#F57C00" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#F57C00" stroke-width="1.5"/><text x="24" y="27" text-anchor="middle" fill="#F57C00" font-size="8" font-weight="700" font-family="JetBrains Mono,monospace">&lt;/&gt;</text></svg>';
    }
    if (in_array($ext, ['txt', 'md', 'log', 'ini', 'cfg', 'conf'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#546E7A" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#546E7A" stroke-width="1.5"/><line x1="14" y1="16" x2="34" y2="16" stroke="#546E7A" stroke-width="1.5" opacity="0.5"/><line x1="14" y1="22" x2="30" y2="22" stroke="#546E7A" stroke-width="1.5" opacity="0.5"/><line x1="14" y1="28" x2="28" y2="28" stroke="#546E7A" stroke-width="1.5" opacity="0.5"/></svg>';
    }
    if (in_array($ext, ['exe', 'msi', 'dmg', 'deb', 'rpm', 'app'])) {
        return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#455A64" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#455A64" stroke-width="1.5"/><path d="M24 14v8M20 18h8" stroke="#455A64" stroke-width="2" stroke-linecap="round"/></svg>';
    }
    return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2" width="36" height="44" rx="4" fill="#78909C" opacity="0.15"/><rect x="6" y="2" width="36" height="44" rx="4" stroke="#78909C" stroke-width="1.5"/><line x1="14" y1="16" x2="34" y2="16" stroke="#78909C" stroke-width="1.5" opacity="0.5"/><line x1="14" y1="22" x2="34" y2="22" stroke="#78909C" stroke-width="1.5" opacity="0.5"/><line x1="14" y1="28" x2="26" y2="28" stroke="#78909C" stroke-width="1.5" opacity="0.5"/></svg>';
}

function getFileColor(string $mime, string $name = ''): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext === 'pdf') return '#E53935';
    if (in_array($ext, ['doc', 'docx'])) return '#1565C0';
    if (in_array($ext, ['xls', 'xlsx', 'csv'])) return '#2E7D32';
    if (in_array($ext, ['ppt', 'pptx'])) return '#E65100';
    if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) return '#6A1B9A';
    if (in_array($ext, ['mp4', 'avi', 'mkv', 'mov', 'webm'])) return '#AD1457';
    if (in_array($ext, ['mp3', 'wav', 'ogg', 'flac'])) return '#00838F';
    if (str_starts_with($mime, 'image/')) return '#1565C0';
    if (in_array($ext, ['js', 'ts', 'php', 'py', 'html', 'css', 'json'])) return '#F57C00';
    return '#546E7A';
}

function formatSize(int $bytes): string {
    $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 1) . ' ' . $units[$pow];
}
?>

<div class="file-browser">
    <!-- Toolbar -->
    <div class="fb-toolbar">
        <div class="fb-toolbar-left">
            <div class="fb-breadcrumb">
                <?php foreach ($breadcrumb as $i => $bc): ?>
                    <?php if ($i > 0): ?><span class="fb-bc-sep">›</span><?php endif; ?>
                    <a href="<?= $bc['url'] ?>" class="fb-bc-item"><?= htmlspecialchars($bc['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="fb-toolbar-right">
            <div class="fb-view-toggle" id="viewToggle">
                <button class="fb-view-btn active" data-view="grid" title="Grille">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
                </button>
                <button class="fb-view-btn" data-view="list" title="Liste">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="2" width="14" height="3" rx="1"/><rect x="1" y="7" width="14" height="3" rx="1"/><rect x="1" y="12" width="14" height="3" rx="1"/></svg>
                </button>
            </div>
            <button class="fb-btn fb-btn-primary" id="uploadBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload
            </button>
            <button class="fb-btn fb-btn-outline" id="newFolderBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                Nouveau dossier
            </button>
        </div>
    </div>

    <!-- Storage Bar -->
    <div class="fb-storage">
        <div class="fb-storage-info">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            <span><?= formatSize($used) ?> / <?= formatSize($quota) ?></span>
        </div>
        <?php $pct = $quota > 0 ? min(($used / $quota) * 100, 100) : 0; ?>
        <div class="fb-storage-bar">
            <div class="fb-storage-fill <?= $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : '') ?>" style="width: <?= $pct ?>%"></div>
        </div>
    </div>

    <!-- Batch Actions Bar (hidden by default) -->
    <div class="fb-batch-bar" id="batchBar" style="display:none;">
        <span id="batchCount">0 sélectionnés</span>
        <div class="fb-batch-actions">
            <button class="fb-batch-btn" onclick="batchDownload()" title="Télécharger">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </button>
            <button class="fb-batch-btn" onclick="batchMove()" title="Déplacer">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
            </button>
            <button class="fb-batch-btn fb-batch-danger" onclick="batchDelete()" title="Supprimer">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            </button>
            <button class="fb-batch-btn" onclick="clearSelection()" title="Annuler">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="fb-content">
        <!-- Folders -->
        <?php if (!empty($folders)): ?>
        <div class="fb-section" id="foldersSection">
            <div class="fb-section-header">
                <span class="fb-section-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                    Dossiers
                </span>
                <span class="fb-section-count"><?= count($folders) ?></span>
            </div>
            <div class="fb-grid" id="foldersGrid">
                <?php foreach ($folders as $folder): ?>
                <div class="fb-item fb-folder" data-id="<?= $folder['id'] ?>" data-type="folder" data-name="<?= htmlspecialchars($folder['name']) ?>" data-path="<?= htmlspecialchars($folder['path'] ?? '/') ?>" draggable="true">
                    <div class="fb-item-icon">
                        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 12C6 9.79 7.79 8 10 8H18L22 14H38C40.21 14 42 15.79 42 18V36C42 38.21 40.21 40 38 40H10C7.79 40 6 38.21 6 36V12Z" fill="#F59E0B" opacity="0.2"/>
                            <path d="M6 12C6 9.79 7.79 8 10 8H18L22 14H38C40.21 14 42 15.79 42 18V36C42 38.21 40.21 40 38 40H10C7.79 40 6 38.21 6 36V12Z" stroke="#F59E0B" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <a href="/files?folder=<?= $folder['id'] ?>" class="fb-item-link fb-folder-link">
                        <div class="fb-item-info">
                            <div class="fb-item-name" title="<?= htmlspecialchars($folder['name']) ?>"><?= htmlspecialchars($folder['name']) ?></div>
                            <div class="fb-item-meta"><?= ($folder['file_count'] ?? 0) ?> fichiers</div>
                        </div>
                    </a>
                    <div class="fb-item-actions">
                        <button class="fb-action-btn" onclick="renameFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['name'])) ?>')" title="Renommer">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="fb-action-btn fb-action-danger" onclick="deleteFolder(<?= $folder['id'] ?>)" title="Supprimer">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Files -->
        <?php if (!empty($files)): ?>
        <div class="fb-section" id="filesSection">
            <div class="fb-section-header">
                <span class="fb-section-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Fichiers
                </span>
                <span class="fb-section-count"><?= count($files) ?></span>
            </div>
            <div class="fb-grid" id="filesGrid">
                <?php foreach ($files as $file): ?>
                <div class="fb-item fb-file" data-id="<?= $file['id'] ?>" data-type="file" data-mime="<?= htmlspecialchars($file['mime_type'] ?? '') ?>" data-name="<?= htmlspecialchars($file['original_name']) ?>">
                    <div class="fb-item-checkbox">
                        <input type="checkbox" class="fb-select" data-id="<?= $file['id'] ?>" data-type="file">
                    </div>
                    <div class="fb-item-icon" style="color: <?= getFileColor($file['mime_type'] ?? '', $file['original_name']) ?>">
                        <?= getFileIconSvg($file['mime_type'] ?? '', $file['original_name']) ?>
                    </div>
                    <div class="fb-item-info">
                        <div class="fb-item-name" title="<?= htmlspecialchars($file['original_name']) ?>"><?= htmlspecialchars($file['original_name']) ?></div>
                        <div class="fb-item-meta"><?= formatSize((int)$file['size']) ?> · <?= date('d/m/Y H:i', strtotime($file['created_at'])) ?></div>
                    </div>
                    <div class="fb-item-actions">
                        <button class="fb-action-btn" onclick="viewFile(<?= $file['id'] ?>, '<?= htmlspecialchars($file['mime_type'] ?? '') ?>', '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" title="Ouvrir">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button class="fb-action-btn" onclick="downloadFile(<?= $file['id'] ?>)" title="Télécharger">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                        <button class="fb-action-btn" onclick="shareFile(<?= $file['id'] ?>)" title="Partager">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        </button>
                        <button class="fb-action-btn fb-action-danger" onclick="deleteFile(<?= $file['id'] ?>)" title="Supprimer">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if (empty($files) && empty($folders)): ?>
        <div class="fb-empty">
            <div class="fb-empty-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity="0.3"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
            </div>
            <h3>Aucun fichier</h3>
            <p>Glissez des fichiers ici ou cliquez sur Upload</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Modal -->
<div class="fb-modal" id="uploadModal" style="display:none;">
    <div class="fb-modal-overlay" onclick="closeUploadModal()"></div>
    <div class="fb-modal-content">
        <div class="fb-modal-header">
            <h3>Upload de fichiers</h3>
            <button class="fb-modal-close" onclick="closeUploadModal()">×</button>
        </div>
        <div class="fb-modal-body">
            <div class="fb-upload-dropzone" id="uploadDropzone">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.4"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <p>Glissez vos fichiers ou dossiers ici</p>
                <span>ou</span>
                <button class="fb-btn fb-btn-primary" onclick="document.getElementById('uploadFileInput').click()">Parcourir fichiers</button>
                <input type="file" id="uploadFileInput" multiple style="display:none;">
                <button class="fb-btn fb-btn-outline" id="pickFolderBtn" style="display:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                    Choisir un dossier
                </button>
            </div>
            <div class="fb-upload-dest">
                <label class="fb-label">Destination :</label>
                <select id="uploadFolderSelect" class="fb-select-input">
                    <option value="">📁 Racine</option>
                    <?php foreach (($allFolders ?? $folders) as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= $parentId == $f['id'] ? 'selected' : '' ?>>📁 <?= htmlspecialchars($f['path'] ?? $f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fb-upload-list" id="uploadList"></div>
        </div>
        <div class="fb-modal-footer">
            <button class="fb-btn fb-btn-ghost" onclick="closeUploadModal()">Annuler</button>
            <button class="fb-btn fb-btn-primary" id="uploadStartBtn" disabled>Uploader</button>
        </div>
    </div>
</div>

<!-- New Folder Modal -->
<div class="fb-modal" id="folderModal" style="display:none;">
    <div class="fb-modal-overlay" onclick="closeFolderModal()"></div>
    <div class="fb-modal-content fb-modal-sm">
        <div class="fb-modal-header">
            <h3>Nouveau dossier</h3>
            <button class="fb-modal-close" onclick="closeFolderModal()">×</button>
        </div>
        <div class="fb-modal-body">
            <input type="text" id="folderNameInput" class="fb-input" placeholder="Nom du dossier" autofocus>
        </div>
        <div class="fb-modal-footer">
            <button class="fb-btn fb-btn-ghost" onclick="closeFolderModal()">Annuler</button>
            <button class="fb-btn fb-btn-primary" onclick="createFolder()">Créer</button>
        </div>
    </div>
</div>

<!-- Viewer Modal -->
<div class="fb-modal fb-viewer" id="viewerModal" style="display:none;">
    <div class="fb-modal-overlay" onclick="closeViewer()"></div>
    <div class="fb-viewer-content">
        <div class="fb-viewer-header">
            <div class="fb-viewer-title" id="viewerTitle">fichier.txt</div>
            <div class="fb-viewer-actions">
                <button class="fb-btn fb-btn-ghost fb-btn-sm" id="viewerEditBtn" onclick="toggleEditMode()" style="display:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Éditer
                </button>
                <button class="fb-btn fb-btn-ghost fb-btn-sm" id="viewerSaveBtn" onclick="saveFileContent()" style="display:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Sauvegarder
                </button>
                <button class="fb-btn fb-btn-ghost fb-btn-sm" onclick="downloadFile(currentViewerFileId)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </button>
                <button class="fb-viewer-close" onclick="closeViewer()">×</button>
            </div>
        </div>
        <div class="fb-viewer-body" id="viewerBody">
            <div class="fb-viewer-loading">Chargement...</div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="fb-modal" id="shareModal" style="display:none;">
    <div class="fb-modal-overlay" onclick="closeShareModal()"></div>
    <div class="fb-modal-content fb-modal-sm">
        <div class="fb-modal-header">
            <h3>Partager le fichier</h3>
            <button class="fb-modal-close" onclick="closeShareModal()">×</button>
        </div>
        <div class="fb-modal-body">
            <div class="fb-form-group">
                <label class="fb-label">Expiration</label>
                <input type="datetime-local" id="shareExpiry" class="fb-input">
            </div>
            <div class="fb-form-group">
                <label class="fb-label">Mot de passe (optionnel)</label>
                <input type="password" id="sharePassword" class="fb-input" placeholder="••••••••">
            </div>
            <div id="shareResult" style="display:none;">
                <label class="fb-label">Lien de partage</label>
                <div class="fb-share-link-row">
                    <input type="text" id="shareLink" class="fb-input" readonly>
                    <button class="fb-btn fb-btn-outline" onclick="copyShareLink()">Copier</button>
                </div>
            </div>
        </div>
        <div class="fb-modal-footer">
            <button class="fb-btn fb-btn-ghost" onclick="closeShareModal()">Fermer</button>
            <button class="fb-btn fb-btn-primary" id="shareCreateBtn" onclick="createShare()">Créer le lien</button>
        </div>
    </div>
</div>

<!-- Move Modal -->
<div class="fb-modal" id="moveModal" style="display:none;">
    <div class="fb-modal-overlay" onclick="closeMoveModal()"></div>
    <div class="fb-modal-content fb-modal-sm">
        <div class="fb-modal-header">
            <h3>Déplacer vers...</h3>
            <button class="fb-modal-close" onclick="closeMoveModal()">×</button>
        </div>
        <div class="fb-modal-body">
            <div class="fb-move-list">
                <div class="fb-move-item" onclick="moveFilesTo(null)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Racine
                </div>
                <?php foreach ($folders as $f): ?>
                <div class="fb-move-item" onclick="moveFilesTo(<?= $f['id'] ?>)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                    <?= htmlspecialchars($f['name']) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu (Right-click) -->
<div class="fb-context-menu" id="contextMenu" style="display:none; position:fixed; z-index:10000;">
    <div class="fb-context-menu-inner">
        <div class="fb-context-section">
            <button class="fb-context-item" onclick="contextRename()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                <span>Renommer</span>
                <kbd>F2</kbd>
            </button>
            <button class="fb-context-item" onclick="contextDownload()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Télécharger</span>
            </button>
            <button class="fb-context-item" onclick="contextShare()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                <span>Partager</span>
            </button>
        </div>
        <div class="fb-context-divider"></div>
        <div class="fb-context-section">
            <button class="fb-context-item" onclick="contextCopy()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                <span>Copier</span>
                <kbd>Ctrl+C</kbd>
            </button>
            <button class="fb-context-item" onclick="contextCut()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <span>Couper</span>
                <kbd>Ctrl+X</kbd>
            </button>
            <button class="fb-context-item" onclick="contextPaste()" id="contextPasteBtn" disabled>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                <span>Coller</span>
                <kbd>Ctrl+V</kbd>
            </button>
        </div>
        <div class="fb-context-divider"></div>
        <div class="fb-context-section">
            <button class="fb-context-item" onclick="contextMove()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                <span>Déplacer vers...</span>
            </button>
            <button class="fb-context-item fb-context-danger" onclick="contextDelete()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                <span>Supprimer</span>
                <kbd>Suppr</kbd>
            </button>
        </div>
        <div class="fb-context-divider"></div>
        <div class="fb-context-section">
            <button class="fb-context-item" onclick="contextInfo()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Propriétés</span>
                <kbd>Ctrl+I</kbd>
            </button>
        </div>
    </div>
</div>

<style>
/* ══════════════════════════════════════════════════════════════
   FILE BROWSER — Desktop-style
   ══════════════════════════════════════════════════════════════ */
.file-browser {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 80px);
    overflow: hidden;
}

/* Toolbar */
.fb-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-card);
    flex-shrink: 0;
}
.fb-toolbar-left { display: flex; align-items: center; gap: 12px; }
.fb-toolbar-right { display: flex; align-items: center; gap: 8px; }
.fb-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 13px; }
.fb-bc-item { color: var(--text-secondary); text-decoration: none; padding: 4px 8px; border-radius: 6px; transition: all 0.15s; }
.fb-bc-item:hover { color: var(--accent); background: rgba(0,255,136,0.06); }
.fb-bc-item:last-child { color: var(--text-primary); font-weight: 600; }
.fb-bc-sep { color: var(--text-muted); font-size: 16px; }
.fb-view-toggle { display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
.fb-view-btn { background: none; border: none; color: var(--text-muted); padding: 6px 10px; cursor: pointer; transition: all 0.15s; }
.fb-view-btn.active { background: var(--accent); color: #000; }
.fb-view-btn:hover:not(.active) { color: var(--text-primary); background: rgba(255,255,255,0.05); }
.fb-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; border: none; font-family: inherit; }
.fb-btn-primary { background: var(--accent); color: #000; }
.fb-btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); }
.fb-btn-outline { background: none; border: 1px solid var(--border); color: var(--text-secondary); }
.fb-btn-outline:hover { border-color: var(--accent); color: var(--accent); }
.fb-btn-ghost { background: none; color: var(--text-secondary); }
.fb-btn-ghost:hover { color: var(--text-primary); background: rgba(255,255,255,0.05); }
.fb-btn-sm { padding: 5px 10px; font-size: 12px; }

/* Storage */
.fb-storage { display: flex; align-items: center; gap: 12px; padding: 10px 24px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
.fb-storage-info { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); white-space: nowrap; }
.fb-storage-bar { flex: 1; height: 4px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden; max-width: 200px; }
.fb-storage-fill { height: 100%; background: var(--accent); border-radius: 4px; transition: width 0.3s; }
.fb-storage-fill.warning { background: #F59E0B; }
.fb-storage-fill.danger { background: #EF4444; }

/* Batch Bar */
.fb-batch-bar { display: flex; align-items: center; justify-content: space-between; padding: 8px 24px; background: rgba(0,255,136,0.05); border-bottom: 1px solid rgba(0,255,136,0.15); flex-shrink: 0; font-size: 13px; color: var(--accent); }
.fb-batch-actions { display: flex; gap: 4px; }
.fb-batch-btn { background: none; border: 1px solid rgba(0,255,136,0.2); color: var(--accent); padding: 5px 10px; border-radius: 6px; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; }
.fb-batch-btn:hover { background: rgba(0,255,136,0.1); }
.fb-batch-danger { color: #EF4444; border-color: rgba(239,68,68,0.3); }
.fb-batch-danger:hover { background: rgba(239,68,68,0.1); }

/* Content */
.fb-content { flex: 1; overflow-y: auto; padding: 20px 24px; }
.fb-section { margin-bottom: 24px; }
.fb-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.fb-section-title { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
.fb-section-count { font-size: 11px; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 10px; }

/* Grid View */
.fb-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; }
.fb-grid[data-view="list"] { grid-template-columns: 1fr; }

/* File/Folder Item */
.fb-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid transparent;
    transition: all 0.15s;
    cursor: default;
    overflow: hidden;
}
.fb-item:hover { background: rgba(255,255,255,0.03); border-color: var(--border); }
.fb-item.selected { background: rgba(0,255,136,0.06); border-color: rgba(0,255,136,0.3); }
.fb-item-checkbox { position: absolute; top: 8px; left: 8px; opacity: 0; transition: opacity 0.15s; }
.fb-item:hover .fb-item-checkbox, .fb-item.selected .fb-item-checkbox { opacity: 1; }
.fb-select { width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; }
.fb-item-icon { width: 44px; height: 44px; flex-shrink: 0; }
.fb-item-icon svg { width: 100%; height: 100%; }
.fb-item-info { flex: 1; min-width: 0; }
.fb-item-name { font-size: 13px; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fb-item-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.fb-item-actions { display: flex; gap: 2px; opacity: 0; transition: opacity 0.15s; }
.fb-item:hover .fb-item-actions { opacity: 1; }
.fb-action-btn { background: none; border: none; color: var(--text-muted); padding: 6px; border-radius: 6px; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; }
.fb-action-btn:hover { color: var(--text-primary); background: rgba(255,255,255,0.08); }
.fb-action-danger:hover { color: #EF4444; background: rgba(239,68,68,0.1); }
.fb-item-link { position: absolute; inset: 0; z-index: 1; pointer-events: none; }
.fb-folder-link { position: relative; z-index: 2; pointer-events: auto; display: flex; flex: 1; min-width: 0; }
.fb-folder { cursor: pointer; }

/* List View */
[data-view="list"] .fb-grid { display: flex; flex-direction: column; gap: 2px; }
[data-view="list"] .fb-item { border-radius: 6px; padding: 10px 16px; }
[data-view="list"] .fb-item-icon { width: 32px; height: 32px; }

/* Empty */
.fb-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 20px; text-align: center; }
.fb-empty-icon { margin-bottom: 16px; }
.fb-empty h3 { font-size: 16px; color: var(--text-secondary); margin-bottom: 8px; }
.fb-empty p { font-size: 13px; color: var(--text-muted); }

/* Modals */
.fb-modal { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; }
.fb-modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); }
.fb-modal-content { position: relative; background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; width: 90%; max-width: 520px; max-height: 80vh; display: flex; flex-direction: column; animation: modalIn 0.2s ease-out; }
.fb-modal-sm { max-width: 400px; }
.fb-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); }
.fb-modal-header h3 { font-size: 15px; font-weight: 600; margin: 0; }
.fb-modal-close { background: none; border: none; color: var(--text-muted); font-size: 20px; cursor: pointer; padding: 4px 8px; border-radius: 6px; }
.fb-modal-close:hover { color: var(--text-primary); background: rgba(255,255,255,0.05); }
.fb-modal-body { padding: 20px; overflow-y: auto; flex: 1; }
.fb-modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 20px; border-top: 1px solid var(--border); }
.fb-form-group { margin-bottom: 14px; }
.fb-label { display: block; font-size: 12px; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; }
.fb-input { width: 100%; padding: 8px 12px; background: var(--bg-input, rgba(255,255,255,0.04)); border: 1px solid var(--border); border-radius: 8px; color: var(--text-primary); font-size: 13px; font-family: inherit; outline: none; transition: border-color 0.15s; }
.fb-input:focus { border-color: var(--accent); }
.fb-select-input { width: 100%; padding: 8px 12px; background: var(--bg-input, rgba(255,255,255,0.04)); border: 1px solid var(--border); border-radius: 8px; color: var(--text-primary); font-size: 13px; font-family: inherit; outline: none; }
@keyframes modalIn { from { opacity: 0; transform: scale(0.96) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }

/* Upload Dropzone */
.fb-upload-dropzone { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 40px 20px; border: 2px dashed var(--border); border-radius: 12px; text-align: center; transition: all 0.2s; }
.fb-upload-dropzone.dragover { border-color: var(--accent); background: rgba(0,255,136,0.04); }
.fb-upload-dropzone p { font-size: 14px; color: var(--text-secondary); margin: 0; }
.fb-upload-dropzone span { font-size: 12px; color: var(--text-muted); }
.fb-upload-dest { display: flex; align-items: center; gap: 10px; margin-top: 16px; }
.fb-upload-dest label { font-size: 12px; color: var(--text-muted); white-space: nowrap; }
.fb-upload-dest .fb-select-input { flex: 1; }
.fb-upload-list { margin-top: 16px; display: flex; flex-direction: column; gap: 6px; max-height: 200px; overflow-y: auto; }
.fb-upload-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: rgba(255,255,255,0.03); border-radius: 8px; font-size: 13px; }
.fb-upload-item-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fb-upload-item-size { color: var(--text-muted); font-size: 11px; }
.fb-upload-item-status { font-size: 14px; }
.fb-upload-progress { width: 60px; height: 3px; background: rgba(255,255,255,0.06); border-radius: 3px; overflow: hidden; }
.fb-upload-progress-fill { height: 100%; background: var(--accent); border-radius: 3px; transition: width 0.2s; }

/* Viewer */
.fb-viewer { align-items: stretch; justify-content: stretch; }
.fb-viewer-content { position: relative; background: var(--bg-card); display: flex; flex-direction: column; width: 100%; height: 100%; animation: modalIn 0.2s ease-out; }
.fb-viewer-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
.fb-viewer-title { font-size: 14px; font-weight: 600; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fb-viewer-actions { display: flex; align-items: center; gap: 4px; }
.fb-viewer-close { background: none; border: none; color: var(--text-muted); font-size: 22px; cursor: pointer; padding: 4px 8px; border-radius: 6px; }
.fb-viewer-close:hover { color: var(--text-primary); background: rgba(255,255,255,0.05); }
.fb-viewer-body { flex: 1; overflow: auto; display: flex; align-items: center; justify-content: center; }
.fb-viewer-loading { color: var(--text-muted); font-size: 14px; }
.fb-viewer-body img { max-width: 100%; max-height: 100%; object-fit: contain; }
.fb-viewer-body iframe { width: 100%; height: 100%; border: none; }
.fb-viewer-body video { max-width: 100%; max-height: 100%; }
.fb-viewer-body audio { width: 300px; }
.fb-viewer-body pre { width: 100%; height: 100%; margin: 0; padding: 20px; overflow: auto; font-family: 'JetBrains Mono', monospace; font-size: 13px; line-height: 1.6; color: var(--text-primary); background: transparent; white-space: pre-wrap; word-break: break-word; }
.fb-viewer-body textarea.fb-editor { width: 100%; height: 100%; margin: 0; padding: 20px; border: none; background: transparent; color: var(--text-primary); font-family: 'JetBrains Mono', monospace; font-size: 13px; line-height: 1.6; resize: none; outline: none; white-space: pre; tab-size: 4; }

/* Move Modal */
.fb-move-list { display: flex; flex-direction: column; gap: 4px; }
.fb-move-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; color: var(--text-secondary); transition: all 0.15s; }
.fb-move-item:hover { background: rgba(0,255,136,0.06); color: var(--accent); }

/* Share */
.fb-share-link-row { display: flex; gap: 8px; }
.fb-share-link-row .fb-input { flex: 1; }

/* Context Menu */
.fb-context-menu {
    position: fixed;
    z-index: 10000;
    pointer-events: none;
}
.fb-context-menu-inner {
    pointer-events: auto;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    min-width: 200px;
    overflow: hidden;
    animation: ctxIn 0.1s ease-out;
}
@keyframes ctxIn {
    from { opacity: 0; transform: scale(0.95) translateY(4px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.fb-context-section { padding: 4px 0; }
.fb-context-divider { height: 1px; background: var(--border); margin: 4px 8px; }
.fb-context-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 8px 12px;
    background: none;
    border: none;
    color: var(--text-primary);
    font-size: 13px;
    font-family: inherit;
    cursor: pointer;
    text-align: left;
    transition: background 0.1s;
}
.fb-context-item:hover { background: rgba(0,255,136,0.08); color: var(--accent); }
.fb-context-item:disabled { opacity: 0.4; cursor: not-allowed; }
.fb-context-item kbd {
    margin-left: auto;
    font-size: 10px;
    padding: 2px 6px;
    background: rgba(255,255,255,0.06);
    border-radius: 4px;
    color: var(--text-muted);
    font-family: inherit;
}
.fb-context-danger { color: var(--rose-500); }
.fb-context-danger:hover { background: rgba(239,68,68,0.1); color: var(--rose-400); }
.fb-item.cut { opacity: 0.5; background: rgba(239,68,68,0.05); }

/* Folder Drag & Drop */
.fb-folder.dragging { opacity: 0.4; transform: scale(1.02); box-shadow: 0 4px 20px rgba(0,255,136,0.3); }
.fb-folder.drag-over { background: rgba(0,255,136,0.1); border: 2px dashed var(--accent); border-radius: 8px; }
</style>

<script>
const CSRF = '<?= $csrf ?>';
const CURRENT_FOLDER = <?= json_encode($parentId) ?>;
let selectedFiles = new Set();
let currentViewerFileId = null;
let currentViewerMime = '';
let pendingUploads = [];
let editingFileId = null;

/* ── View Toggle ── */
document.getElementById('viewToggle')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.fb-view-btn');
    if (!btn) return;
    document.querySelectorAll('.fb-view-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const view = btn.dataset.view;
    document.querySelectorAll('.fb-grid').forEach(g => g.setAttribute('data-view', view));
});

/* ── Upload ── */
document.getElementById('uploadBtn')?.addEventListener('click', () => {
    document.getElementById('uploadModal').style.display = 'flex';
});
function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
    pendingUploads = [];
    document.getElementById('uploadList').innerHTML = '';
    document.getElementById('uploadStartBtn').disabled = true;
}

const dropzone = document.getElementById('uploadDropzone');
const fileInput = document.getElementById('uploadFileInput');
if (dropzone) {
    dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', async (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        
        // Support dossier drop via webkitGetAsEntry (Chrome/Edge) + File System Access API
        const items = e.dataTransfer.items;
        if (items && items.length) {
            const entries = [];
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                if (item.kind === 'file') {
                    const entry = item.webkitGetAsEntry?.() || item.getAsEntry?.();
                    if (entry) {
                        entries.push(entry);
                    } else {
                        // Fallback: fichier simple
                        const file = item.getAsFile();
                        if (file) entries.push({ isFile: true, file });
                    }
                }
            }
            if (entries.length) {
                await processEntries(entries, document.getElementById('uploadFolderSelect').value);
                return;
            }
        }
        // Fallback: fichiers simples
        addFilesToUploadQueue(e.dataTransfer.files);
    });
}
if (fileInput) {
    fileInput.addEventListener('change', (e) => {
        addFilesToUploadQueue(e.target.files);
        e.target.value = '';
    });
}

// File System Access API (modern browsers) - bouton "Choisir dossier"
const pickFolderBtn = document.getElementById('pickFolderBtn');
if (pickFolderBtn && 'showDirectoryPicker' in window) {
    pickFolderBtn.style.display = 'inline-flex';
    pickFolderBtn.addEventListener('click', async () => {
        try {
            const dirHandle = await window.showDirectoryPicker({ mode: 'read' });
            const entries = [];
            for await (const [name, handle] of dirHandle.entries()) {
                if (handle.kind === 'file') {
                    const file = await handle.getFile();
                    entries.push({ isFile: true, file, relativePath: name });
                } else if (handle.kind === 'directory') {
                    entries.push({ isDirectory: true, handle, relativePath: name });
                }
            }
            await processEntries(entries, document.getElementById('uploadFolderSelect').value);
        } catch (err) {
            if (err.name !== 'AbortError') console.error('Folder picker error:', err);
        }
    });
}

function addFilesToUploadQueue(files) {
    const list = document.getElementById('uploadList');
    for (const file of files) {
        pendingUploads.push(file);
        const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
        const item = document.createElement('div');
        item.className = 'fb-upload-item';
        item.innerHTML = `
            <span class="fb-upload-item-status">📄</span>
            <span class="fb-upload-item-name">${file.name}</span>
            <span class="fb-upload-item-size">${sizeMB} MB</span>
            <div class="fb-upload-progress" style="display:none;"><div class="fb-upload-progress-fill" style="width:0%"></div></div>
            <span class="fb-upload-item-status"></span>
        `;
        list.appendChild(item);
    }
    document.getElementById('uploadStartBtn').disabled = pendingUploads.length === 0;
}

// Process directory entries recursively (webkitGetAsEntry + File System Access API)
async function processEntries(entries, folderId) {
    for (const entry of entries) {
        if (entry.isFile) {
            pendingUploads.push(entry.file);
            addFileToList(entry.file, entry.relativePath || entry.file.name);
        } else if (entry.isDirectory && entry.handle) {
            // File System Access API: traverse directory
            await traverseDirectory(entry.handle, entry.relativePath || '', folderId);
        } else if (entry.isDirectory) {
            // webkitGetAsEntry: traverse directory
            await traverseWebkitDirectory(entry, entry.relativePath || '', folderId);
        }
    }
    document.getElementById('uploadStartBtn').disabled = pendingUploads.length === 0;
}

async function traverseDirectory(dirHandle, basePath, folderId) {
    for await (const [name, handle] of dirHandle.entries()) {
        const relativePath = basePath ? `${basePath}/${name}` : name;
        if (handle.kind === 'file') {
            const file = await handle.getFile();
            file.relativePath = relativePath;
            pendingUploads.push(file);
            addFileToList(file, relativePath);
        } else if (handle.kind === 'directory') {
            await traverseDirectory(handle, relativePath, folderId);
        }
    }
}

async function traverseWebkitDirectory(dirEntry, basePath, folderId) {
    return new Promise((resolve) => {
        const reader = dirEntry.createReader();
        const readEntries = () => {
            reader.readEntries(async (entries) => {
                if (!entries.length) {
                    resolve();
                    return;
                }
                for (const entry of entries) {
                    const relativePath = basePath ? `${basePath}/${entry.name}` : entry.name;
                    if (entry.isFile) {
                        entry.file((file) => {
                            file.relativePath = relativePath;
                            pendingUploads.push(file);
                            addFileToList(file, relativePath);
                        });
                    } else if (entry.isDirectory) {
                        await traverseWebkitDirectory(entry, relativePath, folderId);
                    }
                }
                readEntries();
            });
        };
        readEntries();
    });
}

function addFileToList(file, displayName) {
    const list = document.getElementById('uploadList');
    const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
    const item = document.createElement('div');
    item.className = 'fb-upload-item';
    item.dataset.relativePath = file.relativePath || '';
    item.innerHTML = `
        <span class="fb-upload-item-status">📄</span>
        <span class="fb-upload-item-name" title="${displayName}">${displayName}</span>
        <span class="fb-upload-item-size">${sizeMB} MB</span>
        <div class="fb-upload-progress" style="display:none;"><div class="fb-upload-progress-fill" style="width:0%"></div></div>
        <span class="fb-upload-item-status"></span>
    `;
    list.appendChild(item);
}

document.getElementById('uploadStartBtn')?.addEventListener('click', () => {
    if (!pendingUploads.length) return;
    const folderId = document.getElementById('uploadFolderSelect').value;
    const btn = document.getElementById('uploadStartBtn');
    btn.disabled = true;
    btn.textContent = 'Upload en cours...';

    let done = 0;
    const total = pendingUploads.length;

    pendingUploads.forEach((file, i) => {
        const items = document.querySelectorAll('.fb-upload-item');
        const item = items[i];
        const progress = item.querySelector('.fb-upload-progress');
        const fill = item.querySelector('.fb-upload-progress-fill');
        const status = item.querySelector('.fb-upload-item-status:last-child');
        progress.style.display = 'block';

        const fd = new FormData();
        fd.append('file', file);
        if (folderId) fd.append('folder_id', folderId);
        if (file.relativePath) fd.append('relative_path', file.relativePath);

        const xhr = new XMLHttpRequest();
        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                fill.style.width = Math.round((e.loaded / e.total) * 100) + '%';
            }
        };
        xhr.onload = () => {
            const data = JSON.parse(xhr.responseText);
            status.textContent = data.success ? '✅' : '❌';
            progress.style.display = 'none';
            done++;
            if (done === total) {
                setTimeout(() => { location.reload(); }, 600);
            }
        };
        xhr.onerror = () => {
            status.textContent = '❌';
            progress.style.display = 'none';
            done++;
            if (done === total) {
                setTimeout(() => { location.reload(); }, 600);
            }
        };
        xhr.open('POST', '/files/upload');
        xhr.setRequestHeader('X-CSRF-Token', CSRF);
        xhr.send(fd);
    });
});

/* ── New Folder ── */
document.getElementById('newFolderBtn')?.addEventListener('click', () => {
    document.getElementById('folderModal').style.display = 'flex';
    document.getElementById('folderNameInput').value = '';
    setTimeout(() => document.getElementById('folderNameInput').focus(), 100);
});
function closeFolderModal() { document.getElementById('folderModal').style.display = 'none'; }
async function createFolder() {
    const name = document.getElementById('folderNameInput').value.trim();
    if (!name) return;
    const fd = new FormData();
    fd.append('name', name);
    fd.append('_token', CSRF);
    if (CURRENT_FOLDER) fd.append('parent_id', CURRENT_FOLDER);
    const res = await fetch('/folders', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { location.reload(); } else { alert(data.error); }
}

/* ── Folder Actions ── */
async function deleteFolder(id) {
    if (!confirm('Supprimer ce dossier ?')) return;
    const res = await fetch('/folders/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-Token': CSRF }
    });
    const data = await res.json();
    if (data.success) location.reload(); else alert(data.error);
}
async function renameFolder(id, oldName) {
    const name = prompt('Nouveau nom :', oldName);
    if (!name || name === oldName) return;
    const fd = new FormData();
    fd.append('name', name);
    fd.append('_token', CSRF);
    const res = await fetch('/folders/' + id + '/rename', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) location.reload(); else alert(data.error);
}

/* ── File Actions ── */
function downloadFile(id) { window.location.href = '/files/' + id + '/download'; }

async function deleteFile(id) {
    if (!confirm('Supprimer ce fichier ?')) return;
    const res = await fetch('/files/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-Token': CSRF }
    });
    const data = await res.json();
    if (data.success) location.reload(); else alert(data.error);
}

/* ── File Viewer ── */
function viewFile(id, mime, name) {
    currentViewerFileId = id;
    currentViewerMime = mime;
    document.getElementById('viewerTitle').textContent = name;
    document.getElementById('viewerBody').innerHTML = '<div class="fb-viewer-loading">Chargement...</div>';
    document.getElementById('viewerModal').style.display = 'flex';

    const isText = mime.startsWith('text/') || ['application/json','application/javascript','application/xml'].includes(mime);
    const isImage = mime.startsWith('image/');
    const isVideo = mime.startsWith('video/');
    const isAudio = mime.startsWith('audio/');
    const isPdf = mime === 'application/pdf';

    document.getElementById('viewerEditBtn').style.display = isText ? 'inline-flex' : 'none';
    document.getElementById('viewerSaveBtn').style.display = 'none';
    editingFileId = null;

    if (isImage) {
        document.getElementById('viewerBody').innerHTML = `<img src="/files/${id}/preview" alt="${name}">`;
    } else if (isPdf) {
        document.getElementById('viewerBody').innerHTML = `<iframe src="/files/${id}/preview"></iframe>`;
    } else if (isVideo) {
        document.getElementById('viewerBody').innerHTML = `<video controls autoplay src="/files/${id}/preview"></video>`;
    } else if (isAudio) {
        document.getElementById('viewerBody').innerHTML = `<audio controls autoplay src="/files/${id}/preview"></audio>`;
    } else if (isText) {
        fetch('/files/' + id + '/content')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('viewerBody').innerHTML = `<pre id="viewerContent">${escapeHtml(data.file.content)}</pre>`;
                } else {
                    document.getElementById('viewerBody').innerHTML = `<div class="fb-viewer-loading">${data.error}</div>`;
                }
            });
    } else {
        document.getElementById('viewerBody').innerHTML = `
            <div style="text-align:center; padding:40px;">
                <p style="color:var(--text-muted); margin-bottom:16px;">Aperçu non disponible pour ce type de fichier</p>
                <button class="fb-btn fb-btn-primary" onclick="downloadFile(${id})">Télécharger</button>
            </div>`;
    }
}
function closeViewer() {
    document.getElementById('viewerModal').style.display = 'none';
    currentViewerFileId = null;
}

function toggleEditMode() {
    if (editingFileId === currentViewerFileId) {
        // Sortir du mode édition
        editingFileId = null;
        document.getElementById('viewerEditBtn').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Éditer';
        document.getElementById('viewerSaveBtn').style.display = 'none';
        // Recharger en mode lecture
        viewFile(currentViewerFileId, currentViewerMime, document.getElementById('viewerTitle').textContent);
    } else {
        // Passer en mode édition
        editingFileId = currentViewerFileId;
        document.getElementById('viewerEditBtn').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Annuler';
        document.getElementById('viewerSaveBtn').style.display = 'inline-flex';

        const pre = document.getElementById('viewerContent');
        if (pre) {
            const text = pre.textContent;
            const textarea = document.createElement('textarea');
            textarea.className = 'fb-editor';
            textarea.id = 'viewerContent';
            textarea.value = text;
            pre.replaceWith(textarea);
            textarea.focus();
        }
    }
}

async function saveFileContent() {
    const textarea = document.getElementById('viewerContent');
    if (!textarea || !currentViewerFileId) return;
    const btn = document.getElementById('viewerSaveBtn');
    btn.textContent = 'Sauvegarde...';
    btn.disabled = true;

    const res = await fetch('/files/' + currentViewerFileId + '/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: textarea.value })
    });
    const data = await res.json();
    btn.disabled = false;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg> Sauvegarder';
    if (data.success) {
        editingFileId = null;
        document.getElementById('viewerEditBtn').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Éditer';
        document.getElementById('viewerSaveBtn').style.display = 'none';
        if (typeof SaecToast !== 'undefined') SaecToast.success('Fichier sauvegardé');
    } else {
        alert(data.error || 'Erreur sauvegarde');
    }
}

function escapeHtml(str) {
    return str.replace(/&/g,"&").replace(/</g,"<").replace(/>/g,">");
}

/* ── Context Menu ── */
let contextTarget = null;
let clipboard = { action: null, ids: [] };

document.addEventListener("contextmenu", (e) => {
    const folderItem = e.target.closest(".fb-folder");
    const fileItem = e.target.closest(".fb-file");
    if (!folderItem && !fileItem) return;
    
    e.preventDefault();
    contextTarget = folderItem || fileItem;
    
    const menu = document.getElementById("contextMenu");
    menu.style.left = e.clientX + "px";
    menu.style.top = e.clientY + "px";
    menu.style.display = "block";
    
    document.getElementById("contextPasteBtn").disabled = clipboard.ids.length === 0;
});

document.addEventListener("click", () => {
    document.getElementById("contextMenu").style.display = "none";
    contextTarget = null;
});

function contextRename() {
    if (!contextTarget) return;
    const id = contextTarget.dataset.id;
    const name = contextTarget.dataset.name;
    const isFolder = contextTarget.dataset.type === "folder";
    if (isFolder) renameFolder(id, name);
    else renameFile(id, name);
    closeContext();
}

function contextDownload() {
    if (!contextTarget) return;
    const id = contextTarget.dataset.id;
    downloadFile(id);
    closeContext();
}

function contextShare() {
    if (!contextTarget) return;
    const id = contextTarget.dataset.id;
    shareFile(id);
    closeContext();
}

function contextCopy() {
    if (!contextTarget) return;
    const id = parseInt(contextTarget.dataset.id);
    const type = contextTarget.dataset.type;
    clipboard = { action: "copy", ids: [id], type };
    document.getElementById("contextPasteBtn").disabled = false;
    closeContext();
}

function contextCut() {
    if (!contextTarget) return;
    const id = parseInt(contextTarget.dataset.id);
    const type = contextTarget.dataset.type;
    clipboard = { action: "cut", ids: [id], type };
    contextTarget.classList.add("cut");
    document.getElementById("contextPasteBtn").disabled = false;
    closeContext();
}

function contextPaste() {
    if (clipboard.ids.length === 0) return;
    const targetFolder = contextTarget?.dataset.type === "folder" ? contextTarget.dataset.id : CURRENT_FOLDER;
    const action = clipboard.action;
    let done = 0;
    const total = clipboard.ids.length;
    
    clipboard.ids.forEach(async (id) => {
        if (action === "copy") {
            await fetch("/files/" + id + "/copy", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ folder_id: targetFolder, _token: CSRF })
            });
        } else if (action === "cut") {
            await fetch("/files/" + id + "/move", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ folder_id: targetFolder })
            });
        }
        done++;
        if (done === total) location.reload();
    });
    clipboard = { action: null, ids: [] };
    closeContext();
}

function contextMove() {
    if (!contextTarget) return;
    const id = contextTarget.dataset.id;
    moveTargets = [parseInt(id)];
    document.getElementById("moveModal").style.display = "flex";
    closeContext();
}

function contextDelete() {
    if (!contextTarget) return;
    const id = contextTarget.dataset.id;
    const isFolder = contextTarget.dataset.type === "folder";
    if (isFolder) deleteFolder(id);
    else deleteFile(id);
    closeContext();
}

function contextInfo() {
    if (!contextTarget) return;
    const id = contextTarget.dataset.id;
    const mime = contextTarget.dataset.mime;
    const name = contextTarget.dataset.name;
    viewFile(parseInt(id), mime, name);
    closeContext();
}

function closeContext() {
    document.getElementById("contextMenu").style.display = "none";
    contextTarget = null;
}

/* ── Share ── */
let shareFileId = null;
function shareFile(id) {
    shareFileId = id;
    document.getElementById('shareResult').style.display = 'none';
    document.getElementById('shareCreateBtn').style.display = 'inline-flex';
    document.getElementById('shareModal').style.display = 'flex';
}
function closeShareModal() { document.getElementById('shareModal').style.display = 'none'; shareFileId = null; }
async function createShare() {
    if (!shareFileId) return;
    const fd = new FormData();
    fd.append('_token', CSRF);
    const expiry = document.getElementById('shareExpiry').value;
    const pass = document.getElementById('sharePassword').value;
    if (expiry) fd.append('expires_at', expiry);
    if (pass) fd.append('password', pass);
    const res = await fetch('/files/' + shareFileId + '/share', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        document.getElementById('shareLink').value = window.location.origin + '/share/' + data.share.share_token;
        document.getElementById('shareResult').style.display = 'block';
        document.getElementById('shareCreateBtn').style.display = 'none';
    } else {
        alert(data.error);
    }
}
function copyShareLink() {
    const input = document.getElementById('shareLink');
    input.select();
    navigator.clipboard.writeText(input.value);
}

/* ── Selection ── */
document.addEventListener('click', (e) => {
    const checkbox = e.target.closest('.fb-select');
    if (checkbox) {
        e.stopPropagation();
        const id = parseInt(checkbox.dataset.id);
        if (checkbox.checked) { selectedFiles.add(id); checkbox.closest('.fb-item').classList.add('selected'); }
        else { selectedFiles.delete(id); checkbox.closest('.fb-item').classList.remove('selected'); }
        updateBatchBar();
    }
    // Click on file item = open viewer
    const item = e.target.closest('.fb-file');
    if (item && !e.target.closest('.fb-item-actions') && !e.target.closest('.fb-item-checkbox')) {
        const id = item.dataset.id;
        const mime = item.dataset.mime;
        const name = item.dataset.name;
        viewFile(parseInt(id), mime, name);
    }
});
function updateBatchBar() {
    const bar = document.getElementById('batchBar');
    if (selectedFiles.size > 0) {
        bar.style.display = 'flex';
        document.getElementById('batchCount').textContent = selectedFiles.size + ' sélectionné' + (selectedFiles.size > 1 ? 's' : '');
    } else {
        bar.style.display = 'none';
    }
}
function clearSelection() {
    selectedFiles.clear();
    document.querySelectorAll('.fb-select').forEach(c => { c.checked = false; c.closest('.fb-item')?.classList.remove('selected'); });
    updateBatchBar();
}
function batchDownload() { selectedFiles.forEach(id => downloadFile(id)); }
function batchDelete() {
    if (!confirm('Supprimer ' + selectedFiles.size + ' fichier(s) ?')) return;
    let done = 0;
    selectedFiles.forEach(async (id) => {
        await fetch('/files/' + id, { method: 'DELETE', headers: { 'X-CSRF-Token': CSRF } });
        done++;
        if (done === selectedFiles.size) location.reload();
    });
}

/* ── Move ── */
let moveTargets = [];
function batchMove() {
    moveTargets = [...selectedFiles];
    document.getElementById('moveModal').style.display = 'flex';
}
function closeMoveModal() { document.getElementById('moveModal').style.display = 'none'; moveTargets = []; }
async function moveFilesTo(folderId) {
    let done = 0;
    const total = moveTargets.length;
    moveTargets.forEach(async (id) => {
        await fetch('/files/' + id + '/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ folder_id: folderId })
        });
        done++;
        if (done === total) location.reload();
    });
}

/* ── Folder Drag & Drop (move folder into folder) ── */
let dragSourceFolder = null;

document.addEventListener('dragstart', (e) => {
    const folder = e.target.closest('.fb-folder');
    if (!folder) return;
    dragSourceFolder = folder;
    folder.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', folder.dataset.id);
});

document.addEventListener('dragend', (e) => {
    const folder = e.target.closest('.fb-folder');
    if (folder) folder.classList.remove('dragging');
    dragSourceFolder = null;
});

document.addEventListener('dragover', (e) => {
    const folder = e.target.closest('.fb-folder');
    if (!folder || folder === dragSourceFolder) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    folder.classList.add('drag-over');
});

document.addEventListener('dragleave', (e) => {
    const folder = e.target.closest('.fb-folder');
    if (folder) folder.classList.remove('drag-over');
});

document.addEventListener('drop', async (e) => {
    const targetFolder = e.target.closest('.fb-folder');
    if (!targetFolder || targetFolder === dragSourceFolder) return;
    e.preventDefault();
    targetFolder.classList.remove('drag-over');
    
    const sourceId = parseInt(dragSourceFolder.dataset.id);
    const targetId = parseInt(targetFolder.dataset.id);
    
    if (sourceId === targetId) return;
    
    // Vérifier que target n'est pas un descendant de source
    let current = targetId;
    while (current) {
        if (current === sourceId) {
            alert('Impossible de déplacer un dossier dans son propre sous-dossier');
            return;
        }
        // On aurait besoin de l'API pour vérifier la hiérarchie complète
        // Pour l'instant, on fait confiance au backend
        break;
    }
    
    const fd = new FormData();
    fd.append('_token', CSRF);
    const res = await fetch('/folders/' + sourceId + '/move', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ parent_id: targetId })
    });
    const data = await res.json();
    if (data.success) {
        location.reload();
    } else {
        alert(data.error || 'Erreur déplacement');
    }
});

/* ── Keyboard shortcuts ── */
document.addEventListener('keydown', (e) => {
    // F2 = rename
    if (e.key === 'F2') {
        e.preventDefault();
        const selected = document.querySelector('.fb-item.selected');
        if (selected) {
            const id = selected.dataset.id;
            const name = selected.dataset.name;
            const isFolder = selected.dataset.type === 'folder';
            if (isFolder) renameFolder(id, name);
            else renameFile(id, name);
        }
    }
    // Delete = delete
    if (e.key === 'Delete') {
        const selected = document.querySelector('.fb-item.selected');
        if (selected && !e.target.matches('input, textarea')) {
            e.preventDefault();
            const id = selected.dataset.id;
            const isFolder = selected.dataset.type === 'folder';
            if (isFolder) deleteFolder(id);
            else deleteFile(id);
        }
    }
    // Enter = open
    if (e.key === 'Enter') {
        const selected = document.querySelector('.fb-item.selected');
        if (selected) {
            const id = selected.dataset.id;
            const mime = selected.dataset.mime;
            const name = selected.dataset.name;
            if (mime) viewFile(parseInt(id), mime, name);
        }
    }
    // Ctrl+A = select all
    if (e.ctrlKey && e.key === 'a') {
        e.preventDefault();
        document.querySelectorAll('.fb-select').forEach(c => {
            c.checked = true;
            c.closest('.fb-item')?.classList.add('selected');
            selectedFiles.add(parseInt(c.dataset.id));
        });
        updateBatchBar();
    }
    // Escape = clear selection / close modals
    if (e.key === 'Escape') {
        clearSelection();
        closeContext();
        closeUploadModal();
        closeFolderModal();
        closeViewer();
        closeShareModal();
        closeMoveModal();
    }
});
</script>
