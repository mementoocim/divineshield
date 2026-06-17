/**
 * DivineShield - Landing Page Interactive Script (main.js)
 */
document.addEventListener("DOMContentLoaded", function() {

  // 1. SCROLL DETECTOR FOR HEADER STICKY GLASSMORPHISM
  const navbar = document.getElementById("navbar");
  window.addEventListener("scroll", function() {
    if (window.scrollY > 20) {
      navbar.classList.add("scrolled");
    } else {
      navbar.classList.remove("scrolled");
    }
  });

  // 2. MOBILE MENU HAMBURGER TOGGLE
  const hamburger = document.getElementById("hamburger");
  const navLinks = document.querySelector(".nav-links");

  if (hamburger && navLinks) {
    hamburger.addEventListener("click", function() {
      const isExpanded = navLinks.style.display === "flex";
      navLinks.style.display = isExpanded ? "none" : "flex";
      hamburger.querySelector("i").className = isExpanded ? "fas fa-bars" : "fas fa-xmark";
    });
  }

  // 3. INTERACTIVE PORTAL TOUR SIMULATOR
  const tabBtns = document.querySelectorAll(".tour-tab-btn");
  const panes = document.querySelectorAll(".tour-pane");
  
  // Simulated components to update in the mockup console
  const mockPortalTitle = document.getElementById("mock-portal-title");
  const mockPortalRole = document.getElementById("mock-portal-role");
  const mockStatsGrid = document.getElementById("mock-stats-grid");
  const mockTableTitle = document.getElementById("mock-table-title");
  const mockRowsContainer = document.getElementById("mock-rows-container");

  // Simulation data mappings
  const simulationData = {
    admin: {
      title: "Admin_Dashboard",
      role: "ADMIN",
      roleBg: "rgba(59, 130, 246, 0.1)",
      roleColor: "#60a5fa",
      stats: [
        { num: "200+", label: "Sites" },
        { num: "1,842", label: "Children" },
        { num: "15", label: "Encoders" }
      ],
      tableTitle: "System Activity Log",
      rows: [
        { desc: "Site Activation: Grace Born-Again", badge: "Approved", class: "mb-success" },
        { desc: "Staff Check-in: @encoder1", badge: "Checked In", class: "mb-success" },
        { desc: "Audit: Exported reports.csv", badge: "Logged", class: "mb-warning" }
      ]
    },
    staff: {
      title: "Staff_Review_Portal",
      role: "STAFF_ENCODER",
      roleBg: "rgba(245, 158, 11, 0.1)",
      roleColor: "#fbbf24",
      stats: [
        { num: "14", label: "Pending Reviews" },
        { num: "182", label: "Assessments Done" },
        { num: "99.1%", label: "Accuracy" }
      ],
      tableTitle: "Verification Queue",
      rows: [
        { desc: "Review: Submission #12 (Juan Dela Cruz)", badge: "Pending", class: "mb-warning" },
        { desc: "Action: Disqualified candidate #4", badge: "Rejected", class: "mb-danger" },
        { desc: "Check-in: Logged daily shift start", badge: "Logged", class: "mb-success" }
      ]
    },
    leader: {
      title: "Leader_Gateway",
      role: "CHURCH_LEADER",
      roleBg: "rgba(16, 185, 129, 0.1)",
      roleColor: "#34d399",
      stats: [
        { num: "38", label: "Enrolled Kids" },
        { num: "2", label: "Pending" },
        { num: "120d", label: "Active Cycle" }
      ],
      tableTitle: "My Site Beneficiaries",
      rows: [
        { desc: "Register: Submitted child credentials", badge: "Submitted", class: "mb-success" },
        { desc: "RFID scan: Verified program feeding", badge: "Success", class: "mb-success" },
        { desc: "Growth: Recorded monthly height/weight", badge: "Updated", class: "mb-success" }
      ]
    }
  };

  tabBtns.forEach(btn => {
    btn.addEventListener("click", function() {
      // Toggle button active classes
      tabBtns.forEach(b => b.classList.remove("active"));
      this.classList.add("active");

      const portal = this.getAttribute("data-portal");

      // Toggle text content panes active class
      panes.forEach(pane => {
        pane.classList.remove("active");
        if (pane.classList.contains("tour-pane-" + portal)) {
          pane.classList.add("active");
        }
      });

      // Update the simulator mockup visual
      if (simulationData[portal]) {
        const data = simulationData[portal];
        
        // Title & Role
        mockPortalTitle.textContent = data.title;
        mockPortalRole.textContent = data.role;
        mockPortalRole.style.background = data.roleBg;
        mockPortalRole.style.color = data.roleColor;

        // Stats grid
        mockStatsGrid.innerHTML = "";
        data.stats.forEach(st => {
          mockStatsGrid.innerHTML += `
            <div class="mock-stat-tile">
              <div class="mock-tile-num">${st.num}</div>
              <div class="mock-tile-label">${st.label}</div>
            </div>
          `;
        });

        // Table
        mockTableTitle.textContent = data.tableTitle;
        mockRowsContainer.innerHTML = "";
        data.rows.forEach(rw => {
          mockRowsContainer.innerHTML += `
            <div class="mock-table-row">
              <span>${rw.desc}</span>
              <span class="mock-badge-status ${rw.class}">${rw.badge}</span>
            </div>
          `;
        });
      }
    });
  });

});
