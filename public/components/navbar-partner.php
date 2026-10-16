<?php
/**
 * KaamMitra - Reusable Partner Portal Navbar Component
 * Parameters: $isDashboard (bool), $partner (array|null)
 */

if (!function_exists('getAppBasePath')) {
    require_once __DIR__ . '/../includes/auth.php';
}

$base = getAppBasePath();
$isDashboard = $isDashboard ?? false;
$partner = $partner ?? (function_exists('getLoggedInPartner') ? getLoggedInPartner() : null);
?>
<?php if ($isDashboard && $partner): ?>
    <!-- Dark Navy Dashboard Header -->
    <header class="dashboard-header" style="background: #0A1325; color: #FFFFFF; padding: 1rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
        <div class="dash-nav-container" style="max-width: 1200px; margin: 0 auto; padding: 0 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="<?= $base; ?>index.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: inherit;">
                    <div style="background: linear-gradient(135deg, #FF6600, #FF8533); width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: 800; font-size: 1.1rem;">
                        K
                    </div>
                    <div>
                        <span style="font-size: 1.15rem; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em;">Kaam<span style="color: #FF6600;">Mitra</span></span>
                        <span style="font-size: 0.68rem; font-weight: 700; color: rgba(255, 255, 255, 0.6); display: block; line-height: 1; text-transform: uppercase; letter-spacing: 0.05em;">Partner Desk</span>
                    </div>
                </a>
                <span style="background: rgba(255, 255, 255, 0.1); width: 1px; height: 24px; display: inline-block;"></span>
                <span style="font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.85); display: flex; align-items: center; gap: 0.35rem;">
                    <i data-lucide="map-pin" style="width: 14px; height: 14px; color: #FF6600;"></i>
                    <?= htmlspecialchars($partner['city']); ?> Hub
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255, 255, 255, 0.08); padding: 0.35rem 0.75rem; border-radius: 100px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #10B981; display: inline-block;"></span>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #FFFFFF;">Online &bull; Available</span>
                </div>
                <a href="<?= $base; ?>partner/logout.php" class="btn btn-outline btn-sm" style="color: #FFFFFF; border-color: rgba(255, 255, 255, 0.25); text-decoration: none;">
                    <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </div>
    </header>
<?php else: ?>
    <!-- Clean Auth Screen Header -->
    <header style="background: #FFFFFF; border-bottom: 1px solid var(--border-subtle); padding: 0.85rem 1.25rem;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;">
            <a href="<?= $base; ?>index.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: inherit;">
                <div style="background: linear-gradient(135deg, #FF6600, #FF8533); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: 800; font-size: 1.15rem; box-shadow: 0 4px 12px rgba(255, 102, 0, 0.25);">
                    K
                </div>
                <div>
                    <span style="font-size: 1.15rem; font-weight: 800; color: #0A1325; letter-spacing: -0.02em;">Kaam<span style="color: #FF6600;">Mitra</span></span>
                    <span style="font-size: 0.7rem; font-weight: 700; color: #64748B; display: block; line-height: 1; text-transform: uppercase; letter-spacing: 0.05em;">Partner Desk</span>
                </div>
            </a>
            
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <a href="<?= $base; ?>customer/login.php" style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 0.35rem;">
                    <i data-lucide="user" style="width: 14px; height: 14px;"></i>
                    <span>Customer Sign In</span>
                </a>
                <a href="<?= $base; ?>index.php" style="font-size: 0.85rem; font-weight: 600; color: var(--navy-900); text-decoration: none; display: flex; align-items: center; gap: 0.35rem;">
                    <i data-lucide="home" style="width: 14px; height: 14px;"></i>
                    <span>Back to Home</span>
                </a>
            </div>
        </div>
    </header>
<?php endif; ?>
