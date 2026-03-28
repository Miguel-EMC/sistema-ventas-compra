<form class="sales-order-edit-panel" name="form" action="UpdatePreventa.php" method="GET">
    <input type="hidden" id="idProducto" name="idProducto" value="<?php echo $idProducto; ?>">
    <input type="hidden" id="imagen" name="imagen" value="<?php echo $imagen; ?>">
    <input type="hidden" id="precio" name="precio" value="<?php echo $precio; ?>">
    <input type="hidden" id="pventa" name="pventa" value="<?php echo $pventa; ?>">
    <input type="hidden" id="tipo" name="tipo" value="<?PHP echo $tipoUsuserio; ?>">
    <input type="hidden" id="tipoPedido" name="tipoPedido" value="<?php echo $tipoPedido; ?>">
    <input type="hidden" id="userId" name="userId" value="<?php echo $userId; ?>">
    <input name="idpreventa" id="idpreventa" type="hidden" value="<?php echo $idPreventa; ?>">

    <div class="sales-order-edit-panel__header">
        <strong>Editar producto del pedido</strong>
        <span>Ajusta la cantidad antes de continuar con el cobro.</span>
    </div>

    <div class="sales-order-edit-grid">
        <div class="sales-order-edit-field">
            <label for="producto">Producto</label>
            <input name="producto" id="producto" class="form-control" type="text" value="<?php echo $producto; ?>" readonly>
        </div>

        <div class="sales-order-edit-field">
            <label for="cantidadUpdated">Nueva cantidad</label>
            <input name="cantidadUpdated" id="cantidadUpdated" class="form-control" autocomplete="off" type="text" value="<?php echo $cantidadActual; ?>">
        </div>

        <div class="sales-order-edit-field">
            <label for="nuevoPrecio">Precio unitario</label>
            <input name="nuevoPrecio" id="nuevoPrecio" class="form-control" readonly type="text" value="<?php echo $precio; ?>">
        </div>
    </div>

    <div class="sales-order-edit-actions">
        <button name="a_update" type="submit" class="btn btn-primary">Actualizar pedido</button>
    </div>
</form>
