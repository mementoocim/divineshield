<?php
/**
 * DivineShield - Church Leader Children Records
 */
require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'church_leader') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Children Records";

// get site id for leader
$stmtSite = $pdo->prepare("SELECT id FROM church_sites WHERE church_leader_id = ?");
$stmtSite->execute([$_SESSION['user_id']]);
$church_site_id = $stmtSite->fetchColumn();

if (!$church_site_id) {
    $church_site_id = 0;
}

// load filters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$genderFilter = $_GET['gender'] ?? '';

// Build Query
$query = "SELECT c.*, cs.church_name 
          FROM children c 
          JOIN church_sites cs ON c.church_site_id = cs.id";
$params = [$church_site_id];
$where_clauses = ["c.church_site_id = ?"];

if (!empty($search)) {
    $where_clauses[] = "(c.first_name LIKE ? OR c.last_name LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
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

$query .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- Search & Filters Bar conforming to design system -->
<section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
  <form action="children_records.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
    
    <div style="flex:1.2; min-width:200px;">
      <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
      <input type="text" name="search" class="auth-input filter-input" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
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
      <?php if (!empty($search) || !empty($statusFilter) || !empty($genderFilter)): ?>
        <a href="children_records.php" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
          <i class="fas fa-filter-circle-xmark"></i> Clear
        </a>
      <?php endif; ?>
    </div>
  </form>
</section>

<div class="dashboard-card">
    <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 14px; margin-bottom: 24px;">
        <h3 class="dashboard-card-title" style="font-family: var(--font-head); font-size: 1.15rem; font-weight: 700; color: var(--white);">Children Registry</h3>
    </div>

    <div class="panel-body" style="padding:0;">
        <?php if (count($children) > 0): ?>
            <div class="dark-table-wrap">
                <table class="dark-table">
                    <thead>
                        <tr>
                            <th>Child Name</th>
                            <th>Gender</th>
                            <th>Birthdate / Age</th>
                            <th>Church Site</th>
                            <th>Guardian</th>
                            <th>Date Enrolled</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($children as $child): 
                            $age = date_diff(date_create($child['birthdate']), date_create('today'))->y;
                        ?>
                        <tr>
                            <td class="fw-semibold text-white">
                                <?php echo htmlspecialchars($child['first_name'] . ' ' . (isset($child['middle_name']) ? $child['middle_name'] . ' ' : '') . $child['last_name']); ?>
                            </td>
                            <td><?php echo ucfirst($child['gender']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($child['birthdate'])); ?> (<?php echo $age; ?> yrs)</td>
                            <td><?php echo htmlspecialchars($child['church_name']); ?></td>
                            <td><?php echo htmlspecialchars($child['guardian_name']); ?></td>
                            <td class="text-muted"><?php echo date('M d, Y', strtotime($child['created_at'])); ?></td>
                            <td>
                                <?php if($child['status'] === 'active'): ?>
                                    <span class="status-badge success"><i class="fas fa-check-circle"></i> Active</span>
                                <?php elseif($child['status'] === 'graduated'): ?>
                                    <span class="status-badge info" style="background:rgba(59,130,246,0.1); color:var(--blue-400);"><i class="fas fa-graduation-cap"></i> Graduated</span>
                                <?php else: ?>
                                    <span class="status-badge error"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 60px; text-align: center;">
                <i class="fas fa-children empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                <h4 style="color: var(--white); margin-bottom: 8px;">No Children Records Found</h4>
                <p style="color: var(--gray-400);">No children registered for your site match the filters or search criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
