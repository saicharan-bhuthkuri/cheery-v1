<?php
/**
 * KaamMitra - Customer Dashboard
 * Protected customer portal with profile & active bookings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../data/services.php';

$base = getAppBasePath();
$customer = requireCustomerAuth($base . 'customer/dashboard.php');

$pdo = getDBConnection();

// Fetch customer's active and past bookings
$stmt = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE customer_id = ? OR phone = ? 
    ORDER BY id DESC
");
$stmt->execute([$customer['id'], $customer['mobile']]);
$bookings = $stmt->fetchAll();

$totalBookings = count($bookings);
$activeBookings = array_filter($bookings, fn($b) => in_array($b['status'], ['Confirmed', 'In Progress']));
$completedBookings = array_filter($bookings, fn($b) => $b['status'] === 'Completed');

$pageTitle = 'Customer Dashboard - ' . APP_NAME;
$assetPath = $base . 'assets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../components/head.php'; ?>
    <style>
        body {
            background-color: #F8FAFC;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .dashboard-header {
            background: #0A1325;
            color: #FFFFFF;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .dash-nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dash-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.25rem;
            width: 100%;
            flex: 1;
        }
        .dash-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 1.75rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .dash-grid {
                grid-template-columns: 1fr;
            }
        }
        .profile-card {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: 0 4px 20px -2px rgba(10, 19, 37, 0.06);
            border: 1px solid var(--border-subtle);
        }
        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF6600, #E65100);
            color: #FFFFFF;
            font-size: 1.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 14px rgba(255, 102, 0, 0.35);
        }
        .profile-field {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.88rem;
        }
        .profile-field-icon {
            color: var(--gray-400);
            margin-top: 2px;
            flex-shrink: 0;
        }
        .profile-field-value {
            color: var(--navy-900);
            font-weight: 500;
            word-break: break-word;
        }
        .profile-field-label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .booking-card {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 2px 8px rgba(10, 19, 37, 0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .booking-card:hover {
            box-shadow: 0 8px 24px rgba(10, 19, 37, 0.08);
            transform: translateY(-2px);
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .status-Confirmed {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .status-InProgress {
            background: #EFF6FF;
            color: #2563EB;
            border: 1px solid #BFDBFE;
        }
        .status-Completed {
            background: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
        }
        .status-Cancelled {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        .empty-bookings-box {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 3.5rem 2rem;
            text-align: center;
            border: 1.5px dashed var(--gray-300);
        }
        .quick-cat-btn {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--navy-900);
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.2s ease;
        }
        .quick-cat-btn:hover {
            background: #FFFFFF;
            border-color: var(--orange-500);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 102, 0, 0.15);
        }
    </style>
</head>
<body>

<!-- Dashboard Header -->
<header class="dashboard-header">
    <div class="dash-nav-container">
        <a href="<?= $base; ?>index.php" style="display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #FF6600, #E65100); display: flex; align-items: center; justify-content: center; color: #FFFFFF;">
                <i data-lucide="wrench" style="width: 20px; height: 20px;"></i>
            </div>
            <div>
                <span style="font-size: 1.35rem; font-weight: 800; color: #FFFFFF;">Kaam<span style="color: #FF6600;">Mitra</span></span>
                <span style="font-size: 0.65rem; color: rgba(255,255,255,0.5); display: block; text-transform: uppercase; letter-spacing: 0.05em;">Customer Hub</span>
            </div>
        </a>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="<?= $base; ?>customer/book.php" class="btn btn-primary btn-sm" style="border-radius: 8px; text-decoration: none;">
                <i data-lucide="calendar-plus" style="width: 16px; height: 16px;"></i>
                <span>Book a Service</span>
            </a>
            <a href="<?= $base; ?>index.php" class="btn btn-outline btn-sm" style="color: #FFFFFF; border-color: rgba(255,255,255,0.25); border-radius: 8px; text-decoration: none;">
                <i data-lucide="home" style="width: 15px; height: 15px;"></i>
                <span>Home</span>
            </a>
            <a href="<?= $base; ?>customer/logout.php" class="btn btn-ghost btn-sm" style="color: rgba(255,255,255,0.7); border-radius: 8px; text-decoration: none;" title="Log out">
                <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</header>

<main class="dash-content">
    <!-- Top Welcome Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--navy-900); margin: 0 0 0.35rem;">
                Welcome, <?= htmlspecialchars($customer['full_name']); ?>!
            </h1>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.95rem;">
                Manage your home service requests and verified Mitra appointments.
            </p>
        </div>
        <div>
            <a href="<?= $base; ?>customer/book.php" class="btn btn-primary" style="padding: 0.75rem 1.4rem; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 14px rgba(255,102,0,0.3); text-decoration: none;">
                <i data-lucide="calendar-plus" style="width: 18px; height: 18px;"></i>
                <span>Schedule New Mitra</span>
            </a>
        </div>
    </div>

    <!-- Layout: Left Profile, Right Bookings -->
    <div class="dash-grid">
        <!-- Profile Column -->
        <div>
            <div class="profile-card">
                <div style="text-align: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                    <div class="profile-avatar" style="margin: 0 auto 0.75rem;">
                        <?= strtoupper(substr($customer['full_name'], 0, 1)); ?>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--navy-900); margin: 0 0 0.25rem;">
                        <?= htmlspecialchars($customer['full_name']); ?>
                    </h2>
                    <span style="font-size: 0.8rem; background: var(--gray-100); color: var(--text-secondary); padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 600;">
                        Verified Customer
                    </span>
                </div>

                <div class="profile-field">
                    <i data-lucide="phone" class="profile-field-icon" style="width: 16px; height: 16px;"></i>
                    <div>
                        <span class="profile-field-label">Mobile Number</span>
                        <span class="profile-field-value">+91 <?= htmlspecialchars($customer['mobile']); ?></span>
                    </div>
                </div>

                <div class="profile-field">
                    <i data-lucide="mail" class="profile-field-icon" style="width: 16px; height: 16px;"></i>
                    <div>
                        <span class="profile-field-label">Email Address</span>
                        <span class="profile-field-value"><?= htmlspecialchars($customer['email']); ?></span>
                    </div>
                </div>

                <div class="profile-field">
                    <i data-lucide="users" class="profile-field-icon" style="width: 16px; height: 16px;"></i>
                    <div>
                        <span class="profile-field-label">Gender</span>
                        <span class="profile-field-value"><?= htmlspecialchars($customer['gender'] ?? 'Not specified'); ?></span>
                    </div>
                </div>

                <div class="profile-field">
                    <i data-lucide="map-pin" class="profile-field-icon" style="width: 16px; height: 16px;"></i>
                    <div>
                        <span class="profile-field-label">Service City</span>
                        <span class="profile-field-value"><?= htmlspecialchars($customer['city'] ?? 'Mumbai'); ?><?= !empty($customer['pincode']) ? ' - ' . htmlspecialchars($customer['pincode']) : ''; ?></span>
                    </div>
                </div>

                <?php if (!empty($customer['address_line'])): ?>
                <div class="profile-field">
                    <i data-lucide="home" class="profile-field-icon" style="width: 16px; height: 16px;"></i>
                    <div>
                        <span class="profile-field-label">Saved Address</span>
                        <span class="profile-field-value"><?= htmlspecialchars($customer['address_line']); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="profile-field" style="margin-bottom: 0;">
                    <i data-lucide="calendar" class="profile-field-icon" style="width: 16px; height: 16px;"></i>
                    <div>
                        <span class="profile-field-label">Customer Since</span>
                        <span class="profile-field-value"><?= date('F Y', strtotime($customer['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- KaamMitra Assurance Card -->
            <div style="background: linear-gradient(135deg, #0A1325, #142442); color: #FFFFFF; border-radius: 20px; padding: 1.5rem; margin-top: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <i data-lucide="shield-check" style="width: 22px; height: 22px; color: #10B981;"></i>
                    <h3 style="font-size: 1rem; font-weight: 700; color: #FFFFFF; margin: 0;">Mitra Protection Pledge</h3>
                </div>
                <p style="font-size: 0.82rem; color: rgba(255,255,255,0.7); line-height: 1.5; margin: 0 0 1rem;">
                    Every appointment includes full background-verified Mitras, transparent standard rates, zero advance, and a 30-day rework warranty.
                </p>
                <a href="tel:<?= APP_PHONE; ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--orange-400); font-size: 0.82rem; font-weight: 700; text-decoration: none;">
                    <i data-lucide="headphones" style="width: 15px; height: 15px;"></i>
                    <span>24/7 Helpline: <?= APP_PHONE_DISPLAY; ?></span>
                </a>
            </div>
        </div>

        <!-- Bookings Column -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--navy-900); margin: 0;">
                    My Bookings <span style="font-size: 0.95rem; color: var(--text-muted); font-weight: 500;">(<?= $totalBookings; ?>)</span>
                </h2>
                <div style="display: flex; gap: 0.5rem;">
                    <span style="font-size: 0.8rem; background: #ECFDF5; color: #059669; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 9999px;">
                        <?= count($activeBookings); ?> Active
                    </span>
                    <span style="font-size: 0.8rem; background: var(--gray-100); color: var(--text-secondary); font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 9999px;">
                        <?= count($completedBookings); ?> Completed
                    </span>
                </div>
            </div>

            <?php if (empty($bookings)): ?>
                <!-- Empty State -->
                <div class="empty-bookings-box">
                    <div style="width: 68px; height: 68px; border-radius: 50%; background: var(--orange-50); color: var(--orange-500); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                        <i data-lucide="calendar-x" style="width: 32px; height: 32px;"></i>
                    </div>
                    <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--navy-900); margin: 0 0 0.5rem;">No Bookings Yet</h3>
                    <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto 1.75rem; font-size: 0.92rem;">
                        You haven't scheduled any services yet. Choose from our most popular home service categories below:
                    </p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; max-width: 600px; margin: 0 auto 2rem;">
                        <a href="book.php?service=plumbing&sub=Tap%20Repair%20/%20Replacement&price=199" class="quick-cat-btn">
                            <i data-lucide="droplet" style="color: #0284C7; width: 18px; height: 18px;"></i>
                            <span>Plumbing (₹199)</span>
                        </a>
                        <a href="book.php?service=electrical&sub=Switchboard%20/%20Socket%20Repair&price=149" class="quick-cat-btn">
                            <i data-lucide="zap" style="color: #F59E0B; width: 18px; height: 18px;"></i>
                            <span>Electrical (₹149)</span>
                        </a>
                        <a href="book.php?service=appliances&sub=AC%20Jet%20Pump%20Foam%20Servicing&price=499" class="quick-cat-btn">
                            <i data-lucide="cpu" style="color: #8B5CF6; width: 18px; height: 18px;"></i>
                            <span>AC Cleaning (₹499)</span>
                        </a>
                        <a href="book.php?service=cleaning&sub=Intense%20Bathroom%20Deep%20Cleaning&price=399" class="quick-cat-btn">
                            <i data-lucide="sparkles" style="color: #10B981; width: 18px; height: 18px;"></i>
                            <span>Cleaning (₹399)</span>
                        </a>
                    </div>

                    <a href="book.php" class="btn btn-primary" style="padding: 0.85rem 2rem; border-radius: 12px; font-weight: 700;">
                        <i data-lucide="calendar-plus" style="width: 18px; height: 18px;"></i>
                        <span>Book a Service Now</span>
                    </a>
                </div>
            <?php else: ?>
                <!-- Render Bookings List -->
                <?php foreach ($bookings as $b): ?>
                    <?php 
                        $statusClass = 'status-' . str_replace(' ', '', $b['status']);
                        $serviceName = ucwords(str_replace('-', ' ', $b['service_category']));
                    ?>
                    <div class="booking-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.85rem;">
                            <div>
                                <span style="font-size: 0.75rem; font-weight: 700; color: var(--orange-500); letter-spacing: 0.05em; text-transform: uppercase;">
                                    Booking ID: <?= htmlspecialchars($b['booking_reference']); ?>
                                </span>
                                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--navy-900); margin: 0.2rem 0 0;">
                                    <?= htmlspecialchars($b['sub_service'] ?: $serviceName); ?>
                                </h3>
                            </div>
                            <div>
                                <span class="status-pill <?= $statusClass; ?>">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                    <?= htmlspecialchars($b['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; font-size: 0.88rem; margin-bottom: 1rem;">
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Scheduled Date &amp; Slot</span>
                                <strong style="color: var(--navy-900); display: flex; align-items: center; gap: 0.35rem; margin-top: 0.2rem;">
                                    <i data-lucide="calendar" style="width: 15px; height: 15px; color: var(--orange-500);"></i>
                                    <?= date('d M Y', strtotime($b['service_date'])); ?>
                                </strong>
                                <span style="color: var(--text-secondary); font-size: 0.82rem;"><?= htmlspecialchars($b['time_slot']); ?></span>
                            </div>

                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Assigned Mitra</span>
                                <strong style="color: var(--navy-900); display: flex; align-items: center; gap: 0.35rem; margin-top: 0.2rem;">
                                    <i data-lucide="user-check" style="width: 15px; height: 15px; color: #10B981;"></i>
                                    <?= htmlspecialchars($b['mitra_name'] ?? 'Rajesh Kumar'); ?>
                                </strong>
                                <span style="color: #F59E0B; font-weight: 600; font-size: 0.82rem;">★ <?= htmlspecialchars($b['mitra_rating'] ?? '4.95'); ?> (Verified)</span>
                            </div>

                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Standard Visiting Fee</span>
                                <strong style="color: #059669; font-size: 1.15rem; display: block; margin-top: 0.2rem;">
                                    ₹<?= number_format($b['estimated_price']); ?>
                                </strong>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Zero Advance • Pay After Service</span>
                            </div>
                        </div>

                        <div style="background: var(--gray-50); border-radius: 8px; padding: 0.65rem 0.85rem; font-size: 0.82rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="map-pin" style="width: 15px; height: 15px; color: var(--gray-400); flex-shrink: 0;"></i>
                            <span><?= htmlspecialchars($b['address']); ?>, <?= htmlspecialchars($b['city']); ?><?= !empty($b['pincode']) ? ' - ' . htmlspecialchars($b['pincode']) : ''; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php 
$portalName = 'Customer Hub';
require_once __DIR__ . '/../components/footer-portal.php'; 
?>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>
</body>
</html>
