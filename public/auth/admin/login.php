<?php
/**
 * KaamMitra - Administrative Authentication Portal
 * Matches the light two-column design of customer and service provider authentication.
 * Backed by secure password hashing, rate limiting, and RBAC session initialization.
 */

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/admin-auth.php';

initSecureSession();

// Sanitize redirect target
$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if (!empty($redirect)) {
    // Only allow local redirects within the admin dashboard
    if (preg_match('/^(https?:|\/\/)/i', $redirect)) {
        $redirect = '../../admin/index.php';
    } elseif (!str_starts_with($redirect, '../') && !str_starts_with($redirect, '/')) {
        $redirect = '../../admin/' . $redirect;
    }
} else {
    $redirect = '../../admin/index.php';
}

// If already authenticated as an active admin, redirect directly to dashboard
if (isAdminLoggedIn() && getCurrentAdmin() !== null) {
    header("Location: " . $redirect);
    exit;
}

$loginError = '';
$loginSuccess = '';

// Handle Admin Sign In
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $loginError = 'Security validation failed (CSRF token expired or invalid). Please refresh and submit again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = loginAdmin($username, $password);
        if ($result['success']) {
            header("Location: " . $redirect);
            exit;
        } else {
            $loginError = $result['message'];
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
    <title>Admin Portal Sign In - <?= htmlspecialchars(APP_NAME); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/navbar.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="../../assets/css/admin-auth.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="stylesheet" href="../../assets/css/dark-mode.css">
    <script src="../../assets/js/lucide.min.js"></script>
</head>
<body data-base="../../">

    <?php 
    $base = '../../';
    require dirname(__DIR__, 2) . "/components/navbar-main.php"; 
    ?>

    <main class="admin-auth-layout">
        <div class="admin-auth-container">
            
            <!-- Left Section: Administrative Governance & Security Highlights -->
            <div class="admin-auth-left">
                <a href="../../index.php" class="admin-back-btn">
                    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                    <span>Back to Marketplace</span>
                </a>

                <div class="admin-light-badge">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px; color: var(--brand-coral, #FF5500);"></i>
                    <span>ADMINISTRATIVE GOVERNANCE</span>
                </div>

                <h1 class="admin-light-heading">
                    Enterprise Platform Security &amp; Control
                </h1>

                <p class="admin-light-subtext">
                    Unified management console for KaamMitra operations. Review provider verification requests, manage customer bookings, govern administrator roles, and monitor immutable audit trails.
                </p>

                <div class="admin-security-features">
                    <div class="admin-security-item">
                        <div class="admin-icon-box box-indigo">
                            <i data-lucide="shield" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="admin-feature-info">
                            <h4 class="admin-feature-title">Role-Based Access Control (RBAC)</h4>
                            <p class="admin-feature-desc">Dual-tier governance separating Super Admin system control from standard operational management.</p>
                        </div>
                    </div>

                    <div class="admin-security-item">
                        <div class="admin-icon-box box-teal">
                            <i data-lucide="lock" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="admin-feature-info">
                            <h4 class="admin-feature-title">Encrypted Session Security</h4>
                            <p class="admin-feature-desc">Hardened HTTP-only sessions with live database authority validation on every protected request.</p>
                        </div>
                    </div>

                    <div class="admin-security-item">
                        <div class="admin-icon-box box-purple">
                            <i data-lucide="file-check" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="admin-feature-info">
                            <h4 class="admin-feature-title">Immutable Audit Trail</h4>
                            <p class="admin-feature-desc">Every provider approval, role modification, and sensitive event is permanently recorded.</p>
                        </div>
                    </div>

                    <div class="admin-security-item">
                        <div class="admin-icon-box box-coral">
                            <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="admin-feature-info">
                            <h4 class="admin-feature-title">Brute-Force &amp; Rate-Limit Shield</h4>
                            <p class="admin-feature-desc">Automatic multi-attempt lockout defense preventing credential stuffing and unauthorized probing.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section: Elevated White Login Card -->
            <div class="admin-auth-right">
                <div class="admin-white-card">
                    <div class="admin-card-header">
                        <div class="admin-card-badge">
                            <i data-lucide="shield-alert" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h2 class="admin-card-title">Admin Console Sign In</h2>
                        <p class="admin-card-subtitle">Enter your authorized credentials to access platform management</p>
                    </div>

                    <?php if (!empty($loginError)): ?>
                        <div class="alert-box alert-error" role="alert" style="margin-bottom: 1.5rem;">
                            <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                            <span><?= htmlspecialchars($loginError); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" id="adminLoginForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">

                        <!-- Admin ID Field -->
                        <div class="form-group">
                            <label class="form-label" for="adminUsername">Admin ID / Username</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left">
                                    <i data-lucide="user-check" style="width: 18px; height: 18px;"></i>
                                </span>
                                <input 
                                    type="text" 
                                    id="adminUsername" 
                                    name="username" 
                                    class="input-control" 
                                    placeholder="Enter your Admin ID"
                                    value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>"
                                    required 
                                    autocomplete="username"
                                    autofocus
                                >
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="form-group">
                            <label class="form-label" for="adminPassword">Password</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left">
                                    <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                                </span>
                                <input 
                                    type="password" 
                                    id="adminPassword" 
                                    name="password" 
                                    class="input-control" 
                                    placeholder="Enter your administrative password"
                                    required 
                                    autocomplete="current-password"
                                >
                                <button type="button" class="password-toggle-btn" data-target="adminPassword" aria-label="Toggle password visibility">
                                    <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Sign In Button -->
                        <div style="margin-top: 1.75rem;">
                            <button type="submit" id="adminSignInBtn" class="btn btn-primary btn-full">
                                <span>Sign In to Admin Console</span>
                                <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                            </button>
                        </div>
                    </form>

                    <div class="admin-security-notice">
                        <i data-lucide="info" style="width: 16px; height: 16px;"></i>
                        <span>Restricted System. Authorized personnel only. All access requests, actions, and IP addresses are recorded in platform audit logs.</span>
                    </div>
                </div> <!-- /.admin-white-card -->
            </div> <!-- /.admin-auth-right -->

        </div> <!-- /.admin-auth-container -->
    </main>

    <?php 
    $base = '../../';
    require dirname(__DIR__, 2) . "/components/footer-main.php"; 
    ?>

    <script src="../../assets/js/core.js" defer></script>
    <script src="../../assets/js/navbar.js" defer></script>
    <script src="../../assets/js/location.js" defer></script>
    <script src="../../assets/js/auth.js" defer></script>
</body>
</html>
