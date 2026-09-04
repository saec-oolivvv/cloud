<?php
/** @var array $user */
/** @var array $files */
/** @var array $folders */
/** @var array $breadcrumb */
/** @var int $total */

$user = $user ?? [];
$files = $files ?? [];
$folders = $folders ?? [];
$breadcrumb = $breadcrumb ?? [['name' => t('files.breadcrumb_home'), 'url' => '/files']];
$total = $total ?? 0;
$parentId = $parentId ?? null;
$used = $used ?? 0;
$quota = $quota ?? 10737418240;
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><?= t('files.title') ?></h1>
        <div class="breadcrumb">
            <?php foreach ($breadcrumb as $i => $bc): ?>
            <?php if ($i > 0): ?> / <?php endif; ?>
            <?php if ($bc['url']): ?>
            <a href="<?= $bc['url'] ?>"><?= htmlspecialchars($bc['name']) ?></a>
            <?php else: ?>
            <span><?= htmlspecialchars($bc['name']) ?></span>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline" onclick="document.getElementById('newFolderModal').style.display='flex'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <?= t('files.create_folder') ?>
        </button>
        <button class="btn btn-primary" onclick="document.getElementById('uploadZone').scrollIntoView({behavior:'smooth'})">
            + <?= t('files.upload') ?>
        </button>
    </div>
</div>

<!-- Storage Bar -->
<div class="card" style="margin-bottom: var(--space-6);">
    <div class="card-header">
        <span class="card-title"><?= t('dashboard.storage_used') ?></span>
        <span class="card-sub">
            <?= $this->formatSize($used) ?> / <?= $this->formatSize($quota) ?>
        </span>
    </div>
    <?php $pct = $quota > 0 ? ($used / $quota) * 100 : 0; ?>
    <div class="storage-bar">
        <div class="storage-bar-fill <?= $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : '') ?>" style="width: <?= min($pct, 100) ?>%"></div>
    </div>
</div>

<!-- Upload Zone -->
<div class="upload-zone" id="uploadZone">
    <div class="upload-icon" style="background: linear-gradient(135deg, var(--blue-500), var(--cyan-400)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">☁️</div>
    <h3><?= t('files.upload_drag') ?></h3>
    <p><?= t('files.upload_click') ?></p>
    <div class="upload-actions">
        <button class="btn btn-primary" onclick="document.getElementById('fileInput').click();">
            📁 <?= t('common.next') ?>
        </button>
    </div>
    <div class="file-types">
        <?= t('files.upload_hint', ['size' => '10 GB']) ?>
    </div>
    <input type="file" id="fileInput" multiple style="display:none;" />
</div>

<!-- Upload Queue (hidden by default) -->
<div class="upload-queue" id="uploadQueue" style="display:none;">
    <div class="queue-header">
        <span class="queue-title">📦 Queue</span>
        <span class="queue-count" id="queueCount">0 files</span>
    </div>
    <div id="queueList"></div>
</div>

<!-- Folders -->
<?php if (!empty($folders)): ?>
<div style="margin-bottom: var(--space-4);">
    <div class="file-list-header">
        <span class="list-title">📁 <?= t('nav.files') ?></span>
    </div>
    <div class="file-list" data-stagger>
        <?php foreach ($folders as $folder): ?>
        <div class="file-row">
            <div class="row-icon" style="color: var(--amber-400);">📁</div>
            <div class="row-info">
                <a href="/files?folder=<?= $folder['id'] ?>" class="row-name">
                    <?= htmlspecialchars($folder['name']) ?>
                </a>
                <div class="row-meta"><?= date('d/m/Y', strtotime($folder['created_at'])) ?></div>
            </div>
            <div class="row-actions">
                <span class="action-btn" onclick="renameFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars($folder['name']) ?>')" title="<?= t('common.save') ?>">✏️</span>
                <span class="action-btn" onclick="deleteFolder(<?= $folder['id'] ?>)" title="<?= t('files.delete') ?>" style="color:var(--rose-500);">🗑</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Files -->
<?php if (!empty($files)): ?>
<div>
    <div class="file-list-header">
        <span class="list-title">📄 <?= t('files.title') ?> (<?= count($files) ?>)</span>
    </div>
    <div class="file-list" data-stagger>
        <?php foreach ($files as $file): ?>
        <div class="file-row" data-id="<?= $file['id'] ?>">
            <div class="row-icon"><?= getFileIcon($file['mime_type'] ?? '') ?></div>
            <div class="row-info">
                <div class="row-name"><?= htmlspecialchars($file['original_name']) ?></div>
                <div class="row-meta">
                    <?= $this->formatSize($file['size']) ?> · <?= date('d/m/Y H:i', strtotime($file['created_at'])) ?>
                </div>
            </div>
            <div class="row-size"><?= $this->formatSize($file['size']) ?></div>
            <div class="row-actions">
                <span class="action-btn" onclick="downloadFile(<?= $file['id'] ?>)" title="<?= t('files.download') ?>">📥</span>
                <span class="action-btn" onclick="shareFile(<?= $file['id'] ?>)" title="<?= t('files.share') ?>">🔗</span>
                <span class="action-btn" onclick="deleteFile(<?= $file['id'] ?>)" title="<?= t('files.delete') ?>" style="color:var(--rose-500);">🗑</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($files) && empty($folders)): ?>
