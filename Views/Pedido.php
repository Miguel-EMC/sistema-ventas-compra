<?php
$preventaRows = [];
$showPreventa = $con->getPreventa();
$orderLineCount = 0;
$orderUnitsTotal = 0;

while ($preventa = mysqli_fetch_array($showPreventa)) {
    $preventaRows[] = $preventa;
    $orderLineCount++;
    $orderUnitsTotal += (int) ($preventa['cantidad'] ?? 0);
}

$totalAmount = '0.00';
$userId = null;
$totalPreventaConsulta = $con->getTotalPreventa();

while ($totalVenta = mysqli_fetch_array($totalPreventaConsulta)) {
    $userId = $totalVenta['idUser'] ?? $userId;
    $totalAmount = (string) ($totalVenta['total'] ?? $totalAmount);
}

$invoiceUrl = app_url('/Controller/Factura.php');
?>

<div class="sales-order-shell">
    <div class="sales-order-intro">
        <div class="sales-order-intro__copy">
            <strong>Resumen del pedido</strong>
            <span>Revisa cantidades, precios y tipo de entrega antes del cobro.</span>
        </div>
        <div class="sales-order-meta">
            <span class="sales-order-meta__pill"><?php echo htmlspecialchars((string) $orderLineCount, ENT_QUOTES, 'UTF-8'); ?> lineas</span>
            <span class="sales-order-meta__pill"><?php echo htmlspecialchars((string) $orderUnitsTotal, ENT_QUOTES, 'UTF-8'); ?> unidades</span>
        </div>
    </div>

    <div id="formularioEdit" class="u-hidden"></div>

    <?php if ($preventaRows === []): ?>
        <div class="sales-order-empty">
            <span class="sales-order-empty__icon"><i class="icon_cart"></i></span>
            <strong>Tu pedido aun esta vacio</strong>
            <p>Agrega productos desde el catalogo para ver el detalle del cobro aqui.</p>
        </div>
    <?php else: ?>
        <div class="sales-order-items">
            <?php foreach ($preventaRows as $preventa): ?>
                <article class="sales-order-item">
                    <div class="sales-order-item__media">
                        <img
                            src="<?php echo htmlspecialchars($urlViews . $preventa['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars((string) $preventa['producto'], ENT_QUOTES, 'UTF-8'); ?>"
                            width="64"
                            height="64"
                        >
                    </div>
                    <div class="sales-order-item__body">
                        <div class="sales-order-item__top">
                            <div class="sales-order-item__copy">
                                <strong><?php echo htmlspecialchars((string) $preventa['producto'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="sales-order-item__meta">
                                    <span class="sales-order-badge"><?php echo htmlspecialchars((string) $preventa['tipo'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="sales-order-badge sales-order-badge--muted"><?php echo htmlspecialchars((string) $preventa['cantidad'], ENT_QUOTES, 'UTF-8'); ?> uds</span>
                                </div>
                            </div>
                            <div class="sales-order-item__amount">
                                <span>Total</span>
                                <strong><?php echo htmlspecialchars((string) $preventa['totalPrecio'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                        </div>

                        <div class="sales-order-item__bottom">
                            <span class="sales-order-item__unit">
                                Unitario: <?php echo htmlspecialchars((string) $preventa['precio'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <div class="sales-order-row-actions">
                                <a
                                    class="btn btn-default"
                                    onclick="editarPreventa('<?php echo htmlspecialchars((string) $preventa['idProducto'], ENT_QUOTES, 'UTF-8'); ?>','<?php echo htmlspecialchars((string) $preventa['tipo'], ENT_QUOTES, 'UTF-8'); ?>','<?php echo htmlspecialchars((string) $preventa['idUser'], ENT_QUOTES, 'UTF-8'); ?>')"
                                >
                                    <i class="icon_pencil-edit"></i> Editar
                                </a>
                                <a
                                    class="btn btn-danger"
                                    onclick="deleteOnlyProducto('<?php echo htmlspecialchars((string) $preventa['idProducto'], ENT_QUOTES, 'UTF-8'); ?>','<?php echo htmlspecialchars((string) $preventa['tipo'], ENT_QUOTES, 'UTF-8'); ?>','<?php echo htmlspecialchars((string) $preventa['idUser'], ENT_QUOTES, 'UTF-8'); ?>')"
                                >
                                    <i class="icon_minus-box"></i> Quitar
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="sales-order-footer">
        <div class="sales-order-total">
            <span>Total del pedido</span>
            <strong><?php echo htmlspecialchars($totalAmount, ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>

        <div class="sales-order-actions">
            <?php if ($userId !== null): ?>
                <a data-toggle="modal" class="btn btn-primary enabled" href="<?php echo htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8'); ?>" data-target="#myModal">
                    <i class="icon_check"></i><strong> ACEPTAR</strong>
                </a>
                <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                    <div class="modal-dialog"></div>
                </div>
                <a class="btn btn-danger" onclick="deleteAllPreventa('<?php echo htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8'); ?>')">
                    <i class="icon_minus-box"></i><strong> CANCELAR</strong>
                </a>
            <?php else: ?>
                <a class="btn btn-primary disabled">
                    <i class="icon_check"></i><strong> ACEPTAR</strong>
                </a>
                <a class="btn btn-danger disabled">
                    <i class="icon_minus-box"></i><strong> CANCELAR</strong>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
