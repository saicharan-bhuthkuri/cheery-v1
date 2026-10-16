<?php
/**
 * KaamMitra - Customer Registration
 * Redesigned to match index.php visual design system & brand aesthetic
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/alert.php';

initSecureSession();

$base = getAppBasePath();

// Sanitize redirect destination
$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if (!empty($redirect)) {
    if (preg_match('/^(https?:|\/\/)/i', $redirect)) {
        $redirect = $base . 'customer/dashboard.php';
    }
} else {
    $redirect = $base . 'customer/dashboard.php';
}

// If already logged in, redirect to dashboard or intended target
if (isCustomerLoggedIn()) {
    header("Location: " . $redirect);
    exit;
}

$errors = [];
$topError = '';
$formData = [
    'full_name' => '',
    'mobile' => '',
    'email' => '',
    'gender' => 'Prefer not to say',
    'address_line' => '',
    'city' => 'Mumbai',
    'state' => 'Maharashtra',
    'pincode' => '',
    'agree_terms' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $topError = 'Security validation failed (CSRF). Please refresh and submit again.';
    } else {
        $formData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'mobile' => trim($_POST['mobile'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'gender' => trim($_POST['gender'] ?? 'Prefer not to say'),
            'address_line' => trim($_POST['address_line'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => trim($_POST['state'] ?? ''),
            'pincode' => trim($_POST['pincode'] ?? ''),
            'agree_terms' => !empty($_POST['agree_terms'])
        ];

        $registerPayload = array_merge($formData, [
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ]);

        $result = registerCustomer($registerPayload);
        if ($result['success']) {
            // New customer is automatically logged in; redirect to target
            header("Location: " . $redirect);
            exit;
        } else {
            $topError = $result['message'];
            $errors = $result['errors'] ?? [];
        }
    }
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $pageTitle = 'Customer Registration - ' . APP_NAME;
    $assetPath = $base . 'assets';
    require_once __DIR__ . '/../components/head.php'; 
    ?>
    <style>
        body {
            background-color: var(--bg-page);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-primary);
            font-family: var(--font-body);
        }

        .auth-page-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.25rem 4rem;
            position: relative;
            background: 
                radial-gradient(circle at 10% 10%, rgba(255, 102, 0, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(16, 185, 129, 0.04) 0%, transparent 40%);
        }

        .register-container {
            max-width: 680px;
            width: 100%;
            margin: 0 auto;
        }

        .auth-card {
            background: #FFFFFF;
            border-radius: 24px;
            padding: 2.5rem 2.25rem;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-subtle);
            transition: box-shadow 0.2s ease;
        }
        @media (max-width: 600px) {
            .auth-card {
                padding: 1.75rem 1.25rem;
                border-radius: 20px;
            }
        }

        .auth-card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-segmented-pill {
            display: flex;
            background: var(--gray-100);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 1.75rem;
            border: 1px solid var(--gray-200);
        }

        .auth-segmented-tab {
            flex: 1;
            padding: 0.65rem 1rem;
            text-align: center;
            font-size: 0.88rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            color: var(--text-secondary);
            border: none;
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .auth-segmented-tab.active {
            background: #FFFFFF;
            color: var(--navy-900);
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(10, 19, 37, 0.08);
        }

        .form-section-header {
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--orange-500);
            margin: 1.75rem 0 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 0.4rem;
        }
        .form-section-header:first-of-type {
            margin-top: 0;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 600px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
                gap: 0.85rem;
            }
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: 1.2fr 1fr 0.8fr;
            gap: 1rem;
        }
        @media (max-width: 600px) {
            .form-grid-3 {
                grid-template-columns: 1fr;
                gap: 0.85rem;
            }
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--navy-900);
            margin-bottom: 0.45rem;
        }

        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap input,
        .input-icon-wrap select {
            width: 100%;
            height: 48px;
            border-radius: 12px;
            border: 1.5px solid var(--border-subtle);
            background-color: #FFFFFF;
            padding-left: 2.75rem;
            padding-right: 2.75rem;
            font-size: 0.92rem;
            color: var(--navy-900);
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-icon-wrap input:focus,
        .input-icon-wrap select:focus {
            outline: none;
            border-color: var(--orange-500);
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15);
        }

        .input-icon-wrap.has-error input,
        .input-icon-wrap.has-error select {
            border-color: #EF4444;
            background-color: #FEF2F2;
        }

        .input-icon-left {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .input-icon-wrap input:focus + .input-icon-left,
        .input-icon-wrap:focus-within .input-icon-left {
            color: var(--orange-500);
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.35rem;
            display: flex;
            align-items: center;
            border-radius: 6px;
        }

        .password-toggle-btn:hover {
            color: var(--orange-500);
        }

        .field-error-msg {
            color: #DC2626;
            font-size: 0.75rem;
            margin-top: 0.3rem;
            display: block;
            font-weight: 600;
        }

        .auth-alert {
            padding: 0.85rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .auth-alert-error {
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #DC2626;
        }

        .terms-box {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 0.9rem 1rem;
            margin-bottom: 1.5rem;
        }

        .trust-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-top: 1.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-subtle);
            text-align: center;
        }
        @media (max-width: 480px) {
            .trust-strip {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: left;
            }
        }

        .trust-item {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }
        @media (max-width: 480px) {
            .trust-item {
                flex-direction: row;
                gap: 0.4rem;
            }
        }
    </style>
</head>
<body>

    <!-- Main Floating Navbar Component -->
    <?php require_once __DIR__ . '/../components/navbar-main.php'; ?>

    <!-- Main Registration Body -->
    <main class="auth-page-main">
        <div class="register-container">
            <div class="auth-card">
                <!-- Header Badge & Heading -->
                <div class="auth-card-header">
                    <span class="section-badge" style="margin-bottom: 0.6rem;">
                        <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
                        <span>Customer Registration</span>
                    </span>
                    <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--navy-900); margin: 0 0 0.4rem; letter-spacing: -0.02em;">
                        Create Your Customer Account
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">
                        Join 50,000+ homeowners booking verified electricians, plumbers, and home experts.
                    </p>
                </div>

                <!-- Segmented Tab Switcher (Preserves ?redirect=) -->
                <div class="auth-segmented-pill">
                    <a href="<?= $base; ?>customer/login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="auth-segmented-tab">
                        <i data-lucide="log-in" style="width: 15px; height: 15px;"></i>
                        <span>Sign In</span>
                    </a>
                    <a href="<?= $base; ?>customer/register.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="auth-segmented-tab active">
                        <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                        <span>Create Account</span>
                    </a>
                </div>

                <!-- Top Error Alert -->
                <?= renderAlert($topError, 'error'); ?>

                <form action="<?= $base; ?>customer/register.php" method="POST" id="registerForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">

                    <!-- 1. PERSONAL INFORMATION -->
                    <div class="form-section-header">
                        <i data-lucide="user" style="width: 15px; height: 15px;"></i>
                        <span>1. Personal Information</span>
                    </div>

                    <div class="form-grid-2">
                        <!-- 1. Full Name -->
                        <div class="form-group">
                            <label for="fullNameInput">
                                Full Legal Name <span style="color: #EF4444;">*</span>
                            </label>
                            <div class="input-icon-wrap <?= isset($errors['full_name']) ? 'has-error' : ''; ?>">
                                <span class="input-icon-left"><i data-lucide="user" style="width: 16px; height: 16px;"></i></span>
                                <input 
                                    type="text" 
                                    id="fullNameInput" 
                                    name="full_name" 
                                    placeholder="e.g. Rahul Sharma" 
                                    value="<?= htmlspecialchars($formData['full_name']); ?>"
                                    required
                                >
                            </div>
                            <?php if (isset($errors['full_name'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['full_name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- 2. Mobile Number -->
                        <div class="form-group">
                            <label for="mobileInput">
                                Mobile Number <span style="color: #EF4444;">*</span>
                            </label>
                            <div class="input-icon-wrap <?= isset($errors['mobile']) ? 'has-error' : ''; ?>">
                                <span class="input-icon-left" style="font-weight: 700; font-size: 0.85rem; color: var(--navy-700);">+91</span>
                                <input 
                                    type="tel" 
                                    id="mobileInput" 
                                    name="mobile" 
                                    placeholder="10-digit number" 
                                    maxlength="10"
                                    value="<?= htmlspecialchars($formData['mobile']); ?>"
                                    required
                                >
                            </div>
                            <?php if (isset($errors['mobile'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['mobile']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <!-- 3. Email Address -->
                        <div class="form-group">
                            <label for="emailInput">
                                Email Address <span style="color: #EF4444;">*</span>
                            </label>
                            <div class="input-icon-wrap <?= isset($errors['email']) ? 'has-error' : ''; ?>">
                                <span class="input-icon-left"><i data-lucide="mail" style="width: 16px; height: 16px;"></i></span>
                                <input 
                                    type="email" 
                                    id="emailInput" 
                                    name="email" 
                                    placeholder="e.g. rahul@gmail.com" 
                                    value="<?= htmlspecialchars($formData['email']); ?>"
                                    required
                                >
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['email']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- 4. Gender Selection -->
                        <div class="form-group">
                            <label for="genderSelect">
                                Gender Selection
                            </label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="users" style="width: 16px; height: 16px;"></i></span>
                                <select id="genderSelect" name="gender">
                                    <option value="Prefer not to say" <?= $formData['gender'] === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                    <option value="Male" <?= $formData['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?= $formData['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?= $formData['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 2. SECURITY & PASSWORDS -->
                    <div class="form-section-header">
                        <i data-lucide="lock" style="width: 15px; height: 15px;"></i>
                        <span>2. Account Security</span>
                    </div>

                    <div class="form-grid-2">
                        <!-- 5. Password -->
                        <div class="form-group">
                            <label for="regPassword">
                                Create Password <span style="color: #EF4444;">*</span>
                            </label>
                            <div class="input-icon-wrap <?= isset($errors['password']) ? 'has-error' : ''; ?>">
                                <span class="input-icon-left"><i data-lucide="key" style="width: 16px; height: 16px;"></i></span>
                                <input 
                                    type="password" 
                                    id="regPassword" 
                                    name="password" 
                                    placeholder="Min 6 characters" 
                                    required
                                >
                                <button type="button" class="password-toggle-btn toggle-pwd-btn" data-target="regPassword" aria-label="Toggle password">
                                    <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- 6. Confirm Password -->
                        <div class="form-group">
                            <label for="regConfirmPassword">
                                Confirm Password <span style="color: #EF4444;">*</span>
                            </label>
                            <div class="input-icon-wrap <?= isset($errors['confirm_password']) ? 'has-error' : ''; ?>">
                                <span class="input-icon-left"><i data-lucide="shield-check" style="width: 16px; height: 16px;"></i></span>
                                <input 
                                    type="password" 
                                    id="regConfirmPassword" 
                                    name="confirm_password" 
                                    placeholder="Re-enter password" 
                                    required
                                >
                                <button type="button" class="password-toggle-btn toggle-pwd-btn" data-target="regConfirmPassword" aria-label="Toggle confirm password">
                                    <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['confirm_password']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 3. SERVICE ADDRESS -->
                    <div class="form-section-header">
                        <i data-lucide="map-pin" style="width: 15px; height: 15px;"></i>
                        <span>3. Doorstep Service Address <span style="font-weight: 500; text-transform: none; color: var(--text-muted);">(Optional now)</span></span>
                    </div>

                    <!-- 7. House number, street & locality -->
                    <div class="form-group">
                        <label for="addressInput">
                            House No., Street &amp; Locality
                        </label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left"><i data-lucide="home" style="width: 16px; height: 16px;"></i></span>
                            <input 
                                type="text" 
                                id="addressInput" 
                                name="address_line" 
                                placeholder="e.g. Flat 304, Green Heights, Andheri West" 
                                value="<?= htmlspecialchars($formData['address_line']); ?>"
                            >
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <!-- 8. City -->
                        <div class="form-group">
                            <label for="citySelect">
                                City
                            </label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="map-pin" style="width: 16px; height: 16px;"></i></span>
                                <select id="citySelect" name="city">
                                    <?php foreach ($AVAILABLE_CITIES as $city): ?>
                                        <option value="<?= htmlspecialchars($city); ?>" <?= $formData['city'] === $city ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- 9. State -->
                        <div class="form-group">
                            <label for="stateInput">
                                State
                            </label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="compass" style="width: 16px; height: 16px;"></i></span>
                                <input 
                                    type="text" 
                                    id="stateInput" 
                                    name="state" 
                                    placeholder="e.g. Maharashtra" 
                                    value="<?= htmlspecialchars($formData['state']); ?>"
                                >
                            </div>
                        </div>

                        <!-- 10. PIN Code -->
                        <div class="form-group">
                            <label for="pincodeInput">
                                PIN Code
                            </label>
                            <div class="input-icon-wrap <?= isset($errors['pincode']) ? 'has-error' : ''; ?>">
                                <span class="input-icon-left"><i data-lucide="hash" style="width: 16px; height: 16px;"></i></span>
                                <input 
                                    type="text" 
                                    id="pincodeInput" 
                                    name="pincode" 
                                    placeholder="6 digits" 
                                    maxlength="6"
                                    value="<?= htmlspecialchars($formData['pincode']); ?>"
                                >
                            </div>
                            <?php if (isset($errors['pincode'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['pincode']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Terms & Conditions Checkbox -->
                    <div class="terms-box">
                        <label style="display: flex; align-items: flex-start; gap: 0.65rem; font-size: 0.86rem; color: var(--text-secondary); cursor: pointer; margin: 0;">
                            <input 
                                type="checkbox" 
                                name="agree_terms" 
                                value="1" 
                                id="agreeTerms" 
                                <?= !empty($formData['agree_terms']) ? 'checked' : ''; ?>
                                style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--orange-500); cursor: pointer;" 
                                required
                            >
                            <span>
                                I accept KaamMitra's <a href="javascript:void(0)" onclick="alert('KaamMitra Terms of Service: Standard consumer booking terms apply with 30-day warranty.');" style="color: var(--orange-500); font-weight: 700; text-decoration: none;">Terms of Service</a> and <a href="javascript:void(0)" onclick="alert('KaamMitra Privacy Policy: Your personal information is encrypted and strictly used for booking fulfillment.');" style="color: var(--orange-500); font-weight: 700; text-decoration: none;">Privacy Policy</a>.
                            </span>
                        </label>
                        <?php if (isset($errors['agree_terms'])): ?>
                            <span class="field-error-msg" style="margin-top: 0.4rem;"><?= htmlspecialchars($errors['agree_terms']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitRegisterBtn" class="btn btn-primary btn-full" style="padding: 0.95rem; font-size: 1rem; font-weight: 700; border-radius: 12px; margin-bottom: 1.25rem;">
                        <span id="regBtnText">Create My KaamMitra Account</span>
                        <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                    </button>
                </form>

                <!-- Switch to Login -->
                <div style="text-align: center; border-top: 1px solid var(--border-subtle); padding-top: 1.25rem;">
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">
                        Already registered with KaamMitra? 
                        <a href="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" style="color: var(--orange-500); font-weight: 800; text-decoration: none;">
                            Sign In Here
                        </a>
                    </p>
                </div>

                <!-- Brand Assurance Strip -->
                <div class="trust-strip">
                    <div class="trust-item">
                        <i data-lucide="shield-check" style="width: 16px; height: 16px; color: #10B981;"></i>
                        <strong>100% Background Verified</strong>
                    </div>
                    <div class="trust-item">
                        <i data-lucide="badge-check" style="width: 16px; height: 16px; color: var(--orange-500);"></i>
                        <strong>30-Day Service Warranty</strong>
                    </div>
                    <div class="trust-item">
                        <i data-lucide="wallet" style="width: 16px; height: 16px; color: #0284C7;"></i>
                        <strong>Zero Advance Payment</strong>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Main Footer Component -->
    <?php require_once __DIR__ . '/../components/footer-main.php'; ?>

    <script>
        if (window.lucide) {
            lucide.createIcons();
        }

        // Password visibility toggle handler
        document.querySelectorAll('.toggle-pwd-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.innerHTML = isPassword 
                    ? '<i data-lucide="eye-off" style="width: 16px; height: 16px;"></i>' 
                    : '<i data-lucide="eye" style="width: 16px; height: 16px;"></i>';
                if (window.lucide) lucide.createIcons();
            });
        });

        // Client-side instant validation
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitRegisterBtn');
        const btnText = document.getElementById('regBtnText');

        if (form) {
            form.addEventListener('submit', (e) => {
                const fullName = document.getElementById('fullNameInput').value.trim();
                const mobile = document.getElementById('mobileInput').value.trim();
                const email = document.getElementById('emailInput').value.trim();
                const pwd = document.getElementById('regPassword').value;
                const confirmPwd = document.getElementById('regConfirmPassword').value;
                const agree = document.getElementById('agreeTerms').checked;

                if (fullName.length < 2) {
                    e.preventDefault();
                    alert('Please enter your full legal name (minimum 2 characters).');
                    return;
                }

                const cleanMobile = mobile.replace(/\D/g, '');
                if (!/^[6-9]\d{9}$/.test(cleanMobile)) {
                    e.preventDefault();
                    alert('Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.');
                    return;
                }

                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                    return;
                }

                if (pwd.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                    return;
                }

                if (pwd !== confirmPwd) {
                    e.preventDefault();
                    alert('Passwords do not match. Please verify.');
                    return;
                }

                if (!agree) {
                    e.preventDefault();
                    alert('Please accept the Terms of Service and Privacy Policy to continue.');
                    return;
                }

                submitBtn.style.pointerEvents = 'none';
                submitBtn.style.opacity = '0.85';
                btnText.textContent = 'Creating your account...';
            });
        }
    </script>
</body>
</html>
