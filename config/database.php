<?php

declare(strict_types=1);

return [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
    'database' => $_ENV['DB_NAME'] ?? 'saec_cloud',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
];
