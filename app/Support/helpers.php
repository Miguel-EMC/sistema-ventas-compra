<?php

declare(strict_types=1);

use VentasPos\Domain\Auth\AuthRepository;
use VentasPos\Domain\Auth\AuthService;
use VentasPos\Domain\Catalog\CatalogRepository;
use VentasPos\Domain\Menu\MenuRepository;
use VentasPos\Domain\Reports\ReportsRepository;
use VentasPos\Domain\Sales\SalesRepository;
use VentasPos\Domain\Settings\SettingsRepository;
use VentasPos\Domain\Uploads\UploadService;
use VentasPos\Domain\Users\UsersRepository;
use VentasPos\Infrastructure\Database\LegacyMysqliResult;
use VentasPos\Infrastructure\Database\MysqliCompat;
use VentasPos\Infrastructure\Database\PdoConnectionFactory;
use VentasPos\Support\Csrf;
use VentasPos\Support\LegacyHtmlSanitizer;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);

        if ($path === '') {
            return $basePath;
        }

        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === '') {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('app_load_config')) {
    function app_load_config(): void
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        $loaded = true;

        $GLOBALS['app.config'] = [];
        foreach (glob(base_path('config/*.php')) as $file) {
            $GLOBALS['app.config'][basename($file, '.php')] = require $file;
        }
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        app_load_config();

        $segments = explode('.', $key);
        $value = $GLOBALS['app.config'] ?? [];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('app_boot_session')) {
    function app_boot_session(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) config('app.session.name', 'ventaspos_session'));
        session_set_cookie_params([
            'lifetime' => (int) config('app.session.lifetime', 7200),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

if (!function_exists('app_instance')) {
    function app_instance(string $key, callable $factory): mixed
    {
        static $instances = [];

        if (!array_key_exists($key, $instances)) {
            $instances[$key] = $factory();
        }

        return $instances[$key];
    }
}

if (!function_exists('pdo_connection')) {
    function pdo_connection(): PDO
    {
        return app_instance(PDO::class, static fn (): PDO => (new PdoConnectionFactory())->make());
    }
}

if (!function_exists('auth_service')) {
    function auth_service(): AuthService
    {
        return app_instance(AuthService::class, static fn (): AuthService => new AuthService(new AuthRepository(pdo_connection())));
    }
}

if (!function_exists('menu_repository')) {
    function menu_repository(): MenuRepository
    {
        return app_instance(MenuRepository::class, static fn (): MenuRepository => new MenuRepository(pdo_connection()));
    }
}

if (!function_exists('settings_repository')) {
    function settings_repository(): SettingsRepository
    {
        return app_instance(SettingsRepository::class, static fn (): SettingsRepository => new SettingsRepository(pdo_connection()));
    }
}

if (!function_exists('users_repository')) {
    function users_repository(): UsersRepository
    {
        return app_instance(UsersRepository::class, static fn (): UsersRepository => new UsersRepository(pdo_connection()));
    }
}

if (!function_exists('catalog_repository')) {
    function catalog_repository(): CatalogRepository
    {
        return app_instance(CatalogRepository::class, static fn (): CatalogRepository => new CatalogRepository(pdo_connection()));
    }
}

if (!function_exists('sales_repository')) {
    function sales_repository(): SalesRepository
    {
        return app_instance(SalesRepository::class, static fn (): SalesRepository => new SalesRepository(pdo_connection()));
    }
}

if (!function_exists('reports_repository')) {
    function reports_repository(): ReportsRepository
    {
        return app_instance(ReportsRepository::class, static fn (): ReportsRepository => new ReportsRepository(pdo_connection()));
    }
}

if (!function_exists('upload_service')) {
    function upload_service(): UploadService
    {
        return app_instance(UploadService::class, static fn (): UploadService => new UploadService(
            base_path((string) config('filesystems.uploads.disk_path')),
            base_path((string) config('filesystems.uploads.public_disk')),
            (string) config('filesystems.uploads.public_path'),
            (int) config('filesystems.uploads.max_size', 5 * 1024 * 1024),
        ));
    }
}

if (!function_exists('csrf')) {
    function csrf(): Csrf
    {
        return app_instance(Csrf::class, static fn (): Csrf => new Csrf());
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return csrf()->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('validate_csrf_or_abort')) {
    function validate_csrf_or_abort(): void
    {
        if (!csrf()->validate($_POST['_csrf'] ?? null)) {
            flash('error', 'Tu sesion expiro o el token CSRF no es valido.');
            redirect(app_url('/index.php'));
        }
    }
}

if (!function_exists('flash')) {
    function flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('consume_flash')) {
    function consume_flash(string $key): ?string
    {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }

        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $message;
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $base = (string) config('app.url', '');
        $path = ltrim($path, '/');

        if ($base === '') {
            return '/' . $path;
        }

        return $path === '' ? $base : $base . '/' . $path;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string
    {
        $base = (string) config('app.asset_url', app_url('public/assets'));
        $path = ltrim($path, '/');

        return $path === '' ? $base : $base . '/' . $path;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        if (!isset($_SESSION['auth']['user_id'])) {
            return null;
        }

        return auth_service()->findById((int) $_SESSION['auth']['user_id']);
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'user_id' => (int) $user['id_usu'],
            'login' => $user['login'],
            'display_name' => $user['nombre'],
            'role' => $user['tipo'],
        ];
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void
    {
        unset($_SESSION['auth']);
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}

if (!function_exists('legacy_sentinel_password')) {
    function legacy_sentinel_password(): string
    {
        return '__SESSION__';
    }
}

if (!function_exists('legacy_sync_request_auth')) {
    function legacy_sync_request_auth(): void
    {
        $user = auth_user();

        if ($user === null) {
            return;
        }

        $_GET['usuario'] ??= $user['login'];
        $_GET['password'] ??= legacy_sentinel_password();
        $_GET['usuarioLogin'] ??= $user['login'];
        $_GET['passwordLogin'] ??= legacy_sentinel_password();
        $_POST['usuarioLogin'] ??= $user['login'];
        $_POST['passwordLogin'] ??= legacy_sentinel_password();
    }
}

if (!function_exists('legacy_attempt_request_login')) {
    function legacy_attempt_request_login(): bool
    {
        if (auth_user() !== null) {
            legacy_sync_request_auth();

            return true;
        }

        $login = trim((string) ($_POST['usuario'] ?? $_GET['usuario'] ?? $_POST['usuarioLogin'] ?? $_GET['usuarioLogin'] ?? ''));
        $password = (string) ($_POST['password'] ?? $_GET['password'] ?? $_POST['passwordLogin'] ?? $_GET['passwordLogin'] ?? '');

        if ($login === '' || $password === '' || $password === legacy_sentinel_password()) {
            return false;
        }

        $user = auth_service()->attempt($login, $password);

        if ($user === null) {
            return false;
        }

        login_user($user);
        legacy_sync_request_auth();

        return true;
    }
}

if (!function_exists('legacy_require_auth')) {
    function legacy_require_auth(): void
    {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $exempt = ['AccessUsers.php', 'LoginController.php', 'Logout.php', 'index.php'];

        if (in_array($script, $exempt, true)) {
            return;
        }

        legacy_attempt_request_login();
        legacy_sync_request_auth();

        if (auth_user() === null) {
            flash('error', 'Necesitas iniciar sesion para continuar.');
            redirect(app_url('/index.php'));
        }
    }
}

if (!function_exists('legacy_strip_auth_from_url')) {
    function legacy_strip_auth_from_url(string $url): string
    {
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, 'javascript:')) {
            return $url;
        }

        $parts = parse_url(html_entity_decode($url, ENT_QUOTES));
        if ($parts === false) {
            return $url;
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['usuario'], $query['password'], $query['usuarioLogin'], $query['passwordLogin']);
        }

        $rebuilt = '';
        if (isset($parts['scheme'])) {
            $rebuilt .= $parts['scheme'] . '://';
        }
        if (isset($parts['host'])) {
            $rebuilt .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }
        $rebuilt .= $parts['path'] ?? $url;

        if ($query !== []) {
            $rebuilt .= '?' . http_build_query($query);
        }

        if (isset($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }
}

if (!function_exists('legacy_start_html_sanitizer')) {
    function legacy_start_html_sanitizer(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        static $started = false;

        if ($started) {
            return;
        }

        $started = true;
        ob_start(static fn (string $buffer): string => (new LegacyHtmlSanitizer())->sanitize($buffer));
    }
}

if (!function_exists('app_boot_legacy_mysqli_compat')) {
    function app_boot_legacy_mysqli_compat(): void
    {
        if (!class_exists('mysqli', false)) {
            class_alias(MysqliCompat::class, 'mysqli');
        }

        if (!defined('MYSQLI_ASSOC')) {
            define('MYSQLI_ASSOC', 1);
        }

        if (!defined('MYSQLI_NUM')) {
            define('MYSQLI_NUM', 2);
        }

        if (!defined('MYSQLI_BOTH')) {
            define('MYSQLI_BOTH', 3);
        }
    }
}

if (!function_exists('mysqli_fetch_array')) {
    function mysqli_fetch_array(LegacyMysqliResult $result, int $mode = MYSQLI_BOTH): array|false
    {
        return $result->fetchArray($mode);
    }
}

if (!function_exists('mysqli_num_rows')) {
    function mysqli_num_rows(LegacyMysqliResult $result): int
    {
        return $result->numRows();
    }
}

if (!function_exists('cuadro_error')) {
    function cuadro_error(string $message): void
    {
        flash('error', $message);
    }
}

if (!function_exists('cuadro_mensaje')) {
    function cuadro_mensaje(string $message): void
    {
        flash('success', $message);
    }
}
