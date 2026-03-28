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

if (isset($_POST['nuevo_cliente'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $direccion = $_POST['direccion'];
    $telefonoFijo = $_POST['telefonoFijo'];
    $telefonoCelular = $_POST['telefonoCelular'];
    $email = $_POST['email'];
    $fechaRegistro = $_POST['fechaRegistro'];
    $ci = $_POST['ci'];

    if (!empty($_FILES['userfile']['name'])) {
        try {
            $destino = upload_service()->storeUploadedImage($_FILES['userfile']);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            header("Location: Cliente.php?estado=Activo");
            exit;
        }
    } else {
        $destino = "fotoUsuario/user.png";
    }

    $mensaje = "Se registro un nuevo cliente correctamente";
    $alerta = "alert alert-success";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->registerNewCliente($destino, $nombre, $apellido, $direccion, $telefonoFijo, $telefonoCelular, $email, $fechaRegistro, $ci);
}

if (isset($_GET['idborrar'])) {
    $usuarioLogin = $_GET['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_GET['passwordLogin'] ?? $passwordLogin;
    $idborrar = $_GET['idborrar'];

    $mensaje = "Se elimino los datos del cliente correctamente";
    $alerta = "alert alert-danger";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->deleteClient($idborrar);
}

if (isset($_POST['update_cliente'])) {
    $idcliente = $_POST['idcliente'];
    $imagen = $_POST['imagen'];
    $usuarioLogin = $_POST['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_POST['passwordLogin'] ?? $passwordLogin;

    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $direccion = $_POST['direccion'];
    $telefonoFijo = $_POST['telefonoFijo'];
    $telefonoCelular = $_POST['telefonoCelular'];
    $email = $_POST['email'];
    $fechaRegistro = date('Y-m-d');
    $ci = $_POST['ci'];

    if (!empty($_FILES['userfileEdit']['name'])) {
        try {
            $destino = upload_service()->storeUploadedImage($_FILES['userfileEdit']);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            header("Location: Cliente.php?estado=Activo");
            exit;
        }
    } else {
        $destino = $imagen;
    }

    $mensaje = "Se Actualizo los datos del cliente correctamente";
    $alerta = "alert alert-info";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->updateClient($idcliente, $destino, $nombre, $apellido, $direccion, $telefonoFijo, $telefonoCelular, $email, $fechaRegistro, $ci);
}

header("Location: Cliente.php?estado=Activo");
exit;
