<?php
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'fr';
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions d'utilisation — SAEC Cloud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <style>
        :root { --bg:#0F111A; --card:#1A1D28; --border:#2A2D3A; --text:#F1F5F9; --text2:#94A3B8; --muted:#64748B; --blue:#2563EB; --cyan:#06B6D4; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { background:var(--bg); color:var(--text); font-family:'Inter',sans-serif; line-height:1.7; }
        .nav { position:fixed; top:0; left:0; right:0; z-index:100; display:flex; align-items:center; justify-content:space-between; padding:0 32px; height:72px; background:rgba(15,17,26,0.85); backdrop-filter:blur(20px); border-bottom:1px solid var(--border); }
        .nav-brand { display:flex; align-items:center; gap:12px; }
        .nav-brand img { height:28px; }
        .nav-brand span { font-weight:700; font-size:16px; }
        .nav-links { display:flex; gap:24px; }
        .nav-links a { font-size:14px; color:var(--text2); text-decoration:none; transition:color 0.15s; }
        .nav-links a:hover { color:var(--text); }
        .hero { padding:120px 24px 60px; text-align:center; background:radial-gradient(ellipse at top, rgba(37,99,235,0.08), transparent 60%); }
        .hero h1 { font-size:clamp(28px,4vw,42px); font-weight:800; letter-spacing:-0.03em; background:linear-gradient(135deg,var(--text),var(--cyan)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .hero p { font-size:16px; color:var(--text2); max-width:600px; margin:16px auto 0; }
        .content { max-width:800px; margin:0 auto; padding:40px 24px 80px; }
        .content h2 { font-size:20px; font-weight:700; color:var(--text); margin:32px 0 12px; padding-bottom:8px; border-bottom:1px solid var(--border); }
        .content h3 { font-size:16px; font-weight:600; color:var(--text); margin:24px 0 8px; }
        .content p { font-size:14px; color:var(--text2); margin-bottom:12px; }
        .content ul { margin:8px 0 16px 20px; }
        .content li { font-size:14px; color:var(--text2); margin-bottom:6px; }
        .footer { padding:32px 24px; display:flex; justify-content:space-between; font-size:12px; color:var(--muted); border-top:1px solid var(--border); }
        .footer a { color:var(--text2); text-decoration:none; }
        @media(max-width:768px) { .nav-links { display:none; } }
    </style>
</head>
<body>
    <nav class="nav">
        <a href="/" class="nav-brand">
            <img src="/img/logo.png" alt="SAEC">
            <span>SAEC <span style="color:var(--cyan)">Cloud</span></span>
        </a>
        <div class="nav-links">
            <a href="/">Accueil</a>
            <a href="/pricing">Tarifs</a>
            <a href="/privacy">Confidentialité</a>
        </div>
    </nav>

    <section class="hero">
        <h1>Conditions d'utilisation</h1>
        <p>Dernière mise à jour : <?= date('d/m/Y') ?></p>
    </section>

    <div class="content">
        <h2>1. Acceptation des conditions</h2>
        <p>En accédant et en utilisant SAEC Cloud, vous acceptez ces conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser le service.</p>

        <h2>2. Description du service</h2>
        <p>SAEC Cloud est un service de stockage cloud souverain, offrant :</p>
        <ul>
            <li>Stockage de fichiers chiffrés (AES-256-GCM)</li>
            <li>Partage de fichiers via liens sécurisés</li>
            <li>Gestion de dossiers et organisation</li>
            <li>Administration multi-tenant</li>
            <li>Logs d'audit et traçabilité</li>
        </ul>

        <h2>3. Inscription et compte</h2>
        <ul>
            <li>Vous devez fournir une adresse email valide</li>
            <li>Un seul compte par adresse email</li>
            <li>Vous êtes responsable de la sécurité de votre mot de passe</li>
            <li>Vous devez nous notifier tout usage non autorisé de votre compte</li>
            <li>Vous devez être âgé d'au moins 16 ans</li>
        </ul>

        <h2>4. Utilisation acceptable</h2>
        <p>Vous vous engagez à NE PAS :</p>
        <ul>
            <li>Stocker du contenu illégal, diffamatoire, ou violant les droits de tiers</li>
            <li>Partager du contenu pornographique impliquant des mineurs</li>
            <li>Distribuer du malware ou du contenu malveillant</li>
            <li>Violer les droits de propriété intellectuelle</li>
            <li>Tenter d'accéder non autorisé à d'autres comptes ou systèmes</li>
            <li>Utiliser le service pour du spam ou du phishing</li>
            <li>Overloader délibérément l'infrastructure</li>
            <li>Revendre l'accès au service sans autorisation</li>
        </ul>

        <h2>5. Propriété du contenu</h2>
        <ul>
            <li>Vous conservez 100% de la propriété de vos fichiers</li>
            <li>SAEC n'a aucun droit sur votre contenu</li>
            <li>Nous ne pouvons PAS lire vos fichiers (chiffrement E2E)</li>
            <li>Vous pouvez exporter ou supprimer vos données à tout moment</li>
        </ul>

        <h2>6. Quotas et limitations</h2>
        <p>Chaque plan a des limitations spécifiques :</p>
        <ul>
            <li><strong>Starter</strong> : 10 GB, 500 fichiers, 1 utilisateur</li>
            <li><strong>Professional</strong> : 100 GB, 5000 fichiers, 5 utilisateurs</li>
            <li><strong>Enterprise</strong> : illimité selon contrat</li>
        </ul>
        <p>Le dépassement de quota entraîne l'impossibilité d'uploader de nouveaux fichiers.</p>

        <h2>7. Facturation</h2>
        <ul>
            <li>Les plans sont facturés mensuellement</li>
        </ul>
    </div>

    <footer class="footer">
        <div>© <?= date('Y') ?> SAEC Ltd. — Bespoke Systems Engineering</div>
        <div><a href="/">Accueil</a> · <a href="/login">Connexion</a></div>
    </footer>
</body>
</html>
