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
            <a href="<?= $bc['url'] ?>" style="color:var(--text-secondary);"><?= htmlspecialchars($bc['name']) ?></a>
            <?php else: ?>
            <span><?= htmlspecialchars($bc['name']) ?></span>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div style="display:flex; gap:var(--space-3);">
        <button class="btn btn-outline" onclick="document.getElementById('newFolderModal').style.display='flex'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
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
        <span style="font-size:12px; color:var(--text-muted);">
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
    <div class="upload-icon">☁️</div>
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
    <div class="file-list">
        <?php foreach ($folders as $folder): ?>
        <div class="file-row">
            <div class="row-icon" style="color: var(--amber-400);">📁</div>
            <div class="row-info">
                <a href="/files?folder=<?= $folder['id'] ?>" class="row-name" style="color:var(--text-primary);">
                    <?= htmlspecialchars($folder['name']) ?>
                </a>
                <div class="row-meta"><?= date('d/m/Y', strtotime($folder['created_at'])) ?></div>
            </div>
            <div class="row-actions">
                <span class="action-btn" onclick="renameFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars($folder['name']) ?>')" title="<?= t('common.save') ?>">✏️</span>
                <span class="action-btn" onclick="deleteFolder(<?= $folder['id'] ?>)" title="<?= t('files.delete') ?>" style="color:var(--rose-400);">🗑</span>
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
    <div class="file-list">
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
                <span class="action-btn" onclick="deleteFile(<?= $file['id'] ?>)" title="<?= t('files.delete') ?>" style="color:var(--rose-400);">🗑</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($files) && empty($folders)): ?>
<div class="card" style="text-align:center; padding: var(--space-10);">
    <div style="font-size:48px; margin-bottom:var(--space-4); opacity:0.5;">📂</div>
    <div style="font-size:16px; font-weight:500; margin-bottom:var(--space-2);"><?= t('files.no_files') ?></div>
    <div style="font-size:13px; color:var(--text-muted);"><?= t('files.no_files_hint') ?></div>
</div>
<?php endif; ?>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span>AES-256-GCM · <?= t('app.footer_stack') ?></span>
</div>

<!-- Modal New Folder -->
<div id="newFolderModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:440px;">
        <div style="background:var(--bg-secondary); border-radius:var(--radius-lg); border:1px solid var(--border-subtle); overflow:hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.5); animation: modalIn 0.2s ease-out;">
            <div style="padding:var(--space-5) var(--space-6); border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:var(--space-3);">
                    <div style="width:36px; height:36px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:18px; height:18px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                    </div>
                    <div>
                        <div style="font-size:15px; font-weight:600;"><?= t('files.create_folder') ?></div>
                        <div style="font-size:11px; color:var(--text-muted);">Créer un nouveau dossier</div>
                    </div>
                </div>
                <button onclick="document.getElementById('newFolderModal').style.display='none'" style="width:32px; height:32px; border-radius:var(--radius-sm); border:none; background:transparent; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px; transition:all 0.15s;">×</button>
            </div>
            <form id="newFolderForm">
                <div style="padding:var(--space-6);">
                    <div class="form-group">
                        <label class="form-label">Nom du dossier</label>
                        <input type="text" name="name" class="form-input" required autofocus placeholder="Mon dossier" style="font-size:14px; padding:var(--space-3) var(--space-4);" autocomplete="off">
                    </div>
                    <?php if ($parentId): ?>
                    <input type="hidden" name="parent_id" value="<?= $parentId ?>">
                    <?php endif; ?>
                </div>
                <div style="padding:var(--space-4) var(--space-6); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:var(--space-3); background:rgba(0,0,0,0.15);">
                    <button type="button" onclick="document.getElementById('newFolderModal').style.display='none'" class="btn btn-ghost" style="font-size:13px;">Annuler</button>
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:var(--space-2) var(--space-5);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                        Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
@keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>

<!-- Modal Share -->
<div id="shareModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= t('shares.create') ?></span>
                <button class="btn btn-ghost" onclick="document.getElementById('shareModal').style.display='none'">×</button>
            </div>
            <div class="card-body">
                <form id="shareForm">
                    <input type="hidden" name="file_id" id="shareFileId">
                    <div class="form-group">
                        <label class="form-label"><?= t('shares.expires') ?></label>
                        <input type="datetime-local" name="expires_at" class="form-input">
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
                setTimeout(() => location.reload(), 800);
            } else {
                status.className = 'file-status error';
                status.textContent = '❌';
                label.textContent = data.error || 'Error';
            }
        };
        xhr.onerror = function() {
            status.className = 'file-status error';
            status.textContent = '❌';
        };
        xhr.open('POST', '/files/upload');
        xhr.send(formData);
    } catch(e) {
        status.className = 'file-status error';
        status.textContent = '❌';
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
    if (data.success) location.reload();
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
    }
});
function copyShareLink() {
    const input = document.getElementById('shareLink');
    input.select();
    document.execCommand('copy');
    alert('<?= t('shares.link_copied') ?>');
}

// Folder
document.getElementById('newFolderForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    form.append('_token', '<?= \Saec\Core\Session::csrfToken() ?>');
    const res = await fetch('/folders', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Erreur lors de la création');
});
async function deleteFolder(id) {
    if (!confirm('Delete folder?')) return;
    const form = new FormData();
    form.append('_token', '<?= \Saec\Core\Session::csrfToken() ?>');
    const res = await fetch(`/folders/${id}`, { method: 'DELETE', body: form });
    const data = await res.json();
    if (data.success) location.reload();
}
async function renameFolder(id, oldName) {
    const name = prompt('New name:', oldName);
    if (!name || name === oldName) return;
    const form = new FormData();
    form.append('name', name);
    form.append('_token', '<?= \Saec\Core\Session::csrfToken() ?>');
    const res = await fetch(`/folders/${id}/rename`, { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
}
</script>
