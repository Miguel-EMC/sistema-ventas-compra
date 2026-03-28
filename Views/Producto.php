<?php
$hasProducts = false;
while ($product = mysqli_fetch_array($allProducto)) {
    $hasProducts = true;
    ?>
    <article class="pos-product-card">
        <div class="pos-product-media">
            <img
                src="<?php echo htmlspecialchars($urlViews . $product['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars((string) $product['nombreProducto'], ENT_QUOTES, 'UTF-8'); ?>"
                class="imgRedonda"
            >
        </div>
        <div class="pos-product-copy">
            <h4 class="pos-product-name"><?php echo htmlspecialchars((string) $product['nombreProducto'], ENT_QUOTES, 'UTF-8'); ?></h4>
            <div class="pos-product-price">
                <?php echo htmlspecialchars((string) $product['precioVenta'], ENT_QUOTES, 'UTF-8'); ?>
                <?php echo htmlspecialchars((string) $tipoMonedaElegida, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
        <div class="pos-product-actions">
            <button
                type="button"
                class="pos-product-action pos-product-action--mesa"
                onclick="insertarPedidoMesa('<?php echo $product['idproducto']; ?>','<?php echo $id_usuario; ?>')"
            >
                Mesa
            </button>
            <button
                type="button"
                class="pos-product-action"
                onclick="insertarPedidoLlevar('<?php echo $product['idproducto']; ?>','<?php echo $id_usuario; ?>')"
            >
                Llevar
            </button>
        </div>
    </article>
    <?php
}

if (!$hasProducts) {
    echo '<div class="alert alert-warning">No hay productos disponibles para mostrar en caja.</div>';
}
?>
