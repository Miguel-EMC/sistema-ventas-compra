<?php
$resolvedPageTitle = 'iCONT - Sistema Profesional Para Empresas';
if (isset($pageTitle) && is_string($pageTitle) && trim($pageTitle) !== '') {
    $resolvedPageTitle = trim($pageTitle) . ' | ' . strtoupper((string) config('app.name', 'ventaspos'));
}

$coreStyles = [
    'css/font-awesome.min.css',
    'css/elegant-icons-style.css',
    'css/app.css',
];

$pageStyles = [];
if (isset($extraStyles) && is_array($extraStyles)) {
    $pageStyles = array_values(array_filter($extraStyles, static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
}

$allStyles = array_merge($coreStyles, $pageStyles);
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema para el Menejo de Empresas - contabilidad , ventas , ingresos , egresos">
    <meta name="author" content="GeeksLabs">
    <meta name="keyword" content="Sistema modo tactil , para escritorio tabletas ">
    <meta name="theme-color" content="#0f4c5c">
    <link rel="shortcut icon" href="<?php echo $urlViews; ?>img/logoI.png">

    <title><?php echo htmlspecialchars($resolvedPageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 -->
    <!--[if lt IE 9]>
    <script src="<?php echo $urlViews; ?>js/html5shiv.js"></script>
    <script src="<?php echo $urlViews; ?>js/respond.min.js"></script>
    <script src="<?php echo $urlViews; ?>js/lte-ie7.js"></script>
    <![endif]-->
    <?php foreach ($allStyles as $style): ?>
        <link href="<?php echo htmlspecialchars($urlViews . ltrim($style, '/'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php endforeach; ?>

    <!-- letra style -->
    <!-- <link rel="stylesheet" href="css/letra.css">-->

</head>
