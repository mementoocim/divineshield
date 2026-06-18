<?php
// check auth and role must be handled in the main page before including this.
// Fetch church leader details
$stmtLeader = $pdo->prepare("SELECT profile_picture, first_name, last_name FROM users WHERE id = ?");
$stmtLeader->execute([$_SESSION['user_id']]);
$leaderData = $stmtLeader->fetch(PDO::FETCH_ASSOC);
$leaderProfilePic = $leaderData['profile_picture'] ?? null;
$leaderFullName = trim(($leaderData['first_name'] ?? '') . ' ' . ($leaderData['last_name'] ?? ''));
if (empty($leaderFullName)) {
    $leaderFullName = 'Church Pastor';
}

$stmtSite = $pdo->prepare("SELECT church_name FROM church_sites WHERE church_leader_id = ?");
$stmtSite->execute([$_SESSION['user_id']]);
$mySiteName = $stmtSite->fetchColumn() ?: 'Local Church';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Church Leader Portal'; ?> – DivineShield</title>
  <link rel="icon" type="image/png" href="../../assets/images/mainpi-logo.png" />
  <link rel="stylesheet" href="../../assets/css/style.css?v=15" />
  <link rel="stylesheet" href="../../assets/css/filtering_global.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    /* Dark Theme styling for SweetAlert2 to match DivineShield */
    .swal2-popup {
      background: #0f172a !important;
      color: #f8fafc !important;
      border: 1px solid rgba(255, 255, 255, 0.08) !important;
      border-radius: 16px !important;
    }
    .swal2-title {
      color: #ffffff !important;
      font-family: 'Poppins', sans-serif !important;
    }
    .swal2-html-container {
      color: #94a3b8 !important;
      font-family: 'Inter', sans-serif !important;
    }
    .swal2-confirm {
      background-color: #2563eb !important; /* blue-600 */
      color: #ffffff !important;
      border-radius: 8px !important;
    }
    .swal2-cancel {
      background-color: transparent !important;
      color: #94a3b8 !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
      border-radius: 8px !important;
    }
    .swal2-deny {
      border-radius: 8px !important;
    }
  </style>
</head>
<body>
  <div class="admin-layout">
<!-- sidebar navigation -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
<!-- top navigation bar -->
      <header class="admin-topbar">
        <div class="topbar-title">
          <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Church Site Leader Panel'; ?>
        </div>
        
        <div class="topbar-user">
          <div class="user-badge-group">
            <div class="user-badge-name"><?php echo htmlspecialchars($leaderFullName); ?></div>
            <div class="user-badge-role"><?php echo htmlspecialchars($mySiteName); ?></div>
          </div>
          <?php if (!empty($leaderProfilePic) && file_exists('../../' . $leaderProfilePic)): ?>
            <img src="../../<?php echo htmlspecialchars($leaderProfilePic); ?>" alt="Profile" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.15);" />
          <?php else: ?>
            <div class="logo-mark small" style="background:linear-gradient(135deg, var(--blue-500), var(--blue-700)); color:var(--white);"><i class="fas fa-church"></i></div>
          <?php endif; ?>
        </div>
      </header>

      <div class="admin-content">
