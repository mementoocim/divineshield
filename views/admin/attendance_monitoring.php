<?php
/**
 * DivineShield - Administrator Attendance Monitoring Portal
 */
require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../login.php");
  exit;
}

// Fetch admin profile picture for topbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();

$pageTitle = "Attendance Monitor";
$tab = $_GET['tab'] ?? 'staff';
if (!in_array($tab, ['staff', 'beneficiaries'])) {
  $tab = 'staff';
}

// Common Date Filters
$dateStart = $_GET['date_start'] ?? '';
$dateEnd = $_GET['date_end'] ?? '';
$search = $_GET['search'] ?? '';

// Build Query Parameters & WHERE clauses
$whereClauses = [];
$queryParams = [];

if (!empty($dateStart)) {
  if ($tab === 'staff') {
    $whereClauses[] = "DATE(sa.check_in_time) >= ?";
  } else {
    $whereClauses[] = "DATE(a.logged_at) >= ?";
  }
  $queryParams[] = $dateStart;
}

if (!empty($dateEnd)) {
  if ($tab === 'staff') {
    $whereClauses[] = "DATE(sa.check_in_time) <= ?";
  } else {
    $whereClauses[] = "DATE(a.logged_at) <= ?";
  }
  $queryParams[] = $dateEnd;
}

