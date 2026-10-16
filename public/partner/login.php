<?php
/**
 * KaamMitra - Partner Login Portal
 * Secure authentication for registered service providers
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/icons.php';
require_once __DIR__ . '/../components/alert.php';

initSecureSession();
$base = getAppBasePath();

// If already logged in as approved partner, redirect to partner dashboard
if (isPartnerLoggedIn()) {
    $partner = getLoggedInPartner();
    if ($partner && $partner['verification_status'] === 'Approved') {
        header("Location: " . $base . "partner/dashboard.php");
        exit;
    }
}

$error = '';
$successMsg = '';
$pendingPartner = null;
$rejectedPartner = null;
$rejectionReason = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logged_out') {
        $successMsg = 'You have been safely signed out of the Partner Portal.';
    } elseif ($_GET['msg'] === 'auth_required') {
        $error = 'Please sign in to access your Partner Dashboard and manage bookings.';
    } elseif ($_GET['msg'] === 'session_invalid') {
        $error = 'Your partner session has expired. Please sign in again.';
    } elseif ($_GET['msg'] === 'registered_pending') {
        $successMsg = 'Your application has been received! Please sign in with your credentials to check status.';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security check failed (CSRF). Please refresh and try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        $result = loginPartner($identifier, $password);

        if ($result['success'] && $result['status'] === 'Approved') {
            header("Location: " . $base . "partner/dashboard.php");
            exit;
        } elseif ($result['status'] === 'Pending') {
            $pendingPartner = $result['partner'] ?? null;
        } elseif ($result['status'] === 'Rejected') {
            $rejectedPartner = $result['partner'] ?? null;
            $rejectionReason = $result['rejection_reason'] ?? 'Trade and document requirements were not fulfilled.';
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = getCsrfToken();
$pageTitle = 'Partner Login - ' . APP_NAME . ' Professional Portal';
$extraHead = '<style>
    body { background-color: var(--bg-page); min-height: 100vh; display: flex; flex-direction: column; color: var(--text-primary); font-family: var(--font-body); }
    .auth-page-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem 1.25rem 4rem; position: relative; background: radial-gradient(circle at 15% 15%, rgba(255, 102, 0, 0.05) 0%, transparent 45%), radial-gradient(circle at 85% 85%, rgba(10, 19, 37, 0.05) 0%, transparent 45%); }
    .auth-container { max-width: 490px; width: 100%; margin: 0 auto; }
    .auth-card { background: #FFFFFF; border-radius: 24px; padding: 2.5rem 2.25rem; box-shadow: 0 10px 30px rgba(10, 19, 37, 0.06), 0 1px 3px rgba(10, 19, 37, 0.04); border: 1px solid var(--border-subtle); transition: box-shadow 0.2s ease; }
    @media (max-width: 540px) { .auth-card { padding: 1.75rem 1.25rem; border-radius: 20px; } }
    .auth-card-header { text-align: center; margin-bottom: 1.75rem; }
    .partner-portal-badge { display: inline-flex; align-items: center; gap: 0.4rem; background: #FFF7ED; color: #EA580C; border: 1px solid #FFEDD5; padding: 0.35rem 0.85rem; border-radius: 100px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem; }
    .auth-segmented-pill { display: flex; background: var(--gray-100); border-radius: 12px; padding: 4px; margin-bottom: 1.75rem; border: 1px solid var(--gray-200); }
    .auth-segmented-tab { flex: 1; padding: 0.65rem 1rem; text-align: center; font-size: 0.88rem; font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.2s ease; color: var(--text-secondary); border: none; background: transparent; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; }
    .auth-segmented-tab.active { background: #FFFFFF; color: var(--navy-900); font-weight: 700; box-shadow: 0 2px 8px rgba(10, 19, 37, 0.08); }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--navy-900); margin-bottom: 0.45rem; }
    .input-icon-wrap { position: relative; }
    .input-icon-wrap input { width: 100%; height: 48px; border-radius: 12px; border: 1.5px solid var(--border-subtle); background-color: #FFFFFF; padding-left: 2.75rem; padding-right: 2.75rem; font-size: 0.95rem; color: var(--navy-900); font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s; }
    .input-icon-wrap input:focus { outline: none; border-color: var(--orange-500); box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15); }
    .input-icon-left { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); pointer-events: none; display: flex; align-items: center; }
    .input-icon-wrap input:focus + .input-icon-left, .input-icon-wrap:focus-within .input-icon-left { color: var(--orange-500); }
    .password-toggle-btn { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--gray-400); cursor: pointer; padding: 0.35rem; display: flex; align-items: center; border-radius: 6px; }
    .password-toggle-btn:hover { color: var(--orange-500); }
    .status-display-card { border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: left; }
    .status-display-pending { background: #FFFBEB; border: 1.5px solid #FDE68A; }
    .status-display-rejected { background: #FEF2F2; border: 1.5px solid #FECACA; }
    .trust-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-top: 1.75rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle); text-align: center; }
    @media (max-width: 480px) { .trust-strip { grid-template-columns: 1fr; gap: 0.5rem; } }
    .trust-item { display: flex; flex-direction: column; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: var(--text-secondary); }
    .trust-item i, .trust-item svg, .trust-item .lucide { color: var(--orange-500) !important; width: 22px !important; height: 22px !important; }
    .demo-credentials-box { background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 12px; padding: 0.85rem 1rem; margin-top: 1.25rem; font-size: 0.8rem; color: #475569; }
    .demo-credentials-box strong { color: #0F172A; }
    .demo-pill { display: inline-block; background: #E2E8F0; padding: 0.15rem 0.45rem; border-radius: 6px; font-family: monospace; cursor: pointer; user-select: all; }
</style>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../components/head.php'; ?>
</head>
<body>
<?php
require __DIR__ . '/../components/navbar-partner.php';
?>

<!-- Main Content Area -->
<main class="auth-page-main">
    <div class="auth-container">
        
        <div class="auth-card">
            
            <div class="auth-card-header">
                <div class="partner-portal-badge">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                    <span>Verified Service Provider Network</span>
                </div>
                <h1 style="font-size: 1.65rem; font-weight: 800; color: var(--navy-900); margin-bottom: 0.35rem; letter-spacing: -0.02em;">
                    Partner Portal Login
                </h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    Sign in to view your service requests, earnings &amp; daily schedule
                </p>
            </div>

            <!-- Segmented Tabs for Partner Login vs Become a Partner -->
            <div class="auth-segmented-pill">
                <button type="button" class="auth-segmented-tab active">
                    <i data-lucide="log-in" style="width: 15px; height: 15px;"></i>
                    <span>Partner Login</span>
                </button>
                <a href="<?= $base; ?>partner/register.php" class="auth-segmented-tab">
                    <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                    <span>Become a Partner</span>
                </a>
            </div>

            <!-- General Alerts -->
            <?php if (!empty($error)) echo renderAlert($error, 'error'); ?>
            <?php if (!empty($successMsg)) echo renderAlert($successMsg, 'success'); ?>

            <!-- PENDING VERIFICATION NOTICE -->
            <?php if ($pendingPartner): ?>
                <div class="status-display-card status-display-pending">
                    <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.75rem;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="clock" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.05rem; font-weight: 800; color: #92400E; margin: 0;">Application Under Review</h3>
                            <span style="font-size: 0.78rem; font-weight: 700; color: #B45309; text-transform: uppercase;">Status: Pending Admin Verification</span>
                        </div>
                    </div>

                    <p style="font-size: 0.88rem; color: #78350F; line-height: 1.5; margin-bottom: 1rem;">
                        Hello <strong><?= htmlspecialchars($pendingPartner['full_name']); ?></strong>! Your partner application has been submitted and is currently being reviewed by our trust and safety onboarding team.
                    </p>

                    <div style="background: rgba(255, 255, 255, 0.7); border-radius: 10px; padding: 0.75rem 0.9rem; font-size: 0.82rem; color: #78350F; margin-bottom: 1rem; border: 1px solid #FDE68A;">
                        <div><strong>Registered Trade:</strong> <?= htmlspecialchars($pendingPartner['primary_skill']); ?></div>
                        <div><strong>Operating City:</strong> <?= htmlspecialchars($pendingPartner['city']); ?></div>
                        <div><strong>Contact Mobile:</strong> <?= htmlspecialchars($pendingPartner['mobile']); ?></div>
                    </div>

                    <p style="font-size: 0.82rem; color: #78350F; margin-bottom: 0.85rem;">
                        <i data-lucide="info" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i>
                        <strong>Next Step:</strong> Our regional partner coordinator will verify your trade experience within <strong>4 business hours</strong>. You cannot accept customer bookings until approved.
                    </p>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline btn-sm" style="background: #FFFFFF; font-size: 0.82rem; text-decoration: none;">
                            <i data-lucide="phone" style="width: 13px; height: 13px;"></i>
                            <span>Call Partner Desk: <?= APP_PHONE_DISPLAY; ?></span>
                        </a>
                        <a href="<?= $base; ?>partner/login.php" class="btn btn-dark btn-sm" style="font-size: 0.82rem; text-decoration: none;">
                            <span>Refresh Status</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- REJECTED NOTICE -->
            <?php if ($rejectedPartner): ?>
                <div class="status-display-card status-display-rejected">
                    <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.75rem;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="x-circle" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.05rem; font-weight: 800; color: #991B1B; margin: 0;">Application Not Approved</h3>
                            <span style="font-size: 0.78rem; font-weight: 700; color: #B91C1C; text-transform: uppercase;">Status: Application Rejected</span>
                        </div>
                    </div>

                    <p style="font-size: 0.88rem; color: #7F1D1D; line-height: 1.5; margin-bottom: 0.75rem;">
                        Applicant: <strong><?= htmlspecialchars($rejectedPartner['full_name']); ?></strong> (<?= htmlspecialchars($rejectedPartner['primary_skill']); ?>)
                    </p>

                    <div style="background: #FFFFFF; border-radius: 10px; padding: 0.75rem 0.9rem; font-size: 0.82rem; color: #991B1B; margin-bottom: 1rem; border: 1px solid #FECACA;">
                        <strong>Reason:</strong> <?= htmlspecialchars($rejectionReason); ?>
                    </div>

                    <p style="font-size: 0.82rem; color: #7F1D1D; margin-bottom: 0.85rem;">
                        If you believe this was in error, you may re-apply with verified documents or contact our Partner Support team for guidance.
                    </p>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="<?= $base; ?>partner/register.php" class="btn btn-primary btn-sm" style="font-size: 0.82rem; text-decoration: none;">
                            <i data-lucide="refresh-cw" style="width: 13px; height: 13px;"></i>
                            <span>Re-Apply as Partner</span>
                        </a>
                        <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline btn-sm" style="background: #FFFFFF; font-size: 0.82rem; text-decoration: none;">
                            <i data-lucide="phone" style="width: 13px; height: 13px;"></i>
                            <span>Partner Support</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= $base; ?>partner/login.php" method="POST" id="partnerLoginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">

                <!-- Mobile or Email -->
                <div class="form-group">
                    <label for="identifier">Mobile Number or Email</label>
                    <div class="input-icon-wrap">
                        <input 
                            type="text" 
                            id="identifier" 
                            name="identifier" 
                            placeholder="e.g. 9876543210 or partner@gmail.com" 
                            value="<?= htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                            autocomplete="username"
                            required
                        >
                        <span class="input-icon-left">
                            <i data-lucide="user-check" style="width: 18px; height: 18px;"></i>
                        </span>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                        <label for="password" style="margin-bottom: 0;">Password</label>
                        <a href="tel:<?= APP_PHONE; ?>" style="font-size: 0.78rem; color: var(--orange-500); text-decoration: none; font-weight: 600;">
                            Forgot password?
                        </a>
                    </div>
                    <div class="input-icon-wrap">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your partner password" 
                            autocomplete="current-password"
                            required
                        >
                        <span class="input-icon-left">
                            <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                        </span>
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">
                            <i data-lucide="eye" id="eyeIcon" style="width: 18px; height: 18px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-full" id="submitBtn" style="height: 48px; font-size: 0.95rem; font-weight: 700; border-radius: 12px; margin-top: 1.5rem; justify-content: center; gap: 0.5rem;">
                    <span>Sign In to Partner Portal</span>
                    <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                </button>
            </form>

            <!-- Demo helper box for evaluation -->
            <div class="demo-credentials-box">
                <div style="font-weight: 700; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.35rem;">
                    <i data-lucide="key" style="width: 14px; height: 14px; color: #FF6600;"></i>
                    <span>Testing Credentials (Click to fill):</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                    <div>
                        Approved Mitra: <span class="demo-pill" onclick="fillCreds('9876543210','Password@123')">9876543210</span> (Rajesh Kumar, Electrician)
                    </div>
                    <div>
                        Pending Mitra: <span class="demo-pill" onclick="fillCreds('9811122233','Password@123')">9811122233</span> (Amit Sharma, Plumber)
                    </div>
                    <div>
                        Rejected Mitra: <span class="demo-pill" onclick="fillCreds('9822233344','Password@123')">9822233344</span> (Vikram Singh, Painter)
                    </div>
                </div>
            </div>

            <!-- Registration Prompt -->
            <div style="text-align: center; margin-top: 1.75rem; font-size: 0.9rem; color: var(--text-secondary);">
                Want to offer your professional skills?
                <a href="<?= $base; ?>partner/register.php" style="color: var(--orange-500); font-weight: 700; text-decoration: none; margin-left: 0.25rem;">
                    Register as a KaamMitra Partner &rarr;
                </a>
            </div>

            <!-- Trust Badges -->
            <div class="trust-strip">
                <div class="trust-item">
                    <i data-lucide="calendar" style="width: 22px; height: 22px; min-width: 22px; min-height: 22px; color: var(--orange-500);"></i>
                    <span><strong>Weekly</strong> Payouts</span>
                </div>
                <div class="trust-item">
                    <i data-lucide="percent" style="width: 22px; height: 22px; min-width: 22px; min-height: 22px; color: var(--orange-500);"></i>
                    <span><strong>0% Commission</strong> 30 Days</span>
                </div>
                <div class="trust-item">
                    <i data-lucide="headphones" style="width: 22px; height: 22px; min-width: 22px; min-height: 22px; color: var(--orange-500);"></i>
                    <span><strong>24/7 Helpline</strong> Support</span>
                </div>
            </div>

        </div>

    </div>
</main>

<?php
$portalName = 'Partner Operations Desk';
require __DIR__ . '/../components/footer-portal.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Password Show/Hide Toggle
        const togglePasswordBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');

        if (togglePasswordBtn && passwordInput) {
            togglePasswordBtn.addEventListener('click', () => {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                
                togglePasswordBtn.innerHTML = isPassword 
                    ? '<i data-lucide="eye-off" style="width: 18px; height: 18px;"></i>' 
                    : '<i data-lucide="eye" style="width: 18px; height: 18px;"></i>';
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        }
    });

    function fillCreds(identifier, pass) {
        document.getElementById('identifier').value = identifier;
        document.getElementById('password').value = pass;
    }
</script>
</body>
</html>
