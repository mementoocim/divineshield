<?php
/**
 * DivineShield - Church Leader Attendance Monitoring (RFID-free manual checklist)
 */
require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'church_leader') {
    header("Location: ../../login.php");
    exit;
}

// Fetch church leader's site ID
$stmtSite = $pdo->prepare("SELECT id FROM church_sites WHERE church_leader_id = ?");
$stmtSite->execute([$_SESSION['user_id']]);
$church_site_id = $stmtSite->fetchColumn();

if (!$church_site_id) {
    $church_site_id = 0;
}

// Fetch church leader profile picture for topbar
$stmtLeader = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtLeader->execute([$_SESSION['user_id']]);
$leaderProfilePic = $stmtLeader->fetchColumn();

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

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

// ──────────────────────────────────────────
// HANDLE ACTIONS: SAVE ATTENDANCE CHECKLIST
// ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance']) && $id > 0) {
    $attendanceData = $_POST['attendance'] ?? []; // Array of [child_id => status]
    try {
        $pdo->beginTransaction();
        
        // Fetch program details and verify site authorization
        $stmtFP = $pdo->prepare("SELECT title, church_site_id FROM feeding_programs WHERE id = ?");
        $stmtFP->execute([$id]);
        $fpData = $stmtFP->fetch();
        
        if (!$fpData || $fpData['church_site_id'] != $church_site_id) {
            throw new Exception("Unauthorized to modify attendance for this program session.");
        }
        
        $fpTitle = $fpData['title'];

        foreach ($attendanceData as $childId => $attStatus) {
            if (in_array($attStatus, ['present', 'absent', 'excused'])) {
                // Verify the child belongs to this site
                $stmtChildCheck = $pdo->prepare("SELECT id FROM children WHERE id = ? AND church_site_id = ?");
                $stmtChildCheck->execute([$childId, $church_site_id]);
                if (!$stmtChildCheck->fetch()) {
                    continue; // Skip if child doesn't belong to leader's site
                }

                // Check if already logged
                $stmtCheck = $pdo->prepare("SELECT id FROM attendance WHERE feeding_program_id = ? AND child_id = ?");
                $stmtCheck->execute([$id, $childId]);
                $exists = $stmtCheck->fetch();

                if ($exists) {
                    $stmtUpdate = $pdo->prepare("UPDATE attendance SET status = ?, logged_via = 'manual' WHERE feeding_program_id = ? AND child_id = ?");
                    $stmtUpdate->execute([$attStatus, $id, $childId]);
                } else {
                    $stmtInsert = $pdo->prepare("INSERT INTO attendance (feeding_program_id, child_id, status, logged_via) VALUES (?, ?, ?, 'manual')");
                    $stmtInsert->execute([$id, $childId, $attStatus]);
                }
            }
        }
        
        // Log Audit Trail
        logAudit($pdo, $_SESSION['user_id'], 'ATTENDANCE_RECORDED', "Pastor recorded/updated manual attendance for program: '$fpTitle' (ID: $id)");
        
        $pdo->commit();
        $_SESSION['success_msg'] = "Attendance checklist has been successfully saved!";
        header("Location: attendance.php?action=view&id=" . $id);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error saving attendance: " . $e->getMessage();
    }
}

// ──────────────────────────────────────────
// FETCH NECESSARY DATA FOR RENDERING
// ──────────────────────────────────────────

