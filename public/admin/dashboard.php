<?php
/**
 * KaamMitra - Dedicated Protected Administrator Dashboard
 * Route: dashboard.php (also dashboard.php/ and dashboard.php.php)
 * 
 * Strict access control: Only authenticated admins can access this dashboard.
 * Shows customers and service providers in separate lists with counts and access to full details.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/icons.php';
require_once __DIR__ . '/../components/alert.php';

$base = getAppBasePath();

// Strict Admin Route Protection
$admin = requireAdminAuth('admin/dashboard.php');

$pdo = getDBConnection();

$actionMsg = '';
$actionType = '';

// Handle Moderation Actions (Approve / Reject / Reset Provider)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $actionMsg = 'Security validation failed (CSRF token mismatch).';
        $actionType = 'error';
    } else {
        $actionTypePost = trim($_POST['action_type'] ?? '');
        $providerId = (int)($_POST['provider_id'] ?? 0);

        if ($providerId > 0) {
            if ($actionTypePost === 'approve_partner') {
                $stmt = $pdo->prepare("UPDATE service_providers SET verification_status = 'Approved', rejection_reason = NULL, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$providerId]);
                $actionMsg = "Provider #MITRA-" . str_pad($providerId, 3, '0', STR_PAD_LEFT) . " has been successfully APPROVED. They can now log in and take service jobs.";
                $actionType = 'success';
            } elseif ($actionTypePost === 'reject_partner') {
                $reason = trim($_POST['rejection_reason'] ?? '');
                if (empty($reason)) {
                    $reason = 'Trade experience certificate or address verification documents did not meet requirements.';
                }
                $stmt = $pdo->prepare("UPDATE service_providers SET verification_status = 'Rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$reason, $providerId]);
                $actionMsg = "Provider #MITRA-" . str_pad($providerId, 3, '0', STR_PAD_LEFT) . " has been marked as REJECTED.";
                $actionType = 'warning';
            } elseif ($actionTypePost === 'reset_partner_pending') {
                $stmt = $pdo->prepare("UPDATE service_providers SET verification_status = 'Pending', rejection_reason = NULL, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$providerId]);
                $actionMsg = "Provider #MITRA-" . str_pad($providerId, 3, '0', STR_PAD_LEFT) . " status reset to PENDING review.";
                $actionType = 'info';
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 1. Fetch Aggregated Platform Statistics & Counts
// -----------------------------------------------------------------------------
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalProviders = (int)$pdo->query("SELECT COUNT(*) FROM service_providers")->fetchColumn();
$pendingProvidersCount = (int)$pdo->query("SELECT COUNT(*) FROM service_providers WHERE verification_status = 'Pending'")->fetchColumn();
$approvedProvidersCount = (int)$pdo->query("SELECT COUNT(*) FROM service_providers WHERE verification_status = 'Approved'")->fetchColumn();
$rejectedProvidersCount = (int)$pdo->query("SELECT COUNT(*) FROM service_providers WHERE verification_status = 'Rejected'")->fetchColumn();

$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$completedBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Completed'")->fetchColumn();
$activeBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('Confirmed', 'In Progress')")->fetchColumn();

// -----------------------------------------------------------------------------
// 2. Fetch Customers List with Booking Stats
// -----------------------------------------------------------------------------
$customersStmt = $pdo->query("
    SELECT 
        c.*,
        COUNT(b.id) AS total_bookings,
        MAX(b.created_at) AS latest_booking_at
    FROM customers c
    LEFT JOIN bookings b ON (b.customer_id = c.id OR b.phone = c.mobile)
    GROUP BY c.id
    ORDER BY c.id DESC
");
$customers = $customersStmt->fetchAll();

// -----------------------------------------------------------------------------
// 3. Fetch Service Providers List with Assigned Bookings Stats
// -----------------------------------------------------------------------------
$providersStmt = $pdo->query("
    SELECT 
        p.*,
        COUNT(b.id) AS assigned_bookings,
        SUM(CASE WHEN b.status = 'Completed' THEN 1 ELSE 0 END) AS completed_jobs
    FROM service_providers p
    LEFT JOIN bookings b ON b.provider_id = p.id
    GROUP BY p.id
    ORDER BY p.id DESC
");
$providers = $providersStmt->fetchAll();

// -----------------------------------------------------------------------------
// 4. Fetch All Bookings to provide rich details in Customer & Provider Modals
// -----------------------------------------------------------------------------
$allBookingsStmt = $pdo->query("
    SELECT 
        id, booking_reference, customer_id, provider_id, customer_name, phone,
        service_category, sub_service, city, address, pincode, service_date,
        time_slot, estimated_price, status, mitra_name, mitra_rating, created_at
    FROM bookings
    ORDER BY id DESC
");
$allBookings = $allBookingsStmt->fetchAll();

// Index bookings by customer (by customer_id and phone)
$customerBookingsMap = [];
foreach ($allBookings as $bk) {
    if (!empty($bk['customer_id'])) {
        $customerBookingsMap['id_' . $bk['customer_id']][] = $bk;
    }
    if (!empty($bk['phone'])) {
        $customerBookingsMap['phone_' . $bk['phone']][] = $bk;
    }
}

// Index bookings by provider_id
$providerBookingsMap = [];
foreach ($allBookings as $bk) {
    if (!empty($bk['provider_id'])) {
        $providerBookingsMap[$bk['provider_id']][] = $bk;
    }
}

// Active Tab from Query (customers | providers)
$activeTab = trim($_GET['tab'] ?? 'customers');
if (!in_array($activeTab, ['customers', 'providers'], true)) {
    $activeTab = 'customers';
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Operations Console - <?= htmlspecialchars(APP_NAME); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= $base; ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base; ?>assets/css/style.css">
    <script src="<?= $base; ?>assets/js/lucide.min.js"></script>

    <style>
        :root {
            --admin-dark-bg: #090F1D;
            --admin-header-bg: #0B132B;
            --admin-card-bg: #FFFFFF;
            --admin-border: #E2E8F0;
            --admin-text-main: #0F172A;
            --admin-text-sub: #64748B;
            --admin-orange: #FF6600;
            --admin-orange-hover: #E65100;
            --admin-orange-soft: #FFF5EE;
            --admin-green: #10B981;
            --admin-green-soft: #ECFDF5;
            --admin-amber: #F59E0B;
            --admin-amber-soft: #FEF3C7;
            --admin-red: #EF4444;
            --admin-red-soft: #FEF2F2;
            --admin-blue: #3B82F6;
            --admin-blue-soft: #EFF6FF;
            --font-mono: 'JetBrains Mono', monospace;
        }

        body {
            background-color: #F8FAFC;
            min-height: 100vh;
            margin: 0;
            font-family: var(--font-body);
            color: var(--admin-text-main);
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .admin-top-nav {
            background: var(--admin-header-bg);
            color: #FFFFFF;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .admin-top-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .admin-logo-mark {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: linear-gradient(135deg, #FF6600, #E65100);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            box-shadow: 0 2px 8px rgba(255, 102, 0, 0.4);
        }

        .brand-title-wrap {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #FFFFFF;
            line-height: 1.1;
        }

        .brand-title span {
            color: var(--admin-orange);
        }

        .brand-tag {
            font-size: 0.72rem;
            font-weight: 700;
            color: #94A3B8;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .nav-links-center {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.06);
            padding: 0.3rem 0.4rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .nav-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #CBD5E1;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: transparent;
        }

        .nav-tab-btn:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-tab-btn.active {
            background: var(--admin-orange);
            color: #FFFFFF;
            box-shadow: 0 2px 8px rgba(255, 102, 0, 0.35);
        }

        .nav-tab-badge {
            background: rgba(0, 0, 0, 0.25);
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .admin-user-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-profile-pill {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.35rem 0.75rem 0.35rem 0.4rem;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #0284C7;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .admin-name-text {
            font-size: 0.82rem;
            font-weight: 700;
            color: #F8FAFC;
        }

        .admin-role-badge {
            font-size: 0.68rem;
            color: #38BDF8;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-top-action {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-view-site {
            background: rgba(255, 255, 255, 0.08);
            color: #CBD5E1;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .btn-view-site:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-admin-logout {
            background: rgba(239, 68, 68, 0.15);
            color: #FCA5A5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-admin-logout:hover {
            background: #EF4444;
            color: #FFFFFF;
        }

        /* Main Content Container */
        .admin-main-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
            flex: 1;
            width: 100%;
            box-sizing: border-box;
        }

        /* Banner Flash Alert */
        .action-alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: var(--admin-green-soft);
            border: 1px solid #A7F3D0;
            color: #065F46;
        }

        .alert-warning {
            background: var(--admin-amber-soft);
            border: 1px solid #FDE68A;
            color: #92400E;
        }

        .alert-error {
            background: var(--admin-red-soft);
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        .alert-info {
            background: var(--admin-blue-soft);
            border: 1px solid #BFDBFE;
            color: #1E40AF;
        }

        /* Statistics Cards Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--admin-card-bg);
            border-radius: 16px;
            padding: 1.35rem 1.5rem;
            border: 1px solid var(--admin-border);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .stat-card-left {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--admin-text-sub);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--admin-text-main);
            line-height: 1.1;
            font-family: var(--font-heading);
        }

        .stat-sub {
            font-size: 0.78rem;
            color: var(--admin-text-sub);
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .stat-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icon-blue {
            background: var(--admin-blue-soft);
            color: var(--admin-blue);
        }

        .icon-orange {
            background: var(--admin-orange-soft);
            color: var(--admin-orange);
        }

        .icon-amber {
            background: var(--admin-amber-soft);
            color: var(--admin-amber);
        }

        .icon-green {
            background: var(--admin-green-soft);
            color: var(--admin-green);
        }

        /* Section Container */
        .section-box {
            background: var(--admin-card-bg);
            border: 1px solid var(--admin-border);
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .section-header-bar {
            padding: 1.35rem 1.75rem;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            background: #FFFFFF;
        }

        .section-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--admin-text-main);
            margin: 0;
            letter-spacing: -0.01em;
        }

        .count-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            background: #F1F5F9;
            color: #334155;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* Controls (Search, Filters, Subtabs) */
        .section-controls-bar {
            padding: 1rem 1.75rem;
            background: #F8FAFC;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-box-wrap {
            position: relative;
            min-width: 300px;
            flex: 1;
            max-width: 440px;
        }

        .search-icon-pos {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            pointer-events: none;
            display: flex;
        }

        .search-input {
            width: 100%;
            box-sizing: border-box;
            background: #FFFFFF;
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            padding: 0.65rem 0.85rem 0.65rem 2.4rem;
            font-size: 0.88rem;
            font-family: inherit;
            color: var(--admin-text-main);
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--admin-orange);
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15);
        }

        .filter-pills-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: #FFFFFF;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            padding: 0.45rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .filter-btn:hover {
            border-color: #CBD5E1;
            color: #0F172A;
            background: #F1F5F9;
        }

        .filter-btn.active {
            background: #0F172A;
            border-color: #0F172A;
            color: #FFFFFF;
        }

        .filter-badge {
            padding: 0.1rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            background: rgba(255, 255, 255, 0.2);
        }

        .filter-btn:not(.active) .filter-badge {
            background: #E2E8F0;
            color: #334155;
        }

        /* Data Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .admin-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }

        .admin-data-table th {
            background: #F8FAFC;
            color: #475569;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.95rem 1.25rem;
            border-bottom: 1px solid var(--admin-border);
            white-space: nowrap;
        }

        .admin-data-table td {
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
            color: #1E293B;
        }

        .admin-data-table tbody tr {
            transition: background 0.15s ease;
        }

        .admin-data-table tbody tr:hover {
            background: #F8FAFC;
        }

        /* User Profile Cells */
        .user-identity-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.88rem;
            flex-shrink: 0;
        }

        .avatar-customer {
            background: linear-gradient(135deg, #0284C7, #0369A1);
            color: #FFFFFF;
        }

        .avatar-provider {
            background: linear-gradient(135deg, #FF6600, #D97706);
            color: #FFFFFF;
        }

        .user-name-title {
            font-weight: 700;
            color: #0F172A;
            line-height: 1.2;
            margin-bottom: 0.2rem;
        }

        .user-id-sub {
            font-family: var(--font-mono);
            font-size: 0.72rem;
            color: #64748B;
        }

        .contact-links {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            font-size: 0.82rem;
        }

        .contact-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: #475569;
            text-decoration: none;
            transition: color 0.15s;
        }

        .contact-link:hover {
            color: var(--admin-orange);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-approved {
            background: var(--admin-green-soft);
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .status-pending {
            background: var(--admin-amber-soft);
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .status-rejected {
            background: var(--admin-red-soft);
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .skill-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.55rem;
            background: #EFF6FF;
            color: #1D4ED8;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid #DBEAFE;
        }

        .exp-tag {
            font-size: 0.74rem;
            color: #64748B;
            margin-top: 0.25rem;
        }

        /* Action Buttons */
        .action-btns-group {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .btn-action-sm {
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s;
            text-decoration: none;
        }

        .btn-view-details {
            background: #F1F5F9;
            color: #1E293B;
            border-color: #CBD5E1;
        }

        .btn-view-details:hover {
            background: #E2E8F0;
            color: #0F172A;
        }

        .btn-quick-approve {
            background: var(--admin-green-soft);
            color: #065F46;
            border-color: #6EE7B7;
        }

        .btn-quick-approve:hover {
            background: #10B981;
            color: #FFFFFF;
        }

        .btn-quick-reject {
            background: var(--admin-red-soft);
            color: #991B1B;
            border-color: #FCA5A5;
        }

        .btn-quick-reject:hover {
            background: #EF4444;
            color: #FFFFFF;
        }

        .btn-quick-reset {
            background: #FEF3C7;
            color: #92400E;
            border-color: #FDE68A;
        }

        .btn-quick-reset:hover {
            background: #F59E0B;
            color: #FFFFFF;
        }

        /* Empty State */
        .empty-table-state {
            padding: 3.5rem 1.5rem;
            text-align: center;
            color: #64748B;
        }

        .empty-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #F1F5F9;
            color: #94A3B8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        /* Detail Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            box-sizing: border-box;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .modal-overlay.open {
            display: flex;
            opacity: 1;
        }

        .modal-card {
            background: #FFFFFF;
            border-radius: 20px;
            width: 100%;
            max-width: 720px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalSlideUp {
            from {
                transform: translateY(20px) scale(0.98);
            }
            to {
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #F8FAFC;
        }

        .modal-header-title {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0F172A;
        }

        .btn-close-modal {
            background: none;
            border: none;
            color: #64748B;
            cursor: pointer;
            padding: 0.4rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-close-modal:hover {
            background: #E2E8F0;
            color: #0F172A;
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--admin-border);
            background: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .info-panel {
            background: #F8FAFC;
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 1rem 1.15rem;
        }

        .info-panel-title {
            font-size: 0.72rem;
            font-weight: 800;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .info-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 0.65rem;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 0.72rem;
            color: #64748B;
            font-weight: 600;
        }

        .info-val {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0F172A;
            margin-top: 0.15rem;
            word-break: break-word;
        }

        .bookings-subtable {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .bookings-subtable th {
            background: #F1F5F9;
            padding: 0.5rem 0.75rem;
            color: #475569;
            font-weight: 700;
            text-align: left;
            border-radius: 6px;
        }

        .bookings-subtable td {
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid #E2E8F0;
            color: #1E293B;
        }

        /* Reject Reason Modal Popup */
        .rejection-prompt-box {
            margin-top: 1rem;
            background: var(--admin-red-soft);
            border: 1px solid #FECACA;
            border-radius: 10px;
            padding: 1rem;
        }

        .rejection-textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 0.65rem;
            font-family: inherit;
            font-size: 0.85rem;
            margin-top: 0.4rem;
            outline: none;
        }

        .rejection-textarea:focus {
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        @media (max-width: 900px) {
            .admin-top-container {
                flex-direction: column;
                align-items: stretch;
            }
            .nav-links-center {
                justify-content: center;
            }
            .admin-user-group {
                justify-content: space-between;
            }
            .modal-grid-2col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <?php require __DIR__ . "/../components/navbar-admin.php"; ?>

    <!-- =======================================================================
         MAIN DASHBOARD BODY
         ======================================================================= -->
    <main class="admin-main-wrap">

        <!-- Flash Message Banners -->
        <?php if (!empty($actionMsg)): ?>
            <div class="action-alert alert-<?= htmlspecialchars($actionType); ?>" role="alert">
                <i data-lucide="<?= $actionType === 'success' ? 'check-circle' : ($actionType === 'warning' ? 'alert-triangle' : 'info'); ?>" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
                <div><?= htmlspecialchars($actionMsg); ?></div>
            </div>
        <?php endif; ?>

        <!-- ===================================================================
             TOP STAT CARDS GRID
             =================================================================== -->
        <section class="stats-grid" aria-label="Key Platform Metrics">
            <!-- 1. Total Customers -->
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Registered Customers</span>
                    <span class="stat-number"><?= number_format($totalCustomers); ?></span>
                    <span class="stat-sub">
                        <i data-lucide="user-check" style="width: 14px; height: 14px; color: #10B981;"></i>
                        <span>Active home service consumers</span>
                    </span>
                </div>
                <div class="stat-icon-wrap icon-blue">
                    <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                </div>
            </div>

            <!-- 2. Total Service Providers -->
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Total Providers (Mitras)</span>
                    <span class="stat-number"><?= number_format($totalProviders); ?></span>
                    <span class="stat-sub">
                        <i data-lucide="check" style="width: 14px; height: 14px; color: #10B981;"></i>
                        <span><?= $approvedProvidersCount; ?> Approved &bull; <?= $pendingProvidersCount; ?> Pending</span>
                    </span>
                </div>
                <div class="stat-icon-wrap icon-orange">
                    <i data-lucide="wrench" style="width: 24px; height: 24px;"></i>
                </div>
            </div>

            <!-- 3. Pending Verifications -->
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Awaiting Verification</span>
                    <span class="stat-number" style="color: <?= $pendingProvidersCount > 0 ? '#D97706' : '#0F172A'; ?>;"><?= $pendingProvidersCount; ?></span>
                    <span class="stat-sub">
                        <i data-lucide="clock" style="width: 14px; height: 14px; color: #F59E0B;"></i>
                        <span>Applications requiring admin review</span>
                    </span>
                </div>
                <div class="stat-icon-wrap icon-amber">
                    <i data-lucide="shield-alert" style="width: 24px; height: 24px;"></i>
                </div>
            </div>

            <!-- 4. Total Platform Bookings -->
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Platform Bookings</span>
                    <span class="stat-number"><?= number_format($totalBookings); ?></span>
                    <span class="stat-sub">
                        <i data-lucide="badge-check" style="width: 14px; height: 14px; color: #10B981;"></i>
                        <span><?= $completedBookings; ?> completed jobs &bull; <?= $activeBookings; ?> active</span>
                    </span>
                </div>
                <div class="stat-icon-wrap icon-green">
                    <i data-lucide="calendar-check" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             SECTION 1: REGISTERED CUSTOMERS LIST
             =================================================================== -->
        <section class="section-box" id="sectionCustomers" style="<?= $activeTab === 'customers' ? '' : 'display: none;'; ?>">
            <!-- Section Header -->
            <div class="section-header-bar">
                <div class="section-title-wrap">
                    <h2 class="section-title">Registered Customers</h2>
                    <span class="count-pill"><strong id="customerVisibleCount"><?= count($customers); ?></strong> registered</span>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: #64748B;">Showing complete consumer directory with contact and booking details</span>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="section-controls-bar">
                <div class="search-box-wrap">
                    <span class="search-icon-pos">
                        <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                    </span>
                    <input 
                        type="text" 
                        id="customerSearchInput" 
                        class="search-input" 
                        placeholder="Search customers by name, phone, email, or city..."
                        oninput="filterCustomersTable()"
                    >
                </div>
                <div>
                    <span style="font-size: 0.78rem; color: #64748B;">Click <strong>View Details</strong> to review address and past bookings</span>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="table-responsive">
                <table class="admin-data-table" id="customersTable">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Gender</th>
                            <th>Bookings Placed</th>
                            <th>Joined Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-table-state">
                                        <div class="empty-icon-wrap">
                                            <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                                        </div>
                                        <h4 style="margin: 0 0 0.25rem;">No registered customers found</h4>
                                        <p style="margin: 0; font-size: 0.85rem;">Customers who register on KaamMitra will automatically appear in this list.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): ?>
                                <?php
                                    $cid = (int)$c['id'];
                                    $cName = $c['full_name'];
                                    $cMobile = $c['mobile'];
                                    $cEmail = $c['email'];
                                    $cCity = $c['city'] ?: 'Not Specified';
                                    $cState = $c['state'] ?: '';
                                    $cPincode = $c['pincode'] ?: '';
                                    $cAddress = $c['address_line'] ?: 'No street address provided';
                                    $cGender = $c['gender'] ?: 'Not Specified';
                                    $cJoined = date('d M Y, h:i A', strtotime($c['created_at']));
                                    $bCount = (int)$c['total_bookings'];

                                    // Build JSON payload for modal preview
                                    $custBookingsList = array_merge(
                                        $customerBookingsMap['id_' . $cid] ?? [],
                                        $customerBookingsMap['phone_' . $cMobile] ?? []
                                    );
                                    // Deduplicate bookings by ID
                                    $dedupBookings = [];
                                    foreach ($custBookingsList as $bkItem) {
                                        $dedupBookings[$bkItem['id']] = $bkItem;
                                    }
                                    $custBookingsJson = htmlspecialchars(json_encode(array_values($dedupBookings)), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="customer-row" 
                                    data-name="<?= strtolower(htmlspecialchars($cName)); ?>"
                                    data-mobile="<?= htmlspecialchars($cMobile); ?>"
                                    data-email="<?= strtolower(htmlspecialchars($cEmail)); ?>"
                                    data-city="<?= strtolower(htmlspecialchars($cCity)); ?>"
                                >
                                    <td>
                                        <div class="user-identity-cell">
                                            <div class="user-avatar-circle avatar-customer">
                                                <?= strtoupper(substr($cName, 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="user-name-title"><?= htmlspecialchars($cName); ?></div>
                                                <div class="user-id-sub">#CUST-<?= str_pad($cid, 3, '0', STR_PAD_LEFT); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-links">
                                            <a href="tel:<?= htmlspecialchars($cMobile); ?>" class="contact-link" title="Call customer">
                                                <i data-lucide="phone" style="width: 13px; height: 13px; color: #0284C7;"></i>
                                                <span><?= htmlspecialchars($cMobile); ?></span>
                                            </a>
                                            <a href="mailto:<?= htmlspecialchars($cEmail); ?>" class="contact-link" title="Email customer">
                                                <i data-lucide="mail" style="width: 13px; height: 13px; color: #64748B;"></i>
                                                <span><?= htmlspecialchars($cEmail); ?></span>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong style="font-size: 0.85rem; color: #1E293B;"><?= htmlspecialchars($cCity); ?></strong>
                                            <?php if (!empty($cPincode)): ?>
                                                <div style="font-size: 0.75rem; color: #64748B;">PIN: <?= htmlspecialchars($cPincode); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.82rem; color: #475569;"><?= htmlspecialchars($cGender); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($bCount > 0): ?>
                                            <span class="status-badge status-approved" style="font-size: 0.78rem;">
                                                <i data-lucide="calendar" style="width: 13px; height: 13px;"></i>
                                                <span><?= $bCount; ?> Bookings</span>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size: 0.78rem; color: #94A3B8;">0 Bookings</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8rem; color: #475569;"><?= date('d M Y', strtotime($c['created_at'])); ?></span>
                                        <div style="font-size: 0.72rem; color: #94A3B8;"><?= date('h:i A', strtotime($c['created_at'])); ?></div>
                                    </td>
                                    <td style="text-align: right;">
                                        <button 
                                            type="button" 
                                            class="btn-action-sm btn-view-details" 
                                            onclick='openCustomerModal({
                                                id: <?= $cid; ?>,
                                                name: "<?= addslashes(htmlspecialchars($cName)); ?>",
                                                mobile: "<?= addslashes(htmlspecialchars($cMobile)); ?>",
                                                email: "<?= addslashes(htmlspecialchars($cEmail)); ?>",
                                                gender: "<?= addslashes(htmlspecialchars($cGender)); ?>",
                                                address: "<?= addslashes(htmlspecialchars($cAddress)); ?>",
                                                city: "<?= addslashes(htmlspecialchars($cCity)); ?>",
                                                state: "<?= addslashes(htmlspecialchars($cState)); ?>",
                                                pincode: "<?= addslashes(htmlspecialchars($cPincode)); ?>",
                                                joined: "<?= $cJoined; ?>",
                                                bookings: <?= $custBookingsJson; ?>
                                            })'
                                        >
                                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                            <span>View Details</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ===================================================================
             SECTION 2: REGISTERED SERVICE PROVIDERS (PARTNERS) LIST
             =================================================================== -->
        <section class="section-box" id="sectionProviders" style="<?= $activeTab === 'providers' ? '' : 'display: none;'; ?>">
            <!-- Section Header -->
            <div class="section-header-bar">
                <div class="section-title-wrap">
                    <h2 class="section-title">Service Providers (Partners)</h2>
                    <span class="count-pill"><strong id="providerVisibleCount"><?= count($providers); ?></strong> total</span>
                </div>
                <div class="filter-pills-group" role="group" aria-label="Status Filters">
                    <button type="button" class="filter-btn active" id="btnFilterAll" onclick="filterProvidersByStatus('All')">
                        <span>All Providers</span>
                        <span class="filter-badge"><?= $totalProviders; ?></span>
                    </button>
                    <button type="button" class="filter-btn" id="btnFilterPending" onclick="filterProvidersByStatus('Pending')">
                        <span style="color: #D97706;">Pending</span>
                        <span class="filter-badge"><?= $pendingProvidersCount; ?></span>
                    </button>
                    <button type="button" class="filter-btn" id="btnFilterApproved" onclick="filterProvidersByStatus('Approved')">
                        <span style="color: #059669;">Approved</span>
                        <span class="filter-badge"><?= $approvedProvidersCount; ?></span>
                    </button>
                    <button type="button" class="filter-btn" id="btnFilterRejected" onclick="filterProvidersByStatus('Rejected')">
                        <span style="color: #DC2626;">Rejected</span>
                        <span class="filter-badge"><?= $rejectedProvidersCount; ?></span>
                    </button>
                </div>
            </div>

            <!-- Search and Controls -->
            <div class="section-controls-bar">
                <div class="search-box-wrap">
                    <span class="search-icon-pos">
                        <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                    </span>
                    <input 
                        type="text" 
                        id="providerSearchInput" 
                        class="search-input" 
                        placeholder="Search providers by name, skill, phone, city, or status..."
                        oninput="filterProvidersTable()"
                    >
                </div>
                <div>
                    <span style="font-size: 0.78rem; color: #64748B;">Verify, review trade background, or update provider status</span>
                </div>
            </div>

            <!-- Providers Table -->
            <div class="table-responsive">
                <table class="admin-data-table" id="providersTable">
                    <thead>
                        <tr>
                            <th>Service Provider</th>
                            <th>Trade / Skill</th>
                            <th>Contact</th>
                            <th>Operational Hub</th>
                            <th>Verification Status</th>
                            <th>Assigned Jobs</th>
                            <th>Registered</th>
                            <th style="text-align: right;">Moderation & Actions</th>
                        </tr>
                    </thead>
                    <tbody id="providersTableBody">
                        <?php if (empty($providers)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-table-state">
                                        <div class="empty-icon-wrap">
                                            <i data-lucide="wrench" style="width: 24px; height: 24px;"></i>
                                        </div>
                                        <h4 style="margin: 0 0 0.25rem;">No service providers registered</h4>
                                        <p style="margin: 0; font-size: 0.85rem;">Partners who register through the partner portal will be listed here for verification.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($providers as $p): ?>
                                <?php
                                    $pid = (int)$p['id'];
                                    $pName = $p['full_name'];
                                    $pMobile = $p['mobile'];
                                    $pEmail = $p['email'];
                                    $pSkill = $p['primary_skill'];
                                    $pExp = $p['experience_years'];
                                    $pCity = $p['city'];
                                    $pArea = $p['service_area'] ?: 'All City Zones';
                                    $pAddress = $p['address'] ?: 'No address specified';
                                    $pGender = $p['gender'] ?: 'Not specified';
                                    $pStatus = $p['verification_status'] ?? 'Pending';
                                    $pReason = $p['rejection_reason'] ?: '';
                                    $pJoined = date('d M Y, h:i A', strtotime($p['created_at']));
                                    $assignedJobs = (int)$p['assigned_bookings'];

                                    $partnerBookings = $providerBookingsMap[$pid] ?? [];
                                    $partnerBookingsJson = htmlspecialchars(json_encode(array_values($partnerBookings)), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="provider-row" 
                                    data-status="<?= htmlspecialchars($pStatus); ?>"
                                    data-name="<?= strtolower(htmlspecialchars($pName)); ?>"
                                    data-skill="<?= strtolower(htmlspecialchars($pSkill)); ?>"
                                    data-mobile="<?= htmlspecialchars($pMobile); ?>"
                                    data-email="<?= strtolower(htmlspecialchars($pEmail)); ?>"
                                    data-city="<?= strtolower(htmlspecialchars($pCity)); ?>"
                                >
                                    <td>
                                        <div class="user-identity-cell">
                                            <div class="user-avatar-circle avatar-provider">
                                                <?= strtoupper(substr($pName, 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="user-name-title"><?= htmlspecialchars($pName); ?></div>
                                                <div class="user-id-sub">#MITRA-<?= str_pad($pid, 3, '0', STR_PAD_LEFT); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="skill-tag">
                                                <i data-lucide="wrench" style="width: 12px; height: 12px;"></i>
                                                <span><?= htmlspecialchars($pSkill); ?></span>
                                            </span>
                                            <div class="exp-tag"><?= htmlspecialchars($pExp); ?> Exp</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-links">
                                            <a href="tel:<?= htmlspecialchars($pMobile); ?>" class="contact-link" title="Call Mitra">
                                                <i data-lucide="phone" style="width: 13px; height: 13px; color: #FF6600;"></i>
                                                <span><?= htmlspecialchars($pMobile); ?></span>
                                            </a>
                                            <a href="mailto:<?= htmlspecialchars($pEmail); ?>" class="contact-link" title="Email Mitra">
                                                <i data-lucide="mail" style="width: 13px; height: 13px; color: #64748B;"></i>
                                                <span><?= htmlspecialchars($pEmail); ?></span>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong style="font-size: 0.85rem; color: #1E293B;"><?= htmlspecialchars($pCity); ?></strong>
                                            <div style="font-size: 0.75rem; color: #64748B;"><?= htmlspecialchars($pArea); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($pStatus === 'Approved'): ?>
                                            <span class="status-badge status-approved">
                                                <i data-lucide="check-circle-2" style="width: 13px; height: 13px;"></i>
                                                <span>Approved</span>
                                            </span>
                                        <?php elseif ($pStatus === 'Rejected'): ?>
                                            <span class="status-badge status-rejected" title="<?= htmlspecialchars($pReason); ?>">
                                                <i data-lucide="x-circle" style="width: 13px; height: 13px;"></i>
                                                <span>Rejected</span>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">
                                                <i data-lucide="clock" style="width: 13px; height: 13px;"></i>
                                                <span>Pending Review</span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.82rem; font-weight: 700; color: #334155;"><?= $assignedJobs; ?> Jobs</span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8rem; color: #475569;"><?= date('d M Y', strtotime($p['created_at'])); ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="action-btns-group" style="justify-content: flex-end;">
                                            <!-- View Details Button -->
                                            <button 
                                                type="button" 
                                                class="btn-action-sm btn-view-details" 
                                                onclick='openProviderModal({
                                                    id: <?= $pid; ?>,
                                                    name: "<?= addslashes(htmlspecialchars($pName)); ?>",
                                                    mobile: "<?= addslashes(htmlspecialchars($pMobile)); ?>",
                                                    email: "<?= addslashes(htmlspecialchars($pEmail)); ?>",
                                                    gender: "<?= addslashes(htmlspecialchars($pGender)); ?>",
                                                    skill: "<?= addslashes(htmlspecialchars($pSkill)); ?>",
                                                    experience: "<?= addslashes(htmlspecialchars($pExp)); ?>",
                                                    city: "<?= addslashes(htmlspecialchars($pCity)); ?>",
                                                    area: "<?= addslashes(htmlspecialchars($pArea)); ?>",
                                                    address: "<?= addslashes(htmlspecialchars($pAddress)); ?>",
                                                    status: "<?= $pStatus; ?>",
                                                    reason: "<?= addslashes(htmlspecialchars($pReason)); ?>",
                                                    joined: "<?= $pJoined; ?>",
                                                    assignedJobs: <?= $assignedJobs; ?>,
                                                    bookings: <?= $partnerBookingsJson; ?>
                                                })'
                                            >
                                                <i data-lucide="eye" style="width: 13px; height: 13px;"></i>
                                                <span>Details</span>
                                            </button>

                                            <!-- Inline Moderation Controls -->
                                            <?php if ($pStatus !== 'Approved'): ?>
                                                <form action="dashboard.php?tab=providers" method="POST" style="display: inline;" onsubmit="return confirm('Approve partner #MITRA-<?= str_pad($pid, 3, '0', STR_PAD_LEFT); ?> (<?= addslashes($pName); ?>)?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action_type" value="approve_partner">
                                                    <input type="hidden" name="provider_id" value="<?= $pid; ?>">
                                                    <button type="submit" class="btn-action-sm btn-quick-approve" title="Approve Mitra">
                                                        <i data-lucide="check" style="width: 13px; height: 13px;"></i>
                                                        <span>Approve</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($pStatus !== 'Rejected'): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn-action-sm btn-quick-reject" 
                                                    title="Reject Mitra"
                                                    onclick="openRejectModal(<?= $pid; ?>, '<?= addslashes(htmlspecialchars($pName)); ?>')"
                                                >
                                                    <i data-lucide="x" style="width: 13px; height: 13px;"></i>
                                                    <span>Reject</span>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($pStatus !== 'Pending'): ?>
                                                <form action="dashboard.php?tab=providers" method="POST" style="display: inline;" onsubmit="return confirm('Reset status to Pending for partner #MITRA-<?= str_pad($pid, 3, '0', STR_PAD_LEFT); ?>?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action_type" value="reset_partner_pending">
                                                    <input type="hidden" name="provider_id" value="<?= $pid; ?>">
                                                    <button type="submit" class="btn-action-sm btn-quick-reset" title="Reset to Pending">
                                                        <i data-lucide="rotate-ccw" style="width: 13px; height: 13px;"></i>
                                                        <span>Reset</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- =======================================================================
         CUSTOMER DETAILS MODAL
         ======================================================================= -->
    <div class="modal-overlay" id="customerModalOverlay" onclick="closeModalOnBackdrop(event, 'customerModalOverlay')">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-header-title">
                    <div class="user-avatar-circle avatar-customer" style="width: 32px; height: 32px; font-size: 0.8rem;" id="modalCustAvatar">C</div>
                    <div>
                        <div id="modalCustName" style="font-size: 1.1rem; font-weight: 800; line-height: 1.2;">Customer Name</div>
                        <div id="modalCustId" style="font-family: var(--font-mono); font-size: 0.72rem; color: #64748B;">#CUST-000</div>
                    </div>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeModal('customerModalOverlay')">
                    <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- 2-column info cards -->
                <div class="modal-grid-2col">
                    <div class="info-panel">
                        <div class="info-panel-title">
                            <i data-lucide="user" style="width: 14px; height: 14px; color: #0284C7;"></i>
                            <span>Personal & Contact Info</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Mobile Number</span>
                            <span class="info-val" id="modalCustMobile">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email Address</span>
                            <span class="info-val" id="modalCustEmail">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Gender</span>
                            <span class="info-val" id="modalCustGender">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Registration Date</span>
                            <span class="info-val" id="modalCustJoined">-</span>
                        </div>
                    </div>

                    <div class="info-panel">
                        <div class="info-panel-title">
                            <i data-lucide="map-pin" style="width: 14px; height: 14px; color: #FF6600;"></i>
                            <span>Address & Location</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Street Address</span>
                            <span class="info-val" id="modalCustAddress">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">City</span>
                            <span class="info-val" id="modalCustCity">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">State</span>
                            <span class="info-val" id="modalCustState">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PIN Code</span>
                            <span class="info-val" id="modalCustPincode">-</span>
                        </div>
                    </div>
                </div>

                <!-- Past Bookings History -->
                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: #0F172A; margin: 0 0 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                        <span>Booking History</span>
                        <span id="modalCustBookingsCount" class="count-pill">0 Bookings</span>
                    </h4>
                    <div id="modalCustBookingsContainer">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <span style="font-size: 0.78rem; color: #64748B;">Registered consumer on KaamMitra home platform</span>
                <button type="button" class="btn-action-sm btn-view-details" onclick="closeModal('customerModalOverlay')">Close</button>
            </div>
        </div>
    </div>

    <!-- =======================================================================
         SERVICE PROVIDER DETAILS MODAL
         ======================================================================= -->
    <div class="modal-overlay" id="providerModalOverlay" onclick="closeModalOnBackdrop(event, 'providerModalOverlay')">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-header-title">
                    <div class="user-avatar-circle avatar-provider" style="width: 32px; height: 32px; font-size: 0.8rem;" id="modalProvAvatar">M</div>
                    <div>
                        <div id="modalProvName" style="font-size: 1.1rem; font-weight: 800; line-height: 1.2;">Provider Name</div>
                        <div id="modalProvId" style="font-family: var(--font-mono); font-size: 0.72rem; color: #64748B;">#MITRA-000</div>
                    </div>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeModal('providerModalOverlay')">
                    <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Verification Status Header Banner -->
                <div id="modalProvStatusBanner" style="padding: 0.85rem 1rem; border-radius: 10px; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.04em;">Verification Status</div>
                        <div id="modalProvStatusText" style="font-size: 1rem; font-weight: 800;">-</div>
                    </div>
                    <div id="modalProvStatusBadge"></div>
                </div>

                <!-- Rejection Reason if any -->
                <div id="modalProvRejectionNotice" style="display: none; padding: 0.85rem 1rem; background: var(--admin-red-soft); border: 1px solid #FECACA; border-radius: 10px; margin-bottom: 1.25rem; font-size: 0.85rem; color: #991B1B;">
                    <strong>Rejection Reason:</strong> <span id="modalProvRejectionReasonText"></span>
                </div>

                <!-- 2-col info cards -->
                <div class="modal-grid-2col">
                    <div class="info-panel">
                        <div class="info-panel-title">
                            <i data-lucide="wrench" style="width: 14px; height: 14px; color: #FF6600;"></i>
                            <span>Trade & Professional Skills</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Primary Trade Skill</span>
                            <span class="info-val" id="modalProvSkill">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Years of Experience</span>
                            <span class="info-val" id="modalProvExp">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Assigned Platform Jobs</span>
                            <span class="info-val" id="modalProvJobsCount">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Joined Registry</span>
                            <span class="info-val" id="modalProvJoined">-</span>
                        </div>
                    </div>

                    <div class="info-panel">
                        <div class="info-panel-title">
                            <i data-lucide="map-pin" style="width: 14px; height: 14px; color: #0284C7;"></i>
                            <span>Contact & Operating Hub</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Mobile Number</span>
                            <span class="info-val" id="modalProvMobile">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email Address</span>
                            <span class="info-val" id="modalProvEmail">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">City of Operation</span>
                            <span class="info-val" id="modalProvCity">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Service Area / Zones</span>
                            <span class="info-val" id="modalProvArea">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Residential Address</span>
                            <span class="info-val" id="modalProvAddress">-</span>
                        </div>
                    </div>
                </div>

                <!-- Assigned Jobs History -->
                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: #0F172A; margin: 0 0 0.75rem;">Assigned Service Jobs</h4>
                    <div id="modalProvBookingsContainer">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Modal Moderation Footer -->
            <div class="modal-footer" id="modalProvFooterActions">
                <!-- Action buttons populated dynamically by JS based on current status -->
            </div>
        </div>
    </div>

    <!-- =======================================================================
         REJECT PARTNER REASON MODAL
         ======================================================================= -->
    <div class="modal-overlay" id="rejectPromptModalOverlay" onclick="closeModalOnBackdrop(event, 'rejectPromptModalOverlay')">
        <div class="modal-card" style="max-width: 480px;">
            <div class="modal-header">
                <div class="modal-header-title">
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px; color: #EF4444;"></i>
                    <span>Reject Partner Application</span>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeModal('rejectPromptModalOverlay')">
                    <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                </button>
            </div>

            <form action="dashboard.php?tab=providers" method="POST" id="rejectPartnerForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action_type" value="reject_partner">
                <input type="hidden" name="provider_id" id="rejectPartnerIdInput" value="">

                <div class="modal-body">
                    <p style="font-size: 0.88rem; color: #475569; margin: 0 0 1rem;">
                        You are rejecting partner <strong id="rejectPartnerNameText" style="color: #0F172A;">-</strong>. Please state the reason for rejection (this will be visible to the partner when they log in):
                    </p>

                    <label for="rejectionReasonInput" style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 0.35rem;">Reason for Rejection</label>
                    <textarea 
                        name="rejection_reason" 
                        id="rejectionReasonInput" 
                        class="rejection-textarea" 
                        rows="3" 
                        required
                    >Trade certificate or residential address verification requirements were not fulfilled.</textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action-sm btn-view-details" onclick="closeModal('rejectPromptModalOverlay')">Cancel</button>
                    <button type="submit" class="btn-action-sm btn-quick-reject" style="background: #EF4444; color: #FFFFFF; border: none; padding: 0.55rem 1rem;">
                        <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                        <span>Confirm Rejection</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =======================================================================
         JAVASCRIPT LOGIC FOR REAL-TIME SEARCH, TABS, & MODALS
         ======================================================================= -->
    <script>
        lucide.createIcons();

        // ---------------------------------------------------------------------
        // Switch between Customers and Service Providers sections
        // ---------------------------------------------------------------------
        function switchSectionTab(tabName, evt) {
            if (evt) evt.preventDefault();

            const secCust = document.getElementById('sectionCustomers');
            const secProv = document.getElementById('sectionProviders');
            const tabCust = document.getElementById('tabLinkCustomers');
            const tabProv = document.getElementById('tabLinkProviders');

            if (tabName === 'providers') {
                secCust.style.display = 'none';
                secProv.style.display = 'block';
                tabCust.classList.remove('active');
                tabProv.classList.add('active');
                window.history.replaceState(null, '', 'dashboard.php?tab=providers');
            } else {
                secCust.style.display = 'block';
                secProv.style.display = 'none';
                tabCust.classList.add('active');
                tabProv.classList.remove('active');
                window.history.replaceState(null, '', 'dashboard.php?tab=customers');
            }
        }

        // ---------------------------------------------------------------------
        // Filter Customers Table in Real-Time
        // ---------------------------------------------------------------------
        function filterCustomersTable() {
            const query = (document.getElementById('customerSearchInput').value || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.customer-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const mobile = row.getAttribute('data-mobile') || '';
                const email = row.getAttribute('data-email') || '';
                const city = row.getAttribute('data-city') || '';

                const matches = !query || name.includes(query) || mobile.includes(query) || email.includes(query) || city.includes(query);
                if (matches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('customerVisibleCount').textContent = visibleCount;
        }

        // ---------------------------------------------------------------------
        // Filter Providers Table by Verification Status
        // ---------------------------------------------------------------------
        let currentStatusFilter = 'All';

        function filterProvidersByStatus(status) {
            currentStatusFilter = status;

            // Update active pill styling
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            if (status === 'Pending') document.getElementById('btnFilterPending').classList.add('active');
            else if (status === 'Approved') document.getElementById('btnFilterApproved').classList.add('active');
            else if (status === 'Rejected') document.getElementById('btnFilterRejected').classList.add('active');
            else document.getElementById('btnFilterAll').classList.add('active');

            filterProvidersTable();
        }

        function filterProvidersTable() {
            const query = (document.getElementById('providerSearchInput').value || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.provider-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const status = row.getAttribute('data-status') || '';
                const name = row.getAttribute('data-name') || '';
                const skill = row.getAttribute('data-skill') || '';
                const mobile = row.getAttribute('data-mobile') || '';
                const email = row.getAttribute('data-email') || '';
                const city = row.getAttribute('data-city') || '';

                const statusMatches = (currentStatusFilter === 'All') || (status === currentStatusFilter);
                const queryMatches = !query || name.includes(query) || skill.includes(query) || mobile.includes(query) || email.includes(query) || city.includes(query) || status.toLowerCase().includes(query);

                if (statusMatches && queryMatches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('providerVisibleCount').textContent = visibleCount;
        }

        // ---------------------------------------------------------------------
        // Modal Open / Close Helpers
        // ---------------------------------------------------------------------
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('open');
                document.body.style.overflow = 'hidden';
                lucide.createIcons();
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('open');
                document.body.style.overflow = '';
            }
        }

        function closeModalOnBackdrop(e, modalId) {
            if (e.target.id === modalId) {
                closeModal(modalId);
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('customerModalOverlay');
                closeModal('providerModalOverlay');
                closeModal('rejectPromptModalOverlay');
            }
        });

        // ---------------------------------------------------------------------
        // Open Customer Details Modal with Complete Booking History
        // ---------------------------------------------------------------------
        function openCustomerModal(data) {
            document.getElementById('modalCustAvatar').textContent = data.name.charAt(0).toUpperCase();
            document.getElementById('modalCustName').textContent = data.name;
            document.getElementById('modalCustId').textContent = '#CUST-' + String(data.id).padStart(3, '0');

            document.getElementById('modalCustMobile').textContent = data.mobile || '-';
            document.getElementById('modalCustEmail').textContent = data.email || '-';
            document.getElementById('modalCustGender').textContent = data.gender || '-';
            document.getElementById('modalCustJoined').textContent = data.joined || '-';

            document.getElementById('modalCustAddress').textContent = data.address || '-';
            document.getElementById('modalCustCity').textContent = data.city || '-';
            document.getElementById('modalCustState').textContent = data.state || 'Not Specified';
            document.getElementById('modalCustPincode').textContent = data.pincode || 'Not Specified';

            const bookings = data.bookings || [];
            document.getElementById('modalCustBookingsCount').textContent = bookings.length + (bookings.length === 1 ? ' Booking' : ' Bookings');

            const container = document.getElementById('modalCustBookingsContainer');
            if (bookings.length === 0) {
                container.innerHTML = `
                    <div style="padding: 1.5rem; text-align: center; background: #F8FAFC; border-radius: 10px; border: 1px dashed #CBD5E1; color: #64748B; font-size: 0.85rem;">
                        No bookings placed yet by this customer.
                    </div>
                `;
            } else {
                let html = `
                    <table class="bookings-subtable">
                        <thead>
                            <tr>
                                <th>Booking Ref</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                bookings.forEach(b => {
                    const priceFormatted = '₹' + parseFloat(b.estimated_price || 0).toLocaleString('en-IN');
                    const statusClass = b.status === 'Completed' ? 'status-approved' : (b.status === 'Cancelled' ? 'status-rejected' : 'status-pending');
                    html += `
                        <tr>
                            <td><strong style="font-family: var(--font-mono); font-size: 0.78rem;">${escapeHtml(b.booking_reference)}</strong></td>
                            <td>
                                <div><strong>${escapeHtml(b.service_category)}</strong></div>
                                <div style="font-size: 0.75rem; color: #64748B;">${escapeHtml(b.sub_service)}</div>
                            </td>
                            <td>
                                <div>${escapeHtml(b.service_date)}</div>
                                <div style="font-size: 0.72rem; color: #64748B;">${escapeHtml(b.time_slot)}</div>
                            </td>
                            <td><strong>${priceFormatted}</strong></td>
                            <td><span class="status-badge ${statusClass}">${escapeHtml(b.status)}</span></td>
                        </tr>
                    `;
                });
                html += `</tbody></table>`;
                container.innerHTML = html;
            }

            openModal('customerModalOverlay');
        }

        // ---------------------------------------------------------------------
        // Open Provider Details Modal with Moderation Controls
        // ---------------------------------------------------------------------
        function openProviderModal(data) {
            document.getElementById('modalProvAvatar').textContent = data.name.charAt(0).toUpperCase();
            document.getElementById('modalProvName').textContent = data.name;
            document.getElementById('modalProvId').textContent = '#MITRA-' + String(data.id).padStart(3, '0');

            const statusBanner = document.getElementById('modalProvStatusBanner');
            const statusText = document.getElementById('modalProvStatusText');
            const statusBadge = document.getElementById('modalProvStatusBadge');
            const rejectionNotice = document.getElementById('modalProvRejectionNotice');

            if (data.status === 'Approved') {
                statusBanner.style.background = '#ECFDF5';
                statusBanner.style.border = '1px solid #A7F3D0';
                statusText.style.color = '#065F46';
                statusText.textContent = 'Approved & Verified';
                statusBadge.innerHTML = '<span class="status-badge status-approved"><i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> Verified Active</span>';
                rejectionNotice.style.display = 'none';
            } else if (data.status === 'Rejected') {
                statusBanner.style.background = '#FEF2F2';
                statusBanner.style.border = '1px solid #FECACA';
                statusText.style.color = '#991B1B';
                statusText.textContent = 'Application Rejected';
                statusBadge.innerHTML = '<span class="status-badge status-rejected"><i data-lucide="x-circle" style="width: 14px; height: 14px;"></i> Rejected</span>';
                rejectionNotice.style.display = 'block';
                document.getElementById('modalProvRejectionReasonText').textContent = data.reason || 'Trade and credential verification failed.';
            } else {
                statusBanner.style.background = '#FEF3C7';
                statusBanner.style.border = '1px solid #FDE68A';
                statusText.style.color = '#92400E';
                statusText.textContent = 'Pending Admin Review';
                statusBadge.innerHTML = '<span class="status-badge status-pending"><i data-lucide="clock" style="width: 14px; height: 14px;"></i> Awaiting Approval</span>';
                rejectionNotice.style.display = 'none';
            }

            document.getElementById('modalProvSkill').textContent = data.skill || '-';
            document.getElementById('modalProvExp').textContent = data.experience || '-';
            document.getElementById('modalProvJobsCount').textContent = (data.assignedJobs || 0) + ' Jobs assigned';
            document.getElementById('modalProvJoined').textContent = data.joined || '-';

            document.getElementById('modalProvMobile').textContent = data.mobile || '-';
            document.getElementById('modalProvEmail').textContent = data.email || '-';
            document.getElementById('modalProvCity').textContent = data.city || '-';
            document.getElementById('modalProvArea').textContent = data.area || 'All City Zones';
            document.getElementById('modalProvAddress').textContent = data.address || '-';

            // Bookings container
            const bookings = data.bookings || [];
            const container = document.getElementById('modalProvBookingsContainer');
            if (bookings.length === 0) {
                container.innerHTML = `
                    <div style="padding: 1.25rem; text-align: center; background: #F8FAFC; border-radius: 10px; border: 1px dashed #CBD5E1; color: #64748B; font-size: 0.85rem;">
                        No jobs currently assigned to this partner.
                    </div>
                `;
            } else {
                let html = `
                    <table class="bookings-subtable">
                        <thead>
                            <tr>
                                <th>Booking Ref</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                bookings.forEach(b => {
                    const statusClass = b.status === 'Completed' ? 'status-approved' : (b.status === 'Cancelled' ? 'status-rejected' : 'status-pending');
                    html += `
                        <tr>
                            <td><strong style="font-family: var(--font-mono); font-size: 0.78rem;">${escapeHtml(b.booking_reference)}</strong></td>
                            <td>${escapeHtml(b.customer_name)}</td>
                            <td>${escapeHtml(b.service_category)}</td>
                            <td>${escapeHtml(b.service_date)}</td>
                            <td><span class="status-badge ${statusClass}">${escapeHtml(b.status)}</span></td>
                        </tr>
                    `;
                });
                html += `</tbody></table>`;
                container.innerHTML = html;
            }

            // Footer Actions
            const csrfToken = "<?= htmlspecialchars($csrfToken); ?>";
            let footerHtml = `<div>`;
            if (data.status !== 'Approved') {
                footerHtml += `
                    <form action="dashboard.php?tab=providers" method="POST" style="display: inline;" onsubmit="return confirm('Approve partner #MITRA-${String(data.id).padStart(3, '0')} (${escapeHtml(data.name)})?');">
                        <input type="hidden" name="csrf_token" value="${csrfToken}">
                        <input type="hidden" name="action_type" value="approve_partner">
                        <input type="hidden" name="provider_id" value="${data.id}">
                        <button type="submit" class="btn-action-sm btn-quick-approve" style="padding: 0.5rem 1rem;">
                            <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                            <span>Approve Partner</span>
                        </button>
                    </form>
                `;
            }
            if (data.status !== 'Rejected') {
                footerHtml += `
                    <button type="button" class="btn-action-sm btn-quick-reject" style="padding: 0.5rem 1rem; margin-left: 0.4rem;" onclick="closeModal('providerModalOverlay'); openRejectModal(${data.id}, '${escapeHtml(data.name)}');">
                        <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                        <span>Reject Application</span>
                    </button>
                `;
            }
            if (data.status !== 'Pending') {
                footerHtml += `
                    <form action="dashboard.php?tab=providers" method="POST" style="display: inline; margin-left: 0.4rem;" onsubmit="return confirm('Reset verification status to Pending?');">
                        <input type="hidden" name="csrf_token" value="${csrfToken}">
                        <input type="hidden" name="action_type" value="reset_partner_pending">
                        <input type="hidden" name="provider_id" value="${data.id}">
                        <button type="submit" class="btn-action-sm btn-quick-reset" style="padding: 0.5rem 1rem;">
                            <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                            <span>Reset to Pending</span>
                        </button>
                    </form>
                `;
            }
            footerHtml += `</div>
                <button type="button" class="btn-action-sm btn-view-details" onclick="closeModal('providerModalOverlay')">Close</button>
            `;

            document.getElementById('modalProvFooterActions').innerHTML = footerHtml;

            openModal('providerModalOverlay');
        }

        // ---------------------------------------------------------------------
        // Open Reject Prompt Modal
        // ---------------------------------------------------------------------
        function openRejectModal(providerId, providerName) {
            document.getElementById('rejectPartnerIdInput').value = providerId;
            document.getElementById('rejectPartnerNameText').textContent = providerName + ' (#MITRA-' + String(providerId).padStart(3, '0') + ')';
            openModal('rejectPromptModalOverlay');
            document.getElementById('rejectionReasonInput').focus();
        }

        // Helper
        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
<?php
$portalName = 'Admin Central Operations Console';
require __DIR__ . '/../components/footer-portal.php';
?>
</body>
</html>
