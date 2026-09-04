<?php
/**
 * SAEC Cloud — Pricing Page (Standalone)
 */
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'en';
$availableLangs = $lang ? Saec\Core\Translation::getAvailable() : [];
$plans = [
    [
        'name' => 'Starter',
        'price' => '€9',
        'period' => '/mois',
        'color' => '#06b6d4',
        'features' => [
            '10 GB de stockage',
            '1 utilisateur inclus',
            '500 fichiers max',
            '1 GB par fichier',
            'Partages externes',
            'Chiffrement E2E AES-256',
            'Corbeille 30 jours',
            'Support email',
        ],
        'not_included' => ['Dossiers team', 'Versions fichiers', 'API access', 'Audit logs', 'Branding custom', 'SLA 99.99%'],
        'extra_user_price' => 2,
    ],
    [
        'name' => 'Professional',
        'price' => '€29',
        'period' => '/mois',
        'color' => '#a855f7',
        'popular' => true,
        'features' => [
            '100 GB de stockage',
            '5 utilisateurs inclus',
            '5 000 fichiers max',
            '10 GB par fichier',
            'Partages externes',
            'Chiffrement E2E AES-256',
            'Dossiers team',
            'Versions fichiers (30j)',
            'Corbeille 30 jours',
            'Support prioritaire',
            'API access',
            'Audit logs (90j)',
        ],
        'not_included' => [],
        'extra_user_price' => 1.50,
    ],
    [
        'name' => 'Enterprise',
        'price' => 'Sur devis',
        'period' => '',
        'color' => '#10B981',
        'features' => [
            'Stockage illimité',
            '15 utilisateurs inclus',
            'Fichiers illimités',
            '50 GB par fichier',
            'Partages externes',
            'Chiffrement E2E AES-256',
            'Dossiers team',
            'Versions fichiers (illimité)',
            'Corbeille 90 jours',
            'Support dédié',
            'API access',
            'Audit logs (illimité)',
            'Branding custom',
            'SLA 99.99%',
        ],
        'not_included' => [],
        'extra_user_price' => 1,
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifs — SAEC Cloud</title>
    <meta name="description" content="Cloud souverain AES-256-GCM. Tarifs transparents, pas de frais cachés.">
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

        .pricing-hero {
            padding: 120px 24px 60px; text-align: center;
            background: radial-gradient(ellipse at top, rgba(37, 99, 235, 0.08) 0%, transparent 60%);
        }
        .pricing-hero h1 {
            font-size: clamp(28px, 4vw, 42px); font-weight: 800; letter-spacing: -0.03em;
            background: linear-gradient(135deg, var(--text-primary), var(--cyan-400));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin-top: 20px;
        }
        .pricing-hero p {
            font-size: 16px; color: var(--text-secondary); max-width: 520px;
            margin: 16px auto 0; line-height: 1.6;
        }

        .pricing-section { padding: 20px 24px 80px; }
        .pricing-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
            max-width: 1100px; margin: 0 auto;
        }
        .pricing-card {
            background: var(--bg-card); border-radius: 16px; padding: 40px 28px;
            text-align: left; position: relative; border: 1px solid var(--border-subtle);
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            display: flex; flex-direction: column;
        }
        .pricing-card:hover { border-color: rgba(37, 99, 235, 0.4); transform: translateY(-4px); box-shadow: 0 8px 40px rgba(0,0,0,0.3); }
        .pricing-card.popular {
            border-color: var(--blue-500);
            box-shadow: 0 8px 40px rgba(37, 99, 235, 0.15);
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.06) 0%, var(--bg-card) 100%);
        }
        .pricing-badge {
            position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg, var(--blue-500), var(--cyan-400));
            color: #fff; padding: 4px 16px; border-radius: 9999px;
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .pricing-name { font-size: 16px; font-weight: 700; }
        .pricing-price { margin-top: 12px; display: flex; align-items: baseline; gap: 4px; }
        .pricing-amount { font-size: 40px; font-weight: 800; letter-spacing: -0.03em; color: var(--text-primary); line-height: 1; }
        .pricing-period { font-size: 14px; color: var(--text-muted); }
        .pricing-features { list-style: none; margin: 28px 0; flex: 1; }
        .pricing-feature {
            padding: 8px 0; font-size: 13px; color: var(--text-secondary);
            display: flex; align-items: center; gap: 12px;
        }
        .pricing-feature.included i { color: var(--emerald-400); font-size: 12px; width: 16px; text-align: center; }
        .pricing-feature.excluded { color: var(--text-muted); opacity: 0.5; }
        .pricing-feature.excluded i { color: var(--text-muted); font-size: 10px; width: 16px; text-align: center; }
        .pricing-feature.extra-users {
            padding: 8px 0; font-size: 12px; color: var(--amber-400);
            display: flex; align-items: center; gap: 8px;
        }
        .pricing-card .btn { width: 100%; margin-top: auto; }

        .pricing-faq { padding: 60px 24px 80px; max-width: 700px; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid var(--border-subtle); padding: 24px 0; }
        .faq-item:last-child { border-bottom: none; }
        .faq-question { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px; }
        .faq-answer { font-size: 14px; color: var(--text-secondary); line-height: 1.6; }

        .pricing-trust {
            padding: 48px 24px; border-top: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
        }
        .trust-badges {
            display: flex; justify-content: center; gap: 48px; flex-wrap: wrap;
            max-width: 800px; margin: 0 auto;
        }
        .trust-badge {
            display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center;
        }
        .trust-badge i { font-size: 24px; color: var(--blue-400); }
        .trust-label { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .trust-desc { font-size: 11px; color: var(--text-muted); }

        .landing-footer {
            padding: 32px 24px; display: flex; align-items: center; justify-content: space-between;
            font-size: 12px; color: var(--text-muted);
        }
        .landing-footer a { color: var(--text-secondary); transition: color 0.15s; }
        .landing-footer a:hover { color: var(--blue-400); }
        .footer-links { display: flex; gap: 24px; }

        @media (max-width: 768px) {
            .pricing-grid { grid-template-columns: 1fr; max-width: 400px; }
            .landing-nav .nav-links { display: none; }
            .trust-badges { gap: 24px; }
        }
    </style>
</head>
<body>

    <!-- Nav -->
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

    <!-- Hero -->
    <section class="pricing-hero">
        <span class="eyebrow">Tarifs</span>
        <h1>Un stockage sécurisé, à votre mesure.</h1>
        <p>Chiffrement militaire AES-256-GCM. Multi-tenant. Souveraineté totale de vos données.</p>
    </section>

    <!-- Plans -->
    <section class="pricing-section">
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
            <div class="pricing-card <?= ($plan['popular'] ?? false) ? 'popular' : '' ?>">
                <?php if ($plan['popular'] ?? false): ?>
                <div class="pricing-badge">Populaire</div>
                <?php endif; ?>

                <div class="pricing-header">
                    <h3 class="pricing-name" style="color: <?= $plan['color'] ?>"><?= $plan['name'] ?></h3>
                    <div class="pricing-price">
                        <span class="pricing-amount"><?= $plan['price'] ?></span>
                        <span class="pricing-period"><?= $plan['period'] ?></span>
                    </div>
                </div>

                <ul class="pricing-features">
                    <?php foreach ($plan['features'] as $feature): ?>
                    <li class="pricing-feature included">
                        <i class="fas fa-check"></i>
                        <?= $feature ?>
                    </li>
                    <?php endforeach; ?>
                    <?php if (($plan['extra_user_price'] ?? 0) > 0): ?>
                    <li class="pricing-feature extra-users">
                        <i class="fas fa-user-plus"></i>
                        Utilisateurs supplémentaires : <?= number_format($plan['extra_user_price'], 2) ?>€/mois chacun
                    </li>
                    <?php endif; ?>
                    <?php foreach ($plan['not_included'] as $feature): ?>
                    <li class="pricing-feature excluded">
                        <i class="fas fa-minus"></i>
                        <?= $feature ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <a href="/subscribe?plan=<?= strtolower($plan['name']) ?>" class="btn <?= ($plan['popular'] ?? false) ? 'btn-primary' : 'btn-outline' ?>">
                    Commencer →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- FAQ -->
    <section class="pricing-faq">
        <div style="text-align:center; margin-bottom:40px;">
            <span class="eyebrow" style="margin-bottom:16px; display:inline-block;">FAQ</span>
            <h2 class="section-title">Questions fréquentes</h2>
        </div>

        <div class="faq-item">
            <h4 class="faq-question">Comment fonctionne la facturation ?</h4>
            <p class="faq-answer">Après soumission de votre demande, notre équipe vous envoie une facture par email. Aucun paiement en ligne pour le moment.</p>
        </div>
        <div class="faq-item">
            <h4 class="faq-question">Puis-je changer de plan ?</h4>
            <p class="faq-answer">Oui, vous pouvez upgrader ou downgrader à tout moment. Le changement est effectif immédiatement avec calcul au prorata.</p>
        </div>
        <div class="faq-item">
            <h4 class="faq-question">Mes données sont-elles chiffrées ?</h4>
            <p class="faq-answer">Oui, tous les fichiers sont chiffrés en AES-256-GCM. Les clés sont générées par fichier et stockées de manière sécurisée.</p>
        </div>
        <div class="faq-item">
            <h4 class="faq-question">Où sont hébergées mes données ?</h4>
            <p class="faq-answer">Sur notre infrastructure privée (NAS Synology). Aucun cloud public. Vos données ne quittent jamais notre stack.</p>
        </div>
        <div class="faq-item">
            <h4 class="faq-question">Puis-je tester avant de m'engager ?</h4>
            <p class="faq-answer">Contactez-nous pour une démo personnalisée. Nous pouvons aussi configurer un essai gratuit de 14 jours.</p>
        </div>
    </section>

    <!-- Trust -->
    <section class="pricing-trust">
        <div class="trust-badges">
            <div class="trust-badge">
                <i class="fas fa-shield-halved"></i>
                <span class="trust-label">AES-256</span>
                <span class="trust-desc">Chiffrement bout en bout</span>
            </div>
            <div class="trust-badge">
                <i class="fas fa-scale-balanced"></i>
                <span class="trust-label">RGPD</span>
                <span class="trust-desc">Conformité européenne</span>
            </div>
            <div class="trust-badge">
                <i class="fas fa-flag"></i>
                <span class="trust-label">Souverain</span>
                <span class="trust-desc">Infrastructure 100% Belgique</span>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div>© <?= date('Y') ?> SAEC Ltd. — Bespoke Systems Engineering</div>
        <div class="footer-links">
            <a href="/">Accueil</a>
            <a href="/login">Connexion</a>
            <a href="https://saec.me" target="_blank">saec.me</a>
        </div>
    </footer>

</body>
</html>
