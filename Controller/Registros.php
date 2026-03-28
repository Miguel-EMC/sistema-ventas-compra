<?php

declare(strict_types=1);

require('../Model/Conexion.php');
require('Constants.php');

if (!isset($_SESSION)) {
    session_start();
}

$usuarioLogin = $_POST['usuarioLogin'] ?? $_GET['usuarioLogin'] ?? ($_SESSION['auth']['login'] ?? '');
$passwordLogin = $_POST['passwordLogin'] ?? $_GET['passwordLogin'] ?? legacy_sentinel_password();

$con = new Conexion();

if (isset($_POST['nuevo_usuario'])) {
    $usuario = $_POST['login'];
    $tipo = $_POST['tipo'];
    $nombre = $_POST['nombre'];
    $password = $_POST['password'];

    $mensaje = "Se Añadio un nuevo Usuario";
    $alerta = "alert alert-success";
    $con->updateMensajeAlert($mensaje, $alerta);

    $imagenUsuario = "fotoUsuario/user.png";
    if (!empty($_FILES['userfile']['name'])) {
        try {
            $imagenUsuario = upload_service()->storeUploadedImage($_FILES['userfile']);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            header("Location: Usuario.php?estado=Activo");
            exit;
        }
    }

    $con->getRegisterNewUser($nombre, $tipo, $usuario, $password, $imagenUsuario);
}

if (isset($_GET['idborrar'])) {
    $idUsuario = $_GET['idborrar'];
    $usuarioLogin = $_GET['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_GET['passwordLogin'] ?? $passwordLogin;

    $mensaje = "Se Elimino un usuario";
    $alerta = "alert alert-danger";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->deleteUsuario($idUsuario);
}

if (isset($_POST['update_usuario'])) {
    $idUsuarioData = $_POST['idUsuario'];
    $login = $_POST['login'];
    $tipo = $_POST['tipo'];
    $nombre = $_POST['nombre'];
    $password = $_POST['password'];
    $imagen = $_POST['imagen'];

    $usuarioLogin = $_POST['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_POST['passwordLogin'] ?? $passwordLogin;

    $mensaje = "Se Edito los datos de un usuario";
    $alerta = "alert alert-info";
    $con->updateMensajeAlert($mensaje, $alerta);

    $imagenUsuario = $imagen;
    if (!empty($_FILES['userfileEdit']['name'])) {
        try {
            $imagenUsuario = upload_service()->storeUploadedImage($_FILES['userfileEdit']);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            header("Location: Usuario.php?estado=Activo");
            exit;
        }
    }

    $con->updateUsuario($login, $tipo, $nombre, $password, $imagenUsuario, $idUsuarioData);
}

header("Location: Usuario.php?estado=Activo");
exit;
