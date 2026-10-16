<?php
/**
 * KaamMitra - Reusable Floating Island Main Navbar Component
 * Supports public visitors, customer authentication states, and mobile drawer
 */

if (!function_exists('getAppBasePath')) {
    require_once __DIR__ . '/../includes/auth.php';
}

$base = getAppBasePath();
$isLoggedIn = function_exists('isCustomerLoggedIn') ? isCustomerLoggedIn() : false;
$customer = $isLoggedIn ? getLoggedInCustomer() : null;

$isPartnerLogged = function_exists('isPartnerLoggedIn') ? isPartnerLoggedIn() : false;
$partnerUser = $isPartnerLogged ? getLoggedInPartner() : null;
?>
<!-- Floating Island Navbar -->
<div class="header-wrapper">
    <header class="site-navbar" id="siteHeader">
        <!-- Brand -->
        <div class="nav-brand-group">
            <a href="<?= $base; ?>index.php" class="brand-link" aria-label="KaamMitra Home">
                <div class="brand-mark">
                    <i data-lucide="wrench" style="width: 20px; height: 20px;"></i>
                </div>
                <div class="brand-text-block">
                    <span class="brand-name">Kaam<span>Mitra</span></span>
                    <span class="brand-domain-tag">kaammitra.in</span>
                </div>
            </a>

            <!-- Location Selector Pill -->
            <button type="button" class="nav-location-pill trigger-location-modal" id="navLocationBtn" aria-label="Choose service city">
                <span class="location-live-dot"></span>
                <span id="navCityText">Mumbai, MH</span>
                <i data-lucide="chevron-down" style="width: 14px; height: 14px;"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="nav-desktop-menu">
            <ul class="nav-menu-links">
                <li><a href="<?= $base; ?>index.php#services">Services</a></li>
                <li><a href="<?= $base; ?>index.php#trending">Trending</a></li>
                <li><a href="<?= $base; ?>index.php#pledge">Rate Card</a></li>
                <li><a href="<?= $base; ?>index.php#standard">Why Us</a></li>
                <li><a href="<?= $base; ?>index.php#howItWorks">How It Works</a></li>
                <li><a href="<?= $base; ?>index.php#reviews">Reviews</a></li>
            </ul>
        </nav>

        <!-- Actions -->
        <div class="nav-actions">
            <?php if ($isPartnerLogged && $partnerUser): ?>
                <a href="<?= $base; ?>partner/dashboard.php" class="btn btn-outline btn-sm nav-partner-btn" style="text-decoration: none;" title="Partner Dashboard">
                    <i data-lucide="briefcase" style="width: 14px; height: 14px; color: #10B981;"></i>
                    <span>Mitra: <?= htmlspecialchars(explode(' ', $partnerUser['full_name'])[0]); ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $base; ?>partner/login.php" class="btn btn-outline btn-sm nav-partner-btn" style="text-decoration: none;" title="Partner Portal">
                    <i data-lucide="users" style="width: 14px; height: 14px;"></i>
                    <span>For Partners</span>
                </a>
            <?php endif; ?>

            <?php if ($isLoggedIn && $customer): ?>
                <a href="<?= $base; ?>customer/dashboard.php" class="btn btn-outline btn-sm nav-user-pill" title="Customer Dashboard" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;">
                    <i data-lucide="user-check" style="width: 15px; height: 15px; color: #10B981;"></i>
                    <span>Hi, <?= htmlspecialchars(explode(' ', $customer['full_name'])[0]); ?></span>
                </a>
                <a href="<?= $base; ?>customer/book.php" class="btn btn-primary btn-sm nav-book-btn" style="text-decoration: none;">
                    <span class="btn-text-full">Book a Service</span>
                    <span class="btn-text-short">Book</span>
                </a>
                <a href="<?= $base; ?>customer/logout.php" class="btn btn-ghost btn-sm" title="Log Out" style="color: var(--text-muted); padding: 0.4rem 0.5rem; display: inline-flex; align-items: center;">
                    <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                </a>
            <?php else: ?>
                <a href="<?= $base; ?>customer/book.php" class="btn btn-primary btn-sm nav-book-btn" style="text-decoration: none;">
                    <span class="btn-text-full">Book a Service</span>
                    <span class="btn-text-short">Book</span>
                </a>
            <?php endif; ?>

            <a href="tel:<?= defined('APP_PHONE') ? APP_PHONE : '1800-522-6648'; ?>" class="mobile-call-action-btn" title="Call Mitra Emergency Helpline" aria-label="Call emergency support">
                <i data-lucide="phone" style="width: 18px; height: 18px;"></i>
            </a>

            <button id="mobileMenuBtn" class="mobile-hamburger" aria-label="Open mobile menu">
                <i data-lucide="menu" style="width: 22px; height: 22px;"></i>
            </button>
        </div>
    </header>
