<?php
/** @var array $user */
/** @var array $mounts */
/** @var array $providers */
$user = $user ?? [];
$mounts = $mounts ?? [];
$providers = $providers ?? [];
$pageTitle = 'Storage — Mounts';
?>
<style>
    .mounts-wrap { max-width: 960px; margin: 0 auto; padding: 32px 24px 48px; }
    .s-card { background: #232632; border: 1px solid #3A4257; border-radius: 16px; padding: 0; transition: border-color 0.2s, box-shadow 0.2s; overflow: hidden; }
    .s-card:hover { border-color: rgba(37,99,235,0.3); }
    .s-head { display: flex; align-items: center; gap: 14px; padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); }
    .s-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .s-body { padding: 24px; }
    .s-fields { display: flex; flex-direction: column; gap: 16px; }
    .s.field label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94A3B8; margin-bottom: 6px; }
    .s-input { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #3A4257; background: #181A20; color: #F1F5F9; font-size: 14px; font-family: inherit; outline: none; transition: all 0.2s; }
    .s-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
    .s-input::placeholder { color: #64748B; }
    .s-btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .s-btn-primary { background: #2563EB; color: #fff; }
    .s-btn-primary:hover { background: #3B82F6; box-shadow: 0 4px 12px rgba(37,99,235,0.3); transform: translateY(-1px); }
    .s-btn-danger { background: rgba(239,68,68,0.12); color: #F87171; border: 1px solid rgba(239,68,68,0.25); }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px 16px; border-bottom: 1px solid #3A4257; text-align: left; font-size: 13px; color: #F1F5F9; }
    th { color: #94A3B8; }
</style>

<div class="mounts-wrap">
    <div class="s-card settings-full">
        <div class="s-head">
            <div class="s-icon cyan"><i class="fas fa-mountain"></i></div>
            <div class="s-head-text">
                <h3>Mounts Distants</h3>
                <p>Mounts de storage externe (lecture/écriture)</p>
            </div>
        </div>
        <div class="s-body">
            <div style="margin-bottom: 24px;">
                <label>Provider</label>
                <select class="s-input" id="mountProvider">
                    <option value="">-- Sélectionner un provider --</option>
                    <?php foreach ($providers as $p): ?>
                        <option value="<?= htmlspecialchars($p['id'] ?? '') ?>"><?= htmlspecialchars($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 24px;">
                <label>Type</label>
                <select class="s-input" id="mountType">
                    <option value="readwrite">Read/Write</option>
                    <option value="readonly">Read Only</option>
                </select>
            </div>
            <div style="margin-bottom: 24px;">
                <label>Remote Path</label>
                <input type="text" class="s-input" id="remotePath" placeholder="/">
            </div>
            <div style="margin-bottom: 24px;">
                <label>Local Alias</label>
                <input type="text" class="s-input" id="localAlias" placeholder="/remote">
            </div>
            <button class="s-btn s-btn-primary" style="width: 100%;" onclick="createMount()">
                <i class="fas fa-link"></i> Créer mount
            </button>
        </div>
    </div>

    <div class="s-card settings-full" style="margin-top: 32px;">
        <div class="s-head">
            <div class="s-icon rose"><i class="fas fa-share"></i></div>
            <div class="s-head-text">
                <h3>Mounts Actifs</h3>
                <p>Liste des mounts actuellement actifs</p>
            </div>
        </div>
        <div class="s-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Provider</th>
                        <th>Type</th>
                        <th>Remote Path</th>
                        <th>Local Alias</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mounts as $i => $m): ?>
                        <tr style="animation: fadeUp <?= 0.1 + $i * 0.05 ?>s ease-out;">
                            <td><?= htmlspecialchars($m['id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['provider_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['mount_type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['remote_path'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['local_alias'] ?? '') ?></td>
                            <td style="color: #10b981;">Actif</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top: 20px; color: #64748B; font-size: 13px;">Aucun mount trouvé</p>
        </div>
    </div>
</div>

<script>
function createMount() {
    const data = new FormData();
    data.append('provider_id', document.getElementById('mountProvider').value);
    data.append('mount_type', document.getElementById('mountType').value);
    data.append('remote_path', document.getElementById('remotePath').value);
    data.append('local_alias', document.getElementById('localAlias').value);

    fetch('/admin/storage/mounts', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: data
    })
    .then(r => r.json())
    .then(result => {
        if (result.error) {
            showToast(result.error, 'error');
        } else {
            showToast('Mount créé', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(err => showToast('Erreur: ' + err.message, 'error'));
}
</script>