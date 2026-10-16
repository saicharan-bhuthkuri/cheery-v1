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
                        <a href="login.php" class="btn btn-primary" style="text-decoration: none;">
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

                <form action="register.php" method="POST" id="providerRegisterForm" novalidate>
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
                    <a href="login.php" style="color: var(--brand-coral, #FF5500); font-weight: 700; text-decoration: none; margin-left: 0.35rem;">Sign In to check status</a>
                </div>
            <?php endif; ?>

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
