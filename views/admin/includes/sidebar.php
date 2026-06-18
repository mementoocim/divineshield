<?php
/**
 * admin sidebar navigation
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- sidebar navigation -->
<aside class="admin-sidebar">
  <div class="sidebar-header">
    <a href="dashboard.php" class="nav-brand" style="pointer-events: none;">
      <div class="logo-mark small img-wrap" style="background: transparent; box-shadow: none; border: none; overflow: visible;">
        <img src="../../assets/images/DivineShieldLogo.png" alt="DivineShield Logo" style="height: 32px; width: 32px; object-fit: contain;">
      </div>
      <div class="brand-text">
        <span class="brand-main">DivineShield</span>
        <span class="brand-sub">Admin Portal</span>
      </div>
    </a>
  </div>

  <nav class="sidebar-menu" id="adminSidebarMenu">
<!-- core -->
    <div class="sidebar-section-label" style="margin-top:0;">Core</div>
    <a href="dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
      <i class="fas fa-chart-pie"></i>
      <span>Dashboard</span>
    </a>
    <a href="analytics.php" class="sidebar-link <?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>">
      <i class="fas fa-chart-line"></i>
      <span>Analytics</span>
    </a>
<!-- administration -->
    <div class="sidebar-section-label">Administration</div>
    <a href="church_sites.php" class="sidebar-link <?php echo $currentPage === 'church_sites.php' ? 'active' : ''; ?>">
      <i class="fas fa-church"></i>
      <span>Church Sites</span>
    </a>
    <a href="staff.php" class="sidebar-link <?php echo $currentPage === 'staff.php' ? 'active' : ''; ?>">
      <i class="fas fa-user-gear"></i>
      <span>Staff / Encoders</span>
    </a>
<!-- beneficiaries & programs -->
    <div class="sidebar-section-label">Program & Registry</div>
    <a href="children_registry.php" class="sidebar-link <?php echo $currentPage === 'children_registry.php' ? 'active' : ''; ?>">
      <i class="fas fa-child"></i>
      <span>Children Registry</span>
    </a>
    <a href="nutritional_monitoring.php" class="sidebar-link <?php echo $currentPage === 'nutritional_monitoring.php' ? 'active' : ''; ?>">
      <i class="fas fa-heart-pulse"></i>
      <span>Nutritional Monitoring</span>
    </a>
    <a href="feeding_programs.php" class="sidebar-link <?php echo $currentPage === 'feeding_programs.php' ? 'active' : ''; ?>">
      <i class="fas fa-utensils"></i>
      <span>Feeding Programs</span>
    </a>
<!-- tools & system -->
    <div class="sidebar-section-label">Tools & Security</div>
    <a href="reports.php" class="sidebar-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
      <i class="fas fa-file-invoice"></i>
      <span>Reports</span>
    </a>
    <a href="audit_logs.php" class="sidebar-link <?php echo $currentPage === 'audit_logs.php' ? 'active' : ''; ?>">
      <i class="fas fa-scroll"></i>
      <span>Audit Logs</span>
    </a>
    <a href="qr_attendance.php" class="sidebar-link <?php echo $currentPage === 'qr_attendance.php' ? 'active' : ''; ?>">
      <i class="fas fa-qrcode"></i>
      <span>QR Generator</span>
    </a>
    <a href="attendance_monitoring.php" class="sidebar-link <?php echo $currentPage === 'attendance_monitoring.php' ? 'active' : ''; ?>">
      <i class="fas fa-calendar-check"></i>
      <span>Attendance Monitor</span>
    </a>
    <a href="settings.php" class="sidebar-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
      <i class="fas fa-sliders"></i>
      <span>Settings</span>
    </a>
    <a href="profile.php" class="sidebar-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
      <i class="fas fa-user-pen"></i>
      <span>Profile Settings</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="sidebar-link logout-btn-trigger" style="color:var(--red-500); border-color:transparent;">
      <i class="fas fa-sign-out-alt"></i>
      <span>Sign Out</span>
    </a>
  </div>
</aside>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebarMenu = document.getElementById("adminSidebarMenu");
    if (sidebarMenu) {
        // put scroll back
        const scrollPos = sessionStorage.getItem("admin_sidebar_scroll");
        if (scrollPos) {
            sidebarMenu.scrollTop = parseInt(scrollPos, 10);
        }

        // save scroll offset
        const links = sidebarMenu.querySelectorAll(".sidebar-link");
        links.forEach(link => {
            link.addEventListener("click", function() {
                sessionStorage.setItem("admin_sidebar_scroll", sidebarMenu.scrollTop);
            });
        });
    }

    // sweetalert on logout click
    const logoutBtn = document.querySelector('.logout-btn-trigger');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be signed out of your administrator session.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, sign out',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutBtn.getAttribute('href');
                }
            });
        });
    }
});
</script>
