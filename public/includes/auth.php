<?php
/**
 * KaamMitra - Authentication & Session Security Handler
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

/**
 * Detect application base URL prefix (e.g. '/' or '/public/')
 */
function getAppBasePath(): string {
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $pos = strpos($script, '/public/');
    if ($pos !== false) {
        $base = substr($script, 0, $pos + 8);
    } else {
        $base = '/';
    }
    return $base;
}

/**
 * Initialize secure PHP session
 */
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Enforce secure session cookie settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');

        session_start();
    }
}

/**
 * Check if a customer is currently authenticated
 * 
 * @return bool
 */
function isCustomerLoggedIn(): bool {
    initSecureSession();
    return !empty($_SESSION['customer_id']) && is_numeric($_SESSION['customer_id']);
}

/**
 * Retrieve the authenticated customer's full record
 * 
 * @return array|null
 */
function getLoggedInCustomer(): ?array {
    if (!isCustomerLoggedIn()) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, full_name, mobile, email, gender, address_line, city, state, pincode, created_at FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['customer_id']]);
        $customer = $stmt->fetch();
        return $customer ?: null;
    } catch (Exception $e) {
        error_log("Failed to fetch customer: " . $e->getMessage());
        return null;
    }
}

/**
 * Protect customer-only routes; redirects unauthenticated visitors to login
 * 
 * @param string $redirectUrl Target to return to after successful login
 */
function requireCustomerAuth(string $redirectUrl = ''): array {
    if (!isCustomerLoggedIn()) {
        $loginUrl = getAppBasePath() . 'customer/login.php';
        if ($redirectUrl) {
            $loginUrl .= '?redirect=' . urlencode($redirectUrl);
        }
        header("Location: " . $loginUrl);
        exit;
    }

    $customer = getLoggedInCustomer();
    if (!$customer) {
        logoutCustomer();
        header("Location: " . getAppBasePath() . "customer/login.php?msg=session_invalid");
        exit;
    }

    return $customer;
}

/**
 * Authenticate customer by mobile number OR email
 * 
 * @param string $identifier Mobile or Email
 * @param string $password Plain text password
 * @return array ['success' => bool, 'message' => string, 'customer' => array|null]
 */
function loginCustomer(string $identifier, string $password): array {
    initSecureSession();

    $identifier = trim($identifier);
    $password = trim($password);

    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'message' => 'Please provide both your mobile/email and password.'];
    }

    try {
        $pdo = getDBConnection();
        
        // Find customer by email or mobile
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? OR mobile = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $customer = $stmt->fetch();

        if (!$customer || !password_verify($password, $customer['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid mobile/email or password. Please check your credentials.'];
        }

        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['customer_id'] = (int)$customer['id'];
        $_SESSION['customer_name'] = $customer['full_name'];
        $_SESSION['customer_mobile'] = $customer['mobile'];
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['customer_city'] = $customer['city'];

        return [
            'success' => true,
            'message' => 'Login successful! Welcome back, ' . htmlspecialchars($customer['full_name']),
            'customer' => $customer
        ];
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A system error occurred during login. Please try again.'];
    }
}

/**
 * Register a new customer in MySQL database
 * 
 * @param array $data Input form data
 * @return array ['success' => bool, 'message' => string, 'errors' => array, 'customer_id' => int|null]
 */
