<?php
require('../Model/Conexion.php');
require('Constants.php');

if (!isset($_SESSION)) {
    session_start();
}

$usuarioLogin = $_POST['usuarioLogin'] ?? $_GET['usuarioLogin'] ?? ($_SESSION['auth']['login'] ?? '');
$passwordLogin = $_POST['passwordLogin'] ?? $_GET['passwordLogin'] ?? legacy_sentinel_password();

$con = new conexion();

if (isset($_POST['nuevo_Cuenta'])) {
    $tipo = $_POST['tipo'];
    $descripcion = $_POST['descripcion'];
    $totalEfectivo = $_POST['total'];
    $fechaRegistro = $_POST['fechaRegistro'];


    if ($tipo == "Entrada") {
        $newAccount = $con->registerNewAccount("E", $descripcion, $totalEfectivo, $fechaRegistro, $usuarioLogin, 0);
    }

    if ($tipo == "Salida") {
        $newAccount = $con->registerNewAccount("S", $descripcion, 0, $fechaRegistro, $usuarioLogin, $totalEfectivo);
    }

    $mensaje = "Se registro una nueva Cuenta  correctamente !!!";
    $alerta = "alert alert-success";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);

}


if (isset($_GET['idborrar'])) {

    $idAccount = $_GET['idborrar'];
    $usuarioLogin = $_GET['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_GET['passwordLogin'] ?? $passwordLogin;


    $deleteAccount = $con->deleteAccount($idAccount);

    $mensaje = "Se elimino  los datos de la cuenta correctamente !!!";
    $alerta = "alert alert-danger";
    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);

}


if (isset($_POST['update'])) {
    $idgastos = $_POST['idgastos'];
    $tipo = $_POST['tipo'];
    $descripcion = $_POST['descripcion'];
    $totalEfectivo = $_POST['total'];
    $fechaRegistro = $_POST['fechaRegistro'];



    if ($tipo == "E") {
        $newAccount = $con->updateAccount($tipo, $descripcion, $totalEfectivo, $fechaRegistro, $usuarioLogin, 0, $idgastos);
    }

    if ($tipo == "S") {
        $newAccount = $con->updateAccount($tipo, $descripcion, 0, $fechaRegistro, $usuarioLogin, $totalEfectivo, $idgastos);
    }

    $mensaje = "Se Actualizo  los datos de la Cuenta correctamente !!!";
    $alerta = "alert alert-info";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);

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


$menuMain = $con->getMenuMain();

header("Location: Cuenta.php?estado=Activo");


?>
