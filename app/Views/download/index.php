<?php
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'en';
$availableLangs = $lang ? Saec\Core\Translation::getAvailable() : [];

$githubReleases = [];
$releasesUrl = 'https://api.github.com/repos/saec-oolivvv/cloud/releases';
$ctx = stream_context_create([
    'http' => [
        'header' => 'User-Agent: SAEC Cloud',
        'timeout' => 5
    ]
]);
$json = @file_get_contents($releasesUrl, false, $ctx);
if ($json) {
    $releases = json_decode($json, true);
    if (is_array($releases) && !empty($releases)) {
        $latest = $releases[0];
        if (isset($latest['assets'])) {
            foreach ($latest['assets'] as $asset) {
                $githubReleases[$asset['name']] = [
                    'url' => $asset['browser_download_url'],
                    'size' => $asset['size'],
                    'updated' => $asset['updated_at']
                ];
            }
        }
    }
}

$localFiles = [
    'SAEC.Sync_0.1.0_amd64.deb' => ['path' => '/download/SAEC.Sync_0.1.0_amd64.deb', 'local' => true],
    'SAEC.Sync-0.1.0-1.x86_64.rpm' => ['path' => '/download/SAEC.Sync-0.1.0-1.x86_64.rpm', 'local' => true],
    'saec-sync-linux-x64' => ['path' => '/download/saec-sync-linux-x64', 'local' => true],
    'SAEC Sync_0.1.0_x64_en-US.msi' => ['path' => '/download/SAEC Sync_0.1.0_x64_en-US.msi', 'local' => true],
    'install-macos.sh' => ['path' => '/install-macos.sh', 'local' => true],
];

function getDownloadInfo(string $filename, array $githubReleases, array $localFiles): array {
    if (isset($localFiles[$filename])) {
        return ['url' => $localFiles[$filename]['path'], 'source' => 'local', 'available' => true];
    }
    if (isset($githubReleases[$filename])) {
        return ['url' => $githubReleases[$filename]['url'], 'source' => 'github', 'available' => true];
    }
    return ['url' => '#', 'source' => 'none', 'available' => false];
}

