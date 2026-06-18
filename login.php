<?php
/**
 * login portal
 */

require_once 'db.php';
session_start();

// Redirect to dashboard if already fully logged in
if (isset($_SESSION['user_id'])) {
    redirectDashboard($_SESSION['role']);
}

$error = '';
$success = '';
$qrNotice = '';
if (isset($_SESSION['qr_notice'])) {
    $qrNotice = $_SESSION['qr_notice'];
    unset($_SESSION['qr_notice']);
}

// Determine if we are on Step 2 (MFA PIN Verification for Admin)
$showPinVerification = isset($_SESSION['temp_admin_auth']) && $_SESSION['temp_admin_auth'] === true;

// Cancel MFA and return to regular login
if (isset($_GET['cancel_mfa'])) {
    unset($_SESSION['temp_admin_auth']);
    unset($_SESSION['temp_admin_id']);
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_pin'])) {
        // --- STEP 2: VERIFY ADMIN PIN ---
        $pin1 = $_POST['pin1'] ?? '';
        $pin2 = $_POST['pin2'] ?? '';
        $pin3 = $_POST['pin3'] ?? '';
        $pin4 = $_POST['pin4'] ?? '';
        $enteredPin = $pin1 . $pin2 . $pin3 . $pin4;

        if (strlen($enteredPin) !== 4 || !ctype_digit($enteredPin)) {
            $error = 'Please enter a valid 4-digit PIN.';
            logAudit($pdo, $_SESSION['temp_admin_id'] ?? null, 'LOGIN_PIN_INVALID', 'Invalid digits entered for PIN MFA');
        } else {
            $adminId = $_SESSION['temp_admin_id'] ?? 0;
            
            // Check PIN in DB
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$adminId]);
            $adminUser = $stmt->fetch();

            if ($adminUser && $adminUser['admin_pin'] === $enteredPin) {
                // Pin is correct! Setup full session
                $_SESSION['user_id']   = $adminUser['id'];
                $_SESSION['username']  = $adminUser['username'];
                $_SESSION['role']      = 'admin';
                $adminFullName = trim($adminUser['first_name'] . ' ' . ($adminUser['middle_name'] ? $adminUser['middle_name'] . ' ' : '') . $adminUser['last_name']);
                $_SESSION['full_name'] = $adminFullName;

                // Clean temporary session data
                unset($_SESSION['temp_admin_auth']);
                unset($_SESSION['temp_admin_id']);

                logAudit($pdo, $adminUser['id'], 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA');

                redirectDashboard('admin');
            } else {
                $error = 'Incorrect 4-digit PIN. Access denied.';
                logAudit($pdo, $adminId, 'LOGIN_PIN_FAILED', 'Incorrect PIN entered during authentication check');
            }
        }
    } else {
        // --- STEP 1: VERIFY USERNAME & PASSWORD ---
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Both fields are required.';
        } else {
            // Check lockout threshold dynamically
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $lockoutThreshold = (int)getSystemConfig($pdo, 'lockout_threshold', '5');

            $stmtLock = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'LOGIN_FAILED' AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmtLock->execute([$ip]);
            $failedAttempts = (int)$stmtLock->fetchColumn();

            if ($failedAttempts >= $lockoutThreshold) {
                $error = "Too many failed login attempts. Your IP has been temporarily locked out.";
                logAudit($pdo, null, 'LOGIN_BLOCKED', "IP address $ip temporarily locked out due to $failedAttempts failed attempts");
            } else {
                // Find user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Credentials match!
                    
                    // Account status check
                    if ($user['status'] === 'pending') {
                        $error = 'Your account is currently pending administrator activation.';
                        logAudit($pdo, $user['id'], 'LOGIN_BLOCKED', 'Login attempt blocked: account status pending');
                    } elseif ($user['status'] === 'inactive') {
                        $error = 'Your account is disabled. Contact your administrator.';
                        logAudit($pdo, $user['id'], 'LOGIN_BLOCKED', 'Login attempt blocked: account status inactive');
                    } else {
                        // Active account
                        if ($user['role'] === 'admin') {
                            // Admin needs Step 2 verification (MFA PIN)
                            $_SESSION['temp_admin_auth'] = true;
                            $_SESSION['temp_admin_id']   = $user['id'];
                            
                            logAudit($pdo, $user['id'], 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.');
                            
                            // Reload page to show PIN verification form
                            header("Location: login.php");
                            exit;
                        } else {
                            // Staff or Church Leader log in directly
                            $_SESSION['user_id']   = $user['id'];
                            $_SESSION['username']  = $user['username'];
                            $_SESSION['role']      = $user['role'];
                            $userFullName = trim($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']);
                            $_SESSION['full_name'] = $userFullName;

                            logAudit($pdo, $user['id'], 'LOGIN_SUCCESS', "User logged in with role: {$user['role']}");

                            redirectDashboard($user['role']);
                        }
                    }
                } else {
                    $error = 'Invalid username or password.';
                    // Log failed attempt if user was found
                    $failedUserId = $user ? $user['id'] : null;
                    logAudit($pdo, $failedUserId, 'LOGIN_FAILED', "Failed login attempt for username: $username");
                }
            }
        }
    }
}