<div class="empty-state">
    <div class="empty-state-icon">📂</div>
    <h3><?= t('files.no_files') ?></h3>
    <p><?= t('files.no_files_hint') ?></p>
</div>
<?php endif; ?>

<!-- Footer -->
<div class="page-footer">
    <span><?= t('app.copyright') ?></span>
    <span class="footer-badge">🔐 AES-256-GCM · <?= t('app.footer_stack') ?></span>
</div>

<!-- Modal New Folder -->
<div id="newFolderModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:440px;">
        <div class="card" style="animation: modalIn 0.2s ease-out;">
            <div class="card-header">
                <div class="card-title" style="display:flex; align-items:center; gap:var(--space-3);">
                    <div class="settings-section-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                    </div>
                    <div>
                        <div><?= t('files.create_folder') ?></div>
                        <div style="font-size:11px; color:var(--text-muted); font-weight:400;">Créer un nouveau dossier</div>
                    </div>
                </div>
                <button class="modal-close" onclick="document.getElementById('newFolderModal').style.display='none'">×</button>
            </div>
            <div class="card-body">
                <form id="newFolderForm">
                    <div class="form-group">
                        <label class="form-label">Nom du dossier</label>
                        <input type="text" name="name" class="form-input" required autofocus placeholder="Mon dossier" autocomplete="off">
                    </div>
                    <?php if ($parentId): ?>
                    <input type="hidden" name="parent_id" value="<?= $parentId ?>">
                    <?php endif; ?>
                    <div style="display:flex; justify-content:flex-end; gap:var(--space-3);">
                        <button type="button" onclick="document.getElementById('newFolderModal').style.display='none'" class="btn btn-ghost">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Share -->
<div id="shareModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="card">
            <div class="card-header">
                <div class="card-title" style="display:flex; align-items:center; gap:var(--space-3);">
                    <div class="settings-section-icon cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                    </div>
                    <?= t('shares.create') ?>
                </div>
                <button class="modal-close" onclick="document.getElementById('shareModal').style.display='none'">×</button>
            </div>
            <div class="card-body">
                <form id="shareForm">
                    <input type="hidden" name="file_id" id="shareFileId">
                    <div class="form-group">
                        <label class="form-label"><?= t('shares.expires') ?></label>
                        <input type="datetime-local" name="expires_at" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password (optional)</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;"><?= t('common.confirm') ?></button>
                </form>
                <div id="shareResult" style="display:none; margin-top:var(--space-4);">
                    <label class="form-label"><?= t('shares.copy_link') ?></label>
                    <div style="display:flex; gap:var(--space-2);">
                        <input type="text" id="shareLink" class="form-input" readonly>
                        <button class="btn btn-outline" onclick="copyShareLink()"><?= t('shares.copy_link') ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.page-footer {
    margin-top: var(--space-6); font-size: 12px; color: var(--text-muted);
    border-top: 1px solid var(--border-subtle); padding-top: var(--space-4);
    display: flex; justify-content: space-between; flex-wrap: wrap; gap: var(--space-2);
}
.footer-badge {
    display: inline-flex; align-items: center; gap: var(--space-2);
    padding: var(--space-1) var(--space-3); border-radius: var(--radius-full);
    background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2);
    font-family: var(--font-mono); font-size: 10px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.08em; color: var(--emerald-400);
}
</style>

<script>
<?php
// Helper function for file icons
function getFileIcon(string $mime): string {
    $map = [
        'application/pdf' => '📄',
        'image/' => '🖼',
        'video/' => '🎬',
        'audio/' => '🎵',
        'application/zip' => '📦',
        'application/x-rar' => '📦',
        'application/x-7z' => '📦',
        'text/' => '📝',
        'application/json' => '📝',
        'application/vnd.ms-excel' => '📊',
        'application/vnd.openxmlformats-officedocument' => '📊',
        'application/msword' => '📄',
        'application/vnd.ms-powerpoint' => '📊',
    ];
    foreach ($map as $prefix => $icon) {
        if (str_starts_with($mime, $prefix)) return $icon;
    }
    return '📄';
}
?>

// Upload
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');

uploadZone.addEventListener('click', (e) => {
    if (e.target.closest('.upload-actions')) return;
    fileInput.click();
});

uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('dragover'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', (e) => { handleFiles(e.target.files); e.target.value = ''; });

function handleFiles(files) {
    if (!files.length) return;
    const queue = document.getElementById('uploadQueue');
    const queueList = document.getElementById('queueList');
    const queueCount = document.getElementById('queueCount');
    queue.style.display = 'block';

    for (const file of files) {
        uploadFile(file, queueList, queueCount);
    }
}

async function uploadFile(file, queueList, queueCount) {
    const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
    const item = document.createElement('div');
    item.className = 'queue-item';
    item.innerHTML = `
        <div class="file-icon doc">📄</div>
        <div class="file-info">
            <div class="file-name">${file.name}</div>
            <div class="file-meta">${sizeMB} MB</div>
        </div>
        <div class="file-progress">
            <div class="progress-bar"><div class="fill" style="width:0%;"></div></div>
            <div class="progress-label">0%</div>
        </div>
        <div class="file-status uploading">⏳</div>
    `;
    queueList.appendChild(item);
    queueCount.textContent = queueList.children.length + ' files';

    const formData = new FormData();
    formData.append('file', file);

    const fill = item.querySelector('.fill');
    const label = item.querySelector('.progress-label');
    const status = item.querySelector('.file-status');

    try {
        const xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                fill.style.width = pct + '%';
                label.textContent = pct + '%';
            }
        });
        xhr.onload = function() {
            const data = JSON.parse(xhr.responseText);
            if (data.success) {
                fill.style.width = '100%';
                label.textContent = '100%';
                status.className = 'file-status done';
                status.textContent = '✅';
                if (typeof SaecToast !== 'undefined') SaecToast.success('<?= t('files.upload_success') ?>');
                setTimeout(() => location.reload(), 800);
            } else {
                status.className = 'file-status error';
                status.textContent = '❌';
                label.textContent = data.error || 'Error';
                if (typeof SaecToast !== 'undefined') SaecToast.error(data.error || 'Upload failed');
            }
        };
        xhr.onerror = function() {
            status.className = 'file-status error';
            status.textContent = '❌';
            if (typeof SaecToast !== 'undefined') SaecToast.error('Network error');
        };
        xhr.open('POST', '/files/upload');
        xhr.send(formData);
    } catch(e) {
        status.className = 'file-status error';
        status.textContent = '❌';
        if (typeof SaecToast !== 'undefined') SaecToast.error('Upload failed');
    }
}

// Download
function downloadFile(id) { window.location.href = `/files/${id}/download`; }

// Delete
async function deleteFile(id) {
    if (!confirm('Delete file?')) return;
    const form = new FormData();
    form.append('_token', '<?= \Saec\Core\Session::csrfToken() ?>');
    const res = await fetch(`/files/${id}`, { method: 'DELETE', body: form });
    const data = await res.json();
    if (data.success) {
        if (typeof SaecToast !== 'undefined') SaecToast.success('File deleted');
        setTimeout(() => location.reload(), 600);
    } else {
        if (typeof SaecToast !== 'undefined') SaecToast.error(data.error || 'Delete failed');
    }
}

// Share
function shareFile(id) {
    document.getElementById('shareFileId').value = id;
    document.getElementById('shareResult').style.display = 'none';
    document.getElementById('shareModal').style.display = 'flex';
}
document.getElementById('shareForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    form.append('_token', '<?= \Saec\Core\Session::csrfToken() ?>');
    const res = await fetch(`/files/${form.get('file_id')}/share`, { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) {
        document.getElementById('shareLink').value = `${window.location.origin}/share/${data.share.share_token}`;
        document.getElementById('shareResult').style.display = 'block';
        if (typeof SaecToast !== 'undefined') SaecToast.success('Share link created');
    } else {
        if (typeof SaecToast !== 'undefined') SaecToast.error(data.error || 'Share failed');
    }
});
function copyShareLink() {
    const input = document.getElementById('shareLink');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        if (typeof SaecToast !== 'undefined') SaecToast.success('<?= t('shares.link_copied') ?>');
    }).catch(() => {
        document.execCommand('copy');
        if (typeof SaecToast !== 'undefined') SaecToast.success('<?= t('shares.link_copied') ?>');
    });
}

// Folder
document.getElementById('newFolderForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    form.append('_token', '<?= \Saec\Core\Session::csrfToken() ?>');
    const res = await fetch('/folders', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) {
        if (typeof SaecToast !== 'undefined') SaecToast.success('Folder created');
        setTimeout(() => location.reload(), 600);
    } else {
        if (typeof SaecToast !== 'undefined') SaecToast.error(data.error || 'Erreur lors de la création');
    }
});
async function deleteFolder(id) {
    if (!confirm('Delete folder?')) return;
    const form = new FormData();
    form.append('_token', '<?= \Saec\Core\Session::csrfToken() ?>');
    const res = await fetch(`/folders/${id}`, { method: 'DELETE', body: form });
    const data = await res.json();
    if (data.success) {
        if (typeof SaecToast !== 'undefined') SaecToast.success('Folder deleted');
        setTimeout(() => location.reload(), 600);
    } else {
        if (typeof SaecToast !== 'undefined') SaecToast.error(data.error || 'Delete failed');
    }
}
async function renameFolder(id, oldName) {
    const name = prompt('New name:', oldName);
    if (!name || name === oldName) return;
    const form = new FormData();
    form.append('name', name);
    form.append('_token', '<?= \Saec\Core\Session::csrfToken() ?>');
    const res = await fetch(`/folders/${id}/rename`, { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) {
        if (typeof SaecToast !== 'undefined') SaecToast.success('Folder renamed');
        setTimeout(() => location.reload(), 600);
    } else {
        if (typeof SaecToast !== 'undefined') SaecToast.error(data.error || 'Rename failed');
    }
}
</script>
