<?php
/**
 * DivineShield - Staff Attendance QR Check-In Gateway
 */
require_once '../../db.php';
session_start();

// Helper function to render error card — defined first so it can be called anywhere
function renderErrorCard($title, $message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Attendance Error – DivineShield</title>
      <link rel="icon" type="image/png" href="../../assets/images/mainpi-logo.png" />
      <link rel="stylesheet" href="../../assets/css/style.css?v=15" />
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
    </head>
    <body class="auth-body" style="display:flex; align-items:center; justify-content:center; min-height:100vh;">
      <div class="auth-container" style="max-width:420px; width:100%; margin:20px;">
        <div class="auth-card" style="text-align:center; padding: 40px 30px;">
            <div style="width: 72px; height: 72px; background: rgba(239, 68, 68, 0.1); border: 2px solid var(--red-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2.2rem; color: var(--red-500);">
                <i class="fas fa-circle-xmark"></i>
            </div>
            <h2 style="font-family: var(--font-head); font-weight: 700; color: var(--white); margin-bottom: 12px; font-size: 1.35rem;"><?php echo htmlspecialchars($title); ?></h2>
            <p style="color: var(--gray-400); font-size: 0.875rem; line-height: 1.6; margin-bottom: 30px;"><?php echo htmlspecialchars($message); ?></p>
            <a href="../../login.php" class="btn btn-outline" style="border-color: rgba(255,255,255,0.1); width:100%; justify-content:center; color: var(--gray-300);">Back to Portal</a>
        </div>
      </div>
    </body>
    </html>
    <?php
}

// ─────────────────────────────────────────────────────
// MAIN LOGIC
// ─────────────────────────────────────────────────────

$token = trim($_GET['token'] ?? '');

// 1. If not logged in, redirect to login page with redirection hook
if (!isset($_SESSION['user_id'])) {
    if (!empty($token)) {
        $_SESSION['redirect_after_login'] = 'views/staff/check_in.php?token=' . urlencode($token);
        $_SESSION['qr_notice'] = 'Please log in first to record your attendance via QR check-in. 📋';
    }
    header("Location: ../../login.php");
    exit;
}

// 2. Security: Verify user role is staff/encoder
if ($_SESSION['role'] !== 'staff') {
    renderErrorCard("Access Denied", "Only encoder staff are authorized to check-in via this QR portal.");
    exit;
}

// 3. Verify token exists and is not expired
$isValidToken = false;
if (!empty($token)) {
    $stmtToken = $pdo->prepare("SELECT * FROM staff_qr_tokens WHERE token = ? AND expires_at > NOW()");
    $stmtToken->execute([$token]);
    $dbToken = $stmtToken->fetch();
    if ($dbToken) {
        $isValidToken = true;
    }
}

if (!$isValidToken) {
    renderErrorCard("Invalid / Expired QR Code", "The scanned QR Code is either invalid or expired. Please ask the Administrator to generate a new QR session.");
    exit;
}

// 4. Check if already checked in today
$userId = $_SESSION['user_id'];
$stmtCheck = $pdo->prepare("SELECT * FROM staff_attendance WHERE user_id = ? AND DATE(check_in_time) = CURRENT_DATE ORDER BY check_in_time DESC LIMIT 1");
$stmtCheck->execute([$userId]);
$alreadyChecked = $stmtCheck->fetch();

$checkInTime = '';
$checkOutTime = '';
$status = ''; // 'success_in', 'success_out', 'already_logged'

