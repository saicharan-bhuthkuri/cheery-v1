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
   ROLE DEFINITIONS & IMMUTABLE DEVELOPER CONSTANTS
   ========================================================================== */

if (!defined('ROLE_DEVELOPER')) define('ROLE_DEVELOPER', 'developer');
if (!defined('ROLE_SUPER_ADMIN')) define('ROLE_SUPER_ADMIN', 'super_admin');
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'admin');

if (!defined('DEVELOPER_SYSTEM_TAG')) define('DEVELOPER_SYSTEM_TAG', 'DEVELOPER_ROOT');
if (!defined('DEVELOPER_DEFAULT_USERNAME')) define('DEVELOPER_DEFAULT_USERNAME', 'Charan');
if (!defined('DEVELOPER_ALIAS_USERNAME')) define('DEVELOPER_ALIAS_USERNAME', 'charan_developer');

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
            SELECT `id`, `admin_code`, `username`, `email`, `full_name`, `mobile`, `role`, `is_active`, `is_protected`, `system_tag`, `created_at`, `updated_at`, `last_login_at`
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
        $_SESSION['admin_is_developer'] = ($admin['role'] === ROLE_DEVELOPER || ($admin['system_tag'] ?? '') === DEVELOPER_SYSTEM_TAG);

        return $admin;
    } catch (Throwable $e) {
        error_log("getCurrentAdmin error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if the active administrator is the system Developer (Charan).
 * Validates role and immutable system tags.
 */
function isDeveloper(?array $admin = null): bool {
    if ($admin === null) {
        $admin = getCurrentAdmin();
    }
    if (!$admin) return false;
    return ($admin['role'] === ROLE_DEVELOPER) ||
           (($admin['system_tag'] ?? '') === DEVELOPER_SYSTEM_TAG) ||
           (!empty($admin['is_protected']) && (int)$admin['is_protected'] === 1) ||
           (strtolower($admin['username'] ?? '') === strtolower(DEVELOPER_DEFAULT_USERNAME));
}

/**
 * Check if the active administrator possesses the Super Admin role.
 */
function isSuperAdmin(?array $admin = null): bool {
    if ($admin === null) {
        $admin = getCurrentAdmin();
    }
    return $admin !== null && $admin['role'] === ROLE_SUPER_ADMIN;
}

/**
 * Check if the active administrator possesses the operational Admin role.
 */
function isAdminRole(?array $admin = null): bool {
    if ($admin === null) {
        $admin = getCurrentAdmin();
    }
    return $admin !== null && $admin['role'] === ROLE_ADMIN;
}

/**
 * Check if the administrator possesses at least Super Admin clearance (Developer or Super Admin).
 */
function hasSuperAdminClearance(?array $admin = null): bool {
    if ($admin === null) {
        $admin = getCurrentAdmin();
    }
    if (!$admin) return false;
    return isDeveloper($admin) || isSuperAdmin($admin);
}

/**
 * Verify whether a given account record, ID, or username represents the protected Developer account.
 * Checked via system tag, is_protected flag, role, or username.
 *
 * @param array|int|string $account
 * @return bool
 */
function isProtectedDeveloperAccount($account): bool {
    if (is_array($account)) {
        if (($account['system_tag'] ?? '') === DEVELOPER_SYSTEM_TAG) return true;
        if (!empty($account['is_protected']) && (int)$account['is_protected'] === 1) return true;
        if (($account['role'] ?? '') === ROLE_DEVELOPER) return true;
        $uname = strtolower($account['username'] ?? '');
        if ($uname === strtolower(DEVELOPER_DEFAULT_USERNAME) || $uname === strtolower(DEVELOPER_ALIAS_USERNAME)) return true;
        return false;
    }

    $pdo = getDBConnection();
    if (!$pdo) return false;

    try {
        if (is_numeric($account)) {
            $stmt = $pdo->prepare("SELECT `id`, `username`, `role`, `is_protected`, `system_tag` FROM `admins` WHERE `id` = ? LIMIT 1");
            $stmt->execute([(int)$account]);
        } else {
            $stmt = $pdo->prepare("SELECT `id`, `username`, `role`, `is_protected`, `system_tag` FROM `admins` WHERE LOWER(`username`) = LOWER(?) OR `system_tag` = ? LIMIT 1");
            $stmt->execute([trim((string)$account), trim((string)$account)]);
        }
        $row = $stmt->fetch();
        if (!$row) return false;

        $rowUname = strtolower($row['username'] ?? '');
        return ($row['system_tag'] === DEVELOPER_SYSTEM_TAG) ||
               ((int)$row['is_protected'] === 1) ||
               ($row['role'] === ROLE_DEVELOPER) ||
               ($rowUname === strtolower(DEVELOPER_DEFAULT_USERNAME) || $rowUname === strtolower(DEVELOPER_ALIAS_USERNAME));
    } catch (Throwable $e) {
        error_log("isProtectedDeveloperAccount error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current admin role
 */
function getAdminRole(): ?string {
    $admin = getCurrentAdmin();
    return $admin ? $admin['role'] : null;
}

/**
 * Authenticate administrator with username (Admin ID) OR registered Email and password.
 * Enforces rate limiting, password hash verification, account activation check,
 * session regeneration, and audit logging.
 *
 * @param string $loginIdentifier Admin ID, Username, or Registered Email
 * @param string $password Submitted plaintext password
 * @return array ['success' => bool, 'message' => string, 'admin' => array|null]
 */
function loginAdmin(string $loginIdentifier, string $password): array {
    initSecureSession();

    $loginIdentifier = trim($loginIdentifier);
    $password = trim($password);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if (empty($loginIdentifier) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Please provide both your Admin ID / Email and password.'
        ];
    }

    // 1. Rate Limiting Check
    $rateLimit = checkAdminRateLimit($ip, $loginIdentifier);
    if ($rateLimit['locked']) {
        logAdminAudit(null, 'AUTH_RATE_LIMIT_BLOCKED', 'security', $loginIdentifier, "Sign-in blocked for {$loginIdentifier} from {$ip}. Lockout active for {$rateLimit['minutes_left']}m.");
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
        // 2. Fetch admin record by username, email, or official admin_code (and Developer handle alias)
        $stmt = $pdo->prepare("
            SELECT `id`, `admin_code`, `username`, `email`, `full_name`, `mobile`, `password_hash`, `role`, `is_active`, `is_protected`, `system_tag`
            FROM `admins`
            WHERE (
                LOWER(`username`) = LOWER(?) 
                OR LOWER(`email`) = LOWER(?)
                OR LOWER(COALESCE(`admin_code`, '')) = LOWER(?)
                OR (`system_tag` = 'DEVELOPER_ROOT' AND LOWER(?) IN ('charan', 'charan_developer'))
            )
            LIMIT 1
        ");
        $stmt->execute([$loginIdentifier, $loginIdentifier, $loginIdentifier, $loginIdentifier]);
        $admin = $stmt->fetch();

        // 3. Verify password
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            recordAdminLoginAttempt($ip, $loginIdentifier, false);
            logAdminAudit(null, 'AUTH_LOGIN_FAILED', 'admin', $loginIdentifier, "Failed sign-in attempt for identifier '{$loginIdentifier}' from IP {$ip}");
            return [
                'success' => false,
                'message' => 'Invalid Admin ID, Email, or password. Please verify your credentials.'
            ];
        }

        // 4. Check account status
        if ((int)$admin['is_active'] !== 1) {
            recordAdminLoginAttempt($ip, $loginIdentifier, false);
            logAdminAudit((int)$admin['id'], 'AUTH_LOGIN_DEACTIVATED', 'admin', (string)$admin['id'], "Deactivated administrator '{$admin['username']}' attempted login.");
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
        recordAdminLoginAttempt($ip, $loginIdentifier, true);
        try {
            $pdo->prepare("DELETE FROM `admin_login_attempts` WHERE (LOWER(`username`) = LOWER(?) OR LOWER(`username`) = LOWER(?) OR `ip_address` = ?) AND `is_success` = 0")->execute([$loginIdentifier, $admin['username'], $ip]);
        } catch (Throwable $e) {}

        // 7. Regenerate session ID to prevent session fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_code'] = $admin['admin_code'] ?? '';
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_name'] = $admin['full_name'] ?: $admin['username'];
        $_SESSION['admin_mobile'] = $admin['mobile'] ?? '';
        $_SESSION['admin_email'] = $admin['email'] ?? '';
        $_SESSION['admin_is_developer'] = ($admin['role'] === ROLE_DEVELOPER || ($admin['system_tag'] ?? '') === DEVELOPER_SYSTEM_TAG);
        $_SESSION['admin_logged_at'] = time();

        // 8. Update last_login_at timestamp
        $updateLoginStmt = $pdo->prepare("UPDATE `admins` SET `last_login_at` = NOW() WHERE `id` = ?");
        $updateLoginStmt->execute([$admin['id']]);

        // Also update last_login column if it exists for backwards compatibility
        try {
            $pdo->prepare("UPDATE `admins` SET `last_login` = NOW() WHERE `id` = ?")->execute([$admin['id']]);
        } catch (Throwable $e) {}

        // 9. Audit log
        logAdminAudit((int)$admin['id'], 'AUTH_LOGIN_SUCCESS', 'admin', (string)$admin['id'], "Successful login by '{$admin['username']}' ({$admin['role']}) via identifier '{$loginIdentifier}' from {$ip}");

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
 * Require at least Super Admin clearance (Developer or Super Admin).
 * Rejects standard Admin accounts with HTTP 403 Forbidden.
 *
 * @return array Authenticated admin record
 */
function requireSuperAdminClearance(): array {
    $admin = requireAdmin();
    if (!hasSuperAdminClearance($admin)) {
        logAdminAudit(
            (int)$admin['id'],
            'UNAUTHORIZED_GOVERNANCE_ACCESS_ATTEMPT',
            'security',
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            "Admin '{$admin['username']}' attempted to access restricted Governance endpoint"
        );

        http_response_code(403);
        renderForbiddenError("Access Restricted: Governance Clearance Required", "You are signed in as an operational Administrator ('{$admin['username']}'). This endpoint and action require authorized Super Admin or Developer role clearance.");
        exit;
    }
    return $admin;
}

/**
 * Backwards compatibility wrapper for requireSuperAdminClearance
 */
function requireSuperAdmin(): array {
    return requireSuperAdminClearance();
}

/**
 * Require Developer clearance (Charan only).
 * Rejects Super Admins and standard Admins with HTTP 403 Forbidden.
 *
 * @return array Authenticated developer record
 */
function requireDeveloperClearance(): array {
    $admin = requireAdmin();
    if (!isDeveloper($admin)) {
        logAdminAudit(
            (int)$admin['id'],
            'UNAUTHORIZED_DEVELOPER_ACCESS_ATTEMPT',
            'security',
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            "Admin '{$admin['username']}' attempted to access restricted Developer-only function"
        );

        http_response_code(403);
        renderForbiddenError("Access Restricted: Developer Clearance Required", "You are signed in as '{$admin['username']}'. This root action requires system Developer clearance.");
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

    // Developer has full, unrestricted access to all system features
    if (isDeveloper($admin)) {
        return true;
    }

    // Super Admin has authorized administrative and governance features
    if (isSuperAdmin($admin)) {
        $superAdminForbiddenPermissions = [
            'manage_developer',
            'create_super_admin',
            'delete_super_admin',
            'demote_super_admin',
            'promote_to_developer',
            'delete_developer',
            'deactivate_developer',
            'modify_developer_role'
        ];
        return !in_array($permission, $superAdminForbiddenPermissions, true);
    }

    // Role: admin (Restricted operational permissions only)
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
   ADMINISTRATOR ACCOUNT MANAGEMENT & RBAC ENFORCEMENT
   ========================================================================== */

/**
 * Automatically generate a unique, readable administrator username
 * based on Full Name, Role, Mobile Number, and account details.
 *
 * Conflict Resolution Logic:
 * 1. Base format: {first}_{role} (e.g. rahul_admin)
 * 2. Mobile suffix: {first}_{role}{mobileDigits} (e.g. rahul_admin27, rahul_admin3210)
 * 3. Last initial suffix: {first}{lastInitial}_{role}{mobileDigits} (e.g. rahulv_admin27)
 * 4. Structured dot format: {first}.{role}_{counterOrHash} (e.g. rahul.admin_482)
 * 5. Incremental format: {first}_{role}_{n} (e.g. rahul_admin_2, rahul_admin_3)
 *
 * Guaranteed to never assign reserved handles (charan, developer, root, etc.)
 * and loops until an unused username is secured.
 *
 * @param string $fullName
 * @param string $role
 * @param string|null $mobile
 * @param PDO|null $pdo
 * @return string
 */
function generateUniqueAdminUsername(string $fullName, string $role = ROLE_ADMIN, ?string $mobile = null, ?PDO $pdo = null, int $cycle = 0): string {
    if (!$pdo) {
        $pdo = getDBConnection();
    }

    $rawName = trim($fullName);
    $parts = preg_split('/\s+/', $rawName);
    $firstRaw = $parts[0] ?? 'admin';
    $first = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstRaw));
    if (empty($first)) {
        $first = 'admin';
    }

    $lastInit = '';
    if (count($parts) > 1) {
        $lastWord = $parts[count($parts) - 1];
        $lastClean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastWord));
        if (!empty($lastClean)) {
            $lastInit = substr($lastClean, 0, 1);
        }
    }

    $roleNorm = strtolower(trim($role));
    if ($roleNorm === 'super_admin' || $roleNorm === 'superadmin') {
        $roleSlug = 'superadmin';
    } elseif ($roleNorm === 'developer') {
        $roleSlug = 'developer';
    } else {
        $roleSlug = 'admin';
    }

    $mobileDigits = preg_replace('/[^0-9]/', '', (string)$mobile);
    $last2 = (strlen($mobileDigits) >= 2) ? substr($mobileDigits, -2) : '';
    $last4 = (strlen($mobileDigits) >= 4) ? substr($mobileDigits, -4) : '';

    $candidates = [];

    // Format 1: rahul_admin
    $candidates[] = "{$first}_{$roleSlug}";

    // Format 2: rahul_admin27
    if (!empty($last2)) {
        $candidates[] = "{$first}_{$roleSlug}{$last2}";
    }

    // Format 3: rahulv_admin or rahulv_admin27
    if (!empty($lastInit)) {
        if (!empty($last2)) {
            $candidates[] = "{$first}{$lastInit}_{$roleSlug}{$last2}";
        }
        $candidates[] = "{$first}{$lastInit}_{$roleSlug}";
    }

    // Format 4: rahul_admin3210 (last 4 digits)
    if (!empty($last4) && $last4 !== $last2) {
        $candidates[] = "{$first}_{$roleSlug}{$last4}";
    }

    // Format 5: rahul.admin_482 (dot format with 3-digit numeric salt)
    if (!empty($mobileDigits)) {
        $salt = substr(abs(crc32($rawName . $mobileDigits)) . '', 0, 3);
        $candidates[] = "{$first}.{$roleSlug}_{$salt}";
    }

    // Format 6-12: incremental sequence rahul_admin_2, rahul_admin_3, ...
    for ($i = 2; $i <= 15; $i++) {
        $candidates[] = "{$first}_{$roleSlug}_{$i}";
    }

    $reserved = [
        'charan', 'charan_developer', 'developer', 'root', 'system', 
        'superadmin', 'admin', 'administrator', 'kaammitra', 'support'
    ];

    $checkAvailable = function(string $uname) use ($pdo, $reserved): bool {
        if (in_array(strtolower($uname), $reserved, true)) {
            return false;
        }
        if (!$pdo) {
            return true;
        }
        try {
            $stmt = $pdo->prepare("SELECT `id` FROM `admins` WHERE LOWER(`username`) = LOWER(?) LIMIT 1");
            $stmt->execute([$uname]);
            return ($stmt->fetch() === false);
        } catch (Throwable $e) {
            return false;
        }
    };

    $availablePool = [];
    foreach ($candidates as $cand) {
        if ($checkAvailable($cand)) {
            $availablePool[] = $cand;
            if ($cycle <= 0) {
                return $cand;
            }
        }
    }

    if (!empty($availablePool)) {
        return $availablePool[$cycle % count($availablePool)];
    }

    // Continuous alternative loop until an available username is found
    $tries = 0;
    while ($tries < 100) {
        $randomSalt = random_int(100, 999);
        $cand = "{$first}.{$roleSlug}_{$randomSalt}";
        if ($checkAvailable($cand)) {
            return $cand;
        }
        $tries++;
    }

    return "{$first}_{$roleSlug}_" . time();
}

/**
 * Generate a unique standardized Admin Code / ID based on role.
 * Standard format:
 * - Developer: KM-DEV-001
 * - Super Admin: KM-SADM-001, KM-SADM-002...
 * - Admin: KM-ADM-001, KM-ADM-002...
 */
function generateAdminCode(string $role, ?PDO $pdo = null): string {
    if (!$pdo) {
        $pdo = getDBConnection();
    }
    $role = strtolower(trim($role));
    $prefix = ($role === ROLE_DEVELOPER || $role === 'developer') ? 'DEV' : (($role === ROLE_SUPER_ADMIN || $role === 'super_admin' || $role === 'superadmin') ? 'SADM' : 'ADM');

    if (!$pdo) {
        return sprintf("KM-%s-%03d", $prefix, 1);
    }

    try {
        $stmt = $pdo->prepare("SELECT `admin_code` FROM `admins` WHERE `admin_code` LIKE ?");
        $stmt->execute(["KM-{$prefix}-%"]);
        $highest = 0;
        while ($code = $stmt->fetchColumn()) {
            if (preg_match('/KM-[A-Z]+-(\d+)/', $code, $m)) {
                $val = (int)$m[1];
                if ($val > $highest) {
                    $highest = $val;
                }
            }
        }
        return sprintf("KM-%s-%03d", $prefix, $highest + 1);
    } catch (Throwable $e) {
        return sprintf("KM-%s-%03d", $prefix, random_int(100, 999));
    }
}

/**
 * Create a new administrator account.
 * Permissions:
 * - Developer: Can create 'admin' and 'super_admin' accounts. Cannot create additional Developer accounts.
 * - Super Admin: Can create 'admin' accounts ONLY. Cannot create Super Admin or Developer accounts.
 * - Admin: CANNOT create any administrator accounts.
 *
 * @param array $data ['username', 'email', 'full_name', 'mobile', 'password', 'role']
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string, 'admin_id' => int|null, 'username' => string|null]
 */
function createAdminAccount(array $data, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin) {
        return ['success' => false, 'message' => 'Unauthorized: Please authenticate to proceed.'];
    }

    $isDev = isDeveloper($actingAdmin);
    $isSuper = isSuperAdmin($actingAdmin);

    // Operational Admins cannot create any accounts
    if (!$isDev && !$isSuper) {
        logAdminAudit(
            $actingAdminId,
            'UNAUTHORIZED_CREATE_ADMIN_ATTEMPT',
            'security',
            null,
            "Standard Admin '{$actingAdmin['username']}' attempted to create an administrator account."
        );
        return ['success' => false, 'message' => 'Permission Denied: Standard Admins cannot create administrative accounts.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    $fullName = trim($data['full_name'] ?? '');
    $email    = trim($data['email'] ?? '');
    $mobile   = trim($data['mobile'] ?? '');
    $password = $data['password'] ?? '';
    $requestedRole = trim($data['role'] ?? ROLE_ADMIN);
    $submittedUsername = trim($data['username'] ?? '');

    // Validate mobile if provided
    $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
    if (!empty($mobile) && (strlen($cleanMobile) < 10 || strlen($cleanMobile) > 15)) {
        return ['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.'];
    }

    // Role Enforcement:
    // 1. Developer role can never be created via form
    if ($requestedRole === ROLE_DEVELOPER) {
        logAdminAudit(
            $actingAdminId,
            'UNAUTHORIZED_DEVELOPER_CREATION_ATTEMPT',
            'security',
            $submittedUsername,
            "Admin '{$actingAdmin['username']}' attempted to create a Developer account."
        );
        return ['success' => false, 'message' => 'Security Violation: Cannot create additional Developer accounts. Charan is the permanent system Developer.'];
    }

    // 2. Super Admin can ONLY create standard Admin accounts
    if ($isSuper && $requestedRole !== ROLE_ADMIN) {
        logAdminAudit(
            $actingAdminId,
            'UNAUTHORIZED_SUPERADMIN_CREATION_ATTEMPT',
            'security',
            $submittedUsername,
            "Super Admin '{$actingAdmin['username']}' attempted to create Super Admin account."
        );
        return ['success' => false, 'message' => 'Permission Denied: Super Administrators can only create standard Admin accounts. Only the Developer can create Super Administrators.'];
    }

    // 3. Developer can create Admin or Super Admin
    if ($isDev) {
        if (!in_array($requestedRole, [ROLE_ADMIN, ROLE_SUPER_ADMIN], true)) {
            $requestedRole = ROLE_ADMIN;
        }
    } else {
        $requestedRole = ROLE_ADMIN;
    }

    // Resolve username (Strictly System-Generated, not user-chosen):
    // The system automatically generates a unique, collision-free Admin ID based on full name, role & mobile.
    $reservedHandles = ['charan', 'charan_developer', 'developer', 'root', 'system', 'superadmin', 'administrator', 'admin'];
    $finalUsername = '';

    // If an auto-generated suggestion was received from the client-side system generator and is valid & available, use it.
    if (!empty($submittedUsername) && 
        !in_array(strtolower($submittedUsername), $reservedHandles, true) && 
        preg_match('/^[a-zA-Z0-9_.-]{3,35}$/', $submittedUsername)) {
        
        $chkStmt = $pdo->prepare("SELECT `id` FROM `admins` WHERE LOWER(`username`) = LOWER(?) LIMIT 1");
        $chkStmt->execute([$submittedUsername]);
        if (!$chkStmt->fetch()) {
            $finalUsername = $submittedUsername;
        }
    }

    // If not set or already taken, system automatically generates a fresh unique username
    if (empty($finalUsername)) {
        $finalUsername = generateUniqueAdminUsername($fullName ?: 'Admin', $requestedRole, $cleanMobile, $pdo);
    }

    if (empty($password) || strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long for administrative accounts.'];
    }

    try {
        // Check duplicate email if provided
        if (!empty($email)) {
            $chkEmail = $pdo->prepare("SELECT `id` FROM `admins` WHERE LOWER(`email`) = LOWER(?) LIMIT 1");
            $chkEmail->execute([$email]);
            if ($chkEmail->fetch()) {
                return ['success' => false, 'message' => "The email '{$email}' is already associated with an existing administrator."];
            }
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $adminCode = generateAdminCode($requestedRole, $pdo);

        $stmt = $pdo->prepare("
            INSERT INTO `admins` (`admin_code`, `username`, `email`, `full_name`, `mobile`, `password_hash`, `role`, `is_active`, `is_protected`, `system_tag`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, NULL, NOW())
        ");
        
        try {
            $stmt->execute([
                $adminCode,
                $finalUsername,
                !empty($email) ? $email : null,
                !empty($fullName) ? $fullName : $finalUsername,
                !empty($cleanMobile) ? $cleanMobile : null,
                $passwordHash,
                $requestedRole
            ]);
        } catch (PDOException $pe) {
            // If duplicate username collision occurred concurrently (SQLSTATE 23000 / code 1062)
            if ($pe->getCode() == 23000 || strpos($pe->getMessage(), '1062') !== false) {
                $finalUsername = generateUniqueAdminUsername($fullName ?: 'Admin', $requestedRole, $cleanMobile, $pdo);
                $stmt->execute([
                    $adminCode,
                    $finalUsername,
                    !empty($email) ? $email : null,
                    !empty($fullName) ? $fullName : $finalUsername,
                    !empty($cleanMobile) ? $cleanMobile : null,
                    $passwordHash,
                    $requestedRole
                ]);
            } else {
                throw $pe;
            }
        }

        $newAdminId = (int)$pdo->lastInsertId();

        logAdminAudit(
            $actingAdminId,
            'ADMIN_ACCOUNT_CREATED',
            'admin',
            (string)$newAdminId,
            "Created administrator account '{$finalUsername}' [{$adminCode}] with role '{$requestedRole}' (Mobile: " . ($cleanMobile ?: 'N/A') . ")"
        );

        return [
            'success' => true,
            'message' => "Administrator account '{$finalUsername}' ({$requestedRole}) successfully created. Official Admin ID: {$adminCode}",
            'admin_id' => $newAdminId,
            'admin_code' => $adminCode,
            'username' => $finalUsername
        ];
    } catch (Throwable $e) {
        error_log("Failed to create admin: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred while creating the administrator account: ' . $e->getMessage()];
    }
}

/**
 * Update an administrator's profile (Full Name, Mobile Number, Email, Password).
 * Allows Charan (Developer) or any administrator to manage their own profile details.
 * Prevents demotion or deactivation of the Developer account.
 *
 * @param int $targetAdminId
 * @param array $data
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string, 'admin' => array|null]
 */
function updateAdminProfile(int $targetAdminId, array $data, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin) {
        return ['success' => false, 'message' => 'Unauthorized: Please authenticate to proceed.'];
    }

    if ($targetAdminId <= 0) {
        $targetAdminId = (int)$actingAdmin['id'];
    }

    $isDev = isDeveloper($actingAdmin);
    $isSelf = ((int)$targetAdminId === (int)$actingAdmin['id']);

    // Only self or Developer can update a profile
    if (!$isSelf && !$isDev) {
        logAdminAudit($actingAdminId, 'UNAUTHORIZED_PROFILE_UPDATE_ATTEMPT', 'security', (string)$targetAdminId, "Standard Admin '{$actingAdmin['username']}' attempted to update profile for admin #{$targetAdminId}");
        return ['success' => false, 'message' => 'Permission Denied: You can only edit your own profile.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$targetAdminId]);
        $target = $stmt->fetch();
        if (!$target) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        $isTargetDev = ($target['role'] === ROLE_DEVELOPER || isProtectedDeveloperAccount($target));

        // Super Admin or Admin cannot update Developer profile
        if ($isTargetDev && !$isDev) {
            logAdminAudit($actingAdminId, 'UNAUTHORIZED_DEVELOPER_UPDATE_ATTEMPT', 'security', (string)$targetAdminId, "Admin attempted unauthorized update of Developer profile");
            return ['success' => false, 'message' => 'Security Violation: The Developer profile can only be modified by the Developer.'];
        }

        $fullName = trim($data['full_name'] ?? $target['full_name']);
        $email    = trim($data['email'] ?? $target['email']);
        $mobile   = trim($data['mobile'] ?? ($target['mobile'] ?? ''));
        $password = trim($data['new_password'] ?? '');

        // Validation
        if (empty($fullName)) {
            return ['success' => false, 'message' => 'Full name cannot be empty.'];
        }

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Please provide a valid email address.'];
            }
            // Check email uniqueness excluding target
            $chkEmail = $pdo->prepare("SELECT `id` FROM `admins` WHERE LOWER(`email`) = LOWER(?) AND `id` != ? LIMIT 1");
            $chkEmail->execute([$email, $targetAdminId]);
            if ($chkEmail->fetch()) {
                return ['success' => false, 'message' => "The email '{$email}' is already in use by another administrator."];
            }
        }

        if (!empty($mobile)) {
            $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
            if (strlen($cleanMobile) < 10 || strlen($cleanMobile) > 15) {
                return ['success' => false, 'message' => 'Mobile number should be a valid 10-15 digit telephone number.'];
            }
            $mobile = $cleanMobile;
        }

        // Build update
        $fields = [
            '`full_name` = ?',
            '`email` = ?',
            '`mobile` = ?'
        ];
        $params = [$fullName, !empty($email) ? $email : null, !empty($mobile) ? $mobile : null];

        // Optional password update
        if (!empty($password)) {
            if (strlen($password) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
            }
            $fields[] = '`password_hash` = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $params[] = $targetAdminId;

        $updSql = "UPDATE `admins` SET " . implode(', ', $fields) . " WHERE `id` = ?";
        $updStmt = $pdo->prepare($updSql);
        $updStmt->execute($params);

        // Fetch updated record
        $stmt->execute([$targetAdminId]);
        $updatedAdmin = $stmt->fetch();

        // Update active session if self
        if ($isSelf) {
            $_SESSION['admin_name'] = $updatedAdmin['full_name'];
            $_SESSION['admin_email'] = $updatedAdmin['email'];
            $_SESSION['admin_mobile'] = $updatedAdmin['mobile'];
        }

        logAdminAudit(
            $actingAdminId,
            'ADMIN_PROFILE_UPDATED',
            'admin',
            (string)$targetAdminId,
            "Updated profile for '{$updatedAdmin['username']}' (Full Name: {$fullName}, Email: {$email}, Mobile: {$mobile})"
        );

        return [
            'success' => true,
            'message' => 'Profile updated successfully.',
            'admin' => $updatedAdmin
        ];
    } catch (Throwable $e) {
        error_log("updateAdminProfile error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error while updating profile: ' . $e->getMessage()];
    }
}

/**
 * Activate or Deactivate an administrator account.
 * Permissions:
 * - Developer: Can toggle Super Admin and Admin accounts. Developer account can never be deactivated.
 * - Super Admin: Can toggle Admin accounts ONLY. Cannot deactivate Developer or other Super Admins. Cannot deactivate self.
 * - Admin: CANNOT change any account status.
 *
 * @param int $targetAdminId
 * @param int $newStatus 1 for Active, 0 for Deactivated
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string]
 */
function updateAdminStatus(int $targetAdminId, int $newStatus, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin) {
        return ['success' => false, 'message' => 'Unauthorized: Please authenticate to proceed.'];
    }

    $isDev = isDeveloper($actingAdmin);
    $isSuper = isSuperAdmin($actingAdmin);

    // Standard Admins cannot change account activation status
    if (!$isDev && !$isSuper) {
        logAdminAudit($actingAdminId, 'UNAUTHORIZED_STATUS_CHANGE_ATTEMPT', 'security', (string)$targetAdminId, "Standard Admin '{$actingAdmin['username']}' attempted status change");
        return ['success' => false, 'message' => 'Permission Denied: Standard Admins cannot change administrator account status.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `id`, `username`, `role`, `is_active`, `is_protected`, `system_tag` FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$targetAdminId]);
        $target = $stmt->fetch();

        if (!$target) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        // GUARD 1: Developer account can NEVER be deactivated by anyone
        if (isProtectedDeveloperAccount($target)) {
            logAdminAudit(
                $actingAdminId,
                'CRITICAL_SECURITY_ALERT_DEVELOPER_DEACTIVATE_BLOCKED',
                'security',
                (string)$targetAdminId,
                "BLOCKED: Attempt by '{$actingAdmin['username']}' to deactivate Developer '{$target['username']}'"
            );
            return ['success' => false, 'message' => 'Security Violation: The Developer account is permanent and can NEVER be deactivated.'];
        }

        // GUARD 2: Super Admin cannot deactivate Super Admin accounts or their own account
        if ($isSuper) {
            if ($target['role'] === ROLE_SUPER_ADMIN) {
                logAdminAudit($actingAdminId, 'UNAUTHORIZED_SUPERADMIN_STATUS_CHANGE', 'security', (string)$targetAdminId, "Super Admin '{$actingAdmin['username']}' attempted to alter Super Admin status for '{$target['username']}'");
                return ['success' => false, 'message' => 'Permission Denied: Super Administrators cannot deactivate Super Admin accounts.'];
            }
            if ($actingAdminId === $targetAdminId) {
                return ['success' => false, 'message' => 'Permission Denied: You cannot deactivate your own administrative account.'];
            }
        }

        // GUARD 3: Developer cannot deactivate their own account
        if ($isDev && $actingAdminId === $targetAdminId && $newStatus === 0) {
            return ['success' => false, 'message' => 'Security Violation: The system Developer account cannot be deactivated.'];
        }

        $upd = $pdo->prepare("UPDATE `admins` SET `is_active` = ? WHERE `id` = ? AND `is_protected` = 0");
        $upd->execute([$newStatus ? 1 : 0, $targetAdminId]);

        $statusLabel = $newStatus ? 'Active' : 'Deactivated';
        logAdminAudit(
            $actingAdminId,
            $newStatus ? 'ADMIN_ACCOUNT_ACTIVATED' : 'ADMIN_ACCOUNT_DEACTIVATED',
            'admin',
            (string)$targetAdminId,
            "Administrator '{$target['username']}' ({$target['role']}) status set to {$statusLabel}"
        );

        return [
            'success' => true,
            'message' => "Administrator '{$target['username']}' has been {$statusLabel}."
        ];
    } catch (Throwable $e) {
        error_log("updateAdminStatus error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update administrator status: ' . $e->getMessage()];
    }
}

/**
 * Assign or change an administrator's role.
 * Permissions:
 * - Developer: Can change roles between 'admin' and 'super_admin'. Developer role cannot be demoted or assigned.
 * - Super Admin: CANNOT change roles to/from Super Admin or Developer. Cannot modify their own privileges.
 * - Admin: CANNOT change any user roles.
 *
 * @param int $targetAdminId
 * @param string $newRole 'super_admin' or 'admin'
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string]
 */
function updateAdminRole(int $targetAdminId, string $newRole, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin) {
        return ['success' => false, 'message' => 'Unauthorized: Please authenticate to proceed.'];
    }

    $isDev = isDeveloper($actingAdmin);
    $isSuper = isSuperAdmin($actingAdmin);

    // Standard Admins cannot change roles
    if (!$isDev && !$isSuper) {
        logAdminAudit($actingAdminId, 'UNAUTHORIZED_ROLE_CHANGE_ATTEMPT', 'security', (string)$targetAdminId, "Standard Admin '{$actingAdmin['username']}' attempted role change");
        return ['success' => false, 'message' => 'Permission Denied: Standard Admins cannot modify user roles.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `id`, `username`, `role`, `is_active`, `is_protected`, `system_tag` FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$targetAdminId]);
        $target = $stmt->fetch();

        if (!$target) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        // GUARD 1: Developer account role can NEVER be modified, demoted, or transferred by anyone
        if (isProtectedDeveloperAccount($target)) {
            logAdminAudit(
                $actingAdminId,
                'CRITICAL_SECURITY_ALERT_DEVELOPER_ROLE_ALTER_BLOCKED',
                'security',
                (string)$targetAdminId,
                "BLOCKED: Attempt by '{$actingAdmin['username']}' to alter Developer '{$target['username']}' role"
            );
            return ['success' => false, 'message' => 'Security Violation: The Developer role is immutable and can NEVER be modified, transferred, or demoted.'];
        }

        // GUARD 2: No account can ever be elevated or assigned the Developer role
        if ($newRole === ROLE_DEVELOPER) {
            logAdminAudit(
                $actingAdminId,
                'UNAUTHORIZED_DEVELOPER_PROMOTION_ATTEMPT',
                'security',
                (string)$targetAdminId,
                "Attempt by '{$actingAdmin['username']}' to promote '{$target['username']}' to Developer was blocked"
            );
            return ['success' => false, 'message' => 'Security Violation: Developer privileges cannot be assigned or transferred. Charan is the permanent system Developer.'];
        }

        // GUARD 3: Super Admin cannot modify Super Admin accounts, cannot promote to Super Admin, and cannot modify their own privileges
        if ($isSuper) {
            if ($target['role'] === ROLE_SUPER_ADMIN) {
                logAdminAudit($actingAdminId, 'UNAUTHORIZED_SUPERADMIN_ROLE_CHANGE', 'security', (string)$targetAdminId, "Super Admin '{$actingAdmin['username']}' attempted to modify Super Admin '{$target['username']}'");
                return ['success' => false, 'message' => 'Permission Denied: Super Administrators cannot modify Super Admin privileges or demote Super Admins.'];
            }
            if ($newRole === ROLE_SUPER_ADMIN) {
                logAdminAudit($actingAdminId, 'UNAUTHORIZED_SUPERADMIN_PROMOTION', 'security', (string)$targetAdminId, "Super Admin '{$actingAdmin['username']}' attempted to promote '{$target['username']}' to Super Admin");
                return ['success' => false, 'message' => 'Permission Denied: Only the Developer can promote an account to Super Administrator.'];
            }
            if ($actingAdminId === $targetAdminId) {
                return ['success' => false, 'message' => 'Permission Denied: You cannot modify your own administrative privileges.'];
            }
        }

        // Valid roles for assignment
        if (!in_array($newRole, [ROLE_ADMIN, ROLE_SUPER_ADMIN], true)) {
            return ['success' => false, 'message' => 'Invalid role specified. Supported roles: admin, super_admin.'];
        }

        // Only Developer can change roles to/from Super Admin
        if ($newRole === ROLE_SUPER_ADMIN && !$isDev) {
            return ['success' => false, 'message' => 'Permission Denied: Only the Developer can grant Super Administrator role.'];
        }

        $upd = $pdo->prepare("UPDATE `admins` SET `role` = ? WHERE `id` = ? AND `is_protected` = 0");
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
            'message' => "Role for '{$target['username']}' successfully updated to {$newRole}."
        ];
    } catch (Throwable $e) {
        error_log("updateAdminRole error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update administrator role: ' . $e->getMessage()];
    }
}

/**
 * Permanently remove / delete an administrator account subject to strict RBAC rules.
 *
 * Rules:
 * - Developer account can NEVER be deleted by ANYONE (Developer, Super Admin, Admin).
 * - Developer can remove Super Admin and Admin accounts.
 * - Super Admin can remove Admin accounts ONLY. Cannot remove Developer or Super Admin accounts.
 * - Admin CANNOT remove any administrator accounts.
 * - Nobody can delete their own active session account.
 *
 * @param int $targetAdminId
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string]
 */
function deleteAdminAccount(int $targetAdminId, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin) {
        return ['success' => false, 'message' => 'Unauthorized: Please authenticate to proceed.'];
    }

    $isDev = isDeveloper($actingAdmin);
    $isSuper = isSuperAdmin($actingAdmin);

    // Standard Admins cannot delete accounts
    if (!$isDev && !$isSuper) {
        logAdminAudit(
            $actingAdminId,
            'UNAUTHORIZED_ADMIN_DELETE_ATTEMPT',
            'security',
            (string)$targetAdminId,
            "Standard Admin '{$actingAdmin['username']}' attempted to delete admin ID #{$targetAdminId}"
        );
        return ['success' => false, 'message' => 'Permission Denied: Standard Admins cannot remove administrative accounts.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `id`, `username`, `role`, `is_protected`, `system_tag` FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$targetAdminId]);
        $target = $stmt->fetch();

        if (!$target) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        // RULE 1: Developer account must NEVER be deleted by anyone!
        if (isProtectedDeveloperAccount($target)) {
            logAdminAudit(
                $actingAdminId,
                'CRITICAL_SECURITY_ALERT_DEVELOPER_DELETION_BLOCKED',
                'security',
                (string)$targetAdminId,
                "SECURITY ALERT: Blocked attempt by '{$actingAdmin['username']}' to delete Developer account '{$target['username']}'"
            );
            return ['success' => false, 'message' => 'Security Violation: The Developer account is permanent and can NEVER be deleted, removed, or purged.'];
        }

        // Prevent deleting currently logged-in account
        if ($actingAdminId === $targetAdminId) {
            return ['success' => false, 'message' => 'Action Blocked: You cannot delete your own currently active account.'];
        }

        // RULE 2: Super Admin can ONLY remove standard Admin accounts
        if ($isSuper) {
            if ($target['role'] === ROLE_SUPER_ADMIN) {
                logAdminAudit(
                    $actingAdminId,
                    'UNAUTHORIZED_SUPERADMIN_DELETION_ATTEMPT',
                    'security',
                    (string)$targetAdminId,
                    "Super Admin '{$actingAdmin['username']}' attempted to delete Super Admin '{$target['username']}'"
                );
                return ['success' => false, 'message' => 'Permission Denied: Super Administrators cannot remove Super Admin accounts. Only the Developer can remove Super Admins.'];
            }
            if ($target['role'] !== ROLE_ADMIN) {
                return ['success' => false, 'message' => 'Permission Denied: Super Administrators may only remove standard Admin accounts.'];
            }
        }

        // RULE 3: Developer can remove Super Admin and Admin accounts
        if ($isDev) {
            if (!in_array($target['role'], [ROLE_ADMIN, ROLE_SUPER_ADMIN], true)) {
                return ['success' => false, 'message' => 'Permission Denied: Unsupported target account role.'];
            }
        }

        // Execute deletion within transaction
        $pdo->beginTransaction();

        $del = $pdo->prepare("DELETE FROM `admins` WHERE `id` = ? AND `is_protected` = 0");
        $del->execute([$targetAdminId]);

        $pdo->commit();

        logAdminAudit(
            $actingAdminId,
            'ADMIN_ACCOUNT_REMOVED',
            'admin',
            (string)$targetAdminId,
            "Administrator '{$target['username']}' ({$target['role']}) permanently removed by '{$actingAdmin['username']}' ({$actingAdmin['role']})"
        );

        return [
            'success' => true,
            'message' => "Administrator account '{$target['username']}' ({$target['role']}) has been successfully removed."
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("deleteAdminAccount error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to remove administrator account: ' . $e->getMessage()];
    }
}

/**
 * Reset an administrator password through a secure, authorized process.
 * Permissions:
 * - Developer: Can reset Super Admin and Admin passwords. Can reset own password.
 * - Super Admin: Can reset Admin passwords and own password. Cannot reset Developer or other Super Admin passwords.
 * - Admin: CANNOT reset other administrator passwords.
 *
 * @param int $targetAdminId
 * @param string $newPassword
 * @param int $actingAdminId
 * @return array ['success' => bool, 'message' => string]
 */
function resetAdminPassword(int $targetAdminId, string $newPassword, int $actingAdminId): array {
    $actingAdmin = getCurrentAdmin();
    if (!$actingAdmin) {
        return ['success' => false, 'message' => 'Unauthorized: Please authenticate to proceed.'];
    }

    $isDev = isDeveloper($actingAdmin);
    $isSuper = isSuperAdmin($actingAdmin);

    // Standard Admins cannot reset other passwords
    if (!$isDev && !$isSuper && $actingAdminId !== $targetAdminId) {
        logAdminAudit($actingAdminId, 'UNAUTHORIZED_PASSWORD_RESET_ATTEMPT', 'security', (string)$targetAdminId, "Standard Admin '{$actingAdmin['username']}' attempted password reset");
        return ['success' => false, 'message' => 'Permission Denied: Standard Admins cannot reset passwords.'];
    }

    if (empty($newPassword) || strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT `id`, `username`, `role`, `is_protected`, `system_tag` FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$targetAdminId]);
        $target = $stmt->fetch();

        if (!$target) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        // Target is Developer: ONLY Developer themselves can change their own credentials
        if (isProtectedDeveloperAccount($target)) {
            if ($actingAdminId !== (int)$target['id']) {
                logAdminAudit(
                    $actingAdminId,
                    'CRITICAL_SECURITY_ALERT_DEVELOPER_PASSWORD_RESET_BLOCKED',
                    'security',
                    (string)$targetAdminId,
                    "BLOCKED: User '{$actingAdmin['username']}' attempted unauthorized password reset on Developer account"
                );
                return ['success' => false, 'message' => 'Security Violation: Only the Developer can update the Developer account credentials.'];
            }
        }

        // Super Admin cannot reset Developer or other Super Admin passwords
        if ($isSuper) {
            if ($target['role'] === ROLE_SUPER_ADMIN && $actingAdminId !== $targetAdminId) {
                logAdminAudit($actingAdminId, 'UNAUTHORIZED_SUPERADMIN_PASSWORD_RESET', 'security', (string)$targetAdminId, "Super Admin '{$actingAdmin['username']}' attempted to reset password of Super Admin '{$target['username']}'");
                return ['success' => false, 'message' => 'Permission Denied: Super Administrators cannot reset another Super Administrator\'s password.'];
            }
            if ($target['role'] !== ROLE_ADMIN && $actingAdminId !== $targetAdminId) {
                return ['success' => false, 'message' => 'Permission Denied: Super Administrators may only reset standard Admin passwords.'];
            }
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE `admins` SET `password_hash` = ? WHERE `id` = ?");
        $upd->execute([$hash, $targetAdminId]);

        logAdminAudit(
            $actingAdminId,
            'ADMIN_PASSWORD_RESET',
            'admin',
            (string)$targetAdminId,
            "Password securely reset for administrator '{$target['username']}' by '{$actingAdmin['username']}'"
        );

        return [
            'success' => true,
            'message' => "Password for '{$target['username']}' has been successfully reset."
        ];
    } catch (Throwable $e) {
        error_log("resetAdminPassword error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reset password: ' . $e->getMessage()];
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
