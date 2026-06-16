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

