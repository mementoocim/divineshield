<?php
/**
 * DivineShield - Church Leader Wizard Registration with Dynamic Locations
 */

require_once 'db.php';
require_once 'config/email_helper.php';
session_start();

// Redirect if logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: views/admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'staff') {
        header("Location: views/staff/dashboard.php");
    } elseif ($_SESSION['role'] === 'church_leader') {
        header("Location: views/church/dashboard.php");
    }
    exit;
}
$allowReg = getSystemConfig($pdo, 'allow_public_registration', '1');
if ($allowReg !== '1') {
    $_SESSION['qr_notice'] = 'Public registration is currently disabled by the administrator.';
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$startStep = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startStep = 3;

    $churchName    = trim($_POST['church_name'] ?? '');
    $positionTitle = trim($_POST['position_title'] ?? '');
    $streetAddress = trim($_POST['street_address'] ?? '');
    $region        = trim($_POST['region'] ?? '');
    $province      = trim($_POST['province'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $barangay      = trim($_POST['barangay'] ?? '');

    $firstName    = trim($_POST['first_name'] ?? '');
    $middleName   = trim($_POST['middle_name'] ?? '');
    $lastName     = trim($_POST['last_name'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $adminMessage = trim($_POST['admin_message'] ?? '');

    $username        = trim($_POST['username'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($churchName) || empty($positionTitle) || empty($streetAddress) || empty($region) || empty($province) || empty($city) || empty($barangay) ||
        empty($firstName) || empty($lastName) || empty($phone) || empty($email) || empty($username) || empty($password)) {
        $error = 'All required fields must be filled. Make sure Church Name, Street Address, Region, Province, City/Municipality, and Barangay are specified.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Enforce password complexity policies dynamically
        $minLen = (int)getSystemConfig($pdo, 'pw_min_length', '8');
        $reqNum = getSystemConfig($pdo, 'pw_req_number', '1') === '1';
        $reqSpec = getSystemConfig($pdo, 'pw_req_special', '1') === '1';
        $reqCase = getSystemConfig($pdo, 'pw_req_case', '1') === '1';

        if (strlen($password) < $minLen) {
            $error = "Password must be at least $minLen characters long.";
        } elseif ($reqNum && !preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';
        } elseif ($reqCase && (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password))) {
            $error = 'Password must contain both uppercase and lowercase letters.';
        } elseif ($reqSpec && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $error = 'Password must contain at least one special character.';
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username is already taken.';
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email is already registered.';
                }
            }

            if (empty($error)) {
                $pdo->beginTransaction();

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $requireApproval = getSystemConfig($pdo, 'require_admin_approval', '1');
                $initialStatus = ($requireApproval === '1') ? 'pending' : 'active';

                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, position_title, first_name, middle_name, last_name, email, phone, admin_message, status) VALUES (?, ?, 'church_leader', ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $passwordHash, $positionTitle, $firstName, empty($middleName) ? null : $middleName, $lastName, $email, $phone, $adminMessage, $initialStatus]);

                $leaderId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO church_sites (church_leader_id, church_name, address, region, province, city_municipality, barangay, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$leaderId, $churchName, $streetAddress, $region, $province, $city, $barangay, $phone]);

                $leaderName = trim($firstName . ' ' . $lastName);
                logAudit($pdo, $leaderId, 'USER_REGISTER', "Pastor $leaderName registered church site: $churchName (" . ($initialStatus === 'active' ? 'Automatically approved' : 'Pending approval') . ")");

                $pdo->commit();

                // Notify admin of the new registration
                sendAdminNewRegistrationEmail(
                    $firstName,
                    $lastName,
                    $email,
                    $phone,
                    $positionTitle,
                    $username,
                    $churchName,
                    $streetAddress,
                    $region,
                    $city,
                    $barangay,
                    $adminMessage
                );

                if ($initialStatus === 'active') {
                    $success = 'Your registration was successful and approved automatically! You can now log in.';
                } else {
                    $success = 'Your registration was submitted successfully! Please wait for administrator approval.';
                }
                $startStep = 1;
                $_POST = [];
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/images/mainpi-logo.png" />
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Account – DivineShield</title>
  <link rel="stylesheet" href="assets/css/style.css?v=7" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
</head>
<body class="auth-body">

  <div class="auth-wrapper">
    <div class="auth-container">
      
      <div class="auth-header">
        <a href="index.php">
          <img src="assets/images/mainpi-logo.png?v=3" alt="MAINPI Logo" class="auth-logo" />
        </a>
        <h1>DivineShield</h1>
        <p>MAINPI Cloud System – Registration</p>
      </div>

      <!-- Stepper Wizard Header -->
      <div class="stepper-header">
        <div class="stepper-line">
          <div class="stepper-line-fill" id="stepperLineFill"></div>
        </div>
        <div class="stepper-step active" id="stepIndicator1">
          <div class="stepper-circle">1</div>
          <span class="stepper-label">Ministry Info</span>
        </div>
        <div class="stepper-step" id="stepIndicator2">
          <div class="stepper-circle">2</div>
          <span class="stepper-label">Personal Details</span>
        </div>
        <div class="stepper-step" id="stepIndicator3">
          <div class="stepper-circle">3</div>
          <span class="stepper-label">Account Setup</span>
        </div>
      </div>

      <div class="auth-card">
        
        <?php if (!empty($error)): ?>
          <div class="auth-alert auth-alert-danger" id="errorAlert">
            <i class="fas fa-circle-exclamation"></i>
            <div><strong>Registration Error</strong> <span><?php echo htmlspecialchars($error); ?></span></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="auth-alert auth-alert-success">
            <i class="fas fa-circle-check"></i>
            <div><strong>Success!</strong> <span><?php echo htmlspecialchars($success); ?></span></div>
          </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="wizardForm" autocomplete="off">
          
          <!-- step 1: ministry info panel -->
          <div class="wizard-panel active" id="panel1">
            <h3 class="auth-card-title">Ministry Information</h3>
            <p style="color:var(--gray-400); font-size:0.85rem; margin-bottom: 20px;">
              Tell us about the church or feeding site you represent.
            </p>

            <div class="auth-form-group">
              <label for="church_name">Church / Site Name *</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-church"></i>
                <input type="text" id="church_name" name="church_name" class="auth-input" placeholder="e.g. Grace Gospel Church" value="<?php echo htmlspecialchars($_POST['church_name'] ?? ''); ?>" required />
              </div>
            </div>

            <div class="auth-form-group">
              <label for="street_address">Street Address / Landmark *</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-map-location-dot"></i>
                <input type="text" id="street_address" name="street_address" class="auth-input" placeholder="e.g. 123 Mabini St. or Purok 4" value="<?php echo htmlspecialchars($_POST['street_address'] ?? ''); ?>" required />
              </div>
            </div>

            <div class="auth-form-group">
              <label for="position_title">Your Position / Title *</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-user-tag"></i>
                <select id="position_title" name="position_title" class="auth-select" style="padding-left:48px;" required>
                  <option value="" disabled <?php echo empty($_POST['position_title']) ? 'selected' : ''; ?>>Select your position...</option>
                  <option value="Lead Pastor" <?php echo ($_POST['position_title'] ?? '') === 'Lead Pastor' ? 'selected' : ''; ?>>Lead Pastor</option>
                  <option value="Assistant Pastor" <?php echo ($_POST['position_title'] ?? '') === 'Assistant Pastor' ? 'selected' : ''; ?>>Assistant Pastor</option>
                  <option value="Elder" <?php echo ($_POST['position_title'] ?? '') === 'Elder' ? 'selected' : ''; ?>>Elder / Deacon</option>
                  <option value="Ministry Coordinator" <?php echo ($_POST['position_title'] ?? '') === 'Ministry Coordinator' ? 'selected' : ''; ?>>Ministry Coordinator</option>
                  <option value="Volunteer Coordinator" <?php echo ($_POST['position_title'] ?? '') === 'Volunteer Coordinator' ? 'selected' : ''; ?>>Volunteer Coordinator</option>
                  <option value="Other" <?php echo ($_POST['position_title'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
              </div>
            </div>

            <!-- Dynamic Locations Dropdown Section -->
            <div class="form-grid-2">
              <div class="auth-form-group">
                <label for="region_select">Region *</label>
                <div class="auth-input-wrapper" id="regionFieldContainer">
                  <i class="fas fa-map"></i>
                  <select id="region_select" class="auth-select" style="padding-left:48px;" required>
                    <option value="" disabled selected>Select region...</option>
                  </select>
                </div>
                <input type="hidden" name="region" id="region" value="<?php echo htmlspecialchars($_POST['region'] ?? ''); ?>" />
              </div>

              <div class="auth-form-group">
                <label for="province">Province *</label>
                <div class="auth-input-wrapper">
                  <i class="fas fa-map-location-dot"></i>
                  <input type="text" id="province" name="province" class="auth-input" placeholder="e.g. Rizal" value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>" required />
                </div>
              </div>
            </div>

            <div class="form-grid-2">
              <div class="auth-form-group">
                <label for="city_select">City / Municipality *</label>
                <div class="auth-input-wrapper" id="cityFieldContainer">
                  <i class="fas fa-building"></i>
                  <select id="city_select" class="auth-select" style="padding-left:48px;" required disabled>
                    <option value="" disabled selected>Select city...</option>
                  </select>
                </div>
                <input type="hidden" name="city" id="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" />
              </div>
              
              <div class="auth-form-group">
                <label for="barangay_select">Barangay *</label>
                <div class="auth-input-wrapper" id="barangayFieldContainer">
                  <i class="fas fa-map-pin"></i>
                  <select id="barangay_select" class="auth-select" style="padding-left:48px;" required disabled>
                    <option value="" disabled selected>Select barangay...</option>
                  </select>
                </div>
                <input type="hidden" name="barangay" id="barangay" value="<?php echo htmlspecialchars($_POST['barangay'] ?? ''); ?>" />
              </div>
            </div>

            <div class="wizard-btn-row">
              <button type="button" class="btn btn-primary" onclick="nextStep(2)"><i class="fas fa-arrow-right"></i> Next Step</button>
            </div>
          </div>

          <!-- step 2: personal details panel -->
          <div class="wizard-panel" id="panel2">
            <h3 class="auth-card-title">Personal &amp; Contact Details</h3>
            <p style="color:var(--gray-400); font-size:0.85rem; margin-bottom: 20px;">
              Provide contact details as the main system administrator point of contact.
            </p>

            <div class="form-grid-3-resp">
              <div class="auth-form-group" style="margin-bottom:0;">
                <label for="first_name">First Name *</label>
                <div class="auth-input-wrapper">
                  <i class="fas fa-user"></i>
                  <input type="text" id="first_name" name="first_name" class="auth-input" style="padding-left:44px;" placeholder="e.g. Juan" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required />
                </div>
              </div>
              
              <div class="auth-form-group" style="margin-bottom:0;">
                <label for="middle_name">Middle Name</label>
                <div class="auth-input-wrapper">
                  <i class="fas fa-user-tag"></i>
                  <input type="text" id="middle_name" name="middle_name" class="auth-input" style="padding-left:44px;" placeholder="e.g. Santos" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" />
                </div>
              </div>

              <div class="auth-form-group" style="margin-bottom:0;">
                <label for="last_name">Last Name *</label>
                <div class="auth-input-wrapper">
                  <i class="fas fa-user"></i>
                  <input type="text" id="last_name" name="last_name" class="auth-input" style="padding-left:44px;" placeholder="e.g. Dela Cruz" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required />
                </div>
              </div>
            </div>

            <div class="form-grid-2">
              <div class="auth-form-group">
                <label for="phone">Contact Number *</label>
                <div class="auth-input-wrapper">
                  <i class="fas fa-phone"></i>
                  <input type="text" id="phone" name="phone" class="auth-input" placeholder="e.g. 0917-123-4567" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required />
                </div>
              </div>
              <div class="auth-form-group">
                <label for="email">Email Address *</label>
                <div class="auth-input-wrapper">
                  <i class="fas fa-envelope"></i>
                  <input type="email" id="email" name="email" class="auth-input" placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
                </div>
              </div>
            </div>

            <div class="auth-form-group">
              <label for="admin_message">Message to Administrator (Optional)</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-comment-dots" style="top:20px;"></i>
                <textarea id="admin_message" name="admin_message" class="auth-input" style="height:90px; resize:none; padding-top:10px;" placeholder="Any additional information you'd like us to know about your church or feeding program..."><?php echo htmlspecialchars($_POST['admin_message'] ?? ''); ?></textarea>
              </div>
            </div>

            <div class="wizard-btn-row">
              <button type="button" class="btn btn-outline" style="border-color:rgba(255,255,255,0.15); color:var(--gray-300);" onclick="prevStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
              <button type="button" class="btn btn-primary" onclick="nextStep(3)"><i class="fas fa-arrow-right"></i> Next Step</button>
            </div>
          </div>

          <!-- step 3: account setup panel -->
          <div class="wizard-panel" id="panel3">
            <h3 class="auth-card-title">Account Setup</h3>
            <p style="color:var(--gray-400); font-size:0.85rem; margin-bottom: 20px;">
              Choose your username and create a secure password.
            </p>

            <div class="auth-form-group">
              <label for="username">Username *</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-at"></i>
                <input type="text" id="username" name="username" class="auth-input" placeholder="Choose a unique username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autocomplete="new-username" />
              </div>
            </div>

            <div class="auth-form-group">
              <label for="password">Password *</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" class="auth-input" placeholder="Create a secure password" required autocomplete="new-password" />
                <i class="fas fa-eye password-toggle-icon" id="togglePassword"></i>
              </div>
              
              <!-- Strength Meter -->
              <div class="strength-meter-container">
                <div class="strength-meter-bar">
                  <div class="strength-meter-fill" id="strengthFill"></div>
                </div>
                <span class="strength-meter-text" id="strengthText">Password Strength: Empty</span>
              </div>
            </div>

            <div class="auth-form-group">
              <label for="confirm_password">Confirm Password *</label>
              <div class="auth-input-wrapper">
                <i class="fas fa-thumbs-up"></i>
                <input type="password" id="confirm_password" name="confirm_password" class="auth-input" placeholder="Re-enter your password" required autocomplete="new-password" />
                <i class="fas fa-eye password-toggle-icon" id="toggleConfirmPassword"></i>
              </div>
            </div>

            <div class="auth-form-group" style="margin-top:24px;">
              <label class="perm-item" style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                <input type="checkbox" name="terms" id="terms" required style="width:18px; height:18px; cursor:pointer;" />
                <span style="font-size:0.78rem; color:var(--gray-300); font-weight:normal; line-height:1.4;">
                  I confirm that the information I provided is accurate and complete, and I agree to the <strong>Terms of Use</strong> and <strong>Privacy Policy</strong> of DivineShield MAINPI.
                </span>
              </label>
            </div>

            <div class="wizard-btn-row">
              <button type="button" class="btn btn-outline" style="border-color:rgba(255,255,255,0.15); color:var(--gray-300);" onclick="prevStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
              <button type="submit" class="btn btn-primary" style="background:var(--teal-500); border-color:var(--teal-500);" id="submitBtn"><i class="fas fa-paper-plane"></i> Submit Registration</button>
            </div>
          </div>

        </form>

        <div class="auth-footer">
          Already registered? <a href="login.php">Log in to Portal</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    let currentStep = <?php echo $startStep; ?>;
    let isOfflineMode = false;
    let isRestoring = true;

    document.addEventListener("DOMContentLoaded", async () => {
      isRestoring = true;
      showStep(currentStep);
      restoreBasicFormState();
      setupPersistence();
      await initLocations();
      isRestoring = false;
    });

    <?php if (!empty($success)): ?>
      localStorage.removeItem("divineshield_register_data");
    <?php endif; ?>

    function saveFormData() {
      if (isRestoring) return;
      const regionSelect = document.getElementById("region_select");
      const citySelect = document.getElementById("city_select");
      const barangaySelect = document.getElementById("barangay_select");

      let data = {
        currentStep: currentStep,
        isOfflineMode: isOfflineMode,
        church_name: document.getElementById("church_name").value,
        position_title: document.getElementById("position_title").value,
        street_address: document.getElementById("street_address").value,
        province: document.getElementById("province").value,
        first_name: document.getElementById("first_name").value,
        middle_name: document.getElementById("middle_name").value,
        last_name: document.getElementById("last_name").value,
        phone: document.getElementById("phone").value,
        email: document.getElementById("email").value,
        admin_message: document.getElementById("admin_message").value,
        username: document.getElementById("username").value
      };

      if (isOfflineMode) {
        data.region = regionSelect ? regionSelect.value : "";
        data.city = citySelect ? citySelect.value : "";
        data.barangay = barangaySelect ? barangaySelect.value : "";
      } else {
        data.region_code = regionSelect ? regionSelect.value : "";
        data.region_name = document.getElementById("region") ? document.getElementById("region").value : "";
        data.city_code = citySelect ? citySelect.value : "";
        data.city_name = document.getElementById("city") ? document.getElementById("city").value : "";
        data.barangay_code = barangaySelect ? barangaySelect.value : "";
        data.barangay_name = document.getElementById("barangay") ? document.getElementById("barangay").value : "";
      }

      localStorage.setItem("divineshield_register_data", JSON.stringify(data));
    }

    function restoreBasicFormState() {
      const raw = localStorage.getItem("divineshield_register_data");
      if (!raw) return;

      try {
        const data = JSON.parse(raw);
        document.getElementById("church_name").value = data.church_name || "";
        document.getElementById("position_title").value = data.position_title || "";
        document.getElementById("street_address").value = data.street_address || "";
        document.getElementById("province").value = data.province || "";
        document.getElementById("first_name").value = data.first_name || "";
        document.getElementById("middle_name").value = data.middle_name || "";
        document.getElementById("last_name").value = data.last_name || "";
        document.getElementById("phone").value = data.phone || "";
        document.getElementById("email").value = data.email || "";
        document.getElementById("admin_message").value = data.admin_message || "";
        document.getElementById("username").value = data.username || "";

        if (data.currentStep && data.currentStep >= 1 && data.currentStep <= 3) {
          showStep(data.currentStep);
        }
      } catch (err) {
        console.error("Failed to restore basic saved form state", err);
      }
    }

    async function restoreLocationState() {
      const raw = localStorage.getItem("divineshield_register_data");
      if (!raw) return;

      try {
        const data = JSON.parse(raw);

        if (data.isOfflineMode) {
          enableOfflineFallback();
          document.getElementById("region_select").value = data.region || "";
          document.getElementById("city_select").value = data.city || "";
          document.getElementById("barangay_select").value = data.barangay || "";
        } else {
          const regionSelect = document.getElementById("region_select");
          if (data.region_code) {
            regionSelect.value = data.region_code;
            document.getElementById("region").value = data.region_name || "";

            await loadCities(data.region_code);
            const citySelect = document.getElementById("city_select");
            if (data.city_code) {
              citySelect.value = data.city_code;
              document.getElementById("city").value = data.city_name || "";

              await loadBarangays(data.city_code);
              const barangaySelect = document.getElementById("barangay_select");
              if (data.barangay_code) {
                barangaySelect.value = data.barangay_code;
                document.getElementById("barangay").value = data.barangay_name || "";
              }
            }
          }
        }
      } catch (err) {
        console.error("Failed to restore location dropdown state", err);
      }
    }

    function setupPersistence() {
      const form = document.getElementById("wizardForm");
      form.addEventListener("input", saveFormData);
      form.addEventListener("change", saveFormData);
    }

    async function initLocations() {
      const regionSelect = document.getElementById("region_select");
      const citySelect = document.getElementById("city_select");
      const barangaySelect = document.getElementById("barangay_select");
      
      try {
        const response = await fetch("https://psgc.gitlab.io/api/regions/");
        if (!response.ok) throw new Error("API failed");
        
        const regions = await response.json();
        regions.sort((a, b) => a.name.localeCompare(b.name));
        
        regionSelect.innerHTML = '<option value="" disabled selected>Select region...</option>';
        regions.forEach(r => {
          const opt = document.createElement("option");
          opt.value = r.code;
          opt.textContent = r.name + (r.regionName ? ` (${r.regionName})` : '');
          opt.dataset.name = r.name;
          regionSelect.appendChild(opt);
        });

        await restoreLocationState();

        const prevRegion = "<?php echo $_POST['region'] ?? ''; ?>";
        if (prevRegion && !localStorage.getItem("divineshield_register_data")) {
          const matchingOpt = Array.from(regionSelect.options).find(o => o.dataset.name === prevRegion);
          if (matchingOpt) {
            regionSelect.value = matchingOpt.value;
            document.getElementById("region").value = prevRegion;
            await loadCities(matchingOpt.value);
          }
        }
      } catch (err) {
        console.warn("Failed to load regions from API. Swapping to local offline text fallbacks.", err);
        enableOfflineFallback();
      }

      regionSelect.addEventListener("change", async () => {
        if (isOfflineMode) return;
        const regionCode = regionSelect.value;
        const selectedOpt = regionSelect.options[regionSelect.selectedIndex];
        document.getElementById("region").value = selectedOpt.dataset.name;
        await loadCities(regionCode);
        saveFormData();
      });

      citySelect.addEventListener("change", async () => {
        if (isOfflineMode) return;
        const cityCode = citySelect.value;
        const selectedOpt = citySelect.options[citySelect.selectedIndex];
        document.getElementById("city").value = selectedOpt.dataset.name;
        await loadBarangays(cityCode);
        saveFormData();
      });

      barangaySelect.addEventListener("change", () => {
        if (isOfflineMode) return;
        const selectedOpt = barangaySelect.options[barangaySelect.selectedIndex];
        document.getElementById("barangay").value = selectedOpt.dataset.name;
        saveFormData();
      });
    }

    async function loadCities(regionCode) {
      const citySelect = document.getElementById("city_select");
      const barangaySelect = document.getElementById("barangay_select");
      
      citySelect.disabled = true;
      citySelect.innerHTML = '<option value="" disabled selected>Loading cities...</option>';
      barangaySelect.disabled = true;
      barangaySelect.innerHTML = '<option value="" disabled selected>Select barangay...</option>';

      try {
        const response = await fetch(`https://psgc.gitlab.io/api/regions/${regionCode}/cities-municipalities/`);
        if (!response.ok) throw new Error("API failed");
        
        const cities = await response.json();
        cities.sort((a, b) => a.name.localeCompare(b.name));

        citySelect.innerHTML = '<option value="" disabled selected>Select city...</option>';
        cities.forEach(c => {
          const opt = document.createElement("option");
          opt.value = c.code;
          opt.textContent = c.name;
          opt.dataset.name = c.name;
          citySelect.appendChild(opt);
        });

        citySelect.disabled = false;

        const prevCity = "<?php echo $_POST['city'] ?? ''; ?>";
        if (prevCity) {
          const matchingOpt = Array.from(citySelect.options).find(o => o.dataset.name === prevCity);
          if (matchingOpt) {
            citySelect.value = matchingOpt.value;
            document.getElementById("city").value = prevCity;
            await loadBarangays(matchingOpt.value);
          }
        }
      } catch (err) {
        console.error("Error loading cities", err);
        enableOfflineFallback();
      }
    }

    async function loadBarangays(cityCode) {
      const barangaySelect = document.getElementById("barangay_select");
      
      barangaySelect.disabled = true;
      barangaySelect.innerHTML = '<option value="" disabled selected>Loading barangays...</option>';

      try {
        const response = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
        if (!response.ok) throw new Error("API failed");
        
        const barangays = await response.json();
        barangays.sort((a, b) => a.name.localeCompare(b.name));

        barangaySelect.innerHTML = '<option value="" disabled selected>Select barangay...</option>';
        barangays.forEach(b => {
          const opt = document.createElement("option");
          opt.value = b.code;
          opt.textContent = b.name;
          opt.dataset.name = b.name;
          barangaySelect.appendChild(opt);
        });

        barangaySelect.disabled = false;

        const prevBarangay = "<?php echo $_POST['barangay'] ?? ''; ?>";
        if (prevBarangay) {
          const matchingOpt = Array.from(barangaySelect.options).find(o => o.dataset.name === prevBarangay);
          if (matchingOpt) {
            barangaySelect.value = matchingOpt.value;
            document.getElementById("barangay").value = prevBarangay;
          }
        }
      } catch (err) {
        console.error("Error loading barangays", err);
        enableOfflineFallback();
      }
    }

    function enableOfflineFallback() {
      if (isOfflineMode) return;
      isOfflineMode = true;
      
      console.log("Applying offline text inputs fallback.");

      const regionContainer = document.getElementById("regionFieldContainer");
      regionContainer.innerHTML = `
        <i class="fas fa-map"></i>
        <select id="region_select" name="region" class="auth-select" style="padding-left:48px;" required>
          <option value="" disabled selected>Select region...</option>
          <option value="NCR">NCR – Metro Manila</option>
          <option value="CAR">CAR – Cordillera</option>
          <option value="Region I">Region I – Ilocos</option>
          <option value="Region II">Region II – Cagayan Valley</option>
          <option value="Region III">Region III – Central Luzon</option>
          <option value="Region IV-A">Region IV-A – CALABARZON</option>
          <option value="MIMAROPA">MIMAROPA Region</option>
          <option value="Region V">Region V – Bicol</option>
          <option value="Region VI">Region VI – Western Visayas</option>
          <option value="Region VII">Region VII – Central Visayas</option>
          <option value="Region VIII">Region VIII – Eastern Visayas</option>
          <option value="Region IX">Region IX – Zamboanga Peninsula</option>
          <option value="Region X">Region X – Northern Mindanao</option>
          <option value="Region XI">Region XI – Davao Region</option>
          <option value="Region XII">Region XII – SOCCSKSARGEN</option>
          <option value="Region XIII">Region XIII – Caraga</option>
          <option value="BARMM">BARMM – Muslim Mindanao</option>
        </select>
      `;

      const cityContainer = document.getElementById("cityFieldContainer");
      const prevCityValue = document.getElementById("city").value || "<?php echo $_POST['city'] ?? ''; ?>";
      cityContainer.innerHTML = `
        <i class="fas fa-building"></i>
        <input type="text" id="city_select" name="city" class="auth-input" placeholder="e.g. Quezon City" value="${prevCityValue}" required />
      `;

      const barangayContainer = document.getElementById("barangayFieldContainer");
      const prevBarangayValue = document.getElementById("barangay").value || "<?php echo $_POST['barangay'] ?? ''; ?>";
      barangayContainer.innerHTML = `
        <i class="fas fa-map-pin"></i>
        <input type="text" id="barangay_select" name="barangay" class="auth-input" placeholder="e.g. Batasan Hills" value="${prevBarangayValue}" required />
      `;

      const hiddenRegion = document.getElementById("region");
      const hiddenCity = document.getElementById("city");
      const hiddenBarangay = document.getElementById("barangay");
      if (hiddenRegion) hiddenRegion.remove();
      if (hiddenCity) hiddenCity.remove();
      if (hiddenBarangay) hiddenBarangay.remove();

      const regionSelect = document.getElementById("region_select");
      regionSelect.addEventListener("change", () => {
        regionSelect.name = "region";
        saveFormData();
      });
    }

    function showStep(step) {
      document.querySelectorAll(".wizard-panel").forEach(panel => panel.classList.remove("active"));
      document.getElementById("panel" + step).classList.add("active");

      for (let i = 1; i <= 3; i++) {
        const indicator = document.getElementById("stepIndicator" + i);
        if (i < step) {
          indicator.className = "stepper-step completed";
        } else if (i === step) {
          indicator.className = "stepper-step active";
        } else {
          indicator.className = "stepper-step";
        }
      }

      const lineFill = document.getElementById("stepperLineFill");
      if (step === 1) lineFill.style.width = "0%";
      if (step === 2) lineFill.style.width = "50%";
      if (step === 3) lineFill.style.width = "100%";

      currentStep = step;
      saveFormData();
      document.querySelector(".auth-card").scrollIntoView({ behavior: 'smooth' });
    }

    function nextStep(step) {
      const currentPanel = document.getElementById("panel" + (step - 1));
      const selects = currentPanel.querySelectorAll("select");
      const inputs = currentPanel.querySelectorAll("input");
      
      let allValid = true;
      
      selects.forEach(sel => {
        if (sel.required && (sel.value === "" || sel.disabled)) {
          sel.reportValidity();
          allValid = false;
        }
      });

      inputs.forEach(inp => {
        if (inp.required && !inp.value.trim()) {
          inp.reportValidity();
          allValid = false;
        }
      });

      if (!allValid) return;
      showStep(step);
    }

    function prevStep(step) {
      showStep(step);
    }

    const setupTogglePassword = (inputId, toggleIconId) => {
      const passwordInput = document.getElementById(inputId);
      const toggleIcon = document.getElementById(toggleIconId);
      toggleIcon.addEventListener("click", () => {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        toggleIcon.classList.toggle("fa-eye");
        toggleIcon.classList.toggle("fa-eye-slash");
      });
    };

    setupTogglePassword("password", "togglePassword");
    setupTogglePassword("confirm_password", "toggleConfirmPassword");

    const passwordInput = document.getElementById("password");
    const strengthFill = document.getElementById("strengthFill");
    const strengthText = document.getElementById("strengthText");

    passwordInput.addEventListener("input", () => {
      const val = passwordInput.value;
      let score = 0;

      if (!val) {
        strengthFill.style.width = "0%";
        strengthFill.style.background = "var(--red-500)";
        strengthText.textContent = "Password Strength: Empty";
        return;
      }

      if (val.length >= 6) score++;
      if (val.length >= 10) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      let width = "0%";
      let color = "var(--red-500)";
      let label = "Weak";

      switch (score) {
        case 1:
        case 2:
          width = "30%";
          color = "var(--red-500)";
          label = "Weak";
          break;
        case 3:
          width = "60%";
          color = "var(--yellow-500)";
          label = "Medium";
          break;
        case 4:
          width = "85%";
          color = "var(--blue-500)";
          label = "Strong";
          break;
        case 5:
          width = "100%";
          color = "var(--green-500)";
          label = "Very Secure";
          break;
      }

      strengthFill.style.width = width;
      strengthFill.style.background = color;
      strengthText.innerHTML = `Password Strength: <span style="color:${color}">${label}</span>`;
    });
  </script>

</body>
</html>