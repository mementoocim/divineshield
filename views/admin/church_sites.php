<?php
/**
 * DivineShield - Church Sites & Leaders Management
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

// Helper function to format address without city/barangay duplication
function formatChurchAddress($address, $barangay, $city, $province) {
    $parts = [];
    if (!empty($address)) {
        foreach (explode(',', $address) as $p) {
            $trimmed = trim($p);
            if (!empty($trimmed)) {
                $parts[] = $trimmed;
            }
        }
    }
    if (!empty($barangay)) $parts[] = trim($barangay);
    if (!empty($city)) $parts[] = trim($city);
    if (!empty($province)) $parts[] = trim($province);
    
    $seen = [];
    $unique = [];
    foreach ($parts as $part) {
        $lower = strtolower($part);
        if (!in_array($lower, $seen)) {
            $seen[] = $lower;
            $unique[] = $part;
        }
    }
    return implode(', ', $unique);
}

// Helper to extract clean street address (strip redundant location tokens)
function cleanStreetAddress($address, $barangay, $city, $province) {
    if (empty($address)) return 'N/A';
    $addrParts = array_map('trim', explode(',', $address));
    $cleanParts = [];
    $redundantTokens = [
        strtolower(trim($barangay)),
        strtolower(trim($city)),
        strtolower(trim($province))
    ];
    
    foreach ($addrParts as $part) {
        $lowerPart = strtolower($part);
        $isRedundant = false;
        foreach ($redundantTokens as $token) {
            if (!empty($token) && ($lowerPart === $token || strpos($lowerPart, $token) !== false || strpos($token, $lowerPart) !== false)) {
                $isRedundant = true;
                break;
            }
        }
        if (!$isRedundant) {
            $cleanParts[] = $part;
        }
    }
    return empty($cleanParts) ? 'N/A' : implode(', ', $cleanParts);
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// ──────────────────────────────────────────
// HANDLE ACTIONS: APPROVE & REJECT LEADERS
// ──────────────────────────────────────────
$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if ($action === 'approve_leader' && $id > 0) {
    try {
        // Fetch leader info for logging
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ? AND role = 'church_leader'");
        $stmt->execute([$id]);
        $leader = $stmt->fetch();
        
        if ($leader) {
            $username = $leader['username'];
            
            // Begin Transaction
            $pdo->beginTransaction();
            
            // Update user status
            $stmtUpdate = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmtUpdate->execute([$id]);
            
            // Log audit
            logAudit($pdo, $_SESSION['user_id'], 'ACCOUNT_ACTIVATED', "Manually approved and activated church leader: @$username (ID: $id)");
            
            $pdo->commit();
            $_SESSION['success_msg'] = "Pastor @$username's account has been successfully activated!";
        } else {
            $_SESSION['error_msg'] = "Church Leader account not found.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = "Error approving leader: " . $e->getMessage();
    }
    header("Location: church_sites.php");
    exit;
}

if ($action === 'reject_leader' && $id > 0) {
    try {
        // Fetch leader info for logging
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ? AND role = 'church_leader'");
        $stmt->execute([$id]);
        $leader = $stmt->fetch();
        
        if ($leader) {
            $username = $leader['username'];
            
            // Begin Transaction
            $pdo->beginTransaction();
            
            // Set status to 'inactive' to block login
            $stmtUpdate = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmtUpdate->execute([$id]);
            
            // Log audit
            logAudit($pdo, $_SESSION['user_id'], 'ACCOUNT_DEACTIVATED', "Rejected and disabled church leader: @$username (ID: $id)");
            
            $pdo->commit();
            $_SESSION['success_msg'] = "Pastor @$username's account has been deactivated/rejected.";
        } else {
            $_SESSION['error_msg'] = "Church Leader account not found.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = "Error rejecting leader: " . $e->getMessage();
    }
    header("Location: church_sites.php");
    exit;
}

// ──────────────────────────────────────────
// ──────────────────────────────────────────
// HANDLE CHILD ACTIONS: APPROVE, REJECT, REVIEW
// ──────────────────────────────────────────
if ($action === 'approve_child' && $id > 0) {
    try {
        // Fetch child submission
        $stmt = $pdo->prepare("SELECT * FROM children_submissions WHERE id = ? AND submission_status = 'pending'");
        $stmt->execute([$id]);
        $submission = $stmt->fetch();
        
        if ($submission) {
            $pdo->beginTransaction();
            
            // Update submission status to approved
            $stmtUpdate = $pdo->prepare("UPDATE children_submissions SET submission_status = 'approved', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtUpdate->execute([$_SESSION['user_id'], $id]);
            
            // Check if already registered
            $stmtCheck = $pdo->prepare("SELECT id FROM children WHERE submission_id = ?");
            $stmtCheck->execute([$id]);
            if (!$stmtCheck->fetch()) {
                // Insert into official children registry
                $stmtInsert = $pdo->prepare("INSERT INTO children (submission_id, church_site_id, first_name, last_name, middle_name, gender, birthdate, guardian_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmtInsert->execute([
                    $id,
                    $submission['church_site_id'],
                    $submission['first_name'],
                    $submission['last_name'],
                    $submission['middle_name'],
                    $submission['gender'],
                    $submission['birthdate'],
                    $submission['guardian_name']
                ]);
            }
            
            // Log audit trail
            logAudit($pdo, $_SESSION['user_id'], 'CHILD_SUBMISSION_APPROVED', "Approved child beneficiary submission: " . $submission['first_name'] . " " . $submission['last_name'] . " for Site ID: " . $submission['church_site_id']);
            
            $pdo->commit();
            $_SESSION['success_msg'] = "Child submission has been successfully approved and registered!";
        } else {
            $_SESSION['error_msg'] = "Child submission not found or already processed.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = "Error approving child: " . $e->getMessage();
    }
    
    $redirectSiteId = intval($_GET['site_id'] ?? 0);
    header("Location: church_sites.php?action=view&id=" . ($redirectSiteId > 0 ? $redirectSiteId : $id));
    exit;
}

if ($action === 'reject_child' && $id > 0) {
    try {
        // Fetch child submission
        $stmt = $pdo->prepare("SELECT * FROM children_submissions WHERE id = ? AND submission_status = 'pending'");
        $stmt->execute([$id]);
        $submission = $stmt->fetch();
        
        if ($submission) {
            $pdo->beginTransaction();
            
            // Update submission status to rejected
            $stmtUpdate = $pdo->prepare("UPDATE children_submissions SET submission_status = 'rejected', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtUpdate->execute([$_SESSION['user_id'], $id]);
            
            // Log audit trail
            logAudit($pdo, $_SESSION['user_id'], 'CHILD_SUBMISSION_REJECTED', "Rejected child beneficiary submission: " . $submission['first_name'] . " " . $submission['last_name'] . " for Site ID: " . $submission['church_site_id']);
            
            $pdo->commit();
            $_SESSION['success_msg'] = "Child submission has been rejected.";
        } else {
            $_SESSION['error_msg'] = "Child submission not found or already processed.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = "Error rejecting child: " . $e->getMessage();
    }
    
    $redirectSiteId = intval($_GET['site_id'] ?? 0);
    header("Location: church_sites.php?action=view&id=" . ($redirectSiteId > 0 ? $redirectSiteId : $id));
    exit;
}

if ($action === 'review_child' && $id > 0) {
    // Locate the site ID of this child submission to display the correct profile
    $stmt = $pdo->prepare("SELECT church_site_id FROM children_submissions WHERE id = ?");
    $stmt->execute([$id]);
    $csId = $stmt->fetchColumn();
    if ($csId) {
        header("Location: church_sites.php?action=view&id=" . $csId . "&focus_child_id=" . $id);
        exit;
    } else {
        $_SESSION['error_msg'] = "Child submission record not found.";
        header("Location: church_sites.php");
        exit;
    }
}

// ──────────────────────────────────────────
// 1. FETCH DETAILS IF IN VIEW MODE
// ──────────────────────────────────────────
$viewSite = null;
$siteChildren = [];

if ($action === 'view' && $id > 0) {
    // Fetch church site details
    $stmt = $pdo->prepare("SELECT cs.*, u.status AS leader_status, u.username, u.first_name AS u_first, u.middle_name AS u_middle, u.last_name AS u_last, u.email, u.phone AS leader_phone, u.position_title 
                           FROM church_sites cs 
                           JOIN users u ON cs.church_leader_id = u.id 
                           WHERE cs.id = ?");
    $stmt->execute([$id]);
    $viewSite = $stmt->fetch();
    
    if ($viewSite) {
        // Fetch child beneficiary submissions for this site
        $stmtChildren = $pdo->prepare("SELECT * FROM children_submissions WHERE church_site_id = ? ORDER BY created_at DESC");
        $stmtChildren->execute([$id]);
        $siteChildren = $stmtChildren->fetchAll();
    } else {
        $error = "Church site details could not be found.";
    }
}

// ──────────────────────────────────────────
// 2. FETCH ALL REGISTERED CHURCH SITES & LEADERS
// ──────────────────────────────────────────
$stmtSites = $pdo->query("SELECT cs.*, u.username, u.first_name AS u_first, u.middle_name AS u_middle, u.last_name AS u_last, u.status AS leader_status 
                           FROM church_sites cs 
                           JOIN users u ON cs.church_leader_id = u.id 
                           WHERE u.status = 'active'
                           ORDER BY cs.created_at DESC");
$allSites = $stmtSites->fetchAll();

// ──────────────────────────────────────────
// 3. FETCH PENDING CHURCH LEADER ACCOUNTS
// ──────────────────────────────────────────
$stmtPending = $pdo->query("SELECT u.*, cs.id AS site_id, cs.church_name, cs.address, cs.region, cs.province, cs.city_municipality, cs.barangay, cs.contact_number 
                            FROM users u 
                            LEFT JOIN church_sites cs ON u.id = cs.church_leader_id 
                            WHERE u.role = 'church_leader' AND u.status = 'pending' 
                            ORDER BY u.created_at DESC");
$pendingLeaders = $stmtPending->fetchAll();

// ──────────────────────────────────────────
// 4. FETCH REJECTED CHURCH LEADER ACCOUNTS
// ──────────────────────────────────────────
$stmtRejected = $pdo->query("SELECT u.*, cs.id AS site_id, cs.church_name, cs.address, cs.region, cs.province, cs.city_municipality, cs.barangay, cs.contact_number 
                             FROM users u 
                             LEFT JOIN church_sites cs ON u.id = cs.church_leader_id 
                             WHERE u.role = 'church_leader' AND u.status = 'inactive' 
                             ORDER BY u.created_at DESC");
$rejectedLeaders = $stmtRejected->fetchAll();

?>
<?php
$pageTitle = "Church Sites Management";
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

        <?php if (!$viewSite): ?>
          <!-- Pill Tab Bar -->
          <div class="pill-tabs">
            <button class="pill-tab main-pill-tab active" onclick="switchTab('registered')">
              <i class="fas fa-church"></i> Registered Sites (<?php echo count($allSites); ?>)
            </button>
            <button class="pill-tab main-pill-tab" onclick="switchTab('pending')">
              <i class="fas fa-clock"></i> Pending Leaders 
              <?php if (count($pendingLeaders) > 0): ?>
                <span class="tab-badge"><?php echo count($pendingLeaders); ?></span>
              <?php endif; ?>
            </button>
            <button class="pill-tab main-pill-tab" onclick="switchTab('rejected')">
              <i class="fas fa-ban"></i> Rejected Leaders
              <?php if (count($rejectedLeaders) > 0): ?>
                <span class="tab-badge" style="background:var(--gray-600);"><?php echo count($rejectedLeaders); ?></span>
              <?php endif; ?>
            </button>
          </div>
        <?php endif; ?>

        <!-- ==========================================
             VIEW SINGLE CHURCH SITE DETAILS CARD
             ========================================== -->
        <?php if ($viewSite): ?>
          <section class="dashboard-card detail-card">
            <div class="detail-card-header">
              <div class="detail-card-title">Site Profile: <?php echo htmlspecialchars($viewSite['church_name']); ?></div>
              <a href="church_sites.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Close View</a>
            </div>

            <div class="detail-grid">
              <div class="detail-item">
                <label>Pastor / Leader</label>
                <span>Pastor <?php echo htmlspecialchars($viewSite['u_first'] . ' ' . $viewSite['u_last']); ?></span>
              </div>
              <div class="detail-item">
                <label>Position / Title</label>
                <span><?php echo htmlspecialchars($viewSite['position_title'] ?? 'Leader'); ?></span>
              </div>
              <div class="detail-item">
                <label>Contact Number</label>
                <span><?php echo htmlspecialchars($viewSite['contact_number'] ?? 'N/A'); ?></span>
              </div>
              <div class="detail-item">
                <label>Email Address</label>
                <span><?php echo htmlspecialchars($viewSite['email']); ?></span>
              </div>
            </div>

            <div class="detail-grid" style="border-top:1px solid rgba(255,255,255,0.05); padding-top:20px;">
              <div class="detail-item">
                <label>Street Address</label>
                <span><?php echo htmlspecialchars(cleanStreetAddress($viewSite['address'], $viewSite['barangay'], $viewSite['city_municipality'], $viewSite['province'])); ?></span>
              </div>
              <div class="detail-item">
                <label>Barangay</label>
                <span><?php echo htmlspecialchars($viewSite['barangay']); ?></span>
              </div>
              <div class="detail-item">
                <label>City / Municipality</label>
                <span><?php echo htmlspecialchars($viewSite['city_municipality']); ?></span>
              </div>
              <div class="detail-item">
                <label>Province &amp; Region</label>
                <span><?php echo htmlspecialchars($viewSite['province']); ?> &middot; <?php echo htmlspecialchars($viewSite['region']); ?></span>
              </div>
            </div>

            <?php if ($viewSite['leader_status'] === 'pending'): ?>
              <div style="background: rgba(251,191,36,0.05); padding: 20px; border-radius: 8px; border: 1px dashed var(--yellow-500); margin-top: 24px;">
                <h4 style="color:var(--yellow-400); margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Leader Registration Pending Approval</h4>
                <p style="margin-bottom: 15px; color: var(--gray-300);">This church leader account is currently pending activation. Please verify the credentials and ministry details above before approving.</p>
                <div style="display:flex; gap: 15px;">
                  <a href="church_sites.php?action=approve_leader&id=<?php echo $viewSite['church_leader_id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this church leader account?');">
                    <i class="fas fa-check"></i> Approve & Activate Account
                  </a>
                  <a href="church_sites.php?action=reject_leader&id=<?php echo $viewSite['church_leader_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject/disable this registration?');">
                    <i class="fas fa-times"></i> Reject Account
                  </a>
                </div>
              </div>
            <?php endif; ?>

            <div style="border-top: 1px solid rgba(255,255,255,0.08); margin-top:24px; padding-top:24px;">
              <h4 style="font-family:var(--font-head); font-size:1rem; margin-bottom:16px;"><i class="fas fa-children" style="color:var(--blue-400); margin-right:8px;"></i> Children Submissions Registry</h4>
              
              <?php 
                $approvedChildren = [];
                $pendingChildren = [];
                $rejectedChildren = [];
                
                foreach ($siteChildren as $child) {
                    if ($child['submission_status'] === 'approved') {
                        $approvedChildren[] = $child;
                    } elseif ($child['submission_status'] === 'pending') {
                        $pendingChildren[] = $child;
                    } elseif ($child['submission_status'] === 'rejected') {
                        $rejectedChildren[] = $child;
                    }
                }
              ?>
              
              <?php if (empty($siteChildren)): ?>
                <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
              <?php else: ?>
                <!-- Child Pill Tab Bar -->
                <div class="pill-tabs" style="margin-bottom: 20px;">
                  <button class="pill-tab child-pill-tab active" onclick="switchChildTab('approved')">
                    <i class="fas fa-circle-check"></i> Approved (<?php echo count($approvedChildren); ?>)
                  </button>
                  <button class="pill-tab child-pill-tab" onclick="switchChildTab('pending')">
                    <i class="fas fa-clock"></i> Pending Review 
                    <?php if (count($pendingChildren) > 0): ?>
                      <span class="tab-badge"><?php echo count($pendingChildren); ?></span>
                    <?php endif; ?>
                  </button>
                  <button class="pill-tab child-pill-tab" onclick="switchChildTab('rejected')">
                    <i class="fas fa-ban"></i> Rejected
                    <?php if (count($rejectedChildren) > 0): ?>
                      <span class="tab-badge" style="background:var(--gray-600);"><?php echo count($rejectedChildren); ?></span>
                    <?php endif; ?>
                  </button>
                </div>

                <!-- APPROVED CHILDREN PANEL -->
                <div id="child-tab-approved" class="child-tab-panel active">
                  <?php if (empty($approvedChildren)): ?>
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
                            <th>Suggested BMI Status</th>
                            <th>Submitted At</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($approvedChildren as $child): ?>
                            <tr>
                              <td><strong><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></strong></td>
                              <td style="text-transform: capitalize;"><?php echo htmlspecialchars($child['gender']); ?></td>
                              <td><?php echo htmlspecialchars($child['birthdate']); ?></td>
                              <td>
                                <span class="badge <?php echo $child['suggested_status'] === 'qualified' ? 'badge-success' : 'badge-danger'; ?>">
                                  <?php echo htmlspecialchars($child['suggested_status']); ?>
                                </span>
                              </td>
                              <td><?php echo date('M d, Y', strtotime($child['created_at'])); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- PENDING CHILDREN PANEL -->
                <div id="child-tab-pending" class="child-tab-panel" style="display:none;">
                  <?php if (empty($pendingChildren)): ?>
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
                            <th>Suggested BMI Status</th>
                            <th>Submitted At</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($pendingChildren as $child): ?>
                            <tr id="child-row-<?php echo $child['id']; ?>">
                              <td><strong><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></strong></td>
                              <td style="text-transform: capitalize;"><?php echo htmlspecialchars($child['gender']); ?></td>
                              <td><?php echo htmlspecialchars($child['birthdate']); ?></td>
                              <td>
                                <span class="badge <?php echo $child['suggested_status'] === 'qualified' ? 'badge-success' : 'badge-danger'; ?>">
                                  <?php echo htmlspecialchars($child['suggested_status']); ?>
                                </span>
                              </td>
                              <td><?php echo date('M d, Y', strtotime($child['created_at'])); ?></td>
                              <td>
                                <div style="display:flex; gap:8px;">
                                  <a href="church_sites.php?action=approve_child&id=<?php echo $child['id']; ?>&site_id=<?php echo $viewSite['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this child submission?');">
                                    <i class="fas fa-check"></i> Approve
                                  </a>
                                  <a href="church_sites.php?action=reject_child&id=<?php echo $child['id']; ?>&site_id=<?php echo $viewSite['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this child submission?');">
                                    <i class="fas fa-times"></i> Reject
                                  </a>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- REJECTED CHILDREN PANEL -->
                <div id="child-tab-rejected" class="child-tab-panel" style="display:none;">
                  <?php if (empty($rejectedChildren)): ?>
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
                            <th>Suggested BMI Status</th>
                            <th>Submitted At</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($rejectedChildren as $child): ?>
                            <tr>
                              <td><strong><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></strong></td>
                              <td style="text-transform: capitalize;"><?php echo htmlspecialchars($child['gender']); ?></td>
                              <td><?php echo htmlspecialchars($child['birthdate']); ?></td>
                              <td>
                                <span class="badge <?php echo $child['suggested_status'] === 'qualified' ? 'badge-success' : 'badge-danger'; ?>">
                                  <?php echo htmlspecialchars($child['suggested_status']); ?>
                                </span>
                              </td>
                              <td><?php echo date('M d, Y', strtotime($child['created_at'])); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if (!$viewSite): ?>
          <!-- TAB PANEL: PENDING LEADERS -->
          <div id="tab-pending" class="tab-panel">
            <!-- ==========================================
                 PENDING CHURCH LEADER REGISTRATIONS CARD
                 ========================================== -->
            <?php if (empty($pendingLeaders)): ?>
              <div class="dashboard-card">
                <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
              </div>
            <?php else: ?>
              <section class="dashboard-card" style="margin-bottom:32px; border-color:rgba(245,158,11,0.25);">
              <div class="dashboard-card-header">
                <div class="dashboard-card-title" style="color:var(--yellow-400);">Pending Church Leader Registrations
                </div>
                <span style="font-size:0.75rem; font-weight:700; background:rgba(245,158,11,0.15); color:var(--yellow-400); padding:4px 10px; border-radius:999px;">
                  Action Required
                </span>
              </div>

              <div class="dark-table-wrap">
                <table class="dark-table">
                  <thead>
                    <tr>
                      <th>Leader Info</th>
                      <th>Church Site Details</th>
                      <th>Location</th>
                      <th>Registered At</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pendingLeaders as $pLeader): ?>
                      <tr>
                        <td>
                          <strong>Pastor <?php echo htmlspecialchars($pLeader['first_name'] . ' ' . $pLeader['last_name']); ?></strong>
                          <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                            @<?php echo htmlspecialchars($pLeader['username']); ?> &middot; <?php echo htmlspecialchars($pLeader['email']); ?><br>
                            Phone: <?php echo htmlspecialchars($pLeader['phone'] ?? 'N/A'); ?>
                          </div>
                        </td>
                        <td>
                          <strong><?php echo htmlspecialchars($pLeader['church_name'] ?? 'No Site Created'); ?></strong>
                          <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                            Address: <?php echo htmlspecialchars(cleanStreetAddress($pLeader['address'] ?? '', $pLeader['barangay'] ?? '', $pLeader['city_municipality'] ?? '', $pLeader['province'] ?? '')); ?><br>
                            Contact: <?php echo htmlspecialchars($pLeader['contact_number'] ?? 'N/A'); ?>
                          </div>
                        </td>
                        <td>
                          <span style="font-size:0.85rem;">
                            <?php echo htmlspecialchars($pLeader['barangay'] . ', ' . $pLeader['city_municipality'] . ', ' . $pLeader['province']); ?>
                          </span>
                        </td>
                        <td><?php echo date('M d, Y h:i A', strtotime($pLeader['created_at'])); ?></td>
                        <td>
                          <div style="display:flex; gap:8px;">
                            <a href="church_sites.php?action=approve_leader&id=<?php echo $pLeader['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this church leader account?');">
                              <i class="fas fa-check"></i> Approve
                            </a>
                            <a href="church_sites.php?action=reject_leader&id=<?php echo $pLeader['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject/disable this registration?');">
                              <i class="fas fa-times"></i> Reject
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </section>
            <?php endif; ?>
          </div> <!-- End of tab-pending -->

          <!-- TAB PANEL: REGISTERED SITES -->
          <div id="tab-registered" class="tab-panel active">
            <!-- ==========================================
                 REGISTERED CHURCH SITES LISTING CARD
                 ========================================== -->
            <section class="dashboard-card">
            <div class="dashboard-card-header">
              <div class="dashboard-card-title">Registered Church Sites &amp; Leaders
              </div>
              <span style="font-size:0.75rem; color:var(--gray-400); background:rgba(255,255,255,0.06); padding:4px 10px; border-radius:999px;">
                Active Registry
              </span>
            </div>

            <?php if (empty($allSites)): ?>
              <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
            <?php else: ?>
              <div class="dark-table-wrap">
                <table class="dark-table">
                  <thead>
                    <tr>
                      <th>Church Site Name</th>
                      <th>Pastor / Leader</th>
                      <th>Address / Location</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($allSites as $site): ?>
                      <tr>
                        <td>
                          <strong style="color:var(--white);"><?php echo htmlspecialchars($site['church_name']); ?></strong>
                          <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                            Site ID: CS-<?php echo str_pad($site['id'], 3, '0', STR_PAD_LEFT); ?> &middot; Phone: <?php echo htmlspecialchars($site['contact_number'] ?? 'N/A'); ?>
                          </div>
                        </td>
                        <td>
                          <strong>Pastor <?php echo htmlspecialchars($site['u_first'] . ' ' . $site['u_last']); ?></strong>
                          <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                            @<?php echo htmlspecialchars($site['username']); ?>
                          </div>
                        </td>
                        <td>
                          <span style="font-size:0.85rem; line-height: 1.4;">
                            <?php echo htmlspecialchars(formatChurchAddress($site['address'], $site['barangay'], $site['city_municipality'], $site['province'])); ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge <?php echo $site['leader_status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo htmlspecialchars($site['leader_status']); ?>
                          </span>
                        </td>
                        <td>
                          <a href="church_sites.php?action=view&id=<?php echo $site['id']; ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> View Profile
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </section>
          </div> <!-- End of tab-registered -->

        <!-- TAB PANEL: REJECTED LEADERS -->
        <div id="tab-rejected" class="tab-panel">
          <!-- REJECTED CHURCH LEADERS CARD -->
          <?php if (empty($rejectedLeaders)): ?>
            <div class="dashboard-card">
              <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
            </div>
          <?php else: ?>
            <section class="dashboard-card" style="border-color:rgba(239,68,68,0.25);">
              <div class="dashboard-card-header">
                <div class="dashboard-card-title" style="color:var(--red-500);">Rejected &amp; Disabled Church Leaders
                </div>
                <span style="font-size:0.75rem; font-weight:700; background:rgba(239,68,68,0.15); color:var(--red-500); padding:4px 10px; border-radius:999px;">
                  Deactivated Registry
                </span>
              </div>

              <div class="dark-table-wrap">
                <table class="dark-table">
                  <thead>
                    <tr>
                      <th>Leader Info</th>
                      <th>Church Site Details</th>
                      <th>Location</th>
                      <th>Registered At</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($rejectedLeaders as $rLeader): ?>
                      <tr>
                        <td>
                          <strong>Pastor <?php echo htmlspecialchars($rLeader['first_name'] . ' ' . $rLeader['last_name']); ?></strong>
                          <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                            @<?php echo htmlspecialchars($rLeader['username']); ?> &middot; <?php echo htmlspecialchars($rLeader['email']); ?><br>
                            Phone: <?php echo htmlspecialchars($rLeader['phone'] ?? 'N/A'); ?>
                          </div>
                        </td>
                        <td>
                          <strong><?php echo htmlspecialchars($rLeader['church_name'] ?? 'No Site Created'); ?></strong>
                          <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                            Address: <?php echo htmlspecialchars(cleanStreetAddress($rLeader['address'] ?? '', $rLeader['barangay'] ?? '', $rLeader['city_municipality'] ?? '', $rLeader['province'] ?? '')); ?><br>
                            Contact: <?php echo htmlspecialchars($rLeader['contact_number'] ?? 'N/A'); ?>
                          </div>
                        </td>
                        <td>
                          <span style="font-size:0.85rem;">
                            <?php echo htmlspecialchars($rLeader['barangay'] . ', ' . $rLeader['city_municipality'] . ', ' . $rLeader['province']); ?>
                          </span>
                        </td>
                        <td><?php echo date('M d, Y h:i A', strtotime($rLeader['created_at'])); ?></td>
                        <td>
                          <div style="display:flex; gap:8px;">
                            <a href="church_sites.php?action=approve_leader&id=<?php echo $rLeader['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to reactivate this church leader account?');">
                              <i class="fas fa-check"></i> Reactivate
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </section>
          <?php endif; ?>
        </div> <!-- End of tab-rejected -->
      <?php endif; ?>

      </div>
    </main>

  </div>

  <script>
    function switchTab(tabName) {
      // Hide all panels
      document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.remove('active');
      });
      // Deactivate all main tab buttons
      document.querySelectorAll('.main-pill-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Show active panel
      const targetPanel = document.getElementById('tab-' + tabName);
      if (targetPanel) {
        targetPanel.classList.add('active');
      }
      
      // Activate clicked tab button visually
      const targetTabBtn = document.querySelector(`.main-pill-tab[onclick*="${tabName}"]`);
      if (targetTabBtn) {
        targetTabBtn.classList.add('active');
      }
      
      // Save current tab in localStorage
      localStorage.setItem('admin_church_tab', tabName);
    }

    function switchChildTab(tabName) {
      // Hide all child panels
      document.querySelectorAll('.child-tab-panel').forEach(panel => {
        panel.classList.remove('active');
        panel.style.display = 'none';
      });
      // Deactivate all child tab buttons
      document.querySelectorAll('.child-pill-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Show active child panel
      const targetPanel = document.getElementById('child-tab-' + tabName);
      if (targetPanel) {
        targetPanel.classList.add('active');
        targetPanel.style.display = 'block';
      }
      
      // Activate clicked child tab button visually
      const targetTabBtn = document.querySelector(`.child-pill-tab[onclick*="${tabName}"]`);
      if (targetTabBtn) {
        targetTabBtn.classList.add('active');
      }
      
      // Save current child tab in localStorage
      localStorage.setItem('admin_child_tab', tabName);
    }

    // Set active class visually for main tab clicks
    document.querySelectorAll('.main-pill-tab').forEach(tabBtn => {
      tabBtn.addEventListener('click', (e) => {
        document.querySelectorAll('.main-pill-tab').forEach(t => t.classList.remove('active'));
        e.currentTarget.classList.add('active');
      });
    });

    // Set active class visually for child tab clicks
    document.querySelectorAll('.child-pill-tab').forEach(tabBtn => {
      tabBtn.addEventListener('click', (e) => {
        document.querySelectorAll('.child-pill-tab').forEach(t => t.classList.remove('active'));
        e.currentTarget.classList.add('active');
      });
    });

    // Restore active tabs on load
    document.addEventListener('DOMContentLoaded', () => {
      // 1. Restore main tab
      const savedTab = localStorage.getItem('admin_church_tab') || 'registered';
      const targetTabBtn = document.querySelector(`.main-pill-tab[onclick*="${savedTab}"]`);
      if (targetTabBtn) {
        targetTabBtn.click();
      }

      // 2. Restore or focus child tab
      const urlParams = new URLSearchParams(window.location.search);
      const focusChildId = urlParams.get('focus_child_id');
      if (focusChildId) {
        switchChildTab('pending');
        const targetRow = document.getElementById('child-row-' + focusChildId);
        if (targetRow) {
          targetRow.style.backgroundColor = 'rgba(245, 158, 11, 0.18)';
          targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else {
        const savedChildTab = localStorage.getItem('admin_child_tab') || 'approved';
        switchChildTab(savedChildTab);
      }
    });
  </script>
</body>
</html>
