<?php
/**
 * KaamMitra - Administrator Logout Handler
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

logoutAdmin();
$base = getAppBasePath();
header("Location: " . $base . "admin/index.php?msg=logged_out");
exit;
