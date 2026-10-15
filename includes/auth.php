<?php
/**
 * KaamMitra - Authentication & Session Security Handler
 */

require_once __DIR__ . '/db.php';

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
        $loginUrl = 'login.php';
        if ($redirectUrl) {
            $loginUrl .= '?redirect=' . urlencode($redirectUrl);
        }
        header("Location: " . $loginUrl);
        exit;
    }

    $customer = getLoggedInCustomer();
    if (!$customer) {
        logoutCustomer();
        header("Location: login.php?msg=session_invalid");
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
