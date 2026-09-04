<?php
/**
 * SAEC Cloud — Landing Page
 * Hero + Features + Pricing Preview + CTA + Footer
 */
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'en';
$availableLangs = $lang ? Saec\Core\Translation::getAvailable() : [];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAEC Cloud — Stockage Sécurisé Souverain</title>
    <meta name="description" content="Cloud souverain AES-256-GCM. Chiffrement militaire, multi-tenant, hébergé en Belgique.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <style>
        body { background: var(--bg-primary); overflow-x: hidden; }

        /* ── Nav ── */
        .landing-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 var(--space-8); height: 72px;
            background: rgba(15, 17, 26, 0.8); backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border-subtle);
        }
        .landing-nav .nav-brand { display: flex; align-items: center; gap: var(--space-3); }
        .landing-nav .nav-brand img { height: 28px; }
        .landing-nav .nav-brand span { font-weight: 700; font-size: 16px; letter-spacing: -0.02em; }
        .landing-nav .nav-links { display: flex; align-items: center; gap: var(--space-6); }
        .landing-nav .nav-links a {
            font-size: 14px; color: var(--text-secondary); font-weight: 500;
            transition: color var(--duration-fast);
        }
        .landing-nav .nav-links a:hover { color: var(--text-primary); }
        .landing-nav .nav-actions { display: flex; align-items: center; gap: var(--space-3); }

        /* ── Hero ── */
        .hero {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            text-align: center; padding: var(--space-16) var(--space-6);
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(37, 99, 235, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 50%, rgba(6, 182, 212, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 0%, rgba(139, 92, 246, 0.05) 0%, transparent 40%);
        }
        .hero::after {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(rgba(37, 99, 235, 0.07) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
        }
        .hero-content { position: relative; z-index: 1; max-width: 800px; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: var(--space-2);
            padding: var(--space-2) var(--space-4); border-radius: var(--radius-full);
            background: rgba(37, 99, 235, 0.1); border: 1px solid rgba(37, 99, 235, 0.2);
            font-size: 12px; font-weight: 600; color: var(--blue-400);
            text-transform: uppercase; letter-spacing: 0.08em;
            margin-bottom: var(--space-6); animation: slideUp 0.6s var(--ease-out);
        }
        .hero-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--emerald-400); animation: pulse 2s infinite; }
        .hero h1 {
            font-size: clamp(36px, 6vw, 64px); font-weight: 800; letter-spacing: -0.04em;
            line-height: 1.1; margin-bottom: var(--space-6); animation: slideUp 0.6s var(--ease-out) 0.1s both;
        }
        .hero h1 .gradient {
            background: linear-gradient(135deg, var(--blue-500), var(--cyan-400));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero p {
            font-size: 18px; color: var(--text-secondary); max-width: 560px; margin: 0 auto var(--space-8);
            line-height: 1.7; animation: slideUp 0.6s var(--ease-out) 0.2s both;
        }
        .hero-actions {
            display: flex; gap: var(--space-4); justify-content: center; flex-wrap: wrap;
            animation: slideUp 0.6s var(--ease-out) 0.3s both;
        }
        .hero-actions .btn-lg { padding: var(--space-4) var(--space-8); font-size: 15px; border-radius: var(--radius-lg); }
        .hero-stats {
            display: flex; gap: var(--space-10); justify-content: center; margin-top: var(--space-12);
            animation: slideUp 0.6s var(--ease-out) 0.4s both;
        }
        .hero-stat { text-align: center; }
        .hero-stat .stat-value { font-size: 28px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
        .hero-stat .stat-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-top: var(--space-1); }

        /* ── Terminal ── */
        .hero-terminal {
            margin-top: var(--space-10); padding: var(--space-5); border-radius: var(--radius-xl);
            background: var(--bg-card); border: 1px solid var(--border-subtle);
            text-align: left; font-family: var(--font-mono); font-size: 13px;
            max-width: 520px; margin-left: auto; margin-right: auto;
            animation: slideUp 0.6s var(--ease-out) 0.5s both;
            box-shadow: var(--shadow-lg);
        }
        .terminal-bar {
            display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-4);
            padding-bottom: var(--space-3); border-bottom: 1px solid var(--border-subtle);
        }
        .terminal-dot { width: 10px; height: 10px; border-radius: 50%; }
        .terminal-dot.r { background: #EF4444; }
        .terminal-dot.y { background: #F59E0B; }
        .terminal-dot.g { background: #10B981; }
        .terminal-line { color: var(--text-muted); line-height: 1.8; }
        .terminal-line .prompt { color: var(--emerald-400); }
        .terminal-line .cmd { color: var(--cyan-400); }
        .terminal-line .output { color: var(--text-secondary); }
        .terminal-line .success { color: var(--emerald-400); }

        /* ── Features ── */
        .features-section {
            padding: var(--space-16) var(--space-6);
            background: linear-gradient(180deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }
        .features-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-6);
            max-width: 1100px; margin: 0 auto;
        }
        .feature-card {
            padding: var(--space-8); border-radius: var(--radius-xl);
            background: var(--bg-card); border: 1px solid var(--border-subtle);
            transition: all var(--duration-smooth) var(--ease-out);
            position: relative; overflow: hidden;
        }
        .feature-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--blue-500), var(--cyan-400));
            opacity: 0; transition: opacity var(--duration-smooth);
        }
        .feature-card:hover { border-color: rgba(37, 99, 235, 0.3); transform: translateY(-4px); box-shadow: var(--shadow-card); }
        .feature-card:hover::before { opacity: 1; }
        .feature-icon {
            width: 48px; height: 48px; border-radius: var(--radius-lg);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: var(--space-5);
        }
        .feature-icon.blue { background: rgba(37, 99, 235, 0.12); color: var(--blue-400); }
        .feature-icon.cyan { background: rgba(6, 182, 212, 0.12); color: var(--cyan-400); }
        .feature-icon.emerald { background: rgba(16, 185, 129, 0.12); color: var(--emerald-400); }
        .feature-icon.purple { background: rgba(139, 92, 246, 0.12); color: var(--purple-500); }
        .feature-icon.amber { background: rgba(245, 158, 11, 0.12); color: var(--amber-400); }
        .feature-icon.rose { background: rgba(239, 68, 68, 0.12); color: var(--rose-500); }
        .feature-card h3 { font-size: 16px; font-weight: 700; margin-bottom: var(--space-2); letter-spacing: -0.01em; }
        .feature-card p { font-size: 14px; color: var(--text-secondary); line-height: 1.6; }

        /* ── Pricing Preview ── */
        .pricing-section {
            padding: var(--space-16) var(--space-6);
        }
        .pricing-section .section-title {
            font-size: clamp(24px, 3vw, 32px);
        }
        .lp-pricing-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
            max-width: 1000px; margin: 0 auto;
        }
        .lp-pricing-card {
            background: var(--bg-card); border-radius: 16px; padding: 36px 28px;
            text-align: left; position: relative; border: 1px solid var(--border-subtle);
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            display: flex; flex-direction: column;
        }
        .lp-pricing-card:hover {
            border-color: rgba(37, 99, 235, 0.4); transform: translateY(-4px);
            box-shadow: 0 8px 40px rgba(0,0,0,0.3);
        }
        .lp-pricing-card.popular {
            border-color: var(--blue-500);
            box-shadow: 0 8px 40px rgba(37, 99, 235, 0.15);
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.06) 0%, var(--bg-card) 100%);
        }
        .lp-pricing-badge {
            position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg, var(--blue-500), var(--cyan-400));
            color: #fff; padding: 4px 16px; border-radius: 9999px;
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .lp-pricing-name { font-size: 16px; font-weight: 700; margin-bottom: 12px; }
        .lp-pricing-price { display: flex; align-items: baseline; gap: 4px; margin-bottom: 24px; }
        .lp-pricing-amount { font-size: 40px; font-weight: 800; letter-spacing: -0.03em; color: var(--text-primary); line-height: 1; }
        .lp-pricing-period { font-size: 14px; color: var(--text-muted); }
        .lp-pricing-features { list-style: none; margin: 0 0 28px 0; flex: 1; }
        .lp-pricing-feature {
            padding: 8px 0; font-size: 13px; color: var(--text-secondary);
            display: flex; align-items: center; gap: 10px;
            border-top: 1px solid var(--border-subtle);
        }
        .lp-pricing-feature:first-child { border-top: none; padding-top: 0; }
        .lp-pricing-feature i { color: var(--emerald-400); font-size: 12px; width: 16px; text-align: center; flex-shrink: 0; }
        .lp-pricing-card .btn { width: 100%; }

        /* ── Trust ── */
        .trust-section {
            padding: var(--space-12) var(--space-6);
            border-top: 1px solid var(--border-subtle);
        }
        .trust-grid {
            display: flex; justify-content: center; gap: var(--space-10); flex-wrap: wrap;
            max-width: 900px; margin: 0 auto;
        }
        .trust-item { text-align: center; }
        .trust-item .trust-icon { font-size: 28px; margin-bottom: var(--space-2); }
        .trust-item .trust-label { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .trust-item .trust-sub { font-size: 11px; color: var(--text-muted); }

        /* ── CTA ── */
        .cta-section {
            padding: var(--space-16) var(--space-6); text-align: center;
            background: radial-gradient(ellipse at center, rgba(37, 99, 235, 0.06) 0%, transparent 60%);
        }
        .cta-box {
            max-width: 600px; margin: 0 auto; padding: var(--space-12) var(--space-8);
            background: var(--bg-card); border: 1px solid var(--border-subtle);
            border-radius: var(--radius-2xl); position: relative; overflow: hidden;
        }
        .cta-box::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), rgba(6, 182, 212, 0.05));
        }
        .cta-box h2 { font-size: 28px; font-weight: 800; letter-spacing: -0.03em; margin-bottom: var(--space-4); position: relative; }
        .cta-box p { font-size: 15px; color: var(--text-secondary); margin-bottom: var(--space-6); position: relative; }
        .cta-box .btn { position: relative; }

        /* ── Footer ── */
        .landing-footer {
            padding: var(--space-8) var(--space-6);
            border-top: 1px solid var(--border-subtle);
            display: flex; align-items: center; justify-content: space-between;
            font-size: 12px; color: var(--text-muted);
        }
        .landing-footer a { color: var(--text-secondary); transition: color var(--duration-fast); }
        .landing-footer a:hover { color: var(--blue-400); }
        .footer-links { display: flex; gap: var(--space-6); }

        @media (max-width: 768px) {
            .features-grid { grid-template-columns: 1fr; }
            .lp-pricing-grid { grid-template-columns: 1fr; max-width: 380px; }
            .hero-stats { flex-direction: column; gap: var(--space-6); }
            .landing-nav .nav-links { display: none; }
            .trust-grid { gap: var(--space-6); }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="landing-nav">
        <div class="nav-brand">
            <img src="/img/logo.png" alt="SAEC">
            <span>SAEC <span style="color:var(--cyan-400)">Cloud</span></span>
        </div>
        <div class="nav-links">
            <a href="#features">Fonctionnalités</a>
            <a href="/pricing">Tarifs</a>
            <a href="#trust">Sécurité</a>
        </div>
        <div class="nav-actions">
            <a href="/login" class="btn btn-ghost">Connexion</a>
            <a href="/subscribe?plan=professional" class="btn btn-primary">Commencer</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="dot"></span>
                Stack souverain — Hébergé en Belgique
            </div>
            <h1>Votre cloud.<br><span class="gradient">Vos règles.</span></h1>
            <p>Stockage sécurisé AES-256-GCM, multi-tenant, chiffré de bout en bout. Aucune fuite. Aucun compromis. Zéro dépendance aux GAFAM.</p>
            <div class="hero-actions">
                <a href="/subscribe?plan=professional" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket"></i> Démarrer maintenant
                </a>
                <a href="/pricing" class="btn btn-outline btn-lg">
                    Voir les tarifs
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="stat-value">AES-256</div>
                    <div class="stat-label">Chiffrement</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-value">RGPD</div>
                    <div class="stat-label">Conforme</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-value">0</div>
                    <div class="stat-label">Fuite de données</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">Uptime</div>
                </div>
            </div>

            <!-- Terminal -->
            <div class="hero-terminal">
                <div class="terminal-bar">
                    <div class="terminal-dot r"></div>
                    <div class="terminal-dot y"></div>
                    <div class="terminal-dot g"></div>
                    <span style="font-size:11px; color:var(--text-muted); margin-left:var(--space-2);">saec-cloud</span>
                </div>
                <div class="terminal-line"><span class="prompt">$</span> <span class="cmd">saec</span> deploy --stack sovereign</div>
                <div class="terminal-line"><span class="output">⠋ Initializing AES-256-GCM encryption...</span></div>
                <div class="terminal-line"><span class="success">✓</span> <span class="output">Multi-tenant isolation active</span></div>
                <div class="terminal-line"><span class="success">✓</span> <span class="output">E2E encryption enabled</span></div>
                <div class="terminal-line"><span class="success">✓</span> <span class="output">Sovereign stack deployed to belgium-west-1</span></div>
                <div class="terminal-line"><span class="prompt">$</span> <span class="cmd">saec</span> status</div>
                <div class="terminal-line"><span class="success">● SAEC_CLOUD // STATUS: NOMINAL</span></div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section" id="features">
        <div style="text-align:center; max-width:600px; margin:0 auto var(--space-12);">
            <span class="eyebrow" style="margin-bottom:var(--space-4); display:inline-block;">Fonctionnalités</span>
            <h2 class="section-title" style="margin-bottom:var(--space-4);">Tout ce qu'un cloud sovereign.</h2>
            <p class="section-desc" style="margin:0 auto;">Pas de compromis sur la sécurité. Pas de compromis sur la souveraineté. Pas de compromis sur l'UX.</p>
        </div>
        <div class="features-grid" data-stagger>
            <div class="feature-card">
                <div class="feature-icon blue"><i class="fas fa-shield-halved"></i></div>
                <h3>Chiffrement AES-256-GCM</h3>
                <p>Chaque fichier chiffré individuellement. Clés générées par fichier, jamais en clair. Chiffrement militaire.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon cyan"><i class="fas fa-layer-group"></i></div>
                <h3>Multi-Tenant Isolé</h3>
                <p>Chaque tenant isolé cryptographiquement. Aucun cross-tenant access possible. Données physiquement séparées.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon emerald"><i class="fas fa-server"></i></div>
                <h3>Hébergement Souverain</h3>
                <p>Infrastructure privée Synology. Aucun cloud public. Vos données ne quittent jamais notre stack.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon purple"><i class="fas fa-users"></i></div>
                <h3>Collaboration d'Équipe</h3>
                <p>Dossiers partagés, permissions granulaires, partages externes avec expiration. Travaillez ensemble en toute sécurité.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon amber"><i class="fas fa-clock-rotate-left"></i></div>
                <h3>Versions & Corbeille</h3>
                <p>Historique des versions, restauration en un clic, corbeille configurable. Jamais plus de fichier perdu.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon rose"><i class="fas fa-file-shield"></i></div>
                <h3>Audit & Conformité</h3>
                <p>Logs d'audit complets, traçabilité totale, conformité RGPD. Prouvez votre conformité en un clic.</p>
            </div>
        </div>
    </section>

    <!-- Pricing Preview -->
    <section class="pricing-section" id="pricing">
        <div style="text-align:center; max-width:600px; margin:0 auto var(--space-10);">
            <span class="eyebrow" style="margin-bottom:var(--space-4); display:inline-block;">Tarifs</span>
            <h2 class="section-title" style="margin-bottom:var(--space-4);">Simple. Transparent. Juste.</h2>
            <p class="section-desc" style="margin:0 auto;">Pas de frais cachés. Pas de surprises. Annulation à tout moment.</p>
        </div>
        <?php
        $previewPlans = [
            ['name' => 'Starter', 'price' => '€9', 'color' => '#06b6d4', 'features' => ['10 GB de stockage', '1 utilisateur', 'Partages externes', 'Chiffrement AES-256']],
            ['name' => 'Professional', 'price' => '€29', 'color' => '#8B5CF6', 'popular' => true, 'features' => ['100 GB de stockage', '5 utilisateurs', 'Dossiers team', 'Versions fichiers', 'API access']],
            ['name' => 'Enterprise', 'price' => 'Sur devis', 'color' => '#10B981', 'features' => ['Stockage illimité', '15+ utilisateurs', 'Branding custom', 'SLA 99.99%']],
        ];
        ?>
        <div class="lp-pricing-grid" data-stagger>
            <?php foreach ($previewPlans as $plan): ?>
            <div class="lp-pricing-card <?= $plan['popular'] ?? false ? 'popular' : '' ?>">
                <?php if ($plan['popular'] ?? false): ?>
                <div class="lp-pricing-badge">Populaire</div>
                <?php endif; ?>
                <div class="lp-pricing-name" style="color: <?= $plan['color'] ?>"><?= $plan['name'] ?></div>
                <div class="lp-pricing-price">
                    <span class="lp-pricing-amount"><?= $plan['price'] ?></span>
                    <?php if ($plan['price'] !== 'Sur devis'): ?>
                    <span class="lp-pricing-period">/mois</span>
                    <?php endif; ?>
                </div>
                <ul class="lp-pricing-features">
                    <?php foreach ($plan['features'] as $f): ?>
                    <li class="lp-pricing-feature"><i class="fas fa-check"></i> <?= $f ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="/subscribe?plan=<?= strtolower($plan['name']) ?>" class="btn <?= $plan['popular'] ?? false ? 'btn-primary' : 'btn-outline' ?>">
                    Commencer →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center; margin-top:var(--space-8);">
            <a href="/pricing" style="color:var(--blue-400); font-size:14px; font-weight:500;">Voir tous les détails <i class="fas fa-arrow-right" style="font-size:12px;"></i></a>
        </div>
    </section>

    <!-- Trust -->
    <section class="trust-section" id="trust">
        <div class="trust-grid">
            <div class="trust-item">
                <div class="trust-icon">🔒</div>
                <div class="trust-label">AES-256-GCM</div>
                <div class="trust-sub">Chiffrement militaire</div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">🇧🇪</div>
                <div class="trust-label">Hébergé en Belgique</div>
                <div class="trust-sub">Infrastructure souveraine</div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">🛡️</div>
                <div class="trust-label">RGPD Conforme</div>
                <div class="trust-sub">Privacy by design</div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">🏗️</div>
                <div class="trust-label">Open Source Stack</div>
                <div class="trust-sub">Zero vendor lock-in</div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="cta-box">
            <h2>Prêt à reprendre le contrôle ?</h2>
            <p>Créez votre compte en 2 minutes. Aucune carte bancaire requise pour l'essai.</p>
            <a href="/subscribe?plan=professional" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket"></i> Commencer gratuitement
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div>© <?= date('Y') ?> SAEC Ltd. — Bespoke Systems Engineering</div>
        <div class="footer-links">
            <a href="/pricing">Tarifs</a>
            <a href="/login">Connexion</a>
            <a href="https://saec.me" target="_blank">saec.me</a>
        </div>
    </footer>

    <script>
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const target = document.querySelector(a.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // Navbar background on scroll
    const nav = document.querySelector('.landing-nav');
    window.addEventListener('scroll', () => {
        nav.style.borderBottomColor = window.scrollY > 50 ? 'var(--border-subtle)' : 'transparent';
    });
    </script>

</body>
</html>
