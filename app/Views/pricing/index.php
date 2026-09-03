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

<section class="pricing-hero">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <span class="eyebrow">Pricing</span>
        <h1 class="section-title mt-5">Un stockage sécurisé, à votre mesure.</h1>
        <p class="section-desc mx-auto mt-4">
            Chiffrement militaire AES-256-GCM. Multi-tenant. Souveraineté totale de vos données.
        </p>
    </div>
</section>

<section class="pricing-grid-section">
    <div class="max-w-7xl mx-auto px-6">
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
            <div class="pricing-card <?= $plan['popular'] ?? false ? 'popular' : '' ?>">
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
                        <i class="fas fa-check" style="color: <?= $plan['color'] ?>"></i>
                        <?= $feature ?>
                    </li>
                    <?php endforeach; ?>
                    <?php if (($plan['extra_user_price'] ?? 0) > 0): ?>
                    <li class="pricing-feature extra-users" style="color: var(--amber-400); font-size: 12px;">
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

                <a href="/subscribe?plan=<?= strtolower($plan['name']) ?>" class="btn <?= $plan['popular'] ?? false ? 'btn-primary' : 'btn-ghost' ?>" style="<?= $plan['popular'] ?? false ? "background:{$plan['color']};color:#040918" : "border-color:{$plan['color']};color:{$plan['color']}" ?>">
                    Commencer →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pricing-faq">
    <div class="max-w-3xl mx-auto px-6">
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
