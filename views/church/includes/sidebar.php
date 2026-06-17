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
  $isChildrenRecords = ($current === 'children_records.php');
  $isNutritional = ($current === 'nutritional_monitoring.php');
  $isAttendance = ($current === 'attendance.php');
  $isSite = ($current === 'church-sites.php');
  ?>

  <nav class="sidebar-menu" id="churchSidebarMenu">
    <!-- CORE -->
    <div class="sidebar-section-label" style="margin-top:0;">Core</div>
    <a href="dashboard.php" class="sidebar-link <?php echo $isDashboard ? 'active' : ''; ?>" id="menu-dashboard">
      <i class="fas fa-chart-pie"></i>
      <span>Dashboard</span>
    </a>

    <!-- PROGRAM -->
    <div class="sidebar-section-label">Program</div>
    <a href="submit-child.php" class="sidebar-link <?php echo $isSubmit ? 'active' : ''; ?>" id="menu-submit">
      <i class="fas fa-child-reaching"></i>
      <span>Submit Child</span>
    </a>
    <a href="children_records.php" class="sidebar-link <?php echo $isChildrenRecords ? 'active' : ''; ?>" id="menu-children">
      <i class="fas fa-child"></i>
      <span>Children Records</span>
    </a>
    <a href="nutritional_monitoring.php" class="sidebar-link <?php echo $isNutritional ? 'active' : ''; ?>" id="menu-nutritional">
      <i class="fas fa-heart-pulse"></i>
      <span>Nutritional Monitoring</span>
    </a>
    <a href="attendance.php" class="sidebar-link <?php echo $isAttendance ? 'active' : ''; ?>" id="menu-attendance">
      <i class="fas fa-utensils"></i>
      <span>Feeding Programs</span>
    </a>

    <!-- SITE -->
    <div class="sidebar-section-label">Site</div>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebarMenu = document.getElementById("churchSidebarMenu");
    if (sidebarMenu) {
        // Restore scroll position
        const scrollPos = sessionStorage.getItem("church_sidebar_scroll");
        if (scrollPos) {
            sidebarMenu.scrollTop = parseInt(scrollPos, 10);
        }

        // Save scroll position when a link is clicked
        const links = sidebarMenu.querySelectorAll(".sidebar-link");
        links.forEach(link => {
            link.addEventListener("click", function() {
                sessionStorage.setItem("church_sidebar_scroll", sidebarMenu.scrollTop);
            });
        });
    }
});
</script>