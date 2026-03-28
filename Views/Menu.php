<?php
$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<aside>
    <div id="sidebar" class="nav-collapse app-sidebar">
        <div class="app-sidebar__header">
            <span class="app-sidebar__eyebrow">Workspace</span>
            <strong class="app-sidebar__title">Navegacion principal</strong>
        </div>
        <ul class="sidebar-menu">
        <?php
        foreach ($menuMain as $menu) {
            $location = (string) ($menu['location'] ?? '#');
            $targetScript = basename(parse_url($location, PHP_URL_PATH) ?? $location);
            $isActive = strcasecmp($currentScript, $targetScript) === 0;
            $accent = trim((string) ($menu['color'] ?? ''));
            $accentStyle = '';
            if ($accent !== '' && preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([0-9.,\s]+\)|[a-zA-Z]+)$/', $accent) === 1) {
                $accentStyle = ' style="--menu-accent:' . htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') . ';"';
            }
            echo '<li class="app-menu-item' . ($isActive ? ' is-active' : '') . '"' . $accentStyle . '>';
            echo '<a class="app-menu-link" href="' . htmlspecialchars(legacy_strip_auth_from_url($location), ENT_QUOTES, 'UTF-8') . '">';
            echo '<span class="app-menu-icon"><i class="' . htmlspecialchars((string) ($menu['icon'] ?? 'fa fa-circle'), ENT_QUOTES, 'UTF-8') . '"></i></span>';
            echo '<span class="app-menu-label">' . htmlspecialchars((string) ($menu['opcion'] ?? 'Modulo'), ENT_QUOTES, 'UTF-8') . '</span>';
            echo '<span class="app-menu-pill"></span>';
            echo '</a>';
            echo '</li>';
        }
        ?>
        </ul>
    </div>
</aside>
