<?php
/**
 * KaamMitra - Customer Logout Handler
 */

require_once __DIR__ . '/includes/auth.php';

logoutCustomer();

header("Location: index.php?msg=logged_out");
exit;
