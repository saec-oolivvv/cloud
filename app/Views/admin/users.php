<?php
$user = $user ?? [];
$users = $users ?? [];
$tenant = $tenant ?? [];
$csrf = \Saec\Core\Session::csrfToken();
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </span>
            <?= t('admin.users') ?>
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <a href="/admin" style="color:var(--text-secondary);">Admin</a> /
            <a href="/admin/tenants" style="color:var(--text-secondary);">Tenants</a> /
            <a href="/admin/tenants/<?= $tenant['id'] ?? '' ?>" style="color:var(--text-secondary);"><?= htmlspecialchars($tenant['name'] ?? '') ?></a> /
            <span>Users</span>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="document.getElementById('createUserModal').style.display='flex'" style="gap:var(--space-2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            <?= t('admin.create_user') ?>
        </button>
    </div>
</div>

<!-- Tenant Info Card -->
<div class="card" style="margin-bottom:var(--space-6); padding:var(--space-5) var(--space-6);">
    <div style="display:flex; align-items:center; gap:var(--space-4);">
        <div style="width:48px; height:48px; border-radius:var(--radius-lg); background:linear-gradient(135deg, var(--blue-500), var(--cyan-400)); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; color:#fff; flex-shrink:0;">
            <?= strtoupper(substr($tenant['name'] ?? 'T', 0, 2)) ?>
        </div>
        <div>
            <div style="font-size:16px; font-weight:600;"><?= htmlspecialchars($tenant['name'] ?? '') ?></div>
            <div style="font-size:12px; color:var(--text-muted);">
                <?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?> ·
                <?= $this->formatSize($tenant['storage_used'] ?? 0) ?> utilisé
            </div>
        </div>
    </div>
</div>

<!-- Users List -->
<?php if (empty($users)): ?>
<div class="card" style="text-align:center; padding:var(--space-10);">
    <div style="font-size:48px; margin-bottom:var(--space-4); opacity:0.4;">👤</div>
    <div style="font-size:16px; font-weight:600; margin-bottom:var(--space-2);">Aucun utilisateur</div>
    <div style="font-size:13px; color:var(--text-muted);">Ajoutez un premier utilisateur à ce tenant.</div>
</div>
<?php else: ?>
<div style="display:flex; flex-direction:column; gap:var(--space-3);">
<?php foreach ($users as $u):
    $initials = strtoupper(substr($u['email'], 0, 2));
    $isAdmin = $u['role'] === 'admin';
?>
<div class="card" style="padding:0; overflow:hidden;">
    <div style="padding:var(--space-4) var(--space-5); display:flex; align-items:center; gap:var(--space-4);">
        <!-- Avatar -->
        <div style="width:44px; height:44px; border-radius:50%; background:<?= $isAdmin ? 'linear-gradient(135deg, var(--amber-400), var(--rose-500))' : 'linear-gradient(135deg, var(--blue-500), var(--cyan-400))' ?>; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; color:#fff; flex-shrink:0;">
            <?= $initials ?>
        </div>

        <!-- Info -->
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; gap:var(--space-2); margin-bottom:2px;">
                <span style="font-size:14px; font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($u['email']) ?></span>
                <span class="badge <?= $isAdmin ? 'badge-warning' : 'badge-info' ?>" style="font-size:10px;">
                    <?= $isAdmin ? 'Admin' : 'User' ?>
                </span>
                <?php if (!$u['active']): ?>
                <span class="badge badge-danger" style="font-size:10px;">Inactif</span>
                <?php endif; ?>
            </div>
            <div style="font-size:12px; color:var(--text-muted);">
                <?= (int)($u['file_count'] ?? 0) ?> fichiers ·
                Dernière connexion: <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '<span style="color:var(--text-muted);">Jamais</span>' ?>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex; gap:var(--space-2);">
            <button class="btn btn-outline btn-sm" onclick="toggleUserActive(<?= $u['id'] ?>, <?= $u['active'] ? 'false' : 'true' ?>)" style="padding:var(--space-2) var(--space-3);">
                <?= $u['active'] ? 'Désactiver' : 'Activer' ?>
            </button>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4);">
    <?= t('app.copyright') ?>
</div>

<!-- Modal Create User -->
<div id="createUserModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:440px;">
        <div style="background:var(--bg-secondary); border-radius:var(--radius-lg); border:1px solid var(--border-subtle); overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,0.5); animation: modalIn 0.2s ease-out;">
            <div style="padding:var(--space-5) var(--space-6); border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:var(--space-3);">
                    <div style="width:36px; height:36px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--emerald-400), var(--cyan-400)); display:flex; align-items:center; justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:18px; height:18px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    </div>
                    <div>
                        <div style="font-size:15px; font-weight:600;">Nouvel utilisateur</div>
                        <div style="font-size:11px; color:var(--text-muted);">Ajouter un utilisateur au tenant</div>
                    </div>
                </div>
                <button onclick="document.getElementById('createUserModal').style.display='none'" style="width:32px; height:32px; border-radius:var(--radius-sm); border:none; background:transparent; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px;">×</button>
            </div>
            <form id="createUserForm">
                <div style="padding:var(--space-6); display:flex; flex-direction:column; gap:var(--space-4);">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required placeholder="utilisateur@exemple.com" style="font-size:14px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-input" required minlength="8" placeholder="Minimum 8 caractères" style="font-size:14px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rôle</label>
                        <select name="role" class="form-input">
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                </div>
                <div style="padding:var(--space-4) var(--space-6); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:var(--space-3); background:rgba(0,0,0,0.15);">
                    <button type="button" onclick="document.getElementById('createUserModal').style.display='none'" class="btn btn-ghost" style="font-size:13px;">Annuler</button>
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:var(--space-2) var(--space-5);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes modalIn { from { opacity:0; transform:scale(0.95) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
</style>

<script>
document.getElementById('createUserForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    form.append('_token', '<?= $csrf ?>');
    const res = await fetch('/admin/tenants/<?= $tenant['id'] ?? 0 ?>/users', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Erreur');
});

async function toggleUserActive(id, active) {
    const action = active ? 'activer' : 'désactiver';
    if (!confirm(`Voulez-vous ${action} cet utilisateur ?`)) return;
    const form = new FormData();
    form.append('_token', '<?= $csrf ?>');
    form.append('active', active ? '1' : '0');
    const res = await fetch(`/admin/users/${id}`, { method: 'PUT', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Erreur');
}
</script>
