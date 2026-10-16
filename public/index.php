<?php
/**
 * KaamMitra - Modern Marketplace Landing Page
 * Completely focused public showcase for on-demand home repairs & services
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/data/services.php';
require_once __DIR__ . '/data/features.php';
require_once __DIR__ . '/data/testimonials.php';
require_once __DIR__ . '/data/faqs.php';
require_once __DIR__ . '/components/icons.php';

$base = function_exists('getAppBasePath') ? getAppBasePath() : './';
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
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <script src="assets/js/lucide.min.js"></script>
</head>
<body>

    <?php require __DIR__ . "/components/navbar-main.php"; ?>

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
                            <div class="hero-eyebrow-pill" id="locationStatusPill">
                                <span class="pulse-indicator" id="locationPulse"></span>
                                <span id="locationBadgeText">On-Demand Doorstep Repairs • 30-Min Rapid Dispatch</span>
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
                                <a href="auth/partner/login.php?redirect=index.php#services" class="btn btn-primary btn-sm hero-find-btn" style="text-decoration: none;">
                                    <span class="find-btn-text">Explore Services</span>
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
                            <p class="hero-pro-desc" id="heroProDesc">Master Electrician &amp; HVAC Pro • 4.95 ★ (1,250+ verified doorstep jobs completed)</p>

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
                                <button type="button" class="bento-book-btn trigger-category-filter" data-cat="appliances">Explore Service</button>
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
                                <button type="button" class="bento-book-btn trigger-category-filter" data-cat="plumbing">Explore Service</button>
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
                                <button type="button" class="bento-book-btn trigger-category-filter" data-cat="cleaning">Explore Service</button>
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
                                <button type="button" class="bento-book-btn trigger-category-filter" data-cat="electrical">Explore Service</button>
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
                <!-- Live Service Availability Banner -->
                <div id="serviceCoverageBanner" class="service-coverage-banner" style="display: none;">
                    <div class="service-coverage-inner">
                        <div class="service-coverage-left">
                            <span class="coverage-dot" id="coverageDot"></span>
                            <div>
                                <strong id="coverageTitle" class="coverage-title">Checking service availability in your area...</strong>
                                <span id="coverageSubtitle" class="coverage-sub">Doorstep dispatch available across supported metro hubs.</span>
                            </div>
                        </div>
                        <div class="service-coverage-right" id="coverageMeta"></div>
                    </div>
                </div>

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
                                            <i data-lucide="receipt" style="width: 14px; height: 14px; color: var(--brand-coral, #FF5500);"></i>
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
                                                    <a href="tel:<?= APP_PHONE; ?>" class="rate-card-book-btn" title="Call Helpline to Book" style="text-decoration: none;">
                                                        Book
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
                                <a href="tel:<?= APP_PHONE; ?>" class="btn btn-primary btn-sm" style="text-decoration: none;">
                                    <span>Book via Helpline</span>
                                    <i data-lucide="phone" style="width: 14px; height: 14px;"></i>
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
             CONTACT & 24/7 HELPLINE ASSISTANCE SECTION
             =================================================================== -->
        <section class="section" id="contact" style="padding-top: 1rem;">
            <div class="container">
                <div class="partner-editorial-card">
                    <div>
                        <span class="section-badge" style="background: rgba(255, 85, 0, 0.2); border-color: rgba(255, 85, 0, 0.4); color: #FF7D26;">24/7 Doorstep Assistance</span>
                        <h2 class="partner-editorial-title">
                            Need Fast Assistance? <span>Call Our 24/7 Helpline</span>
                        </h2>
                        <p class="partner-editorial-desc">
                            Speak directly with our centralized customer support desk for express technician dispatch, custom repair estimates, or transparent rate quotes across all 10 serviced metros.
                        </p>

                        <div class="partner-perks-row">
                            <div class="perk-pill">
                                <i data-lucide="phone" style="width: 18px; height: 18px;"></i>
                                <span>Toll-Free Helpline: <?= defined('APP_PHONE_DISPLAY') ? APP_PHONE_DISPLAY : '1800-KAAM-MITRA'; ?></span>
                            </div>
                            <div class="perk-pill">
                                <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                                <span>Zero Advance • Pay After Service</span>
                            </div>
                            <div class="perk-pill">
                                <i data-lucide="clock" style="width: 18px; height: 18px;"></i>
                                <span>⚡ 30-Minute Guaranteed Doorstep Arrival</span>
                            </div>
                            <div class="perk-pill">
                                <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
                                <span>100% Background-Verified Mitras</span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="tel:<?= defined('APP_PHONE') ? APP_PHONE : '1800-522-6648'; ?>" class="btn btn-primary btn-lg" style="text-decoration: none;">
                                <i data-lucide="phone-call" style="width: 18px; height: 18px;"></i>
                                <span>Call <?= defined('APP_PHONE_DISPLAY') ? APP_PHONE_DISPLAY : '1800-KAAM-MITRA'; ?></span>
                            </a>
                            <a href="auth/partner/partner-register.php" class="btn btn-outline btn-lg" style="color: #FFFFFF; border-color: rgba(255,255,255,0.4); text-decoration: none; background: rgba(255,255,255,0.12); font-weight: 700;">
                                <i data-lucide="briefcase" style="width: 18px; height: 18px;"></i>
                                <span>Join as a Service Provider</span>
                            </a>
                            <a href="mailto:<?= defined('APP_EMAIL') ? APP_EMAIL : 'support@kaammitra.in'; ?>" class="btn btn-subtle btn-lg" style="color: rgba(255,255,255,0.85); text-decoration: none;">
                                <i data-lucide="mail" style="width: 18px; height: 18px;"></i>
                                <span>Email Support</span>
                            </a>
                        </div>
                    </div>

                    <div class="partner-earnings-widget">
                        <div class="earnings-label">Support Response Time</div>
                        <div class="earnings-amount">&lt; 60 Secs</div>
                        <div class="earnings-sub">✓ 24 Hours • 7 Days a Week • Zero Waiting</div>
                        <p style="font-size: 0.825rem; color: rgba(255, 255, 255, 0.65); line-height: 1.5;">
                            Direct doorstep technician dispatch across Delhi NCR, Mumbai, Bengaluru, Pune, Hyderabad, Chennai, Kolkata, Ahmedabad, Jaipur, and Chandigarh.
                        </p>
                    </div>
                </div>
            </div>
        </section>

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
                            <i data-lucide="shield-check" style="width: 20px; height: 20px; color: var(--accent-emerald, #10B981);"></i>
                            <span class="stat-num">100%</span>
                        </div>
                        <span class="trust-bar-sub">Police &amp; Aadhaar Background Verified</span>
                    </div>

                    <div class="trust-bar-divider"></div>

                    <div class="trust-bar-item">
                        <div class="trust-bar-stat">
                            <i data-lucide="clock" style="width: 20px; height: 20px; color: var(--brand-coral, #FF5500);"></i>
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
                                        <span>Verified Service</span>
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

    <?php require __DIR__ . "/components/footer-main.php"; ?>

    <!-- Global Data & Scripts -->
    <script>
        window.KM_SERVICES = <?= json_encode($SERVICES_DATA); ?>;
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
