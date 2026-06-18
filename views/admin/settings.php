<?php
/**
 * DivineShield - System Settings Configuration Panel
 */

require_once '../../db.php';
session_start();

// auth / role check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired or unauthorized.']);
        exit;
    }
    header("Location: ../../login.php");
    exit;
}

// Database Export API Action
if (isset($_GET['action']) && $_GET['action'] === 'export_db') {
    try {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="divineshield_backup_' . date('Y-m-d_H-i-s') . '.sql"');
        
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $output = "-- DivineShield Database Backup\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $showCreate = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $showCreate['Create Table'] . ";\n\n";
            
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $output .= "INSERT INTO `$table` VALUES \n";
                $valStrings = [];
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $vals[] = "NULL";
                        } else {
                            $vals[] = $pdo->quote($val);
                        }
                    }
                    $valStrings[] = "(" . implode(", ", $vals) . ")";
                }
                $output .= implode(",\n", $valStrings) . ";\n\n";
            }
        }
        
        logAudit($pdo, $_SESSION['user_id'], 'DB_EXPORT', 'Database SQL schema and data exported successfully.');
        echo $output;
    } catch (Exception $e) {
        http_response_code(500);
        echo "Export failed: " . $e->getMessage();
    }
    exit;
}

// AJAX API Post Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'save_config') {
        $keys = [
            'lockout_threshold', 'session_timeout', 
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption',
            'pw_min_length', 'pw_req_number', 'pw_req_special', 'pw_req_case',
            'log_retention_days', 'allow_public_registration', 'require_admin_approval',
            'email_approval_subject', 'email_approval_body',
            'email_rejection_subject', 'email_rejection_body',
            'email_new_reg_subject', 'email_new_reg_body'
        ];
        
        $pdo->beginTransaction();
        try {
            foreach ($keys as $key) {
                if (in_array($key, ['pw_req_number', 'pw_req_special', 'pw_req_case', 'allow_public_registration', 'require_admin_approval'])) {
                    $val = isset($_POST[$key]) ? '1' : '0';
                } else {
                    $val = trim($_POST[$key] ?? '');
                }
                setSystemConfig($pdo, $key, $val);
            }
            $pdo->commit();
            logAudit($pdo, $_SESSION['user_id'], 'SETTINGS_UPDATED', 'System settings configurations saved successfully.');
            echo json_encode(['success' => true, 'message' => 'Configurations saved successfully!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to save configurations: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'test_smtp') {
        $testEmail = trim($_POST['test_email'] ?? '');
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a valid test recipient email address.']);
            exit;
        }
        
        require_once '../../config/email_helper.php';
        $mail = initializePHPMailer();
        if (!$mail) {
            echo json_encode(['success' => false, 'message' => 'Failed to initialize PHPMailer SMTP client. Check settings.']);
            exit;
        }
        
        try {
            $mail->addAddress($testEmail);
            $mail->Subject = 'DivineShield SMTP Test Email';
            $mail->Body = '
                <h3>SMTP Configuration Successful</h3>
                <p>Hello,</p>
                <p>This is a test email confirming that your SMTP connection settings in <strong>DivineShield</strong> are fully configured and functional.</p>
                <br>
                <p>God bless,</p>
                <p><strong>The DivineShield Team</strong></p>';
            $mail->send();
            
            logAudit($pdo, $_SESSION['user_id'], 'SMTP_TEST_SUCCESS', "SMTP test connection mail successfully sent to $testEmail");
            echo json_encode(['success' => true, 'message' => "SMTP connection verified! Test email successfully sent to $testEmail."]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'PHPMailer failed to send test: ' . $mail->ErrorInfo]);
        }
        exit;
    }

    if ($action === 'prune_logs') {
        $password = $_POST['admin_password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $pwdHash = $stmt->fetchColumn();
        
        if (!$pwdHash || !password_verify($password, $pwdHash)) {
            echo json_encode(['success' => false, 'message' => 'Incorrect password. Action denied.']);
            exit;
        }
        
        try {
            $retentionDays = (int)getSystemConfig($pdo, 'log_retention_days', '90');
            
            if ($retentionDays > 0) {
                $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$retentionDays]);
                $deletedCount = $stmt->rowCount();
                logAudit($pdo, $_SESSION['user_id'], 'LOGS_PRUNED', "Audit logs older than $retentionDays days pruned. $deletedCount entries removed.");
                echo json_encode(['success' => true, 'message' => "Successfully pruned $deletedCount log entries older than $retentionDays days."]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Log retention is set to "Never Prune". Adjust retention settings first.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to prune logs: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'restore_db') {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No backup SQL file selected or file upload failed.']);
            exit;
        }
        
        try {
            $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
            if (strpos($sql, 'CREATE TABLE') === false && strpos($sql, 'INSERT INTO') === false) {
                echo json_encode(['success' => false, 'message' => 'Invalid file contents. Uploaded file is not a valid SQL backup.']);
                exit;
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec($sql);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            logAudit($pdo, $_SESSION['user_id'], 'DB_RESTORED', 'System database restored from SQL file backup.');
            echo json_encode(['success' => true, 'message' => 'Database backup successfully restored!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database restore failed: ' . $e->getMessage()]);
        }
        exit;
    }
}

// get profile pic for navbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();

// Fetch dynamic configurations
$lockout_threshold = getSystemConfig($pdo, 'lockout_threshold', '5');
$session_timeout = getSystemConfig($pdo, 'session_timeout', '60');
$smtp_host = getSystemConfig($pdo, 'smtp_host', 'smtp.gmail.com');
$smtp_port = getSystemConfig($pdo, 'smtp_port', '587');
$smtp_user = getSystemConfig($pdo, 'smtp_user', 'mainpilalauanan@gmail.com');
$smtp_pass = getSystemConfig($pdo, 'smtp_pass', 'uoel eiwn gvxv godj');
$smtp_encryption = getSystemConfig($pdo, 'smtp_encryption', 'tls');
$pw_min_length = getSystemConfig($pdo, 'pw_min_length', '8');
$pw_req_number = getSystemConfig($pdo, 'pw_req_number', '1');
$pw_req_special = getSystemConfig($pdo, 'pw_req_special', '1');
$pw_req_case = getSystemConfig($pdo, 'pw_req_case', '1');
$log_retention_days = getSystemConfig($pdo, 'log_retention_days', '90');
$allow_public_registration = getSystemConfig($pdo, 'allow_public_registration', '1');
$require_admin_approval = getSystemConfig($pdo, 'require_admin_approval', '1');

$email_approval_subject = getSystemConfig($pdo, 'email_approval_subject', 'Your DivineShield Account Has Been Approved');
$email_approval_body = getSystemConfig($pdo, 'email_approval_body', '');
$email_rejection_subject = getSystemConfig($pdo, 'email_rejection_subject', 'Your DivineShield Registration Status Update');
$email_rejection_body = getSystemConfig($pdo, 'email_rejection_body', '');
$email_new_reg_subject = getSystemConfig($pdo, 'email_new_reg_subject', 'New Church Leader Registration Pending Approval');
$email_new_reg_body = getSystemConfig($pdo, 'email_new_reg_body', '');

// Fetch recent audit alerts count (last 24 hours)
$stmtAlerts = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action IN ('FAILED_LOGIN', 'LOCKOUT_TRIGGERED', 'UNAUTHORIZED_ACCESS') AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$recentAlerts = (int)$stmtAlerts->fetchColumn();

// Compute SMTP configuration status
$smtpStatus = (!empty($smtp_host) && !empty($smtp_user) && !empty($smtp_pass)) ? 'Active' : 'Unconfigured';

$pageTitle = "System Settings";
include 'includes/header.php';
?>

<!-- Main Settings Layout -->
<form id="settings-form" autocomplete="off" onsubmit="event.preventDefault(); saveSettings();">
    <div style="display:flex; gap:24px; align-items:stretch; height:680px; margin-bottom:24px;">
        <!-- Left Sidebar Column -->
        <div class="dashboard-card" style="width:320px; flex-shrink:0; padding:24px; display:flex; flex-direction:column; gap:20px; height:100%; overflow-y:auto;">
            <span style="font-size:0.75rem; font-weight:800; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.08em; padding-left:8px; margin-bottom:4px;">Settings</span>
            
            <div style="display:flex; flex-direction:column; gap:8px;">
                <!-- Tab 1 -->
                <div onclick="switchTab('tab-access')" id="btn-tab-access" class="settings-tab active">
                    <span class="settings-tab-title">Access Policy Rules</span>
                    <span class="settings-tab-desc">Failed log lockouts &amp; session timers</span>
                </div>
                <!-- Tab 2 -->
                <div onclick="switchTab('tab-password')" id="btn-tab-password" class="settings-tab">
                    <span class="settings-tab-title">Password Complexity</span>
                    <span class="settings-tab-desc">Define rules for password strength</span>
                </div>
                <!-- Tab 3 -->
                <div onclick="switchTab('tab-registration')" id="btn-tab-registration" class="settings-tab">
                    <span class="settings-tab-title">Registration Policies</span>
                    <span class="settings-tab-desc">Public signups &amp; admin approval</span>
                </div>
                <!-- Tab 4 -->
                <div onclick="switchTab('tab-smtp')" id="btn-tab-smtp" class="settings-tab">
                    <span class="settings-tab-title">SMTP Mail Config</span>
                    <span class="settings-tab-desc">Email server details &amp; test tool</span>
                </div>
                <!-- Tab 5 -->
                <div onclick="switchTab('tab-emails')" id="btn-tab-emails" class="settings-tab">
                    <span class="settings-tab-title">Email Templates</span>
                    <span class="settings-tab-desc">Edit custom approval/rejection text</span>
                </div>
                <!-- Tab 6 -->
                <div onclick="switchTab('tab-maintenance')" id="btn-tab-maintenance" class="settings-tab">
                    <span class="settings-tab-title">Database &amp; Maintenance</span>
                    <span class="settings-tab-desc">Backup exports, restores, &amp; logs prune</span>
                </div>
            </div>
        </div>

        <!-- Right Content Column -->
        <div style="flex:1; display:flex; flex-direction:column; height:100%; gap:16px;">
            <!-- Scrollable Card Box -->
            <div class="dashboard-card" style="flex:1; overflow-y:auto; padding:28px; display:flex; flex-direction:column; min-height:0; position:relative;">
                
                <!-- Pane 1: Access Policy Rules -->
                <div id="content-tab-access" class="settings-pane active">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:16px; margin-bottom:24px; gap:16px;">
                        <div>
                            <h2 style="color:var(--white); font-size:1.25rem; font-weight:700; margin:0 0 4px 0; font-family:var(--font-head);">Access Policy Rules</h2>
                            <p style="font-size:0.8rem; color:var(--gray-400); margin:0;">Configure access parameters, account lockouts, and session timeouts for system users.</p>
                        </div>
                        <span class="badge <?php echo $recentAlerts > 0 ? 'badge-danger' : 'badge-success'; ?>" style="font-size:0.75rem; padding:6px 12px; border-radius:8px;">
                            <i class="fas <?php echo $recentAlerts > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check'; ?>"></i>
                            <?php echo $recentAlerts; ?> Recent Alerts (24h)
                        </span>
                    </div>
                    
                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Setting Property</th>
                                <th style="width:320px; text-align:right;">Value / Control</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">1</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Lockout Threshold</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Number of failed login attempts before a temporary lockout is triggered for the IP address.</div>
                                </td>
                                <td style="text-align:right;">
                                    <select class="auth-select" name="lockout_threshold" id="lockout_threshold" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:40px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.88rem;">
                                        <option value="3" <?php echo $lockout_threshold === '3' ? 'selected' : ''; ?>>3 failed attempts (Strict)</option>
                                        <option value="5" <?php echo $lockout_threshold === '5' ? 'selected' : ''; ?>>5 failed attempts (Recommended)</option>
                                        <option value="10" <?php echo $lockout_threshold === '10' ? 'selected' : ''; ?>>10 failed attempts</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">2</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Session Idle Timeout</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Duration of user inactivity before the system automatically terminates the active session.</div>
                                </td>
                                <td style="text-align:right;">
                                    <select class="auth-select" name="session_timeout" id="session_timeout" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:40px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.88rem;">
                                        <option value="30" <?php echo $session_timeout === '30' ? 'selected' : ''; ?>>30 minutes</option>
                                        <option value="60" <?php echo $session_timeout === '60' ? 'selected' : ''; ?>>1 hour (Default)</option>
                                        <option value="120" <?php echo $session_timeout === '120' ? 'selected' : ''; ?>>2 hours</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pane 2: Password Complexity -->
                <div id="content-tab-password" class="settings-pane">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:16px; margin-bottom:24px; gap:16px;">
                        <div>
                            <h2 style="color:var(--white); font-size:1.25rem; font-weight:700; margin:0 0 4px 0; font-family:var(--font-head);">Password Complexity Rules</h2>
                            <p style="font-size:0.8rem; color:var(--gray-400); margin:0;">Enforce authentication strength policies for Leader and Staff accounts on signup.</p>
                        </div>
                    </div>

                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Setting Property</th>
                                <th style="width:320px; text-align:right;">Value / Control</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">1</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Minimum Password Length</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Define the minimum number of characters required for a valid password.</div>
                                </td>
                                <td style="text-align:right;">
                                    <input type="number" class="auth-input" name="pw_min_length" id="pw_min_length" value="<?php echo htmlspecialchars($pw_min_length); ?>" min="6" max="32" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:40px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.88rem; text-align:right;" required>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">2</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Require Numbers</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Enforce that passwords contain at least one numerical digit (0-9).</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; justify-content:flex-end; width:100%;">
                                        <label class="switch" style="position:relative; display:inline-block; width:50px; height:26px; margin:0;">
                                            <input type="checkbox" name="pw_req_number" id="pw_req_number" value="1" <?php echo $pw_req_number === '1' ? 'checked' : ''; ?> style="opacity:0; width:0; height:0;">
                                            <span class="slider" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:rgba(255,255,255,0.1); transition:.4s; border-radius:34px; border:1px solid rgba(255,255,255,0.15);"></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">3</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Require Mixed Case</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Require passwords to mix both uppercase and lowercase letters.</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; justify-content:flex-end; width:100%;">
                                        <label class="switch" style="position:relative; display:inline-block; width:50px; height:26px; margin:0;">
                                            <input type="checkbox" name="pw_req_case" id="pw_req_case" value="1" <?php echo $pw_req_case === '1' ? 'checked' : ''; ?> style="opacity:0; width:0; height:0;">
                                            <span class="slider" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:rgba(255,255,255,0.1); transition:.4s; border-radius:34px; border:1px solid rgba(255,255,255,0.15);"></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">4</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Require Special Characters</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Require passwords to contain at least one special symbol (e.g. @, #, $, !).</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; justify-content:flex-end; width:100%;">
                                        <label class="switch" style="position:relative; display:inline-block; width:50px; height:26px; margin:0;">
                                            <input type="checkbox" name="pw_req_special" id="pw_req_special" value="1" <?php echo $pw_req_special === '1' ? 'checked' : ''; ?> style="opacity:0; width:0; height:0;">
                                            <span class="slider" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:rgba(255,255,255,0.1); transition:.4s; border-radius:34px; border:1px solid rgba(255,255,255,0.15);"></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pane 3: Registration Policies -->
                <div id="content-tab-registration" class="settings-pane">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:16px; margin-bottom:24px; gap:16px;">
                        <div>
                            <h2 style="color:var(--white); font-size:1.25rem; font-weight:700; margin:0 0 4px 0; font-family:var(--font-head);">Registration Policies</h2>
                            <p style="font-size:0.8rem; color:var(--gray-400); margin:0;">Configure self-signup availability and verification steps for new church leaders.</p>
                        </div>
                    </div>

                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Setting Property</th>
                                <th style="width:320px; text-align:right;">Value / Control</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">1</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Allow Public Registration</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Allows new church leaders to register dynamically on the signup portal page.</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; justify-content:flex-end; width:100%;">
                                        <label class="switch" style="position:relative; display:inline-block; width:50px; height:26px; margin:0;">
                                            <input type="checkbox" name="allow_public_registration" id="allow_public_registration" value="1" <?php echo $allow_public_registration === '1' ? 'checked' : ''; ?> style="opacity:0; width:0; height:0;">
                                            <span class="slider" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:rgba(255,255,255,0.1); transition:.4s; border-radius:34px; border:1px solid rgba(255,255,255,0.15);"></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">2</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Require Administrator Approval</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">New signups will be placed in a pending state until manually verified by an administrator.</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; justify-content:flex-end; width:100%;">
                                        <label class="switch" style="position:relative; display:inline-block; width:50px; height:26px; margin:0;">
                                            <input type="checkbox" name="require_admin_approval" id="require_admin_approval" value="1" <?php echo $require_admin_approval === '1' ? 'checked' : ''; ?> style="opacity:0; width:0; height:0;">
                                            <span class="slider" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:rgba(255,255,255,0.1); transition:.4s; border-radius:34px; border:1px solid rgba(255,255,255,0.15);"></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pane 4: SMTP Config -->
                <div id="content-tab-smtp" class="settings-pane">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:16px; margin-bottom:24px; gap:16px;">
                        <div>
                            <h2 style="color:var(--white); font-size:1.25rem; font-weight:700; margin:0 0 4px 0; font-family:var(--font-head);">SMTP Mail Configuration</h2>
                            <p style="font-size:0.8rem; color:var(--gray-400); margin:0;">Define mail server settings for automated user registrations and password notifications.</p>
                        </div>
                        <span class="badge <?php echo $smtpStatus === 'Active' ? 'badge-success' : 'badge-danger'; ?>" style="font-size:0.75rem; padding:6px 12px; border-radius:8px;">
                            <i class="fas <?php echo $smtpStatus === 'Active' ? 'fa-envelope-circle-check' : 'fa-circle-xmark'; ?>"></i>
                            SMTP: <?php echo $smtpStatus; ?>
                        </span>
                    </div>

                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Setting Property</th>
                                <th style="width:340px; text-align:right;">Value / Control</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">1</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">SMTP Host Server</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Outgoing server hostname (e.g. smtp.gmail.com).</div>
                                </td>
                                <td style="text-align:right;">
                                    <input type="text" class="auth-input" name="smtp_host" id="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:40px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.88rem;" required>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">2</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Port &amp; Encryption</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Connection port and encryption protocol to secure outbound mail.</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; gap:12px; justify-content:flex-end;">
                                        <input type="number" class="auth-input" name="smtp_port" id="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:90px; height:40px; border-radius:8px; color:var(--white); text-align:center; font-size:0.88rem;" required>
                                        <select class="auth-select" name="smtp_encryption" id="smtp_encryption" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:160px; height:40px; border-radius:8px; color:var(--white); padding:0 8px; font-size:0.88rem;">
                                            <option value="tls" <?php echo $smtp_encryption === 'tls' ? 'selected' : ''; ?>>STARTTLS</option>
                                            <option value="ssl" <?php echo $smtp_encryption === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo $smtp_encryption === 'none' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">3</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">SMTP Username Email</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">User account email address used to authenticate on the SMTP server.</div>
                                </td>
                                <td style="text-align:right;">
                                    <input type="email" class="auth-input" name="smtp_user" id="smtp_user" value="<?php echo htmlspecialchars($smtp_user); ?>" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:40px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.88rem;" required>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">4</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">SMTP Account Password</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Authentication password or secure App Password.</div>
                                </td>
                                <td style="text-align:right;">
                                    <input type="password" class="auth-input" name="smtp_pass" id="smtp_pass" value="<?php echo htmlspecialchars($smtp_pass); ?>" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:40px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.88rem;" required>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">5</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">SMTP Connection Test</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Send a test email connection request to verify SMTP configuration values.</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; gap:12px; justify-content:flex-end;">
                                        <input type="email" class="auth-input" id="test_recipient_email" placeholder="test-recipient@domain.com" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); height:40px; border-radius:8px; font-size:0.85rem; padding:0 12px; flex:1; max-width:180px;">
                                        <button type="button" onclick="testSMTPConnection()" class="btn btn-outline btn-sm" style="height:40px; display:inline-flex; align-items:center; gap:8px; font-size:0.8rem; padding:0 16px;">
                                            <i class="fas fa-paper-plane"></i> Test Mail
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pane 5: Email Templates Customization -->
                <div id="content-tab-emails" class="settings-pane">
                    <div style="border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:16px; margin-bottom:20px;">
                        <h2 style="color:var(--white); font-size:1.25rem; font-weight:700; margin:0 0 4px 0; font-family:var(--font-head);">Email Notification Templates</h2>
                        <p style="font-size:0.8rem; color:var(--gray-400); margin:0;">Customize dynamic notifications sent by the system. Use brackets to insert placeholders.</p>
                    </div>

                    <div style="background:rgba(37,99,235,0.08); border:1px solid rgba(37,99,235,0.2); border-radius:12px; padding:16px; margin-bottom:24px; display:flex; flex-direction:column; gap:8px;">
                        <span style="font-size:0.75rem; font-weight:700; color:var(--blue-400); text-transform:uppercase; letter-spacing:0.04em;">Available Placeholders:</span>
                        <div style="display:flex; flex-wrap:wrap; gap:8px;">
                            <code style="background:rgba(255,255,255,0.06); padding:4px 8px; border-radius:6px; font-size:0.75rem; color:#fff; border:1px solid rgba(255,255,255,0.08);">{first_name}</code>
                            <code style="background:rgba(255,255,255,0.06); padding:4px 8px; border-radius:6px; font-size:0.75rem; color:#fff; border:1px solid rgba(255,255,255,0.08);">{last_name}</code>
                            <code style="background:rgba(255,255,255,0.06); padding:4px 8px; border-radius:6px; font-size:0.75rem; color:#fff; border:1px solid rgba(255,255,255,0.08);">{username}</code>
                            <code style="background:rgba(255,255,255,0.06); padding:4px 8px; border-radius:6px; font-size:0.75rem; color:#fff; border:1px solid rgba(255,255,255,0.08);">{email}</code>
                            <code style="background:rgba(255,255,255,0.06); padding:4px 8px; border-radius:6px; font-size:0.75rem; color:#fff; border:1px solid rgba(255,255,255,0.08);">{phone}</code>
                            <code style="background:rgba(255,255,255,0.06); padding:4px 8px; border-radius:6px; font-size:0.75rem; color:#fff; border:1px solid rgba(255,255,255,0.08);">{position_title}</code>
                            <code style="background:rgba(255,255,255,0.06); padding:4px 8px; border-radius:6px; font-size:0.75rem; color:#fff; border:1px solid rgba(255,255,255,0.08);">{church_name}</code>
                            <code style="background:rgba(255,255,255,0.06); padding:4px 8px; border-radius:6px; font-size:0.75rem; color:#fff; border:1px solid rgba(255,255,255,0.08);">{admin_message}</code>
                        </div>
                    </div>

                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th style="width:30%;">Setting Property</th>
                                <th style="text-align:left;">Value / Control</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Approval Template -->
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600; vertical-align:top; padding-top:20px;">1</td>
                                <td style="vertical-align:top; padding-top:20px;">
                                    <strong style="color:var(--white); font-size:0.9rem; display:block;"><i class="fas fa-circle-check" style="color:var(--green-500); margin-right:6px;"></i>Approval Email</strong>
                                    <span style="font-size:0.75rem; color:var(--gray-500); display:block; margin-top:4px; line-height:1.4;">Sent when an administrator approves a leader's application.</span>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:10px;">
                                        <div>
                                            <label style="display:block; font-size:0.68rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:4px;">Email Subject</label>
                                            <input type="text" class="auth-input" name="email_approval_subject" value="<?php echo htmlspecialchars($email_approval_subject); ?>" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:38px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.85rem;" required>
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.68rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:4px;">Email Body Template</label>
                                            <textarea class="auth-input" name="email_approval_body" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:130px; font-family:'Courier New', Courier, monospace; font-size:0.82rem; line-height:1.5; padding:12px; resize:vertical; border-radius:8px; color:var(--white);" required><?php echo htmlspecialchars($email_approval_body); ?></textarea>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <!-- Rejection Template -->
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600; vertical-align:top; padding-top:20px;">2</td>
                                <td style="vertical-align:top; padding-top:20px;">
                                    <strong style="color:var(--white); font-size:0.9rem; display:block;"><i class="fas fa-circle-xmark" style="color:var(--red-500); margin-right:6px;"></i>Rejection Email</strong>
                                    <span style="font-size:0.75rem; color:var(--gray-500); display:block; margin-top:4px; line-height:1.4;">Sent when an administrator rejects a leader's application.</span>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:10px;">
                                        <div>
                                            <label style="display:block; font-size:0.68rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:4px;">Email Subject</label>
                                            <input type="text" class="auth-input" name="email_rejection_subject" value="<?php echo htmlspecialchars($email_rejection_subject); ?>" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:38px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.85rem;" required>
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.68rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:4px;">Email Body Template</label>
                                            <textarea class="auth-input" name="email_rejection_body" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:130px; font-family:'Courier New', Courier, monospace; font-size:0.82rem; line-height:1.5; padding:12px; resize:vertical; border-radius:8px; color:var(--white);" required><?php echo htmlspecialchars($email_rejection_body); ?></textarea>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <!-- New Registration Alert -->
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600; vertical-align:top; padding-top:20px;">3</td>
                                <td style="vertical-align:top; padding-top:20px;">
                                    <strong style="color:var(--white); font-size:0.9rem; display:block;"><i class="fas fa-envelope-open-text" style="color:var(--blue-500); margin-right:6px;"></i>Admin Signup Alert</strong>
                                    <span style="font-size:0.75rem; color:var(--gray-500); display:block; margin-top:4px; line-height:1.4;">Notification sent to the main administration email when a signup occurs.</span>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:10px;">
                                        <div>
                                            <label style="display:block; font-size:0.68rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:4px;">Email Subject</label>
                                            <input type="text" class="auth-input" name="email_new_reg_subject" value="<?php echo htmlspecialchars($email_new_reg_subject); ?>" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:38px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.85rem;" required>
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.68rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; margin-bottom:4px;">Email Body Template</label>
                                            <textarea class="auth-input" name="email_new_reg_body" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:130px; font-family:'Courier New', Courier, monospace; font-size:0.82rem; line-height:1.5; padding:12px; resize:vertical; border-radius:8px; color:var(--white);" required><?php echo htmlspecialchars($email_new_reg_body); ?></textarea>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pane 6: Database & Maintenance -->
                <div id="content-tab-maintenance" class="settings-pane">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:16px; margin-bottom:24px; gap:16px;">
                        <div>
                            <h2 style="color:var(--white); font-size:1.25rem; font-weight:700; margin:0 0 4px 0; font-family:var(--font-head);">Database &amp; Maintenance</h2>
                            <p style="font-size:0.8rem; color:var(--gray-400); margin:0;">Backup the system, restore configurations, or prune old audit trail records.</p>
                        </div>
                        <span class="badge badge-success" style="font-size:0.75rem; padding:6px 12px; border-radius:8px; background:rgba(45,212,191,0.15); color:var(--teal-400); border:1px solid rgba(45,212,191,0.2);">
                            <i class="fas fa-server"></i> System Online
                        </span>
                    </div>

                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Setting Property</th>
                                <th style="width:320px; text-align:right;">Value / Control</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">1</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Log Retention Period</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Configure how long database audit logs are kept before being auto-pruned.</div>
                                </td>
                                <td style="text-align:right;">
                                    <select class="auth-select" name="log_retention_days" id="log_retention_days" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.1); width:100%; height:40px; border-radius:8px; color:var(--white); padding:0 12px; font-size:0.88rem;">
                                        <option value="30" <?php echo $log_retention_days === '30' ? 'selected' : ''; ?>>30 Days</option>
                                        <option value="90" <?php echo $log_retention_days === '90' ? 'selected' : ''; ?>>90 Days</option>
                                        <option value="180" <?php echo $log_retention_days === '180' ? 'selected' : ''; ?>>180 Days</option>
                                        <option value="0" <?php echo $log_retention_days === '0' ? 'selected' : ''; ?>>Never Prune Logs</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">2</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">System Database Export</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Download a complete SQL backup file (.sql) containing schema structure and system records.</div>
                                </td>
                                <td style="text-align:right;">
                                    <a href="settings.php?action=export_db" class="btn btn-success btn-sm" style="display:inline-flex; align-items:center; gap:8px; font-size:0.8rem; padding:0 20px; height:38px; border-radius:8px;">
                                        <i class="fas fa-download"></i> Export SQL
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">3</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Prune Audit Trail Logs</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">Manually purge outdated log files from the system logs database. Requires admin auth.</div>
                                </td>
                                <td style="text-align:right;">
                                    <button type="button" onclick="pruneLogs()" class="btn btn-danger btn-sm" style="display:inline-flex; align-items:center; gap:8px; font-size:0.8rem; padding:0 20px; height:38px; border-radius:8px;">
                                        <i class="fas fa-trash-can"></i> Run Pruning
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500); font-weight:600;">4</td>
                                <td>
                                    <strong style="color:var(--white); font-size:0.9rem;">Restore System Database</strong>
                                    <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;"><span style="color:var(--red-500); font-weight:600;">CAUTION:</span> Overwrites all current database records and configurations using a backup file.</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end;">
                                        <input type="file" id="db_restore_file" accept=".sql" style="font-size:0.78rem; color:var(--gray-400); max-width:220px;">
                                        <button type="button" onclick="restoreDatabase()" class="btn btn-outline btn-sm" style="height:36px; display:inline-flex; align-items:center; gap:8px; font-size:0.8rem; padding:0 16px; border-radius:8px;">
                                            <i class="fas fa-rotate-left"></i> Restore Backup
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Docked Save Bar at bottom of Right Column -->
            <div style="
                background: rgba(15, 23, 42, 0.9);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: var(--radius-md);
                padding: 16px 28px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: var(--shadow-lg);
                flex-shrink: 0;
            ">
                <div style="font-size: 0.82rem; color: var(--gray-400); font-family: var(--font-body); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-info" style="color: #2ed573; font-size: 0.95rem;"></i>
                    <span>All categories belong to the same configuration form. You can save from any tab.</span>
                </div>
                <button type="submit" class="btn btn-success" style="padding:12px 36px; height:46px; background:#2ed573; border-color:#2ed573; color:#0f172a; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-floppy-disk"></i> Save Configurations
                </button>
            </div>

        </div>
    </div>