$platforms = [
    ['id' => 'macos', 'name' => 'macOS — Installateur auto', 'icon' => '🍎', 'desc' => 'macOS 12+ (Apple Silicon & Intel) — Script bash d\'installation automatique', 'meta' => ['Architecture: Universal (ARM64 + x64)', 'Installation: /Applications', 'Script: install-macos.sh (full auto)', 'Gatekeeper: quarantaine retirée'], 'filename' => 'install-macos.sh', 'macos' => true],
    ['id' => 'windows-msi', 'name' => 'Windows — .msi', 'icon' => '🪟', 'desc' => 'Windows 10/11 (x64)', 'meta' => ['Build CI: GitHub Actions', 'Installation: per-user (pas d\'admin)', 'Signature: code signing'], 'filename' => 'SAEC Sync_0.1.0_x64_en-US.msi'],
    ['id' => 'linux-deb', 'name' => 'Linux — .deb', 'icon' => '🐧', 'desc' => 'Debian, Ubuntu, Mint, Pop!_OS, Elementary, et dérivés', 'meta' => ['Architecture: amd64', 'Taille: ~11 MB', 'Version: 0.1.0', 'Dépendances: webkit2gtk-4.1, gtk3, libayatana-appindicator3'], 'filename' => 'SAEC.Sync_0.1.0_amd64.deb'],
    ['id' => 'linux-rpm', 'name' => 'Linux — .rpm', 'icon' => '🐧', 'desc' => 'Fedora, RHEL, CentOS, AlmaLinux, Rocky, openSUSE', 'meta' => ['Architecture: x86_64', 'Taille: ~11 MB', 'Version: 0.1.0', 'Dépendances: webkit2gtk4.1, gtk3, libayatana-appindicator-gtk3'], 'filename' => 'SAEC.Sync-0.1.0-1.x86_64.rpm'],
    ['id' => 'linux-appimage', 'name' => 'Linux — AppImage', 'icon' => '🐧', 'desc' => 'Distribution universelle (toutes distros, sans installation)', 'meta' => ['Architecture: x86_64', 'Taille: ~11 MB', 'Version: 0.1.0', 'Exécution: chmod +x && ./SAEC-Sync.AppImage'], 'filename' => 'SAEC Sync-0.1.0.AppImage'],
    ['id' => 'linux-binary', 'name' => 'Linux — Binaire', 'icon' => '🐧', 'desc' => 'Binaire autonome (toutes distros)', 'meta' => ['Architecture: x86_64', 'Taille: ~35 MB', 'Version: 0.1.0', 'Exécution: chmod +x && ./saec-sync-linux-x64'], 'filename' => 'saec-sync-linux-x64'],
];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Télécharger SAEC Sync — SAEC Cloud</title>
    <meta name="description" content="Client de synchronisation desktop pour SAEC Cloud. Disponible sur Linux, Windows, macOS.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <style>
        body { background: var(--bg-primary); overflow-x: hidden; }
        .landing-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px; height: 72px;
            background: rgba(15, 17, 26, 0.85); backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border-subtle);
        }
        .landing-nav .nav-brand { display: flex; align-items: center; gap: 12px; }
        .landing-nav .nav-brand img { height: 28px; }
        .landing-nav .nav-brand span { font-weight: 700; font-size: 16px; letter-spacing: -0.02em; }
        .landing-nav .nav-links { display: flex; align-items: center; gap: 32px; }
        .landing-nav .nav-links a { font-size: 14px; color: var(--text-secondary); font-weight: 500; transition: color 0.15s; }
        .landing-nav .nav-links a:hover { color: var(--text-primary); }
        .landing-nav .nav-actions { display: flex; align-items: center; gap: 12px; }
        .download-hero {
            padding: 120px 24px 60px; text-align: center;
            background: radial-gradient(ellipse at top, rgba(37, 99, 235, 0.08) 0%, transparent 60%);
        }
        .download-hero .eyebrow { display: inline-block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: var(--blue-500); margin-bottom: 16px; }
        .download-hero h1 { font-size: clamp(28px, 4vw, 42px); font-weight: 800; letter-spacing: -0.03em; background: linear-gradient(135deg, var(--text-primary), var(--cyan-400)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-top: 8px; }
        .download-hero p { font-size: 16px; color: var(--text-secondary); max-width: 520px; margin: 16px auto 0; line-height: 1.6; }
        .download-section { padding: 20px 24px 80px; }
        .download-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 1100px; margin: 0 auto; }
        .download-card { background: var(--bg-card); border-radius: 16px; padding: 40px 28px; text-align: left; position: relative; border: 1px solid var(--border-subtle); transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1); display: flex; flex-direction: column; }
        .download-card:hover { border-color: rgba(37, 99, 235, 0.4); transform: translateY(-4px); box-shadow: 0 8px 40px rgba(0,0,0,0.3); }
        .download-icon { font-size: 48px; margin-bottom: 16px; }
        .download-name { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .download-desc { font-size: 13px; color: var(--text-secondary); margin-bottom: 20px; }
        .download-meta { list-style: none; margin: 0 0 24px; font-size: 12px; color: var(--text-muted); }
        .download-meta li { padding: 4px 0; display: flex; gap: 8px; align-items: center; }
        .download-meta i { color: var(--emerald-400); font-size: 10px; width: 14px; text-align: center; }
        .download-btn { width: 100%; margin-top: auto; }
        .download-coming { opacity: 0.5; pointer-events: none; }
        .download-coming .download-btn { background: var(--border-subtle); color: var(--text-muted); cursor: not-allowed; }
        .download-card.macos-highlight { border-color: rgba(0,255,136,0.3); background: linear-gradient(135deg, rgba(0,255,136,0.03), var(--bg-card)); }
        .download-card.macos-highlight:hover { border-color: rgba(0,255,136,0.5); box-shadow: 0 8px 40px rgba(0,255,136,0.1); }
        .features-section { padding: 60px 24px; max-width: 900px; margin: 0 auto; }
        .features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .feature { padding: 24px; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-subtle); }
        .feature h3 { font-size: 14px; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .feature h3 i { color: var(--blue-500); }
        .feature p { font-size: 13px; color: var(--text-secondary); line-height: 1.6; }
        .requirements { padding: 40px 24px; background: var(--bg-secondary); border-top: 1px solid var(--border-subtle); }
        .requirements-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 900px; margin: 0 auto; }
        .req-card { text-align: center; padding: 24px; }
        .req-card h4 { font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .req-card ul { list-style: none; font-size: 12px; color: var(--text-secondary); }
        .req-card li { padding: 2px 0; }
        .landing-footer { padding: 32px 24px; display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--text-muted); }
        .landing-footer a { color: var(--text-secondary); transition: color 0.15s; }
        .landing-footer a:hover { color: var(--blue-400); }
        .footer-links { display: flex; gap: 24px; }
        .mac-overlay { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); align-items: center; justify-content: center; }
        .mac-overlay.active { display: flex; }
        .mac-modal { background: #0a0f1e; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; max-width: 820px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 0; box-shadow: 0 25px 80px rgba(0,0,0,0.6); }
        .mac-modal::-webkit-scrollbar { width: 6px; }
        .mac-modal::-webkit-scrollbar-track { background: transparent; }
        .mac-modal::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
        .mac-header { display: flex; align-items: center; justify-content: space-between; padding: 28px 32px; border-bottom: 1px solid rgba(255,255,255,0.08); position: sticky; top: 0; background: #0a0f1e; z-index: 10; border-radius: 20px 20px 0 0; }
        .mac-header-left { display: flex; align-items: center; gap: 16px; }
        .mac-header-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a1f2e, #0d1117); border: 1px solid rgba(255,255,255,0.06); font-size: 28px; }
        .mac-header h2 { font-size: 22px; font-weight: 700; letter-spacing: -0.02em; }
        .mac-header .subtitle { font-size: 13px; color: rgba(255,255,255,0.6); margin-top: 2px; }
        .mac-close { width: 36px; height: 36px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); background: transparent; color: rgba(255,255,255,0.6); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: all 0.15s; }
        .mac-close:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .mac-body { padding: 32px; }
        .mac-step { display: flex; gap: 20px; margin-bottom: 28px; position: relative; }
        .mac-step:not(:last-child)::after { content: ''; position: absolute; left: 19px; top: 44px; bottom: -8px; width: 2px; background: rgba(255,255,255,0.06); }
        .mac-step-num { width: 40px; height: 40px; min-width: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: #00ff88; background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.2); }
        .mac-step-content { flex: 1; }
        .mac-step-content h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .mac-step-content p { font-size: 13px; color: rgba(255,255,255,0.6); line-height: 1.6; margin-bottom: 10px; }
        .mac-cmd { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #0d1117; border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #e6edf3; position: relative; margin-bottom: 8px; overflow-x: auto; }
        .mac-cmd code { flex: 1; white-space: nowrap; }
        .mac-copy { padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.5); cursor: pointer; font-size: 11px; font-family: 'Inter', sans-serif; transition: all 0.15s; white-space: nowrap; }
        .mac-copy:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .mac-copy.copied { background: rgba(0,255,136,0.15); color: #00ff88; border-color: rgba(0,255,136,0.3); }
        .mac-alert { display: flex; gap: 12px; padding: 14px 16px; border-radius: 10px; font-size: 13px; line-height: 1.5; margin-top: 10px; }
        .mac-alert.info { background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.2); color: #93c5fd; }
        .mac-alert.warn { background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2); color: #fcd34d; }
        .mac-alert.success { background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; }
        .mac-alert i { font-size: 16px; margin-top: 2px; }
        .mac-btn-row { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
        .mac-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer; border: none; }
        .mac-btn-primary { background: #00ff88; color: #0a0f1e; }
        .mac-btn-primary:hover { background: #00cc6a; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,255,136,0.3); }
        .mac-btn-ghost { background: transparent; color: rgba(255,255,255,0.6); border: 1px solid rgba(255,255,255,0.08); }
        .mac-btn-ghost:hover { background: rgba(255,255,255,0.04); color: #fff; }
        .mac-script-block { position: relative; margin-top: 16px; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); }
        .mac-script-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.06); }
        .mac-script-header span { font-size: 12px; color: rgba(255,255,255,0.4); font-family: 'JetBrains Mono', monospace; }
        .mac-script-pre { padding: 16px; background: #0d1117; overflow-x: auto; font-family: 'JetBrains Mono', monospace; font-size: 12px; line-height: 1.6; color: #e6edf3; max-height: 200px; overflow-y: auto; margin: 0; }
        .mac-script-pre::-webkit-scrollbar { width: 5px; height: 5px; }
        .mac-script-pre::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }
        @media (max-width: 768px) { .download-grid { grid-template-columns: 1fr; max-width: 400px; } .landing-nav .nav-links { display: none; } .features-grid { grid-template-columns: 1fr; } .requirements-grid { grid-template-columns: 1fr; } .mac-header { flex-direction: column; gap: 16px; align-items: flex-start; } .mac-step { flex-direction: column; gap: 12px; } .mac-step:not(:last-child)::after { display: none; } }
    </style>
</head>
<body>
<nav class="landing-nav">
    <div class="nav-brand">
        <img src="/img/logo.png" alt="SAEC">
        <span>SAEC <span style="color:var(--cyan-400)">Cloud</span></span>
    </div>
    <div class="nav-links">
        <a href="/#features">Fonctionnalités</a>
        <a href="/pricing">Tarifs</a>
        <a href="/#trust">Sécurité</a>
    </div>
    <div class="nav-actions">
        <a href="/login" class="btn btn-ghost">Connexion</a>
        <a href="/subscribe?plan=professional" class="btn btn-primary">Commencer</a>
    </div>
</nav>

<section class="download-hero">
    <span class="eyebrow">Client Desktop</span>
    <h1>SAEC Sync</h1>
    <p>Synchronisez vos fichiers en arrière-plan. Chiffrement AES-256-GCM. Multi-tenant. Souveraineté totale.</p>
</section>

<section class="download-section">
    <div class="download-grid">
        <?php foreach ($platforms as $platform): 
            $info = getDownloadInfo($platform['filename'], $githubReleases, $localFiles);
            $available = $info['available'];
            $source = $info['source'];
            $url = $info['url'];
            $isMacos = isset($platform['macos']) && $platform['macos'];
        ?>
        <div class="download-card <?= !$available ? 'download-coming' : '' ?> <?= $isMacos ? 'macos-highlight' : '' ?>">
            <div class="download-icon"><?= $platform['icon'] ?></div>
            <h2 class="download-name"><?= $platform['name'] ?></h2>
            <p class="download-desc"><?= $platform['desc'] ?></p>
            <ul class="download-meta">
                <?php foreach ($platform['meta'] as $meta): ?>
                <li>
                    <i class="fas fa-check" style="color: var(--emerald-400);"></i>
                    <?= $meta ?>
                </li>
                <?php endforeach; ?>
                <?php if ($source === 'github'): ?>
                <li><i class="fas fa-cloud-download-alt" style="color: var(--blue-500);"></i> Depuis GitHub Releases</li>
                <?php elseif ($source === 'local'): ?>
                <li><i class="fas fa-server" style="color: var(--cyan-400);"></i> Hébergé localement</li>
                <?php endif; ?>
            </ul>
            <?php if ($isMacos): ?>
                <button class="btn btn-primary download-btn" onclick="document.getElementById('macOverlay').classList.add('active')">
                    <i class="fas fa-download" style="margin-right:8px;"></i>
                    Installer sur macOS
                </button>
            <?php elseif ($available): ?>
                <a href="<?= htmlspecialchars($url) ?>" class="btn btn-primary download-btn" target="_blank" rel="noopener">
                    <i class="fas fa-download" style="margin-right:8px;"></i>
                    Télécharger
                </a>
            <?php else: ?>
                <button class="btn btn-outline download-btn" disabled>
                    <i class="fas fa-clock" style="margin-right:8px;"></i>
                    Bientôt disponible
                </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="features-section">
    <div style="text-align:center; margin-bottom:40px;">
        <span class="eyebrow" style="margin-bottom:16px; display:inline-block;">Fonctionnalités</span>
        <h2 style="font-size:clamp(24px,3vw,32px); font-weight:800; letter-spacing:-0.02em;">Pourquoi SAEC Sync ?</h2>
    </div>
    <div class="features-grid">
        <div class="feature">
            <h3><i class="fas fa-lock"></i> Chiffrement Zero-Knowledge</h3>
            <p>Vos fichiers sont chiffrés localement (AES-256-GCM) avant synchronisation. Ni nous, ni le provider cloud ne peuvent les lire.</p>
        </div>
        <div class="feature">
            <h3><i class="fas fa-sync-alt"></i> Sync bidirectionnelle temps réel</h3>
            <p>Modifications locales → distant instantanément. Modifications distantes → pull automatique. Détection de conflits intelligente.</p>
        </div>
        <div class="feature">
            <h3><i class="fas fa-hdd"></i> Multi-provider (S3, SFTP, WebDAV, Dropbox, GDrive, OneDrive)</h3>
            <p>Votre admin configure le stockage distant. Vous synchronisez transparemment, peu importe le backend.</p>
        </div>
        <div class="feature">
            <h3><i class="fas fa-shield-alt"></i> Intégrité & Vérification</h3>
            <p>Checksums SHA-256 sur chaque fichier. Retry exponentiel. Résolution de conflits: Last-Write-Wins, Keep Both, Ask.</p>
        </div>
        <div class="feature">
            <h3><i class="fas fa-cogs"></i> Tray + Notifications natives</h3>
            <p>Icône systray (Linux/Windows/macOS). Menu: Sync Now, Pause, Ouvrir dossier, Quitter. Notifications desktop natives.</p>
        </div>
        <div class="feature">
            <h3><i class="fas fa-code"></i> Open Source & Auditable</h3>
            <p>Client Tauri (Rust + React). Code source disponible. Pas de télémétrie forcée. Vous contrôlez vos données.</p>
        </div>
    </div>
</section>

<section class="requirements">
    <div class="requirements-grid">
        <div class="req-card">
            <h4>Configuration requise</h4>
            <ul>
                <li>macOS: 12 Monterey+</li>
                <li>Linux: glibc 2.31+, WebKitGTK 4.1</li>
                <li>Windows: Win10 1903+ (x64)</li>
            </ul>
        </div>
        <div class="req-card">
            <h4>Compte SAEC Cloud</h4>
            <ul>
                <li>Tenant configuré par admin</li>
                <li>Storage mount assigné (readwrite)</li>
                <li>Auth device-code flow</li>
            </ul>
        </div>
        <div class="req-card">
            <h4>Installation rapide</h4>
            <ul>
                <li>macOS: <code>chmod +x install-macos.sh && ./install-macos.sh</code></li>
                <li>Linux: <code>sudo apt install ./saec-sync.deb</code></li>
                <li>Windows: double-clic sur le .msi</li>
            </ul>
        </div>
    </div>
</section>

<footer class="landing-footer">
    <div>&copy; 2026 SAEC Ltd. — Bespoke Systems Engineering</div>
    <div class="footer-links">
        <a href="/privacy">Confidentialité</a>
        <a href="/terms">Conditions</a>
        <a href="https://saec.me" target="_blank">SAEC.me</a>
    </div>
</footer>

<!-- OVERLAY macOS -->
<div class="mac-overlay" id="macOverlay">
    <div class="mac-modal">
        <div class="mac-header">
            <div class="mac-header-left">
                <div class="mac-header-icon">🍎</div>
                <div>
                    <h2>SAEC Sync — macOS</h2>
                    <div class="subtitle">Installation automatique · Apple Silicon &amp; Intel · macOS 12+</div>
                </div>
            </div>
            <button class="mac-close" onclick="document.getElementById('macOverlay').classList.remove('active')">&times;</button>
        </div>
        <div class="mac-body">
            <div class="mac-step">
                <div class="mac-step-num">1</div>
                <div class="mac-step-content">
                    <h3>Télécharger le script d'installation</h3>
                    <p>Le script télécharge automatiquement le DMG, le monte, installe l'app dans /Applications, configure le serveur et retire la quarantine Gatekeeper.</p>
                    <div class="mac-btn-row">
                        <a href="/install-macos.sh" download class="mac-btn mac-btn-primary">
                            <i class="fas fa-download"></i> Télécharger install-macos.sh
                        </a>
                        <button class="mac-btn mac-btn-ghost" onclick="copyScript()">
                            <i class="fas fa-copy"></i> Copier le script
                        </button>
                    </div>
                </div>
            </div>

            <div class="mac-step">
                <div class="mac-step-num">2</div>
                <div class="mac-step-content">
                    <h3>Rendre exécutable et lancer</h3>
                    <p>Ouvrez Terminal (Finder → Applications → Utilitaires → Terminal) puis collez :</p>
                    <div class="mac-cmd">
                        <code>chmod +x ~/Downloads/install-macos.sh &amp;&amp; ~/Downloads/install-macos.sh</code>
                        <button class="mac-copy" onclick="copyCmd(this, 'chmod +x ~/Downloads/install-macos.sh && ~/Downloads/install-macos.sh')">Copier</button>
                    </div>
                    <div class="mac-alert info">
                        <i class="fas fa-info-circle"></i>
                        <div>Le script demande confirmation avant de remplacer une version existante. Il affiche la progression en temps réel.</div>
                    </div>
                </div>
            </div>

            <div class="mac-step">
                <div class="mac-step-num">3</div>
                <div class="mac-step-content">
                    <h3>Autoriser l'ouverture (si bloqué par Gatekeeper)</h3>
                    <p>Si macOS affiche "SAEC Sync ne peut pas être ouvert car il provient d'un développeur non identifié" :</p>
                    <div class="mac-cmd">
                        <code>System Preferences → Security &amp; Privacy → General → "Open Anyway"</code>
                        <button class="mac-copy" onclick="copyCmd(this, 'System Preferences → Security & Privacy → General → Open Anyway')">Copier</button>
                    </div>
                    <div class="mac-alert warn">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>Ce message apparaît uniquement la première fois. Après ouverture, macOS se souvient de votre choix.</div>
                    </div>
                </div>
            </div>

            <div class="mac-step">
                <div class="mac-step-num">4</div>
                <div class="mac-step-content">
                    <h3>Connecter votre compte SAEC Cloud</h3>
                    <p>L'app s'ouvre et affiche un code d'appareil (ex: ABCD-EFGH). Validez-le depuis votre navigateur :</p>
                    <div class="mac-cmd">
                        <code>https://cloud.saec.me/device?code=VOTRE-CODE</code>
                        <button class="mac-copy" onclick="copyCmd(this, 'https://cloud.saec.me/device?code=VOTRE-CODE')">Copier</button>
                    </div>
                    <div class="mac-alert success">
                        <i class="fas fa-check-circle"></i>
                        <div>Après validation, l'app se connecte automatiquement et synchronise les dossiers assignés par votre admin.</div>
                    </div>
                </div>
            </div>

            <div class="mac-step">
                <div class="mac-step-num">⚡</div>
                <div class="mac-step-content">
                    <h3>Script complet (pour copier-coller)</h3>
                    <p>Vous pouvez aussi copier le script entier et le coller dans Terminal :</p>
                    <div class="mac-script-block">
                        <div class="mac-script-header">
                            <span>install-macos.sh</span>
                            <button class="mac-copy" onclick="copyFullScript()">Tout copier</button>
                        </div>
                        <pre class="mac-script-pre" id="macScriptFull">#!/usr/bin/env bash
set -euo pipefail
APP_NAME="SAEC Sync"
APP_BUNDLE="SAEC Sync.app"
VERSION="${1:-0.1.36}"
API_URL="https://cloud.saec.me"
DMG_BASENAME="SAEC-Sync-${VERSION}"
INSTALL_DIR="/Applications"
TMPDIR_INSTALL="/tmp/saec-install"
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
log()  { echo -e "${GREEN}[✓]${NC} $*"; }
warn() { echo -e "${YELLOW}[!]${NC} $*"; }
err()  { echo -e "${RED}[✗]${NC} $*"; }
info() { echo -e "${CYAN}[·]${NC} $*"; }
cleanup() { rm -rf "$TMPDIR_INSTALL" 2>/dev/null; }
trap cleanup EXIT
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo -e "${CYAN}  SAEC Sync — macOS Installer v${VERSION}${NC}"
echo -e "${CYAN}═══════════════════════════════════════${NC}"
mkdir -p "$TMPDIR_INSTALL"
[[ "$(uname)" != "Darwin" ]] &amp;&amp; { err "macOS uniquement."; exit 1; }
log "macOS $(sw_vers -productVersion) détecté"
ARCH="$(uname -m)"
[[ "$ARCH" == "arm64" ]] &amp;&amp; DMG_ARCH="aarch64-apple-darwin" || DMG_ARCH="x86_64-apple-darwin"
command -v curl &amp;>/dev/null || { err "curl manquant."; exit 1; }
if [[ -d "${INSTALL_DIR}/${APP_BUNDLE}" ]]; then
    EXISTING_VER=$(/usr/libexec/PlistBuddy -c "Print :CFBundleShortVersionString" "${INSTALL_DIR}/${APP_BUNDLE}/Contents/Info.plist" 2>/dev/null || echo "?")
    warn "Déjà install (v${EXISTING_VER}). Remplacer ? [O/n]"
    read -p "&gt; " -n 1 -r; echo
    [[ $REPLY =~ ^[Nn]$ ]] &amp;&amp; { open "${INSTALL_DIR}/${APP_BUNDLE}"; exit 0; }
fi
GITHUB_URL="https://github.com/saec-oolivvv/cloud/releases/download/v${VERSION}/${DMG_BASENAME}.dmg"
SAEC_URL="${API_URL}/download/saec-sync-${VERSION}.dmg"
DMG_URL=""
for url in "$GITHUB_URL" "$SAEC_URL"; do
    if curl -fsSL --head "$url" &amp;>/dev/null; then DMG_URL="$url"; log "DMG trouvé: $url"; break; fi
done
[[ -z "$DMG_URL" ]] &amp;&amp; { err "Aucun DMG trouvé pour v${VERSION}."; exit 1; }
DMG_FILE="${TMPDIR_INSTALL}/${DMG_BASENAME}.dmg"
info "Téléchargement..."
curl -L --progress-bar -o "$DMG_FILE" "$DMG_URL"
log "DMG téléchargé ($(du -h "$DMG_FILE" | cut -f1))"
MOUNT_POINT=$(hdiutil attach -nobrowse -readonly "$DMG_FILE" 2>&amp;1 | grep -E "/Volumes/SAEC" | awk '{print $NF}')
[[ -z "$MOUNT_POINT" ]] &amp;&amp; { err "Montage DMG raté"; exit 1; }
rm -rf "${INSTALL_DIR}/${APP_BUNDLE}"
cp -R "${MOUNT_POINT}/${APP_BUNDLE}" "${INSTALL_DIR}/"
hdiutil detach "$MOUNT_POINT" 2>/dev/null
log "Installé dans ${INSTALL_DIR}"
PLIST="${INSTALL_DIR}/${APP_BUNDLE}/Contents/Info.plist"
/usr/libexec/PlistBuddy -c "Add :SAECServerURL string ${API_URL}" "$PLIST" 2>/dev/null || /usr/libexec/PlistBuddy -c "Set :SAECServerURL ${API_URL}" "$PLIST" 2>/dev/null
/usr/libexec/PlistBuddy -c "Add :SAECClientVersion string ${VERSION}" "$PLIST" 2>/dev/null || /usr/libexec/PlistBuddy -c "Set :SAECClientVersion ${VERSION}" "$PLIST" 2>/dev/null
xattr -dr com.apple.quarantine "${INSTALL_DIR}/${APP_BUNDLE}" 2>/dev/null || true
chmod -R 755 "${INSTALL_DIR}/${APP_BUNDLE}"
log "Permissions OK"
echo ""
log "Installation terminée !"
echo "  App: ${INSTALL_DIR}/${APP_BUNDLE}"
echo "  Version: ${VERSION}"
echo ""
read -p "Lancer SAEC Sync ? [O/n] " -n 1 -r; echo
[[ ! $REPLY =~ ^[Nn]$ ]] &amp;&amp; open "${INSTALL_DIR}/${APP_BUNDLE}"</pre>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function copyCmd(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = 'Copié ✓';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Copier'; btn.classList.remove('copied'); }, 2000);
    });
}
function copyScript() {
    var script = document.getElementById('macScriptFull').textContent;
    navigator.clipboard.writeText(script).then(() => {
        var btn = event.target.closest('button');
        btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copier le script'; }, 2000);
    });
}
function copyFullScript() {
    var script = document.getElementById('macScriptFull').textContent;
    navigator.clipboard.writeText(script).then(() => {
        var btn = event.target.closest('button');
        btn.textContent = 'Copié ✓';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Tout copier'; btn.classList.remove('copied'); }, 2000);
    });
}
document.getElementById('macOverlay').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('macOverlay').classList.remove('active');
});
</script>
</body>
</html>
