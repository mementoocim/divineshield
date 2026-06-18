<?php
/**
 * DivineShield - Staff Attendance History
 */
require_once '../../db.php';
session_start();

// auth / role check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "My Attendance Logs";
$userId = $_SESSION['user_id'];

// get profile picture
$stmtStaff = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtStaff->execute([$userId]);
$staffProfilePic = $stmtStaff->fetchColumn();

// get logs
$dateStart = $_GET['date_start'] ?? '';
$dateEnd = $_GET['date_end'] ?? '';

$query = "SELECT * FROM staff_attendance WHERE user_id = ?";
$params = [$userId];

if (!empty($dateStart)) {
    $query .= " AND DATE(check_in_time) >= ?";
    $params[] = $dateStart;
}

if (!empty($dateEnd)) {
    $query .= " AND DATE(check_in_time) <= ?";
    $params[] = $dateEnd;
}

$query .= " ORDER BY check_in_time DESC";

$stmtLogs = $pdo->prepare($query);
$stmtLogs->execute($params);
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// Calculate present days in the current month
$stmtMonthCount = $pdo->prepare("
    SELECT COUNT(*) 
    FROM staff_attendance 
    WHERE user_id = ? 
      AND YEAR(check_in_time) = YEAR(CURRENT_DATE) 
      AND MONTH(check_in_time) = MONTH(CURRENT_DATE)
");
$stmtMonthCount->execute([$userId]);
$currentMonthPresentCount = intval($stmtMonthCount->fetchColumn());

// Calculate present days total
$totalCheckins = count($logs);

include 'includes/header.php';
?>

<!-- KPI Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 24px;">
    <!-- KPI 1: Present This Month -->
    <div class="stat-box" style="border-color: rgba(45, 212, 191, 0.2); background: rgba(45, 212, 191, 0.02);">
        <div class="stat-box-info">
            <h4>Present This Month</h4>
            <div class="stat-val" style="color: var(--teal-400);"><?php echo $currentMonthPresentCount; ?></div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">Check-ins for <?php echo date('F Y'); ?></p>
        </div>
        <div class="stat-box-icon" style="color: var(--teal-400); background: rgba(45, 212, 191, 0.1);">
            <i class="fas fa-calendar-check"></i>
        </div>
    </div>

    <!-- KPI 2: Total Check-ins -->
    <div class="stat-box">
        <div class="stat-box-info">
            <h4>Total Attendance Days</h4>
            <div class="stat-val"><?php echo $totalCheckins; ?></div>
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top:4px;">Cumulative check-ins recorded</p>
        </div>
        <div class="stat-box-icon">
            <i class="fas fa-clipboard-user"></i>
        </div>
    </div>
</div>

<!-- Search & Filters Bar conforming to design system -->
<section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
  <form action="attendance_history.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
    
    <div style="flex:1; min-width:140px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Start Date</label>
      <input type="date" name="date_start" class="auth-input filter-input" value="<?php echo htmlspecialchars($dateStart); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
    </div>

    <div style="flex:1; min-width:140px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">End Date</label>
      <input type="date" name="date_end" class="auth-input filter-input" value="<?php echo htmlspecialchars($dateEnd); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
    </div>

    <div style="display:flex; gap:10px; width:auto;">
      <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
        <i class="fas fa-filter"></i> Apply Filters
      </button>
      <?php if (!empty($dateStart) || !empty($dateEnd)): ?>
        <a href="attendance_history.php" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
          <i class="fas fa-filter-circle-xmark"></i> Clear
        </a>
      <?php endif; ?>
    </div>
  </form>
</section>

<!-- Attendance Registry Table Card -->
<div class="dashboard-card">
    <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 14px; margin-bottom: 24px;">
        <h3 class="dashboard-card-title" style="font-family: var(--font-head); font-size: 1.15rem; font-weight: 700; color: var(--white);">Check-In Registry Log</h3>
    </div>

    <div class="panel-body" style="padding:0;">
        <?php if ($totalCheckins > 0): ?>
            <div class="dark-table-wrap">
                <table class="dark-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check-In Time</th>
                            <th>Check-Out Time</th>
                            <th>Logging Status</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td class="fw-semibold text-white">
                                <?php echo date('M d, Y', strtotime($log['check_in_time'])); ?>
                            </td>
                            <td style="font-family: monospace; color:var(--white);">
                                <?php echo date('h:i A', strtotime($log['check_in_time'])); ?>
                            </td>
                            <td style="font-family: monospace; color:var(--white);">
                                <?php echo $log['check_out_time'] ? date('h:i A', strtotime($log['check_out_time'])) : '<span class="text-muted">—</span>'; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $log['check_out_time'] ? 'success' : 'warning'; ?>" style="font-weight:600;">
                                    <i class="fas <?php echo $log['check_out_time'] ? 'fa-circle-check' : 'fa-clock'; ?>"></i> 
                                    <?php echo $log['check_out_time'] ? 'Completed' : 'Checked In'; ?>
                                </span>
                            </td>
                            <td class="text-muted" style="font-family: monospace; font-size: 0.82rem;">
                                <?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 60px; text-align: center;">
                <i class="fas fa-calendar-times empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                <h4 style="color: var(--white); margin-bottom: 8px;">No Attendance Records Logged</h4>
                <p style="color: var(--gray-400);">You haven't logged check-in attendance yet. Scan a renewable check-in QR code to log your presence.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