</form>

<style>
/* Switch/Toggle CSS */
.switch input:checked + .slider {
    background-color: #2ed573 !important;
}
.switch input:checked + .slider:before {
    transform: translateX(24px);
    background-color: #0f172a !important;
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: var(--gray-400);
    transition: .4s;
    border-radius: 50%;
}

/* Sidebar Settings Tabs styling */
.settings-tab {
    padding: 14px 18px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: transparent;
    border: 1px solid transparent;
    margin-bottom: 4px;
}
.settings-tab:hover {
    background: rgba(255, 255, 255, 0.03);
}
.settings-tab.active {
    background: rgba(46, 213, 115, 0.08); /* Green tint matching screenshot */
    border-color: rgba(46, 213, 115, 0.12);
}
.settings-tab .settings-tab-title {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--white);
    transition: all 0.2s ease;
}
.settings-tab.active .settings-tab-title {
    color: #2ed573 !important; /* Green text matching screenshot */
}
.settings-tab .settings-tab-desc {
    font-size: 0.70rem;
    color: var(--gray-500);
    transition: all 0.2s ease;
}
.settings-tab.active .settings-tab-desc {
    color: rgba(46, 213, 115, 0.6) !important;
}

/* Custom Settings Table layout */
.settings-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
}
.settings-table th {
    text-align: left;
    padding: 12px 16px;
    color: var(--gray-500);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.settings-table td {
    padding: 18px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--gray-300);
    font-size: 0.88rem;
    vertical-align: middle;
}
.settings-table tr:last-child td {
    border-bottom: none;
}
.settings-table tr:hover td {
    background: rgba(255, 255, 255, 0.01);
}

