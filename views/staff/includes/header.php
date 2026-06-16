<?php
// Session and Role Verification must be handled in the main page before including this.
// Fetch staff profile picture for topbar
$stmtStaff = $pdo->prepare("SELECT profile_picture, first_name, last_name, role FROM users WHERE id = ?");
$stmtStaff->execute([$_SESSION['user_id']]);
$staffData = $stmtStaff->fetch(PDO::FETCH_ASSOC);
$staffProfilePic = $staffData['profile_picture'] ?? null;
$staffFullName = trim(($staffData['first_name'] ?? '') . ' ' . ($staffData['last_name'] ?? ''));
if (empty($staffFullName)) {
    $staffFullName = 'System Staff';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Staff Portal'; ?> – DivineShield</title>
  <link rel="stylesheet" href="../../assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
</head>
<body>
  <div class="admin-layout">
    
    <!-- SIDEBAR NAVIGATION -->
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
      
      <!-- TOP NAVIGATION BAR -->
      <header class="admin-topbar">
        <div class="topbar-title">
          <i class="fas fa-desktop" style="margin-right:10px; color:var(--blue-400);"></i> <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Staff Dashboard'; ?>
        </div>
        
        <div class="topbar-user">
          <div class="user-badge-group">
            <div class="user-badge-name"><?php echo htmlspecialchars($staffFullName); ?></div>
            <div class="user-badge-role">Staff / Encoder</div>
          </div>
          <?php if (!empty($staffProfilePic) && file_exists('../../' . $staffProfilePic)): ?>
            <img src="../../<?php echo htmlspecialchars($staffProfilePic); ?>" alt="Profile" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.15);" />
          <?php else: ?>
            <div class="logo-mark small" style="background:linear-gradient(135deg, var(--blue-400), var(--blue-500)); color:var(--white);"><i class="fas fa-user"></i></div>
          <?php endif; ?>
        </div>
      </header>

      <div class="admin-content">
