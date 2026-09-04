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
            <button class="mobile-toggle" onclick="toggleSidebar()">
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

        <!-- Header Search -->
        <div class="header-search">
            <i class="fas fa-search"></i>
            <input type="text" id="headerSearchInput" placeholder="Rechercher...">
            <kbd>Ctrl+K</kbd>
        </div>

        <!-- Right: Notifications + User -->
        <div class="header-right">
            <div class="header-notif" id="notifToggle" onclick="toggleNotifMenu(event)">
                <i class="fas fa-bell"></i>
                <span class="notif-count" id="notifCount">3</span>
            </div>

            <div class="user-profile" id="userToggle" onclick="toggleUserMenu(event)">
                <div class="avatar">AD</div>
                <span class="user-name">admin@saec.me</span>
                <span class="user-arrow"></span>
            </div>

            <!-- Notifications Menu -->
            <div class="notif-menu" id="notifMenu">
                <div class="dropdown-header">Notifications</div>
                <div class="dropdown-scroll">
                    <div class="dropdown-item" onclick="window.location='/audit'">
                        <i class="fas fa-file-download" style="color:var(--cyan-400);"></i>
                        <span>Nouveau fichier téléchargé</span>
                    </div>
                    <div class="dropdown-item" onclick="window.location='/admin/audit'">
                        <i class="fas fa-shield-alt" style="color:var(--emerald-400);"></i>
                        <span>Nouvelle tentative de connexion</span>
                    </div>
                    <div class="dropdown-item" onclick="window.location='/files'">
                        <i class="fas fa-upload" style="color:var(--blue-500);"></i>
                        <span>Nouveau fichier uploadé</span>
                    </div>
                </div>
                <div class="dropdown-footer">Tout marquer comme lu</div>
            </div>

            <!-- User Menu -->
            <div class="user-menu" id="userMenu">
                <div class="dropdown-header">Profil</div>
                <a href="/config" class="dropdown-item" onclick="toggleUserMenu(event);">
                    <i class="fas fa-cog" style="color:var(--amber-400);"></i>
                    <span>Paramètres</span>
                </a>
                <a href="/logout" class="dropdown-item" style="color:var(--rose-500);">
                    <i class="fas fa-sign-out-alt" style="color:var(--rose-500);"></i>
                    <span>Déconnexion</span>
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

                <a href="/config" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/config') !== false && strpos($_SERVER['REQUEST_URI'], '/admin') === false ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="nav-text">Mon compte</span>
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
                    <div class="sidebar-footer-label">Language</div>
                    <form method="GET" action="<?= strtok($_SERVER['REQUEST_URI'], '?') ?: '/' ?>" style="display:flex; gap:4px;">
                        <select name="lang" onchange="this.form.submit()" class="sidebar-lang-select">
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
        <main class="main" id="mainContent" data-stagger>
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

        <!-- Global Toast -->
        <div id="globalToast" class="toast"></div>

    </div> <!-- /.app-layout -->

    <style>
        /* Dropdown menus */
        .notif-menu, .user-menu {
            position: absolute; right: 0; top: 100%; margin-top: var(--space-2);
            background: var(--bg-card); border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md); min-width: 220px; display: none; z-index: 100;
            box-shadow: var(--shadow-lg);
        }
        .dropdown-header {
            padding: var(--space-2) var(--space-4); font-size: 11px; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--text-muted); font-weight: 600;
            border-bottom: 1px solid var(--border-subtle);
        }
        .dropdown-scroll { max-height: 200px; overflow-y: auto; }
        .dropdown-item {
            display: flex; align-items: center; gap: var(--space-3);
            padding: var(--space-2) var(--space-4); font-size: 13px; color: var(--text-primary);
            cursor: pointer; transition: background var(--duration-fast) var(--ease-out);
        }
        .dropdown-item:hover { background: var(--bg-card-hover); }
        .dropdown-item i { width: 16px; text-align: center; flex-shrink: 0; }
        .dropdown-footer {
            padding: var(--space-3) var(--space-4); text-align: center;
            font-size: 11px; color: var(--text-muted); border-top: 1px solid var(--border-subtle);
        }
        /* Sidebar footer helpers */
        .sidebar-footer-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em;
            color: var(--text-muted); font-weight: 600; margin-bottom: var(--space-1);
        }
        .sidebar-lang-select {
            width: 100%; padding: 4px 8px; border-radius: var(--radius-sm);
            border: 1px solid var(--border-subtle); background: var(--bg-card);
            color: var(--text-primary); font-size: 11px; cursor: pointer;
            font-family: inherit;
        }
    </style>

