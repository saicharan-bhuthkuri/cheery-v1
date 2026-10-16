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
            padding: 2.25rem;
            box-shadow: 0 20px 40px -10px rgba(9, 13, 22, 0.08);
            animation: fadeIn 0.3s ease;
        }

        .auth-card-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 85, 0, 0.1);
            color: var(--brand-coral, #FF5500);
            border: 1px solid rgba(255, 85, 0, 0.25);
            padding: 0.3rem 0.85rem;
            border-radius: var(--radius-full, 9999px);
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .auth-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--brand-navy, #0B132B);
            margin: 0 0 0.4rem;
        }

        .auth-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted, #64748B);
            margin: 0;
        }

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

        .btn-full {
            width: 100%;
            justify-content: center;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
            border-radius: var(--radius-md, 12px);
        }

        .alert-box {
            padding: 0.85rem 1rem;
            border-radius: var(--radius-md, 12px);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            line-height: 1.45;
        }

        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .alert-amber {
            background: #FFFBEB;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .alert-green {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
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

            <form action="partner-login.php" method="POST" id="providerLoginForm" novalidate>
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
                <a href="partner-register.php">Join as a Service Provider</a>
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

        // Password visibility toggles
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
