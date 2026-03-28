<aside>
    <div id="sidebar" class="nav-collapse">
        <ul class="sidebar-menu" style="background-color: #4e4e4e; ">
        <?php
        foreach ($menuMain as $menu) {
            echo "<li class='' style='background-color:".$menu['color'].";'>";
            echo "<a class='' href=\"" . htmlspecialchars(legacy_strip_auth_from_url($menu['location']), ENT_QUOTES, 'UTF-8') . "\">";
            echo "<i class=" . $menu['icon'] . "></i>";
            echo "<span><span #fff style=\"color: ; \"'>".$menu['opcion']."</span></span>";
            echo "</a>";
            echo "</li>";
    
        }
        ?>
        </ul>




    </div>


</aside>
