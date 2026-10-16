<?php
/**
 * KaamMitra - Clean Marketplace Footer Component
 * Public footer with city selector modal and streamlined mobile bottom navigation
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config.php';
}

$base = function_exists('getAppBasePath') ? getAppBasePath() : './';

global $AVAILABLE_CITIES;
if (empty($AVAILABLE_CITIES)) {
    $AVAILABLE_CITIES = ['Delhi NCR', 'Mumbai', 'Bengaluru', 'Pune', 'Hyderabad', 'Chennai', 'Kolkata', 'Ahmedabad', 'Jaipur', 'Chandigarh'];
}
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
                <h4 class="footer-heading">Cities Served</h4>
                <ul class="footer-links-list">
                    <?php foreach (array_slice($AVAILABLE_CITIES, 0, 6) as $c): ?>
                        <li><a href="javascript:void(0)" class="trigger-location-modal"><?= htmlspecialchars($c); ?></a></li>
                    <?php endforeach; ?>
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

<!-- Location Selector Modal -->
<div id="locationModalOverlay" class="modal-overlay" role="dialog" aria-modal="true" aria-label="Select City">
    <div class="modal-container" style="max-width: 540px;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <i data-lucide="map-pin" style="color: #FF5500; width: 22px; height: 22px;"></i>
                <h3 class="modal-title">Select Your Service City</h3>
            </div>
            <button id="closeLocationModalBtn" class="modal-close-btn" aria-label="Close location modal">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 1.25rem;">
                KaamMitra operates dedicated rapid-dispatch hubs across major Indian metros with 30-minute doorstep arrival.
            </p>
            
            <div class="location-search-wrap" style="position: relative; margin-bottom: 1.25rem;">
                <i data-lucide="search" style="width: 17px; height: 17px; color: #94A3B8; position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);"></i>
                <input type="text" id="citySearchInput" class="form-control" style="padding-left: 2.75rem;" placeholder="Search your city name..." autocomplete="off">
            </div>

            <div class="cities-grid" id="citiesGrid">
                <?php foreach ($AVAILABLE_CITIES as $city): ?>
                    <button type="button" class="city-select-btn <?= $city === 'Mumbai' ? 'active' : ''; ?>" data-city="<?= htmlspecialchars($city); ?>">
                        <div class="city-select-info">
                            <span class="city-name"><?= htmlspecialchars($city); ?></span>
                            <span class="city-badge">⚡ 30m Dispatch</span>
                        </div>
                        <i data-lucide="check" class="city-check-icon" style="width: 18px; height: 18px;"></i>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

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
