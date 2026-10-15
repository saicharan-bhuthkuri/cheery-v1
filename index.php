<?php
/**
 * KaamMitra - Modern Responsive Landing Page
 * Urban Company UI/UX Reference Architecture
 * Mumbai / Delhi NCR Multi-City Support
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/data/services.php';
require_once __DIR__ . '/data/features.php';
require_once __DIR__ . '/data/testimonials.php';
require_once __DIR__ . '/data/faqs.php';

/**
 * Helper function for crisp, lightweight inline SVGs
 *
 * @param string $name
 * @param int $size
 * @return string
 */
function renderIcon(string $name, int $size = 20): string {
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
        'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
        'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"></path><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path><path d="M12 6v2m0 8v2"></path>',
        'headset' => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>',
        'wallet' => '<path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"></path><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"></path>',
        'gift' => '<polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>',
        'shirt' => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"></path>',
        'heart-handshake' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>',
        'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"></polyline>'
    ];

    $content = $icons[$name] ?? '<circle cx="12" cy="12" r="10"></circle>';
    return sprintf(
        '<svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="km-icon km-icon-%s">%s</svg>',
        $size, $size, htmlspecialchars($name), $content
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME); ?> - Professional Home Services at Your Doorstep</title>
    
    <!-- SEO & Metadata -->
    <meta name="description" content="Urban Company inspired home services: background-verified plumbers, electricians, appliance technicians, and carpenters at your doorstep in under 45 minutes.">
    <meta name="keywords" content="KaamMitra, Urban Company Mumbai, home services, plumber near me, electrician, AC servicing, carpentry, deep cleaning">
    <meta name="author" content="KaamMitra Technologies">
    
    <!-- OpenGraph -->
    <meta property="og:title" content="KaamMitra - Trusted Home Services On-Demand">
    <meta property="og:description" content="Get expert professionals near you right at your doorstep covering home repair, maintenance, and cleaning.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.kaammitra.in/mumbai">
    <meta property="og:image" content="assets/images/hero-technician.jpg">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- =======================================================================
         NAVIGATION BAR (URBAN COMPANY AESTHETIC)
         ======================================================================= -->
    <header class="site-header" id="siteHeader">
        <div class="container navbar">
            <!-- Brand Logo & Domain Badge -->
            <div class="brand-group">
                <a href="index.php" class="brand-link" aria-label="KaamMitra Home">
                    <div class="brand-logo-symbol">
                        <?= renderIcon('wrench', 20); ?>
                    </div>
                    <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                        <span class="brand-title">Kaam<span>Mitra</span></span>
                        <span class="brand-url-badge">kaammitra.in</span>
                    </div>
                </a>

                <!-- Location Selector Pill -->
                <div class="nav-location-pill" title="Choose your service area">
                    <?= renderIcon('map-pin', 15); ?>
                    <span id="navCityText">Powai, Mumbai</span>
                    <?= renderIcon('chevron-down', 13); ?>
                </div>
            </div>

            <!-- Header Quick Search (Urban Company Style) -->
            <div class="header-search-wrap">
                <?= renderIcon('search', 16); ?>
                <input type="text" id="headerSearchInput" class="header-search-input" placeholder="Search for 'AC service', 'Plumber'..." aria-label="Search services">
            </div>

            <!-- Navigation Links -->
            <nav>
                <ul class="nav-menu">
                    <li><a href="#services" class="nav-link">Services</a></li>
                    <li><a href="#spotlight" class="nav-link">Spotlight</a></li>
                    <li><a href="#howItWorks" class="nav-link">How It Works</a></li>
                    <li><a href="#whyUs" class="nav-link">Why Us</a></li>
                    <li><a href="#reviews" class="nav-link">Reviews</a></li>
                    <li><a href="#partnerSection" class="nav-link">For Partners</a></li>
                </ul>
            </nav>

            <!-- Call-to-Action Group -->
            <div class="nav-cta-group">
                <button type="button" class="btn btn-outline-navy btn-sm trigger-partner-modal">
                    <?= renderIcon('users', 14); ?>
                    <span>Register as Partner</span>
                </button>
                <button type="button" class="btn btn-primary btn-sm trigger-booking-modal" data-service-cat="plumbing">
                    <span>Book a Service</span>
                </button>
                <button id="mobileMenuBtn" class="mobile-hamburger" aria-label="Open mobile menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation Drawer -->
    <div id="mobileDrawerOverlay" class="mobile-drawer-overlay" role="dialog" aria-modal="true" aria-label="Mobile Navigation Menu">
        <div class="mobile-drawer">
            <div class="mobile-drawer-header">
                <div class="brand-group">
                    <div class="brand-logo-symbol">
                        <?= renderIcon('wrench', 18); ?>
                    </div>
                    <span class="brand-title">Kaam<span>Mitra</span></span>
                </div>
                <button id="closeMobileDrawerBtn" class="drawer-close-btn" aria-label="Close menu">&times;</button>
            </div>
            
            <div class="mobile-drawer-location">
                <?= renderIcon('map-pin', 14); ?>
                <span>Serving Mumbai &amp; Delhi NCR</span>
            </div>

            <ul class="mobile-drawer-links">
                <li><a href="#services" class="mobile-nav-link"><span>Our Services</span> <?= renderIcon('chevron-down', 14); ?></a></li>
                <li><a href="#spotlight" class="mobile-nav-link"><span>Spotlight Deals</span> <?= renderIcon('chevron-down', 14); ?></a></li>
                <li><a href="#howItWorks" class="mobile-nav-link"><span>How It Works</span> <?= renderIcon('chevron-down', 14); ?></a></li>
                <li><a href="#whyUs" class="mobile-nav-link"><span>Why KaamMitra</span> <?= renderIcon('chevron-down', 14); ?></a></li>
                <li><a href="#reviews" class="mobile-nav-link"><span>Customer Reviews</span> <?= renderIcon('chevron-down', 14); ?></a></li>
                <li><a href="#faq" class="mobile-nav-link"><span>Help &amp; FAQs</span> <?= renderIcon('chevron-down', 14); ?></a></li>
                <li><a href="#partnerSection" class="mobile-nav-link trigger-partner-modal" style="color: #FF6600;"><span>Join as Partner (Earn ₹48k+)</span> <?= renderIcon('check', 14); ?></a></li>
            </ul>

            <div class="mobile-drawer-actions">
                <button type="button" class="btn btn-primary btn-full trigger-booking-modal" data-service-cat="plumbing">
                    <span>Book a Service</span>
                </button>
                <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline-navy btn-full" style="justify-content: center;">
                    <?= renderIcon('phone', 15); ?>
                    <span>Helpline: <?= APP_PHONE_DISPLAY; ?></span>
                </a>
            </div>
        </div>
    </div>

    <main>
        <!-- ===================================================================
             HERO SECTION (URBAN COMPANY SIGNATURE CATEGORY GRID BOX)
             =================================================================== -->
        <section class="hero-section" id="hero">
            <div class="container hero-grid">
                <!-- Left: Headline, Subtitle, Boxed Category Grid & Trust Markers -->
                <div class="hero-left-pane">
                    <h1 class="hero-headline">Home services at your doorstep</h1>
                    <p class="hero-subprompt">What are you looking for?</p>

                    <!-- The Urban Company Boxed Grid (8 App Tiles in 2 Rows) -->
                    <div class="uc-category-box">
                        <div class="uc-category-grid-8">
                            <!-- 1. Plumbing -->
                            <button type="button" class="uc-tile-btn trigger-category-filter" data-cat="plumbing" aria-label="Plumbing Services">
                                <span class="uc-tile-time-tag">⚡ 30 mins</span>
                                <div class="uc-tile-icon-wrap plumbing"><?= renderIcon('droplet', 22); ?></div>
                                <span class="uc-tile-title">Plumbing</span>
                            </button>

                            <!-- 2. Electrical -->
                            <button type="button" class="uc-tile-btn trigger-category-filter" data-cat="electrical" aria-label="Electrical Repairs">
                                <span class="uc-tile-time-tag">⚡ 25 mins</span>
                                <div class="uc-tile-icon-wrap electrical"><?= renderIcon('zap', 22); ?></div>
                                <span class="uc-tile-title">Electrical</span>
                            </button>

                            <!-- 3. AC & Appliances -->
                            <button type="button" class="uc-tile-btn trigger-category-filter" data-cat="appliances" aria-label="AC and Appliance Repair">
                                <span class="uc-tile-time-tag">Popular</span>
                                <div class="uc-tile-icon-wrap appliances"><?= renderIcon('cpu', 22); ?></div>
                                <span class="uc-tile-title">AC & Appliance</span>
                            </button>

                            <!-- 4. Carpentry -->
                            <button type="button" class="uc-tile-btn trigger-category-filter" data-cat="carpentry" aria-label="Carpentry Services">
                                <span class="uc-tile-time-tag">⚡ 40 mins</span>
                                <div class="uc-tile-icon-wrap carpentry"><?= renderIcon('hammer', 22); ?></div>
                                <span class="uc-tile-title">Carpentry</span>
                            </button>

                            <!-- 5. Deep Cleaning -->
                            <button type="button" class="uc-tile-btn trigger-category-filter" data-cat="cleaning" aria-label="Deep Cleaning Services">
                                <span class="uc-tile-time-tag">Eco-Clean</span>
                                <div class="uc-tile-icon-wrap cleaning"><?= renderIcon('sparkles', 22); ?></div>
                                <span class="uc-tile-title">Cleaning & Pest</span>
                            </button>

                            <!-- 6. Painting -->
                            <button type="button" class="uc-tile-btn trigger-category-filter" data-cat="painting" aria-label="Painting Services">
                                <span class="uc-tile-time-tag">Free Visit</span>
                                <div class="uc-tile-icon-wrap painting"><?= renderIcon('paint-brush', 22); ?></div>
                                <span class="uc-tile-title">Painting</span>
                            </button>

                            <!-- 7. Smart Locks / Security -->
                            <button type="button" class="uc-tile-btn trigger-category-filter" data-cat="carpentry" aria-label="Smart Locks & Security">
                                <span class="uc-tile-time-tag">New</span>
                                <div class="uc-tile-icon-wrap security"><?= renderIcon('lock', 22); ?></div>
                                <span class="uc-tile-title">Smart Locks</span>
                            </button>

                            <!-- 8. InstaHelp / Express -->
                            <button type="button" class="uc-tile-btn trigger-booking-modal" data-service-cat="plumbing" aria-label="InstaHelp Emergency Service">
                                <span class="uc-tile-time-tag" style="background: #FEF3C7; color: #D97706; border-color: #FDE68A;">⚡ 15 mins</span>
                                <div class="uc-tile-icon-wrap instant"><?= renderIcon('bolt', 22); ?></div>
                                <span class="uc-tile-title">InstaHelp</span>
                            </button>
                        </div>
                    </div>

                    <!-- Trust Markers (Matching Urban Company Hero Trust Metrics) -->
                    <div class="uc-trust-markers">
                        <div class="uc-trust-marker-item">
                            <div class="uc-marker-icon-box"><?= renderIcon('star', 22); ?></div>
                            <div class="uc-marker-text">
                                <h4>4.88</h4>
                                <p>Service Rating*</p>
                            </div>
                        </div>

                        <div class="uc-trust-marker-item">
                            <div class="uc-marker-icon-box"><?= renderIcon('users', 22); ?></div>
                            <div class="uc-marker-text">
                                <h4>50,000+</h4>
                                <p>Customers Served*</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Urban Company Signature 2x2 Photo Collage Grid -->
                <div class="hero-right-pane">
                    <div class="hero-collage-container">
                        <div class="hero-collage-grid">
                            <div class="collage-item">
                                <img src="assets/images/cleaning-service.jpg" alt="Home Deep Cleaning Expert" width="280" height="280">
                                <span class="collage-item-tag">Deep Cleaning</span>
                            </div>
                            <div class="collage-item">
                                <img src="assets/images/plumbing-repair.jpg" alt="Licensed Plumber" width="280" height="280">
                                <span class="collage-item-tag">Plumbing Repair</span>
                            </div>
                            <div class="collage-item">
                                <img src="assets/images/electrician-service.jpg" alt="Master Electrician" width="280" height="280">
                                <span class="collage-item-tag">Electrical &amp; Wiring</span>
                            </div>
                            <div class="collage-item">
                                <img src="assets/images/ac-service.jpg" alt="AC Power-Jet Servicing" width="280" height="280">
                                <span class="collage-item-tag">AC &amp; Appliance</span>
                            </div>
                        </div>

                        <!-- Floating Mitra Profile Badge -->
                        <div class="hero-floating-pro-pill">
                            <div class="pro-avatar">RK</div>
                            <div class="pro-info">
                                <h5>Rajesh Kumar <span class="badge badge-green-soft">Police Verified</span></h5>
                                <p>Master Electrician &amp; AC Pro • 4.95 ★ (1,250 jobs)</p>
                            </div>
                        </div>

                        <!-- Floating 30-Day Guarantee Badge -->
                        <div class="hero-floating-guarantee-pill">
                            <div class="guarantee-icon-wrap">
                                <?= renderIcon('badge-check', 22); ?>
                            </div>
                            <div class="guarantee-info-text">
                                <strong>30-Day Free Rework</strong>
                                <span>Guaranteed Peace of Mind</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             "IN THE SPOTLIGHT" PROMOTIONAL CAROUSEL ROW (URBAN COMPANY SIGNATURE)
             =================================================================== -->
        <section class="spotlight-section" id="spotlight">
            <div class="container">
                <div class="section-title-wrap">
                    <span class="section-eyebrow">Trending Offers</span>
                    <h2 class="section-title">In the Spotlight</h2>
                    <p class="section-subtitle">Curated packages with guaranteed turnaround, certified equipment, and complimentary warranties.</p>
                </div>

                <div class="spotlight-grid">
                    <!-- Banner 1: AC Servicing -->
                    <div class="spotlight-card spotlight-ac">
                        <div class="spotlight-card-content">
                            <div>
                                <span class="badge badge-orange-soft spotlight-top-badge">Summer Essential</span>
                                <h3 class="spotlight-title">AC Power Foam-Jet Servicing</h3>
                                <p class="spotlight-desc">2X deeper indoor coil cleaning with spill catch jacket &amp; pressure pump wash.</p>
                            </div>
                            <div class="spotlight-bottom-row">
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748B; font-weight: 700; display: block;">Starts at</span>
                                    <span class="spotlight-price">₹499</span>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm trigger-booking-modal" data-service-cat="appliances" data-sub-name="AC Jet Pump Foam Servicing" data-sub-price="499">
                                    <span>Book Now</span>
                                    <?= renderIcon('check', 14); ?>
                                </button>
                            </div>
                        </div>
                        <div class="spotlight-card-thumb">
                            <img src="assets/images/ac-service.jpg" alt="AC Power Foam-Jet Servicing">
                        </div>
                    </div>

                    <!-- Banner 2: Plumbing -->
                    <div class="spotlight-card spotlight-plumbing">
                        <div class="spotlight-card-content">
                            <div>
                                <span class="badge badge-green-soft spotlight-top-badge">Express 30 Mins</span>
                                <h3 class="spotlight-title">Emergency Plumbing Rescue</h3>
                                <p class="spotlight-desc">Rapid water leakage diagnostics, tap repair &amp; drainage unblocking at fixed rate cards.</p>
                            </div>
                            <div class="spotlight-bottom-row">
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748B; font-weight: 700; display: block;">Starts at</span>
                                    <span class="spotlight-price">₹199</span>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm trigger-booking-modal" data-service-cat="plumbing" data-sub-name="Tap Repair / Replacement" data-sub-price="199">
                                    <span>Book Now</span>
                                    <?= renderIcon('check', 14); ?>
                                </button>
                            </div>
                        </div>
                        <div class="spotlight-card-thumb">
                            <img src="assets/images/plumbing-repair.jpg" alt="Emergency Plumbing Rescue">
                        </div>
                    </div>

                    <!-- Banner 3: Cleaning -->
                    <div class="spotlight-card spotlight-cleaning">
                        <div class="spotlight-card-content">
                            <div>
                                <span class="badge badge-orange-soft spotlight-top-badge">Eco-Friendly</span>
                                <h3 class="spotlight-title">Bathroom &amp; Kitchen Deep Clean</h3>
                                <p class="spotlight-desc">Multi-stage descaling, tile grime removal &amp; slab degreasing with branded chemicals.</p>
                            </div>
                            <div class="spotlight-bottom-row">
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748B; font-weight: 700; display: block;">Starts at</span>
                                    <span class="spotlight-price">₹399</span>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm trigger-booking-modal" data-service-cat="cleaning" data-sub-name="Intense Bathroom Deep Cleaning" data-sub-price="399">
                                    <span>Book Now</span>
                                    <?= renderIcon('check', 14); ?>
                                </button>
                            </div>
                        </div>
                        <div class="spotlight-card-thumb">
                            <img src="assets/images/kitchen-cleaning.jpg" alt="Bathroom &amp; Kitchen Deep Clean">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             "NEW AND NOTEWORTHY" (URBAN COMPANY SQUARE TILES ROW)
             =================================================================== -->
        <section class="uc-section-wrap" id="newNoteworthy">
            <div class="container">
                <div class="uc-section-head">
                    <h2 class="uc-section-title">New and noteworthy</h2>
                </div>

                <div class="noteworthy-grid">
                    <!-- 1 -->
                    <div class="noteworthy-card trigger-booking-modal" data-service-cat="cleaning" data-sub-name="Sofa Shampooing (3-Seater)" data-sub-price="599">
                        <div class="noteworthy-img-wrap">
                            <img src="assets/images/cleaning-service.jpg" alt="Sofa &amp; Carpet Cleaning">
                        </div>
                        <div class="noteworthy-body">
                            <span class="noteworthy-title">Sofa &amp; Upholstery Cleaning</span>
                            <span class="noteworthy-price">Starts at ₹599</span>
                        </div>
                    </div>

                    <!-- 2 -->
                    <div class="noteworthy-card trigger-booking-modal" data-service-cat="painting" data-sub-name="Wall Dampness Waterproof Treatment" data-sub-price="899">
                        <div class="noteworthy-img-wrap">
                            <img src="assets/images/wall-painting.jpg" alt="Waterproofing &amp; Repaint">
                        </div>
                        <div class="noteworthy-body">
                            <span class="noteworthy-title">Wall Dampness Waterproofing</span>
                            <span class="noteworthy-price">Starts at ₹899</span>
                        </div>
                    </div>

                    <!-- 3 -->
                    <div class="noteworthy-card trigger-booking-modal" data-service-cat="appliances" data-sub-name="AC Jet Pump Foam Servicing" data-sub-price="499">
                        <div class="noteworthy-img-wrap">
                            <img src="assets/images/ac-service.jpg" alt="AC Power-Jet Foam Wash">
                        </div>
                        <div class="noteworthy-body">
                            <span class="noteworthy-title">AC Power-Jet Foam Servicing</span>
                            <span class="noteworthy-price">Starts at ₹499</span>
                        </div>
                    </div>

                    <!-- 4 -->
                    <div class="noteworthy-card trigger-booking-modal" data-service-cat="electrical" data-sub-name="Switchboard / Socket Repair" data-sub-price="149">
                        <div class="noteworthy-img-wrap">
                            <img src="assets/images/electrician-service.jpg" alt="Smart Switchboard Repair">
                        </div>
                        <div class="noteworthy-body">
                            <span class="noteworthy-title">Smart Switchboard Repair</span>
                            <span class="noteworthy-price">Starts at ₹149</span>
                        </div>
                    </div>

                    <!-- 5 -->
                    <div class="noteworthy-card trigger-booking-modal" data-service-cat="plumbing" data-sub-name="Tap Repair / Replacement" data-sub-price="199">
                        <div class="noteworthy-img-wrap">
                            <img src="assets/images/plumbing-repair.jpg" alt="Tap &amp; Pipe Leakage Fix">
                        </div>
                        <div class="noteworthy-body">
                            <span class="noteworthy-title">Tap &amp; Pipe Leakage Fix</span>
                            <span class="noteworthy-price">Starts at ₹199</span>
                        </div>
                    </div>

                    <!-- 6 -->
                    <div class="noteworthy-card trigger-booking-modal" data-service-cat="carpentry" data-sub-name="Door Lock Repair / Installation" data-sub-price="249">
                        <div class="noteworthy-img-wrap">
                            <img src="assets/images/smart-door-lock.jpg" alt="Digital Smart Door Locks">
                        </div>
                        <div class="noteworthy-body">
                            <span class="noteworthy-title">Digital Smart Locks &amp; Fix</span>
                            <span class="noteworthy-price">Starts at ₹249</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             "MOST BOOKED SERVICES" (URBAN COMPANY 5-CARD SHOWCASE)
             =================================================================== -->
        <section class="uc-section-wrap" id="mostBooked">
            <div class="container">
                <div class="uc-section-head">
                    <h2 class="uc-section-title">Most booked services</h2>
                </div>

                <div class="uc-cards-row">
                    <!-- Card 1 -->
                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/bathroom-cleaning.jpg" alt="Bathroom Deep Cleaning">
                            <span class="uc-card-badge">Eco-Chemicals</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Intense Bathroom Deep Cleaning</h3>
                                <div class="uc-card-rating">
                                    <span class="uc-star-tag">★ 4.90</span>
                                    <span>(11.8k reviews)</span>
                                </div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹399</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="cleaning" data-sub-name="Intense Bathroom Deep Cleaning" data-sub-price="399">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/plumbing-repair.jpg" alt="Tap Repair">
                            <span class="uc-card-badge">Express 30 Mins</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Tap Repair &amp; Replacement</h3>
                                <div class="uc-card-rating">
                                    <span class="uc-star-tag">★ 4.88</span>
                                    <span>(12.4k reviews)</span>
                                </div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹199</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="plumbing" data-sub-name="Tap Repair / Replacement" data-sub-price="199">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/ac-service.jpg" alt="AC Foam Jet Servicing">
                            <span class="uc-card-badge">2X Deeper Clean</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">AC Power-Jet Foam Servicing</h3>
                                <div class="uc-card-rating">
                                    <span class="uc-star-tag">★ 4.85</span>
                                    <span>(9.7k reviews)</span>
                                </div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹499</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="appliances" data-sub-name="AC Jet Pump Foam Servicing" data-sub-price="499">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4 -->
                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/electrician-service.jpg" alt="Switchboard &amp; Socket Repair">
                            <span class="uc-card-badge">Safety Inspected</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Switchboard &amp; Socket Repair</h3>
                                <div class="uc-card-rating">
                                    <span class="uc-star-tag">★ 4.92</span>
                                    <span>(18.1k reviews)</span>
                                </div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹149</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="electrical" data-sub-name="Switchboard / Socket Repair" data-sub-price="149">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5 -->
                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/smart-door-lock.jpg" alt="Door Lock Repair">
                            <span class="uc-card-badge">Certified Mitra</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Door Lock Repair &amp; Fitting</h3>
                                <div class="uc-card-rating">
                                    <span class="uc-star-tag">★ 4.81</span>
                                    <span>(6.3k reviews)</span>
                                </div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹249</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="carpentry" data-sub-name="Door Lock Repair / Installation" data-sub-price="249">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             "CLEANING ESSENTIALS" (URBAN COMPANY CATEGORY ROW)
             =================================================================== -->
        <section class="uc-section-wrap" id="cleaningEssentials">
            <div class="container">
                <div class="uc-section-head">
                    <h2 class="uc-section-title">Cleaning Essentials</h2>
                    <a href="#services" class="uc-see-all-btn trigger-category-filter" data-cat="cleaning">see all <?= renderIcon('chevron-down', 14); ?></a>
                </div>

                <div class="uc-cards-row">
                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/bathroom-cleaning.jpg" alt="Intense Bathroom Deep Cleaning">
                            <span class="uc-card-badge">Anti-Bacterial</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Intense Bathroom Deep Cleaning</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.90</span> <span>(11.8k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹399</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="cleaning" data-sub-name="Intense Bathroom Deep Cleaning" data-sub-price="399">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/kitchen-cleaning.jpg" alt="Kitchen Deep Degreasing">
                            <span class="uc-card-badge">Oil Stain Removal</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Kitchen Deep Degreasing Service</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.88</span> <span>(8.4k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹799</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="cleaning" data-sub-name="Kitchen Deep Degreasing Service" data-sub-price="799">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/cleaning-service.jpg" alt="Sofa Shampooing">
                            <span class="uc-card-badge">Fabric Dry Wash</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Sofa Shampooing (3-Seater)</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.92</span> <span>(9.1k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹599</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="cleaning" data-sub-name="Sofa Shampooing (3-Seater)" data-sub-price="599">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/living-room-makeover.jpg" alt="Full Home Deep Cleaning">
                            <span class="uc-card-badge">Move-in Special</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">1 BHK Full Home Deep Cleaning</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.89</span> <span>(5.7k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹1,999</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="cleaning" data-sub-name="1 BHK Full Home Deep Cleaning" data-sub-price="1999">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/hero-technician.jpg" alt="Balcony & Floor Scrubbing">
                            <span class="uc-card-badge">Machine Scrub</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Balcony &amp; Floor Scrubbing</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.85</span> <span>(4.2k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹349</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="cleaning" data-sub-name="Balcony & Floor Scrubbing" data-sub-price="349">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             "HOME REPAIR & MAINTENANCE" (URBAN COMPANY CATEGORY ROW)
             =================================================================== -->
        <section class="uc-section-wrap" id="homeRepair">
            <div class="container">
                <div class="uc-section-head">
                    <h2 class="uc-section-title">Home repair &amp; maintenance</h2>
                    <a href="#services" class="uc-see-all-btn trigger-category-filter" data-cat="plumbing">see all <?= renderIcon('chevron-down', 14); ?></a>
                </div>

                <div class="uc-cards-row">
                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/plumbing-repair.jpg" alt="Tap Repair">
                            <span class="uc-card-badge">Instant Arrival</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Tap Repair &amp; Replacement</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.88</span> <span>(12.4k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹199</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="plumbing" data-sub-name="Tap Repair / Replacement" data-sub-price="199">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/electrician-service.jpg" alt="Switchboard Repair">
                            <span class="uc-card-badge">Short Circuit Fix</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Switchboard / Socket Repair</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.92</span> <span>(18.1k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹149</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="electrical" data-sub-name="Switchboard / Socket Repair" data-sub-price="149">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/ceiling-fan.jpg" alt="Ceiling Fan Installation">
                            <span class="uc-card-badge">Fast Fitting</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Ceiling Fan Installation / Fix</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.91</span> <span>(14.3k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹199</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="electrical" data-sub-name="Ceiling Fan Installation / Repair" data-sub-price="199">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/smart-door-lock.jpg" alt="Digital Door Lock Repair">
                            <span class="uc-card-badge">Smart Security</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Digital Door Lock &amp; Fitting</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.87</span> <span>(7.9k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹349</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="carpentry" data-sub-name="Door Lock Repair / Installation" data-sub-price="349">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/hero-technician.jpg" alt="TV Wall Mount">
                            <span class="uc-card-badge">Heavy Duty Drill</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">TV Wall Mount Installation</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.84</span> <span>(6.1k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹299</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="carpentry" data-sub-name="TV Wall Mount Installation" data-sub-price="299">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             FULL-WIDTH PROMO BANNER: KAAMMITRA EXPRESS (URBAN COMPANY NATIVE STYLE)
             =================================================================== -->
        <section class="container">
            <div class="uc-promo-banner-dark">
                <div>
                    <span class="badge badge-orange-soft" style="background: rgba(255,102,0,0.18); color: #FF7D26; margin-bottom: 0.85rem;">⚡ Emergency Service</span>
                    <h2 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 0.65rem; letter-spacing: -0.02em;">KaamMitra Express: Under 30-Min Doorstep Rescue</h2>
                    <p style="color: #94A3B8; font-size: 1rem; max-width: 620px; line-height: 1.6; margin-bottom: 1.5rem;">
                        Water pipe burst? Electric sparking or AC breakdown in Mumbai's peak heat? Book an Express Mitra. Guaranteed arrival in under 30 minutes with police-verified technicians.
                    </p>
                    <button type="button" class="btn btn-primary btn-lg trigger-booking-modal" data-service-cat="plumbing">
                        <span>Book Express Now</span>
                        <?= renderIcon('check', 18); ?>
                    </button>
                </div>
                <div class="uc-banner-dark-pill-group">
                    <div class="express-feature-pill">
                        <?= renderIcon('clock', 20); ?>
                        <div>
                            <strong>28 Mins</strong>
                            <span>Avg Doorstep Arrival</span>
                        </div>
                    </div>
                    <div class="express-feature-pill">
                        <?= renderIcon('shield-check', 20); ?>
                        <div>
                            <strong>100% Verified</strong>
                            <span>Police Checked Pros</span>
                        </div>
                    </div>
                    <div class="express-feature-pill">
                        <?= renderIcon('badge-check', 20); ?>
                        <div>
                            <strong>30-Day Free</strong>
                            <span>Rework Warranty</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             "APPLIANCE REPAIR & SERVICE" (URBAN COMPANY CATEGORY ROW)
             =================================================================== -->
        <section class="uc-section-wrap" id="applianceRepair">
            <div class="container">
                <div class="uc-section-head">
                    <h2 class="uc-section-title">Appliance repair &amp; service</h2>
                    <a href="#services" class="uc-see-all-btn trigger-category-filter" data-cat="appliances">see all <?= renderIcon('chevron-down', 14); ?></a>
                </div>

                <div class="uc-cards-row">
                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/ac-service.jpg" alt="AC Power-Jet Foam Servicing">
                            <span class="uc-card-badge">Spill Catch Jacket</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">AC Power-Jet Foam Servicing</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.85</span> <span>(9.7k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹499</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="appliances" data-sub-name="AC Jet Pump Foam Servicing" data-sub-price="499">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/washing-machine.jpg" alt="Washing Machine Repair">
                            <span class="uc-card-badge">All Top &amp; Front Load</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Washing Machine Diagnostics &amp; Fix</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.86</span> <span>(7.3k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹349</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="appliances" data-sub-name="Washing Machine Diagnostics & Fix" data-sub-price="349">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/refrigerator.jpg" alt="Refrigerator Repair">
                            <span class="uc-card-badge">Cooling Expert</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Refrigerator Cooling Problem Fix</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.84</span> <span>(6.5k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹399</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="appliances" data-sub-name="Refrigerator Cooling Problem Fix" data-sub-price="399">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/ceiling-fan.jpg" alt="Exhaust &amp; Ventilation Fan">
                            <span class="uc-card-badge">Motor &amp; Capacitor</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Exhaust &amp; Ventilation Fan Repair</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.89</span> <span>(4.8k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹199</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="electrical" data-sub-name="Ceiling Fan Installation / Repair" data-sub-price="199">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="uc-service-card">
                        <div class="uc-card-thumb-wrap">
                            <img src="assets/images/plumbing-repair.jpg" alt="Geyser Repair">
                            <span class="uc-card-badge">Instant Heating</span>
                        </div>
                        <div class="uc-card-body">
                            <div>
                                <h3 class="uc-card-title">Geyser Repair &amp; Thermostat</h3>
                                <div class="uc-card-rating"><span class="uc-star-tag">★ 4.87</span> <span>(5.1k)</span></div>
                            </div>
                            <div class="uc-card-footer">
                                <span class="uc-card-price">₹299</span>
                                <button type="button" class="btn-uc-add trigger-booking-modal" data-service-cat="appliances" data-sub-name="Geyser Repair & Element Replacement" data-sub-price="299">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             FULL-WIDTH LIFESTYLE BANNER: GLOW-UP (URBAN COMPANY WALL PANELS STYLE)
             =================================================================== -->
        <section class="container">
            <div class="uc-promo-banner-light">
                <div class="uc-banner-text-pane">
                    <span class="badge badge-orange-soft" style="background: rgba(255,102,0,0.14); color: #D97706; margin-bottom: 0.85rem;">✨ Home Transformation</span>
                    <h2 style="font-size: 2.25rem; font-weight: 800; color: #0A1325; margin-bottom: 0.65rem; letter-spacing: -0.02em;">Give your space the glow-up it deserves</h2>
                    <p style="color: #64748B; font-size: 1.05rem; max-width: 500px; line-height: 1.6; margin-bottom: 1.75rem;">
                        Festive home makeover, wall waterproofing, custom stencil painting, and deep sanitization packages at transparent per-sqft estimates.
                    </p>
                    <div>
                        <button type="button" class="btn btn-navy btn-lg trigger-booking-modal" data-service-cat="painting">
                            <span>Explore Makeovers</span>
                            <?= renderIcon('check', 18); ?>
                        </button>
                    </div>
                </div>
                <div class="uc-banner-image-pane">
                    <img src="assets/images/living-room-makeover.jpg" alt="Luxury Living Room Wall Panels and Furniture Makeover" class="uc-banner-img">
                </div>
            </div>
        </section>

        <!-- ===================================================================
             ALL SERVICES CATALOG (WITH INTERACTIVE CATEGORY TABS)
             =================================================================== -->
        <section class="section services-section" id="services">
            <div class="container">
                <div class="section-title-wrap">
                    <span class="section-eyebrow">Complete Catalog</span>
                    <h2 class="section-title">All Services &amp; Rate Cards</h2>
                    <p class="section-subtitle">Transparent rate cards, zero hidden doorstep costs, and verified technician warranty on every booking.</p>
                </div>

                <!-- Category Tab Bar -->
                <div class="category-tab-bar">
                    <button type="button" class="category-tab-btn active" data-category="all">All Services</button>
                    <?php foreach ($SERVICE_CATEGORIES as $k => $name): ?>
                        <?php if ($k !== 'all'): ?>
                            <button type="button" class="category-tab-btn" data-category="<?= htmlspecialchars($k); ?>"><?= htmlspecialchars($name); ?></button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Grid of Services -->
                <div class="services-catalog-grid" id="servicesCatalogGrid">
                    <?php foreach ($SERVICES_DATA as $service): ?>
                        <div class="catalog-card" data-category="<?= htmlspecialchars($service['category']); ?>">
                            <div class="card-top-row">
                                <div class="card-icon-pill">
                                    <?= renderIcon($service['icon'], 26); ?>
                                </div>
                                <span class="badge badge-orange-soft"><?= htmlspecialchars($service['badge']); ?></span>
                            </div>

                            <h3 class="card-title"><?= htmlspecialchars($service['title']); ?></h3>
                            <p class="card-subtitle"><?= htmlspecialchars($service['subtitle']); ?></p>

                            <div class="card-meta-row">
                                <span class="card-meta-item">
                                    <?= renderIcon('star', 15); ?>
                                    <strong><?= $service['rating']; ?></strong> (<?= $service['reviews']; ?>)
                                </span>
                                <span class="card-meta-item">
                                    <?= renderIcon('clock', 15); ?>
                                    <span><?= $service['duration']; ?></span>
                                </span>
                            </div>

                            <ul class="card-features-list">
                                <?php foreach ($service['features'] as $feat): ?>
                                    <li>
                                        <?= renderIcon('check', 16); ?>
                                        <span><?= htmlspecialchars($feat); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="card-bottom-row">
                                <div class="card-price-group">
                                    <span class="card-price-caption">Starting From</span>
                                    <span class="card-price-number">₹<?= $service['starting_price']; ?></span>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm trigger-booking-modal" data-service-cat="<?= htmlspecialchars($service['category']); ?>">
                                    <span>Book Service</span>
                                    <?= renderIcon('check', 14); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             "HOW IT WORKS" PROGRESSIVE SECTION
             =================================================================== -->
        <section class="section how-it-works-section" id="howItWorks">
            <div class="container">
                <div class="section-title-wrap">
                    <span class="section-eyebrow">Hassle-Free Experience</span>
                    <h2 class="section-title">How KaamMitra Works</h2>
                    <p class="section-subtitle">Effortless 4-step process from booking to complete satisfaction at your doorstep.</p>
                </div>

                <div class="steps-timeline-grid">
                    <?php foreach ($HOW_IT_WORKS_CUSTOMER as $item): ?>
                        <div class="step-item-card">
                            <div class="step-counter-header">
                                <div class="step-icon-bubble">
                                    <?= renderIcon($item['icon'], 26); ?>
                                </div>
                                <span class="step-number-tag"><?= $item['step']; ?></span>
                            </div>
                            <h4 class="step-item-title"><?= htmlspecialchars($item['title']); ?></h4>
                            <p class="step-item-desc"><?= htmlspecialchars($item['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             THE KAAMMITRA ASSURANCE (TRUST PILLARS)
             =================================================================== -->
        <section class="section advantage-section" id="whyUs">
            <div class="container">
                <div class="section-title-wrap">
                    <span class="section-eyebrow">Urban Company Standard</span>
                    <h2 class="section-title">The KaamMitra Assurance</h2>
                    <p class="section-subtitle">We hold ourselves to the highest standards of safety, verified background checks, and honest pricing.</p>
                </div>

                <div class="advantage-grid">
                    <?php foreach ($TRUST_PILLARS as $p): ?>
                        <div class="advantage-card">
                            <div class="advantage-icon-wrapper">
                                <?= renderIcon($p['icon'], 24); ?>
                            </div>
                            <span class="advantage-tag"><?= htmlspecialchars($p['tag']); ?></span>
                            <h4 class="advantage-title"><?= htmlspecialchars($p['title']); ?></h4>
                            <p class="advantage-desc"><?= htmlspecialchars($p['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             PARTNER ONBOARDING SECTION ("GROW WITH KAAMMITRA")
             =================================================================== -->
        <section class="section partner-section" id="partnerSection">
            <div class="container">
                <div class="partner-card-wrapper">
                    <div class="partner-content">
                        <span class="badge badge-orange-soft" style="margin-bottom: 1.25rem; background: rgba(255,102,0,0.18); color: var(--orange-400); border-color: rgba(255,102,0,0.3);">
                            Empowering Skilled Professionals
                        </span>
                        <h2>Are You an Electrician, Plumber, or Technician? Grow with Us!</h2>
                        <p class="partner-lead">
                            Join over 3,500+ verified professionals who enjoy steady daily orders, respectful treatment, 0% commission for 30 days, and comprehensive family insurance.
                        </p>

                        <div class="partner-benefits-grid">
                            <div class="partner-benefit-cell">
                                <div class="partner-benefit-icon"><?= renderIcon('wallet', 16); ?></div>
                                <div>
                                    <h6>Daily Bank Payouts</h6>
                                    <p>Your earnings credited every evening via UPI or bank.</p>
                                </div>
                            </div>
                            <div class="partner-benefit-cell">
                                <div class="partner-benefit-icon"><?= renderIcon('gift', 16); ?></div>
                                <div>
                                    <h6>0% Commission Month 1</h6>
                                    <p>Keep 100% of customer billings as your welcome bonus.</p>
                                </div>
                            </div>
                            <div class="partner-benefit-cell">
                                <div class="partner-benefit-icon"><?= renderIcon('shirt', 16); ?></div>
                                <div>
                                    <h6>Free Uniform & Toolkit</h6>
                                    <p>Branded workwear, ID card, and testing tools provided.</p>
                                </div>
                            </div>
                            <div class="partner-benefit-cell">
                                <div class="partner-benefit-icon"><?= renderIcon('heart-handshake', 16); ?></div>
                                <div>
                                    <h6>₹3 Lakh Medical Cover</h6>
                                    <p>Full hospitalization and accidental insurance protection.</p>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-lg trigger-partner-modal">
                            <span>Register as Service Partner</span>
                            <?= renderIcon('check', 18); ?>
                        </button>
                    </div>

                    <!-- Earnings Callout Card -->
                    <div class="partner-earnings-box">
                        <span class="earnings-badge">Partner Monthly Earnings</span>
                        <div class="earnings-number">₹48,500</div>
                        <p class="earnings-caption">Average monthly earnings for full-time Mitras</p>

                        <div class="partner-stats-table">
                            <div>
                                <span>Daily Bookings:</span>
                                <strong>4 to 6 Jobs</strong>
                            </div>
                            <div>
                                <span>Payout Frequency:</span>
                                <strong>Every Day by 9:00 PM</strong>
                            </div>
                            <div>
                                <span>Verification Time:</span>
                                <strong>Under 4 Hours</strong>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-white btn-full trigger-partner-modal">
                            Start Application Now
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             TESTIMONIALS SECTION
             =================================================================== -->
        <section class="section testimonials-section" id="reviews">
            <div class="container">
                <div class="section-title-wrap">
                    <span class="section-eyebrow">Real Reviews</span>
                    <h2 class="section-title">Loved by Indian Homeowners</h2>
                    <p class="section-subtitle">Read how KaamMitra provides consistent, reliable, and punctual service every single day.</p>
                </div>

                <div class="testimonials-grid">
                    <?php foreach ($CUSTOMER_TESTIMONIALS as $t): ?>
                        <div class="review-card">
                            <div>
                                <div class="review-stars">
                                    <?php for ($i = 0; $i < $t['rating']; $i++): ?>
                                        <?= renderIcon('star', 17); ?>
                                    <?php endfor; ?>
                                </div>
                                <p class="review-quote">"<?= htmlspecialchars($t['comment']); ?>"</p>
                            </div>
                            <div class="review-author">
                                <div class="author-avatar"><?= htmlspecialchars($t['avatar']); ?></div>
                                <div class="author-meta">
                                    <h5><?= htmlspecialchars($t['name']); ?></h5>
                                    <p><?= htmlspecialchars($t['service']); ?> • <?= htmlspecialchars($t['location']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             FAQS ACCORDION
             =================================================================== -->
        <section class="section faq-section" id="faq">
            <div class="container">
                <div class="section-title-wrap">
                    <span class="section-eyebrow">Answers to Common Questions</span>
                    <h2 class="section-title">Frequently Asked Questions</h2>
                    <p class="section-subtitle">Clear information about booking, transparent pricing, warranties, and service partner onboarding.</p>
                </div>

                <div class="faq-container">
                    <?php 
                    $faqCount = 0;
                    foreach ($FAQS as $group):
                        foreach ($group['items'] as $item):
                            $faqCount++;
                    ?>
                        <div class="faq-card <?= $faqCount === 1 ? 'active' : ''; ?>">
                            <button type="button" class="faq-trigger">
                                <span><?= htmlspecialchars($item['q']); ?></span>
                                <span class="faq-chevron">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </span>
                            </button>
                            <div class="faq-content">
                                <div class="faq-content-inner">
                                    <?= htmlspecialchars($item['a']); ?>
                                </div>
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
         FOOTER (URBAN COMPANY INSPIRED DEEP NAVY)
         ======================================================================= -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-top-grid">
                <!-- Col 1: Brand & Helpline -->
                <div class="footer-brand">
                    <h4>Kaam<span>Mitra</span></h4>
                    <p>India's trusted home service partner for plumbing, electrical, carpentry, AC repair, and deep cleaning. Professionalism and trust delivered to your doorstep.</p>
                    
                    <div class="footer-helpline-card">
                        <span>24/7 Helpline & Express Booking</span>
                        <strong><?= APP_PHONE_DISPLAY; ?></strong>
                    </div>
                </div>

                <!-- Col 2: Services -->
                <div>
                    <h5 class="footer-column-heading">Popular Services</h5>
                    <ul class="footer-links-list">
                        <li><a href="#services" class="trigger-booking-modal" data-service-cat="plumbing">Plumbing Repairs</a></li>
                        <li><a href="#services" class="trigger-booking-modal" data-service-cat="electrical">Electrical Wiring & Fan</a></li>
                        <li><a href="#services" class="trigger-booking-modal" data-service-cat="appliances">AC Foam-Jet Servicing</a></li>
                        <li><a href="#services" class="trigger-booking-modal" data-service-cat="carpentry">Carpentry & Lock Fix</a></li>
                        <li><a href="#services" class="trigger-booking-modal" data-service-cat="cleaning">Bathroom Deep Clean</a></li>
                        <li><a href="#services" class="trigger-booking-modal" data-service-cat="painting">Waterproofing & Paint</a></li>
                    </ul>
                </div>

                <!-- Col 3: Company -->
                <div>
                    <h5 class="footer-column-heading">Company</h5>
                    <ul class="footer-links-list">
                        <li><a href="#howItWorks">How It Works</a></li>
                        <li><a href="#whyUs">Why KaamMitra</a></li>
                        <li><a href="#reviews">Customer Stories</a></li>
                        <li><a href="#partnerSection" class="trigger-partner-modal">Join as Service Mitra</a></li>
                        <li><a href="#faq">Help & FAQ</a></li>
                        <li><a href="https://www.kaammitra.in" target="_blank" rel="noopener">www.kaammitra.in</a></li>
                    </ul>
                </div>

                <!-- Col 4: Service Areas -->
                <div>
                    <h5 class="footer-column-heading">Service Hubs</h5>
                    <p style="font-size: 0.85rem; color: #94A3B8; margin-bottom: 1rem;">
                        Available across Mumbai, Delhi NCR, Bengaluru, Pune, Hyderabad, Chennai, Kolkata, and Ahmedabad.
                    </p>
                    <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                        <?php foreach (array_slice($AVAILABLE_CITIES, 0, 6) as $city): ?>
                            <span class="badge" style="background: rgba(255,255,255,0.06); color: #CBD5E1; border: 1px solid rgba(255,255,255,0.1);"><?= htmlspecialchars($city); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom Strip -->
            <div class="footer-bottom-bar">
                <span>&copy; <?= date('Y'); ?> KaamMitra Technologies Pvt. Ltd. All rights reserved. • <a href="https://www.kaammitra.in" style="color: #FF7D26;">www.kaammitra.in</a></span>
                <span>Crafted for Indian Homes • 100% Background Verified Pros</span>
            </div>
        </div>
    </footer>

    <!-- =======================================================================
         CUSTOMER BOOKING MODAL (3-STEP WIZARD)
         ======================================================================= -->
    <div id="bookingModalOverlay" class="modal-overlay" role="dialog" aria-modal="true">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">Book a Verified Mitra</h3>
                <button id="closeBookingModalBtn" class="modal-close-btn" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Stepper Progress Header -->
                <div class="stepper-header">
                    <div id="stepperNode1" class="step-node active">
                        <div class="step-circle">1</div>
                        <span class="step-node-label">Service</span>
                    </div>
                    <div id="stepperNode2" class="step-node">
                        <div class="step-circle">2</div>
                        <span class="step-node-label">Schedule</span>
                    </div>
                    <div id="stepperNode3" class="step-node">
                        <div class="step-circle">3</div>
                        <span class="step-node-label">Confirm</span>
                    </div>
                </div>

                <!-- STEP 1: Service Selection -->
                <div id="modalStep1" class="modal-step-panel active">
                    <div class="form-field-group" style="margin-bottom: 1.25rem;">
                        <label for="bookServiceSelect" class="field-label">Service Category</label>
                        <select id="bookServiceSelect" class="select-control">
                            <?php foreach ($SERVICE_CATEGORIES as $k => $name): ?>
                                <?php if ($k !== 'all'): ?>
                                    <option value="<?= htmlspecialchars($k); ?>"><?= htmlspecialchars($name); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field-group" style="margin-bottom: 1.5rem;">
                        <label for="bookSubServiceSelect" class="field-label">Select Specific Problem / Service</label>
                        <select id="bookSubServiceSelect" class="select-control">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </div>

                    <div style="background: #F8FAFC; border: 1.5px solid #E2E8F0; padding: 1.15rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <div>
                            <span style="font-size: 0.85rem; color: #64748B; display: block;">Standard Inspection & Service Fee:</span>
                            <span style="font-size: 0.775rem; color: #059669; font-weight: 600;">✓ Backed by 30-day rework warranty</span>
                        </div>
                        <strong id="modalPriceValue" style="font-size: 1.45rem; color: #0A1325;">₹199</strong>
                    </div>

                    <div class="modal-footer-nav" style="justify-content: flex-end;">
                        <button type="button" id="btnNextToStep2" class="btn btn-primary">
                            <span>Continue to Schedule</span>
                            <?= renderIcon('check', 16); ?>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Date, Time & Address -->
                <div id="modalStep2" class="modal-step-panel">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-field-group">
                            <label for="bookDateInput" class="field-label">Preferred Date</label>
                            <input type="date" id="bookDateInput" class="form-control" value="<?= date('Y-m-d'); ?>">
                        </div>
                        <div class="form-field-group">
                            <label for="bookTimeSelect" class="field-label">Time Slot</label>
                            <select id="bookTimeSelect" class="select-control">
                                <option value="Express (Within 45 Mins)">⚡ Express (Within 45 Mins)</option>
                                <option value="10:00 AM - 12:00 PM">10:00 AM - 12:00 PM</option>
                                <option value="12:00 PM - 02:00 PM">12:00 PM - 02:00 PM</option>
                                <option value="02:00 PM - 04:00 PM">02:00 PM - 04:00 PM</option>
                                <option value="04:00 PM - 06:00 PM">04:00 PM - 06:00 PM</option>
                                <option value="06:00 PM - 08:00 PM">06:00 PM - 08:00 PM</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-field-group">
                            <label for="bookCitySelect" class="field-label">City</label>
                            <select id="bookCitySelect" class="select-control">
                                <?php foreach ($AVAILABLE_CITIES as $city): ?>
                                    <option value="<?= htmlspecialchars($city); ?>"><?= htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field-group">
                            <label for="bookPincodeInput" class="field-label">Pincode</label>
                            <input type="text" id="bookPincodeInput" class="form-control" placeholder="e.g. 400076" maxlength="6">
                        </div>
                    </div>

                    <div class="form-field-group" style="margin-bottom: 1.5rem;">
                        <label for="bookAddressInput" class="field-label">Complete Doorstep Address</label>
                        <input type="text" id="bookAddressInput" class="form-control" placeholder="e.g. 1201, Cliff Ave, Hiranandani Gardens, Powai">
                    </div>

                    <div class="modal-footer-nav">
                        <button type="button" id="btnBackToStep1" class="btn btn-outline-navy btn-sm">Back</button>
                        <button type="button" id="btnNextToStep3" class="btn btn-primary">
                            <span>Continue to Contact</span>
                            <?= renderIcon('check', 16); ?>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: Contact & Review -->
                <div id="modalStep3" class="modal-step-panel">
                    <div class="form-field-group" style="margin-bottom: 1rem;">
                        <label for="bookCustomerName" class="field-label">Full Name</label>
                        <input type="text" id="bookCustomerName" class="form-control" placeholder="e.g. Rahul Sharma">
                    </div>

                    <div class="form-field-group" style="margin-bottom: 1.25rem;">
                        <label for="bookCustomerPhone" class="field-label">Mobile Number</label>
                        <input type="tel" id="bookCustomerPhone" class="form-control" placeholder="10-digit mobile number, e.g. 9876543210" maxlength="10">
                    </div>

                    <div class="form-field-group" style="margin-bottom: 1.25rem;">
                        <label for="bookCustomerNotes" class="field-label">Optional Problem Details</label>
                        <input type="text" id="bookCustomerNotes" class="form-control" placeholder="e.g. AC cooling low and making sound">
                    </div>

                    <!-- Review Card -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.875rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: #64748B;">Service:</span>
                            <strong id="reviewServiceSummary" style="color: #0A1325;">-</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: #64748B;">Schedule:</span>
                            <span id="reviewScheduleSummary" style="color: #0A1325;">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: #64748B;">Location:</span>
                            <span id="reviewLocationSummary" style="color: #0A1325;">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed #CBD5E1; padding-top: 0.5rem; margin-top: 0.5rem;">
                            <strong>Total Estimated:</strong>
                            <strong id="reviewTotalEstimate" style="color: #E65100; font-size: 1.15rem;">₹199</strong>
                        </div>
                    </div>

                    <div class="modal-footer-nav">
                        <button type="button" id="btnBackToStep2" class="btn btn-outline-navy btn-sm">Back</button>
                        <button type="button" id="btnSubmitBooking" class="btn btn-primary">
                            <span>Confirm & Book Mitra</span>
                            <?= renderIcon('check', 16); ?>
                        </button>
                    </div>
                </div>

                <!-- CONFIRMATION PANEL -->
                <div id="modalConfirmationPanel" class="modal-step-panel">
                    <div style="text-align: center; padding: 1.5rem 0;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                            <?= renderIcon('check', 34); ?>
                        </div>
                        <h4 style="font-size: 1.45rem; color: #0A1325; margin-bottom: 0.45rem;">Booking Confirmed!</h4>
                        <p style="color: #64748B; font-size: 0.925rem;">A verified specialist has been assigned to your service request.</p>

                        <div style="display: inline-block; background: #F1F5F9; color: #0A1325; border: 1.5px dashed #0A1325; padding: 0.45rem 1.15rem; border-radius: 10px; font-weight: 700; font-size: 0.95rem; margin: 1rem 0 1.5rem;">
                            Booking ID: <span id="confBookingId">KM-202610-XXXX</span>
                        </div>

                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1.25rem; text-align: left; margin-bottom: 1.5rem; font-size: 0.9rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.45rem;">
                                <span style="color: #64748B;">Customer:</span>
                                <strong id="confCustomerName" style="color: #0A1325;">-</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.45rem;">
                                <span style="color: #64748B;">Assigned Mitra:</span>
                                <strong id="confMitraName" style="color: #E65100;">-</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.45rem;">
                                <span style="color: #64748B;">Rating:</span>
                                <span id="confMitraRating">-</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.45rem;">
                                <span style="color: #64748B;">Slot:</span>
                                <span id="confSlot">-</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed #CBD5E1; padding-top: 0.5rem; margin-top: 0.5rem;">
                                <span>Pay After Service:</span>
                                <strong id="confAmount" style="color: #059669;">-</strong>
                            </div>
                        </div>

                        <button type="button" id="btnBookingDone" class="btn btn-navy btn-full">
                            Done & Back to Home
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =======================================================================
         PARTNER REGISTRATION MODAL
         ======================================================================= -->
    <div id="partnerModalOverlay" class="modal-overlay" role="dialog" aria-modal="true">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">Join as a KaamMitra Partner</h3>
                <button id="closePartnerModalBtn" class="modal-close-btn" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="partnerFormSection">
                    <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 1.5rem;">
                        Earn ₹35,000 to ₹55,000/month with steady verified bookings in your city. 0% commission for your first 30 days!
                    </p>

                    <form id="partnerRegisterForm">
                        <div class="form-field-group" style="margin-bottom: 1rem;">
                            <label for="partnerNameInput" class="field-label">Full Legal Name (as per Aadhaar)</label>
                            <input type="text" id="partnerNameInput" class="form-control" placeholder="e.g. Ramesh Kumar" required>
                        </div>

                        <div class="form-field-group" style="margin-bottom: 1rem;">
                            <label for="partnerPhoneInput" class="field-label">Mobile Number</label>
                            <input type="tel" id="partnerPhoneInput" class="form-control" placeholder="10-digit mobile number" maxlength="10" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-field-group">
                                <label for="partnerTradeSelect" class="field-label">Primary Skill / Trade</label>
                                <select id="partnerTradeSelect" class="select-control" required>
                                    <option value="Electrician">Master Electrician</option>
                                    <option value="Plumber">Certified Plumber</option>
                                    <option value="AC Technician">AC & Appliance Specialist</option>
                                    <option value="Carpenter">Carpenter & Woodworker</option>
                                    <option value="Home Cleaner">Deep Cleaning Specialist</option>
                                    <option value="Painter">Painter & Waterproofing</option>
                                </select>
                            </div>
                            <div class="form-field-group">
                                <label for="partnerExpSelect" class="field-label">Experience</label>
                                <select id="partnerExpSelect" class="select-control">
                                    <option value="1-3 Years">1 - 3 Years</option>
                                    <option value="3-5 Years">3 - 5 Years</option>
                                    <option value="5-10 Years">5 - 10 Years</option>
                                    <option value="10+ Years">10+ Years</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-field-group">
                                <label for="partnerCitySelect" class="field-label">City</label>
                                <select id="partnerCitySelect" class="select-control">
                                    <?php foreach ($AVAILABLE_CITIES as $city): ?>
                                        <option value="<?= htmlspecialchars($city); ?>"><?= htmlspecialchars($city); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field-group">
                                <label for="partnerAadhaarInput" class="field-label">Aadhaar (Last 4 Digits)</label>
                                <input type="text" id="partnerAadhaarInput" class="form-control" placeholder="e.g. 5421" maxlength="4">
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitPartner" class="btn btn-primary btn-lg btn-full">
                            <span>Submit Partner Application</span>
                            <?= renderIcon('check', 18); ?>
                        </button>
                    </form>
                </div>

                <div id="partnerConfirmationSection" style="display: none; text-align: center; padding: 1.5rem 0;">
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: #FFF5EE; color: #E65100; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                        <?= renderIcon('check', 34); ?>
                    </div>
                    <h4 style="font-size: 1.45rem; color: #0A1325; margin-bottom: 0.45rem;">Application Received!</h4>
                    <p style="color: #64748B; font-size: 0.925rem;">Welcome to the KaamMitra partner ecosystem.</p>

                    <div style="display: inline-block; background: #FFF5EE; color: #E65100; border: 1.5px dashed #FF7D26; padding: 0.45rem 1.15rem; border-radius: 10px; font-weight: 700; font-size: 0.95rem; margin: 1rem 0 1.5rem;">
                        Partner Ref: <span id="confPartnerId">MITRA-10293</span>
                    </div>

                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1.25rem; text-align: left; margin-bottom: 1.5rem; font-size: 0.9rem;">
                        <p style="margin-bottom: 0.4rem;">Applicant: <strong id="confPartnerName" style="color: #0A1325;">-</strong></p>
                        <p style="margin-bottom: 0.4rem;">Trade: <strong id="confPartnerTrade" style="color: #0A1325;">-</strong></p>
                        <p style="margin-bottom: 0.4rem;">City: <strong id="confPartnerCity" style="color: #0A1325;">-</strong></p>
                        <p style="color: #059669; font-weight: 600; margin-top: 0.65rem;">
                            ✓ Our coordinator will call you within 4 hours to verify documents and dispatch your free toolkit.
                        </p>
                    </div>

                    <button type="button" id="btnPartnerDone" class="btn btn-navy btn-full">
                        Close & Back to Site
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- =======================================================================
         MOBILE STICKY BOTTOM ACTION BAR
         ======================================================================= -->
    <div class="mobile-bottom-bar" aria-label="Mobile Actions">
        <div class="mobile-bottom-inner">
            <a href="tel:<?= APP_PHONE; ?>" class="btn btn-outline-navy" style="flex: 1; padding: 0.65rem 0.5rem; border-radius: 12px;">
                <?= renderIcon('phone', 16); ?>
                <span>Call Help</span>
            </a>
            <button class="btn btn-primary trigger-booking-modal" data-service-cat="plumbing" style="flex: 2; padding: 0.65rem 1rem; border-radius: 12px;">
                <span>Book a Service</span>
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.KM_SERVICES = <?= json_encode($SERVICES_DATA); ?>;
    </script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/booking.js"></script>
    <script src="assets/js/partner.js"></script>
</body>
</html>
