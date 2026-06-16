<?php
/**
 * DivineShield - Administrator Profile Settings
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
// HANDLE ACTIONS: POST UPDATES
// ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. UPDATE PROFILE DETAILS
    if (isset($_POST['update_details'])) {
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            $error = "First Name, Last Name, and Email are required fields.";
        } else {
            try {
                // Check email uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $error = "Email address is already in use by another user.";
                } else {
                    $stmtUpdate = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmtUpdate->execute([$firstName, empty($middleName) ? null : $middleName, $lastName, $email, empty($phone) ? null : $phone, $_SESSION['user_id']]);
                    
                    $_SESSION['full_name'] = trim($firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName);
                    $_SESSION['success_msg'] = "Profile details updated successfully!";
                    header("Location: profile.php");
                    exit;
                }
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // 2. CHANGE ACCOUNT PASSWORD
    if (isset($_POST['change_password'])) {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $error = "All password fields are required.";
        } elseif ($newPass !== $confirmPass) {
            $error = "New password verification fails. Passwords must match.";
        } elseif (strlen($newPass) < 6) {
            $error = "New password must be at least 6 characters long.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $currHash = $stmt->fetchColumn();
                
                if (password_verify($currentPass, $currHash)) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmtUpdate->execute([$newHash, $_SESSION['user_id']]);
                    
                    logAudit($pdo, $_SESSION['user_id'], 'ADMIN_PASSWORD_CHANGED', "Administrator updated account password.");
                    
                    $_SESSION['success_msg'] = "Your password has been changed successfully!";
                    header("Location: profile.php");
                    exit;
                } else {
                    $error = "Current credentials invalid. Password check failed.";
                }
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // 3. CHANGE MFA PIN
    if (isset($_POST['change_pin'])) {
        $newPin = $_POST['new_pin'] ?? '';
        
        if (strlen($newPin) !== 4 || !ctype_digit($newPin)) {
            $error = "PIN must be exactly 4 numeric digits.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE users SET admin_pin = ? WHERE id = ?");
                $stmtUpdate->execute([$newPin, $_SESSION['user_id']]);
                
                logAudit($pdo, $_SESSION['user_id'], 'ADMIN_PIN_CHANGED', "Administrator updated Two-Step PIN settings.");
                
                $_SESSION['success_msg'] = "MFA verification PIN updated successfully!";
                header("Location: profile.php");
                exit;
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // 4. UPLOAD PROFILE PICTURE
    if (isset($_POST['upload_picture']) && isset($_FILES['profile_pic'])) {
        $file = $_FILES['profile_pic'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "Image file upload failed. Please try again.";
        } else {
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $file['name'];
            $fileSize = $file['size'];
            $fileTmp = $file['tmp_name'];
            
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExts)) {
                $error = "File extension invalid. Only JPG, JPEG, PNG, and GIF files are accepted.";
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = "File size exceeds 2MB limit.";
            } else {
                try {
                    $uploadDir = '../../assets/uploads/profile_pics/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $newFilename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                    $destPath = $uploadDir . $newFilename;
                    
                    if (move_uploaded_file($fileTmp, $destPath)) {
                        // Delete old profile picture if exists
                        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $oldPic = $stmt->fetchColumn();
                        if (!empty($oldPic) && file_exists('../../' . $oldPic)) {
                            unlink('../../' . $oldPic);
                        }
                        
                        $dbPath = 'assets/uploads/profile_pics/' . $newFilename;
                        $stmtUpdate = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                        $stmtUpdate->execute([$dbPath, $_SESSION['user_id']]);
                        
                        logAudit($pdo, $_SESSION['user_id'], 'ADMIN_AVATAR_UPLOADED', "Administrator uploaded a new profile image.");
                        
                        $_SESSION['success_msg'] = "Profile picture updated successfully!";
                        header("Location: profile.php");
                        exit;
                    } else {
                        $error = "Failed to copy uploaded image file to target destination.";
                    }
                } catch (Exception $e) {
                    $error = "File processing error: " . $e->getMessage();
                }
            }
        }
    }
}

// ──────────────────────────────────────────
// FETCH CURRENT ADMIN USER ROW
// ──────────────────────────────────────────
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$adminUser = $stmtUser->fetch();

$profilePic = $adminUser['profile_picture'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Settings – DivineShield</title>
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
        <div class="topbar-title">Profile &amp; Account Settings</div>
        <div class="topbar-user">
          <div class="user-badge-group">
            <div class="user-badge-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Administrator'); ?></div>
            <div class="user-badge-role">System Administrator</div>
          </div>
          <?php if (!empty($profilePic) && file_exists('../../' . $profilePic)): ?>
            <img src="../../<?php echo htmlspecialchars($profilePic); ?>" alt="Profile" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.15);" />
          <?php else: ?>
            <div class="logo-mark small" style="background:linear-gradient(135deg, var(--yellow-400), var(--yellow-500)); color:var(--gray-900);"><i class="fas fa-user-shield"></i></div>
          <?php endif; ?>
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

        <!-- Responsive Split Layout -->
        <div class="dashboard-row" style="align-items: flex-start; gap: 32px;">
          
          <!-- Column 1: Avatar Showcase Card -->
          <div style="flex: 1; display: flex; flex-direction: column; gap: 32px;">
            <div class="dashboard-card">
              <div class="dashboard-card-header">
                <div class="dashboard-card-title">Profile Photo</div>
              </div>
              <div style="text-align: center; padding: 32px 24px;">
                <div style="position: relative; width: 140px; height: 140px; margin: 0 auto 20px;">
                  <?php if (!empty($profilePic) && file_exists('../../' . $profilePic)): ?>
                    <img src="../../<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Photo" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.1);" />
                  <?php else: ?>
                    <div class="logo-mark" style="width: 140px; height: 140px; font-size: 4rem; border-radius: 50%; margin: 0; background: linear-gradient(135deg, var(--yellow-400), var(--yellow-500)); color: var(--gray-900);">
                      <i class="fas fa-user-shield"></i>
                    </div>
                  <?php endif; ?>
                </div>
                
                <h3 style="font-family: var(--font-head); color: var(--white); font-size: 1.25rem; font-weight: 700; margin-bottom: 4px;">
                  <?php echo htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name']); ?>
                </h3>
                <p style="font-size: 0.8rem; color: var(--gray-400); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 24px;">
                  @<?php echo htmlspecialchars($adminUser['username']); ?> &middot; System Administrator
                </p>

                <form id="profile-upload-form" action="profile.php" method="POST" enctype="multipart/form-data" style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 24px;">
                  <input type="hidden" name="upload_picture" value="1" />
                  
                  <input type="file" id="profile_pic_input" name="profile_pic" accept="image/*" required style="display:none;" onchange="document.getElementById('profile-upload-form').submit();" />
                  
                  <button type="button" class="btn btn-primary" style="padding: 10px 18px; width: 100%; justify-content: center; background: rgba(59, 130, 246, 0.15); color: #93c5fd; border-color: rgba(59, 130, 246, 0.25);" onclick="document.getElementById('profile_pic_input').click();">
                    <i class="fas fa-camera-rotate"></i> Change Profile Picture
                  </button>
                </form>
              </div>
            </div>
            <!-- 3. MFA PIN CARD -->
            <section class="dashboard-card" style="margin-bottom: 0;">
              <div class="dashboard-card-header">
                <div class="dashboard-card-title">Two-Step MFA PIN Settings</div>
                <span class="badge badge-warning">Required for Login</span>
              </div>
              
              <form action="profile.php" method="POST" autocomplete="off" style="margin-top:16px;">
                <input type="hidden" name="change_pin" value="1" />
                
                <div class="auth-form-group" style="margin-bottom:20px;">
                  <label>MFA Gateway Verification PIN *</label>
                  <input type="password" name="new_pin" maxlength="4" value="<?php echo htmlspecialchars($adminUser['admin_pin'] ?? ''); ?>" placeholder="4-digit numeric code" class="auth-input" style="padding-left:16px; width:150px; font-size:1.1rem; letter-spacing:0.3em; text-align:center;" required />
                  <p style="font-size:0.75rem; color:var(--gray-400); margin-top:8px;">Enter a secure 4-digit code (e.g. 1234) used to bypass the gateway after signing in with standard credentials.</p>
                </div>

                <button type="submit" class="btn btn-primary" style="padding:10px 20px; background:var(--teal-500);"><i class="fas fa-shield"></i> Update Security PIN</button>
              </form>
            </section>
          </div>

          <!-- Column 2: Configuration Options Forms -->
          <div style="flex: 2; display: flex; flex-direction: column; gap: 32px;">
            
            <!-- 1. PERSONAL DETAILS CARD -->
            <section class="dashboard-card" style="margin-bottom: 0;">
              <div class="dashboard-card-header">
                <div class="dashboard-card-title">Personal Details</div>
              </div>
              
              <form action="profile.php" method="POST" autocomplete="off" style="margin-top:16px;">
                <input type="hidden" name="update_details" value="1" />
                
                <div class="form-grid-3-resp" style="margin-bottom:20px;">
                  <div class="auth-form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($adminUser['first_name']); ?>" class="auth-input" style="padding-left:16px;" required />
                  </div>
                  <div class="auth-form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($adminUser['middle_name'] ?? ''); ?>" class="auth-input" style="padding-left:16px;" />
                  </div>
                  <div class="auth-form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($adminUser['last_name']); ?>" class="auth-input" style="padding-left:16px;" required />
                  </div>
                </div>

                <div class="form-grid-2" style="margin-bottom:20px;">
                  <div class="auth-form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($adminUser['email']); ?>" class="auth-input" style="padding-left:16px;" required />
                  </div>
                  <div class="auth-form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($adminUser['phone'] ?? ''); ?>" class="auth-input" style="padding-left:16px;" />
                  </div>
                </div>

                <button type="submit" class="btn btn-primary" style="padding:10px 20px; background:var(--blue-600);"><i class="fas fa-floppy-disk"></i> Update Personal Details</button>
              </form>
            </section>

            <!-- 2. PASSWORD SECURITY CARD -->
            <section class="dashboard-card" style="margin-bottom: 0;">
              <div class="dashboard-card-header">
                <div class="dashboard-card-title">Change Account Password</div>
              </div>
              
              <form action="profile.php" method="POST" autocomplete="off" style="margin-top:16px;">
                <input type="hidden" name="change_password" value="1" />
                
                <div class="auth-form-group" style="margin-bottom:20px;">
                  <label>Current Password *</label>
                  <input type="password" name="current_password" class="auth-input" style="padding-left:16px;" required />
                </div>

                <div class="form-grid-2" style="margin-bottom:20px;">
                  <div class="auth-form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" class="auth-input" style="padding-left:16px;" required />
                  </div>
                  <div class="auth-form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" class="auth-input" style="padding-left:16px;" required />
                  </div>
                </div>

                <button type="submit" class="btn btn-primary" style="padding:10px 20px; background:var(--yellow-500); color:var(--gray-900); font-weight:700;"><i class="fas fa-lock"></i> Update Password</button>
              </form>
            </section>



          </div>

        </div>

      </div>
    </main>

  </div>

</body>
</html>
