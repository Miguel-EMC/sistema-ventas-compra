
<!DOCTYPE html>
<html lang="en">
<?php
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

<!--sidebar end-->
<!--main content start-->
<section id="main-content">
    <section class="wrapper">
        <!--overview start-->
        <div class="row">
            <div class="col-lg-12">
                <h3 class="page-header"><i class="fa fa-laptop"></i> PRINCIPAL</h3>
                <div class="<?PHP echo $alerta; ?>" role="alert">
                    <strong><?PHP echo $mensaje; ?></strong>
                </div>

                <ol class="breadcrumb">
                    <li><i class="fa fa-home"></i><a href="principal.php">Inicio</a></li>
                    <li><i class="fa fa-truck"></i>Consolidar Ventas</li>

                </ol>
            </div>
        </div>

        <header class="panel-heading"> Consolidar Ventas</header>


        <div class="panel-body">
            <div class="dataTable_wrapper">
                <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                    <thead>
                    <tr>
                        <th><i class="icon_profile"></i> NUM FICHA</th>
                        <th><i class="icon_profile"></i> FECHA</th>
                        <th><i class="icon_briefcase_alt"></i> CLIENTE</th>
                        <th><i class="icon_profile"></i> TOTAL Sus.</th>
                        <th><i class="icon_contacts_alt"></i> CODIGO CONTROL</th>
                        <th><i class="icon_contacts_alt"></i> ESTADO</th>
                        <th><i class="icon_contacts_alt"></i> COMMENTARIO</th>
                        <th><i class="icon_cog"></i> ACCIONES</th>
                    </tr>
                    </thead>
                    <?php

                    while ($ventas = mysqli_fetch_array($allVentas)) {
                        ?>

                        <tr>
                            <td align="center"><?php echo $ventas['idVentas']; ?></td>
                            <td><?php echo $ventas['fechaVenta']; ?></td>
                            <td><?php echo $ventas['cliente']; ?></td>
                            <td align="center" ><?php echo $ventas['total']; ?></td>
                            <td><?php echo $ventas['codigoControl']; ?></td>
                            <td><?php echo $ventas['estado']; ?></td>
                            <td><?php echo $ventas['comentario']; ?></td>


                            <td>
                                <a href="#a<?php echo $ventas[0]; ?>" role="button" class="btn btn-warning"
                                   data-toggle="modal"><i class="icon_pencil-edit_alt"></i></a>
                                <a href="ConsolidarVenta.php?idConsolidar=<?php echo $ventas[0]; ?>&codigoControl=<?php echo $ventas[5]; ?>&usuarioLogin=<?php echo $usuario; ?>&passwordLogin=<?php echo $password; ?>"
                                   class="btn btn-success"><i class="icon_box-checked"></i></a>
                            </td>
                        </tr>

                        <div id="a<?php echo $ventas[0]; ?>" class="modal fade" tabindex="-1" role="dialog"
                             aria-labelledby="myModalLabel" aria-hidden="true">
                            <form class="form-validate form-horizontal" name="form2" action="ConsolidarVenta.php"
                                  method="post">
                                <input name="usuarioLogin" value="<?php echo $usuario;?>" type="hidden" >
                                <input name="passwordLogin" value="<?php echo $password;?>" type="hidden" >
                                <input type="hidden" name="idVentas"
                                       value="<?php echo $ventas['idVentas']; ?>">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true">×
                                            </button>
                                            <h3 id="myModalLabel" align="center">Escribir Comentario</h3>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-group ">
                                                <label for="responsable"
                                                       class="control-label col-lg-2">Detalle de la venta:</label>
                                                <div class="col-lg-10">
                                                    <input class="form-control input-lg m-bot15" type="text"  name="comentario" >
                                                </div>
                                            </div>
                                        </div>

                                        <br>
                                        <div class="modal-footer">
                                            <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">
                                                <strong>Cerrar</strong></button>
                                            <button name="insertarComentario" type="submit" class="btn btn-primary"><strong>Editar</strong>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                    <?php } ?>
                </table>
            </div>
        </div>
    </section>
</section>
<!--main content end-->

<?PHP include("LibraryJs.php"); ?>


</body>
</html>