if ($tab === 'staff') {
  if (!empty($search)) {
    $whereClauses[] = "(u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $queryParams[] = $likeSearch;
    $queryParams[] = $likeSearch;
    $queryParams[] = $likeSearch;
    $queryParams[] = $likeSearch;
  }

  $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

  // Count Query
  $countQuery = "SELECT COUNT(*) FROM staff_attendance sa JOIN users u ON sa.user_id = u.id $whereSql";
  $stmtCount = $pdo->prepare($countQuery);
  $stmtCount->execute($queryParams);
  $totalRows = $stmtCount->fetchColumn();

  // Pagination config
  $limit = 15;
  $page = max(1, intval($_GET['page'] ?? 1));
  $offset = ($page - 1) * $limit;
  $totalPages = max(1, ceil($totalRows / $limit));

  // Main Fetch Query
  $dataQuery = "SELECT sa.*, u.username, u.first_name, u.last_name, u.email 
                FROM staff_attendance sa 
                JOIN users u ON sa.user_id = u.id 
                $whereSql 
                ORDER BY sa.check_in_time DESC 
                LIMIT $limit OFFSET $offset";
  $stmtData = $pdo->prepare($dataQuery);
  $stmtData->execute($queryParams);
  $records = $stmtData->fetchAll();

} else {
  // Beneficiary Filters
  $statusFilter = $_GET['status'] ?? '';
  $siteFilter = $_GET['site_id'] ?? '';

  if (!empty($statusFilter)) {
    $whereClauses[] = "a.status = ?";
    $queryParams[] = $statusFilter;
  }

  if (!empty($siteFilter)) {
    $whereClauses[] = "fp.church_site_id = ?";
    $queryParams[] = intval($siteFilter);
  }

  if (!empty($search)) {
    $whereClauses[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR fp.title LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $queryParams[] = $likeSearch;
    $queryParams[] = $likeSearch;
    $queryParams[] = $likeSearch;
  }

  $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

  // Count Query
  $countQuery = "SELECT COUNT(*) 
                 FROM attendance a 
                 JOIN children c ON a.child_id = c.id 
                 JOIN feeding_programs fp ON a.feeding_program_id = fp.id 
                 JOIN church_sites cs ON fp.church_site_id = cs.id
                 $whereSql";
  $stmtCount = $pdo->prepare($countQuery);
  $stmtCount->execute($queryParams);
  $totalRows = $stmtCount->fetchColumn();

  // Pagination config
  $limit = 15;
  $page = max(1, intval($_GET['page'] ?? 1));
  $offset = ($page - 1) * $limit;
  $totalPages = max(1, ceil($totalRows / $limit));

  // Main Fetch Query
  $dataQuery = "SELECT a.*, c.first_name AS child_first, c.last_name AS child_last, 
                       fp.title AS program_title, fp.scheduled_date, cs.church_name 
                FROM attendance a 
                JOIN children c ON a.child_id = c.id 
                JOIN feeding_programs fp ON a.feeding_program_id = fp.id 
                JOIN church_sites cs ON fp.church_site_id = cs.id
                $whereSql 
                ORDER BY a.logged_at DESC 
                LIMIT $limit OFFSET $offset";
  $stmtData = $pdo->prepare($dataQuery);
  $stmtData->execute($queryParams);
  $records = $stmtData->fetchAll();

  // Fetch all sites for the dropdown filter
  $stmtSites = $pdo->query("SELECT id, church_name FROM church_sites ORDER BY church_name ASC");
  $churchSites = $stmtSites->fetchAll();
}

include 'includes/header.php';
?>

<!-- Pill Tabs Navigation -->
<div class="pill-tabs" style="margin-bottom: 24px;">
  <a href="?tab=staff" class="pill-tab <?php echo $tab === 'staff' ? 'active' : ''; ?>" style="text-decoration:none;">
    <i class="fas fa-user-clock"></i> Staff Check-Ins
  </a>
  <a href="?tab=beneficiaries" class="pill-tab <?php echo $tab === 'beneficiaries' ? 'active' : ''; ?>" style="text-decoration:none;">
    <i class="fas fa-clipboard-user"></i> Beneficiary Attendance
  </a>
</div>

<!-- Filter Controls -->
<section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
  <form action="attendance_monitoring.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">

    <div style="flex:1.2; min-width:200px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
      <input type="text" name="search" class="auth-input" placeholder="<?php echo $tab === 'staff' ? 'Name, username, email...' : 'Child name, feeding program...'; ?>" value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
    </div>

    <?php if ($tab === 'beneficiaries'): ?>
      <div style="flex:1; min-width:150px;">
        <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Church Site</label>
        <select name="site_id" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
          <option value="">-- All Sites --</option>
          <?php foreach ($churchSites as $site): ?>
            <option value="<?php echo $site['id']; ?>" <?php echo (isset($siteFilter) && $siteFilter == $site['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($site['church_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="flex:0.8; min-width:130px;">
        <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Status</label>
        <select name="status" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
          <option value="">-- All --</option>
          <option value="present" <?php echo (isset($statusFilter) && $statusFilter === 'present') ? 'selected' : ''; ?>>Present</option>
          <option value="absent" <?php echo (isset($statusFilter) && $statusFilter === 'absent') ? 'selected' : ''; ?>>Absent</option>
          <option value="excused" <?php echo (isset($statusFilter) && $statusFilter === 'excused') ? 'selected' : ''; ?>>Excused</option>
        </select>
      </div>
    <?php endif; ?>

    <div style="flex:0.8; min-width:140px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Start Date</label>
      <input type="date" name="date_start" class="auth-input" value="<?php echo htmlspecialchars($dateStart); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
    </div>

    <div style="flex:0.8; min-width:140px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">End Date</label>
      <input type="date" name="date_end" class="auth-input" value="<?php echo htmlspecialchars($dateEnd); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
    </div>

    <div style="display:flex; gap:10px; width:auto;">
      <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
        <i class="fas fa-filter"></i> Apply Filters
      </button>
      <?php if (!empty($search) || !empty($dateStart) || !empty($dateEnd) || ($tab === 'beneficiaries' && (!empty($statusFilter) || !empty($siteFilter)))): ?>
        <a href="attendance_monitoring.php?tab=<?php echo urlencode($tab); ?>" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
          <i class="fas fa-filter-circle-xmark"></i> Clear
        </a>
      <?php endif; ?>
    </div>
  </form>
</section>

<!-- Attendance Log Card -->
<section class="dashboard-card">
  <div class="dashboard-card-header">
    <div class="dashboard-card-title">
      <?php echo $tab === 'staff' ? 'Staff Check-In Ledger' : 'Beneficiary Program Attendance Logs'; ?>
    </div>
    <span style="font-size:0.75rem; color:var(--gray-400); background:rgba(255,255,255,0.06); padding:4px 10px; border-radius:999px;">
      Total: <?php echo number_format($totalRows); ?> records
    </span>
  </div>

  <?php if (empty($records)): ?>
    <div class="empty-state" style="padding: 40px; text-align: center;">
      <i class="fas fa-calendar-times empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
      <h4 style="color: var(--white); margin-bottom: 8px;">No attendance logs found</h4>
      <p style="color: var(--gray-400); font-size:0.8rem;">Adjust your filter criteria or check back later.</p>
    </div>
  <?php else: ?>
    <div class="dark-table-wrap">
      <table class="dark-table">
        <?php if ($tab === 'staff'): ?>
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Staff / Encoder</th>
              <th>Email</th>
              <th>IP Address</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $record): ?>
              <tr>
                <td style="font-size:0.82rem; white-space:nowrap; color:var(--gray-400);">
                  <?php echo date('M d, Y h:i:s A', strtotime($record['check_in_time'])); ?>
                </td>
                <td>
                  <strong style="color:var(--white);"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                  <div style="font-size:0.72rem; color:var(--gray-500); margin-top:2px;">
                    @<?php echo htmlspecialchars($record['username']); ?>
                  </div>
                </td>
                <td style="color:var(--gray-300); font-size:0.875rem;">
                  <?php echo htmlspecialchars($record['email']); ?>
                </td>
                <td style="font-size:0.82rem; font-family:monospace; color:var(--gray-400);">
                  <?php echo htmlspecialchars($record['ip_address'] ?? '—'); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        <?php else: ?>
          <thead>
            <tr>
              <th>Logged At</th>
              <th>Beneficiary Name</th>
              <th>Feeding Program</th>
              <th>Church Site</th>
              <th>Method</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $record): ?>
              <tr>
                <td style="font-size:0.82rem; white-space:nowrap; color:var(--gray-400);">
                  <?php echo date('M d, Y h:i A', strtotime($record['logged_at'])); ?>
                </td>
                <td>
                  <strong style="color:var(--white);"><?php echo htmlspecialchars($record['child_first'] . ' ' . $record['child_last']); ?></strong>
                </td>
                <td>
                  <span style="color:var(--white); font-weight:500;"><?php echo htmlspecialchars($record['program_title']); ?></span>
                  <div style="font-size:0.72rem; color:var(--gray-500); margin-top:2px;">
                    Date: <?php echo date('M d, Y', strtotime($record['scheduled_date'])); ?>
                  </div>
                </td>
                <td style="color:var(--gray-300); font-size:0.875rem;">
                  <?php echo htmlspecialchars($record['church_name']); ?>
                </td>
                <td style="font-size:0.82rem; font-weight: 500; text-transform: capitalize; color: var(--gray-400);">
                  <?php echo htmlspecialchars($record['logged_via']); ?>
                </td>
                <td>
                  <?php if ($record['status'] === 'present'): ?>
                    <span class="status-badge success"><i class="fas fa-check-circle"></i> Present</span>
                  <?php elseif ($record['status'] === 'absent'): ?>
                    <span class="status-badge error"><i class="fas fa-times-circle"></i> Absent</span>
                  <?php else: ?>
                    <span class="status-badge warning"><i class="fas fa-exclamation-circle"></i> Excused</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        <?php endif; ?>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; border-top:1px solid rgba(255,255,255,0.08); padding-top:16px; padding-left:28px; padding-right:28px; padding-bottom:20px;">
        <span style="font-size:0.8rem; color:var(--gray-400);">
          Showing page <?php echo $page; ?> of <?php echo $totalPages; ?>
        </span>
        
        <div style="display:flex; gap:8px;">
          <?php if ($page > 1): ?>
            <a href="attendance_monitoring.php?tab=<?php echo urlencode($tab); ?>&search=<?php echo urlencode($search); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?><?php echo $tab === 'beneficiaries' ? '&status=' . urlencode($statusFilter) . '&site_id=' . urlencode($siteFilter) : ''; ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline btn-sm">
              <i class="fas fa-chevron-left"></i> Previous
            </a>
          <?php endif; ?>

          <?php if ($page < $totalPages): ?>
            <a href="attendance_monitoring.php?tab=<?php echo urlencode($tab); ?>&search=<?php echo urlencode($search); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?><?php echo $tab === 'beneficiaries' ? '&status=' . urlencode($statusFilter) . '&site_id=' . urlencode($siteFilter) : ''; ?>&page=<?php echo $page + 1; ?>" class="btn btn-outline btn-sm">
              Next <i class="fas fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
