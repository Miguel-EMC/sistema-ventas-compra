<?php
require('../Model/Conexion.php');
require('Constants.php');

if (!isset($_SESSION)) {
    session_start();
}

$usuarioLogin = $_POST['usuarioLogin'];
$passwordLogin = $_POST['passwordLogin'];

$con = new conexion();

if (isset($_POST['nuevo_Tipo'])) {
    $tipoproducto = $_POST['tipoProducto'];

    $mensaje = "Se registro un nuevo tipo producto  correctamente !!!";
    $alerta = "alert alert-success";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);
    $regiestroNewTipoProducto = $con->registerNewTipoProduct($tipoproducto);

}


if (isset($_GET['idborrar'])) {
    $usuarioLogin = $_GET['usuarioLogin'];
    $passwordLogin = $_GET['passwordLogin'];
    $idborrar = $_GET['idborrar'];

    $mensaje = "Se elimino  los datos del tipo producto correctamente !!!";
    $alerta = "alert alert-danger";
    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);

    $deleteTipoProducto = $con->deleteTipoProduct($idborrar);


}

if (isset($_POST['update_tipo'])) {
    $idproducto = $_POST['idtipoproducto'];
    $tipoproducto = $_POST['tipoProducto'];


    $mensaje = "Se Actualizo  los datos del tipo Producto correctamente !!!";
    $alerta = "alert alert-info";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);

    $updateTipoProducto = $con->updateTipoProducto($idproducto, $tipoproducto);
}


$menuMain = $con->getMenuMain();
header("Location: TipoProducto.php?usuario=$usuarioLogin&password=$passwordLogin&estado='Activo'");
