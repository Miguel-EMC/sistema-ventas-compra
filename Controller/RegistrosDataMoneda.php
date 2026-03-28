<?php
require('../Model/Conexion.php');
require('Constants.php');

if (!isset($_SESSION)) {
    session_start();
}

$usuarioLogin = $_POST['usuarioLogin'];
$passwordLogin = $_POST['passwordLogin'];

$con = new conexion();

$allUsuarios = $con->getAllUserData();
$menuMain = $con->getMenuMain();


if (isset($_POST['update_data_moneda'])) {

    $usuarioLogin = $_POST['usuarioLogin'];
    $passwordLogin = $_POST['passwordLogin'];
    $idMoneda = $_POST['idMoneda'];
    $moneda = $_POST['moneda'];


    if ($moneda == "Argentina") {
        $pais = "Argentina";
        $tipoMoneda = "$";
        $contexto = "peso argentino";
    }

    if ($moneda == "EUA") {
        $pais = "Estados Unidos";
        $tipoMoneda = "$ USD";
        $contexto = "dólar estadounidense";
    }
    if ($moneda == "Bolivia") {
        $pais = "Bolivia";
        $tipoMoneda = "Bs.";
        $contexto = "bolivianos";
    }
    if ($moneda == "Ecuador") {
        $pais = "Ecuador";
        $tipoMoneda = "$";
        $contexto = "dólar estadounidense";
    }
    if ($moneda == "Colombia") {
        $pais = "Colombia";
        $tipoMoneda = "$";
        $contexto = "peso colombiano";
    }
    if ($moneda == "Peru") {
        $pais = "Peru";
        $tipoMoneda = "S/";
        $contexto = "sol";
    }
    if ($moneda == "Brasil") {
        $pais = "Brasil";
        $tipoMoneda = "R$";
        $contexto = "real brasileño";
    }
    if ($moneda == "Chile") {
        $pais = "Chile";
        $tipoMoneda = "$";
        $contexto = "peso chileno	";
    }
    if ($moneda == "Venezuela") {
        $pais = "Venezuela";
        $tipoMoneda = "Bs F";
        $contexto = "bolívar fuerte";
    }
    if ($moneda == "Mexico") {
        $pais = "Mexico";
        $tipoMoneda = "$";
        $contexto = "peso mexicano";
    }
    if ($moneda == "Espania") {
        $pais = "Espania";
        $tipoMoneda = "€";
        $contexto = "euro";
    }
    if ($moneda == "Paraguay") {
        $pais = "Paraguay";
        $tipoMoneda = "₲";
        $contexto = "guaraní paraguayo";
    }
    if ($moneda == "Uruguay") {
        $pais = "Uruguay";
        $tipoMoneda = "$";
        $contexto = "peso uruguayo";
    }

    $mensaje = "Se Actualizo  los datos de la Moneda correctamente !!!";
    $alerta = "alert alert-info";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);
    $updateDatosMoneda = $con->updateDataMoneda($idMoneda, $pais, $tipoMoneda, $contexto);

    header("Location: Moneda.php?usuario=$usuarioLogin&password=$passwordLogin&estado='Activo'");









}