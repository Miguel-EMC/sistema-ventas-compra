<?php
require ('../Model/Conexion.php');
require ('Constants.php');






if(!isset($_SESSION)){

    session_start();


}

$usuario = $_GET['usuario'];
$password = $_GET['password'];

$con = new conexion();


$searchUser = $con->getUser($usuario , $password);

foreach($searchUser as $user) {

    $tipo = $user['tipo'];
    $id_usuario = $user['id_usu'];
    $nombres = $user['nombre'];
    $password = $user['password'];
    $foto = $user['foto'];



}

if(empty($searchUser)){

    echo '
        <script language = javascript>
            alert("Usuario o Password incorrectos, por favor intenta de nuevo")
            self.location = "../index.php"
    </script>
   ';



}

else if($tipo == 'VENTAS'){


    require ('../Views/WellcomeVentas.php');



}

else if($tipo == 'ADMINISTRADOR'){


    $urlViews = URL_VIEWS;

    $imageUser = $foto;
    
    $userLogueado=$nombres;


    $menuMain=$con->getMenuMain();
    require ('../Views/Wellcome.php');



}








