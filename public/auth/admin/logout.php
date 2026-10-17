<?php
/**
 * KaamMitra - Admin Auth Logout Redirect
 */
require_once dirname(__DIR__, 2) . '/includes/admin-auth.php';
logoutAdmin();
header("Location: login.php?logged_out=1");
exit;
