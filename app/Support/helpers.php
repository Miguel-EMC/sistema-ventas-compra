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
            redirect(legacy_frontend_login_url(null, [
                'legacy' => 'auth-expired',
            ]));
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

if (!function_exists('frontend_url')) {
    function frontend_url(string $path = ''): string
    {
        $base = (string) config('app.frontend_url', 'http://localhost:4200');
        $path = ltrim($path, '/');

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

if (!function_exists('legacy_redirect_to_modern_reports')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_reports(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/reports', $params));
    }
}

if (!function_exists('legacy_redirect_to_modern_sales')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_sales(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/sales', $params));
    }
}

if (!function_exists('legacy_sales_bridge_value')) {
    /**
     * @param array<int, array<string, mixed>> $sources
     * @param list<string> $keys
     */
    function legacy_sales_bridge_value(array $sources, array $keys): ?string
    {
        foreach ($keys as $key) {
            foreach ($sources as $source) {
                $value = $source[$key] ?? null;

                if (! is_scalar($value)) {
                    continue;
                }

                $normalized = trim((string) $value);

                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }

        return null;
    }
}

if (!function_exists('legacy_sales_bridge_params')) {
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $payload
     * @return array<string, scalar|null>
     */
    function legacy_sales_bridge_params(string $script, array $query = [], array $payload = []): array
    {
        $entry = strtolower(basename($script));
        $sources = [$payload, $query];
        $customerDocument = legacy_sales_bridge_value($sources, ['ci', 'nit']);
        $amountPaid = legacy_sales_bridge_value($sources, ['ingreso2', 'efectivo']);
        $draftNote = legacy_sales_bridge_value($sources, ['comentario', 'observacion', 'notes', 'nota']);

        $params = match ($entry) {
            'factura.php' => [
                'legacy' => 'checkout-entry',
                'legacy_source' => 'invoice-menu',
            ],
            'confactura.php' => [
                'legacy' => 'checkout-entry',
                'legacy_source' => 'checkout-factura',
                'document_type' => 'factura',
                'payment_method' => 'cash',
                'customer_document' => $customerDocument,
                'amount_paid' => $amountPaid,
            ],
            'sinfactura.php' => [
                'legacy' => 'checkout-entry',
                'legacy_source' => 'checkout-ticket',
                'document_type' => 'ticket',
                'payment_method' => 'cash',
                'customer_document' => $customerDocument,
                'amount_paid' => $amountPaid,
            ],
            'registrarventa.php' => [
                'legacy' => 'checkout-entry',
                'legacy_source' => 'register-sale',
                'payment_method' => 'cash',
                'customer_document' => $customerDocument,
                'amount_paid' => $amountPaid,
            ],
            'registrarpreventa.php' => [
                'legacy' => 'checkout-entry',
                'legacy_source' => 'register-pre-sale',
                'payment_method' => 'cash',
                'customer_document' => $customerDocument,
                'amount_paid' => $amountPaid,
                'draft_note' => $draftNote,
            ],
            'consolidar.php' => [
                'legacy' => 'sales-consolidation',
                'legacy_source' => 'consolidation-screen',
            ],
            'consolidarventa.php' => [
                'legacy' => isset($payload['insertarComentario']) ? 'sales-consolidation-write' : 'sales-consolidation',
                'legacy_source' => isset($payload['insertarComentario']) ? 'consolidation-comment' : 'consolidation-screen',
                'draft_note' => $draftNote,
            ],
            'insertarpedidomesa.php' => [
                'legacy' => 'pre-sale-cart-write',
                'legacy_source' => 'pre-sale-cart',
                'legacy_cart_action' => 'add',
                'service_mode' => 'mesa',
                'draft_note' => $draftNote,
            ],
            'insertarpedidollevar.php' => [
                'legacy' => 'pre-sale-cart-write',
                'legacy_source' => 'pre-sale-cart',
                'legacy_cart_action' => 'add',
                'service_mode' => 'llevar',
                'draft_note' => $draftNote,
            ],
            'deleteonlypreventa.php' => [
                'legacy' => 'pre-sale-cart-delete',
                'legacy_source' => 'pre-sale-cart',
                'legacy_cart_action' => 'delete-item',
            ],
            'deleteallpreventa.php' => [
                'legacy' => 'pre-sale-cart-delete',
                'legacy_source' => 'pre-sale-cart',
                'legacy_cart_action' => 'clear',
            ],
            'editpreventa.php' => [
                'legacy' => 'pre-sale-cart-edit',
                'legacy_source' => 'pre-sale-cart',
                'legacy_cart_action' => 'edit',
                'draft_note' => $draftNote,
            ],
            'updatepreventa.php' => [
                'legacy' => 'pre-sale-cart-edit',
                'legacy_source' => 'pre-sale-cart',
                'legacy_cart_action' => 'update',
                'draft_note' => $draftNote,
            ],
            default => [
                'legacy' => 'checkout-entry',
            ],
        };

        return array_filter(
            $params,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }
}

if (!function_exists('legacy_redirect_sales_bridge_entry')) {
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $payload
     */
    function legacy_redirect_sales_bridge_entry(string $script, array $query = [], array $payload = []): never
    {
        legacy_redirect_to_modern_sales(legacy_sales_bridge_params($script, $query, $payload));
    }
}

if (!function_exists('legacy_redirect_to_modern_purchases')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_purchases(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/purchases', $params));
    }
}

if (!function_exists('legacy_redirect_to_modern_customers')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_customers(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/customers', $params));
    }
}

if (!function_exists('legacy_redirect_to_modern_suppliers')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_suppliers(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/suppliers', $params));
    }
}

if (!function_exists('legacy_redirect_to_modern_users')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_users(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/users', $params));
    }
}

if (!function_exists('legacy_redirect_to_modern_products')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_products(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/products', $params));
    }
}

if (!function_exists('legacy_redirect_to_modern_assets')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_assets(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/assets', $params));
    }
}

if (!function_exists('legacy_redirect_to_modern_cash')) {
    /**
     * @param array<string, scalar|null> $params
     */
    function legacy_redirect_to_modern_cash(array $params = []): never
    {
        redirect(legacy_frontend_entry_url('/cash', $params));
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

if (!function_exists('legacy_default_frontend_path')) {
    function legacy_default_frontend_path(?array $user = null): string
    {
        $user ??= auth_user();
        $role = strtoupper(trim((string) ($user['tipo'] ?? $user['role'] ?? '')));

        return $role === 'VENTAS' ? '/sales' : '/dashboard';
    }
}

if (!function_exists('legacy_frontend_login_url')) {
    /**
     * @param array<string, scalar|null> $query
     */
    function legacy_frontend_login_url(?string $redirect = null, array $query = []): string
    {
        $filteredQuery = array_filter(
            $query,
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        if ($redirect !== null && $redirect !== '' && str_starts_with($redirect, '/')) {
            $filteredQuery['redirect'] = $redirect;
        }

        $path = '/login';
        if ($filteredQuery !== []) {
            $path .= '?' . http_build_query($filteredQuery);
        }

        return frontend_url($path);
    }
}

if (!function_exists('legacy_safe_redirect_path')) {
    function legacy_safe_redirect_path(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $redirect = trim((string) $value);

        if ($redirect === '' || !str_starts_with($redirect, '/')) {
            return null;
        }

        return $redirect;
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
            $redirect = legacy_frontend_route_for_script($script);

            redirect(legacy_frontend_login_url($redirect, [
                'legacy' => 'auth-required',
            ]));
        }
    }
}

if (!function_exists('legacy_frontend_route_for_script')) {
    function legacy_frontend_route_for_script(string $script): ?string
    {
        return match (strtolower($script)) {
            'cliente.php',
            'registrocliente.php',
            'searchcontact.php' => '/customers',
            'producto.php',
            'registroproducto.php',
            'tipoproducto.php',
            'registrotipoproducto.php' => '/products',
            'inventario.php',
            'registroactivo.php' => '/assets',
            'proveedor.php',
            'registrosproveedor.php' => '/suppliers',
            'usuario.php',
            'registros.php' => '/users',
            'cuenta.php',
            'registrocuenta.php' => '/cash',
            'pedido.php',
            'registropedido.php' => '/purchases',
            'moneda.php',
            'registrosdatamoneda.php',
            'datosfactura.php',
            'registrosdatafactura.php',
            'languaje.php',
            'registrosdataidioma.php' => '/settings',
            'ventas.php',
            'factura.php',
            'registrarpreventa.php',
            'registrarventa.php',
            'confactura.php',
            'sinfactura.php',
            'consolidar.php',
            'consolidarventa.php',
            'insertarpedidomesa.php',
            'insertarpedidollevar.php',
            'deleteonlypreventa.php',
            'deleteallpreventa.php',
            'editpreventa.php',
            'updatepreventa.php' => '/sales',
            'reportesventas.php',
            'reportes.php',
            'estadistica.php',
            'reportemes.php',
            'reporteporanio.php',
            'reporteultimos6mes.php' => '/reports',
            default => null,
        };
    }
}

if (!function_exists('legacy_frontend_bridge_definition')) {
    /**
     * @return array{path: string, params: array<string, scalar|null>}|null
     */
    function legacy_frontend_bridge_definition(string $script): ?array
    {
        return match (strtolower($script)) {
            'cliente.php' => [
                'path' => '/customers',
                'params' => [],
            ],
            'producto.php',
            'tipoproducto.php' => [
                'path' => '/products',
                'params' => [],
            ],
            'inventario.php' => [
                'path' => '/assets',
                'params' => [],
            ],
            'proveedor.php' => [
                'path' => '/suppliers',
                'params' => [],
            ],
            'usuario.php' => [
                'path' => '/users',
                'params' => [],
            ],
            'cuenta.php' => [
                'path' => '/cash',
                'params' => [],
            ],
            'pedido.php' => [
                'path' => '/purchases',
                'params' => ['legacy' => 'company-orders'],
            ],
            'moneda.php' => [
                'path' => '/settings',
                'params' => [],
            ],
            'datosfactura.php' => [
                'path' => '/settings',
                'params' => [
                    'section' => 'billing',
                    'legacy' => 'invoice-data',
                ],
            ],
            'languaje.php' => [
                'path' => '/settings',
                'params' => [
                    'section' => 'system',
                    'legacy' => 'language',
                ],
            ],
            'ventas.php' => [
                'path' => '/sales',
                'params' => [],
            ],
            'principal.php' => [
                'path' => legacy_default_frontend_path(),
                'params' => ['legacy' => 'shell-home'],
            ],
            default => null,
        };
    }
}

if (!function_exists('legacy_redirect_frontend_bridge_entry')) {
    function legacy_redirect_frontend_bridge_entry(string $script): never
    {
        $definition = legacy_frontend_bridge_definition($script);

        if (! is_array($definition)) {
            redirect(legacy_frontend_entry_url('/'));
        }

        redirect(legacy_frontend_entry_url(
            (string) ($definition['path'] ?? '/'),
            is_array($definition['params'] ?? null) ? $definition['params'] : [],
        ));
    }
}

if (!function_exists('legacy_report_date')) {
    function legacy_report_date(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        if ($normalized === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $normalized);

        return $date instanceof \DateTimeImmutable ? $date->format('Y-m-d') : null;
    }
}

if (!function_exists('legacy_report_month_range')) {
    /**
     * @return array{date_from: string|null, date_to: string|null}
     */
    function legacy_report_month_range(string $year, string $month): array
    {
        if (!preg_match('/^\d{4}$/', $year) || !preg_match('/^\d{2}$/', $month)) {
            return ['date_from' => null, 'date_to' => null];
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%s-%s-01', $year, $month));

        if (!($date instanceof \DateTimeImmutable)) {
            return ['date_from' => null, 'date_to' => null];
        }

        return [
            'date_from' => $date->format('Y-m-01'),
            'date_to' => $date->format('Y-m-t'),
        ];
    }
}

if (!function_exists('legacy_reports_bridge_params')) {
    /**
     * @param array<string, mixed> $query
     * @return array<string, scalar|null>
     */
    function legacy_reports_bridge_params(string $script, array $query = []): array
    {
        $entry = strtolower(basename($script));

        $params = match ($entry) {
            'reportesventas.php' => [
                'legacy_source' => 'reportes_ventas',
            ],
            'estadistica.php' => [
                'legacy_source' => 'estadistica',
            ],
            'reportemes.php' => [
                ...legacy_report_month_range(
                    isset($query['anio']) ? trim((string) $query['anio']) : '',
                    isset($query['mes']) ? trim((string) $query['mes']) : '',
                ),
                'legacy_source' => 'reporte_mes',
            ],
            'reporteporanio.php' => (static function () use ($query): array {
                $year = isset($query['anio']) ? trim((string) $query['anio']) : '';

                return [
                    'date_from' => preg_match('/^\d{4}$/', $year) ? sprintf('%s-01-01', $year) : null,
                    'date_to' => preg_match('/^\d{4}$/', $year) ? sprintf('%s-12-31', $year) : null,
                    'legacy_source' => 'reporte_anual',
                ];
            })(),
            'reporteultimos6mes.php' => (static function (): array {
                $today = new \DateTimeImmutable('today');

                return [
                    'date_from' => $today->modify('first day of this month')->modify('-5 months')->format('Y-m-d'),
                    'date_to' => $today->format('Y-m-d'),
                    'legacy_source' => 'reporte_ultimos_6_meses',
                ];
            })(),
            'reportes.php' => (static function () use ($query): array {
                $params = [
                    'legacy_source' => 'reportes',
                ];

                if (isset($query['reporte_dia'])) {
                    $date = legacy_report_date($query['fechaVentas'] ?? null);
                    $params['date_from'] = $date;
                    $params['date_to'] = $date;
                } elseif (
                    isset($query['rango_fecha'])
                    || isset($query['reporte_producto'])
                    || isset($query['utilidad'])
                    || isset($query['gastos'])
                ) {
                    $params['date_from'] = legacy_report_date($query['fechaInicialVentas'] ?? null);
                    $params['date_to'] = legacy_report_date($query['fechaFinalVentas'] ?? null);
                } elseif (isset($query['reporte_mes'])) {
                    $range = legacy_report_month_range(
                        isset($query['anio']) ? trim((string) $query['anio']) : '',
                        isset($query['mes']) ? trim((string) $query['mes']) : '',
                    );
                    $params['date_from'] = $range['date_from'];
                    $params['date_to'] = $range['date_to'];
                } elseif (isset($query['reporte_anual'])) {
                    $year = isset($query['anio']) ? trim((string) $query['anio']) : '';
                    $params['date_from'] = preg_match('/^\d{4}$/', $year) ? sprintf('%s-01-01', $year) : null;
                    $params['date_to'] = preg_match('/^\d{4}$/', $year) ? sprintf('%s-12-31', $year) : null;
                } elseif (isset($query['reporte_6meses'])) {
                    $today = new \DateTimeImmutable('today');
                    $params['date_from'] = $today->modify('first day of this month')->modify('-5 months')->format('Y-m-d');
                    $params['date_to'] = $today->format('Y-m-d');
                }

                return $params;
            })(),
            default => [
                'legacy_source' => 'reportes',
            ],
        };

        return array_filter(
            $params,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }
}

if (!function_exists('legacy_redirect_reports_bridge_entry')) {
    /**
     * @param array<string, mixed> $query
     */
    function legacy_redirect_reports_bridge_entry(string $script, array $query = []): never
    {
        legacy_redirect_to_modern_reports(legacy_reports_bridge_params($script, $query));
    }
}

if (!function_exists('legacy_action_bridge_value')) {
    /**
     * @param array<int, array<string, mixed>> $sources
     * @param list<string> $keys
     */
    function legacy_action_bridge_value(array $sources, array $keys): ?string
    {
        foreach ($keys as $key) {
            foreach ($sources as $source) {
                $value = $source[$key] ?? null;

                if (! is_scalar($value)) {
                    continue;
                }

                $normalized = trim((string) $value);

                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }

        return null;
    }
}

if (!function_exists('legacy_action_bridge_definition')) {
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $payload
     * @return array{path: string, params: array<string, scalar|null>}
     */
    function legacy_action_bridge_definition(string $script, array $query = [], array $payload = []): array
    {
        $entry = strtolower(basename($script));
        $sources = [$payload, $query];

        return match ($entry) {
            'registroactivo.php' => [
                'path' => '/assets',
                'params' => (static function () use ($query, $payload): array {
                    if (isset($payload['nuevo_Producto']) || isset($payload['update_producto'])) {
                        return array_filter([
                            'legacy' => 'asset-form-write',
                            'name' => trim((string) ($payload['descripcion'] ?? '')),
                            'code' => trim((string) ($payload['codigo'] ?? '')),
                            'quantity' => trim((string) ($payload['cantidad'] ?? '')),
                            'acquired_at' => trim((string) ($payload['fechaRegistro'] ?? '')),
                        ], static fn (mixed $value): bool => $value !== '');
                    }

                    if (isset($query['idborrar'])) {
                        return ['legacy' => 'asset-form-delete'];
                    }

                    return ['legacy' => 'asset-form-write'];
                })(),
            ],
            'registrocliente.php' => [
                'path' => '/customers',
                'params' => (static function () use ($query, $payload): array {
                    if (isset($payload['nuevo_cliente']) || isset($payload['update_cliente'])) {
                        return array_filter([
                            'legacy' => 'customer-form-write',
                            'name' => trim((string) ($payload['nombre'] ?? '')),
                            'document_number' => trim((string) ($payload['ci'] ?? '')),
                            'email' => trim((string) ($payload['email'] ?? '')),
                            'phone' => trim((string) ($payload['telefonoCelular'] ?? $payload['telefonoFijo'] ?? '')),
                            'address' => trim((string) ($payload['direccion'] ?? '')),
                        ], static fn (mixed $value): bool => $value !== '');
                    }

                    if (isset($query['idborrar'])) {
                        return ['legacy' => 'customer-form-delete'];
                    }

                    return ['legacy' => 'customer-form-write'];
                })(),
            ],
            'registrocuenta.php' => [
                'path' => '/cash',
                'params' => (static function () use ($query, $payload): array {
                    $movementDate = trim((string) ($payload['fechaRegistro'] ?? ''));

                    if ($movementDate !== '') {
                        $parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $movementDate);
                        $movementDate = $parsedDate instanceof \DateTimeImmutable
                            ? $parsedDate->format('Y-m-d').'T12:00'
                            : $movementDate;
                    }

                    if (isset($payload['nuevo_Cuenta']) || isset($payload['update'])) {
                        $movementType = strtoupper(trim((string) ($payload['tipo'] ?? '')));

                        return array_filter([
                            'legacy' => 'cash-movement-write',
                            'type' => $movementType === 'ENTRADA' || $movementType === 'E' ? 'income' : 'expense',
                            'amount' => trim((string) ($payload['total'] ?? '')),
                            'notes' => trim((string) ($payload['descripcion'] ?? '')),
                            'occurred_at' => $movementDate,
                        ], static fn (mixed $value): bool => $value !== '');
                    }

                    if (isset($query['idborrar'])) {
                        return ['legacy' => 'cash-movement-delete'];
                    }

                    return ['legacy' => 'cash-movement-write'];
                })(),
            ],
            'registropedido.php' => [
                'path' => '/purchases',
                'params' => (static function () use ($query, $payload): array {
                    if (isset($payload['nuevo_Pedido']) || isset($payload['update'])) {
                        $legacyDescription = trim((string) ($payload['descripcion'] ?? ''));
                        $legacyTotal = trim((string) ($payload['total'] ?? ''));
                        $legacyNote = $legacyDescription;

                        if ($legacyTotal !== '') {
                            $legacyNote = $legacyNote === ''
                                ? "Monto legacy: {$legacyTotal}"
                                : "{$legacyDescription} · Monto legacy: {$legacyTotal}";
                        }

                        return array_filter([
                            'legacy' => 'company-orders-write',
                            'supplier_name' => trim((string) ($payload['proveedor'] ?? '')),
                            'ordered_at' => trim((string) ($payload['fechaRegistro'] ?? '')),
                            'legacy_note' => $legacyNote,
                        ], static fn (mixed $value): bool => $value !== '');
                    }

                    if (isset($query['idborrar'])) {
                        return ['legacy' => 'company-orders-delete'];
                    }

                    return ['legacy' => 'company-orders'];
                })(),
            ],
            'registroproducto.php' => [
                'path' => '/products',
                'params' => (static function () use ($query, $payload): array {
                    if (isset($payload['nuevo_Producto']) || isset($payload['update_producto'])) {
                        return array_filter([
                            'legacy' => 'product-form-write',
                            'name' => trim((string) ($payload['descripcion'] ?? '')),
                            'sku' => trim((string) ($payload['codigo'] ?? '')),
                            'sale_price' => trim((string) ($payload['pventa'] ?? '')),
                            'cost_price' => trim((string) ($payload['pcompra'] ?? '')),
                            'initial_stock' => trim((string) ($payload['cantidad'] ?? '')),
                            'legacy_category_name' => trim((string) ($payload['tipoproducto'] ?? '')),
                        ], static fn (mixed $value): bool => $value !== '');
                    }

                    if (isset($query['idborrar'])) {
                        return ['legacy' => 'product-form-delete'];
                    }

                    return ['legacy' => 'product-form-write'];
                })(),
            ],
            'registrotipoproducto.php' => [
                'path' => '/products',
                'params' => (static function () use ($query, $payload): array {
                    if (isset($payload['nuevo_Tipo']) || isset($payload['update_tipo'])) {
                        return array_filter([
                            'legacy' => 'product-category-write',
                            'category_name' => trim((string) ($payload['tipoProducto'] ?? '')),
                        ], static fn (mixed $value): bool => $value !== '');
                    }

                    if (isset($query['idborrar'])) {
                        return ['legacy' => 'product-category-delete'];
                    }

                    return ['legacy' => 'product-category-write'];
                })(),
            ],
            'registros.php' => [
                'path' => '/users',
                'params' => (static function () use ($query, $payload): array {
                    if (isset($payload['nuevo_usuario']) || isset($payload['update_usuario'])) {
                        return array_filter([
                            'legacy' => 'user-form-write',
                            'name' => trim((string) ($payload['nombre'] ?? '')),
                            'display_name' => trim((string) ($payload['nombre'] ?? '')),
                            'username' => trim((string) ($payload['login'] ?? '')),
                            'legacy_role' => trim((string) ($payload['tipo'] ?? '')),
                        ], static fn (mixed $value): bool => $value !== '');
                    }

                    if (isset($query['idborrar'])) {
                        return ['legacy' => 'user-form-delete'];
                    }

                    return ['legacy' => 'user-form-write'];
                })(),
            ],
            'registrosdatafactura.php' => [
                'path' => '/settings',
                'params' => [
                    'section' => 'billing',
                    'legacy' => 'invoice-data',
                ],
            ],
            'registrosdataidioma.php' => [
                'path' => '/settings',
                'params' => [
                    'section' => 'system',
                    'legacy' => 'language',
                ],
            ],
            'registrosdatamoneda.php' => [
                'path' => '/settings',
                'params' => [
                    'section' => 'system',
                    'legacy' => 'currency',
                ],
            ],
            'registrosproveedor.php' => [
                'path' => '/suppliers',
                'params' => (static function () use ($query, $payload): array {
                    if (isset($payload['new_proveedor']) || isset($payload['update_proveedor'])) {
                        return array_filter([
                            'legacy' => 'supplier-form-write',
                            'name' => trim((string) ($payload['proveedor'] ?? '')),
                            'phone' => trim((string) ($payload['telefono'] ?? '')),
                            'address' => trim((string) ($payload['direccion'] ?? '')),
                        ], static fn (mixed $value): bool => $value !== '');
                    }

                    if (isset($query['idborrar'])) {
                        return ['legacy' => 'supplier-form-delete'];
                    }

                    return ['legacy' => 'supplier-form-write'];
                })(),
            ],
            'searchcontact.php' => [
                'path' => '/customers',
                'params' => [
                    'legacy' => 'customer-lookup-retired',
                    'document_number' => legacy_action_bridge_value($sources, ['ci', 'nit', 'search']),
                ],
            ],
            default => [
                'path' => '/',
                'params' => [],
            ],
        };
    }
}

if (!function_exists('legacy_redirect_action_bridge_entry')) {
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $payload
     */
    function legacy_redirect_action_bridge_entry(string $script, array $query = [], array $payload = []): never
    {
        $definition = legacy_action_bridge_definition($script, $query, $payload);

        redirect(legacy_frontend_entry_url(
            (string) ($definition['path'] ?? '/'),
            is_array($definition['params'] ?? null) ? $definition['params'] : [],
        ));
    }
}

if (!function_exists('legacy_bridge_secret')) {
    function legacy_bridge_secret(): string
    {
        return trim((string) config('app.legacy_bridge_secret', ''));
    }
}

if (!function_exists('legacy_frontend_bridge_token')) {
    function legacy_frontend_bridge_token(): ?string
    {
        $user = auth_user();
        $secret = legacy_bridge_secret();

        if ($user === null || $secret === '') {
            return null;
        }

        $payload = [
            'login' => (string) ($user['login'] ?? ''),
            'issued_at' => time(),
            'expires_at' => time() + 90,
        ];

        if ($payload['login'] === '') {
            return null;
        }

        $encodedPayload = rtrim(strtr(base64_encode((string) json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encodedPayload, $secret);

        return $encodedPayload . '.' . $signature;
    }
}

if (!function_exists('legacy_frontend_entry_url')) {
    /**
     * @param array<string, scalar|null> $query
     */
    function legacy_frontend_entry_url(string $path = '', array $query = []): string
    {
        $filteredQuery = array_filter(
            $query,
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        $path = '/' . ltrim($path, '/');

        if ($filteredQuery !== []) {
            $path .= '?' . http_build_query($filteredQuery);
        }

        $bridgeToken = legacy_frontend_bridge_token();
        if ($bridgeToken === null) {
            return frontend_url($path);
        }

        return frontend_url('/auth/bridge?' . http_build_query([
            'token' => $bridgeToken,
            'redirect' => $path,
        ]));
    }
}

if (!function_exists('legacy_modernize_url')) {
    function legacy_modernize_url(string $url): string
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

        $path = (string) ($parts['path'] ?? $url);
        $script = basename($path);
        $modernRoute = legacy_frontend_route_for_script($script);

        if ($modernRoute !== null) {
            return legacy_frontend_entry_url($modernRoute, $query);
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
        $rebuilt .= $path;

        if ($query !== []) {
            $rebuilt .= '?' . http_build_query($query);
        }

        if (isset($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }
}

if (!function_exists('legacy_public_controller_allowlist')) {
    /**
     * @return list<string>
     */
    function legacy_public_controller_allowlist(): array
    {
        return [
            'AccessUsers.php',
            'Cliente.php',
            'ConFactura.php',
            'Consolidar.php',
            'ConsolidarVenta.php',
            'Cuenta.php',
            'DatosFactura.php',
            'DeleteAllPreVenta.php',
            'DeleteOnlyPreVenta.php',
            'EditPreventa.php',
            'Estadistica.php',
            'Factura.php',
            'InsertarPedidoLlevar.php',
            'InsertarPedidoMesa.php',
            'Inventario.php',
            'Languaje.php',
            'LoginController.php',
            'Logout.php',
            'Moneda.php',
            'Pedido.php',
            'Producto.php',
            'Proveedor.php',
            'RegistrarPreventa.php',
            'RegistrarVenta.php',
            'RegistroActivo.php',
            'RegistroCliente.php',
            'RegistroCuenta.php',
            'RegistroPedido.php',
            'RegistroProducto.php',
            'RegistroTipoProducto.php',
            'Registros.php',
            'RegistrosDataFactura.php',
            'RegistrosDataIdioma.php',
            'RegistrosDataMoneda.php',
            'RegistrosProveedor.php',
            'ReporteMes.php',
            'ReportePorAnio.php',
            'ReporteProductosPdf.php',
            'ReporteUltimos6Mes.php',
            'Reportes.php',
            'ReportesVentas.php',
            'SearchContact.php',
            'SinFactura.php',
            'TipoProducto.php',
            'UpdatePreventa.php',
            'Usuario.php',
            'Ventas.php',
            'principal.php',
            'producto.php',
            'proveedor.php',
        ];
    }
}

if (!function_exists('legacy_public_controller_path')) {
    function legacy_public_controller_path(string $requested): ?string
    {
        if (!in_array($requested, legacy_public_controller_allowlist(), true)) {
            return null;
        }

        $target = base_path('Controller/' . $requested);

        return is_file($target) ? $target : null;
    }
}

if (!function_exists('legacy_strip_auth_from_url')) {
    function legacy_strip_auth_from_url(string $url): string
    {
        return legacy_modernize_url($url);
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
