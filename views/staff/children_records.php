<?php
/**
 * DivineShield - Staff / Encoder Children Records
 */
require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Children Records";

// Get filters
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';

// Build Query
$query = "SELECT c.*, cs.church_name 
          FROM children c 
          JOIN church_sites cs ON c.church_site_id = cs.id";
$params = [];
$where_clauses = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "c.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR cs.church_name LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
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

<!-- Search & Filters Row -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
    <!-- Pill Tabs -->
    <div class="pill-tabs" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
        <a href="children_records.php?status=all&search=<?php echo urlencode($search); ?>" 
           class="pill-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
        <a href="children_records.php?status=active&search=<?php echo urlencode($search); ?>" 
           class="pill-tab <?php echo $status_filter === 'active' ? 'active' : ''; ?>"><i class="fas fa-check-circle" style="font-size:0.8rem; margin-right:4px;"></i> Active</a>
        <a href="children_records.php?status=graduated&search=<?php echo urlencode($search); ?>" 
           class="pill-tab <?php echo $status_filter === 'graduated' ? 'active' : ''; ?>"><i class="fas fa-graduation-cap" style="font-size:0.8rem; margin-right:4px;"></i> Graduated</a>
        <a href="children_records.php?status=inactive&search=<?php echo urlencode($search); ?>" 
           class="pill-tab <?php echo $status_filter === 'inactive' ? 'active' : ''; ?>"><i class="fas fa-times-circle" style="font-size:0.8rem; margin-right:4px;"></i> Inactive</a>
    </div>

    <!-- Search Form -->
    <form method="GET" action="children_records.php" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <input type="text" name="search" placeholder="Search by name or site..." 
               value="<?php echo htmlspecialchars($search); ?>" 
               style="padding:8px 16px; background:rgba(30, 41, 59, 0.6); border:1px solid rgba(255,255,255,0.08); border-radius:999px; color:var(--white); outline:none; font-size:0.85rem;">
        <button type="submit" class="btn btn-primary" style="padding:8px 20px; border-radius:999px; font-size:0.85rem; height:36px; display:inline-flex; align-items:center; justify-content:center;">Search</button>
        <?php if (!empty($search) || $status_filter !== 'all'): ?>
            <a href="children_records.php" class="btn btn-outline" style="padding:8px 20px; border-radius:999px; font-size:0.85rem; height:36px; display:inline-flex; align-items:center; justify-content:center;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="dashboard-card">
    <div class="dashboard-card-header" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 14px; margin-bottom: 24px;">
        <h3 class="dashboard-card-title" style="font-family: var(--font-head); font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--white);"><i class="fas fa-children" style="color:var(--blue-400);"></i> Children Registry</h3>
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
                <p style="color: var(--gray-400);">No children match the filters or search criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
