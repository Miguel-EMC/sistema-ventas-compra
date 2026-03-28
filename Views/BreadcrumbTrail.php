<?php
$breadcrumbItems = isset($breadcrumbItems) && is_array($breadcrumbItems) ? $breadcrumbItems : [];
?>
<?php foreach ($breadcrumbItems as $breadcrumbItem): ?>
    <?php
    if (!is_array($breadcrumbItem)) {
        continue;
    }

    $label = trim((string) ($breadcrumbItem['label'] ?? ''));
    if ($label === '') {
        continue;
    }

    $href = trim((string) ($breadcrumbItem['href'] ?? ''));
    $icon = trim((string) ($breadcrumbItem['icon'] ?? ''));
    ?>
    <li>
        <?php if ($icon !== ''): ?>
            <i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
        <?php endif; ?>
        <?php if ($href !== ''): ?>
            <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php else: ?>
            <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
