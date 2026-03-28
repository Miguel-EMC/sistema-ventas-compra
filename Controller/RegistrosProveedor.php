<?php
require('../Model/Conexion.php');
require('Constants.php');

if (!isset($_SESSION)) {
    session_start();
}

$usuarioLogin = $_POST['usuarioLogin'] ?? $_GET['usuarioLogin'] ?? ($_SESSION['auth']['login'] ?? '');
$passwordLogin = $_POST['passwordLogin'] ?? $_GET['passwordLogin'] ?? legacy_sentinel_password();

$con = new conexion();

if (isset($_POST['new_proveedor'])) {

    $usuarioLogin = $_POST['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_POST['passwordLogin'] ?? $passwordLogin;
    $proveedor = $_POST['proveedor'];
    $responsable = $_POST['responsable'];
    $fechaRegistro = $_POST['fechaRegistro'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    $mensaje = "Se Actualizo  los datos de la Proveedor correctamente !!!";
    $alerta = "alert alert-success";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);

    $registrarNewProveedor = $con->registerNewProveedor($proveedor, $responsable, $direccion, $telefono, $fechaRegistro);


}


if (isset($_GET['idborrar'])) {

    $usuarioLogin = $_GET['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_GET['passwordLogin'] ?? $passwordLogin;
    $idProveedor = $_GET['idborrar'];

    $mensaje = "Se elimino  los datos de la Proveedor correctamente !!!";
    $alerta = "alert alert-danger";
    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);

    $deleteProveedor = $con->deleteProveedor($idProveedor);




}


if (isset($_POST['update_proveedor'])) {
    $idProveedor = $_POST['idproveedor'];
    $usuarioLogin = $_POST['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_POST['passwordLogin'] ?? $passwordLogin;
    $proveedor = $_POST['proveedor'];
    $responsable = $_POST['responsable'];
    $fechaRegistro = $_POST['fechaRegistro'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['direccion'];


    $mensaje = "Se Actualizo  los datos de la Proveedor correctamente !!!";
    $alerta = "alert alert-info";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);


    $registrarNewProveedor = $con->updateProveedor($idProveedor, $proveedor, $responsable, $direccion, $telefono, $fechaRegistro);


}


$searchUser = $con->getUser($usuarioLogin, $passwordLogin);
$allUsuarios = $con->getAllUserData();

foreach ($searchUser as $user) {
    $tipo = $user['tipo'];
    $id_usuario = $user['id_usu'];
    $nombres = $user['nombre'];
    $password = $user['password'];
    $foto = $user['foto'];
}



header("Location: Proveedor.php?estado=Activo");

?>
