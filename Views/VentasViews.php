<!DOCTYPE html>
<html lang="en">
<?php
$pageTitle = 'Ventas';
include('Head.php');
?>
<body>
<section id="container" class="">
    <header class="header dark-bg">
        <div class="toggle-nav">
            <div class="icon-reorder tooltips" data-original-title="Toggle Navigation" data-placement="bottom"><i
                    class="icon_menu"></i></div>
        </div>
        <?PHP include("Logo.php") ?>
        <div class="nav search-row" id="top_menu">
            <!--  search form start -->
            <ul class="nav top-menu">
                <li>
                    <form class="navbar-form">
                        <!--                              <input class="form-control" placeholder="Search" type="text">-->
                    </form>
                </li>
            </ul>
            <!--  search form end -->
        </div>
        <?PHP include("DropDown.php"); ?>
    </header>
    <?PHP include("Menu.php") ?>

</section>

<section id="main-content">
    <section class="wrapper">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="page-header"><i class="fa fa-shopping-cart"></i> OPERACION DE VENTAS</h3>
                <ol class="breadcrumb">
                    <li><i class="fa fa-home"></i><a href="principal.php">Inicio</a></li>
                    <li><i class="fa fa-shopping-basket"></i><a href="Ventas.php">Caja</a></li>
                    <li><i class="fa fa-bolt"></i><span>Atencion rapida</span></li>
                </ol>
                <?php if (isset($mensaje) && trim((string) $mensaje) !== ''): ?>
                    <div class="<?php echo htmlspecialchars((string) ($alerta ?? 'alert alert-success'), ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                        <strong><?php echo htmlspecialchars((string) $mensaje, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="sales-summary">
            <span class="sales-summary__item">Operador: <?php echo htmlspecialchars((string) ($userLogueado ?? 'Usuario'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="sales-summary__item">Rol: <?php echo htmlspecialchars((string) ($tipo ?? 'VENTAS'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="sales-summary__item">Moneda: <?php echo htmlspecialchars((string) ($tipoMonedaElegida ?? 'Local'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="sales-summary__item">Fecha: <?php echo htmlspecialchars(date('d/m/Y'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <div class="sales-shell">
            <section class="panel sales-panel">
                <header class="panel-heading sales-panel__header">
                    <div class="sales-panel__title">
                        <strong>Catalogo de productos</strong>
                        <span>Selecciona productos por mesa o para llevar sin salir del flujo de caja.</span>
                    </div>
                    <div class="sales-actions">
                        <a href="Cliente.php" class="btn btn-default"><i class="icon_contacts_alt"></i> Clientes</a>
                        <a href="Reporte.php" class="btn btn-primary"><i class="icon_datareport"></i> Reportes</a>
                    </div>
                </header>
                <div class="panel-body">
                    <div class="pos-products-grid">
                        <?PHP include("Producto.php"); ?>
                    </div>
                </div>
            </section>

            <section class="panel sales-panel sales-order-panel">
                <header class="panel-heading sales-panel__header">
                    <div class="sales-panel__title">
                        <strong>Resumen del pedido</strong>
                        <span>Confirma cantidades, abre el cobro y cierra la venta desde este panel.</span>
                    </div>
                </header>
                <div class="panel-body">
                    <div id="resultado">
                        <?PHP include("Pedido.php"); ?>
                    </div>
                </div>
            </section>
        </div>
    </section>
</section>

<?PHP include("LibraryJs.php"); ?>


</body>
</html>
