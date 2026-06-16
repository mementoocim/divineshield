<?php
/**
 * DivineShield - Feeding Programs
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Fetch admin profile picture for topbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();
?>
<?php
$pageTitle = "Feeding Programs";
include 'includes/header.php';
?>
        <div class="dashboard-card" style="padding: 24px;">
          <h2 style="color: var(--white); margin-bottom: 8px;">Feeding Programs</h2>
          <p style="color: var(--gray-400);">This page is under development.</p>
        </div>
      <?php include 'includes/footer.php'; ?>
