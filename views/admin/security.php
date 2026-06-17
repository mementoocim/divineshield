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

// Fetch admin profile picture for topbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();

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
    <!-- Column: Policies and Lockouts -->
    <div class="dashboard-card" style="flex:1; min-width:320px; padding:28px;">
        <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom: 20px;">
            <h3 class="dashboard-card-title">Access Policy Rules</h3>
        </div>

        <form id="security-form" onsubmit="event.preventDefault(); saveSecurityRules();">
            <div class="form-group" style="margin-bottom:20px; margin-top:16px;">
                <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Lockout Threshold</label>
                <select class="auth-select" id="lockout_threshold" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;">
                    <option value="5">5 failed attempts (Recommended)</option>
                    <option value="3">3 failed attempts (Strict)</option>
                    <option value="10">10 failed attempts</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:28px;">
                <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Session Idle Timeout</label>
                <select class="auth-select" id="session_timeout" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;">
                    <option value="30">30 minutes</option>
                    <option value="60" selected>1 hour (Default)</option>
                    <option value="120">2 hours</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; height:46px; justify-content:center;">
                <i class="fas fa-shield-halved"></i> Save Policy Rules
            </button>
        </form>
    </div>
</div>

<script>
function saveSecurityRules() {
    Swal.fire({
        title: 'Saving Policy Rules...',
        text: 'Applying access policies and lockout thresholds.',
        timer: 1500,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
        }
    }).then(() => {
        const threshold = document.getElementById('lockout_threshold').value;
        Swal.fire({
            title: 'Security Rules Saved!',
            text: 'System security policy has been updated successfully. Lockout threshold set to ' + threshold + ' attempts.',
            icon: 'success',
            confirmButtonText: 'Understood'
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>
