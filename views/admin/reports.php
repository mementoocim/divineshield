<?php
/**
 * DivineShield - Reports Control Panel
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

// Fetch all sites for dropdown select
$stmtSites = $pdo->query("SELECT id, church_name FROM church_sites ORDER BY church_name ASC");
$churchSites = $stmtSites->fetchAll();

$pageTitle = "Reports";
include 'includes/header.php';
?>

<!-- KPI Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-box">
        <div class="stat-box-info">
            <h4>Generated Reports</h4>
            <div class="stat-val">24</div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">Total exported this month</p>
        </div>
        <div class="stat-box-icon" style="color:var(--blue-400); background:rgba(59,130,246,0.1);">
            <i class="fas fa-file-pdf"></i>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-info">
            <h4>System Audits Cleaned</h4>
            <div class="stat-val">100%</div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">No critical errors flagged</p>
        </div>
        <div class="stat-box-icon" style="color:var(--teal-400); background:rgba(45,212,191,0.1);">
            <i class="fas fa-circle-check"></i>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-info">
            <h4>Export Formats Ready</h4>
            <div class="stat-val">3</div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">PDF, Excel, &amp; CSV templates</p>
        </div>
        <div class="stat-box-icon" style="color:var(--yellow-400); background:rgba(251,191,36,0.1);">
            <i class="fas fa-file-excel"></i>
        </div>
    </div>
</div>

<!-- Main Column Layout -->
<div style="display:flex; flex-wrap:wrap; gap:24px;">
    <!-- LEFT SIDE: Generator Form -->
    <div class="dashboard-card" style="flex:1; min-width:320px; padding:28px;">
        <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom:20px;">
            <h3 class="dashboard-card-title">Generate System Report</h3>
        </div>
        
        <form id="report-generator-form" autocomplete="off" onsubmit="event.preventDefault(); triggerSimulatedExport();">
            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Report Type *</label>
                <select id="report_type" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;" required>
                    <option value="">-- Choose Type --</option>
                    <option value="nutritional">Nutritional Monitoring Report (BMI Records)</option>
                    <option value="attendance">Program Attendance Ledger</option>
                    <option value="beneficiaries">Beneficiary Demographics &amp; Registry</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Church Site</label>
                <select id="site_id" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); width:100%; height:46px;">
                    <option value="">-- All Sites --</option>
                    <?php foreach ($churchSites as $site): ?>
                        <option value="<?php echo $site['id']; ?>"><?php echo htmlspecialchars($site['church_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:16px; margin-bottom:20px;">
                <div style="flex:1;">
                    <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Start Date</label>
                    <input type="date" id="date_start" class="auth-input" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;">
                </div>
                <div style="flex:1;">
                    <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">End Date</label>
                    <input type="date" id="date_end" class="auth-input" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:28px;">
                <label class="form-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Export Format *</label>
                <div style="display:flex; gap:16px;">
                    <label style="display:inline-flex; align-items:center; color:var(--white); cursor:pointer;">
                        <input type="radio" name="format" value="pdf" checked style="margin-right:8px;"> PDF Format
                    </label>
                    <label style="display:inline-flex; align-items:center; color:var(--white); cursor:pointer;">
                        <input type="radio" name="format" value="excel" style="margin-right:8px;"> Excel Workbook
                    </label>
                    <label style="display:inline-flex; align-items:center; color:var(--white); cursor:pointer;">
                        <input type="radio" name="format" value="csv" style="margin-right:8px;"> CSV Raw Data
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; height:46px; justify-content:center;">
                <i class="fas fa-file-export"></i> Compile &amp; Export Report
            </button>
        </form>
    </div>

    <!-- RIGHT SIDE: History Log -->
    <div class="dashboard-card" style="flex:1.5; min-width:400px; padding:28px;">
        <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom:20px;">
            <h3 class="dashboard-card-title">Recent Generated Exports</h3>
        </div>

        <div class="dark-table-wrap">
            <table class="dark-table">
                <thead>
                    <tr>
                        <th>Date Compiled</th>
                        <th>Report Name</th>
                        <th>Type</th>
                        <th>Format</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-size:0.82rem; color:var(--gray-400);">Jun 17, 2026 10:15 AM</td>
                        <td class="fw-semibold text-white">Nutri_Report_SaintNicos</td>
                        <td>Nutritional Status</td>
                        <td><span class="status-badge success" style="padding:2px 8px;"><i class="fas fa-file-pdf"></i> PDF</span></td>
                        <td class="text-right">
                            <button class="btn btn-info btn-sm" onclick="Swal.fire('Download Triggered', 'Simulated file download: Nutri_Report_SaintNicos.pdf', 'success')"><i class="fas fa-download"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:0.82rem; color:var(--gray-400);">Jun 15, 2026 02:40 PM</td>
                        <td class="fw-semibold text-white">Attendance_Ledger_Q2</td>
                        <td>Attendance Logs</td>
                        <td><span class="status-badge warning" style="padding:2px 8px; background:rgba(16,185,129,0.15); color:#34d399; border-color:rgba(16,185,129,0.3);"><i class="fas fa-file-excel"></i> XLSX</span></td>
                        <td class="text-right">
                            <button class="btn btn-info btn-sm" onclick="Swal.fire('Download Triggered', 'Simulated file download: Attendance_Ledger_Q2.xlsx', 'success')"><i class="fas fa-download"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:0.82rem; color:var(--gray-400);">Jun 12, 2026 09:12 AM</td>
                        <td class="fw-semibold text-white">Beneficiary_Registry_Raw</td>
                        <td>Registry list</td>
                        <td><span class="status-badge error" style="padding:2px 8px; background:rgba(99,102,241,0.15); color:#818cf8; border-color:rgba(99,102,241,0.3);"><i class="fas fa-file-csv"></i> CSV</span></td>
                        <td class="text-right">
                            <button class="btn btn-info btn-sm" onclick="Swal.fire('Download Triggered', 'Simulated file download: Beneficiary_Registry_Raw.csv', 'success')"><i class="fas fa-download"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function triggerSimulatedExport() {
    const type = document.getElementById('report_type').value;
    const format = document.querySelector('input[name="format"]:checked').value.toUpperCase();
    
    Swal.fire({
        title: 'Compiling Report...',
        html: 'Retrieving data rows, calculating nutritional metrics, and packaging file...',
        timer: 2000,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
        }
    }).then((result) => {
        Swal.fire({
            title: 'Report Compiled!',
            text: `System report has been generated successfully in ${format} format.`,
            icon: 'success',
            confirmButtonText: 'Download File'
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>
