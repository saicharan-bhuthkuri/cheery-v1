<?php
/**
 * KaamMitra - Authentication & Session Security Handler
 * Manages customer and service-provider registration, authentication, sessions, and terms acceptance.
 */

require_once __DIR__ . '/db.php';

/**
 * Initialize secure PHP session with hardened cookie parameters
 */
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

/**
 * Generate or retrieve CSRF token
 */
function getCsrfToken(): string {
    initSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken(?string $token): bool {
    initSecureSession();
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/* ==========================================================================
   CUSTOMER AUTHENTICATION & REGISTRATION
   ========================================================================== */

/**
 * Check if a customer is currently authenticated
 */
function isCustomerLoggedIn(): bool {
    initSecureSession();
    return !empty($_SESSION['customer_id']) && is_numeric($_SESSION['customer_id']);
}

/**
 * Get logged-in customer record
 */
function getLoggedInCustomer(): ?array {
    if (!isCustomerLoggedIn()) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        if (!$pdo) return null;

        $stmt = $pdo->prepare("SELECT id, full_name, mobile, email, gender, address_line, city, state, pincode, terms_accepted, terms_accepted_at, created_at FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['customer_id']]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        error_log("Failed to fetch customer: " . $e->getMessage());
        return null;
    }
}

/**
 * Authenticate customer by mobile number or email
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
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection unavailable. Please try again shortly.'];
        }

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? OR mobile = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $customer = $stmt->fetch();

        if (!$customer || !password_verify($password, $customer['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid mobile/email or password. Please verify your credentials.'];
        }

        session_regenerate_id(true);

        $_SESSION['customer_id'] = (int)$customer['id'];
        $_SESSION['customer_name'] = $customer['full_name'];
        $_SESSION['customer_mobile'] = $customer['mobile'];
        $_SESSION['customer_email'] = $customer['email'];

        return [
            'success' => true,
            'message' => 'Login successful! Welcome back, ' . htmlspecialchars($customer['full_name']),
            'customer' => $customer
        ];
    } catch (Throwable $e) {
        error_log("Customer Login Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A system error occurred during login. Please try again.'];
    }
}

/**
 * Register a new customer in the SQL database
 * 
 * @param array $data Input form data
 * @return array ['success' => bool, 'message' => string, 'errors' => array, 'customer_id' => int|null]
 */
function registerCustomer(array $data): array {
    initSecureSession();

    $fullName    = trim($data['full_name'] ?? '');
    $mobile      = trim($data['mobile'] ?? '');
    $email       = trim($data['email'] ?? '');
    $password    = $data['password'] ?? '';
    $confirmPass = $data['confirm_password'] ?? '';
    $agreeTerms  = !empty($data['agree_terms']);

    $errors = [];

    // 1. Full Legal Name
    if (empty($fullName) || mb_strlen($fullName) < 2) {
        $errors['full_name'] = 'Full legal name is required (minimum 2 characters).';
    }

    // 2. 10-digit Indian Mobile Number
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

    // 4. Password validation
    if (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    // 5. Confirm password
    if ($password !== $confirmPass) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // 6. Backend Terms and Conditions validation
    if (!$agreeTerms) {
        $errors['agree_terms'] = 'You must agree to the Terms and Conditions and Privacy Policy.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors];
    }

    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection unavailable. Please try again shortly.', 'errors' => []];
        }

        // Check duplicate mobile
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'An account is already registered with this mobile number. Please sign in.',
                'errors' => ['mobile' => 'Mobile number already registered.']
            ];
        }

        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'An account is already registered with this email address. Please sign in.',
                'errors' => ['email' => 'Email address already registered.']
            ];
        }

        // Hash password securely
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $acceptedAt = date('Y-m-d H:i:s');

        $insertStmt = $pdo->prepare("
            INSERT INTO customers 
            (full_name, mobile, email, password_hash, terms_accepted, terms_accepted_at)
            VALUES (?, ?, ?, ?, 1, ?)
        ");
        $insertStmt->execute([
            $fullName,
            $mobile,
            $email,
            $passwordHash,
            $acceptedAt
        ]);

        $customerId = (int)$pdo->lastInsertId();

        // Establish session
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['customer_name'] = $fullName;
        $_SESSION['customer_mobile'] = $mobile;
        $_SESSION['customer_email'] = $email;

        return [
            'success' => true,
            'customer_id' => $customerId,
            'message' => 'Registration successful! Welcome to KaamMitra.',
            'errors' => []
        ];
    } catch (Throwable $e) {
        error_log("Customer Registration DB Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred during registration. Please try again.', 'errors' => []];
    }
}

/**
 * Log out customer
 */
function logoutCustomer(): void {
    initSecureSession();
    unset(
        $_SESSION['customer_id'],
        $_SESSION['customer_name'],
        $_SESSION['customer_mobile'],
        $_SESSION['customer_email']
    );
}

/* ==========================================================================
   SERVICE PROVIDER (PARTNER) AUTHENTICATION & REGISTRATION
   ========================================================================== */

/**
 * Check if a provider is currently authenticated
 */
function isPartnerLoggedIn(): bool {
    initSecureSession();
    return !empty($_SESSION['partner_id']) && is_numeric($_SESSION['partner_id']);
}

/**
 * Get logged-in provider record
 */
function getLoggedInPartner(): ?array {
    if (!isPartnerLoggedIn()) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        if (!$pdo) return null;

        $stmt = $pdo->prepare("SELECT id, full_name, mobile, email, primary_skill, experience_years, city, service_area, address, verification_status, rejection_reason, terms_accepted, terms_accepted_at, created_at FROM service_providers WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['partner_id']]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        error_log("Failed to fetch partner: " . $e->getMessage());
        return null;
    }
}