// A. View mode detail fetching
$viewProgram = null;
$childrenList = [];
if ($action === 'view' && $id > 0) {
    $stmtView = $pdo->prepare("
        SELECT fp.*, cs.church_name, cs.id AS site_id 
        FROM feeding_programs fp 
        JOIN church_sites cs ON fp.church_site_id = cs.id 
        WHERE fp.id = ? AND fp.church_site_id = ?
    ");
    $stmtView->execute([$id, $church_site_id]);
    $viewProgram = $stmtView->fetch();

    if ($viewProgram) {
        // Fetch all active children in that site with their current attendance status
        $stmtChild = $pdo->prepare("
            SELECT c.id, c.first_name, c.last_name, c.gender, c.birthdate, a.status AS att_status 
            FROM children c 
            LEFT JOIN attendance a ON c.id = a.child_id AND a.feeding_program_id = ? 
            WHERE c.church_site_id = ? AND c.status = 'active'
            ORDER BY c.first_name ASC, c.last_name ASC
        ");
        $stmtChild->execute([$id, $church_site_id]);
        $childrenList = $stmtChild->fetchAll();
    } else {
        $error = "Feeding program session could not be found or you are not authorized to view it.";
    }
}

// B. Status tabs filter and list query
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$dateStart = $_GET['date_start'] ?? '';
$dateEnd = $_GET['date_end'] ?? '';

$query = "
    SELECT fp.*, cs.church_name 
    FROM feeding_programs fp 
    JOIN church_sites cs ON fp.church_site_id = cs.id
    WHERE fp.church_site_id = ?
";
$params = [$church_site_id];

if ($status_filter !== 'all') {
    $query .= " AND fp.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND fp.title LIKE ?";
    $params[] = '%' . $search . '%';
}

if (!empty($dateStart)) {
    $query .= " AND fp.scheduled_date >= ?";
    $params[] = $dateStart;
}

if (!empty($dateEnd)) {
    $query .= " AND fp.scheduled_date <= ?";
    $params[] = $dateEnd;
}

$query .= " ORDER BY fp.scheduled_date DESC, fp.scheduled_time DESC";
$stmtPrograms = $pdo->prepare($query);
$stmtPrograms->execute($params);
$programsList = $stmtPrograms->fetchAll();

$pageTitle = "Feeding Programs Attendance";
include 'includes/header.php';
?>

<!-- Alerts -->
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

<!-- ──────────────────────────────────────────
     ACTION: VIEW DETAILS AND RECORD ATTENDANCE
     ────────────────────────────────────────── -->
<?php if ($action === 'view' && $viewProgram): ?>
  <!-- BACK BUTTON ROW -->
  <div style="margin-bottom: 20px;">
    <a href="attendance.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Attendance</a>
  </div>

  <!-- PROGRAM DETAILS CARD -->
  <section class="dashboard-card detail-card" style="margin-bottom:24px;">
    <div class="detail-card-header">
      <div class="detail-card-title">Feeding Session Detail: <?php echo htmlspecialchars($viewProgram['title']); ?></div>
      <div>
        <?php if ($viewProgram['status'] === 'scheduled'): ?>
          <span class="status-badge warning" style="background:rgba(251,191,36,0.1); color:var(--yellow-400);"><i class="fas fa-calendar"></i> Scheduled</span>
        <?php elseif ($viewProgram['status'] === 'completed'): ?>
          <span class="status-badge success"><i class="fas fa-circle-check"></i> Completed</span>
        <?php else: ?>
          <span class="status-badge error"><i class="fas fa-circle-xmark"></i> Cancelled</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-item">
        <label>Church Site</label>
        <span class="text-white fw-semibold"><?php echo htmlspecialchars($viewProgram['church_name']); ?></span>
      </div>
      <div class="detail-item">
        <label>Date Scheduled</label>
        <span><?php echo date('M d, Y', strtotime($viewProgram['scheduled_date'])); ?></span>
      </div>
      <div class="detail-item">
        <label>Time Scheduled</label>
        <span><?php echo date('h:i A', strtotime($viewProgram['scheduled_time'])); ?></span>
      </div>
      <div class="detail-item">
        <label>Status</label>
        <span style="text-transform: capitalize;"><?php echo htmlspecialchars($viewProgram['status']); ?></span>
      </div>
    </div>
  </section>

  <!-- ATTENDANCE MANUAL CHECKLIST CARD -->
  <section class="dashboard-card">
    <div class="dashboard-card-header">
      <div class="dashboard-card-title">Attendance Registry (<?php echo count($childrenList); ?> Active Enrolled Children)</div>
    </div>
    
    <?php if (empty($childrenList)): ?>
      <div class="empty-state" style="padding: 40px; text-align: center;">
        <i class="fas fa-children empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
        <h4 style="color: var(--white); margin-bottom: 8px;">No Children Enrolled</h4>
        <p style="color: var(--gray-400);">There are no active children currently enrolled at this church site.</p>
      </div>
    <?php else: ?>
      <form action="attendance.php?action=view&id=<?php echo $viewProgram['id']; ?>" method="POST" style="padding:0; margin:0;">
        <div class="dark-table-wrap">
          <table class="dark-table">
            <thead>
              <tr>
                <th>Child Beneficiary</th>
                <th>Gender</th>
                <th>Age</th>
                <th style="width: 320px; text-align: center;">Attendance Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($childrenList as $child): ?>
                <?php $age = date_diff(date_create($child['birthdate']), date_create('today'))->y; ?>
                <tr>
                  <td>
                    <strong class="text-white"><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></strong>
                  </td>
                  <td style="text-transform: capitalize;"><?php echo htmlspecialchars($child['gender']); ?></td>
                  <td><?php echo $age; ?> yrs</td>
                  <td>
                    <div style="display:flex; justify-content:center; gap:20px;">
                      <label style="display:flex; align-items:center; gap:6px; color:var(--green-400); font-weight:600; cursor:pointer; font-size:0.85rem;">
                        <input type="radio" name="attendance[<?php echo $child['id']; ?>]" value="present" <?php echo $child['att_status'] === 'present' ? 'checked' : ''; ?> required style="accent-color:var(--green-500); transform:scale(1.15);" />
                        Present
                      </label>
                      <label style="display:flex; align-items:center; gap:6px; color:var(--red-400); font-weight:600; cursor:pointer; font-size:0.85rem;">
                        <input type="radio" name="attendance[<?php echo $child['id']; ?>]" value="absent" <?php echo $child['att_status'] === 'absent' ? 'checked' : ''; ?> style="accent-color:var(--red-500); transform:scale(1.15);" />
                        Absent
                      </label>
                      <label style="display:flex; align-items:center; gap:6px; color:var(--yellow-400); font-weight:600; cursor:pointer; font-size:0.85rem;">
                        <input type="radio" name="attendance[<?php echo $child['id']; ?>]" value="excused" <?php echo $child['att_status'] === 'excused' ? 'checked' : ''; ?> style="accent-color:var(--yellow-500); transform:scale(1.15);" />
                        Excused
                      </label>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="padding: 24px; display:flex; justify-content:flex-end; border-top:1px solid rgba(255,255,255,0.06);">
          <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 10px 24px;"><i class="fas fa-floppy-disk"></i> Save Attendance Checklist</button>
        </div>
      </form>
    <?php endif; ?>
  </section>

<!-- ──────────────────────────────────────────
     DEFAULT LIST VIEW
     ────────────────────────────────────────── -->
<?php else: ?>

  <!-- Pill Tabs Row -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
    <!-- Pill Tabs -->
    <div class="pill-tabs" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
      <a href="attendance.php?status=all&search=<?php echo urlencode($search); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>" class="pill-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Sessions</a>
      <a href="attendance.php?status=scheduled&search=<?php echo urlencode($search); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>" class="pill-tab <?php echo $status_filter === 'scheduled' ? 'active' : ''; ?>">
        <i class="fas fa-calendar" style="font-size:0.8rem; margin-right:4px;"></i> Scheduled
      </a>
      <a href="attendance.php?status=completed&search=<?php echo urlencode($search); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>" class="pill-tab <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
        <i class="fas fa-check-circle" style="font-size:0.8rem; margin-right:4px;"></i> Completed
      </a>
    </div>
  </div>

  <!-- Search & Filters Bar conforming to design system -->
  <section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
    <form action="attendance.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
      
      <div style="flex:1.2; min-width:200px;">
        <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
        <input type="text" name="search" class="auth-input" placeholder="Search program title..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
      </div>

      <div style="flex:1; min-width:140px;">
        <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Start Date</label>
        <input type="date" name="date_start" class="auth-input" value="<?php echo htmlspecialchars($dateStart); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
      </div>

      <div style="flex:1; min-width:140px;">
        <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">End Date</label>
        <input type="date" name="date_end" class="auth-input" value="<?php echo htmlspecialchars($dateEnd); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
      </div>

      <div style="display:flex; gap:10px; width:auto;">
        <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
          <i class="fas fa-filter"></i> Apply Filters
        </button>
        <?php if (!empty($search) || !empty($dateStart) || !empty($dateEnd)): ?>
          <a href="attendance.php?status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
            <i class="fas fa-filter-circle-xmark"></i> Clear
          </a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <!-- MAIN LISTING CARD -->
  <div class="dashboard-card">
    <div class="dashboard-card-header">
      <h3 class="dashboard-card-title">Feeding Program Roster</h3>
      <span style="font-size:0.75rem; color:var(--gray-400); background:rgba(255,255,255,0.06); padding:4px 10px; border-radius:999px;">
        Local Site
      </span>
    </div>
    
    <div class="panel-body" style="padding:0;">
      <?php if (count($programsList) > 0): ?>
        <div class="dark-table-wrap">
          <table class="dark-table">
            <thead>
              <tr>
                <th>Feeding Program Session</th>
                <th>Church Site</th>
                <th>Scheduled Date &amp; Time</th>
                <th>Status</th>
                <th class="text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($programsList as $program): ?>
                <tr>
                  <td>
                    <strong class="text-white"><?php echo htmlspecialchars($program['title']); ?></strong>
                    <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                      ID: FP-<?php echo str_pad($program['id'], 3, '0', STR_PAD_LEFT); ?>
                    </div>
                  </td>
                  <td>
                    <strong><?php echo htmlspecialchars($program['church_name']); ?></strong>
                  </td>
                  <td>
                    <div class="text-white fw-semibold"><?php echo date('M d, Y', strtotime($program['scheduled_date'])); ?></div>
                    <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                      <?php echo date('h:i A', strtotime($program['scheduled_time'])); ?>
                    </div>
                  </td>
                  <td>
                    <?php if ($program['status'] === 'scheduled'): ?>
                      <span class="status-badge warning" style="background:rgba(251,191,36,0.1); color:var(--yellow-400);"><i class="fas fa-calendar"></i> Scheduled</span>
                    <?php elseif ($program['status'] === 'completed'): ?>
                      <span class="status-badge success"><i class="fas fa-circle-check"></i> Completed</span>
                    <?php else: ?>
                      <span class="status-badge error"><i class="fas fa-circle-xmark"></i> Cancelled</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-right">
                    <a href="attendance.php?action=view&id=<?php echo $program['id']; ?>" class="btn btn-info btn-sm" style="display:inline-flex; align-items:center; justify-content:center;">
                      <i class="fas fa-clipboard-user" style="margin-right:6px;"></i> Take Attendance
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state" style="padding: 60px; text-align: center;">
          <i class="fas fa-utensils empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
          <h4 style="color: var(--white); margin-bottom: 8px;">No Feeding Programs Scheduled</h4>
          <p style="color: var(--gray-400);">There are no scheduled feeding program sessions listed for your church site.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
