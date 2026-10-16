<?php
/**
 * KaamMitra - Partner Registration Portal
 * Dedicated onboarding workflow for professional service providers
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/icons.php';
require_once __DIR__ . '/../components/alert.php';

initSecureSession();
$base = getAppBasePath();

// If already logged in as approved partner, redirect to dashboard
if (isPartnerLoggedIn()) {
    $partner = getLoggedInPartner();
    if ($partner && $partner['verification_status'] === 'Approved') {
        header("Location: " . $base . "partner/dashboard.php");
        exit;
    }
}

$errors = [];
$topError = '';
$isSubmittedSuccess = false;
$submittedPartner = null;

$formData = [
    'full_name' => '',
    'mobile' => '',
    'email' => '',
    'gender' => 'Prefer not to say',
    'primary_skill' => 'Master Electrician',
    'experience_years' => '3-5 Years',
    'city' => 'Mumbai',
    'service_area' => '',
    'address' => '',
    'agree_terms' => ''
];

$tradesList = [
    'Master Electrician' => 'Electrician (Wiring, MCB, Inverter, Appliances)',
    'Certified Plumber' => 'Plumber (Piping, Taps, Leakages, Fittings)',
    'AC & Appliance Specialist' => 'AC & Appliance Technician (AC, Fridge, Washing Machine)',
    'Carpenter & Woodworker' => 'Carpenter (Furniture, Modular, Doors & Locks)',
    'Painter & Waterproofing' => 'Painter (Interior, Exterior, Waterproofing)',
    'Deep Cleaning Specialist' => 'Cleaning Specialist (Home, Kitchen, Sofa & Bathroom)'
];

$experienceList = [
    '1-3 Years' => '1 - 3 Years (Junior Technician)',
    '3-5 Years' => '3 - 5 Years (Experienced Technician)',
    '5-10 Years' => '5 - 10 Years (Senior Master Craftsman)',
    '10+ Years' => '10+ Years (Expert Supervisor / Specialist)'
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
            'primary_skill' => trim($_POST['primary_skill'] ?? ''),
            'experience_years' => trim($_POST['experience_years'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'service_area' => trim($_POST['service_area'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'agree_terms' => !empty($_POST['agree_terms'])
        ];

        $registerPayload = array_merge($formData, [
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ]);

        $result = registerPartner($registerPayload);

        if ($result['success']) {
            $isSubmittedSuccess = true;
            $submittedPartner = $result['partner'] ?? null;
            $submittedPartner['id'] = $result['partner_id'] ?? 0;
        } else {
            $topError = $result['message'];
            $errors = $result['errors'] ?? [];
        }
    }
}

$csrfToken = getCsrfToken();
$pageTitle = 'Become a ' . APP_NAME . ' Partner - Service Provider Registration';
$extraHead = '<style>
    body { background-color: var(--bg-page); min-height: 100vh; display: flex; flex-direction: column; color: var(--text-primary); font-family: var(--font-body); }
    .auth-page-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.25rem 4rem; position: relative; background: radial-gradient(circle at 10% 10%, rgba(255, 102, 0, 0.04) 0%, transparent 40%), radial-gradient(circle at 90% 90%, rgba(10, 19, 37, 0.04) 0%, transparent 40%); }
    .auth-container { max-width: 680px; width: 100%; margin: 0 auto; }
    .auth-card { background: #FFFFFF; border-radius: 24px; padding: 2.5rem 2.5rem; box-shadow: 0 10px 30px rgba(10, 19, 37, 0.06), 0 1px 3px rgba(10, 19, 37, 0.04); border: 1px solid var(--border-subtle); }
    @media (max-width: 640px) { .auth-card { padding: 1.75rem 1.25rem; border-radius: 20px; } }
    .auth-card-header { text-align: center; margin-bottom: 2rem; }
    .partner-portal-badge { display: inline-flex; align-items: center; gap: 0.4rem; background: #FFF7ED; color: #EA580C; border: 1px solid #FFEDD5; padding: 0.35rem 0.85rem; border-radius: 100px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem; }
    .auth-segmented-pill { display: flex; background: var(--gray-100); border-radius: 12px; padding: 4px; margin-bottom: 2rem; border: 1px solid var(--gray-200); }
    .auth-segmented-tab { flex: 1; padding: 0.65rem 1rem; text-align: center; font-size: 0.88rem; font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.2s ease; color: var(--text-secondary); border: none; background: transparent; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; }
    .auth-segmented-tab.active { background: #FFFFFF; color: var(--navy-900); font-weight: 700; box-shadow: 0 2px 8px rgba(10, 19, 37, 0.08); }
    .section-separator { display: flex; align-items: center; gap: 0.75rem; margin: 1.75rem 0 1.25rem; font-size: 0.82rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; }
    .section-separator::before, .section-separator::after { content: ""; flex: 1; height: 1px; background: var(--border-subtle); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.15rem; }
    @media (max-width: 600px) { .form-grid-2 { grid-template-columns: 1fr; gap: 1rem; } }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--navy-900); margin-bottom: 0.45rem; }
    .field-hint { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem; }
    .input-icon-wrap { position: relative; }
    .input-icon-wrap input, .input-icon-wrap select, .form-textarea { width: 100%; border-radius: 12px; border: 1.5px solid var(--border-subtle); background-color: #FFFFFF; font-size: 0.95rem; color: var(--navy-900); font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s; }
    .input-icon-wrap input, .input-icon-wrap select { height: 48px; padding-left: 2.75rem; padding-right: 1rem; }
    .form-textarea { padding: 0.75rem 1rem 0.75rem 2.75rem; min-height: 85px; resize: vertical; }
    .input-icon-wrap input:focus, .input-icon-wrap select:focus, .form-textarea:focus { outline: none; border-color: var(--orange-500); box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15); }
    .input-icon-left { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); pointer-events: none; display: flex; align-items: center; }
    .input-icon-textarea-left { position: absolute; left: 1rem; top: 1rem; color: var(--gray-400); pointer-events: none; display: flex; align-items: center; }
    .input-icon-wrap input:focus + .input-icon-left, .input-icon-wrap select:focus + .input-icon-left, .input-icon-wrap:focus-within .input-icon-left, .input-icon-wrap:focus-within .input-icon-textarea-left { color: var(--orange-500); }
    .password-toggle-btn { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--gray-400); cursor: pointer; padding: 0.35rem; display: flex; align-items: center; border-radius: 6px; }
    .password-toggle-btn:hover { color: var(--orange-500); }
    .field-error-msg { color: #DC2626; font-size: 0.78rem; font-weight: 600; margin-top: 0.35rem; display: flex; align-items: center; gap: 0.25rem; }
    .input-has-error input, .input-has-error select, .input-has-error textarea { border-color: #EF4444 !important; background-color: #FEF2F2 !important; }
    .terms-checkbox-wrap { display: flex; align-items: flex-start; gap: 0.65rem; margin: 1.5rem 0 1.25rem; padding: 1rem; background: #F8FAFC; border-radius: 12px; border: 1px solid var(--border-subtle); }
    .terms-checkbox-wrap input[type="checkbox"] { margin-top: 0.2rem; width: 18px; height: 18px; accent-color: var(--orange-500); cursor: pointer; }
    .terms-checkbox-wrap label { font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5; cursor: pointer; }
    .confirmation-banner { background: #ECFDF5; border: 1.5px solid #10B981; border-radius: 18px; padding: 2rem 1.75rem; text-align: center; }
    .timeline-step { display: flex; align-items: flex-start; gap: 0.75rem; text-align: left; margin-bottom: 0.85rem; }
    .timeline-num { width: 24px; height: 24px; border-radius: 50%; background: #0A1325; color: #FFFFFF; font-weight: 700; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
    .perks-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; padding: 1rem; background: #F1F5F9; border-radius: 12px; }
    @media (max-width: 500px) { .perks-grid { grid-template-columns: 1fr; } }
    .perk-item { display: flex; align-items: center; gap: 0.6rem; font-size: 0.82rem; font-weight: 600; color: #1E293B; }
    .perk-item i, .perk-item svg, .perk-item .lucide { color: #10B981 !important; width: 20px !important; height: 20px !important; flex-shrink: 0 !important; }
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

<!-- Main Registration Section -->
<main class="auth-page-main">
    <div class="auth-container">
        
        <div class="auth-card">

            <?php if ($isSubmittedSuccess && $submittedPartner): ?>
                <!-- SUCCESS CONFIRMATION VIEW -->
                <div class="confirmation-banner">
                    <div style="width: 68px; height: 68px; border-radius: 50%; background: #10B981; color: #FFFFFF; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);">
                        <i data-lucide="check" style="width: 36px; height: 36px; stroke-width: 3;"></i>
                    </div>

                    <h2 style="font-size: 1.55rem; font-weight: 800; color: #064E3B; margin-bottom: 0.4rem;">
                        Partner Application Received!
                    </h2>
                    <p style="font-size: 0.95rem; color: #047857; margin-bottom: 1.25rem;">
                        Welcome to the KaamMitra Professional Partner Ecosystem.
                    </p>

                    <div style="display: inline-block; background: #FFFFFF; border: 1.5px dashed #059669; padding: 0.5rem 1.25rem; border-radius: 12px; font-weight: 800; color: #065F46; font-size: 1rem; margin-bottom: 1.5rem;">
                        Partner Ref: <span>KM-PRT-<?= str_pad((string)$submittedPartner['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>

                    <div style="background: #FFFFFF; border-radius: 14px; padding: 1.25rem; text-align: left; margin-bottom: 1.5rem; border: 1px solid #A7F3D0;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; font-size: 0.85rem; color: #1E293B;">
                            <div><strong>Applicant Name:</strong> <?= htmlspecialchars($submittedPartner['full_name']); ?></div>
                            <div><strong>Primary Skill:</strong> <?= htmlspecialchars($submittedPartner['primary_skill']); ?></div>
                            <div><strong>Operating City:</strong> <?= htmlspecialchars($submittedPartner['city']); ?></div>
                            <div><strong>Registered Mobile:</strong> <?= htmlspecialchars($submittedPartner['mobile']); ?></div>
                        </div>
                        <div style="margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px solid #E2E8F0; font-size: 0.82rem; color: #059669; font-weight: 700;">
                            Current Status: <span style="background: #FEF3C7; color: #B45309; padding: 0.2rem 0.5rem; border-radius: 6px;">Pending Admin Verification</span>
                        </div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.85); border-radius: 14px; padding: 1.25rem; text-align: left; margin-bottom: 1.5rem; border: 1px solid #A7F3D0;">
                        <h4 style="font-size: 0.9rem; font-weight: 800; color: #064E3B; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em;">
                            What Happens Next?
                        </h4>
                        <div class="timeline-step">
                            <div class="timeline-num">1</div>
                            <div style="font-size: 0.85rem; color: #1E293B;">
                                <strong>Admin Profile Verification:</strong> Our city hub coordinator verifies your trade experience and references within <strong>4 business hours</strong>.
                            </div>
                        </div>
                        <div class="timeline-step">
                            <div class="timeline-num">2</div>
                            <div style="font-size: 0.85rem; color: #1E293B;">
                                <strong>Free Toolkit &amp; Uniform:</strong> Collect your complimentary KaamMitra toolkit and safety kit from the nearest city service hub.
                            </div>
                        </div>
                        <div class="timeline-step">
                            <div class="timeline-num">3</div>
                            <div style="font-size: 0.85rem; color: #1E293B;">
                                <strong>Go Live &amp; Earn:</strong> Once approved, sign in with your mobile/email and password to accept customer bookings and earn ₹35,000 to ₹55,000/month with 0% commission for your first 30 days!
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                        <a href="<?= $base; ?>partner/login.php" class="btn btn-primary" style="padding: 0.75rem 1.75rem; font-weight: 700; text-decoration: none;">
                            <i data-lucide="log-in" style="width: 16px; height: 16px;"></i>
                            <span>Go to Partner Login</span>
                        </a>
                        <a href="<?= $base; ?>index.php" class="btn btn-outline" style="background: #FFFFFF; text-decoration: none;">
                            <span>Return to Home</span>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- REGISTRATION FORM -->

                <div class="auth-card-header">
                    <div class="partner-portal-badge">
                        <i data-lucide="trending-up" style="width: 14px; height: 14px;"></i>
                        <span>Earn ₹35k - ₹55k/Month &bull; 0% Commission (30 Days)</span>
                    </div>
                    <h1 style="font-size: 1.65rem; font-weight: 800; color: var(--navy-900); margin-bottom: 0.35rem; letter-spacing: -0.02em;">
                        Join as a KaamMitra Partner
                    </h1>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">
                        Register your trade details and start receiving steady verified home service orders
                    </p>
                </div>

                <!-- Segmented Switcher -->
                <div class="auth-segmented-pill">
                    <a href="<?= $base; ?>partner/login.php" class="auth-segmented-tab">
                        <i data-lucide="log-in" style="width: 15px; height: 15px;"></i>
                        <span>Partner Login</span>
                    </a>
                    <button type="button" class="auth-segmented-tab active">
                        <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                        <span>Become a Partner</span>
                    </button>
                </div>

                <!-- Partner Perks Strip -->
                <div class="perks-grid">
                    <div class="perk-item">
                        <i data-lucide="check-circle-2" style="width: 20px; height: 20px; min-width: 20px; min-height: 20px; color: #10B981;"></i>
                        <span>Weekly Direct Bank Payouts</span>
                    </div>
                    <div class="perk-item">
                        <i data-lucide="check-circle-2" style="width: 20px; height: 20px; min-width: 20px; min-height: 20px; color: #10B981;"></i>
                        <span>Free Branded Toolkit &amp; Uniform</span>
                    </div>
                    <div class="perk-item">
                        <i data-lucide="check-circle-2" style="width: 20px; height: 20px; min-width: 20px; min-height: 20px; color: #10B981;"></i>
                        <span>0% Commission For First 30 Days</span>
                    </div>
                    <div class="perk-item">
                        <i data-lucide="check-circle-2" style="width: 20px; height: 20px; min-width: 20px; min-height: 20px; color: #10B981;"></i>
                        <span>Accidental Insurance Coverage</span>
                    </div>
                </div>

                <!-- Top Error Alert -->
                <?php if (!empty($topError)) echo renderAlert($topError, 'error'); ?>

                <form action="<?= $base; ?>partner/register.php" method="POST" id="partnerRegisterForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">

                    <!-- 1. PERSONAL INFORMATION -->
                    <div class="section-separator">
                        <span>1. Personal Information</span>
                    </div>

                    <!-- Full Legal Name -->
                    <div class="form-group <?= isset($errors['full_name']) ? 'input-has-error' : ''; ?>">
                        <label for="full_name">Full Legal Name <span style="color: #EF4444;">*</span></label>
                        <div class="input-icon-wrap">
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                placeholder="e.g. Ramesh Kumar" 
                                value="<?= htmlspecialchars($formData['full_name']); ?>"
                                required
                            >
                            <span class="input-icon-left">
                                <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                            </span>
                        </div>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['full_name']); ?></div>
                        <?php endif; ?>
                        <div class="field-hint">Enter your real name as per government records</div>
                    </div>

                    <!-- Mobile & Email in 2 columns -->
                    <div class="form-grid-2">
                        <!-- Mobile Number -->
                        <div class="form-group <?= isset($errors['mobile']) ? 'input-has-error' : ''; ?>">
                            <label for="mobile">Mobile Number <span style="color: #EF4444;">*</span></label>
                            <div class="input-icon-wrap">
                                <input 
                                    type="tel" 
                                    id="mobile" 
                                    name="mobile" 
                                    placeholder="10-digit mobile number" 
                                    maxlength="10" 
                                    value="<?= htmlspecialchars($formData['mobile']); ?>"
                                    required
                                >
                                <span class="input-icon-left">
                                    <i data-lucide="phone" style="width: 18px; height: 18px;"></i>
                                </span>
                            </div>
                            <?php if (isset($errors['mobile'])): ?>
                                <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['mobile']); ?></div>
                            <?php endif; ?>
                            <div class="field-hint">Used for job alerts and customer OTP calls</div>
                        </div>

                        <!-- Email Address -->
                        <div class="form-group <?= isset($errors['email']) ? 'input-has-error' : ''; ?>">
                            <label for="email">Email Address <span style="color: #EF4444;">*</span></label>
                            <div class="input-icon-wrap">
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    placeholder="e.g. partner@gmail.com" 
                                    value="<?= htmlspecialchars($formData['email']); ?>"
                                    required
                                >
                                <span class="input-icon-left">
                                    <i data-lucide="mail" style="width: 18px; height: 18px;"></i>
                                </span>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                            <div class="field-hint">For monthly payout statements &amp; tax invoices</div>
                        </div>
                    </div>

                    <!-- Gender (Optional) -->
                    <div class="form-group">
                        <label for="gender">Gender <span style="font-weight: normal; color: var(--text-muted);">(Optional)</span></label>
                        <div class="input-icon-wrap">
                            <select id="gender" name="gender">
                                <option value="Male" <?= $formData['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?= $formData['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?= $formData['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                <option value="Prefer not to say" <?= $formData['gender'] === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                            <span class="input-icon-left">
                                <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                            </span>
                        </div>
                    </div>

                    <!-- 2. TRADE & EXPERIENCE -->
                    <div class="section-separator">
                        <span>2. Trade &amp; Professional Experience</span>
                    </div>

                    <div class="form-grid-2">
                        <!-- Primary Skill or Trade -->
                        <div class="form-group <?= isset($errors['primary_skill']) ? 'input-has-error' : ''; ?>">
                            <label for="primary_skill">Primary Skill / Trade <span style="color: #EF4444;">*</span></label>
                            <div class="input-icon-wrap">
                                <select id="primary_skill" name="primary_skill" required>
                                    <?php foreach ($tradesList as $tradeKey => $tradeLabel): ?>
                                        <option value="<?= htmlspecialchars($tradeKey); ?>" <?= $formData['primary_skill'] === $tradeKey ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($tradeKey); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="input-icon-left">
                                    <i data-lucide="wrench" style="width: 18px; height: 18px;"></i>
                                </span>
                            </div>
                            <?php if (isset($errors['primary_skill'])): ?>
                                <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['primary_skill']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Years of Experience -->
                        <div class="form-group <?= isset($errors['experience_years']) ? 'input-has-error' : ''; ?>">
                            <label for="experience_years">Years of Experience <span style="color: #EF4444;">*</span></label>
                            <div class="input-icon-wrap">
                                <select id="experience_years" name="experience_years" required>
                                    <?php foreach ($experienceList as $expKey => $expLabel): ?>
                                        <option value="<?= htmlspecialchars($expKey); ?>" <?= $formData['experience_years'] === $expKey ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($expLabel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="input-icon-left">
                                    <i data-lucide="award" style="width: 18px; height: 18px;"></i>
                                </span>
                            </div>
                            <?php if (isset($errors['experience_years'])): ?>
                                <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['experience_years']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 3. SERVICE LOCATION & ADDRESS -->
                    <div class="section-separator">
                        <span>3. Operating Location &amp; Residential Address</span>
                    </div>

                    <div class="form-grid-2">
                        <!-- Operating City -->
                        <div class="form-group <?= isset($errors['city']) ? 'input-has-error' : ''; ?>">
                            <label for="city">Primary Operating City <span style="color: #EF4444;">*</span></label>
                            <div class="input-icon-wrap">
                                <select id="city" name="city" required>
                                    <?php foreach ($AVAILABLE_CITIES as $c): ?>
                                        <option value="<?= htmlspecialchars($c); ?>" <?= $formData['city'] === $c ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($c); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="input-icon-left">
                                    <i data-lucide="map-pin" style="width: 18px; height: 18px;"></i>
                                </span>
                            </div>
                            <?php if (isset($errors['city'])): ?>
                                <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['city']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Preferred Service Area / Zones -->
                        <div class="form-group">
                            <label for="service_area">Preferred Service Areas <span style="font-weight: normal; color: var(--text-muted);">(Optional)</span></label>
                            <div class="input-icon-wrap">
                                <input 
                                    type="text" 
                                    id="service_area" 
                                    name="service_area" 
                                    placeholder="e.g. Andheri, Bandra, Powai" 
                                    value="<?= htmlspecialchars($formData['service_area']); ?>"
                                >
                                <span class="input-icon-left">
                                    <i data-lucide="navigation" style="width: 18px; height: 18px;"></i>
                                </span>
                            </div>
                            <div class="field-hint">Areas where you prefer to accept bookings</div>
                        </div>
                    </div>

                    <!-- Residential Address -->
                    <div class="form-group <?= isset($errors['address']) ? 'input-has-error' : ''; ?>">
                        <label for="address">Residential Address <span style="color: #EF4444;">*</span></label>
                        <div class="input-icon-wrap">
                            <textarea 
                                id="address" 
                                name="address" 
                                class="form-textarea" 
                                placeholder="Door No, Street Name, Landmark &amp; Pincode" 
                                rows="2"
                                required
                            ><?= htmlspecialchars($formData['address']); ?></textarea>
                            <span class="input-icon-textarea-left">
                                <i data-lucide="home" style="width: 18px; height: 18px;"></i>
                            </span>
                        </div>
                        <?php if (isset($errors['address'])): ?>
                            <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['address']); ?></div>
                        <?php endif; ?>
                        <div class="field-hint">Required for toolkit dispatch and regional hub assignment</div>
                    </div>

                    <!-- 4. ACCOUNT SECURITY -->
                    <div class="section-separator">
                        <span>4. Partner Portal Password</span>
                    </div>

                    <div class="form-grid-2">
                        <!-- Password -->
                        <div class="form-group <?= isset($errors['password']) ? 'input-has-error' : ''; ?>">
                            <label for="password">Create Password <span style="color: #EF4444;">*</span></label>
                            <div class="input-icon-wrap">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Minimum 6 characters" 
                                    autocomplete="new-password"
                                    required
                                >
                                <span class="input-icon-left">
                                    <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                                </span>
                                <button type="button" class="password-toggle-btn" onclick="togglePass('password', this)" aria-label="Toggle password">
                                    <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group <?= isset($errors['confirm_password']) ? 'input-has-error' : ''; ?>">
                            <label for="confirm_password">Confirm Password <span style="color: #EF4444;">*</span></label>
                            <div class="input-icon-wrap">
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    placeholder="Re-enter password" 
                                    autocomplete="new-password"
                                    required
                                >
                                <span class="input-icon-left">
                                    <i data-lucide="check-check" style="width: 18px; height: 18px;"></i>
                                </span>
                                <button type="button" class="password-toggle-btn" onclick="togglePass('confirm_password', this)" aria-label="Toggle password">
                                    <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="field-error-msg"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['confirm_password']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 5. TERMS AND AGREEMENT -->
                    <div class="terms-checkbox-wrap <?= isset($errors['agree_terms']) ? 'input-has-error' : ''; ?>">
                        <input 
                            type="checkbox" 
                            id="agree_terms" 
                            name="agree_terms" 
                            value="1" 
                            <?= !empty($formData['agree_terms']) ? 'checked' : ''; ?>
                            required
                        >
                        <label for="agree_terms">
                            I agree to the <a href="#terms" style="color: var(--orange-500); font-weight: 700; text-decoration: none;">Terms &amp; Conditions</a> and <a href="#agreement" style="color: var(--orange-500); font-weight: 700; text-decoration: none;">KaamMitra Partner Agreement</a>. I certify that all trade details provided above are true and accurate.
                        </label>
                    </div>
                    <?php if (isset($errors['agree_terms'])): ?>
                        <div class="field-error-msg" style="margin-top: -0.5rem; margin-bottom: 1rem;"><i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars($errors['agree_terms']); ?></div>
                    <?php endif; ?>

                    <!-- Safe verification note -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 0.75rem 0.95rem; font-size: 0.78rem; color: #64748B; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="shield-check" style="width: 18px; height: 18px; color: #10B981; flex-shrink: 0;"></i>
                        <span>Identity and skill verification will be securely conducted during the onboarding call by our verified regional supervisor.</span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-full" id="btnSubmitPartner" style="height: 50px; font-size: 1rem; font-weight: 700; border-radius: 12px; justify-content: center; gap: 0.5rem;">
                        <span>Submit Partner Application</span>
                        <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                    </button>

                </form>

                <!-- Login Link -->
                <div style="text-align: center; margin-top: 1.75rem; font-size: 0.9rem; color: var(--text-secondary);">
                    Already registered as a KaamMitra Partner?
                    <a href="<?= $base; ?>partner/login.php" style="color: var(--orange-500); font-weight: 700; text-decoration: none; margin-left: 0.25rem;">
                        Sign In to Partner Portal &rarr;
                    </a>
                </div>

            <?php endif; ?>

        </div>

    </div>
</main>

<?php
$portalName = 'Partner Onboarding Desk';
require __DIR__ . '/../components/footer-portal.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    function togglePass(inputId, btn) {
        const el = document.getElementById(inputId);
        if (!el) return;
        const isPass = el.getAttribute('type') === 'password';
        el.setAttribute('type', isPass ? 'text' : 'password');
        btn.innerHTML = isPass 
            ? '<i data-lucide="eye-off" style="width: 18px; height: 18px;"></i>' 
            : '<i data-lucide="eye" style="width: 18px; height: 18px;"></i>';
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
</script>
</body>
</html>
