<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema para manejo de empresas y ventas">
    <meta name="author" content="ventaspos">
    <meta name="keyword" content="POS, ventas, inventario, caja">
    <link rel="shortcut icon" href="<?= htmlspecialchars(asset_url('img/favicon.png'), ENT_QUOTES, 'UTF-8'); ?>">

    <title>iCONT - Sistema Profesional Para Empresas</title>
    <link href="<?= htmlspecialchars(asset_url('css/bootstrap.min.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(asset_url('css/bootstrap-theme.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(asset_url('css/elegant-icons-style.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(asset_url('css/font-awesome.min.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(asset_url('css/style.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(asset_url('css/style-responsive.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <!--[if lt IE 9]>
    <script src="<?= htmlspecialchars(asset_url('js/html5shiv.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/respond.min.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <![endif]-->
</head>

<body class="login-img3-body">
<div class="container">
    <form class="login-form" action="<?= htmlspecialchars(app_url('/Controller/AccessUsers.php'), ENT_QUOTES, 'UTF-8'); ?>" method="post">
        <?= csrf_field(); ?>
        <div class="login-wrap">
            <p class="login-img"><i class="icon_lock_alt"></i></p>
            <?php if (($flashError = consume_flash('error')) !== null): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (($flashSuccess = consume_flash('success')) !== null): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div class="input-group">
                <span class="input-group-addon"><i class="icon_profile"></i></span>
                <input type="text" name="usuario" class="form-control" placeholder="Nombre de Usuario" autocomplete="username" autofocus required>
            </div>
            <div class="input-group">
                <span class="input-group-addon"><i class="icon_key_alt"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary btn-lg btn-block" type="submit">Ingresar</button>
            <button class="btn btn-info btn-lg btn-block" type="button" disabled>Registro pendiente de migracion</button>
        </div>
    </form>
</div>
</body>
</html>
