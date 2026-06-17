<?php
/**
 * DivineShield - System Security Configuration Panel
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$success = '';
$error = '';

// Process security update form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_security'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPin = trim($_POST['new_pin'] ?? '');
    
    // Validate inputs
    if (strlen($newPin) !== 4 || !is_numeric($newPin)) {
        $error = "Admin PIN must be exactly 4 digits.";
    } else {
        try {
            // Verify password first
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $hash = $stmt->fetchColumn();
            
            if ($hash && password_verify($currentPassword, $hash)) {
                // Password is correct, update PIN
                $stmtUpdate = $pdo->prepare("UPDATE users SET admin_pin = ? WHERE id = ?");
                $stmtUpdate->execute([$newPin, $_SESSION['user_id']]);
                
                logAudit($pdo, $_SESSION['user_id'], 'ADMIN_PIN_CHANGED', "Administrator updated Two-Step PIN settings on Security page.");
                $success = "Security settings updated successfully! Your new 4-digit PIN is active.";
            } else {
                $error = "Incorrect password. Security credentials verification failed.";
            }
        } catch (Exception $e) {
            $error = "System error: " . $e->getMessage();
        }
    }
}

// Fetch admin profile picture and current PIN for form
$stmtAdmin = $pdo->prepare("SELECT admin_pin, profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminUser = $stmtAdmin->fetch();
$adminProfilePic = $adminUser['profile_picture'] ?? null;
$currentPin = $adminUser['admin_pin'] ?? '';

$pageTitle = "System Security";
include 'includes/header.php';
?>

<?php if ($success): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:24px;">
    <i class="fas fa-circle-check"></i>
    <div><strong>Success</strong> <span><?php echo htmlspecialchars($success); ?></span></div>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:24px;">
    <i class="fas fa-circle-exclamation"></i>
    <div><strong>Error</strong> <span><?php echo htmlspecialchars($error); ?></span></div>
  </div>
<?php endif; ?>

<!-- KPI Row -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-box">
        <div class="stat-box-info">
            <h4>MFA Security Status</h4>
            <div class="stat-val" style="color:var(--teal-400);">Enabled</div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">Two-step PIN verification active</p>
        </div>
        <div class="stat-box-icon" style="color:var(--teal-400); background:rgba(45,212,191,0.1);">
            <i class="fas fa-shield-check"></i>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-info">
            <h4>Recent Audit Alerts</h4>
            <div class="stat-val">0</div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">No security warnings flagged</p>
        </div>
        <div class="stat-box-icon" style="color:var(--blue-400); background:rgba(59,130,246,0.1);">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
    </div>
</div>

<!-- Main security sections -->
<div style="display:flex; flex-wrap:wrap; gap:24px;">
    <!-- Left Column: MFA PIN configuration -->
    <div class="dashboard-card" style="flex:1; min-width:320px; padding:28px;">
        <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom: 20px;">
            <h3 class="dashboard-card-title">MFA Authentication PIN</h3>
        </div>

        <form action="security.php" method="POST" autocomplete="off" style="margin-top:16px;">
            <input type="hidden" name="update_security" value="1">

            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Two-Step Verification PIN Code</label>
                <div style="display:flex; align-items:center; gap:12px;">
                    <input type="password" name="new_pin" maxlength="4" value="<?php echo htmlspecialchars($currentPin); ?>" placeholder="4-digit numeric code" class="auth-input" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:150px; font-size:1.15rem; letter-spacing:0.3em; text-align:center; height:46px;" required>
                    <span style="font-size:0.8rem; color:var(--gray-400);">Must be a numeric 4-digit code.</span>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:28px; border-top:1px solid rgba(255,255,255,0.06); padding-top:20px;">
                <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Verify Admin Password *</label>
                <input type="password" name="current_password" class="auth-input" placeholder="Enter password to authorize changes" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; height:46px; justify-content:center;">
                <i class="fas fa-lock"></i> Update Security Credentials
            </button>
        </form>
    </div>

    <!-- Right Column: Policies and Lockouts -->
    <div class="dashboard-card" style="flex:1.2; min-width:320px; padding:28px;">
        <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom: 20px;">
            <h3 class="dashboard-card-title">Access Policy Rules</h3>
        </div>

        <div class="form-group" style="margin-bottom:20px; margin-top:16px;">
            <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Lockout Threshold</label>
            <select class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;">
                <option value="5">5 failed attempts (Recommended)</option>
                <option value="3">3 failed attempts (Strict)</option>
                <option value="10">10 failed attempts</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:20px;">
            <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Session Idle Timeout</label>
            <select class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;">
                <option value="30">30 minutes</option>
                <option value="60" selected>1 hour (Default)</option>
                <option value="120">2 hours</option>
            </select>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
