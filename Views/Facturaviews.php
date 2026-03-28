<div class="modal-content">
    <div class="modal-header">
        <h3 id="myModalLabel" align="center">Registrar cobro</h3>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
    </div>
    <div class="modal-body">
        <form class="form-validate form-horizontal" name="calculadora" action="RegistrarPreventa.php" method="post">
            <input type="hidden" id="usuario" name="usuario" value="<?php echo $usuario; ?>">
            <input type="hidden" id="password" name="password" value="<?php echo $password; ?>">

            <table class="factura-form-table">
                <tr>
                    <th><label for="searchNit">NIT / CI</label></th>
                    <td colspan="5">
                        <input class="form-control input-lg m-bot15" type="text" required name="ci" autocomplete="off" onkeyup="myFunctionSearch()" id="searchNit">
                    </td>
                </tr>
                <tr>
                    <th><label for="myDiv">Nombre</label></th>
                    <td colspan="5">
                        <div class="textbox2" readonly name="nombreCliente" id="myDiv"></div>
                    </td>
                </tr>
                <tr>
                    <th><label for="ingreso1">Total a pagar</label></th>
                    <td>
                        <input class="form-control input-lg m-bot15" id="ingreso1" type="text" required name="ingreso1" readonly value="<?PHP echo $preventa; ?>" onkeyup="Suma()">
                    </td>
                    <th><label for="ingreso2">Efectivo</label></th>
                    <td>
                        <input class="form-control input-lg m-bot15" id="ingreso2" type="text" required name="ingreso2" value="" onkeyup="Suma()">
                    </td>
                    <th><label for="resultado">Cambio</label></th>
                    <td>
                        <input class="form-control input-lg m-bot15" id="resultado" type="text" required name="resultado" readonly onkeyup="Suma()">
                    </td>
                </tr>
            </table>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button name="registrar" type="submit" class="btn btn-success">Aceptar</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var form = document.forms.calculadora;
        if (!form || !form.registrar) {
            return;
        }

        form.registrar.disabled = true;

        window.myFunctionSearch = function () {
            var clientSearch = document.getElementById('searchNit').value;
            var resultContainer = document.getElementById('myDiv');

            if (clientSearch === '') {
                resultContainer.innerHTML = '';
                return;
            }

            loadDoc('nitClient=' + clientSearch, 'SearchContact.php', function () {
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
                    resultContainer.innerHTML = xmlhttp.responseText;
                }
            });
        };

        window.Suma = function () {
            var ingreso1 = form.ingreso1.value;
            var ingreso2 = form.ingreso2.value;

            ingreso1 = isNaN(parseFloat(ingreso1)) ? 0 : parseFloat(ingreso1);
            ingreso2 = isNaN(parseFloat(ingreso2)) ? 0 : parseFloat(ingreso2);
            form.resultado.value = ingreso2 - ingreso1;
            form.registrar.disabled = ingreso2 === 0 || form.resultado.value < 0;
        };
    })();
</script>
