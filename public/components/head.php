<?php
/**
 * KaamMitra - Reusable HTML Head Component
 * Centralizes meta tags, fonts, stylesheets, and core scripts
 */

if (!function_exists('getAppBasePath')) {
    require_once __DIR__ . '/../includes/auth.php';
}
$base = getAppBasePath();
$assetPath = $assetPath ?? ($base . 'assets');
$pageTitle = $pageTitle ?? (defined('APP_NAME') ? APP_NAME : 'KaamMitra') . ' - On-Demand Professional Home Services';
$pageDescription = $pageDescription ?? 'KaamMitra delivers background-verified electricians, plumbers, appliance technicians, and cleaning specialists to your doorstep in 30 minutes.';
$pageKeywords = $pageKeywords ?? 'KaamMitra, home services, plumber, electrician, AC repair, carpentry, deep cleaning, Mumbai, Delhi';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle); ?></title>

<meta name="description" content="<?= htmlspecialchars($pageDescription); ?>">
<meta name="keywords" content="<?= htmlspecialchars($pageKeywords); ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= htmlspecialchars($assetPath); ?>/css/variables.css">
<link rel="stylesheet" href="<?= htmlspecialchars($assetPath); ?>/css/style.css">
<script src="<?= htmlspecialchars($assetPath); ?>/js/lucide.min.js"></script>
<?php if (!empty($extraCss)): ?>
    <?= $extraCss; ?>
<?php endif; ?>
<?php if (!empty($extraHead)): ?>
    <?= $extraHead; ?>
<?php endif; ?>
<?php if (!empty($extraJs)): ?>
    <?= $extraJs; ?>
<?php endif; ?>
