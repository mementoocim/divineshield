<?php
/**
 * DivineShield - Administrator Nutritional Monitoring (Read-Only)
 */
require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Nutritional Monitoring";

// Fetch admin profile picture for topbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();

$success = '';
$error = '';
if (isset($_SESSION['success_msg'])) { $success = $_SESSION['success_msg']; unset($_SESSION['success_msg']); }
if (isset($_SESSION['error_msg'])) { $error = $_SESSION['error_msg']; unset($_SESSION['error_msg']); }

$action = $_GET['action'] ?? 'list';
$child_id = intval($_GET['child_id'] ?? 0);

// Block write/record attempts
if ($action === 'record' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['error_msg'] = "Access denied: Administrators have view-only access to nutritional growth recording.";
    header("Location: nutritional_monitoring.php");
    exit;
}

include 'includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px; padding:15px; background:rgba(45,212,191,0.1); border-left:4px solid var(--teal-500); color:var(--teal-100); border-radius: 4px;">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger" style="margin-bottom:20px; padding:15px; background:rgba(239,68,68,0.1); border-left:4px solid var(--red-500); color:var(--red-100); border-radius: 4px;">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($action === 'history' && $child_id > 0): ?>
    <?php
    // Fetch Child Info
    $stmt = $pdo->prepare("SELECT c.*, cs.church_name 
                           FROM children c 
                           JOIN church_sites cs ON c.church_site_id = cs.id
                           WHERE c.id = ?");
    $stmt->execute([$child_id]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$child) {
        echo "<div class='dashboard-card'><h3 style='color:var(--white);'>Child record not found.</h3><a href='nutritional_monitoring.php' class='btn btn-outline btn-sm' style='margin-top:15px;'>Back to List</a></div>";
    } else {
        // Fetch Assessment History
        $stmtHist = $pdo->prepare("SELECT na.*, u.first_name, u.last_name, u.role
                                   FROM nutritional_assessments na 
                                   JOIN users u ON na.encoder_id = u.id
                                   WHERE na.child_id = ? 
                                   ORDER BY na.assessment_date DESC, na.id DESC");
        $stmtHist->execute([$child_id]);
        $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="admin-panel">
        <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 class="panel-title">Assessment History: <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h3>
            <div>
                <a href="nutritional_monitoring.php" class="btn btn-outline btn-sm">Back to List</a>
            </div>
        </div>
        <div class="panel-body" style="padding:0;">
            <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.01);">
                <p><strong>Church Site:</strong> <?php echo htmlspecialchars($child['church_name']); ?></p>
                <p><strong>Gender / Birthdate:</strong> <?php echo ucfirst($child['gender']) . ' / ' . date('M d, Y', strtotime($child['birthdate'])); ?></p>
            </div>

            <?php if (count($history) > 0): ?>
                <div class="dark-table-wrap">
                    <table class="dark-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Height (cm)</th>
                                <th>Weight (kg)</th>
                                <th>BMI</th>
                                <th>Status</th>
                                <th>Assessed By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($history as $record): ?>
                            <tr>
                                <td class="fw-semibold text-white"><?php echo date('M d, Y', strtotime($record['assessment_date'])); ?></td>
                                <td><?php echo number_format($record['height'], 1); ?> cm</td>
                                <td><?php echo number_format($record['weight'], 1); ?> kg</td>
                                <td class="text-white"><?php echo number_format($record['bmi'], 2); ?></td>
                                <td>
                                    <?php
                                    $bStat = $record['bmi_status'];
                                    if ($bStat === 'Normal Weight' || $bStat === 'Normal') {
                                        echo '<span class="status-badge success"><i class="fas fa-circle-check"></i> ' . htmlspecialchars($bStat) . '</span>';
                                    } elseif ($bStat === 'Underweight') {
                                        echo '<span class="status-badge warning" style="background:rgba(251,191,36,0.1); color:var(--yellow-400);"><i class="fas fa-circle-exclamation"></i> Underweight</span>';
                                    } else {
                                        echo '<span class="status-badge error"><i class="fas fa-circle-xmark"></i> ' . htmlspecialchars($bStat) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name'] . ' (' . ucfirst($record['role']) . ')'); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 40px; text-align: center;">
                    <i class="fas fa-history empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                    <h4 style="color: var(--white); margin-bottom: 8px;">No Assessments Recorded</h4>
                    <p style="color: var(--gray-400);">No historical records exist for this child.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php } ?>

<?php else: ?>
    <?php
    // Get filters
    $search = trim($_GET['search'] ?? '');
    $siteFilter = $_GET['site_id'] ?? '';
    $statusFilter = $_GET['bmi_status'] ?? '';

    // Fetch all church sites for dropdown filter
    $stmtSites = $pdo->query("SELECT id, church_name FROM church_sites ORDER BY church_name ASC");
    $churchSites = $stmtSites->fetchAll(PDO::FETCH_ASSOC);

    // Default list view: fetch active children from all sites
    $query = "SELECT c.id as child_id, c.first_name, c.last_name, c.gender, cs.church_name,
                     na.bmi, na.bmi_status, na.assessment_date
              FROM children c
              JOIN church_sites cs ON c.church_site_id = cs.id
              LEFT JOIN (
                  SELECT na1.* FROM nutritional_assessments na1
                  JOIN (
                      SELECT child_id, MAX(assessment_date) as max_date, MAX(id) as max_id 
                      FROM nutritional_assessments 
                      GROUP BY child_id
                  ) na2 ON na1.child_id = na2.child_id AND na1.assessment_date = na2.max_date AND na1.id = na2.max_id
              ) na ON c.id = na.child_id
              WHERE c.status = 'active'";

    $params = [];
    $where_clauses = [];

    if (!empty($search)) {
        $where_clauses[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR cs.church_name LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($siteFilter)) {
        $where_clauses[] = "c.church_site_id = ?";
        $params[] = intval($siteFilter);
    }

    if (!empty($statusFilter)) {
        if ($statusFilter === 'Not Assessed') {
            $where_clauses[] = "na.bmi_status IS NULL";
        } else {
            $where_clauses[] = "na.bmi_status = ?";
            $params[] = $statusFilter;
        }
    }

    if (count($where_clauses) > 0) {
        $query .= " AND " . implode(" AND ", $where_clauses);
    }

    $query .= " ORDER BY c.first_name ASC, c.last_name ASC";

    $stmtList = $pdo->prepare($query);
    $stmtList->execute($params);
    $activeChildren = $stmtList->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <!-- Search & Filters Bar conforming to design system -->
    <section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
      <form action="nutritional_monitoring.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
        
        <div style="flex:1.2; min-width:200px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
          <input type="text" name="search" class="auth-input" placeholder="Search by name or site..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
        </div>

        <div style="flex:1; min-width:150px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Church Site</label>
          <select name="site_id" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
            <option value="">-- All Sites --</option>
            <?php foreach ($churchSites as $site): ?>
              <option value="<?php echo $site['id']; ?>" <?php echo $siteFilter == $site['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($site['church_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="flex:1; min-width:150px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">BMI Status</label>
          <select name="bmi_status" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
            <option value="">-- All --</option>
            <option value="Normal Weight" <?php echo $statusFilter === 'Normal Weight' ? 'selected' : ''; ?>>Normal Weight</option>
            <option value="Underweight" <?php echo $statusFilter === 'Underweight' ? 'selected' : ''; ?>>Underweight</option>
            <option value="Severely Underweight" <?php echo $statusFilter === 'Severely Underweight' ? 'selected' : ''; ?>>Severely Underweight</option>
            <option value="Not Assessed" <?php echo $statusFilter === 'Not Assessed' ? 'selected' : ''; ?>>Not Assessed</option>
          </select>
        </div>

        <div style="display:flex; gap:10px; width:auto;">
          <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
            <i class="fas fa-filter"></i> Apply Filters
          </button>
          <?php if (!empty($search) || !empty($siteFilter) || !empty($statusFilter)): ?>
            <a href="nutritional_monitoring.php" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
              <i class="fas fa-filter-circle-xmark"></i> Clear
            </a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <div class="admin-panel">
        <div class="panel-header">
            <h3 class="panel-title">Nutritional Monitoring Overview</h3>
        </div>
        <div class="panel-body" style="padding:0;">
            <?php if (count($activeChildren) > 0): ?>
                <div class="dark-table-wrap">
                    <table class="dark-table">
                        <thead>
                            <tr>
                                <th>Child Name</th>
                                <th>Church Site</th>
                                <th>Latest BMI</th>
                                <th>Nutritional Status</th>
                                <th>Last Assessed</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($activeChildren as $child): ?>
                            <tr>
                                <td class="fw-semibold text-white">
                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($child['church_name']); ?></td>
                                <td class="text-white"><?php echo $child['bmi'] ? number_format($child['bmi'], 2) : '—'; ?></td>
                                <td>
                                    <?php if ($child['bmi_status']): ?>
                                        <?php
                                        $status = $child['bmi_status'];
                                        if ($status === 'Normal Weight' || $status === 'Normal') {
                                            echo '<span class="status-badge success"><i class="fas fa-circle-check"></i> ' . htmlspecialchars($status) . '</span>';
                                        } elseif ($status === 'Underweight') {
                                            echo '<span class="status-badge warning" style="background:rgba(251,191,36,0.1); color:var(--yellow-400);"><i class="fas fa-circle-exclamation"></i> Underweight</span>';
                                        } else {
                                            echo '<span class="status-badge error"><i class="fas fa-circle-xmark"></i> ' . htmlspecialchars($status) . '</span>';
                                        }
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assessed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?php echo $child['assessment_date'] ? date('M d, Y', strtotime($child['assessment_date'])) : '—'; ?></td>
                                <td class="text-right" style="display:flex; gap:10px; justify-content:flex-end;">
                                    <a href="nutritional_monitoring.php?action=history&child_id=<?php echo $child['child_id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-history"></i> History</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 60px; text-align: center;">
                    <i class="fas fa-children empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                    <h4 style="color: var(--white); margin-bottom: 8px;">No Active Children Found</h4>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
