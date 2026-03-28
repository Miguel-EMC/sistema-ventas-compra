<?php
$workspaceTitle = isset($workspaceTitle) && is_string($workspaceTitle) ? trim($workspaceTitle) : '';
$workspaceDescription = isset($workspaceDescription) && is_string($workspaceDescription) ? trim($workspaceDescription) : '';
$workspaceItems = isset($workspaceItems) && is_array($workspaceItems) ? $workspaceItems : [];
$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<?php if ($workspaceTitle !== '' && $workspaceItems !== []): ?>
    <section class="workspace-nav" aria-label="<?php echo htmlspecialchars($workspaceTitle, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="workspace-nav__header">
            <div>
                <span class="section-kicker"><?php echo htmlspecialchars($workspaceTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ($workspaceDescription !== ''): ?>
                    <p><?php echo htmlspecialchars($workspaceDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="workspace-nav__grid">
            <?php foreach ($workspaceItems as $workspaceItem): ?>
                <?php
                if (!is_array($workspaceItem)) {
                    continue;
                }

                $label = trim((string) ($workspaceItem['label'] ?? ''));
                $href = trim((string) ($workspaceItem['href'] ?? ''));
                if ($label === '' || $href === '') {
                    continue;
                }

                $icon = trim((string) ($workspaceItem['icon'] ?? 'fa fa-circle'));
                $description = trim((string) ($workspaceItem['description'] ?? ''));
                $parsedPath = parse_url($href, PHP_URL_PATH);
                $targetScript = basename(is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : $href);
                $isActive = strcasecmp($currentScript, $targetScript) === 0;
                ?>
                <a
                    class="workspace-nav__item<?php echo $isActive ? ' is-active' : ''; ?>"
                    href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"
                    <?php echo $isActive ? 'aria-current="page"' : ''; ?>
                >
                    <span class="workspace-nav__icon">
                        <i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                    </span>
                    <span class="workspace-nav__copy">
                        <strong><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php if ($description !== ''): ?>
                            <small><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
