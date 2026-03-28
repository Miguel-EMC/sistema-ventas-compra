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
    $tipoproducto = $_POST['tipoproducto'];
    $codigo = $_POST['codigo'];
    $nombreProducto = $_POST['descripcion'];
    $cantidad = $_POST['cantidad'];
    $precioVenta = $_POST['pventa'];
    $precioCompra = $_POST['pcompra'];
    $fechaRegistro = $_POST['fechaRegistro'];
    $proveedor = null;

    if (!empty($_FILES['userfile']['name'])) {
        try {
            $destino = upload_service()->storeUploadedImage($_FILES['userfile']);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            header("Location: Producto.php?estado=Activo");
            exit;
        }
    } else {
        $destino = "fotoUsuario/user.png";
    }

    $mensaje = "Se registro un nuevo producto correctamente";
    $alerta = "alert alert-success";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->registerNewProducto($destino, $codigo, $nombreProducto, $cantidad, $fechaRegistro, $precioVenta, $tipoproducto, $proveedor, $precioCompra);
}

if (isset($_GET['idborrar'])) {
    $usuarioLogin = $_GET['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_GET['passwordLogin'] ?? $passwordLogin;
    $idborrar = $_GET['idborrar'];

    $mensaje = "Se elimino los datos del producto correctamente";
    $alerta = "alert alert-danger";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->deleteProduct($idborrar);
}

if (isset($_POST['update_producto'])) {
    $idproducto = $_POST['idproducto'];
    $imagen = $_POST['imagen'];
    $tipoproducto = $_POST['tipoproducto'];
    $codigo = $_POST['codigo'];
    $nombreProducto = $_POST['descripcion'];
    $cantidad = $_POST['cantidad'];
    $precioVenta = $_POST['pventa'];
    $precioCompra = $_POST['pcompra'];
    $fechaRegistro = date("Y-m-d");
    $proveedor = null;

    if (!empty($_FILES['userfileEdit']['name'])) {
        try {
            $destino = upload_service()->storeUploadedImage($_FILES['userfileEdit']);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            header("Location: Producto.php?estado=Activo");
            exit;
        }
    } else {
        $destino = $imagen;
    }

    $mensaje = "Se Actualizo los datos del Producto correctamente";
    $alerta = "alert alert-info";
    $con->updateMensajeAlert($mensaje, $alerta);
    $con->updateProduct($destino, $codigo, $nombreProducto, $cantidad, $fechaRegistro, $precioVenta, $tipoproducto, $proveedor, $precioCompra, $idproducto);
}

header("Location: Producto.php?estado=Activo");
exit;
