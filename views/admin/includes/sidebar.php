<?php
/**
 * Shared Admin Sidebar Navigation Template - Brand New Premium Glassmorphic Redesign
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
/* BRAND NEW PREMIUM GLASS SIDEBAR FOR ADMIN */
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
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
  box-shadow: 0 0 16px rgba(59, 130, 246, 0.4) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.brand-main {
  font-family: 'Poppins', sans-serif;
  font-weight: 800;
  letter-spacing: -0.02em;
  background: linear-gradient(120deg, #ffffff 30%, #93c5fd 90%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.brand-sub {
  color: #60a5fa !important;
  font-weight: 700 !important;
  letter-spacing: 0.12em !important;
  text-transform: uppercase;
}

#adminSidebarMenu {
  padding: 20px 14px !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 6px !important;
}

#adminSidebarMenu .sidebar-section-label {
  font-size: 0.65rem !important;
  font-weight: 800 !important;
  color: #3b82f6 !important;
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
#adminSidebarMenu .sidebar-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, rgba(59, 130, 246, 0.15), transparent);
}

#adminSidebarMenu .sidebar-link {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 11px 16px !important;
  border-radius: 12px !important;
  color: #94a3b8 !important;
  font-weight: 500 !important;
  font-size: 0.85rem !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  border: 1px solid transparent !important;
  position: relative;
  overflow: hidden;
  background: transparent;
}

#adminSidebarMenu .sidebar-link i {
  width: 18px;
  font-size: 0.95rem;
  color: #64748b;
  transition: all 0.25s ease;
}

/* Hover State */
#adminSidebarMenu .sidebar-link:hover {
  color: #f1f5f9 !important;
  background: rgba(255, 255, 255, 0.03) !important;
  border-color: rgba(255, 255, 255, 0.05) !important;
  transform: translateX(4px);
}
#adminSidebarMenu .sidebar-link:hover i {
  color: #3b82f6;
  transform: scale(1.1);
}

/* Active State */
#adminSidebarMenu .sidebar-link.active {
  color: #ffffff !important;
  background: linear-gradient(90deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.02) 100%) !important;
  border-color: rgba(59, 130, 246, 0.25) !important;
  font-weight: 600 !important;
  box-shadow: inset 4px 0 0 #3b82f6;
}
#adminSidebarMenu .sidebar-link.active i {
  color: #3b82f6 !important;
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

  <nav class="sidebar-menu" id="adminSidebarMenu">
    <!-- CORE -->
    <div class="sidebar-section-label" style="margin-top:0;">Core</div>
    <a href="dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
      <i class="fas fa-chart-pie"></i>
      <span>Dashboard</span>
    </a>
    <a href="analytics.php" class="sidebar-link <?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>">
      <i class="fas fa-chart-line"></i>
      <span>Analytics</span>
    </a>

    <!-- ADMINISTRATION -->
    <div class="sidebar-section-label">Administration</div>
    <a href="church_sites.php" class="sidebar-link <?php echo $currentPage === 'church_sites.php' ? 'active' : ''; ?>">
      <i class="fas fa-church"></i>
      <span>Church Sites</span>
    </a>
    <a href="staff.php" class="sidebar-link <?php echo $currentPage === 'staff.php' ? 'active' : ''; ?>">
      <i class="fas fa-user-gear"></i>
      <span>Staff / Encoders</span>
    </a>

    <!-- BENEFICIARIES & PROGRAMS -->
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

    <!-- TOOLS & SYSTEM -->
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
    <a href="security.php" class="sidebar-link <?php echo $currentPage === 'security.php' ? 'active' : ''; ?>">
      <i class="fas fa-shield-halved"></i>
      <span>Security</span>
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
        // Restore scroll position
        const scrollPos = sessionStorage.getItem("admin_sidebar_scroll");
        if (scrollPos) {
            sidebarMenu.scrollTop = parseInt(scrollPos, 10);
        }

        // Save scroll position when a link is clicked
        const links = sidebarMenu.querySelectorAll(".sidebar-link");
        links.forEach(link => {
            link.addEventListener("click", function() {
                sessionStorage.setItem("admin_sidebar_scroll", sidebarMenu.scrollTop);
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
