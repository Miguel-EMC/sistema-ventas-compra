<!DOCTYPE html>
<html lang="es">
<?php
$pageTitle = 'Panel principal';
$quickActions = array_values(array_filter(
    $menuMain,
    static fn (array $item): bool => (($item['location'] ?? '') !== 'AccessUsers.php')
));
include('Head.php');
?>
<body>
<section id="container" class="">
    <header class="header dark-bg">
        <div class="toggle-nav">
            <div class="icon-reorder tooltips" data-original-title="Mostrar u ocultar menu" data-placement="bottom">
                <i class="icon_menu"></i>
            </div>
        </div>
        <?php include("Logo.php"); ?>
        <div class="nav search-row" id="top_menu">
            <ul class="nav top-menu">
                <li>
                    <form class="navbar-form" action="#" onsubmit="return false;">
                    </form>
                </li>
            </ul>
        </div>
        <?php include("DropDown.php"); ?>
    </header>

    <?php include("Menu.php"); ?>
</section>

<section id="main-content">
    <section class="wrapper">
        <div class="dashboard-hero">
            <div class="dashboard-hero__content">
                <span class="dashboard-kicker">Centro de operaciones</span>
                <h1>Hola, <?php echo htmlspecialchars((string) $userLogueado, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>
                    Gestiona la operacion diaria desde un panel mas limpio: ventas, inventario, clientes,
                    reportes y configuracion quedan al alcance sin perder el flujo clasico del sistema.
                </p>
                <div class="dashboard-hero__actions">
                    <a href="Ventas.php" class="btn btn-primary">
                        <i class="fa fa-shopping-cart"></i> Abrir ventas
                    </a>
                    <a href="Producto.php" class="btn btn-default">
                        <i class="fa fa-cubes"></i> Revisar productos
                    </a>
                </div>
            </div>

            <div class="dashboard-hero__stats">
                <article class="metric-tile">
                    <span>Rol actual</span>
                    <strong><?php echo htmlspecialchars((string) $tipo, ENT_QUOTES, 'UTF-8'); ?></strong>
                </article>
                <article class="metric-tile">
                    <span>Fecha operativa</span>
                    <strong><?php echo htmlspecialchars(date('d M Y'), ENT_QUOTES, 'UTF-8'); ?></strong>
                </article>
                <article class="metric-tile">
                    <span>Entorno</span>
                    <strong><?php echo htmlspecialchars((string) strtoupper((string) config('app.env', 'local')), ENT_QUOTES, 'UTF-8'); ?></strong>
                </article>
            </div>
        </div>

        <div class="dashboard-section-head">
            <div>
                <span class="section-kicker">Atajos principales</span>
                <h3>Modulos mas usados</h3>
            </div>
            <p>
                Accesos directos a las areas clave para caja, inventario, clientes y reportes.
            </p>
        </div>

        <div class="quick-link-grid">
            <?php foreach (array_slice($quickActions, 0, 6) as $menu): ?>
                <a class="quick-link-card" href="<?php echo htmlspecialchars(legacy_strip_auth_from_url((string) ($menu['location'] ?? '#')), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="quick-link-card__icon">
                        <i class="<?php echo htmlspecialchars((string) ($menu['icon'] ?? 'fa fa-circle'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                    </span>
                    <strong><?php echo htmlspecialchars((string) ($menu['opcion'] ?? 'Modulo'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p>
                        Entra rapido a este modulo para seguir operando sin pasar por menus largos.
                    </p>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-highlight-grid">
            <div class="info-box blue-bg">
                <i class="fa fa-truck"></i>
                <div class="count">Panel</div>
                <div class="title">Proveedores y abastecimiento</div>
            </div>
            <div class="info-box brown-bg">
                <i class="icon_piechart"></i>
                <div class="count">Reportes</div>
                <div class="title">Ventas y rendimiento comercial</div>
            </div>
            <div class="info-box dark-bg">
                <i class="fa fa-money"></i>
                <div class="count">Caja</div>
                <div class="title">Control de ingresos y egresos</div>
            </div>
            <div class="info-box green-bg">
                <i class="fa fa-cubes"></i>
                <div class="count">Stock</div>
                <div class="title">Inventario disponible del negocio</div>
            </div>
        </div>

        <div class="dashboard-content-grid">
            <article class="dashboard-card">
                <div class="dashboard-card__header">Rutina recomendada del dia</div>
                <div class="dashboard-card__body">
                    <ul class="status-list">
                        <li>
                            <span class="status-list__label">Paso 1</span>
                            <span class="status-list__value">Revisar productos y stock critico</span>
                        </li>
                        <li>
                            <span class="status-list__label">Paso 2</span>
                            <span class="status-list__value">Abrir ventas y atender operaciones del dia</span>
                        </li>
                        <li>
                            <span class="status-list__label">Paso 3</span>
                            <span class="status-list__value">Registrar movimientos de caja y compras</span>
                        </li>
                        <li>
                            <span class="status-list__label">Paso 4</span>
                            <span class="status-list__value">Cerrar con reportes y consolidacion</span>
                        </li>
                    </ul>
                </div>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__header">Estado del sistema</div>
                <div class="dashboard-card__body">
                    <ul class="status-list">
                        <li>
                            <span class="status-list__label">Sesion</span>
                            <span class="status-pill status-pill--success">Activa</span>
                        </li>
                        <li>
                            <span class="status-list__label">Interfaz</span>
                            <span class="status-list__value">Template general activo</span>
                        </li>
                        <li>
                            <span class="status-list__label">Base de datos</span>
                            <span class="status-list__value">Configurada via .env</span>
                        </li>
                        <li>
                            <span class="status-list__label">Zona horaria</span>
                            <span class="status-list__value"><?php echo htmlspecialchars((string) config('app.timezone', 'UTC'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                    </ul>
                </div>
            </article>
        </div>
    </section>
</section>

<?php include("LibraryJs.php"); ?>
</body>
</html>
