<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../Phpmailer/src/Exception.php';
require_once __DIR__ . '/../Phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../Phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function initializePHPMailer()
{
  global $pdo;
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    
    // Retrieve configurations dynamically from DB
    $host = getSystemConfig($pdo, 'smtp_host', 'smtp.gmail.com');
    $port = (int)getSystemConfig($pdo, 'smtp_port', '587');
    $user = getSystemConfig($pdo, 'smtp_user', 'mainpilalauanan@gmail.com');
    $pass = getSystemConfig($pdo, 'smtp_pass', 'uoel eiwn gvxv godj');
    $encryption = getSystemConfig($pdo, 'smtp_encryption', 'tls');
    
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $pass;
    
    if ($encryption === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPAuth = !empty($user); // Only authenticate if user is specified
        $mail->SMTPSecure = '';
    }
    
    $mail->Port = $port;
    $mail->setFrom($user, 'DivineShield');
    $mail->isHTML(true);
    return $mail;
  } catch (Exception $e) {
    error_log("PHPMailer init failed: " . $e->getMessage());
    return null;
  }
}

/**
 * Send approval notification to a church leader.
 */
function sendLeaderApprovalEmail(string $toEmail, string $firstName, string $lastName, string $username): bool
{
  global $pdo;
  $mail = initializePHPMailer();
  if (!$mail)
    return false;

  try {
    $subject = getSystemConfig($pdo, 'email_approval_subject', 'Your DivineShield Account Has Been Approved');
    $bodyTemplate = getSystemConfig($pdo, 'email_approval_body', '');
    
    // Replace placeholders
    $search = ['{first_name}', '{last_name}', '{username}'];
    $replace = [$firstName, $lastName, $username];
    $bodyText = str_replace($search, $replace, $bodyTemplate);
    $htmlBody = nl2br($bodyText);
    
    $mail->addAddress($toEmail, "Pastor $firstName $lastName");
    $mail->Subject = $subject;
    $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a1f3c, #2563eb); padding: 36px 30px; text-align: center; }
            .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px; }
            .header p { color: #93c5fd; margin: 6px 0 0; font-size: 13px; }
            .body { padding: 36px 30px; color: #333333; }
            .badge { display: inline-block; background: #dcfce7; color: #16a34a; font-weight: 700; padding: 6px 16px; border-radius: 999px; font-size: 13px; margin-bottom: 20px; }
            h2 { font-size: 20px; color: #1e3a8a; margin: 0 0 12px; }
            p { line-height: 1.7; font-size: 15px; color: #444; }
            .footer { background: #f8fafc; text-align: center; padding: 20px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>DivineShield</h1>
              <p>Church Beneficiary Management System</p>
            </div>
            <div class="body">
              <span class="badge">✔ Account Approved</span>
              <p>' . $htmlBody . '</p>
            </div>
            <div class="footer">
              &copy; ' . date('Y') . ' DivineShield &mdash; All rights reserved.<br>
              This is an automated notification. Please do not reply to this email.
            </div>
          </div>
        </body>
        </html>';

    $mail->AltBody = strip_tags($bodyText);

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Approval email failed for $toEmail: " . $e->getMessage());
    return false;
  }
}

/**
 * Send rejection/deactivation notification to a church leader.
 */
function sendLeaderRejectionEmail(string $toEmail, string $firstName, string $lastName, string $username): bool
{
  global $pdo;
  $mail = initializePHPMailer();
  if (!$mail)
    return false;

  try {
    $subject = getSystemConfig($pdo, 'email_rejection_subject', 'Your DivineShield Registration Status Update');
    $bodyTemplate = getSystemConfig($pdo, 'email_rejection_body', '');
    
    $search = ['{first_name}', '{last_name}', '{username}'];
    $replace = [$firstName, $lastName, $username];
    $bodyText = str_replace($search, $replace, $bodyTemplate);
    $htmlBody = nl2br($bodyText);

    $mail->addAddress($toEmail, "Pastor $firstName $lastName");
    $mail->Subject = $subject;
    $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a1f3c, #7f1d1d); padding: 36px 30px; text-align: center; }
            .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px; }
            .header p { color: #fca5a5; margin: 6px 0 0; font-size: 13px; }
            .body { padding: 36px 30px; color: #333333; }
            .badge { display: inline-block; background: #fee2e2; color: #dc2626; font-weight: 700; padding: 6px 16px; border-radius: 999px; font-size: 13px; margin-bottom: 20px; }
            h2 { font-size: 20px; color: #7f1d1d; margin: 0 0 12px; }
            p { line-height: 1.7; font-size: 15px; color: #444; }
            .footer { background: #f8fafc; text-align: center; padding: 20px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>DivineShield</h1>
              <p>Church Beneficiary Management System</p>
            </div>
            <div class="body">
              <span class="badge">✖ Account Not Approved</span>
              <p>' . $htmlBody . '</p>
            </div>
            <div class="footer">
              &copy; ' . date('Y') . ' DivineShield &mdash; All rights reserved.<br>
              This is an automated notification. Please do not reply to this email.
            </div>
          </div>
        </body>
        </html>';

    $mail->AltBody = strip_tags($bodyText);

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Rejection email failed for $toEmail: " . $e->getMessage());
    return false;
  }
}

/**
 * Notify the admin of a new pending church leader registration.
 */
function sendAdminNewRegistrationEmail(
  string $leaderFirstName,
  string $leaderLastName,
  string $leaderEmail,
  string $leaderPhone,
  string $positionTitle,
  string $username,
  string $churchName,
  string $streetAddress,
  string $region,
  string $city,
  string $barangay,
  string $adminMessage = ''
): bool {
  global $pdo;
  $mail = initializePHPMailer();
  if (!$mail)
    return false;

  try {
    $subject = getSystemConfig($pdo, 'email_new_reg_subject', 'New Church Leader Registration Pending Approval');
    $bodyTemplate = getSystemConfig($pdo, 'email_new_reg_body', '');
    
    $search = ['{first_name}', '{last_name}', '{username}', '{email}', '{phone}', '{position_title}', '{church_name}', '{admin_message}'];
    $replace = [$leaderFirstName, $leaderLastName, $username, $leaderEmail, $leaderPhone, $positionTitle, $churchName, $adminMessage];
    $bodyText = str_replace($search, $replace, $bodyTemplate);
    $htmlBody = nl2br($bodyText);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $folder = '/Divineshield';
    if (stripos($uri, '/divinely-shield') !== false) {
      $folder = '/divinely-shield';
    }
    $portalUrl = $protocol . '://' . $host . $folder . '/views/admin/dashboard.php';

    $mail->addAddress('balmacedamico028@gmail.com', 'DivineShield Administrator');
    $mail->Subject = $subject;
    $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a1f3c, #2563eb); padding: 36px 30px; text-align: center; }
            .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px; }
            .header p { color: #93c5fd; margin: 6px 0 0; font-size: 13px; }
            .body { padding: 36px 30px; color: #333333; }
            .badge { display: inline-block; background: #fef9c3; color: #854d0e; font-weight: 700; padding: 6px 16px; border-radius: 999px; font-size: 13px; margin-bottom: 20px; }
            h2 { font-size: 20px; color: #1e3a8a; margin: 0 0 12px; }
            p { line-height: 1.7; font-size: 15px; color: #444; }
            .btn { display: inline-block; margin-top: 28px; background: #2563eb; color: #ffffff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 15px; }
            .footer { background: #f8fafc; text-align: center; padding: 20px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>DivineShield</h1>
              <p>Church Beneficiary Management System</p>
            </div>
            <div class="body">
              <span class="badge">⏳ Pending Review</span>
              <p>' . $htmlBody . '</p>
              <a href="' . $portalUrl . '" class="btn">Go to Admin Portal →</a>
            </div>
            <div class="footer">
              &copy; ' . date('Y') . ' DivineShield &mdash; All rights reserved.<br>
              This is an automated notification. Please do not reply to this email.
            </div>
          </div>
        </body>
        </html>';

    $mail->AltBody = strip_tags($bodyText);

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Admin notification email failed: " . $e->getMessage());
    return false;
  }
}