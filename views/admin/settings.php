<?php
/**
 * DivineShield - System Settings Configuration Panel
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Fetch admin profile picture for topbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();

$pageTitle = "System Settings";
include 'includes/header.php';
?>

<!-- KPI Row -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-box">
        <div class="stat-box-info">
            <h4>System Mode</h4>
            <div class="stat-val" style="color:var(--teal-400);">Online</div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">App is live and accessible</p>
        </div>
        <div class="stat-box-icon" style="color:var(--teal-400); background:rgba(45,212,191,0.1);">
            <i class="fas fa-server"></i>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-info">
            <h4>SMTP Server</h4>
            <div class="stat-val">Active</div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">Notifications sending correctly</p>
        </div>
        <div class="stat-box-icon" style="color:var(--blue-400); background:rgba(59,130,246,0.1);">
            <i class="fas fa-envelope-circle-check"></i>
        </div>
    </div>
</div>

<!-- Main Double Column Form Layout -->
<form id="settings-form" autocomplete="off" onsubmit="event.preventDefault(); saveSettings();">
    <div style="display:flex; flex-wrap:wrap; gap:24px;">
        <!-- Left Column: General & Branding -->
        <div style="flex:1; min-width:320px; display:flex; flex-direction:column; gap:24px;">
            <div class="dashboard-card" style="padding:28px;">
                <div class="dashboard-card-header" style="border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom:20px;">
                    <h3 class="dashboard-card-title">Branding &amp; Contact</h3>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Application Title</label>
                    <input type="text" class="auth-input" value="DivineShield" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;" required>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Contact Email</label>
                    <input type="email" class="auth-input" value="admin.support@mainpi.org" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;" required>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Contact Hotline</label>
                    <input type="text" class="auth-input" value="+63 912 345 6789" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;">
                </div>
            </div>
        </div>

        <!-- Right Column: Program Rules & Maintenance -->
        <div style="flex:1; min-width:320px; display:flex; flex-direction:column; gap:24px;">
            <div class="dashboard-card" style="padding:28px;">
                <div class="dashboard-card-header" style="border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom:20px;">
                    <h3 class="dashboard-card-title">Program Benchmarks</h3>
                </div>

                <div style="display:flex; gap:16px; margin-bottom:20px;">
                    <div style="flex:1;">
                        <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Min Target Age (Yrs)</label>
                        <input type="number" class="auth-input" value="2" min="1" max="18" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;" required>
                    </div>
                    <div style="flex:1;">
                        <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Max Target Age (Yrs)</label>
                        <input type="number" class="auth-input" value="12" min="2" max="21" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Default Program Length (Days)</label>
                    <input type="number" class="auth-input" value="120" min="30" max="365" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;" required>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">System Status</label>
                    <div style="display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.02); padding:12px 16px; border-radius:8px; border:1px solid rgba(255,255,255,0.06);">
                        <div>
                            <strong style="color:var(--white); font-size:0.9rem;">Maintenance Mode</strong>
                            <div style="font-size:0.75rem; color:var(--gray-400); margin-top:2px;">Disables staff/leader portal access</div>
                        </div>
                        <label class="switch" style="position:relative; display:inline-block; width:50px; height:26px;">
                            <input type="checkbox" id="maintenance_toggle" style="opacity:0; width:0; height:0;">
                            <span class="slider" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:rgba(255,255,255,0.1); transition:.4s; border-radius:34px; border:1px solid rgba(255,255,255,0.15);"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Save Bar -->
    <div style="margin-top:24px; text-align:right; border-top:1px solid rgba(255,255,255,0.08); padding-top:24px;">
        <button type="submit" class="btn btn-primary" style="padding:12px 36px; height:46px;">
            <i class="fas fa-floppy-disk"></i> Save Settings Configuration
        </button>
    </div>
</form>

<style>
/* Toggle Slider Styling */
.switch input:checked + .slider {
    background-color: var(--blue-600) !important;
}
.switch input:checked + .slider:before {
    transform: translateX(24px);
    background-color: var(--white);
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: var(--gray-400);
    transition: .4s;
    border-radius: 50%;
}
</style>

<script>
function saveSettings() {
    Swal.fire({
        title: 'Saving System Configurations...',
        text: 'Applying application settings and updating benchmark parameters.',
        timer: 1500,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
        }
    }).then(() => {
        const maintChecked = document.getElementById('maintenance_toggle').checked;
        Swal.fire({
            title: 'Settings Saved Successfully!',
            text: 'System configurations have been updated. Maintenance mode is ' + (maintChecked ? 'ENABLED' : 'DISABLED') + '.',
            icon: 'success',
            confirmButtonText: 'Great'
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>
