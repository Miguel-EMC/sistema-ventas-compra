<?php

declare(strict_types=1);

if (!defined('LEGACY_REPORTS_BRIDGE_ENTRY')) {
    http_response_code(404);
    exit('Controlador legacy no disponible.');
}

require __DIR__ . '/../bootstrap/app.php';

legacy_require_auth();

$entry = defined('LEGACY_REPORTS_BRIDGE_ENTRY')
    ? (string) constant('LEGACY_REPORTS_BRIDGE_ENTRY')
    : basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

legacy_redirect_reports_bridge_entry($entry, $_GET);
