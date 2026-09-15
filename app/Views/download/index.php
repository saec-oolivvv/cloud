<?php
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'en';
$availableLangs = $lang ? Saec\Core\Translation::getAvailable() : [];

// Récupérer les releases GitHub pour les artifacts
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

// Fichiers locaux disponibles
$localFiles = [
    'saec-sync-0.1.0-amd64.deb' => ['path' => '/download/saec-sync-0.1.0-amd64.deb', 'local' => true],
    'saec-sync-0.1.0-x86_64.rpm' => ['path' => '/download/saec-sync-0.1.0-x86_64.rpm', 'local' => true],
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
    ['id' => 'linux-deb', 'name' => 'Linux — .deb', 'icon' => '🐧', 'desc' => 'Debian, Ubuntu, Mint, Pop!_OS, Elementary, et dérivés', 'meta' => ['Architecture: amd64', 'Taille: ~11 MB', 'Version: 0.1.0', 'Dépendances: webkit2gtk-4.1, gtk3, libayatana-appindicator3'], 'filename' => 'saec-sync-0.1.0-amd64.deb'],
    ['id' => 'linux-rpm', 'name' => 'Linux — .rpm', 'icon' => '🐧', 'desc' => 'Fedora, RHEL, CentOS, AlmaLinux, Rocky, openSUSE', 'meta' => ['Architecture: x86_64', 'Taille: ~11 MB', 'Version: 0.1.0', 'Dépendances: webkit2gtk4.1, gtk3, libayatana-appindicator-gtk3'], 'filename' => 'saec-sync-0.1.0-x86_64.rpm'],
    ['id' => 'linux-appimage', 'name' => 'Linux — AppImage', 'icon' => '🐧', 'desc' => 'Distribution universelle (toutes distros, sans installation)', 'meta' => ['Architecture: x86_64', 'Taille: ~11 MB', 'Version: 0.1.0', 'Exécution: chmod +x && ./SAEC-Sync.AppImage'], 'filename' => 'saec-sync-0.1.0-x86_64.AppImage'],
    ['id' => 'windows-msi', 'name' => 'Windows — .msi', 'icon' => '🪟', 'desc' => 'Windows 10/11 (x64)', 'meta' => ['Build CI: GitHub Actions', 'Installation: per-user (pas d\'admin)', 'Signature: code signing'], 'filename' => 'SAEC Sync_0.1.0_amd64.msi'],
    ['id' => 'macos-dmg', 'name' => 'macOS — .dmg', 'icon' => '🍎', 'desc' => 'macOS 12+ (Apple Silicon & Intel)', 'meta' => ['Build CI: GitHub Actions', 'Notarisation: Apple', 'Universal: ARM64 + x64'], 'filename' => 'SAEC Sync-0.1.0.dmg'],
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
        .download-hero .eyebrow {
            display: inline-block; font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.12em; color: var(--blue-500); margin-bottom: 16px;
        }
        .download-hero h1 {
            font-size: clamp(28px, 4vw, 42px); font-weight: 800; letter-spacing: -0.03em;
            background: linear-gradient(135deg, var(--text-primary), var(--cyan-400));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin-top: 8px;
        }
        .download-hero p {
            font-size: 16px; color: var(--text-secondary); max-width: 520px;
            margin: 16px auto 0; line-height: 1.6;
        }

        .download-section { padding: 20px 24px 80px; }
        .download-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
            max-width: 1100px; margin: 0 auto;
        }
        .download-card {
            background: var(--bg-card); border-radius: 16px; padding: 40px 28px;
            text-align: left; position: relative; border: 1px solid var(--border-subtle);
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            display: flex; flex-direction: column;
        }
        .download-card:hover { border-color: rgba(37, 99, 235, 0.4); transform: translateY(-4px); box-shadow: 0 8px 40px rgba(0,0,0,0.3); }
        .download-icon { font-size: 48px; margin-bottom: 16px; }
        .download-name { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .download-desc { font-size: 13px; color: var(--text-secondary); margin-bottom: 20px; }
        .download-meta { list-style: none; margin: 0 0 24px; font-size: 12px; color: var(--text-muted); }
        .download-meta li { padding: 4px 0; display: flex; gap: 8px; align-items: center; }
        .download-meta i { color: var(--emerald-400); font-size: 10px; width: 14px; text-align: center; }
        .download-btn { width: 100%; margin-top: auto; }

        .download-coming {
            opacity: 0.5; pointer-events: none;
        }
        .download-coming .download-btn { background: var(--border-subtle); color: var(--text-muted); cursor: not-allowed; }

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

        .landing-footer {
            padding: 32px 24px; display: flex; align-items: center; justify-content: space-between;
            font-size: 12px; color: var(--text-muted);
        }
        .landing-footer a { color: var(--text-secondary); transition: color 0.15s; }
        .landing-footer a:hover { color: var(--blue-400); }
        .footer-links { display: flex; gap: 24px; }

        @media (max-width: 768px) {
            .download-grid { grid-template-columns: 1fr; max-width: 400px; }
            .landing-nav .nav-links { display: none; }
            .features-grid { grid-template-columns: 1fr; }
            .requirements-grid { grid-template-columns: 1fr; }
        }
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
        ?>
        <div class="download-card <?= !$available ? 'download-coming' : '' ?>">
            <div class="download-icon"><?= $platform['icon'] ?></div>
            <h2 class="download-name"><?= $platform['name'] ?></h2>
            <p class="download-desc"><?= $platform['desc'] ?></p>
            <ul class="download-meta">
                <?php foreach ($platform['meta'] as $meta): ?>
                <li>
                    <i class="fas <?= $available ? 'fa-check' : 'fa-clock' ?>" 
                       style="color: <?= $available ? 'var(--emerald-400)' : 'var(--amber-400)' ?>;"></i>
                    <?= $meta ?>
                </li>
                <?php endforeach; ?>
                <?php if ($source === 'github'): ?>
                <li><i class="fas fa-cloud-download-alt" style="color: var(--blue-500);"></i> Depuis GitHub Releases</li>
                <?php elseif ($source === 'local'): ?>
                <li><i class="fas fa-server" style="color: var(--cyan-400);"></i> Hébergé localement</li>
                <?php endif; ?>
            </ul>
            <?php if ($available): ?>
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
                <li>Linux: glibc 2.31+, WebKitGTK 4.1</li>
                <li>Windows: Win10 1903+ (x64)</li>
                <li>macOS: 12 Monterey+</li>
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
            <h4>Installation</h4>
            <ul>
                <li>Linux: <code>sudo apt install ./saec-sync.deb</code></li>
                <li>Linux: <code>sudo rpm -i saec-sync.rpm</code></li>
                <li>AppImage: <code>chmod +x && ./SAEC-Sync.AppImage</code></li>
            </ul>
        </div>
    </div>
</section>

<footer class="landing-footer">
    <div>© 2026 SAEC Ltd. — Bespoke Systems Engineering</div>
    <div class="footer-links">
        <a href="/privacy">Confidentialité</a>
        <a href="/terms">Conditions</a>
        <a href="https://saec.me" target="_blank">SAEC.me</a>
    </div>
</footer>

</body>
</html>