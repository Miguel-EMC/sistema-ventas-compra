<!DOCTYPE html>
<html lang="es">
<?php
$pageTitle = 'Panel de ventas';
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
                <span class="dashboard-kicker">Operacion comercial</span>
                <h1>Panel de ventas listo para caja</h1>
                <p>
                    Este espacio prioriza rapidez de atencion, consulta de clientes y acceso inmediato a ventas y reportes.
                    La idea es que el equipo comercial vea menos ruido y mas acciones utiles.
                </p>
                <div class="dashboard-hero__actions">
                    <a href="Ventas.php" class="btn btn-primary">
                        <i class="fa fa-shopping-basket"></i> Registrar venta
                    </a>
                    <a href="Cliente.php" class="btn btn-default">
                        <i class="fa fa-users"></i> Ver clientes
                    </a>
                </div>
            </div>

            <div class="dashboard-hero__stats">
                <article class="metric-tile">
                    <span>Operador</span>
                    <strong><?php echo htmlspecialchars((string) $userLogueado, ENT_QUOTES, 'UTF-8'); ?></strong>
                </article>
                <article class="metric-tile">
                    <span>Rol</span>
                    <strong><?php echo htmlspecialchars((string) $tipo, ENT_QUOTES, 'UTF-8'); ?></strong>
                </article>
                <article class="metric-tile">
                    <span>Jornada</span>
                    <strong><?php echo htmlspecialchars(date('d M Y'), ENT_QUOTES, 'UTF-8'); ?></strong>
                </article>
            </div>
        </div>

        <div class="dashboard-section-head">
            <div>
                <span class="section-kicker">Flujo rapido</span>
                <h3>Acciones recomendadas</h3>
            </div>
            <p>
                Atajos directos para trabajar en caja sin perder tiempo buscando opciones.
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
                        Acceso rapido a este modulo para continuar con el flujo comercial del dia.
                    </p>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-content-grid">
            <article class="dashboard-card">
                <div class="dashboard-card__header">Guia rapida para el equipo de ventas</div>
                <div class="dashboard-card__body">
                    <ul class="status-list">
                        <li>
                            <span class="status-list__label">Paso 1</span>
                            <span class="status-list__value">Abrir ventas y registrar la operacion</span>
                        </li>
                        <li>
                            <span class="status-list__label">Paso 2</span>
                            <span class="status-list__value">Consultar cliente o crear uno nuevo si hace falta</span>
                        </li>
                        <li>
                            <span class="status-list__label">Paso 3</span>
                            <span class="status-list__value">Confirmar producto, stock y total vendido</span>
                        </li>
                        <li>
                            <span class="status-list__label">Paso 4</span>
                            <span class="status-list__value">Revisar reportes al cierre de caja</span>
                        </li>
                    </ul>
                </div>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__header">Estado operativo</div>
                <div class="dashboard-card__body">
                    <ul class="status-list">
                        <li>
                            <span class="status-list__label">Sesion</span>
                            <span class="status-pill status-pill--success">Activa</span>
                        </li>
                        <li>
                            <span class="status-list__label">Interfaz</span>
                            <span class="status-list__value">Optimizada para trabajo diario</span>
                        </li>
                        <li>
                            <span class="status-list__label">Accesos</span>
                            <span class="status-list__value"><?php echo htmlspecialchars((string) count($quickActions), ENT_QUOTES, 'UTF-8'); ?> modulos disponibles</span>
                        </li>
                        <li>
                            <span class="status-list__label">Objetivo</span>
                            <span class="status-list__value">Cobro rapido y seguimiento comercial</span>
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
