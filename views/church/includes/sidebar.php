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

  <nav class="sidebar-menu">
    <button onclick="switchTab('dashboard')" class="sidebar-link active" id="menu-dashboard">
      <i class="fas fa-chart-pie"></i>
      <span>Dashboard</span>
    </button>
    <button onclick="switchTab('submit')" class="sidebar-link" id="menu-submit">
      <i class="fas fa-child-reaching"></i>
      <span>Submit Child</span>
    </button>
    <button onclick="switchTab('site')" class="sidebar-link" id="menu-site">
      <i class="fas fa-church"></i>
      <span>Church Site</span>
    </button>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="sidebar-link" style="color:var(--red-500); border-color:transparent;">
      <i class="fas fa-sign-out-alt"></i>
      <span>Sign Out</span>
    </a>
  </div>
</aside>
