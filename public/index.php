<?php

declare(strict_types=1);

// ══════════════════════════════════════════════════════════════
// SAEC CLOUD — Entry Point
// ══════════════════════════════════════════════════════════════

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');

// Gestion erreurs — only fatal/catchable errors trigger 500
set_error_handler(function($severity, $message, $file, $line) {
    error_log("[ERROR] [$severity] $message in $file:$line");
    if ($severity === E_ERROR || $severity === E_PARSE || $severity === E_COMPILE_ERROR) {
        http_response_code(500);
        echo 'Internal Server Error';
        exit;
    }
    return false;
});

set_exception_handler(function($exception) {
    error_log("[EXCEPTION] " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
    http_response_code(500);
    echo 'Internal Server Error';
    exit;
});

// Charger la config
$configFiles = glob(__DIR__ . '/../storage/x*.conf');
if (empty($configFiles)) {
    http_response_code(500);
    echo 'Configuration non trouvée. Exécutez deploy.php';
    exit(1);
}

$config = json_decode(file_get_contents($configFiles[0]), true);
if (!$config) {
    http_response_code(500);
    echo 'Configuration invalide';
    exit(1);
}

$GLOBALS['SAEC_CONFIG'] = $config;

// Autoloader
spl_autoload_register(function (string $class) {
    $prefixes = [
        'Saec\\' => __DIR__ . '/../app/',
        'Saec\\Core\\' => __DIR__ . '/../core/',
    ];
    
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Initialiser
Saec\Core\JWTHandler::init($config['security']['jwt_secret'] ?? '');
Saec\Core\Session::start();

// i18n — detect and load language
$translation = Saec\Core\Translation::getInstance();
$translation->setLang($translation->detectLang());
$GLOBALS['SAEC_TRANSLATION'] = $translation;

// Helper function t() — global namespace
if (!function_exists('t')) {
    function t(string $key, array $replace = []): string {
        return \Saec\Core\Translation::getInstance()->get($key, $replace);
    }
}

// Language switch route (manual)
if (isset($_GET['lang']) && preg_match('/^[a-z]{2}$/', $_GET['lang'])) {
    $translation->setLang($_GET['lang']);
    setcookie('lang', $_GET['lang'], [
        'expires' => time() + 86400 * 365,
        'path' => '/',
        'secure' => true,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
    header('Location: ' . $referer);
    exit;
}

// Security headers (CSP adapté pour Cloudflare)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

// CSP adaptée pour Cloudflare
$csp = [
    "default-src 'self'",
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://static.cloudflareinsights.com https://cdnjs.cloudflare.com",
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
    "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
    "img-src 'self' data: https:",
    "connect-src 'self' https://static.cloudflareinsights.com",
    "frame-ancestors 'none'",
];
header("Content-Security-Policy: " . implode('; ', $csp));

if (($config['app']['env'] ?? 'production') !== 'development') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Routes
$router = new Saec\Core\Router();
require_once __DIR__ . '/../config/routes.php';

// Dispatch
$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);
