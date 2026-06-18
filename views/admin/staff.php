<?php
/**
 * DivineShield - Staff / Encoders Management
 */

require_once '../../db.php';
session_start();

// auth / role check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// get profile pic for navbar
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

// handle actions: CREATE, TOGGLE STATUS, DELETE

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

// 1. ADD NEW STAFF USER (POST Handler)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $firstName  = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName   = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    if (empty($username) || empty($password) || empty($firstName) || empty($lastName) || empty($email)) {
        $error = "All fields marked with an asterisk (*) are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check username uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username is already taken by another account.";
            } else {
                // Check email uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email address is already registered.";
                } else {
                    // Hash Password
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Insert User
                    $stmtInsert = $pdo->prepare("INSERT INTO users (username, password_hash, role, first_name, middle_name, last_name, email, phone, status) VALUES (?, ?, 'staff', ?, ?, ?, ?, ?, 'active')");
                    $stmtInsert->execute([
                        $username,
                        $passwordHash,
                        $firstName,
                        empty($middleName) ? null : $middleName,
                        $lastName,
                        $email,
                        empty($phone) ? null : $phone
                    ]);
                    
                    $newStaffId = $pdo->lastInsertId();
                    
                    // Log Audit
                    logAudit($pdo, $_SESSION['user_id'], 'STAFF_CREATED', "Created new encoder staff account: @$username (ID: $newStaffId)");
                    
                    $_SESSION['success_msg'] = "Encoder staff account @$username has been successfully created!";
                    header("Location: staff.php");
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// 2. TOGGLE STAFF ACCOUNT STATUS (Active/Inactive)
if ($action === 'toggle_status' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT username, status FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$id]);
        $staff = $stmt->fetch();
        
        if ($staff) {
            $username = $staff['username'];
            $newStatus = $staff['status'] === 'active' ? 'inactive' : 'active';
            
            $stmtUpdate = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $id]);
            
            // Log Audit
            logAudit($pdo, $_SESSION['user_id'], 'STAFF_STATUS_TOGGLED', "Changed account status of staff encoder @$username to '$newStatus'");
            
            $_SESSION['success_msg'] = "Successfully toggled @$username status to '$newStatus'.";
        } else {
            $_SESSION['error_msg'] = "Staff account not found.";
        }
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error toggling account: " . $e->getMessage();
    }
    header("Location: staff.php");
    exit;
}

// 3. DELETE STAFF USER
if ($action === 'delete_staff' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$id]);
        $staff = $stmt->fetch();
        
        if ($staff) {
            $username = $staff['username'];
            
            $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmtDel->execute([$id]);
            
            // Log Audit
            logAudit($pdo, $_SESSION['user_id'], 'STAFF_DELETED', "Permanently deleted encoder staff account @$username (ID: $id)");
            
            $_SESSION['success_msg'] = "Permanently deleted encoder account @$username.";
        } else {
            $_SESSION['error_msg'] = "Staff account not found.";
        }
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error deleting account: " . $e->getMessage();
    }
    header("Location: staff.php");
    exit;
}

// fetch all staff users

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$query = "SELECT * FROM users WHERE role = 'staff'";
$params = [];

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

if (!empty($statusFilter)) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY created_at DESC";

$stmtStaff = $pdo->prepare($query);
$stmtStaff->execute($params);
$staffList = $stmtStaff->fetchAll();

