<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

logout_user();
flash('success', 'Sesion cerrada correctamente.');
redirect(app_url('/index.php'));

