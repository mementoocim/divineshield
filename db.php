<?php
/**
 * database config
 * Uses PDO for secure, prepared SQL execution.
 */

$host = '127.0.0.1';
$db = 'divineshield_db';
$user = 'root';
$pass = ''; // Default XAMPP MySQL password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
// create system_configurations table if missing
try {
    $pdo->query("SELECT 1 FROM system_configurations LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_configurations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(100) NOT NULL UNIQUE,
            config_value TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $ex) {
        // Fail silently
    }
}

// Ensure all default configuration keys are populated
try {
    $defaults = [
        'lockout_threshold' => '5',
        'session_timeout' => '60',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_user' => 'mainpilalauanan@gmail.com',
        'smtp_pass' => 'uoel eiwn gvxv godj',
        'smtp_encryption' => 'tls',
        'pw_min_length' => '8',
        'pw_req_number' => '1',
        'pw_req_special' => '1',
        'pw_req_case' => '1',
        'log_retention_days' => '90',
        'allow_public_registration' => '1',
        'require_admin_approval' => '1',
        'email_approval_subject' => 'Your DivineShield Account Has Been Approved',
        'email_approval_body' => "Welcome, Pastor {first_name}!\n\nWe are pleased to inform you that your church leader account on DivineShield has been reviewed and approved by our system administrator. Your account is now fully active.\n\nUsername: @{username}\nStatus: Active ✔\n\nYou may now log in to the DivineShield portal to manage your church site, submit child beneficiary records, and access all features available to church leaders.\n\nGod bless your ministry,\nThe DivineShield Team",
        'email_rejection_subject' => 'Your DivineShield Registration Status Update',
        'email_rejection_body' => "Dear Pastor {first_name},\n\nThank you for your interest in joining the DivineShield platform. After careful review, we regret to inform you that your church leader registration has not been approved at this time.\n\nUsername: @{username}\nStatus: Not Approved ✖\n\nThis may be due to incomplete information, unverified credentials, or other administrative reasons. If you believe this is an error or would like to clarify your registration details, please contact our support team.\n\nGod bless,\nThe DivineShield Team",
        'email_new_reg_subject' => 'New Church Leader Registration Pending Approval',
        'email_new_reg_body' => "A new church leader has registered and is awaiting your review.\n\n👤 LEADER INFORMATION:\nName: Pastor {first_name} {last_name}\nPosition: {position_title}\nUsername: @{username}\nEmail: {email}\nPhone: {phone}\n\n⛪ SITE INFORMATION:\nChurch Site: {church_name}\nMessage to Admin: {admin_message}\n\nPlease log in to the admin portal to review this pending registration."
    ];
    
    $insertStmt = $pdo->prepare("INSERT INTO system_configurations (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_key=config_key");
    foreach ($defaults as $key => $val) {
        $insertStmt->execute([$key, $val]);
    }
} catch (Exception $ex) {
    // Fail silently
}

/**
 * Get a configuration value from the system_configurations table.
 */
function getSystemConfig($pdo, $key, $default = null)
{
    try {
        $stmt = $pdo->prepare("SELECT config_value FROM system_configurations WHERE config_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Set or update a configuration value in the system_configurations table.
 */
function setSystemConfig($pdo, $key, $value)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO system_configurations (config_key, config_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE config_value = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Log an action into the system audit_logs table.
 * 
 * @param PDO $pdo The database connection instance.
 * @param int|null $userId ID of the user performing the action.
 * @param string $action The shorthand code for action (e.g. LOGIN_SUCCESS).
 * @param string $details A human-readable description of what occurred.
 */
function logAudit($pdo, $userId, $action, $details)
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // fail silently to avoid breaking main flow
    }
}

// auto schema update: add profile_picture column

try {
    $pdo->query("SELECT profile_picture FROM users LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL AFTER admin_pin");
    } catch (Exception $alterEx) {
        // ignore migration errors
    }
}

// create staff_qr_tokens table if missing
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

// create staff_attendance table if missing
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

// add check_out_time to staff_attendance
try {
    $pdo->query("SELECT check_out_time FROM staff_attendance LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE staff_attendance ADD COLUMN check_out_time TIMESTAMP NULL DEFAULT NULL AFTER check_in_time");
    } catch (Exception $ex) {
        // Fail silently
    }
}

