<?php
/**
 * Shared Church Leader Sidebar Navigation Template - Brand New Premium Glassmorphic Redesign
 */
?>
<style>
/* BRAND NEW PREMIUM GLASS SIDEBAR FOR CHURCH LEADER */
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
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
  box-shadow: 0 0 16px rgba(245, 158, 11, 0.4) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.brand-main {
  font-family: 'Poppins', sans-serif;
  font-weight: 800;
  letter-spacing: -0.02em;
  background: linear-gradient(120deg, #ffffff 30%, #fde68a 90%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.brand-sub {
  color: #fbbf24 !important;
  font-weight: 700 !important;
  letter-spacing: 0.12em !important;
  text-transform: uppercase;
}

#churchSidebarMenu {
  padding: 20px 14px !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important;
}

#churchSidebarMenu .sidebar-section-label {
  font-size: 0.65rem !important;
  font-weight: 800 !important;
  color: #fbbf24 !important;
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
#churchSidebarMenu .sidebar-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, rgba(245, 158, 11, 0.15), transparent);
}

#churchSidebarMenu .sidebar-link {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 12px 18px !important;
  border-radius: 12px !important;
  color: #94a3b8 !important;
  font-weight: 500 !important;
  font-size: 0.86rem !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  border: 1px solid transparent !important;
  position: relative;
  overflow: hidden;
  background: transparent;
}

#churchSidebarMenu .sidebar-link i {
  width: 18px;
  font-size: 0.95rem;
  color: #64748b;
  transition: all 0.25s ease;
}

/* Hover State */
#churchSidebarMenu .sidebar-link:hover {
  color: #f1f5f9 !important;
  background: rgba(255, 255, 255, 0.03) !important;
  border-color: rgba(255, 255, 255, 0.05) !important;
  transform: translateX(4px);
}
#churchSidebarMenu .sidebar-link:hover i {
  color: #fbbf24;
  transform: scale(1.1);
}

/* Active State */
#churchSidebarMenu .sidebar-link.active {
  color: #ffffff !important;
  background: linear-gradient(90deg, rgba(245, 158, 11, 0.12) 0%, rgba(245, 158, 11, 0.02) 100%) !important;
  border-color: rgba(245, 158, 11, 0.25) !important;
  font-weight: 600 !important;
  box-shadow: inset 4px 0 0 #f59e0b;
}
#churchSidebarMenu .sidebar-link.active i {
  color: #fbbf24 !important;
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
    <a href="logout.php" class="sidebar-link logout-btn-trigger" style="color:var(--red-500); border-color:transparent;">
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

    // Logout sweet alert confirmation
    const logoutBtn = document.querySelector('.logout-btn-trigger');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be signed out of your leader session.",
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