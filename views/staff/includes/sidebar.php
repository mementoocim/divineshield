<?php
/**
 * Shared Staff Sidebar Navigation Template - Brand New Premium Glassmorphic Redesign
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
/* BRAND NEW PREMIUM GLASS SIDEBAR FOR STAFF */
.admin-sidebar {
  background: rgba(10, 15, 30, 0.85) !important;
  backdrop-filter: blur(28px) !important;
  -webkit-backdrop-filter: blur(28px) !important;
  border-right: 1px solid rgba(255, 255, 255, 0.06) !important;
  box-shadow: 10px 0 30px rgba(0, 0, 0, 0.3);
}

.sidebar-header {
  padding: 24px 24px 20px !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
}

.logo-mark {
  background: linear-gradient(135deg, #10b981 0%, #047857 100%) !important;
  box-shadow: 0 0 16px rgba(16, 185, 129, 0.4) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.brand-main {
  font-family: 'Poppins', sans-serif;
  font-weight: 800;
  letter-spacing: -0.02em;
  background: linear-gradient(120deg, #ffffff 30%, #a7f3d0 90%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.brand-sub {
  color: #34d399 !important;
  font-weight: 700 !important;
  letter-spacing: 0.12em !important;
  text-transform: uppercase;
}

#staffSidebarMenu {
  padding: 20px 14px !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 12px !important;
}

#staffSidebarMenu .sidebar-section-label {
  font-size: 0.65rem !important;
  font-weight: 800 !important;
  color: #10b981 !important;
  letter-spacing: 0.12em !important;
  text-transform: uppercase;
  margin-top: 18px !important;
  margin-bottom: 6px !important;
  padding-left: 12px !important;
  opacity: 0.8;
  display: flex;
  align-items: center;
  gap: 8px;
}
#staffSidebarMenu .sidebar-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, rgba(16, 185, 129, 0.15), transparent);
}

#staffSidebarMenu .sidebar-link {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 14px 18px !important;
  border-radius: 12px !important;
  color: #94a3b8 !important;
  font-weight: 500 !important;
  font-size: 0.88rem !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  border: 1px solid transparent !important;
  position: relative;
  overflow: hidden;
  background: transparent;
}

#staffSidebarMenu .sidebar-link i {
  width: 18px;
  font-size: 0.95rem;
  color: #64748b;
  transition: all 0.25s ease;
}

/* Hover State */
#staffSidebarMenu .sidebar-link:hover {
  color: #f1f5f9 !important;
  background: rgba(255, 255, 255, 0.03) !important;
  border-color: rgba(255, 255, 255, 0.05) !important;
  transform: translateX(4px);
}
#staffSidebarMenu .sidebar-link:hover i {
  color: #10b981;
  transform: scale(1.1);
}

/* Active State */
#staffSidebarMenu .sidebar-link.active {
  color: #ffffff !important;
  background: linear-gradient(90deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 185, 129, 0.02) 100%) !important;
  border-color: rgba(16, 185, 129, 0.25) !important;
  font-weight: 600 !important;
  box-shadow: inset 4px 0 0 #10b981;
}
#staffSidebarMenu .sidebar-link.active i {
  color: #10b981 !important;
}

/* Footer & Logout */
.sidebar-footer {
  padding: 18px 20px !important;
  border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
}

.sidebar-footer .sidebar-link {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 12px 16px !important;
  border-radius: 12px !important;
  font-weight: 600 !important;
  font-size: 0.85rem !important;
  transition: all 0.25s ease !important;
  border: 1px solid transparent !important;
}
.sidebar-footer .sidebar-link:hover {
  background: rgba(239, 68, 68, 0.08) !important;
  border-color: rgba(239, 68, 68, 0.15) !important;
  transform: translateX(4px);
}
</style>

<!-- SIDEBAR NAVIGATION -->
<aside class="admin-sidebar" id="staffSidebar">
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
