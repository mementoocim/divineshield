<?php
/**
 * DivineShield - Administrator Dashboard
 */

require_once '../../db.php';
session_start();

// Session and Role Verification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Fetch admin profile picture for topbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();

// ──────────────────────────────────────────
// 1. FETCH METRICS FOR STAT CARDS
// ──────────────────────────────────────────

// Active Staff Count
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff' AND status = 'active'");
$staffCount = $stmt->fetchColumn();

// Active Church Leaders Count
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'church_leader' AND status = 'active'");
$leaderCount = $stmt->fetchColumn();

// Registered Church Sites Count
$stmt = $pdo->query("SELECT COUNT(*) FROM church_sites");
$siteCount = $stmt->fetchColumn();

// Active Child Beneficiaries Count
$stmt = $pdo->query("SELECT COUNT(*) FROM children WHERE status = 'active'");
$childCount = $stmt->fetchColumn();

// Pending Church Leaders (Accounts waiting activation)
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'church_leader' AND status = 'pending'");
$pendingLeaderCount = $stmt->fetchColumn();

// Pending Child Beneficiary Submissions
$stmt = $pdo->query("SELECT COUNT(*) FROM children_submissions WHERE submission_status = 'pending'");
$pendingChildCount = $stmt->fetchColumn();

// Total Pending Count (combined)
$totalPendingCount = $pendingLeaderCount + $pendingChildCount;

// ──────────────────────────────────────────
// 2. FETCH RECENT PENDING ITEMS FOR SIDE DISPLAY
// ──────────────────────────────────────────

