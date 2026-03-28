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

if (isset($_POST['nuevo_Producto'])) {
    $codigo = $_POST['codigo'];
    $nombreProducto = $_POST['descripcion'];
    $cantidad = $_POST['cantidad'];
    $fechaRegistro = $_POST['fechaRegistro'];

    if (!empty($_FILES['userfile']['name'])) {
        try {
            $destino = upload_service()->storeUploadedImage($_FILES['userfile']);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            header("Location: Inventario.php?estado=Activo");
            exit;
        }
    } else {
        $destino = "fotoUsuario/user.png";
    }

    $mensaje = "Se registro un nuevo Activo correctamente";
    $alerta = "alert alert-success";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->registerNewActivo($destino, $codigo, $nombreProducto, $cantidad, $fechaRegistro);
}

if (isset($_GET['idborrar'])) {
    $usuarioLogin = $_GET['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_GET['passwordLogin'] ?? $passwordLogin;
    $idborrar = $_GET['idborrar'];

    $mensaje = "Se elimino los datos del activo correctamente";
    $alerta = "alert alert-danger";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->deleteActivo($idborrar);
}

if (isset($_POST['update_producto'])) {
    $idproducto = $_POST['idactivo'];
    $imagen = $_POST['imagen'];
    $codigo = $_POST['codigo'];
    $nombreProducto = $_POST['descripcion'];
    $cantidad = $_POST['cantidad'];
    $fechaRegistro = date("Y-m-d");

    if (!empty($_FILES['userfileEdit']['name'])) {
        try {
            $destino = upload_service()->storeUploadedImage($_FILES['userfileEdit']);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            header("Location: Inventario.php?estado=Activo");
            exit;
        }
    } else {
        $destino = $imagen;
    }

    $mensaje = "Se Actualizo los datos del activo correctamente";
    $alerta = "alert alert-info";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->updateActivo($destino, $codigo, $nombreProducto, $cantidad, $fechaRegistro, $idproducto);
}

header("Location: Inventario.php?estado=Activo");
exit;
