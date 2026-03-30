<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'ventaspos'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'asset_url' => rtrim((string) env('APP_ASSET_URL', ''), '/'),
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:4200'), '/'),
    'legacy_bridge_secret' => (string) env('LEGACY_BRIDGE_SECRET', 'ventaspos-legacy-bridge-local'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'session' => [
        'name' => env('SESSION_NAME', 'ventaspos_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),
    ],
];
