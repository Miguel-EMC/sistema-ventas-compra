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

if (isset($_POST['update_data_factura'])) {

    $iddatos = $_POST['iddatos'];

    $usuarioLogin = $_POST['usuarioLogin'];
    $passwordLogin = $_POST['passwordLogin'];
    $iddatos = $_POST['iddatos'];
    $propietario = $_POST['propietario'];
    $razon = $_POST['razon'];
    $direccion = $_POST['direccion'];
    $nro = $_POST['nro'];
    $telefono = $_POST['telefono'];

    $mensaje = "Se Actualizo  los datos de la factura correctamente !!!";
    $alerta = "alert alert-info";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);

    $updateDatosFactura = $con->updateDataFactura($iddatos, $propietario, $razon, $direccion, $nro, $telefono);



}


    header("Location: DatosFactura.php?usuario=$usuarioLogin&password=$passwordLogin&estado='Activo'");



?>