// Recent Pending Leaders
$stmt = $pdo->query("SELECT u.id, cs.id AS site_id, u.username, u.first_name, u.last_name, u.email, u.created_at 
                     FROM users u 
                     LEFT JOIN church_sites cs ON cs.church_leader_id = u.id 
                     WHERE u.role = 'church_leader' AND u.status = 'pending' 
                     ORDER BY u.created_at DESC LIMIT 3");
$recentPendingLeaders = $stmt->fetchAll();

// Recent Pending Submissions
$stmt = $pdo->query("SELECT s.id, s.first_name, s.last_name, s.suggested_status, s.created_at, cs.church_name 
                     FROM children_submissions s 
                     JOIN church_sites cs ON s.church_site_id = cs.id
                     WHERE s.submission_status = 'pending' 
                     ORDER BY s.created_at DESC LIMIT 3");
$recentPendingChildren = $stmt->fetchAll();

// ──────────────────────────────────────────
// 3. FETCH RECENT AUDIT LOGS
// ──────────────────────────────────────────
$stmt = $pdo->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
$recentLogs = $stmt->fetchAll();

?>
<?php
$pageTitle = "Admin Dashboard";
include 'includes/header.php';
?>
        
        <!-- Welcome Banner -->
        <div class="dashboard-banner">
          <div class="banner-welcome">
            <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'Admin')[0]); ?>!</h2>
            <p>MAINPI cloud system is secure. Monitor active registrations, pending assessments, and security logs below.</p>
          </div>
          <div class="logo-mark" style="width:64px; height:64px; font-size:1.8rem; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); box-shadow:none;">
            <i class="fas fa-cloud" style="color:var(--blue-400);"></i>
          </div>
        </div>

        <!-- Stats Metric Grid -->
        <section class="stats-grid">
          
          <div class="stat-box">
            <div class="stat-box-info">
              <h4>Total Church Sites</h4>
              <div class="stat-val"><?php echo number_format($siteCount); ?></div>
            </div>
            <div class="stat-box-icon">
              <i class="fas fa-church"></i>
            </div>
          </div>

          <div class="stat-box">
            <div class="stat-box-info">
              <h4>Active Children</h4>
              <div class="stat-val"><?php echo number_format($childCount); ?></div>
            </div>
            <div class="stat-box-icon">
              <i class="fas fa-children" style="color:var(--teal-400);"></i>
            </div>
          </div>

          <div class="stat-box">
            <div class="stat-box-info">
              <h4>Encoder Staff</h4>
              <div class="stat-val"><?php echo number_format($staffCount); ?></div>
            </div>
            <div class="stat-box-icon">
              <i class="fas fa-user-group" style="color:var(--yellow-400);"></i>
            </div>
          </div>

          <div class="stat-box" style="<?php echo $totalPendingCount > 0 ? 'border-color:rgba(245,158,11,0.3); background:rgba(245,158,11,0.04);' : ''; ?>">
            <div class="stat-box-info">
              <h4>Pending Approvals</h4>
              <div class="stat-val" style="<?php echo $totalPendingCount > 0 ? 'color:var(--yellow-400);' : ''; ?>">
                <?php echo $totalPendingCount; ?>
              </div>
            </div>
            <div class="stat-box-icon" style="<?php echo $totalPendingCount > 0 ? 'color:var(--yellow-400); background:rgba(245,158,11,0.1);' : ''; ?>">
              <i class="fas fa-clock"></i>
            </div>
          </div>

        </section>

        <!-- Main Dashboard Split Grid -->
        <div class="dashboard-row">
          
          <!-- Column 1: Pending Approvals Quick View -->
          <div class="dashboard-card">
            <div class="dashboard-card-header">
              <div class="dashboard-card-title">Pending Registrations &amp; Submissions
              </div>
              <span style="font-size:0.75rem; color:var(--gray-400); font-weight:600; background:rgba(255,255,255,0.06); padding:4px 10px; border-radius:999px;">
                Needs Action
              </span>
            </div>

            <div class="pending-list">
              
              <!-- 1. Pending Church Leader Registrations -->
              <?php if (empty($recentPendingLeaders) && empty($recentPendingChildren)): ?>
                <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
              <?php endif; ?>

              <?php if (!empty($recentPendingLeaders)): ?>
                <div style="margin-bottom:10px;">
                  <h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--yellow-400); margin-bottom:8px; font-weight:700; letter-spacing:0.04em;">Pending Church Leaders</h4>
                  <?php foreach ($recentPendingLeaders as $leader): ?>
                    <div class="pending-item" style="margin-bottom:8px;">
                      <div class="pending-item-details">
                        <h5>Pastor <?php echo htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']); ?></h5>
                        <p>Username: @<?php echo htmlspecialchars($leader['username']); ?> &middot; <?php echo htmlspecialchars($leader['email']); ?></p>
                      </div>
                      <div class="pending-actions">
                        <a href="church_sites.php?action=view&id=<?php echo $leader['site_id']; ?>" class="btn-small btn-small-success">
                          <i class="fas fa-user-check"></i> Review
                        </a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <!-- 2. Pending Child Beneficiary Submissions -->
              <?php if (!empty($recentPendingChildren)): ?>
                <div>
                  <h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--blue-400); margin-bottom:8px; font-weight:700; letter-spacing:0.04em;">Pending Child Submissions</h4>
                  <?php foreach ($recentPendingChildren as $child): ?>
                    <div class="pending-item" style="margin-bottom:8px;">
                      <div class="pending-item-details">
                        <h5><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h5>
                        <p>Site: <?php echo htmlspecialchars($child['church_name']); ?> &middot; Rec: <span style="text-transform: capitalize; color: <?php echo $child['suggested_status'] === 'qualified' ? '#86efac' : '#fca5a5'; ?>"><?php echo $child['suggested_status']; ?></span></p>
                      </div>
                      <div class="pending-actions">
                        <a href="church_sites.php?action=review_child&id=<?php echo $child['id']; ?>" class="btn-small btn-small-success">
                          <i class="fas fa-clipboard-check"></i> Review
                        </a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

            </div>
          </div>

          <!-- Column 2: Recent System Activity Logs -->
          <div class="dashboard-card">
            <div class="dashboard-card-header">
              <div class="dashboard-card-title">Recent Security Gateway Activity
              </div>
              <a href="audit_logs.php" style="font-size:0.8rem; color:var(--blue-400); font-weight:600;">
                View All <i class="fas fa-arrow-right" style="font-size:0.75rem; margin-left:4px;"></i>
              </a>
            </div>

            <div class="activity-list">
              <?php if (empty($recentLogs)): ?>
                <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
              <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                  <div class="activity-item">
                    <div class="activity-item-icon">
                      <?php 
                        $action = $log['action'];
                        if (str_contains($action, 'SUCCESS')) {
                            echo '<i class="fas fa-circle-check" style="color:var(--green-500);"></i>';
                        } elseif (str_contains($action, 'FAILED') || str_contains($action, 'BLOCKED')) {
                            echo '<i class="fas fa-triangle-exclamation" style="color:var(--red-500);"></i>';
                        } else {
                            echo '<i class="fas fa-info-circle"></i>';
                        }
                      ?>
                    </div>
                    <div class="activity-item-content">
                      <div class="activity-item-title"><?php echo htmlspecialchars($log['action']); ?></div>
                      <div class="activity-item-details"><?php echo htmlspecialchars($log['details']); ?></div>
                      <div class="activity-item-meta">
                        <span>User: <?php echo htmlspecialchars($log['username'] ?? 'Anonymous'); ?></span>
                        <span><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>

      <?php include 'includes/footer.php'; ?>
