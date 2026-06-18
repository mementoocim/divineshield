<?php
/**
 * DivineShield - Administrator MannaPack Rice Inventory & Distribution
 */

require_once '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// ─── AJAX: Site child stats ────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'site_stats' && isset($_GET['site_id'])) {
    header('Content-Type: application/json');
    $siteId = intval($_GET['site_id']);
    $stmt = $pdo->prepare("
        SELECT
            COUNT(c.id)                                                              AS total,
            SUM(CASE WHEN cs.suggested_status = 'qualified'    THEN 1 ELSE 0 END)   AS qualified,
            SUM(CASE WHEN cs.suggested_status = 'disqualified' THEN 1 ELSE 0 END)   AS disqualified
        FROM children c
        LEFT JOIN children_submissions cs ON c.submission_id = cs.id
        WHERE c.church_site_id = ? AND c.status = 'active'
    ");
    $stmt->execute([$siteId]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

// ─── Stock calculation (derived, no single-row table needed) ───────────────
$totalReceived    = (int)$pdo->query("SELECT COALESCE(SUM(quantity_added),   0) FROM manna_restock_log")    ->fetchColumn();
$totalDistributed = (int)$pdo->query("SELECT COALESCE(SUM(packs_distributed),0) FROM manna_distribution_log")->fetchColumn();
$currentStock     = $totalReceived - $totalDistributed;

$success = '';
$error   = '';

// ─── POST: Restock ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restock') {
    $donorName  = trim($_POST['donor_name']     ?? '');
    $quantity   = intval($_POST['quantity_added'] ?? 0);
    $notes      = trim($_POST['notes']           ?? '');
    $receivedAt = $_POST['received_at']          ?? date('Y-m-d H:i:s');

    if (empty($donorName) || $quantity <= 0) {
        $error = "Donor name and a valid quantity (greater than 0) are required.";
    } else {
        try {
            $pdo->prepare("INSERT INTO manna_restock_log (added_by, donor_name, quantity_added, notes, received_at) VALUES (?, ?, ?, ?, ?)")
                ->execute([$_SESSION['user_id'], $donorName, $quantity, empty($notes) ? null : $notes, $receivedAt]);

            logAudit($pdo, $_SESSION['user_id'], 'MANNA_RESTOCK',
                "Added {$quantity} MannaPack pack(s) from donor: \"{$donorName}\"");

            $totalReceived += $quantity;
            $currentStock  += $quantity;
            $success = "Successfully added {$quantity} MannaPack pack(s) from donor \"{$donorName}\". Current stock: {$currentStock}.";
        } catch (Exception $e) {
            $error = "Failed to add stock: " . $e->getMessage();
        }
    }
}

// ─── POST: Distribute ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'distribute') {
    $siteId = intval($_POST['church_site_id']   ?? 0);
    $packs  = intval($_POST['packs_distributed'] ?? 0);
    $notes  = trim($_POST['notes']               ?? '');

    if ($siteId <= 0 || $packs <= 0) {
        $error = "Please select a church site and enter a valid quantity greater than 0.";
    } elseif ($packs > $currentStock) {
        $error = "Insufficient stock. You tried to distribute {$packs} pack(s) but only {$currentStock} are available.";
    } else {
        try {
            // snapshot child counts at time of distribution
            $stmtStats = $pdo->prepare("
                SELECT
                    COUNT(c.id)                                                              AS total,
                    SUM(CASE WHEN cs.suggested_status = 'qualified'    THEN 1 ELSE 0 END)   AS qualified,
                    SUM(CASE WHEN cs.suggested_status = 'disqualified' THEN 1 ELSE 0 END)   AS disqualified
                FROM children c
                LEFT JOIN children_submissions cs ON c.submission_id = cs.id
                WHERE c.church_site_id = ? AND c.status = 'active'
            ");
            $stmtStats->execute([$siteId]);
            $snap = $stmtStats->fetch(PDO::FETCH_ASSOC);

            $stockBefore = $currentStock;
            $stockAfter  = $currentStock - $packs;

            $pdo->prepare("INSERT INTO manna_distribution_log
                (church_site_id, distributed_by, total_children, qualified_children_count,
                 disqualified_children_count, packs_distributed, stock_before, stock_after, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([
                    $siteId, $_SESSION['user_id'],
                    intval($snap['total']), intval($snap['qualified']), intval($snap['disqualified']),
                    $packs, $stockBefore, $stockAfter,
                    empty($notes) ? null : $notes
                ]);

            $siteInfo = $pdo->prepare("SELECT church_name, barangay FROM church_sites WHERE id = ?");
            $siteInfo->execute([$siteId]);
            $site = $siteInfo->fetch(PDO::FETCH_ASSOC);

            logAudit($pdo, $_SESSION['user_id'], 'MANNA_DISTRIBUTED',
                "Distributed {$packs} MannaPack pack(s) to {$site['church_name']} (Brgy. {$site['barangay']}). Stock: {$stockBefore} → {$stockAfter}");

            $currentStock      = $stockAfter;
            $totalDistributed += $packs;
            $success = "Successfully distributed {$packs} MannaPack pack(s) to {$site['church_name']}. Remaining stock: {$currentStock}.";
        } catch (Exception $e) {
            $error = "Failed to record distribution: " . $e->getMessage();
        }
    }
}

// ─── Page data ─────────────────────────────────────────────────────────────
$churchSites = $pdo->query("SELECT id, church_name, barangay FROM church_sites ORDER BY church_name ASC")
                    ->fetchAll(PDO::FETCH_ASSOC);


$activeTab = $_GET['tab'] ?? 'distribution';

$distributionLog = $pdo->query("
    SELECT dl.*, cs.church_name, cs.barangay, u.first_name, u.last_name
    FROM manna_distribution_log dl
    JOIN church_sites cs ON dl.church_site_id = cs.id
    JOIN users u ON dl.distributed_by = u.id
    ORDER BY dl.distributed_at DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$restockLog = $pdo->query("
    SELECT rl.*, u.first_name, u.last_name
    FROM manna_restock_log rl
    JOIN users u ON rl.added_by = u.id
    ORDER BY rl.received_at DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "MannaPack Inventory";
include 'includes/header.php';
?>

<?php if (!empty($success)): ?>
    <div class="auth-alert auth-alert-success" style="margin-bottom:24px;">
        <i class="fas fa-circle-check"></i>
        <div><strong>Success</strong> <span><?php echo htmlspecialchars($success); ?></span></div>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="auth-alert auth-alert-danger" style="margin-bottom:24px;">
        <i class="fas fa-circle-exclamation"></i>
        <div><strong>Error</strong> <span><?php echo htmlspecialchars($error); ?></span></div>
    </div>
<?php endif; ?>

<!-- ── Stats Row ─────────────────────────────────────────────────────────── -->
<section class="stats-grid" style="margin-bottom:28px;">

    <div class="stat-box" style="<?php echo $currentStock <= 20 && $currentStock > 0 ? 'border-color:rgba(245,158,11,0.35); background:rgba(245,158,11,0.04);' : ($currentStock == 0 ? 'border-color:rgba(239,68,68,0.35); background:rgba(239,68,68,0.04);' : ''); ?>">
        <div class="stat-box-info">
            <h4>Current Stock</h4>
            <div class="stat-val" style="<?php echo $currentStock == 0 ? 'color:var(--red-400);' : ($currentStock <= 20 ? 'color:var(--yellow-400);' : ''); ?>"><?php echo number_format($currentStock); ?></div>
            <div style="font-size:0.72rem; color:var(--gray-500); margin-top:2px;">packs available</div>
        </div>
        <div class="stat-box-icon" style="<?php echo $currentStock == 0 ? 'color:var(--red-400); background:rgba(239,68,68,0.1);' : ($currentStock <= 20 ? 'color:var(--yellow-400); background:rgba(245,158,11,0.1);' : ''); ?>">
            <i class="fas fa-boxes-stacked"></i>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-info">
            <h4>Total Received</h4>
            <div class="stat-val"><?php echo number_format($totalReceived); ?></div>
            <div style="font-size:0.72rem; color:var(--gray-500); margin-top:2px;">packs all-time</div>
        </div>
        <div class="stat-box-icon" style="color:var(--teal-400); background:rgba(20,184,166,0.1);">
            <i class="fas fa-circle-arrow-down"></i>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-info">
            <h4>Total Distributed</h4>
            <div class="stat-val"><?php echo number_format($totalDistributed); ?></div>
            <div style="font-size:0.72rem; color:var(--gray-500); margin-top:2px;">packs all-time</div>
        </div>
        <div class="stat-box-icon" style="color:var(--blue-400); background:rgba(59,130,246,0.1);">
            <i class="fas fa-circle-arrow-up"></i>
        </div>
    </div>


</section>

<!-- ── Action Buttons Row ───────────────────────────────────────────────── -->
<section class="dashboard-card" style="margin-bottom:28px; padding:20px 28px;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
        <div>
            <div style="font-weight:700; color:var(--white); font-size:0.95rem;">Inventory Actions</div>
            <div style="font-size:0.8rem; color:var(--gray-400); margin-top:2px;">Record donor restocks and barangay distributions</div>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button id="btnOpenRestock" type="button" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Stock from Donor
            </button>
            <button id="btnOpenDistribute" type="button" class="btn btn-primary">
                <i class="fas fa-truck-ramp-box"></i> Distribute MannaPack
            </button>
        </div>
    </div>
</section>

<!-- ── Restock Modal ─────────────────────────────────────────────────────── -->
<div id="restockModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.65); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:20px;">
    <div style="background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:20px; width:100%; max-width:520px; box-shadow:0 25px 60px rgba(0,0,0,0.5); animation:modalSlideIn 0.22s ease;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:22px 28px; border-bottom:1px solid rgba(255,255,255,0.07);">
            <div>
                <div style="font-family:var(--font-head); font-size:1.05rem; font-weight:700; color:var(--white);">Add Stock from Donor</div>
                <div style="font-size:0.78rem; color:var(--gray-400); margin-top:2px;">Record MannaPack packs received</div>
            </div>
            <button type="button" id="btnCloseRestock" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:24px 28px;">
            <form method="POST" action="manna_inventory.php" autocomplete="off" id="restockForm">
                <input type="hidden" name="action" value="restock" />

                <div class="auth-form-group" style="margin-bottom:16px;">
                    <label for="donor_name">Donor Name *</label>
                    <div class="auth-input-wrapper">
                        <input type="text" id="donor_name" name="donor_name" class="auth-input"
                            style="padding-left:16px;" placeholder="e.g. Gawad Kalinga Foundation" required />
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div class="auth-form-group">
                        <label for="quantity_added">Quantity Received *</label>
                        <div class="auth-input-wrapper">
                            <input type="number" id="quantity_added" name="quantity_added" class="auth-input"
                                style="padding-left:16px;" placeholder="e.g. 100" min="1" required />
                        </div>
                    </div>
                    <div class="auth-form-group">
                        <label for="received_at">Date Received *</label>
                        <div class="auth-input-wrapper">
                            <input type="date" id="received_at" name="received_at" class="auth-input"
                                style="padding-left:16px;" value="<?php echo date('Y-m-d'); ?>" required />
                        </div>
                    </div>
                </div>

                <div class="auth-form-group" style="margin-bottom:24px;">
                    <label for="restock_notes">Notes <span style="color:var(--gray-500); font-weight:400;">(optional)</span></label>
                    <div class="auth-input-wrapper">
                        <input type="text" id="restock_notes" name="notes" class="auth-input"
                            style="padding-left:16px;" placeholder="e.g. 2nd batch for June 2026" />
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-success" style="flex:1; justify-content:center; padding:11px;">
                        <i class="fas fa-plus"></i> Add to Stock
                    </button>
                    <button type="button" id="btnCancelRestock" class="btn btn-outline" style="padding:11px 20px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Distribute Modal ──────────────────────────────────────────────────── -->
<div id="distributeModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.65); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:20px;">
    <div style="background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:20px; width:100%; max-width:560px; box-shadow:0 25px 60px rgba(0,0,0,0.5); animation:modalSlideIn 0.22s ease;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:22px 28px; border-bottom:1px solid rgba(255,255,255,0.07);">
            <div>
                <div style="font-family:var(--font-head); font-size:1.05rem; font-weight:700; color:var(--white);">Distribute MannaPack</div>
                <div style="font-size:0.78rem; color:var(--gray-400); margin-top:2px;">
                    Available stock: <strong style="color:<?php echo $currentStock <= 20 ? 'var(--yellow-400)' : 'var(--teal-400)'; ?>"><?php echo number_format($currentStock); ?> packs</strong>
                </div>
            </div>
            <button type="button" id="btnCloseDistribute" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:24px 28px;">
            <form method="POST" action="manna_inventory.php" autocomplete="off" id="distributeForm">
                <input type="hidden" name="action" value="distribute" />

                <?php if ($currentStock <= 0): ?>
                    <div class="auth-alert auth-alert-danger" style="margin-bottom:16px; padding:10px 14px;">
                        <i class="fas fa-circle-exclamation" style="margin-right:8px; color:var(--red-400);"></i>
                        <div style="font-size:0.8rem;">
                            <strong>No Stock Available:</strong> Distribution is disabled until stock is replenished.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="auth-form-group" style="margin-bottom:16px;">
                    <label for="church_site_id">Church Site / Barangay *</label>
                    <div class="auth-input-wrapper">
                        <select id="church_site_id" name="church_site_id" class="auth-input"
                            style="padding-left:16px; background:#0f172a; border-color:rgba(255,255,255,0.08);" required>
                            <option value="" disabled selected>— Select a site —</option>
                            <?php foreach ($churchSites as $site): ?>
                                <option value="<?php echo $site['id']; ?>">
                                    <?php echo htmlspecialchars($site['church_name']); ?> — Brgy. <?php echo htmlspecialchars($site['barangay']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Reference counts — loads via JS -->
                <div id="siteStatsBox" style="display:none; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:14px 18px; margin-bottom:16px;">
                    <div style="font-size:0.72rem; text-transform:uppercase; color:var(--gray-400); font-weight:700; letter-spacing:0.05em; margin-bottom:10px;">
                        Site Reference Counts (Active Children)
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; text-align:center;">
                        <div>
                            <div style="font-size:1.4rem; font-weight:800; color:var(--white);" id="refTotal">—</div>
                            <div style="font-size:0.72rem; color:var(--gray-400);">Total</div>
                        </div>
                        <div>
                            <div style="font-size:1.4rem; font-weight:800; color:#86efac;" id="refQualified">—</div>
                            <div style="font-size:0.72rem; color:var(--gray-400);">Qualified</div>
                        </div>
                        <div>
                            <div style="font-size:1.4rem; font-weight:800; color:#fca5a5;" id="refDisqualified">—</div>
                            <div style="font-size:0.72rem; color:var(--gray-400);">Disqualified</div>
                        </div>
                    </div>
                    <div style="margin-top:10px; font-size:0.7rem; color:var(--gray-500); font-style:italic; text-align:center;">
                        For reference only — you may distribute any quantity
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div class="auth-form-group">
                        <label for="packs_distributed">Packs to Distribute *</label>
                        <div class="auth-input-wrapper">
                            <input type="number" id="packs_distributed" name="packs_distributed" class="auth-input"
                                style="padding-left:16px;" placeholder="Enter quantity" min="1"
                                max="<?php echo $currentStock; ?>" required />
                        </div>
                    </div>
                    <div style="display:flex; align-items:flex-end; padding-bottom:2px;">
                        <div style="width:100%; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:10px 14px; text-align:center;">
                            <div style="font-size:0.68rem; color:var(--gray-400); text-transform:uppercase; font-weight:700;">Available Stock</div>
                            <div style="font-size:1.3rem; font-weight:800; color:<?php echo $currentStock <= 20 ? 'var(--yellow-400)' : 'var(--white)'; ?>;">
                                <?php echo number_format($currentStock); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="auth-form-group" style="margin-bottom:24px;">
                    <label for="dist_notes">Notes <span style="color:var(--gray-500); font-weight:400;">(optional)</span></label>
                    <div class="auth-input-wrapper">
                        <input type="text" id="dist_notes" name="notes" class="auth-input"
                            style="padding-left:16px;" placeholder="e.g. June 2026 distribution cycle" />
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center; padding:11px;"
                        <?php echo $currentStock <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-truck-ramp-box"></i>
                        <?php echo $currentStock <= 0 ? 'No Stock Available' : 'Record Distribution'; ?>
                    </button>
                    <button type="button" id="btnCancelDistribute" class="btn btn-outline" style="padding:11px 20px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ── Transaction History ───────────────────────────────────────────────── -->
<div class="pill-tabs" style="margin-bottom:20px;">
    <a href="manna_inventory.php?tab=distribution" class="pill-tab <?php echo $activeTab === 'distribution' ? 'active' : ''; ?>" style="text-decoration:none;">
        <i class="fas fa-truck-ramp-box"></i> Distribution Log
        <?php if (count($distributionLog) > 0): ?>
            <span style="background:rgba(59,130,246,0.2); color:var(--blue-400); padding:1px 7px; border-radius:999px; font-size:0.7rem; margin-left:4px; font-weight:700;"><?php echo count($distributionLog); ?></span>
        <?php endif; ?>
    </a>
    <a href="manna_inventory.php?tab=restock" class="pill-tab <?php echo $activeTab === 'restock' ? 'active' : ''; ?>" style="text-decoration:none;">
        <i class="fas fa-circle-arrow-down"></i> Restock Log
        <?php if (count($restockLog) > 0): ?>
            <span style="background:rgba(20,184,166,0.2); color:var(--teal-400); padding:1px 7px; border-radius:999px; font-size:0.7rem; margin-left:4px; font-weight:700;"><?php echo count($restockLog); ?></span>
        <?php endif; ?>
    </a>
</div>

<?php if ($activeTab === 'distribution'): ?>
<!-- Distribution Log Table -->
<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">Distribution Transaction History</div>
        <span style="font-size:0.75rem; background:rgba(255,255,255,0.05); padding:4px 10px; border-radius:999px; color:var(--gray-400);">
            <?php echo count($distributionLog); ?> record(s)
        </span>
    </div>
    <div class="panel-body" style="padding:0;">
        <?php if (count($distributionLog) > 0): ?>
            <div class="dark-table-wrap">
                <table class="dark-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Site / Barangay</th>
                            <th style="text-align:center;">Total</th>
                            <th style="text-align:center;">Qualified</th>
                            <th style="text-align:center;">DQ</th>
                            <th style="text-align:center;">Packs Given</th>
                            <th style="text-align:center;">Stock Before</th>
                            <th style="text-align:center;">Stock After</th>
                            <th>Recorded By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($distributionLog as $log): ?>
                        <tr>
                            <td class="fw-semibold text-white" style="white-space:nowrap;">
                                <i class="fas fa-calendar-day" style="color:var(--blue-400); margin-right:5px; font-size:0.8rem;"></i>
                                <?php echo date('M d, Y', strtotime($log['distributed_at'])); ?>
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--white); line-height:1.3;"><?php echo htmlspecialchars($log['church_name']); ?></div>
                                <div style="font-size:0.72rem; color:var(--gray-500);">Brgy. <?php echo htmlspecialchars($log['barangay']); ?></div>
                            </td>
                            <td style="text-align:center; color:var(--gray-300);"><?php echo $log['total_children']; ?></td>
                            <td style="text-align:center;">
                                <span style="color:#86efac; font-weight:700;"><?php echo $log['qualified_children_count']; ?></span>
                            </td>
                            <td style="text-align:center;">
                                <span style="color:#fca5a5; font-weight:700;"><?php echo $log['disqualified_children_count']; ?></span>
                            </td>
                            <td style="text-align:center;">
                                <span style="background:rgba(59,130,246,0.15); color:var(--blue-400); padding:3px 10px; border-radius:999px; font-weight:700; font-size:0.9rem;">
                                    <?php echo $log['packs_distributed']; ?>
                                </span>
                            </td>
                            <td style="text-align:center; color:var(--gray-400);"><?php echo number_format($log['stock_before']); ?></td>
                            <td style="text-align:center;">
                                <span style="color:<?php echo $log['stock_after'] <= 20 ? 'var(--yellow-400)' : 'var(--white)'; ?>; font-weight:600;">
                                    <?php echo number_format($log['stock_after']); ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem;">
                                <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                            </td>
                            <td class="text-muted" style="font-size:0.8rem; max-width:160px;">
                                <?php echo htmlspecialchars($log['notes'] ?? '—'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:60px; text-align:center;">
                <i class="fas fa-truck-ramp-box empty-icon" style="font-size:3rem; color:var(--gray-500); margin-bottom:16px;"></i>
                <h4 style="color:var(--white); margin-bottom:8px;">No Distributions Yet</h4>
                <p style="color:var(--gray-400);">Use the distribute form above to record your first MannaPack distribution.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Restock Log Table -->
<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">Donor Restock History</div>
        <span style="font-size:0.75rem; background:rgba(255,255,255,0.05); padding:4px 10px; border-radius:999px; color:var(--gray-400);">
            <?php echo count($restockLog); ?> record(s)
        </span>
    </div>
    <div class="panel-body" style="padding:0;">
        <?php if (count($restockLog) > 0): ?>
            <div class="dark-table-wrap">
                <table class="dark-table">
                    <thead>
                        <tr>
                            <th>Date Received</th>
                            <th>Donor Name</th>
                            <th style="text-align:center;">Packs Added</th>
                            <th>Notes</th>
                            <th>Added By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($restockLog as $log): ?>
                        <tr>
                            <td class="fw-semibold text-white" style="white-space:nowrap;">
                                <i class="fas fa-calendar-day" style="color:var(--teal-400); margin-right:5px; font-size:0.8rem;"></i>
                                <?php echo date('M d, Y', strtotime($log['received_at'])); ?>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:30px; height:30px; border-radius:50%; background:rgba(192,132,252,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i class="fas fa-hand-holding-heart" style="color:#c084fc; font-size:0.75rem;"></i>
                                    </div>
                                    <span style="font-weight:600; color:var(--white);"><?php echo htmlspecialchars($log['donor_name']); ?></span>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span style="background:rgba(20,184,166,0.15); color:var(--teal-400); padding:3px 10px; border-radius:999px; font-weight:700; font-size:0.9rem;">
                                    +<?php echo number_format($log['quantity_added']); ?>
                                </span>
                            </td>
                            <td class="text-muted" style="font-size:0.8rem; max-width:200px;">
                                <?php echo htmlspecialchars($log['notes'] ?? '—'); ?>
                            </td>
                            <td style="font-size:0.82rem;">
                                <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:60px; text-align:center;">
                <i class="fas fa-hand-holding-heart empty-icon" style="font-size:3rem; color:var(--gray-500); margin-bottom:16px;"></i>
                <h4 style="color:var(--white); margin-bottom:8px;">No Restock Records Yet</h4>
                <p style="color:var(--gray-400);">Use the "Add Stock from Donor" form above to record your first MannaPack receipt.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>
@keyframes modalSlideIn {
    from { opacity:0; transform:translateY(-14px) scale(0.97); }
    to   { opacity:1; transform:translateY(0)     scale(1);    }
}
</style>
<script>
// ── Modal helpers ─────────────────────────────────────────────────────────
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
}

document.addEventListener('DOMContentLoaded', function () {

    // Button triggers
    document.getElementById('btnOpenRestock')    ?.addEventListener('click', () => openModal('restockModal'));
    document.getElementById('btnOpenDistribute') ?.addEventListener('click', () => openModal('distributeModal'));
    document.getElementById('btnCloseRestock')   ?.addEventListener('click', () => closeModal('restockModal'));
    document.getElementById('btnCancelRestock')  ?.addEventListener('click', () => closeModal('restockModal'));
    document.getElementById('btnCloseDistribute')?.addEventListener('click', () => closeModal('distributeModal'));
    document.getElementById('btnCancelDistribute')?.addEventListener('click', () => closeModal('distributeModal'));

    // Close on backdrop click
    ['restockModal', 'distributeModal'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function (e) {
            if (e.target === this) closeModal(id);
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal('restockModal');
            closeModal('distributeModal');
        }
    });

    // Auto-reopen modal if POST returned an error
    const autoOpen = <?php echo json_encode(!empty($error) && !empty($_POST['action']) ? $_POST['action'] : ''); ?>;
    if (autoOpen === 'restock')    openModal('restockModal');
    if (autoOpen === 'distribute') openModal('distributeModal');

    // ── AJAX: site reference counts ───────────────────────────────────────
    const siteSelect      = document.getElementById('church_site_id');
    const statsBox        = document.getElementById('siteStatsBox');
    const refTotal        = document.getElementById('refTotal');
    const refQualified    = document.getElementById('refQualified');
    const refDisqualified = document.getElementById('refDisqualified');
    const packsInput      = document.getElementById('packs_distributed');

    if (siteSelect) {
        siteSelect.addEventListener('change', function () {
            const siteId = this.value;
            if (!siteId) { statsBox.style.display = 'none'; return; }

            statsBox.style.display = 'block';
            refTotal.textContent = '…';
            refQualified.textContent = '…';
            refDisqualified.textContent = '…';

            fetch(`manna_inventory.php?ajax=site_stats&site_id=${siteId}`)
                .then(r => r.json())
                .then(data => {
                    refTotal.textContent        = data.total        ?? 0;
                    refQualified.textContent    = data.qualified    ?? 0;
                    refDisqualified.textContent = data.disqualified ?? 0;
                    if (packsInput && !packsInput.value) {
                        packsInput.value = data.qualified ?? '';
                    }
                })
                .catch(() => {
                    refTotal.textContent        = '—';
                    refQualified.textContent    = '—';
                    refDisqualified.textContent = '—';
                });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