/* Settings Panes visibility */
.settings-pane {
    display: none;
}
.settings-pane.active {
    display: block;
}

/* Custom Scrollbar for scrollable panes */
.dashboard-card::-webkit-scrollbar {
    width: 6px;
}
.dashboard-card::-webkit-scrollbar-track {
    background: transparent;
}
.dashboard-card::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}
.dashboard-card::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>

<script>
function switchTab(tabId) {
    // Hide all panes
    document.querySelectorAll('.settings-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    // Deactivate all buttons
    document.querySelectorAll('.settings-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    // Show active pane & button
    document.getElementById('content-' + tabId).classList.add('active');
    document.getElementById('btn-' + tabId).classList.add('active');
}

function saveSettings() {
    Swal.fire({
        title: 'Saving System Configurations...',
        text: 'Applying application settings and updating policies in database.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData(document.getElementById('settings-form'));
    formData.append('action', 'save_config');

    fetch('settings.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Saved Successfully!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'Great'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Error Saving Settings',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'Retry'
            });
        }
    })
    .catch(err => {
        Swal.fire({
            title: 'Request Failed',
            text: 'An error occurred while saving configs.',
            icon: 'error',
            confirmButtonText: 'Okay'
        });
    });
}

function testSMTPConnection() {
    const email = document.getElementById('test_recipient_email').value;
    if (!email) {
        Swal.fire('Input Required', 'Please enter a test email recipient.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Testing Mail Server...',
        text: 'Connecting to SMTP server and sending a test email to ' + email,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('action', 'test_smtp');
    formData.append('test_email', email);

    fetch('settings.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('SMTP Verified!', data.message, 'success');
        } else {
            Swal.fire('SMTP Connection Failed', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Request Failed', 'Failed to communicate with the SMTP test API.', 'error');
    });
}

function pruneLogs() {
    const retention = document.getElementById('log_retention_days').value;
    if (retention === '0') {
        Swal.fire('Action Disabled', 'Log retention is configured to "Never Prune". Please select a retention limit to prune logs.', 'info');
        return;
    }

    Swal.fire({
        title: 'Confirm Log Pruning',
        text: 'This will permanently delete all audit trail entries older than ' + retention + ' days. Please enter your Admin password to authorize:',
        input: 'password',
        inputAttributes: {
            autocapitalize: 'off',
            autocorrect: 'off'
        },
        showCancelButton: true,
        confirmButtonText: 'Confirm Prune',
        confirmButtonColor: '#ef4444',
        showLoaderOnConfirm: true,
        preConfirm: (password) => {
            if (!password) {
                Swal.showValidationMessage('Password is required.');
                return false;
            }
            const formData = new FormData();
            formData.append('action', 'prune_logs');
            formData.append('admin_password', password);

            return fetch('settings.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value.success) {
                Swal.fire('Pruning Complete', result.value.message, 'success');
            } else {
                Swal.fire('Failed', result.value.message, 'error');
            }
        }
    });
}

function restoreDatabase() {
    const fileInput = document.getElementById('db_restore_file');
    if (fileInput.files.length === 0) {
        Swal.fire('Select File', 'Please choose a SQL backup file first.', 'warning');
        return;
    }

    Swal.fire({
        title: 'CAUTION: Restore Database?',
        text: 'This operation will overwrite the current database with the uploaded backup SQL. All current data could be lost. Are you absolutely sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Restore Database',
        confirmButtonColor: '#d97706',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Restoring Database...',
                text: 'Executing SQL backup script...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('action', 'restore_db');
            formData.append('backup_file', fileInput.files[0]);

            fetch('settings.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Restore Successful!',
                        text: data.message,
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Restore Failed', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Request Failed', 'Failed to communicate with DB restore API.', 'error');
            });
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
