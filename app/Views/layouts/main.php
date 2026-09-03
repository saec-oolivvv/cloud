<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SAEC Cloud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
</head>
<body>

    <!-- GLOBAL HEADER -->
    <header class="global-header">
        <!-- Left: Logo + Mobile Sidebar Toggle -->
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()" style="display:none; margin-right:var(--space-2); padding:var(--space-1); background:var(--bg-card); border:none; border-radius:var(--radius-sm); cursor:pointer; color:var(--text-primary);">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-logo">
                <img src="/img/logo.png" alt="SAEC" style="height:32px; width:auto;">
            </div>
        </div>

        <!-- Center: Page Title -->
        <div class="header-title">
            <h1 class="page-title" id="pageTitle">SAEC Cloud</h1>
        </div>

        <!-- Right: Notifications + User -->
        <div class="header-right">
            <div class="header-notif" onclick="toggleNotifMenu()">
                <i class="fas fa-bell"></i>
                <span class="notif-count" id="notifCount">3</span>
            </div>

            <div class="user-profile" onclick="toggleUserMenu()">
                <div class="avatar">AD</div>
                <span class="user-name">admin@saec.me</span>
                <span class="user-arrow"></span>
            </div>

            <!-- Notifications Menu (mobile) -->
            <div class="notif-menu" id="notifMenu" style="position:absolute; right:0; top:100%; margin-top:8px; background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:var(--space-4); min-width:200px; display:none; z-index:100;">
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:var(--space-3);">Notifications</div>
                <div style="max-height:200px; overflow-y:auto;">
                    <div style="padding:var(--space-2) var(--space-3); border-bottom:1px solid var(--border-subtle); font-size:13px; color:var(--text-primary);" onclick="window.location='/audit'">
                        <i class="fas fa-file-download" style="color:var(--cyan-400); margin-right:var(--space-2);"></i>Nouveau fichier téléchargé
                    </div>
                    <div style="padding:var(--space-2) var(--space-3); border-bottom:1px solid var(--border-subtle); font-size:13px; color:var(--text-primary);" onclick="window.location='/admin/audit'">
                        <i class="fas fa-shield-alt" style="color:var(--emerald-400); margin-right:var(--space-2);"></i>Nouvelle tentative de connexion
                    </div>
                    <div style="padding:var(--space-2) var(--space-3); border-bottom:1px solid var(--border-subtle); font-size:13px; color:var(--text-primary);" onclick="window.location='/files'">
                        <i class="fas fa-upload" style="color:var(--blue-500); margin-right:var(--space-2);"></i>Nouveau fichier uploadé
                    </div>
                </div>
                <div style="padding:var(--space-3) var(--space-4); text-align:center; font-size:11px; color:var(--text-muted);">
                    Tout marquer comme lu
                </div>
            </div>

            <!-- User Menu (mobile) -->
            <div class="user-menu" id="userMenu" style="position:absolute; right:0; top:100%; margin-top:8px; background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:var(--space-4); min-width:200px; display:none; z-index:100;">
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:var(--space-3);">Profil</div>
                <a href="/config" style="display:block; padding:var(--space-2) var(--space-3); color:var(--text-primary); font-size:13px; margin-bottom:var(--space-2); border-radius:var(--radius-sm);" onclick="toggleUserMenu();return false;">
                    <i class="fas fa-cog" style="color:var(--amber-400); margin-right:var(--space-2);"></i>Paramètres
                </a>
                <a href="/logout" style="display:block; padding:var(--space-2) var(--space-3); color:var(--rose-500); font-size:13px; border-radius:var(--radius-sm);">
                    <i class="fas fa-sign-out-alt" style="color:var(--rose-500); margin-right:var(--space-2);"></i>Déconnexion
                </a>
            </div>
        </div>
    </header>

    <!-- APP LAYOUT WRAPPER -->
    <div class="app-layout">

        <!-- MOBILE OVERLAY -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <!-- SIDEBAR COLLAPSIBLE -->
        <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="/img/logo.png" alt="SAEC" style="height:32px; width:auto;">
            <span>SAEC <span>Cloud</span></span>
        </div>

        <div class="sidebar-status">
            <span class="dot"></span>
            Système nominal
        </div>

        <nav class="sidebar-nav">
            <a href="/dashboard" class="nav-item <?= $currentPage === 'dashboard' || basename($_SERVER['REQUEST_URI']) === '/' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <span class="nav-text">Tableau de bord</span>
            </a>

            <a href="/files" class="nav-item <?= basename($_SERVER['REQUEST_URI']) === 'files.php' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                <span class="nav-text">Fichiers</span>
            </a>

            <a href="/shares" class="nav-item <?= basename($_SERVER['REQUEST_URI']) === 'shares.php' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <span class="nav-text">Partages</span>
            </a>

            <?php if ($isAdmin): ?>
            <div class="nav-section-label">Administration</div>

            <a href="/admin" class="nav-item <?= $currentPage === 'admin' || strpos($_SERVER['REQUEST_URI'], '/admin') !== false ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span class="nav-text">Tableau de bord admin</span>
            </a>

            <a href="/admin/tenants" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/tenants') !== false ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <span class="nav-text">Tenants</span>
            </a>

            <a href="/admin/modules" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/modules') !== false ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                <span class="nav-text">Modules</span>
            </a>

            <a href="/admin/audit" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/audit') !== false ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span class="nav-text">Audit</span>
            </a>

            <a href="/admin/config" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/config') !== false ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span class="nav-text">Configuration</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div style="margin-bottom:var(--space-3);">
                <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); font-weight:600; margin-bottom:var(--space-1);">Language</div>
                <form method="GET" action="<?= strtok($_SERVER['REQUEST_URI'], '?') ?: '/' ?>" style="display:flex; gap:4px;">
                    <select name="lang" onchange="this.form.submit()" style="
                        width:100%; padding:4px 8px; border-radius:var(--radius-sm);
                        border:1px solid var(--border-subtle); background:var(--bg-card);
                        color:var(--text-primary); font-size:11px; cursor:pointer;
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
                <div style="font-size:10px; color:var(--text-muted); margin-bottom:var(--space-1);">SAEC Ltd.</div>
                <div style="margin-top:2px; color:var(--text-muted); font-size:10px;">
                    <?php if ($user): ?>
                    <?= htmlspecialchars($user['email']) ?> · <a href="/logout" style="color:var(--rose-400);">Déconnexion</a>
                    <?php else: ?>
                    <a href="/login" style="color:var(--blue-400);">Connexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main" id="mainContent">
        <?php if (isset($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success" style="margin-bottom:var(--space-4);">
            <i class="fas fa-check-circle" style="color:var(--emerald-400); margin-right:var(--space-2);"></i>
            <?= htmlspecialchars($_SESSION['_flash']['success']) ?>
        </div>
        <?php unset($_SESSION['_flash']['success']); endif; ?>

        <?php if (isset($_SESSION['_flash']['error'])): ?>
        <div class="alert alert-error" style="margin-bottom:var(--space-4);">
            <i class="fas fa-exclamation-circle" style="color:var(--rose-500); margin-right:var(--space-2);"></i>
            <?= htmlspecialchars($_SESSION['_flash']['error']) ?>
        </div>
        <?php unset($_SESSION['_flash']['error']); endif; ?>

        <?= $content ?>
    </main>

</div> <!-- /.app-layout -->

<script>
    // Sidebar toggle — fixed sidebar with transform
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    }

    // User menu toggle
    function toggleUserMenu() {
        const menu = document.getElementById('userMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    // Notifications menu toggle
    function toggleNotifMenu() {
        const menu = document.getElementById('notifMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    // Close menus on overlay click
    document.getElementById('sidebarOverlay').onclick = function(e) {
        if (e.target !== this) return;
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
        toggleUserMenu();
        toggleNotifMenu();
    };

    // Close menus on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            toggleUserMenu();
            toggleNotifMenu();
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay.classList.contains('show')) {
                overlay.classList.remove('show');
                document.getElementById('sidebar').classList.remove('open');
            }
        }
    });
</script>
</body>
</html>