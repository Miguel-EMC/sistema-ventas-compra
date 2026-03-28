<?php

declare(strict_types=1);

return [
    'uploads' => [
        'disk_path' => env('UPLOADS_DISK_PATH', 'storage/uploads/fotoproducto'),
        'public_path' => trim((string) env('UPLOADS_PUBLIC_PATH', 'fotoproducto'), '/'),
        'public_disk' => env('UPLOADS_PUBLIC_DISK', 'public/assets/fotoproducto'),
        'max_size' => (int) env('UPLOADS_MAX_SIZE', 5 * 1024 * 1024),
    ],
];
