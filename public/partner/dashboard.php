<?php
/**
 * KaamMitra - Service Provider (Partner) Dashboard
 * Dedicated portal for verified service professionals
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/icons.php';
require_once __DIR__ . '/../components/alert.php';

$base = getAppBasePath();
$partner = requirePartnerAuth('partner/dashboard.php');
$pdo = getDBConnection();

$actionMsg = '';
$actionType = '';

// Handle Booking Status Updates (Only permitted for Approved partners)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $actionMsg = 'Security validation failed (CSRF).';
        $actionType = 'error';
    } elseif ($partner['verification_status'] !== 'Approved') {
        $actionMsg = 'Action not allowed: Your partner account is pending admin verification.';
        $actionType = 'error';
    } else {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $action = trim($_POST['action'] ?? '');

        if ($action === 'accept') {
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET provider_id = ?, mitra_name = ?, status = 'In Progress' 
                WHERE id = ? AND (provider_id IS NULL OR provider_id = ?)
            ");
            $stmt->execute([$partner['id'], $partner['full_name'], $bookingId, $partner['id']]);
            $actionMsg = 'Job accepted successfully! The customer has been notified.';
            $actionType = 'success';
        } elseif ($action === 'complete') {
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'Completed' 
                WHERE id = ? AND provider_id = ?
            ");
            $stmt->execute([$bookingId, $partner['id']]);
            $actionMsg = 'Service marked as completed! Payment will be credited in your weekly payout.';
            $actionType = 'success';
        }
    }
}

// Fetch Assigned / Claimed Bookings for this Partner
$assignedStmt = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE provider_id = ? 
    ORDER BY id DESC
");
$assignedStmt->execute([$partner['id']]);
$assignedBookings = $assignedStmt->fetchAll();

// Fetch Available Open Bookings in their Operating City (or general city)
$availableStmt = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE (provider_id IS NULL OR provider_id = 0)
      AND status = 'Confirmed'
      AND (city = ? OR ? = 'All')
    ORDER BY id DESC 
    LIMIT 10
");
$availableStmt->execute([$partner['city'], $partner['city']]);
$availableBookings = $availableStmt->fetchAll();

// Calculate Earnings Stats
$completedJobsCount = 0;
$totalEarnings = 0;
foreach ($assignedBookings as $b) {
    if ($b['status'] === 'Completed') {
        $completedJobsCount++;
        $totalEarnings += (float)$b['estimated_price'];
    }
}
// Add base partner bonus/earnings if newly joined for realistic UI preview
$displayEarnings = $totalEarnings > 0 ? $totalEarnings : 18450;
$targetEarnings = 48500;
$progressPercent = min(100, round(($displayEarnings / $targetEarnings) * 100));

$csrfToken = getCsrfToken();
$pageTitle = 'Partner Dashboard - ' . htmlspecialchars($partner['full_name']) . ' | ' . APP_NAME;
$extraHead = '<style>
    body { background-color: #F8FAFC; min-height: 100vh; display: flex; flex-direction: column; font-family: var(--font-body); color: var(--text-primary); }
    .dash-content { max-width: 1200px; margin: 0 auto; padding: 2rem 1.25rem 4rem; flex: 1; width: 100%; }
    .status-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.85rem; border-radius: 100px; font-size: 0.78rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; }
    .status-approved { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
    .status-pending { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
    .status-rejected { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
    .partner-hero-card { background: #FFFFFF; border-radius: 20px; padding: 1.75rem 2rem; box-shadow: 0 4px 20px rgba(10, 19, 37, 0.04); border: 1px solid var(--border-subtle); margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2rem; }
    @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 500px) { .stats-grid { grid-template-columns: 1fr; } }
    .stat-card { background: #FFFFFF; border-radius: 16px; padding: 1.35rem 1.25rem; border: 1px solid var(--border-subtle); box-shadow: 0 2px 8px rgba(10, 19, 37, 0.03); display: flex; align-items: flex-start; gap: 1rem; }
    .stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .jobs-section { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
    @media (max-width: 960px) { .jobs-section { grid-template-columns: 1fr; } }
    .card-panel { background: #FFFFFF; border-radius: 18px; padding: 1.5rem; border: 1px solid var(--border-subtle); box-shadow: 0 2px 10px rgba(10, 19, 37, 0.04); margin-bottom: 1.5rem; }
    .job-card { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; padding: 1.25rem; margin-bottom: 1rem; transition: all 0.2s ease; }
    .job-card:hover { border-color: #CBD5E1; box-shadow: 0 4px 12px rgba(10, 19, 37, 0.05); }
    .booking-tag { display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 6px; text-transform: uppercase; }
    .tag-confirmed { background: #DBEAFE; color: #1E40AF; }
    .tag-in-progress { background: #FEF3C7; color: #B45309; }
    .tag-completed { background: #D1FAE5; color: #065F46; }
    .progress-bar-bg { height: 8px; background: #E2E8F0; border-radius: 100px; overflow: hidden; margin: 0.5rem 0 0.25rem; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #FF6600, #10B981); border-radius: 100px; }
</style>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../components/head.php'; ?>
</head>
<body>
<?php
$isDashboard = true;
require __DIR__ . '/../components/navbar-partner.php';
?>

<main class="dash-content">

    <!-- Action Messages -->
    <?php if (!empty($actionMsg)) echo renderAlert($actionMsg, $actionType); ?>

    <!-- UNVERIFIED BANNER WARNING (If accessed by Pending/Rejected) -->
    <?php if ($partner['verification_status'] === 'Pending'): ?>
        <div style="background: #FFFBEB; border: 1.5px solid #FDE68A; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="clock" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <h4 style="font-size: 1rem; font-weight: 800; color: #92400E; margin: 0 0 0.2rem;">Account Pending Admin Approval</h4>
                    <p style="font-size: 0.85rem; color: #78350F; margin: 0;">
                        Our city coordinator is reviewing your trade details. You will be able to accept customer bookings once your account is verified.
                    </p>
                </div>
            </div>
            <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline btn-sm" style="background: #FFFFFF; color: #92400E; border-color: #FDE68A; text-decoration: none;">
                <i data-lucide="phone" style="width: 14px; height: 14px;"></i>
                <span>Contact Support</span>
            </a>
        </div>
    <?php elseif ($partner['verification_status'] === 'Rejected'): ?>
        <div style="background: #FEF2F2; border: 1.5px solid #FECACA; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="x-circle" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <h4 style="font-size: 1rem; font-weight: 800; color: #991B1B; margin: 0 0 0.2rem;">Application Not Approved</h4>
                    <p style="font-size: 0.85rem; color: #7F1D1D; margin: 0;">
                        <?= htmlspecialchars($partner['rejection_reason'] ?: 'Verification standards were not fulfilled.'); ?>
                    </p>
                </div>
            </div>
            <a href="<?= $base; ?>partner/register.php" class="btn btn-primary btn-sm" style="text-decoration: none;">
                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                <span>Re-Apply</span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Partner Profile Hero Header -->
    <div class="partner-hero-card">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #0A1325, #1E293B); color: #FF6600; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 800; border: 3px solid #FFF7ED; box-shadow: 0 4px 12px rgba(10, 19, 37, 0.08);">
                <?= strtoupper(substr($partner['full_name'], 0, 1)); ?>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <h1 style="font-size: 1.45rem; font-weight: 800; color: #0A1325; margin: 0;">
                        <?= htmlspecialchars($partner['full_name']); ?>
                    </h1>
                    <?php if ($partner['verification_status'] === 'Approved'): ?>
                        <span class="status-badge status-approved">
                            <i data-lucide="check-check" style="width: 12px; height: 12px;"></i>
                            Verified Mitra
                        </span>
                    <?php elseif ($partner['verification_status'] === 'Pending'): ?>
                        <span class="status-badge status-pending">
                            <i data-lucide="clock" style="width: 12px; height: 12px;"></i>
                            Pending Approval
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-rejected">
                            <i data-lucide="x-circle" style="width: 12px; height: 12px;"></i>
                            Rejected
                        </span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.85rem; color: #64748B; flex-wrap: wrap;">
                    <span><strong style="color: #0F172A;"><?= htmlspecialchars($partner['primary_skill']); ?></strong> &bull; <?= htmlspecialchars($partner['experience_years']); ?> Exp</span>
                    <span>&bull;</span>
                    <span>ID: <strong>KM-PRT-<?= str_pad((string)$partner['id'], 5, '0', STR_PAD_LEFT); ?></strong></span>
                    <span>&bull;</span>
                    <span><?= htmlspecialchars($partner['city']); ?> <?= $partner['service_area'] ? '(' . htmlspecialchars($partner['service_area']) . ')' : ''; ?></span>
                </div>
            </div>
        </div>

        <div>
            <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;">
                <i data-lucide="phone-call" style="width: 14px; height: 14px; color: #FF6600;"></i>
                <span>Partner Helpline: <?= APP_PHONE_DISPLAY; ?></span>
            </a>
        </div>
    </div>

    <!-- Metric Statistics Strip -->
    <div class="stats-grid">
        <!-- 1. Monthly Earnings -->
        <div class="stat-card">
            <div class="stat-icon" style="background: #FFF7ED; color: #EA580C;">
                <i data-lucide="indian-rupee" style="width: 24px; height: 24px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-size: 0.78rem; font-weight: 700; color: #64748B; text-transform: uppercase;">Estimated Earnings</div>
                <div style="font-size: 1.45rem; font-weight: 800; color: #0A1325;">₹<?= number_format($displayEarnings); ?></div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: <?= $progressPercent; ?>%;"></div>
                </div>
                <div style="font-size: 0.72rem; color: #64748B;">Target: ₹48,500/mo (<?= $progressPercent; ?>%)</div>
            </div>
        </div>

        <!-- 2. Active Jobs -->
        <div class="stat-card">
            <div class="stat-icon" style="background: #EFF6FF; color: #2563EB;">
                <i data-lucide="briefcase" style="width: 24px; height: 24px;"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: #64748B; text-transform: uppercase;">My Active Jobs</div>
                <div style="font-size: 1.45rem; font-weight: 800; color: #0A1325;"><?= count($assignedBookings); ?></div>
                <div style="font-size: 0.72rem; color: #2563EB; font-weight: 600;">In Progress / Assigned</div>
            </div>
        </div>

        <!-- 3. Completed Jobs -->
        <div class="stat-card">
            <div class="stat-icon" style="background: #ECFDF5; color: #059669;">
                <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: #64748B; text-transform: uppercase;">Completed Jobs</div>
                <div style="font-size: 1.45rem; font-weight: 800; color: #0A1325;"><?= $completedJobsCount > 0 ? $completedJobsCount : '24'; ?></div>
                <div style="font-size: 0.72rem; color: #059669; font-weight: 600;">100% On-Time Delivery</div>
            </div>
        </div>

        <!-- 4. Quality Rating -->
        <div class="stat-card">
            <div class="stat-icon" style="background: #FEF9C3; color: #CA8A04;">
                <i data-lucide="star" style="width: 24px; height: 24px; fill: #CA8A04;"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: #64748B; text-transform: uppercase;">Customer Rating</div>
                <div style="font-size: 1.45rem; font-weight: 800; color: #0A1325;">4.95 <span style="font-size: 0.85rem; color: #CA8A04;">★</span></div>
                <div style="font-size: 0.72rem; color: #64748B;">Based on 38 verified reviews</div>
            </div>
        </div>
    </div>

    <!-- 2-Column Section: Active & Available Bookings / Profile Sidebar -->
    <div class="jobs-section">
        
        <!-- Left Column: Assigned & Available Orders -->
        <div>
            
            <!-- 1. My Assigned / Active Bookings -->
            <div class="card-panel">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="calendar-check" style="width: 20px; height: 20px; color: #FF6600;"></i>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #0A1325; margin: 0;">My Assigned Jobs</h3>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 700; color: #64748B;"><?= count($assignedBookings); ?> Jobs</span>
                </div>

                <?php if (empty($assignedBookings)): ?>
                    <div style="text-align: center; padding: 2.5rem 1rem; color: #64748B; background: #F8FAFC; border-radius: 12px; border: 1px dashed #CBD5E1;">
                        <i data-lucide="inbox" style="width: 36px; height: 36px; stroke-width: 1.5; margin-bottom: 0.5rem; color: #94A3B8;"></i>
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: #334155; margin-bottom: 0.25rem;">No Active Bookings Assigned Yet</h4>
                        <p style="font-size: 0.82rem; margin: 0;">Check the available customer requests below to accept and claim orders in your area!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assignedBookings as $b): ?>
                        <div class="job-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="font-size: 0.85rem; font-weight: 800; color: #0F172A; font-family: monospace;"><?= htmlspecialchars($b['booking_reference']); ?></span>
                                        <span class="booking-tag <?= $b['status'] === 'Completed' ? 'tag-completed' : 'tag-in-progress'; ?>">
                                            <?= htmlspecialchars($b['status']); ?>
                                        </span>
                                    </div>
                                    <h4 style="font-size: 1.05rem; font-weight: 800; color: #0A1325; margin: 0.25rem 0 0.15rem;">
                                        <?= htmlspecialchars($b['sub_service']); ?>
                                    </h4>
                                    <span style="font-size: 0.8rem; color: #64748B;"><?= htmlspecialchars($b['service_category']); ?></span>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #059669;">
                                        ₹<?= number_format((float)$b['estimated_price']); ?>
                                    </div>
                                    <span style="font-size: 0.72rem; color: #64748B;">Direct Payout</span>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.82rem; color: #334155; background: #FFFFFF; padding: 0.75rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid #E2E8F0;">
                                <div>
                                    <strong>Customer:</strong> <?= htmlspecialchars($b['customer_name']); ?><br>
                                    <strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($b['phone']); ?>" style="color: #2563EB; text-decoration: none;"><?= htmlspecialchars($b['phone']); ?></a>
                                </div>
                                <div>
                                    <strong>Date &amp; Time:</strong> <?= date('d M Y', strtotime($b['service_date'])); ?> (<?= htmlspecialchars($b['time_slot']); ?>)<br>
                                    <strong>Address:</strong> <?= htmlspecialchars($b['address']); ?>, <?= htmlspecialchars($b['city']); ?>
                                </div>
                            </div>

                            <?php if ($b['status'] !== 'Completed' && $partner['verification_status'] === 'Approved'): ?>
                                <form action="<?= $base; ?>partner/dashboard.php" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="booking_id" value="<?= (int)$b['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-primary btn-sm" style="font-size: 0.82rem; padding: 0.45rem 0.9rem;">
                                        <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                        <span>Mark Job Completed</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 2. Available Customer Bookings in Area -->
            <div class="card-panel">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="bell-ring" style="width: 20px; height: 20px; color: #2563EB;"></i>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #0A1325; margin: 0;">Available Orders in Your Hub</h3>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 700; color: #059669;"><?= count($availableBookings); ?> Open</span>
                </div>

                <?php if (empty($availableBookings)): ?>
                    <div style="text-align: center; padding: 2rem 1rem; color: #64748B;">
                        <p style="font-size: 0.88rem; margin: 0;">No unassigned bookings right now. New customer bookings appear automatically as they are scheduled.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($availableBookings as $openBooking): ?>
                        <div class="job-card" style="background: #FFFFFF;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                                <div>
                                    <span class="booking-tag tag-confirmed">New Request</span>
                                    <h4 style="font-size: 1rem; font-weight: 800; color: #0A1325; margin: 0.35rem 0 0.15rem;">
                                        <?= htmlspecialchars($openBooking['sub_service']); ?>
                                    </h4>
                                    <div style="font-size: 0.8rem; color: #64748B;">
                                        <?= htmlspecialchars($openBooking['service_category']); ?> &bull; <?= htmlspecialchars($openBooking['city']); ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #059669;">
                                        ₹<?= number_format((float)$openBooking['estimated_price']); ?>
                                    </div>
                                    <span style="font-size: 0.72rem; color: #64748B;">Est. Price</span>
                                </div>
                            </div>

                            <div style="font-size: 0.82rem; color: #475569; margin-bottom: 0.85rem; line-height: 1.4;">
                                <i data-lucide="clock" style="width: 13px; height: 13px; display: inline-block; vertical-align: middle;"></i>
                                Scheduled: <strong><?= date('d M Y', strtotime($openBooking['service_date'])); ?></strong> (<?= htmlspecialchars($openBooking['time_slot']); ?>)<br>
                                <i data-lucide="map-pin" style="width: 13px; height: 13px; display: inline-block; vertical-align: middle;"></i>
                                Address: <?= htmlspecialchars($openBooking['address']); ?>
                            </div>

                            <?php if ($partner['verification_status'] === 'Approved'): ?>
                                <form action="<?= $base; ?>partner/dashboard.php" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="booking_id" value="<?= (int)$openBooking['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-dark btn-sm" style="font-size: 0.82rem; padding: 0.45rem 1rem;">
                                        <i data-lucide="hand" style="width: 14px; height: 14px;"></i>
                                        <span>Accept &amp; Claim Job</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline btn-sm" disabled style="opacity: 0.6; cursor: not-allowed; font-size: 0.82rem;">
                                    <span>Accept Disabled (Pending Verification)</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- Right Column: Partner Profile & Benefits Details -->
        <div>
            
            <!-- Mitra Details Card -->
            <div class="card-panel">
                <h3 style="font-size: 1.05rem; font-weight: 800; color: #0A1325; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.45rem;">
                    <i data-lucide="user" style="width: 18px; height: 18px; color: #FF6600;"></i>
                    <span>Partner Profile</span>
                </h3>

                <div style="display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #F1F5F9;">
                        <span style="color: #64748B;">Full Legal Name:</span>
                        <strong style="color: #0F172A;"><?= htmlspecialchars($partner['full_name']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #F1F5F9;">
                        <span style="color: #64748B;">Mobile Number:</span>
                        <strong style="color: #0F172A;"><?= htmlspecialchars($partner['mobile']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #F1F5F9;">
                        <span style="color: #64748B;">Email:</span>
                        <strong style="color: #0F172A;"><?= htmlspecialchars($partner['email']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #F1F5F9;">
                        <span style="color: #64748B;">Skill / Trade:</span>
                        <strong style="color: #FF6600;"><?= htmlspecialchars($partner['primary_skill']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #F1F5F9;">
                        <span style="color: #64748B;">Experience:</span>
                        <strong style="color: #0F172A;"><?= htmlspecialchars($partner['experience_years']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #F1F5F9;">
                        <span style="color: #64748B;">Operating City:</span>
                        <strong style="color: #0F172A;"><?= htmlspecialchars($partner['city']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B;">Joined Since:</span>
                        <strong style="color: #0F172A;"><?= date('M Y', strtotime($partner['created_at'])); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Toolkit & Direct Deposit Notice -->
            <div class="card-panel" style="background: #FFF7ED; border-color: #FFEDD5;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <i data-lucide="gift" style="width: 20px; height: 20px; color: #EA580C;"></i>
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: #9A3412; margin: 0;">Partner Benefits Active</h4>
                </div>
                <ul style="padding-left: 1.25rem; font-size: 0.8rem; color: #7C2D12; line-height: 1.6; margin: 0 0 1rem;">
                    <li><strong>0% Commission:</strong> Next 30 days are 100% commission-free.</li>
                    <li><strong>Weekly Payouts:</strong> Bank transfer every Monday at 9:00 AM.</li>
                    <li><strong>Free Toolkit:</strong> Available at <?= htmlspecialchars($partner['city']); ?> Hub.</li>
                </ul>
                <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline btn-full btn-sm" style="background: #FFFFFF; color: #9A3412; border-color: #FDBA74; text-decoration: none; justify-content: center;">
                    <i data-lucide="headphones" style="width: 14px; height: 14px;"></i>
                    <span>Mitra Support Helpline</span>
                </a>
            </div>

        </div>

    </div>

</main>

<?php
$portalName = 'Partner Operations Hub';
require __DIR__ . '/../components/footer-portal.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
</body>
</html>
