<?php
require('../Model/Conexion.php');
require('Constants.php');

$con = new conexion();
$currentUser = auth_user();

if ($currentUser !== null) {
    $usuario = $currentUser['login'];
    $password = legacy_sentinel_password();
    $tipoUsuserio = $currentUser['tipo'];
} else {
    $onlyUserSession = $con->getUser((string) $usuario, (string) $password);
    $user = $onlyUserSession[0] ?? null;

    if ($user === null) {
        flash('error', 'Necesitas iniciar sesion para continuar.');
        redirect(app_url('/index.php'));
    }

    $usuario = $user['login'];
    $password = $user['password'];
    $tipoUsuserio = $user['tipo'];
}
$urlViews = URL_VIEWS;

$getTotalPreventa = $con->getTotalPreventa();

foreach ($getTotalPreventa as $preVentaTotal){
    $preventa = $preVentaTotal['total'];
}

require('../Views/Facturaviews.php');
?>
