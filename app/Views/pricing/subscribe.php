<?php
/**
 * SAEC Cloud — Souscription (Standalone)
 */
$plan = $_GET['plan'] ?? 'professional';
$plans = [
    'starter' => ['name' => 'Starter', 'price' => '€9/mois', 'storage' => '10 GB', 'users' => 5],
    'professional' => ['name' => 'Professional', 'price' => '€29/mois', 'storage' => '100 GB', 'users' => 25],
    'enterprise' => ['name' => 'Enterprise', 'price' => 'Sur devis', 'storage' => 'Illimité', 'users' => 'Illimité'],
];
$currentPlan = $plans[$plan] ?? $plans['professional'];
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'en';
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Souscription — SAEC Cloud</title>
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

        .subscribe-hero {
            padding: 120px 24px 40px; text-align: center;
            background: radial-gradient(ellipse at top, rgba(37, 99, 235, 0.08) 0%, transparent 60%);
        }
        .subscribe-hero h1 {
            font-size: clamp(28px, 4vw, 42px); font-weight: 800; letter-spacing: -0.03em;
            background: linear-gradient(135deg, var(--text-primary), var(--cyan-400));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin-top: 20px;
        }
        .subscribe-hero p { font-size: 16px; color: var(--text-secondary); margin-top: 12px; }
        .subscribe-hero strong { color: var(--cyan-400); }

        .subscribe-form-section { padding: 20px 24px 80px; max-width: 640px; margin: 0 auto; }

        .subscribe-progress { display: flex; align-items: center; justify-content: center; gap: 0; margin-bottom: 40px; }
        .subscribe-step { display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .step-num {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--bg-card); border: 2px solid var(--border-subtle);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: var(--text-muted);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .subscribe-step.active .step-num {
            background: var(--blue-500); border-color: var(--blue-500); color: #fff;
            box-shadow: 0 0 20px rgba(37, 99, 235, 0.3);
        }
        .subscribe-step.done .step-num {
            background: var(--emerald-400); border-color: var(--emerald-400); color: #fff;
        }
        .step-label { font-size: 11px; color: var(--text-muted); font-weight: 500; }
        .subscribe-step.active .step-label { color: var(--text-primary); }
        .subscribe-step-line {
            width: 60px; height: 2px; background: var(--border-subtle);
            margin: 0 8px; margin-bottom: 18px; transition: background 0.3s;
        }
        .subscribe-step-line.active { background: var(--blue-500); }

        .subscribe-form {
            background: var(--bg-card); border: 1px solid var(--border-subtle);
            border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        .form-step { display: none; animation: fadeIn 0.3s ease-out; }
        .form-step.active { display: block; }
        .form-step-title { font-size: 18px; font-weight: 700; margin-bottom: 24px; letter-spacing: -0.01em; }
        .btn-group { display: flex; gap: 12px; justify-content: space-between; margin-top: 24px; }

        .subscribe-summary {
            background: var(--bg-secondary); border: 1px solid var(--border-subtle);
            border-radius: 12px; padding: 20px; margin-bottom: 24px;
        }
        .summary-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; font-size: 14px;
        }
        .summary-row + .summary-row { border-top: 1px solid var(--border-subtle); margin-top: 8px; padding-top: 8px; }
        .summary-row span { color: var(--text-secondary); }

        .subscribe-notice {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 16px; background: rgba(37, 99, 235, 0.06);
            border: 1px solid rgba(37, 99, 235, 0.15); border-radius: 8px;
            font-size: 13px; color: var(--text-secondary); margin-bottom: 24px;
        }
        .subscribe-notice i { color: var(--blue-500); margin-top: 2px; }

        .subscribe-success { text-align: center; padding: 40px; animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .subscribe-success--hidden { display: none; }
        .subscribe-success .success-icon { font-size: 56px; color: var(--emerald-400); margin-bottom: 16px; }

        .landing-footer {
            padding: 32px 24px; display: flex; align-items: center; justify-content: space-between;
            font-size: 12px; color: var(--text-muted); border-top: 1px solid var(--border-subtle);
        }
        .landing-footer a { color: var(--text-secondary); transition: color 0.15s; }
        .landing-footer a:hover { color: var(--blue-400); }
        .footer-links { display: flex; gap: 24px; }

        @media (max-width: 768px) {
            .landing-nav .nav-links { display: none; }
            .subscribe-form { padding: 24px; }
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
    <section class="subscribe-hero">
        <span class="eyebrow">Souscription</span>
        <h1>Rejoindre SAEC Cloud</h1>
        <p>Plan <strong><?= $currentPlan['name'] ?></strong> — <?= $currentPlan['price'] ?></p>
    </section>

    <!-- Form -->
    <section class="subscribe-form-section">
        <!-- Progress -->
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

        <form id="subscribeForm" class="subscribe-form">
            <input type="hidden" name="plan" value="<?= htmlspecialchars($plan) ?>">

            <!-- Step 1 -->
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
                <div class="btn-group">
                    <span></span>
                    <button type="button" class="btn btn-primary next-step" data-next="2">Suivant →</button>
                </div>
            </div>

            <!-- Step 2 -->
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

            <!-- Step 3 -->
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
                        <label class="checkbox-label"><input type="checkbox" name="needs[]" value="sync"> Synchronisation multi-appareils</label>
                        <label class="checkbox-label"><input type="checkbox" name="needs[]" value="api"> Accès API</label>
                        <label class="checkbox-label"><input type="checkbox" name="needs[]" value="branding"> Personnalisation branding</label>
                        <label class="checkbox-label"><input type="checkbox" name="needs[]" value="audit"> Logs d'audit avancés</label>
                        <label class="checkbox-label"><input type="checkbox" name="needs[]" value="2fa"> Double authentification</label>
                        <label class="checkbox-label"><input type="checkbox" name="needs[]" value="sla"> SLA garanti</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Date souhaitée</label>
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

            <!-- Step 4 -->
            <div class="form-step" data-step="4">
                <h3 class="form-step-title">Confirmer la demande</h3>
                <div class="subscribe-summary">
                    <div class="summary-row"><span>Plan</span><strong style="color:var(--cyan-400)"><?= $currentPlan['name'] ?></strong></div>
                    <div class="summary-row"><span>Prix</span><strong><?= $currentPlan['price'] ?></strong></div>
                    <div class="summary-row"><span>Stockage</span><strong><?= $currentPlan['storage'] ?></strong></div>
                    <div class="summary-row"><span>Utilisateurs max</span><strong><?= $currentPlan['users'] ?></strong></div>
                </div>
                <div class="subscribe-notice">
                    <i class="fas fa-info-circle"></i>
                    Votre demande sera examinée par notre équipe. Vous recevrez une facture par email sous 24h.
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-ghost prev-step" data-prev="3">← Retour</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Envoyer la demande</button>
                </div>
            </div>
        </form>

        <!-- Success -->
        <div id="subscribeSuccess" class="subscribe-success subscribe-success--hidden">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <h3 style="font-size:22px; font-weight:700; margin-bottom:8px;">Demande envoyée !</h3>
            <p style="color:var(--text-secondary); margin-bottom:24px;">Notre équipe va examiner votre demande. Vous recevrez une facture par email sous 24h.</p>
            <a href="/login" class="btn btn-primary">Retour au login</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div>© <?= date('Y') ?> SAEC Ltd. — Bespoke Systems Engineering</div>
        <div class="footer-links">
            <a href="/">Accueil</a>
            <a href="/pricing">Tarifs</a>
            <a href="/login">Connexion</a>
        </div>
    </footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('subscribeForm');
    const steps = document.querySelectorAll('.form-step');
    const stepIndicators = document.querySelectorAll('.subscribe-step');
    const submitBtn = form.querySelector('[type="submit"]');

    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', function() { goToStep(parseInt(this.dataset.next)); });
    });
    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', function() { goToStep(parseInt(this.dataset.prev)); });
    });

    function goToStep(step) {
        steps.forEach(s => s.classList.remove('active'));
        stepIndicators.forEach(s => s.classList.remove('active', 'done'));
        for (let i = 1; i < step; i++) {
            document.querySelector('.subscribe-step[data-step="'+i+'"]').classList.add('done');
        }
        document.querySelector('.form-step[data-step="'+step+'"]').classList.add('active');
        document.querySelector('.subscribe-step[data-step="'+step+'"]').classList.add('active');
        document.querySelectorAll('.subscribe-step-line').forEach((line, idx) => {
            line.classList.toggle('active', idx < step - 1);
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
        const data = new FormData(form);
        fetch('/subscribe', { method: 'POST', body: data })
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

</body>
</html>