// Helper function to handle redirection to dashboards
function redirectDashboard($role) {
    if (isset($_SESSION['redirect_after_login'])) {
        $target = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header("Location: " . $target);
        exit;
    }
    if ($role === 'admin') {
        header("Location: views/admin/dashboard.php");
    } elseif ($role === 'staff') {
        header("Location: views/staff/dashboard.php");
    } else {
        header("Location: views/church/dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – DivineShield</title>
  <link rel="icon" type="image/png" href="assets/images/mainpi-logo.png" />
  <link rel="stylesheet" href="assets/css/style.css?v=5" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <style>
    /* Styling to restrict input values and auto-focus helper script */
    .pin-fields-container input::-webkit-outer-spin-button,
    .pin-fields-container input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    .pin-fields-container input[type=number] {
      -moz-appearance: textfield;
    }
  </style>
</head>
<body class="auth-body">

  <div class="auth-wrapper">
    <div class="auth-container">
      <div class="auth-header">
        <a href="index.php">
          <img src="assets/images/mainpi-logo.png?v=3" alt="MAINPI Logo" class="auth-logo" />
        </a>
        <h1>DivineShield</h1>
        <p>MAINPI Cloud System – Security Gateway</p>
      </div>

      <div class="auth-card">
        
        <?php if (!$showPinVerification): ?>
          <!-- step 1: credentials form (all roles) -->
          <h2 class="auth-card-title">Sign In to Portal</h2>

          <?php if (!empty($error)): ?>
            <div class="auth-alert auth-alert-danger">
              <i class="fas fa-circle-exclamation"></i>
              <div><strong>Access Denied</strong> <span><?php echo htmlspecialchars($error); ?></span></div>
            </div>
          <?php endif; ?>

          <form action="login.php" method="POST" autocomplete="off">
            <div class="auth-form-group">
              <label for="username">Username</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username" class="auth-input" placeholder="Enter your username" required autocomplete="username" />
              </div>
            </div>

            <div class="auth-form-group">
              <label for="password">Password</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-key"></i>
                <input type="password" id="password" name="password" class="auth-input" placeholder="Enter your password" required autocomplete="current-password" />
              </div>
            </div>

            <button type="submit" class="btn btn-primary auth-submit-btn">Verify Credentials</button>
          </form>

          <div class="auth-footer">
            Church Leader without an account? <a href="register.php">Register Church Site</a>
          </div>

          <!-- Testing Credentials Panel -->
          <div style="margin-top: 20px; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden;">
            <div style="background: rgba(255,255,255,0.04); padding: 10px 14px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--gray-400); border-bottom: 1px solid rgba(255,255,255,0.06);">
              Testing Credentials
            </div>
            <div style="padding: 4px 0;">
              <?php
              $testCreds = [
                ['role' => 'Admin',          'color' => '#60a5fa', 'user' => 'admin',    'pass' => 'admin123'],
                ['role' => 'Encoder',        'color' => '#34d399', 'user' => 'encoder1', 'pass' => 'admin123'],
                ['role' => 'Church Leader',  'color' => '#f59e0b', 'user' => 'rina123',  'pass' => 'rina123'],
              ];
              foreach ($testCreds as $cred): ?>
                <div onclick="document.getElementById('username').value='<?php echo $cred['user']; ?>';document.getElementById('password').value='<?php echo $cred['pass']; ?>';"
                     style="display:flex; align-items:center; padding:10px 14px; cursor:pointer; transition:background 0.15s;"
                     onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='transparent'">
                  <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:0.68rem; font-weight:700; color:<?php echo $cred['color']; ?>; background:<?php echo $cred['color']; ?>1a; padding:2px 8px; border-radius:999px;"><?php echo $cred['role']; ?></span>
                    <code style="font-size:0.78rem; color:var(--gray-300);"><?php echo $cred['user']; ?></code>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div style="padding: 8px 14px; border-top: 1px solid rgba(255,255,255,0.06); font-size: 0.68rem; color: var(--white); text-align:center;">
              Click any row to auto-fill credentials
            </div>
          </div>


        <?php else: ?>
          <!-- step 2: 4-digit pin mfa form (admins only) -->
          <h2 class="auth-card-title">Two-Factor PIN Verification</h2>
          <p style="color:var(--gray-300); font-size:0.875rem; margin-bottom: 20px; line-height: 1.5;">
            An administrator login requires a secondary security PIN. Please enter your 4-digit security PIN below:
          </p>

          <?php if (!empty($error)): ?>
            <div class="auth-alert auth-alert-danger">
              <i class="fas fa-circle-exclamation"></i>
              <div><strong>Invalid PIN</strong> <span><?php echo htmlspecialchars($error); ?></span></div>
            </div>
          <?php endif; ?>

          <form action="login.php" method="POST" autocomplete="off">
            <input type="hidden" name="verify_pin" value="1" />
            
            <div class="pin-fields-container" id="pinContainer">
              <input type="text" name="pin1" class="pin-digit-input" maxlength="1" pattern="[0-9]" required autofocus autocomplete="off" />
              <input type="text" name="pin2" class="pin-digit-input" maxlength="1" pattern="[0-9]" required autocomplete="off" />
              <input type="text" name="pin3" class="pin-digit-input" maxlength="1" pattern="[0-9]" required autocomplete="off" />
              <input type="text" name="pin4" class="pin-digit-input" maxlength="1" pattern="[0-9]" required autocomplete="off" />
            </div>

            <button type="submit" class="btn btn-primary auth-submit-btn" style="background:var(--yellow-500); border-color:var(--yellow-500); color:var(--gray-900);">Confirm Identity</button>
            
            <div style="text-align: center; margin-top: 20px;">
              <a href="login.php?cancel_mfa=1" class="btn btn-outline" style="border-color:rgba(255,255,255,0.15); color:var(--gray-400); width:100%; justify-content:center; padding: 10px;">Cancel and Sign Out</a>
            </div>
          </form>

          <script>
            // JavaScript for auto-focus navigation in the PIN inputs
            const pinInputs = document.querySelectorAll('.pin-digit-input');
            pinInputs.forEach((input, index) => {
              // Automatically move focus forward when a digit is entered
              input.addEventListener('input', (e) => {
                if (input.value.length === 1 && index < pinInputs.length - 1) {
                  pinInputs[index + 1].focus();
                }
              });

              // Allow moving back with backspace
              input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                  pinInputs[index - 1].focus();
                }
              });

              // Allow only numbers
              input.addEventListener('keypress', (e) => {
                if (e.which < 48 || e.which > 57) {
                  e.preventDefault();
                }
              });
            });
          </script>
        <?php endif; ?>

      </div>
    </div>
  </div>