/**
 * Authenticate service provider
 */
function loginPartner(string $identifier, string $password): array {
    initSecureSession();

    $identifier = trim($identifier);
    $password = trim($password);

    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'status' => 'invalid_input', 'message' => 'Please provide your registered mobile/email and password.'];
    }

    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return ['success' => false, 'status' => 'db_error', 'message' => 'Database connection unavailable. Please try again shortly.'];
        }

        $stmt = $pdo->prepare("SELECT * FROM service_providers WHERE email = ? OR mobile = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $partner = $stmt->fetch();

        if (!$partner || !password_verify($password, $partner['password_hash'])) {
            return ['success' => false, 'status' => 'invalid_credentials', 'message' => 'Invalid mobile/email or password. Please verify your credentials.'];
        }

        $verificationStatus = $partner['verification_status'] ?? 'Pending';

        // Check verification status
        if ($verificationStatus === 'Pending') {
            return [
                'success' => false,
                'status' => 'Pending',
                'message' => 'Your service provider application is awaiting admin verification. You will be notified once verified and approved.',
                'partner' => [
                    'id' => $partner['id'],
                    'full_name' => $partner['full_name'],
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
                'message' => 'Your application was not approved.',
                'rejection_reason' => $partner['rejection_reason'] ?: 'Trade verification documents did not meet the required standard.',
                'partner' => [
                    'id' => $partner['id'],
                    'full_name' => $partner['full_name']
                ]
            ];
        }

        // Approved partner
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
            'message' => 'Welcome back, ' . htmlspecialchars($partner['full_name']),
            'partner' => $partner
        ];
    } catch (Throwable $e) {
        error_log("Partner Login Error: " . $e->getMessage());
        return ['success' => false, 'status' => 'error', 'message' => 'A system error occurred during login. Please try again.'];
    }
}

/**
 * Register a new service provider (Partner)
 * Saves details to SQL with 'Pending' verification status by default.
 * 
 * @param array $data Input form data
 * @return array
 */
function registerPartner(array $data): array {
    initSecureSession();

    $fullName     = trim($data['full_name'] ?? '');
    $mobile       = trim($data['mobile'] ?? '');
    $email        = trim($data['email'] ?? '');
    $primarySkill = trim($data['primary_skill'] ?? '');
    $experience   = trim($data['experience_years'] ?? '');
    $city         = trim($data['city'] ?? '');
    $serviceArea  = trim($data['service_area'] ?? '');
    $password     = $data['password'] ?? '';
    $confirmPass  = $data['confirm_password'] ?? '';
    $agreeTerms   = !empty($data['agree_terms']);

    $errors = [];

    // 1. Full Name
    if (empty($fullName) || mb_strlen($fullName) < 2) {
        $errors['full_name'] = 'Full legal name is required (minimum 2 characters).';
    }

    // 2. 10-digit Indian Mobile
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

    // 4. Primary Skill
    if (empty($primarySkill)) {
        $errors['primary_skill'] = 'Please select your primary skill or trade.';
    }

    // 5. Experience
    if (empty($experience)) {
        $errors['experience_years'] = 'Please select your years of experience.';
    }

    // 6. City
    if (empty($city)) {
        $errors['city'] = 'Please enter or select your city.';
    }

    // 7. Service Area
    if (empty($serviceArea)) {
        $errors['service_area'] = 'Please enter your preferred service area or locality.';
    }

    // 8. Password
    if (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    // 9. Confirm Password
    if ($password !== $confirmPass) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // 10. Terms and Conditions validation
    if (!$agreeTerms) {
        $errors['agree_terms'] = 'You must agree to the Terms and Conditions and Privacy Policy.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors];
    }

    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection unavailable. Please try again shortly.', 'errors' => []];
        }

        // Check duplicate mobile
        $stmt = $pdo->prepare("SELECT id FROM service_providers WHERE mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'A service provider account is already registered with this mobile number. Please sign in.',
                'errors' => ['mobile' => 'Mobile number already registered.']
            ];
        }

        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM service_providers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'A service provider account is already registered with this email address. Please sign in.',
                'errors' => ['email' => 'Email address already registered.']
            ];
        }

        // Hash password securely
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $acceptedAt = date('Y-m-d H:i:s');

        // New providers are saved with 'Pending' verification status by default
        $insertStmt = $pdo->prepare("
            INSERT INTO service_providers 
            (full_name, mobile, email, primary_skill, experience_years, city, service_area, password_hash, verification_status, terms_accepted, terms_accepted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 1, ?)
        ");
        $insertStmt->execute([
            $fullName,
            $mobile,
            $email,
            $primarySkill,
            $experience,
            $city,
            $serviceArea,
            $passwordHash,
            $acceptedAt
        ]);

        $partnerId = (int)$pdo->lastInsertId();

        return [
            'success' => true,
            'partner_id' => $partnerId,
            'status' => 'Pending',
            'message' => 'Your registration as a Service Provider has been submitted! Your application is in Pending verification status.',
            'partner' => [
                'id' => $partnerId,
                'full_name' => $fullName,
                'mobile' => $mobile,
                'email' => $email,
                'primary_skill' => $primarySkill,
                'city' => $city,
                'service_area' => $serviceArea
            ],
            'errors' => []
        ];
    } catch (Throwable $e) {
        error_log("Partner Registration DB Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred during registration. Please try again.', 'errors' => []];
    }
}

/**
 * Log out partner
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
