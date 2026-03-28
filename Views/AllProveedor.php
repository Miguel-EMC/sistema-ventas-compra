<?php

foreach (catalog_repository()->providers() as $proveedorOption) {
    $nombreProveedor = htmlspecialchars((string) ($proveedorOption['proveedor'] ?? ''), ENT_QUOTES, 'UTF-8');
    echo "<option value=\"{$nombreProveedor}\">{$nombreProveedor}</option>";
}

