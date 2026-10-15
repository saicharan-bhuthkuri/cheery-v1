<?php
/**
 * KaamMitra - Customer Login
 * Redesigned to match index.php visual design system & brand aesthetic
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

initSecureSession();

// Sanitize redirect destination
$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if (!empty($redirect)) {
    if (preg_match('/^(https?:|\/\/)/i', $redirect)) {
        $redirect = 'dashboard.php';
    }
} else {
    $redirect = 'dashboard.php';
}

// If already logged in, redirect immediately
if (isCustomerLoggedIn()) {
    header("Location: " . $redirect);
    exit;
}

$error = '';
$successMsg = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logged_out') {
        $successMsg = 'You have logged out safely. See you again soon!';
    } elseif ($_GET['msg'] === 'auth_required') {
        $error = 'Please log in to your account to complete your booking.';
    } elseif ($_GET['msg'] === 'session_invalid') {
        $error = 'Your session has expired. Please sign in again.';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security check failed (CSRF). Please refresh the page and try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = loginCustomer($identifier, $password);
        if ($result['success']) {
            header("Location: " . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - <?= htmlspecialchars(APP_NAME); ?></title>
    
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/lucide.min.js"></script>
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
                radial-gradient(circle at 15% 15%, rgba(255, 102, 0, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.04) 0%, transparent 40%);
        }

        .auth-container {
            max-width: 480px;
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
        @media (max-width: 540px) {
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

        .form-group {
            margin-bottom: 1.25rem;
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

        .input-icon-wrap input {
            width: 100%;
            height: 48px;
            border-radius: 12px;
            border: 1.5px solid var(--border-subtle);
            background-color: #FFFFFF;
            padding-left: 2.75rem;
            padding-right: 2.75rem;
            font-size: 0.95rem;
            color: var(--navy-900);
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-icon-wrap input:focus {
            outline: none;
            border-color: var(--orange-500);
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15);
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

        .auth-alert-success {
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            color: #059669;
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

    <!-- =======================================================================
         FLOATING ISLAND NAVBAR (HEADER - IDENTICAL TO INDEX.PHP)
         ======================================================================= -->
    <div class="header-wrapper">
        <header class="site-navbar" id="siteHeader">
            <!-- Brand -->
            <div class="nav-brand-group">
                <a href="index.php" class="brand-link" aria-label="KaamMitra Home">
                    <div class="brand-mark">
                        <i data-lucide="wrench" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="brand-text-block">
                        <span class="brand-name">Kaam<span>Mitra</span></span>
                        <span class="brand-domain-tag">kaammitra.in</span>
                    </div>
                </a>

                <!-- Location Selector Pill -->
                <a href="index.php" class="nav-location-pill" style="text-decoration: none;" aria-label="Home city">
                    <span class="location-live-dot"></span>
                    <span>Mumbai, MH</span>
                    <i data-lucide="chevron-down" style="width: 14px; height: 14px;"></i>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="nav-desktop-menu">
                <ul class="nav-menu-links">
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#pledge">Rate Card</a></li>
                    <li><a href="index.php#standard">Why Us</a></li>
                    <li><a href="index.php#reviews">Reviews</a></li>
                </ul>
            </nav>

            <!-- Actions -->
            <div class="nav-actions">
                <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline btn-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i data-lucide="phone" style="width: 14px; height: 14px; color: var(--orange-500);"></i>
                    <span><?= APP_PHONE_DISPLAY; ?></span>
                </a>
                <a href="index.php" class="btn btn-ghost btn-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; color: var(--text-secondary);">
                    <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
                    <span>Back to Home</span>
                </a>
            </div>
        </header>
    </div>

    <!-- Main Auth Form Body -->
    <main class="auth-page-main">
        <div class="auth-container">
            <div class="auth-card">
                <!-- Header Badge & Heading -->
                <div class="auth-card-header">
                    <span class="section-badge" style="margin-bottom: 0.6rem;">
                        <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                        <span>Customer Authentication</span>
                    </span>
                    <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--navy-900); margin: 0 0 0.4rem; letter-spacing: -0.02em;">
                        Welcome Back
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">
                        Sign in to schedule verified Mitras and track active home bookings.
                    </p>
                </div>

                <!-- Segmented Tab Switcher (Preserves ?redirect=) -->
                <div class="auth-segmented-pill">
                    <a href="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="auth-segmented-tab active">
                        <i data-lucide="log-in" style="width: 15px; height: 15px;"></i>
                        <span>Sign In</span>
                    </a>
                    <a href="register.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="auth-segmented-tab">
                        <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                        <span>Create Account</span>
                    </a>
                </div>

                <!-- Error Alert -->
                <?php if (!empty($error)): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span><?= htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Success Alert -->
                <?php if (!empty($successMsg)): ?>
                    <div class="auth-alert auth-alert-success" role="alert">
                        <i data-lucide="check-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span><?= htmlspecialchars($successMsg); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="login.php" method="POST" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">

                    <!-- Identifier: Mobile or Email -->
                    <div class="form-group">
                        <label for="identifierInput">
                            Registered Mobile Number or Email
                        </label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="text" 
                                id="identifierInput" 
                                name="identifier" 
                                placeholder="10-digit mobile or your email"
                                value="<?= htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                                required 
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.78rem; margin-top: 0.35rem; display: block;">
                            e.g. 9820123456 or name@gmail.com
                        </small>
                    </div>

                    <!-- Password -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                            <label for="passwordInput" style="margin: 0;">Password</label>
                            <a href="javascript:void(0)" onclick="alert('Password reset instructions will be sent to your registered mobile or email.');" style="font-size: 0.8rem; color: var(--orange-500); text-decoration: none; font-weight: 700;">
                                Forgot?
                            </a>
                        </div>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="password" 
                                id="passwordInput" 
                                name="password" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">
                                <i data-lucide="eye" id="eyeIcon" style="width: 18px; height: 18px;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitLoginBtn" class="btn btn-primary btn-full" style="padding: 0.9rem; font-size: 1rem; font-weight: 700; border-radius: 12px; margin-bottom: 1.25rem;">
                        <span id="btnText">Sign In to KaamMitra</span>
                        <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                    </button>
                </form>

                <!-- Switch to Register -->
                <div style="text-align: center; border-top: 1px solid var(--border-subtle); padding-top: 1.25rem;">
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">
                        Don't have an account yet? 
                        <a href="register.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" style="color: var(--orange-500); font-weight: 800; text-decoration: none;">
                            Create Account
                        </a>
                    </p>
                </div>

                <!-- Brand Assurance Strip (Matches index.php guarantee section) -->
                <div class="trust-strip">
                    <div class="trust-item">
                        <i data-lucide="clock" style="width: 16px; height: 16px; color: var(--orange-500);"></i>
                        <strong>30 Min Express</strong>
                    </div>
                    <div class="trust-item">
                        <i data-lucide="shield-check" style="width: 16px; height: 16px; color: #10B981;"></i>
                        <strong>100% Verified</strong>
                    </div>
                    <div class="trust-item">
                        <i data-lucide="badge-check" style="width: 16px; height: 16px; color: var(--navy-700);"></i>
                        <strong>30-Day Warranty</strong>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Matching index.php -->
    <footer style="background: #FFFFFF; border-top: 1px solid var(--border-subtle); padding: 1.5rem 0; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
        <div style="max-width: 1260px; margin: 0 auto; padding: 0 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                &copy; <?= date('Y'); ?> KaamMitra Services India Pvt. Ltd. All rights reserved.
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <a href="index.php" style="color: var(--text-secondary); text-decoration: none;">Home</a>
                <a href="tel:<?= APP_PHONE; ?>" style="color: var(--text-secondary); text-decoration: none;">Support: <?= APP_PHONE_DISPLAY; ?></a>
                <a href="register.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" style="color: var(--orange-500); font-weight: 700; text-decoration: none;">Sign Up</a>
            </div>
        </div>
    </footer>

    <script>
        if (window.lucide) {
            lucide.createIcons();
        }

        // Toggle Password Visibility
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const pwdInput = document.getElementById('passwordInput');

        if (toggleBtn && pwdInput) {
            toggleBtn.addEventListener('click', () => {
                const isPassword = pwdInput.type === 'password';
                pwdInput.type = isPassword ? 'text' : 'password';
                toggleBtn.innerHTML = isPassword 
                    ? '<i data-lucide="eye-off" style="width: 18px; height: 18px;"></i>' 
                    : '<i data-lucide="eye" style="width: 18px; height: 18px;"></i>';
                if (window.lucide) lucide.createIcons();
            });
        }

        // Client-side Validation & Loading State
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitLoginBtn');
        const btnText = document.getElementById('btnText');

        if (form) {
            form.addEventListener('submit', (e) => {
                const idVal = document.getElementById('identifierInput').value.trim();
                const passVal = document.getElementById('passwordInput').value.trim();

                if (!idVal || !passVal) {
                    e.preventDefault();
                    alert('Please enter both your mobile/email and password.');
                    return;
                }

                submitBtn.style.pointerEvents = 'none';
                submitBtn.style.opacity = '0.85';
                btnText.textContent = 'Verifying credentials...';
            });
        }
    </script>
</body>
</html>
