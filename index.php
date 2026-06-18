<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/images/mainpi-logo.png" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MAINPI – Minister's Association Integrated Nutrition Program Inc.</title>
  <link rel="stylesheet" href="assets/css/style.css?v=4" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
</head>
<body>

  <!-- navbar -->
  <nav class="navbar" id="navbar">
    <div class="nav-container">
      <a href="#home" class="nav-brand">
        <div class="logo-mark img-wrap" style="background: transparent; box-shadow: none; border: none; overflow: visible;">
          <img src="assets/images/DivineShieldLogo.png" alt="DivineShield Logo" style="height: 38px; width: 38px; object-fit: contain;">
        </div>
        <div class="brand-text">
          <span class="brand-main">DivineShield</span>
          <span class="brand-sub">MAINPI Cloud System</span>
        </div>
      </a>

      <ul class="nav-menu" id="navMenu">
        <li><a href="#home"     class="nav-link active">Home</a></li>
        <li><a href="#about"    class="nav-link">About</a></li>
        <li><a href="#programs" class="nav-link">Programs</a></li>
        <li><a href="login.php" class="nav-btn">Login <i class="fas fa-arrow-right-to-bracket"></i></a></li>
      </ul>

      <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <!-- hero -->
  <section class="hero" id="home">
    <div class="hero-bg-grid"></div>
    <div class="hero-container">
      <div class="hero-logo-block">
        <img src="assets/images/mainpi-logo.png?v=3" alt="MAINPI Logo" class="hero-logo" />
      </div>
      <h1 class="hero-title">
        MAINPI – <span class="gradient-text">Minister's Association<br>Integrated Nutrition<br>Program Inc.</span>
      </h1>
      <p class="hero-desc">
        Feeding programs in the Philippines play a vital role in addressing child malnutrition
        by providing essential nutritional support to children in underserved communities.
        These initiatives promote health, improve development, and contribute to building
        stronger, more resilient communities through coordinated and compassionate action.
      </p>
      <div class="hero-actions">
        <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login to System</a>
        <a href="#overview" class="btn btn-outline" onclick="document.getElementById('overview').scrollIntoView({behavior:'smooth'});return false;"><i class="fas fa-desktop"></i> System Overview</a>
      </div>
      <div class="hero-stats">
        <div class="stat-card">
          <span class="stat-num">200+</span>
          <span class="stat-label">Church Leaders</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-card">
          <span class="stat-num">15</span>
          <span class="stat-label">MAINPI Staff</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-card">
          <span class="stat-num">1–12</span>
          <span class="stat-label">Age Range (yrs)</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-card">
          <span class="stat-num">100%</span>
          <span class="stat-label">Cloud-Based</span>
        </div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-photos">
        <div class="hero-photo-frame frame-1">
          <img src="assets/images/pict_large.jpg" alt="MAINPI Feeding Program – children eating indoors" />
        </div>
        <div class="hero-photo-frame frame-2">
          <img src="assets/images/philippines_freemeals001.webp" alt="MAINPI Feeding Program – children eating outdoors" />
        </div>
        <div class="hero-photo-badge">
          <i class="fas fa-heart"></i> Serving Children Since 2017
        </div>
      </div>
    </div>
  </section>

  <!-- trust bar -->
  <div class="trust-bar">
    <div class="trust-container">
      <div class="trust-item"><i class="fab fa-google"></i> Google Cloud Platform</div>
      <div class="trust-item"><i class="fas fa-lock"></i> AES-256 Encryption</div>
      <div class="trust-item"><i class="fas fa-user-shield"></i> Role-Based Access Control</div>
      <div class="trust-item"><i class="fas fa-database"></i> Cloud SQL & BigQuery</div>
      <div class="trust-item"><i class="fas fa-chart-pie"></i> Looker Dashboards</div>
      <div class="trust-item"><i class="fas fa-shield-virus"></i> Adaptive Threat Detection</div>
    </div>
  </div>

  <!-- about -->
  <section class="about section" id="about">
    <div class="section-container">
      <div class="about-grid">
        <div class="about-text">
          <div class="section-eyebrow"><i class="fas fa-heart"></i> About MAINPI</div>
          <h2 class="section-title">Minister's Association Integrated Nutrition Program Inc.</h2>
          <p class="section-desc">
            MAINPI is a Filipino nonprofit Association operating nationwide feeding programs
            for malnourished children aged 5–15 years old. Working with over 200 church leaders
            and volunteer coordinators across the Philippines, MAINPI coordinates food assistance
            delivered by Christian and Born-Again pastors in local communities.
          </p>
          <p class="section-desc">
            With a core team of 15 personnel — 12 Encoders and 3 Administrators — and the support
            of partner organizations such as <strong>Feed My Starving Children</strong>,
            MAINPI serves thousands of children in both urban and rural areas.
          </p>
          <div class="mv-cards">
            <div class="mv-card">
              <div class="mv-icon"><i class="fas fa-bullseye"></i></div>
              <div>
                <h4>Mission</h4>
                <p>Restore family well-being of the less fortunate, and vulnerable groups through Spiritual, Moral and Social Service </p>
              </div>
            </div>
            <div class="mv-card">
              <div class="mv-icon"><i class="fas fa-eye"></i></div>
              <div>
                <h4>Vision</h4>
                <p>Spiritual, Moral and Social Restoration throughout the Philippines.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="about-visual">
          <div class="about-img-stack">
            <div class="about-badge-card top-card">
              <i class="fas fa-child"></i>
              <div>
                <strong>Child Beneficiaries</strong>
                <span>Ages 1–12 Years Old</span>
              </div>
            </div>
            <div class="about-img-block">
              <div class="placeholder-img">
                <i class="fas fa-hands-holding-child"></i>
                <span>MAINPI Feeding Program</span>
              </div>
            </div>
            <div class="about-badge-card bottom-card">
              <i class="fas fa-church"></i>
              <div>
                <strong>200+ Church Sites</strong>
                <span>Nationwide Coverage</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- programs -->
  <section class="programs section" id="programs">
    <div class="section-container">
      <div class="section-header center">
        <div class="section-eyebrow"><i class="fas fa-bowl-food"></i> Feeding Programs</div>
        <h2 class="section-title">Active Program Sites Nationwide</h2>
        <p class="section-desc max-700">
          MAINPI's feeding programs operate across hundreds of sites in both urban and rural
          communities throughout the Philippines, managed digitally through DivineShield.
        </p>
      </div>

      <div class="programs-grid">
        <div class="program-card">
          <div class="program-img">
            <div class="program-img-placeholder">
              <i class="fas fa-house-heart"></i>
            </div>
            <div class="program-tag active">Active</div>
          </div>
          <div class="program-body">
            <div class="program-location"><i class="fas fa-location-dot"></i> Metro Manila Region</div>
            <h3>Urban Community Feeding Program</h3>
            <p>Targeted feeding for malnourished children in densely populated urban barangays with limited nutrition access.</p>
            <div class="program-meta">
              <span><i class="fas fa-children"></i> 1,240 beneficiaries</span>
              <span><i class="fas fa-church"></i> 18 church sites</span>
            </div>
          </div>
        </div>

        <div class="program-card">
          <div class="program-img">
            <div class="program-img-placeholder green">
              <i class="fas fa-seedling"></i>
            </div>
            <div class="program-tag active">Active</div>
          </div>
          <div class="program-body">
            <div class="program-location"><i class="fas fa-location-dot"></i> Visayas Region</div>
            <h3>Rural Agricultural Community Program</h3>
            <p>Nutrition assistance for children in farming communities where food insecurity is compounded by seasonal harvests.</p>
            <div class="program-meta">
              <span><i class="fas fa-children"></i> 876 beneficiaries</span>
              <span><i class="fas fa-church"></i> 12 church sites</span>
            </div>
          </div>
        </div>

        <div class="program-card">
          <div class="program-img">
            <div class="program-img-placeholder orange">
              <i class="fas fa-water"></i>
            </div>
            <div class="program-tag ongoing">Ongoing</div>
          </div>
          <div class="program-body">
            <div class="program-location"><i class="fas fa-location-dot"></i> Mindanao Region</div>
            <h3>Coastal Community Relief Program</h3>
            <p>Emergency and sustained nutrition relief for fishing communities with high malnutrition incidence among school-age children.</p>
            <div class="program-meta">
              <span><i class="fas fa-children"></i> 654 beneficiaries</span>
              <span><i class="fas fa-church"></i> 9 church sites</span>
            </div>
          </div>
        </div>
      </div>

      <div class="program-cta">
        <div class="program-cta-inner">
          <div class="program-cta-text">
            <h3>Register a New Feeding Program Site</h3>
            <p>Church leaders can log in to the system and submit new feeding site details directly through the platform.</p>
          </div>
          <a href="login.php" class="btn btn-primary">Register Your Site <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </section>

  <!-- system overview -->
  <section class="section" id="overview" style="background:#f8fafc;">
    <div class="section-container">
      <div class="section-header center">
        <div class="section-eyebrow"><i class="fas fa-desktop"></i> System Overview</div>
        <h2 class="section-title">Three Portals. One Unified System.</h2>
        <p class="section-desc max-700">
          DivineShield provides purpose-built dashboards for every role in the MAINPI organization —
          each with distinct access levels, tools, and responsibilities.
          Full access to any portal requires authentication.
        </p>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-top:8px;">

        <!-- Admin Portal Card -->
        <div style="background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(37,99,235,.08);border:1px solid #e0e7ff;overflow:hidden;">
          <div style="background:linear-gradient(135deg,#1d4ed8,#2563eb);padding:28px 28px 20px;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.06);"></div>
            <div style="position:absolute;bottom:-30px;left:-10px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.04);"></div>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;position:relative;">
              <div style="width:52px;height:52px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;">
                <i class="fas fa-user-tie"></i>
              </div>
              <div>
                <div style="font-family:'Poppins',sans-serif;font-weight:700;font-size:1.1rem;color:#fff;">Admin Dashboard</div>
                <div style="font-size:.75rem;color:rgba(255,255,255,.7);">Full system control</div>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;position:relative;">
              <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:10px 12px;text-align:center;">
                <div style="font-size:1.3rem;font-weight:800;color:#fff;">4,821</div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.7);">Assessments</div>
              </div>
              <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:10px 12px;text-align:center;">
                <div style="font-size:1.3rem;font-weight:800;color:#fff;">6</div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.7);">Active Programs</div>
              </div>
              <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:10px 12px;text-align:center;">
                <div style="font-size:1.3rem;font-weight:800;color:#fff;">200+</div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.7);">Church Sites</div>
              </div>
              <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:10px 12px;text-align:center;">
                <div style="font-size:1.3rem;font-weight:800;color:#fff;">15</div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.7);">Staff Members</div>
              </div>
            </div>
          </div>
          <div style="padding:22px 28px;">
            <ul style="list-style:none;padding:0;margin:0 0 20px;display:flex;flex-direction:column;gap:9px;">
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#2563eb;flex-shrink:0;"></i> Analytics &amp; performance reports</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#2563eb;flex-shrink:0;"></i> Child beneficiary management</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#2563eb;flex-shrink:0;"></i> RFID staff attendance tracking</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#2563eb;flex-shrink:0;"></i> PDF &amp; CSV report generation</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#2563eb;flex-shrink:0;"></i> User management &amp; audit logs</li>
            </ul>
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <span style="font-size:.75rem;color:#64748b;background:#f1f5f9;padding:5px 12px;border-radius:20px;"><i class="fas fa-lock" style="margin-right:5px;color:#94a3b8;"></i>Requires Admin Login</span>
              <a href="login.php" style="font-size:.82rem;font-weight:600;color:#2563eb;text-decoration:none;">Sign In <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>

        <!-- Staff Portal Card -->
        <div style="background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(245,158,11,.08);border:1px solid #fef3c7;overflow:hidden;">
          <div style="background:linear-gradient(135deg,#d97706,#f59e0b);padding:28px 28px 20px;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.06);"></div>
            <div style="position:absolute;bottom:-30px;left:-10px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.04);"></div>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;position:relative;">
              <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;">
                <i class="fas fa-user-pen"></i>
              </div>
              <div>
                <div style="font-family:'Poppins',sans-serif;font-weight:700;font-size:1.1rem;color:#fff;">Staff / Encoder Portal</div>
                <div style="font-size:.75rem;color:rgba(255,255,255,.8);">Operational data entry</div>
              </div>
            </div>
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:14px 16px;position:relative;">
              <div style="font-size:.72rem;color:rgba(255,255,255,.75);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Today's Attendance Snapshot</div>
              <div style="display:flex;gap:10px;">
                <div style="flex:1;text-align:center;background:rgba(255,255,255,.15);border-radius:8px;padding:8px 6px;">
                  <div style="font-size:1.2rem;font-weight:800;color:#fff;">12</div>
                  <div style="font-size:.62rem;color:rgba(255,255,255,.75);">Present</div>
                </div>
                <div style="flex:1;text-align:center;background:rgba(255,255,255,.15);border-radius:8px;padding:8px 6px;">
                  <div style="font-size:1.2rem;font-weight:800;color:#fff;">2</div>
                  <div style="font-size:.62rem;color:rgba(255,255,255,.75);">Late</div>
                </div>
                <div style="flex:1;text-align:center;background:rgba(255,255,255,.15);border-radius:8px;padding:8px 6px;">
                  <div style="font-size:1.2rem;font-weight:800;color:#fff;">1</div>
                  <div style="font-size:.62rem;color:rgba(255,255,255,.75);">Absent</div>
                </div>
              </div>
            </div>
          </div>
          <div style="padding:22px 28px;">
            <ul style="list-style:none;padding:0;margin:0 0 20px;display:flex;flex-direction:column;gap:9px;">
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#f59e0b;flex-shrink:0;"></i> Validate &amp; approve child submissions</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#f59e0b;flex-shrink:0;"></i> Encode nutritional assessment data</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#f59e0b;flex-shrink:0;"></i> RFID-based attendance logging</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#f59e0b;flex-shrink:0;"></i> Church site coordination</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#f59e0b;flex-shrink:0;"></i> View program schedules &amp; rosters</li>
            </ul>
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <span style="font-size:.75rem;color:#64748b;background:#fffbeb;padding:5px 12px;border-radius:20px;"><i class="fas fa-lock" style="margin-right:5px;color:#94a3b8;"></i>Requires Staff Login</span>
              <a href="login.php" style="font-size:.82rem;font-weight:600;color:#d97706;text-decoration:none;">Sign In <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>

        <!-- Church Leader Portal Card -->
        <div style="background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(20,184,166,.08);border:1px solid #ccfbf1;overflow:hidden;">
          <div style="background:linear-gradient(135deg,#0f766e,#14b8a6);padding:28px 28px 20px;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.06);"></div>
            <div style="position:absolute;bottom:-30px;left:-10px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.04);"></div>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;position:relative;">
              <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;">
                <i class="fas fa-church"></i>
              </div>
              <div>
                <div style="font-family:'Poppins',sans-serif;font-weight:700;font-size:1.1rem;color:#fff;">Church Leader Portal</div>
                <div style="font-size:.75rem;color:rgba(255,255,255,.8);">Submit &amp; track beneficiaries</div>
              </div>
            </div>
            <div style="background:rgba(255,255,255,.12);border-radius:12px;padding:14px 16px;position:relative;">
              <div style="font-size:.72rem;color:rgba(255,255,255,.75);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Submission Status Tracker</div>
              <div style="display:flex;flex-direction:column;gap:6px;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:8px;height:8px;border-radius:50%;background:#86efac;flex-shrink:0;"></div>
                  <div style="font-size:.78rem;color:rgba(255,255,255,.9);flex:1;">38 Approved</div>
                  <div style="font-size:.7rem;color:rgba(255,255,255,.6);">81%</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:8px;height:8px;border-radius:50%;background:#fde68a;flex-shrink:0;"></div>
                  <div style="font-size:.78rem;color:rgba(255,255,255,.9);flex:1;">6 Pending Review</div>
                  <div style="font-size:.7rem;color:rgba(255,255,255,.6);">13%</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:8px;height:8px;border-radius:50%;background:#fca5a5;flex-shrink:0;"></div>
                  <div style="font-size:.78rem;color:rgba(255,255,255,.9);flex:1;">3 Rejected</div>
                  <div style="font-size:.7rem;color:rgba(255,255,255,.6);">6%</div>
                </div>
              </div>
            </div>
          </div>
          <div style="padding:22px 28px;">
            <ul style="list-style:none;padding:0;margin:0 0 20px;display:flex;flex-direction:column;gap:9px;">
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#14b8a6;flex-shrink:0;"></i> Submit child beneficiary records</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#14b8a6;flex-shrink:0;"></i> Track submission approval status</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#14b8a6;flex-shrink:0;"></i> Receive notifications from admin</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#14b8a6;flex-shrink:0;"></i> View feeding program schedules</li>
              <li style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#334155;"><i class="fas fa-check-circle" style="color:#14b8a6;flex-shrink:0;"></i> Manage church site profile</li>
            </ul>
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <span style="font-size:.75rem;color:#64748b;background:#f0fdfa;padding:5px 12px;border-radius:20px;"><i class="fas fa-lock" style="margin-right:5px;color:#94a3b8;"></i>Requires Church Login</span>
              <a href="login.php" style="font-size:.82rem;font-weight:600;color:#0f766e;text-decoration:none;">Sign In <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>

      </div>

      <!-- Auth notice -->
      <div style="margin-top:28px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:16px 24px;display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;background:#dbeafe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#2563eb;font-size:1.1rem;">
          <i class="fas fa-shield-halved"></i>
        </div>
        <div>
          <div style="font-weight:700;font-size:.9rem;color:#1e40af;margin-bottom:2px;">Secure Role-Based Access</div>
          <div style="font-size:.82rem;color:#3b82f6;line-height:1.5;">Access to any portal requires valid credentials. Each role sees only the features relevant to their responsibilities. Unauthorized access attempts are logged and monitored.</div>
        </div>
        <a href="login.php" class="btn btn-primary" style="flex-shrink:0;padding:10px 20px;font-size:.85rem;white-space:nowrap;"><i class="fas fa-sign-in-alt"></i> Log In Now</a>
      </div>
    </div>
  </section>

  <!-- security -->
  <section class="section" id="security" style="background:#0f172a;">
    <div class="section-container">
      <div class="section-header center">
        <div class="section-eyebrow" style="color:#60a5fa;border-color:#1e3a5f;background:#1e3a5f;"><i class="fas fa-shield-halved"></i> Security &amp; Infrastructure</div>
        <h2 class="section-title" style="color:#f1f5f9;">Built for Trust.<br>Designed for Scale.</h2>
        <p class="section-desc max-700" style="color:#94a3b8;">
          DivineShield is built on enterprise-grade security infrastructure to protect
          sensitive child beneficiary data and ensure the integrity of every record.
        </p>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:20px;margin-top:8px;">
        <div style="background:#1e293b;border-radius:16px;padding:24px;border:1px solid #334155;">
          <div style="width:46px;height:46px;background:#1e3a5f;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#60a5fa;font-size:1.2rem;"><i class="fas fa-lock"></i></div>
          <h4 style="font-family:'Poppins',sans-serif;color:#f1f5f9;font-size:.95rem;margin:0 0 8px;">AES-256 Encryption</h4>
          <p style="font-size:.82rem;color:#64748b;margin:0;line-height:1.6;">All data at rest and in transit is protected with military-grade AES-256 encryption.</p>
        </div>
        <div style="background:#1e293b;border-radius:16px;padding:24px;border:1px solid #334155;">
          <div style="width:46px;height:46px;background:#14291a;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#4ade80;font-size:1.2rem;"><i class="fas fa-user-shield"></i></div>
          <h4 style="font-family:'Poppins',sans-serif;color:#f1f5f9;font-size:.95rem;margin:0 0 8px;">Role-Based Access Control</h4>
          <p style="font-size:.82rem;color:#64748b;margin:0;line-height:1.6;">Strict RBAC ensures Admin, Staff, and Church Leaders access only what they are authorized to see.</p>
        </div>
        <div style="background:#1e293b;border-radius:16px;padding:24px;border:1px solid #334155;">
          <div style="width:46px;height:46px;background:#1e1a3a;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#a78bfa;font-size:1.2rem;"><i class="fas fa-key"></i></div>
          <h4 style="font-family:'Poppins',sans-serif;color:#f1f5f9;font-size:.95rem;margin:0 0 8px;">Multi-Factor Authentication</h4>
          <p style="font-size:.82rem;color:#64748b;margin:0;line-height:1.6;">Admin accounts require a secondary 4-digit PIN after credentials, adding an extra layer of protection.</p>
        </div>
        <div style="background:#1e293b;border-radius:16px;padding:24px;border:1px solid #334155;">
          <div style="width:46px;height:46px;background:#1a2a1e;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#34d399;font-size:1.2rem;"><i class="fab fa-google"></i></div>
          <h4 style="font-family:'Poppins',sans-serif;color:#f1f5f9;font-size:.95rem;margin:0 0 8px;">Google Cloud Platform</h4>
          <p style="font-size:.82rem;color:#64748b;margin:0;line-height:1.6;">Hosted on Google Cloud with IAM policies, Cloud SQL, and BigQuery analytics integration.</p>
        </div>
        <div style="background:#1e293b;border-radius:16px;padding:24px;border:1px solid #334155;">
          <div style="width:46px;height:46px;background:#2a1a1a;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#f87171;font-size:1.2rem;"><i class="fas fa-scroll"></i></div>
          <h4 style="font-family:'Poppins',sans-serif;color:#f1f5f9;font-size:.95rem;margin:0 0 8px;">Audit Log Monitoring</h4>
          <p style="font-size:.82rem;color:#64748b;margin:0;line-height:1.6;">Every user action is logged with timestamp and IP address for full accountability and traceability.</p>
        </div>
        <div style="background:#1e293b;border-radius:16px;padding:24px;border:1px solid #334155;">
          <div style="width:46px;height:46px;background:#1a2030;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#38bdf8;font-size:1.2rem;"><i class="fas fa-shield-virus"></i></div>
          <h4 style="font-family:'Poppins',sans-serif;color:#f1f5f9;font-size:.95rem;margin:0 0 8px;">Threat Detection</h4>
          <p style="font-size:.82rem;color:#64748b;margin:0;line-height:1.6;">Adaptive threat detection monitors for brute-force attempts, suspicious logins, and unauthorized access.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- footer -->
  <footer style="background:#020617;padding:40px 24px 28px;text-align:center;">
    <div style="max-width:900px;margin:0 auto;">
      <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:16px;">
        <div class="logo-mark small" style="width:36px;height:36px;font-size:1rem;"><i class="fas fa-shield-halved"></i></div>
        <div style="text-align:left;">
          <div style="font-family:'Poppins',sans-serif;font-weight:700;color:#f1f5f9;font-size:1rem;">DivineShield</div>
          <div style="font-size:.72rem;color:#475569;">MAINPI Cloud System</div>
        </div>
      </div>
      <p style="font-size:.8rem;color:#475569;line-height:1.7;max-width:560px;margin:0 auto 20px;">
        Minister's Association Integrated Nutrition Program Inc. — Empowering communities through coordinated,
        compassionate child nutrition programs across the Philippines since 2017.
      </p>
      <div style="display:flex;align-items:center;justify-content:center;gap:24px;flex-wrap:wrap;margin-bottom:20px;">
        <a href="#home" style="font-size:.82rem;color:#64748b;text-decoration:none;">Home</a>
        <a href="#about" style="font-size:.82rem;color:#64748b;text-decoration:none;">About</a>
        <a href="#programs" style="font-size:.82rem;color:#64748b;text-decoration:none;">Programs</a>
        <a href="#overview" style="font-size:.82rem;color:#64748b;text-decoration:none;">System Overview</a>
        <a href="login.php" style="font-size:.82rem;color:#3b82f6;text-decoration:none;font-weight:600;">Login <i class="fas fa-arrow-right"></i></a>
      </div>
      <div style="border-top:1px solid #1e293b;padding-top:18px;font-size:.75rem;color:#334155;">
        &copy; 2026 DivineShield &mdash; MAINPI Cloud System. All rights reserved. &nbsp;&middot;&nbsp; Capstone Project &nbsp;&middot;&nbsp; Secure &middot; Reliable &middot; Cloud-Based
      </div>
    </div>
  </footer>

<script src="assets/js/main.js?v=3"></script>
</body>
</html>