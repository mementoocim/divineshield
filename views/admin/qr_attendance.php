<?php
/**
 * DivineShield - Administrator QR Attendance Portal
 */
require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../login.php");
  exit;
}

// Handle AJAX request to fetch check-in logs
if (isset($_GET['action']) && $_GET['action'] === 'get_logs') {
  header('Content-Type: application/json');
  try {
    $stmtCheckIns = $pdo->query("
        SELECT sa.check_in_time, sa.check_out_time, sa.ip_address, u.username, u.first_name, u.last_name, u.email
        FROM staff_attendance sa
        JOIN users u ON sa.user_id = u.id
        WHERE DATE(sa.check_in_time) = CURRENT_DATE
        ORDER BY sa.check_in_time DESC
    ");
    $todayLogs = $stmtCheckIns->fetchAll();
    
    // Format dates nicely
    foreach ($todayLogs as &$log) {
      $log['formatted_in'] = date('h:i:s A', strtotime($log['check_in_time']));
      $log['formatted_out'] = $log['check_out_time'] ? date('h:i:s A', strtotime($log['check_out_time'])) : '—';
      $log['full_name'] = htmlspecialchars($log['first_name'] . ' ' . $log['last_name']);
      $log['username'] = htmlspecialchars($log['username']);
      $log['email'] = htmlspecialchars($log['email']);
      $log['ip_address'] = htmlspecialchars($log['ip_address'] ?? '—');
    }
    echo json_encode(['success' => true, 'logs' => $todayLogs]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
  exit;
}

// get profile pic for navbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();

$pageTitle = "QR Attendance Generator";
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

// handle actions: RENEW QR TOKEN

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew_token'])) {
  try {
    $token = bin2hex(random_bytes(16));
    $durationHours = 24; // QR code lasts a full day
    $expiresAt = date('Y-m-d H:i:s', time() + ($durationHours * 3600));

    $pdo->beginTransaction();
    // Clear old tokens to keep it clean
    $pdo->exec("DELETE FROM staff_qr_tokens");

    // Insert new token
    $stmtInsert = $pdo->prepare("INSERT INTO staff_qr_tokens (token, expires_at) VALUES (?, ?)");
    $stmtInsert->execute([$token, $expiresAt]);

    logAudit($pdo, $_SESSION['user_id'], 'QR_ATTENDANCE_RENEWED', "Administrator generated/renewed the active staff check-in QR code.");

    $pdo->commit();
    $_SESSION['success_msg'] = "Attendance QR Code successfully generated! It is valid for the next $durationHours hours (until " . date('M d, Y h:i A', strtotime($expiresAt)) . ").";
    header("Location: qr_attendance.php");
    exit;
  } catch (Exception $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $error = "Error renewing QR Code: " . $e->getMessage();
  }
}

// Fetch active token
$stmtToken = $pdo->query("SELECT * FROM staff_qr_tokens WHERE expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
$activeToken = $stmtToken->fetch();

// Determine check-in URL
// Always use the actual LAN IP so scanned QR works on phones (not localhost)
$checkInUrl = '';
$secondsRemaining = 0;
if ($activeToken) {
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

  // If accessed via localhost/127.0.0.1, resolve the real LAN IP so the QR is scannable by phones
  $serverAddr = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
  if ($serverAddr === '127.0.0.1' || $serverAddr === '::1') {
    $lanIp = gethostbyname(gethostname());
    // gethostbyname fallback: if it returns the same hostname, use SERVER_ADDR
    $host = filter_var($lanIp, FILTER_VALIDATE_IP) ? $lanIp : $serverAddr;
  } else {
    $host = $serverAddr;
  }

  $checkInUrl = $protocol . $host . "/Divineshield/views/staff/check_in.php?token=" . $activeToken['token'];

  $expiresTime = strtotime($activeToken['expires_at']);
  $secondsRemaining = max(0, $expiresTime - time());
}

include 'includes/header.php';
?>

<?php if ($success): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:24px;">
    <i class="fas fa-circle-check"></i>
    <div><strong>Success</strong> <span><?php echo htmlspecialchars($success); ?></span></div>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:24px;">
    <i class="fas fa-circle-exclamation"></i>
    <div><strong>Error</strong> <span><?php echo htmlspecialchars($error); ?></span></div>
  </div>
<?php endif; ?>

<div class="dashboard-row" style="gap:24px; align-items: stretch;">

  <!-- Left Side: QR Code Display -->
  <div class="dashboard-card"
    style="flex: 1.2; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center;">
    <h3 class="dashboard-card-title" style="margin-bottom:12px; font-family: var(--font-head); font-size: 1.25rem;">Active Check-In Code</h3>
    <p style="color: var(--gray-400); font-size: 0.85rem; margin-bottom: 30px; max-width: 380px;">Encoders and staff
      scan this code using their mobile devices to log their present attendance for today.</p>

    <?php if ($activeToken): ?>
      <!-- QR Code Outer Wrapper (rendered client-side via qrcode.js) -->
      <div
        style="background: var(--white); padding: 24px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); display: inline-block; margin-bottom: 24px;">
        <canvas id="qr-canvas" style="display: block; width: 200px; height: 200px;"></canvas>
      </div>

      <!-- Countdown timer -->
      <div
        style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 12px 24px; border-radius: 12px; margin-bottom: 16px;">
        <div
          style="font-size:0.72rem; text-transform:uppercase; color:var(--gray-500); font-weight:700; letter-spacing:0.04em;">
          Expires In</div>
        <div id="timer-display"
          style="font-size:1.6rem; font-weight:800; color:var(--yellow-400); font-family:monospace; margin-top:2px;">
          00:00:00</div>
        <div style="font-size:0.72rem; color:var(--gray-500); margin-top:4px;">Valid until:
          <?php echo date('M d, Y h:i A', strtotime($activeToken['expires_at'])); ?></div>
      </div>

      <div
        style="font-size: 0.75rem; color: var(--gray-500); word-break: break-all; max-width: 400px; line-height: 1.4; border-top:1px solid rgba(255,255,255,0.06); padding-top: 14px;">
        <strong>Direct Link:</strong><br>
        <a href="<?php echo htmlspecialchars($checkInUrl); ?>" target="_blank"
          style="color:var(--blue-400); font-family:monospace;"><?php echo htmlspecialchars($checkInUrl); ?></a>
      </div>
    <?php else: ?>
      <!-- Empty QR code view -->
      <div
        style="background: rgba(255,255,255,0.02); border: 2px dashed rgba(255,255,255,0.1); width: 250px; height: 250px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin-bottom: 30px;">
        <i class="fas fa-qrcode" style="font-size: 5rem; color: var(--gray-600);"></i>
      </div>
      <div
        style="color:var(--red-400); font-weight:700; background:rgba(239,68,68,0.1); padding:10px 20px; border-radius:8px; margin-bottom:20px; font-size:0.85rem;">
        <i class="fas fa-circle-exclamation" style="margin-right:6px;"></i> Session Inactive / Expired
      </div>
    <?php endif; ?>

    <!-- Action Button Form -->
    <form method="POST" action="qr_attendance.php" style="width:100%; max-width: 250px; margin-top: 10px;">
      <input type="hidden" name="renew_token" value="1">
      <button type="submit" class="btn btn-primary"
        style="width: 100%; justify-content: center; font-size:0.9rem; height:46px;">
        <i class="fas fa-rotate" style="margin-right:8px;"></i>
        <?php echo $activeToken ? 'Renew QR Code' : 'Generate QR Code'; ?>
      </button>
    </form>
  </div>

  <!-- Right Side: Recent Check-in Logs / Details -->
  <div class="dashboard-card" style="flex: 1.5; display: flex; flex-direction: column; padding: 28px;">
    <div class="dashboard-card-header"
      style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom: 20px;">
      <h3 class="dashboard-card-title">Today's Check-in Log</h3>
    </div>

    <div class="panel-body" id="today-logs-panel" style="padding:0; flex-grow:1;">
      <?php
      // Fetch today's check-ins for encoders
      $stmtCheckIns = $pdo->query("
          SELECT sa.check_in_time, sa.check_out_time, sa.ip_address, u.username, u.first_name, u.last_name, u.email
          FROM staff_attendance sa
          JOIN users u ON sa.user_id = u.id
          WHERE DATE(sa.check_in_time) = CURRENT_DATE
          ORDER BY sa.check_in_time DESC
      ");
      $todayLogs = $stmtCheckIns->fetchAll();
      ?>

      <?php if (empty($todayLogs)): ?>
        <div class="empty-state" style="padding: 40px; text-align: center;">
          <i class="fas fa-user-clock empty-icon"
            style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
          <h4 style="color: var(--white); margin-bottom: 8px;">No Staff Checked-in Today</h4>
          <p style="color: var(--gray-400); font-size:0.8rem;">Once staff scan the active QR code, their check-in records will appear here.</p>
        </div>
      <?php else: ?>
        <div class="dark-table-wrap" style="max-height: 380px; overflow-y: auto;">
          <table class="dark-table">
            <thead>
              <tr>
                <th>Staff Member</th>
                <th>Check-in Time</th>
                <th>Check-out Time</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($todayLogs as $log): ?>
                <tr>
                  <td>
                    <strong
                      style="color:var(--white);"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong>
                    <div style="font-size: 0.72rem; color: var(--gray-400); margin-top:2px;">
                      @<?php echo htmlspecialchars($log['username']); ?> &middot;
                      <?php echo htmlspecialchars($log['email']); ?></div>
                  </td>
                  <td style="font-family: monospace; color:var(--teal-400); font-weight:600;">
                    <?php echo date('h:i:s A', strtotime($log['check_in_time'])); ?>
                  </td>
                  <td style="font-family: monospace; color:var(--blue-400); font-weight:600;">
                    <?php echo $log['check_out_time'] ? date('h:i:s A', strtotime($log['check_out_time'])) : '<span class="text-muted">—</span>'; ?>
                  </td>
                  <td style="font-family: monospace; font-size:0.8rem; color:var(--gray-400);">
                    <?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php if ($activeToken): ?>
  <!-- QR Code library hosted locally -->
  <script src="/Divineshield/assets/js/qrcode.min.js"></script>
  <script>
    // ── Generate QR Code on canvas ──
    try {
      const checkInUrl = <?php echo json_encode($checkInUrl); ?>;
      QRCode.toCanvas(document.getElementById('qr-canvas'), checkInUrl, {
        width: 200,
        margin: 2,
        color: { dark: '#0f172a', light: '#ffffff' }
      }, function (err) {
        if (err) console.error('QR generation error:', err);
      });
    } catch (e) {
      console.error('QRCode library error:', e);
      document.getElementById('qr-canvas').parentElement.innerHTML =
        '<p style="color:red;font-size:0.8rem;">QR render failed: ' + e.message + '</p>';
    }
  </script>

  <script>
    // ── Countdown Timer (hours : minutes : seconds) ──
    let secondsLeft = <?php echo (int) $secondsRemaining; ?>;
    const display = document.getElementById('timer-display');

    function updateTimer() {
      if (secondsLeft <= 0) {
        display.textContent = "00:00:00";
        display.style.color = "var(--red-500)";
        setTimeout(() => { window.location.reload(); }, 2000);
        return;
      }

      const hrs = Math.floor(secondsLeft / 3600);
      const mins = Math.floor((secondsLeft % 3600) / 60);
      const secs = secondsLeft % 60;

      display.textContent =
        (hrs < 10 ? '0' : '') + hrs + ':' +
        (mins < 10 ? '0' : '') + mins + ':' +
        (secs < 10 ? '0' : '') + secs;

      // Warn red when less than 1 hour left
      if (secondsLeft < 3600) display.style.color = "var(--red-400)";
      else display.style.color = "var(--yellow-400)";

      secondsLeft--;
      setTimeout(updateTimer, 1000);
    }

    updateTimer();
  </script>
  <script>
    // ── Live Polling for Today's Check-in Log ──
    function pollLogs() {
      fetch('qr_attendance.php?action=get_logs')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const panel = document.getElementById('today-logs-panel');
            if (data.logs.length === 0) {
              panel.innerHTML = `
                <div class="empty-state" style="padding: 40px; text-align: center;">
                  <i class="fas fa-user-clock empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                  <h4 style="color: var(--white); margin-bottom: 8px;">No Staff Checked-in Today</h4>
                  <p style="color: var(--gray-400); font-size:0.8rem;">Once staff scan the active QR code, their check-in records will appear here.</p>
                </div>`;
            } else {
              let rowsHtml = '';
              data.logs.forEach(log => {
                const outTime = log.check_out_time ? log.formatted_out : '<span class="text-muted">—</span>';
                rowsHtml += `
                  <tr>
                    <td>
                      <strong style="color:var(--white);">${log.full_name}</strong>
                      <div style="font-size: 0.72rem; color: var(--gray-400); margin-top:2px;">
                        @${log.username} &middot; ${log.email}
                      </div>
                    </td>
                    <td style="font-family: monospace; color:var(--teal-400); font-weight:600;">
                      ${log.formatted_in}
                    </td>
                    <td style="font-family: monospace; color:var(--blue-400); font-weight:600;">
                      ${outTime}
                    </td>
                    <td style="font-family: monospace; font-size:0.8rem; color:var(--gray-400);">
                      ${log.ip_address}
                    </td>
                  </tr>`;
              });

              panel.innerHTML = `
                <div class="dark-table-wrap" style="max-height: 380px; overflow-y: auto;">
                  <table class="dark-table">
                    <thead>
                      <tr>
                        <th>Staff Member</th>
                        <th>Check-in Time</th>
                        <th>Check-out Time</th>
                        <th>IP Address</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${rowsHtml}
                    </tbody>
                  </table>
                </div>`;
            }
          }
        })
        .catch(err => console.error('Error polling logs:', err));
    }

    // Poll every 3 seconds
    setInterval(pollLogs, 3000);
  </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>