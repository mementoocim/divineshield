<?php
/**
 * DivineShield - Church Leader Portal Dashboard
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
// 1. FETCH CHURCH SITE FOR LOGGED IN LEADER
// ──────────────────────────────────────────
$stmtSite = $pdo->prepare("SELECT * FROM church_sites WHERE church_leader_id = ?");
$stmtSite->execute([$_SESSION['user_id']]);
$mySite = $stmtSite->fetch();

$church_site_id = $mySite ? $mySite['id'] : 0;

// ──────────────────────────────────────────
// 2. HANDLE SUBMIT BENEFICIARY POST ACTION
// ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_child'])) {
    $firstName    = trim($_POST['first_name'] ?? '');
    $middleName   = trim($_POST['middle_name'] ?? '');
    $lastName     = trim($_POST['last_name'] ?? '');
    $gender       = $_POST['gender'] ?? '';
    $birthdate    = $_POST['birthdate'] ?? '';
    $guardian     = trim($_POST['guardian_name'] ?? '');
    $relationship = trim($_POST['guardian_relationship'] ?? '');
    $weight       = floatval($_POST['initial_weight'] ?? 0);
    $height       = floatval($_POST['initial_height'] ?? 0);

    if (empty($firstName) || empty($lastName) || empty($gender) || empty($birthdate) || empty($guardian) || empty($relationship) || $weight <= 0 || $height <= 0) {
        $error = "All fields marked with an asterisk (*) are required, and Height / Weight must be greater than zero.";
    } else {
        try {
            if ($church_site_id === 0) {
                throw new Exception("Your church site profile could not be found. Please contact an administrator.");
            }

            // Calculate BMI
            $heightInM = $height / 100;
            $bmi = $weight / ($heightInM * $heightInM);
            $bmi = round($bmi, 2);

            // Determine suggested qualification status based on BMI
            if ($bmi < 15.0) {
                $bmiStatus = 'Severely Underweight';
                $suggestedStatus = 'qualified';
            } elseif ($bmi >= 15.0 && $bmi < 16.5) {
                $bmiStatus = 'Underweight';
                $suggestedStatus = 'qualified';
            } elseif ($bmi >= 16.5 && $bmi <= 22.0) {
                $bmiStatus = 'Normal Weight';
                $suggestedStatus = 'disqualified';
            } else {
                $bmiStatus = 'Overweight / Obese';
                $suggestedStatus = 'disqualified';
            }

            // Insert child submission
            $stmtInsert = $pdo->prepare("INSERT INTO children_submissions 
                (church_site_id, church_leader_id, first_name, last_name, middle_name, gender, birthdate, guardian_name, guardian_relationship, initial_weight, initial_height, initial_bmi, initial_bmi_status, suggested_status, submission_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmtInsert->execute([
                $church_site_id,
                $_SESSION['user_id'],
                $firstName,
                $lastName,
                empty($middleName) ? null : $middleName,
                $gender,
                $birthdate,
                $guardian,
                $relationship,
                $weight,
                $height,
                $bmi,
                $bmiStatus,
                $suggestedStatus
            ]);

            $subId = $pdo->lastInsertId();

            // Log Audit event
            logAudit($pdo, $_SESSION['user_id'], 'CHILD_SUBMITTED', "Pastor submitted beneficiary request: $firstName $lastName (ID: $subId) for Site ID: $church_site_id");

            $_SESSION['success_msg'] = "Child submission for $firstName $lastName has been successfully submitted and queued for review!";
            header("Location: dashboard.php");
            exit;
        } catch (Exception $e) {
            $error = "Failed to submit beneficiary details: " . $e->getMessage();
        }
    }
}

// ──────────────────────────────────────────
// 3. FETCH METRICS FOR LEADER DASHBOARD
// ──────────────────────────────────────────
// Total submissions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM children_submissions WHERE church_leader_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalSubmissions = $stmt->fetchColumn();

// Approved submissions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM children_submissions WHERE church_leader_id = ? AND submission_status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$approvedCount = $stmt->fetchColumn();

// Pending submissions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM children_submissions WHERE church_leader_id = ? AND submission_status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pendingCount = $stmt->fetchColumn();

// Rejected submissions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM children_submissions WHERE church_leader_id = ? AND submission_status = 'rejected'");
$stmt->execute([$_SESSION['user_id']]);
$rejectedCount = $stmt->fetchColumn();

// ──────────────────────────────────────────
// 4. FETCH ANNOUNCEMENTS FROM ADMIN
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
// 5. FETCH FEEDING SCHEDULES FOR THIS SITE
// ──────────────────────────────────────────
$schedules = [];
if ($church_site_id > 0) {
    $stmtSchedules = $pdo->prepare("SELECT * FROM feeding_programs WHERE church_site_id = ? AND scheduled_date >= CURRENT_DATE ORDER BY scheduled_date ASC, scheduled_time ASC LIMIT 5");
    $stmtSchedules->execute([$church_site_id]);
    $schedules = $stmtSchedules->fetchAll();
}

// ──────────────────────────────────────────
// 6. FETCH CHILD SUBMISSIONS LIST
// ──────────────────────────────────────────
$mySubmissions = [];
if ($_SESSION['user_id'] > 0) {
    $stmtSubs = $pdo->prepare("SELECT * FROM children_submissions WHERE church_leader_id = ? ORDER BY created_at DESC");
    $stmtSubs->execute([$_SESSION['user_id']]);
    $mySubmissions = $stmtSubs->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Church Leader Dashboard – DivineShield</title>
  <link rel="stylesheet" href="../../assets/css/style.css?v=8" />
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
        <div class="topbar-title">
          <i class="fas fa-house-chimney-user" style="margin-right:10px; color:var(--blue-400);"></i> Church Site Leader Panel
        </div>
        
        <div class="topbar-user">
          <div class="user-badge-group">
            <div class="user-badge-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Church Pastor'); ?></div>
            <div class="user-badge-role"><?php echo htmlspecialchars($mySite['church_name'] ?? 'Local Church'); ?></div>
          </div>
          <div class="logo-mark small" style="background:linear-gradient(135deg, var(--blue-500), var(--blue-700)); color:var(--white);"><i class="fas fa-church"></i></div>
        </div>
      </header>

      <!-- CONTENT WRAPPER -->
      <div class="admin-content">
        
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

        <!-- ==========================================
             TAB PANEL: MAIN DASHBOARD
             ========================================== -->
        <div id="tab-dashboard" class="tab-panel active">
          
          <!-- Welcome Banner -->
          <div class="dashboard-banner">
            <div class="banner-welcome">
              <h2>Greetings, Pastor <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'Leader')[0]); ?>!</h2>
              <p>Welcome to the DivineShield Leader dashboard for <strong><?php echo htmlspecialchars($mySite['church_name'] ?? 'your local site'); ?></strong>. Submit children beneficiaries, monitor approvals, and coordinate with nutrition encoders.</p>
            </div>
            <div class="logo-mark" style="width:64px; height:64px; font-size:1.8rem; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); box-shadow:none;">
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
              <div class="stat-box-icon" style="color:var(--green-500); background:rgba(34, 197, 94, 0.1);"><i class="fas fa-circle-check"></i></div>
            </div>

            <div class="stat-box">
              <div class="stat-box-info">
                <h4>Pending Review</h4>
                <div class="stat-val" style="color:var(--yellow-500);"><?php echo number_format($pendingCount); ?></div>
              </div>
              <div class="stat-box-icon" style="color:var(--yellow-500); background:rgba(245, 158, 11, 0.1);"><i class="fas fa-clock"></i></div>
            </div>

            <div class="stat-box">
              <div class="stat-box-info">
                <h4>Rejected / Disqualified</h4>
                <div class="stat-val" style="color:var(--red-500);"><?php echo number_format($rejectedCount); ?></div>
              </div>
              <div class="stat-box-icon" style="color:var(--red-500); background:rgba(239, 68, 68, 0.1);"><i class="fas fa-circle-xmark"></i></div>
            </div>
          </section>

          <!-- Split Row: Announcements & Schedules -->
          <div class="dashboard-row">
            
            <!-- Column 1: Announcements from Admin -->
            <div class="dashboard-card" style="flex: 1.5;">
              <div class="dashboard-card-header">
                <div class="dashboard-card-title"><i class="fas fa-bullhorn" style="color:var(--blue-400);"></i> Announcements &amp; Broadcasts</div>
                <span class="badge badge-info">Latest Info</span>
              </div>
              <div class="activity-list" style="margin-top:16px;">
                <?php if (empty($announcements)): ?>
                  <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
                <?php else: ?>
                  <?php foreach ($announcements as $announce): ?>
                    <div class="activity-item" style="padding-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.05); margin-bottom:12px;">
                      <div class="activity-item-content" style="margin-left:0;">
                        <h4 style="color:var(--white); font-size:0.95rem; font-weight:700; margin-bottom:4px;"><?php echo htmlspecialchars($announce['title']); ?></h4>
                        <p style="font-size:0.85rem; color:var(--gray-300); line-height:1.5; margin-bottom:6px;"><?php echo nl2br(htmlspecialchars($announce['content'])); ?></p>
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
                <div class="dashboard-card-title"><i class="fas fa-calendar-days" style="color:var(--teal-400);"></i> Feeding Programs</div>
                <span class="badge badge-success">Upcoming</span>
              </div>
              <div class="pending-list" style="margin-top:16px;">
                <?php if (empty($schedules)): ?>
                  <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
                <?php else: ?>
                  <?php foreach ($schedules as $sched): ?>
                    <div class="pending-item" style="margin-bottom:12px; align-items:flex-start;">
                      <div class="pending-item-details">
                        <h5 style="color:var(--white); font-weight:600;"><?php echo htmlspecialchars($sched['title']); ?></h5>
                        <p style="font-size:0.8rem; margin-top:4px;"><i class="fas fa-calendar-day" style="margin-right:6px; color:var(--gray-400);"></i><?php echo date('F d, Y', strtotime($sched['scheduled_date'])); ?></p>
                        <p style="font-size:0.8rem; margin-top:2px;"><i class="fas fa-clock" style="margin-right:6px; color:var(--gray-400);"></i><?php echo date('h:i A', strtotime($sched['scheduled_time'])); ?></p>
                      </div>
                      <span class="badge <?php 
                        if ($sched['status'] === 'scheduled') echo 'badge-warning'; 
                        elseif ($sched['status'] === 'completed') echo 'badge-success';
                        else echo 'badge-danger';
                      ?>" style="font-size:0.65rem; margin-top:4px;">
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
              <div class="dashboard-card-title"><i class="fas fa-list-check" style="color:var(--blue-400);"></i> My Child Beneficiary Submissions</div>
              <button onclick="switchTab('submit')" class="btn btn-primary" style="padding: 8px 16px; font-size:0.8rem; background:var(--blue-600);"><i class="fas fa-plus"></i> Submit Child</button>
            </div>

            <?php if (empty($mySubmissions)): ?>
              <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
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
                          <strong style="color:var(--white);"><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></strong>
                          <?php if (!empty($sub['middle_name'])): ?>
                            <span style="font-size:0.8rem; color:var(--gray-400);">(<?php echo htmlspecialchars($sub['middle_name']); ?>)</span>
                          <?php endif; ?>
                          <div style="font-size:0.78rem; color:var(--gray-400); margin-top:2px;">
                            Guardian: <?php echo htmlspecialchars($sub['guardian_name'] . ' (' . $sub['guardian_relationship'] . ')'); ?>
                          </div>
                        </td>
                        <td style="text-transform: capitalize;"><?php echo htmlspecialchars($sub['gender']); ?></td>
                        <td>
                          <?php echo date('M d, Y', strtotime($sub['birthdate'])); ?>
                        </td>
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
                          <span class="badge <?php echo $sub['suggested_status'] === 'qualified' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo htmlspecialchars($sub['suggested_status']); ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge <?php 
                            if ($sub['submission_status'] === 'approved') echo 'badge-success';
                            elseif ($sub['submission_status'] === 'pending') echo 'badge-warning';
                            else echo 'badge-danger';
                          ?>">
                            <?php echo htmlspecialchars($sub['submission_status']); ?>
                          </span>
                          <?php if ($sub['submission_status'] === 'rejected' && !empty($sub['review_notes'])): ?>
                            <div style="font-size:0.78rem; color:var(--red-500); margin-top:4px; max-width:180px; line-height:1.3;">
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

        </div> <!-- End of tab-dashboard -->

        <!-- ==========================================
             TAB PANEL: SUBMIT BENEFICIARY FORM
             ========================================== -->
        <div id="tab-submit" class="tab-panel">
          <section class="dashboard-card detail-card" style="border-color:rgba(59,130,246,0.3); margin-bottom:32px;">
            <div class="detail-card-header">
              <div class="detail-card-title"><i class="fas fa-child-reaching"></i> Register Beneficiary Request</div>
              <button onclick="switchTab('dashboard')" class="btn btn-primary" style="padding: 8px 16px; font-size:0.8rem;"><i class="fas fa-times"></i> Close</button>
            </div>

            <form action="dashboard.php" method="POST" autocomplete="off" style="margin-top:16px;">
              <input type="hidden" name="submit_child" value="1" />
              
              <!-- 3-Column Child Names Grid -->
              <div class="form-grid-3-resp" style="margin-bottom:20px;">
                <div class="auth-form-group">
                  <label for="first_name">First Name *</label>
                  <div class="auth-input-wrapper">
                    <input type="text" id="first_name" name="first_name" class="auth-input" style="padding-left:16px;" placeholder="e.g. Juan" required />
                  </div>
                </div>
                <div class="auth-form-group">
                  <label for="middle_name">Middle Name</label>
                  <div class="auth-input-wrapper">
                    <input type="text" id="middle_name" name="middle_name" class="auth-input" style="padding-left:16px;" placeholder="e.g. Santos" />
                  </div>
                </div>
                <div class="auth-form-group">
                  <label for="last_name">Last Name *</label>
                  <div class="auth-input-wrapper">
                    <input type="text" id="last_name" name="last_name" class="auth-input" style="padding-left:16px;" placeholder="e.g. Dela Cruz" required />
                  </div>
                </div>
              </div>

              <!-- Gender, Birthdate Grid -->
              <div class="form-grid-2" style="margin-bottom:20px;">
                <div class="auth-form-group">
                  <label for="gender">Gender *</label>
                  <div class="auth-input-wrapper">
                    <select id="gender" name="gender" class="auth-input" style="padding-left:16px; background:#0f172a; border-color:rgba(255,255,255,0.08);" required>
                      <option value="" disabled selected>Select Gender</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                    </select>
                  </div>
                </div>
                <div class="auth-form-group">
                  <label for="birthdate">Birthdate *</label>
                  <div class="auth-input-wrapper">
                    <input type="date" id="birthdate" name="birthdate" class="auth-input" style="padding-left:16px;" required />
                  </div>
                </div>
              </div>

              <!-- Guardian Details Grid -->
              <div class="form-grid-2" style="margin-bottom:20px;">
                <div class="auth-form-group">
                  <label for="guardian_name">Guardian Name *</label>
                  <div class="auth-input-wrapper">
                    <input type="text" id="guardian_name" name="guardian_name" class="auth-input" style="padding-left:16px;" placeholder="e.g. Maria Dela Cruz" required />
                  </div>
                </div>
                <div class="auth-form-group">
                  <label for="guardian_relationship">Relationship to Child *</label>
                  <div class="auth-input-wrapper">
                    <input type="text" id="guardian_relationship" name="guardian_relationship" class="auth-input" style="padding-left:16px;" placeholder="e.g. Mother, Father, Grandmother" required />
                  </div>
                </div>
              </div>

              <!-- Height & Weight Metrics Grid -->
              <div class="form-grid-2" style="margin-bottom:20px;">
                <div class="auth-form-group">
                  <label for="initial_height">Height (in centimeters) *</label>
                  <div class="auth-input-wrapper">
                    <input type="number" step="0.1" id="initial_height" name="initial_height" class="auth-input" style="padding-left:16px;" placeholder="e.g. 110.5" required />
                  </div>
                </div>
                <div class="auth-form-group">
                  <label for="initial_weight">Weight (in kilograms) *</label>
                  <div class="auth-input-wrapper">
                    <input type="number" step="0.1" id="initial_weight" name="initial_weight" class="auth-input" style="padding-left:16px;" placeholder="e.g. 18.2" required />
                  </div>
                </div>
              </div>

              <!-- Dynamic Live Auto-BMI Card -->
              <div class="dashboard-card" id="live-bmi-card" style="display:none; background:rgba(30, 41, 59, 0.4); border-color:rgba(59, 130, 246, 0.2); margin: 24px 0;">
                <h4 style="font-family:var(--font-head); font-size:0.9rem; text-transform:uppercase; color:var(--blue-400); margin-bottom:14px; font-weight:700;"><i class="fas fa-calculator" style="margin-right:8px;"></i> Live Auto-BMI Assessment</h4>
                
                <div class="detail-grid" style="margin-bottom:0; gap:16px;">
                  <div class="detail-item">
                    <label>Calculated BMI</label>
                    <span id="bmi_live_val" style="font-size:1.3rem; font-weight:800;">0.00</span>
                  </div>
                  <div class="detail-item">
                    <label>BMI Nutritional Classification</label>
                    <span id="bmi_status_live_val" style="font-weight:700;">Normal</span>
                  </div>
                  <div class="detail-item">
                    <label>Suggested System Status</label>
                    <span id="suggested_badge" class="badge">
                      <span id="suggested_status_live_val" style="font-weight:700; text-transform:uppercase;">TBD</span>
                    </span>
                  </div>
                </div>
              </div>

              <button type="submit" class="btn btn-primary" style="padding:12px 28px; width:100%; justify-content:center; background:var(--blue-600);"><i class="fas fa-paper-plane"></i> Submit Request to Registry</button>
            </form>
          </section>
        </div> <!-- End of tab-submit -->

        <!-- ==========================================
             TAB PANEL: CHURCH SITE PROFILE
             ========================================== -->
        <div id="tab-site" class="tab-panel">
          <section class="dashboard-card detail-card">
            <div class="detail-card-header">
              <div class="detail-card-title"><i class="fas fa-church"></i> Feeding Site Profile: <?php echo htmlspecialchars($mySite['church_name'] ?? 'Local Church'); ?></div>
              <button onclick="switchTab('dashboard')" class="btn btn-primary" style="padding: 8px 16px; font-size:0.8rem;"><i class="fas fa-arrow-left"></i> Return</button>
            </div>

            <?php if (!$mySite): ?>
              <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
            <?php else: ?>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Feeding Site Name</label>
                  <span><?php echo htmlspecialchars($mySite['church_name']); ?></span>
                </div>
                <div class="detail-item">
                  <label>Site ID Reference</label>
                  <span>CS-<?php echo str_pad($mySite['id'], 3, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="detail-item">
                  <label>Contact Phone Number</label>
                  <span><?php echo htmlspecialchars($mySite['contact_number'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                  <label>Pastor / Church Leader</label>
                  <span>Pastor <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
              </div>

              <div class="detail-grid" style="border-top:1px solid rgba(255,255,255,0.05); padding-top:20px;">
                <div class="detail-item">
                  <label>Street Address Details</label>
                  <span><?php echo htmlspecialchars($mySite['address']); ?></span>
                </div>
                <div class="detail-item">
                  <label>Barangay</label>
                  <span><?php echo htmlspecialchars($mySite['barangay']); ?></span>
                </div>
                <div class="detail-item">
                  <label>City / Municipality</label>
                  <span><?php echo htmlspecialchars($mySite['city_municipality']); ?></span>
                </div>
                <div class="detail-item">
                  <label>Province &amp; Region</label>
                  <span><?php echo htmlspecialchars($mySite['province'] . ' &middot; ' . $mySite['region']); ?></span>
                </div>
              </div>

              <div class="dashboard-card" style="margin-top:24px; background:rgba(255,255,255,0.02); border-color:rgba(255,255,255,0.04);">
                <h4 style="font-family:var(--font-head); font-size:0.95rem; font-weight:700; margin-bottom:8px; color:var(--blue-400);"><i class="fas fa-circle-info" style="margin-right:8px;"></i> Profile Read-only Constraints</h4>
                <p style="font-size:0.8rem; color:var(--gray-400); line-height:1.5;">Feeding site details and region assignments are configured directly by MAINPI administrators during registration approval. If you require contact information updates or street corrections, please launch a support request with your network administrator.</p>
              </div>
            <?php endif; ?>
          </section>
        </div> <!-- End of tab-site -->

      </div>
    </main>

  </div>

  <script>
    function switchTab(tabName) {
      // Hide all panels
      document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.remove('active');
        panel.style.display = 'none';
      });
      // Deactivate all tab buttons in sidebar
      document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
      });
      
      // Show active panel
      const targetPanel = document.getElementById('tab-' + tabName);
      if (targetPanel) {
        targetPanel.classList.add('active');
        targetPanel.style.display = 'block';
      }
      
      // Activate clicked button visually
      const targetBtn = document.getElementById('menu-' + tabName);
      if (targetBtn) {
        targetBtn.classList.add('active');
      }
      
      // Save current tab in localStorage
      localStorage.setItem('leader_dashboard_tab', tabName);
    }

    // Set active tab on DOM load
    document.addEventListener('DOMContentLoaded', () => {
      const savedTab = localStorage.getItem('leader_dashboard_tab') || 'dashboard';
      switchTab(savedTab);
    });

    // ──────────────────────────────────────────
    // LIVE AUTO-BMI CALCULATION SCRIPT
    // ──────────────────────────────────────────
    const weightInput = document.getElementById('initial_weight');
    const heightInput = document.getElementById('initial_height');
    const bmiOutput = document.getElementById('bmi_live_val');
    const bmiStatusOutput = document.getElementById('bmi_status_live_val');
    const suggestedStatusOutput = document.getElementById('suggested_status_live_val');
    const suggestedBadge = document.getElementById('suggested_badge');

    function calculateLiveBMI() {
      const w = parseFloat(weightInput.value);
      const h = parseFloat(heightInput.value);
      
      if (w > 0 && h > 0) {
        const heightInM = h / 100;
        const bmi = w / (heightInM * heightInM);
        const bmiFixed = bmi.toFixed(2);
        
        bmiOutput.textContent = bmiFixed;
        
        let status = '';
        let suggested = '';
        let badgeClass = '';
        
        if (bmi < 15.0) {
          status = 'Severely Underweight';
          suggested = 'Qualified';
          badgeClass = 'badge-success';
        } else if (bmi >= 15.0 && bmi < 16.5) {
          status = 'Underweight';
          suggested = 'Qualified';
          badgeClass = 'badge-success';
        } else if (bmi >= 16.5 && bmi <= 22.0) {
          status = 'Normal Weight';
          suggested = 'Disqualified';
          badgeClass = 'badge-danger';
        } else {
          status = 'Overweight / Obese';
          suggested = 'Disqualified';
          badgeClass = 'badge-danger';
        }
        
        bmiStatusOutput.textContent = status;
        suggestedStatusOutput.textContent = suggested;
        suggestedBadge.className = 'badge ' + badgeClass;
        
        document.getElementById('live-bmi-card').style.display = 'block';
      } else {
        document.getElementById('live-bmi-card').style.display = 'none';
      }
    }

    weightInput.addEventListener('input', calculateLiveBMI);
    heightInput.addEventListener('input', calculateLiveBMI);
  </script>
</body>
</html>
