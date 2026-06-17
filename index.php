<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/images/mainpi-logo.png" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MAINPI – Minister's Association Integrated Nutrition Program Inc.</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  
  <style>
    /* ── CUSTOM BESPOKE LANDING THEME & STYLES ────────────────────────── */
    :root {
      --bg-dark: #030712;
      --bg-card: rgba(17, 24, 39, 0.7);
      --bg-card-hover: rgba(31, 41, 55, 0.8);
      --border-glow: rgba(99, 102, 241, 0.15);
      
      --color-primary: #6366f1; /* Indigo */
      --color-primary-glow: rgba(99, 102, 241, 0.4);
      --color-secondary: #a855f7; /* Purple */
      --color-accent: #10b981; /* Emerald */
      --color-accent-glow: rgba(16, 185, 129, 0.2);
      
      --text-main: #f3f4f6;
      --text-muted: #9ca3af;
      --text-dark: #111827;
      
      --font-outfit: 'Outfit', sans-serif;
      --font-jakarta: 'Plus Jakarta Sans', sans-serif;
      
      --glass-border: 1px solid rgba(255, 255, 255, 0.08);
      --glass-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
      --transition-smooth: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      scroll-behavior: smooth;
    }

    body {
      font-family: var(--font-jakarta);
      background-color: var(--bg-dark);
      color: var(--text-main);
      overflow-x: hidden;
      line-height: 1.6;
    }

    /* ── SCROLLBAR ───────────────────────────────────────────────────── */
    ::-webkit-scrollbar {
      width: 10px;
    }
    ::-webkit-scrollbar-track {
      background: #020617;
    }
    ::-webkit-scrollbar-thumb {
      background: #1e293b;
      border-radius: 5px;
      border: 2px solid #020617;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: var(--color-primary);
    }

    /* ── COMMON LAYOUT ──────────────────────────────────────────────── */
    .section-container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
    }

    .eyebrow-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(99, 102, 241, 0.1);
      border: 1px solid rgba(99, 102, 241, 0.2);
      color: #a5b4fc;
      font-family: var(--font-outfit);
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 6px 16px;
      border-radius: 100px;
      margin-bottom: 20px;
    }

    .grad-text {
      background: linear-gradient(135deg, #a5b4fc 0%, #c084fc 50%, #818cf8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .btn-action {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 14px 30px;
      border-radius: 12px;
      font-family: var(--font-outfit);
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      transition: var(--transition-smooth);
      cursor: pointer;
    }

    .btn-action-primary {
      background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
      color: #ffffff;
      box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
      border: none;
    }

    .btn-action-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(99, 102, 241, 0.5);
    }

    .btn-action-secondary {
      background: rgba(255, 255, 255, 0.03);
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-action-secondary:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.25);
    }

    /* ── HEADER ─────────────────────────────────────────────────────── */
    .header-nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 80px;
      z-index: 1000;
      background: rgba(3, 7, 18, 0.7);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      transition: var(--transition-smooth);
    }

    .header-nav.scrolled {
      background: rgba(3, 7, 18, 0.9);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      height: 72px;
    }

    .nav-wrapper {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 100%;
    }

    .brand-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }

    .logo-badge {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.25rem;
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
    }

    .brand-meta {
      display: flex;
      flex-direction: column;
    }

    .brand-title {
      font-family: var(--font-outfit);
      font-weight: 800;
      font-size: 1.25rem;
      color: #fff;
      line-height: 1.1;
    }

    .brand-subtitle {
      font-size: 0.7rem;
      color: var(--text-muted);
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 32px;
      list-style: none;
    }

    .nav-link-item {
      font-size: 0.88rem;
      font-weight: 500;
      color: var(--text-muted);
      text-decoration: none;
      transition: var(--transition-smooth);
    }

    .nav-link-item:hover, .nav-link-item.active {
      color: #fff;
    }

    .btn-login-nav {
      padding: 10px 22px;
      border-radius: 10px;
      background: rgba(99, 102, 241, 0.1);
      border: 1px solid rgba(99, 102, 241, 0.2);
      color: #fff;
      font-size: 0.88rem;
      font-weight: 600;
      font-family: var(--font-outfit);
      text-decoration: none;
      transition: var(--transition-smooth);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-login-nav:hover {
      background: var(--color-primary);
      border-color: var(--color-primary);
      box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
    }

    .hamburger-btn {
      display: none;
      background: none;
      border: none;
      cursor: pointer;
      color: #fff;
      font-size: 1.5rem;
    }

    /* ── HERO ───────────────────────────────────────────────────────── */
    .hero-sec {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 140px 0 80px;
      background: radial-gradient(circle at 10% 10%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                  radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.06) 0%, transparent 45%);
      overflow: hidden;
    }

    .hero-glow-blob {
      position: absolute;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
      filter: blur(40px);
      pointer-events: none;
      z-index: 0;
    }

    .blob-1 { top: 20%; left: -100px; }
    .blob-2 { bottom: 10%; right: -100px; }

    .hero-grid {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: 60px;
      align-items: center;
      position: relative;
      z-index: 1;
    }

    .hero-content {
      max-width: 620px;
    }

    .hero-logo-box {
      margin-bottom: 24px;
      display: inline-block;
    }

    .hero-logo-img {
      height: 64px;
      width: auto;
      object-fit: contain;
    }

    .hero-main-title {
      font-family: var(--font-outfit);
      font-weight: 900;
      font-size: clamp(2.2rem, 5.5vw, 3.8rem);
      line-height: 1.1;
      color: #fff;
      margin-bottom: 20px;
      letter-spacing: -0.02em;
    }

    .hero-description {
      font-size: 1.05rem;
      color: var(--text-muted);
      line-height: 1.8;
      margin-bottom: 36px;
    }

    .hero-btn-group {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 48px;
    }

    /* Floating Stats Bar */
    .hero-stats-bar {
      display: flex;
      background: var(--bg-card);
      backdrop-filter: blur(12px);
      border: var(--glass-border);
      border-radius: 16px;
      padding: 18px 24px;
      gap: 32px;
      max-width: 540px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .hero-stat-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .hero-stat-num {
      font-family: var(--font-outfit);
      font-size: 1.6rem;
      font-weight: 800;
      color: #fff;
      line-height: 1;
    }

    .hero-stat-lbl {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
    }

    .hero-stat-sep {
      width: 1px;
      background: rgba(255, 255, 255, 0.1);
      align-self: stretch;
    }

    /* Right Visual: Interactive System Mockup Console */
    .hero-preview-console {
      position: relative;
      background: #090d16;
      border: var(--glass-border);
      border-radius: 20px;
      box-shadow: var(--glass-shadow), 0 0 40px rgba(99, 102, 241, 0.1);
      padding: 24px;
      overflow: hidden;
      aspect-ratio: 1.15;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .console-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      padding-bottom: 14px;
    }

    .console-dots {
      display: flex;
      gap: 6px;
    }

    .console-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }

    .console-dot-r { background: #ef4444; }
    .console-dot-y { background: #eab308; }
    .console-dot-g { background: #22c55e; }

    .console-title {
      font-family: var(--font-outfit);
      font-size: 0.78rem;
      color: var(--text-muted);
      letter-spacing: 0.04em;
      text-transform: uppercase;
      font-weight: 700;
    }

    .console-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .mock-metrics {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .mock-metric-card {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      padding: 14px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .mock-metric-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }

    .m-i-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .m-i-teal { background: rgba(16, 185, 129, 0.1); color: #10b981; }

    .mock-metric-vals {
      display: flex;
      flex-direction: column;
    }

    .mock-metric-val {
      font-family: var(--font-outfit);
      font-weight: 700;
      font-size: 1.15rem;
      color: #fff;
    }

    .mock-metric-lbl {
      font-size: 0.65rem;
      color: var(--text-muted);
    }

    /* Mock Chart Container */
    .mock-chart-container {
      flex: 1;
      background: rgba(255, 255, 255, 0.01);
      border: 1px solid rgba(255, 255, 255, 0.04);
      border-radius: 12px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .mock-chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.72rem;
      margin-bottom: 8px;
    }

    .mock-chart-title {
      font-weight: 600;
      color: #fff;
    }

    .mock-chart-trend {
      color: #10b981;
      font-weight: 700;
    }

    .mock-chart-bars {
      display: flex;
      align-items: flex-end;
      height: 90px;
      gap: 12px;
      padding-top: 10px;
    }

    .mock-chart-bar-wrap {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
    }

    .mock-chart-bar-fill {
      width: 100%;
      background: linear-gradient(to top, var(--color-primary), var(--color-secondary));
      border-radius: 4px 4px 0 0;
      min-height: 10px;
      transition: var(--transition-smooth);
    }

    .mock-chart-bar-lbl {
      font-size: 0.6rem;
      color: var(--text-muted);
    }

    /* Floating visual badge */
    .visual-pill-badge {
      position: absolute;
      bottom: 12px;
      right: 12px;
      background: var(--color-accent);
      color: var(--text-dark);
      font-family: var(--font-outfit);
      font-weight: 700;
      font-size: 0.78rem;
      padding: 8px 16px;
      border-radius: 100px;
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* ── ABOUT SECTION ──────────────────────────────────────────────── */
    .about-sec {
      padding: 100px 0;
      background-color: #020617;
      border-top: 1px solid rgba(255, 255, 255, 0.03);
    }

    .about-grid-layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 80px;
      align-items: center;
    }

    .about-visuals {
      position: relative;
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 20px;
    }

    .about-img-box {
      border-radius: 16px;
      overflow: hidden;
      border: var(--glass-border);
      box-shadow: var(--glass-shadow);
      aspect-ratio: 1.15;
    }

    .about-img-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .about-stacked-glow {
      position: absolute;
      width: 250px;
      height: 250px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, transparent 70%);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      filter: blur(30px);
      z-index: 0;
      pointer-events: none;
    }

    .about-quote-card {
      background: var(--bg-card);
      backdrop-filter: blur(12px);
      border: var(--glass-border);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      gap: 12px;
      align-self: center;
    }

    .quote-icon {
      font-size: 1.8rem;
      color: var(--color-primary);
    }

    .quote-text {
      font-size: 0.85rem;
      color: var(--text-muted);
      line-height: 1.6;
      font-style: italic;
    }

    .about-header-desc {
      font-size: 1.05rem;
      color: var(--text-muted);
      margin-bottom: 28px;
    }

    /* Core pillars grid */
    .pillars-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .pillar-card {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.04);
      border-radius: 16px;
      padding: 24px;
      transition: var(--transition-smooth);
    }

    .pillar-card:hover {
      border-color: rgba(99, 102, 241, 0.2);
      background: rgba(255, 255, 255, 0.04);
    }

    .pillar-icon {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      background: rgba(99, 102, 241, 0.1);
      border: 1px solid rgba(99, 102, 241, 0.2);
      color: #a5b4fc;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      margin-bottom: 16px;
    }

    .pillar-card h4 {
      font-family: var(--font-outfit);
      font-size: 1.05rem;
      color: #fff;
      margin-bottom: 8px;
    }

    .pillar-card p {
      font-size: 0.82rem;
      color: var(--text-muted);
      line-height: 1.5;
    }

    /* ── SYSTEM TOUR (INTERACTIVE PORTAL SWITCHER) ────────────────────── */
    .tour-sec {
      padding: 100px 0;
      background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.03) 0%, transparent 60%);
    }

    .tour-header {
      text-align: center;
      max-width: 700px;
      margin: 0 auto 56px;
    }

    .tour-title {
      font-family: var(--font-outfit);
      font-size: 2.2rem;
      font-weight: 800;
      color: #fff;
      margin-bottom: 16px;
    }

    .tour-tabs {
      display: inline-flex;
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 14px;
      padding: 6px;
      margin-bottom: 40px;
      gap: 6px;
    }

    .tour-tab-btn {
      background: none;
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-family: var(--font-outfit);
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--text-muted);
      cursor: pointer;
      transition: var(--transition-smooth);
    }

    .tour-tab-btn.active {
      background: var(--color-primary);
      color: #fff;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .tour-content-panel {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 60px;
      align-items: center;
      background: var(--bg-card);
      border: var(--glass-border);
      border-radius: 24px;
      padding: 48px;
      box-shadow: var(--glass-shadow);
      min-height: 480px;
    }

    .tour-pane {
      display: none;
      animation: fadeIn 0.4s ease-out forwards;
    }

    .tour-pane.active {
      display: contents;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .tour-text-side {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .tour-panel-badge {
      align-self: flex-start;
      padding: 5px 14px;
      border-radius: 100px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .tour-pane-admin .tour-panel-badge { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
    .tour-pane-staff .tour-panel-badge { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
    .tour-pane-leader .tour-panel-badge { background: rgba(16, 185, 129, 0.15); color: #34d399; }

    .tour-pane-title {
      font-family: var(--font-outfit);
      font-size: 1.8rem;
      font-weight: 800;
      color: #fff;
      line-height: 1.2;
    }

    .tour-pane-desc {
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.7;
    }

    .tour-features-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      list-style: none;
    }

    .tour-feature-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.85rem;
      color: var(--text-main);
    }

    .tour-feature-item i {
      color: var(--color-primary);
    }

    /* Live Interactive Dashboard Simulator (Visual side of panel) */
    .tour-visual-side {
      background: #090d16;
      border: var(--glass-border);
      border-radius: 16px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      box-shadow: inset 0 0 30px rgba(0,0,0,0.8);
      aspect-ratio: 1.25;
      font-family: monospace;
      font-size: 0.72rem;
      color: #64748b;
      position: relative;
    }

    /* Mini Mockup components */
    .mock-topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      padding-bottom: 10px;
    }

    .mock-avatar-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .mock-avatar {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #1e293b;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #94a3b8;
    }

    .mock-grid-box {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }

    .mock-stat-tile {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 8px;
      padding: 10px;
      text-align: center;
    }

    .mock-tile-num {
      color: #fff;
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 2px;
    }

    .mock-tile-label {
      font-size: 0.55rem;
      color: var(--text-muted);
      text-transform: uppercase;
    }

    .mock-table-view {
      flex: 1;
      background: rgba(255,255,255,0.01);
      border: 1px solid rgba(255,255,255,0.04);
      border-radius: 10px;
      padding: 12px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      overflow: hidden;
    }

    .mock-table-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    }

    .mock-table-row:last-child { border: none; }
    .mock-badge-status {
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 0.55rem;
    }

    .mb-success { background: rgba(16, 185, 129, 0.1); color: #34d399; }
    .mb-warning { background: rgba(245, 158, 11, 0.1); color: #fbbf24; }
    .mb-danger { background: rgba(239, 68, 68, 0.1); color: #f87171; }

    /* ── SYSTEM SECURITY TERMINAL ───────────────────────────────────── */
    .sec-panel-sec {
      padding: 100px 0;
      background: #020617;
      border-top: 1px solid rgba(255,255,255,0.03);
      border-bottom: 1px solid rgba(255,255,255,0.03);
    }

    .sec-panel-layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 80px;
      align-items: center;
    }

    .sec-terminal-mock {
      background: #080d16;
      border: var(--glass-border);
      border-radius: 16px;
      box-shadow: var(--glass-shadow);
      padding: 24px;
      font-family: monospace;
      color: #38bdf8;
      font-size: 0.75rem;
      line-height: 1.5;
      display: flex;
      flex-direction: column;
      gap: 12px;
      min-height: 320px;
    }

    .terminal-title-row {
      display: flex;
      justify-content: space-between;
      color: var(--text-muted);
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      padding-bottom: 8px;
      margin-bottom: 6px;
    }

    .terminal-body {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .terminal-row {
      display: flex;
      gap: 12px;
    }

    .terminal-time {
      color: #64748b;
    }

    .terminal-tag {
      color: #34d399;
      font-weight: 700;
    }

    .terminal-tag.warn {
      color: #fbbf24;
    }

    .terminal-msg {
      color: #94a3b8;
    }

    /* ── FOOTER ─────────────────────────────────────────────────────── */
    .footer-section {
      background: #030712;
      padding: 80px 0 32px;
      border-top: 1px solid rgba(255, 255, 255, 0.03);
    }

    .footer-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 40px;
      text-align: center;
    }

    .footer-desc {
      font-size: 0.9rem;
      color: var(--text-muted);
      max-width: 580px;
      line-height: 1.7;
    }

    .footer-menu {
      display: flex;
      gap: 32px;
      flex-wrap: wrap;
      justify-content: center;
      list-style: none;
    }

    .footer-menu-link {
      font-size: 0.88rem;
      color: var(--text-muted);
      text-decoration: none;
      transition: var(--transition-smooth);
    }

    .footer-menu-link:hover {
      color: #fff;
    }

    .footer-copyright {
      width: 100%;
      border-top: 1px solid rgba(255, 255, 255, 0.04);
      padding-top: 24px;
      font-size: 0.72rem;
      color: #4b5563;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }

    .footer-cr-links {
      display: flex;
      gap: 16px;
    }

    /* ── RESPONSIVE RESPONSIVENESS ───────────────────────────────────── */
    @media (max-width: 1024px) {
      .hero-grid, .about-grid-layout, .tour-content-panel, .sec-panel-layout {
        grid-template-columns: 1fr;
        gap: 56px;
      }
      
      .hero-content {
        max-width: 100%;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
      }

      .hero-btn-group {
        justify-content: center;
      }

      .hero-stats-bar {
        max-width: 100%;
        width: 100%;
      }

      .about-visuals {
        order: -1;
      }
    }

    @media (max-width: 768px) {
      .nav-links {
        display: none;
      }

      .hamburger-btn {
        display: block;
      }

      .footer-copyright {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <!-- ═══════════════════════════════════════════════════ NAVBAR ══ -->
  <nav class="header-nav" id="navbar">
    <div class="nav-wrapper">
      <a href="#home" class="brand-logo">
        <div class="logo-badge">
          <i class="fas fa-shield-halved"></i>
        </div>
        <div class="brand-meta">
          <span class="brand-title">DivineShield</span>
          <span class="brand-subtitle">MAINPI Cloud System</span>
        </div>
      </a>

      <ul class="nav-links">
        <li><a href="#home" class="nav-link-item active">Home</a></li>
        <li><a href="#about" class="nav-link-item">About</a></li>
        <li><a href="#tour" class="nav-link-item">System Tour</a></li>
        <li><a href="#security" class="nav-link-item">Security</a></li>
        <li><a href="login.php" class="btn-login-nav">Login <i class="fas fa-right-to-bracket"></i></a></li>
      </ul>

      <button class="hamburger-btn" id="hamburger" aria-label="Toggle Menu">
        <i class="fas fa-bars"></i>
      </button>
    </div>
  </nav>

  <!-- ═══════════════════════════════════════════════════ HERO ══ -->
  <section class="hero-sec" id="home">
    <div class="hero-glow-blob blob-1"></div>
    <div class="hero-glow-blob blob-2"></div>
    
    <div class="section-container">
      <div class="hero-grid">
        <div class="hero-content">
          <div class="hero-logo-box">
            <img src="assets/images/mainpi-logo.png" alt="MAINPI Logo" class="hero-logo-img" />
          </div>
          
          <h1 class="hero-main-title">
            Empowering Nationwide <span class="grad-text">Child Nutrition</span> Initiatives
          </h1>
          
          <p class="hero-description">
            DivineShield is an enterprise-grade cloud system coordinating health monitoring and feeding schedules for Minister's Association Integrated Nutrition Program Inc. (MAINPI). Bringing administrators, encoders, and church leaders together to build healthy futures.
          </p>
          
          <div class="hero-btn-group">
            <a href="login.php" class="btn-action btn-action-primary"><i class="fas fa-sign-in-alt"></i> Login to System</a>
            <a href="#tour" class="btn-action btn-action-secondary"><i class="fas fa-desktop"></i> Explore Portals</a>
          </div>
          
          <div class="hero-stats-bar">
            <div class="hero-stat-item">
              <span class="hero-stat-num">200+</span>
              <span class="hero-stat-lbl">Church Sites</span>
            </div>
            <div class="hero-stat-sep"></div>
            <div class="hero-stat-item">
              <span class="hero-stat-num">12</span>
              <span class="hero-stat-lbl">Staff Encoders</span>
            </div>
            <div class="hero-stat-sep"></div>
            <div class="hero-stat-item">
              <span class="hero-stat-num">1-12</span>
              <span class="hero-stat-lbl">Age Range (Yrs)</span>
            </div>
          </div>
        </div>
        
        <!-- Interactive Mockup Console -->
        <div class="hero-preview-console">
          <div class="console-header">
            <div class="console-dots">
              <span class="console-dot console-dot-r"></span>
              <span class="console-dot console-dot-y"></span>
              <span class="console-dot console-dot-g"></span>
            </div>
            <span class="console-title">Live Analytics Feed</span>
            <span style="font-size:0.65rem; color:#10b981; display:flex; align-items:center; gap:4px;"><span style="width:6px;height:6px;background:#10b981;border-radius:50%;display:inline-block;"></span> Online</span>
          </div>
          
          <div class="console-body">
            <div class="mock-metrics">
              <div class="mock-metric-card">
                <div class="mock-metric-icon m-i-blue">
                  <i class="fas fa-children"></i>
                </div>
                <div class="mock-metric-vals">
                  <span class="mock-metric-val">1,842</span>
                  <span class="mock-metric-lbl">Total Registered</span>
                </div>
              </div>
              
              <div class="mock-metric-card">
                <div class="mock-metric-icon m-i-teal">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="mock-metric-vals">
                  <span class="mock-metric-val">94.2%</span>
                  <span class="mock-metric-lbl">Qualified Status</span>
                </div>
              </div>
            </div>
            
            <div class="mock-chart-container">
              <div class="mock-chart-header">
                <span class="mock-chart-title">Nutritional Recovery (Weekly Success)</span>
                <span class="mock-chart-trend"><i class="fas fa-arrow-trend-up"></i> +4.8%</span>
              </div>
              
              <div class="mock-chart-bars">
                <div class="mock-chart-bar-wrap">
                  <div class="mock-chart-bar-fill" style="height: 35px;"></div>
                  <span class="mock-chart-bar-lbl">W1</span>
                </div>
                <div class="mock-chart-bar-wrap">
                  <div class="mock-chart-bar-fill" style="height: 55px;"></div>
                  <span class="mock-chart-bar-lbl">W2</span>
                </div>
                <div class="mock-chart-bar-wrap">
                  <div class="mock-chart-bar-fill" style="height: 72px;"></div>
                  <span class="mock-chart-bar-lbl">W3</span>
                </div>
                <div class="mock-chart-bar-wrap">
                  <div class="mock-chart-bar-fill" style="height: 60px;"></div>
                  <span class="mock-chart-bar-lbl">W4</span>
                </div>
                <div class="mock-chart-bar-wrap">
                  <div class="mock-chart-bar-fill" style="height: 85px;"></div>
                  <span class="mock-chart-bar-lbl">W5</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="visual-pill-badge">
            <i class="fas fa-star"></i> Since 2017
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════ ABOUT ══ -->
  <section class="about-sec" id="about">
    <div class="section-container">
      <div class="about-grid-layout">
        
        <div class="about-visuals">
          <div class="about-stacked-glow"></div>
          
          <div class="about-img-box">
            <img src="assets/images/pict_large.jpg" alt="MAINPI Child Feeding Session" />
          </div>
          
          <div class="about-quote-card">
            <i class="fas fa-quote-left quote-icon"></i>
            <p class="quote-text">
              "We partner with Born-Again pastors and Christian organizations to rescue, nourish, and support vulnerable children in urban and agricultural communities throughout the Philippines."
            </p>
            <span style="font-size:0.75rem; font-weight:700; color:#fff;">&mdash; MAINPI Board Directors</span>
          </div>
        </div>
        
        <div class="about-text-content">
          <div class="eyebrow-badge">
            <i class="fas fa-heart"></i> About MAINPI
          </div>
          
          <h2 class="hero-main-title" style="font-size: 2.2rem; margin-bottom:16px;">
            Spiritual, Moral and Social Restoration
          </h2>
          
          <p class="about-header-desc">
            Minister's Association Integrated Nutrition Program Inc. (MAINPI) coordinates meal delivery and growth tracking for Filipino children. Powered by over 200 church leaders and 15 operations staff, we bring food and care to where it's needed most.
          </p>
          
          <div class="pillars-grid">
            <div class="pillar-card">
              <div class="pillar-icon">
                <i class="fas fa-cross"></i>
              </div>
              <h4>Restoration</h4>
              <p>Reclaiming communities and restoring families through targeted spiritual and moral service initiatives.</p>
            </div>
            
            <div class="pillar-card">
              <div class="pillar-icon">
                <i class="fas fa-kit-medical"></i>
              </div>
              <h4>Nutrition</h4>
              <p>Tracking heights, weights, and nutritional parameters to ensure healthy development and active growth.</p>
            </div>
          </div>
        </div>
        
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════ SYSTEM TOUR ══ -->
  <section class="tour-sec" id="tour">
    <div class="section-container">
      <div class="tour-header">
        <div class="eyebrow-badge"><i class="fas fa-magnifying-glass"></i> Interactive Tour</div>
        <h2 class="tour-title">Three Portal Channels. One Cloud.</h2>
        <p style="color:var(--text-muted); font-size:0.95rem;">
          Select a role tab below to see how our cloud system adapts to deliver tools and metrics designed specifically for each workflow.
        </p>
      </div>

      <div style="text-align:center;">
        <div class="tour-tabs">
          <button class="tour-tab-btn active" data-portal="admin">Admin Portal</button>
          <button class="tour-tab-btn" data-portal="staff">Staff Portal</button>
          <button class="tour-tab-btn" data-portal="leader">Church Leader</button>
        </div>
      </div>

      <div class="tour-content-panel">
        
        <!-- LEFT: Info pane -->
        <div class="tour-text-side">
          
          <!-- Admin Pane Info -->
          <div class="tour-pane tour-pane-admin active">
            <span class="tour-panel-badge">Administrator View</span>
            <h3 class="tour-pane-title" style="margin: 12px 0 16px;">Full System Control &amp; Analytics</h3>
            <p class="tour-pane-desc" style="margin-bottom: 24px;">
              Admin portal gives full administrative oversight of users, locations, and system parameters. Access analytical charts, configure rules, and compile comprehensive reports.
            </p>
            <ul class="tour-features-list">
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Account and role activation controls</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Interactive CSV, Excel, and PDF exports</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> System settings &amp; status locks</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Detailed activity audit logs</li>
            </ul>
          </div>
          
          <!-- Staff Pane Info -->
          <div class="tour-pane tour-pane-staff">
            <span class="tour-panel-badge">Encoder View</span>
            <h3 class="tour-pane-title" style="margin: 12px 0 16px;">Review, Verification &amp; Log Entry</h3>
            <p class="tour-pane-desc" style="margin-bottom: 24px;">
              Staff encoders perform growth metric verifications and submissions review. Approve pending submissions or input feedback notes directly to local church leaders.
            </p>
            <ul class="tour-features-list">
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Review pending leader applications</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Approve or reject child candidates</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Track personal staff check-in times</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Real-time activity timeline tracking</li>
            </ul>
          </div>
          
          <!-- Church Leader Pane Info -->
          <div class="tour-pane tour-pane-leader">
            <span class="tour-panel-badge">Leader Gateway</span>
            <h3 class="tour-pane-title" style="margin: 12px 0 16px;">Submit &amp; Track Site Beneficiaries</h3>
            <p class="tour-pane-desc" style="margin-bottom: 24px;">
              Local pastors register their sites and submit children candidate details. Track submissions, record child growth logs, and scan RFID tags during feeding programs.
            </p>
            <ul class="tour-features-list">
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Submit weight/height for auto-BMI assessment</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Track site approval status list</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Hardware-integrated RFID check-ins</li>
              <li class="tour-feature-item"><i class="fas fa-circle-check"></i> Nutritional history progress logs</li>
            </ul>
          </div>

          <a href="login.php" class="btn-action btn-action-primary" style="margin-top: 12px; align-self: flex-start;">
            Access Gateway <i class="fas fa-arrow-right"></i>
          </a>
        </div>

        <!-- RIGHT: Dashboard simulator screen -->
        <div class="tour-visual-side">
          
          <!-- Top bar emulator -->
          <div class="mock-topbar">
            <div style="display:flex; align-items:center; gap:8px;">
              <i class="fas fa-shield-halved" style="color:var(--color-primary);"></i>
              <span id="mock-portal-title" style="color:#fff; font-weight:700;">Admin_Dashboard</span>
            </div>
            
            <div class="mock-avatar-row">
              <span id="mock-portal-role" style="font-size:0.6rem; color:#60a5fa; background:rgba(96,165,250,0.1); padding:2px 8px; border-radius:4px; font-weight:700;">ADMIN</span>
              <div class="mock-avatar">
                <i class="fas fa-user" style="font-size:0.6rem;"></i>
              </div>
            </div>
          </div>

          <!-- Live updating mock stats -->
          <div class="mock-grid-box" id="mock-stats-grid">
            <div class="mock-stat-tile">
              <div class="mock-tile-num" id="tile-1-val">200+</div>
              <div class="mock-tile-label" id="tile-1-lbl">Sites</div>
            </div>
            <div class="mock-stat-tile">
              <div class="mock-tile-num" id="tile-2-val">1,842</div>
              <div class="mock-tile-label" id="tile-2-lbl">Children</div>
            </div>
            <div class="mock-stat-tile">
              <div class="mock-tile-num" id="tile-3-val">15</div>
              <div class="mock-tile-label" id="tile-3-lbl">Encoders</div>
            </div>
          </div>

          <!-- Live updating mock table/log contents -->
          <div class="mock-table-view">
            <div style="font-size:0.6rem; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom:4px;" id="mock-table-title">System Activity Log</div>
            
            <div id="mock-rows-container">
              <div class="mock-table-row">
                <span>Site Activation: Grace Born-Again</span>
                <span class="mock-badge-status mb-success">Approved</span>
              </div>
              <div class="mock-table-row">
                <span>Staff Check-in: @encoder1</span>
                <span class="mock-badge-status mb-success">Checked In</span>
              </div>
              <div class="mock-table-row">
                <span>Audit: Exported reports.csv</span>
                <span class="mock-badge-status mb-warning">Logged</span>
              </div>
            </div>
          </div>
          
        </div>

      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════ SECURITY ══ -->
  <section class="sec-panel-sec" id="security">
    <div class="section-container">
      <div class="sec-panel-layout">
        
        <div class="sec-text-content">
          <div class="eyebrow-badge" style="background:rgba(16, 185, 129, 0.1); border-color:rgba(16, 185, 129, 0.2); color:#a7f3d0;">
            <i class="fas fa-shield-halved"></i> Cloud Infrastructure
          </div>
          
          <h2 class="hero-main-title" style="font-size: 2.2rem; margin-bottom:16px;">
            Secure &amp; Protected Data Architecture
          </h2>
          
          <p class="about-header-desc">
            We employ industry-standard cloud protocols to safeguard critical child metrics and coordinate network access. Security and accountability are built into every system log.
          </p>
          
          <div class="pillars-grid" style="grid-template-columns: 1fr; gap:16px;">
            <div class="pillar-card" style="display:flex; gap:16px; align-items:flex-start;">
              <div class="pillar-icon" style="background:rgba(16,185,129,0.1); border-color:rgba(16,185,129,0.2); color:#34d399; margin-bottom:0; flex-shrink:0;">
                <i class="fas fa-lock"></i>
              </div>
              <div>
                <h4 style="margin-bottom:4px;">AES-256 System Encryption</h4>
                <p>Protects candidate data structures at rest and in transit across GCP Cloud SQL structures.</p>
              </div>
            </div>
            
            <div class="pillar-card" style="display:flex; gap:16px; align-items:flex-start;">
              <div class="pillar-icon" style="background:rgba(59,130,246,0.1); border-color:rgba(59,130,246,0.2); color:#60a5fa; margin-bottom:0; flex-shrink:0;">
                <i class="fas fa-user-shield"></i>
              </div>
              <div>
                <h4>Role-Based Gateway Isolation</h4>
                <p>Strict access tokens ensure that users view only data parameters assigned to their verified role.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- System Security Log Console Mockup -->
        <div class="sec-terminal-mock">
          <div class="terminal-title-row">
            <span>🛡 DivineShield Security Terminal</span>
            <span>Active Monitor</span>
          </div>
          
          <div class="terminal-body">
            <div class="terminal-row">
              <span class="terminal-time">[08:42:15]</span>
              <span class="terminal-tag">SYS_OK</span>
              <span class="terminal-msg">Encryption handshake verified (TLS 1.3)</span>
            </div>
            <div class="terminal-row">
              <span class="terminal-time">[09:15:33]</span>
              <span class="terminal-tag">AUTH</span>
              <span class="terminal-msg">Admin login successful (IP: 192.168.1.10)</span>
            </div>
            <div class="terminal-row">
              <span class="terminal-time">[10:04:12]</span>
              <span class="terminal-tag warn">WARN</span>
              <span class="terminal-msg">Attempted login blocked for role staff: inactive</span>
            </div>
            <div class="terminal-row">
              <span class="terminal-time">[11:59:22]</span>
              <span class="terminal-tag">DB_SAVE</span>
              <span class="terminal-msg">Successfully encrypted new submission record (ID: 5)</span>
            </div>
            <div class="terminal-row" style="color:var(--color-primary);">
              <span class="terminal-time">[12:12:00]</span>
              <span class="terminal-tag" style="color:var(--color-secondary);">GCP_SQL</span>
              <span class="terminal-msg" style="color:#c084fc;">Automatic backup compiled &amp; synced</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════════ FOOTER ══ -->
  <footer class="footer-section">
    <div class="section-container">
      <div class="footer-wrapper">
        <div style="display:flex; align-items:center; gap:12px;">
          <div class="logo-badge" style="width:36px; height:36px; font-size:1rem;">
            <i class="fas fa-shield-halved"></i>
          </div>
          <div style="text-align:left;">
            <span class="brand-title" style="font-size:1.1rem; display:block;">DivineShield</span>
            <span class="brand-subtitle" style="font-size:0.65rem;">MAINPI Cloud Operations</span>
          </div>
        </div>
        
        <p class="footer-desc">
          Minister's Association Integrated Nutrition Program Inc. (MAINPI) coordinates meal programs and nutritional assessments for children in local communities throughout the Philippines since 2017.
        </p>
        
        <ul class="footer-menu">
          <li><a href="#home" class="footer-menu-link">Home</a></li>
          <li><a href="#about" class="footer-menu-link">About</a></li>
          <li><a href="#tour" class="footer-menu-link">System Tour</a></li>
          <li><a href="#security" class="footer-menu-link">Security</a></li>
          <li><a href="login.php" class="footer-menu-link" style="color:var(--color-primary); font-weight:700;">Login <i class="fas fa-right-to-bracket"></i></a></li>
        </ul>
        
        <div class="footer-copyright">
          <span>&copy; 2026 DivineShield &mdash; MAINPI. All rights reserved. &middot; Capstone Project</span>
          <div class="footer-cr-links">
            <span style="color:#475569;">Secure</span>
            <span style="color:#475569;">&bull;</span>
            <span style="color:#475569;">Reliable</span>
            <span style="color:#475569;">&bull;</span>
            <span style="color:#475569;">Cloud-Based</span>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <script src="assets/js/main.js?v=4"></script>
</body>
</html>