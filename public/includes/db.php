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

        // 3. Ensure Admins Table Exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admins` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `email` VARCHAR(150) DEFAULT NULL,
                `full_name` VARCHAR(150) DEFAULT NULL,
                `mobile` VARCHAR(20) DEFAULT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `last_login_at` DATETIME DEFAULT NULL,
                INDEX (`username`),
                INDEX (`role`),
                INDEX (`is_active`),
                INDEX (`mobile`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Ensure Admin Audit Logs Table Exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `admin_id` INT NULL,
                `admin_username` VARCHAR(100) NULL,
                `action` VARCHAR(100) NOT NULL,
                `target_type` VARCHAR(50) DEFAULT NULL,
                `target_id` VARCHAR(100) DEFAULT NULL,
                `details` TEXT DEFAULT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `user_agent` VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (`admin_id`),
                INDEX (`action`),
                INDEX (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Ensure Admin Login Attempts Table Exists (Rate Limiting)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `ip_address` VARCHAR(45) NOT NULL,
                `username` VARCHAR(100) NOT NULL,
                `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `is_success` TINYINT(1) NOT NULL DEFAULT 0,
                INDEX (`ip_address`, `attempt_time`),
                INDEX (`username`, `attempt_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. Safe Column Additions for Existing Tables
        $columnsToCheck = [
            'customers' => [
                'terms_accepted' => "TINYINT(1) NOT NULL DEFAULT 0",
                'terms_accepted_at' => "DATETIME DEFAULT NULL"
            ],
            'service_providers' => [
                'terms_accepted' => "TINYINT(1) NOT NULL DEFAULT 0",
                'terms_accepted_at' => "DATETIME DEFAULT NULL"
            ],
            'admins' => [
                'admin_code' => "VARCHAR(30) NULL UNIQUE",
                'mobile' => "VARCHAR(20) NULL DEFAULT NULL",
                'last_login_at' => "DATETIME DEFAULT NULL",
                'role' => "VARCHAR(50) NOT NULL DEFAULT 'admin'",
                'is_active' => "TINYINT(1) NOT NULL DEFAULT 1",
                'is_protected' => "TINYINT(1) NOT NULL DEFAULT 0",
                'system_tag' => "VARCHAR(50) NULL UNIQUE"
            ]
        ];

        try {
            $pdo->exec("ALTER TABLE `admins` MODIFY COLUMN `email` VARCHAR(150) NULL DEFAULT NULL;");
            $pdo->exec("ALTER TABLE `admins` MODIFY COLUMN `full_name` VARCHAR(150) NULL DEFAULT NULL;");
        } catch (Throwable $e) {}

        foreach ($columnsToCheck as $table => $cols) {
            try {
                $existingCols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($cols as $colName => $colDef) {
                    if (!in_array($colName, $existingCols, true)) {
                        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$colName}` {$colDef};");
                    }
                }
            } catch (Throwable $e) {
                error_log("Column check error for {$table}: " . $e->getMessage());
            }
        }

        // Sync last_login to last_login_at in admins if present
        try {
            $adminCols = $pdo->query("SHOW COLUMNS FROM `admins`")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('last_login', $adminCols, true)) {
                $pdo->exec("UPDATE `admins` SET `last_login_at` = `last_login` WHERE `last_login_at` IS NULL AND `last_login` IS NOT NULL;");
            }
        } catch (Throwable $e) {}

        // Safe reviewed role normalization for existing admin records
        // Charan is the permanent, default Developer with full system access
        try {
            $pdo->exec("UPDATE `admins` SET `role` = 'admin' WHERE `role` NOT IN ('developer', 'super_admin', 'admin');");
        } catch (Throwable $e) {}

        // 7. Initial Protected Developer Account Setup (Charan)
        // Charan is the default immutable Developer of the system.
        try {
            $checkCharan = $pdo->prepare("SELECT `id`, `role`, `is_protected`, `system_tag`, `mobile` FROM `admins` WHERE LOWER(`username`) IN ('charan', 'charan_developer') OR `system_tag` = 'DEVELOPER_ROOT' LIMIT 1");
            $checkCharan->execute();
            $charanRow = $checkCharan->fetch();
            if ($charanRow) {
                // Ensure Charan has developer role, immutable developer flags, official contact info, and admin_code
                $updCharan = $pdo->prepare("
                    UPDATE `admins` 
                    SET `role` = 'developer',
                        `is_protected` = 1,
                        `system_tag` = 'DEVELOPER_ROOT',
                        `is_active` = 1,
                        `username` = 'charan_developer',
                        `admin_code` = 'KM-DEV-001',
                        `full_name` = 'Charan',
                        `mobile` = '6301133468',
                        `email` = 'saicharanbhuthkuri8336@gmail.com'
                    WHERE `id` = ?
                ");
                $updCharan->execute([$charanRow['id']]);
            } else {
                $initPass = getenv('INITIAL_SUPERADMIN_PASSWORD') ?: 'Bharat@8336';
                $initialHash = password_hash($initPass, PASSWORD_DEFAULT);
                $initStmt = $pdo->prepare("
                    INSERT INTO `admins` (`admin_code`, `username`, `email`, `full_name`, `mobile`, `password_hash`, `role`, `is_active`, `is_protected`, `system_tag`, `created_at`)
                    VALUES ('KM-DEV-001', 'charan_developer', 'saicharanbhuthkuri8336@gmail.com', 'Charan', '6301133468', ?, 'developer', 1, 1, 'DEVELOPER_ROOT', NOW())
                ");
                $initStmt->execute([$initialHash]);
            }

            // Sync legacy admin accounts so every admin has an official Admin ID (KM-DEV-001, KM-SADM-XXX, KM-ADM-XXX)
            $existingAdmins = $pdo->query("SELECT `id`, `role`, `admin_code`, `username`, `full_name`, `mobile` FROM `admins` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
            $roleCounters = ['DEV' => 1, 'SADM' => 1, 'ADM' => 1];
            foreach ($existingAdmins as $admRow) {
                $aId = (int)$admRow['id'];
                $aRole = strtolower($admRow['role'] ?? 'admin');
                $prefix = ($aRole === 'developer') ? 'DEV' : (($aRole === 'super_admin' || $aRole === 'superadmin') ? 'SADM' : 'ADM');
                
                $currCode = $admRow['admin_code'] ?? '';
                if (empty($currCode)) {
                    $assignedCode = sprintf("KM-%s-%03d", $prefix, $roleCounters[$prefix]);
                    // Check if code taken
                    while ($pdo->query("SELECT `id` FROM `admins` WHERE `admin_code` = '{$assignedCode}' AND `id` != {$aId}")->fetch()) {
                        $roleCounters[$prefix]++;
                        $assignedCode = sprintf("KM-%s-%03d", $prefix, $roleCounters[$prefix]);
                    }
                    $pdo->prepare("UPDATE `admins` SET `admin_code` = ? WHERE `id` = ?")->execute([$assignedCode, $aId]);
                    $roleCounters[$prefix]++;
                }
            }
        } catch (Throwable $e) {
            error_log("Failed to seed/verify initial Developer Charan: " . $e->getMessage());
        }

        // 8. Enforce Database Engine-Level Security Triggers for Developer Account
        try {
            $pdo->exec("DROP TRIGGER IF EXISTS `trg_admins_protect_developer_delete`");
            $pdo->exec("
                CREATE TRIGGER `trg_admins_protect_developer_delete`
                BEFORE DELETE ON `admins`
                FOR EACH ROW
                BEGIN
                    IF (OLD.role = 'developer' OR OLD.is_protected = 1 OR OLD.system_tag = 'DEVELOPER_ROOT' OR LOWER(OLD.username) IN ('charan', 'charan_developer')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Security Violation: The system Developer account is immutable and cannot be deleted or removed.';
                    END IF;
                END
            ");

            $pdo->exec("DROP TRIGGER IF EXISTS `trg_admins_protect_developer_update`");
            $pdo->exec("
                CREATE TRIGGER `trg_admins_protect_developer_update`
                BEFORE UPDATE ON `admins`
                FOR EACH ROW
                BEGIN
                    -- Protect the developer account from demotion, deactivation, or unprotecting
                    IF (OLD.role = 'developer' OR OLD.is_protected = 1 OR OLD.system_tag = 'DEVELOPER_ROOT' OR LOWER(OLD.username) IN ('charan', 'charan_developer')) THEN
                        IF NEW.role != 'developer' THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: The Developer role cannot be demoted or modified.';
                        END IF;
                        IF NEW.is_active != 1 THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: The Developer account cannot be deactivated.';
                        END IF;
                        IF NEW.is_protected != 1 THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: Developer protection flag cannot be disabled.';
                        END IF;
                        IF NEW.system_tag != 'DEVELOPER_ROOT' THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: Developer system identifier is immutable.';
                        END IF;
                        IF LOWER(NEW.username) NOT IN ('charan', 'charan_developer') THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: Developer username is restricted to authorized developer handles.';
                        END IF;
                    END IF;

                    -- Prevent other accounts from acquiring developer role or DEVELOPER_ROOT system tag
                    IF (OLD.system_tag IS NULL OR OLD.system_tag != 'DEVELOPER_ROOT') AND LOWER(OLD.username) NOT IN ('charan', 'charan_developer') THEN
                        IF NEW.system_tag = 'DEVELOPER_ROOT' THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: Cannot assign DEVELOPER_ROOT system identifier to another account.';
                        END IF;
                        IF NEW.role = 'developer' THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: Cannot promote or assign Developer role to another account.';
                        END IF;
                        IF NEW.is_protected = 1 THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: Protected status is reserved for the system Developer.';
                        END IF;
                        IF LOWER(NEW.username) IN ('charan', 'charan_developer') THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Security Violation: Developer username is reserved.';
                        END IF;
                    END IF;
                END
            ");
        } catch (Throwable $e) {
            error_log("Database trigger registration warning: " . $e->getMessage());
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
