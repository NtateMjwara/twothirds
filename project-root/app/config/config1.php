<?php
return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'your_db_name',
        'user'    => 'your_db_user',
        'pass'    => 'your_db_password',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'         => 'Platform',
        'url'          => 'https://yourdomain.co.za',
        'env'          => 'production', // 'local' | 'production'
        'session_name' => 'platform_session',
        // Generate a fresh key per deployment - never reuse this one in production.
        // Regenerate with: base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES))
        'encryption_key' => 'IRhX93dbqoppEW2aochnxAOWXEXAupqlGCWz+9qdVII=',
    ],
    'mail' => [
        'host'         => 'smtp.yourdomain.co.za',
        'port'         => 587,
        'username'     => 'no-reply@yourdomain.co.za',
        'password'     => '',
        'from_address' => 'no-reply@yourdomain.co.za',
        'from_name'    => 'Platform',
    ],
];
