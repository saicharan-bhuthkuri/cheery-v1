<?php
/**
 * KaamMitra - Real-Time Geolocation & Live Provider Availability API
 * Reverse-geocodes actual visitor coordinates and queries database for live service availability.
 * Strict no-fabrication: no preset cities, no mocked coordinates, no assumed locations.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/db.php';

// Helper: Secure, Cached Reverse Geocoding with Native SSL
function reverseGeocode(float $lat, float $lng): ?array {
    // 1. Check local file cache (~1.1km grid, 1 hour TTL) to respect Nominatim usage policy
    $roundedLat = round($lat, 2);
    $roundedLng = round($lng, 2);
    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'km_geo_' . md5($roundedLat . '_' . $roundedLng) . '.json';

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
        $cached = file_get_contents($cacheFile);
        if ($cached) {
            $data = json_decode($cached, true);
            if (is_array($data)) {
                return $data;
            }
        }
    }

    // 2. Query OpenStreetMap Nominatim with strict SSL verification
    $url = sprintf(
        'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=%F&lon=%F&zoom=14&addressdetails=1',
        $lat, $lng
    );

    $ch = curl_init();
    $curlOptions = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'KaamMitra-ServicePlatform/1.0 (contact@kaammitra.in)',
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ];

    if (defined('CURLSSLOPT_NATIVE_CA')) {
        $curlOptions[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
    }

    curl_setopt_array($ch, $curlOptions);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    if ($httpCode === 200 && $response) {
        $json = json_decode($response, true);
        if (is_array($json)) {
            @file_put_contents($cacheFile, $response);
            return $json;
        }
    }

    return null;
}

// 1. Parse Input Coordinates
$rawInput = file_get_contents('php://input');
$postData = json_decode($rawInput, true);
if (!is_array($postData)) {
    $postData = $_REQUEST;
}

$latRaw = $postData['lat'] ?? null;
$lngRaw = $postData['lng'] ?? null;
$accuracy = isset($postData['accuracy']) ? floatval($postData['accuracy']) : null;
$clientTimestamp = isset($postData['timestamp']) ? intval($postData['timestamp']) : time();

// 2. Strict Coordinate Validation
if ($latRaw === null || $lngRaw === null || !is_numeric($latRaw) || !is_numeric($lngRaw)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid geographic coordinates (lat, lng) are required.'
    ]);
    exit;
}

$lat = floatval($latRaw);
$lng = floatval($lngRaw);

if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0 || ($lat == 0.0 && $lng == 0.0)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid geographic coordinate range.'
    ]);
    exit;
}

// 3. Reverse Geocode Coordinates
$geoData = reverseGeocode($lat, $lng);
$address = $geoData['address'] ?? [];

$detectedCity = $address['city'] ?? $address['town'] ?? $address['municipality'] ?? $address['city_district'] ?? $address['village'] ?? '';
$detectedLocality = $address['suburb'] ?? $address['neighbourhood'] ?? $address['quarter'] ?? $address['residential'] ?? '';
$detectedState = $address['state'] ?? '';
$detectedCountry = $address['country'] ?? '';

// Clean up administrative ward numbers if present (e.g. "Ward 107 Madhapur" -> "Madhapur")
if ($detectedLocality !== '') {
    $cleanedLocality = trim(preg_replace('/^Ward\s+\d+\s*/i', '', $detectedLocality));
    if ($cleanedLocality !== '') {
        $detectedLocality = $cleanedLocality;
    }
}

// Build display name without fabrication
$displayName = '';
if ($detectedLocality !== '' && $detectedCity !== '' && strtolower($detectedLocality) !== strtolower($detectedCity)) {
    $displayName = $detectedLocality . ', ' . $detectedCity;
} elseif ($detectedCity !== '') {
    $displayName = $detectedCity;
} elseif ($detectedLocality !== '') {
    $displayName = $detectedLocality;
}

// 4. Query Real Database Records for Service Availability
$activeProviders = getApprovedProviderCount($detectedCity, $detectedLocality);
$isServiceable = ($activeProviders > 0);

// 5. Return Verified Response (Visitor's actual coordinates are preserved)
echo json_encode([
    'success' => true,
    'lat' => $lat,
    'lng' => $lng,
    'locality' => $detectedLocality,
    'city' => $detectedCity,
    'state' => $detectedState,
    'country' => $detectedCountry,
    'display_name' => $displayName,
    'serviceable' => $isServiceable,
    'active_providers' => $activeProviders,
    'accuracy_m' => $accuracy,
    'timestamp' => time()
]);