<?php if (!empty($qrNotice)): ?>
<!-- QR Attendance Modal Notification -->
<div id="qr-toast-backdrop" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 9998;
    transition: opacity 0.3s ease;
    opacity: 0;
">
  <!-- Toast Modal itself -->
  <div id="qr-toast" style="
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.85);
      background: linear-gradient(135deg, rgba(30,41,59,0.98), rgba(15,23,42,0.98));
      border: 1px solid rgba(59,130,246,0.35);
      color: #e2e8f0;
      padding: 24px 30px;
      border-radius: 16px;
      font-size: 0.95rem;
      font-family: 'Inter', sans-serif;
      max-width: 400px;
      width: calc(100% - 48px);
      box-shadow: 0 16px 48px rgba(0,0,0,0.7);
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
      opacity: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      gap: 16px;
  ">
      <div style="background: rgba(59,130,246,0.1); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
          <i class="fas fa-qrcode" style="color:#60a5fa; font-size:1.8rem;"></i>
      </div>
      <div>
          <div style="font-weight:700; color:#fff; font-size:1.2rem; margin-bottom:8px;">System Notification</div>
          <div style="color:#94a3b8; font-size:0.9rem; line-height:1.5;"><?php echo htmlspecialchars($qrNotice); ?></div>
      </div>
      <button onclick="dismissToast()" class="btn btn-primary" style="margin-top: 8px; width: 100%; height: 44px; display: inline-flex; align-items: center; justify-content: center; font-weight: 600;">
          Got it
      </button>
  </div>
</div>
<script>
    const backdrop = document.getElementById('qr-toast-backdrop');
    const toast = document.getElementById('qr-toast');
    
    // Show modal & backdrop
    requestAnimationFrame(() => {
        setTimeout(() => {
            backdrop.style.opacity = '1';
            toast.style.transform = 'translate(-50%, -50%) scale(1)';
            toast.style.opacity = '1';
        }, 100);
    });

    const autoDismiss = setTimeout(dismissToast, 10000);
    function dismissToast() {
        clearTimeout(autoDismiss);
        backdrop.style.opacity = '0';
        toast.style.transform = 'translate(-50%, -50%) scale(0.85)';
        toast.style.opacity = '0';
        setTimeout(() => {
            backdrop.remove();
        }, 300);
    }
</script>
<?php endif; ?>

</body>
</html>
