<?php
/** @var array $user */
$user = $user ?? [];
$providers = $providers ?? [];
$supportedTypes = $supportedTypes ?? [];
$pageTitle = 'Storage — Providers';
?>

<style>
.providers-wrap { max-width: 1100px; margin: 0 auto; padding: 32px 24px 48px; }
.s-card { background: #232632; border: 1px solid #3A4257; border-radius: 16px; padding: 0; transition: border-color 0.2s, box-shadow 0.2s; overflow: hidden; margin-bottom: 24px; }
.s-card:hover { border-color: rgba(37,99,235,0.3); }
.s-head { display: flex; align-items: center; gap: 14px; padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); }
.s-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.s-icon.blue { background: rgba(37,99,235,0.12); color: #60A5FA; }
.s-icon.cyan { background: rgba(6,182,212,0.12); color: #22D3EE; }
.s-icon.green { background: rgba(16,185,129,0.12); color: #34D399; }
.s-icon.red { background: rgba(239,68,68,0.12); color: #F87171; }
.s-body { padding: 24px; }
.s-fields { display: flex; flex-direction: column; gap: 16px; }
.s-field label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94A3B8; margin-bottom: 6px; }
.s-input { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #3A4257; background: #181A20; color: #F1F5F9; font-size: 14px; font-family: inherit; outline: none; transition: all 0.2s; }
.s-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
.s-input::placeholder { color: #64748B; }
.s-btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
.s-btn-primary { background: #2563EB; color: #fff; }
.s-btn-primary:hover { background: #3B82F6; box-shadow: 0 4px 12px rgba(37,99,235,0.3); transform: translateY(-1px); }
.s-btn-danger { background: rgba(239,68,68,0.12); color: #F87171; border: 1px solid rgba(239,68,68,0.25); }
.s-btn-danger:hover { background: rgba(239,68,68,0.2); }
.s-btn-warning { background: rgba(245,158,11,0.12); color: #FBBF24; border: 1px solid rgba(245,158,11,0.25); }
.s-btn-warning:hover { background: rgba(245,158,11,0.2); }
.s-btn-sm { padding: 6px 12px; font-size: 12px; }
.provider-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.provider-item { background: #1A1D27; border: 1px solid #3A4257; border-radius: 12px; padding: 20px; transition: all 0.2s; }
.provider-item:hover { border-color: rgba(37,99,235,0.4); box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.provider-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.provider-type-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.provider-name { font-size: 15px; font-weight: 600; color: #F1F5F9; }
.provider-meta { font-size: 12px; color: #64748B; margin-top: 2px; }
.provider-actions { display: flex; gap: 8px; margin-top: 16px; }
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-active { background: rgba(16,185,129,0.12); color: #34D399; }
.status-inactive { background: rgba(239,68,68,0.12); color: #F87171; }
.modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; }
.modal-overlay.active { display: flex; }
.modal { background: #232632; border: 1px solid #3A4257; border-radius: 16px; width: 100%; max-width: 600px; max-height: 80vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); }
.modal-body { padding: 24px; }
.modal-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.04); display: flex; gap: 12px; justify-content: flex-end; }
.config-section { margin-top: 16px; padding: 16px; background: #1A1D27; border-radius: 8px; border: 1px solid #3A4257; }
.config-section h4 { font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; }
.toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; z-index: 2000; transform: translateY(100px); opacity: 0; transition: all 0.3s; }
.toast.success { background: #059669; color: #fff; }
.toast.error { background: #DC2626; color: #fff; }
.toast.show { transform: translateY(0); opacity: 1; }
.empty-state { text-align: center; padding: 48px 24px; color: #64748B; }
.empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
</style>

<div class="providers-wrap">
    <!-- Header -->
    <div class="page-header" style="margin-bottom: 32px;">
        <div>
            <h1 style="display:flex; align-items:center; gap:var(--space-3); color: #F1F5F9;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, #06B6D4, #2563EB);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </span>
                Storage Providers
            </h1>
            <div style="margin-top: var(--space-1); color: #64748B; font-size: 14px;">
                Gestion des providers de stockage externes (S3, SFTP, FTP, WebDAV, Cloud)
            </div>
        </div>
        <div class="header-actions">
            <button class="s-btn s-btn-primary" onclick="openCreateModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter un provider
            </button>
        </div>
    </div>

    <!-- Providers Grid -->
    <?php if (empty($providers)): ?>
    <div class="s-card">
        <div class="empty-state">
            <i class="fas fa-cloud-upload-alt"></i>
            <h3 style="color: #F1F5F9; margin-bottom: 8px;">Aucun provider configuré</h3>
            <p style="color: #64748B; margin-bottom: 24px;">Ajoutez un provider pour commencer à stocker vos fichiers.</p>
            <button class="s-btn s-btn-primary" onclick="openCreateModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter votre premier provider
            </button>
        </div>
    </div>
    <?php else: ?>
    <div class="provider-grid">
        <?php foreach ($providers as $provider): ?>
        <?php
            $typeConfig = $supportedTypes[$provider['type']] ?? [];
            $typeIcon = $typeConfig['icon'] ?? '📦';
            $typeLabel = $typeConfig['label'] ?? $provider['type'];
            $isActive = (bool)($provider['is_active'] ?? 1);
            $isDefault = (bool)($provider['is_default'] ?? 0);
        ?>
        <div class="provider-item" id="provider-<?= $provider['id'] ?>">
            <div class="provider-header">
                <div class="provider-type-icon" style="background: rgba(6,182,212,0.12); color: #22D3EE;">
                    <?= $typeIcon ?>
                </div>
                <div style="flex: 1;">
                    <div class="provider-name"><?= htmlspecialchars($provider['name']) ?></div>
                    <div class="provider-meta">
                        <?= $typeLabel ?>
                        <?php if ($isDefault): ?>
                        <span style="color: #FBBF24;">· Par défaut</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="status-badge <?= $isActive ? 'status-active' : 'status-inactive' ?>">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                    <?= $isActive ? 'Actif' : 'Inactif' ?>
                </div>
            </div>
            <div class="provider-actions">
                <button class="s-btn s-btn-sm s-btn-primary" onclick="testProvider(<?= $provider['id'] ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Tester
                </button>
                <button class="s-btn s-btn-sm s-btn-warning" onclick="editProvider(<?= $provider['id'] ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Modifier
                </button>
                <button class="s-btn s-btn-sm s-btn-danger" onclick="deleteProvider(<?= $provider['id'] ?>, '<?= htmlspecialchars($provider['name']) ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Supprimer
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Supported Types Reference -->
    <div class="s-card" style="margin-top: 32px;">
        <div class="s-head">
            <div class="s-icon cyan"><i class="fas fa-info-circle"></i></div>
            <div class="s-head-text">
                <h3 style="color: #F1F5F9;">Types de providers supportés</h3>
            </div>
        </div>
        <div class="s-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                <?php foreach ($supportedTypes as $type => $info): ?>
                <div style="padding: 12px; background: #1A1D27; border-radius: 8px; border: 1px solid #3A4257;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <span style="font-size: 16px;"><?= $info['icon'] ?></span>
                        <span style="font-weight: 600; color: #F1F5F9; font-size: 13px;"><?= $info['label'] ?></span>
                    </div>
                    <p style="color: #64748B; font-size: 11px; margin: 0;"><?= $info['description'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal-overlay" id="providerModal">
    <div class="modal">
        <div class="modal-header">
            <h3 style="color: #F1F5F9; margin: 0;" id="modalTitle">Ajouter un provider</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: #64748B; cursor: pointer; font-size: 20px;">&times;</button>
        </div>
        <div class="modal-body">
            <form id="providerForm" onsubmit="saveProvider(event)">
                <input type="hidden" name="id" id="providerId" value="">
                <div class="s-fields">
                    <div class="s-field">
                        <label>Nom du provider</label>
                        <input type="text" name="name" class="s-input" placeholder="Mon S3 Bucket" required>
                    </div>
                    <div class="s-field">
                        <label>Type</label>
                        <select name="type" class="s-input" id="providerTypeSelect" onchange="updateConfigFields(this.value)" required>
                            <option value="">-- Sélectionner un type --</option>
                            <?php foreach ($supportedTypes as $type => $info): ?>
                            <option value="<?= $type ?>"><?= $info['icon'] ?> <?= $info['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="s-field">
                        <label>
                            <input type="checkbox" name="is_active" value="1" checked style="margin-right: 8px;">
                            Provider actif
                        </label>
                    </div>
                    <div class="s-field">
                        <label>
                            <input type="checkbox" name="is_default" value="1" style="margin-right: 8px;">
                            Provider par défaut
                        </label>
                    </div>
                </div>

                <div class="config-section" id="configSection">
                    <h4>Configuration</h4>
                    <div id="configFields">
                        <p style="color: #64748B; font-size: 13px;">Sélectionnez un type pour afficher les champs de configuration.</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="s-btn s-btn-warning" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="s-btn s-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Test Result Toast -->
<div class="toast" id="toast"></div>

<script>
// Move modal to body to escape .main stacking context
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('providerModal');
    if (modal) document.body.appendChild(modal);
    const toast = document.getElementById('toast');
    if (toast) document.body.appendChild(toast);
});

const supportedTypes = <?= json_encode($supportedTypes) ?>;
const configFieldsMap = {
    local: [
        { name: 'root_dir', label: 'Chemin racine', type: 'text', placeholder: '/volume1/web/cloud/storage/uploads', required: true }
    ],
    s3: [
        { name: 'endpoint', label: 'Endpoint', type: 'text', placeholder: 's3.amazonaws.com', required: true },
        { name: 'access_key', label: 'Access Key', type: 'text', placeholder: 'AKIA...', required: true },
        { name: 'secret_key', label: 'Secret Key', type: 'password', placeholder: '••••••••', required: true },
        { name: 'bucket', label: 'Bucket', type: 'text', placeholder: 'my-bucket', required: true },
        { name: 'region', label: 'Region', type: 'text', placeholder: 'us-east-1', required: true },
        { name: 'path_style', label: 'Path Style (MinIO, etc.)', type: 'checkbox' },
        { name: 'use_ssl', label: 'Utiliser SSL', type: 'checkbox', checked: true }
    ],
    sftp: [
        { name: 'host', label: 'Host', type: 'text', placeholder: 'sftp.example.com', required: true },
        { name: 'port', label: 'Port', type: 'number', placeholder: '22', value: 22, required: true },
        { name: 'username', label: 'Username', type: 'text', placeholder: 'user', required: true },
        { name: 'password', label: 'Password', type: 'password', placeholder: '••••••••' },
        { name: 'private_key', label: 'Clé privée SSH (optionnel)', type: 'textarea', placeholder: '-----BEGIN RSA PRIVATE KEY-----' },
        { name: 'root_dir', label: 'Chemin racine', type: 'text', placeholder: '/home/user/files' }
    ],
    ftp: [
        { name: 'host', label: 'Host', type: 'text', placeholder: 'ftp.example.com', required: true },
        { name: 'port', label: 'Port', type: 'number', placeholder: '21', value: 21, required: true },
        { name: 'username', label: 'Username', type: 'text', placeholder: 'user', required: true },
        { name: 'password', label: 'Password', type: 'password', placeholder: '••••••••', required: true },
        { name: 'root_dir', label: 'Chemin racine', type: 'text', placeholder: '/public_html' },
        { name: 'passive', label: 'Mode passif', type: 'checkbox', checked: true },
        { name: 'ssl', label: 'FTPS (SSL/TLS)', type: 'checkbox' }
    ],
    webdav: [
        { name: 'endpoint', label: 'URL WebDAV', type: 'text', placeholder: 'https://cloud.example.com/remote.php/dav/files/user/', required: true },
        { name: 'username', label: 'Username', type: 'text', placeholder: 'user', required: true },
        { name: 'password', label: 'Password', type: 'password', placeholder: '••••••••', required: true },
        { name: 'root_dir', label: 'Chemin racine (optionnel)', type: 'text', placeholder: '/' }
    ],
    gdrive: [
        { name: 'client_id', label: 'Client ID', type: 'text', placeholder: '123456789.apps.googleusercontent.com', required: true },
        { name: 'client_secret', label: 'Client Secret', type: 'password', placeholder: '••••••••', required: true },
        { name: 'refresh_token', label: 'Refresh Token', type: 'password', placeholder: '••••••••', required: true },
        { name: 'root_dir', label: 'Dossier racine ID', type: 'text', placeholder: 'root' }
    ],
    dropbox: [
        { name: 'access_token', label: 'Access Token', type: 'password', placeholder: 'sl.XXXX...', required: true },
        { name: 'root_dir', label: 'Chemin racine', type: 'text', placeholder: '/' }
    ],
    onedrive: [
        { name: 'client_id', label: 'Client ID', type: 'text', placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', required: true },
        { name: 'client_secret', label: 'Client Secret', type: 'password', placeholder: '••••••••', required: true },
        { name: 'refresh_token', label: 'Refresh Token', type: 'password', placeholder: '••••••••', required: true },
        { name: 'root_dir', label: 'Dossier racine', type: 'text', placeholder: 'root' }
    ]
};

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter un provider';
    document.getElementById('providerId').value = '';
    document.getElementById('providerForm').reset();
    document.getElementById('configFields').innerHTML = '<p style="color: #64748B; font-size: 13px;">Sélectionnez un type pour afficher les champs de configuration.</p>';
    document.getElementById('providerModal').classList.add('active');
}

function editProvider(id) {
    fetch(`/admin/storage/providers/${id}/json`)
        .then(r => r.json())
        .then(provider => {
            document.getElementById('modalTitle').textContent = 'Modifier le provider';
            document.getElementById('providerId').value = provider.id;
            document.querySelector('[name="name"]').value = provider.name;
            document.getElementById('providerTypeSelect').value = provider.type;
            document.querySelector('[name="is_active"]').checked = provider.is_active;
            document.querySelector('[name="is_default"]').checked = provider.is_default;
            updateConfigFields(provider.type, JSON.parse(provider.config || '{}'));
            document.getElementById('providerModal').classList.add('active');
        });
}

function closeModal() {
    document.getElementById('providerModal').classList.remove('active');
}

function updateConfigFields(type, values = {}) {
    const fields = configFieldsMap[type] || [];
    const container = document.getElementById('configFields');

    if (fields.length === 0) {
        container.innerHTML = '<p style="color: #64748B; font-size: 13px;">Pas de configuration requise pour ce type.</p>';
        return;
    }

    let html = '<div class="s-fields">';
    fields.forEach(field => {
        const val = values[field.name] ?? field.value ?? '';
        const checked = values[field.name] !== undefined ? values[field.name] : (field.checked || false);

        if (field.type === 'checkbox') {
            html += `
                <div class="s-field">
                    <label style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: normal; cursor: pointer;">
                        <input type="checkbox" name="config[${field.name}]" value="1" ${checked ? 'checked' : ''}>
                        ${field.label}
                    </label>
                </div>`;
        } else if (field.type === 'textarea') {
            html += `
                <div class="s-field">
                    <label>${field.label}</label>
                    <textarea name="config[${field.name}]" class="s-input" rows="3" placeholder="${field.placeholder || ''}">${val}</textarea>
                </div>`;
        } else {
            html += `
                <div class="s-field">
                    <label>${field.label}</label>
                    <input type="${field.type}" name="config[${field.name}]" class="s-input" value="${val}" placeholder="${field.placeholder || ''}" ${field.required ? 'required' : ''}>
                </div>`;
        }
    });
    html += '</div>';
    container.innerHTML = html;
}

function saveProvider(e) {
    e.preventDefault();
    const form = document.getElementById('providerForm');
    const formData = new FormData(form);

    // Collect config fields into a JSON object
    const config = {};
    formData.forEach((value, key) => {
        if (key.startsWith('config[')) {
            const field = key.replace('config[', '').replace(']', '');
            config[field] = value;
        }
    });

    // Create a new FormData to send properly
    const submitData = new FormData();
    submitData.append('name', formData.get('name'));
    submitData.append('type', formData.get('type'));
    submitData.append('config', JSON.stringify(config));
    submitData.append('is_active', formData.has('is_active') ? '1' : '0');
    submitData.append('is_default', formData.has('is_default') ? '1' : '0');

    const id = document.getElementById('providerId').value;
    const url = id ? `/admin/storage/providers/${id}` : '/admin/storage/providers';
    const method = id ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        body: submitData
    })
    .then(r => r.json())
    .then(result => {
        if (result.error) {
            showToast(result.error, 'error');
        } else {
            showToast('Provider enregistré', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(err => showToast('Erreur: ' + err.message, 'error'));
}

function testProvider(id) {
    showToast('Test en cours...', 'success');
    fetch(`/admin/storage/providers/${id}/test`, { method: 'POST' })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                showToast(`✓ ${result.message} (${result.latency_ms}ms)`, 'success');
            } else {
                showToast(`✗ ${result.message}`, 'error');
            }
        })
        .catch(err => showToast('Erreur: ' + err.message, 'error'));
}

function deleteProvider(id, name) {
    if (!confirm(`Supprimer le provider "${name}" ?`)) return;

    fetch(`/admin/storage/providers/${id}`, { method: 'DELETE' })
        .then(r => r.json())
        .then(result => {
            if (result.error) {
                showToast(result.error, 'error');
            } else {
                document.getElementById(`provider-${id}`).remove();
                showToast('Provider supprimé', 'success');
            }
        })
        .catch(err => showToast('Erreur: ' + err.message, 'error'));
}

function showToast(message, type) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type + ' show';
    setTimeout(() => toast.classList.remove('show'), 3000);
}
</script>
