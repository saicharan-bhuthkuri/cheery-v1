<?php
/**
 * KaamMitra - Customer Login & Registration
 * Provides tabbed Login and Register options with required terms acceptance and destination preservation.
 */

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

initSecureSession();

// Sanitize redirect target
$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if (!empty($redirect)) {
    // Only allow local redirects
    if (preg_match('/^(https?:|\/\/)/i', $redirect)) {
        $redirect = '../../index.php#services';
    } elseif (!str_starts_with($redirect, '../') && !str_starts_with($redirect, '/')) {
        $redirect = '../../' . $redirect;
    }
} else {
    $redirect = '../../index.php#services';
}

// If already logged in, redirect directly to target destination
if (isCustomerLoggedIn()) {
    header("Location: " . $redirect);
    exit;
}

$activeTab = trim($_GET['tab'] ?? $_POST['tab'] ?? 'login');
if ($activeTab !== 'register') {
    $activeTab = 'login';
}

$loginError = '';
$registerErrors = [];
$registerTopError = '';
$registerSuccessMsg = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        if ($action === 'register') {
            $registerTopError = 'Security validation failed (CSRF). Please refresh and submit again.';
            $activeTab = 'register';
        } else {
            $loginError = 'Security validation failed (CSRF). Please refresh and submit again.';
            $activeTab = 'login';
        }
    } else {
        if ($action === 'login') {
            $activeTab = 'login';
            $identifier = trim($_POST['identifier'] ?? '');
            $password   = $_POST['password'] ?? '';

            $result = loginCustomer($identifier, $password);
            if ($result['success']) {
                header("Location: " . $redirect);
                exit;
            } else {
                $loginError = $result['message'];
            }
        } elseif ($action === 'register') {
            $activeTab = 'register';
            $payload = [
                'full_name'        => trim($_POST['full_name'] ?? ''),
                'mobile'           => trim($_POST['mobile'] ?? ''),
                'email'            => trim($_POST['email'] ?? ''),
                'password'         => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? '',
                'agree_terms'      => !empty($_POST['agree_terms'])
            ];

            $result = registerCustomer($payload);
            if ($result['success']) {
                // Customer registered and session established; redirect to intended destination
                header("Location: " . $redirect);
                exit;
            } else {
                $registerTopError = $result['message'];
                $registerErrors = $result['errors'] ?? [];
            }
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
    <title>Customer Login &amp; Registration - <?= htmlspecialchars(APP_NAME); ?></title>
    
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dark-mode.css">
    <script src="../../assets/js/lucide.min.js"></script>
    <style>
        body {
            background-color: var(--bg-page, #F8FAFC);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-primary, #090D16);
            font-family: var(--font-body, system-ui, -apple-system, sans-serif);
        }

        .auth-main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.25rem;
        }

        .auth-card-island {
            width: 100%;
            max-width: 480px;
            background: #FFFFFF;
            border: 1px solid var(--border-light, #E2E8F0);
            border-radius: var(--radius-xl, 24px);
            padding: 2.25rem 2.25rem 2rem;
            box-shadow: 0 20px 40px -10px rgba(9, 13, 22, 0.08);
            animation: fadeIn 0.3s ease;
        }

        .auth-card-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .auth-logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 1rem;
            text-decoration: none;
        }

        .auth-brand-mark {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--brand-gradient, linear-gradient(135deg, #FF5500 0%, #FF8533 100%));
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(255, 85, 0, 0.3);
        }

        .auth-brand-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--brand-navy, #0B132B);
        }
        .auth-brand-name span {
            color: var(--brand-coral, #FF5500);
        }

        .auth-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--brand-navy, #0B132B);
            margin: 0 0 0.4rem;
        }

        .auth-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted, #64748B);
            margin: 0;
        }

        /* Tabs Switcher */
        .auth-tabs-nav {
            display: flex;
            background: var(--bg-surface-alt, #F1F5F9);
            border-radius: var(--radius-full, 9999px);
            padding: 0.3rem;
            margin-bottom: 1.75rem;
            gap: 0.3rem;
        }

        .auth-tab-btn {
            flex: 1;
            text-align: center;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
            font-weight: 700;
            border: none;
            background: transparent;
            color: var(--text-muted, #64748B);
            border-radius: var(--radius-full, 9999px);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .auth-tab-btn.active {
            background: #FFFFFF;
            color: var(--text-primary, #090D16);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* Form elements */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-primary, #090D16);
            margin-bottom: 0.4rem;
        }

        .input-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 1rem;
            color: var(--text-muted, #94A3B8);
            display: flex;
            align-items: center;
            pointer-events: none;
        }

        .input-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            font-size: 0.95rem;
            font-family: inherit;
            border: 1.5px solid var(--border-light, #E2E8F0);
            border-radius: var(--radius-md, 12px);
            background: var(--bg-surface-alt, #F8FAFC);
            color: var(--text-primary, #090D16);
            outline: none;
            transition: all 0.2s ease;
        }

        .input-control:focus {
            border-color: var(--brand-coral, #FF5500);
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(255, 85, 0, 0.12);
        }

        .input-control.is-invalid {
            border-color: #EF4444;
            background: #FEF2F2;
        }

        .field-error-msg {
            color: #DC2626;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.35rem;
            display: block;
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.85rem;
            background: none;
            border: none;
            color: var(--text-muted, #94A3B8);
            cursor: pointer;
            padding: 0.35rem;
            display: flex;
            align-items: center;
        }

        /* Checkbox Container */
        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            margin: 1.25rem 0 1.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            line-height: 1.45;
            color: var(--text-secondary, #334155);
        }

        .checkbox-container input[type="checkbox"] {
            margin-top: 0.15rem;
            width: 17px;
            height: 17px;
            accent-color: var(--brand-coral, #FF5500);
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
            border-radius: var(--radius-md, 12px);
        }

        .btn[disabled] {
            opacity: 0.55;
            cursor: not-allowed;
            pointer-events: none;
        }

        .alert-box {
            padding: 0.85rem 1rem;
            border-radius: var(--radius-md, 12px);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .switch-footer-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-light, #E2E8F0);
            font-size: 0.88rem;
            color: var(--text-muted, #64748B);
        }
        .switch-footer-link a {
            color: var(--brand-coral, #FF5500);
            font-weight: 700;
            text-decoration: none;
        }

        .partner-switch-callout {
            text-align: center;
            margin-top: 1.5rem;
            padding: 0.85rem;
            background: var(--bg-surface-alt, #F1F5F9);
            border-radius: var(--radius-md, 12px);
            font-size: 0.825rem;
            color: var(--text-secondary, #334155);
        }
        .partner-switch-callout a {
            color: var(--brand-navy, #0B132B);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
    </style>
</head>
<body>

    <?php 
    $base = '../../';
    require dirname(__DIR__, 2) . "/components/navbar-main.php"; 
    ?>

    <main class="auth-main-wrapper">
        <div class="auth-card-island">
            
            <div class="auth-card-header">
                <h1 class="auth-title">Explore Home Services</h1>
                <p class="auth-subtitle">Sign in or create an account to access vetted doorstep professionals</p>
            </div>

            <!-- Tab Switcher (Login vs Register) -->
            <div class="auth-tabs-nav" role="tablist">
                <button type="button" class="auth-tab-btn <?= $activeTab === 'login' ? 'active' : ''; ?>" id="tabLoginBtn" role="tab" aria-selected="<?= $activeTab === 'login' ? 'true' : 'false'; ?>">
                    <i data-lucide="log-in" style="width: 15px; height: 15px; margin-right: 0.4rem;"></i>
                    <span>Sign In</span>
                </button>
                <button type="button" class="auth-tab-btn <?= $activeTab === 'register' ? 'active' : ''; ?>" id="tabRegisterBtn" role="tab" aria-selected="<?= $activeTab === 'register' ? 'true' : 'false'; ?>">
                    <i data-lucide="user-plus" style="width: 15px; height: 15px; margin-right: 0.4rem;"></i>
                    <span>Create Account</span>
                </button>
            </div>

            <!-- TAB 1: LOGIN FORM -->
            <div id="loginTabContent" style="display: <?= $activeTab === 'login' ? 'block' : 'none'; ?>;">
                <?php if (!empty($loginError)): ?>
                    <div class="alert-box alert-error" role="alert">
                        <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span><?= htmlspecialchars($loginError); ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" id="customerLoginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">

                    <div class="form-group">
                        <label class="form-label" for="loginIdentifier">Mobile Number or Email</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="text" 
                                id="loginIdentifier" 
                                name="identifier" 
                                class="input-control" 
                                placeholder="10-digit mobile or your email"
                                value="<?= htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                                required 
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                            <label class="form-label" for="loginPassword" style="margin: 0;">Password</label>
                        </div>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="password" 
                                id="loginPassword" 
                                name="password" 
                                class="input-control" 
                                placeholder="Enter your password"
                                required 
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle-btn" data-target="loginPassword" aria-label="Toggle password visibility">
                                <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="customerLoginBtn" class="btn btn-primary btn-full">
                        <span>Sign In &amp; Explore Services</span>
                        <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    </button>
                </form>

                <div class="switch-footer-link">
                    <span>Don't have an account yet?</span>
                    <a href="javascript:void(0)" id="switchToRegisterLink">Create an account</a>
                </div>
            </div>

            <!-- TAB 2: REGISTER FORM -->
            <div id="registerTabContent" style="display: <?= $activeTab === 'register' ? 'block' : 'none'; ?>;">
                <?php if (!empty($registerTopError)): ?>
                    <div class="alert-box alert-error" role="alert">
                        <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span><?= htmlspecialchars($registerTopError); ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" id="customerRegisterForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">

                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="regFullName">Full Legal Name</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="text" 
                                id="regFullName" 
                                name="full_name" 
                                class="input-control <?= isset($registerErrors['full_name']) ? 'is-invalid' : ''; ?>" 
                                placeholder="e.g. Aditi Rao"
                                value="<?= htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                required
                            >
                        </div>
                        <?php if (isset($registerErrors['full_name'])): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($registerErrors['full_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile -->
                    <div class="form-group">
                        <label class="form-label" for="regMobile">10-Digit Mobile Number</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="phone" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="tel" 
                                id="regMobile" 
                                name="mobile" 
                                maxlength="10"
                                class="input-control <?= isset($registerErrors['mobile']) ? 'is-invalid' : ''; ?>" 
                                placeholder="e.g. 9820123456"
                                value="<?= htmlspecialchars($_POST['mobile'] ?? ''); ?>"
                                required
                            >
                        </div>
                        <?php if (isset($registerErrors['mobile'])): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($registerErrors['mobile']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="regEmail">Email Address</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="mail" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="email" 
                                id="regEmail" 
                                name="email" 
                                class="input-control <?= isset($registerErrors['email']) ? 'is-invalid' : ''; ?>" 
                                placeholder="e.g. name@gmail.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                            >
                        </div>
                        <?php if (isset($registerErrors['email'])): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($registerErrors['email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label" for="regPassword">Create Password</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="password" 
                                id="regPassword" 
                                name="password" 
                                class="input-control <?= isset($registerErrors['password']) ? 'is-invalid' : ''; ?>" 
                                placeholder="Minimum 6 characters"
                                required
                            >
                            <button type="button" class="password-toggle-btn" data-target="regPassword" aria-label="Toggle password visibility">
                                <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                            </button>
                        </div>
                        <?php if (isset($registerErrors['password'])): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($registerErrors['password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label" for="regConfirmPassword">Confirm Password</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left">
                                <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input 
                                type="password" 
                                id="regConfirmPassword" 
                                name="confirm_password" 
                                class="input-control <?= isset($registerErrors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                placeholder="Re-type your password"
                                required
                            >
                        </div>
                        <?php if (isset($registerErrors['confirm_password'])): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($registerErrors['confirm_password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Required Terms and Conditions Checkbox (Exact text as specified) -->
                    <label class="checkbox-container" for="customerTermsCheckbox">
                        <input 
                            type="checkbox" 
                            id="customerTermsCheckbox" 
                            name="agree_terms" 
                            value="1" 
                            required
                            <?= !empty($_POST['agree_terms']) ? 'checked' : ''; ?>
                        >
                        <span>I agree to the Terms and Conditions and Privacy Policy.</span>
                    </label>
                    <?php if (isset($registerErrors['agree_terms'])): ?>
                        <span class="field-error-msg" style="margin-top: -0.75rem; margin-bottom: 1rem;"><?= htmlspecialchars($registerErrors['agree_terms']); ?></span>
                    <?php endif; ?>

                    <!-- Submit Register Button (Disabled until checkbox is selected) -->
                    <button type="submit" id="customerRegisterBtn" class="btn btn-primary btn-full" disabled>
                        <span>Create Account &amp; Explore Services</span>
                        <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    </button>
                </form>

                <div class="switch-footer-link">
                    <span>Already have an account?</span>
                    <a href="javascript:void(0)" id="switchToLoginLink">Sign In here</a>
                </div>
            </div>

            <!-- Join as Service Provider Callout -->
            <div class="partner-switch-callout">
                <span>Looking to work with us?</span>
                <a href="partner-register.php">
                    <span>Join as a Service Provider</span>
                    <i data-lucide="arrow-up-right" style="width: 13px; height: 13px;"></i>
                </a>
            </div>

        </div>
    </main>

    <footer style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border-light);">
        &copy; <?= date('Y'); ?> KaamMitra Technologies Private Limited. All rights reserved.
    </footer>

    <script>
        window.KM_BASE_PATH = '../../';
    </script>
    <script src="../../assets/js/main.js"></script>
    <script>

        // Tab Switching Logic
        const tabLoginBtn = document.getElementById('tabLoginBtn');
        const tabRegisterBtn = document.getElementById('tabRegisterBtn');
        const loginContent = document.getElementById('loginTabContent');
        const registerContent = document.getElementById('registerTabContent');
        const switchToRegisterLink = document.getElementById('switchToRegisterLink');
        const switchToLoginLink = document.getElementById('switchToLoginLink');

        function setTab(tab) {
            if (tab === 'register') {
                tabRegisterBtn.classList.add('active');
                tabRegisterBtn.setAttribute('aria-selected', 'true');
                tabLoginBtn.classList.remove('active');
                tabLoginBtn.setAttribute('aria-selected', 'false');
                loginContent.style.display = 'none';
                registerContent.style.display = 'block';
            } else {
                tabLoginBtn.classList.add('active');
                tabLoginBtn.setAttribute('aria-selected', 'true');
                tabRegisterBtn.classList.remove('active');
                tabRegisterBtn.setAttribute('aria-selected', 'false');
                loginContent.style.display = 'block';
                registerContent.style.display = 'none';
            }
        }

        if (tabLoginBtn) tabLoginBtn.addEventListener('click', () => setTab('login'));
        if (tabRegisterBtn) tabRegisterBtn.addEventListener('click', () => setTab('register'));
        if (switchToRegisterLink) switchToRegisterLink.addEventListener('click', () => setTab('register'));
        if (switchToLoginLink) switchToLoginLink.addEventListener('click', () => setTab('login'));

        // Checkbox Gate for Register Button
        // Keep Register button disabled until checkbox is selected
        const termsCheckbox = document.getElementById('customerTermsCheckbox');
        const registerBtn = document.getElementById('customerRegisterBtn');

        function updateRegisterBtnState() {
            if (!termsCheckbox || !registerBtn) return;
            registerBtn.disabled = !termsCheckbox.checked;
        }

        if (termsCheckbox) {
            termsCheckbox.addEventListener('change', updateRegisterBtnState);
            updateRegisterBtnState(); // Initialize on page load
        }

        // Password Visibility Toggles
        document.querySelectorAll('.password-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.innerHTML = isPassword 
                    ? '<i data-lucide="eye-off" style="width: 18px; height: 18px;"></i>' 
                    : '<i data-lucide="eye" style="width: 18px; height: 18px;"></i>';
                if (window.lucide) lucide.createIcons();
            });
        });
    </script>
</body>
</html>
