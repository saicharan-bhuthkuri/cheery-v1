<?php
/**
 * KaamMitra - Clean Marketplace Navbar Component
 * Streamlined public navigation focused on services, pricing, and contact
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config.php';
}

$base = function_exists('getAppBasePath') ? getAppBasePath() : './';
?>
<!-- Floating Island Navbar -->
<div class="header-wrapper">
    <header class="site-navbar" id="siteHeader">
        <!-- Brand & Real-Time Location -->
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

            <!-- Compact Real-Time Location Indicator beside logo -->
            <div class="nav-location-indicator" id="navLocationIndicator" style="display: none;" aria-live="polite">
                <span class="nav-location-sep" aria-hidden="true"></span>
                <div class="nav-location-tag">
                    <i data-lucide="map-pin" class="nav-location-pin-icon" aria-hidden="true"></i>
                    <span class="nav-location-name" id="navLocationName"></span>
                </div>
            </div>
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
                <li><a href="<?= $base; ?>index.php#faq">FAQs</a></li>
                <li><a href="<?= $base; ?>index.php#contact">Contact</a></li>
            </ul>
        </nav>

        <!-- Actions -->
        <div class="nav-actions">
            <a href="<?= $base; ?>index.php#services" class="btn btn-primary btn-sm nav-book-btn" style="text-decoration: none;">
                <span class="btn-text-full">Explore Services</span>
                <span class="btn-text-short">Services</span>
            </a>

            <a href="tel:<?= defined('APP_PHONE') ? APP_PHONE : '1800-522-6648'; ?>" class="mobile-call-action-btn" title="Call Mitra 24/7 Helpline: <?= defined('APP_PHONE_DISPLAY') ? APP_PHONE_DISPLAY : '1800-KAAM-MITRA'; ?>" aria-label="Call emergency support">
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


            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 2rem;">
                <a href="<?= $base; ?>index.php#services" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                    <i data-lucide="layout-grid" style="width: 18px; height: 18px; color: #FF5500;"></i>
                    <span>All Services Catalog</span>
                </a>
                <a href="<?= $base; ?>index.php#trending" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                    <i data-lucide="flame" style="width: 18px; height: 18px; color: #FF5500;"></i>
                    <span>Trending Doorstep Repairs</span>
                </a>
                <a href="<?= $base; ?>index.php#pledge" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                    <i data-lucide="receipt" style="width: 18px; height: 18px; color: #FF5500;"></i>
                    <span>Fixed ₹149 Rate Card</span>
                </a>
                <a href="<?= $base; ?>index.php#standard" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                    <i data-lucide="shield-check" style="width: 18px; height: 18px; color: #FF5500;"></i>
                    <span>The KaamMitra Standard</span>
                </a>
                <a href="<?= $base; ?>index.php#howItWorks" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                    <i data-lucide="workflow" style="width: 18px; height: 18px; color: #FF5500;"></i>
                    <span>How It Works</span>
                </a>
                <a href="<?= $base; ?>index.php#reviews" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                    <i data-lucide="star" style="width: 18px; height: 18px; color: #FF5500;"></i>
                    <span>Verified Homeowner Reviews</span>
                </a>
                <a href="<?= $base; ?>index.php#faq" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                    <i data-lucide="help-circle" style="width: 18px; height: 18px; color: #FF5500;"></i>
                    <span>Frequently Asked Questions</span>
                </a>
                <a href="<?= $base; ?>index.php#contact" class="mobile-nav-link" style="font-size: 1rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                    <i data-lucide="phone-call" style="width: 18px; height: 18px; color: #FF5500;"></i>
                    <span>Contact Helpline (24/7)</span>
                </a>
            </div>

            <div style="border-top: 1px solid #E2E8F0; padding-top: 1.5rem;">
                <a href="<?= $base; ?>index.php#services" class="btn btn-primary" style="width: 100%; justify-content: center; margin-bottom: 0.75rem; text-decoration: none;">
                    <span>Explore All Services</span>
                </a>
                <a href="tel:<?= defined('APP_PHONE') ? APP_PHONE : '1800-522-6648'; ?>" class="btn btn-outline" style="width: 100%; justify-content: center; text-decoration: none;">
                    <i data-lucide="phone" style="width: 16px; height: 16px;"></i>
                    <span>Call <?= defined('APP_PHONE_DISPLAY') ? APP_PHONE_DISPLAY : '1800-KAAM-MITRA'; ?></span>
                </a>
            </div>
        </div>
    </div>
</div>
