<?php
/**
 * KaamMitra - Partner Logout
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

logoutPartner();
header("Location: partner-login.php?msg=logged_out");
exit;
