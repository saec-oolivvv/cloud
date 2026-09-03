<?php
$user = $user ?? [];
$shares = $shares ?? [];
?>

<!-- Page Header -->
<div class="page-header">
    <h1><?= t('shares.title') ?></h1>
    <div class="header-actions">
        <span class="badge badge-neutral"><?= count($shares) ?> active</span>
    </div>
</div>

<?php if (empty($shares)): ?>
<div class="card" style="text-align:center; padding:var(--space-10);">
    <div style="font-size:48px; margin-bottom:var(--space-4); opacity:0.5;">🔗</div>
    <div style="font-size:16px; font-weight:500; margin-bottom:var(--space-2);"><?= t('shares.no_shares') ?></div>
    <div style="font-size:13px; color:var(--text-muted);">Share files from the Files page</div>
    <a href="/files" class="btn btn-primary" style="margin-top:var(--space-4);">📁 <?= t('nav.files') ?></a>
</div>
<?php else: ?>
<div class="card">
    <div class="file-list" style="border:none;">
        <?php foreach ($shares as $share): ?>
        <div class="file-row">
            <div class="row-icon">🔗</div>
            <div class="row-info">
                <div class="row-name"><?= htmlspecialchars($share['original_name']) ?></div>
                <div class="row-meta">
                    <?= $this->formatSize($share['size']) ?>
                    · <?= t('shares.downloads') ?>: <?= $share['download_count'] ?? 0 ?>
                    <?php if ($share['expires_at']): ?>
                    · <?= t('shares.expires') ?>: <?= date('d/m/Y', strtotime($share['expires_at'])) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row-actions">
                <span class="action-btn" onclick="copyLink('<?= $share['share_token'] ?>')" title="<?= t('shares.copy_link') ?>">📋</span>
                <span class="action-btn" onclick="revokeShare(<?= $share['id'] ?>)" title="<?= t('shares.revoke') ?>" style="color:var(--rose-400);">🗑</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span>AES-256-GCM · <?= t('app.footer_stack') ?></span>
</div>

<script>
function copyLink(token) {
    const link = `${window.location.origin}/share/${token}`;
    navigator.clipboard.writeText(link);
    alert('<?= t('shares.link_copied') ?>');
}
async function revokeShare(id) {
    if (!confirm('Revoke this share?')) return;
    const res = await fetch(`/shares/${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) location.reload();
}
</script>
