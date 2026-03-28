<?php
require('../Model/Conexion.php');
require('Constants.php');

$con = new Conexion();
$currentUser = auth_user();

if ($currentUser !== null) {
    $usuario = $currentUser['login'];
    $password = legacy_sentinel_password();
    $tipo = $currentUser['tipo'];
    $id_usuario = $currentUser['id_usu'];
    $nombres = $currentUser['nombre'];
    $foto = $currentUser['foto'];
} else {
    $searchUser = $con->getUser((string) $usuario, (string) $password);
    $user = $searchUser[0] ?? null;

    if ($user === null) {
        flash('error', 'Necesitas iniciar sesion para continuar.');
        redirect(app_url('/index.php'));
    }

    $tipo = $user['tipo'];
    $id_usuario = $user['id_usu'];
    $nombres = $user['nombre'];
    $password = $user['password'];
    $foto = $user['foto'];
}

$colorElegido="#4e4e4e";
$colorDefecto="#0061c2";
$idMenu="7";

/*$updateMenuColorElegido=$con->updateOpcionElegida($colorElegido,$idMenu);
$updateMenuColorDefecto=$con->updateOpcionDefecto($colorDefecto,$idMenu);*/

$tipoDeAlerta = $con->getMensajeAlerta();
foreach ($tipoDeAlerta as $tipoAlerta) {
    $alerta = $tipoAlerta['tipoAlerta'];
    $mensaje = $tipoAlerta['mensaje'];
}

if (!isset($_GET['estado'])) {
    $mensaje = "";
    $alerta = "";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);
}


$urlViews = URL_VIEWS;
$userLogueado = $nombres;
$imageUser = $foto;


$tipoDeMoneda = $con->getTipoMoneda();
foreach ($tipoDeMoneda as $moneda){
    $tipoMonedaElegida =$moneda['tipoMoneda'];
}

$allProducto =$con->getAllProducto();
$menuMain = $tipo === 'VENTAS' ? $con->getMenuMainVentas() : $con->getMenuMain();
require("../Views/VentasViews.php");


?>
