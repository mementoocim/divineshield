<?php
/**
 * DivineShield - Staff / Encoder Dashboard
 */

require_once '../../db.php';
session_start();

// Session and Role Verification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Dashboard Overview";

// FETCH METRICS FOR STAFF DASHBOARD
// Active Child Beneficiaries Count
$stmt = $pdo->query("SELECT COUNT(*) FROM children WHERE status = 'active'");
$childCount = $stmt->fetchColumn();

// Pending Child Beneficiary Submissions
// To avoid missing tables error, check if children_submissions table exists, else set to 0.
$pendingChildCount = 0;
$recentPendingChildren = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM children_submissions WHERE submission_status = 'pending'");
    $pendingChildCount = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT s.id, s.first_name, s.last_name, s.suggested_status, s.created_at, cs.church_name 
                         FROM children_submissions s 
                         JOIN church_sites cs ON s.church_site_id = cs.id
                         WHERE s.submission_status = 'pending' 
                         ORDER BY s.created_at DESC LIMIT 5");
    $recentPendingChildren = $stmt->fetchAll();
} catch (PDOException $e) {
    // Tables might not be created yet, silently ignore for dashboard
}

include 'includes/header.php';
?>

<!-- Welcome Banner -->
<div class="dashboard-banner" style="background: linear-gradient(135deg, var(--blue-800), var(--blue-600));">
  <div class="banner-welcome">
    <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $staffFullName)[0]); ?>!</h2>
    <p>You have <?php echo $pendingChildCount; ?> pending child submissions waiting for your review.</p>
  </div>
  <div class="logo-mark" style="width:64px; height:64px; font-size:1.8rem; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); box-shadow:none;">
    <i class="fas fa-inbox" style="color:var(--white);"></i>
  </div>
</div>

<!-- Stats Metric Grid -->
<section class="stats-grid">
  <div class="stat-box">
    <div class="stat-box-info">
      <h4>Pending Submissions</h4>
      <div class="stat-val" style="color: var(--yellow-400);"><?php echo number_format($pendingChildCount); ?></div>
    </div>
    <div class="stat-box-icon" style="background: rgba(251,191,36,0.1); color: var(--yellow-400);">
      <i class="fas fa-clock"></i>
    </div>
  </div>

  <div class="stat-box">
    <div class="stat-box-info">
      <h4>Total Active Children</h4>
      <div class="stat-val"><?php echo number_format($childCount); ?></div>
    </div>
    <div class="stat-box-icon" style="background: rgba(45,212,191,0.1); color: var(--teal-400);">
      <i class="fas fa-children"></i>
    </div>
  </div>
</section>

<!-- Content Section -->
<div class="admin-panel" style="margin-top: 32px;">
    <div class="panel-header">
        <h3 class="panel-title">Recent Pending Submissions</h3>
        <a href="submissions.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="panel-body" style="padding:0;">
        <?php if (count($recentPendingChildren) > 0): ?>
            <div class="dark-table-wrap">
                <table class="dark-table">
                    <thead>
                        <tr>
                            <th>Child Name</th>
                            <th>Church Site</th>
                            <th>Suggested Status</th>
                            <th>Submitted On</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentPendingChildren as $child): ?>
                        <tr>
                            <td class="fw-semibold text-white">
                                <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($child['church_name']); ?></td>
                            <td>
                                <?php if($child['suggested_status'] === 'Qualified'): ?>
                                    <span class="status-badge success"><i class="fas fa-check-circle"></i> Qualified</span>
                                <?php else: ?>
                                    <span class="status-badge error"><i class="fas fa-times-circle"></i> Disqualified</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?php echo date('M d, Y', strtotime($child['created_at'])); ?></td>
                            <td class="text-right">
                                <a href="submissions.php?id=<?php echo $child['id']; ?>" class="btn btn-primary btn-sm">Review</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
