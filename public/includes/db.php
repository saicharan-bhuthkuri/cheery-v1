<?php
/**
 * KaamMitra - Database Handler
 * Connects to the application database and queries actual service-provider records.
 */

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'kaammitra_db');

/**
 * Get PDO connection to the KaamMitra database.
 */
function getDBConnection(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 3
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (Throwable $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check service availability using actual approved service-provider records in the database.
 * Does not determine or infer the visitor's location.
 */
function getApprovedProviderCount(?string $city, ?string $locality): int {
    $city = trim($city ?? '');
    $locality = trim($locality ?? '');
    if ($city === '' && $locality === '') {
        return 0;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        return 0;
    }

    try {
        $conditions = [];
        $params = [];

        if ($city !== '') {
            $conditions[] = "LOWER(city) = LOWER(:city)";
            $params[':city'] = $city;
        }

        if ($locality !== '') {
            $conditions[] = "LOWER(service_area) LIKE :locality";
            $params[':locality'] = '%' . strtolower($locality) . '%';
        }

        $sql = "SELECT COUNT(*) FROM service_providers WHERE verification_status = 'Approved' AND (" . implode(' OR ', $conditions) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log("Provider query error: " . $e->getMessage());
        return 0;
    }
}
