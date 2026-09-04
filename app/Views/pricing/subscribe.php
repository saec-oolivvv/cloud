<?php
/**
 * SAEC Cloud — Questionnaire de Souscription
 * Étape 1: Choix du plan
 * Étape 2: Informations entreprise
 * Étape 3: Besoins spécifiques
 * Étape 4: Confirmation
 */

$plan = $_GET['plan'] ?? 'professional';
$plans = [
    'starter' => ['name' => 'Starter', 'price' => '€9/mois', 'storage' => '10 GB', 'users' => 5],
    'professional' => ['name' => 'Professional', 'price' => '€29/mois', 'storage' => '100 GB', 'users' => 25],
    'enterprise' => ['name' => 'Enterprise', 'price' => 'Sur devis', 'storage' => 'Illimité', 'users' => 'Illimité'],
];
$currentPlan = $plans[$plan] ?? $plans['professional'];
?>

<section class="subscribe-hero">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <span class="eyebrow">Souscription</span>
        <h1 class="section-title mt-5">Rejoindre SAEC Cloud</h1>
        <p class="section-desc mx-auto mt-4">
            Plan <strong class="text-accent"><?= $currentPlan['name'] ?></strong> — <?= $currentPlan['price'] ?>
        </p>
    </div>
</section>

<section class="subscribe-form-section">
    <div class="max-w-2xl mx-auto px-6">
        <!-- Progress bar -->
        <div class="subscribe-progress">
            <div class="subscribe-step active" data-step="1">
                <span class="step-num">1</span>
                <span class="step-label">Entreprise</span>
            </div>
            <div class="subscribe-step-line"></div>
            <div class="subscribe-step" data-step="2">
                <span class="step-num">2</span>
                <span class="step-label">Contact</span>
            </div>
            <div class="subscribe-step-line"></div>
            <div class="subscribe-step" data-step="3">
                <span class="step-num">3</span>
                <span class="step-label">Besoins</span>
            </div>
            <div class="subscribe-step-line"></div>
            <div class="subscribe-step" data-step="4">
                <span class="step-num">4</span>
                <span class="step-label">Envoyer</span>
            </div>
        </div>

        <form id="subscribeForm" class="subscribe-form" data-stagger>
            <input type="hidden" name="plan" value="<?= $plan ?>">

            <!-- Step 1: Entreprise -->
            <div class="form-step active" data-step="1">
                <h3 class="form-step-title">Votre entreprise</h3>

                <div class="form-group">
                    <label class="form-label">Nom de l'entreprise *</label>
                    <input type="text" name="company" class="form-input" required placeholder="Ex: ACME Corp">
                </div>

                <div class="form-group">
                    <label class="form-label">Site web</label>
                    <input type="url" name="website" class="form-input" placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Secteur d'activité *</label>
                    <select name="industry" class="form-input" required>
                        <option value="">Choisir...</option>
                        <option value="tech">Technologie / IT</option>
                        <option value="finance">Finance / Banque</option>
                        <option value="health">Santé</option>
                        <option value="legal">Juridique / Avocat</option>
                        <option value="education">Éducation</option>
                        <option value="media">Média / Communication</option>
                        <option value="retail">Commerce / Distribution</option>
                        <option value="industry">Industrie</option>
                        <option value="public">Secteur public</option>
                        <option value="other">Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre d'employés *</label>
                    <select name="company_size" class="form-input" required>
                        <option value="">Choisir...</option>
                        <option value="1-5">1-5</option>
                        <option value="6-20">6-20</option>
                        <option value="21-50">21-50</option>
                        <option value="51-200">51-200</option>
                        <option value="200+">200+</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Pays *</label>
                    <select name="country" class="form-input" required>
                        <option value="">Choisir...</option>
                        <option value="BE" selected>Belgique</option>
                        <option value="FR">France</option>
                        <option value="LU">Luxembourg</option>
                        <option value="NL">Pays-Bas</option>
                        <option value="DE">Allemagne</option>
                        <option value="GB">Royaume-Uni</option>
                        <option value="US">États-Unis</option>
                        <option value="OTHER">Autre</option>
                    </select>
                </div>

                <button type="button" class="btn btn-primary next-step" data-next="2">Suivant →</button>
            </div>

            <!-- Step 2: Contact -->
            <div class="form-step" data-step="2">
                <h3 class="form-step-title">Personne de contact</h3>

                <div class="form-group">
                    <label class="form-label">Nom complet *</label>
                    <input type="text" name="contact_name" class="form-input" required placeholder="Jean Dupont">
                </div>

                <div class="form-group">
                    <label class="form-label">Email professionnel *</label>
                    <input type="email" name="contact_email" class="form-input" required placeholder="jean@acme.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="contact_phone" class="form-input" placeholder="+32 470 12 34 56">
                </div>

                <div class="form-group">
                    <label class="form-label">Poste</label>
                    <input type="text" name="contact_role" class="form-input" placeholder="Directeur IT">
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-ghost prev-step" data-prev="1">← Retour</button>
                    <button type="button" class="btn btn-primary next-step" data-next="3">Suivant →</button>
                </div>
            </div>

            <!-- Step 3: Besoins -->
            <div class="form-step" data-step="3">
                <h3 class="form-step-title">Vos besoins</h3>

                <div class="form-group">
                    <label class="form-label">Usage principal *</label>
                    <select name="use_case" class="form-input" required>
                        <option value="">Choisir...</option>
                        <option value="files">Stockage et partage de fichiers</option>
                        <option value="backup">Backup et archivage</option>
                        <option value="collaboration">Collaboration d'équipe</option>
                        <option value="client">Partage avec clients</option>
                        <option value="compliance">Conformité (RGPD, etc.)</option>
                        <option value="other">Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Volume de fichiers estimé</label>
                    <select name="file_volume" class="form-input">
                        <option value="">Choisir...</option>
                        <option value="<100">Moins de 100 fichiers</option>
                        <option value="100-1000">100 à 1 000 fichiers</option>
                        <option value="1000-10000">1 000 à 10 000 fichiers</option>
                        <option value=">10000">Plus de 10 000 fichiers</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Types de fichiers principaux</label>
                    <input type="text" name="file_types" class="form-input" placeholder="PDF, images, documents Word...">
                </div>

                <div class="form-group">
                    <label class="form-label">Besoins spécifiques</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="needs[]" value="sync"> Synchronisation multi-appareils
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="needs[]" value="api"> Accès API
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="needs[]" value="branding"> Personnalisation branding
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="needs[]" value="audit"> Logs d'audit avancés
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="needs[]" value="2fa"> Double authentification
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="needs[]" value="sla"> SLA garanti
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Date de souhaitée</label>
                    <input type="date" name="desired_date" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Message additionnel</label>
                    <textarea name="message" class="form-input" rows="3" placeholder="Décrivez vos besoins..."></textarea>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-ghost prev-step" data-prev="2">← Retour</button>
                    <button type="button" class="btn btn-primary next-step" data-next="4">Suivant →</button>
                </div>
            </div>

            <!-- Step 4: Confirmation -->
            <div class="form-step" data-step="4">
                <h3 class="form-step-title">Confirmer la demande</h3>

                <div class="subscribe-summary">
                    <div class="summary-row">
                        <span>Plan</span>
                        <strong class="text-accent"><?= $currentPlan['name'] ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Prix</span>
                        <strong><?= $currentPlan['price'] ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Stockage</span>
                        <strong><?= $currentPlan['storage'] ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Utilisateurs max</span>
                        <strong><?= $currentPlan['users'] ?></strong>
                    </div>
                </div>

                <div class="subscribe-notice">
                    <i class="fas fa-info-circle"></i>
                    Votre demande sera examinée par notre équipe. Vous recevrez une facture par email sous 24h.
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-ghost prev-step" data-prev="3">← Retour</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Envoyer la demande
                    </button>
                </div>
            </div>
        </form>

        <!-- Success message -->
        <div id="subscribeSuccess" class="subscribe-success subscribe-success--hidden">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Demande envoyée !</h3>
            <p>Notre équipe va examiner votre demande. Vous recevrez une facture par email sous 24h.</p>
            <a href="/login" class="btn btn-primary mt-4">Retour au login</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('subscribeForm');
    const steps = document.querySelectorAll('.form-step');
    const stepIndicators = document.querySelectorAll('.subscribe-step');
    const submitBtn = form.querySelector('[type="submit"]');

    // Next step
    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', function() {
            const next = parseInt(this.dataset.next);
            goToStep(next);
        });
    });

    // Previous step
    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', function() {
            const prev = parseInt(this.dataset.prev);
            goToStep(prev);
        });
    });

    function goToStep(step) {
        steps.forEach(s => s.classList.remove('active'));
        stepIndicators.forEach(s => s.classList.remove('active', 'done'));

        // Mark previous steps as done
        for (let i = 1; i < step; i++) {
            document.querySelector(`.subscribe-step[data-step="${i}"]`).classList.add('done');
        }

        document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');
        document.querySelector(`.subscribe-step[data-step="${step}"]`).classList.add('active');

        // Activate step lines
        document.querySelectorAll('.subscribe-step-line').forEach((line, idx) => {
            line.classList.toggle('active', idx < step - 1);
        });
    }

    // Submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

        const data = new FormData(form);

        fetch('/subscribe', {
            method: 'POST',
            body: data
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                form.style.display = 'none';
                document.getElementById('subscribeSuccess').classList.remove('subscribe-success--hidden');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer la demande';
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer la demande';
        });
    });
});
</script>
