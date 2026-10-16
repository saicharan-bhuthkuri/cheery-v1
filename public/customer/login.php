<?php
/**
 * KaamMitra - Customer Login Portal
 * Reorganized under /customer/ with shared reusable components
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
$pageTitle = 'Customer Login - ' . APP_NAME;
$assetPath = $base . 'assets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../components/head.php'; ?>
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
            background: var(--bg-surface);
            border-radius: var(--radius-xl);
            padding: 2.5rem 2.25rem;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-subtle);
            position: relative;
        }

        .auth-card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-segmented-pill {
            display: flex;
            background: var(--bg-surface-alt);
            padding: 0.35rem;
            border-radius: var(--radius-full);
            margin-bottom: 1.75rem;
            border: 1px solid var(--border-subtle);
        }

        .auth-segmented-tab {
            flex: 1;
            text-align: center;
            padding: 0.65rem 1rem;
            border-radius: var(--radius-full);
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .auth-segmented-tab.active {
            background: var(--bg-surface);
            color: var(--navy-900);
            box-shadow: var(--shadow-subtle);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 0.45rem;
            color: var(--navy-900);
        }

        .input-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .input-icon-wrap input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.85rem;
            border: 1.5px solid var(--border-subtle);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            color: var(--text-primary);
            background: #FFFFFF;
            transition: all var(--transition-fast);
            outline: none;
            font-family: inherit;
        }

        .input-icon-wrap input:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15);
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.85rem;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .trust-strip {
            display: flex;
            justify-content: space-around;
            padding-top: 1.75rem;
            margin-top: 1.75rem;
            border-top: 1px solid var(--border-subtle);
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.78rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>

    <!-- Main Floating Navbar Component -->
    <?php require_once __DIR__ . '/../components/navbar-main.php'; ?>

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

                <!-- Segmented Tab Switcher -->
                <div class="auth-segmented-pill">
                    <a href="<?= $base; ?>customer/login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="auth-segmented-tab active">
                        <i data-lucide="log-in" style="width: 15px; height: 15px;"></i>
                        <span>Sign In</span>
                    </a>
                    <a href="<?= $base; ?>customer/register.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="auth-segmented-tab">
                        <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                        <span>Create Account</span>
                    </a>
                </div>

                <!-- Alerts rendered via reusable alert component -->
                <?= renderAlert($error, 'error'); ?>
                <?= renderAlert($successMsg, 'success'); ?>

                <!-- Login Form -->
                <form action="<?= $base; ?>customer/login.php" method="POST" id="loginForm" novalidate>
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
                        <a href="<?= $base; ?>customer/register.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" style="color: var(--orange-500); font-weight: 800; text-decoration: none;">
                            Create Account
                        </a>
                    </p>
                </div>

                <!-- Brand Assurance Strip -->
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

    <!-- Main Footer Component -->
    <?php require_once __DIR__ . '/../components/footer-main.php'; ?>

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
