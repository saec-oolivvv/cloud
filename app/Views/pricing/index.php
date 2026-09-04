<?php
/**
 * SAEC Cloud — Pricing Page
 * 3 options: Starter, Professional, Enterprise
 */
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
        'not_included' => ['Dossiers team', 'Versions fichiers', 'API access', 'Audit logs'],
        'storage' => 10737418240,
        'max_users' => 1,
        'max_file_size' => 1073741824,
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
        'not_included' => ['Branding custom', 'SLA 99.99%'],
        'storage' => 107374182400,
        'max_users' => 5,
        'max_file_size' => 10737418240,
        'extra_user_price' => 1.50,
    ],
    [
        'name' => 'Enterprise',
        'price' => 'Sur devis',
        'period' => '',
        'color' => '#00ff88',
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
        'storage' => 0,
        'max_users' => 15,
        'max_file_size' => 53687091200,
        'extra_user_price' => 1,
    ],
];
?>

<!-- ═══ HERO ═══ -->
<section class="pricing-hero" data-stagger>
    <div class="max-w-7xl mx-auto px-6 text-center">
        <span class="eyebrow animate-fade-in">Pricing</span>
        <h1 class="section-title mt-5 animate-slide-up">Un stockage sécurisé, à votre mesure.</h1>
        <p class="section-desc mx-auto mt-4 animate-slide-up">
            Chiffrement militaire AES-256-GCM. Multi-tenant. Souveraineté totale de vos données.
        </p>
    </div>
</section>

<!-- ═══ PLANS ═══ -->
<section class="pricing-grid-section">
    <div class="max-w-7xl mx-auto px-6">
        <div class="pricing-grid" data-stagger>
            <?php foreach ($plans as $plan): ?>
            <div class="pricing-card <?= ($plan['popular'] ?? false) ? 'popular' : '' ?>" data-color="<?= $plan['color'] ?>">
                <?php if ($plan['popular'] ?? false): ?>
                <div class="pricing-badge">Populaire</div>
                <?php endif; ?>

                <div class="pricing-header">
                    <h3 class="pricing-name"><?= $plan['name'] ?></h3>
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
                        <span class="badge badge-warning"><i class="fas fa-user-plus"></i></span>
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

                <a href="/subscribe?plan=<?= strtolower($plan['name']) ?>" class="btn <?= ($plan['popular'] ?? false) ? 'btn-primary btn-lg' : 'btn-outline btn-lg' ?>">
                    Commencer →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ FAQ ═══ -->
<section class="pricing-faq">
    <div class="max-w-3xl mx-auto px-6">
        <span class="eyebrow pricing-faq-eyebrow">FAQ</span>
        <h2 class="section-title text-center">Questions fréquentes</h2>

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
    </div>
</section>

<!-- ═══ TRUST FOOTER ═══ -->
<section class="pricing-trust" data-stagger>
    <div class="max-w-7xl mx-auto px-6 text-center">
        <div class="trust-badges">
            <div class="trust-badge animate-slide-up">
                <i class="fas fa-shield-halved"></i>
                <span class="trust-label">AES-256</span>
                <span class="trust-desc">Chiffrement bout en bout</span>
            </div>
            <div class="trust-badge animate-slide-up">
                <i class="fas fa-scale-balanced"></i>
                <span class="trust-label">RGPD</span>
                <span class="trust-desc">Conformité européenne</span>
            </div>
            <div class="trust-badge animate-slide-up">
                <i class="fas fa-flag"></i>
                <span class="trust-label">Souverain</span>
                <span class="trust-desc">Infrastructure 100% française</span>
            </div>
        </div>
    </div>
</section>
