<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap/app.php';

if (auth_user() !== null) {
    redirect(app_url('/Controller/AccessUsers.php'));
}

require __DIR__ . '/Controller/LoginController.php';
