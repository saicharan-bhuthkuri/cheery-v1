<?php
/**
 * KaamMitra - Customer Logout Handler
 */

require_once __DIR__ . '/../includes/auth.php';

logoutCustomer();

header("Location: " . getAppBasePath() . "index.php?msg=logged_out");
exit;
