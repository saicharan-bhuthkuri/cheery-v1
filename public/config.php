<?php
/**
 * KaamMitra - Configuration File
 */

if (!defined('APP_NAME')) define('APP_NAME', 'KaamMitra');
if (!defined('APP_TAGLINE')) define('APP_TAGLINE', 'Your Trusted Home Services Partner');
if (!defined('APP_PHONE')) define('APP_PHONE', '1800-522-6648');
if (!defined('APP_PHONE_DISPLAY')) define('APP_PHONE_DISPLAY', '1800-KAAM-MITRA');
if (!defined('APP_EMAIL')) define('APP_EMAIL', 'support@kaammitra.in');
if (!defined('APP_CURRENCY')) define('APP_CURRENCY', '₹');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');

if (!function_exists('getAppBasePath')) {
    function getAppBasePath(): string {
        return './';
    }
}

$AVAILABLE_CITIES = [
    'Delhi NCR',
    'Mumbai',
    'Bengaluru',
    'Pune',
    'Hyderabad',
    'Chennai',
    'Kolkata',
    'Ahmedabad',
    'Jaipur',
    'Chandigarh'
];

$PLATFORM_STATS = [
    ['count' => '50,000+', 'label' => 'Happy Homes Served', 'icon' => 'home'],
    ['count' => '4.9 ★', 'label' => 'Average Rating', 'icon' => 'star'],
    ['count' => '3,500+', 'label' => 'Verified Service Mitras', 'icon' => 'users'],
    ['count' => '45 Mins', 'label' => 'Average Arrival Time', 'icon' => 'clock'],
    ['count' => '100%', 'label' => 'Background Verified', 'icon' => 'shield-check'],
    ['count' => '30 Days', 'label' => 'Service Warranty', 'icon' => 'badge-check']
];
