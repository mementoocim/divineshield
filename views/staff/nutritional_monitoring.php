<?php
/**
 * DivineShield - Staff / Encoder Nutritional Monitoring
 */
require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = "Nutritional Monitoring";
include 'includes/header.php';
?>

  <div class="dashboard-card" style="padding: 48px 32px; text-align: center;">
    <h2 style="color: var(--white); margin-bottom: 8px;">Nutritional Monitoring</h2>
    <p style="color: var(--gray-400);">This page is under development.</p>
  </div>

<?php include 'includes/footer.php'; ?>
