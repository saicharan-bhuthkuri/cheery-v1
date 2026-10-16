<?php
/**
 * KaamMitra - Customer Registration Forwarder
 * Routes directly to the Create Account tab on login.php
 */

$redirect = trim($_GET['redirect'] ?? '');
$target = 'login.php?tab=register';
if (!empty($redirect)) {
    $target .= '&redirect=' . urlencode($redirect);
}

header("Location: " . $target);
exit;
