<?php
/**
 * KaamMitra - Admin Authentication, RBAC, and Audit Logging Engine
 *
 * Implements strict role-based access control (super_admin vs admin),
 * rate limiting, server-side session hardening, fresh DB role resolution,
 * and comprehensive immutable audit logging.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php'; // For initSecureSession, CSRF helpers

/* ==========================================================================
   RATE LIMITING & BRUTE FORCE PROTECTION
   ========================================================================== */

/**
 * Check if the given IP address or username is temporarily locked out
 * Threshold: 5 failed attempts in 15 minutes = 15-minute lockout.
 *
 * @param string $ip
 * @param string $username
 * @return array ['locked' => bool, 'minutes_left' => int]
 */
function checkAdminRateLimit(string $ip, string $username): array {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['locked' => false, 'minutes_left' => 0];
    }

    try {
        $windowMinutes = 15;
        $maxUserAttempts = 5;
        $maxIpAttempts = 25;

        // Check failed attempts for this specific username
        $userStmt = $pdo->prepare("
            SELECT COUNT(*), MIN(`attempt_time`) FROM `admin_login_attempts`
            WHERE LOWER(`username`) = LOWER(:username)
              AND `is_success` = 0
              AND `attempt_time` > DATE_SUB(NOW(), INTERVAL :window MINUTE)
        ");
        $userStmt->bindValue(':username', $username);
        $userStmt->bindValue(':window', $windowMinutes, PDO::PARAM_INT);
        $userStmt->execute();
        $userRow = $userStmt->fetch(PDO::FETCH_NUM);
        $userFailedCount = (int)($userRow[0] ?? 0);
        $userEarliest = $userRow[1] ?? null;

        // Check failed attempts from this IP
        $ipStmt = $pdo->prepare("
            SELECT COUNT(*), MIN(`attempt_time`) FROM `admin_login_attempts`
            WHERE `ip_address` = :ip
              AND `is_success` = 0
              AND `attempt_time` > DATE_SUB(NOW(), INTERVAL :window MINUTE)
        ");
        $ipStmt->bindValue(':ip', $ip);
        $ipStmt->bindValue(':window', $windowMinutes, PDO::PARAM_INT);
        $ipStmt->execute();
        $ipRow = $ipStmt->fetch(PDO::FETCH_NUM);
        $ipFailedCount = (int)($ipRow[0] ?? 0);
        $ipEarliest = $ipRow[1] ?? null;

        if ($userFailedCount >= $maxUserAttempts || $ipFailedCount >= $maxIpAttempts) {
            $earliest = ($userFailedCount >= $maxUserAttempts) ? $userEarliest : $ipEarliest;
            $minutesLeft = 15;
            if ($earliest) {
                $elapsed = (time() - strtotime($earliest)) / 60;
                $minutesLeft = max(1, (int)ceil($windowMinutes - $elapsed));
            }
            return ['locked' => true, 'minutes_left' => $minutesLeft];
        }

        return ['locked' => false, 'minutes_left' => 0];
    } catch (Throwable $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        return ['locked' => false, 'minutes_left' => 0];
    }
}

/**
 * Record a login attempt
 */
function recordAdminLoginAttempt(string $ip, string $username, bool $isSuccess): void {
    $pdo = getDBConnection();
    if (!$pdo) return;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO `admin_login_attempts` (`ip_address`, `username`, `attempt_time`, `is_success`)
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([$ip, $username, $isSuccess ? 1 : 0]);

        // Clean up attempts older than 24 hours to prevent table bloat
        if (mt_rand(1, 20) === 1) {
            $pdo->exec("DELETE FROM `admin_login_attempts` WHERE `attempt_time` < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        }
    } catch (Throwable $e) {
        error_log("Failed to record login attempt: " . $e->getMessage());
    }
}

/* ==========================================================================
   AUDIT LOGGING
   ========================================================================== */

/**
 * Log an administrative event in the immutable audit log
 * Never logs passwords, password hashes, or session IDs.
 *
 * @param int|null $adminId ID of acting admin (or null if unauthenticated attempt)
 * @param string $action Standard action key (e.g. 'AUTH_LOGIN_SUCCESS', 'ROLE_CHANGED')
 * @param string|null $targetType Entity type (e.g. 'admin', 'provider', 'customer', 'booking', 'security')
 * @param string|null $targetId Affected record ID
 * @param string|null $details Context or details description
 * @return bool
 */
function logAdminAudit(?int $adminId, string $action, ?string $targetType = null, ?string $targetId = null, ?string $details = null): bool {
    $pdo = getDBConnection();
    if (!$pdo) return false;

    try {
        $username = null;
        if ($adminId) {
            $uStmt = $pdo->prepare("SELECT `username` FROM `admins` WHERE `id` = ? LIMIT 1");
            $uStmt->execute([$adminId]);
            $username = $uStmt->fetchColumn() ?: null;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);

        $stmt = $pdo->prepare("
            INSERT INTO `admin_audit_logs` (`admin_id`, `admin_username`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `user_agent`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $adminId,
            $username,
            $action,
            $targetType,
            $targetId ? (string)$targetId : null,
            $details,
            $ip,
            $ua
        ]);
    } catch (Throwable $e) {
        error_log("Audit log failed: " . $e->getMessage());
        return false;
    }
}

/* ==========================================================================
   AUTHENTICATION & SESSION VERIFICATION
   ========================================================================== */

/**
 * Check if an administrator session is currently present
 */
function isAdminLoggedIn(): bool {
    initSecureSession();
    return !empty($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
}

/**
 * Retrieve the currently authenticated administrator record fresh from the database.
 * If the account was deactivated or the role changed in the database,
 * this function immediately detects it and invalidates stale privileges.
 *
 * @return array|null Admin record, or null if unauthenticated/deactivated
 */
function getCurrentAdmin(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }

    try {
        $adminId = (int)$_SESSION['admin_id'];
        $stmt = $pdo->prepare("
            SELECT `id`, `username`, `email`, `full_name`, `role`, `is_active`, `created_at`, `updated_at`, `last_login_at`
            FROM `admins`
            WHERE `id` = ?
            LIMIT 1
        ");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if (!$admin) {
            // Admin no longer exists in DB; clear session
            logoutAdmin();
            return null;
        }

        if ((int)$admin['is_active'] !== 1) {
            // Account deactivated; immediately terminate session
            logAdminAudit($adminId, 'DEACTIVATED_SESSION_TERMINATED', 'admin', (string)$adminId, 'Session rejected: Account is inactive');
            logoutAdmin();
            return null;
        }

        // Keep session role in sync with database authority
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_username'] = $admin['username'];

        return $admin;
    } catch (Throwable $e) {
        error_log("getCurrentAdmin error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if the currently authenticated user has the Super Admin role
 */
function isSuperAdmin(): bool {
    $admin = getCurrentAdmin();
    return $admin !== null && $admin['role'] === 'super_admin';
}

/**
 * Get current admin role
 */
function getAdminRole(): ?string {
    $admin = getCurrentAdmin();
    return $admin ? $admin['role'] : null;
}

/**
 * Authenticate administrator with username (Admin ID) and password.
 * Enforces rate limiting, password hash verification, account activation check,
 * session regeneration, and audit logging.
 *
 * @param string $username Admin ID
 * @param string $password Submitted plaintext password
 * @return array ['success' => bool, 'message' => string, 'admin' => array|null]
 */
function loginAdmin(string $username, string $password): array {
    initSecureSession();

    $username = trim($username);
    $password = trim($password);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if (empty($username) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Please provide both your Admin ID and password.'
        ];
    }

    // 1. Rate Limiting Check
    $rateLimit = checkAdminRateLimit($ip, $username);
    if ($rateLimit['locked']) {
        logAdminAudit(null, 'AUTH_RATE_LIMIT_BLOCKED', 'security', $username, "Sign-in blocked for {$username} from {$ip}. Lockout active for {$rateLimit['minutes_left']}m.");
        return [
            'success' => false,
            'message' => "Too many failed sign-in attempts. Account access is temporarily locked for {$rateLimit['minutes_left']} minute(s) for security."
        ];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return [
            'success' => false,
            'message' => 'Administrative database service is currently unavailable. Please try again shortly.'
        ];
    }

    try {
        // 2. Fetch admin record by username using prepared statement
        $stmt = $pdo->prepare("
            SELECT `id`, `username`, `email`, `full_name`, `password_hash`, `role`, `is_active`
            FROM `admins`
            WHERE LOWER(`username`) = LOWER(?)
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // 3. Verify password
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            recordAdminLoginAttempt($ip, $username, false);
            logAdminAudit(null, 'AUTH_LOGIN_FAILED', 'admin', $username, "Failed sign-in attempt for username '{$username}' from IP {$ip}");
            return [
                'success' => false,
                'message' => 'Invalid Admin ID or password. Please verify your credentials.'
            ];
        }

        // 4. Check account status
        if ((int)$admin['is_active'] !== 1) {
            recordAdminLoginAttempt($ip, $username, false);
            logAdminAudit((int)$admin['id'], 'AUTH_LOGIN_DEACTIVATED', 'admin', (string)$admin['id'], "Deactivated administrator '{$username}' attempted login.");
            return [
                'success' => false,
                'message' => 'This administrator account has been deactivated. Please contact your Super Administrator.'
            ];
        }

        // 5. Password rehash if needed
        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $rehashStmt = $pdo->prepare("UPDATE `admins` SET `password_hash` = ? WHERE `id` = ?");
            $rehashStmt->execute([$newHash, $admin['id']]);
        }

        // 6. Record successful attempt & clear failure counts
        recordAdminLoginAttempt($ip, $username, true);
        try {
            $pdo->prepare("DELETE FROM `admin_login_attempts` WHERE (LOWER(`username`) = LOWER(?) OR `ip_address` = ?) AND `is_success` = 0")->execute([$username, $ip]);
        } catch (Throwable $e) {}

        // 7. Regenerate session ID to prevent session fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_name'] = $admin['full_name'] ?: $admin['username'];
        $_SESSION['admin_logged_at'] = time();

        // 8. Update last_login_at timestamp
        $updateLoginStmt = $pdo->prepare("UPDATE `admins` SET `last_login_at` = NOW() WHERE `id` = ?");
        $updateLoginStmt->execute([$admin['id']]);

        // Also update last_login column if it exists for backwards compatibility
        try {
            $pdo->prepare("UPDATE `admins` SET `last_login` = NOW() WHERE `id` = ?")->execute([$admin['id']]);
        } catch (Throwable $e) {}

        // 9. Audit log
        logAdminAudit((int)$admin['id'], 'AUTH_LOGIN_SUCCESS', 'admin', (string)$admin['id'], "Successful login by '{$admin['username']}' ({$admin['role']}) from {$ip}");

        return [
            'success' => true,
            'message' => 'Authentication successful! Redirecting to Administrative Dashboard...',
            'admin' => $admin
        ];
    } catch (Throwable $e) {
        error_log("Admin login error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'A secure server error occurred during authentication. Please try again.'
        ];
    }
}

/**
 * Invalidate administrator session and remove cookies securely
 */
function logoutAdmin(): void {
    initSecureSession();

    if (!empty($_SESSION['admin_id'])) {
        $adminId = (int)$_SESSION['admin_id'];
        logAdminAudit($adminId, 'AUTH_LOGOUT', 'admin', (string)$adminId, "Administrator signed out cleanly.");
    }

    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_username'],
        $_SESSION['admin_role'],
        $_SESSION['admin_name'],
        $_SESSION['admin_logged_at']
    );

    // Optionally destroy session if only admin was using it
    if (empty($_SESSION['customer_id']) && empty($_SESSION['partner_id'])) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }
}

/* ==========================================================================
   BACKEND AUTHORIZATION GUARDS / MIDDLEWARE
   ========================================================================== */

/**
 * Require valid Admin authentication.
 * Redirects unauthenticated requests to the admin login page with destination preserved.
 *
 * @param string|null $loginPath Custom path to login if outside standard structure
 * @return array Authenticated admin record
 */
function requireAdmin(?string $loginPath = null): array {
    $admin = getCurrentAdmin();
    if (!$admin) {
        $defaultLogin = (function_exists('getAppBasePath') ? getAppBasePath() : './') . 'auth/admin/login.php';
        $loginUrl = $loginPath ?: $defaultLogin;
        
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        if ($currentUrl && !str_contains($currentUrl, 'login.php')) {
            $delimiter = str_contains($loginUrl, '?') ? '&' : '?';
            $loginUrl .= $delimiter . 'redirect=' . urlencode($currentUrl);
        }
        
        header("Location: " . $loginUrl);
        exit;
    }
    return $admin;
}

/**
 * Require Super Admin role.
 * Rejects standard Admin accounts with HTTP 403 Forbidden.
 *
 * @return array Authenticated super admin record
 */
function requireSuperAdmin(): array {
    $admin = requireAdmin();
    if ($admin['role'] !== 'super_admin') {
        logAdminAudit(
            (int)$admin['id'],
            'UNAUTHORIZED_SUPERADMIN_ACCESS_ATTEMPT',
            'security',
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            "Admin '{$admin['username']}' attempted to access restricted Super Admin endpoint"
        );

        http_response_code(403);
        renderForbiddenError("Access Restricted: Super Administrator Privileges Required", "You are signed in as an operational Administrator ('{$admin['username']}'). This endpoint and action require authorized Super Admin role clearance.");
        exit;
    }
    return $admin;
}

/**
 * Check if the active administrator possesses a specific capability
 *
 * @param string $permission
 * @param array|null $admin
 * @return bool
 */
function hasAdminPermission(string $permission, ?array $admin = null): bool {
    if ($admin === null) {
        $admin = getCurrentAdmin();
    }
    if (!$admin) return false;

    // Super Admin has all administrative privileges
    if ($admin['role'] === 'super_admin') {
        return true;
    }

    // Role: admin (Restricted operational permissions)
    $adminAllowedPermissions = [
        'view_customers',
        'update_customer',
        'view_providers',
        'review_providers',
        'approve_provider',
        'reject_provider',
        'view_bookings',
        'manage_bookings',
        'view_reports'
    ];

    return in_array($permission, $adminAllowedPermissions, true);
}

/**
 * Render clean, secure HTTP 403 Forbidden page
 */
function renderForbiddenError(string $title, string $message): void {
    $base = function_exists('getAppBasePath') ? getAppBasePath() : '../';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - <?= htmlspecialchars($title); ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
        <style>
            body {
                margin: 0;
                padding: 0;
                background: #090D16;
                color: #FFFFFF;
                font-family: 'Plus Jakarta Sans', sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .error-card {
                background: #141A28;
                border: 1px solid rgba(239, 68, 68, 0.4);
                border-radius: 16px;
                padding: 2.5rem;
                max-width: 520px;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            }
            .shield-icon {
                width: 64px;
                height: 64px;
                background: rgba(239, 68, 68, 0.15);
                color: #EF4444;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1.5rem;
                font-size: 28px;
            }
            h1 { font-size: 1.5rem; font-weight: 800; margin: 0 0 1rem; color: #FFFFFF; }
            p { color: #94A3B8; font-size: 0.95rem; line-height: 1.6; margin: 0 0 2rem; }
            .btn-back {
                display: inline-block;
                background: #FF5500;
                color: #FFFFFF;
                font-weight: 700;
                padding: 0.75rem 1.75rem;
                border-radius: 8px;
                text-decoration: none;
                transition: background 0.2s;
            }
            .btn-back:hover { background: #E04B00; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="shield-icon">🛡️</div>
            <h1><?= htmlspecialchars($title); ?></h1>
            <p><?= htmlspecialchars($message); ?></p>
            <a href="index.php" class="btn-back">Return to Authorized Dashboard</a>
        </div>
    </body>
    </html>
    <?php
}

/* ==========================================================================
   ADMINISTRATOR ACCOUNT MANAGEMENT (SUPER ADMIN PRIVILEGES)
   ========================================================================== */

/**
 * Create a new administrator account (Super Admin only).
 * Default role is 'admin'. Only an existing Super Admin can grant 'super_admin'.
 *
 * @param array $data ['username', 'email', 'full_name', 'password', 'role']
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string, 'admin_id' => int|null]
 */
function createAdminAccount(array $data, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin || $actingAdmin['role'] !== 'super_admin') {
        return ['success' => false, 'message' => 'Unauthorized: Only Super Administrators can create administrative accounts.'];
    }

    $username = trim($data['username'] ?? '');
    $email    = trim($data['email'] ?? '');
    $fullName = trim($data['full_name'] ?? '');
    $password = $data['password'] ?? '';
    $role     = trim($data['role'] ?? 'admin');

    // Validation
    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        return ['success' => false, 'message' => 'Admin ID must be at least 3 characters and contain only letters, numbers, hyphens, and underscores.'];
    }

    if (empty($password) || strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long for administrative accounts.'];
    }

    if (!in_array($role, ['admin', 'super_admin'], true)) {
        $role = 'admin'; // Safe default
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        // Check duplicate username
        $chkStmt = $pdo->prepare("SELECT `id` FROM `admins` WHERE LOWER(`username`) = LOWER(?) LIMIT 1");
        $chkStmt->execute([$username]);
        if ($chkStmt->fetch()) {
            return ['success' => false, 'message' => "The Admin ID '{$username}' is already taken. Please choose another."];
        }

        // Check duplicate email if provided
        if (!empty($email)) {
            $chkEmail = $pdo->prepare("SELECT `id` FROM `admins` WHERE LOWER(`email`) = LOWER(?) LIMIT 1");
            $chkEmail->execute([$email]);
            if ($chkEmail->fetch()) {
                return ['success' => false, 'message' => "The email '{$email}' is already associated with an existing administrator."];
            }
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO `admins` (`username`, `email`, `full_name`, `password_hash`, `role`, `is_active`, `created_at`)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $username,
            !empty($email) ? $email : null,
            !empty($fullName) ? $fullName : $username,
            $passwordHash,
            $role
        ]);

        $newAdminId = (int)$pdo->lastInsertId();

        logAdminAudit(
            $actingAdminId,
            'ADMIN_ACCOUNT_CREATED',
            'admin',
            (string)$newAdminId,
            "Created administrator account '{$username}' with role '{$role}'"
        );

        return [
            'success' => true,
            'message' => "Administrator account '{$username}' ({$role}) successfully created.",
            'admin_id' => $newAdminId
        ];
    } catch (Throwable $e) {
        error_log("Failed to create admin: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred while creating the administrator account.'];
    }
}

/**
 * Activate or Deactivate an administrator account.
 * Guard: Prevents locking out the system's last active Super Admin.
 *
 * @param int $targetAdminId
 * @param int $newStatus 1 for Active, 0 for Deactivated
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string]
 */
function updateAdminStatus(int $targetAdminId, int $newStatus, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin || $actingAdmin['role'] !== 'super_admin') {
        return ['success' => false, 'message' => 'Unauthorized: Only Super Administrators can change account activation status.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `id`, `username`, `role`, `is_active` FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$targetAdminId]);
        $target = $stmt->fetch();

        if (!$target) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        // Prevent deactivating the last active Super Admin
        if ($newStatus === 0 && $target['role'] === 'super_admin') {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `admins` WHERE `role` = 'super_admin' AND `is_active` = 1");
            $activeSuperCount = (int)$countStmt->fetchColumn();
            if ($activeSuperCount <= 1) {
                return ['success' => false, 'message' => 'Action Blocked: Cannot deactivate the platform\'s only active Super Administrator.'];
            }
        }

        $upd = $pdo->prepare("UPDATE `admins` SET `is_active` = ? WHERE `id` = ?");
        $upd->execute([$newStatus ? 1 : 0, $targetAdminId]);

        $statusLabel = $newStatus ? 'Active' : 'Deactivated';
        logAdminAudit(
            $actingAdminId,
            $newStatus ? 'ADMIN_ACCOUNT_ACTIVATED' : 'ADMIN_ACCOUNT_DEACTIVATED',
            'admin',
            (string)$targetAdminId,
            "Administrator '{$target['username']}' status set to {$statusLabel}"
        );

        return [
            'success' => true,
            'message' => "Administrator '{$target['username']}' has been {$statusLabel}."
        ];
    } catch (Throwable $e) {
        error_log("updateAdminStatus error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update administrator status.'];
    }
}

/**
 * Assign or change an administrator's role (Super Admin only).
 * Guard: Prevents demoting the last active Super Admin, and prevents standard Admin from elevating privileges.
 *
 * @param int $targetAdminId
 * @param string $newRole 'super_admin' or 'admin'
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string]
 */
function updateAdminRole(int $targetAdminId, string $newRole, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin || $actingAdmin['role'] !== 'super_admin') {
        return ['success' => false, 'message' => 'Unauthorized: Only Super Administrators can assign or change roles.'];
    }

    if (!in_array($newRole, ['super_admin', 'admin'], true)) {
        return ['success' => false, 'message' => 'Invalid role specified. Supported roles: super_admin, admin.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `id`, `username`, `role`, `is_active` FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$targetAdminId]);
        $target = $stmt->fetch();

        if (!$target) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        // Prevent demoting the last active Super Admin
        if ($target['role'] === 'super_admin' && $newRole === 'admin') {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `admins` WHERE `role` = 'super_admin' AND `is_active` = 1");
            $activeSuperCount = (int)$countStmt->fetchColumn();
            if ($activeSuperCount <= 1) {
                return ['success' => false, 'message' => 'Action Blocked: Cannot demote the platform\'s last active Super Administrator.'];
            }
        }

        $upd = $pdo->prepare("UPDATE `admins` SET `role` = ? WHERE `id` = ?");
        $upd->execute([$newRole, $targetAdminId]);

        logAdminAudit(
            $actingAdminId,
            'ADMIN_ROLE_CHANGED',
            'admin',
            (string)$targetAdminId,
            "Role for '{$target['username']}' updated from '{$target['role']}' to '{$newRole}'"
        );

        return [
            'success' => true,
            'message' => "Role for '{$target['username']}' updated to {$newRole}."
        ];
    } catch (Throwable $e) {
        error_log("updateAdminRole error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update administrator role.'];
    }
}

/**
 * Reset an administrator password through a secure, authorized process (Super Admin only).
 *
 * @param int $targetAdminId
 * @param string $newPassword
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string]
 */
function resetAdminPassword(int $targetAdminId, string $newPassword, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin || $actingAdmin['role'] !== 'super_admin') {
        return ['success' => false, 'message' => 'Unauthorized: Only Super Administrators can reset passwords.'];
    }

    if (empty($newPassword) || strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `username` FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$targetAdminId]);
        $username = $stmt->fetchColumn();

        if (!$username) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE `admins` SET `password_hash` = ? WHERE `id` = ?");
        $upd->execute([$hash, $targetAdminId]);

        logAdminAudit(
            $actingAdminId,
            'ADMIN_PASSWORD_RESET',
            'admin',
            (string)$targetAdminId,
            "Password securely reset for administrator '{$username}'"
        );

        return [
            'success' => true,
            'message' => "Password for '{$username}' has been successfully reset."
        ];
    } catch (Throwable $e) {
        error_log("resetAdminPassword error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reset password.'];
    }
}

/* ==========================================================================
   OPERATIONAL ENTITY MANAGEMENT (PROVIDERS, BOOKINGS, CUSTOMERS)
   ========================================================================== */

/**
 * Update service provider verification status (Approve, Reject, Pending)
 */
function updateProviderVerification(int $providerId, string $status, ?string $rejectionReason, int $actingAdminId): array {
    $admin = getCurrentAdmin();
    if (!$admin) {
        return ['success' => false, 'message' => 'Unauthorized request.'];
    }

    if (!hasAdminPermission('review_providers', $admin)) {
        return ['success' => false, 'message' => 'Permission denied: Cannot review provider applications.'];
    }

    if (!in_array($status, ['Approved', 'Rejected', 'Pending'], true)) {
        return ['success' => false, 'message' => 'Invalid provider verification status.'];
    }

    if ($status === 'Rejected' && empty(trim($rejectionReason ?? ''))) {
        return ['success' => false, 'message' => 'A rejection reason is required when rejecting a service provider.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $provStmt = $pdo->prepare("SELECT `full_name`, `verification_status` FROM `service_providers` WHERE `id` = ? LIMIT 1");
        $provStmt->execute([$providerId]);
        $provider = $provStmt->fetch();

        if (!$provider) {
            return ['success' => false, 'message' => 'Service provider record not found.'];
        }

        $upd = $pdo->prepare("
            UPDATE `service_providers`
            SET `verification_status` = ?, `rejection_reason` = ?
            WHERE `id` = ?
        ");
        $upd->execute([
            $status,
            $status === 'Rejected' ? trim($rejectionReason) : null,
            $providerId
        ]);

        logAdminAudit(
            $actingAdminId,
            'PROVIDER_VERIFICATION_' . strtoupper($status),
            'provider',
            (string)$providerId,
            "Provider '{$provider['full_name']}' verification changed from {$provider['verification_status']} to {$status}." . ($status === 'Rejected' ? " Reason: {$rejectionReason}" : "")
        );

        return [
            'success' => true,
            'message' => "Service provider '{$provider['full_name']}' has been updated to {$status}."
        ];
    } catch (Throwable $e) {
        error_log("updateProviderVerification error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error updating provider status.'];
    }
}

/**
 * Update booking status
 */
function updateBookingStatus(int $bookingId, string $status, int $actingAdminId): array {
    $admin = getCurrentAdmin();
    if (!$admin) {
        return ['success' => false, 'message' => 'Unauthorized request.'];
    }

    if (!hasAdminPermission('manage_bookings', $admin)) {
        return ['success' => false, 'message' => 'Permission denied: Cannot manage bookings.'];
    }

    $validStatuses = ['Confirmed', 'In Progress', 'Completed', 'Cancelled'];
    if (!in_array($status, $validStatuses, true)) {
        return ['success' => false, 'message' => 'Invalid booking status.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `booking_reference`, `status` FROM `bookings` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['success' => false, 'message' => 'Booking record not found.'];
        }

        $upd = $pdo->prepare("UPDATE `bookings` SET `status` = ? WHERE `id` = ?");
        $upd->execute([$status, $bookingId]);

        logAdminAudit(
            $actingAdminId,
            'BOOKING_STATUS_UPDATED',
            'booking',
            (string)$bookingId,
            "Booking {$booking['booking_reference']} status changed from {$booking['status']} to {$status}"
        );

        return [
            'success' => true,
            'message' => "Booking {$booking['booking_reference']} updated to {$status}."
        ];
    } catch (Throwable $e) {
        error_log("updateBookingStatus error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error updating booking status.'];
    }
}

/**
 * Update customer status (Active / Suspended)
 */
function updateCustomerStatus(int $customerId, string $status, int $actingAdminId): array {
    $admin = getCurrentAdmin();
    if (!$admin) {
        return ['success' => false, 'message' => 'Unauthorized request.'];
    }

    if (!hasAdminPermission('update_customer', $admin)) {
        return ['success' => false, 'message' => 'Permission denied: Cannot update customer records.'];
    }

    if (!in_array($status, ['Active', 'Suspended'], true)) {
        return ['success' => false, 'message' => 'Invalid customer status. Supported: Active, Suspended.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `full_name`, `status` FROM `customers` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return ['success' => false, 'message' => 'Customer record not found.'];
        }

        $upd = $pdo->prepare("UPDATE `customers` SET `status` = ? WHERE `id` = ?");
        $upd->execute([$status, $customerId]);

        logAdminAudit(
            $actingAdminId,
            'CUSTOMER_STATUS_UPDATED',
            'customer',
            (string)$customerId,
            "Customer '{$customer['full_name']}' status changed to {$status}"
        );

        return [
            'success' => true,
            'message' => "Customer '{$customer['full_name']}' is now {$status}."
        ];
    } catch (Throwable $e) {
        error_log("updateCustomerStatus error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error updating customer status.'];
    }
}
