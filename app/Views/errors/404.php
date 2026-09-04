<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 — SAEC Cloud</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#0F111A;--blue:#2563EB;--cyan:#06B6D4;--text:#E2E8F0;--muted:#64748B}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;position:relative}
body::before{content:'';position:fixed;inset:0;background-image:radial-gradient(circle,rgba(37,99,235,.12) 1px,transparent 1px);background-size:32px 32px;animation:gridScroll 20s linear infinite;z-index:0}
@keyframes gridScroll{to{background-position:32px 32px}}
.error-code{font-family:'JetBrains Mono',monospace;font-size:clamp(80px,18vw,160px);font-weight:700;color:var(--cyan);position:relative;z-index:1;animation:float 4s ease-in-out infinite;line-height:1;margin-bottom:24px}
.error-code::before,.error-code::after{content:'404';position:absolute;top:0;left:0;width:100%;height:100%}
.error-code::before{color:var(--blue);animation:glitch 3s infinite linear alternate-reverse;clip-path:polygon(0 0,100% 0,100% 35%,0 35%)}
.error-code::after{color:var(--cyan);animation:glitch 2s infinite linear alternate;clip-path:polygon(0 65%,100% 65%,100% 100%,0 100%);opacity:.8}
@keyframes glitch{0%,90%,100%{transform:translate(0)}92%{transform:translate(4px,-2px)}94%{transform:translate(-4px,2px)}96%{transform:translate(2px,1px)}98%{transform:translate(-2px,-1px)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.error-page{position:relative;z-index:1;text-align:center}
.message{font-size:18px;color:var(--muted);margin-bottom:32px}
.message::after{content:'█';animation:blink 1s step-end infinite;margin-left:2px;color:var(--blue);font-size:16px}
@keyframes blink{50%{opacity:0}}
.btn{display:inline-block;padding:14px 32px;background:linear-gradient(135deg,var(--blue),var(--cyan));color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;letter-spacing:.5px;transition:transform .2s,box-shadow .2s;box-shadow:0 0 24px rgba(37,99,235,.25)}
.btn:hover{transform:translateY(-2px);box-shadow:0 0 40px rgba(37,99,235,.4)}
.footer{position:fixed;bottom:24px;font-size:12px;color:var(--muted);font-family:'JetBrains Mono',monospace;z-index:1;opacity:.6}
</style>
</head>
<body>
<div class="error-page">
<div class="error-code">404</div>
<div class="message">Page non trouvée</div>
<a href="/dashboard" class="btn">Retour au tableau de bord</a>
</div>
<div class="footer">© 2026 SAEC Ltd. — Sovereign stack</div>
</body>
</html>
