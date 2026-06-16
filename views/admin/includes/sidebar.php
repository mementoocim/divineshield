<?php
/**
 * Shared Admin Sidebar Navigation Template
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- SIDEBAR NAVIGATION -->
<aside class="admin-sidebar">
  <div class="sidebar-header">
    <a href="dashboard.php" class="nav-brand" style="pointer-events: none;">
      <div class="logo-mark small">
        <i class="fas fa-shield-halved"></i>
      </div>
      <div class="brand-text">
        <span class="brand-main">DivineShield</span>
        <span class="brand-sub">Admin Portal</span>
      </div>
    </a>
  </div>

  <nav class="sidebar-menu">
    <a href="dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
      <i class="fas fa-chart-pie"></i>
      <span>Dashboard</span>
    </a>
    <a href="church_sites.php" class="sidebar-link <?php echo $currentPage === 'church_sites.php' ? 'active' : ''; ?>">
      <i class="fas fa-church"></i>
      <span>Church Sites</span>
    </a>
    <a href="staff.php" class="sidebar-link <?php echo $currentPage === 'staff.php' ? 'active' : ''; ?>">
      <i class="fas fa-user-gear"></i>
      <span>Staff / Encoders</span>
    </a>
    <a href="audit_logs.php" class="sidebar-link <?php echo $currentPage === 'audit_logs.php' ? 'active' : ''; ?>">
      <i class="fas fa-scroll"></i>
      <span>Audit Logs</span>
    </a>
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
