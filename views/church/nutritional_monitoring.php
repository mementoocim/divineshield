<?php
/**
 * DivineShield - Church Leader Nutritional Monitoring
 */
require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'church_leader') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Nutritional Monitoring";
$user_id = $_SESSION['user_id'];

// get site id for leader
$stmtSite = $pdo->prepare("SELECT id FROM church_sites WHERE church_leader_id = ?");
$stmtSite->execute([$user_id]);
$church_site_id = $stmtSite->fetchColumn();

if (!$church_site_id) {
    $church_site_id = 0;
}

$success = '';
$error = '';
if (isset($_SESSION['success_msg'])) { $success = $_SESSION['success_msg']; unset($_SESSION['success_msg']); }
if (isset($_SESSION['error_msg'])) { $error = $_SESSION['error_msg']; unset($_SESSION['error_msg']); }

$action = $_GET['action'] ?? 'list';
$child_id = intval($_GET['child_id'] ?? 0);

// handle actions

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessment'])) {
    $child_id = intval($_POST['child_id']);
    $weight = floatval($_POST['weight'] ?? 0);
    $height = floatval($_POST['height'] ?? 0);
    $assessment_date = $_POST['assessment_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');

    if ($child_id <= 0 || $weight <= 0 || $height <= 0) {
        $_SESSION['error_msg'] = "All fields are required. Height and weight must be greater than zero.";
        header("Location: nutritional_monitoring.php?action=record&child_id=" . $child_id);
        exit;
    }

    try {
        // Fetch child details and verify authorization
        $stmtChild = $pdo->prepare("SELECT first_name, last_name, church_site_id FROM children WHERE id = ?");
        $stmtChild->execute([$child_id]);
        $childObj = $stmtChild->fetch();
        
        if (!$childObj || $childObj['church_site_id'] != $church_site_id) {
            throw new Exception("Unauthorized database access for this child.");
        }
        
        $childName = $childObj['first_name'] . ' ' . $childObj['last_name'];

        // calc bmi
        $heightInM = $height / 100;
        $bmi = round($weight / ($heightInM * $heightInM), 2);

        // get bmi class
        if ($bmi < 15.0) {
            $bmiStatus = 'Severely Underweight';
        } elseif ($bmi >= 15.0 && $bmi < 16.5) {
            $bmiStatus = 'Underweight';
        } elseif ($bmi >= 16.5 && $bmi <= 22.0) {
            $bmiStatus = 'Normal Weight';
        } else {
            $bmiStatus = 'Overweight / Obese';
        }

        // Insert Assessment
        $stmt = $pdo->prepare("INSERT INTO nutritional_assessments (child_id, encoder_id, weight, height, bmi, bmi_status, assessment_date, notes) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$child_id, $user_id, $weight, $height, $bmi, $bmiStatus, $assessment_date, $notes]);

        logAudit($pdo, $user_id, 'RECORD_BMI', "Pastor recorded nutritional assessment for child: $childName (BMI: $bmi, Status: $bmiStatus)");

        $_SESSION['success_msg'] = "Nutritional assessment recorded successfully for " . htmlspecialchars($childName) . ".";
        header("Location: nutritional_monitoring.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error saving assessment: " . $e->getMessage();
        header("Location: nutritional_monitoring.php?action=record&child_id=" . $child_id);
        exit;
    }
}

include 'includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px; padding:15px; background:rgba(45,212,191,0.1); border-left:4px solid var(--teal-500); color:var(--teal-100); border-radius: 4px;">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger" style="margin-bottom:20px; padding:15px; background:rgba(239,68,68,0.1); border-left:4px solid var(--red-500); color:var(--red-100); border-radius: 4px;">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($action === 'record' && $child_id > 0): ?>
    <?php
    // get child data (Ensuring child belongs to leader's site)
    $stmt = $pdo->prepare("SELECT c.*, cs.church_name 
                           FROM children c 
                           JOIN church_sites cs ON c.church_site_id = cs.id
                           WHERE c.id = ? AND c.church_site_id = ?");
    $stmt->execute([$child_id, $church_site_id]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$child) {
        echo "<div class='dashboard-card'><h3 style='color:var(--white);'>Child record not found or access denied.</h3><a href='nutritional_monitoring.php' class='btn btn-outline btn-sm' style='margin-top:15px;'>Back to List</a></div>";
    } else {
    ?>
    <div class="admin-panel">
        <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 class="panel-title">Record Monthly Assessment</h3>
            <a href="nutritional_monitoring.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
        <div class="panel-body">
            <!-- Child Details Box -->
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:16px; border-radius:8px; margin-bottom:24px; display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <h4 style="color:var(--blue-400); margin-bottom:8px;"><i class="fas fa-child"></i> Beneficiary Details</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></p>
                    <p><strong>Gender / Birthdate:</strong> <?php echo ucfirst($child['gender']) . ' / ' . date('M d, Y', strtotime($child['birthdate'])); ?></p>
                </div>
                <div>
                    <h4 style="color:var(--teal-400); margin-bottom:8px;"><i class="fas fa-church"></i> Location</h4>
                    <p><strong>Church Site:</strong> <?php echo htmlspecialchars($child['church_name']); ?></p>
                </div>
            </div>

            <!-- Record Form -->
            <form method="POST" action="nutritional_monitoring.php" autocomplete="off">
                <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                <input type="hidden" name="save_assessment" value="1">

                <div class="form-grid-2" style="margin-bottom:20px;">
                    <div class="auth-form-group">
                        <label for="height">Height (in centimeters) *</label>
                        <div class="auth-input-wrapper">
                            <input type="number" step="0.1" id="height" name="height" class="auth-input" style="padding-left:16px;" placeholder="e.g. 110.5" required>
                        </div>
                    </div>
                    <div class="auth-form-group">
                        <label for="weight">Weight (in kilograms) *</label>
                        <div class="auth-input-wrapper">
                            <input type="number" step="0.1" id="weight" name="weight" class="auth-input" style="padding-left:16px;" placeholder="e.g. 18.2" required>
                        </div>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom:20px;">
                    <div class="auth-form-group">
                        <label for="assessment_date">Assessment Date *</label>
                        <div class="auth-input-wrapper">
                            <input type="date" id="assessment_date" name="assessment_date" class="auth-input" style="padding-left:16px;" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="auth-form-group">
                        <label for="notes">Notes / Observations</label>
                        <div class="auth-input-wrapper">
                            <input type="text" id="notes" name="notes" class="auth-input" style="padding-left:16px;" placeholder="Optional details...">
                        </div>
                    </div>
                </div>

                <!-- Live BMI Display -->
                <div class="dashboard-card" id="live-bmi-card" style="display:none; background:rgba(30, 41, 59, 0.4); border-color:rgba(59, 130, 246, 0.2); margin: 24px 0;">
                    <h4 style="font-family:var(--font-head); font-size:0.9rem; text-transform:uppercase; color:var(--blue-400); margin-bottom:14px; font-weight:700;">
                        <i class="fas fa-calculator" style="margin-right:8px;"></i> Live BMI Status
                    </h4>
                    <div style="display:flex; gap:30px; align-items:center;">
                        <div>
                            <span style="font-size:0.8rem; color:var(--gray-400); display:block;">Calculated BMI</span>
                            <span id="bmi_live_val" style="font-size:1.5rem; font-weight:800; color:var(--white);">0.00</span>
                        </div>
                        <div>
                            <span style="font-size:0.8rem; color:var(--gray-400); display:block;">Nutritional Classification</span>
                            <span id="bmi_status_live_val" style="font-weight:700; color:var(--teal-400); font-size:1.1rem;">Normal</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;"><i class="fas fa-save"></i> Save Assessment</button>
            </form>
        </div>
    </div>

    <script>
        const weightInput = document.getElementById('weight');
        const heightInput = document.getElementById('height');
        const bmiOutput = document.getElementById('bmi_live_val');
        const bmiStatusOutput = document.getElementById('bmi_status_live_val');

        function calculateLiveBMI() {
            const w = parseFloat(weightInput.value);
            const h = parseFloat(heightInput.value);

            if (w > 0 && h > 0) {
                const heightInM = h / 100;
                const bmi = w / (heightInM * heightInM);
                const bmiFixed = bmi.toFixed(2);

                bmiOutput.textContent = bmiFixed;

                let status = '';
                if (bmi < 15.0) {
                    status = 'Severely Underweight';
                } else if (bmi >= 15.0 && bmi < 16.5) {
                    status = 'Underweight';
                } else if (bmi >= 16.5 && bmi <= 22.0) {
                    status = 'Normal Weight';
                } else {
                    status = 'Overweight / Obese';
                }

                bmiStatusOutput.textContent = status;
                document.getElementById('live-bmi-card').style.display = 'block';
            } else {
                document.getElementById('live-bmi-card').style.display = 'none';
            }
        }

        if (weightInput && heightInput) {
            weightInput.addEventListener('input', calculateLiveBMI);
            heightInput.addEventListener('input', calculateLiveBMI);
        }
    </script>
    <?php } ?>

<?php elseif ($action === 'history' && $child_id > 0): ?>
    <?php
    // get child data (Verify belongs to site)
    $stmt = $pdo->prepare("SELECT c.*, cs.church_name 
                           FROM children c 
                           JOIN church_sites cs ON c.church_site_id = cs.id
                           WHERE c.id = ? AND c.church_site_id = ?");
    $stmt->execute([$child_id, $church_site_id]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$child) {
        echo "<div class='dashboard-card'><h3 style='color:var(--white);'>Child record not found or access denied.</h3><a href='nutritional_monitoring.php' class='btn btn-outline btn-sm' style='margin-top:15px;'>Back to List</a></div>";
    } else {
        // get assessment history
        $stmtHist = $pdo->prepare("SELECT na.*, u.first_name, u.last_name, u.role
                                   FROM nutritional_assessments na 
                                   JOIN users u ON na.encoder_id = u.id
                                   WHERE na.child_id = ? 
                                   ORDER BY na.assessment_date DESC, na.id DESC");
        $stmtHist->execute([$child_id]);
        $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="admin-panel">
        <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 class="panel-title">Assessment History: <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h3>
            <div style="display:flex; gap:10px;">
                <a href="nutritional_monitoring.php?action=record&child_id=<?php echo $child['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> New Assessment</a>
                <a href="nutritional_monitoring.php" class="btn btn-outline btn-sm">Back to List</a>
            </div>
        </div>
        <div class="panel-body" style="padding:0;">
            <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.01);">
                <p><strong>Church Site:</strong> <?php echo htmlspecialchars($child['church_name']); ?></p>
                <p><strong>Gender / Birthdate:</strong> <?php echo ucfirst($child['gender']) . ' / ' . date('M d, Y', strtotime($child['birthdate'])); ?></p>
            </div>

            <?php if (count($history) > 0): ?>
                <div class="dark-table-wrap">
                    <table class="dark-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Height (cm)</th>
                                <th>Weight (kg)</th>
                                <th>BMI</th>
                                <th>Status</th>
                                <th>Assessed By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($history as $record): ?>
                            <tr>
                                <td class="fw-semibold text-white"><?php echo date('M d, Y', strtotime($record['assessment_date'])); ?></td>
                                <td><?php echo number_format($record['height'], 1); ?> cm</td>
                                <td><?php echo number_format($record['weight'], 1); ?> kg</td>
                                <td class="text-white"><?php echo number_format($record['bmi'], 2); ?></td>
                                <td>
                                    <?php
                                    $bStat = $record['bmi_status'];
                                    if ($bStat === 'Normal Weight' || $bStat === 'Normal') {
                                        echo '<span class="status-badge success"><i class="fas fa-circle-check"></i> ' . htmlspecialchars($bStat) . '</span>';
                                    } elseif ($bStat === 'Underweight') {
                                        echo '<span class="status-badge warning" style="background:rgba(251,191,36,0.1); color:var(--yellow-400);"><i class="fas fa-circle-exclamation"></i> Underweight</span>';
                                    } else {
                                        echo '<span class="status-badge error"><i class="fas fa-circle-xmark"></i> ' . htmlspecialchars($bStat) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name'] . ' (' . ucfirst($record['role']) . ')'); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 40px; text-align: center;">
                    <i class="fas fa-history empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                    <h4 style="color: var(--white); margin-bottom: 8px;">No Assessments Recorded</h4>
                    <p style="color: var(--gray-400);">No historical records exist for this child.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php } ?>

<?php else: ?>
    <?php
    // load filters
    $search = trim($_GET['search'] ?? '');
    $statusFilter = $_GET['bmi_status'] ?? '';

    // Default list view: fetch children with their LATEST assessment if it exists (restricted to leader's site)
    $query = "SELECT c.id as child_id, c.first_name, c.last_name, c.gender, cs.church_name,
                     na.bmi, na.bmi_status, na.assessment_date
              FROM children c
              JOIN church_sites cs ON c.church_site_id = cs.id
              LEFT JOIN (
                  SELECT na1.* FROM nutritional_assessments na1
                  JOIN (
                      SELECT child_id, MAX(assessment_date) as max_date, MAX(id) as max_id 
                      FROM nutritional_assessments 
                      GROUP BY child_id
                  ) na2 ON na1.child_id = na2.child_id AND na1.assessment_date = na2.max_date AND na1.id = na2.max_id
              ) na ON c.id = na.child_id
              WHERE c.status = 'active' AND c.church_site_id = ?";

    $params = [$church_site_id];

    if (!empty($search)) {
        $query .= " AND (c.first_name LIKE ? OR c.last_name LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($statusFilter)) {
        if ($statusFilter === 'Not Assessed') {
            $query .= " AND na.bmi_status IS NULL";
        } else {
            $query .= " AND na.bmi_status = ?";
            $params[] = $statusFilter;
        }
    }

    $query .= " ORDER BY c.first_name ASC, c.last_name ASC";

    $stmtList = $pdo->prepare($query);
    $stmtList->execute($params);
    $activeChildren = $stmtList->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <!-- Search & Filters Bar conforming to design system -->
    <section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
      <form action="nutritional_monitoring.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
        
        <div style="flex:1.2; min-width:200px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
          <input type="text" name="search" class="auth-input filter-input" placeholder="Search child by name..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
        </div>

        <div style="flex:1; min-width:150px;">
          <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">BMI Status</label>
          <select name="bmi_status" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
            <option value="">-- All --</option>
            <option value="Normal Weight" <?php echo $statusFilter === 'Normal Weight' ? 'selected' : ''; ?>>Normal Weight</option>
            <option value="Underweight" <?php echo $statusFilter === 'Underweight' ? 'selected' : ''; ?>>Underweight</option>
            <option value="Severely Underweight" <?php echo $statusFilter === 'Severely Underweight' ? 'selected' : ''; ?>>Severely Underweight</option>
            <option value="Not Assessed" <?php echo $statusFilter === 'Not Assessed' ? 'selected' : ''; ?>>Not Assessed</option>
          </select>
        </div>

        <div style="display:flex; gap:10px; width:auto;">
          <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
            <i class="fas fa-filter"></i> Apply Filters
          </button>
          <?php if (!empty($search) || !empty($statusFilter)): ?>
            <a href="nutritional_monitoring.php" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
              <i class="fas fa-filter-circle-xmark"></i> Clear
            </a>
          <?php endif; ?>
        </div>
      </form>
    </section>
    <div class="admin-panel">
        <div class="panel-header">
            <h3 class="panel-title">Nutritional Monitoring Overview</h3>
        </div>
        <div class="panel-body" style="padding:0;">
            <?php if (count($activeChildren) > 0): ?>
                <div class="dark-table-wrap">
                    <table class="dark-table">
                        <thead>
                            <tr>
                                <th>Child Name</th>
                                <th>Church Site</th>
                                <th>Latest BMI</th>
                                <th>Nutritional Status</th>
                                <th>Last Assessed</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($activeChildren as $child): ?>
                            <tr>
                                <td class="fw-semibold text-white">
                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($child['church_name']); ?></td>
                                <td class="text-white"><?php echo $child['bmi'] ? number_format($child['bmi'], 2) : '—'; ?></td>
                                <td>
                                    <?php if ($child['bmi_status']): ?>
                                        <?php
                                        $status = $child['bmi_status'];
                                        if ($status === 'Normal Weight' || $status === 'Normal') {
                                            echo '<span class="status-badge success"><i class="fas fa-circle-check"></i> ' . htmlspecialchars($status) . '</span>';
                                        } elseif ($status === 'Underweight') {
                                            echo '<span class="status-badge warning" style="background:rgba(251,191,36,0.1); color:var(--yellow-400);"><i class="fas fa-circle-exclamation"></i> Underweight</span>';
                                        } else {
                                            echo '<span class="status-badge error"><i class="fas fa-circle-xmark"></i> ' . htmlspecialchars($status) . '</span>';
                                        }
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assessed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?php echo $child['assessment_date'] ? date('M d, Y', strtotime($child['assessment_date'])) : '—'; ?></td>
                                <td class="text-right" style="display:flex; gap:10px; justify-content:flex-end;">
                                    <a href="nutritional_monitoring.php?action=record&child_id=<?php echo $child['child_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Record BMI</a>
                                    <a href="nutritional_monitoring.php?action=history&child_id=<?php echo $child['child_id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-history"></i> History</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 60px; text-align: center;">
                    <i class="fas fa-children empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                    <h4 style="color: var(--white); margin-bottom: 8px;">No Active Children Found</h4>
                    <p style="color: var(--gray-400);">Submit new beneficiaries first to begin monitoring.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
