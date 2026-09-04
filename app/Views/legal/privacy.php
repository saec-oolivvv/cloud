<?php
$lang = $GLOBALS['SAEC_TRANSLATION'] ?? null;
$currentLang = $lang ? $lang->getLang() : 'fr';
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de confidentialité — SAEC Cloud</title>
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
            <a href="/terms">Conditions</a>
        </div>
    </nav>

    <section class="hero">
        <h1>Politique de confidentialité</h1>
        <p>Dernière mise à jour : <?= date('d/m/Y') ?></p>
    </section>

    <div class="content">
        <h2>1. Responsable du traitement</h2>
        <p>SAEC Ltd., responsable du traitement des données personnelles collectées via la plateforme SAEC Cloud.</p>
        <p>Contact : <a href="mailto:privacy@saec.me" style="color:var(--blue);">privacy@saec.me</a></p>

        <h2>2. Données collectées</h2>
        <h3>2.1 Données d'identification</h3>
        <ul>
            <li>Adresse email (obligatoire pour la création de compte)</li>
            <li>Nom complet (optionnel)</li>
            <li>Rôle (utilisateur ou administrateur)</li>
        </ul>

        <h3>2.2 Données d'utilisation</h3>
        <ul>
            <li>Adresses IP (pour la sécurité et l'audit)</li>
            <li>User-Agent du navigateur</li>
            <li>Horodatages de connexion et d'activité</li>
            <li>Logs d'audit (actions effectuées sur la plateforme)</li>
        </ul>

        <h3>2.3 Données de stockage</h3>
        <ul>
            <li>Fichiers uploadés (chiffrés AES-256-GCM)</li>
            <li>Métadonnées de fichiers (nom, taille, type MIME)</li>
            <li>Noms de dossiers</li>
            <li>Liens de partage créés</li>
        </ul>

        <h2>3. Finalités du traitement</h2>
        <ul>
            <li>Fourniture du service de stockage cloud</li>
            <li>Authentification et gestion des accès</li>
            <li>Sécurité (détection d'intrusion, rate limiting)</li>
            <li>Traçabilité (logs d'audit)</li>
            <li>Support technique</li>
            <li>Amélioration du service</li>
        </ul>

        <h2>4. Base légale</h2>
        <p>Le traitement est fondé sur :</p>
        <ul>
            <li><strong>Exécution du contrat</strong> (Art. 6.1.b RGPD) — Fourniture du service</li>
            <li><strong>Intérêt légitime</strong> (Art. 6.1.f RGPD) — Sécurité et audit</li>
            <li><strong>Consentement</strong> (Art. 6.1.a RGPD) — Cookies non essentiels</li>
        </ul>

        <h2>5. Chiffrement et sécurité</h2>
        <ul>
            <li>Tous les fichiers sont chiffrés en AES-256-GCM</li>
            <li>Clés de chiffrement générées par fichier</li>
            <li>Les clés ne sont jamais stockées en clair</li>
            <li>Transit chiffré via TLS 1.3</li>
            <li>Isolation multi-tenant cryptographique</li>
            <li>Infrastructure hébergée en Belgique</li>
        </ul>

        <h2>6. Durée de conservation</h2>
        <ul>
            <li><strong>Compte</strong> : tant que le compte est actif</li>
            <li><strong>Fichiers</strong> : tant que non supprimés (+ corbeille 30-90 jours selon plan)</li>
            <li><strong>Logs d'audit</strong> : 90 jours (Starter) à illimité (Enterprise)</li>
            <li><strong>Sessions</strong> : 30 minutes d'inactivité</li>
            <li><strong>Données de connexion</strong> : 12 mois</li>
        </ul>

        <h2>7. Vos droits (RGPD)</h2>
        <ul>
            <li><strong>Accès</strong> (Art. 15) — Consulter vos données</li>
            <li><strong>Rectification</strong> (Art. 16) — Corriger vos données</li>
            <li><strong>Effacement</strong> (Art. 17) — Supprimer votre compte</li>
            <li><strong>Portabilité</strong> (Art. 20) — Exporter vos données</li>
            <li><strong>Opposition</strong> (Art. 21) — Vous opposer au traitement</li>
            <li><strong>Limitation</strong> (Art. 18) — Limiter le traitement</li>
        </ul>
        <p>Pour exercer vos droits, contactez <a href="mailto:privacy@saec.me" style="color:var(--blue);">privacy@saec.me</a>.</p>

        <h2>8. Sous-traitants</h2>
        <ul>
            <li><strong>Hébergement</strong> : Infrastructure Synology privée (Belgique)</li>
            <li><strong>Email</strong> : SMTP configuré sur infrastructure propre</li>
            <li><strong>Paiement</strong> : Pas de traitement de paiement en ligne actuellement</li>
        </ul>

        <h2>9. Transferts internationaux</h2>
        <p>Aucun transfert de données hors de l'EEE. Toutes les données sont stockées et traitées en Belgique.</p>

        <h2>10. Cookies</h2>
        <ul>
            <li><strong>Session</strong> : cookie de session PHP (strictement nécessaire)</li>
            <li><strong>Préférences</strong> : langue sélectionnée (12 mois)</li>
            <li><strong>Consentement</strong> : mémorisation du consentement cookies</li>
        </ul>

        <h2>11. Modifications</h2>
        <p>Cette politique peut être mise à jour. Les utilisateurs seront notifiés par email de toute modification significative.</p>

        <h2>12. Contact</h2>
        <p>Pour toute question : <a href="mailto:privacy@saec.me" style="color:var(--blue);">privacy@saec.me</a></p>
        <p>Autorité de contrôle : Autorité de protection des données de Belgique (APD) — <a href="https://www.dataprotectionauthority.be" target="_blank" style="color:var(--blue);">dataprotectionauthority.be</a></p>
    </div>

    <footer class="footer">
        <div>© <?= date('Y') ?> SAEC Ltd. — Bespoke Systems Engineering</div>
        <div><a href="/">Accueil</a> · <a href="/login">Connexion</a></div>
    </footer>
</body>
</html>
