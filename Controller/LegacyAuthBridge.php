<?php

declare(strict_types=1);

if (!defined('LEGACY_AUTH_BRIDGE_ENTRY')) {
    http_response_code(404);
    exit('Controlador legacy no disponible.');
}

require __DIR__ . '/../bootstrap/app.php';

$entry = defined('LEGACY_AUTH_BRIDGE_ENTRY')
    ? (string) constant('LEGACY_AUTH_BRIDGE_ENTRY')
    : basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

$normalizedEntry = strtolower($entry);
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$requestedRedirect = $_POST['redirect'] ?? $_GET['redirect'] ?? null;
$intendedRedirect = legacy_safe_redirect_path($requestedRedirect);

if ($normalizedEntry === 'index.php' || $normalizedEntry === 'logincontroller.php') {
    if (auth_user() !== null) {
        redirect(legacy_frontend_entry_url(
            legacy_default_frontend_path(),
            ['legacy' => 'shell-home'],
        ));
    }

    redirect(legacy_frontend_login_url($intendedRedirect, [
        'legacy' => 'shell-login',
    ]));
}

if ($normalizedEntry === 'logout.php') {
    logout_user();

    redirect(frontend_url($intendedRedirect ?? '/login?legacy=signed-out&logout=1'));
}

if ($normalizedEntry !== 'accessusers.php') {
    http_response_code(404);
    exit('Controlador legacy no disponible.');
}

if ($method === 'POST') {
    validate_csrf_or_abort();

    $login = trim((string) ($_POST['usuario'] ?? ''));
    $plainPassword = (string) ($_POST['password'] ?? '');

    if ($login === '' || $plainPassword === '') {
        redirect(legacy_frontend_login_url($intendedRedirect, [
            'legacy' => 'auth-missing-fields',
        ]));
    }

    $user = auth_service()->attempt($login, $plainPassword);

    if ($user === null) {
        redirect(legacy_frontend_login_url($intendedRedirect, [
            'legacy' => 'auth-invalid',
        ]));
    }

    login_user($user);

    if ($intendedRedirect !== null) {
        redirect(legacy_frontend_entry_url($intendedRedirect));
    }

    redirect(legacy_frontend_entry_url(
        legacy_default_frontend_path($user),
        ['legacy' => 'shell-home'],
    ));
}

$currentUser = auth_user();

if ($currentUser === null) {
    redirect(legacy_frontend_login_url($intendedRedirect, [
        'legacy' => 'auth-required',
    ]));
}

if ($intendedRedirect !== null) {
    redirect(legacy_frontend_entry_url($intendedRedirect));
}

redirect(legacy_frontend_entry_url(
    legacy_default_frontend_path($currentUser),
    ['legacy' => 'shell-home'],
));
