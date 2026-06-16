<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
  <div class="sidebar-header">
    <a href="dashboard.php" class="nav-brand" style="pointer-events: none;">
      <div class="logo-mark small">
        <i class="fas fa-shield-halved"></i>
      </div>
      <div class="brand-text">
        <span class="brand-main">DivineShield</span>
        <span class="brand-sub">Staff Portal</span>
      </div>
    </a>
  </div>

  <nav class="sidebar-menu">
    <!-- CORE -->
    <div class="sidebar-section-label" style="margin-top:0;">Core</div>
    <a href="dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
      <i class="fas fa-chart-pie"></i>
      <span>Dashboard</span>
    </a>
    <a href="submissions.php" class="sidebar-link <?php echo $currentPage === 'submissions.php' ? 'active' : ''; ?>">
      <i class="fas fa-inbox"></i>
      <span>Submission Review</span>
    </a>

    <!-- BENEFICIARIES -->
    <div class="sidebar-section-label">Beneficiaries</div>
    <a href="children_records.php" class="sidebar-link <?php echo $currentPage === 'children_records.php' ? 'active' : ''; ?>">
      <i class="fas fa-child"></i>
      <span>Children Records</span>
    </a>
    <a href="nutritional_monitoring.php" class="sidebar-link <?php echo $currentPage === 'nutritional_monitoring.php' ? 'active' : ''; ?>">
      <i class="fas fa-heart-pulse"></i>
      <span>Nutritional Monitoring</span>
    </a>
    <a href="attendance.php" class="sidebar-link <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
      <i class="fas fa-clipboard-user"></i>
      <span>Attendance & RFID</span>
    </a>

    <!-- SYSTEM -->
    <div class="sidebar-section-label">Account</div>
    <a href="profile.php" class="sidebar-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
      <i class="fas fa-user-pen"></i>
      <span>Profile Settings</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="sidebar-link" style="color:var(--red-500); border-color:transparent;">
      <i class="fas fa-sign-out-alt"></i>
      <span>Sign Out</span>
    </a>
  </div>
</aside>
