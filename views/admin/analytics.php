<?php
/**
 * DivineShield - Analytics Dashboard
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Fetch admin profile picture for topbar
$stmtAdmin = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$adminProfilePic = $stmtAdmin->fetchColumn();

// ──────────────────────────────────────────
// PHP DATA PREPARATION FOR KEY METRICS
// ──────────────────────────────────────────

// 1. Total Registered Children (Active)
$stmtTotalChildren = $pdo->query("SELECT COUNT(*) FROM children WHERE status = 'active'");
$totalChildren = intval($stmtTotalChildren->fetchColumn());

// 2. Submissions Status (Qualified vs Disqualified)
$stmtRatio = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN suggested_status = 'qualified' THEN 1 ELSE 0 END) as qualified,
        SUM(CASE WHEN suggested_status = 'disqualified' THEN 1 ELSE 0 END) as disqualified
    FROM children_submissions
");
$ratioData = $stmtRatio->fetch();
$totalSubmissions = intval($ratioData['total'] ?? 0);
$qualifiedSubmissions = intval($ratioData['qualified'] ?? 0);
$disqualifiedSubmissions = intval($ratioData['disqualified'] ?? 0);

// 3. Total Church Sites
$stmtTotalSites = $pdo->query("SELECT COUNT(*) FROM church_sites");
$totalSites = intval($stmtTotalSites->fetchColumn());

// 4. Pending Submissions Count
$stmtPendingSub = $pdo->query("SELECT COUNT(*) FROM children_submissions WHERE submission_status = 'pending'");
$pendingSubmissions = intval($stmtPendingSub->fetchColumn());

// ──────────────────────────────────────────
// CHART 1: Beneficiaries by Region/Province
// ──────────────────────────────────────────
$stmtRegion = $pdo->query("
    SELECT cs.region, COUNT(c.id) as count 
    FROM children c 
    JOIN church_sites cs ON c.church_site_id = cs.id 
    WHERE c.status = 'active'
    GROUP BY cs.region
    ORDER BY count DESC
    LIMIT 10
");
$regionLabels = [];
$regionCounts = [];
while ($row = $stmtRegion->fetch()) {
    $regionLabels[] = $row['region'];
    $regionCounts[] = intval($row['count']);
}

// ──────────────────────────────────────────
// CHART 2: Current BMI Status Distribution
// ──────────────────────────────────────────
// Combines latest nutritional assessment, falling back to initial bmi status if no assessments yet.
$stmtBMI = $pdo->query("
    SELECT COALESCE(latest_na.bmi_status, sub.initial_bmi_status) as status_name, COUNT(c.id) as count
    FROM children c
    LEFT JOIN children_submissions sub ON c.submission_id = sub.id
    LEFT JOIN (
        SELECT na1.child_id, na1.bmi_status 
        FROM nutritional_assessments na1
        JOIN (
            SELECT child_id, MAX(assessment_date) as max_date, MAX(id) as max_id 
            FROM nutritional_assessments 
            GROUP BY child_id
        ) na2 ON na1.child_id = na2.child_id AND na1.assessment_date = na2.max_date AND na1.id = na2.max_id
    ) latest_na ON c.id = latest_na.child_id
    WHERE c.status = 'active'
    GROUP BY status_name
");
$bmiLabels = [];
$bmiCounts = [];
$bmiColors = [];

// Curated colors for BMI statuses
$bmiColorMap = [
    'Normal' => 'rgb(16, 185, 129)',          // Emerald
    'Normal Weight' => 'rgb(16, 185, 129)',   // Emerald
    'Underweight' => 'rgb(245, 158, 11)',     // Amber
    'Severely Underweight' => 'rgb(239, 68, 68)', // Rose
    'Overweight' => 'rgb(99, 102, 241)',      // Indigo
    'Obese' => 'rgb(139, 92, 246)'            // Violet
];

while ($row = $stmtBMI->fetch()) {
    $statusName = $row['status_name'] ?? 'Not Assessed';
    $bmiLabels[] = $statusName;
    $bmiCounts[] = intval($row['count']);
    $bmiColors[] = $bmiColorMap[$statusName] ?? 'rgb(100, 116, 139)'; // Slate fallback
}

// ──────────────────────────────────────────
// CHART 3: Monthly Submissions Trend (Last 6 Months)
// ──────────────────────────────────────────
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M Y', strtotime("-$i months"));
    $months[$monthKey] = [
        'label' => $monthLabel,
        'count' => 0
    ];
}

$stmtTrend = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month_val, COUNT(*) as count 
    FROM children_submissions 
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY month_val
");
while ($row = $stmtTrend->fetch()) {
    if (isset($months[$row['month_val']])) {
        $months[$row['month_val']]['count'] = intval($row['count']);
    }
}

$trendLabels = [];
$trendCounts = [];
foreach ($months as $m) {
    $trendLabels[] = $m['label'];
    $trendCounts[] = $m['count'];
}

$pageTitle = "System Analytics";
include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  /* Localized Dashboard Styles */
  .analytics-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
  }
  
  .kpi-card {
    background: rgba(30, 41, 59, 0.45);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
  }
  
  .kpi-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 255, 255, 0.15);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
  }
  
  .kpi-icon-wrapper {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
  }
  
  .kpi-details {
    display: flex;
    flex-direction: column;
  }
  
  .kpi-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--white);
    line-height: 1.1;
    font-family: var(--font-head);
  }
  
  .kpi-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--gray-400);
    margin-top: 3px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .kpi-subtext {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 2px;
  }

  .charts-grid-2 {
    display: grid;
    grid-template-columns: 1.8fr 1.2fr;
    gap: 24px;
    margin-bottom: 24px;
  }
  
  .charts-grid-1 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    margin-bottom: 24px;
  }

  @media (max-width: 1024px) {
    .charts-grid-2 {
      grid-template-columns: 1fr;
    }
  }
  
  .chart-card {
    background: rgba(30, 41, 59, 0.45);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 24px;
    display: flex;
    flex-direction: column;
  }
  
  .chart-title {
    font-family: var(--font-head);
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--white);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    padding-bottom: 12px;
  }
  
  .chart-container-inner {
    position: relative;
    width: 100%;
    flex-grow: 1;
    display: flex;
    align-items: center;
    justify-content: center;
  }
