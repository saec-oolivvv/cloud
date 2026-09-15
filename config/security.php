<?php

declare(strict_types=1);

return [
    'jwt_secret' => $_ENV['JWT_SECRET'] ?? '',
    'jwt_expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 900),
    'refresh_expiry' => (int) ($_ENV['REFRESH_EXPIRY'] ?? 604800),

    'password_algo' => PASSWORD_ARGON2ID,
    'password_options' => [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3,
    ],

    'rate_limit' => [
        'login' => ['max' => 5, 'window' => 900],
        'api' => ['max' => 100, 'window' => 60],
        'upload' => ['max' => 20, 'window' => 60],
    ],

    'upload' => [
        'max_file_size' => 3355443200, // 3200MB
        'allowed_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/zip',
            'text/plain',
            'text/csv',
        ],
    ],

    'mfa' => [
        'enabled' => false,
        'issuer' => 'SAEC Cloud',
    ],

    'encryption' => [
        'cipher' => 'aes-256-gcm',
        'key_path' => $_ENV['ENCRYPTION_KEY_PATH'] ?? __DIR__ . '/../storage/keys/master.key',
    ],
];
