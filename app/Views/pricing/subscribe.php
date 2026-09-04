<?php
$plan = $_GET['plan'] ?? 'professional';
$plans = [
    'starter' => ['name' => 'Starter', 'price' => '€9/mois', 'storage' => '10 GB', 'users' => 5, 'color' => '#06B6D4'],
    'professional' => ['name' => 'Professional', 'price' => '€29/mois', 'storage' => '100 GB', 'users' => 25, 'color' => '#8B5CF6'],
    'enterprise' => ['name' => 'Enterprise', 'price' => 'Sur devis', 'storage' => 'Illimité', 'users' => 'Illimité', 'color' => '#10B981'],
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
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <style>
        :root {
            --bg: #0F111A; --bg2: #181A20; --card: #232632; --card-hover: #2D3342;
            --border: #3A4257; --text: #F1F5F9; --text2: #94A3B8; --muted: #64748B;
            --blue: #2563EB; --blue2: #3B82F6; --cyan: #06B6D4; --green: #10B981;
            --amber: #F59E0B; --rose: #EF4444; --purple: #8B5CF6;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh; overflow-x: hidden; }
        ::selection { background: var(--blue); color: #fff; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideRight { from { opacity: 0; transform: translateX(-16px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
        @keyframes checkPop { 0% { transform: scale(0); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(37,99,235,0.4); } 70% { box-shadow: 0 0 0 10px rgba(37,99,235,0); } }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
        @keyframes confetti { 0% { transform: translateY(0) rotate(0deg); opacity: 1; } 100% { transform: translateY(-200px) rotate(720deg); opacity: 0; } }

        /* ── NAV ── */
        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px; height: 64px;
            background: rgba(15,17,26,0.9); backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border);
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; }
        .nav-brand img { height: 26px; }
        .nav-brand span { font-weight: 700; font-size: 15px; }
        .nav-links { display: flex; align-items: center; gap: 28px; }
        .nav-links a { font-size: 13px; color: var(--text2); font-weight: 500; text-decoration: none; transition: color 0.15s; }
        .nav-links a:hover { color: var(--text); }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; border: none; cursor: pointer; font-family: inherit; transition: all 0.2s cubic-bezier(0.22,1,0.36,1); text-decoration: none; }
        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0) scale(0.98); }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:hover { background: var(--blue2); box-shadow: 0 4px 16px rgba(37,99,235,0.35); }
        .btn-ghost { background: transparent; color: var(--text2); }
        .btn-ghost:hover { color: var(--text); background: rgba(255,255,255,0.05); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { border-color: var(--muted); background: rgba(255,255,255,0.03); }
        .btn-lg { padding: 12px 28px; font-size: 14px; border-radius: 10px; }
        .btn[disabled] { opacity: 0.5; cursor: not-allowed; transform: none; }

        /* ── HERO ── */
        .hero {
            padding: 100px 24px 32px; text-align: center;
            background: radial-gradient(ellipse at top, rgba(37,99,235,0.08) 0%, transparent 60%);
        }
        .eyebrow {
            display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.12em; color: var(--blue2); padding: 4px 14px;
            border-radius: 99px; background: rgba(37,99,235,0.1); border: 1px solid rgba(37,99,235,0.2);
            animation: slideUp 0.5s ease-out;
        }
        .hero h1 {
            font-size: clamp(26px, 4vw, 38px); font-weight: 800; letter-spacing: -0.03em;
            margin-top: 16px; animation: slideUp 0.5s ease-out 0.1s both;
        }
        .hero h1 .gradient {
            background: linear-gradient(135deg, var(--text), var(--cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-plan {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 16px;
            padding: 6px 16px; border-radius: 99px; font-size: 14px; font-weight: 600;
            background: rgba(255,255,255,0.04); border: 1px solid var(--border);
            animation: slideUp 0.5s ease-out 0.2s both;
        }
        .hero-plan .plan-dot { width: 8px; height: 8px; border-radius: 50%; }

        /* ── PROGRESS ── */
        .progress-wrap {
            max-width: 520px; margin: 0 auto 40px; padding: 0 24px;
            animation: slideUp 0.5s ease-out 0.3s both;
        }
        .progress-bar {
            display: flex; align-items: center; justify-content: space-between;
            position: relative;
        }
        .progress-bar::before {
            content: ''; position: absolute; top: 18px; left: 24px; right: 24px;
            height: 2px; background: var(--border); z-index: 0;
        }
        .progress-bar::after {
            content: ''; position: absolute; top: 18px; left: 24px;
            height: 2px; background: linear-gradient(90deg, var(--blue), var(--cyan));
            z-index: 1; transition: width 0.4s cubic-bezier(0.22,1,0.36,1);
            width: 0%;
        }
        .progress-bar[data-progress="1"]::after { width: 0%; }
        .progress-bar[data-progress="2"]::after { width: 33%; }
        .progress-bar[data-progress="3"]::after { width: 66%; }
        .progress-bar[data-progress="4"]::after { width: 100%; }

        .p-step { display: flex; flex-direction: column; align-items: center; gap: 6px; z-index: 2; position: relative; }
        .p-num {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--card); border: 2px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: var(--muted);
            transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        .p-step.active .p-num {
            background: var(--blue); border-color: var(--blue); color: #fff;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.15), 0 0 20px rgba(37,99,235,0.3);
            animation: pulse 2s infinite;
        }
        .p-step.done .p-num {
            background: var(--green); border-color: var(--green); color: #fff;
            box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
        }
        .p-step.done .p-num::after {
            content: '✓'; font-size: 14px;
        }
        .p-step.done .p-num span { display: none; }
        .p-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); }
        .p-step.active .p-label { color: var(--text); }
        .p-step.done .p-label { color: var(--green); }

        /* ── FORM CARD ── */
        .form-card {
            max-width: 560px; margin: 0 auto 80px; padding: 0 24px;
        }
        .form-box {
            background: var(--card); border: 1px solid var(--border); border-radius: 16px;
            overflow: hidden; animation: scaleIn 0.3s ease-out;
            box-shadow: 0 4px 24px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.02);
        }
        .form-box-header {
            padding: 24px 32px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px;
        }
        .form-box-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .form-box-icon.step1 { background: rgba(37,99,235,0.12); color: var(--blue2); }
        .form-box-icon.step2 { background: rgba(6,182,212,0.12); color: var(--cyan); }
        .form-box-icon.step3 { background: rgba(139,92,246,0.12); color: var(--purple); }
        .form-box-icon.step4 { background: rgba(16,185,129,0.12); color: var(--green); }
        .form-box-title { font-size: 17px; font-weight: 700; letter-spacing: -0.01em; }
        .form-box-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }

        .form-box-body { padding: 28px 32px 32px; }

        .step { display: none; animation: slideRight 0.3s ease-out; }
        .step.active { display: block; }

        .field { margin-bottom: 20px; }
        .field-label {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--text2); margin-bottom: 8px;
        }
        .field-label .req { color: var(--rose); font-size: 10px; }
        .field-input {
            width: 100%; padding: 11px 14px; border-radius: 8px;
            border: 1px solid var(--border); background: var(--bg2);
            color: var(--text); font-size: 14px; font-family: inherit;
            transition: all 0.2s; outline: none;
        }
        .field-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.12); background: var(--bg); }
        .field-input::placeholder { color: var(--muted); }
        textarea.field-input { resize: vertical; min-height: 80px; }
        select.field-input {
            appearance: none; cursor: pointer;
            background: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 12px center;
            padding-right: 36px;
        }
        select.field-input option { background: var(--bg); color: var(--text); }

        .checkbox-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .cb-label {
            display: flex; align-items: center; gap: 10px; cursor: pointer;
            font-size: 13px; color: var(--text2); padding: 8px 12px; border-radius: 8px;
            border: 1px solid transparent; transition: all 0.15s;
        }
        .cb-label:hover { background: rgba(255,255,255,0.03); border-color: var(--border); color: var(--text); }
        .cb-label input { display: none; }
        .cb-mark {
            width: 18px; height: 18px; border-radius: 5px; flex-shrink: 0;
            border: 2px solid var(--border); background: var(--bg2);
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s; font-size: 10px; color: transparent;
        }
        .cb-label input:checked + .cb-mark {
            background: var(--blue); border-color: var(--blue); color: #fff;
        }

        .form-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border); }
        .form-nav .step-counter { font-size: 12px; color: var(--muted); font-family: 'JetBrains Mono', monospace; }

        /* ── SUMMARY ── */
        .summary-card {
            background: var(--bg2); border: 1px solid var(--border); border-radius: 12px;
            padding: 20px; margin-bottom: 20px;
        }
        .summary-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; font-size: 14px;
        }
        .summary-row + .summary-row { border-top: 1px solid rgba(255,255,255,0.04); }
        .summary-row .label { color: var(--text2); }
        .summary-row .value { font-weight: 600; }
        .summary-highlight {
            display: flex; align-items: center; gap: 12px; padding: 16px;
            background: linear-gradient(135deg, rgba(37,99,235,0.08), rgba(6,182,212,0.08));
            border: 1px solid rgba(37,99,235,0.2); border-radius: 10px; margin-bottom: 20px;
        }
        .summary-highlight .sh-icon {
            width: 44px; height: 44px; border-radius: 10px;
            background: rgba(37,99,235,0.15); display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .summary-highlight .sh-plan { font-size: 16px; font-weight: 700; }
        .summary-highlight .sh-price { font-size: 13px; color: var(--text2); }

        .notice-box {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 14px 16px; background: rgba(37,99,235,0.05);
            border: 1px solid rgba(37,99,235,0.12); border-radius: 10px;
            font-size: 13px; color: var(--text2); line-height: 1.5;
        }
        .notice-box i { color: var(--blue2); margin-top: 2px; flex-shrink: 0; }

        /* ── SUCCESS ── */
        .success-wrap { text-align: center; padding: 48px 24px; animation: scaleIn 0.4s cubic-bezier(0.34,1.56,0.64,1); }
        .success-wrap.hidden { display: none; }
        .success-circle {
            width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 24px;
            background: linear-gradient(135deg, var(--green), var(--cyan));
            display: flex; align-items: center; justify-content: center;
            font-size: 36px; color: #fff;
            animation: checkPop 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.2s both;
            box-shadow: 0 0 0 8px rgba(16,185,129,0.1), 0 8px 24px rgba(16,185,129,0.2);
        }
        .success-wrap h2 { font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 8px; }
        .success-wrap p { color: var(--text2); font-size: 14px; line-height: 1.6; max-width: 400px; margin: 0 auto 28px; }

        /* ── FOOTER ── */
        .footer {
            padding: 24px; text-align: center; font-size: 11px; color: var(--muted);
            border-top: 1px solid var(--border);
        }
        .footer a { color: var(--text2); text-decoration: none; transition: color 0.15s; }
        .footer a:hover { color: var(--blue2); }

        @media (max-width: 640px) {
            .nav-links { display: none; }
            .form-box-header { padding: 20px 24px; }
            .form-box-body { padding: 24px; }
            .checkbox-grid { grid-template-columns: 1fr; }
            .progress-wrap { margin-bottom: 28px; }
        }
    </style>
</head>
<body>

<nav class="nav">
    <div class="nav-brand">
        <img src="/img/logo.png" alt="SAEC">
        <span>SAEC <span style="color:var(--cyan)">Cloud</span></span>
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

<section class="hero">
    <span class="eyebrow">Souscription</span>
    <h1><span class="gradient">Rejoindre</span> SAEC Cloud</h1>
    <div class="hero-plan">
        <span class="plan-dot" style="background:<?= $currentPlan['color'] ?>"></span>
        Plan <?= $currentPlan['name'] ?> — <?= $currentPlan['price'] ?>
    </div>
</section>

<div class="progress-wrap">
    <div class="progress-bar" data-progress="1">
        <div class="p-step active" data-step="1"><div class="p-num"><span>1</span></div><div class="p-label">Entreprise</div></div>
        <div class="p-step" data-step="2"><div class="p-num"><span>2</span></div><div class="p-label">Contact</div></div>
        <div class="p-step" data-step="3"><div class="p-num"><span>3</span></div><div class="p-label">Besoins</div></div>
        <div class="p-step" data-step="4"><div class="p-num"><span>4</span></div><div class="p-label">Envoyer</div></div>
    </div>
</div>

<div class="form-card">
    <div class="form-box">
        <div class="form-box-header">
            <div class="form-box-icon step1" id="formIcon"><i class="fas fa-building"></i></div>
            <div><div class="form-box-title" id="formTitle">Votre entreprise</div><div class="form-box-sub" id="formSub">Étape 1 sur 4</div></div>
        </div>
        <div class="form-box-body">

            <form id="subscribeForm">
                <input type="hidden" name="plan" value="<?= htmlspecialchars($plan) ?>">

                <!-- Step 1 -->
                <div class="step active" data-step="1">
                    <div class="field">
                        <div class="field-label">Nom de l'entreprise <span class="req">*</span></div>
                        <input type="text" name="company" class="field-input" required placeholder="Ex: ACME Corp">
                    </div>
                    <div class="field">
                        <div class="field-label">Site web</div>
                        <input type="url" name="website" class="field-input" placeholder="https://example.com">
                    </div>
                    <div class="field">
                        <div class="field-label">Secteur d'activité <span class="req">*</span></div>
                        <select name="industry" class="field-input" required>
                            <option value="">Choisir un secteur...</option>
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
                    <div class="field">
                        <div class="field-label">Nombre d'employés <span class="req">*</span></div>
                        <select name="company_size" class="field-input" required>
                            <option value="">Choisir...</option>
                            <option value="1-5">1-5</option>
                            <option value="6-20">6-20</option>
                            <option value="21-50">21-50</option>
                            <option value="51-200">51-200</option>
                            <option value="200+">200+</option>
                        </select>
                    </div>
                    <div class="field">
                        <div class="field-label">Pays <span class="req">*</span></div>
                        <select name="country" class="field-input" required>
                            <option value="">Choisir un pays...</option>
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
                    <div class="form-nav">
                        <span class="step-counter">01 / 04</span>
                        <button type="button" class="btn btn-primary next-step" data-next="2">Suivant <i class="fas fa-arrow-right" style="font-size:11px;"></i></button>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step" data-step="2">
                    <div class="field">
                        <div class="field-label">Nom complet <span class="req">*</span></div>
                        <input type="text" name="contact_name" class="field-input" required placeholder="Jean Dupont">
                    </div>
                    <div class="field">
                        <div class="field-label">Email professionnel <span class="req">*</span></div>
                        <input type="email" name="contact_email" class="field-input" required placeholder="jean@acme.com">
                    </div>
                    <div class="field">
                        <div class="field-label">Téléphone</div>
                        <input type="tel" name="contact_phone" class="field-input" placeholder="+32 470 12 34 56">
                    </div>
                    <div class="field">
                        <div class="field-label">Poste / Fonction</div>
                        <input type="text" name="contact_role" class="field-input" placeholder="Directeur IT">
                    </div>
                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost prev-step" data-prev="1"><i class="fas fa-arrow-left" style="font-size:11px;"></i> Retour</button>
                        <span class="step-counter">02 / 04</span>
                        <button type="button" class="btn btn-primary next-step" data-next="3">Suivant <i class="fas fa-arrow-right" style="font-size:11px;"></i></button>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step" data-step="3">
                    <div class="field">
                        <div class="field-label">Usage principal <span class="req">*</span></div>
                        <select name="use_case" class="field-input" required>
                            <option value="">Choisir un usage...</option>
                            <option value="files">Stockage et partage de fichiers</option>
                            <option value="backup">Backup et archivage</option>
                            <option value="collaboration">Collaboration d'équipe</option>
                            <option value="client">Partage avec clients</option>
                            <option value="compliance">Conformité (RGPD, etc.)</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="field">
                        <div class="field-label">Volume de fichiers estimé</div>
                        <select name="file_volume" class="field-input">
                            <option value="">Choisir...</option>
                            <option value="<100">Moins de 100 fichiers</option>
                            <option value="100-1000">100 à 1 000 fichiers</option>
                            <option value="1000-10000">1 000 à 10 000 fichiers</option>
                            <option value=">10000">Plus de 10 000 fichiers</option>
                        </select>
                    </div>
                    <div class="field">
                        <div class="field-label">Types de fichiers</div>
                        <input type="text" name="file_types" class="field-input" placeholder="PDF, images, documents Word...">
                    </div>
                    <div class="field">
                        <div class="field-label">Besoins spécifiques</div>
                        <div class="checkbox-grid">
                            <label class="cb-label"><input type="checkbox" name="needs[]" value="sync"><span class="cb-mark"><i class="fas fa-check"></i></span> Sync multi-appareils</label>
                            <label class="cb-label"><input type="checkbox" name="needs[]" value="api"><span class="cb-mark"><i class="fas fa-check"></i></span> Accès API</label>
                            <label class="cb-label"><input type="checkbox" name="needs[]" value="branding"><span class="cb-mark"><i class="fas fa-check"></i></span> Branding custom</label>
                            <label class="cb-label"><input type="checkbox" name="needs[]" value="audit"><span class="cb-mark"><i class="fas fa-check"></i></span> Audit logs avancés</label>
                            <label class="cb-label"><input type="checkbox" name="needs[]" value="2fa"><span class="cb-mark"><i class="fas fa-check"></i></span> Double authentification</label>
                            <label class="cb-label"><input type="checkbox" name="needs[]" value="sla"><span class="cb-mark"><i class="fas fa-check"></i></span> SLA garanti</label>
                        </div>
                    </div>
                    <div class="field">
                        <div class="field-label">Date souhaitée</div>
                        <input type="date" name="desired_date" class="field-input">
                    </div>
                    <div class="field">
                        <div class="field-label">Message additionnel</div>
                        <textarea name="message" class="field-input" rows="3" placeholder="Décrivez vos besoins..."></textarea>
                    </div>
                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost prev-step" data-prev="2"><i class="fas fa-arrow-left" style="font-size:11px;"></i> Retour</button>
                        <span class="step-counter">03 / 04</span>
                        <button type="button" class="btn btn-primary next-step" data-next="4">Suivant <i class="fas fa-arrow-right" style="font-size:11px;"></i></button>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="step" data-step="4">
                    <div class="summary-highlight">
                        <div class="sh-icon"><i class="fas fa-rocket"></i></div>
                        <div><div class="sh-plan" style="color:<?= $currentPlan['color'] ?>"><?= $currentPlan['name'] ?></div><div class="sh-price"><?= $currentPlan['price'] ?> · <?= $currentPlan['storage'] ?> · <?= $currentPlan['users'] ?> utilisateurs</div></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-row"><span class="label">Plan</span><span class="value" style="color:<?= $currentPlan['color'] ?>"><?= $currentPlan['name'] ?></span></div>
                        <div class="summary-row"><span class="label">Prix</span><span class="value"><?= $currentPlan['price'] ?></span></div>
                        <div class="summary-row"><span class="label">Stockage</span><span class="value"><?= $currentPlan['storage'] ?></span></div>
                        <div class="summary-row"><span class="label">Utilisateurs</span><span class="value"><?= $currentPlan['users'] ?></span></div>
                    </div>
                    <div class="notice-box">
                        <i class="fas fa-shield-halved"></i>
                        <div>Votre demande sera examinée par notre équipe. Vous recevrez une facture par email sous 24h. AucuneCB requise pour démarrer.</div>
                    </div>
                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost prev-step" data-prev="3"><i class="fas fa-arrow-left" style="font-size:11px;"></i> Retour</button>
                        <span class="step-counter">04 / 04</span>
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn"><i class="fas fa-paper-plane"></i> Envoyer la demande</button>
                    </div>
                </div>
            </form>

            <!-- Success -->
            <div id="successState" class="success-wrap hidden">
                <div class="success-circle"><i class="fas fa-check"></i></div>
                <h2>Demande envoyée !</h2>
                <p>Notre équipe va examiner votre demande. Vous recevrez une facture par email sous 24h.</p>
                <a href="/login" class="btn btn-primary btn-lg">Retour au login <i class="fas fa-arrow-right" style="font-size:11px;"></i></a>
            </div>

        </div>
    </div>
</div>

<footer class="footer">
    © <?= date('Y') ?> SAEC Ltd. — Bespoke Systems Engineering · <a href="/">Accueil</a> · <a href="/pricing">Tarifs</a>
</footer>

<script>
const stepMeta = {
    1: { icon: 'fas fa-building', cls: 'step1', title: 'Votre entreprise', sub: 'Étape 1 sur 4' },
    2: { icon: 'fas fa-user', cls: 'step2', title: 'Personne de contact', sub: 'Étape 2 sur 4' },
    3: { icon: 'fas fa-sliders', cls: 'step3', title: 'Vos besoins', sub: 'Étape 3 sur 4' },
    4: { icon: 'fas fa-check-circle', cls: 'step4', title: 'Confirmer', sub: 'Étape 4 sur 4' },
};

function goToStep(step) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.p-step').forEach(s => { s.classList.remove('active', 'done'); });
    for (let i = 1; i < step; i++) document.querySelector('.p-step[data-step="'+i+'"]').classList.add('done');
    document.querySelector('.step[data-step="'+step+'"]').classList.add('active');
    document.querySelector('.p-step[data-step="'+step+'"]').classList.add('active');
    document.querySelector('.progress-bar').setAttribute('data-progress', step);
    const m = stepMeta[step];
    const icon = document.getElementById('formIcon');
    icon.className = 'form-box-icon ' + m.cls;
    icon.innerHTML = '<i class="' + m.icon + '"></i>';
    document.getElementById('formTitle').textContent = m.title;
    document.getElementById('formSub').textContent = m.sub;
    document.querySelector('.form-box').style.animation = 'none';
    requestAnimationFrame(() => { document.querySelector('.form-box').style.animation = 'scaleIn 0.3s ease-out'; });
}

document.querySelectorAll('.next-step').forEach(b => b.addEventListener('click', () => goToStep(parseInt(b.dataset.next))));
document.querySelectorAll('.prev-step').forEach(b => b.addEventListener('click', () => goToStep(parseInt(b.dataset.prev))));

document.getElementById('subscribeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
    fetch('/subscribe', { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('subscribeForm').style.display = 'none';
            document.getElementById('successState').classList.remove('hidden');
        } else { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer la demande'; }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer la demande'; });
});
</script>
</body>
</html>
