<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap/app.php';

$requested = basename((string) ($_GET['file'] ?? ''));

if ($requested === '') {
    http_response_code(404);
    exit('Controller no encontrado.');
}

$target = legacy_public_controller_path($requested);

if ($target === null) {
    http_response_code(404);
    exit('Controller no encontrado.');
}

require $target;
