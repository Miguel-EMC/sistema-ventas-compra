<?php
require('../Model/Conexion.php');
require('Constants.php');

if (!isset($_SESSION)) {
    session_start();
}

$usuario = $_GET['usuario'];
$password = $_GET['password'];


$con = new conexion();

$onlyUserSession = $con->getUser($usuario,$password);

foreach ($onlyUserSession as $user) {
    $usuario = $user['login'];
    $password = $user['password'];
    $tipoUsuserio = $user['tipo'];
}
$urlViews = URL_VIEWS;

$getTotalPreventa = $con->getTotalPreventa();

foreach ($getTotalPreventa as $preVentaTotal){
    $preventa = $preVentaTotal['total'];
}

require('../Views/Facturaviews.php');
?>
