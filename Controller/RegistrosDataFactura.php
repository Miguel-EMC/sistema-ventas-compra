<?php
require('../Model/Conexion.php');
require('Constants.php');


if (!isset($_SESSION)) {
    session_start();
}

$usuarioLogin = $_POST['usuarioLogin'] ?? $_GET['usuarioLogin'] ?? ($_SESSION['auth']['login'] ?? '');
$passwordLogin = $_POST['passwordLogin'] ?? $_GET['passwordLogin'] ?? legacy_sentinel_password();


$con = new conexion();

$allUsuarios = $con->getAllUserData();
$menuMain = $con->getMenuMain();

if (isset($_POST['update_data_factura'])) {

    $iddatos = $_POST['iddatos'];

    $usuarioLogin = $_POST['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_POST['passwordLogin'] ?? $passwordLogin;
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


    header("Location: DatosFactura.php?estado=Activo");



?>
