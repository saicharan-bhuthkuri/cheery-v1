<?php
/**
 * KaamMitra - Service Provider Registration & Onboarding Portal
 * Dedicated two-section onboarding workflow for professional service providers.
 * Displays partner value proposition on the left and full registration/login on the right.
 */

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

initSecureSession();

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

$activeTab = trim($_GET['tab'] ?? $_POST['tab'] ?? 'register');
if ($activeTab !== 'login') {
    $activeTab = 'register';
}

$errors = [];
$topError = '';
$isSubmittedSuccess = false;
$submittedPartner = null;

$loginError = '';
$loginSuccessMsg = '';
$pendingPartner = null;
$rejectedPartner = null;
$approvedPartner = null;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? 'register';

    if (!verifyCsrfToken($csrfToken)) {
        if ($action === 'login') {
            $loginError = 'Security validation failed (CSRF). Please refresh and submit again.';
            $activeTab = 'login';
        } else {
            $topError = 'Security validation failed (CSRF). Please refresh and submit again.';
            $activeTab = 'register';
        }
    } else {
        if ($action === 'register') {
            $activeTab = 'register';
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
        } elseif ($action === 'login') {
            $activeTab = 'login';
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
                $loginError = $result['message'];
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
    <title>Join as a Service Provider - <?= htmlspecialchars(APP_NAME); ?></title>
    
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

    <main class="auth-partner-layout">
        <div class="auth-partner-container">
            
            <!-- Left Section: About KaamMitra Partner Network (Matching Image 2 light visual style) -->
            <div class="auth-partner-left">
                <a href="../../index.php" class="auth-back-btn">
                    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                    <span>Back to Homepage</span>
                </a>

                <div class="auth-light-badge">
                    <i data-lucide="briefcase" style="width: 14px; height: 14px;"></i>
                    <span>PARTNER NETWORK • HIGH EARNINGS</span>
                </div>

                <h1 class="auth-light-heading">
                    Grow Your Trade Business With KaamMitra
                </h1>

                <p class="auth-light-subtext">
                    Join India's trusted on-demand home maintenance network. Get daily local repair requests, guaranteed weekly direct bank payouts, and zero commission surprises.
                </p>

                <div class="auth-light-features">
                    <div class="auth-light-feature-item">
                        <div class="auth-light-icon-box box-teal">
                            <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="auth-feature-info">
                            <h4 class="auth-light-feature-title">Steady Local Customers</h4>
                            <p class="auth-light-feature-desc">Daily confirmed doorstep repair leads from homeowners in your immediate service area.</p>
                        </div>
                    </div>

                    <div class="auth-light-feature-item">
                        <div class="auth-light-icon-box box-indigo">
                            <i data-lucide="wallet" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="auth-feature-info">
                            <h4 class="auth-light-feature-title">Guaranteed Weekly Payouts</h4>
                            <p class="auth-light-feature-desc">Direct bank deposits every Tuesday with transparent standardized rates and zero hidden cuts.</p>
                        </div>
                    </div>

                    <div class="auth-light-feature-item">
                        <div class="auth-light-icon-box box-coral">
                            <i data-lucide="smartphone" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="auth-feature-info">
                            <h4 class="auth-light-feature-title">Easy Mobile Management</h4>
                            <p class="auth-light-feature-desc">Accept jobs, view customer location, navigate directly, and manage your daily schedule on the go.</p>
                        </div>
                    </div>

                    <div class="auth-light-feature-item">
                        <div class="auth-light-icon-box box-purple">
                            <i data-lucide="trending-up" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="auth-feature-info">
                            <h4 class="auth-light-feature-title">High Income &amp; Reputation</h4>
                            <p class="auth-light-feature-desc">Earn ₹45,000+ monthly with performance bonuses, 5-star customer ratings, and repeat clients.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section: Elevated White Form Card (Matching Image 2) -->
            <div class="auth-partner-right">
                <div class="auth-white-card">

                <?php if ($isSubmittedSuccess && $submittedPartner): ?>
                    <!-- SUCCESS & PENDING VERIFICATION NOTICE -->
                    <div class="status-success-card">
                        <div class="status-icon-halo">
                            <i data-lucide="clock" style="width: 32px; height: 32px;"></i>
                        </div>

                        <div class="status-pill-badge">
                            <span>● Status: Pending Verification</span>
                        </div>

                        <h2 class="auth-title" style="margin-bottom: 0.5rem; text-align: center;">Application Registered!</h2>
                        <p class="auth-subtitle" style="text-align: center;">
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

                        <div class="alert-box alert-pending">
                            <i data-lucide="shield-alert" style="width: 18px; height: 18px; flex-shrink: 0; color: #D97706;"></i>
                            <span>In accordance with KaamMitra quality &amp; safety policies, your account is in <strong>Pending</strong> verification status. Approved provider access will be activated once an authorized administrator verifies your trade credentials.</span>
                        </div>

                        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1.5rem;">
                            <a href="login.php" class="btn btn-primary">
                                <i data-lucide="log-in" style="width: 16px; height: 16px;"></i>
                                <span>Sign In to Check Status</span>
                            </a>
                            <a href="../../index.php" class="btn btn-outline">
                                <span>Return to Home</span>
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <div class="auth-card-top-row">
                        <h2 class="auth-card-heading">Join as a Service Provider</h2>
                        <p class="auth-card-subheading">Connect with verified homeowners in your area. Guaranteed weekly payouts, fair rate cards, and steady local jobs.</p>
                    </div>

                    <!-- Interactive Tab Switcher -->
                    <div class="auth-tabs-nav" role="tablist">
                        <button type="button" class="auth-tab-btn <?= $activeTab === 'register' ? 'active' : ''; ?>" id="tabRegisterBtn" role="tab" aria-selected="<?= $activeTab === 'register' ? 'true' : 'false'; ?>">
                            <i data-lucide="user-plus" style="width: 15px; height: 15px; margin-right: 0.4rem;"></i>
                            <span>Join as Provider</span>
                        </button>
                        <button type="button" class="auth-tab-btn <?= $activeTab === 'login' ? 'active' : ''; ?>" id="tabLoginBtn" role="tab" aria-selected="<?= $activeTab === 'login' ? 'true' : 'false'; ?>">
                            <i data-lucide="log-in" style="width: 15px; height: 15px; margin-right: 0.4rem;"></i>
                            <span>Sign In</span>
                        </button>
                    </div>

                    <!-- TAB 1: REGISTRATION FORM -->
                    <div id="registerTabContent" style="display: <?= $activeTab === 'register' ? 'block' : 'none'; ?>;">
                        
                        <?php if (!empty($topError)): ?>
                            <div class="alert-box alert-error" role="alert">
                                <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                                <span><?= htmlspecialchars($topError); ?></span>
                            </div>
                        <?php endif; ?>

                        <form action="register.php" method="POST" id="providerRegisterForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="register">

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

                        <div class="switch-footer-link">
                            <span>Already submitted an application?</span>
                            <a href="javascript:void(0)" id="switchToLoginLink">Sign In to check status</a>
                        </div>
                    </div>

                    <!-- TAB 2: LOGIN FORM -->
                    <div id="loginTabContent" style="display: <?= $activeTab === 'login' ? 'block' : 'none'; ?>;">
                        
                        <?php if (!empty($loginSuccessMsg)): ?>
                            <div class="alert-box alert-success" role="alert">
                                <i data-lucide="check-circle" style="width: 18px; height: 18px; flex-shrink: 0; color: #10B981;"></i>
                                <span><?= htmlspecialchars($loginSuccessMsg); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($loginError)): ?>
                            <div class="alert-box alert-error" role="alert">
                                <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0; color: #EF4444;"></i>
                                <span><?= htmlspecialchars($loginError); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($pendingPartner): ?>
                            <div class="alert-box alert-pending" role="alert">
                                <i data-lucide="clock" style="width: 20px; height: 20px; flex-shrink: 0; color: #D97706; margin-top: 0.1rem;"></i>
                                <div>
                                    <strong style="display: block; font-size: 0.92rem; margin-bottom: 0.2rem;">Application Under Review (Status: Pending)</strong>
                                    <span>Hello <?= htmlspecialchars($pendingPartner['full_name']); ?>, your service provider account is currently awaiting administrative review and background check. Approved access will be granted once an administrator verifies your application.</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($approvedPartner): ?>
                            <div class="alert-box alert-success" role="alert">
                                <i data-lucide="check-circle" style="width: 20px; height: 20px; flex-shrink: 0; color: #10B981;"></i>
                                <div>
                                    <strong style="display: block; font-size: 0.95rem; margin-bottom: 0.2rem;">Welcome, <?= htmlspecialchars($approvedPartner['full_name']); ?>!</strong>
                                    <span>Your service provider account is verified and approved (Status: Active). Doorstep repair dispatches are active in your area.</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($rejectedPartner): ?>
                            <div class="alert-box alert-rejected" role="alert">
                                <i data-lucide="x-circle" style="width: 20px; height: 20px; flex-shrink: 0; color: #DC2626;"></i>
                                <div>
                                    <strong style="display: block; font-size: 0.95rem; margin-bottom: 0.2rem;">Application Status: Not Approved</strong>
                                    <span>We could not verify your trade credentials at this time. Please contact partner support for more details.</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="register.php" method="POST" id="providerLoginForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="login">

                            <div class="form-group">
                                <label class="form-label" for="partnerLoginIdent">Registered Mobile Number or Email</label>
                                <div class="input-icon-wrap">
                                    <span class="input-icon-left"><i data-lucide="user" style="width: 18px; height: 18px;"></i></span>
                                    <input 
                                        type="text" 
                                        id="partnerLoginIdent" 
                                        name="identifier" 
                                        class="input-control" 
                                        placeholder="10-digit mobile or your email"
                                        required 
                                        autocomplete="username"
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="partnerLoginPass">Password</label>
                                <div class="input-icon-wrap">
                                    <span class="input-icon-left"><i data-lucide="lock" style="width: 18px; height: 18px;"></i></span>
                                    <input 
                                        type="password" 
                                        id="partnerLoginPass" 
                                        name="password" 
                                        class="input-control" 
                                        placeholder="Enter your password"
                                        required 
                                        autocomplete="current-password"
                                    >
                                    <button type="button" class="password-toggle-btn" data-target="partnerLoginPass" aria-label="Toggle password">
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
                            <span>Need to submit a new application?</span>
                            <a href="javascript:void(0)" id="switchToRegisterLink">Join as a Service Provider</a>
                        </div>
                    </div>

                <?php endif; ?>

                <!-- Customer Auth Switch Callout -->
                <div class="cross-portal-callout">
                    <span>Looking to book a home service?</span>
                    <a href="../customer/login.php">
                        <span>Customer Sign In</span>
                        <i data-lucide="arrow-up-right" style="width: 14px; height: 14px;"></i>
                    </a>
                </div>

                </div> <!-- /.auth-white-card -->
            </div> <!-- /.auth-partner-right -->
        </div> <!-- /.auth-partner-container -->
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
