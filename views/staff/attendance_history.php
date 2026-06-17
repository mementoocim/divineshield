<?php
/**
 * DivineShield - Staff Attendance History
 */
require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "My Attendance Logs";
$userId = $_SESSION['user_id'];

// Fetch staff profile picture for topbar
$stmtStaff = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtStaff->execute([$userId]);
$staffProfilePic = $stmtStaff->fetchColumn();

// Fetch check-in logs
$stmtLogs = $pdo->prepare("
    SELECT * 
    FROM staff_attendance 
    WHERE user_id = ? 
    ORDER BY check_in_time DESC
");
$stmtLogs->execute([$userId]);
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

<!-- Attendance Registry Table Card -->
<div class="dashboard-card">
    <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 14px; margin-bottom: 24px;">
        <h3 class="dashboard-card-title" style="font-family: var(--font-head); font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--white);">
            <i class="fas fa-history" style="color:var(--blue-400);"></i> Check-In Registry Log
        </h3>
    </div>

    <div class="panel-body" style="padding:0;">
        <?php if ($totalCheckins > 0): ?>
            <div class="dark-table-wrap">
                <table class="dark-table">
                    <thead>
                        <tr>
                            <th>Check-In Date</th>
                            <th>Check-In Time</th>
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
                                <?php echo date('h:i:s A', strtotime($log['check_in_time'])); ?>
                            </td>
                            <td>
                                <span class="status-badge success" style="font-weight:600;"><i class="fas fa-circle-check"></i> Present</span>
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
