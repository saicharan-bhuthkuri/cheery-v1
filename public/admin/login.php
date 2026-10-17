<?php
/**
 * KaamMitra - Admin Login Entrypoint Redirect
 */
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: ../auth/admin/login.php" . $query);
exit;
