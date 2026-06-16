<?php
/**
 * DivineShield - Church Leader Portal - Submit Beneficiary
 */

require_once '../../db.php';
session_start();

// Security and Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'church_leader') {
    header("Location: ../../login.php");
    exit;
}

$success = '';
$error = '';

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// ──────────────────────────────────────────
// FETCH CHURCH SITE FOR LOGGED IN LEADER
// ──────────────────────────────────────────
$stmtSite = $pdo->prepare("SELECT * FROM church_sites WHERE church_leader_id = ?");
$stmtSite->execute([$_SESSION['user_id']]);
$mySite = $stmtSite->fetch();

$church_site_id = $mySite ? $mySite['id'] : 0;

// ──────────────────────────────────────────
// HANDLE SUBMIT BENEFICIARY POST ACTION
// ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_child'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $guardian = trim($_POST['guardian_name'] ?? '');
    $relationship = trim($_POST['guardian_relationship'] ?? '');
    $weight = floatval($_POST['initial_weight'] ?? 0);
    $height = floatval($_POST['initial_height'] ?? 0);

    if (empty($firstName) || empty($lastName) || empty($gender) || empty($birthdate) || empty($guardian) || empty($relationship) || $weight <= 0 || $height <= 0) {
        $error = "All fields marked with an asterisk (*) are required, and Height / Weight must be greater than zero.";
    } else {
        try {
            if ($church_site_id === 0) {
                throw new Exception("Your church site profile could not be found. Please contact an administrator.");
            }

            // Calculate BMI
            $heightInM = $height / 100;
            $bmi = $weight / ($heightInM * $heightInM);
            $bmi = round($bmi, 2);

            // Determine suggested qualification status based on BMI
            if ($bmi < 15.0) {
                $bmiStatus = 'Severely Underweight';
                $suggestedStatus = 'qualified';
            } elseif ($bmi >= 15.0 && $bmi < 16.5) {
                $bmiStatus = 'Underweight';
                $suggestedStatus = 'qualified';
            } elseif ($bmi >= 16.5 && $bmi <= 22.0) {
                $bmiStatus = 'Normal Weight';
                $suggestedStatus = 'disqualified';
            } else {
                $bmiStatus = 'Overweight / Obese';
                $suggestedStatus = 'disqualified';
            }

            // Insert child submission
            $stmtInsert = $pdo->prepare("INSERT INTO children_submissions 
                (church_site_id, church_leader_id, first_name, last_name, middle_name, gender, birthdate, guardian_name, guardian_relationship, initial_weight, initial_height, initial_bmi, initial_bmi_status, suggested_status, submission_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

            $stmtInsert->execute([
                $church_site_id,
                $_SESSION['user_id'],
                $firstName,
                $lastName,
                empty($middleName) ? null : $middleName,
                $gender,
                $birthdate,
                $guardian,
                $relationship,
                $weight,
                $height,
                $bmi,
                $bmiStatus,
                $suggestedStatus
            ]);

            $subId = $pdo->lastInsertId();

            // Log Audit event
            logAudit($pdo, $_SESSION['user_id'], 'CHILD_SUBMITTED', "Pastor submitted beneficiary request: $firstName $lastName (ID: $subId) for Site ID: $church_site_id");

            $_SESSION['success_msg'] = "Child submission for $firstName $lastName has been successfully submitted and queued for review!";
            header("Location: submit-child.php");
            exit;
        } catch (Exception $e) {
            $error = "Failed to submit beneficiary details: " . $e->getMessage();
        }
    }
}
?>
<?php
$pageTitle = "Submit Child";
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

                <section class="dashboard-card detail-card"
                    style="border-color:rgba(59,130,246,0.3); margin-bottom:32px;">
                    <div class="detail-card-header">
                        <div class="detail-card-title">Register Beneficiary
                            Request</div>
                        <a href="dashboard.php" class="btn btn-primary" style="padding: 8px 16px; font-size:0.8rem;"><i
                                class="fas fa-arrow-left"></i> Return</a>
                    </div>

                    <form action="submit-child.php" method="POST" autocomplete="off" style="margin-top:16px;">
                        <input type="hidden" name="submit_child" value="1" />

                        <!-- 3-Column Child Names Grid -->
                        <div class="form-grid-3-resp" style="margin-bottom:20px;">
                            <div class="auth-form-group">
                                <label for="first_name">First Name *</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" id="first_name" name="first_name" class="auth-input"
                                        style="padding-left:16px;" placeholder="e.g. Juan" required />
                                </div>
                            </div>
                            <div class="auth-form-group">
                                <label for="middle_name">Middle Name</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" id="middle_name" name="middle_name" class="auth-input"
                                        style="padding-left:16px;" placeholder="e.g. Santos" />
                                </div>
                            </div>
                            <div class="auth-form-group">
                                <label for="last_name">Last Name *</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" id="last_name" name="last_name" class="auth-input"
                                        style="padding-left:16px;" placeholder="e.g. Dela Cruz" required />
                                </div>
                            </div>
                        </div>

                        <!-- Gender, Birthdate Grid -->
                        <div class="form-grid-2" style="margin-bottom:20px;">
                            <div class="auth-form-group">
                                <label for="gender">Gender *</label>
                                <div class="auth-input-wrapper">
                                    <select id="gender" name="gender" class="auth-input"
                                        style="padding-left:16px; background:#0f172a; border-color:rgba(255,255,255,0.08);"
                                        required>
                                        <option value="" disabled selected>Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="auth-form-group">
                                <label for="birthdate">Birthdate *</label>
                                <div class="auth-input-wrapper">
                                    <input type="date" id="birthdate" name="birthdate" class="auth-input"
                                        style="padding-left:16px;" required />
                                </div>
                            </div>
                        </div>

                        <!-- Guardian Details Grid -->
                        <div class="form-grid-2" style="margin-bottom:20px;">
                            <div class="auth-form-group">
                                <label for="guardian_name">Guardian Name *</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" id="guardian_name" name="guardian_name" class="auth-input"
                                        style="padding-left:16px;" placeholder="e.g. Maria Dela Cruz" required />
                                </div>
                            </div>
                            <div class="auth-form-group">
                                <label for="guardian_relationship">Relationship to Child *</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" id="guardian_relationship" name="guardian_relationship"
                                        class="auth-input" style="padding-left:16px;"
                                        placeholder="e.g. Mother, Father, Grandmother" required />
                                </div>
                            </div>
                        </div>

                        <!-- Height & Weight Metrics Grid -->
                        <div class="form-grid-2" style="margin-bottom:20px;">
                            <div class="auth-form-group">
                                <label for="initial_height">Height (in centimeters) *</label>
                                <div class="auth-input-wrapper">
                                    <input type="number" step="0.1" id="initial_height" name="initial_height"
                                        class="auth-input" style="padding-left:16px;" placeholder="e.g. 110.5"
                                        required />
                                </div>
                            </div>
                            <div class="auth-form-group">
                                <label for="initial_weight">Weight (in kilograms) *</label>
                                <div class="auth-input-wrapper">
                                    <input type="number" step="0.1" id="initial_weight" name="initial_weight"
                                        class="auth-input" style="padding-left:16px;" placeholder="e.g. 18.2"
                                        required />
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Live Auto-BMI Card -->
                        <div class="dashboard-card" id="live-bmi-card"
                            style="display:none; background:rgba(30, 41, 59, 0.4); border-color:rgba(59, 130, 246, 0.2); margin: 24px 0;">
                            <h4
                                style="font-family:var(--font-head); font-size:0.9rem; text-transform:uppercase; color:var(--blue-400); margin-bottom:14px; font-weight:700;">
                                <i class="fas fa-calculator" style="margin-right:8px;"></i> Live Auto-BMI Assessment
                            </h4>

                            <div class="detail-grid" style="margin-bottom:0; gap:16px;">
                                <div class="detail-item">
                                    <label>Calculated BMI</label>
                                    <span id="bmi_live_val" style="font-size:1.3rem; font-weight:800;">0.00</span>
                                </div>
                                <div class="detail-item">
                                    <label>BMI Nutritional Classification</label>
                                    <span id="bmi_status_live_val" style="font-weight:700;">Normal</span>
                                </div>
                                <div class="detail-item">
                                    <label>Suggested System Status</label>
                                    <span id="suggested_badge" class="badge">
                                        <span id="suggested_status_live_val"
                                            style="font-weight:700; text-transform:uppercase;">TBD</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"
                            style="padding:12px 28px; width:100%; justify-content:center; background:var(--blue-600);"><i
                                class="fas fa-paper-plane"></i> Submit Request to Registry</button>
                    </form>
                </section>
            </div>
        </main>
    </div>

    <script>
        const weightInput = document.getElementById('initial_weight');
        const heightInput = document.getElementById('initial_height');
        const bmiOutput = document.getElementById('bmi_live_val');
        const bmiStatusOutput = document.getElementById('bmi_status_live_val');
        const suggestedStatusOutput = document.getElementById('suggested_status_live_val');
        const suggestedBadge = document.getElementById('suggested_badge');

        function calculateLiveBMI() {
            const w = parseFloat(weightInput.value);
            const h = parseFloat(heightInput.value);

            if (w > 0 && h > 0) {
                const heightInM = h / 100;
                const bmi = w / (heightInM * heightInM);
                const bmiFixed = bmi.toFixed(2);

                bmiOutput.textContent = bmiFixed;

                let status = '';
                let suggested = '';
                let badgeClass = '';

                if (bmi < 15.0) {
                    status = 'Severely Underweight';
                    suggested = 'Qualified';
                    badgeClass = 'badge-success';
                } else if (bmi >= 15.0 && bmi < 16.5) {
                    status = 'Underweight';
                    suggested = 'Qualified';
                    badgeClass = 'badge-success';
                } else if (bmi >= 16.5 && bmi <= 22.0) {
                    status = 'Normal Weight';
                    suggested = 'Disqualified';
                    badgeClass = 'badge-danger';
                } else {
                    status = 'Overweight / Obese';
                    suggested = 'Disqualified';
                    badgeClass = 'badge-danger';
                }

                bmiStatusOutput.textContent = status;
                suggestedStatusOutput.textContent = suggested;
                suggestedBadge.className = 'badge ' + badgeClass;

                document.getElementById('live-bmi-card').style.display = 'block';
            } else {
                document.getElementById('live-bmi-card').style.display = 'none';
            }
        }

        if (weightInput && heightInput) {
            weightInput.addEventListener('input', calculateLiveBMI);
            heightInput.addEventListener('input', calculateLiveBMI);
        }
    </script>
</body>

</html>