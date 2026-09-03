<?php
$user = $user ?? [];
$users = $users ?? [];
$tenant = $tenant ?? [];
?>

<div class="page-header">
    <div>
        <h1><?= t('admin.users') ?></h1>
        <div class="breadcrumb" style="font-size:13px; color:var(--text-secondary);">
            <a href="/admin/tenants" style="color:var(--text-secondary);">Tenants</a> /
            <a href="/admin/tenants/<?= $tenant['id'] ?? '' ?>" style="color:var(--text-secondary);"><?= htmlspecialchars($tenant['name'] ?? '') ?></a> /
            <span>Users</span>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="document.getElementById('createUserModal').style.display='flex'">
            + <?= t('admin.create_user') ?>
        </button>
    </div>
</div>

<div class="card">
    <?php if (empty($users)): ?>
    <div style="text-align:center; padding:var(--space-8); color:var(--text-muted);">No users</div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr><th>Email</th><th>Role</th><th>Files</th><th>Last Login</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['email']) ?></strong></td>
                <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-warning' : 'badge-neutral' ?>"><?= $u['role'] ?></span></td>
                <td><?= (int)($u['file_count'] ?? 0) ?></td>
                <td style="color:var(--text-muted);"><?= $u['last_login'] ? date('d/m H:i', strtotime($u['last_login'])) : 'Never' ?></td>
                <td>
                    <?php if ($u['active']): ?>
                    <span class="badge badge-success">Active</span>
                    <?php else: ?>
                    <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted);">
    <?= t('app.copyright') ?>
</div>

<!-- Modal Create User -->
<div id="createUserModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= t('admin.create_user') ?></span>
                <button class="btn btn-ghost" onclick="document.getElementById('createUserModal').style.display='none'">×</button>
            </div>
            <div class="card-body">
                <form id="createUserForm">
                    <div class="form-group">
                        <label class="form-label"><?= t('auth.email') ?></label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('auth.password') ?></label>
                        <input type="password" name="password" class="form-input" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-input">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;"><?= t('common.confirm') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('createUserForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    const res = await fetch('/admin/tenants/<?= $tenant['id'] ?? 0 ?>/users', { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Error');
});
</script>
