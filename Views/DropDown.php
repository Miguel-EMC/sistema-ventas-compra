<?php
$displayName = trim((string) ($userLogueado ?? 'Usuario'));
$avatarInitial = strtoupper(function_exists('mb_substr') ? mb_substr($displayName, 0, 1) : substr($displayName, 0, 1));
if ($avatarInitial === '') {
    $avatarInitial = 'U';
}
?>
<div class="top-nav notification-row">
    <ul class="nav pull-right top-menu">
        <li class="dropdown app-user-menu">
            <a data-toggle="dropdown" class="dropdown-toggle app-user-toggle" href="#" aria-haspopup="true" aria-expanded="false">
                <span class="profile-ava app-user-avatar app-user-avatar--initial" aria-hidden="true">
                    <span><?php echo htmlspecialchars($avatarInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
                <span class="app-user-copy">
                    <span class="username"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <small><?php echo htmlspecialchars((string) ($tipo ?? 'OPERADOR'), ENT_QUOTES, 'UTF-8'); ?></small>
                </span>
                <span class="app-user-chevron"><i class="fa fa-angle-down"></i></span>
            </a>
            <?php include("MenuOpciones.php"); ?>
        </li>
        <li class="app-quick-action">
            <a class="app-quick-action__link" href="<?= htmlspecialchars(app_url('/Controller/Logout.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="icon_key_alt"></i> Salir
            </a>
        </li>
    </ul>
</div>
			
