<?php
/**
 * DivineShield - Reports Control Panel (Live Preview & Export)
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

// Get filter inputs
$type = $_GET['report_type'] ?? 'nutritional';
$siteId = isset($_GET['site_id']) && $_GET['site_id'] !== '' ? intval($_GET['site_id']) : null;
$dateStart = $_GET['date_start'] ?? '';
$dateEnd = $_GET['date_end'] ?? '';

// Handle real report export download
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $params = [];
    if ($type === 'nutritional') {
        $sql = "SELECT c.first_name, c.last_name, c.gender, c.birthdate, 
                       na.weight, na.height, na.bmi, na.bmi_status, na.assessment_date, 
                       cs.church_name, na.notes
                FROM nutritional_assessments na
                JOIN children c ON na.child_id = c.id
                JOIN church_sites cs ON c.church_site_id = cs.id WHERE 1=1";
        
        if ($siteId) {
            $sql .= " AND c.church_site_id = ?";
            $params[] = $siteId;
        }
        if (!empty($dateStart)) {
            $sql .= " AND na.assessment_date >= ?";
            $params[] = $dateStart;
        }
        if (!empty($dateEnd)) {
            $sql .= " AND na.assessment_date <= ?";
            $params[] = $dateEnd;
        }
        
        $sql .= " ORDER BY na.assessment_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "Nutritional_Report_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['First Name', 'Last Name', 'Gender', 'Birthdate', 'Weight (kg)', 'Height (cm)', 'BMI', 'BMI Status', 'Assessment Date', 'Church Site', 'Notes']);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;

    } elseif ($type === 'attendance') {
        $sql = "SELECT a.logged_at, c.first_name, c.last_name, 
                       fp.title AS program_title, cs.church_name, a.status, a.logged_via
                FROM attendance a
                JOIN children c ON a.child_id = c.id
                JOIN feeding_programs fp ON a.feeding_program_id = fp.id
                JOIN church_sites cs ON fp.church_site_id = cs.id WHERE 1=1";

        if ($siteId) {
            $sql .= " AND fp.church_site_id = ?";
            $params[] = $siteId;
        }
        if (!empty($dateStart)) {
            $sql .= " AND DATE(a.logged_at) >= ?";
            $params[] = $dateStart;
        }
        if (!empty($dateEnd)) {
            $sql .= " AND DATE(a.logged_at) <= ?";
            $params[] = $dateEnd;
        }

        $sql .= " ORDER BY a.logged_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "Attendance_Report_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['Logged At', 'First Name', 'Last Name', 'Program Title', 'Church Site', 'Status', 'Logged Via']);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;

    } elseif ($type === 'beneficiaries') {
        $sql = "SELECT c.first_name, c.last_name, c.gender, c.birthdate, 
                       cs.church_name, c.status, c.guardian_name
                FROM children c
                JOIN church_sites cs ON c.church_site_id = cs.id WHERE 1=1";

        if ($siteId) {
            $sql .= " AND c.church_site_id = ?";
            $params[] = $siteId;
        }

        $sql .= " ORDER BY c.last_name ASC, c.first_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "Beneficiaries_Report_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['First Name', 'Last Name', 'Gender', 'Birthdate', 'Church Site', 'Status', 'Guardian Name']);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}

// Fetch preview data
$previewRows = [];
$previewParams = [];
if ($type === 'nutritional') {
    $sql = "SELECT c.first_name, c.last_name, c.gender, c.birthdate, 
                   na.weight, na.height, na.bmi, na.bmi_status, na.assessment_date, 
                   cs.church_name
            FROM nutritional_assessments na
            JOIN children c ON na.child_id = c.id
            JOIN church_sites cs ON c.church_site_id = cs.id WHERE 1=1";
    if ($siteId) {
        $sql .= " AND c.church_site_id = ?";
        $previewParams[] = $siteId;
    }
    if (!empty($dateStart)) {
        $sql .= " AND na.assessment_date >= ?";
        $previewParams[] = $dateStart;
    }
    if (!empty($dateEnd)) {
        $sql .= " AND na.assessment_date <= ?";
        $previewParams[] = $dateEnd;
    }
    $sql .= " ORDER BY na.assessment_date DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($previewParams);
    $previewRows = $stmt->fetchAll();

} elseif ($type === 'attendance') {
    $sql = "SELECT a.logged_at, c.first_name, c.last_name, 
                   fp.title AS program_title, cs.church_name, a.status, a.logged_via
            FROM attendance a
            JOIN children c ON a.child_id = c.id
            JOIN feeding_programs fp ON a.feeding_program_id = fp.id
            JOIN church_sites cs ON fp.church_site_id = cs.id WHERE 1=1";
    if ($siteId) {
        $sql .= " AND fp.church_site_id = ?";
        $previewParams[] = $siteId;
    }
    if (!empty($dateStart)) {
        $sql .= " AND DATE(a.logged_at) >= ?";
        $previewParams[] = $dateStart;
    }
    if (!empty($dateEnd)) {
        $sql .= " AND DATE(a.logged_at) <= ?";
        $previewParams[] = $dateEnd;
    }
    $sql .= " ORDER BY a.logged_at DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($previewParams);
    $previewRows = $stmt->fetchAll();

} elseif ($type === 'beneficiaries') {
    $sql = "SELECT c.first_name, c.last_name, c.gender, c.birthdate, 
                   cs.church_name, c.status, c.guardian_name
            FROM children c
            JOIN church_sites cs ON c.church_site_id = cs.id WHERE 1=1";
    if ($siteId) {
        $sql .= " AND c.church_site_id = ?";
        $previewParams[] = $siteId;
    }
    $sql .= " ORDER BY c.last_name ASC, c.first_name ASC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($previewParams);
    $previewRows = $stmt->fetchAll();
}

$pageTitle = "Reports Generator";
include 'includes/header.php';
?>

<!-- Top Filter Configuration Card -->
<section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
  <form action="reports.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
    <div style="flex:1.2; min-width:200px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Report Type</label>
      <select name="report_type" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;" required>
        <option value="nutritional" <?php echo $type === 'nutritional' ? 'selected' : ''; ?>>Nutritional Monitoring (BMI Records)</option>
        <option value="attendance" <?php echo $type === 'attendance' ? 'selected' : ''; ?>>Program Attendance Ledger</option>
        <option value="beneficiaries" <?php echo $type === 'beneficiaries' ? 'selected' : ''; ?>>Beneficiary Demographics &amp; Registry</option>
      </select>
    </div>

    <div style="flex:1; min-width:150px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Church Site</label>
      <select name="site_id" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;">
        <option value="">-- All Sites --</option>
        <?php foreach ($churchSites as $site): ?>
          <option value="<?php echo $site['id']; ?>" <?php echo $siteId == $site['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($site['church_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

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
        <i class="fas fa-rotate"></i> Generate Preview
      </button>
      <?php if ($siteId || !empty($dateStart) || !empty($dateEnd) || $type !== 'nutritional'): ?>
        <a href="reports.php" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
          <i class="fas fa-filter-circle-xmark"></i> Reset
        </a>
      <?php endif; ?>
    </div>
  </form>
</section>

<!-- Live Preview Table Card -->
<div class="dashboard-card">
  <div class="dashboard-card-header" style="border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
    <div class="dashboard-card-title">
        Report Live Preview (Showing up to 50 rows)
    </div>
    
    <?php if (!empty($previewRows)): ?>
      <a href="reports.php?action=export&report_type=<?php echo urlencode($type); ?>&site_id=<?php echo urlencode($siteId ?? ''); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>" class="btn btn-success btn-sm">
        <i class="fas fa-download"></i> Export Filtered CSV
      </a>
    <?php endif; ?>
  </div>

  <div class="panel-body" style="padding:0;">
    <?php if (empty($previewRows)): ?>
      <div class="empty-state" style="padding: 60px; text-align: center;">
        <i class="fas fa-file-excel empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
        <h4 style="color: var(--white); margin-bottom: 8px;">No matching records found</h4>
        <p style="color: var(--gray-400);">No database entries match the selected filters.</p>
      </div>
    <?php else: ?>
      
      <div class="dark-table-wrap">
        <table class="dark-table">
          <?php if ($type === 'nutritional'): ?>
            <thead>
              <tr>
                <th>Assessment Date</th>
                <th>Child Beneficiary</th>
                <th>Gender</th>
                <th>Age</th>
                <th>Weight / Height</th>
                <th>BMI</th>
                <th>Status</th>
                <th>Church Site</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($previewRows as $row): ?>
                <?php $age = date_diff(date_create($row['birthdate']), date_create('today'))->y; ?>
                <tr>
                  <td style="color:var(--gray-400); font-size:0.82rem;"><?php echo date('M d, Y', strtotime($row['assessment_date'])); ?></td>
                  <td class="fw-semibold text-white"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                  <td style="text-transform: capitalize;"><?php echo htmlspecialchars($row['gender']); ?></td>
                  <td><?php echo $age; ?> yrs</td>
                  <td><?php echo $row['weight']; ?> kg / <?php echo $row['height']; ?> cm</td>
                  <td style="font-family: monospace; color:var(--white);"><?php echo number_format($row['bmi'], 2); ?></td>
                  <td>
                    <?php 
                    $status = $row['bmi_status'];
                    if ($status === 'Normal Weight' || $status === 'Normal') {
                        echo '<span class="status-badge success"><i class="fas fa-check-circle"></i> Normal</span>';
                    } elseif ($status === 'Underweight') {
                        echo '<span class="status-badge warning"><i class="fas fa-exclamation-circle"></i> Underweight</span>';
                    } else {
                        echo '<span class="status-badge error"><i class="fas fa-times-circle"></i> Obese/Overweight</span>';
                    }
                    ?>
                  </td>
                  <td><?php echo htmlspecialchars($row['church_name']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>

          <?php elseif ($type === 'attendance'): ?>
            <thead>
              <tr>
                <th>Date Logged</th>
                <th>Beneficiary Name</th>
                <th>Feeding Program</th>
                <th>Church Site</th>
                <th>Method</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($previewRows as $row): ?>
                <tr>
                  <td style="color:var(--gray-400); font-size:0.82rem;"><?php echo date('M d, Y h:i A', strtotime($row['logged_at'])); ?></td>
                  <td class="fw-semibold text-white"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['program_title']); ?></td>
                  <td><?php echo htmlspecialchars($row['church_name']); ?></td>
                  <td style="text-transform: capitalize; font-size:0.82rem;"><?php echo htmlspecialchars($row['logged_via']); ?></td>
                  <td>
                    <?php if ($row['status'] === 'present'): ?>
                      <span class="status-badge success"><i class="fas fa-check-circle"></i> Present</span>
                    <?php elseif ($row['status'] === 'absent'): ?>
                      <span class="status-badge error"><i class="fas fa-times-circle"></i> Absent</span>
                    <?php else: ?>
                      <span class="status-badge warning"><i class="fas fa-exclamation-circle"></i> Excused</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>

          <?php elseif ($type === 'beneficiaries'): ?>
            <thead>
              <tr>
                <th>Beneficiary Name</th>
                <th>Gender</th>
                <th>Birthdate</th>
                <th>Age</th>
                <th>Church Site</th>
                <th>Guardian Info</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($previewRows as $row): ?>
                <?php $age = date_diff(date_create($row['birthdate']), date_create('today'))->y; ?>
                <tr>
                  <td class="fw-semibold text-white"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                  <td style="text-transform: capitalize;"><?php echo htmlspecialchars($row['gender']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($row['birthdate'])); ?></td>
                  <td><?php echo $age; ?> yrs</td>
                  <td><?php echo htmlspecialchars($row['church_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['guardian_name']); ?></td>
                  <td>
                    <?php if ($row['status'] === 'active'): ?>
                      <span class="status-badge success"><i class="fas fa-check-circle"></i> Active</span>
                    <?php elseif ($row['status'] === 'graduated'): ?>
                      <span class="status-badge warning" style="background:rgba(59,130,246,0.15); color:#60a5fa; border-color:rgba(59,130,246,0.3);"><i class="fas fa-graduation-cap"></i> Graduated</span>
                    <?php else: ?>
                      <span class="status-badge error"><i class="fas fa-times-circle"></i> Inactive</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          <?php endif; ?>
        </table>
      </div>

    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
