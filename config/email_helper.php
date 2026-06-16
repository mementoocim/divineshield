<?php
require_once __DIR__ . '/../Phpmailer/src/Exception.php';
require_once __DIR__ . '/../Phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../Phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function initializePHPMailer() {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mainpilalauanan@gmail.com';
        $mail->Password   = 'uoel eiwn gvxv godj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('mainpilalauanan@gmail.com', 'DivineShield');
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
function sendLeaderApprovalEmail(string $toEmail, string $firstName, string $lastName, string $username): bool {
    $mail = initializePHPMailer();
    if (!$mail) return false;

    try {
        $mail->addAddress($toEmail, "Pastor $firstName $lastName");
        $mail->Subject = 'Your DivineShield Account Has Been Approved';
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
            .info-box { background: #f0f9ff; border-left: 4px solid #2563eb; border-radius: 6px; padding: 16px 20px; margin: 24px 0; }
            .info-box p { margin: 4px 0; font-size: 14px; color: #1e3a8a; }
            .btn { display: inline-block; margin-top: 24px; background: #2563eb; color: #ffffff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 15px; }
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
              <h2>Welcome, Pastor ' . htmlspecialchars($firstName) . '!</h2>
              <p>We are pleased to inform you that your church leader account on <strong>DivineShield</strong> has been <strong>reviewed and approved</strong> by our system administrator. Your account is now fully active.</p>
              <div class="info-box">
                <p><strong>Name:</strong> Pastor ' . htmlspecialchars($firstName . ' ' . $lastName) . '</p>
                <p><strong>Username:</strong> @' . htmlspecialchars($username) . '</p>
                <p><strong>Status:</strong> Active ✔</p>
              </div>
              <p>You may now log in to the DivineShield portal to manage your church site, submit child beneficiary records, and access all features available to church leaders.</p>
              <p>If you have any questions or need assistance, please don\'t hesitate to reach out to our support team.</p>
              <p style="margin-top: 28px;">God bless your ministry,<br><strong>The DivineShield Team</strong></p>
            </div>
            <div class="footer">
              &copy; ' . date('Y') . ' DivineShield &mdash; All rights reserved.<br>
              This is an automated notification. Please do not reply to this email.
            </div>
          </div>
        </body>
        </html>';

        $mail->AltBody = "Dear Pastor $firstName $lastName,\n\nYour DivineShield church leader account (@$username) has been approved and is now active.\n\nYou may now log in to the portal.\n\nGod bless,\nThe DivineShield Team";

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
function sendLeaderRejectionEmail(string $toEmail, string $firstName, string $lastName, string $username): bool {
    $mail = initializePHPMailer();
    if (!$mail) return false;

    try {
        $mail->addAddress($toEmail, "Pastor $firstName $lastName");
        $mail->Subject = 'Your DivineShield Registration Status Update';
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
            .info-box { background: #fff7f7; border-left: 4px solid #ef4444; border-radius: 6px; padding: 16px 20px; margin: 24px 0; }
            .info-box p { margin: 4px 0; font-size: 14px; color: #7f1d1d; }
            .footer { background: #f8fafc; text-align: center; padding: 20px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>✝ DivineShield</h1>
              <p>Church Beneficiary Management System</p>
            </div>
            <div class="body">
              <span class="badge">✖ Account Not Approved</span>
              <h2>Dear Pastor ' . htmlspecialchars($firstName) . ',</h2>
              <p>Thank you for your interest in joining the <strong>DivineShield</strong> platform. After careful review, we regret to inform you that your church leader registration has <strong>not been approved</strong> at this time.</p>
              <div class="info-box">
                <p><strong>Name:</strong> Pastor ' . htmlspecialchars($firstName . ' ' . $lastName) . '</p>
                <p><strong>Username:</strong> @' . htmlspecialchars($username) . '</p>
                <p><strong>Status:</strong> Not Approved</p>
              </div>
              <p>This may be due to incomplete information, unverified credentials, or other administrative reasons. If you believe this is an error or would like to clarify your registration details, please contact our support team directly.</p>
              <p>We appreciate your understanding and your commitment to your ministry.</p>
              <p style="margin-top: 28px;">God bless,<br><strong>The DivineShield Team</strong></p>
            </div>
            <div class="footer">
              &copy; ' . date('Y') . ' DivineShield &mdash; All rights reserved.<br>
              This is an automated notification. Please do not reply to this email.
            </div>
          </div>
        </body>
        </html>';

        $mail->AltBody = "Dear Pastor $firstName $lastName,\n\nWe regret to inform you that your DivineShield church leader account (@$username) has not been approved at this time.\n\nIf you believe this is in error, please contact our support team.\n\nGod bless,\nThe DivineShield Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Rejection email failed for $toEmail: " . $e->getMessage());
        return false;
    }
}