<?php
/**
 * DivineShield - Administrator Children Registry
 */

require_once '../../db.php';
session_start();

// auth / role check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Children Registry";
$view_child_id = intval($_GET['view_child_id'] ?? 0);

include 'includes/header.php';
?>

<?php if ($view_child_id > 0): ?>
    <?php
    // Fetch Child Details
    $stmt = $pdo->prepare("SELECT c.*, cs.church_name 
                           FROM children c 
                           JOIN church_sites cs ON c.church_site_id = cs.id
                           WHERE c.id = ?");
    $stmt->execute([$view_child_id]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$child) {
        echo '<div class="dashboard-card"><h3 style="color:var(--white);">Child record not found.</h3><a href="children_registry.php" class="btn btn-outline btn-sm" style="margin-top:15px;">Back to Registry</a></div>';
    } else {
        // get assessment history
        $stmtHist = $pdo->prepare("SELECT na.*, u.first_name, u.last_name 
                                   FROM nutritional_assessments na 
                                   JOIN users u ON na.encoder_id = u.id
                                   WHERE na.child_id = ? 
                                   ORDER BY na.assessment_date DESC, na.id DESC");
        $stmtHist->execute([$view_child_id]);
        $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
        
        $age = date_diff(date_create($child['birthdate']), date_create('today'))->y;
    ?>
<!-- back button row -->
    <div style="margin-bottom: 20px;">
        <a href="children_registry.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Registry</a>
    </div>
<!-- child detail card -->
    <section class="dashboard-card detail-card" style="margin-bottom: 24px;">
        <div class="detail-card-header">
            <div class="detail-card-title">Beneficiary Profile: <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></div>
            <div>
                <?php if($child['status'] === 'active'): ?>
                    <span class="status-badge success"><i class="fas fa-circle-check"></i> Active</span>
                <?php elseif($child['status'] === 'graduated'): ?>
                    <span class="status-badge info" style="background:rgba(59,130,246,0.1); color:var(--blue-400);"><i class="fas fa-graduation-cap"></i> Graduated</span>
                <?php else: ?>
                    <span class="status-badge error"><i class="fas fa-circle-xmark"></i> Inactive</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <label>Gender</label>
                <span><?php echo ucfirst($child['gender']); ?></span>
            </div>
            <div class="detail-item">
                <label>Birthdate / Age</label>
                <span><?php echo date('M d, Y', strtotime($child['birthdate'])); ?> (<?php echo $age; ?> yrs)</span>
            </div>
            <div class="detail-item">
                <label>Guardian</label>
                <span><?php echo htmlspecialchars($child['guardian_name']); ?></span>
            </div>
            <div class="detail-item">
                <label>Church Site</label>
                <span><?php echo htmlspecialchars($child['church_name']); ?></span>
            </div>
            <div class="detail-item">
                <label>RFID Tag</label>
                <span><?php echo htmlspecialchars($child['rfid_tag'] ?? 'No Tag Assigned'); ?></span>
            </div>
            <div class="detail-item">
                <label>Enrollment Date</label>
                <span><?php echo date('M d, Y', strtotime($child['created_at'])); ?></span>
            </div>
        </div>
    </section>
<!-- nutritional growth logs card -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h3 class="dashboard-card-title">Nutritional Assessment History</h3>
        </div>
        <div class="panel-body" style="padding:0;">
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
                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
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
    // load filters
    $search = trim($_GET['search'] ?? '');
    $siteFilter = $_GET['site_id'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $genderFilter = $_GET['gender'] ?? '';

    // Fetch all church sites for dropdown filter
    $stmtSites = $pdo->query("SELECT id, church_name FROM church_sites ORDER BY church_name ASC");
    $churchSites = $stmtSites->fetchAll(PDO::FETCH_ASSOC);

    // Build Query
    $query = "SELECT c.*, cs.church_name, na.bmi, na.bmi_status, na.assessment_date
              FROM children c
              JOIN church_sites cs ON c.church_site_id = cs.id
              LEFT JOIN (
                  SELECT na1.* FROM nutritional_assessments na1
                  JOIN (
                      SELECT child_id, MAX(assessment_date) as max_date, MAX(id) as max_id 
                      FROM nutritional_assessments 
                      GROUP BY child_id
                  ) na2 ON na1.child_id = na2.child_id AND na1.assessment_date = na2.max_date AND na1.id = na2.max_id
              ) na ON c.id = na.child_id";
              
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
        $where_clauses[] = "c.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($genderFilter)) {
        $where_clauses[] = "c.gender = ?";
        $params[] = $genderFilter;
    }

    if (count($where_clauses) > 0) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $query .= " ORDER BY c.first_name ASC, c.last_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <!-- Search & Filters Bar conforming to design system -->
    <section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
      <form action="children_registry.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
        
        <div style="flex:1.2; min-width:200px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
          <input type="text" name="search" class="auth-input filter-input" placeholder="Search by name or site..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
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

        <div style="flex:0.8; min-width:120px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Status</label>
          <select name="status" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
            <option value="">-- All --</option>
            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="graduated" <?php echo $statusFilter === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
          </select>
        </div>

        <div style="flex:0.8; min-width:120px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Gender</label>
          <select name="gender" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
            <option value="">-- All --</option>
            <option value="male" <?php echo $genderFilter === 'male' ? 'selected' : ''; ?>>Male</option>
            <option value="female" <?php echo $genderFilter === 'female' ? 'selected' : ''; ?>>Female</option>
          </select>
        </div>

        <div style="display:flex; gap:10px; width:auto;">
          <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
            <i class="fas fa-filter"></i> Apply Filters
          </button>
          <?php if (!empty($search) || !empty($siteFilter) || !empty($statusFilter) || !empty($genderFilter)): ?>
            <a href="children_registry.php" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
              <i class="fas fa-filter-circle-xmark"></i> Clear
            </a>
          <?php endif; ?>
        </div>
      </form>
    </section>
<!-- main registry card -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h3 class="dashboard-card-title">Children Registry</h3>
        </div>
        <div class="panel-body" style="padding:0;">
            <?php if (count($children) > 0): ?>
                <div class="dark-table-wrap">
                    <table class="dark-table">
                        <thead>
                            <tr>
                                <th>Child Name</th>
                                <th>Gender</th>
                                <th>Church Site</th>
                                <th>Latest BMI</th>
                                <th>Nutritional Status</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($children as $child): ?>
                            <tr>
                                <td class="fw-semibold text-white">
                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                </td>
                                <td><?php echo ucfirst($child['gender']); ?></td>
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
                                <td>
                                    <?php if($child['status'] === 'active'): ?>
                                        <span class="status-badge success"><i class="fas fa-circle-check"></i> Active</span>
                                    <?php elseif($child['status'] === 'graduated'): ?>
                                        <span class="status-badge info" style="background:rgba(59,130,246,0.1); color:var(--blue-400);"><i class="fas fa-graduation-cap"></i> Graduated</span>
                                    <?php else: ?>
                                        <span class="status-badge error"><i class="fas fa-circle-xmark"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <a href="children_registry.php?view_child_id=<?php echo $child['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> View Profile</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 60px; text-align: center;">
                    <i class="fas fa-children empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                    <h4 style="color: var(--white); margin-bottom: 8px;">No Beneficiary Registry Found</h4>
                    <p style="color: var(--gray-400);">No children match the filters or search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
