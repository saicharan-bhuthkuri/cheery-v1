<?php
/**
 * KaamMitra - Dedicated Administrator Authentication Portal
 * Route: /admin (also /admin/ and /admin/index.php)
 * 
 * Strict access control: Only authenticated admins can access the dashboard.
 * Customers and service providers are not permitted to log in through this portal.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/icons.php';
require_once __DIR__ . '/../components/alert.php';

initSecureSession();
$base = getAppBasePath();

// Sanitize redirect target (must stay within admin scope)
$defaultRedirect = $base . 'admin/dashboard.php';
$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? $defaultRedirect);
if (!str_contains($redirect, 'admin')) {
    $redirect = $defaultRedirect;
}

// If already logged in as admin, redirect to admin dashboard immediately
if (isAdminLoggedIn()) {
    header("Location: " . $redirect);
    exit;
}

$error = '';
$successMsg = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logged_out') {
        $successMsg = 'Admin session terminated safely. All credentials cleared.';
    } elseif ($_GET['msg'] === 'auth_required') {
        $error = 'Admin authentication required. Please sign in with your administrator credentials.';
    } elseif ($_GET['msg'] === 'session_invalid') {
        $error = 'Your admin session has expired. Please authenticate again.';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security verification failed (CSRF token mismatch). Please refresh and try again.';
    } else {
        $adminId = trim($_POST['admin_id'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = loginAdmin($adminId, $password);
        if ($result['success']) {
            header("Location: " . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = getCsrfToken();
$pageTitle = 'Admin Sign In - ' . APP_NAME . ' Operations Console';
$extraHead = '<style>
    :root {
        --admin-navy-dark: #030712;
        --admin-navy-card: #0F172A;
        --admin-navy-border: #1E293B;
        --admin-accent-orange: #FF6600;
        --admin-accent-glow: rgba(255, 102, 0, 0.25);
        --admin-cyan: #06B6D4;
    }
    body {
        background: radial-gradient(circle at 50% 20%, #0F172A 0%, #030712 100%);
        min-height: 100vh;
        margin: 0;
        font-family: var(--font-body);
        color: #F8FAFC;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow-x: hidden;
    }
    body::before {
        content: "";
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: 
            linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
        z-index: 0;
    }
    .admin-auth-header {
        padding: 1.5rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        z-index: 10;
    }
    .admin-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: #FFFFFF;
    }
    .admin-brand-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #FF6600 0%, #E65100 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 14px rgba(255, 102, 0, 0.4);
        color: #FFFFFF;
    }
    .admin-brand-title {
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .admin-brand-title span {
        color: var(--orange-500);
    }
    .admin-badge-hq {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.65rem;
        background: rgba(6, 182, 212, 0.12);
        border: 1px solid rgba(6, 182, 212, 0.3);
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #38BDF8;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .admin-return-link {
        color: #94A3B8;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: color 0.2s;
    }
    .admin-return-link:hover {
        color: #FFFFFF;
    }
    .admin-auth-container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.25rem;
        position: relative;
        z-index: 10;
    }
    .admin-auth-card {
        width: 100%;
        max-width: 440px;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 2.5rem 2.25rem;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05), 0 0 50px -15px var(--admin-accent-glow);
        position: relative;
    }
    .admin-card-glow {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #FF6600, transparent);
    }
    .admin-auth-header-text {
        text-align: center;
        margin-bottom: 2rem;
    }
    .admin-shield-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(255, 102, 0, 0.15), rgba(230, 81, 0, 0.25));
        border: 1px solid rgba(255, 102, 0, 0.3);
        color: #FF6600;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem;
    }
    .admin-auth-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #FFFFFF;
        letter-spacing: -0.02em;
        margin: 0 0 0.4rem;
    }
    .admin-auth-desc {
        font-size: 0.88rem;
        color: #94A3B8;
        margin: 0;
        line-height: 1.5;
    }
    .form-group {
        margin-bottom: 1.25rem;
    }
    .form-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.82rem;
        font-weight: 600;
        color: #CBD5E1;
        margin-bottom: 0.45rem;
        letter-spacing: 0.02em;
    }
    .form-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .input-icon-left {
        position: absolute;
        left: 1rem;
        color: #64748B;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        transition: color 0.2s;
    }
    .form-control {
        width: 100%;
        box-sizing: border-box;
        background: rgba(2, 6, 23, 0.6);
        border: 1px solid #1E293B;
        border-radius: 10px;
        padding: 0.85rem 1rem 0.85rem 2.85rem;
        font-size: 0.95rem;
        font-family: inherit;
        color: #FFFFFF;
        transition: all 0.2s ease;
        outline: none;
    }
    .form-control:focus {
        border-color: #FF6600;
        background: rgba(2, 6, 23, 0.9);
        box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.2);
    }
    .form-control:focus + .input-icon-left,
    .form-control:focus ~ .input-icon-left {
        color: #FF6600;
    }
    .toggle-password {
        position: absolute;
        right: 0.85rem;
        background: none;
        border: none;
        color: #64748B;
        cursor: pointer;
        padding: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
    }
    .toggle-password:hover {
        color: #E2E8F0;
    }
    .btn-admin-submit {
        width: 100%;
        padding: 0.95rem;
        background: linear-gradient(135deg, #FF6600 0%, #EA580C 100%);
        border: none;
        border-radius: 10px;
        color: #FFFFFF;
        font-size: 0.95rem;
        font-weight: 700;
        font-family: inherit;
        letter-spacing: 0.02em;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        box-shadow: 0 4px 16px rgba(255, 102, 0, 0.35);
        transition: all 0.2s ease;
        margin-top: 1.5rem;
    }
    .btn-admin-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(255, 102, 0, 0.45);
        background: linear-gradient(135deg, #FF7D26 0%, #FF6600 100%);
    }
    .credentials-helper {
        margin-top: 1.75rem;
        background: rgba(30, 41, 59, 0.4);
        border: 1px dashed rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 0.85rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .credentials-info {
        font-size: 0.8rem;
        color: #94A3B8;
    }
    .credentials-info strong {
        color: #F8FAFC;
        font-family: monospace;
    }
    .btn-fill-creds {
        background: rgba(255, 102, 0, 0.15);
        border: 1px solid rgba(255, 102, 0, 0.3);
        color: #FF7D26;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .btn-fill-creds:hover {
        background: #FF6600;
        color: #FFFFFF;
    }
    .security-footer {
        margin-top: 1.75rem;
        padding-top: 1.25rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.25rem;
        font-size: 0.72rem;
        color: #64748B;
    }
    .security-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
</style>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../components/head.php'; ?>
</head>
<body>

<!-- Header -->
<header class="admin-auth-header">
    <a href="<?= $base; ?>index.php" class="admin-brand">
        <div class="admin-brand-icon">
            <i data-lucide="shield" style="width: 20px; height: 20px;"></i>
        </div>
        <div>
            <div class="admin-brand-title">Kaam<span>Mitra</span></div>
        </div>
        <span class="admin-badge-hq">Ops Console</span>
    </a>

    <a href="<?= $base; ?>index.php" class="admin-return-link">
        <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
        <span>Back to Public Website</span>
    </a>
</header>

<!-- Main Login Card -->
<main class="admin-auth-container">
    <div class="admin-auth-card">
        <div class="admin-card-glow"></div>

        <div class="admin-auth-header-text">
            <div class="admin-shield-icon">
                <i data-lucide="shield-check" style="width: 28px; height: 28px;"></i>
            </div>
            <h1 class="admin-auth-title">Admin Sign In</h1>
            <p class="admin-auth-desc">Restricted administrative access. Enter your executive credentials to access the central operations dashboard.</p>
        </div>

        <?php if (!empty($error)) echo renderAlert($error, 'error'); ?>
        <?php if (!empty($successMsg)) echo renderAlert($successMsg, 'success'); ?>

        <form action="<?= $base; ?>admin/index.php" method="POST" id="adminLoginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">

            <!-- Admin ID Field -->
            <div class="form-group">
                <label class="form-label" for="adminId">
                    <span>Admin ID</span>
                    <span style="font-size: 0.72rem; color: #64748B;">Case-insensitive</span>
                </label>
                <div class="form-input-wrapper">
                    <div class="input-icon-left">
                        <i data-lucide="user-check" style="width: 18px; height: 18px;"></i>
                    </div>
                    <input 
                        type="text" 
                        id="adminId" 
                        name="admin_id" 
                        class="form-control" 
                        placeholder="Enter your Admin ID" 
                        value="<?= htmlspecialchars($_POST['admin_id'] ?? ''); ?>"
                        required 
                        autofocus 
                        autocomplete="username"
                    >
                </div>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label class="form-label" for="adminPassword">
                    <span>Password</span>
                </label>
                <div class="form-input-wrapper">
                    <div class="input-icon-left">
                        <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                    </div>
                    <input 
                        type="password" 
                        id="adminPassword" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your Password" 
                        required 
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-password" id="togglePasswordBtn" aria-label="Toggle password visibility">
                        <i data-lucide="eye" id="toggleEyeIcon" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-admin-submit" id="submitBtn">
                <span>Authenticate &amp; Enter Console</span>
                <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
            </button>
        </form>

        <!-- Quick Auto-Fill Credential Pill for Smooth Testing -->
        <div class="credentials-helper">
            <div class="credentials-info">
                <div>Authorized: ID <strong>Charan</strong> &bull; Pass <strong>Bharat</strong></div>
            </div>
            <button type="button" class="btn-fill-creds" id="btnFillCreds" onclick="fillAdminCredentials()">
                Auto-Fill
            </button>
        </div>

        <div class="security-footer">
            <div class="security-badge">
                <i data-lucide="shield" style="width: 12px; height: 12px; color: #38BDF8;"></i>
                <span>256-Bit TLS</span>
            </div>
            <div class="security-badge">
                <i data-lucide="fingerprint" style="width: 12px; height: 12px; color: #10B981;"></i>
                <span>Isolated Session</span>
            </div>
            <div class="security-badge">
                <i data-lucide="zap" style="width: 12px; height: 12px; color: #F59E0B;"></i>
                <span>Rate Limited</span>
            </div>
        </div>
    </div>
</main>

<?php
$portalName = 'Internal Administration Console';
require __DIR__ . '/../components/footer-portal.php';
?>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }

    // Password visibility toggle
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('adminPassword');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword 
                ? '<i data-lucide="eye-off" style="width: 18px; height: 18px;"></i>' 
                : '<i data-lucide="eye" style="width: 18px; height: 18px;"></i>';
            if (window.lucide) lucide.createIcons();
        });
    }

    // Quick auto-fill helper for test / demo convenience
    function fillAdminCredentials() {
        document.getElementById('adminId').value = 'Charan';
        document.getElementById('adminPassword').value = 'Bharat';
        document.getElementById('adminPassword').focus();
    }
</script>
</body>
</html>