if ($alreadyChecked) {
    $checkInTime = date('h:i A', strtotime($alreadyChecked['check_in_time']));
    if (empty($alreadyChecked['check_out_time'])) {
        // Log Check-Out
        try {
            $stmtUpdate = $pdo->prepare("UPDATE staff_attendance SET check_out_time = NOW() WHERE id = ?");
            $stmtUpdate->execute([$alreadyChecked['id']]);

            logAudit($pdo, $userId, 'STAFF_CHECK_OUT', "Staff member @{$_SESSION['username']} checked out successfully via QR code.");
            
            $checkOutTime = date('h:i A');
            $status = 'success_out';
        } catch (Exception $e) {
            renderErrorCard("Database Connection Error", "Could not record check-out: " . $e->getMessage());
            exit;
        }
    } else {
        $checkOutTime = date('h:i A', strtotime($alreadyChecked['check_out_time']));
        $status = 'already_logged';
    }
} else {
    // 5. Log Attendance Check-In
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmtInsert = $pdo->prepare("INSERT INTO staff_attendance (user_id, ip_address) VALUES (?, ?)");
        $stmtInsert->execute([$userId, $ip]);

        logAudit($pdo, $userId, 'STAFF_CHECK_IN', "Staff member @{$_SESSION['username']} checked in successfully via QR code.");

        $checkInTime = date('h:i A');
        $status = 'success_in';
    } catch (Exception $e) {
        renderErrorCard("Database Connection Error", "Could not record check-in: " . $e->getMessage());
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $status === 'success' ? 'Check-In Confirmed' : 'Already Logged'; ?> – DivineShield</title>
  <link rel="icon" type="image/png" href="../../assets/images/mainpi-logo.png" />
  <link rel="stylesheet" href="../../assets/css/style.css?v=15" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      background: #080e1a;
      background-image:
        radial-gradient(ellipse 80% 60% at 20% 10%, rgba(59,130,246,0.12) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 90%, rgba(16,185,129,0.10) 0%, transparent 55%);
      padding: 24px;
    }

    .card {
      width: 100%;
      max-width: 420px;
      background: rgba(15, 23, 42, 0.72);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255,255,255,0.09);
      border-radius: 24px;
      padding: 44px 36px 36px;
      text-align: center;
      box-shadow: 0 24px 64px rgba(0,0,0,0.55), 0 0 0 1px rgba(255,255,255,0.04) inset;
      animation: slideUp 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
    }

    @keyframes slideUp {
      from { opacity:0; transform: translateY(30px) scale(0.97); }
      to   { opacity:1; transform: translateY(0)   scale(1); }
    }

    /* Icon ring */
    .icon-ring {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 28px;
      font-size: 2.6rem;
      position: relative;
    }
    .icon-ring.success {
      background: radial-gradient(circle, rgba(16,185,129,0.18) 0%, rgba(16,185,129,0.04) 70%);
      border: 2px solid rgba(16,185,129,0.5);
      color: #34d399;
      box-shadow: 0 0 28px rgba(16,185,129,0.25);
      animation: pulseGreen 2.4s ease-in-out infinite;
    }
    .icon-ring.info {
      background: radial-gradient(circle, rgba(59,130,246,0.18) 0%, rgba(59,130,246,0.04) 70%);
      border: 2px solid rgba(59,130,246,0.5);
      color: #60a5fa;
      box-shadow: 0 0 28px rgba(59,130,246,0.22);
    }

    @keyframes pulseGreen {
      0%,100% { box-shadow: 0 0 28px rgba(16,185,129,0.25); }
      50%      { box-shadow: 0 0 44px rgba(16,185,129,0.45); }
    }

    h1 {
      font-family: 'Poppins', sans-serif;
      font-weight: 800;
      font-size: 1.55rem;
      color: #f1f5f9;
      margin-bottom: 8px;
      letter-spacing: -0.02em;
    }
    .subtitle {
      color: #64748b;
      font-size: 0.875rem;
      line-height: 1.6;
      margin-bottom: 32px;
    }

    /* Status badge */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    .badge.present {
      background: rgba(16,185,129,0.15);
      color: #34d399;
      border: 1px solid rgba(16,185,129,0.3);
    }

    /* Details panel */
    .details {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 16px;
      padding: 6px 0;
      margin-bottom: 28px;
      text-align: left;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-label {
      color: #475569;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }
    .detail-value {
      color: #f1f5f9;
      font-size: 0.875rem;
      font-weight: 600;
    }
    .detail-value.mono { font-family: monospace; color: #94a3b8; }

    /* Buttons */
    .btn-group { display: flex; flex-direction: column; gap: 10px; }
    .btn-main {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 13px;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.2s;
      cursor: pointer;
      border: none;
    }
    .btn-main.primary {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: #fff;
      box-shadow: 0 4px 16px rgba(59,130,246,0.35);
    }
    .btn-main.primary:hover {
      background: linear-gradient(135deg, #60a5fa, #3b82f6);
      box-shadow: 0 6px 22px rgba(59,130,246,0.5);
      transform: translateY(-1px);
    }
    .btn-main.ghost {
      background: rgba(255,255,255,0.04);
      color: #94a3b8;
      border: 1px solid rgba(255,255,255,0.08);
    }
    .btn-main.ghost:hover {
      background: rgba(255,255,255,0.08);
      color: #cbd5e1;
    }

    /* Divider */
    .divider {
      height: 1px;
      background: rgba(255,255,255,0.06);
      margin: 0 0 28px;
    }

    /* Date chip */
    .date-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.07);
      padding: 5px 14px;
      border-radius: 999px;
      font-size: 0.78rem;
      color: #64748b;
      margin-bottom: 28px;
    }
  </style>
</head>
<body>
  <div class="card">

    <?php if ($status === 'success_in'): ?>
      <div class="icon-ring success">
        <i class="fas fa-circle-check"></i>
      </div>
      <h1>Check-In Logged!</h1>
      <p class="subtitle">Your check-in attendance has been successfully recorded.</p>
    <?php elseif ($status === 'success_out'): ?>
      <div class="icon-ring success" style="border-color: rgba(59,130,246,0.5); color: #60a5fa; box-shadow: 0 0 28px rgba(59,130,246,0.22); animation: none;">
        <i class="fas fa-right-from-bracket"></i>
      </div>
      <h1>Check-Out Logged!</h1>
      <p class="subtitle">Your check-out attendance has been successfully recorded.</p>
    <?php else: ?>
      <div class="icon-ring info">
        <i class="fas fa-user-check"></i>
      </div>
      <h1>Already Logged</h1>
      <p class="subtitle">You have already recorded your check-in and check-out today.</p>
    <?php endif; ?>

    <!-- Date chip -->
    <div class="date-chip">
      <i class="fas fa-calendar-day"></i>
      <?php echo date('l, F j, Y'); ?>
    </div>

    <!-- Details Panel -->
    <div class="details">
      <div class="detail-row">
        <span class="detail-label">Encoder</span>
        <span class="detail-value"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Log Status</span>
        <span class="badge present"><i class="fas fa-check-double"></i> <?php echo ($status === 'success_in') ? 'Checked In' : (($status === 'success_out') ? 'Checked Out' : 'Completed'); ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Check-In Time</span>
        <span class="detail-value mono"><?php echo $checkInTime ?: '—'; ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Check-Out Time</span>
        <span class="detail-value mono"><?php echo $checkOutTime ?: '—'; ?></span>
      </div>
    </div>

    <div class="divider"></div>

    <!-- Buttons -->
    <div class="btn-group">
      <a href="dashboard.php" class="btn-main primary">
        <i class="fas fa-gauge-high"></i> Go to Dashboard
      </a>
      <a href="attendance_history.php" class="btn-main ghost">
        <i class="fas fa-clock-rotate-left"></i> View Attendance History
      </a>
    </div>

  </div>
</body>
</html>
