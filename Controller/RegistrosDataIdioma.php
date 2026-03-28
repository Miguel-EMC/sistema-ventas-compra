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

if (isset($_POST['update_data_idioma'])) {

    $usuarioLogin = $_POST['usuarioLogin'] ?? $usuarioLogin;
    $passwordLogin = $_POST['passwordLogin'] ?? $passwordLogin;
    $idIdiomaSytem = $_POST['idIdioma'];
    $idioma = $_POST['idioma'];

    if ($idioma == "Espaniol") {
        $idiomaConfiguration = array(
            array('14', 'Principal'),
            array('15', 'Configuracion'),
            array('16', 'Proveedores'),
            array('17', 'Clientes'),
            array('18', 'Productos'),
            array('19', 'Activos'),
            array('20', 'Ventas'),
            array('21', 'Cuentas'),
            array('22', 'Pedidos'),
            array('23', 'Consolidar'),
            array('24', 'Reporte'),
            array('25', 'Reportes Graficos')
        );

        foreach ($idiomaConfiguration as $idiomaElegido) {
            list($idIdioma, $opcionMenu) = $idiomaElegido;
            $updateIdiomaMenu = $con->updateIdiomaSistem($opcionMenu, $idIdioma);
        }


    }

    if ($idioma == "Portugues") {

        $idiomaConfiguration = array(
            array('14', 'Diretor'),
            array('15', 'Configuração'),
            array('16', 'Vendedores'),
            array('17', 'Clientes'),
            array('18', 'Produtos'),
            array('19', 'Ativos'),
            array('20', 'Vendas'),
            array('21', 'Contas'),
            array('22', 'Pedidos'),
            array('23', 'Consolidar'),
            array('24', 'Relatório'),
            array('25', 'Relatórios Gráficos')
        );

        foreach ($idiomaConfiguration as $idiomaElegido) {
            list($idIdioma, $opcionMenu) = $idiomaElegido;
            $updateIdiomaMenu = $con->updateIdiomaSistem($opcionMenu, $idIdioma);
        }


    }

    if ($idioma == "Ingles") {

        $idiomaConfiguration = array(
            array('14', 'Main'),
            array('15', 'Setting'),
            array('16', 'vendors'),
            array('17', 'Customers'),
            array('18', 'Products'),
            array('19', 'Assets'),
            array('20', 'Sales'),
            array('21', 'Accounts'),
            array('22', 'Orders'),
            array('23', 'Consolidate'),
            array('24', 'Report'),
            array('25', 'Graphic Reports')
        );

        foreach ($idiomaConfiguration as $idiomaElegido) {
            list($idIdioma, $opcionMenu) = $idiomaElegido;
            $updateIdiomaMenu = $con->updateIdiomaSistem($opcionMenu, $idIdioma);
        }
    }


    $mensaje = "Se Actualizo  los datos del Idioma correctamente !!!";
    $alerta = "alert alert-info";

    $updateMensaje = $con->updateMensajeAlert($mensaje, $alerta);
    $updateDatosIdioma = $con->updateDataIdioma($idioma, $idIdiomaSytem);



}

header("Location: Languaje.php?estado=Activo");
