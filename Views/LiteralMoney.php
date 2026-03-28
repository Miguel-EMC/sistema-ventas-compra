<?php

class EnLetras
{
    var $Void = "";
    var $SP = " ";
    var $Dot = ".";
    var $Zero = "0";
    var $Neg = "Menos";

    function ValorEnLetras($x, $Moneda)
    {
        $s = "";
        $Ent = "";
        $Frc = "";
        $Signo = "";

        // Verificar si el valor es negativo
        $Signo = floatVal($x) < 0 ? $this->Neg . " " : "";

        // Redondear el valor a 2 decimales para evitar errores en la comparación
        $formattedX = round($x, 2);
        $s = number_format($formattedX, 2, '.', '');

        // Encontrar la posición del punto decimal
        $Pto = strpos($s, $this->Dot);

        if ($Pto === false) {
            $Ent = $s;
            $Frc = $this->Void;
        } else {
            $Ent = substr($s, 0, $Pto);
            $Frc = substr($s, $Pto + 1);
        }

        // Convertir la parte entera a letras
        if ($Ent == $this->Zero || $Ent == $this->Void) {
            $s = "Cero ";
        } elseif (strlen($Ent) > 7) {
            $s = $this->SubValLetra(intval(substr($Ent, 0, strlen($Ent) - 6))) .
                "Millones " . $this->SubValLetra(intval(substr($Ent, -6, 6)));
        } else {
            $s = $this->SubValLetra(intval($Ent));
        }

        if (substr($s, -9, 9) == "Millones " || substr($s, -7, 7) == "Millón ")
            $s = $s . "de ";

        // Añadir el nombre de la moneda
        $s = $s . $Moneda;

        // Convertir la fracción a letras si existe
        if ($Frc != $this->Void) {
            $s = $s . " " . $Frc . "/100";
        }

        return $Signo . $s . " ";
    }

    function SubValLetra($numero)
    {
        $Ptr = "";
        $n = 0;
        $i = 0;
        $x = "";
        $Rtn = "";
        $Tem = "";

        $x = trim("$numero");
        $n = strlen($x);

        $Tem = $this->Void;
        $i = $n;

        while ($i > 0) {
            $Tem = $this->Parte(intval(substr($x, $n - $i, 1) .
                str_repeat($this->Zero, $i - 1)));
            if ($Tem != "Cero")
                $Rtn .= $Tem . $this->SP;
            $i = $i - 1;
        }

        // Filtrar "Mil Mil" para corregir redundancias
        $Rtn = str_replace(" Mil Mil", " Un Mil", $Rtn);
        while (1) {
            $Ptr = strpos($Rtn, "Mil ");
            if (!($Ptr === false)) {
                if (!(strpos($Rtn, "Mil ", $Ptr + 1) === false))
                    $this->ReplaceStringFrom($Rtn, "Mil ", "", $Ptr);
                else
                    break;
            } else break;
        }

        // Filtrar "Cien" a "Ciento" si es necesario
        $Ptr = -1;
        do {
            $Ptr = strpos($Rtn, "Cien ", $Ptr + 1);
            if (!($Ptr === false)) {
                $Tem = substr($Rtn, $Ptr + 5, 1);
                if ($Tem != "M" && $Tem != $this->Void)
                    $this->ReplaceStringFrom($Rtn, "Cien", "Ciento", $Ptr);
            }
        } while (!($Ptr === false));

        // Manejar especiales (Once, Doce, etc.)
        $Rtn = str_replace("Diez Un", "Once", $Rtn);
        $Rtn = str_replace("Diez Dos", "Doce", $Rtn);
        $Rtn = str_replace("Diez Tres", "Trece", $Rtn);
        $Rtn = str_replace("Diez Cuatro", "Catorce", $Rtn);
        $Rtn = str_replace("Diez Cinco", "Quince", $Rtn);
        $Rtn = str_replace("Diez Seis", "Dieciséis", $Rtn);
        $Rtn = str_replace("Diez Siete", "Diecisiete", $Rtn);
        $Rtn = str_replace("Diez Ocho", "Dieciocho", $Rtn);
        $Rtn = str_replace("Diez Nueve", "Diecinueve", $Rtn);
        $Rtn = str_replace("Veinte Un", "Veintiún", $Rtn);
        $Rtn = str_replace("Veinte Dos", "Veintidós", $Rtn);
        $Rtn = str_replace("Veinte Tres", "Veintitrés", $Rtn);
        $Rtn = str_replace("Veinte Cuatro", "Veinticuatro", $Rtn);
        $Rtn = str_replace("Veinte Cinco", "Veinticinco", $Rtn);
        $Rtn = str_replace("Veinte Seis", "Veintiséis", $Rtn);
        $Rtn = str_replace("Veinte Siete", "Veintisiete", $Rtn);
        $Rtn = str_replace("Veinte Ocho", "Veintiocho", $Rtn);
        $Rtn = str_replace("Veinte Nueve", "Veintinueve", $Rtn);

        // Agregar "Un" si es necesario
        if (substr($Rtn, 0, 1) == "M") $Rtn = "Un " . $Rtn;

        // Adicionar "y" en números compuestos
        for ($i = 65; $i <= 88; $i++) {
            if ($i != 77)
                $Rtn = str_replace("a " . Chr($i), "* y " . Chr($i), $Rtn);
        }
        $Rtn = str_replace("*", "a", $Rtn);

        return $Rtn;
    }

    function ReplaceStringFrom(&$x, $OldWrd, $NewWrd, $Ptr)
    {
        $x = substr($x, 0, $Ptr) . $NewWrd . substr($x, strlen($OldWrd) + $Ptr);
    }

    function Parte($x)
    {
        $Rtn = '';
        switch ($x) {
            case 0: $Rtn = "Cero"; break;
            case 1: $Rtn = "Un"; break;
            case 2: $Rtn = "Dos"; break;
            case 3: $Rtn = "Tres"; break;
            case 4: $Rtn = "Cuatro"; break;
            case 5: $Rtn = "Cinco"; break;
            case 6: $Rtn = "Seis"; break;
            case 7: $Rtn = "Siete"; break;
            case 8: $Rtn = "Ocho"; break;
            case 9: $Rtn = "Nueve"; break;
            case 10: $Rtn = "Diez"; break;
            case 20: $Rtn = "Veinte"; break;
            case 30: $Rtn = "Treinta"; break;
            case 40: $Rtn = "Cuarenta"; break;
            case 50: $Rtn = "Cincuenta"; break;
            case 60: $Rtn = "Sesenta"; break;
            case 70: $Rtn = "Setenta"; break;
            case 80: $Rtn = "Ochenta"; break;
            case 90: $Rtn = "Noventa"; break;
            case 100: $Rtn = "Cien"; break;
            case 200: $Rtn = "Doscientos"; break;
            case 300: $Rtn = "Trescientos"; break;
            case 400: $Rtn = "Cuatrocientos"; break;
            case 500: $Rtn = "Quinientos"; break;
            case 600: $Rtn = "Seiscientos"; break;
            case 700: $Rtn = "Setecientos"; break;
            case 800: $Rtn = "Ochocientos"; break;
            case 900: $Rtn = "Novecientos"; break;
            case 1000: $Rtn = "Mil"; break;
            case 1000000: $Rtn = "Millón"; break;
        }

        // Añadir palabras según el valor del número
        switch ((int)($x / 1000)) {
            case 1: $Rtn = "Mil"; break;
            case 2: $Rtn = "Dos Mil"; break;
        }

        return $Rtn;
    }
}
?>
