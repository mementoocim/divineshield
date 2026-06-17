<?php
/**
 * DivineShield - Database Connection Configuration
 * Uses PDO for secure, prepared SQL execution.
 */

$host = '127.0.0.1';
$db   = 'divineshield_db';
$user = 'root';
$pass = ''; // Default XAMPP MySQL password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

/**
 * Log an action into the system audit_logs table.
 * 
 * @param PDO $pdo The database connection instance.
 * @param int|null $userId ID of the user performing the action.
 * @param string $action The shorthand code for action (e.g. LOGIN_SUCCESS).
 * @param string $details A human-readable description of what occurred.
 */
function logAudit($pdo, $userId, $action, $details) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // Fail silently or handle logger failure to avoid breaking critical actions
    }
}

// ──────────────────────────────────────────
// AUTO SCHEMA UPDATE: ADD PROFILE_PICTURE COLUMN
// ──────────────────────────────────────────
try {
    $pdo->query("SELECT profile_picture FROM users LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL AFTER admin_pin");
    } catch (Exception $alterEx) {
        // Handle migration failure gracefully
    }
}

// Create staff_qr_tokens table if not exists
try {
    $pdo->query("SELECT 1 FROM staff_qr_tokens LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS staff_qr_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL
        ) ENGINE=InnoDB");
    } catch (Exception $ex) {
        // Fail silently
    }
}

// Create staff_attendance table if not exists
try {
    $pdo->query("SELECT 1 FROM staff_attendance LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS staff_attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    } catch (Exception $ex) {
        // Fail silently
    }
}

// Auto schema update: Add check_out_time to staff_attendance table
try {
    $pdo->query("SELECT check_out_time FROM staff_attendance LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE staff_attendance ADD COLUMN check_out_time TIMESTAMP NULL DEFAULT NULL AFTER check_in_time");
    } catch (Exception $ex) {
        // Fail silently
    }
}

