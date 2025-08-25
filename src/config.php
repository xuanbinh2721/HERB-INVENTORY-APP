<?php
// Basic config via env (Docker Compose .env)
return [
    'env' => getenv('APP_ENV') ?: 'production',
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'herb_shop',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4'
    ],
    'app_name' => 'Herb Inventory & Invoicing',
];
