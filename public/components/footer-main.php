<?php
/**
 * KaamMitra - Clean Marketplace Footer Component
 * Public footer with city selector modal and streamlined mobile bottom navigation
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config.php';
}

$base = function_exists('getAppBasePath') ? getAppBasePath() : './';

?>
<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-top-grid">
            <div>
                <h3 class="footer-brand-title">Kaam<span>Mitra</span></h3>
                <p class="footer-desc">
                    India's trusted on-demand home maintenance marketplace. We connect residential homeowners with background-verified technicians delivering guaranteed work within 30 minutes.
                </p>
                <div style="display: flex; align-items: center; gap: 0.75rem; color: #FFFFFF; font-weight: 700; font-size: 0.95rem;">
                    <i data-lucide="phone" style="width: 18px; height: 18px; color: var(--brand-coral, #FF5500);"></i>
                    <span>Helpline: <?= defined('APP_PHONE_DISPLAY') ? APP_PHONE_DISPLAY : '1800-KAAM-MITRA'; ?></span>
                </div>
            </div>

            <div>
                <h4 class="footer-heading">Services</h4>
                <ul class="footer-links-list">
                    <li><a href="<?= $base; ?>index.php#services">Plumbing &amp; Leakage</a></li>
                    <li><a href="<?= $base; ?>index.php#services">Electrical Repairs</a></li>
                    <li><a href="<?= $base; ?>index.php#services">AC &amp; Appliance Service</a></li>
                    <li><a href="<?= $base; ?>index.php#services">Home Deep Cleaning</a></li>
                    <li><a href="<?= $base; ?>index.php#services">Carpentry &amp; Woodwork</a></li>
                    <li><a href="<?= $base; ?>index.php#services">Waterproofing &amp; Paint</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-heading">Company</h4>
                <ul class="footer-links-list">
                    <li><a href="<?= $base; ?>index.php#standard">The KaamMitra Standard</a></li>
                    <li><a href="<?= $base; ?>index.php#pledge">Transparent Pricing</a></li>
                    <li><a href="<?= $base; ?>index.php#howItWorks">How It Works</a></li>
                    <li><a href="<?= $base; ?>index.php#reviews">Verified Reviews</a></li>
                    <li><a href="<?= $base; ?>index.php#faq">Frequently Asked Questions</a></li>
                    <li><a href="<?= $base; ?>index.php#contact">24/7 Helpline Support</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-heading">Coverage</h4>
                <ul class="footer-links-list">
                    <li><span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">Real-Time GPS Dispatch</span></li>
                    <li><span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">Live Provider Availability</span></li>
                    <li><span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">30-Minute Doorstep Reach</span></li>
                    <li><span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">Verified Background Checks</span></li>
                    <li><span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">30-Day Work Warranty</span></li>
                    <li><span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">24/7 Rapid Emergency Response</span></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom-row">
            <div>&copy; <?= date('Y'); ?> KaamMitra Technologies Private Limited. All rights reserved.</div>
            <div style="display: flex; gap: 1.5rem;">
                <a href="javascript:void(0)" style="color: rgba(255,255,255,0.6); text-decoration: none;">Privacy Policy</a>
                <a href="javascript:void(0)" style="color: rgba(255,255,255,0.6); text-decoration: none;">Terms of Service</a>
                <a href="javascript:void(0)" style="color: rgba(255,255,255,0.6); text-decoration: none;">Security &amp; Safety</a>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile App-like Bottom Navigation -->
<nav class="mobile-app-bottom-bar" id="mobileAppBottomBar" aria-label="Mobile Navigation">
    <a href="<?= $base; ?>index.php#hero" class="app-tab active" data-tab="home" id="tabHome">
        <i data-lucide="home"></i>
        <span>Home</span>
    </a>
    <a href="<?= $base; ?>index.php#services" class="app-tab" data-tab="services" id="tabServices">
        <i data-lucide="layout-grid"></i>
        <span>Services</span>
    </a>
    <a href="<?= $base; ?>index.php#pledge" class="app-tab app-tab-highlight" data-tab="pledge" id="tabPledge" aria-label="Rate Card">
        <div class="tab-highlight-circle">
            <i data-lucide="receipt"></i>
        </div>
        <span>Rate Card</span>
    </a>
    <a href="<?= $base; ?>index.php#reviews" class="app-tab" data-tab="reviews" id="tabReviews" aria-label="Reviews">
        <i data-lucide="star"></i>
        <span>Reviews</span>
    </a>
    <a href="tel:<?= defined('APP_PHONE') ? APP_PHONE : '1800-522-6648'; ?>" class="app-tab" data-tab="contact" id="tabContact" aria-label="24/7 Helpline">
        <i data-lucide="phone"></i>
        <span>Helpline</span>
    </a>
</nav>
