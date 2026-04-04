#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ] && [ -f .env ]; then
  php artisan key:generate --force --no-interaction
fi

php <<'PHP'
<?php

$host = getenv('DB_HOST') ?: 'postgres';
$port = getenv('DB_PORT') ?: '5432';
$database = getenv('DB_DATABASE') ?: 'ventaspos';
$username = getenv('DB_USERNAME') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);

for ($attempt = 0; $attempt < 60; $attempt++) {
    try {
        new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        exit(0);
    } catch (Throwable $exception) {
        usleep(1000000);
    }
}

fwrite(STDERR, "PostgreSQL was not reachable after 60 seconds.\n");
exit(1);
PHP

php artisan migrate --force
php artisan db:seed --force

exec php artisan serve --host=0.0.0.0 --port=8001