function registerCustomer(array $data): array {
    initSecureSession();

    $fullName    = trim($data['full_name'] ?? '');
    $mobile      = trim($data['mobile'] ?? '');
    $email       = trim($data['email'] ?? '');
    $gender      = trim($data['gender'] ?? 'Prefer not to say');
    $password    = $data['password'] ?? '';
    $confirmPass = $data['confirm_password'] ?? '';
    $address     = trim($data['address_line'] ?? '');
    $city        = trim($data['city'] ?? '');
    $state       = trim($data['state'] ?? '');
    $pincode     = trim($data['pincode'] ?? '');
    $agreeTerms  = !empty($data['agree_terms']);

    $errors = [];

    // 1. Full Name Validation
    if (empty($fullName) || strlen($fullName) < 2) {
        $errors['full_name'] = 'Full legal name is required (minimum 2 characters).';
    }

    // 2. Mobile Validation (Indian 10-digit mobile starting with 6-9)
    $cleanMobile = preg_replace('/[^\d]/', '', $mobile);
    if (!preg_match('/^[6-9]\d{9}$/', $cleanMobile)) {
        $errors['mobile'] = 'Please enter a valid 10-digit Indian mobile number.';
    } else {
        $mobile = $cleanMobile;
    }

    // 3. Email Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address (e.g., name@gmail.com).';
    }

    // 4. Gender Validation
    $allowedGenders = ['Male', 'Female', 'Other', 'Prefer not to say'];
    if (!in_array($gender, $allowedGenders, true)) {
        $gender = 'Prefer not to say';
    }

    // 5. Password Validation
    if (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    // 6. Confirm Password Validation
    if ($password !== $confirmPass) {
        $errors['confirm_password'] = 'Passwords do not match. Please verify.';
    }

    // 7. PIN Code Validation (optional, but if provided must be 6 digits)
    if (!empty($pincode)) {
        $cleanPin = preg_replace('/[^\d]/', '', $pincode);
        if (!preg_match('/^[1-9][0-9]{5}$/', $cleanPin)) {
            $errors['pincode'] = 'PIN code must be a valid 6-digit postal code.';
        } else {
            $pincode = $cleanPin;
        }
    }

    // 8. Terms & Conditions
    if (!$agreeTerms) {
        $errors['agree_terms'] = 'You must accept the Terms of Service and Privacy Policy.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $errors];
    }

    try {
        $pdo = getDBConnection();

        // Check for existing mobile
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'An account is already registered with this mobile number. Please log in instead.',
                'errors' => ['mobile' => 'Mobile number already registered.']
            ];
        }

        // Check for existing email
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'An account is already registered with this email address. Please log in instead.',
                'errors' => ['email' => 'Email address already registered.']
            ];
        }

        // Hash password securely with BCRYPT
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new customer record
        $insertStmt = $pdo->prepare("
            INSERT INTO customers (full_name, mobile, email, gender, password_hash, address_line, city, state, pincode)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $fullName,
            $mobile,
            $email,
            $gender,
            $passwordHash,
            $address ?: null,
            $city ?: null,
            $state ?: null,
            $pincode ?: null
        ]);

        $customerId = (int)$pdo->lastInsertId();

        // Auto-login upon registration
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['customer_name'] = $fullName;
        $_SESSION['customer_mobile'] = $mobile;
        $_SESSION['customer_email'] = $email;
        $_SESSION['customer_city'] = $city;

        return [
            'success' => true,
            'message' => 'Account created successfully! Welcome to KaamMitra.',
            'errors' => [],
            'customer_id' => $customerId
        ];
    } catch (Exception $e) {
        error_log("Registration DB Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred during registration. Please try again.', 'errors' => []];
    }
}

/**
 * Log out customer and destroy active session
 */
function logoutCustomer(): void {
    initSecureSession();
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

/**
 * CSRF Protection
 */
function getCsrfToken(): string {
    initSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    initSecureSession();
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/* ============================================================================
   SERVICE PROVIDER (PARTNER) AUTHENTICATION & MANAGEMENT
   ============================================================================ */

/**
 * Check if a service provider is currently authenticated
 * 
 * @return bool
 */
function isPartnerLoggedIn(): bool {
    initSecureSession();
    return !empty($_SESSION['partner_id']) && is_numeric($_SESSION['partner_id']);
}

/**
 * Retrieve the authenticated partner's full record from database
 * 
 * @return array|null
 */
function getLoggedInPartner(): ?array {
    if (!isPartnerLoggedIn()) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, full_name, mobile, email, gender, primary_skill, experience_years, city, service_area, address, verification_status, rejection_reason, created_at, updated_at FROM service_providers WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['partner_id']]);
        $partner = $stmt->fetch();
        return $partner ?: null;
    } catch (Exception $e) {
        error_log("Failed to fetch partner: " . $e->getMessage());
        return null;
    }
}

/**
 * Protect partner-only routes; redirects unauthenticated visitors to partner login
 * 
 * @param string $redirectUrl Target to return to after successful login
 * @return array Authenticated partner data
 */
function requirePartnerAuth(string $redirectUrl = ''): array {
    if (!isPartnerLoggedIn()) {
        $loginUrl = getAppBasePath() . 'partner/login.php';
        if ($redirectUrl) {
            $loginUrl .= '?redirect=' . urlencode($redirectUrl);
        }
        header("Location: " . $loginUrl);
        exit;
    }

    $partner = getLoggedInPartner();
    if (!$partner) {
        logoutPartner();
        header("Location: " . getAppBasePath() . "partner/login.php?msg=session_invalid");
        exit;
    }

    return $partner;
}

