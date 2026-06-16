<?php
/**
 * DivineShield - Staff / Encoder Attendance & RFID
 */
require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Attendance & RFID";
include 'includes/header.php';
?>

  <div class="dashboard-card">
    <h2 style="color: var(--white); margin-bottom: 8px;">Attendance &amp; RFID</h2>
    <p style="color: var(--gray-400);">This page is under development.</p>
  </div>

<?php include 'includes/footer.php'; ?>
