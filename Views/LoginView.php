<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema para manejo de empresas y ventas">
    <meta name="author" content="ventaspos">
    <meta name="keyword" content="POS, ventas, inventario, caja">
    <meta name="theme-color" content="#0f4c5c">
    <link rel="shortcut icon" href="<?= htmlspecialchars(asset_url('img/logoI.png'), ENT_QUOTES, 'UTF-8'); ?>">

    <title><?= htmlspecialchars(strtoupper((string) config('app.name', 'ventaspos')) . ' | Acceso seguro', ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="<?= htmlspecialchars(asset_url('css/font-awesome.min.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(asset_url('css/elegant-icons-style.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(asset_url('css/app.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <!--[if lt IE 9]>
    <script src="<?= htmlspecialchars(asset_url('js/html5shiv.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/respond.min.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <![endif]-->
</head>

<body class="auth-page">
<div class="auth-shell">
    <div class="auth-card">
        <aside class="auth-hero">
            <div class="auth-brand">
                <div class="auth-brand__mark">
                    <img src="<?= htmlspecialchars(asset_url('img/logoI.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Logo de iCONT">
                </div>
                <div class="auth-brand__copy">
                    <span>Template General</span>
                    <strong>iCONT</strong>
                </div>
            </div>

            <p class="auth-lead">
                Opera ventas, inventario, clientes y caja desde una interfaz limpia, consistente y preparada para seguir creciendo modulo por modulo.
            </p>

            <ul class="auth-points">
                <li>
                    <i class="icon_datareport"></i>
                    <span>Un solo lenguaje visual para login, panel, formularios, tablas y modales.</span>
                </li>
                <li>
                    <i class="icon_shield"></i>
                    <span>Acceso protegido con sesiones, CSRF y autenticacion migrada a practicas modernas.</span>
                </li>
                <li>
                    <i class="icon_box-checked"></i>
                    <span>Base lista para limpiar los modulos legacy sin arrastrar estilos globales innecesarios.</span>
                </li>
            </ul>

            <div class="auth-meta">
                <span>Core UI</span>
                <span>PHP 8.5+</span>
                <span>PDO + .env</span>
            </div>
        </aside>

        <section class="auth-form-panel">
            <div class="auth-form-intro">
                <span class="auth-badge">Acceso seguro</span>
                <h2>Inicia sesion</h2>
                <p>
                    Entra con tu cuenta operativa para continuar al panel administrativo y al flujo de ventas.
                </p>
            </div>

            <?php if (($flashError = consume_flash('error')) !== null): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (($flashSuccess = consume_flash('success')) !== null): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="auth-form" action="<?= htmlspecialchars(app_url('/Controller/AccessUsers.php'), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                <?= csrf_field(); ?>

                <label for="usuario">Usuario</label>
                <div class="input-group">
                    <span class="input-group-addon"><i class="icon_profile"></i></span>
                    <input id="usuario" type="text" name="usuario" class="form-control" placeholder="Ej. henry" autocomplete="username" autofocus required>
                </div>

                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-addon"><i class="icon_key_alt"></i></span>
                    <input id="password" type="password" name="password" class="form-control" placeholder="Ingresa tu clave" autocomplete="current-password" required>
                </div>

                <button class="btn btn-primary btn-lg btn-block" type="submit">Entrar al panel</button>
                <button class="btn btn-default btn-lg btn-block" type="button" disabled>Registro publico pendiente de migracion</button>
            </form>

            <p class="auth-helper">
                Entorno actual: <?= htmlspecialchars((string) strtoupper((string) config('app.env', 'local')), ENT_QUOTES, 'UTF-8'); ?>.
            </p>
        </section>
    </div>
</div>

<script src="<?= htmlspecialchars(asset_url('js/app.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
