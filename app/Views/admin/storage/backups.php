<?php
/** @var array $user */
/** @var array $backups */
/** @var array $providers */
$user = $user ?? [];
$backups = $backups ?? [];
$providers = $providers ?? [];
$pageTitle = 'Storage — Backups';

$statusColors = [
    'pending' => ['#F59E0B', '#FEF3C7'],
    'running' => ['#3B82F6', '#DBEAFE'],
    'completed' => ['#10B981', '#D1FAE5'],
    'failed' => ['#EF4444', '#FEE2E2'],
];

$typeLabels = [
    'full' => 'Full Snapshot',
    'incremental' => 'Incremental',
    'files_only' => 'Files Only',
    'db_only' => 'DB Only',
];
?>

<style>
.backups-wrap { max-width: 1100px; margin: 0 auto; padding: 32px 24px 48px; }
.s-card { background: #232632; border: 1px solid #3A4257; border-radius: 16px; padding: 0; transition: border-color 0.2s, box-shadow 0.2s; overflow: hidden; margin-bottom: 24px; }
.s-card:hover { border-color: rgba(37,99,235,0.3); }
.s-head { display: flex; align-items: center; gap: 14px; padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); }
.s-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.s-icon.blue { background: rgba(37,99,235,0.12); color: #60A5FA; }
.s-icon.emerald { background: rgba(16,185,129,0.12); color: #34D399; }
.s-icon.rose { background: rgba(239,68,68,0.12); color: #F87171; }
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
.s-btn-sm { padding: 6px 12px; font-size: 12px; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.04); text-align: left; font-size: 13px; color: #F1F5F9; }
th { color: #94A3B8; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; }
tr:hover { background: rgba(255,255,255,0.02); }
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.empty-state { text-align: center; padding: 48px 24px; color: #64748B; }
.empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
.toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; z-index: 2000; transform: translateY(100px); opacity: 0; transition: all 0.3s; }
.toast.success { background: #059669; color: #fff; }
.toast.error { background: #DC2626; color: #fff; }
.toast.show { transform: translateY(0); opacity: 1; }
.modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; }
.modal-overlay.active { display: flex; }
.modal { background: #232632; border: 1px solid #3A4257; border-radius: 16px; width: 100%; max-width: 500px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); }
.modal-body { padding: 24px; }
.modal-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.04); display: flex; gap: 12px; justify-content: flex-end; }
.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-mini { background: #1A1D27; border: 1px solid #3A4257; border-radius: 10px; padding: 16px; text-align: center; }
.kpi-mini-value { font-size: 24px; font-weight: 700; color: #F1F5F9; }
.kpi-mini-label { font-size: 11px; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }
</style>

<div class="backups-wrap">
    <!-- Header -->
    <div class="page-header" style="margin-bottom: 32px;">
        <div>
            <h1 style="display:flex; align-items:center; gap:var(--space-3); color: #F1F5F9;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, #10B981, #059669);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                </span>
                Backups
            </h1>
            <div style="margin-top: var(--space-1); color: #64748B; font-size: 14px;">
                Gestion des sauvegardes automatiques et manuelles
            </div>
        </div>
        <div class="header-actions">
            <button class="s-btn s-btn-primary" onclick="openCreateModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nouvelle backup
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-row">
        <div class="kpi-mini">
            <div class="kpi-mini-value" style="color: #F1F5F9;"><?= count($backups) ?></div>
            <div class="kpi-mini-label">Total</div>
        </div>
        <div class="kpi-mini">
            <div class="kpi-mini-value" style="color: #10B981;"><?= count(array_filter($backups, fn($b) => $b['status'] === 'completed')) ?></div>
            <div class="kpi-mini-label">Succès</div>
        </div>
        <div class="kpi-mini">
            <div class="kpi-mini-value" style="color: #EF4444;"><?= count(array_filter($backups, fn($b) => $b['status'] === 'failed')) ?></div>
            <div class="kpi-mini-label">Échecs</div>
        </div>
        <div class="kpi-mini">
            <div class="kpi-mini-value" style="color: #F59E0B;"><?= count(array_filter($backups, fn($b) => $b['status'] === 'pending' || $b['status'] === 'running')) ?></div>
            <div class="kpi-mini-label">En cours</div>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="s-card">
        <div class="s-head">
            <div class="s-icon rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px; height:20px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div class="s-head-text">
                <h3 style="color: #F1F5F9; margin: 0;">Historique des backups</h3>
            </div>
        </div>
        <div class="s-body">
            <?php if (empty($backups)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px; height:48px; margin: 0 auto 16px; opacity: 0.3;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <h3 style="color: #F1F5F9; margin-bottom: 8px;">Aucune backup</h3>
                <p style="color: #64748B; margin-bottom: 24px;">Créez votre première backup pour protéger vos données.</p>
                <button class="s-btn s-btn-primary" onclick="openCreateModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Créer une backup
                </button>
            </div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Provider</th>
                            <th>Type</th>
                            <th>Tenant</th>
                            <th>Taille</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $i => $b): ?>
                        <?php
                            $statusColor = $statusColors[$b['status']] ?? ['#64748B', '#F1F5F9'];
                            $typeLabel = $typeLabels[$b['type']] ?? $b['type'];
                            $size = $b['file_size'] ? $this->formatSize($b['file_size']) : '-';
                            $duration = $b['duration_seconds'] ? $b['duration_seconds'] . 's' : '-';
                        ?>
                        <tr style="animation: fadeUp <?= 0.05 + $i * 0.03 ?>s ease-out;">
                            <td style="color: #94A3B8;">#<?= $b['id'] ?></td>
                            <td><?= htmlspecialchars($b['provider_name'] ?? 'N/A') ?></td>
                            <td><span style="color: #60A5FA;"><?= $typeLabel ?></span></td>
                            <td><?= htmlspecialchars($b['tenant_name'] ?? 'System') ?></td>
                            <td><?= $size ?></td>
                            <td><?= $duration ?></td>
                            <td>
                                <span class="status-badge" style="background: <?= $statusColor[1] ?>; color: <?= $statusColor[0] ?>;">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                    <?= ucfirst($b['status']) ?>
                                </span>
                            </td>
                            <td style="color: #94A3B8;"><?= date('d/m/Y H:i', strtotime($b['created_at'] ?? 'now')) ?></td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <?php if ($b['status'] === 'completed'): ?>
                                    <button class="s-btn s-btn-sm s-btn-primary" onclick="restoreBackup(<?= $b['id'] ?>)" title="Restaurer">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                    </button>
                                    <?php endif; ?>
                                    <button class="s-btn s-btn-sm s-btn-danger" onclick="deleteBackup(<?= $b['id'] ?>)" title="Supprimer">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal-overlay" id="backupModal">
    <div class="modal">
        <div class="modal-header">
            <h3 style="color: #F1F5F9; margin: 0;">Nouvelle backup</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: #64748B; cursor: pointer; font-size: 20px;">&times;</button>
        </div>
        <div class="modal-body">
            <form id="backupForm" onsubmit="createBackup(event)">
                <div class="s-fields">
                    <div class="s-field">
                        <label>Provider</label>
                        <select name="provider_id" class="s-input" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($providers as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="s-field">
                        <label>Type de backup</label>
                        <select name="type" class="s-input" required>
                            <option value="full">Full Snapshot (DB + fichiers)</option>
                            <option value="incremental">Incremental (depuis dernier backup)</option>
                            <option value="files_only">Files Only</option>
                            <option value="db_only">DB Only</option>
                        </select>
                    </div>
                    <div class="s-field">
                        <label>Tenant (optionnel)</label>
                        <select name="tenant_id" class="s-input">
                            <option value="">-- Tous les tenants --</option>
                        </select>
                    </div>
                    <div class="s-field">
                        <label style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: normal; cursor: pointer;">
                            <input type="checkbox" name="encrypted" value="1" checked>
                            Chiffrer le backup (AES-256-GCM)
                        </label>
                    </div>
                    <div class="s-field">
                        <label>Compression</label>
                        <select name="compression" class="s-input">
                            <option value="gzip">Gzip</option>
                            <option value="zstd">Zstandard (meilleur ratio)</option>
                            <option value="none">Aucune</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="s-btn s-btn-danger" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="s-btn s-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
                        Lancer la backup
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
// Move modal to body to escape .main stacking context
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('backupModal');
    if (modal) document.body.appendChild(modal);
});

function openCreateModal() {
    document.getElementById('backupForm').reset();
    document.getElementById('backupModal').classList.add('active');
}

function closeModal() {
    document.getElementById('backupModal').classList.remove('active');
}

function createBackup(e) {
    e.preventDefault();
    const form = document.getElementById('backupForm');
    const formData = new FormData(form);

    fetch('/admin/storage/backups', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(result => {
        if (result.error) {
            showToast(result.error, 'error');
        } else {
            showToast('Backup lancée ! ID: ' + result.backup_id, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(err => showToast('Erreur: ' + err.message, 'error'));
}

function restoreBackup(id) {
    if (!confirm('Restaurer cette backup ? Les données actuelles seront écrasées.')) return;

    showToast('Restauration en cours...', 'success');
    fetch(`/admin/storage/backups/${id}/restore`, { method: 'POST' })
        .then(r => r.json())
        .then(result => {
            if (result.error) {
                showToast(result.error, 'error');
            } else {
                showToast('Restauration terminée !', 'success');
            }
        })
        .catch(err => showToast('Erreur: ' + err.message, 'error'));
}

function deleteBackup(id) {
    if (!confirm('Supprimer cette backup ?')) return;

    fetch(`/admin/storage/backups/${id}`, { method: 'DELETE' })
        .then(r => r.json())
        .then(result => {
            if (result.error) {
                showToast(result.error, 'error');
            } else {
                showToast('Backup supprimée', 'success');
                setTimeout(() => location.reload(), 1000);
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
