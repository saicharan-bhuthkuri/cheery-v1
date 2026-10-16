<?php
/**
 * KaamMitra - Service Provider Login Portal
 * Secure authentication for registered service providers with verification status handling.
 */

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

initSecureSession();

$error = '';
$successMsg = '';
$pendingPartner = null;
$rejectedPartner = null;
$approvedPartner = null;

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logged_out') {
        $successMsg = 'You have been safely signed out.';
    } elseif ($_GET['msg'] === 'auth_required') {
        $error = 'Please sign in to access your provider account.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (CSRF). Please refresh and try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        $result = loginPartner($identifier, $password);

        if ($result['success'] && $result['status'] === 'Approved') {
            $approvedPartner = $result['partner'];
        } elseif ($result['status'] === 'Pending') {
            $pendingPartner = $result['partner'] ?? null;
        } elseif ($result['status'] === 'Rejected') {
            $rejectedPartner = $result['partner'] ?? null;
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
    <title>Service Provider Login - <?= htmlspecialchars(APP_NAME); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/navbar.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="stylesheet" href="../../assets/css/dark-mode.css">
    <script src="../../assets/js/lucide.min.js"></script>
</head>
<body data-base="../../">

    <?php 
    $base = '../../';
    require dirname(__DIR__, 2) . "/components/navbar-main.php"; 
    ?>

    <main class="auth-main-wrapper">
        <div class="auth-card-island">

            <div class="auth-card-header">
                <div class="auth-badge">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                    <span>Authorized Partner Access</span>
                </div>
                <h1 class="auth-title">Service Provider Login</h1>
                <p class="auth-subtitle">Sign in to check your application status and access job dispatch</p>
            </div>

            <?php if (!empty($successMsg)): ?>
                <div class="alert-box alert-green" role="alert">
                    <i data-lucide="check-circle" style="width: 18px; height: 18px; flex-shrink: 0; color: #10B981;"></i>
                    <span><?= htmlspecialchars($successMsg); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-box alert-error" role="alert">
                    <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0; color: #EF4444;"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($pendingPartner): ?>
                <!-- Pending Notice -->
                <div class="alert-box alert-amber" role="alert">
                    <i data-lucide="clock" style="width: 20px; height: 20px; flex-shrink: 0; color: #D97706; margin-top: 0.1rem;"></i>
                    <div>
                        <strong style="display: block; font-size: 0.92rem; margin-bottom: 0.2rem;">Application Under Review (Status: Pending)</strong>
                        <span>Hello <?= htmlspecialchars($pendingPartner['full_name']); ?>, your service provider account is currently awaiting administrative review and background check. Approved access will be granted once an administrator verifies your application.</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($approvedPartner): ?>
                <!-- Approved State -->
                <div class="alert-box alert-green" role="alert">
                    <i data-lucide="check-circle" style="width: 20px; height: 20px; flex-shrink: 0; color: #10B981;"></i>
                    <div>
                        <strong style="display: block; font-size: 0.95rem; margin-bottom: 0.2rem;">Welcome, <?= htmlspecialchars($approvedPartner['full_name']); ?>!</strong>
                        <span>Your service provider account is verified and approved (Status: Active). Doorstep repair dispatches are active in your area.</span>
                    </div>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" id="providerLoginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">

                <div class="form-group">
                    <label class="form-label" for="partnerIdentifier">Registered Mobile Number or Email</label>
                    <div class="input-icon-wrap">
                        <span class="input-icon-left"><i data-lucide="user" style="width: 18px; height: 18px;"></i></span>
                        <input 
                            type="text" 
                            id="partnerIdentifier" 
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
                    <label class="form-label" for="partnerPassword">Password</label>
                    <div class="input-icon-wrap">
                        <span class="input-icon-left"><i data-lucide="lock" style="width: 18px; height: 18px;"></i></span>
                        <input 
                            type="password" 
                            id="partnerPassword" 
                            name="password" 
                            class="input-control" 
                            placeholder="Enter your password"
                            required 
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle-btn" data-target="partnerPassword" aria-label="Toggle password">
                            <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <span>Sign In to Provider Portal</span>
                    <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                </button>
            </form>

            <div class="switch-footer-link">
                <span>Want to register as a new technician?</span>
                <a href="register.php">Join as a Service Provider</a>
            </div>

        </div>
    </main>

    <footer style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border-light);">
        &copy; <?= date('Y'); ?> KaamMitra Technologies Private Limited. All rights reserved.
    </footer>

    <script src="../../assets/js/core.js" defer></script>
    <script src="../../assets/js/navbar.js" defer></script>
    <script src="../../assets/js/location.js" defer></script>
    <script src="../../assets/js/auth.js" defer></script>
</body>
</html>
