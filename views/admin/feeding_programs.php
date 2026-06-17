<?php
/**
 * DivineShield - Feeding Programs Management
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
// HANDLE ACTIONS: COMPLETE, CANCEL, ADD, SAVE ATTENDANCE
// ──────────────────────────────────────────

// 1. Mark feeding program as Completed
if ($action === 'complete' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT title FROM feeding_programs WHERE id = ?");
        $stmt->execute([$id]);
        $title = $stmt->fetchColumn();

        if ($title) {
            $stmtUpdate = $pdo->prepare("UPDATE feeding_programs SET status = 'completed' WHERE id = ?");
            $stmtUpdate->execute([$id]);
            
            logAudit($pdo, $_SESSION['user_id'], 'FEEDING_PROGRAM_COMPLETED', "Marked feeding program '$title' (ID: $id) as completed");
            $_SESSION['success_msg'] = "Feeding program '$title' has been marked as completed.";
        } else {
            $_SESSION['error_msg'] = "Feeding program not found.";
        }
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error updating feeding program: " . $e->getMessage();
    }
    header("Location: feeding_programs.php");
    exit;
}

// 2. Cancel a feeding program
if ($action === 'cancel' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT title FROM feeding_programs WHERE id = ?");
        $stmt->execute([$id]);
        $title = $stmt->fetchColumn();

        if ($title) {
            $stmtUpdate = $pdo->prepare("UPDATE feeding_programs SET status = 'cancelled' WHERE id = ?");
            $stmtUpdate->execute([$id]);
            
            logAudit($pdo, $_SESSION['user_id'], 'FEEDING_PROGRAM_CANCELLED', "Marked feeding program '$title' (ID: $id) as cancelled");
            $_SESSION['success_msg'] = "Feeding program '$title' has been marked as cancelled.";
        } else {
            $_SESSION['error_msg'] = "Feeding program not found.";
        }
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error updating feeding program: " . $e->getMessage();
    }
    header("Location: feeding_programs.php");
    exit;
}

// 3. Create Feeding Program (POST Handler)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_program'])) {
    $churchSiteId = intval($_POST['church_site_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $schedDate    = $_POST['scheduled_date'] ?? '';
    $schedTime    = $_POST['scheduled_time'] ?? '';

    if ($churchSiteId <= 0 || empty($title) || empty($schedDate) || empty($schedTime)) {
        $error = "All fields are required to schedule a feeding program.";
    } else {
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO feeding_programs (church_site_id, title, scheduled_date, scheduled_time, status) VALUES (?, ?, ?, ?, 'scheduled')");
            $stmtInsert->execute([$churchSiteId, $title, $schedDate, $schedTime]);
            $newProgramId = $pdo->lastInsertId();

            logAudit($pdo, $_SESSION['user_id'], 'FEEDING_PROGRAM_SCHEDULED', "Scheduled new feeding program '$title' (ID: $newProgramId) for Church Site ID: $churchSiteId");

            $_SESSION['success_msg'] = "Feeding program '$title' has been successfully scheduled!";
            header("Location: feeding_programs.php");
            exit;
        } catch (Exception $e) {
            $error = "Error scheduling feeding program: " . $e->getMessage();
        }
    }
}

// 4. Save/Update Manual Attendance (POST Handler)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance']) && $id > 0) {
    $attendanceData = $_POST['attendance'] ?? []; // Array of [child_id => status]
    try {
        $pdo->beginTransaction();
        
        // Fetch program info to log audit detail
        $stmtFP = $pdo->prepare("SELECT title FROM feeding_programs WHERE id = ?");
        $stmtFP->execute([$id]);
        $fpTitle = $stmtFP->fetchColumn();

        foreach ($attendanceData as $childId => $attStatus) {
            if (in_array($attStatus, ['present', 'absent', 'excused'])) {
                // Check if record exists
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
        
        logAudit($pdo, $_SESSION['user_id'], 'ATTENDANCE_RECORDED', "Recorded/updated manual attendance list for program: '$fpTitle' (ID: $id)");
        
        $pdo->commit();
        $_SESSION['success_msg'] = "Attendance list has been successfully saved!";
        header("Location: feeding_programs.php?action=view&id=" . $id);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error recording attendance: " . $e->getMessage();
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
        WHERE fp.id = ?
    ");
    $stmtView->execute([$id]);
    $viewProgram = $stmtView->fetch();

    if ($viewProgram) {
        // Fetch all active children in that site with their attendance status
        $stmtChild = $pdo->prepare("
            SELECT c.id, c.first_name, c.last_name, c.gender, c.birthdate, a.status AS att_status 
            FROM children c 
            LEFT JOIN attendance a ON c.id = a.child_id AND a.feeding_program_id = ? 
            WHERE c.church_site_id = ? AND c.status = 'active'
            ORDER BY c.first_name ASC, c.last_name ASC
        ");
        $stmtChild->execute([$id, $viewProgram['site_id']]);
        $childrenList = $stmtChild->fetchAll();
    } else {
        $error = "Feeding program record could not be found.";
    }
}

// B. Fetch all active church sites for dropdown
$stmtSites = $pdo->query("SELECT id, church_name FROM church_sites ORDER BY church_name ASC");
$churchSites = $stmtSites->fetchAll();

// C. Status tabs filter and list query
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$siteFilter = $_GET['site_id'] ?? '';
$dateStart = $_GET['date_start'] ?? '';
$dateEnd = $_GET['date_end'] ?? '';

$query = "
    SELECT fp.*, cs.church_name 
    FROM feeding_programs fp 
    JOIN church_sites cs ON fp.church_site_id = cs.id
";
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "fp.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(fp.title LIKE ? OR cs.church_name LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

if (!empty($siteFilter)) {
    $where_clauses[] = "fp.church_site_id = ?";
    $params[] = intval($siteFilter);
}

if (!empty($dateStart)) {
    $where_clauses[] = "fp.scheduled_date >= ?";
    $params[] = $dateStart;
}

if (!empty($dateEnd)) {
    $where_clauses[] = "fp.scheduled_date <= ?";
    $params[] = $dateEnd;
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY fp.scheduled_date DESC, fp.scheduled_time DESC";
$stmtPrograms = $pdo->prepare($query);
$stmtPrograms->execute($params);
$programsList = $stmtPrograms->fetchAll();

$pageTitle = "Feeding Programs Management";
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
     ACTION: ADD FEEDING PROGRAM FORM
     ────────────────────────────────────────── -->
<?php if ($action === 'add'): ?>
  <section class="dashboard-card detail-card" style="border-color:rgba(59,130,246,0.3); margin-bottom:32px;">
    <div class="detail-card-header">
      <div class="detail-card-title">Schedule Feeding Program</div>
      <a href="feeding_programs.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Cancel</a>
    </div>

    <form action="feeding_programs.php" method="POST" autocomplete="off" style="margin-top:16px;">
      <input type="hidden" name="create_program" value="1" />

      <!-- Title Input -->
      <div class="auth-form-group" style="margin-bottom:20px;">
        <label for="title">Feeding Session Title *</label>
        <div class="auth-input-wrapper">
          <input type="text" id="title" name="title" class="auth-input" style="padding-left:16px;" placeholder="e.g., Weekly Supplemental Feeding - Session #5" required />
        </div>
      </div>

      <!-- Church Site Dropdown -->
      <div class="auth-form-group" style="margin-bottom:20px;">
        <label for="church_site_id">Target Church Site *</label>
        <div class="auth-input-wrapper">
          <select id="church_site_id" name="church_site_id" class="auth-input" style="padding-left:16px; background-color: var(--slate-900); color: var(--white); cursor: pointer;" required>
            <option value="" disabled selected>-- Select Church Site --</option>
            <?php foreach ($churchSites as $site): ?>
              <option value="<?php echo $site['id']; ?>"><?php echo htmlspecialchars($site['church_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Date and Time Grid -->
      <div class="form-grid-2" style="margin-bottom:24px;">
        <div class="auth-form-group">
          <label for="scheduled_date">Scheduled Date *</label>
          <div class="auth-input-wrapper">
            <input type="date" id="scheduled_date" name="scheduled_date" class="auth-input" style="padding-left:16px; color: var(--white);" required />
          </div>
        </div>
        <div class="auth-form-group">
          <label for="scheduled_time">Scheduled Time *</label>
          <div class="auth-input-wrapper">
            <input type="time" id="scheduled_time" name="scheduled_time" class="auth-input" style="padding-left:16px; color: var(--white);" required />
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="padding:12px 28px; width:100%; justify-content:center; background:var(--blue-600);"><i class="fas fa-calendar-check"></i> Schedule Program</button>
    </form>
  </section>

<!-- ──────────────────────────────────────────
     ACTION: VIEW DETAILS AND ATTENDANCE
     ────────────────────────────────────────── -->
<?php elseif ($action === 'view' && $viewProgram): ?>
  <!-- BACK BUTTON ROW -->
  <div style="margin-bottom: 20px;">
    <a href="feeding_programs.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Feeding Programs</a>
  </div>

  <!-- PROGRAM SPECIFICS CARD -->
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
        <label>Created On</label>
        <span><?php echo date('M d, Y h:i A', strtotime($viewProgram['created_at'])); ?></span>
      </div>
    </div>

    <?php if ($viewProgram['status'] === 'scheduled'): ?>
      <div style="margin-top:20px; display:flex; gap:10px;">
        <a href="feeding_programs.php?action=complete&id=<?php echo $viewProgram['id']; ?>" class="btn btn-success btn-sm" onclick="event.preventDefault(); Swal.fire({ title: 'Complete Feeding Session?', text: 'Are you sure you want to mark this program session as COMPLETED?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, complete', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => { if (result.isConfirmed) { window.location.href = this.href; } });">
          <i class="fas fa-check"></i> Mark Complete
        </a>
        <a href="feeding_programs.php?action=cancel&id=<?php echo $viewProgram['id']; ?>" class="btn btn-danger btn-sm" onclick="event.preventDefault(); Swal.fire({ title: 'Cancel Feeding Session?', text: 'Are you sure you want to CANCEL this program session?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, cancel', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => { if (result.isConfirmed) { window.location.href = this.href; } });">
          <i class="fas fa-times"></i> Cancel Session
        </a>
      </div>
    <?php endif; ?>
  </section>

  <!-- ATTENDANCE REGISTRY CARD -->
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
      <form action="feeding_programs.php?action=view&id=<?php echo $viewProgram['id']; ?>" method="POST" style="padding:0; margin:0;">
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
          <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 10px 24px;"><i class="fas fa-floppy-disk"></i> Save Attendance Registry</button>
        </div>
      </form>
    <?php endif; ?>
  </section>

<!-- ──────────────────────────────────────────
     DEFAULT LIST VIEW
     ────────────────────────────────────────── -->
<?php else: ?>

  <!-- Pill Tabs & Top Buttons Row -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
    <!-- Pill Tabs (Placed outside and above the dashboard-card container) -->
    <div class="pill-tabs" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
      <a href="feeding_programs.php?status=all&search=<?php echo urlencode($search); ?>&site_id=<?php echo urlencode($siteFilter); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>" class="pill-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Sessions</a>
      <a href="feeding_programs.php?status=scheduled&search=<?php echo urlencode($search); ?>&site_id=<?php echo urlencode($siteFilter); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>" class="pill-tab <?php echo $status_filter === 'scheduled' ? 'active' : ''; ?>">
        <i class="fas fa-calendar" style="font-size:0.8rem; margin-right:4px;"></i> Scheduled
      </a>
      <a href="feeding_programs.php?status=completed&search=<?php echo urlencode($search); ?>&site_id=<?php echo urlencode($siteFilter); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>" class="pill-tab <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
        <i class="fas fa-check-circle" style="font-size:0.8rem; margin-right:4px;"></i> Completed
      </a>
      <a href="feeding_programs.php?status=cancelled&search=<?php echo urlencode($search); ?>&site_id=<?php echo urlencode($siteFilter); ?>&date_start=<?php echo urlencode($dateStart); ?>&date_end=<?php echo urlencode($dateEnd); ?>" class="pill-tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
        <i class="fas fa-times-circle" style="font-size:0.8rem; margin-right:4px;"></i> Cancelled
      </a>
    </div>

    <!-- Actions -->
    <div>
      <a href="feeding_programs.php?action=add" class="btn btn-primary" style="padding:8px 20px; font-size:0.85rem; height:36px; display:inline-flex; align-items:center; justify-content:center;">
        <i class="fas fa-plus"></i> Schedule Session
      </a>
    </div>
  </div>

  <!-- Roster Filter Bar -->
  <section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
    <form action="feeding_programs.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
      
      <div style="flex:1.2; min-width:200px;">
        <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
        <input type="text" name="search" class="auth-input" placeholder="Search title or site..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
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

      <div style="flex:0.8; min-width:140px;">
        <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Start Date</label>
        <input type="date" name="date_start" class="auth-input" value="<?php echo htmlspecialchars($dateStart); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
      </div>

      <div style="flex:0.8; min-width:140px;">
        <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">End Date</label>
        <input type="date" name="date_end" class="auth-input" value="<?php echo htmlspecialchars($dateEnd); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
      </div>

      <div style="display:flex; gap:10px; width:auto;">
        <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
          <i class="fas fa-filter"></i> Apply Filters
        </button>
        <?php if (!empty($search) || !empty($siteFilter) || !empty($dateStart) || !empty($dateEnd)): ?>
          <a href="feeding_programs.php?status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
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
        Overview List
      </span>
    </div>
    
    <div class="panel-body" style="padding:0;">
      <?php if (count($programsList) > 0): ?>
        <div class="dark-table-wrap">
          <table class="dark-table">
            <thead>
              <tr>
                <th>Feeding Program Session</th>
                <th>Church Site Site</th>
                <th>Scheduled Date &amp; Time</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
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
                    <div style="display:inline-flex; gap:8px;">
                      <a href="feeding_programs.php?action=view&id=<?php echo $program['id']; ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> View &amp; Attendance
                      </a>
                      <?php if ($program['status'] === 'scheduled'): ?>
                        <a href="feeding_programs.php?action=complete&id=<?php echo $program['id']; ?>" class="btn btn-success btn-sm" onclick="event.preventDefault(); Swal.fire({ title: 'Complete Feeding Session?', text: 'Are you sure you want to mark this program session as COMPLETED?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, complete', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => { if (result.isConfirmed) { window.location.href = this.href; } });">
                          <i class="fas fa-check"></i> Complete
                        </a>
                        <a href="feeding_programs.php?action=cancel&id=<?php echo $program['id']; ?>" class="btn btn-danger btn-sm" onclick="event.preventDefault(); Swal.fire({ title: 'Cancel Feeding Session?', text: 'Are you sure you want to CANCEL this program session?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, cancel', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => { if (result.isConfirmed) { window.location.href = this.href; } });">
                          <i class="fas fa-ban"></i> Cancel
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state" style="padding: 60px; text-align: center;">
          <i class="fas fa-utensils empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
          <h4 style="color: var(--white); margin-bottom: 8px;">No Feeding Programs Found</h4>
          <p style="color: var(--gray-400);">There are no scheduled, completed, or cancelled feeding program sessions listed.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
