<?php
// check auth and role must be handled in the main page before including this.
// get profile picture
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
  <link rel="icon" type="image/png" href="../../assets/images/mainpi-logo.png" />
  <link rel="stylesheet" href="../../assets/css/style.css?v=16" />
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
  <!-- Backdrop overlay for mobile sidebar -->
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <div class="admin-layout">
<!-- sidebar navigation -->
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
<!-- top navigation bar -->
      <header class="admin-topbar">
        <div style="display:flex; align-items:center; flex:1; min-width:0;">
          <!-- Hamburger button (visible on mobile only) -->
          <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
            <i class="fas fa-bars"></i>
          </button>
          <div class="topbar-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Staff Dashboard'; ?>
          </div>
        </div>

        <div class="topbar-user">
          <div class="user-badge-group">
            <div class="user-badge-name"><?php echo htmlspecialchars($staffFullName); ?></div>
            <div class="user-badge-role">Staff / Encoder</div>
          </div>
          <?php if (!empty($staffProfilePic) && file_exists('../../' . $staffProfilePic)): ?>
            <img src="../../<?php echo htmlspecialchars($staffProfilePic); ?>" alt="Profile" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.15); flex-shrink:0;" />
          <?php else: ?>
            <div class="logo-mark small" style="background:linear-gradient(135deg, var(--blue-400), var(--blue-500)); color:var(--white); flex-shrink:0;"><i class="fas fa-user"></i></div>
          <?php endif; ?>
        </div>
      </header>

      <div class="admin-content">

<!-- ── Hamburger / Drawer JS ───────────────────────────────────────── -->
<script>
(function() {
  const btn      = document.getElementById('hamburgerBtn');
  const backdrop = document.getElementById('sidebarBackdrop');
  const sidebar  = document.querySelector('.admin-sidebar');

  function openSidebar() {
    sidebar.classList.add('sidebar-open');
    backdrop.classList.add('active');
    document.body.classList.add('sidebar-is-open');
    btn.innerHTML = '<i class="fas fa-xmark"></i>';
  }

  function closeSidebar() {
    sidebar.classList.remove('sidebar-open');
    backdrop.classList.remove('active');
    document.body.classList.remove('sidebar-is-open');
    btn.innerHTML = '<i class="fas fa-bars"></i>';
  }

  btn.addEventListener('click', function() {
    sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
  });

  // close when tapping outside
  backdrop.addEventListener('click', closeSidebar);

  // close menu on link click
  document.querySelectorAll('.admin-sidebar .sidebar-link').forEach(function(link) {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) closeSidebar();
    });
  });

  // close drawer on desktop resize
  window.addEventListener('resize', function() {
    if (window.innerWidth > 768) closeSidebar();
  });
})();
</script>
