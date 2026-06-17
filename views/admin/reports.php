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

// Fetch unique barangays from church sites for filtering
$stmtBarangays = $pdo->query("SELECT DISTINCT barangay FROM church_sites WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay ASC");
$barangays = $stmtBarangays->fetchAll(PDO::FETCH_COLUMN);

// Get filter inputs - Default to 'qualification'
$type = $_GET['report_type'] ?? 'qualification';
$siteId = isset($_GET['site_id']) && $_GET['site_id'] !== '' ? intval($_GET['site_id']) : null;
$dateStart = $_GET['date_start'] ?? '';
$dateEnd = $_GET['date_end'] ?? '';
$format = $_GET['format'] ?? 'csv';
$barangayFilter = $_GET['barangay'] ?? '';
$qualFilter = $_GET['qualification'] ?? '';

// Handle real report export download
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $params = [];
    
    // 1. Resolve query based on type
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
        $headers = ['First Name', 'Last Name', 'Gender', 'Birthdate', 'Weight (kg)', 'Height (cm)', 'BMI', 'BMI Status', 'Assessment Date', 'Church Site', 'Notes'];

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
        $headers = ['Logged At', 'First Name', 'Last Name', 'Program Title', 'Church Site', 'Status', 'Logged Via'];

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
        $headers = ['First Name', 'Last Name', 'Gender', 'Birthdate', 'Church Site', 'Status', 'Guardian Name'];
        
    } elseif ($type === 'beneficiaries_monthly') {
        $sql = "SELECT c.first_name, c.last_name, c.gender, c.birthdate, 
                       cs.church_name, c.status, c.guardian_name, c.created_at
                FROM children c
                JOIN church_sites cs ON c.church_site_id = cs.id 
                WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        if ($siteId) {
            $sql .= " AND c.church_site_id = ?";
            $params[] = $siteId;
        }
        $sql .= " ORDER BY c.created_at DESC";
        $headers = ['First Name', 'Last Name', 'Gender', 'Birthdate', 'Church Site', 'Status', 'Guardian Name', 'Registration Date'];

    } elseif ($type === 'qualification') {
        $sql = "SELECT cs.barangay, cs.church_name, 
                       SUM(CASE WHEN sub.suggested_status = 'qualified' THEN 1 ELSE 0 END) AS qualified_count,
                       SUM(CASE WHEN sub.suggested_status = 'disqualified' THEN 1 ELSE 0 END) AS disqualified_count
                FROM children_submissions sub
                JOIN church_sites cs ON sub.church_site_id = cs.id WHERE 1=1";
        if (!empty($barangayFilter)) {
            $sql .= " AND cs.barangay = ?";
            $params[] = $barangayFilter;
        }
        $sql .= " GROUP BY cs.barangay, cs.church_name ORDER BY cs.barangay ASC, cs.church_name ASC";
        $headers = ['Barangay', 'Church Site', 'Qualified Beneficiaries', 'Disqualified Beneficiaries'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Output File Formats
    if ($format === 'print') {
        // Output clean HTML printable layout
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>System Report – DivineShield</title>
            <style>
                body { font-family: 'Inter', sans-serif; color: #333; padding: 40px; line-height: 1.5; }
                .header-title { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
                .header-title h2 { margin: 0 0 5px; font-size: 1.6rem; text-transform: uppercase; letter-spacing: 0.05em; }
                .header-title p { margin: 0; color: #666; font-size: 0.9rem; }
                .meta-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 0.85rem; color: #555; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.85rem; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                tr:nth-child(even) { background-color: #fafafa; }
            </style>
        </head>
        <body>
            <div class="header-title">
                <h2>DivineShield Report</h2>
                <p>System Export: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $type))); ?> Details</p>
            </div>
            <div class="meta-info">
                <span><strong>Date Generated:</strong> <?php echo date('F d, Y h:i A'); ?></span>
                <span><strong>Records Count:</strong> <?php echo count($rows); ?> entries</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($headers as $h): ?>
                            <th><?php echo htmlspecialchars($h); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="<?php echo count($headers); ?>" style="text-align:center;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $val): ?>
                                    <td><?php echo htmlspecialchars($val ?? '—'); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Excel (.xls) or CSV (.csv) output
        $filename = ucwords($type) . "_Report_" . date('Ymd_His');
        if ($format === 'excel') {
            $filename .= ".xls";
            header('Content-Type: application/vnd.ms-excel');
        } else {
            $filename .= ".csv";
            header('Content-Type: text/csv; charset=utf-8');
        }
        
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        
        // Output headers
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}

// Fetch preview data (limit to 50 for preview)
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
    
} elseif ($type === 'beneficiaries_monthly') {
    $sql = "SELECT c.first_name, c.last_name, c.gender, c.birthdate, 
                   cs.church_name, c.status, c.guardian_name, c.created_at
            FROM children c
            JOIN church_sites cs ON c.church_site_id = cs.id 
            WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    if ($siteId) {
        $sql .= " AND c.church_site_id = ?";
        $previewParams[] = $siteId;
    }
    $sql .= " ORDER BY c.created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($previewParams);
    $previewRows = $stmt->fetchAll();

} elseif ($type === 'qualification') {
    $sql = "SELECT cs.barangay, cs.church_name, 
                   SUM(CASE WHEN sub.suggested_status = 'qualified' THEN 1 ELSE 0 END) AS qualified_count,
                   SUM(CASE WHEN sub.suggested_status = 'disqualified' THEN 1 ELSE 0 END) AS disqualified_count
            FROM children_submissions sub
            JOIN church_sites cs ON sub.church_site_id = cs.id WHERE 1=1";
    if (!empty($barangayFilter)) {
        $sql .= " AND cs.barangay = ?";
        $previewParams[] = $barangayFilter;
    }
    $sql .= " GROUP BY cs.barangay, cs.church_name ORDER BY cs.barangay ASC, cs.church_name ASC LIMIT 50";
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
    <div style="flex:1.2; min-width:180px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Report Type</label>
      <select name="report_type" id="report_type_select" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;" required onchange="toggleFilterFields()">
        <option value="qualification" <?php echo $type === 'qualification' ? 'selected' : ''; ?>>Qualification by Barangay</option>
        <option value="nutritional" <?php echo $type === 'nutritional' ? 'selected' : ''; ?>>Nutritional Monitoring (BMI Records)</option>
        <option value="attendance" <?php echo $type === 'attendance' ? 'selected' : ''; ?>>Program Attendance Ledger</option>
        <option value="beneficiaries" <?php echo $type === 'beneficiaries' ? 'selected' : ''; ?>>Beneficiary Demographics &amp; Registry</option>
        <option value="beneficiaries_monthly" <?php echo $type === 'beneficiaries_monthly' ? 'selected' : ''; ?>>New Registrations (Last 30 Days)</option>
      </select>
    </div>

    <!-- Church Site (Hidden for Barangay report since it is barangay-based) -->
    <div id="site-filter-wrapper" style="flex:1; min-width:150px;">
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

    <!-- Barangay Dropdown (Visible only for Qualification Report) -->
    <div id="barangay-filter-wrapper" style="flex:1; min-width:150px; display:none;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Barangay</label>
      <select name="barangay" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;">
        <option value="">-- All Barangays --</option>
        <?php foreach ($barangays as $bg): ?>
          <option value="<?php echo htmlspecialchars($bg); ?>" <?php echo $barangayFilter === $bg ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($bg); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Qualification Status (Visible only for Qualification Report) -->
    <div id="qual-filter-wrapper" style="flex:0.8; min-width:130px; display:none;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Qualification</label>
      <select name="qualification" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px; width:100%;">
        <option value="">-- All --</option>
        <option value="qualified" <?php echo $qualFilter === 'qualified' ? 'selected' : ''; ?>>Qualified</option>
        <option value="disqualified" <?php echo $qualFilter === 'disqualified' ? 'selected' : ''; ?>>Disqualified</option>
      </select>
    </div>

    <div id="start-date-wrapper" style="flex:0.8; min-width:130px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Start Date</label>
      <input type="date" name="date_start" class="auth-input" value="<?php echo htmlspecialchars($dateStart); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
    </div>

    <div id="end-date-wrapper" style="flex:0.8; min-width:130px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">End Date</label>
      <input type="date" name="date_end" class="auth-input" value="<?php echo htmlspecialchars($dateEnd); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
    </div>

    <div style="display:flex; gap:10px; width:auto;">
      <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
        <i class="fas fa-rotate"></i> Generate Preview
      </button>
      <?php if ($siteId || !empty($dateStart) || !empty($dateEnd) || !empty($barangayFilter) || !empty($qualFilter) || $type !== 'qualification'): ?>
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
      <div class="dropdown" style="position: relative; display: inline-block;">
        <button class="btn btn-success btn-sm dropdown-toggle" type="button" style="display:inline-flex; align-items:center; gap:8px;">
          <i class="fas fa-file-export"></i> Export Report <i class="fas fa-chevron-down" style="font-size:0.75rem;"></i>
        </button>
        <div class="dropdown-content" style="display: none; position: absolute; right: 0; background-color: #0f172a; min-width: 170px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.55); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; z-index: 100; margin-top: 4px; overflow: hidden;">
          <a href="reports.php?action=export&report_type=<?php echo urlencode($type); ?>&site_id=<?php echo urlencode($siteId ?? ''); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>&barangay=<?php echo urlencode($barangayFilter); ?>&qualification=<?php echo urlencode($qualFilter); ?>&format=csv" style="color: var(--white); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.82rem; border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='transparent'">
            <i class="fas fa-file-csv" style="color:#818cf8; width:16px; margin-right: 8px;"></i> Export to CSV
          </a>
          <a href="reports.php?action=export&report_type=<?php echo urlencode($type); ?>&site_id=<?php echo urlencode($siteId ?? ''); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>&barangay=<?php echo urlencode($barangayFilter); ?>&qualification=<?php echo urlencode($qualFilter); ?>&format=excel" style="color: var(--white); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.82rem; border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='transparent'">
            <i class="fas fa-file-excel" style="color:#34d399; width:16px; margin-right: 8px;"></i> Export to Excel
          </a>
          <a href="reports.php?action=export&report_type=<?php echo urlencode($type); ?>&site_id=<?php echo urlencode($siteId ?? ''); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>&barangay=<?php echo urlencode($barangayFilter); ?>&qualification=<?php echo urlencode($qualFilter); ?>&format=print" target="_blank" style="color: var(--white); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.82rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='transparent'">
            <i class="fas fa-print" style="color:#60a5fa; width:16px; margin-right: 8px;"></i> Print / Save PDF
          </a>
        </div>
      </div>
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
          <?php if ($type === 'qualification'): ?>
            <thead>
              <tr>
                <th>Barangay</th>
                <th>Church Site</th>
                <th>Qualified Count</th>
                <th>Disqualified Count</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($previewRows as $row): ?>
                <tr>
                  <td class="fw-semibold text-white"><?php echo htmlspecialchars($row['barangay']); ?></td>
                  <td><?php echo htmlspecialchars($row['church_name']); ?></td>
                  <td>
                    <span class="status-badge success"><?php echo intval($row['qualified_count']); ?> Qualified</span>
                  </td>
                  <td>
                    <span class="status-badge error"><?php echo intval($row['disqualified_count']); ?> Disqualified</span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>

          <?php elseif ($type === 'nutritional'): ?>
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

          <?php elseif ($type === 'beneficiaries_monthly'): ?>
            <thead>
              <tr>
                <th>Beneficiary Name</th>
                <th>Gender</th>
                <th>Birthdate</th>
                <th>Age</th>
                <th>Church Site</th>
                <th>Guardian Info</th>
                <th>Registration Date</th>
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
                  <td style="color:var(--gray-400); font-size:0.82rem;"><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.querySelector('.dropdown-toggle');
  const dropdownContent = document.querySelector('.dropdown-content');
  if (toggleBtn && dropdownContent) {
    toggleBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function() {
      dropdownContent.style.display = 'none';
    });
  }
  
  // Call on load
  toggleFilterFields();
});

function toggleFilterFields() {
  const typeSelect = document.getElementById('report_type_select');
  const type = typeSelect.value;
  
  const siteWrapper = document.getElementById('site-filter-wrapper');
  const barangayWrapper = document.getElementById('barangay-filter-wrapper');
  const qualWrapper = document.getElementById('qual-filter-wrapper');
  const startWrapper = document.getElementById('start-date-wrapper');
  const endWrapper = document.getElementById('end-date-wrapper');
  
  if (type === 'qualification') {
    siteWrapper.style.display = 'none';
    barangayWrapper.style.display = 'block';
    qualWrapper.style.display = 'none';
    startWrapper.style.display = 'none';
    endWrapper.style.display = 'none';
  } else {
    siteWrapper.style.display = 'block';
    barangayWrapper.style.display = 'none';
    qualWrapper.style.display = 'none';
    
    // Hide dates for simple registry lists
    if (type === 'beneficiaries' || type === 'beneficiaries_monthly') {
      startWrapper.style.display = 'none';
      endWrapper.style.display = 'none';
    } else {
      startWrapper.style.display = 'block';
      endWrapper.style.display = 'block';
    }
  }
}
</script>

<?php include 'includes/footer.php'; ?>
