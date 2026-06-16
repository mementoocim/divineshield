<?php
/**
 * Shared Church Leader Sidebar Navigation Template
 */
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
        <span class="brand-sub">Leader Portal</span>
      </div>
    </a>
  </div>

  <?php
  // Active link state based on current page
  $current = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
  $isDashboard = ($current === 'dashboard.php');
  $isSubmit = ($current === 'submit-child.php');
  $isSite = ($current === 'church-sites.php');
  ?>

  <nav class="sidebar-menu">
    <a href="dashboard.php" class="sidebar-link <?php echo $isDashboard ? 'active' : ''; ?>" id="menu-dashboard">
      <i class="fas fa-chart-pie"></i>
      <span>Dashboard</span>
    </a>
    <a href="submit-child.php" class="sidebar-link <?php echo $isSubmit ? 'active' : ''; ?>" id="menu-submit">
      <i class="fas fa-child-reaching"></i>
      <span>Submit Child</span>
    </a>
    <a href="church-sites.php" class="sidebar-link <?php echo $isSite ? 'active' : ''; ?>" id="menu-site">
      <i class="fas fa-church"></i>
      <span>Church Site</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="sidebar-link" style="color:var(--red-500); border-color:transparent;">
      <i class="fas fa-sign-out-alt"></i>
      <span>Sign Out</span>
    </a>
  </div>
</aside>