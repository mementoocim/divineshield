<?php
/**
 * DivineShield - System Audit Logs
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

// ──────────────────────────────────────────
// FETCH UNIQUE FILTERS FOR DROPDOWNS
// ──────────────────────────────────────────
// 1. Fetch unique action codes
$stmtActions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
$uniqueActions = $stmtActions->fetchAll(PDO::FETCH_COLUMN);

// 2. Fetch unique logging users
$stmtUsers = $pdo->query("SELECT DISTINCT u.id, u.username FROM audit_logs a JOIN users u ON a.user_id = u.id ORDER BY u.username ASC");
$uniqueUsers = $stmtUsers->fetchAll();

// ──────────────────────────────────────────
// BUILD FILTER QUERY & PAGINATION
// ──────────────────────────────────────────
$whereClauses = [];
$queryParams = [];

$actionFilter = $_GET['action_filter'] ?? '';
$userFilter = $_GET['user_filter'] ?? '';

if (!empty($actionFilter)) {
    $whereClauses[] = "a.action = ?";
    $queryParams[] = $actionFilter;
}
if (!empty($userFilter)) {
    $whereClauses[] = "a.user_id = ?";
    $queryParams[] = intval($userFilter);
}

$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

// Count total matching logs
$countQuery = "SELECT COUNT(*) FROM audit_logs a $whereSql";
$stmtCount = $pdo->prepare($countQuery);
$stmtCount->execute($queryParams);
$totalLogs = $stmtCount->fetchColumn();

// Pagination config
$limit = 15;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$totalPages = max(1, ceil($totalLogs / $limit));

// Fetch matching logs
$logQuery = "SELECT a.*, u.username 
             FROM audit_logs a 
             LEFT JOIN users u ON a.user_id = u.id 
             $whereSql 
             ORDER BY a.created_at DESC 
             LIMIT $limit OFFSET $offset";

$stmtLogs = $pdo->prepare($logQuery);
$stmtLogs->execute($queryParams);
$logs = $stmtLogs->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="../../assets/images/mainpi-logo.png" />
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Security Audit Logs – DivineShield</title>
  <link rel="stylesheet" href="../../assets/css/style.css?v=6" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
</head>
<body>

  <div class="admin-layout">
    
    <!-- SIDEBAR NAVIGATION -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTAINER -->
    <main class="admin-main">
      
      <!-- TOP NAVIGATION BAR -->
      <header class="admin-topbar">
        <div class="topbar-title">System Audit Logs</div>
        <div class="topbar-user">
          <div class="user-badge-group">
            <div class="user-badge-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Administrator'); ?></div>
            <div class="user-badge-role">System Administrator</div>
          </div>
          <?php if (!empty($adminProfilePic) && file_exists('../../' . $adminProfilePic)): ?>
            <img src="../../<?php echo htmlspecialchars($adminProfilePic); ?>" alt="Profile" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.15);" />
          <?php else: ?>
            <div class="logo-mark small" style="background:linear-gradient(135deg, var(--yellow-400), var(--yellow-500)); color:var(--gray-900);"><i class="fas fa-user-shield"></i></div>
          <?php endif; ?>
        </div>
      </header>

      <!-- CONTENT WRAPPER -->
      <div class="admin-content">
        
        <!-- Filter Controls Card -->
        <section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
          <form action="audit_logs.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            
            <div style="flex:1; min-width:200px;">
              <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Filter by Action</label>
              <select name="action_filter" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
                <option value="">-- All Actions --</option>
                <?php foreach ($uniqueActions as $act): ?>
                  <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $actionFilter === $act ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($act); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="flex:1; min-width:200px;">
              <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Filter by User</label>
              <select name="user_filter" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
                <option value="">-- All Users --</option>
                <?php foreach ($uniqueUsers as $u): ?>
                  <option value="<?php echo $u['id']; ?>" <?php echo $userFilter == $u['id'] ? 'selected' : ''; ?>>
                    @<?php echo htmlspecialchars($u['username']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="display:flex; gap:10px; width:auto;">
              <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;"><i class="fas fa-filter"></i> Apply Filters</button>
              <?php if (!empty($actionFilter) || !empty($userFilter)): ?>
                <a href="audit_logs.php" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;"><i class="fas fa-filter-circle-xmark"></i> Clear</a>
              <?php endif; ?>
            </div>
            
          </form>
        </section>

        <!-- Audit Logs Listing Card -->
        <section class="dashboard-card">
          <div class="dashboard-card-header">
            <div class="dashboard-card-title">System Activity Log Entries
            </div>
            <span style="font-size:0.75rem; color:var(--gray-400); background:rgba(255,255,255,0.06); padding:4px 10px; border-radius:999px;">
              Total: <?php echo number_format($totalLogs); ?> rows
            </span>
          </div>

          <?php if (empty($logs)): ?>
            <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
          <?php else: ?>
            <div class="dark-table-wrap">
              <table class="dark-table">
                <thead>
                  <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Action Code</th>
                    <th>Logged Details</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($logs as $log): ?>
                    <tr>
                      <td style="font-size:0.82rem; white-space:nowrap; color:var(--gray-400);">
                        <?php echo date('M d, Y h:i:s A', strtotime($log['created_at'])); ?>
                      </td>
                      <td>
                        <strong style="color:var(--white);"><?php echo htmlspecialchars($log['username'] ?? 'Anonymous'); ?></strong>
                        <div style="font-size:0.72rem; color:var(--gray-500); margin-top:2px;">
                          ID: <?php echo $log['user_id'] ? 'U-' . str_pad($log['user_id'], 3, '0', STR_PAD_LEFT) : 'SYSTEM'; ?>
                        </div>
                      </td>
                      <td style="font-size:0.82rem; font-family:monospace; color:var(--gray-400);">
                        <?php echo htmlspecialchars($log['ip_address'] ?? 'UNKNOWN'); ?>
                      </td>
                      <td>
                        <span class="badge <?php 
                          $action = $log['action'];
                          if (str_contains($action, 'SUCCESS') || str_contains($action, 'CREATED') || str_contains($action, 'ACTIVATED')) {
                              echo 'badge-success';
                          } elseif (str_contains($action, 'FAILED') || str_contains($action, 'BLOCKED') || str_contains($action, 'DEACTIVATED') || str_contains($action, 'DELETED')) {
                              echo 'badge-danger';
                          } else {
                              echo 'badge-info';
                          }
                        ?>">
                          <?php echo htmlspecialchars($log['action']); ?>
                        </span>
                      </td>
                      <td style="font-size:0.875rem; color:var(--gray-200); line-height: 1.4;">
                        <?php echo htmlspecialchars($log['details']); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- PAGINATION LINKS -->
            <?php if ($totalPages > 1): ?>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; border-top:1px solid rgba(255,255,255,0.08); padding-top:16px;">
                <span style="font-size:0.8rem; color:var(--gray-400);">
                  Showing page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </span>
                
                <div style="display:flex; gap:8px;">
                  <?php if ($page > 1): ?>
                    <a href="audit_logs.php?action_filter=<?php echo urlencode($actionFilter); ?>&user_filter=<?php echo urlencode($userFilter); ?>&page=<?php echo $page - 1; ?>" class="btn-small" style="background:rgba(255,255,255,0.04); border-color:rgba(255,255,255,0.1); color:var(--white);">
                      <i class="fas fa-chevron-left"></i> Previous
                    </a>
                  <?php endif; ?>

                  <?php if ($page < $totalPages): ?>
                    <a href="audit_logs.php?action_filter=<?php echo urlencode($actionFilter); ?>&user_filter=<?php echo urlencode($userFilter); ?>&page=<?php echo $page + 1; ?>" class="btn-small" style="background:rgba(255,255,255,0.04); border-color:rgba(255,255,255,0.1); color:var(--white);">
                      Next <i class="fas fa-chevron-right"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>

          <?php endif; ?>
        </section>

      </div>
    </main>

  </div>

</body>
</html>
