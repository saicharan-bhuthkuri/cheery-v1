<?php
/**
 * KaamMitra - Admin Sign Out Handler
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

logoutAdmin();
header("Location: ../auth/admin/login.php?logged_out=1");
exit;
