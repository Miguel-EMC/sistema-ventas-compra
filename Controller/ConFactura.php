<?php
require('../Model/Conexion.php');
require('Constants.php');
require_once('Codigo_control.class.php');
if (!isset($_SESSION)) {
    session_start();
}
$con = new conexion();

$usuario= $_GET['usuario'];
$password = $_GET['password'];

$ci =$_GET['ci'];

$totalAPagar =$_GET['ingreso1'];
$efectivo =$_GET['ingreso2'];
$cambio=$_GET['resultado'];
$fechaVenta = date("Y-m-d H:i:s");

$searchClient = $con->getClienteDatos($ci);

foreach ($searchClient as $cliente) {
    $nombreClienteDato = $cliente['apellido'];
}

$registrarDatoscliente = $con->registrarDatosPreventa($ci,$nombreClienteDato ,$totalAPagar,$efectivo,$cambio,$fechaVenta,'1');


$searchUser = $con->getUser($usuario,$password);

foreach ($searchUser as $user) {
    $tipo = $user['tipo'];
    $id_usuario = $user['id_usu'];
    $nombres = $user['nombre'];
    $password = $user['password'];
    $foto = $user['foto'];
}
$urlViews = URL_VIEWS;
$userLogueado = $nombres;
$imageUser = $foto;

$datosFactura = $con-> getDatosFactura();
 foreach ($datosFactura as $facturaPropieario){
     $propietario = $facturaPropieario['propietario'];
     $razon = $facturaPropieario['razon'];
     $direccion = $facturaPropieario['direccion'];
     $nro = $facturaPropieario['nro'];
     $telefono = $facturaPropieario['telefono'];
 }

 $datosDosificacion = $con->getDatosDosificacion();
 foreach ($datosDosificacion as $dosificacion){
   $autorizacion = $dosificacion['autorizacion'];
   $factura =$dosificacion['factura'];
   $llave =$dosificacion['llave'];
   $nit =$dosificacion['nit'];
   $fechaLimite=$dosificacion['fechaL'];
 }

 $obtenerDatosCliente =$con->getDataCliente();
 foreach ( $obtenerDatosCliente as $datosCliente){
     $nombreCliente = $datosCliente['nombre'];
     $ci = $datosCliente['ci'];
     $fecha = $datosCliente['fecha'];
     $totalApagar = $datosCliente['totalApagar'];
     $efectivo = $datosCliente['efectivo'];
     $cambio = $datosCliente['cambio'];
 }

 $pedidoTotalPreventa = $con->getPedidoTotalForFactura();
 $pedido = mysqli_num_rows($pedidoTotalPreventa);

$dataMoneda = $con -> getMoneda();

while ($dataMonedaValues = mysqli_fetch_array($dataMoneda)){
    $contextMoneda = $dataMonedaValues['contexto'];
    $tipoMoneda = $dataMonedaValues['tipoMoneda'];
}

$fechaCodigoControl = date("Ymd");

$codigoControl = new CodigoControl($autorizacion, $factura, $nit, $fechaCodigoControl, $totalApagar, $llave);
$getCodigoControl = $codigoControl->generar();

$getDatosFecha = explode("-",$fechaLimite);
$fechaLimiteAnio=$getDatosFecha[0];
$fechaLimiteMes=$getDatosFecha[1];
$fechaLimiteDia=$getDatosFecha[2];

$fechaLimiteEmision = $fechaLimiteDia.' / '.$fechaLimiteMes.' / '.$fechaLimiteAnio;

date_default_timezone_set("America/Caracas" ) ;
$dateInicial= date('Y-m-d');
$dateFinal= date('Y-m-d');
$getNumeroFicha = $con->getNumFicha($dateInicial,$dateFinal);

foreach ( $getNumeroFicha as $numFicha){
    $ficha = $numFicha['numficha'];
}

$menuMain = $con->getMenuMain();
require('../Views/ConFacturaViews.php');
?>
