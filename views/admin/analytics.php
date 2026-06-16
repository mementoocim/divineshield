<?php
/**
 * DivineShield - Analytics
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
  <title>Analytics – DivineShield</title>
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
        <div class="topbar-title"><i class="fas fa-chart-line" style="margin-right:10px; color:var(--blue-400);"></i> Analytics</div>
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
        
        <!-- Under Construction / Development Card -->
        <div class="dashboard-card" style="text-align: center; padding: 64px 32px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px;">
          <div class="logo-mark" style="width: 80px; height: 80px; font-size: 2.5rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); box-shadow: none; margin: 0 auto;">
            <i class="fas fa-chart-line" style="color: var(--blue-400);"></i>
          </div>
          
          <h2 style="font-family: var(--font-head); color: var(--white); font-size: 1.75rem; font-weight: 700;">Analytics</h2>
          <span class="badge badge-warning" style="font-size: 0.9rem; padding: 6px 16px; border-radius: 20px; font-weight: 600;"><i class="fas fa-hammer" style="margin-right: 6px;"></i> For Development</span>
          
          <p style="color: var(--gray-400); max-width: 500px; line-height: 1.6; font-size: 0.95rem;">
            This module is currently scheduled for development. Once complete, it will provide administrative interfaces and tools to fully manage the interactive graphs, regional nutrition progress indexes, and dynamic metrics for active feeding participants.
          </p>
          
          <a href="dashboard.php" class="btn btn-outline" style="margin-top: 10px; border-color: rgba(255,255,255,0.1); color: var(--gray-300); padding: 10px 24px;">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Dashboard
          </a>
        </div>

      </div>
    </main>

  </div>

</body>
</html>
