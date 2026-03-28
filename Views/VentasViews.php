<!DOCTYPE html>
<html lang="en">
<?php
include('Head.php');
?>
<body>
<section id="container" class="">
    <header class="header dark-bg">
        <style>
            .textbox {
                border: 1px solid #6a98e6;
                font-size: 23px;
                font-family: Arial, Verdana;
                padding-left: 7px;
                padding-right: 7px;
                padding-top: 10px;
                padding-bottom: 1px;
                border-radius: 4px;
                -moz-border-radius: 4px;
                -webkit-border-radius: 4px;
                -o-border-radius: 4px;
                background: #FFFFFF;
                background: linear-gradient(left, #FFFFFF, #6a98e6);
                background: -moz-linear-gradient(left, #FFFFFF, #F7F9FA);
                background: -webkit-linear-gradient(left, #FFFFFF, #F7F9FA);
                background: -o-linear-gradient(left, #FFFFFF, #F7F9FA);
                color: #2E3133;
            }

            .textbox:hover {
                color: #2E3133;
                border-color: #ff0000;
            }
        </style>
        <style>
            .textbox2 {
                border: 1px solid #6a98e6;
                font-size: 23px;
                font-family: Arial, Verdana;
                padding-left: 7px;
                padding-right: 7px;
                padding-top: 0px;
                padding-bottom: 1px;
                border-radius: 4px;
                -moz-border-radius: 4px;
                -webkit-border-radius: 4px;
                -o-border-radius: 4px;
                background: #FFFFFF;
                background: linear-gradient(left, #FFFFFF, #6a98e6);
                background: -moz-linear-gradient(left, #FFFFFF, #F7F9FA);
                background: -webkit-linear-gradient(left, #FFFFFF, #F7F9FA);
                background: -o-linear-gradient(left, #FFFFFF, #F7F9FA);
                color: #2E3133;
            }

            .textbox2:hover {
                color: #2E3133;
                border-color: #ff0000;
            }
        </style>
        <style>
            p:hover {
                background-color: yellow;
                border-color: #ff0000;
            }
        </style>
        <style type="text/css">
            a.nounderline:link {
                text-decoration: none;
            }
        </style>


        <div class="toggle-nav">
            <div class="icon-reorder tooltips" data-original-title="Toggle Navigation" data-placement="bottom"><i
                    class="icon_menu"></i></div>
        </div>
        <?PHP include("Logo.php") ?>
        <div class="nav search-row" id="top_menu">
            <!--  search form start -->
            <ul class="nav top-menu">
                <li>
                    <form class="navbar-form">
                        <!--                              <input class="form-control" placeholder="Search" type="text">-->
                    </form>
                </li>
            </ul>
            <!--  search form end -->
        </div>
        <?PHP include("DropDown.php"); ?>
    </header>
    <?PHP include("Menu.php") ?>

</section>

<!--sidebar end-->
<!--main content start-->
<section id="main-content">
    <section class="wrapper">
        <div class="row">
            <div class="col-lg-12">
            </div></div>
        </div>
<br><br><br>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <!-- page start-->
                    <div class="row">
                        <div class="col-sm-7">

                            <section class="panel">
                                <div class="row">
                                    <header class="panel-heading tab-bg-primary ">

                                        <ul class="nav nav-tabs">
                                            <li class="active">
                                                <a data-toggle="tab" href="#home">PRODUCTOS</a>
                                            </li>
                                        </ul>

                                    </header>
                                    <div class="panel-body">
                                        <div class="tab-content">
                                            <div id="home" class="tab-pane active">

<!--                                                id="dataTables-example"-->

                                                <table class="table table-striped table-bordered table-hover">
                                                    <thead>
                                                    <tr>
                                                     <?PHP
                                                       include ("Producto.php");
                                                     ?>

                                                    </tr>
                                                    </thead>

                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                        </div>

                        <div class="col-sm-5">

                            <div id="resultado">
                             <?PHP
                               include ("Pedido.php");
                             ?>
                            </div>

                        </div>

                    </div>
                </div>


            </div>
        </div>

    </section>
</section>
<!--main content end-->

<?PHP include("LibraryJs.php"); ?>


</body>
</html>