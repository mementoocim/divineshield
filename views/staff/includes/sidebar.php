<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" id="staffSidebar">
  <div class="sidebar-header">
    <a href="dashboard.php" class="nav-brand" style="pointer-events: none;">
      <div class="logo-mark small img-wrap" style="background: transparent; box-shadow: none; border: none; overflow: visible;">
        <img src="../../assets/images/DivineShieldLogo.png" alt="DivineShield Logo" style="height: 50px; width: 50px; object-fit: contain;">
      </div>
      <div class="brand-text">
        <span class="brand-main">DivineShield</span>
        <span class="brand-sub">Staff Portal</span>
      </div>
    </a>
  </div>

  <nav class="sidebar-menu" id="staffSidebarMenu">
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
    <a href="attendance_history.php" class="sidebar-link <?php echo $currentPage === 'attendance_history.php' ? 'active' : ''; ?>">
      <i class="fas fa-calendar-check"></i>
      <span>My Attendance</span>
    </a>


    <!-- SYSTEM -->
    <div class="sidebar-section-label">Account</div>
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
    const sidebarMenu = document.getElementById("staffSidebarMenu");
    if (sidebarMenu) {
        // Restore scroll position
        const scrollPos = sessionStorage.getItem("staff_sidebar_scroll");
        if (scrollPos) {
            sidebarMenu.scrollTop = parseInt(scrollPos, 10);
        }

        // Save scroll position when a link is clicked
        const links = sidebarMenu.querySelectorAll(".sidebar-link");
        links.forEach(link => {
            link.addEventListener("click", function() {
                sessionStorage.setItem("staff_sidebar_scroll", sidebarMenu.scrollTop);
            });
        });
    }

    // Logout sweet alert confirmation
    const logoutBtn = document.querySelector('.logout-btn-trigger');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be signed out of your staff session.",
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

