<?php
/** @var string $content */
$user = $_SESSION['user'] ?? null;
$currentPage = basename($_SERVER['REQUEST_URI'], '/');
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$availableLangs = $lang ? Saec\Core\Translation::getAvailable() : [];
$currentLang = $lang ? $lang->getLang() : 'en';
$isAdmin = ($user['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? 'SAEC Cloud' ?> — SAEC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/img/favicon-192x192.png">
    <link rel="icon" type="image/png" sizes="180x180" href="/img/favicon-180x180.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon-180x180.png">
</head>
<body>

    <!-- MOBILE HEADER -->
    <div class="mobile-header">
        <div class="brand">
            <img src="/img/logo.png" alt="SAEC" style="height:28px;">
            SAEC Cloud
        </div>
        <button class="hamburger" onclick="toggleSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="/img/logo.png" alt="SAEC" style="height:32px; width:auto;">
            <div class="brand-text">SAEC <span>Cloud</span></div>
        </div>

        <div class="sidebar-status">
            <span class="dot"></span>
            <?= t('status.operational') ?>
        </div>

        <nav class="sidebar-nav">
            <a href="/dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <?= t('nav.dashboard') ?>
            </a>

            <a href="/files" class="nav-item <?= $currentPage === 'files' ? 'active' : '' ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                <?= t('nav.files') ?>
            </a>

            <a href="/shares" class="nav-item <?= $currentPage === 'shares' ? 'active' : '' ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                <?= t('nav.shares') ?>
            </a>

            <?php if ($isAdmin): ?>
            <div class="nav-section-label">Admin</div>

            <a href="/admin" class="nav-item <?= $currentPage === 'admin' ? 'active' : '' ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <?= t('nav.admin') ?>
            </a>

            <a href="/admin/tenants" class="nav-item <?= $currentPage === 'tenants' ? 'active' : '' ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="2" width="20" height="20" rx="2.18"/><line x1="8" y1="2" x2="8" y2="22"/><line x1="16" y1="2" x2="16" y2="22"/><line x1="2" y1="8" x2="22" y2="8"/><line x1="2" y1="16" x2="22" y2="16"/></svg>
                <?= t('admin.tenants') ?>
            </a>

            <a href="/admin/modules" class="nav-item <?= $currentPage === 'modules' ? 'active' : '' ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H5.78a1.65 1.65 0 0 0-1.51 1 1.65 1.65 0 0 0 .33 1.82l.04.04A10 10 0 0 0 12 17.66 10 10 0 0 0 19.36 15z"/></svg>
                Modules
            </a>

            <a href="/admin/audit" class="nav-item <?= $currentPage === 'audit' ? 'active' : '' ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <?= t('admin.audit') ?>
            </a>

            <a href="/admin/config" class="nav-item <?= $currentPage === 'config' ? 'active' : '' ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H5.78a1.65 1.65 0 0 0-1.51 1 1.65 1.65 0 0 0 .33 1.82l.04.04A10 10 0 0 0 12 17.66 10 10 0 0 0 19.36 15z"/></svg>
                <?= t('admin.config') ?>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div style="margin-bottom: var(--space-3);">
                <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); font-weight:600; margin-bottom:var(--space-1);">Language</div>
                <form method="GET" action="<?= strtok($_SERVER['REQUEST_URI'], '?') ?: '/' ?>">
                    <select name="lang" onchange="this.form.submit()" style="
                        width:100%; padding:6px 8px; border-radius:var(--radius-sm);
                        border:1px solid var(--border-subtle); background:var(--bg-card);
                        color:var(--text-primary); font-size:12px; cursor:pointer;
                        font-family:inherit;
                    ">
                        <?php foreach ($availableLangs as $code => $info): ?>
                        <option value="<?= $code ?>" <?= $code === $currentLang ? 'selected' : '' ?>>
                            <?= $info['flag'] ?> <?= $info['name'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div>
                <div>SAEC Ltd. — v1.0.0</div>
                <div style="margin-top:2px; color:var(--text-muted); font-size:11px;">
                    <?php if ($user): ?>
                    <?= htmlspecialchars($user['email']) ?> · <a href="/logout" style="color:var(--rose-400);">Logout</a>
                    <?php else: ?>
                    <a href="/login" style="color:var(--blue-400);">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">
        <?php if (isset($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['_flash']['success']) ?>
        </div>
        <?php unset($_SESSION['_flash']['success']); endif; ?>

        <?php if (isset($_SESSION['_flash']['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['_flash']['error']) ?>
        </div>
        <?php unset($_SESSION['_flash']['error']); endif; ?>

        <?= $content ?>
    </main>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    </script>
</body>
</html>