</style>

<!-- KPI Cards Roster Grid -->
<div class="analytics-kpi-grid">
  <!-- KPI 1: Active Beneficiaries -->
  <div class="kpi-card">
    <div class="kpi-icon-wrapper" style="background: rgba(59, 130, 246, 0.15); color: var(--blue-400);">
      <i class="fas fa-children"></i>
    </div>
    <div class="kpi-details">
      <span class="kpi-value"><?php echo number_format($totalChildren); ?></span>
      <span class="kpi-label">Active Registry</span>
      <span class="kpi-subtext">Verified Beneficiaries</span>
    </div>
  </div>

  <!-- KPI 2: Submissions Ratio -->
  <div class="kpi-card">
    <div class="kpi-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: var(--teal-400);">
      <i class="fas fa-clipboard-check"></i>
    </div>
    <div class="kpi-details">
      <span class="kpi-value"><?php echo $qualifiedSubmissions; ?> / <?php echo $disqualifiedSubmissions; ?></span>
      <span class="kpi-label">Qualified vs Disq</span>
      <span class="kpi-subtext">From <?php echo number_format($totalSubmissions); ?> Submissions</span>
    </div>
  </div>

  <!-- KPI 3: Church Sites -->
  <div class="kpi-card">
    <div class="kpi-icon-wrapper" style="background: rgba(139, 92, 246, 0.15); color: var(--purple-400);">
      <i class="fas fa-church"></i>
    </div>
    <div class="kpi-details">
      <span class="kpi-value"><?php echo number_format($totalSites); ?></span>
      <span class="kpi-label">Church Sites</span>
      <span class="kpi-subtext">Registered nationwide</span>
    </div>
  </div>

  <!-- KPI 4: Pending Submissions -->
  <div class="kpi-card" <?php echo $pendingSubmissions > 0 ? 'style="border-color: rgba(245, 158, 11, 0.35);"' : ''; ?>>
    <div class="kpi-icon-wrapper" style="background: rgba(245, 158, 11, 0.15); color: var(--yellow-400);">
      <i class="fas fa-clock <?php echo $pendingSubmissions > 0 ? 'fa-spin' : ''; ?>" style="animation-duration: 4s;"></i>
    </div>
    <div class="kpi-details">
      <span class="kpi-value" style="<?php echo $pendingSubmissions > 0 ? 'color: var(--yellow-400); text-shadow: 0 0 10px rgba(245,158,11,0.3);' : ''; ?>"><?php echo number_format($pendingSubmissions); ?></span>
      <span class="kpi-label">Pending Reviews</span>
      <span class="kpi-subtext">Awaiting Staff action</span>
    </div>
  </div>
</div>

