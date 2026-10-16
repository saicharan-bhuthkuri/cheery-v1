<?php
/**
 * KaamMitra - Service Provider Registration Portal
 * Dedicated onboarding workflow for professional service providers.
 * Saves applications with 'Pending' verification status by default until admin verification.
 */

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

initSecureSession();

$errors = [];
$topError = '';
$isSubmittedSuccess = false;
$submittedPartner = null;

$formData = [
    'full_name'        => '',
    'mobile'           => '',
    'email'            => '',
    'primary_skill'    => 'Master Electrician',
    'experience_years' => '3-5 Years',
    'city'             => '',
    'service_area'     => '',
    'agree_terms'      => ''
];

$skillsList = [
    'Master Electrician'       => 'Electrician (Wiring, MCB, Inverter, Appliances)',
    'Certified Plumber'        => 'Plumber (Piping, Taps, Leakages, Bathroom Fittings)',
    'AC & Appliance Specialist'=> 'AC & Appliance Technician (AC Foam Jet, Fridge, Washing Machine)',
    'Carpenter & Woodworker'   => 'Carpenter (Furniture, Modular, Smart Locks)',
    'Painter & Waterproofing'  => 'Painter (Interior, Exterior, Waterproofing)',
    'Deep Cleaning Specialist' => 'Deep Cleaning Pro (Home, Kitchen & Bathroom)'
];