?>
<?php
$pageTitle = "Staff &amp; Encoders Management";
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

        <!-- add new staff form card (toggled) -->
        <?php if ($action === 'add'): ?>
          <section class="dashboard-card detail-card" style="border-color:rgba(59,130,246,0.3); margin-bottom:32px;">
            <div class="detail-card-header">
              <div class="detail-card-title">Add Encoder Staff Account</div>
              <a href="staff.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Cancel</a>
            </div>

            <form action="staff.php" method="POST" autocomplete="off" style="margin-top:16px;">
              <input type="hidden" name="create_staff" value="1" />
              
              <!-- 3-Column Names Grid -->
              <div class="form-grid-3-resp" style="margin-bottom:20px;">
                <div class="auth-form-group">
                  <label for="first_name">First Name *</label>
                  <div class="auth-input-wrapper">
                    <input type="text" id="first_name" name="first_name" class="auth-input" style="padding-left:16px;" placeholder="e.g. Maria" required />
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
                    <input type="text" id="last_name" name="last_name" class="auth-input" style="padding-left:16px;" placeholder="e.g. Reyes" required />
                  </div>
                </div>
              </div>

              <!-- Username, Password & Details Grid -->
              <div class="form-grid-2" style="margin-bottom:20px;">
                <div class="auth-form-group">
                  <label for="username">Username *</label>
                  <div class="auth-input-wrapper">
                    <input type="text" id="username" name="username" class="auth-input" style="padding-left:16px;" placeholder="e.g. maria_encoder" required />
                  </div>
                </div>
                <div class="auth-form-group">
                  <label for="password">Default Password *</label>
                  <div class="auth-input-wrapper">
                    <input type="password" id="password" name="password" class="auth-input" style="padding-left:16px;" placeholder="Min. 6 characters" required />
                  </div>
                </div>
              </div>

              <div class="form-grid-2" style="margin-bottom:20px;">
                <div class="auth-form-group">
                  <label for="email">Email Address *</label>
                  <div class="auth-input-wrapper">
                    <input type="email" id="email" name="email" class="auth-input" style="padding-left:16px;" placeholder="e.g. email@mainpi.org" required />
                  </div>
                </div>
                <div class="auth-form-group">
                  <label for="phone">Phone Number</label>
                  <div class="auth-input-wrapper">
                    <input type="text" id="phone" name="phone" class="auth-input" style="padding-left:16px;" placeholder="e.g. 09171234567" />
                  </div>
                </div>
              </div>

              <button type="submit" class="btn btn-primary" style="padding:12px 28px; width:100%; justify-content:center; background:var(--blue-600);"><i class="fas fa-floppy-disk"></i> Register Account</button>
            </form>
          </section>
        <?php endif; ?>

        <?php if ($action !== 'add'): ?>
        <!-- Search & Filters Bar conforming to design system -->
        <section class="dashboard-card" style="margin-bottom:24px; padding: 20px 28px;">
          <form action="staff.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            
            <div style="flex:1.2; min-width:200px;">
              <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Search</label>
              <input type="text" name="search" class="auth-input filter-input" placeholder="Search by name, username, email..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
            </div>

            <div style="flex:0.8; min-width:150px;">
              <label style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:8px; letter-spacing:0.04em;">Status</label>
              <select name="status" class="auth-select" style="background:rgba(15,23,42,0.8); border-color:rgba(255,255,255,0.1); height:46px;">
                <option value="">-- All --</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>

            <div style="display:flex; gap:10px; width:auto;">
              <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:0.85rem; height:46px;">
                <i class="fas fa-filter"></i> Apply Filters
              </button>
              <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="staff.php" class="btn btn-outline" style="padding:10px 20px; font-size:0.85rem; height:46px; border-color:rgba(255,255,255,0.1); color:var(--gray-300); align-items:center;">
                  <i class="fas fa-filter-circle-xmark"></i> Clear
                </a>
              <?php endif; ?>
            </div>
          </form>
        </section>
        <?php endif; ?>

        <!-- staff registry listing card -->
        <section class="dashboard-card">
          <div class="dashboard-card-header">
            <div class="dashboard-card-title">Encoder Staff Registry
            </div>
            <?php if ($action !== 'add'): ?>
              <a href="staff.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Create Account</a>
            <?php endif; ?>
          </div>

          <?php if (empty($staffList)): ?>
            <div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
          <?php else: ?>
            <div class="dark-table-wrap">
              <table class="dark-table">
                <thead>
                  <tr>
                    <th>Staff Encoder</th>
                    <th>Credentials &amp; Access</th>
                    <th>Contact Info</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($staffList as $staff): ?>
                    <tr>
                      <td>
                        <strong style="color:var(--white);"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></strong>
                        <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                          ID: ST-<?php echo str_pad($staff['id'], 3, '0', STR_PAD_LEFT); ?>
                        </div>
                      </td>
                      <td>
                        <strong>@<?php echo htmlspecialchars($staff['username']); ?></strong>
                        <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">
                          Role: Encoder
                        </div>
                      </td>
                      <td>
                        <span style="font-size:0.85rem;">
                          Email: <?php echo htmlspecialchars($staff['email']); ?><br>
                          Phone: <?php echo htmlspecialchars($staff['phone'] ?? 'N/A'); ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge <?php echo $staff['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                          <?php echo htmlspecialchars($staff['status']); ?>
                        </span>
                      </td>
                      <td>
                        <div style="display:flex; gap:8px;">
                          <?php if ($staff['status'] === 'active'): ?>
                            <a href="staff.php?action=toggle_status&id=<?php echo $staff['id']; ?>" class="btn btn-danger btn-sm" onclick="event.preventDefault(); Swal.fire({ title: 'Deactivate Staff?', text: 'Are you sure you want to deactivate this staff member?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, deactivate', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => { if (result.isConfirmed) { window.location.href = this.href; } });">
                              <i class="fas fa-user-slash"></i> Deactivate
                            </a>
                          <?php else: ?>
                            <a href="staff.php?action=toggle_status&id=<?php echo $staff['id']; ?>" class="btn btn-success btn-sm" onclick="event.preventDefault(); Swal.fire({ title: 'Activate Staff?', text: 'Are you sure you want to activate this staff member?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, activate', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => { if (result.isConfirmed) { window.location.href = this.href; } });">
                              <i class="fas fa-user-check"></i> Activate
                            </a>
                          <?php endif; ?>
                          <a href="staff.php?action=delete_staff&id=<?php echo $staff['id']; ?>" class="btn btn-danger btn-sm" onclick="event.preventDefault(); Swal.fire({ title: 'Delete Encoder Account?', text: 'Are you sure you want to permanently delete this encoder account? This action is irreversible.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => { if (result.isConfirmed) { window.location.href = this.href; } });">
                            <i class="fas fa-trash-can"></i> Delete
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>

      <?php include 'includes/footer.php'; ?>
