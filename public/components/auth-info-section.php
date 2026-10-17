<?php
/**
 * KaamMitra - Shared Authentication Informational Sidebar Component
 * Premium two-section branding sidebar with live network indicators,
 * verified feature highlights, platform metrics, and social proof.
 *
 * @param string $authType 'customer' | 'partner'
 * @param string $base Base path for asset URLs (defaults to '../../')
 */

$authType = $authType ?? 'customer';
$base = $base ?? '../../';

$isCustomer = ($authType === 'customer');

$brandBadge = $isCustomer ? 'Verified Doorstep Pros' : 'Partner Network • High Earnings';
$brandBadgeIcon = $isCustomer ? 'sparkles' : 'briefcase';

$headline = $isCustomer 
    ? 'Expert Home Services, Delivered to Your Doorstep'
    : 'Grow Your Trade Business with High-Value Local Jobs';

$subtext = $isCustomer
    ? 'Book background-verified electricians, plumbers, AC specialists, and home maintenance craftsmen with 30-day warranty.'
    : 'Join 3,500+ independent technicians earning guaranteed weekly direct payouts with zero hidden commission surprises.';

$liveTag = $isCustomer ? '1,420+ Pros Active Near You' : 'New Job Dispatches Every 5 Mins';

$features = $isCustomer ? [
    [
        'icon'  => 'zap',
        'title' => 'Rapid Doorstep Booking',
        'desc'  => 'Book in 60 seconds with instant technician dispatch & fixed rate cards.'
    ],
    [
        'icon'  => 'shield-check',
        'title' => 'Police & Aadhaar Verified',
        'desc'  => '100% background-checked master craftsmen equipped with genuine parts.'
    ],
    [
        'icon'  => 'navigation',
        'title' => 'Live GPS Arrival Tracking',
        'desc'  => 'Real-time arrival ETA, direct contact, and flexible digital payments.'
    ],
    [
        'icon'  => 'badge-check',
        'title' => '30-Day Free Work Warranty',
        'desc'  => 'Complete satisfaction guaranteed. Re-work is free if anything isn\'t right.'
    ]
] : [
    [
        'icon'  => 'users',
        'title' => 'Steady Local Customers',
        'desc'  => 'Daily confirmed doorstep repair leads from homeowners in your area.'
    ],
    [
        'icon'  => 'wallet',
        'title' => 'Guaranteed Weekly Payouts',
        'desc'  => 'Direct bank deposits every Tuesday with transparent standardized rates.'
    ],
    [
        'icon'  => 'smartphone',
        'title' => 'Easy Mobile Management',
        'desc'  => 'Accept jobs, manage schedule, and navigate directly with zero paperwork.'
    ],
    [
        'icon'  => 'trending-up',
        'title' => 'High Income & Reputation',
        'desc'  => 'Earn ₹45,000+ monthly with 5-star ratings, repeat clients, and bonuses.'
    ]
];

$stats = $isCustomer ? [
    ['value' => '50,000+', 'label' => 'Happy Homes'],
    ['value' => '4.9 ★', 'label' => 'User Rating'],
    ['value' => '28 Mins', 'label' => 'Avg Arrival']
] : [
    ['value' => '₹45k+', 'label' => 'Avg Monthly'],
    ['value' => '3,500+', 'label' => 'Active Mitras'],
    ['value' => 'Weekly', 'label' => 'Direct Payouts']
];

$quote = $isCustomer ? [
    'text'   => 'The technician arrived in 30 minutes with original parts and repaired our AC cooling immediately. Professional and completely transparent.',
    'author' => 'Ananya Sharma',
    'meta'   => 'Verified Homeowner'
] : [
    'text'   => 'KaamMitra transformed my plumbing career. Steady bookings every morning, and money hits my bank account without fail every week.',
    'author' => 'Ramesh Chandra Verma',
    'meta'   => 'Master Electrician • 2 Yrs Partner'
];
?>

<div class="auth-info-pane">
    <!-- Ambient glowing visual backdrops -->
    <div class="auth-info-ambient-glow glow-top"></div>
    <div class="auth-info-ambient-glow glow-bottom"></div>
    <div class="auth-info-grid-pattern"></div>

    <div class="auth-info-content">
        <!-- Brand Header with Logo and Live Indicator -->
        <div class="auth-info-brand">
            <a href="<?= $base; ?>index.php" class="auth-info-brand-link" aria-label="KaamMitra Home">
                <div class="brand-mark auth-brand-mark">
                    <i data-lucide="wrench"></i>
                </div>
                <div class="auth-brand-text">
                    <span class="auth-brand-name">Kaam<span>Mitra</span></span>
                    <span class="auth-brand-tagline">kaammitra.in</span>
                </div>
            </a>

            <div class="auth-live-pill">
                <span class="auth-pulse-dot"></span>
                <span><?= htmlspecialchars($liveTag); ?></span>
            </div>
        </div>

        <!-- Hero Headline & Audience Badge -->
        <div class="auth-info-hero">
            <div class="auth-info-badge">
                <i data-lucide="<?= $brandBadgeIcon; ?>"></i>
                <span><?= htmlspecialchars($brandBadge); ?></span>
            </div>
            <h2 class="auth-info-heading"><?= htmlspecialchars($headline); ?></h2>
            <p class="auth-info-subtext"><?= htmlspecialchars($subtext); ?></p>
        </div>

        <!-- 4 Key Feature Highlights -->
        <div class="auth-feature-list">
            <?php foreach ($features as $f): ?>
                <div class="auth-feature-card">
                    <div class="auth-feature-icon-box">
                        <i data-lucide="<?= $f['icon']; ?>"></i>
                    </div>
                    <div class="auth-feature-details">
                        <h4 class="auth-feature-title"><?= htmlspecialchars($f['title']); ?></h4>
                        <p class="auth-feature-desc"><?= htmlspecialchars($f['desc']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Platform Metric Chips -->
        <div class="auth-metrics-row">
            <?php foreach ($stats as $st): ?>
                <div class="auth-metric-item">
                    <span class="auth-metric-value"><?= htmlspecialchars($st['value']); ?></span>
                    <span class="auth-metric-label"><?= htmlspecialchars($st['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Verified Testimonial Quote -->
        <div class="auth-testimonial-box">
            <div class="auth-testimonial-top">
                <div class="auth-testimonial-stars">
                    <i data-lucide="star"></i>
                    <i data-lucide="star"></i>
                    <i data-lucide="star"></i>
                    <i data-lucide="star"></i>
                    <i data-lucide="star"></i>
                </div>
                <span class="auth-verified-badge"><i data-lucide="shield-check"></i> Verified</span>
            </div>
            <blockquote class="auth-testimonial-quote">
                &ldquo;<?= htmlspecialchars($quote['text']); ?>&rdquo;
            </blockquote>
            <div class="auth-testimonial-author">
                <strong><?= htmlspecialchars($quote['author']); ?></strong>
                <span><?= htmlspecialchars($quote['meta']); ?></span>
            </div>
        </div>
    </div>
</div>
