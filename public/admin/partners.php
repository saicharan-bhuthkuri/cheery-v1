<?php
/**
 * KaamMitra - Admin Partner Verification & Moderation Console
 * Allows KaamMitra hub operations team to review, approve, or reject partner applications
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/icons.php';
require_once __DIR__ . '/../components/alert.php';

$base = getAppBasePath();
$admin = requireAdminAuth('admin/partners.php');
$pdo = getDBConnection();

$actionMsg = '';
$actionType = '';

// Handle Partner Verification Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $actionMsg = 'CSRF validation failed.';
        $actionType = 'error';
    } else {
        $partnerId = (int)($_POST['partner_id'] ?? 0);
        $action = trim($_POST['action'] ?? '');
        $rejectionReason = trim($_POST['rejection_reason'] ?? 'Trade certificate or background verification requirements were not met.');

        if ($partnerId > 0) {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE service_providers SET verification_status = 'Approved', rejection_reason = NULL WHERE id = ?");
                $stmt->execute([$partnerId]);
                $actionMsg = "Partner ID #{$partnerId} approved successfully! The partner can now log in and accept bookings.";
                $actionType = 'success';
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE service_providers SET verification_status = 'Rejected', rejection_reason = ? WHERE id = ?");
                $stmt->execute([$rejectionReason, $partnerId]);
                $actionMsg = "Partner ID #{$partnerId} has been marked as Rejected.";
                $actionType = 'info';
            } elseif ($action === 'reset_pending') {
                $stmt = $pdo->prepare("UPDATE service_providers SET verification_status = 'Pending', rejection_reason = NULL WHERE id = ?");
                $stmt->execute([$partnerId]);
                $actionMsg = "Partner ID #{$partnerId} status reset to Pending.";
                $actionType = 'info';
            }
        }
    }
}

// Filter partners
$filter = trim($_GET['status'] ?? 'All');
if (in_array($filter, ['Pending', 'Approved', 'Rejected'], true)) {
    $stmt = $pdo->prepare("SELECT * FROM service_providers WHERE verification_status = ? ORDER BY id DESC");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query("SELECT * FROM service_providers ORDER BY id DESC");
}
$partners = $stmt->fetchAll();

// Counts for quick tabs
$countPending = (int)$pdo->query("SELECT COUNT(*) FROM service_providers WHERE verification_status = 'Pending'")->fetchColumn();
$countApproved = (int)$pdo->query("SELECT COUNT(*) FROM service_providers WHERE verification_status = 'Approved'")->fetchColumn();
$countRejected = (int)$pdo->query("SELECT COUNT(*) FROM service_providers WHERE verification_status = 'Rejected'")->fetchColumn();
$totalProviders = $countPending + $countApproved + $countRejected;
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

$csrfToken = getCsrfToken();
$pageTitle = 'Admin Partner Verification Console - ' . APP_NAME;
$extraHead = '<style>
    body { background-color: #F1F5F9; min-height: 100vh; font-family: var(--font-body); color: #0F172A; display: flex; flex-direction: column; }
    .admin-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1.25rem 4rem; flex: 1; width: 100%; box-sizing: border-box; }
    .filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .filter-tab { padding: 0.5rem 1rem; border-radius: 10px; text-decoration: none; font-size: 0.85rem; font-weight: 700; background: #FFFFFF; color: #475569; border: 1px solid #E2E8F0; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.4rem; }
    .filter-tab.active { background: #FF6600; color: #FFFFFF; border-color: #FF6600; }
    .partner-table-card { background: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 4px 12px rgba(10, 19, 37, 0.04); overflow: hidden; }
    .table-responsive { width: 100%; overflow-x: auto; }
    table.admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
    table.admin-table th { background: #F8FAFC; padding: 0.85rem 1rem; font-size: 0.78rem; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #E2E8F0; }
    table.admin-table td { padding: 1rem; border-bottom: 1px solid #F1F5F9; vertical-align: middle; }
    .status-pill { display: inline-block; font-size: 0.75rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 100px; text-transform: uppercase; }
    .status-pill-pending { background: #FEF3C7; color: #B45309; }
    .status-pill-approved { background: #D1FAE5; color: #065F46; }
    .status-pill-rejected { background: #FEE2E2; color: #991B1B; }
    .btn-action-approve { background: #10B981; color: #FFFFFF; border: none; padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem; }
    .btn-action-approve:hover { background: #059669; }
    .btn-action-reject { background: #EF4444; color: #FFFFFF; border: none; padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem; }
    .btn-action-reject:hover { background: #DC2626; }
</style>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../components/head.php'; ?>
</head>
<body>
<?php
$activeTab = 'providers';
require __DIR__ . '/../components/navbar-admin.php';
?>

<main class="admin-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; margin: 0 0 0.25rem; color: #0A1325;">
                Partner Verification Desk
            </h1>
            <p style="color: #64748B; font-size: 0.9rem; margin: 0;">
                Verify new partner onboarding submissions, inspect trade details, and manage portal authorizations
            </p>
        </div>
        
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="<?= $base; ?>admin/dashboard.php?tab=providers" class="btn btn-outline btn-sm" style="text-decoration: none;">
                <i data-lucide="layout-dashboard" style="width: 15px; height: 15px;"></i>
                <span>Operations Dashboard</span>
            </a>
            <a href="<?= $base; ?>partner/register.php" class="btn btn-primary btn-sm" style="text-decoration: none;">
                <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i>
                <span>Register New Test Partner</span>
            </a>
        </div>
    </div>

    <?php if (!empty($actionMsg)) echo renderAlert($actionMsg, $actionType); ?>

    <!-- Status Filter Tabs -->
    <div class="filter-tabs">
        <a href="partners.php?status=All" class="filter-tab <?= $filter === 'All' ? 'active' : ''; ?>">
            <span>All Partners</span>
            <span style="background: rgba(0,0,0,0.08); padding: 0.1rem 0.4rem; border-radius: 100px; font-size: 0.72rem;"><?= $totalProviders; ?></span>
        </a>
        <a href="partners.php?status=Pending" class="filter-tab <?= $filter === 'Pending' ? 'active' : ''; ?>">
            <span>Pending Review</span>
            <span style="background: #FEF3C7; color: #B45309; padding: 0.1rem 0.4rem; border-radius: 100px; font-size: 0.72rem;"><?= $countPending; ?></span>
        </a>
        <a href="partners.php?status=Approved" class="filter-tab <?= $filter === 'Approved' ? 'active' : ''; ?>">
            <span>Approved Mitras</span>
            <span style="background: #D1FAE5; color: #065F46; padding: 0.1rem 0.4rem; border-radius: 100px; font-size: 0.72rem;"><?= $countApproved; ?></span>
        </a>
        <a href="partners.php?status=Rejected" class="filter-tab <?= $filter === 'Rejected' ? 'active' : ''; ?>">
            <span>Rejected</span>
            <span style="background: #FEE2E2; color: #991B1B; padding: 0.1rem 0.4rem; border-radius: 100px; font-size: 0.72rem;"><?= $countRejected; ?></span>
        </a>
    </div>

    <!-- Partners Table -->
    <div class="partner-table-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ref ID / Mitra</th>
                        <th>Trade / Skill</th>
                        <th>Experience</th>
                        <th>Location &amp; Address</th>
                        <th>Contact Info</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($partners)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: #64748B;">
                                <i data-lucide="users" style="width: 32px; height: 32px; stroke-width: 1.5; color: #94A3B8; margin-bottom: 0.5rem;"></i>
                                <div>No service providers found for the selected filter.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($partners as $p): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #0A1325; font-size: 0.95rem;">
                                        <?= htmlspecialchars($p['full_name']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748B; font-family: monospace;">
                                        KM-PRT-<?= str_pad((string)$p['id'], 5, '0', STR_PAD_LEFT); ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #FF6600;">
                                        <?= htmlspecialchars($p['primary_skill']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color: #334155;">
                                        <?= htmlspecialchars($p['experience_years']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #0F172A;"><?= htmlspecialchars($p['city']); ?></div>
                                    <div style="font-size: 0.78rem; color: #64748B; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($p['address']); ?>">
                                        <?= htmlspecialchars($p['address']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($p['mobile']); ?></div>
                                    <div style="font-size: 0.78rem; color: #64748B;"><?= htmlspecialchars($p['email']); ?></div>
                                </td>
                                <td>
                                    <?php if ($p['verification_status'] === 'Approved'): ?>
                                        <span class="status-pill status-pill-approved">Approved</span>
                                    <?php elseif ($p['verification_status'] === 'Pending'): ?>
                                        <span class="status-pill status-pill-pending">Pending</span>
                                    <?php else: ?>
                                        <span class="status-pill status-pill-rejected" title="<?= htmlspecialchars($p['rejection_reason'] ?? ''); ?>">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                        <?php if ($p['verification_status'] !== 'Approved'): ?>
                                            <form action="partners.php?status=<?= urlencode($filter); ?>" method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="partner_id" value="<?= (int)$p['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn-action-approve" title="Approve and grant partner dashboard access">
                                                    <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                                    <span>Approve</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($p['verification_status'] !== 'Rejected'): ?>
                                            <form action="partners.php?status=<?= urlencode($filter); ?>" method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="partner_id" value="<?= (int)$p['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-action-reject" title="Reject partner application">
                                                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                                    <span>Reject</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($p['verification_status'] !== 'Pending'): ?>
                                            <form action="partners.php?status=<?= urlencode($filter); ?>" method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="partner_id" value="<?= (int)$p['id']; ?>">
                                                <input type="hidden" name="action" value="reset_pending">
                                                <button type="submit" style="background: #E2E8F0; color: #475569; border: none; padding: 0.4rem 0.6rem; border-radius: 8px; font-size: 0.75rem; font-weight: 600; cursor: pointer;">
                                                    Reset
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

</main>

<?php
$portalName = 'Admin Compliance & Verification Console';
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
