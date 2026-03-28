<?php
$settingsUrl = ($tipo ?? '') === 'VENTAS' ? 'principal.php' : 'Usuario.php';
?>
<ul class="dropdown-menu extended logout app-user-dropdown">
    <li class="eborder-top">
        <a href="<?php echo htmlspecialchars($settingsUrl, ENT_QUOTES, 'UTF-8'); ?>"><i class="icon_tools"></i> Configuracion</a>
    </li>
    <li>
        <a href="ReportesVentas.php"><i class="icon_datareport"></i> Reportes</a>
    </li>
    <li>
        <a href="Cliente.php"><i class="icon_contacts_alt"></i> Clientes</a>
    </li>
    <li>
        <a href="<?= htmlspecialchars(app_url('/Controller/Logout.php'), ENT_QUOTES, 'UTF-8'); ?>"><i class="icon_key_alt"></i> Salir</a>
    </li>
</ul>
