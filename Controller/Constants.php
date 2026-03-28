<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

legacy_start_html_sanitizer();

$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAjax && $scriptName !== 'AccessUsers.php') {
    validate_csrf_or_abort();
}

legacy_require_auth();
legacy_sync_request_auth();

$currentUser = auth_user();
$usuario = $currentUser['login'] ?? ($_GET['usuario'] ?? $_POST['usuarioLogin'] ?? '');
$password = legacy_sentinel_password();

if (!defined('URL_VIEWS')) {
    define('URL_VIEWS', rtrim(asset_url(), '/') . '/');
}

if (!defined('ADDRESS')) {
    define('ADDRESS', rtrim(base_path((string) config('filesystems.uploads.public_disk')), '/') . '/');
}