<script>
    // Sidebar toggle
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    }

    // Close all open dropdowns
    function closeAllDropdowns() {
        document.getElementById('userMenu').style.display = 'none';
        document.getElementById('notifMenu').style.display = 'none';
    }

    // User menu toggle
    function toggleUserMenu(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('userMenu');
        const isOpen = menu.style.display === 'block';
        closeAllDropdowns();
        if (!isOpen) menu.style.display = 'block';
    }

    // Notifications menu toggle
    function toggleNotifMenu(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('notifMenu');
        const isOpen = menu.style.display === 'block';
        closeAllDropdowns();
        if (!isOpen) menu.style.display = 'block';
    }

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        const userMenu = document.getElementById('userMenu');
        const notifMenu = document.getElementById('notifMenu');
        const userToggle = document.getElementById('userToggle');
        const notifToggle = document.getElementById('notifToggle');

        if (userMenu.style.display === 'block' && !userMenu.contains(e.target) && !userToggle.contains(e.target)) {
            userMenu.style.display = 'none';
        }
        if (notifMenu.style.display === 'block' && !notifMenu.contains(e.target) && !notifToggle.contains(e.target)) {
            notifMenu.style.display = 'none';
        }
    });

    // Close menus on overlay click
    document.getElementById('sidebarOverlay').onclick = function(e) {
        if (e.target !== this) return;
        document.getElementById('sidebar').classList.remove('open');
        this.classList.remove('show');
        closeAllDropdowns();
    };

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+K → focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const input = document.getElementById('headerSearchInput');
            if (input) input.focus();
        }

        // Escape → close everything
        if (e.key === 'Escape') {
            closeAllDropdowns();
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay.classList.contains('show')) {
                overlay.classList.remove('show');
                document.getElementById('sidebar').classList.remove('open');
            }
            const searchInput = document.getElementById('headerSearchInput');
            if (searchInput) searchInput.blur();
        }
    });

    // ============================================================
    // SaecToast — global toast notification utility
    // ============================================================
    const SaecToast = {
        show(msg, type, duration) {
            type = type || 'info';
            duration = duration || 3000;
            const t = document.getElementById('globalToast');
            t.className = 'toast ' + type;
            t.innerHTML = '<span>' + msg + '</span><button class="toast-close" onclick="this.parentElement.style.display=\'none\'">&times;</button>';
            t.style.display = 'flex';
            clearTimeout(this._timer);
            this._timer = setTimeout(function() { t.style.display = 'none'; }, duration);
        },
        success: function(msg) { this.show(msg, 'success'); },
        error: function(msg) { this.show(msg, 'error'); },
        info: function(msg) { this.show(msg, 'info'); },
        warning: function(msg) { this.show(msg, 'warning'); }
    };

    // ============================================================
    // SaecUtils — shared utility functions
    // ============================================================
    const SaecUtils = {
        formatSize: function(bytes) {
            if (!bytes) return '0 B';
            var k = 1024;
            var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
        copyToClipboard: function(text) {
            try {
                return navigator.clipboard.writeText(text).then(function() { return true; });
            } catch(e) {
                var ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                return Promise.resolve(true);
            }
        }
    };
</script>
</body>
</html>
