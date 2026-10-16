<?php
/**
 * KaamMitra - Reusable Admin Console Navbar Component
 * Parameters: $admin (array), $activeTab (string), $totalCustomers (int), $totalProviders (int)
 */

if (!function_exists('getAppBasePath')) {
    require_once __DIR__ . '/../includes/auth.php';
}

$base = getAppBasePath();
$admin = $admin ?? (function_exists('getLoggedInAdmin') ? getLoggedInAdmin() : ['name' => 'Charan']);
$activeTab = $activeTab ?? 'customers';
$totalCustomers = $totalCustomers ?? 0;
$totalProviders = $totalProviders ?? 0;
?>
<header class="admin-top-nav">
    <div class="admin-top-container">
        <!-- Brand Area -->
        <div class="brand-area">
            <div class="admin-logo-mark">
                <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
            </div>
            <div class="brand-title-wrap">
                <div class="brand-title">Kaam<span>Mitra</span></div>
                <div class="brand-tag">Ops Console &bull; Central HQ</div>
            </div>
        </div>

        <!-- Tab Navigation Links -->
        <nav class="nav-links-center" aria-label="Admin Sections">
            <a href="<?= $base; ?>admin/dashboard.php?tab=customers" class="nav-tab-btn <?= $activeTab === 'customers' ? 'active' : ''; ?>" id="tabLinkCustomers" onclick="if (typeof switchSectionTab === 'function') { switchSectionTab('customers', event); }">
                <i data-lucide="users" style="width: 17px; height: 17px;"></i>
                <span>Customers</span>
                <span class="nav-tab-badge" id="badgeCustomersCount"><?= $totalCustomers; ?></span>
            </a>
            <a href="<?= $base; ?>admin/dashboard.php?tab=providers" class="nav-tab-btn <?= $activeTab === 'providers' ? 'active' : ''; ?>" id="tabLinkProviders" onclick="if (typeof switchSectionTab === 'function') { switchSectionTab('providers', event); }">
                <i data-lucide="wrench" style="width: 17px; height: 17px;"></i>
                <span>Service Providers</span>
                <span class="nav-tab-badge" id="badgeProvidersCount"><?= $totalProviders; ?></span>
            </a>
        </nav>

        <!-- Admin User Badge & Actions -->
        <div class="admin-user-group">
            <div class="admin-profile-pill" title="Authenticated Super Admin">
                <div class="admin-avatar"><?= strtoupper(substr($admin['name'] ?? 'C', 0, 1)); ?></div>
                <div>
                    <div class="admin-name-text"><?= htmlspecialchars($admin['name'] ?? 'Admin'); ?></div>
                    <div class="admin-role-badge">Super Admin</div>
                </div>
            </div>

            <a href="<?= $base; ?>index.php" target="_blank" class="btn-top-action btn-view-site" title="Open Public Website in new tab">
                <i data-lucide="external-link" style="width: 15px; height: 15px;"></i>
                <span>Public Site</span>
            </a>

            <a href="<?= $base; ?>admin/logout.php" class="btn-top-action btn-admin-logout" title="Sign out of Admin Session">
                <i data-lucide="log-out" style="width: 15px; height: 15px;"></i>
                <span>Log Out</span>
            </a>
        </div>
    </div>
</header>