$experienceList = [
    '1-3 Years'  => '1 - 3 Years (Junior Technician)',
    '3-5 Years'  => '3 - 5 Years (Experienced Technician)',
    '5-10 Years' => '5 - 10 Years (Senior Master Craftsman)',
    '10+ Years'  => '10+ Years (Expert Lead / Specialist)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $topError = 'Security validation failed (CSRF). Please refresh and submit again.';
    } else {
        $formData = [
            'full_name'        => trim($_POST['full_name'] ?? ''),
            'mobile'           => trim($_POST['mobile'] ?? ''),
            'email'            => trim($_POST['email'] ?? ''),
            'primary_skill'    => trim($_POST['primary_skill'] ?? ''),
            'experience_years' => trim($_POST['experience_years'] ?? ''),
            'city'             => trim($_POST['city'] ?? ''),
            'service_area'     => trim($_POST['service_area'] ?? ''),
            'agree_terms'      => !empty($_POST['agree_terms'])
        ];

        $payload = array_merge($formData, [
            'password'         => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ]);

        $result = registerPartner($payload);

        if ($result['success']) {
            $isSubmittedSuccess = true;
            $submittedPartner = $result['partner'] ?? null;
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join as a Service Provider - <?= htmlspecialchars(APP_NAME); ?></title>
    
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

        .partner-page-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.25rem;
        }

        .partner-card-island {
            width: 100%;
            max-width: 620px;
            background: #FFFFFF;
            border: 1px solid var(--border-light, #E2E8F0);
            border-radius: var(--radius-xl, 24px);
            padding: 2.5rem 2.5rem 2.25rem;
            box-shadow: 0 20px 40px -10px rgba(9, 13, 22, 0.08);
            animation: fadeIn 0.3s ease;
        }

        .partner-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .partner-badge {
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

        .partner-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--brand-navy, #0B132B);
            margin: 0 0 0.5rem;
        }

        .partner-subtitle {
            font-size: 0.92rem;
            color: var(--text-muted, #64748B);
            margin: 0;
            line-height: 1.5;
        }

        .grid-2-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        @media (max-width: 600px) {
            .grid-2-cols {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .partner-card-island {
                padding: 1.75rem 1.25rem;
            }
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

        .input-control, .select-control {
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

        .input-control:focus, .select-control:focus {
            border-color: var(--brand-coral, #FF5500);
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(255, 85, 0, 0.12);
        }

        .input-control.is-invalid, .select-control.is-invalid {
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

        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            margin: 1.5rem 0;
            cursor: pointer;
            font-size: 0.85rem;
            line-height: 1.45;
            color: var(--text-secondary, #334155);
        }

        .checkbox-container input[type="checkbox"] {
            margin-top: 0.15rem;
            width: 18px;
            height: 18px;
            accent-color: var(--brand-coral, #FF5500);
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
            padding: 0.9rem;
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
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        /* Success Status Card */
        .status-success-card {
            text-align: center;
            padding: 1.5rem 0.5rem;
        }

        .status-icon-halo {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #FFFBEB;
            border: 2px solid #FDE68A;
            color: #D97706;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }

        .status-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FCD34D;
            border-radius: var(--radius-full, 9999px);
            font-size: 0.85rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .status-details-box {
            background: var(--bg-surface-alt, #F8FAFC);
            border: 1px solid var(--border-light, #E2E8F0);
            border-radius: var(--radius-md, 14px);
            padding: 1.25rem;
            text-align: left;
            margin: 1.5rem 0;
            font-size: 0.88rem;
        }

        .status-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            border-bottom: 1px solid #EDF2F7;
        }
        .status-detail-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>

    <?php 
    $base = '../../';
    require dirname(__DIR__, 2) . "/components/navbar-main.php"; 
    ?>

    <main class="partner-page-wrap">
        <div class="partner-card-island">

            <?php if ($isSubmittedSuccess && $submittedPartner): ?>
                <!-- SUCCESS & PENDING VERIFICATION NOTICE -->
                <div class="status-success-card">
                    <div class="status-icon-halo">
                        <i data-lucide="clock" style="width: 32px; height: 32px;"></i>
                    </div>

                    <div class="status-pill-badge">
                        <span>● Status: Pending Verification</span>
                    </div>

                    <h1 class="partner-title" style="margin-bottom: 0.5rem;">Application Registered!</h1>
                    <p class="partner-subtitle">
                        Thank you, <strong><?= htmlspecialchars($submittedPartner['full_name']); ?></strong>. Your service provider details have been safely recorded in our database.
                    </p>

                    <div class="status-details-box">
                        <div class="status-detail-row">
                            <span style="color: var(--text-muted);">Trade / Skill:</span>
                            <strong><?= htmlspecialchars($submittedPartner['primary_skill']); ?></strong>
                        </div>
                        <div class="status-detail-row">
                            <span style="color: var(--text-muted);">Mobile:</span>
                            <strong>+91 <?= htmlspecialchars($submittedPartner['mobile']); ?></strong>
                        </div>
                        <div class="status-detail-row">
                            <span style="color: var(--text-muted);">City &amp; Locality:</span>
                            <strong><?= htmlspecialchars($submittedPartner['service_area'] ? ($submittedPartner['service_area'] . ', ' . $submittedPartner['city']) : $submittedPartner['city']); ?></strong>
                        </div>
                        <div class="status-detail-row">
                            <span style="color: var(--text-muted);">Verification Status:</span>
                            <span style="color: #D97706; font-weight: 700;">Pending Admin Review</span>
                        </div>
                    </div>

                    <div style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 12px; padding: 1rem; font-size: 0.85rem; color: #92400E; margin-bottom: 1.5rem; text-align: left; line-height: 1.5;">
                        <i data-lucide="shield-alert" style="width: 16px; height: 16px; display: inline-block; vertical-align: text-bottom; margin-right: 0.35rem;"></i>
                        <strong>Notice:</strong> In accordance with KaamMitra quality &amp; safety policies, your account is in <strong>Pending</strong> verification status. Approved provider access will be activated only after an authorized administrator verifies your trade credentials.
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="partner-login.php" class="btn btn-primary" style="text-decoration: none;">
                            <i data-lucide="log-in" style="width: 16px; height: 16px;"></i>
                            <span>Sign In to Check Status</span>
                        </a>
                        <a href="../../index.php" class="btn btn-outline" style="text-decoration: none;">
                            <span>Return to Home</span>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- REGISTRATION FORM -->
                <div class="partner-header">
                    <div class="partner-badge">
                        <i data-lucide="briefcase" style="width: 14px; height: 14px;"></i>
                        <span>Service Provider Onboarding</span>
                    </div>
                    <h1 class="partner-title">Join as a Service Provider</h1>
                    <p class="partner-subtitle">
                        Connect with verified homeowners in your area. Guaranteed weekly payouts, fair rate cards, and steady local jobs.
                    </p>
                </div>

                <?php if (!empty($topError)): ?>
                    <div class="alert-box alert-error" role="alert">
                        <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span><?= htmlspecialchars($topError); ?></span>
                    </div>
                <?php endif; ?>

                <form action="partner-register.php" method="POST" id="providerRegisterForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">

                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="partnerFullName">Full Legal Name</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon-left"><i data-lucide="user" style="width: 18px; height: 18px;"></i></span>
                            <input 
                                type="text" 
                                id="partnerFullName" 
                                name="full_name" 
                                class="input-control <?= isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                placeholder="e.g. Ramesh Chandra Verma"
                                value="<?= htmlspecialchars($formData['full_name']); ?>"
                                required
                            >
                        </div>
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($errors['full_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- 2-col: Mobile & Email -->
                    <div class="grid-2-cols">
                        <div class="form-group">
                            <label class="form-label" for="partnerMobile">Mobile Number</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="phone" style="width: 18px; height: 18px;"></i></span>
                                <input 
                                    type="tel" 
                                    id="partnerMobile" 
                                    name="mobile" 
                                    maxlength="10"
                                    class="input-control <?= isset($errors['mobile']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="10-digit number"
                                    value="<?= htmlspecialchars($formData['mobile']); ?>"
                                    required
                                >
                            </div>
                            <?php if (isset($errors['mobile'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['mobile']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="partnerEmail">Email Address</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="mail" style="width: 18px; height: 18px;"></i></span>
                                <input 
                                    type="email" 
                                    id="partnerEmail" 
                                    name="email" 
                                    class="input-control <?= isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="name@gmail.com"
                                    value="<?= htmlspecialchars($formData['email']); ?>"
                                    required
                                >
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['email']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2-col: Primary Skill & Experience -->
                    <div class="grid-2-cols">
                        <div class="form-group">
                            <label class="form-label" for="partnerSkill">Primary Skill / Trade</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="wrench" style="width: 18px; height: 18px;"></i></span>
                                <select id="partnerSkill" name="primary_skill" class="select-control" required>
                                    <?php foreach ($skillsList as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key); ?>" <?= ($formData['primary_skill'] === $key) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="partnerExp">Experience</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="award" style="width: 18px; height: 18px;"></i></span>
                                <select id="partnerExp" name="experience_years" class="select-control" required>
                                    <?php foreach ($experienceList as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key); ?>" <?= ($formData['experience_years'] === $key) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 2-col: City & Service Area -->
                    <div class="grid-2-cols">
                        <div class="form-group">
                            <label class="form-label" for="partnerCity">City</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="map-pin" style="width: 18px; height: 18px;"></i></span>
                                <input 
                                    type="text" 
                                    id="partnerCity" 
                                    name="city" 
                                    class="input-control <?= isset($errors['city']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="e.g. Hyderabad"
                                    value="<?= htmlspecialchars($formData['city']); ?>"
                                    required
                                >
                            </div>
                            <?php if (isset($errors['city'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['city']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="partnerServiceArea">Service Area / Locality</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="navigation" style="width: 18px; height: 18px;"></i></span>
                                <input 
                                    type="text" 
                                    id="partnerServiceArea" 
                                    name="service_area" 
                                    class="input-control <?= isset($errors['service_area']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="e.g. Madhapur &amp; Hitec City"
                                    value="<?= htmlspecialchars($formData['service_area']); ?>"
                                    required
                                >
                            </div>
                            <?php if (isset($errors['service_area'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['service_area']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2-col: Password & Confirm Password -->
                    <div class="grid-2-cols">
                        <div class="form-group">
                            <label class="form-label" for="partnerPassword">Create Password</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="lock" style="width: 18px; height: 18px;"></i></span>
                                <input 
                                    type="password" 
                                    id="partnerPassword" 
                                    name="password" 
                                    class="input-control <?= isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="Min 6 characters"
                                    required
                                >
                                <button type="button" class="password-toggle-btn" data-target="partnerPassword" aria-label="Toggle password">
                                    <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="partnerConfirmPass">Confirm Password</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon-left"><i data-lucide="shield-check" style="width: 18px; height: 18px;"></i></span>
                                <input 
                                    type="password" 
                                    id="partnerConfirmPass" 
                                    name="confirm_password" 
                                    class="input-control <?= isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="Re-type password"
                                    required
                                >
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span class="field-error-msg"><?= htmlspecialchars($errors['confirm_password']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Required Terms and Conditions Checkbox (Exact text as required) -->
                    <label class="checkbox-container" for="providerTermsCheckbox">
                        <input 
                            type="checkbox" 
                            id="providerTermsCheckbox" 
                            name="agree_terms" 
                            value="1" 
                            required
                            <?= !empty($formData['agree_terms']) ? 'checked' : ''; ?>
                        >
                        <span>I agree to the Terms and Conditions and Privacy Policy.</span>
                    </label>
                    <?php if (isset($errors['agree_terms'])): ?>
                        <span class="field-error-msg" style="margin-top: -1rem; margin-bottom: 1rem;"><?= htmlspecialchars($errors['agree_terms']); ?></span>
                    <?php endif; ?>

                    <!-- Submit Button: Disabled until checkbox is selected -->
                    <button type="submit" id="providerRegisterBtn" class="btn btn-primary btn-full" disabled>
                        <span>Submit Provider Application</span>
                        <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    </button>
                </form>

                <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light); font-size: 0.88rem; color: var(--text-muted);">
                    <span>Already submitted an application?</span>
                    <a href="partner-login.php" style="color: var(--brand-coral, #FF5500); font-weight: 700; text-decoration: none; margin-left: 0.35rem;">Sign In to check status</a>
                </div>
            <?php endif; ?>

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

        // Keep Register button disabled until checkbox is selected
        const termsCheckbox = document.getElementById('providerTermsCheckbox');
        const registerBtn = document.getElementById('providerRegisterBtn');

        function updateRegisterBtnState() {
            if (!termsCheckbox || !registerBtn) return;
            registerBtn.disabled = !termsCheckbox.checked;
        }

        if (termsCheckbox) {
            termsCheckbox.addEventListener('change', updateRegisterBtnState);
            updateRegisterBtnState();
        }

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