<!-- Primary Chart Grid (Bar + Donut) -->
<div class="charts-grid-2">
  <!-- Left Chart: Regional Distribution -->
  <div class="chart-card">
    <div class="chart-title">Regional Beneficiaries Distribution</div>
    <div class="chart-container-inner" style="min-height: 280px;">
      <?php if (empty($regionLabels)): ?>
        <span style="color:var(--gray-500); font-size:0.9rem;">No data registered in regions yet.</span>
      <?php else: ?>
        <canvas id="regionalChart"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Chart: BMI Distribution -->
  <div class="chart-card">
    <div class="chart-title">Nutritional Distribution (BMI)</div>
    <div class="chart-container-inner" style="min-height: 280px; max-height: 280px;">
      <?php if (empty($bmiLabels)): ?>
        <span style="color:var(--gray-500); font-size:0.9rem;">No nutritional assessments completed yet.</span>
      <?php else: ?>
        <canvas id="bmiChart"></canvas>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Secondary Chart Grid (Line Chart Trend) -->
<div class="charts-grid-1">
  <div class="chart-card">
    <div class="chart-title">Child Registrations &amp; Submissions Trend (Last 6 Months)</div>
    <div class="chart-container-inner" style="min-height: 250px;">
      <canvas id="trendChart"></canvas>
    </div>
  </div>
</div>

<script>
  // Chart.js Global Configurations for Premium Slate/Dark styling
  Chart.defaults.color = 'rgb(148, 163, 184)'; // --gray-400
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.95)';
  Chart.defaults.plugins.tooltip.borderColor = 'rgba(255, 255, 255, 0.08)';
  Chart.defaults.plugins.tooltip.borderWidth = 1;
  Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
  Chart.defaults.plugins.tooltip.bodyColor = '#e2e8f0';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 8;
  
  // 1. Regional Distribution Bar Chart
  <?php if (!empty($regionLabels)): ?>
  const regionalCtx = document.getElementById('regionalChart').getContext('2d');
  new Chart(regionalCtx, {
      type: 'bar',
      data: {
          labels: <?php echo json_encode($regionLabels); ?>,
          datasets: [{
              label: 'Active Beneficiaries',
              data: <?php echo json_encode($regionCounts); ?>,
              backgroundColor: 'rgba(59, 130, 246, 0.55)',
              borderColor: 'rgb(59, 130, 246)',
              borderWidth: 1.5,
              borderRadius: 6,
              hoverBackgroundColor: 'rgba(59, 130, 246, 0.75)'
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: { display: false }
          },
          scales: {
              x: {
                  grid: { display: false },
                  ticks: { font: { size: 10 } }
              },
              y: {
                  grid: { color: 'rgba(255, 255, 255, 0.04)' },
                  ticks: { stepSize: 1, beginAtZero: true }
              }
          }
      }
  });
  <?php endif; ?>

  // 2. BMI Distribution Donut Chart
  <?php if (!empty($bmiLabels)): ?>
  const bmiCtx = document.getElementById('bmiChart').getContext('2d');
  new Chart(bmiCtx, {
      type: 'doughnut',
      data: {
          labels: <?php echo json_encode($bmiLabels); ?>,
          datasets: [{
              data: <?php echo json_encode($bmiCounts); ?>,
              backgroundColor: <?php echo json_encode($bmiColors); ?>,
              borderWidth: 2,
              borderColor: 'rgb(15, 23, 42)',
              hoverOffset: 6
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: {
                  position: 'right',
                  labels: {
                      boxWidth: 12,
                      font: { size: 11 },
                      padding: 14
                  }
              }
          },
          cutout: '65%'
      }
  });
  <?php endif; ?>

  // 3. Submissions Trend Line Chart
  const trendCtx = document.getElementById('trendChart').getContext('2d');
  
  // Custom gradient for line fill
  const purpleGradient = trendCtx.createLinearGradient(0, 0, 0, 250);
  purpleGradient.addColorStop(0, 'rgba(139, 92, 246, 0.25)');
  purpleGradient.addColorStop(1, 'rgba(139, 92, 246, 0.00)');

  new Chart(trendCtx, {
      type: 'line',
      data: {
          labels: <?php echo json_encode($trendLabels); ?>,
          datasets: [{
              label: 'Total Submissions Received',
              data: <?php echo json_encode($trendCounts); ?>,
              backgroundColor: purpleGradient,
              borderColor: 'rgb(139, 92, 246)',
              borderWidth: 3,
              fill: true,
              tension: 0.35,
              pointBackgroundColor: 'rgb(139, 92, 246)',
              pointBorderColor: '#ffffff',
              pointHoverRadius: 6,
              pointRadius: 4
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: { display: false }
          },
          scales: {
              x: {
                  grid: { display: false }
              },
              y: {
                  grid: { color: 'rgba(255, 255, 255, 0.04)' },
                  ticks: { stepSize: 1, beginAtZero: true }
              }
          }
      }
  });
</script>

<?php include 'includes/footer.php'; ?>
