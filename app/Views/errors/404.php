<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — SAEC Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --accent: #00ff88;
            --text: #ffffff;
            --muted: #888888;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            padding: 40px;
        }
        .code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 120px;
            font-weight: 700;
            color: var(--accent);
            line-height: 1;
            margin-bottom: 24px;
        }
        .message {
            font-size: 18px;
            color: var(--muted);
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--accent);
            color: #000;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.8; }
        .footer {
            margin-top: 48px;
            font-size: 12px;
            color: var(--muted);
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">404</div>
        <div class="message">Page non trouvée</div>
        <a href="/login" class="btn">Retour à l'accueil</a>
        <div class="footer">© 2026 SAEC Ltd. — Sovereign stack</div>
    </div>
</body>
</html>
