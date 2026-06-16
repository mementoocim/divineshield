<?php
/**
 * DivineShield - Children Registry
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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Children Registry – DivineShield</title>
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
        <div class="topbar-title"><i class="fas fa-child" style="margin-right:10px; color:var(--blue-400);"></i> Children Registry</div>
        <div class="topbar-user">
          <div class="user-badge-group">
            <div class="user-badge-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Administrator'); ?></div>
            <div class="user-badge-role">System Administrator</div>
          </div>
          <?php if (!empty($adminProfilePic) && file_exists('../../' . $adminProfilePic)): ?>
            <img src="../../<?php echo htmlspecialchars($adminProfilePic); ?>" alt="Profile" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.15);" />
          <?php else: ?>
            <div class="logo-mark small" style="background:linear-gradient(135deg, var(--yellow-400), var(--yellow-500)); color:var(--gray-900);"><i class="fas fa-user-shield"></i></div>
          <?php endif; ?>
        </div>
      </header>

      <!-- CONTENT WRAPPER -->
      <div class="admin-content">
        <div class="dashboard-card" style="padding: 24px;">
          <h2 style="color: var(--white); margin-bottom: 8px;">Children Registry</h2>
          <p style="color: var(--gray-400);">This page is under development.</p>
        </div>
      </div>
    </main>

  </div>

</body>
</html>
