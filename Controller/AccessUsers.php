<?php

declare(strict_types=1);

require('../Model/Conexion.php');
require('Constants.php');

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST') {
    validate_csrf_or_abort();

    $login = trim((string) ($_POST['usuario'] ?? ''));
    $plainPassword = (string) ($_POST['password'] ?? '');

    if ($login === '' || $plainPassword === '') {
        flash('error', 'Debes ingresar usuario y password.');
        redirect(app_url('/index.php'));
    }

    $user = auth_service()->attempt($login, $plainPassword);

    if ($user === null) {
        flash('error', 'Usuario o password incorrectos, por favor intenta de nuevo.');
        redirect(app_url('/index.php'));
    }

    login_user($user);
    redirect(app_url('/Controller/AccessUsers.php'));
}

$currentUser = auth_user();

if ($currentUser === null) {
    flash('error', 'Necesitas iniciar sesion para continuar.');
    redirect(app_url('/index.php'));
}

$con = new Conexion();
$usuario = $currentUser['login'];
$password = legacy_sentinel_password();
$tipo = $currentUser['tipo'];
$id_usuario = $currentUser['id_usu'];
$nombres = $currentUser['nombre'];
$foto = $currentUser['foto'];
$urlViews = URL_VIEWS;
$imageUser = $foto;
$userLogueado = $nombres;
$menuMain = $tipo === 'VENTAS' ? $con->getMenuMainVentas() : $con->getMenuMain();

if ($tipo === 'VENTAS') {
    require('../Views/WellcomeVentas.php');
    exit;
}

require('../Views/Wellcome.php');

