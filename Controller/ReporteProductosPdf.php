<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

legacy_require_auth();

if (isset($_GET['inventario'])) {
    redirect(legacy_frontend_entry_url('/assets', [
        'legacy' => 'inventory-report',
        'auto_export' => 'pdf',
    ]));
}

redirect(legacy_frontend_entry_url('/products', [
    'legacy' => 'catalog-report',
    'auto_export' => 'pdf',
]));
