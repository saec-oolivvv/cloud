<?php
/** @var array $user */
/** @var array $file */
$user = $user ?? [];
$file = $file ?? [];

$pageTitle = $file['original_name'] ?? 'Fichier';

function formatSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
<style>
    .file-viewer { max-width: 960px; margin: 0 auto; padding: 32px 24px 48px; }
    .file-card { background: #232632; border: 1px solid #3A4257; border-radius: 16px; padding: 24px; }
    .file-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
    .file-icon { width: 64px; height: 64px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
    .file-info h1 { font-size: 20px; font-weight: 700; color: #F1F5F9; margin-bottom: 4px; }
    .file-info p { font-size: 13px; color: #64748B; margin: 0; }
    .file-meta { display: flex; gap: 24px; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.04); }
    .file-meta div { font-size: 13px; color: #94A3B8; }
    .file-meta div span { color: #F1F5F9; font-weight: 600; }
    .action-btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
    .action-btn.primary { background: #2563EB; color: #fff; }
    .action-btn.secondary { background: rgba(37,99,235,0.12); color: #60A5FA; border: 1px solid rgba(37,99,235,0.25); }
</style>

<div class="file-viewer">
    <div class="file-card">
        <div class="file-header">
            <div class="file-icon purple"><i class="fas fa-file"></i></div>
            <div class="file-info">
                <h1><?= htmlspecialchars($file['original_name'] ?? 'Fichier') ?></h1>
                <p><?= formatSize($file['size'] ?? 0) ?></p>
            </div>
        </div>

        <div class="file-meta">
            <div><span>ID:</span> <?= htmlspecialchars($file['id'] ?? '') ?></div>
            <div><span>Type:</span> <?= htmlspecialchars($file['mime_type'] ?? '') ?></div>
            <div><span>Téléchargé:</span> <?= date('d/m/Y H:i', strtotime($file['created_at'] ?? 'now')) ?></div>
        </div>

        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.04);">
            <a href="/files/<?= htmlspecialchars($file['id'] ?? '') ?>/download" class="action-btn primary">
                <i class="fas fa-download"></i> Télécharger
            </a>
            <a href="/files/<?= htmlspecialchars($file['id'] ?? '') ?>/view" class="action-btn secondary">
                <i class="fas fa-eye"></i> Prévisualiser
            </a>
        </div>
    </div>
</div>