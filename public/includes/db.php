<?php
/**
 * KaamMitra - Database Handler & Schema Migration
 * Connects to the SQL database using existing configuration and ensures required schema fields.
 * Safe migration: never deletes or resets existing customer or provider records.
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

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    try {
        // First try connecting to MySQL server directly
        $dsnWithoutDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $pdoInit = new PDO($dsnWithoutDb, DB_USER, DB_PASS, $options);
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Connect to the specific database
        $dsnWithDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);

        // Run safe schema migrations
        ensureDatabaseSchema($pdo);

        return $pdo;
    } catch (Throwable $e) {
        // Fallback: try connecting directly with DB name
        try {
            $dsnWithDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);
            ensureDatabaseSchema($pdo);
            return $pdo;
        } catch (Throwable $e2) {
            error_log("Database Connection Error: " . $e2->getMessage());
            return null;
        }
    }
}

/**
 * Safe database schema verification & migration.
 * Preserves all existing tables and records; only creates missing tables or columns.
 */
function ensureDatabaseSchema(PDO $pdo): void {
    static $migrated = false;
    if ($migrated) return;

    try {
        // 1. Ensure Customers Table Exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `customers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `full_name` VARCHAR(150) NOT NULL,
                `mobile` VARCHAR(15) NOT NULL UNIQUE,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `gender` VARCHAR(50) DEFAULT 'Prefer not to say',
                `password_hash` VARCHAR(255) NOT NULL,
                `address_line` VARCHAR(255) DEFAULT NULL,
                `city` VARCHAR(100) DEFAULT NULL,
                `state` VARCHAR(100) DEFAULT NULL,
                `pincode` VARCHAR(10) DEFAULT NULL,
                `terms_accepted` TINYINT(1) NOT NULL DEFAULT 0,
                `terms_accepted_at` DATETIME DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`mobile`),
                INDEX (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Ensure Service Providers Table Exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `service_providers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `full_name` VARCHAR(150) NOT NULL,
                `mobile` VARCHAR(15) NOT NULL UNIQUE,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `gender` VARCHAR(50) DEFAULT 'Prefer not to say',
                `primary_skill` VARCHAR(100) NOT NULL,
                `experience_years` VARCHAR(50) NOT NULL,
                `city` VARCHAR(100) NOT NULL,
                `service_area` VARCHAR(150) DEFAULT NULL,
                `address` TEXT DEFAULT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `verification_status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
                `rejection_reason` TEXT DEFAULT NULL,
                `terms_accepted` TINYINT(1) NOT NULL DEFAULT 0,
                `terms_accepted_at` DATETIME DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`mobile`),
                INDEX (`email`),
                INDEX (`verification_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Safe Column Additions for Existing Tables
        $columnsToCheck = [
            'customers' => [
                'terms_accepted' => "TINYINT(1) NOT NULL DEFAULT 0",
                'terms_accepted_at' => "DATETIME DEFAULT NULL"
            ],
            'service_providers' => [
                'terms_accepted' => "TINYINT(1) NOT NULL DEFAULT 0",
                'terms_accepted_at' => "DATETIME DEFAULT NULL"
            ]
        ];

        foreach ($columnsToCheck as $table => $cols) {
            foreach ($cols as $colName => $colDef) {
                try {
                    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
                    $stmt->execute([$colName]);
                    if (!$stmt->fetch()) {
                        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$colName}` {$colDef};");
                    }
                } catch (Throwable $e) {
                    // Ignore column check exceptions
                }
            }
        }

        $migrated = true;
    } catch (Throwable $e) {
        error_log("Schema Migration Error: " . $e->getMessage());
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
