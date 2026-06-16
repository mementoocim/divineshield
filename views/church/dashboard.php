<?php
/**
 * DivineShield - Church Leader Portal Dashboard (single page)
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'church_leader') {
  header("Location: ../../login.php");
  exit;
}

$success = '';
$error = '';

if (isset($_SESSION['success_msg'])) {
  $success = $_SESSION['success_msg'];
  unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
  $error = $_SESSION['error_msg'];
  unset($_SESSION['error_msg']);
}

// ──────────────────────────────────────────
// FETCH CHURCH SITE FOR LOGGED IN LEADER
// ──────────────────────────────────────────
$stmtSite = $pdo->prepare("SELECT * FROM church_sites WHERE church_leader_id = ?");
$stmtSite->execute([$_SESSION['user_id']]);
$mySite = $stmtSite->fetch();

$church_site_id = $mySite ? $mySite['id'] : 0;

// ──────────────────────────────────────────
// FETCH METRICS FOR LEADER DASHBOARD
// ──────────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM children_submissions WHERE church_leader_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalSubmissions = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM children_submissions WHERE church_leader_id = ? AND submission_status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$approvedCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM children_submissions WHERE church_leader_id = ? AND submission_status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pendingCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM children_submissions WHERE church_leader_id = ? AND submission_status = 'rejected'");
$stmt->execute([$_SESSION['user_id']]);
$rejectedCount = $stmt->fetchColumn();

// ──────────────────────────────────────────
// FETCH ANNOUNCEMENTS FROM ADMIN
// ──────────────────────────────────────────
$stmtAnnouncements = $pdo->prepare("SELECT a.*, u.username AS sender_name 
                                    FROM announcements a 
                                    JOIN users u ON a.sender_id = u.id 
                                    WHERE a.target_role = 'all' OR a.target_role = 'church_leader' 
                                    ORDER BY a.created_at DESC 
                                    LIMIT 5");
$stmtAnnouncements->execute();
$announcements = $stmtAnnouncements->fetchAll();

// ──────────────────────────────────────────
// FETCH FEEDING SCHEDULES FOR THIS SITE
// ──────────────────────────────────────────
$schedules = [];
if ($church_site_id > 0) {
  $stmtSchedules = $pdo->prepare("SELECT * FROM feeding_programs WHERE church_site_id = ? AND scheduled_date >= CURRENT_DATE ORDER BY scheduled_date ASC, scheduled_time ASC LIMIT 5");
  $stmtSchedules->execute([$church_site_id]);
  $schedules = $stmtSchedules->fetchAll();
}

// ──────────────────────────────────────────
// FETCH CHILD SUBMISSIONS LIST
// ──────────────────────────────────────────
$mySubmissions = [];
$stmtSubs = $pdo->prepare("SELECT * FROM children_submissions WHERE church_leader_id = ? ORDER BY created_at DESC");
$stmtSubs->execute([$_SESSION['user_id']]);
$mySubmissions = $stmtSubs->fetchAll();
?>
<?php
$pageTitle = "Church Leader Dashboard";
include 'includes/header.php';
?>

        <?php if (!empty($success)): ?>
          <div class="auth-alert auth-alert-success" style="margin-bottom:24px;">
            <i class="fas fa-circle-check"></i>
            <div><strong>Success</strong> <span><?php echo htmlspecialchars($success); ?></span></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="auth-alert auth-alert-danger" style="margin-bottom:24px;">
            <i class="fas fa-circle-exclamation"></i>
            <div><strong>Error</strong> <span><?php echo htmlspecialchars($error); ?></span></div>
          </div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div class="dashboard-banner">
          <div class="banner-welcome">
            <h2>Greetings, Pastor <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'Leader')[0]); ?>!
            </h2>
            <p>
              Welcome to the DivineShield Leader dashboard for
              <strong><?php echo htmlspecialchars($mySite['church_name'] ?? 'your local site'); ?></strong>.
              Submit children beneficiaries, monitor approvals, and coordinate with nutrition encoders.
            </p>
          </div>
          <div class="logo-mark"
            style="width:64px; height:64px; font-size:1.8rem; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); box-shadow:none;">
            <i class="fas fa-shield-halved" style="color:var(--blue-400);"></i>
          </div>
        </div>

        <!-- Stats Metric Grid -->
        <section class="stats-grid">
          <div class="stat-box">
            <div class="stat-box-info">
              <h4>Total Submissions</h4>
              <div class="stat-val"><?php echo number_format($totalSubmissions); ?></div>
            </div>
            <div class="stat-box-icon"><i class="fas fa-folder-open"></i></div>
          </div>

          <div class="stat-box">
            <div class="stat-box-info">
              <h4>Approved Beneficiaries</h4>
              <div class="stat-val" style="color:var(--green-500);"><?php echo number_format($approvedCount); ?></div>
            </div>
            <div class="stat-box-icon" style="color:var(--green-500); background:rgba(34, 197, 94, 0.1);"><i
                class="fas fa-circle-check"></i></div>
          </div>

          <div class="stat-box">
            <div class="stat-box-info">
              <h4>Pending Review</h4>
              <div class="stat-val" style="color:var(--yellow-500);"><?php echo number_format($pendingCount); ?></div>
            </div>
            <div class="stat-box-icon" style="color:var(--yellow-500); background:rgba(245, 158, 11, 0.1);"><i
                class="fas fa-clock"></i></div>
          </div>

          <div class="stat-box">
            <div class="stat-box-info">
              <h4>Rejected / Disqualified</h4>
              <div class="stat-val" style="color:var(--red-500);"><?php echo number_format($rejectedCount); ?></div>
            </div>
            <div class="stat-box-icon" style="color:var(--red-500); background:rgba(239, 68, 68, 0.1);"><i
                class="fas fa-circle-xmark"></i></div>
          </div>
        </section>

        <!-- Split Row: Announcements & Schedules -->
        <div class="dashboard-row">

          <!-- Column 1: Announcements from Admin -->
          <div class="dashboard-card" style="flex: 1.5;">
            <div class="dashboard-card-header">
              <div class="dashboard-card-title">Announcements &amp; Broadcasts</div>
              <span class="badge badge-info">Latest Info</span>
            </div>
            <div class="activity-list" style="margin-top:16px;">
              <?php if (empty($announcements)): ?>
                <div class="empty-state" style="padding: 40px; text-align: center;">
                  <i class="fas fa-question-circle empty-icon"
                    style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                  <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
                </div>
              <?php else: ?>
                <?php foreach ($announcements as $announce): ?>
                  <div class="activity-item"
                    style="padding-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.05); margin-bottom:12px;">
                    <div class="activity-item-content" style="margin-left:0;">
                      <h4 style="color:var(--white); font-size:0.95rem; font-weight:700; margin-bottom:4px;">
                        <?php echo htmlspecialchars($announce['title']); ?></h4>
                      <p style="font-size:0.85rem; color:var(--gray-300); line-height:1.5; margin-bottom:6px;">
                        <?php echo nl2br(htmlspecialchars($announce['content'])); ?></p>
                      <div class="activity-item-meta" style="font-size:0.75rem;">
                        <span>By: <?php echo htmlspecialchars($announce['sender_name']); ?></span>
                        <span><?php echo date('M d, Y h:i A', strtotime($announce['created_at'])); ?></span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Column 2: Scheduled Feeding sessions -->
          <div class="dashboard-card" style="flex: 1;">
            <div class="dashboard-card-header">
              <div class="dashboard-card-title">Feeding Programs</div>
              <span class="badge badge-success">Upcoming</span>
            </div>
            <div class="pending-list" style="margin-top:16px;">
              <?php if (empty($schedules)): ?>
                <div class="empty-state" style="padding: 40px; text-align: center;">
                  <i class="fas fa-question-circle empty-icon"
                    style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                  <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
                </div>
              <?php else: ?>
                <?php foreach ($schedules as $sched): ?>
                  <div class="pending-item" style="margin-bottom:12px; align-items:flex-start;">
                    <div class="pending-item-details">
                      <h5 style="color:var(--white); font-weight:600;"><?php echo htmlspecialchars($sched['title']); ?></h5>
                      <p style="font-size:0.8rem; margin-top:4px;"><i class="fas fa-calendar-day"
                          style="margin-right:6px; color:var(--gray-400);"></i><?php echo date('F d, Y', strtotime($sched['scheduled_date'])); ?>
                      </p>
                      <p style="font-size:0.8rem; margin-top:2px;"><i class="fas fa-clock"
                          style="margin-right:6px; color:var(--gray-400);"></i><?php echo date('h:i A', strtotime($sched['scheduled_time'])); ?>
                      </p>
                    </div>
                    <span
                      class="badge <?php if ($sched['status'] === 'scheduled')
                        echo 'badge-warning';
                      elseif ($sched['status'] === 'completed')
                        echo 'badge-success';
                      else
                        echo 'badge-danger'; ?>"
                      style="font-size:0.65rem; margin-top:4px;">
                      <?php echo htmlspecialchars($sched['status']); ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- Roster List of Submitted Children -->
        <section class="dashboard-card" style="margin-top:32px;">
          <div class="dashboard-card-header">
            <div class="dashboard-card-title">My Child Beneficiary Submissions</div>
            <a href="submit-child.php" class="btn btn-primary"
              style="padding: 8px 16px; font-size:0.8rem; background:var(--blue-600); text-decoration:none;">
              <i class="fas fa-plus"></i> Submit Child
            </a>
          </div>

          <?php if (empty($mySubmissions)): ?>
            <div class="empty-state" style="padding: 40px; text-align: center;">
              <i class="fas fa-question-circle empty-icon"
                style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
              <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
            </div>
          <?php else: ?>
            <div class="dark-table-wrap">
              <table class="dark-table">
                <thead>
                  <tr>
                    <th>Child Name</th>
                    <th>Gender</th>
                    <th>Birthdate</th>
                    <th>Height &amp; Weight</th>
                    <th>Calculated BMI</th>
                    <th>Suggested Status</th>
                    <th>Verification Status</th>
                    <th>Submitted At</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($mySubmissions as $sub): ?>
                    <tr>
                      <td>
                        <strong
                          style="color:var(--white);"><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></strong>
                        <?php if (!empty($sub['middle_name'])): ?>
                          <span
                            style="font-size:0.8rem; color:var(--gray-400);">(<?php echo htmlspecialchars($sub['middle_name']); ?>)</span>
                        <?php endif; ?>
                        <div style="font-size:0.78rem; color:var(--gray-400); margin-top:2px;">
                          Guardian:
                          <?php echo htmlspecialchars($sub['guardian_name'] . ' (' . $sub['guardian_relationship'] . ')'); ?>
                        </div>
                      </td>
                      <td style="text-transform: capitalize;"> <?php echo htmlspecialchars($sub['gender']); ?> </td>
                      <td><?php echo date('M d, Y', strtotime($sub['birthdate'])); ?></td>
                      <td>
                        <span style="font-size:0.85rem;">
                          Height: <?php echo htmlspecialchars($sub['initial_height']); ?> cm<br>
                          Weight: <?php echo htmlspecialchars($sub['initial_weight']); ?> kg
                        </span>
                      </td>
                      <td>
                        <strong><?php echo htmlspecialchars($sub['initial_bmi']); ?></strong>
                        <div style="font-size:0.75rem; color:var(--gray-400); margin-top:2px; font-weight:500;">
                          <?php echo htmlspecialchars($sub['initial_bmi_status']); ?>
                        </div>
                      </td>
                      <td>
                        <span
                          class="badge <?php echo ($sub['suggested_status'] === 'qualified') ? 'badge-success' : 'badge-danger'; ?>">
                          <?php echo htmlspecialchars($sub['suggested_status']); ?>
                        </span>
                      </td>
                      <td>
                        <span
                          class="badge <?php if ($sub['submission_status'] === 'approved')
                            echo 'badge-success';
                          elseif ($sub['submission_status'] === 'pending')
                            echo 'badge-warning';
                          else
                            echo 'badge-danger'; ?>">
                          <?php echo htmlspecialchars($sub['submission_status']); ?>
                        </span>
                        <?php if ($sub['submission_status'] === 'rejected' && !empty($sub['review_notes'])): ?>
                          <div
                            style="font-size:0.78rem; color:var(--red-500); margin-top:4px; max-width:180px; line-height:1.3;">
                            <strong>Reason:</strong> <?php echo htmlspecialchars($sub['review_notes']); ?>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td><?php echo date('M d, Y', strtotime($sub['created_at'])); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>

      <?php include 'includes/footer.php'; ?>
