<?php
/**
 * KaamMitra - Partner Logout
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

logoutPartner();
$base = getAppBasePath();
header("Location: " . $base . "partner/login.php?msg=logged_out");
exit;
