<?php
$user = $user ?? [];
$shares = $shares ?? [];
$csrf = \Saec\Core\Session::csrfToken();
?>

<!-- Page Header -->
<div class="page-header animate-fade-in">
    <div>
        <h1 style="display:flex; align-items:center; gap:var(--space-3);">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--emerald-400), var(--cyan-400));">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px; height:20px;"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </span>
            <?= t('shares.title') ?>
        </h1>
        <div class="breadcrumb" style="margin-top:var(--space-1);">
            <a href="/dashboard" style="color:var(--text-secondary);">Accueil</a> / <span>Partages</span>
        </div>
    </div>
    <div class="header-actions">
        <span class="badge badge-info"><?= count($shares) ?> actifs</span>
    </div>
</div>

<?php if (empty($shares)): ?>
<div class="card animate-scale-in" style="text-align:center; padding:var(--space-10);">
    <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(6,182,212,0.1)); display:flex; align-items:center; justify-content:center; margin:0 auto var(--space-4);">
        <svg viewBox="0 0 24 24" fill="none" stroke="var(--emerald-400)" stroke-width="1.5" style="width:36px; height:36px; opacity:0.6;"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
    </div>
    <div style="font-size:18px; font-weight:600; margin-bottom:var(--space-2); color:var(--text-primary);">Aucun partage actif</div>
    <div style="font-size:13px; color:var(--text-muted); margin-bottom:var(--space-5); max-width:360px; margin-left:auto; margin-right:auto;">
        Partagez des fichiers depuis la page Fichiers pour créer des liens d'accès sécurisés.
    </div>
    <a href="/files" class="btn btn-primary" style="gap:var(--space-2);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        <?= t('nav.files') ?>
    </a>
</div>
<?php else: ?>

<!-- Shares Stats -->
<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:var(--space-4); margin-bottom:var(--space-6);">
    <?php
    $totalDownloads = 0;
    $expiredShares = 0;
    foreach ($shares as $s) {
        $totalDownloads += (int)($s['download_count'] ?? 0);
        if ($s['expires_at'] && strtotime($s['expires_at']) < time()) $expiredShares++;
    }
    ?>
    <div class="kpi-card emerald">
        <div class="kpi-label">Total partages</div>
        <div class="kpi-value" style="color:var(--emerald-400);"><?= count($shares) ?></div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-label">Téléchargements</div>
        <div class="kpi-value" style="color:var(--cyan-400);"><?= $totalDownloads ?></div>
    </div>
    <div class="kpi-card <?= $expiredShares > 0 ? 'rose' : 'amber' ?>">
        <div class="kpi-label">Expirés</div>
        <div class="kpi-value"><?= $expiredShares ?></div>
    </div>
</div>

<!-- Shares List -->
<div style="display:flex; flex-direction:column; gap:var(--space-3);">
<?php foreach ($shares as $share):
    $isExpired = $share['expires_at'] && strtotime($share['expires_at']) < time();
    $isExpiring = $share['expires_at'] && !$isExpired && strtotime($share['expires_at']) < strtotime('+7 days');
    $token = $share['share_token'] ?? '';
?>
<div class="card animate-slide-up" style="padding:0; overflow:hidden;">
    <div style="height:3px; background:linear-gradient(90deg, <?= $isExpired ? 'var(--rose-500)' : ($isExpiring ? 'var(--amber-400)' : 'var(--emerald-400)') ?>, transparent);"></div>
    <div style="padding:var(--space-4) var(--space-5); display:flex; align-items:center; gap:var(--space-4);">
        <!-- Icon -->
        <div style="width:44px; height:44px; border-radius:var(--radius-md); background:<?= $isExpired ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.1)' ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="<?= $isExpired ? 'var(--rose-500)' : 'var(--emerald-400)' ?>" stroke-width="2" style="width:20px; height:20px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </div>

        <!-- Info -->
        <div style="flex:1; min-width:0;">
            <div style="font-size:14px; font-weight:500; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($share['original_name']) ?></div>
            <div style="display:flex; align-items:center; gap:var(--space-3); font-size:12px; color:var(--text-muted); margin-top:2px; flex-wrap:wrap;">
                <span><?= $this->formatSize($share['size'] ?? 0) ?></span>
                <span>·</span>
                <span style="display:flex; align-items:center; gap:4px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px; height:12px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <?= $share['download_count'] ?? 0 ?>
                </span>
                <?php if ($share['expires_at']): ?>
                <span>·</span>
                <span style="color:<?= $isExpired ? 'var(--rose-500)' : ($isExpiring ? 'var(--amber-400)' : 'var(--text-muted)') ?>">
                    <?= $isExpired ? 'Expiré' : 'Expire le ' . date('d/m/Y', strtotime($share['expires_at'])) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex; gap:var(--space-2); flex-shrink:0;">
            <button onclick="copyLink('<?= $token ?>')" class="btn btn-outline btn-sm" style="gap:var(--space-1);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px; height:12px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copier
            </button>
            <button onclick="revokeShare(<?= $share['id'] ?>)" class="btn btn-danger btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px; height:12px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Révoquer
            </button>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<!-- Footer -->
<div style="margin-top:var(--space-6); font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:var(--space-4); display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-2);">
    <span><?= t('app.copyright') ?></span>
    <span style="font-family:var(--font-mono); font-size:11px;">AES-256-GCM · <?= t('app.footer_stack') ?></span>
</div>

<!-- Toast -->
<div id="toast" class="toast" style="display:none;"></div>

<script>
function copyLink(token) {
    const link = `${window.location.origin}/share/${token}`;
    navigator.clipboard.writeText(link).then(() => {
        showToast('Lien copié dans le presse-papier', 'success');
    }).catch(() => {
        prompt('Copiez ce lien:', link);
    });
}

async function revokeShare(id) {
    if (!confirm('Révoquer ce partage ?')) return;
    const form = new FormData();
    form.append('_token', '<?= $csrf ?>');
    const res = await fetch(`/shares/${id}`, { method: 'DELETE', body: form });
    const data = await res.json();
    if (data.success) {
        showToast('Partage révoqué', 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(data.error || 'Erreur', 'error');
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 3000);
}
</script>
