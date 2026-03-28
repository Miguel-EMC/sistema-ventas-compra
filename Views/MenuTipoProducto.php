<?php

foreach (catalog_repository()->productTypes() as $tipoProductoOption) {
    $tipo = htmlspecialchars((string) ($tipoProductoOption['tipoproducto'] ?? ''), ENT_QUOTES, 'UTF-8');
    echo "<option value=\"{$tipo}\">{$tipo}</option>";
}
