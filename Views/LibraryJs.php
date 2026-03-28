<?php
$coreScripts = [
    'js/jquery.js',
    'js/jquery.dataTables.min.js',
    'js/ajax.js',
    'js/ajaxPos.js',
    'js/print/jquery.printPage.js',
    'js/app.js',
];

$pageScripts = [];
if (isset($extraScripts) && is_array($extraScripts)) {
    $pageScripts = array_values(array_filter($extraScripts, static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
}

$allScripts = array_merge($coreScripts, $pageScripts);
?>
<!-- javascripts -->
<?php foreach ($allScripts as $script): ?>
    <script src="<?php echo htmlspecialchars($urlViews . ltrim($script, '/'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>

<?php if (isset($pageInlineScripts) && is_array($pageInlineScripts)): ?>
    <?php foreach ($pageInlineScripts as $inlineScript): ?>
        <?php if (is_string($inlineScript) && trim($inlineScript) !== ''): ?>
            <script><?php echo $inlineScript; ?></script>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

