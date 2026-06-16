<?php
/**
 * DivineShield - Church Leader Portal - Church Site Profile
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'church_leader') {
    header("Location: ../../login.php");
    exit;
}

// ──────────────────────────────────────────
// FETCH CHURCH SITE FOR LOGGED IN LEADER
// ──────────────────────────────────────────
$stmtSite = $pdo->prepare("SELECT * FROM church_sites WHERE church_leader_id = ?");
$stmtSite->execute([$_SESSION['user_id']]);
$mySite = $stmtSite->fetch();
?>
<?php
$pageTitle = "Church Site Profile";
include 'includes/header.php';
?>
                <section class="dashboard-card detail-card">
                    <div class="detail-card-header">
                        <div class="detail-card-title">Feeding Site Profile:
                            <?php echo htmlspecialchars($mySite['church_name'] ?? 'Local Church'); ?></div>
                        <a href="dashboard.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Return</a>
                    </div>

                    <?php if (!$mySite): ?>
                        <div class="empty-state" style="padding: 40px; text-align: center;">
                            <i class="fas fa-question-circle empty-icon"
                                style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
                            <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
                        </div>
                    <?php else: ?>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Feeding Site Name</label>
                                <span><?php echo htmlspecialchars($mySite['church_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Site ID Reference</label>
                                <span>CS-<?php echo str_pad($mySite['id'], 3, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Contact Phone Number</label>
                                <span><?php echo htmlspecialchars($mySite['contact_number'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Pastor / Church Leader</label>
                                <span>Pastor <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                            </div>
                        </div>

                        <div class="detail-grid" style="border-top:1px solid rgba(255,255,255,0.05); padding-top:20px;">
                            <div class="detail-item">
                                <label>Street Address Details</label>
                                <span><?php echo htmlspecialchars($mySite['address']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Barangay</label>
                                <span><?php echo htmlspecialchars($mySite['barangay']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>City / Municipality</label>
                                <span><?php echo htmlspecialchars($mySite['city_municipality']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Province &amp; Region</label>
                                <span><?php echo htmlspecialchars($mySite['province'] . ' &middot; ' . $mySite['region']); ?></span>
                            </div>
                        </div>

                        <div class="dashboard-card"
                            style="margin-top:24px; background:rgba(255,255,255,0.02); border-color:rgba(255,255,255,0.04);">
                            <h4
                                style="font-family:var(--font-head); font-size:0.95rem; font-weight:700; margin-bottom:8px; color:var(--blue-400);">
                                <i class="fas fa-circle-info" style="margin-right:8px;"></i> Profile Read-only Constraints
                            </h4>
                            <p style="font-size:0.8rem; color:var(--gray-400); line-height:1.5;">Feeding site details and
                                region assignments are configured directly by MAINPI administrators during registration
                                approval. If you require contact information updates or street corrections, please launch a
                                support request with your network administrator.</p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php include 'includes/footer.php'; ?>
