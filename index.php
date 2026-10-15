<?php
/**
 * KaamMitra Next - Modern Marketplace Landing Page
 * Completely redesigned visual architecture & app-first experience
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/data/services.php';
require_once __DIR__ . '/data/features.php';
require_once __DIR__ . '/data/testimonials.php';
require_once __DIR__ . '/data/faqs.php';

$isLoggedIn = isCustomerLoggedIn();
$customer = $isLoggedIn ? getLoggedInCustomer() : null;

$isPartnerLogged = isPartnerLoggedIn();
$partnerUser = $isPartnerLogged ? getLoggedInPartner() : null;

/**
 * Renders a crisp Lucide icon element with fallback inline SVG
 */
function renderIcon(string $name, int $size = 20): string {
    $lucideMap = [
        'wrench' => 'wrench',
        'droplet' => 'droplet',
        'zap' => 'zap',
        'cpu' => 'cpu',
        'hammer' => 'hammer',
        'sparkles' => 'sparkles',
        'paint-brush' => 'palette',
        'lock' => 'lock',
        'bolt' => 'zap',
        'check' => 'check',
        'shield-check' => 'shield-check',
        'badge-check' => 'badge-check',
        'star' => 'star',
        'clock' => 'clock',
        'phone' => 'phone',
        'map-pin' => 'map-pin',
        'calendar-clock' => 'calendar',
        'user-check' => 'user-check',
        'tool' => 'wrench',
        'shield' => 'shield',
        'users' => 'users',
        'home' => 'home',
        'receipt' => 'receipt',
        'headset' => 'headphones',
        'wallet' => 'wallet',
        'gift' => 'gift',
        'shirt' => 'shirt',
        'heart-handshake' => 'heart-handshake',
        'search' => 'search',
        'chevron-down' => 'chevron-down'
    ];
    $lucideName = $lucideMap[$name] ?? $name;

    $icons = [
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>',
        'droplet' => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>',
        'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
        'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line>',
        'hammer' => '<path d="M15 12l-8.5 8.5a2.12 2.12 0 1 1-3-3L12 9"></path><path d="M17.64 6.36a3 3 0 0 1 0 4.24l-1.41 1.41-4.24-4.24 1.41-1.41a3 3 0 0 1 4.24 0z"></path><path d="M14 2l4 4"></path>',
        'sparkles' => '<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>',
        'paint-brush' => '<path d="M18.37 2.63 14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3Z"></path><path d="M9 8c-2 3-4 3.5-7 4l8 8c.5-3 1-5 4-7"></path><path d="M14.5 17.5 4.5 15"></path>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        'bolt' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
        'check' => '<polyline points="20 6 9 17 4 12"></polyline>',
        'shield-check' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path>',
        'badge-check' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path><path d="m9 12 2 2 4-4"></path>',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
        'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
        'map-pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
        'calendar-clock' => '<path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"></path><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path><circle cx="17" cy="17" r="5"></circle><polyline points="17 14 17 17 19 19"></polyline>',
        'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline>',
        'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"></polyline>'
    ];

    $content = $icons[$name] ?? '<circle cx="12" cy="12" r="10"></circle>';
    return sprintf(
        '<i data-lucide="%s" class="km-lucide km-icon-%s" style="width: %dpx; height: %dpx; display: inline-flex; align-items: center; justify-content: center;"><svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg></i>',
        htmlspecialchars($lucideName), htmlspecialchars($name), $size, $size, $size, $size, $content
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME); ?> - On-Demand Professional Home Services</title>
    
    <meta name="description" content="KaamMitra delivers background-verified electricians, plumbers, appliance technicians, and cleaning specialists to your doorstep in 30 minutes.">
    <meta name="keywords" content="KaamMitra, home services, plumber, electrician, AC repair, carpentry, deep cleaning, Mumbai, Delhi">
    
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/lucide.min.js"></script>
</head>
<body>

    <!-- =======================================================================
         FLOATING ISLAND NAVBAR (HEADER)
         ======================================================================= -->
    <div class="header-wrapper">
        <header class="site-navbar" id="siteHeader">
            <!-- Brand -->
            <div class="nav-brand-group">
                <a href="index.php" class="brand-link" aria-label="KaamMitra Home">
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
                    <li><a href="#services">Services</a></li>
                    <li><a href="#trending">Trending</a></li>
                    <li><a href="#pledge">Rate Card</a></li>
                    <li><a href="#standard">Why Us</a></li>
                    <li><a href="#howItWorks">How It Works</a></li>
                    <li><a href="#reviews">Reviews</a></li>
                </ul>
            </nav>

            <!-- Actions -->
            <div class="nav-actions">
                <?php if ($isPartnerLogged && $partnerUser): ?>
                    <a href="partner-dashboard.php" class="btn btn-outline btn-sm nav-partner-btn" style="text-decoration: none;" title="Partner Dashboard">
                        <i data-lucide="briefcase" style="width: 14px; height: 14px; color: #10B981;"></i>
                        <span>Mitra: <?= htmlspecialchars(explode(' ', $partnerUser['full_name'])[0]); ?></span>
                    </a>
                <?php else: ?>
                    <a href="partner-login.php" class="btn btn-outline btn-sm nav-partner-btn" style="text-decoration: none;" title="Partner Portal">
                        <i data-lucide="users" style="width: 14px; height: 14px;"></i>
                        <span>For Partners</span>
                    </a>
                <?php endif; ?>
                <?php if ($isLoggedIn && $customer): ?>
                    <a href="dashboard.php" class="btn btn-outline btn-sm nav-user-pill" title="Customer Dashboard" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i data-lucide="user-check" style="width: 15px; height: 15px; color: #10B981;"></i>
                        <span>Hi, <?= htmlspecialchars(explode(' ', $customer['full_name'])[0]); ?></span>
                    </a>
                    <a href="book.php" class="btn btn-primary btn-sm nav-book-btn">
                        <span class="btn-text-full">Book a Service</span>
                        <span class="btn-text-short">Book</span>
                    </a>
                    <a href="logout.php" class="btn btn-ghost btn-sm" title="Log Out" style="color: var(--text-muted); padding: 0.4rem 0.5rem; display: inline-flex; align-items: center;">
                        <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php?redirect=book.php" class="btn btn-primary btn-sm nav-book-btn" style="text-decoration: none;">
                        <span class="btn-text-full">Book a Service</span>
                        <span class="btn-text-short">Book</span>
                    </a>
                <?php endif; ?>
                <a href="tel:<?= APP_PHONE; ?>" class="mobile-call-action-btn" title="Call Mitra Emergency Helpline" aria-label="Call emergency support">
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
                        <a href="dashboard.php" class="mobile-nav-link" style="font-size: 1.05rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="layout-dashboard" style="width: 18px; height: 18px; color: #FF6600;"></i>
                            <span>My Customer Dashboard</span>
                        </a>
                        <a href="logout.php" class="mobile-nav-link" style="font-size: 0.95rem; font-weight: 600; color: #EF4444; display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                            <span>Sign Out</span>
                        </a>
                    <?php endif; ?>

                    <a href="#services" class="mobile-nav-link" style="font-size: 1.1rem; font-weight: 700; color: #090D16;">Services Catalog</a>
                    <a href="#trending" class="mobile-nav-link" style="font-size: 1.1rem; font-weight: 700; color: #090D16;">Trending Bookings</a>
                    <a href="#pledge" class="mobile-nav-link" style="font-size: 1.1rem; font-weight: 700; color: #090D16;">Transparent Visiting Fee</a>
                    <a href="#standard" class="mobile-nav-link" style="font-size: 1.1rem; font-weight: 700; color: #090D16;">The KaamMitra Standard</a>
                    <a href="#howItWorks" class="mobile-nav-link" style="font-size: 1.1rem; font-weight: 700; color: #090D16;">How It Works</a>
                    <a href="#reviews" class="mobile-nav-link" style="font-size: 1.1rem; font-weight: 700; color: #090D16;">Customer Reviews</a>
                    <a href="#faq" class="mobile-nav-link" style="font-size: 1.1rem; font-weight: 700; color: #090D16;">Help &amp; FAQs</a>
                    <?php if ($isPartnerLogged && $partnerUser): ?>
                        <a href="partner-dashboard.php" class="mobile-nav-link" style="font-size: 1.05rem; font-weight: 700; color: #10B981; display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                            <span>Partner Dashboard (<?= htmlspecialchars(explode(' ', $partnerUser['full_name'])[0]); ?>)</span>
                        </a>
                    <?php else: ?>
                        <a href="partner-login.php" class="mobile-nav-link" style="font-size: 1.05rem; font-weight: 700; color: #090D16; display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="log-in" style="width: 18px; height: 18px; color: #FF6600;"></i>
                            <span>Partner Login</span>
                        </a>
                        <a href="partner-register.php" class="mobile-nav-link" style="font-size: 1.05rem; font-weight: 700; color: #FF5500; display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
                            <span>Become a Partner (₹48k/mo)</span>
                        </a>
                    <?php endif; ?>
                </div>

                <a href="<?= $isLoggedIn ? 'book.php' : 'login.php?redirect=book.php'; ?>" class="btn btn-primary btn-full" style="margin-bottom: 0.75rem; justify-content: center; text-decoration: none;">
                    <span>Book a Service</span>
                </a>
                <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline btn-full" style="text-decoration: none; justify-content: center;">
                    <i data-lucide="phone" style="width: 16px; height: 16px;"></i>
                    <span>Helpline: <?= APP_PHONE_DISPLAY; ?></span>
                </a>
            </div>
        </div>
    </div>

    <main>
        <!-- ===================================================================
             HERO SECTION (BENTO MARKETPLACE SHOWCASE)
             =================================================================== -->
        <section class="hero-section" id="hero">
            <div class="container">
                <div class="hero-bento-grid">
                    <!-- Left Bento Card: Headline, Quick Booking & Chips -->
                    <div class="hero-main-card">
                        <div>
                            <div class="hero-eyebrow-pill">
                                <span class="pulse-indicator"></span>
                                <span>18 Verified Mitras Active Near You</span>
                            </div>

                            <h1 class="hero-headline">
                                Home Repairs Solved in <span>30 Minutes.</span>
                            </h1>

                            <p class="hero-subtext">
                                Background-verified electricians, plumbers, AC technicians, and deep cleaners equipped with original parts. Transparent rate cards and 30-day warranty.
                            </p>
                        </div>

                        <div>
                            <!-- Search & Quick Booking Bar -->
                            <div class="hero-booking-bar">
                                <div class="hero-search-input-wrap">
                                    <i data-lucide="search" style="width: 18px; height: 18px; color: #94A3B8; flex-shrink: 0;"></i>
                                    <input type="text" id="headerSearchInput" placeholder="Search 'tap repair', 'AC service'..." autocomplete="off">
                                    <button type="button" id="clearSearchBtn" class="search-clear-btn" style="display: none;">&times;</button>
                                </div>
                                <a href="<?= $isLoggedIn ? 'book.php?service=plumbing' : 'login.php?redirect=' . urlencode('book.php?service=plumbing'); ?>" class="btn btn-primary btn-sm hero-find-btn" style="text-decoration: none;">
                                    <span class="find-btn-text">Find Mitra</span>
                                    <i data-lucide="arrow-right" style="width: 15px; height: 15px;"></i>
                                </a>
                                <div id="headerSearchDropdown" class="search-dropdown-menu" style="display: none;"></div>
                            </div>

                            <!-- Quick Service Chips -->
                            <div class="hero-chips-wrap">
                                <span class="hero-chips-label">Popular:</span>
                                <button type="button" class="hero-service-chip trigger-category-filter" data-cat="plumbing">
                                    <i data-lucide="droplet" style="width: 13px; height: 13px; color: #2563EB;"></i>
                                    <span>Tap Leakage</span>
                                </button>
                                <button type="button" class="hero-service-chip trigger-category-filter" data-cat="electrical">
                                    <i data-lucide="zap" style="width: 13px; height: 13px; color: #D97706;"></i>
                                    <span>MCB Tripped</span>
                                </button>
                                <button type="button" class="hero-service-chip trigger-category-filter" data-cat="appliances">
                                    <i data-lucide="cpu" style="width: 13px; height: 13px; color: #0891B2;"></i>
                                    <span>AC Foam Jet</span>
                                </button>
                                <button type="button" class="hero-service-chip trigger-category-filter" data-cat="cleaning">
                                    <i data-lucide="sparkles" style="width: 13px; height: 13px; color: #059669;"></i>
                                    <span>Bathroom Clean</span>
                                </button>
                                <button type="button" class="hero-service-chip trigger-category-filter" data-cat="carpentry">
                                    <i data-lucide="lock" style="width: 13px; height: 13px; color: #4F46E5;"></i>
                                    <span>Smart Lock</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Bento Card: Featured Pro Spotlight -->
                    <div class="hero-visual-card">
                        <img src="assets/images/hero-technician.jpg" alt="Certified KaamMitra Technician" class="hero-visual-img">
                        <div class="hero-visual-gradient"></div>
                        <div class="hero-visual-content">
                            <div class="hero-pro-badge">
                                <i data-lucide="shield-check" style="width: 14px; height: 14px; color: #10B981;"></i>
                                <span>Aadhaar &amp; Police Background Verified</span>
                            </div>
                            <h3 class="hero-pro-name">Rajesh Kumar</h3>
                            <p class="hero-pro-desc">Master Electrician &amp; HVAC Pro • 4.95 ★ (1,250 jobs completed in Powai)</p>

                            <div class="hero-floating-stat-box">
                                <div class="stat-mini-item">
                                    <strong>50,000+</strong>
                                    <span>Happy Homes</span>
                                </div>
                                <div class="stat-mini-item">
                                    <strong>28 Mins</strong>
                                    <span>Avg Arrival</span>
                                </div>
                                <div class="stat-mini-item">
                                    <strong>30 Days</strong>
                                    <span>Free Warranty</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             APP CATEGORY BAR (7 CIRCULAR FLOATING HUBS)
             =================================================================== -->
        <section class="app-category-section">
            <div class="container">
                <div class="app-category-grid">
                    <!-- 1. Plumbing -->
                    <div class="category-hub-card trigger-category-filter" data-cat="plumbing">
                        <div class="category-icon-halo halo-blue">
                            <i data-lucide="droplet" style="width: 26px; height: 26px;"></i>
                        </div>
                        <span class="category-hub-title">Plumbing</span>
                        <span class="category-hub-time">⚡ 25 mins</span>
                    </div>

                    <!-- 2. Electrical -->
                    <div class="category-hub-card trigger-category-filter" data-cat="electrical">
                        <div class="category-icon-halo halo-amber">
                            <i data-lucide="zap" style="width: 26px; height: 26px;"></i>
                        </div>
                        <span class="category-hub-title">Electrical</span>
                        <span class="category-hub-time">⚡ 20 mins</span>
                    </div>

                    <!-- 3. AC & Appliances -->
                    <div class="category-hub-card trigger-category-filter" data-cat="appliances">
                        <div class="category-icon-halo halo-cyan">
                            <i data-lucide="cpu" style="width: 26px; height: 26px;"></i>
                        </div>
                        <span class="category-hub-title">AC &amp; Appliance</span>
                        <span class="category-hub-time">Popular</span>
                    </div>

                    <!-- 4. Deep Cleaning -->
                    <div class="category-hub-card trigger-category-filter" data-cat="cleaning">
                        <div class="category-icon-halo halo-emerald">
                            <i data-lucide="sparkles" style="width: 26px; height: 26px;"></i>
                        </div>
                        <span class="category-hub-title">Deep Clean</span>
                        <span class="category-hub-time">Eco-Friendly</span>
                    </div>

                    <!-- 5. Carpentry -->
                    <div class="category-hub-card trigger-category-filter" data-cat="carpentry">
                        <div class="category-icon-halo halo-orange">
                            <i data-lucide="hammer" style="width: 26px; height: 26px;"></i>
                        </div>
                        <span class="category-hub-title">Carpentry</span>
                        <span class="category-hub-time">⚡ 40 mins</span>
                    </div>

                    <!-- 6. Wall Painting -->
                    <div class="category-hub-card trigger-category-filter" data-cat="painting">
                        <div class="category-icon-halo halo-violet">
                            <i data-lucide="palette" style="width: 26px; height: 26px;"></i>
                        </div>
                        <span class="category-hub-title">Painting</span>
                        <span class="category-hub-time">Free Quote</span>
                    </div>

                    <!-- 7. Smart Locks -->
                    <div class="category-hub-card trigger-category-filter" data-cat="carpentry">
                        <div class="category-icon-halo halo-indigo">
                            <i data-lucide="lock" style="width: 26px; height: 26px;"></i>
                        </div>
                        <span class="category-hub-title">Smart Home</span>
                        <span class="category-hub-time">Security</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             TRENDING SERVICES (MODERN BENTO SHOWCASE)
             =================================================================== -->
        <section class="section" id="trending" style="padding-top: 2rem;">
            <div class="container">
                <div class="section-head">
                    <span class="section-badge">Fast Turnaround</span>
                    <h2 class="section-heading">Trending Doorstep Services</h2>
                    <p class="section-subheading">High-demand maintenance and repairs dispatched with guaranteed arrival and certified tools.</p>
                </div>

                <div class="trending-bento-grid">
                    <!-- Card 1: AC Power Jet -->
                    <div class="bento-card">
                        <div class="bento-thumb-wrap">
                            <img src="assets/images/ac-service.jpg" alt="AC Power-Jet Foam Servicing">
                            <span class="bento-badge-tag">2X Deeper Clean</span>
                        </div>
                        <div class="bento-body">
                            <div>
                                <h3 class="bento-title">AC Power-Jet Foam Servicing</h3>
                                <div class="bento-meta-row">
                                    <span class="bento-rating"><i data-lucide="star" style="width: 14px; height: 14px; fill: #D97706;"></i> 4.88</span>
                                    <span>• 45 mins</span>
                                </div>
                            </div>
                            <div class="bento-footer">
                                <div class="bento-price-wrap">
                                    <span class="bento-price-prefix">Starts at</span>
                                    <span class="bento-price">₹499</span>
                                </div>
                                <a href="book.php?service=appliances&sub=<?= urlencode('AC Jet Pump Foam Servicing'); ?>&price=499" class="bento-book-btn">+ Book</a>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Plumbing Tap & Leakage -->
                    <div class="bento-card">
                        <div class="bento-thumb-wrap">
                            <img src="assets/images/plumbing-repair.jpg" alt="Tap Repair & Pipe Leakage">
                            <span class="bento-badge-tag">⚡ 25 Min Arrival</span>
                        </div>
                        <div class="bento-body">
                            <div>
                                <h3 class="bento-title">Tap Repair &amp; Leakage Rescue</h3>
                                <div class="bento-meta-row">
                                    <span class="bento-rating"><i data-lucide="star" style="width: 14px; height: 14px; fill: #D97706;"></i> 4.92</span>
                                    <span>• 30 mins</span>
                                </div>
                            </div>
                            <div class="bento-footer">
                                <div class="bento-price-wrap">
                                    <span class="bento-price-prefix">Starts at</span>
                                    <span class="bento-price">₹199</span>
                                </div>
                                <a href="book.php?service=plumbing&sub=<?= urlencode('Tap Repair / Replacement'); ?>&price=199" class="bento-book-btn">+ Book</a>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Bathroom Cleaning -->
                    <div class="bento-card">
                        <div class="bento-thumb-wrap">
                            <img src="assets/images/bathroom-cleaning.jpg" alt="Bathroom Deep Cleaning">
                            <span class="bento-badge-tag">Eco-Descaling</span>
                        </div>
                        <div class="bento-body">
                            <div>
                                <h3 class="bento-title">Marble Bathroom Spa Cleaning</h3>
                                <div class="bento-meta-row">
                                    <span class="bento-rating"><i data-lucide="star" style="width: 14px; height: 14px; fill: #D97706;"></i> 4.90</span>
                                    <span>• 60 mins</span>
                                </div>
                            </div>
                            <div class="bento-footer">
                                <div class="bento-price-wrap">
                                    <span class="bento-price-prefix">Starts at</span>
                                    <span class="bento-price">₹399</span>
                                </div>
                                <a href="book.php?service=cleaning&sub=<?= urlencode('Intense Bathroom Deep Cleaning'); ?>&price=399" class="bento-book-btn">+ Book</a>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Switchboard & Wiring -->
                    <div class="bento-card">
                        <div class="bento-thumb-wrap">
                            <img src="assets/images/electrician-service.jpg" alt="Electrical Switchboard Repair">
                            <span class="bento-badge-tag">Safety Certified</span>
                        </div>
                        <div class="bento-body">
                            <div>
                                <h3 class="bento-title">Smart Switchboard &amp; MCB Repair</h3>
                                <div class="bento-meta-row">
                                    <span class="bento-rating"><i data-lucide="star" style="width: 14px; height: 14px; fill: #D97706;"></i> 4.95</span>
                                    <span>• 25 mins</span>
                                </div>
                            </div>
                            <div class="bento-footer">
                                <div class="bento-price-wrap">
                                    <span class="bento-price-prefix">Starts at</span>
                                    <span class="bento-price">₹149</span>
                                </div>
                                <a href="book.php?service=electrical&sub=<?= urlencode('Switchboard / Socket Repair'); ?>&price=149" class="bento-book-btn">+ Book</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             TRANSPARENT VISITING FEE & GUARANTEE PLEDGE
             =================================================================== -->
        <section class="section" id="pledge" style="padding-top: 1rem;">
            <div class="container">
                <div class="pledge-banner-card">
                    <div>
                        <span class="pledge-eyebrow">Our Customer Pledge</span>
                        <h2 class="pledge-title">Standard Doorstep Visiting Fee: ₹149</h2>
                        <p class="pledge-desc">
                            Includes 45-minute comprehensive diagnostic check by a certified Mitra. <strong>100% Adjusted &amp; Waived</strong> against your final repair bill if the service booked exceeds ₹499. No bargaining, no surprises.
                        </p>
                    </div>

                    <div class="pledge-metrics-box">
                        <div class="pledge-metric-row">
                            <div class="pledge-metric-icon">
                                <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
                            </div>
                            <div class="pledge-metric-text">
                                <strong>30-Day Happiness Guarantee</strong>
                                <span>Free rework if any issue arises after service</span>
                            </div>
                        </div>

                        <div class="pledge-metric-row">
                            <div class="pledge-metric-icon">
                                <i data-lucide="receipt" style="width: 20px; height: 20px;"></i>
                            </div>
                            <div class="pledge-metric-text">
                                <strong>Zero Advance Required</strong>
                                <span>Pay securely only after the job is 100% complete</span>
                            </div>
                        </div>

                        <div class="pledge-metric-row">
                            <div class="pledge-metric-icon">
                                <i data-lucide="wrench" style="width: 20px; height: 20px;"></i>
                            </div>
                            <div class="pledge-metric-text">
                                <strong>Genuine OEM Spare Parts</strong>
                                <span>All replacement components sealed with warranty</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             ALL SERVICES & RATE CARDS (MODERN CATALOG)
             =================================================================== -->
        <section class="section" id="services" style="padding-top: 1rem;">
            <div class="container">
                <div class="section-head">
                    <span class="section-badge">Full Marketplace</span>
                    <h2 class="section-heading">All Services &amp; Transparent Rates</h2>
                    <p class="section-subheading">Choose any category to view standardized pricing, duration, and included inspection checklists.</p>
                </div>

                <!-- Category Tab Chips -->
                <?php
                $SERVICE_COVER_IMAGES = [
                    'plumbing' => 'assets/images/plumbing-repair.jpg',
                    'electrical' => 'assets/images/electrician-service.jpg',
                    'appliances' => 'assets/images/ac-service.jpg',
                    'carpentry' => 'assets/images/smart-door-lock.jpg',
                    'cleaning' => 'assets/images/bathroom-cleaning.jpg',
                    'painting' => 'assets/images/wall-painting.jpg'
                ];
                ?>
                <div class="catalog-tabs-wrapper">
                    <button type="button" class="catalog-tab-chip active category-tab-btn" data-category="all">
                        <span>All Categories</span>
                        <span class="tab-count-badge"><?= count($SERVICES_DATA); ?></span>
                    </button>
                    <?php foreach ($SERVICE_CATEGORIES as $k => $name): ?>
                        <?php if ($k !== 'all'): ?>
                            <button type="button" class="catalog-tab-chip category-tab-btn" data-category="<?= htmlspecialchars($k); ?>">
                                <span><?= htmlspecialchars($name); ?></span>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Catalog Grid -->
                <div class="services-catalog-grid" id="servicesCatalogGrid">
                    <?php foreach ($SERVICES_DATA as $service): 
                        $coverImg = $SERVICE_COVER_IMAGES[$service['category']] ?? 'assets/images/hero-technician.jpg';
                    ?>
                        <div class="catalog-card" data-category="<?= htmlspecialchars($service['category']); ?>">
                            <!-- Photographic Cover Header -->
                            <div class="catalog-card-image-wrap">
                                <img src="<?= htmlspecialchars($coverImg); ?>" alt="<?= htmlspecialchars($service['title']); ?>" class="catalog-card-img" loading="lazy">
                                <div class="catalog-card-img-gradient"></div>
                                <span class="catalog-badge-pill"><?= htmlspecialchars($service['badge']); ?></span>
                                <div class="catalog-rating-pill">
                                    <i data-lucide="star" style="width: 13px; height: 13px; fill: #D97706; color: #D97706;"></i>
                                    <strong><?= $service['rating']; ?></strong>
                                    <span>(<?= $service['reviews']; ?>)</span>
                                </div>
                            </div>

                            <!-- Card Body Content -->
                            <div class="catalog-card-body">
                                <div class="catalog-card-header">
                                    <div class="catalog-icon-badge">
                                        <?= renderIcon($service['icon'], 22); ?>
                                    </div>
                                    <div>
                                        <h3 class="catalog-card-title"><?= htmlspecialchars($service['title']); ?></h3>
                                        <span class="catalog-category-tag"><?= htmlspecialchars($SERVICE_CATEGORIES[$service['category']] ?? 'Home Service'); ?></span>
                                    </div>
                                </div>

                                <p class="catalog-card-subtitle"><?= htmlspecialchars($service['subtitle']); ?></p>

                                <!-- Trust & Delivery Badges -->
                                <div class="catalog-perks-row">
                                    <span class="catalog-perk"><i data-lucide="clock" style="width: 12px; height: 12px;"></i> <?= htmlspecialchars($service['duration']); ?></span>
                                    <span class="catalog-perk"><i data-lucide="shield-check" style="width: 12px; height: 12px;"></i> 30-Day Guarantee</span>
                                    <span class="catalog-perk"><i data-lucide="award" style="width: 12px; height: 12px;"></i> Verified Mitra</span>
                                </div>

                                <!-- Key Inspection Checklist -->
                                <div class="catalog-checklist-wrap">
                                    <span class="catalog-checklist-title">Included Scope:</span>
                                    <ul class="card-features-list">
                                        <?php foreach ($service['features'] as $feat): ?>
                                            <li>
                                                <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                                <span><?= htmlspecialchars($feat); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- Expandable Rate Card Sub-Services -->
                                <?php if (!empty($service['sub_services'])): ?>
                                <div class="catalog-subservices-section">
                                    <button type="button" class="rate-card-toggle-btn" aria-expanded="false">
                                        <div style="display: flex; align-items: center; gap: 0.45rem;">
                                            <i data-lucide="receipt" style="width: 14px; height: 14px; color: var(--brand-coral);"></i>
                                            <span>View Rate Card (<?= count($service['sub_services']); ?> options)</span>
                                        </div>
                                        <i data-lucide="chevron-down" class="rate-card-chevron" style="width: 15px; height: 15px;"></i>
                                    </button>
                                    
                                    <div class="rate-card-drawer">
                                        <div class="rate-card-items-list">
                                            <?php foreach ($service['sub_services'] as $sub): ?>
                                            <div class="rate-card-item">
                                                <div class="rate-card-item-info">
                                                    <span class="rate-card-item-name"><?= htmlspecialchars($sub['name']); ?></span>
                                                    <span class="rate-card-item-time"><i data-lucide="clock" style="width: 11px; height: 11px;"></i> <?= htmlspecialchars($sub['time']); ?></span>
                                                </div>
                                                <div class="rate-card-item-action">
                                                    <span class="rate-card-item-price">₹<?= $sub['price']; ?></span>
                                                    <a href="book.php?service=<?= urlencode($service['category']); ?>&sub=<?= urlencode($sub['name']); ?>&price=<?= urlencode($sub['price']); ?>" class="rate-card-book-btn">
                                                        + Book
                                                    </a>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Card Bottom Pricing & Action -->
                            <div class="catalog-card-footer">
                                <div class="catalog-price-block">
                                    <span class="catalog-price-label">Visiting / Inspection</span>
                                    <span class="catalog-price-value">₹<?= $service['starting_price']; ?></span>
                                </div>
                                <a href="book.php?service=<?= urlencode($service['category']); ?>&sub=<?= urlencode($service['sub_services'][0]['name'] ?? $service['title']); ?>&price=<?= urlencode($service['sub_services'][0]['price'] ?? $service['starting_price']); ?>" class="btn btn-primary btn-sm" style="text-decoration: none;">
                                    <span>Book Service</span>
                                    <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             THE KAAMMITRA STANDARD (TRUST PILLARS)
             =================================================================== -->
        <section class="section" id="standard" style="background: #FFFFFF;">
            <div class="container">
                <div class="section-head">
                    <span class="section-badge">Quality Guarantee</span>
                    <h2 class="section-heading">The KaamMitra Standard</h2>
                    <p class="section-subheading">Why 50,000+ Indian households prefer us over unorganized local contractors.</p>
                </div>

                <div class="trust-pillars-grid">
                    <div class="trust-pillar-card">
                        <div class="pillar-icon-box">
                            <i data-lucide="shield-check" style="width: 26px; height: 26px;"></i>
                        </div>
                        <h3 class="pillar-title">100% Police Verified</h3>
                        <p class="pillar-desc">Every technician undergoes Aadhaar bio-authentication, police criminal record checks, and skill exams.</p>
                    </div>

                    <div class="trust-pillar-card">
                        <div class="pillar-icon-box">
                            <i data-lucide="receipt" style="width: 26px; height: 26px;"></i>
                        </div>
                        <h3 class="pillar-title">Transparent Rate Card</h3>
                        <p class="pillar-desc">Fixed prices approved by trade experts. No bargaining, no doorstep price inflation or hidden surcharge.</p>
                    </div>

                    <div class="trust-pillar-card">
                        <div class="pillar-icon-box">
                            <i data-lucide="badge-check" style="width: 26px; height: 26px;"></i>
                        </div>
                        <h3 class="pillar-title">30-Day Free Rework</h3>
                        <p class="pillar-desc">If the same defect resurfaces within 30 days, we dispatch a senior supervisor to fix it free of cost.</p>
                    </div>

                    <div class="trust-pillar-card">
                        <div class="pillar-icon-box">
                            <i data-lucide="zap" style="width: 26px; height: 26px;"></i>
                        </div>
                        <h3 class="pillar-title">Rapid 30-Min Arrival</h3>
                        <p class="pillar-desc">Hyperlocal dispatch network ensures a certified Mitra reaches your doorstep with complete toolkit.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             HOW IT WORKS (PROCESS PIPELINE)
             =================================================================== -->
        <section class="section" id="howItWorks">
            <div class="container">
                <div class="section-head">
                    <span class="section-badge">Simple &amp; Effortless</span>
                    <h2 class="section-heading">How KaamMitra Works</h2>
                    <p class="section-subheading">From emergency leak to sparkling clean living room in four seamless steps.</p>
                </div>

                <div class="pipeline-steps-grid">
                    <div class="pipeline-step-card">
                        <div class="step-number-tag">01</div>
                        <h3 class="step-title">Select Your Service</h3>
                        <p class="step-desc">Pick from 50+ home maintenance, plumbing, electrical, or cleaning services with clear prices.</p>
                    </div>

                    <div class="pipeline-step-card">
                        <div class="step-number-tag">02</div>
                        <h3 class="step-title">Pick A Slot</h3>
                        <p class="step-desc">Choose immediate 30-min express dispatch or schedule any convenient 2-hour window.</p>
                    </div>

                    <div class="pipeline-step-card">
                        <div class="step-number-tag">03</div>
                        <h3 class="step-title">Expert Arrives</h3>
                        <p class="step-desc">A uniformed, background-verified technician arrives equipped with genuine tools and parts.</p>
                    </div>

                    <div class="pipeline-step-card">
                        <div class="step-number-tag">04</div>
                        <h3 class="step-title">Pay After Satisfaction</h3>
                        <p class="step-desc">Inspect the finished job and pay securely via UPI, card, or cash with automated invoice.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             PARTNER ECOSYSTEM BANNER
             =================================================================== -->
        <section class="section" id="partnerSection" style="padding-top: 1rem;">
            <div class="container">
                <div class="partner-editorial-card">
                    <div>
                        <span class="section-badge" style="background: rgba(255, 85, 0, 0.2); border-color: rgba(255, 85, 0, 0.4); color: #FF7D26;">For Service Professionals</span>
                        <h2 class="partner-editorial-title">
                            Earn up to <span>₹48,000/month</span> as a Verified KaamMitra Partner
                        </h2>
                        <p class="partner-editorial-desc">
                            Join over 3,500+ master electricians, plumbers, carpenters, and technicians. Get guaranteed daily bookings, free insurance, and direct weekly bank payouts.
                        </p>

                        <div class="partner-perks-row">
                            <div class="perk-pill">
                                <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                                <span>0% Commission for First 30 Days</span>
                            </div>
                            <div class="perk-pill">
                                <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                                <span>Free Branded Toolkit &amp; Uniform</span>
                            </div>
                            <div class="perk-pill">
                                <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                                <span>Weekly Direct Bank Payouts</span>
                            </div>
                            <div class="perk-pill">
                                <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                                <span>Accidental Insurance Coverage</span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="partner-register.php" class="btn btn-primary btn-lg" style="text-decoration: none;">
                                <span>Register as Partner Now</span>
                                <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                            </a>
                            <a href="partner-login.php" class="btn btn-outline btn-lg" style="color: #FFFFFF; border-color: rgba(255,255,255,0.35); text-decoration: none; background: rgba(255,255,255,0.06);">
                                <span>Partner Login</span>
                            </a>
                        </div>
                    </div>

                    <div class="partner-earnings-widget">
                        <div class="earnings-label">Average Monthly Earnings</div>
                        <div class="earnings-amount">₹48,500</div>
                        <div class="earnings-sub">✓ Top 15% earn ₹65,000+ with bonuses</div>
                        <p style="font-size: 0.825rem; color: rgba(255, 255, 255, 0.65); line-height: 1.5;">
                            Simple 2-minute registration. Our hub coordinator will call you to verify your Aadhaar within 4 business hours.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             TESTIMONIALS
             =================================================================== -->
        <!-- ===================================================================
             TESTIMONIALS & TRUST EXPERIENCES
             =================================================================== -->
        <section class="section testimonials-section" id="reviews">
            <div class="container">
                <div class="section-head" style="text-align: center;">
                    <span class="section-badge">Verified Feedback</span>
                    <h2 class="section-heading">Loved by 100,000+ Happy Homes</h2>
                    <p class="section-subheading" style="margin: 0 auto;">Genuine experiences from real homeowners across Mumbai, Delhi NCR, and Bengaluru.</p>
                </div>

                <!-- Aggregate Trust Metrics Bar -->
                <div class="reviews-trust-bar">
                    <div class="trust-bar-item">
                        <div class="trust-bar-rating">
                            <span class="rating-num">4.88</span>
                            <div class="rating-stars">
                                <i data-lucide="star" style="width: 15px; height: 15px; fill: #D97706; color: #D97706;"></i>
                                <i data-lucide="star" style="width: 15px; height: 15px; fill: #D97706; color: #D97706;"></i>
                                <i data-lucide="star" style="width: 15px; height: 15px; fill: #D97706; color: #D97706;"></i>
                                <i data-lucide="star" style="width: 15px; height: 15px; fill: #D97706; color: #D97706;"></i>
                                <i data-lucide="star" style="width: 15px; height: 15px; fill: #D97706; color: #D97706;"></i>
                            </div>
                        </div>
                        <span class="trust-bar-sub">Based on 48,000+ verified ratings</span>
                    </div>

                    <div class="trust-bar-divider"></div>

                    <div class="trust-bar-item">
                        <div class="trust-bar-stat">
                            <i data-lucide="shield-check" style="width: 20px; height: 20px; color: var(--accent-emerald);"></i>
                            <span class="stat-num">100%</span>
                        </div>
                        <span class="trust-bar-sub">Police &amp; Aadhaar Background Verified</span>
                    </div>

                    <div class="trust-bar-divider"></div>

                    <div class="trust-bar-item">
                        <div class="trust-bar-stat">
                            <i data-lucide="clock" style="width: 20px; height: 20px; color: var(--brand-coral);"></i>
                            <span class="stat-num">98.4%</span>
                        </div>
                        <span class="trust-bar-sub">On-Time 30-Min Doorstep Arrival</span>
                    </div>
                </div>

                <!-- Testimonial Cards Grid -->
                <div class="testimonials-modern-grid">
                    <?php 
                    $avatarColors = [
                        ['bg' => 'linear-gradient(135deg, #FF5500, #FF8C00)', 'color' => '#FFFFFF'],
                        ['bg' => 'linear-gradient(135deg, #090D16, #1E293B)', 'color' => '#FFFFFF'],
                        ['bg' => 'linear-gradient(135deg, #059669, #10B981)', 'color' => '#FFFFFF'],
                        ['bg' => 'linear-gradient(135deg, #7C3AED, #A855F7)', 'color' => '#FFFFFF'],
                    ];
                    $idx = 0;
                    foreach ($CUSTOMER_TESTIMONIALS as $review): 
                        $avatarStyle = $avatarColors[$idx % count($avatarColors)];
                        $idx++;
                    ?>
                        <div class="testimonial-card-modern">
                            <!-- Card Header: Stars, Verified Pill & Date -->
                            <div class="testimonial-card-top">
                                <div class="testimonial-rating-wrap">
                                    <div class="testimonial-rating-stars">
                                        <?php for ($i = 0; $i < ($review['rating'] ?? 5); $i++): ?>
                                            <i data-lucide="star" style="width: 15px; height: 15px; fill: #D97706; color: #D97706;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="verified-booking-pill">
                                        <i data-lucide="check-circle" style="width: 12px; height: 12px;"></i>
                                        <span>Verified Booking</span>
                                    </span>
                                </div>
                                <span class="testimonial-date-tag"><?= htmlspecialchars($review['date'] ?? 'Recent'); ?></span>
                            </div>

                            <!-- Service Tag Pill -->
                            <div class="testimonial-service-pill">
                                <i data-lucide="wrench" style="width: 12px; height: 12px;"></i>
                                <span><?= htmlspecialchars($review['service']); ?></span>
                            </div>

                            <!-- Comment Quote -->
                            <p class="testimonial-quote">
                                &ldquo;<?= htmlspecialchars($review['comment']); ?>&rdquo;
                            </p>

                            <!-- Author Profile Row -->
                            <div class="testimonial-author-row">
                                <div class="author-avatar-circle" style="background: <?= $avatarStyle['bg']; ?>; color: <?= $avatarStyle['color']; ?>;">
                                    <?= htmlspecialchars($review['avatar'] ?? strtoupper(substr($review['name'], 0, 2))); ?>
                                </div>
                                <div class="author-meta-block">
                                    <div class="author-name-row">
                                        <span class="author-name"><?= htmlspecialchars($review['name']); ?></span>
                                        <i data-lucide="badge-check" style="width: 15px; height: 15px; color: #0284C7;" title="Verified Customer"></i>
                                    </div>
                                    <div class="author-city">
                                        <i data-lucide="map-pin" style="width: 12px; height: 12px;"></i>
                                        <span><?= htmlspecialchars($review['location']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             FREQUENTLY ASKED QUESTIONS
             =================================================================== -->
        <section class="section" id="faq">
            <div class="container">
                <div class="section-head" style="text-align: center;">
                    <span class="section-badge">Got Questions?</span>
                    <h2 class="section-heading">Frequently Asked Questions</h2>
                    <p class="section-subheading" style="margin: 0 auto;">Everything you need to know about pricing, warranties, and technician safety.</p>
                </div>

                <div class="modern-faq-list">
                    <?php 
                    $faqIdx = 0;
                    foreach ($FAQS as $faqCategory): 
                        foreach ($faqCategory['items'] as $item):
                            $isActive = ($faqIdx === 0);
                            $faqIdx++;
                    ?>
                        <div class="faq-card <?= $isActive ? 'active' : ''; ?>">
                            <button type="button" class="faq-trigger" aria-expanded="<?= $isActive ? 'true' : 'false'; ?>">
                                <span><?= htmlspecialchars($item['q']); ?></span>
                                <i data-lucide="chevron-down" style="width: 18px; height: 18px;"></i>
                            </button>
                            <div class="faq-content">
                                <p><?= htmlspecialchars($item['a']); ?></p>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    endforeach; 
                    ?>
                </div>
            </div>
        </section>
    </main>

    <!-- =======================================================================
         FOOTER (MODERN MINIMALIST DIRECTORY)
         ======================================================================= -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-top-grid">
                <div>
                    <h3 class="footer-brand-title">Kaam<span>Mitra</span></h3>
                    <p class="footer-desc">
                        India's trusted on-demand home maintenance marketplace. We connect residential homeowners with background-verified technicians delivering guaranteed work within 30 minutes.
                    </p>
                    <div style="display: flex; align-items: center; gap: 0.75rem; color: #FFFFFF; font-weight: 700; font-size: 0.95rem;">
                        <i data-lucide="phone" style="width: 18px; height: 18px; color: var(--brand-coral);"></i>
                        <span>Helpline: <?= APP_PHONE_DISPLAY; ?></span>
                    </div>
                </div>

                <div>
                    <h4 class="footer-heading">Services</h4>
                    <ul class="footer-links-list">
                        <li><a href="#services">Plumbing &amp; Leakage</a></li>
                        <li><a href="#services">Electrical Repairs</a></li>
                        <li><a href="#services">AC &amp; Appliance Service</a></li>
                        <li><a href="#services">Home Deep Cleaning</a></li>
                        <li><a href="#services">Carpentry &amp; Woodwork</a></li>
                        <li><a href="#services">Waterproofing &amp; Paint</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-heading">Company</h4>
                    <ul class="footer-links-list">
                        <li><a href="#standard">The KaamMitra Standard</a></li>
                        <li><a href="#pledge">Transparent Pricing</a></li>
                        <li><a href="#howItWorks">How It Works</a></li>
                        <li><a href="#partnerSection">Partner Recruitment</a></li>
                        <li><a href="#reviews">Verified Reviews</a></li>
                        <li><a href="#faq">Safety &amp; Compliance</a></li>
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
                    <a href="javascript:void(0)" style="color: rgba(255,255,255,0.6);">Privacy Policy</a>
                    <a href="javascript:void(0)" style="color: rgba(255,255,255,0.6);">Terms of Service</a>
                    <a href="javascript:void(0)" style="color: rgba(255,255,255,0.6);">Security Audit</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- =======================================================================
         LOCATION SELECTOR MODAL (MULTI-CITY METRO PICKER)
         ======================================================================= -->
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
                    KaamMitra operates with dedicated rapid-dispatch hubs across major Indian metros with 30-minute doorstep arrival.
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




    <!-- =======================================================================
         MOBILE APP-LIKE FIXED BOTTOM NAVIGATION BAR
         ======================================================================= -->
    <nav class="mobile-app-bottom-bar" id="mobileAppBottomBar" aria-label="Mobile App Navigation">
        <a href="#hero" class="app-tab active" data-tab="home" id="tabHome">
            <i data-lucide="home"></i>
            <span>Home</span>
        </a>
        <a href="#services" class="app-tab" data-tab="services" id="tabServices">
            <i data-lucide="layout-grid"></i>
            <span>Services</span>
        </a>
        <a href="<?= $isLoggedIn ? 'book.php' : 'login.php?redirect=book.php'; ?>" class="app-tab app-tab-highlight" data-tab="book" id="tabBook" aria-label="Book a Service">
            <div class="tab-highlight-circle">
                <i data-lucide="calendar-plus"></i>
            </div>
            <span>Book</span>
        </a>
        <a href="<?= $isPartnerLogged ? 'partner-dashboard.php' : 'partner-login.php'; ?>" class="app-tab" data-tab="partner" id="tabPartner" aria-label="Partner Portal">
            <i data-lucide="briefcase"></i>
            <span>Partner</span>
        </a>
        <?php if ($isLoggedIn): ?>
            <a href="dashboard.php" class="app-tab" data-tab="account" id="tabAccount" aria-label="My Account">
                <i data-lucide="user"></i>
                <span>Account</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Global Data & Scripts -->
    <script>
        window.KM_SERVICES = <?= json_encode($SERVICES_DATA); ?>;
    </script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/booking.js"></script>
    <script src="assets/js/partner.js"></script>
</body>
</html>
