<section class="panel">
    <header class="panel-heading">
        <div class="sales-panel__header">
            <div class="sales-panel__title">
                <strong>Productos solicitados</strong>
                <span>Revisa cantidades, precios y tipo de entrega antes del cobro.</span>
            </div>
        </div>
    </header>

    <div id="formularioEdit" class="u-hidden"></div>

    <table class="table table-striped">
        <thead>
        <tr>
            <th width="20">Imagen</th>
            <th>Productos</th>
            <th>Cant.</th>
            <th>Precio</th>
            <th>Total</th>
            <th>Tipo</th>
            <th>Opcion</th>
        </tr>
        </thead>
        <tbody>
        <?PHP
        $showPreventa = $con->getPreventa();
        while ($preventa = mysqli_fetch_array($showPreventa)) {
            ?>

            <tr>
                <td><img src="<?php echo $urlViews . $preventa['imagen'] ?>" height="60" width="60"></td>
                <td><b> <?PHP echo $preventa['producto']; ?></b></td>
                <td><?PHP echo $preventa['cantidad']; ?></td>
                <td><?PHP echo $preventa['precio']; ?></td>
                <td><?PHP echo $preventa['totalPrecio']; ?></td>
                <td><?PHP echo $preventa['tipo']; ?></td>
                <td>


                <?PHP
                    echo "<a class='btn btn-success'   
                               onclick=\"editarPreventa('" . $preventa['idProducto'] . "','" . $preventa['tipo'] . "','" . $preventa['idUser'] . "')\">
                               <i class='icon_pencil-edit'></i></a>";

                    echo "<a class='btn btn-danger'
                         onclick=\"deleteOnlyProducto('" . $preventa['idProducto'] . "','" . $preventa['tipo'] . "','" . $preventa['idUser'] . "')\">
                <i class='icon_minus-box'></i></a>"; ?>

                </td>
            </tr>

        <?PHP } ?>


        <tr>
            <td colspan="3"></td>
            <td><strong> Total :</strong></td>
            <td>
                <h2>
                    <strong>
                        <?PHP
                        $totalPreventaConsulta = $con->getTotalPreventa();
                        while ($totalVenta = mysqli_fetch_array($totalPreventaConsulta)) {
                            $userId = $totalVenta['idUser'];
                            echo $totalVenta['total'];
                        }
                        ?>
                    </strong>
                </h2>
            </td>
            <td colspan="2"></td>
        </tr>

        <tr>
            <td colspan="7" align="center">
                <div class="sales-order-actions">
                <?PHP
                if (isset($userId)) {

                    echo " <a data-toggle='modal' class='btn btn-primary enabled'
                              href='Factura.php?usuario=$usuario&password=$password'
                              data-target='#myModal'>
                    <i class='icon_check'></i><strong> ACEPTAR</strong> </a>
                    <div class='modal fade' id='myModal' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true'>      
                       <div class='modal-dialog'>
                        </div>
                    </div>";
                    echo "<a class='btn btn-danger'
                              onclick=\"deleteAllPreventa('" . $userId . "')\">
                    <i class='icon_minus-box'></i><strong> CANCELAR</strong> </a>";
                } else {
                    echo " <a class='btn btn-primary disabled'
                              onclick=\"\">
                    <i class='icon_check'></i><strong> ACEPTAR</strong> </a>";
                    echo " <a class='btn btn-danger disabled'
                              onclick=\"\">
                    <i class='icon_minus-box'></i><strong> CANCELAR</strong> </a>";
                }
                ?>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</section>
