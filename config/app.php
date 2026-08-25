<?php
// Environment-based settings (dev/prod). Do not commit real secrets in prod.

return [
    'env' => 'production',
    'debug' => false,
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'hris',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'timezone' => 'Asia/Manila',
];
