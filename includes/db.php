<?php
/**
 * KaamMitra - Database Configuration & Connection Handler
 */

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'kaammitra_db');

/**
 * Get PDO Database Connection
 * Automatically creates database and tables on first run if not already existing.
 * 
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsnWithoutDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        // First, connect to MySQL server
        $pdoInit = new PDO($dsnWithoutDb, DB_USER, DB_PASS, $options);
        
        // Ensure Database Exists
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Now connect to the KaamMitra Database
        $dsnWithDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);

        // Ensure Customers Table Exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `customers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `full_name` VARCHAR(150) NOT NULL,
                `mobile` VARCHAR(15) NOT NULL UNIQUE,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `gender` ENUM('Male', 'Female', 'Other', 'Prefer not to say') DEFAULT 'Prefer not to say',
                `password_hash` VARCHAR(255) NOT NULL,
                `address_line` VARCHAR(255) DEFAULT NULL,
                `city` VARCHAR(100) DEFAULT NULL,
                `state` VARCHAR(100) DEFAULT NULL,
                `pincode` VARCHAR(10) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`mobile`),
                INDEX (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Ensure Bookings Table Exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `bookings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `booking_reference` VARCHAR(50) NOT NULL UNIQUE,
                `customer_id` INT DEFAULT NULL,
                `customer_name` VARCHAR(150) NOT NULL,
                `phone` VARCHAR(15) NOT NULL,
                `service_category` VARCHAR(100) NOT NULL,
                `sub_service` VARCHAR(150) NOT NULL,
                `city` VARCHAR(100) NOT NULL,
                `address` TEXT NOT NULL,
                `pincode` VARCHAR(10) DEFAULT NULL,
                `service_date` DATE NOT NULL,
                `time_slot` VARCHAR(100) NOT NULL,
                `estimated_price` DECIMAL(10,2) DEFAULT 0.00,
                `status` ENUM('Confirmed', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Confirmed',
                `mitra_name` VARCHAR(100) DEFAULT 'Rajesh Kumar',
                `mitra_rating` DECIMAL(3,2) DEFAULT 4.95,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (`customer_id`),
                INDEX (`booking_reference`),
                CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        throw new RuntimeException("Database connection error: " . $e->getMessage());
    }
}