/**
 * Authenticate partner by mobile number OR email and password
 * Enforces rate limiting, password verification, and status validation
 * 
 * @param string $identifier Mobile or Email
 * @param string $password Plain text password
 * @return array
 */
function loginPartner(string $identifier, string $password): array {
    initSecureSession();

    $identifier = trim($identifier);
    $password = trim($password);

    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'status' => 'missing_fields', 'message' => 'Please enter your registered mobile/email and password.'];
    }

    // Rate Limiting (5 attempts in 60s)
    if (!isset($_SESSION['partner_login_attempts'])) {
        $_SESSION['partner_login_attempts'] = 0;
        $_SESSION['partner_last_attempt'] = time();
    }
    if ($_SESSION['partner_login_attempts'] >= 5 && (time() - $_SESSION['partner_last_attempt']) < 60) {
        $remaining = 60 - (time() - $_SESSION['partner_last_attempt']);
        return [
            'success' => false,
            'status' => 'rate_limited',
            'message' => "Too many failed attempts. Please wait {$remaining} seconds before trying again."
        ];
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM service_providers WHERE email = ? OR mobile = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $partner = $stmt->fetch();

        if (!$partner || !password_verify($password, $partner['password_hash'])) {
            $_SESSION['partner_login_attempts']++;
            $_SESSION['partner_last_attempt'] = time();
            return [
                'success' => false,
                'status' => 'invalid_credentials',
                'message' => 'Invalid mobile number/email or password. Please verify your credentials.'
            ];
        }

        // Reset rate limiter on valid password
        $_SESSION['partner_login_attempts'] = 0;

        $verificationStatus = $partner['verification_status'] ?? 'Pending';

        // Check verification status
        if ($verificationStatus === 'Pending') {
            return [
                'success' => false,
                'status' => 'Pending',
                'message' => 'Your partner application is awaiting admin verification.',
                'partner' => [
                    'id' => $partner['id'],
                    'full_name' => $partner['full_name'],
                    'mobile' => $partner['mobile'],
                    'email' => $partner['email'],
                    'primary_skill' => $partner['primary_skill'],
                    'city' => $partner['city'],
                    'created_at' => $partner['created_at']
                ]
            ];
        }

        if ($verificationStatus === 'Rejected') {
            return [
                'success' => false,
                'status' => 'Rejected',
                'message' => 'Your partner application could not be approved.',
                'rejection_reason' => $partner['rejection_reason'] ?: 'Trade verification documents did not meet the standard.',
                'partner' => [
                    'id' => $partner['id'],
                    'full_name' => $partner['full_name'],
                    'mobile' => $partner['mobile'],
                    'email' => $partner['email'],
                    'primary_skill' => $partner['primary_skill'],
                    'city' => $partner['city']
                ]
            ];
        }

        // Approved partner: establish session and regenerate ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['partner_id'] = (int)$partner['id'];
        $_SESSION['partner_name'] = $partner['full_name'];
        $_SESSION['partner_mobile'] = $partner['mobile'];
        $_SESSION['partner_email'] = $partner['email'];
        $_SESSION['partner_skill'] = $partner['primary_skill'];
        $_SESSION['partner_city'] = $partner['city'];
        $_SESSION['partner_status'] = 'Approved';

        return [
            'success' => true,
            'status' => 'Approved',
            'message' => 'Login successful! Welcome back, ' . htmlspecialchars($partner['full_name']),
            'partner' => $partner
        ];
    } catch (Exception $e) {
        error_log("Partner Login DB Error: " . $e->getMessage());
        return ['success' => false, 'status' => 'error', 'message' => 'A system error occurred during partner login. Please try again.'];
    }
}

/**
 * Register a new service provider (Partner) with all 11 required fields
 * 
 * @param array $data Input form data
 * @return array
 */
