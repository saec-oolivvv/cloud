<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'SAEC Cloud',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'url' => $_ENV['APP_URL'] ?? 'https://cloud.saec.me',
    'admin_port' => (int) ($_ENV['ADMIN_PORT'] ?? 8443),
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
];
