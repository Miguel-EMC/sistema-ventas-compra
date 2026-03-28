<?php

declare(strict_types=1);

use Dotenv\Dotenv;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}

$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'VentasPos\\';
        $baseDir = BASE_PATH . '/app/';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    });

    require_once BASE_PATH . '/app/Support/helpers.php';
}

if (class_exists(Dotenv::class) && is_file(BASE_PATH . '/.env')) {
    Dotenv::createImmutable(BASE_PATH)->safeLoad();
}

app_load_config();
date_default_timezone_set((string) config('app.timezone', 'UTC'));
app_boot_session();
app_boot_legacy_mysqli_compat();
