<?php
/**
 * KaamMitra - Central Administrative Management Console
 *
 * Implements strict RBAC separating Super Admin governance from standard Admin operations.
 * Connects directly to actual SQL records with zero mocked or fabricated metrics.
 * Protects all state changes with CSRF tokens and records complete immutable audit trails.
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// 1. Enforce authentication and resolve fresh status from database
$currentAdmin = requireAdmin('../auth/admin/login.php');
$isSuperAdmin = ($currentAdmin['role'] === 'super_admin');

$pdo = getDBConnection();
if (!$pdo) {
    die("Database service temporarily unavailable. Please retry shortly.");
}

$activeTab = trim($_GET['tab'] ?? 'overview');
$allowedTabs = ['overview', 'providers', 'bookings', 'customers', 'reports'];
if ($isSuperAdmin) {
    $allowedTabs[] = 'admins';
    $allowedTabs[] = 'audit';
}

// Enforce tab access control: Non-super admins cannot view admins or audit tabs
if (!in_array($activeTab, $allowedTabs, true)) {
    if (in_array($activeTab, ['admins', 'audit'], true) && !$isSuperAdmin) {
        logAdminAudit(
            (int)$currentAdmin['id'],
            'RESTRICTED_TAB_VIEW_BLOCKED',
            'security',
            $activeTab,
            "Admin '{$currentAdmin['username']}' attempted unauthorized navigation to '{$activeTab}' tab"
        );
        header("HTTP/1.1 403 Forbidden");
        $activeTab = 'overview';
        $actionError = "Access Restricted: You do not have Super Administrator clearance to view the requested section.";
    } else {
        $activeTab = 'overview';
    }
}

$actionSuccess = '';
$actionError = $actionError ?? '';

// 2. Handle State-Changing POST Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $actionError = 'Security validation failed (CSRF token expired or invalid). Please try again.';
    } else {
        switch ($action) {
            // PROVIDER VERIFICATION
            case 'approve_provider':
                $providerId = (int)($_POST['provider_id'] ?? 0);
                $res = updateProviderVerification($providerId, 'Approved', null, (int)$currentAdmin['id']);
                if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                break;

            case 'reject_provider':
                $providerId = (int)($_POST['provider_id'] ?? 0);
                $reason = trim($_POST['rejection_reason'] ?? '');
                $res = updateProviderVerification($providerId, 'Rejected', $reason, (int)$currentAdmin['id']);
                if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                break;

            case 'reset_provider_pending':
                $providerId = (int)($_POST['provider_id'] ?? 0);
                $res = updateProviderVerification($providerId, 'Pending', null, (int)$currentAdmin['id']);
                if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                break;

            // BOOKING STATUS
            case 'update_booking_status':
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                $res = updateBookingStatus($bookingId, $status, (int)$currentAdmin['id']);
                if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                break;

            // CUSTOMER STATUS
            case 'update_customer_status':
                $customerId = (int)($_POST['customer_id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                $res = updateCustomerStatus($customerId, $status, (int)$currentAdmin['id']);
                if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                break;

            // SUPER ADMIN PRIVILEGES: CREATE ADMINISTRATOR
            case 'create_admin':
                if (!$isSuperAdmin) {
                    logAdminAudit((int)$currentAdmin['id'], 'UNAUTHORIZED_CREATE_ADMIN_ATTEMPT', 'security', null, "Standard admin '{$currentAdmin['username']}' attempted admin creation");
                    $actionError = "Permission Denied: Only Super Administrators can create administrative accounts.";
                } else {
                    $payload = [
                        'username'  => trim($_POST['new_username'] ?? ''),
                        'full_name' => trim($_POST['new_full_name'] ?? ''),
                        'email'     => trim($_POST['new_email'] ?? ''),
                        'password'  => $_POST['new_password'] ?? '',
                        'role'      => trim($_POST['new_role'] ?? 'admin')
                    ];
                    $res = createAdminAccount($payload, (int)$currentAdmin['id']);
                    if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                }
                break;

            // SUPER ADMIN PRIVILEGES: TOGGLE ADMIN STATUS (ACTIVATE / DEACTIVATE)
            case 'toggle_admin_status':
                if (!$isSuperAdmin) {
                    logAdminAudit((int)$currentAdmin['id'], 'UNAUTHORIZED_STATUS_CHANGE_ATTEMPT', 'security', null, "Standard admin attempted status change");
                    $actionError = "Permission Denied: Only Super Administrators can alter administrator account status.";
                } else {
                    $targetId = (int)($_POST['target_id'] ?? 0);
                    $newStatus = (int)($_POST['new_status'] ?? 0);
                    $res = updateAdminStatus($targetId, $newStatus, (int)$currentAdmin['id']);
                    if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                }
                break;

            // SUPER ADMIN PRIVILEGES: UPDATE ADMIN ROLE
            case 'update_admin_role':
                if (!$isSuperAdmin) {
                    logAdminAudit((int)$currentAdmin['id'], 'UNAUTHORIZED_ROLE_CHANGE_ATTEMPT', 'security', null, "Standard admin attempted role elevation");
                    $actionError = "Permission Denied: Only Super Administrators can assign or change roles.";
                } else {
                    $targetId = (int)($_POST['target_id'] ?? 0);
                    $newRole = trim($_POST['new_role'] ?? 'admin');
                    $res = updateAdminRole($targetId, $newRole, (int)$currentAdmin['id']);
                    if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                }
                break;

            // SUPER ADMIN PRIVILEGES: RESET ADMIN PASSWORD
            case 'reset_admin_password':
                if (!$isSuperAdmin) {
                    logAdminAudit((int)$currentAdmin['id'], 'UNAUTHORIZED_PASSWORD_RESET_ATTEMPT', 'security', null, "Standard admin attempted password reset");
                    $actionError = "Permission Denied: Only Super Administrators can reset passwords.";
                } else {
                    $targetId = (int)($_POST['target_id'] ?? 0);
                    $newPassword = $_POST['new_password'] ?? '';
                    $res = resetAdminPassword($targetId, $newPassword, (int)$currentAdmin['id']);
                    if ($res['success']) { $actionSuccess = $res['message']; } else { $actionError = $res['message']; }
                }
                break;
        }
    }
}

// 3. Compute Real Platform Metrics Directly From SQL (No Mock Data)
$metricTotalCustomers   = (int)$pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
$metricTotalProviders   = (int)$pdo->query("SELECT COUNT(*) FROM `service_providers`")->fetchColumn();
$metricPendingProviders = (int)$pdo->query("SELECT COUNT(*) FROM `service_providers` WHERE `verification_status` = 'Pending'")->fetchColumn();
$metricApprovedProviders= (int)$pdo->query("SELECT COUNT(*) FROM `service_providers` WHERE `verification_status` = 'Approved'")->fetchColumn();
$metricTotalBookings    = (int)$pdo->query("SELECT COUNT(*) FROM `bookings`")->fetchColumn();
$metricActiveBookings   = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` IN ('Confirmed', 'In Progress')")->fetchColumn();
$metricCompletedBookings= (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Completed'")->fetchColumn();
$metricTotalRevenue     = (float)$pdo->query("SELECT COALESCE(SUM(`estimated_price`), 0) FROM `bookings` WHERE `status` = 'Completed'")->fetchColumn();
$metricTotalAdmins      = (int)$pdo->query("SELECT COUNT(*) FROM `admins` WHERE `is_active` = 1")->fetchColumn();

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console - <?= htmlspecialchars(APP_NAME); ?> Administrative Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/forms.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <script src="../assets/js/lucide.min.js"></script>
</head>
<body class="admin-body">

    <!-- Top Administrative Navigation -->
    <header class="admin-top-nav">
        <a href="index.php" class="admin-nav-brand">
            <div class="admin-brand-icon">
                <i data-lucide="shield" style="width: 20px; height: 20px;"></i>
            </div>
            <h1 class="admin-brand-title">Kaam<span>Mitra</span></h1>
            <span class="admin-console-pill">Admin Console</span>
        </a>

        <div class="admin-nav-right">
            <!-- Role Badge -->
            <?php if ($isSuperAdmin): ?>
                <span class="admin-role-badge role-super-admin" title="Full Platform Governance Privileges">
                    <i data-lucide="crown" style="width: 14px; height: 14px;"></i>
                    <span>Super Administrator</span>
                </span>
            <?php else: ?>
                <span class="admin-role-badge role-admin" title="Restricted Operational Privileges">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                    <span>Operational Admin</span>
                </span>
            <?php endif; ?>

            <!-- Admin Profile Display -->
            <div class="admin-user-info">
                <div class="admin-avatar-circle">
                    <?= strtoupper(substr($currentAdmin['username'], 0, 1)); ?>
                </div>
                <span><?= htmlspecialchars($currentAdmin['full_name'] ?: $currentAdmin['username']); ?></span>
            </div>

            <!-- View Public Site -->
            <a href="../index.php" target="_blank" class="btn-admin btn-admin-outline" style="border-color: rgba(255,255,255,0.2); color: rgba(255,255,255,0.85);" title="Open Marketplace in new tab">
                <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                <span>Marketplace</span>
            </a>

            <!-- Sign Out -->
            <a href="logout.php" class="admin-signout-btn" title="Sign out securely">
                <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </header>

    <!-- Main Console Container -->
    <div class="admin-layout-wrapper">
        
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="admin-nav-section-title">Core Operations</div>
            <ul class="admin-sidebar-links">
                <li>
                    <a href="index.php?tab=overview" class="admin-sidebar-link <?= $activeTab === 'overview' ? 'active' : ''; ?>">
                        <div class="link-left">
                            <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                            <span>Overview</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="index.php?tab=providers" class="admin-sidebar-link <?= $activeTab === 'providers' ? 'active' : ''; ?>">
                        <div class="link-left">
                            <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                            <span>Service Mitras</span>
                        </div>
                        <?php if ($metricPendingProviders > 0): ?>
                            <span class="admin-nav-counter" title="Pending Verification Requests"><?= $metricPendingProviders; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="index.php?tab=bookings" class="admin-sidebar-link <?= $activeTab === 'bookings' ? 'active' : ''; ?>">
                        <div class="link-left">
                            <i data-lucide="calendar-check" style="width: 18px; height: 18px;"></i>
                            <span>Bookings</span>
                        </div>
                        <?php if ($metricActiveBookings > 0): ?>
                            <span class="admin-nav-counter" style="background: #EFF6FF; color: #1E40AF; border-color: #BFDBFE;"><?= $metricActiveBookings; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="index.php?tab=customers" class="admin-sidebar-link <?= $activeTab === 'customers' ? 'active' : ''; ?>">
                        <div class="link-left">
                            <i data-lucide="user-check" style="width: 18px; height: 18px;"></i>
                            <span>Customers</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="index.php?tab=reports" class="admin-sidebar-link <?= $activeTab === 'reports' ? 'active' : ''; ?>">
                        <div class="link-left">
                            <i data-lucide="bar-chart-3" style="width: 18px; height: 18px;"></i>
                            <span>Operational Reports</span>
                        </div>
                    </a>
                </li>
            </ul>

            <!-- Super Admin Governed Management Section -->
            <?php if ($isSuperAdmin): ?>
                <div class="admin-nav-section-title" style="margin-top: 0.5rem; color: #7C3AED;">Super Admin Controls</div>
                <ul class="admin-sidebar-links">
                    <li>
                        <a href="index.php?tab=admins" class="admin-sidebar-link <?= $activeTab === 'admins' ? 'active' : ''; ?>">
                            <div class="link-left">
                                <i data-lucide="shield-alert" style="width: 18px; height: 18px; color: #7C3AED;"></i>
                                <span>Administrators</span>
                            </div>
                            <span class="admin-nav-counter" style="background: #F5F3FF; color: #7C3AED; border-color: #DDD6FE;"><?= $metricTotalAdmins; ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?tab=audit" class="admin-sidebar-link <?= $activeTab === 'audit' ? 'active' : ''; ?>">
                            <div class="link-left">
                                <i data-lucide="scroll-text" style="width: 18px; height: 18px; color: #7C3AED;"></i>
                                <span>Security Audit Logs</span>
                            </div>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>

            <div style="margin-top: auto; padding: 1rem 0.5rem; border-top: 1px solid var(--admin-border); font-size: 0.775rem; color: var(--admin-text-muted);">
                <div style="display: flex; align-items: center; gap: 0.4rem; font-weight: 700; color: var(--admin-navy);">
                    <i data-lucide="lock" style="width: 14px; height: 14px;"></i>
                    <span>RBAC Enforced</span>
                </div>
                <div style="margin-top: 0.25rem;">Server-side validated on every request.</div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-content-area">

            <!-- System Alerts -->
            <?php if (!empty($actionSuccess)): ?>
                <div class="alert-box alert-success" style="margin-bottom: 1.5rem; background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 0.85rem 1.25rem; border-radius: 12px; display: flex; align-items: center; gap: 0.65rem;">
                    <i data-lucide="check-circle" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
                    <span style="font-weight: 600;"><?= htmlspecialchars($actionSuccess); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($actionError)): ?>
                <div class="alert-box alert-error" style="margin-bottom: 1.5rem; background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 0.85rem 1.25rem; border-radius: 12px; display: flex; align-items: center; gap: 0.65rem;">
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
                    <span style="font-weight: 600;"><?= htmlspecialchars($actionError); ?></span>
                </div>
            <?php endif; ?>

            <!-- =============================================================
                 TAB 1: OVERVIEW DASHBOARD
                 ============================================================= -->
            <?php if ($activeTab === 'overview'): ?>
                <div class="admin-page-header">
                    <div>
                        <h2 class="admin-page-title">Executive Overview</h2>
                        <p class="admin-page-desc">Real-time platform metrics, technician verification queues, and booking pipeline.</p>
                    </div>
                    <div>
                        <span style="font-size: 0.825rem; color: var(--admin-text-muted); font-weight: 600;">
                            Live SQL sync &bull; <?= date('d M Y, h:i A'); ?>
                        </span>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="admin-metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon-wrap metric-icon-amber">
                            <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="metric-data">
                            <span class="metric-value"><?= number_format($metricPendingProviders); ?></span>
                            <span class="metric-label">Pending Verifications</span>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon-wrap metric-icon-green">
                            <i data-lucide="user-check" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="metric-data">
                            <span class="metric-value"><?= number_format($metricApprovedProviders); ?></span>
                            <span class="metric-label">Approved Providers</span>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon-wrap metric-icon-blue">
                            <i data-lucide="calendar" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="metric-data">
                            <span class="metric-value"><?= number_format($metricActiveBookings); ?></span>
                            <span class="metric-label">Active Bookings</span>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon-wrap metric-icon-coral">
                            <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="metric-data">
                            <span class="metric-value"><?= number_format($metricTotalCustomers); ?></span>
                            <span class="metric-label">Registered Customers</span>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon-wrap metric-icon-purple">
                            <i data-lucide="wallet" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="metric-data">
                            <span class="metric-value">₹<?= number_format($metricTotalRevenue, 2); ?></span>
                            <span class="metric-label">Delivered Revenue</span>
                        </div>
                    </div>
                </div>

                <!-- Priority Section: Pending Verification Queue -->
                <div class="admin-card">
                    <div class="admin-card-head">
                        <h3 class="admin-card-title">
                            <i data-lucide="shield-alert" style="width: 18px; height: 18px; color: var(--admin-amber);"></i>
                            <span>Priority Verification Queue (Pending Review)</span>
                        </h3>
                        <a href="index.php?tab=providers&status=Pending" class="btn-admin btn-admin-outline">View All Pending</a>
                    </div>
                    <div class="admin-card-body" style="padding: 0;">
                        <?php
                        $pendingStmt = $pdo->prepare("SELECT * FROM `service_providers` WHERE `verification_status` = 'Pending' ORDER BY `created_at` DESC LIMIT 5");
                        $pendingStmt->execute();
                        $pendingList = $pendingStmt->fetchAll();
                        ?>
                        <?php if (empty($pendingList)): ?>
                            <div style="padding: 2.5rem; text-align: center; color: var(--admin-text-muted);">
                                <i data-lucide="check-circle-2" style="width: 36px; height: 36px; color: var(--admin-green); margin-bottom: 0.5rem;"></i>
                                <p style="margin: 0; font-weight: 700;">All provider applications have been reviewed!</p>
                                <span style="font-size: 0.85rem;">No technicians currently awaiting administrative clearance.</span>
                            </div>
                        <?php else: ?>
                            <div class="admin-table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Applicant Mitra</th>
                                            <th>Skill / Trade</th>
                                            <th>Experience</th>
                                            <th>Coverage City</th>
                                            <th>Registered On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingList as $p): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--admin-navy);"><?= htmlspecialchars($p['full_name']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($p['mobile']); ?> &bull; <?= htmlspecialchars($p['email']); ?></div>
                                                </td>
                                                <td><span class="badge-status" style="background: #F1F5F9; color: #334155; border: 1px solid #CBD5E1;"><?= htmlspecialchars($p['primary_skill']); ?></span></td>
                                                <td><?= htmlspecialchars($p['experience_years']); ?></td>
                                                <td><?= htmlspecialchars($p['city']); ?> <?= !empty($p['service_area']) ? '(' . htmlspecialchars($p['service_area']) . ')' : ''; ?></td>
                                                <td><?= date('d M Y, h:i A', strtotime($p['created_at'])); ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 0.5rem;">
                                                        <!-- Quick Approve Form -->
                                                        <form method="POST" onsubmit="return confirm('Approve <?= htmlspecialchars(addslashes($p['full_name'])); ?> as a verified KaamMitra service provider?');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="approve_provider">
                                                            <input type="hidden" name="provider_id" value="<?= (int)$p['id']; ?>">
                                                            <button type="submit" class="btn-admin btn-admin-success" title="Approve credentials">
                                                                <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                                                <span>Approve</span>
                                                            </button>
                                                        </form>

                                                        <!-- Reject Trigger (opens prompt) -->
                                                        <form method="POST" onsubmit="var reason = prompt('Please enter the rejection reason for <?= htmlspecialchars(addslashes($p['full_name'])); ?>:'); if (!reason || !reason.trim()) { alert('Rejection reason is required.'); return false; } document.getElementById('reject_reason_<?= $p['id']; ?>').value = reason; return true;">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="reject_provider">
                                                            <input type="hidden" name="provider_id" value="<?= (int)$p['id']; ?>">
                                                            <input type="hidden" name="rejection_reason" id="reject_reason_<?= $p['id']; ?>" value="">
                                                            <button type="submit" class="btn-admin btn-admin-danger" title="Reject application">
                                                                <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                                                <span>Reject</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Bookings Feed -->
                <div class="admin-card">
                    <div class="admin-card-head">
                        <h3 class="admin-card-title">
                            <i data-lucide="calendar" style="width: 18px; height: 18px; color: var(--admin-blue);"></i>
                            <span>Recent Doorstep Bookings</span>
                        </h3>
                        <a href="index.php?tab=bookings" class="btn-admin btn-admin-outline">View All Bookings</a>
                    </div>
                    <div class="admin-card-body" style="padding: 0;">
                        <?php
                        $recentBStmt = $pdo->query("SELECT * FROM `bookings` ORDER BY `id` DESC LIMIT 6");
                        $recentBookings = $recentBStmt->fetchAll();
                        ?>
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Ref Code</th>
                                        <th>Customer</th>
                                        <th>Service Category</th>
                                        <th>City</th>
                                        <th>Service Date</th>
                                        <th>Assigned Mitra</th>
                                        <th>Est. Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBookings as $b): ?>
                                        <tr>
                                            <td><strong style="color: var(--admin-primary);"><?= htmlspecialchars($b['booking_reference']); ?></strong></td>
                                            <td>
                                                <div style="font-weight: 700;"><?= htmlspecialchars($b['customer_name']); ?></div>
                                                <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($b['phone']); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600;"><?= htmlspecialchars($b['service_category']); ?></div>
                                                <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($b['sub_service']); ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($b['city']); ?></td>
                                            <td><?= htmlspecialchars($b['service_date']); ?> (<?= htmlspecialchars($b['time_slot']); ?>)</td>
                                            <td><?= htmlspecialchars($b['mitra_name'] ?: 'Auto-Dispatching'); ?></td>
                                            <td><strong>₹<?= number_format((float)$b['estimated_price'], 2); ?></strong></td>
                                            <td>
                                                <?php
                                                $st = $b['status'];
                                                $badgeClass = match($st) {
                                                    'Confirmed' => 'badge-pending',
                                                    'In Progress' => 'badge-completed',
                                                    'Completed' => 'badge-approved',
                                                    'Cancelled' => 'badge-rejected',
                                                    default => 'badge-pending'
                                                };
                                                ?>
                                                <span class="badge-status <?= $badgeClass; ?>"><?= htmlspecialchars($st); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- =============================================================
                 TAB 2: SERVICE PROVIDER MANAGEMENT & VERIFICATION
                 ============================================================= -->
            <?php elseif ($activeTab === 'providers'): ?>
                <?php
                $statusFilter = trim($_GET['status'] ?? 'All');
                $sql = "SELECT * FROM `service_providers`";
                $params = [];
                if (in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true)) {
                    $sql .= " WHERE `verification_status` = :status";
                    $params[':status'] = $statusFilter;
                }
                $sql .= " ORDER BY `created_at` DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $providers = $stmt->fetchAll();
                ?>

                <div class="admin-page-header">
                    <div>
                        <h2 class="admin-page-title">Service Provider (Mitra) Management</h2>
                        <p class="admin-page-desc">Review verification requests, manage approved technicians, and update credential status.</p>
                    </div>
                    <div class="admin-filter-bar">
                        <a href="index.php?tab=providers&status=All" class="admin-filter-pill <?= $statusFilter === 'All' ? 'active' : ''; ?>">All (<?= $metricTotalProviders; ?>)</a>
                        <a href="index.php?tab=providers&status=Pending" class="admin-filter-pill <?= $statusFilter === 'Pending' ? 'active' : ''; ?>" style="<?= $metricPendingProviders > 0 ? 'border-color: #F59E0B;' : ''; ?>">Pending (<?= $metricPendingProviders; ?>)</a>
                        <a href="index.php?tab=providers&status=Approved" class="admin-filter-pill <?= $statusFilter === 'Approved' ? 'active' : ''; ?>">Approved (<?= $metricApprovedProviders; ?>)</a>
                        <a href="index.php?tab=providers&status=Rejected" class="admin-filter-pill <?= $statusFilter === 'Rejected' ? 'active' : ''; ?>">Rejected</a>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-body" style="padding: 0;">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Mitra Name</th>
                                        <th>Contact</th>
                                        <th>Skill &amp; Experience</th>
                                        <th>City &amp; Locality</th>
                                        <th>Status</th>
                                        <th>Registered Date</th>
                                        <th>Review Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($providers)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
                                                No service providers found matching the selected filter.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($providers as $prov): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--admin-navy);"><?= htmlspecialchars($prov['full_name']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);">ID: #<?= (int)$prov['id']; ?></div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($prov['mobile']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($prov['email']); ?></div>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 600;"><?= htmlspecialchars($prov['primary_skill']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($prov['experience_years']); ?> experience</div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($prov['city']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($prov['service_area'] ?? 'City-wide'); ?></div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $vSt = $prov['verification_status'];
                                                    $vBadge = match($vSt) {
                                                        'Approved' => 'badge-approved',
                                                        'Pending' => 'badge-pending',
                                                        'Rejected' => 'badge-rejected',
                                                        default => 'badge-pending'
                                                    };
                                                    ?>
                                                    <span class="badge-status <?= $vBadge; ?>"><?= htmlspecialchars($vSt); ?></span>
                                                    <?php if ($vSt === 'Rejected' && !empty($prov['rejection_reason'])): ?>
                                                        <div style="font-size: 0.75rem; color: #DC2626; margin-top: 0.25rem; max-width: 220px;" title="<?= htmlspecialchars($prov['rejection_reason']); ?>">
                                                            <em>Reason: <?= htmlspecialchars($prov['rejection_reason']); ?></em>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d M Y', strtotime($prov['created_at'])); ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                                        <?php if ($vSt !== 'Approved'): ?>
                                                            <form method="POST" onsubmit="return confirm('Approve verification for <?= htmlspecialchars(addslashes($prov['full_name'])); ?>?');">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="approve_provider">
                                                                <input type="hidden" name="provider_id" value="<?= (int)$prov['id']; ?>">
                                                                <button type="submit" class="btn-admin btn-admin-success" title="Approve">
                                                                    <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                                                    <span>Approve</span>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <?php if ($vSt !== 'Rejected'): ?>
                                                            <form method="POST" onsubmit="var reason = prompt('Enter rejection reason for <?= htmlspecialchars(addslashes($prov['full_name'])); ?>:'); if (!reason || !reason.trim()) { alert('Rejection reason required.'); return false; } document.getElementById('prov_rej_<?= $prov['id']; ?>').value = reason; return true;">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="reject_provider">
                                                                <input type="hidden" name="provider_id" value="<?= (int)$prov['id']; ?>">
                                                                <input type="hidden" name="rejection_reason" id="prov_rej_<?= $prov['id']; ?>" value="">
                                                                <button type="submit" class="btn-admin btn-admin-danger" title="Reject">
                                                                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                                                    <span>Reject</span>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <?php if ($vSt !== 'Pending'): ?>
                                                            <form method="POST" onsubmit="return confirm('Reset verification status to Pending?');">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="reset_provider_pending">
                                                                <input type="hidden" name="provider_id" value="<?= (int)$prov['id']; ?>">
                                                                <button type="submit" class="btn-admin btn-admin-outline" title="Reset to Pending">
                                                                    <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
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
                    </div>
                </div>

            <!-- =============================================================
                 TAB 3: BOOKINGS MANAGEMENT
                 ============================================================= -->
            <?php elseif ($activeTab === 'bookings'): ?>
                <?php
                $statusFilter = trim($_GET['status'] ?? 'All');
                $sql = "SELECT * FROM `bookings`";
                $params = [];
                if (in_array($statusFilter, ['Confirmed', 'In Progress', 'Completed', 'Cancelled'], true)) {
                    $sql .= " WHERE `status` = :status";
                    $params[':status'] = $statusFilter;
                }
                $sql .= " ORDER BY `id` DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $bookings = $stmt->fetchAll();
                ?>

                <div class="admin-page-header">
                    <div>
                        <h2 class="admin-page-title">Doorstep Bookings &amp; Service Pipeline</h2>
                        <p class="admin-page-desc">Track real-time orders, manage dispatch statuses, and oversee fulfillment.</p>
                    </div>
                    <div class="admin-filter-bar">
                        <a href="index.php?tab=bookings&status=All" class="admin-filter-pill <?= $statusFilter === 'All' ? 'active' : ''; ?>">All (<?= $metricTotalBookings; ?>)</a>
                        <a href="index.php?tab=bookings&status=Confirmed" class="admin-filter-pill <?= $statusFilter === 'Confirmed' ? 'active' : ''; ?>">Confirmed</a>
                        <a href="index.php?tab=bookings&status=In Progress" class="admin-filter-pill <?= $statusFilter === 'In Progress' ? 'active' : ''; ?>">In Progress</a>
                        <a href="index.php?tab=bookings&status=Completed" class="admin-filter-pill <?= $statusFilter === 'Completed' ? 'active' : ''; ?>">Completed (<?= $metricCompletedBookings; ?>)</a>
                        <a href="index.php?tab=bookings&status=Cancelled" class="admin-filter-pill <?= $statusFilter === 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-body" style="padding: 0;">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Ref Code</th>
                                        <th>Customer Details</th>
                                        <th>Service &amp; Category</th>
                                        <th>Address &amp; City</th>
                                        <th>Scheduled Slot</th>
                                        <th>Assigned Mitra</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Update Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bookings)): ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
                                                No bookings found matching the selected filter.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bookings as $b): ?>
                                            <tr>
                                                <td><strong style="color: var(--admin-primary);"><?= htmlspecialchars($b['booking_reference']); ?></strong></td>
                                                <td>
                                                    <div style="font-weight: 700;"><?= htmlspecialchars($b['customer_name']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($b['phone']); ?></div>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 600;"><?= htmlspecialchars($b['service_category']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($b['sub_service']); ?></div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($b['city']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($b['address']); ?>">
                                                        <?= htmlspecialchars($b['address']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($b['service_date']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= htmlspecialchars($b['time_slot']); ?></div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($b['mitra_name'] ?: 'Unassigned'); ?></div>
                                                    <?php if (!empty($b['mitra_rating'])): ?>
                                                        <div style="font-size: 0.75rem; color: #D97706; font-weight: 700;">★ <?= htmlspecialchars($b['mitra_rating']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong>₹<?= number_format((float)$b['estimated_price'], 2); ?></strong></td>
                                                <td>
                                                    <?php
                                                    $st = $b['status'];
                                                    $bBadge = match($st) {
                                                        'Confirmed' => 'badge-pending',
                                                        'In Progress' => 'badge-completed',
                                                        'Completed' => 'badge-approved',
                                                        'Cancelled' => 'badge-rejected',
                                                        default => 'badge-pending'
                                                    };
                                                    ?>
                                                    <span class="badge-status <?= $bBadge; ?>"><?= htmlspecialchars($st); ?></span>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display: flex; gap: 0.35rem; align-items: center;">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="update_booking_status">
                                                        <input type="hidden" name="booking_id" value="<?= (int)$b['id']; ?>">
                                                        <select name="status" class="input-control" style="padding: 0.35rem 0.5rem; font-size: 0.8rem; height: auto;">
                                                            <option value="Confirmed" <?= $st === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                            <option value="In Progress" <?= $st === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                            <option value="Completed" <?= $st === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="Cancelled" <?= $st === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                        <button type="submit" class="btn-admin btn-admin-outline" style="padding: 0.35rem 0.6rem;" title="Save status">
                                                            <i data-lucide="save" style="width: 14px; height: 14px;"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- =============================================================
                 TAB 4: CUSTOMERS DIRECTORY
                 ============================================================= -->
            <?php elseif ($activeTab === 'customers'): ?>
                <?php
                $search = trim($_GET['q'] ?? '');
                $sql = "SELECT * FROM `customers`";
                $params = [];
                if (!empty($search)) {
                    $sql .= " WHERE `full_name` LIKE :q OR `email` LIKE :q OR `mobile` LIKE :q OR `city` LIKE :q";
                    $params[':q'] = "%{$search}%";
                }
                $sql .= " ORDER BY `created_at` DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $customers = $stmt->fetchAll();
                ?>

                <div class="admin-page-header">
                    <div>
                        <h2 class="admin-page-title">Customer Accounts Directory</h2>
                        <p class="admin-page-desc">Search registered homeowner profiles, view verification terms, and manage access.</p>
                    </div>
                    <div>
                        <form method="GET" style="display: flex; gap: 0.5rem;">
                            <input type="hidden" name="tab" value="customers">
                            <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Search name, phone, email..." class="input-control" style="width: 240px; padding: 0.45rem 0.85rem; font-size: 0.85rem;">
                            <button type="submit" class="btn-admin btn-admin-primary">Search</button>
                            <?php if (!empty($search)): ?>
                                <a href="index.php?tab=customers" class="btn-admin btn-admin-outline">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-body" style="padding: 0;">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Mobile &amp; Email</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Terms Accepted</th>
                                        <th>Joined Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
                                                No customers found matching search query.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($customers as $c): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--admin-navy);"><?= htmlspecialchars($c['full_name']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);">ID: #<?= (int)$c['id']; ?> &bull; <?= htmlspecialchars($c['gender'] ?? 'Not specified'); ?></div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($c['mobile']); ?></div>
                                                    <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($c['email']); ?></div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($c['city'] ?: 'City not set'); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= htmlspecialchars($c['address_line'] ?? ''); ?></div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $cStatus = $c['status'] ?? 'Active';
                                                    $cBadge = ($cStatus === 'Active') ? 'badge-approved' : 'badge-rejected';
                                                    ?>
                                                    <span class="badge-status <?= $cBadge; ?>"><?= htmlspecialchars($cStatus); ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($c['terms_accepted'])): ?>
                                                        <span style="color: #059669; font-weight: 700; font-size: 0.8rem;">✓ Verified</span>
                                                        <div style="font-size: 0.7rem; color: var(--admin-text-muted);"><?= htmlspecialchars($c['terms_accepted_at'] ?? ''); ?></div>
                                                    <?php else: ?>
                                                        <span style="color: #94A3B8; font-size: 0.8rem;">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d M Y', strtotime($c['created_at'])); ?></td>
                                                <td>
                                                    <form method="POST" onsubmit="return confirm('Toggle status for <?= htmlspecialchars(addslashes($c['full_name'])); ?>?');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="update_customer_status">
                                                        <input type="hidden" name="customer_id" value="<?= (int)$c['id']; ?>">
                                                        <input type="hidden" name="status" value="<?= $cStatus === 'Active' ? 'Suspended' : 'Active'; ?>">
                                                        <button type="submit" class="btn-admin <?= $cStatus === 'Active' ? 'btn-admin-danger' : 'btn-admin-success'; ?>" style="font-size: 0.775rem; padding: 0.35rem 0.65rem;">
                                                            <?= $cStatus === 'Active' ? 'Suspend' : 'Reactivate'; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- =============================================================
                 TAB 5: OPERATIONAL REPORTS
                 ============================================================= -->
            <?php elseif ($activeTab === 'reports'): ?>
                <div class="admin-page-header">
                    <div>
                        <h2 class="admin-page-title">Operational Reports &amp; Aggregates</h2>
                        <p class="admin-page-desc">Comprehensive database performance figures, category distribution, and geographic coverage.</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <!-- Breakdown by Category -->
                    <div class="admin-card">
                        <div class="admin-card-head">
                            <h3 class="admin-card-title">Bookings by Service Category</h3>
                        </div>
                        <div class="admin-card-body" style="padding: 0;">
                            <?php
                            $catStmt = $pdo->query("
                                SELECT `service_category`, COUNT(*) as `total`, SUM(`estimated_price`) as `revenue`
                                FROM `bookings`
                                GROUP BY `service_category`
                                ORDER BY `total` DESC
                            ");
                            $catStats = $catStmt->fetchAll();
                            ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Trade Category</th>
                                        <th>Bookings</th>
                                        <th>Gross Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($catStats as $cs): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($cs['service_category']); ?></strong></td>
                                            <td><?= number_format($cs['total']); ?></td>
                                            <td>₹<?= number_format((float)$cs['revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Breakdown by City -->
                    <div class="admin-card">
                        <div class="admin-card-head">
                            <h3 class="admin-card-title">Approved Technicians by City</h3>
                        </div>
                        <div class="admin-card-body" style="padding: 0;">
                            <?php
                            $cityStmt = $pdo->query("
                                SELECT `city`, COUNT(*) as `provider_count`
                                FROM `service_providers`
                                WHERE `verification_status` = 'Approved'
                                GROUP BY `city`
                                ORDER BY `provider_count` DESC
                            ");
                            $cityStats = $cityStmt->fetchAll();
                            ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Operational City</th>
                                        <th>Verified Mitras</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cityStats)): ?>
                                        <tr><td colspan="3" style="text-align: center; color: var(--admin-text-muted);">No approved providers in records yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($cityStats as $cit): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($cit['city']); ?></strong></td>
                                                <td><?= number_format($cit['provider_count']); ?> Active Mitras</td>
                                                <td><span class="badge-status badge-approved">Live Dispatch</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- =============================================================
                 TAB 6: ADMINISTRATOR MANAGEMENT (SUPER ADMIN ONLY)
                 ============================================================= -->
            <?php elseif ($activeTab === 'admins' && $isSuperAdmin): ?>
                <div class="admin-page-header">
                    <div>
                        <h2 class="admin-page-title">Administrator Account Governance</h2>
                        <p class="admin-page-desc">Create administrative accounts, assign roles, activate/deactivate personnel, and execute password resets.</p>
                    </div>
                    <div>
                        <button type="button" class="btn-admin btn-admin-primary" onclick="document.getElementById('createAdminSection').scrollIntoView({behavior: 'smooth'});">
                            <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
                            <span>Add New Administrator</span>
                        </button>
                    </div>
                </div>

                <!-- Admin Accounts Table -->
                <div class="admin-card">
                    <div class="admin-card-head">
                        <h3 class="admin-card-title">
                            <i data-lucide="shield-check" style="width: 18px; height: 18px; color: #7C3AED;"></i>
                            <span>Configured Administrative Users</span>
                        </h3>
                    </div>
                    <div class="admin-card-body" style="padding: 0;">
                        <?php
                        $adminsStmt = $pdo->query("SELECT * FROM `admins` ORDER BY `id` ASC");
                        $adminAccounts = $adminsStmt->fetchAll();
                        ?>
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Admin ID (Username)</th>
                                        <th>Full Name &amp; Email</th>
                                        <th>Current Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Role Management</th>
                                        <th>Account Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adminAccounts as $adm): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: var(--admin-navy); font-size: 0.95rem;"><?= htmlspecialchars($adm['username']); ?></strong>
                                                <div style="font-size: 0.725rem; color: var(--admin-text-muted);">Admin ID: #<?= (int)$adm['id']; ?></div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($adm['full_name'] ?: 'N/A'); ?></div>
                                                <div style="font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($adm['email'] ?: 'No email configured'); ?></div>
                                            </td>
                                            <td>
                                                <?php if ($adm['role'] === 'super_admin'): ?>
                                                    <span class="admin-role-badge role-super-admin" style="font-size: 0.725rem; padding: 0.2rem 0.6rem;">Super Admin</span>
                                                <?php else: ?>
                                                    <span class="admin-role-badge role-admin" style="font-size: 0.725rem; padding: 0.2rem 0.6rem;">Admin</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ((int)$adm['is_active'] === 1): ?>
                                                    <span class="badge-status badge-active">Active</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-deactivated">Deactivated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= !empty($adm['last_login_at']) ? date('d M Y, h:i A', strtotime($adm['last_login_at'])) : '<span style="color: #94A3B8;">Never</span>'; ?>
                                            </td>
                                            <td>
                                                <!-- Change Role Form (Super Admin only) -->
                                                <form method="POST" style="display: flex; gap: 0.35rem;" onsubmit="return confirm('Change role for <?= htmlspecialchars(addslashes($adm['username'])); ?>?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="update_admin_role">
                                                    <input type="hidden" name="target_id" value="<?= (int)$adm['id']; ?>">
                                                    <select name="new_role" class="input-control" style="padding: 0.35rem 0.5rem; font-size: 0.8rem; height: auto;">
                                                        <option value="admin" <?= $adm['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                                                        <option value="super_admin" <?= $adm['role'] === 'super_admin' ? 'selected' : ''; ?>>super_admin</option>
                                                    </select>
                                                    <button type="submit" class="btn-admin btn-admin-outline" style="padding: 0.35rem 0.6rem;" title="Update role">Save</button>
                                                </form>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                                    <!-- Toggle Active/Deactivated Status -->
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to <?= (int)$adm['is_active'] === 1 ? 'deactivate' : 'activate'; ?> account <?= htmlspecialchars(addslashes($adm['username'])); ?>?');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="toggle_admin_status">
                                                        <input type="hidden" name="target_id" value="<?= (int)$adm['id']; ?>">
                                                        <input type="hidden" name="new_status" value="<?= (int)$adm['is_active'] === 1 ? '0' : '1'; ?>">
                                                        <button type="submit" class="btn-admin <?= (int)$adm['is_active'] === 1 ? 'btn-admin-danger' : 'btn-admin-success'; ?>" style="font-size: 0.775rem;">
                                                            <?= (int)$adm['is_active'] === 1 ? 'Deactivate' : 'Activate'; ?>
                                                        </button>
                                                    </form>

                                                    <!-- Reset Password Prompt -->
                                                    <form method="POST" onsubmit="var p = prompt('Enter a new password for <?= htmlspecialchars(addslashes($adm['username'])); ?> (minimum 8 characters):'); if (!p || p.length < 8) { alert('Password must be at least 8 characters.'); return false; } document.getElementById('pwd_reset_<?= $adm['id']; ?>').value = p; return true;">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="reset_admin_password">
                                                        <input type="hidden" name="target_id" value="<?= (int)$adm['id']; ?>">
                                                        <input type="hidden" name="new_password" id="pwd_reset_<?= $adm['id']; ?>" value="">
                                                        <button type="submit" class="btn-admin btn-admin-outline" style="font-size: 0.775rem;" title="Reset password">
                                                            <i data-lucide="key" style="width: 12px; height: 12px;"></i>
                                                            <span>Reset</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Create Administrator Form Section -->
                <div class="admin-card" id="createAdminSection">
                    <div class="admin-card-head">
                        <h3 class="admin-card-title">
                            <i data-lucide="user-plus" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                            <span>Create New Administrator Account</span>
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" style="max-width: 640px;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="create_admin">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Admin ID (Username) *</label>
                                    <input type="text" name="new_username" class="input-control" placeholder="e.g. rahul_admin" required autocomplete="off">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="new_full_name" class="input-control" placeholder="e.g. Rahul Verma" required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="new_email" class="input-control" placeholder="e.g. rahul@kaammitra.in" required>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Initial Password (Min 8 Chars) *</label>
                                    <input type="password" name="new_password" class="input-control" placeholder="Secure temporary password" required minlength="8">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label">Assigned Administrative Role *</label>
                                <select name="new_role" class="input-control" required>
                                    <option value="admin" selected>admin (Restricted Operational Clearance - Default)</option>
                                    <option value="super_admin">super_admin (Full Governance &amp; Account Control)</option>
                                </select>
                                <span style="font-size: 0.775rem; color: var(--admin-text-muted); display: block; margin-top: 0.35rem;">
                                    Standard 'admin' accounts cannot manage other administrators, view audit logs, or access security settings.
                                </span>
                            </div>

                            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 0.65rem 1.5rem;">
                                <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
                                <span>Create Administrator Account</span>
                            </button>
                        </form>
                    </div>
                </div>

            <!-- =============================================================
                 TAB 7: IMMUTABLE SECURITY AUDIT LOGS (SUPER ADMIN ONLY)
                 ============================================================= -->
            <?php elseif ($activeTab === 'audit' && $isSuperAdmin): ?>
                <?php
                $filterAction = trim($_GET['action_filter'] ?? '');
                $sql = "SELECT * FROM `admin_audit_logs`";
                $params = [];
                if (!empty($filterAction)) {
                    $sql .= " WHERE `action` LIKE :act";
                    $params[':act'] = "%{$filterAction}%";
                }
                $sql .= " ORDER BY `id` DESC LIMIT 100";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $auditLogs = $stmt->fetchAll();
                ?>

                <div class="admin-page-header">
                    <div>
                        <h2 class="admin-page-title">Platform Security Audit Log</h2>
                        <p class="admin-page-desc">Tamper-evident chronological audit trail of all sign-ins, provider approvals, role modifications, and administrative decisions.</p>
                    </div>
                    <div>
                        <form method="GET" style="display: flex; gap: 0.5rem;">
                            <input type="hidden" name="tab" value="audit">
                            <input type="text" name="action_filter" value="<?= htmlspecialchars($filterAction); ?>" placeholder="Filter by action..." class="input-control" style="width: 220px; padding: 0.45rem 0.85rem; font-size: 0.85rem;">
                            <button type="submit" class="btn-admin btn-admin-primary">Filter</button>
                            <?php if (!empty($filterAction)): ?>
                                <a href="index.php?tab=audit" class="btn-admin btn-admin-outline">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-head">
                        <h3 class="admin-card-title">
                            <i data-lucide="scroll-text" style="width: 18px; height: 18px; color: #7C3AED;"></i>
                            <span>Immutable Event Trail (Last 100 Events)</span>
                        </h3>
                        <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Audit records cannot be modified or deleted.</span>
                    </div>
                    <div class="admin-card-body" style="padding: 0;">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Log ID</th>
                                        <th>Timestamp</th>
                                        <th>Acting Admin</th>
                                        <th>Action Executed</th>
                                        <th>Target</th>
                                        <th>Event Details</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($auditLogs)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
                                                No audit records recorded yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($auditLogs as $log): ?>
                                            <tr>
                                                <td><span style="font-family: monospace; color: var(--admin-text-muted);">#<?= (int)$log['id']; ?></span></td>
                                                <td style="white-space: nowrap; font-size: 0.8rem;"><?= date('d M Y, H:i:s', strtotime($log['created_at'])); ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($log['admin_username'] ?: 'System / Guest'); ?></strong>
                                                    <?php if (!empty($log['admin_id'])): ?>
                                                        <div style="font-size: 0.725rem; color: var(--admin-text-muted);">ID: <?= (int)$log['admin_id']; ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $act = $log['action'];
                                                    $actColor = '#2563EB';
                                                    if (str_contains($act, 'FAILED') || str_contains($act, 'BLOCKED') || str_contains($act, 'UNAUTHORIZED') || str_contains($act, 'REJECTED')) {
                                                        $actColor = '#DC2626';
                                                    } elseif (str_contains($act, 'SUCCESS') || str_contains($act, 'APPROVED') || str_contains($act, 'ACTIVATED')) {
                                                        $actColor = '#059669';
                                                    } elseif (str_contains($act, 'ROLE') || str_contains($act, 'ADMIN')) {
                                                        $actColor = '#7C3AED';
                                                    }
                                                    ?>
                                                    <span style="font-family: monospace; font-size: 0.8rem; font-weight: 700; color: <?= $actColor; ?>;">
                                                        <?= htmlspecialchars($act); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($log['target_type'])): ?>
                                                        <span class="badge-status" style="background: #F1F5F9; color: #475569; font-size: 0.75rem;">
                                                            <?= htmlspecialchars($log['target_type']); ?> <?= !empty($log['target_id']) ? '#' . htmlspecialchars($log['target_id']) : ''; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color: #94A3B8;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="max-width: 320px; font-size: 0.825rem; color: #334155;">
                                                    <?= htmlspecialchars($log['details'] ?? ''); ?>
                                                </td>
                                                <td>
                                                    <span style="font-family: monospace; font-size: 0.775rem; color: var(--admin-text-muted);"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
