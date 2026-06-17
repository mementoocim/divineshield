<?php
/**
 * DivineShield - Staff / Encoder Submissions Review
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Submission Review";
$encoder_id = $_SESSION['user_id'];

$success = '';
$error = '';
if (isset($_SESSION['success_msg'])) { $success = $_SESSION['success_msg']; unset($_SESSION['success_msg']); }
if (isset($_SESSION['error_msg'])) { $error = $_SESSION['error_msg']; unset($_SESSION['error_msg']); }

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// ──────────────────────────────────────────
// HANDLE ACTIONS
// ──────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_submission'])) {
        $sub_id = intval($_POST['submission_id']);
        try {
            $pdo->beginTransaction();
            
            // Get submission details
            $stmt = $pdo->prepare("SELECT * FROM children_submissions WHERE id = ? AND submission_status = 'pending'");
            $stmt->execute([$sub_id]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sub) {
                // 1. Update Submission
                $stmtUp = $pdo->prepare("UPDATE children_submissions SET submission_status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $stmtUp->execute([$encoder_id, $sub_id]);
                
                // 2. Insert to children registry
                $stmtChild = $pdo->prepare("INSERT INTO children (submission_id, church_site_id, first_name, last_name, middle_name, gender, birthdate, guardian_name, status) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmtChild->execute([
                    $sub_id, $sub['church_site_id'], $sub['first_name'], $sub['last_name'], $sub['middle_name'], 
                    $sub['gender'], $sub['birthdate'], $sub['guardian_name']
                ]);
                $child_id = $pdo->lastInsertId();
                
                // 3. Insert initial nutritional assessment
                $stmtNutri = $pdo->prepare("INSERT INTO nutritional_assessments (child_id, encoder_id, weight, height, bmi, bmi_status, assessment_date, notes) 
                                            VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE, 'Initial assessment from registration submission')");
                $stmtNutri->execute([
                    $child_id, $encoder_id, $sub['initial_weight'], $sub['initial_height'], $sub['initial_bmi'], $sub['initial_bmi_status']
                ]);
                
                $pdo->commit();
                
                // Log Audit
                logAudit($pdo, $encoder_id, 'Approve Submission', "Approved submission ID $sub_id and registered child ID $child_id");
                
                $_SESSION['success_msg'] = "Submission approved successfully. Child is now registered.";
            } else {
                $pdo->rollBack();
                $_SESSION['error_msg'] = "Invalid submission or already processed.";
            }
            header("Location: submissions.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Error approving submission: " . $e->getMessage();
            header("Location: submissions.php");
            exit;
        }
    } elseif (isset($_POST['reject_submission'])) {
        $sub_id = intval($_POST['submission_id']);
        $reason = trim($_POST['review_notes']);
        
        if (empty($reason)) {
            $_SESSION['error_msg'] = "Rejection reason is required.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE children_submissions SET submission_status = 'rejected', review_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND submission_status = 'pending'");
                $stmt->execute([$reason, $encoder_id, $sub_id]);
                
                if ($stmt->rowCount() > 0) {
                    logAudit($pdo, $encoder_id, 'Reject Submission', "Rejected submission ID $sub_id with reason: $reason");
                    $_SESSION['success_msg'] = "Submission has been rejected.";
                } else {
                    $_SESSION['error_msg'] = "Invalid submission or already processed.";
                }
            } catch (Exception $e) {
                $_SESSION['error_msg'] = "Error rejecting submission: " . $e->getMessage();
            }
        }
        header("Location: submissions.php");
        exit;
    }
}

// ──────────────────────────────────────────
// RENDER VIEWS
// ──────────────────────────────────────────

include 'includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px; padding:15px; background:rgba(45,212,191,0.1); border-left:4px solid var(--teal-500); color:var(--teal-100);">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger" style="margin-bottom:20px; padding:15px; background:rgba(239,68,68,0.1); border-left:4px solid var(--red-500); color:var(--red-100);">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($action === 'view' && $id > 0): ?>
    <?php
    // View Single Submission
    $stmt = $pdo->prepare("SELECT s.*, cs.church_name, u.first_name as leader_first, u.last_name as leader_last 
                           FROM children_submissions s 
                           JOIN church_sites cs ON s.church_site_id = cs.id
                           JOIN users u ON s.church_leader_id = u.id
                           WHERE s.id = ?");
    $stmt->execute([$id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sub) {
        echo "<div class='empty-state'><h4>Submission not found.</h4><a href='submissions.php' class='btn btn-outline'>Go Back</a></div>";
    } else {
        $age = date_diff(date_create($sub['birthdate']), date_create('today'))->y;
    ?>
    <div class="admin-panel">
        <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 class="panel-title">Review Submission #<?php echo $sub['id']; ?></h3>
            <a href="submissions.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
        <div class="panel-body">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- Child Details -->
                <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                    <h4 style="color:var(--blue-400); margin-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;"><i class="fas fa-user"></i> Child Information</h4>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['middle_name'] . ' ' . $sub['last_name']); ?></p>
                    <p><strong>Gender:</strong> <?php echo ucfirst($sub['gender']); ?></p>
                    <p><strong>Birthdate:</strong> <?php echo date('M d, Y', strtotime($sub['birthdate'])); ?> (<?php echo $age; ?> yrs old)</p>
                    <p><strong>Guardian:</strong> <?php echo htmlspecialchars($sub['guardian_name']); ?> (<?php echo htmlspecialchars($sub['guardian_relationship']); ?>)</p>
                </div>
                
                <!-- Initial Assessment & Source -->
                <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                    <h4 style="color:var(--teal-400); margin-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;"><i class="fas fa-weight-scale"></i> Initial Assessment</h4>
                    <p><strong>Height:</strong> <?php echo $sub['initial_height']; ?> cm</p>
                    <p><strong>Weight:</strong> <?php echo $sub['initial_weight']; ?> kg</p>
                    <p><strong>BMI:</strong> <?php echo $sub['initial_bmi']; ?></p>
                    <p><strong>BMI Status:</strong> <span class="badge" style="background:var(--blue-900); color:var(--blue-300); padding:2px 8px; border-radius:4px;"><?php echo htmlspecialchars($sub['initial_bmi_status']); ?></span></p>
                    <div style="margin-top:15px; padding-top:15px; border-top:1px solid rgba(255,255,255,0.1);">
                        <p><strong>Church Site:</strong> <?php echo htmlspecialchars($sub['church_name']); ?></p>
                        <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($sub['leader_first'] . ' ' . $sub['leader_last']); ?></p>
                        <p><strong>Date Submitted:</strong> <?php echo date('M d, Y h:i A', strtotime($sub['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <?php if ($sub['submission_status'] === 'pending'): ?>
                <div style="background: rgba(251,191,36,0.05); padding: 20px; border-radius: 8px; border: 1px dashed var(--yellow-500); margin-bottom: 20px;">
                    <h4 style="color:var(--yellow-400); margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Action Required</h4>
                    <p style="margin-bottom: 15px; color: var(--gray-300);">System suggested status is: <strong><?php echo $sub['suggested_status']; ?></strong> based on BMI data. Please verify and decide.</p>
                    
                    <div style="display:flex; gap: 15px;">
                        <!-- Approve Form -->
                        <form id="approveForm_<?php echo $sub['id']; ?>" method="POST" action="submissions.php">
                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                            <input type="hidden" name="approve_submission" value="1">
                            <button type="button" class="btn btn-success" onclick="event.preventDefault(); Swal.fire({ title: 'Approve Submission?', text: 'Are you sure you want to APPROVE this child? They will be permanently added to the registry.', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, approve', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => { if (result.isConfirmed) { document.getElementById('approveForm_<?php echo $sub['id']; ?>').submit(); } });">
                                <i class="fas fa-check"></i> Approve & Register Child
                            </button>
                        </form>
                        
                        <!-- Reject Button triggers modal -->
                        <button type="button" class="btn btn-danger" onclick="document.getElementById('rejectModal').style.display='block';"><i class="fas fa-times"></i> Reject Submission</button>
                    </div>
                </div>

                <!-- Reject Modal -->
                <div id="rejectModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; padding:20px;">
                    <div style="background:var(--blue-900); max-width:500px; margin: 100px auto; border-radius:12px; border:1px solid rgba(255,255,255,0.1); overflow:hidden;">
                        <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="color:var(--white); margin:0;">Reject Submission</h3>
                            <button onclick="document.getElementById('rejectModal').style.display='none';" style="background:none; border:none; color:var(--gray-400); cursor:pointer; font-size:1.5rem;">&times;</button>
                        </div>
                        <form method="POST" action="submissions.php">
                            <div style="padding:20px;">
                                <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                <div class="form-group">
                                    <label class="form-label text-white">Reason for Rejection *</label>
                                    <textarea name="review_notes" class="form-control" rows="4" required placeholder="Explain why this child is disqualified (e.g., BMI is normal, outside target age...)" style="background:var(--blue-950); color:white; border:1px solid rgba(255,255,255,0.2); width:100%; padding:10px; border-radius:4px;"></textarea>
                                </div>
                            </div>
                            <div style="padding:20px; background:rgba(0,0,0,0.2); text-align:right;">
                                <button type="button" class="btn btn-outline" onclick="document.getElementById('rejectModal').style.display='none';">Cancel</button>
                                <button type="submit" name="reject_submission" class="btn btn-danger" style="margin-left:10px;">Confirm Rejection</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding: 20px; border-radius: 8px; <?php echo $sub['submission_status'] === 'approved' ? 'background:rgba(45,212,191,0.1); border:1px solid var(--teal-500);' : 'background:rgba(239,68,68,0.1); border:1px solid var(--red-500);'; ?>">
                    <h4 style="margin-bottom: 10px; color: <?php echo $sub['submission_status'] === 'approved' ? 'var(--teal-400)' : 'var(--red-400)'; ?>">
                        <i class="fas <?php echo $sub['submission_status'] === 'approved' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i> 
                        This submission has been <?php echo strtoupper($sub['submission_status']); ?>
                    </h4>
                    <p><strong>Reviewed On:</strong> <?php echo date('M d, Y h:i A', strtotime($sub['reviewed_at'])); ?></p>
                    <?php if ($sub['submission_status'] === 'rejected'): ?>
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($sub['review_notes']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    }
    ?>

<?php else: ?>

    <?php
    // List View
    $tab = $_GET['tab'] ?? 'pending';
    $validTabs = ['pending', 'approved', 'rejected'];
    if (!in_array($tab, $validTabs)) $tab = 'pending';

    $search = trim($_GET['search'] ?? '');
    $siteFilter = $_GET['site_id'] ?? '';

    // Fetch all church sites for filter dropdown
    $stmtSites = $pdo->query("SELECT id, church_name FROM church_sites ORDER BY church_name ASC");
    $churchSites = $stmtSites->fetchAll(PDO::FETCH_ASSOC);

    $query = "SELECT s.*, cs.church_name 
              FROM children_submissions s 
              JOIN church_sites cs ON s.church_site_id = cs.id
              WHERE s.submission_status = ?";
    $params = [$tab];

    if (!empty($search)) {
        $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR cs.church_name LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($siteFilter)) {
        $query .= " AND s.church_site_id = ?";
        $params[] = intval($siteFilter);
    }

    $query .= " ORDER BY s.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll();
    ?>

    <!-- Pill Tab Bar (Outside Card) -->
    <div class="pill-tabs" style="margin-bottom: 24px;">
        <a href="submissions.php?tab=pending&search=<?php echo urlencode($search); ?>&site_id=<?php echo urlencode($siteFilter); ?>" class="pill-tab <?php echo $tab === 'pending' ? 'active' : ''; ?>" style="text-decoration: none;">
            <i class="fas fa-clock"></i> Pending
        </a>
        <a href="submissions.php?tab=approved&search=<?php echo urlencode($search); ?>&site_id=<?php echo urlencode($siteFilter); ?>" class="pill-tab <?php echo $tab === 'approved' ? 'active' : ''; ?>" style="text-decoration: none;">
            <i class="fas fa-check-circle"></i> Approved
        </a>
        <a href="submissions.php?tab=rejected&search=<?php echo urlencode($search); ?>&site_id=<?php echo urlencode($siteFilter); ?>" class="pill-tab <?php echo $tab === 'rejected' ? 'active' : ''; ?>" style="text-decoration: none;">
            <i class="fas fa-ban"></i> Rejected
        </a>
    </div>

    <!-- Search & Filters Bar conforming to design system -->
    <section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
      <form action="submissions.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
        
        <div style="flex:1.2; min-width:200px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
          <input type="text" name="search" class="auth-input" placeholder="Search child name..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
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

        <div style="display:flex; gap:10px; width:auto;">
          <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
            <i class="fas fa-filter"></i> Apply Filters
          </button>
          <?php if (!empty($search) || !empty($siteFilter)): ?>
            <a href="submissions.php?tab=<?php echo urlencode($tab); ?>" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
              <i class="fas fa-filter-circle-xmark"></i> Clear
            </a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <!-- Main Table Card -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">Children Submissions
            </div>
            <span style="font-size:0.75rem; font-weight:700; background:rgba(255,255,255,0.05); color:var(--gray-300); padding:4px 10px; border-radius:999px;">
                <?php echo ucfirst($tab); ?> Registry
            </span>
        </div>
        
        <?php if (count($submissions) > 0): ?>
            <div class="dark-table-wrap">
                <table class="dark-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Child Name</th>
                            <th>Church Site</th>
                            <th>Age</th>
                            <th>BMI Status</th>
                            <th>Suggested</th>
                            <th>Date Submitted</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($submissions as $sub): 
                            $age = date_diff(date_create($sub['birthdate']), date_create('today'))->y;
                        ?>
                        <tr>
                            <td class="text-muted">#<?php echo $sub['id']; ?></td>
                            <td class="fw-semibold text-white">
                                <?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($sub['church_name']); ?></td>
                            <td><?php echo $age; ?> yrs</td>
                            <td><?php echo htmlspecialchars($sub['initial_bmi_status']); ?></td>
                            <td>
                                <?php if($sub['suggested_status'] === 'qualified'): ?>
                                    <span class="status-badge success"><i class="fas fa-check-circle"></i> Qualified</span>
                                <?php else: ?>
                                    <span class="status-badge error"><i class="fas fa-times-circle"></i> Disqualified</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?php echo date('M d, Y', strtotime($sub['created_at'])); ?></td>
                            <td class="text-right">
                                <a href="submissions.php?action=view&id=<?php echo $sub['id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> <?php echo $tab === 'pending' ? 'Review' : 'View'; ?>
                                </a>
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

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