</div>

<!-- Mobile Navigation Drawer -->
<div id="mobileDrawerOverlay" class="modal-overlay" role="dialog" aria-modal="true" aria-label="Mobile Navigation Menu">
    <div class="modal-container" style="max-width: 360px; margin-left: auto; height: 100vh; border-radius: 0;">
        <div class="modal-header">
            <div class="brand-link">
                <div class="brand-mark">
                    <i data-lucide="wrench" style="width: 18px; height: 18px;"></i>
                </div>
                <span class="brand-name">Kaam<span>Mitra</span></span>
            </div>
            <button id="closeMobileDrawerBtn" class="modal-close-btn" aria-label="Close menu">&times;</button>
        </div>
        
        <div class="modal-body" style="padding: 1.5rem;">
            <button type="button" class="nav-location-pill trigger-location-modal" style="width: 100%; justify-content: center; margin-bottom: 1.5rem;">
                <span class="location-live-dot"></span>
                <span id="navMobileCityText">Mumbai, MH</span>
                <i data-lucide="chevron-down" style="width: 14px; height: 14px;"></i>
            </button>

            <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
                <?php if ($isLoggedIn && $customer): ?>
                    <div style="background: #F1F5F9; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 0.5rem;">
                        <div style="font-size: 0.75rem; color: #64748B; text-transform: uppercase; font-weight: 700;">Logged in Customer</div>
                        <div style="font-size: 1.05rem; font-weight: 800; color: #0A1325;"><?= htmlspecialchars($customer['full_name']); ?></div>
                        <div style="font-size: 0.8rem; color: #10B981; font-weight: 600;">+91 <?= htmlspecialchars($customer['mobile']); ?></div>
                    </div>
                    <a href="<?= $base; ?>customer/dashboard.php" class="mobile-nav-link" style="font-size: 1.05rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <i data-lucide="layout-dashboard" style="width: 18px; height: 18px; color: #FF6600;"></i>
                        <span>My Customer Dashboard</span>
                    </a>
                    <a href="<?= $base; ?>customer/logout.php" class="mobile-nav-link" style="font-size: 0.95rem; font-weight: 600; color: #EF4444; display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                        <span>Sign Out</span>
                    </a>
                <?php else: ?>
                    <a href="<?= $base; ?>customer/login.php" class="mobile-nav-link" style="font-size: 1.05rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <i data-lucide="user" style="width: 18px; height: 18px; color: #FF6600;"></i>
                        <span>Customer Login / Register</span>
                    </a>
                <?php endif; ?>

                <hr style="border: 0; border-top: 1px solid #E2E8F0; margin: 0.5rem 0;">

                <a href="<?= $base; ?>partner/login.php" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <i data-lucide="briefcase" style="width: 18px; height: 18px; color: #FF6600;"></i>
                    <span>Partner Hub (Earn with Us)</span>
                </a>
                <a href="<?= $base; ?>admin/index.php" class="mobile-nav-link" style="font-size: 0.9rem; font-weight: 600; color: #64748B; display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <i data-lucide="shield" style="width: 16px; height: 16px;"></i>
                    <span>Admin Console</span>
                </a>
            </div>

            <div style="border-top: 1px solid #E2E8F0; padding-top: 1.5rem;">
                <a href="<?= $base; ?>customer/book.php" class="btn btn-primary" style="width: 100%; justify-content: center; margin-bottom: 0.75rem; text-decoration: none;">Book a Service</a>
                <a href="tel:<?= defined('APP_PHONE') ? APP_PHONE : '1800-522-6648'; ?>" class="btn btn-outline" style="width: 100%; justify-content: center; text-decoration: none;">
                    <i data-lucide="phone" style="width: 16px; height: 16px;"></i>
                    <span>Call <?= defined('APP_PHONE_DISPLAY') ? APP_PHONE_DISPLAY : '1800-KAAM-MITRA'; ?></span>
                </a>
            </div>
        </div>
    </div>
</div>