function registerPartner(array $data): array {
    initSecureSession();

    $fullName     = trim($data['full_name'] ?? '');
    $mobile       = trim($data['mobile'] ?? '');
    $email        = trim($data['email'] ?? '');
    $gender       = trim($data['gender'] ?? 'Prefer not to say');
    $primarySkill = trim($data['primary_skill'] ?? '');
    $experience   = trim($data['experience_years'] ?? '');
    $city         = trim($data['city'] ?? '');
    $serviceArea  = trim($data['service_area'] ?? '');
    $address      = trim($data['address'] ?? '');
    $password     = $data['password'] ?? '';
    $confirmPass  = $data['confirm_password'] ?? '';
    $agreeTerms   = !empty($data['agree_terms']);

    $errors = [];

    // 1. Full legal name
    if (empty($fullName) || strlen($fullName) < 2) {
        $errors['full_name'] = 'Full legal name is required (minimum 2 characters).';
    }

    // 2. Mobile (10-digit Indian mobile)
    $cleanMobile = preg_replace('/[^\d]/', '', $mobile);
    if (!preg_match('/^[6-9]\d{9}$/', $cleanMobile)) {
        $errors['mobile'] = 'Please enter a valid 10-digit Indian mobile number.';
    } else {
        $mobile = $cleanMobile;
    }

    // 3. Email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // 4. Gender (optional)
    $allowedGenders = ['Male', 'Female', 'Other', 'Prefer not to say'];
    if (!in_array($gender, $allowedGenders, true)) {
        $gender = 'Prefer not to say';
    }

    // 5. Primary skill or trade
    if (empty($primarySkill)) {
        $errors['primary_skill'] = 'Please select your primary skill or trade.';
    }

    // 6. Years of experience
    if (empty($experience)) {
        $errors['experience_years'] = 'Please select your years of experience.';
    }

    // 7. City
    if (empty($city)) {
        $errors['city'] = 'Please select your primary city of service.';
    }

    // 8. Residential address
    if (empty($address) || strlen($address) < 5) {
        $errors['address'] = 'Please provide your residential address (minimum 5 characters).';
    }

    // 9. Password
    if (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    // 10. Confirm password
    if ($password !== $confirmPass) {
        $errors['confirm_password'] = 'Passwords do not match. Please verify.';
    }

    // 11. Terms and partner agreement
    if (!$agreeTerms) {
        $errors['agree_terms'] = 'You must accept the Terms and Conditions and KaamMitra Partner Agreement.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors];
    }

    try {
        $pdo = getDBConnection();

        // Check unique mobile
        $stmt = $pdo->prepare("SELECT id FROM service_providers WHERE mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'A partner account is already registered with this mobile number. Please sign in.',
                'errors' => ['mobile' => 'Mobile number already registered.']
            ];
        }

        // Check unique email
        $stmt = $pdo->prepare("SELECT id FROM service_providers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'A partner account is already registered with this email address. Please sign in.',
                'errors' => ['email' => 'Email address already registered.']
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $pdo->prepare("
            INSERT INTO service_providers 
            (full_name, mobile, email, gender, primary_skill, experience_years, city, service_area, address, password_hash, verification_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $insertStmt->execute([
            $fullName,
            $mobile,
            $email,
            $gender,
            $primarySkill,
            $experience,
            $city,
            $serviceArea ?: null,
            $address,
            $passwordHash
        ]);

        $partnerId = (int)$pdo->lastInsertId();

        return [
            'success' => true,
            'partner_id' => $partnerId,
            'status' => 'Pending',
            'message' => 'Registration submitted successfully! Your application is pending admin verification.',
            'partner' => [
                'id' => $partnerId,
                'full_name' => $fullName,
                'mobile' => $mobile,
                'email' => $email,
                'primary_skill' => $primarySkill,
                'city' => $city
            ],
            'errors' => []
        ];
    } catch (Exception $e) {
        error_log("Partner Registration DB Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred during partner registration. Please try again.', 'errors' => []];
    }
}

/**
 * Log out partner and clear partner session variables
 */
function logoutPartner(): void {
    initSecureSession();
    unset(
        $_SESSION['partner_id'],
        $_SESSION['partner_name'],
        $_SESSION['partner_mobile'],
        $_SESSION['partner_email'],
        $_SESSION['partner_skill'],
        $_SESSION['partner_city'],
        $_SESSION['partner_status']
    );
}

/* ============================================================================
   ADMINISTRATOR AUTHENTICATION & SECURITY MANAGEMENT
   ============================================================================ */

if (!defined('ADMIN_DEFAULT_ID')) {
    define('ADMIN_DEFAULT_ID', 'Charan');
}
if (!defined('ADMIN_DEFAULT_PASSWORD')) {
    define('ADMIN_DEFAULT_PASSWORD', 'Bharat');
}

/**
 * Check if an administrator is currently authenticated
 * 
 * @return bool
 */
function isAdminLoggedIn(): bool {
    initSecureSession();
    return !empty($_SESSION['admin_logged_in']) 
        && $_SESSION['admin_logged_in'] === true 
        && !empty($_SESSION['admin_id']) 
        && strcasecmp($_SESSION['admin_id'], ADMIN_DEFAULT_ID) === 0;
}

/**
 * Retrieve the authenticated admin details
 * 
 * @return array|null
 */
function getLoggedInAdmin(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['admin_id'] ?? ADMIN_DEFAULT_ID,
        'name' => $_SESSION['admin_name'] ?? ADMIN_DEFAULT_ID,
        'role' => $_SESSION['admin_role'] ?? 'Super Administrator',
        'login_time' => $_SESSION['admin_login_time'] ?? time(),
        'ip' => $_SESSION['admin_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')
    ];
}

/**
 * Protect admin-only routes; strictly redirects unauthenticated visitors
 * Ensures customers and service providers cannot access admin resources
 * 
 * @param string $redirectUrl Target to return to after successful login
 * @return array Authenticated admin data
 */
function requireAdminAuth(string $redirectUrl = ''): array {
    if (!isAdminLoggedIn()) {
        $loginUrl = getAppBasePath() . 'admin/index.php';
        if ($redirectUrl) {
            $loginUrl .= '?redirect=' . urlencode($redirectUrl);
        }
        header("Location: " . $loginUrl);
        exit;
    }

    $admin = getLoggedInAdmin();
    if (!$admin) {
        logoutAdmin();
        header("Location: " . getAppBasePath() . "admin/index.php?msg=session_invalid");
        exit;
    }

    return $admin;
}

/**
 * Authenticate administrator with dedicated Admin ID and Password
 * Strictly checks admin credentials. Customer and service provider credentials cannot log in here.
 * 
 * @param string $adminId
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function loginAdmin(string $adminId, string $password): array {
    initSecureSession();

    $adminId = trim($adminId);

    if (empty($adminId) || empty($password)) {
        return ['success' => false, 'message' => 'Please enter both Admin ID and Password.'];
    }

    // Rate limiting for admin login (max 5 attempts in 60s)
    if (!isset($_SESSION['admin_login_attempts'])) {
        $_SESSION['admin_login_attempts'] = 0;
        $_SESSION['admin_last_attempt'] = time();
    }
    if ($_SESSION['admin_login_attempts'] >= 5 && (time() - $_SESSION['admin_last_attempt']) < 60) {
        $remaining = 60 - (time() - $_SESSION['admin_last_attempt']);
        return [
            'success' => false,
            'message' => "Too many failed attempts. Security lock active for {$remaining} seconds."
        ];
    }

    // Verify Admin credentials: Admin ID must be Charan and Password Bharat
    $isValidId = (strcasecmp($adminId, ADMIN_DEFAULT_ID) === 0);
    $isValidPass = hash_equals(ADMIN_DEFAULT_PASSWORD, $password);

    if (!$isValidId || !$isValidPass) {
        $_SESSION['admin_login_attempts']++;
        $_SESSION['admin_last_attempt'] = time();
        return [
            'success' => false,
            'message' => 'Invalid Admin ID or Password. Access denied.'
        ];
    }

    // Reset attempts on successful login
    $_SESSION['admin_login_attempts'] = 0;

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    // Isolate admin session from customer and partner sessions
    unset(
        $_SESSION['customer_id'],
        $_SESSION['customer_name'],
        $_SESSION['customer_mobile'],
        $_SESSION['customer_email'],
        $_SESSION['customer_city'],
        $_SESSION['partner_id'],
        $_SESSION['partner_name'],
        $_SESSION['partner_mobile'],
        $_SESSION['partner_email'],
        $_SESSION['partner_skill'],
        $_SESSION['partner_city'],
        $_SESSION['partner_status']
    );

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = ADMIN_DEFAULT_ID;
    $_SESSION['admin_name'] = ADMIN_DEFAULT_ID;
    $_SESSION['admin_role'] = 'Super Administrator';
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    return [
        'success' => true,
        'message' => 'Admin authentication successful! Access granted.',
        'admin' => getLoggedInAdmin()
    ];
}

/**
 * Log out administrator and terminate admin session securely
 */
function logoutAdmin(): void {
    initSecureSession();
    unset(
        $_SESSION['admin_logged_in'],
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_role'],
        $_SESSION['admin_login_time'],
        $_SESSION['admin_ip']
    );
}

