<?php

declare(strict_types=1);

$requested = basename((string) ($_GET['file'] ?? ''));

if ($requested === '') {
    http_response_code(404);
    exit('Controller no encontrado.');
}

$target = dirname(__DIR__, 2) . '/Controller/' . $requested;

if (!is_file($target)) {
    http_response_code(404);
    exit('Controller no encontrado.');
}

require $target;
