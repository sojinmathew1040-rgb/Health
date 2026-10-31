<?php
/**
 * HealthJobsGlobal.com - Healthcare Candidate & Hospital Employer Registration
 */
$pageTitle = "Registration — HealthJobsGlobal";
$pageDescription = "Create your verified healthcare profile or register your hospital recruitment institution.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Handle Registration POST Actions (Candidate & Employer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';

    if ($action === 'register_candidate') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $fullName = mb_strtoupper(trim($firstName . ' ' . $lastName), 'UTF-8');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $degree = trim($_POST['degree'] ?? 'Healthcare Professional');
        $specialty = trim($_POST['specialty'] ?? '');
        $experience = trim($_POST['experience'] ?? 'Experienced');
        $council_reg = mb_strtoupper(trim($_POST['council_reg'] ?? ''), 'UTF-8');
        $location = trim($_POST['location'] ?? '');

        if (empty($fullName)) {
            echo json_encode(['success' => false, 'message' => 'Please provide your full name in Step 1.']);
            exit;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }
        if (empty($password) || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        // Check if email already registered in candidates table
        if ($con && !$con->connect_error) {
            $stmt = $con->prepare("SELECT `id` FROM `candidates` WHERE `email` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->close();
                    echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please log in with your credentials.']);
                    exit;
                }
                $stmt->close();
            }

            // Check if registered in hospitals table
            $stmt = $con->prepare("SELECT `id` FROM `hospitals` WHERE `contact_email` = ? OR `admin_email` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $email, $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->close();
                    echo json_encode(['success' => false, 'message' => 'This email is already registered as a Hospital Employer account. Please sign in or use another email.']);
                    exit;
                }
                $stmt->close();
            }

            // Insert into candidates
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $con->prepare("INSERT INTO `candidates` (`name`, `email`, `password`, `phone`, `degree`, `specialty`, `experience`, `council_reg`, `location`, `status`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Verified', NOW())");
            if ($stmt) {
                $stmt->bind_param("sssssssss", $fullName, $email, $hashed, $phone, $degree, $specialty, $experience, $council_reg, $location);
                $stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();

                // Set session
                $_SESSION['user'] = [
                    'id' => $newId,
                    'role' => 'candidate',
                    'name' => $fullName,
                    'degree' => $degree,
                    'title' => !empty($specialty) ? ($specialty . (!empty($degree) ? ' · ' . $degree : '')) : 'Healthcare Professional',
                    'email' => $email,
                    'phone' => $phone,
                    'initials' => strtoupper(substr($firstName, 0, 1) . substr($lastName ?: $firstName, 0, 1)),
                    'council' => $council_reg
                ];

                echo json_encode([
                    'success' => true,
                    'redirect' => BASE_URL . '/index.php',
                    'message' => "Candidate account created successfully! Welcome, {$fullName}."
                ]);
                exit;
            }
        }

        // Fallback session if DB error
        $_SESSION['user'] = [
            'role' => 'candidate',
            'name' => $fullName,
            'degree' => $degree,
            'title' => !empty($specialty) ? $specialty : 'Healthcare Professional',
            'email' => $email,
            'phone' => $phone,
            'initials' => strtoupper(substr($firstName, 0, 1) . substr($lastName ?: $firstName, 0, 1)),
            'council' => $council_reg
        ];
        echo json_encode([
            'success' => true,
            'redirect' => BASE_URL . '/index.php',
            'message' => "Registration successful! Welcome, {$fullName}."
        ]);
        exit;
    }

    if ($action === 'register_employer') {
        $orgName = mb_strtoupper(trim($_POST['org_name'] ?? ''), 'UTF-8');
        $orgCategory = trim($_POST['org_category'] ?? 'Quaternary Care Multi-Specialty Hospital');
        $operatingCapacity = trim($_POST['operating_capacity'] ?? 'Medium (100 - 500 Beds / 250+ Staff)');
        $generalEmail = trim($_POST['general_email'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $generalPhone = trim($_POST['general_phone'] ?? '');
        $adminPhone = trim($_POST['admin_phone'] ?? '');
        $phone = !empty($generalPhone) ? $generalPhone : $adminPhone;
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $country = trim($_POST['country'] ?? 'India');
        $adminName = mb_strtoupper(trim($_POST['admin_name'] ?? ''), 'UTF-8');
        $adminDesig = mb_strtoupper(trim($_POST['admin_designation'] ?? 'Head of Medical Talent Acquisition'), 'UTF-8');
        $website = trim($_POST['website'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $services = trim($_POST['services'] ?? '');
        $otherServicesDetails = trim($_POST['other_services_details'] ?? '');

        if (empty($orgName)) {
            echo json_encode(['success' => false, 'message' => 'Please provide the Organization / Hospital Name.']);
            exit;
        }
        $contactEmail = !empty($adminEmail) ? $adminEmail : (!empty($generalEmail) ? $generalEmail : '');
        if (empty($contactEmail) || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid official Email Address.']);
            exit;
        }

        // Check if email already registered in hospitals or candidates
        if ($con && !$con->connect_error) {
            $stmt = $con->prepare("SELECT `id` FROM `hospitals` WHERE `contact_email` = ? OR `admin_email` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $contactEmail, $contactEmail);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->close();
                    echo json_encode(['success' => false, 'message' => 'An organization with this email already exists. Please log in.']);
                    exit;
                }
                $stmt->close();
            }

            $stmt = $con->prepare("SELECT `id` FROM `candidates` WHERE `email` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $contactEmail);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->close();
                    echo json_encode(['success' => false, 'message' => 'This email is already registered as a Candidate account. Please sign in or use another email.']);
                    exit;
                }
                $stmt->close();
            }

            // No password is required during employer registration.
            // A secure unguessable placeholder is assigned until admin activates the account
            // and generates the random default password.
            $placeholderPass = bin2hex(random_bytes(16));
            $hashed = password_hash($placeholderPass, PASSWORD_DEFAULT);
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $orgName), '-'));
            $cleanName = preg_replace('/[^a-zA-Z]/', '', $orgName);
            $logoText = strtoupper(substr($cleanName, 0, 5));
            $locationStr = $city . (!empty($address) ? ', ' . $address : '');
            $overview = "{$orgName} is a healthcare establishment registered on HealthJobsGlobal.";

            // Employer registration requires Admin Approval (verified = 0, must_change_password = 1)
            $stmt = $con->prepare("INSERT INTO `hospitals` (
                `name`, `slug`, `logo_text`, `badge`, `type`, `location`, `openings`, `rating`, `verified`, 
                `contact_email`, `admin_email`, `admin_name`, `admin_designation`, `general_email`, `general_phone`, 
                `admin_phone`, `phone`, `address`, `city`, `pincode`, `country`, `operating_capacity`, `services`, 
                `other_services_details`, `password`, `website`, `overview`, `must_change_password`, `created_at`
            ) VALUES (
                ?, ?, ?, 'NABH / JCI Accredited', ?, ?, 0, '4.8', 0,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 1, NOW()
            )");
            if ($stmt) {
                // Exactly 23 string type specifiers matching the 23 parameters
                $stmt->bind_param(
                    "sssssssssssssssssssssss",
                    $orgName, $slug, $logoText, $orgCategory, $locationStr,
                    $contactEmail, $adminEmail, $adminName, $adminDesig, $generalEmail, $generalPhone,
                    $adminPhone, $phone, $address, $city, $pincode, $country, $operatingCapacity, $services,
                    $otherServicesDetails, $hashed, $website, $overview
                );
                if ($stmt->execute()) {
                    $newId = $stmt->insert_id;
                    $stmt->close();

                    echo json_encode([
                        'success' => true,
                        'pending_approval' => true,
                        'redirect' => BASE_URL . '/index.php',
                        'org_name' => $orgName,
                        'message' => "Registration submitted for {$orgName}! Waiting for admin approval..."
                    ]);
                    exit;
                } else {
                    $dbErr = $stmt->error;
                    $stmt->close();
                    echo json_encode(['success' => false, 'message' => 'Failed to save registration: ' . $dbErr]);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $con->error]);
                exit;
            }
        }

        echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please verify MySQL service.']);
        exit;
    }
}

require_once __DIR__ . '/components/header.php';

// Initial role selection (candidate vs employer)
$activeRole = ((isset($_GET['type']) && $_GET['type'] === 'employer') || (isset($_GET['role']) && $_GET['role'] === 'employer')) ? 'employer' : 'candidate';
$generatedCandidateId = "HJG-" . strtoupper(substr(md5(uniqid()), 0, 8));
$generatedHospitalId = "HOSP-" . strtoupper(substr(md5(uniqid()), 0, 6));
?>

<div style="background: var(--color-bg); padding: 48px 0 80px 0; min-height: 90vh;">
  <div class="container" style="max-width: 860px; margin: 0 auto;">
    
    <div class="auth-card-wrap" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 40px; box-shadow: var(--shadow-xl);">
      
      <!-- Role Switcher Tabs (Candidate vs Hospital Employer) -->
      <div class="role-switcher-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 32px; background: var(--color-bg); padding: 6px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
        <button type="button" id="roleBtnCandidate" class="btn <?php echo $activeRole === 'candidate' ? 'btn-primary' : 'btn-outline'; ?>" onclick="setRegistrationRole('candidate')" style="border-radius: var(--radius-md); font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px;">
          <?php echo getIcon('user', '', 18); ?>
          <span>Candidate</span>
        </button>
        <button type="button" id="roleBtnEmployer" class="btn <?php echo $activeRole === 'employer' ? 'btn-primary' : 'btn-outline'; ?>" onclick="setRegistrationRole('employer')" style="border-radius: var(--radius-md); font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px;">
          <?php echo getIcon('briefcase', '', 18); ?>
          <span>Employer (All Healthcare Verticals & Agencies)</span>
        </button>
      </div>

      <!-- ======================================================================
           SECTION 1: CANDIDATE REGISTRATION WIZARD (All Healthcare Professionals)
           ====================================================================== -->
      <div id="candidateRegistrationSection" style="<?php echo $activeRole === 'candidate' ? 'display: block;' : 'display: none;'; ?>">
        
        <!-- Header -->
        <div class="flex-between items-center" style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 20px; flex-wrap: wrap; gap: 12px;">
          <div>
            <span class="eyebrow" style="margin-bottom: 2px;">Healthcare Talent & Staff Registration</span>
            <h1 style="font-size: 1.85rem; color: var(--color-primary); margin: 0;">Candidate</h1>
            <p style="color: var(--color-text-secondary); font-size: 0.9rem; margin-top: 4px;">
              Looking for all kinds of healthcare professionals — from Security Guards and General Domestic Assistants (Housekeepers & Porters) to Clinicians, Nursing Specialists, and Top Leadership.
            </p>
          </div>
          <div style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 6px 14px; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary);">
            Candidate ID: <span style="font-family: monospace;"><?php echo $generatedCandidateId; ?></span>
          </div>
        </div>

        <!-- Multi-step Indicator Header -->
        <div class="wizard-steps-header">
          <div class="step-indicator active" id="stepIndicator1">
            <div class="step-bubble">1</div>
            <span class="step-label">Personal & Contact</span>
          </div>
          <div class="step-indicator" id="stepIndicator2">
            <div class="step-bubble">2</div>
            <span class="step-label">Council & Degree</span>
          </div>
          <div class="step-indicator" id="stepIndicator3">
            <div class="step-bubble">3</div>
            <span class="step-label">Career & Salary</span>
          </div>
          <div class="step-indicator" id="stepIndicator4">
            <div class="step-bubble">4</div>
            <span class="step-label">Profile Activation</span>
          </div>
        </div>

        <form id="multiStepForm" onsubmit="event.preventDefault();">
          
          <!-- STEP 1: Personal & Contact Credentials -->
          <div class="wizard-step-content" id="stepContent1">
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 18px;">Step 1: Personal & Contact Information</h3>

            <!-- Profile Photo (Optional) -->
            <div class="profile-photo-uploader" style="display: flex; align-items: center; gap: 20px; padding: 16px 20px; background: var(--color-bg); border: 1px dashed var(--color-border); border-radius: var(--radius-lg); margin-bottom: 24px;">
              <div class="photo-preview-avatar" id="photoPreviewAvatar" style="width: 76px; height: 76px; border-radius: 50%; border: 2px solid var(--color-border); background: #FFFFFF; display: flex; align-items: center; justify-content: center; color: var(--color-text-muted); overflow: hidden; position: relative; flex-shrink: 0; box-shadow: var(--shadow-sm);">
                <div id="photoPlaceholderIcon">
                  <?php echo getIcon('user', '', 32); ?>
                </div>
                <img id="photoPreviewImg" src="" alt="Profile Photo" style="display: none; width: 100%; height: 100%; object-fit: cover;">
              </div>
              <div style="flex-grow: 1;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                  <label class="form-label" style="margin: 0; font-weight: 700; color: var(--color-primary);">Profile Photo <span style="font-weight: 400; color: var(--color-text-muted);">(Optional)</span></label>
                  <span style="font-size: 0.72rem; color: #0284C7; background: #E0F2FE; font-weight: 600; padding: 2px 8px; border-radius: var(--radius-full);">Recommended</span>
                </div>
                <p style="margin: 0 0 8px 0; font-size: 0.8rem; color: var(--color-text-secondary);">Upload a professional headshot or passport photo (JPG, PNG, WEBP - Max 5MB). Boosts profile visibility to hospitals by 40%.</p>
                <div style="display: flex; align-items: center; gap: 10px;">
                  <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('regProfilePhoto').click()" style="display: inline-flex; align-items: center; gap: 6px;">
                    <?php echo getIcon('camera', '', 14); ?>
                    <span>Choose Photo</span>
                  </button>
                  <button type="button" id="btnRemovePhoto" class="btn btn-sm" onclick="removeProfilePhoto()" style="display: none; color: #DC2626; background: transparent; border: none; padding: 4px 8px; font-size: 0.8rem; cursor: pointer;">
                    Remove Photo
                  </button>
                  <input type="file" id="regProfilePhoto" accept="image/*" style="display: none;" onchange="previewProfilePhoto(event)">
                </div>
              </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="regFirstName">First Name *</label>
                <input type="text" id="regFirstName" class="form-control" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. Arjun" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="regLastName">Surname / Last Name *</label>
                <input type="text" id="regLastName" class="form-control" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. Menon" required>
              </div>
            </div>

            <!-- Mobile & Mobile OTP Verification -->
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regMobile" style="margin: 0;">Mobile Number *</label>
                  <span id="mobileVerifiedBadge" class="verified-pill" style="display: none;">
                    <?php echo getIcon('check', '', 14); ?> Verified
                  </span>
                </div>
                <div style="display: flex; gap: 8px;">
                  <input type="tel" id="regMobile" class="form-control" placeholder="+91 98460 12345" oninput="syncWhatsappWithMobile()" required>
                  <button type="button" id="btnSendMobileOtp" class="btn btn-outline btn-sm" style="white-space: nowrap;" onclick="sendMobileOtp()">Send OTP</button>
                </div>
                <span class="form-hint" id="mobileOtpTimerText">Verification via SMS OTP is available for instant mobile confirmation</span>
              </div>

              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regMobileOtp" style="margin: 0;">Mobile OTP Verification</label>
                  <span class="form-hint" style="color: var(--color-secondary); font-size: 0.75rem; margin: 0;">6-digit code</span>
                </div>
                <div style="display: flex; gap: 8px;">
                  <input type="text" id="regMobileOtp" class="form-control" placeholder="Enter 6-digit code" maxlength="6" disabled>
                  <button type="button" id="btnVerifyMobileOtp" class="btn btn-primary btn-sm" style="white-space: nowrap;" onclick="verifyMobileOtp()" disabled>Verify OTP</button>
                </div>
                <span class="form-hint" id="mobileOtpStatusHint">Click "Send OTP" to receive code</span>
              </div>
            </div>

            <!-- Email & Email Code Verification -->
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regEmail" style="margin: 0;">Email Address *</label>
                  <span id="emailVerifiedBadge" class="verified-pill" style="display: none;">
                    <?php echo getIcon('check', '', 14); ?> Verified
                  </span>
                </div>
                <div style="display: flex; gap: 8px;">
                  <input type="email" id="regEmail" class="form-control" placeholder="arjun.candidate@hospital.com" required>
                  <button type="button" id="btnSendEmailCode" class="btn btn-outline btn-sm" style="white-space: nowrap;" onclick="sendEmailVerificationCode()">Verify</button>
                </div>
                <span class="form-hint" id="emailCodeTimerText">Used for official interview invitations & job offer letters</span>
              </div>

              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regEmailOtp" style="margin: 0;">Email Verification Code</label>
                  <span class="form-hint" style="color: var(--color-secondary); font-size: 0.75rem; margin: 0;">6-digit code</span>
                </div>
                <div style="display: flex; gap: 8px;">
                  <input type="text" id="regEmailOtp" class="form-control" placeholder="Enter 6-digit code" maxlength="6" disabled>
                  <button type="button" id="btnVerifyEmailOtp" class="btn btn-primary btn-sm" style="white-space: nowrap;" onclick="verifyEmailCode()" disabled>Confirm</button>
                </div>
                <span class="form-hint" id="emailOtpStatusHint">Click "Verify" to receive code in email</span>
              </div>
            </div>

            <!-- WhatsApp Number & Date of Birth (Optional) -->
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regWhatsapp" style="margin: 0;">WhatsApp Number</label>
                  <label style="display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: var(--color-secondary); cursor: pointer; margin: 0;">
                    <input type="checkbox" id="syncWhatsappCheck" checked onchange="syncWhatsappWithMobile()">
                    <span>Same as Mobile</span>
                  </label>
                </div>
                <input type="tel" id="regWhatsapp" class="form-control" placeholder="+91 98460 12345 (for instant vacancy alerts)">
                <span class="form-hint">Used for instant interview & hospital vacancy updates</span>
              </div>

              <div class="form-group">
                <label class="form-label" for="regDob">Date of Birth <span style="font-weight: 400; color: var(--color-text-muted);">(Optional)</span></label>
                <input type="date" id="regDob" class="form-control">
                <span class="form-hint">Can be updated later in your candidate dashboard</span>
              </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
              <button type="button" class="btn btn-primary" onclick="validateAndGoToStep2()">
                <span>Next: Council & Qualifications</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 2: Council Registration & Education (4 Columns + Multi-Qualifications) -->
          <div class="wizard-step-content" id="stepContent2" style="display: none;">
            <div class="flex-between items-center" style="margin-bottom: 6px;">
              <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0;">Step 2: Council, Degree & Career Status</h3>
            </div>
            <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 20px;">
              Personalized career matching for all healthcare professions — from GDAs, Porters & Security Guards to Doctors, Nurses & Executives.
            </p>

            <!-- 4 Column Status Selection Grid -->
            <div class="work-status-grid-4">
              <!-- Column 1: Experienced -->
              <div class="work-status-card compact-col active" id="cardStatusExp" onclick="setWorkStatus('experienced')">
                <div class="status-radio-dot"></div>
                <div class="status-icon-box">
                  <?php echo getIcon('briefcase', '', 22); ?>
                </div>
                <div class="status-details">
                  <div class="status-title">1. Experienced</div>
                  <div class="status-desc">Clinical, allied health, administrative, or hospital support work experience</div>
                </div>
              </div>

              <!-- Column 2: Fresher -->
              <div class="work-status-card compact-col" id="cardStatusFresher" onclick="setWorkStatus('fresher')">
                <div class="status-radio-dot"></div>
                <div class="status-icon-box">
                  <?php echo getIcon('award', '', 22); ?>
                </div>
                <div class="status-details">
                  <div class="status-title">2. Fresher</div>
                  <div class="status-desc">Fresh graduate ready for entry-level hospital jobs</div>
                </div>
              </div>

              <!-- Column 3: Internship / Trainee -->
              <div class="work-status-card compact-col" id="cardStatusIntern" onclick="setWorkStatus('internship')">
                <div class="status-radio-dot"></div>
                <div class="status-icon-box">
                  <?php echo getIcon('user-check', '', 22); ?>
                </div>
                <div class="status-details">
                  <div class="status-title">3. Internship / Trainee</div>
                  <div class="status-desc">Seeking internship or on-the-job training (we provide training placement)</div>
                </div>
              </div>

              <!-- Column 4: Student -->
              <div class="work-status-card compact-col" id="cardStatusStudent" onclick="setWorkStatus('student')">
                <div class="status-radio-dot"></div>
                <div class="status-icon-box">
                  <?php echo getIcon('book-open', '', 22); ?>
                </div>
                <div class="status-details">
                  <div class="status-title">4. Student</div>
                  <div class="status-desc">Seeking healthcare education admissions & arrangements</div>
                </div>
              </div>
            </div>

            <!-- Remarks Column (Optional) for Status & Arrangements -->
            <div class="form-group" style="margin-bottom: 22px;">
              <label class="form-label" for="regStatusRemarks">Status Remarks & Special Requests <span style="font-weight: 400; color: var(--color-text-muted);">(Optional)</span></label>
              <input type="text" id="regStatusRemarks" class="form-control" placeholder="e.g. Looking for 6-month hands-on ICU training / need admission guidance for M.Sc Nursing / 5 years hospital experience">
              <span class="form-hint" id="statusRemarksHint">Share any specific training requirements, admission counseling goals, or hospital preferences.</span>
            </div>

            <!-- Current Designation and Preferred Designation -->
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
              <div class="form-group">
                <label class="form-label" for="regCurrentDesignation">Current Designation</label>
                <input type="text" id="regCurrentDesignation" class="form-control" placeholder="e.g. Staff Nurse / Duty Medical Officer / GDA / Security Guard / None">
                <span class="form-hint">Your current job title or role (write 'Fresher' or 'Student' if not currently employed)</span>
              </div>
              <div class="form-group">
                <label class="form-label" for="regPreferredDesignation">Preferred Designation / Target Role *</label>
                <input type="text" id="regPreferredDesignation" class="form-control" placeholder="e.g. ICU Staff Nurse / Consultant Physician / GDA Supervisor / Clinic In-Charge" required>
                <span class="form-hint">Target position or rank you are looking to secure</span>
              </div>
            </div>

            <!-- HIGHEST QUALIFICATION & DYNAMIC QUALIFICATIONS BUILDER -->
            <div class="qualifications-box" style="background: var(--color-bg); border: 1.5px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 24px;">
              <div class="flex-between items-center" style="margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div>
                  <h4 style="margin: 0; font-size: 1.05rem; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                    <?php echo getIcon('graduation-cap', '', 18); ?>
                    Highest Educational & Professional Qualifications
                  </h4>
                  <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: var(--color-text-secondary);">
                    People may hold different qualifications (for example, MD Doctor with LLB for medico-legal cases, or BDS with MBA/MHA for hospital admin). Click "+ Add Qualification" to add more degrees.
                  </p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="addQualificationRow()" style="border-radius: var(--radius-full); font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                  <?php echo getIcon('plus', '', 14); ?> Add Qualification
                </button>
              </div>

              <div id="qualificationsList">
                <!-- Qualification 1 (Default / Primary) -->
                <div class="qualification-card" id="qualCard_1" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 18px; margin-bottom: 12px; position: relative;">
                  <div class="flex-between items-center" style="margin-bottom: 12px;">
                    <span style="font-weight: 700; font-size: 0.88rem; color: var(--color-primary); display: flex; align-items: center; gap: 6px;">
                      <span style="background: var(--color-secondary-soft); color: var(--color-secondary); width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">1</span>
                      Qualification 1 (Highest / Primary Qualification) *
                    </span>
                  </div>

                  <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group" style="margin-bottom: 0;">
                      <label class="form-label" style="font-size: 0.82rem;">Degree / Course *</label>
                      <select class="form-control qual-degree-select" id="qualDegree_1" onchange="handleDegreeSelectChange(1)">
                        <option value="">Select Qualification / Degree</option>
                        <optgroup label="Doctors / Clinicians">
                          <option value="mbbs">MBBS</option>
                          <option value="md_ms" selected>MBBS + MD / MS</option>
                          <option value="dm_mch">DM / MCh Superspecialty</option>
                          <option value="dnb">DNB (National Board of Examinations)</option>
                          <option value="diploma_med">Diploma in Clinical Medicine</option>
                        </optgroup>
                        <optgroup label="Dental">
                          <option value="bds">BDS (Bachelor of Dental Surgery)</option>
                          <option value="mds">MDS (Master of Dental Surgery)</option>
                        </optgroup>
                        <optgroup label="Nursing">
                          <option value="bscnursing">B.Sc Nursing</option>
                          <option value="postbasic_nursing">Post Basic B.Sc Nursing</option>
                          <option value="mscnursing">M.Sc Nursing</option>
                          <option value="gnm">GNM (General Nursing & Midwifery)</option>
                          <option value="anm">ANM (Auxiliary Nurse Midwife)</option>
                        </optgroup>
                        <optgroup label="Pharmacy">
                          <option value="bpharm">B.Pharm (Bachelor of Pharmacy)</option>
                          <option value="mpharm">M.Pharm (Master of Pharmacy)</option>
                          <option value="pharmd">Pharm.D (Doctor of Pharmacy)</option>
                          <option value="dpharm">D.Pharm (Diploma in Pharmacy)</option>
                        </optgroup>
                        <optgroup label="Ayurveda & AYUSH">
                          <option value="bams">BAMS (Ayurvedic Medicine & Surgery)</option>
                          <option value="md_ayurveda">MD / MS Ayurveda</option>
                          <option value="bhms">BHMS (Homeopathic Medicine)</option>
                          <option value="bums">BUMS (Unani Medicine & Surgery)</option>
                          <option value="bnys">BNYS (Naturopathy & Yogic Sciences)</option>
                          <option value="bsms">BSMS (Siddha Medicine & Surgery)</option>
                        </optgroup>
                        <optgroup label="Paramedical & Allied Healthcare">
                          <option value="bpt">BPT (Physiotherapy)</option>
                          <option value="mpt">MPT (Master of Physiotherapy)</option>
                          <option value="bsc_mlt">B.Sc Medical Laboratory Tech (MLT)</option>
                          <option value="bsc_perfusion">B.Sc Perfusion Technology</option>
                          <option value="bsc_radiology">B.Sc Medical Imaging / Radiology Tech</option>
                          <option value="bsc_ot_tech">B.Sc Operation Theatre & Anaesthesia Tech</option>
                          <option value="bsc_dialysis">B.Sc Dialysis Technology</option>
                          <option value="bsc_cardiac">B.Sc Cardiac Care / Cath Lab Tech</option>
                        </optgroup>
                        <optgroup label="Hospital Administration & Medico-Legal">
                          <option value="mha">MHA (Masters in Hospital Administration)</option>
                          <option value="mba_hosp">MBA (Hospital & Healthcare Management)</option>
                          <option value="llb_medicolegal">LLB (Medico-Legal / Medical Jurisprudence / Law)</option>
                          <option value="mph">MPH (Master of Public Health)</option>
                          <option value="pg_hosp_mgmt">PG Diploma in Healthcare Management</option>
                        </optgroup>
                        <optgroup label="Hospital Support & Operations Staff">
                          <option value="gda">General Domestic Assistant (GDA) Certified</option>
                          <option value="patient_care">Patient Care Assistant (PCA)</option>
                          <option value="housekeeping">Hospital Housekeeping & Hospitality</option>
                          <option value="security">Hospital Security & Safety Operations</option>
                          <option value="porter">Hospital Porter / Orderly</option>
                          <option value="cssd">CSSD Technician</option>
                        </optgroup>
                        <optgroup label="General Education & Other">
                          <option value="10th">10th Standard / Matriculation</option>
                          <option value="12th">12th / Higher Secondary (Plus Two)</option>
                          <option value="general_degree">Bachelor's Degree (B.Sc / B.Com / B.A)</option>
                          <option value="general_master">Master's Degree (M.Sc / M.Com / M.A)</option>
                          <option value="other">Other Qualification (Candidate may type below)</option>
                        </optgroup>
                      </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                      <label class="form-label" style="font-size: 0.82rem;">Other / Specialization (Candidate May Type)</label>
                      <input type="text" class="form-control qual-other-input" id="qualOther_1" placeholder="e.g. LLB (Medico-Legal) / MBA / GDA / Fellowship">
                    </div>
                  </div>

                  <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 14px; margin-top: 12px;">
                    <div class="form-group" style="margin-bottom: 0;">
                      <label class="form-label" style="font-size: 0.82rem;">College / University / Institute *</label>
                      <input type="text" class="form-control qual-college-input" id="qualCollege_1" placeholder="e.g. Govt Medical College, Trivandrum / AIIMS / Kerala University">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                      <label class="form-label" style="font-size: 0.82rem;">Year of Passing *</label>
                      <select class="form-control qual-year-select" id="qualYear_1">
                        <?php 
                        $currYr = intval(date('Y'));
                        for ($y = $currYr + 2; $y >= 1980; $y--): ?>
                          <option value="<?php echo $y; ?>" <?php echo $y === ($currYr - 2) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <div style="margin-top: 12px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="addQualificationRow()" style="display: inline-flex; align-items: center; gap: 6px;">
                  <?php echo getIcon('plus', '', 14); ?>
                  <span>+ Add Another Qualification (e.g. Qualification 2: LLB, MBA, Fellowship, Diploma)</span>
                </button>
              </div>
            </div>

            <!-- COUNCIL REGISTRATION SECTION -->
            <div class="council-registration-section" style="background: #FFFFFF; border: 1.5px solid var(--color-border); border-radius: var(--radius-lg); padding: 22px; margin-bottom: 24px;">
              <div style="margin-bottom: 16px;">
                <h4 style="margin: 0 0 4px 0; font-size: 1.1rem; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                  <?php echo getIcon('shield-check', '', 20); ?>
                  Professional Council Registration
                </h4>
                <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-secondary);">
                  Mandatory for clinical practice in India. If you studied abroad or work in hospital support/administration, select "No" and provide remarks.
                </p>
              </div>

              <!-- Radio Option: Yes vs No -->
              <div style="background: var(--color-bg); padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--color-border); margin-bottom: 18px;">
                <label class="form-label" style="font-weight: 700; margin-bottom: 8px;">Do you hold an active Council / Professional Registration? *</label>
                <div style="display: flex; gap: 28px; flex-wrap: wrap;">
                  <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: var(--color-primary);">
                    <input type="radio" name="hasCouncilReg" value="yes" checked onchange="toggleCouncilFields(this.value)">
                    <span>Yes, I have Council Registration</span>
                  </label>
                  <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: var(--color-primary);">
                    <input type="radio" name="hasCouncilReg" value="no" onchange="toggleCouncilFields(this.value)">
                    <span>No (Studied Abroad / Support Role / License Under Process / Not Required)</span>
                  </label>
                </div>
              </div>

              <!-- YES FIELDS -->
              <div id="councilYesFields">
                <div class="form-group">
                  <label class="form-label" for="regCouncilCategory">Council Body / Category *</label>
                  <select id="regCouncilCategory" class="form-control" onchange="handleCouncilCategoryChange()">
                    <option value="">Select Council Body</option>
                    <option value="clinicians" selected>1. Clinicians — NMC (National Medical Commission) & Respective State Medical Councils</option>
                    <option value="nurses">2. Nurses — State Nursing Council / Indian Nursing Council (INC)</option>
                    <option value="dental">3. Dental (NDR ID) — National Dental Commission & State Dental Councils</option>
                    <option value="pharmacy">4. Pharmacy — State Pharmacy Council Registration & PCI</option>
                    <option value="ayurveda">5. Ayurveda (BAMS, MD, MS) — NCISM Registration (National Commission for Indian System of Medicine)</option>
                    <option value="unani">6. Unani Medicine (BUMS, MD, MS) — NCISM Registration (National Commission for Indian System of Medicine)</option>
                    <option value="paramedics">7. Paramedics & Allied — NCAHP (National Commission for Allied and Healthcare Professions)</option>
                    <option value="other">8. Other National / International Medical Council (DHA, MOH, GMC, etc.)</option>
                  </select>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                  <div class="form-group">
                    <label class="form-label" for="regCouncilRegNo">Registration Number (Reg. No.) *</label>
                    <input type="text" id="regCouncilRegNo" class="form-control" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. TCMC 45892 / NMC-2019-98214 / NDR-48902">
                    <span class="form-hint">Official Council License / Registration number</span>
                  </div>
                  <div class="form-group">
                    <label class="form-label" for="regCouncilName">Issuing Council / State *</label>
                    <input type="text" id="regCouncilName" class="form-control" placeholder="e.g. Travancore-Cochin Medical Council / NMC / KNC">
                    <span class="form-hint">State or National regulatory council name</span>
                  </div>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                  <div class="form-group">
                    <label class="form-label" for="regCouncilExpiry">Registration Validity / Expiry Date</label>
                    <input type="date" id="regCouncilExpiry" class="form-control">
                  </div>
                  <div class="form-group" style="display: flex; align-items: center; margin-top: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--color-primary);">
                      <input type="checkbox" id="regCouncilPermanent" onchange="toggleCouncilPermanent(this.checked)">
                      <span>Lifetime / Permanent Registration (No Expiry Date)</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- NO FIELDS (Supportive, helping foreign medical graduates & support roles) -->
              <div id="councilNoFields" style="display: none;">
                <div style="background: #F0F9FF; border: 1.5px solid #BAE6FD; border-radius: var(--radius-md); padding: 16px; margin-bottom: 16px;">
                  <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div style="color: #0284C7; flex-shrink: 0; margin-top: 2px;">
                      <?php echo getIcon('shield-check', '', 22); ?>
                    </div>
                    <div>
                      <h5 style="margin: 0 0 4px 0; color: #0369A1; font-size: 0.95rem;">Studied Abroad or No Active Council Registration? We Can Help!</h5>
                      <p style="margin: 0; font-size: 0.85rem; color: #0C4A6E; line-height: 1.5;">
                        Some doctors who completed studies abroad (Foreign Medical Graduates awaiting FMGE/NExT screening or state licensing), fresh graduates awaiting council badges, or staff in domestic/hospital support services (General Domestic Assistants, Housekeepers, Porters, Security Guards) may not currently have a council registration due to various reasons. <strong>HealthJobsGlobal actively helps you connect to suitable hospital jobs, clinical observer-ships, trainee opportunities, or non-registered healthcare placements!</strong>
                      </p>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="regCouncilNoRemarks">Registration Status & Remarks *</label>
                  <textarea id="regCouncilNoRemarks" class="form-control" rows="3" placeholder="Please specify your situation (e.g. Foreign Medical Graduate - MBBS from Russia/Philippines awaiting screening; Or GDA / Porter not requiring council registration; Or council renewal submitted and awaiting verification; etc.)"></textarea>
                  <span class="form-hint">Our hospital recruitment coordinators will evaluate your remarks and admit/recommend you to suitable hospital vacancies.</span>
                </div>
              </div>
            </div>

            <!-- FURTHER EDUCATION / TRAINING / FELLOWSHIP OPTION BOX -->
            <div class="education-fellowship-box" style="background: linear-gradient(135deg, rgba(14, 116, 144, 0.05) 0%, rgba(20, 184, 166, 0.05) 100%); border: 1.5px solid rgba(14, 116, 144, 0.25); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 24px;">
              <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="color: var(--color-secondary); flex-shrink: 0; margin-top: 2px;">
                  <?php echo getIcon('award', '', 24); ?>
                </div>
                <div style="flex-grow: 1;">
                  <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                    <input type="checkbox" id="checkFurtherEducation" onchange="toggleFurtherEducation(this.checked)" style="width: 18px; height: 18px;">
                    <span style="font-size: 1.05rem; font-weight: 700; color: var(--color-primary);">
                      Are you interested in further education / training / fellowship?
                    </span>
                  </label>
                  <p style="margin: 4px 0 0 28px; font-size: 0.85rem; color: var(--color-text-secondary);">
                    We provide healthcare education admissions, advanced clinical fellowships, and on-job training arrangements with partner universities and accredited teaching hospitals.
                  </p>
                </div>
              </div>

              <div id="furtherEducationDetails" style="display: none; padding-top: 14px; border-top: 1px dashed rgba(14, 116, 144, 0.2); margin-top: 14px;">
                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                  <div class="form-group">
                    <label class="form-label" for="regEducationInterestType">Preferred Program / Training Area</label>
                    <select id="regEducationInterestType" class="form-control">
                      <option value="clinical_fellowship">Clinical Fellowship (Critical Care, Diabetology, Cardiology, Emergency, etc.)</option>
                      <option value="higher_degree">Higher Degree Admission (MD, MS, DNB, M.Sc Nursing, MHA, MBA)</option>
                      <option value="on_job_training">On-the-Job Hospital Training / Hands-on Internship</option>
                      <option value="international_licensing">International Licensing Coaching (NHS PLAB, DHA, USMLE, NCLEX)</option>
                      <option value="support_staff_training">Support Staff Skill Certification (GDA, Infection Control, BLS)</option>
                      <option value="other">Other Educational / Training Program</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label" for="regEducationRemarks">Education & Training Remarks <span style="font-weight: 400; color: var(--color-text-muted);">(Optional)</span></label>
                    <input type="text" id="regEducationRemarks" class="form-control" placeholder="e.g. 1-year Fellowship in Diabetology; or M.Sc Nursing admission in Bengaluru">
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Professional Details (Experience, Notice, etc.) -->
            <div id="experiencedFields" class="dynamic-status-fields">
              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="regCurrentOrg">Current Hospital / Institution / Employer</label>
                  <input type="text" id="regCurrentOrg" class="form-control" placeholder="e.g. Aster Medcity / Apollo Hospitals / Lisie">
                </div>
                <div class="form-group">
                  <label class="form-label" for="regExperienceYears">Total Post-Qualification Experience *</label>
                  <select id="regExperienceYears" class="form-control">
                    <option value="0-1">0 - 1 Year</option>
                    <option value="1-3">1 - 3 Years</option>
                    <option value="4-7" selected>4 - 7 Years (Specialist / In-Charge)</option>
                    <option value="8-12">8 - 12 Years (Consultant / Supervisor)</option>
                    <option value="12+">12+ Years (Senior Consultant / HOD)</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="regNoticeExp">Notice Period *</label>
                <select id="regNoticeExp" class="form-control">
                  <option value="immediate">Immediate Available</option>
                  <option value="15days">15 Days</option>
                  <option value="30days" selected>30 Days (Standard)</option>
                  <option value="60days">60 Days</option>
                  <option value="90days">90 Days</option>
                </select>
              </div>
            </div>

            <!-- Dynamic Fields for Fresher / Intern / Student -->
            <div id="fresherFields" class="dynamic-status-fields" style="display: none;">
              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="regCollegeName">Medical / Healthcare College / University</label>
                  <input type="text" id="regCollegeName" class="form-control" placeholder="e.g. Govt Medical College / Amrita / KUHS">
                </div>
                <div class="form-group">
                  <label class="form-label" for="regDeptInterest">Preferred Hospital Department / Area</label>
                  <select id="regDeptInterest" class="form-control">
                    <option>Critical Care & ICU</option>
                    <option>Emergency & Trauma Care</option>
                    <option>Operation Theatre & Surgery</option>
                    <option>General Medicine & Wards</option>
                    <option>Pediatrics & Neonatology</option>
                    <option>Hospital Support & Operations (GDA / Security / Housekeeping)</option>
                    <option>Hospital Administration & Front Office</option>
                    <option>Diagnostic Laboratory & Pathology</option>
                    <option>Pharmacy & Stores</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- LANGUAGES SPOKEN (Interactive Keyword / Tag Chip Input) -->
            <div class="form-group" style="margin-top: 24px; padding-top: 18px; border-top: 1px dashed var(--color-border);">
              <label class="form-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-weight: 600; color: var(--color-text-primary);">Languages Known & Spoken Fluently *</span>
                <span class="form-hint" style="margin: 0; color: var(--color-secondary); font-size: 0.8rem; font-weight: 500;">Type language & press Enter (or comma)</span>
              </label>
              <div class="tags-input-wrapper" id="languagesTagWrapper" onclick="document.getElementById('tagInputLanguages').focus()">
                <div id="languageChipsContainer" style="display: contents;"></div>
                <input type="text" id="tagInputLanguages" class="tag-inline-input" placeholder="Type language (e.g. English, Malayalam, Hindi) & press Enter..." onkeydown="handleLanguageTagKeyDown(event)">
              </div>
              <div class="tag-suggestions-row">
                <span style="font-size: 0.78rem; color: var(--color-text-muted); font-weight: 600;">Quick Add:</span>
                <button type="button" class="suggestion-chip-btn" data-lang="English" onclick="addLanguageTag('English')">+ English</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Malayalam" onclick="addLanguageTag('Malayalam')">+ Malayalam</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Hindi" onclick="addLanguageTag('Hindi')">+ Hindi</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Arabic" onclick="addLanguageTag('Arabic')">+ Arabic</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Tamil" onclick="addLanguageTag('Tamil')">+ Tamil</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Kannada" onclick="addLanguageTag('Kannada')">+ Kannada</button>
                <button type="button" class="suggestion-chip-btn" data-lang="German" onclick="addLanguageTag('German')">+ German</button>
              </div>
              <input type="hidden" id="selectedLanguagesInput" name="languages" value="English, Malayalam">
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 24px;">
              <button type="button" class="btn btn-outline" onclick="goToStep(1, 'back')">Back</button>
              <button type="button" class="btn btn-primary" onclick="validateAndGoToStep3()">
                <span>Next: Location & Package</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 3: Career, Confidentiality & Salary Preferences -->
          <div class="wizard-step-content" id="stepContent3" style="display: none;">
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 6px;" id="step3Title">Step 3: Location Preferences & Salary Expectation</h3>
            <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 20px;" id="step3Subtitle">Set your target hospital locations and compensation benchmarks.</p>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="regCurrentLoc" id="labelCurrentLoc">Current Location / Base City *</label>
                <input type="text" id="regCurrentLoc" class="form-control" placeholder="e.g. Kochi, Kerala" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="regPreferredLoc" id="labelPreferredLoc">Preferred Hospital Locations *</label>
                <input type="text" id="regPreferredLoc" class="form-control" placeholder="e.g. Kochi, Bengaluru, Dubai, Abu Dhabi" required>
              </div>
            </div>

            <!-- Experienced Salary Grid -->
            <div class="grid" id="rowExpSalary" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group" id="groupCurrentSalary">
                <label class="form-label" for="regCurrentSalary">Current Annual Package / Monthly Salary</label>
                <input type="text" id="regCurrentSalary" class="form-control" placeholder="e.g. ₹18,00,000 / year or ₹35,000 / month">
                <span class="form-hint">Used for confidential benchmarking</span>
              </div>
              <div class="form-group" id="groupExpectedSalary">
                <label class="form-label" for="regExpectedSalary" id="labelExpectedSalary">Expected Annual Package / Monthly Salary *</label>
                <input type="text" id="regExpectedSalary" class="form-control" placeholder="e.g. ₹24,00,000 / year or ₹45,000 / month">
                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.8rem; margin-top: 4px; cursor: pointer;">
                  <input type="checkbox" checked> Package is Negotiable
                </label>
              </div>
            </div>

            <!-- Fresher Availability & Stipend Grid -->
            <div class="grid" id="rowFresherSalary" style="grid-template-columns: 1fr 1fr; gap: 16px; display: none;">
              <div class="form-group" id="groupFresherAvailability">
                <label class="form-label" for="regFresherAvailability">Joining Availability *</label>
                <select id="regFresherAvailability" class="form-control">
                  <option value="immediate" selected>Immediate (Ready to Join)</option>
                  <option value="15days">Within 15 Days</option>
                  <option value="after_internship">Upon Internship / Course Completion</option>
                  <option value="exam_results">Awaiting Final Results / Council License</option>
                </select>
                <span class="form-hint">When you can commence hospital duties</span>
              </div>
              <div class="form-group" id="groupExpectedStipend">
                <label class="form-label" for="regExpectedStipend">Expected Starting Salary / Stipend *</label>
                <input type="text" id="regExpectedStipend" class="form-control" placeholder="e.g. ₹35,000 - ₹50,000 / month or Standard Scale">
                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.8rem; margin-top: 4px; cursor: pointer;">
                  <input type="checkbox" checked> Open to standard hospital trainee / resident scale
                </label>
              </div>
            </div>

            <!-- Experienced Confidentiality Option -->
            <div class="form-group" id="groupHideCurrentOrg">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--color-secondary);">
                <input type="checkbox" id="regHideCurrentOrg" checked>
                <span>Hide current hospital from employer searches (Confidentiality Mode)</span>
              </label>
              <span class="form-hint">Prevents your current hospital HR department from seeing your active profile</span>
            </div>

            <!-- Career Preferences & Shift Flexibility -->
            <div class="form-group" id="groupFresherWillingness" style="background: #F8FAFC; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 14px;">
              <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">Career Preferences & Shift Flexibility:</label>
              <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; color: var(--color-text-secondary);">
                  <input type="checkbox" id="checkRotationalShifts" checked>
                  <span>Open to Rotational Day/Night & ICU duty shifts</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; color: var(--color-text-secondary);">
                  <input type="checkbox" id="checkFellowshipRelocate" checked>
                  <span>Willing to relocate across India / Abroad for Hospital Postings or Training</span>
                </label>
              </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 24px;">
              <button type="button" class="btn btn-outline" onclick="goToStep(2, 'back')">Back</button>
              <button type="button" class="btn btn-primary" onclick="validateAndGoToStep4()">
                <span id="step3NextBtnText">Next: Profile Activation</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 4: Profile Confirmation & Final Submission (NO CERTIFICATE UPLOAD REQUIRED) -->
          <div class="wizard-step-content" id="stepContent4" style="display: none;">
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 6px;">Step 4: Profile Confirmation & Final Activation</h3>
            <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 18px;">Review your registration details and activate your verified candidate profile.</p>

            <!-- Reassurance Banner: NO CERTIFICATE UPLOAD REQUIRED -->
            <div style="background: #F0FDF4; border: 1.5px solid #86EFAC; border-radius: var(--radius-lg); padding: 18px 20px; margin-bottom: 22px; display: flex; gap: 14px; align-items: flex-start;">
              <div style="background: #16A34A; color: #FFFFFF; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                <?php echo getIcon('check', '', 18); ?>
              </div>
              <div>
                <h4 style="margin: 0 0 4px 0; font-size: 1.05rem; color: #166534;">Uploading Education & Council Certificates is NOT Required</h4>
                <p style="margin: 0; font-size: 0.85rem; color: #15803D; line-height: 1.5;">
                  To make registration fast, convenient, and accessible for all healthcare job seekers, <strong>uploading education and professional registration certificates is not required</strong>. Hospital recruiters can view your profile credentials directly. Any required original or digital certificates can be presented later during interview onboarding.
                </p>
              </div>
            </div>

            <!-- Optional CV / Resume Upload -->
            <div class="form-group" style="margin-bottom: 22px;">
              <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Upload CV / Bio-Data <span style="font-weight: normal; color: var(--color-text-muted);">(Optional — You can skip this)</span></span>
                <span style="font-size: 0.75rem; color: #0284C7; font-weight: 600; background: #E0F2FE; padding: 2px 8px; border-radius: var(--radius-full);">Optional</span>
              </label>
              <div style="border: 2px dashed var(--color-border); border-radius: var(--radius-lg); padding: 24px; text-align: center; background: var(--color-bg); cursor: pointer;" onclick="document.getElementById('wizardCVFile').click()">
                <div style="color: var(--color-secondary); margin-bottom: 8px;">
                  <?php echo getIcon('upload-cloud', '', 36); ?>
                </div>
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-primary);" id="wizardCVText">Click to attach CV / Resume (Optional)</div>
                <div style="font-size: 0.78rem; color: var(--color-text-muted); margin-top: 4px;">PDF or DOCX (Max 10MB) — If you don't have a CV right now, our system builds your digital profile automatically.</div>
                <input type="file" id="wizardCVFile" style="display: none;" accept=".pdf,.doc,.docx" onchange="document.getElementById('wizardCVText').textContent = this.files[0] ? this.files[0].name : 'CV uploaded';">
              </div>
            </div>

            <!-- Account Password & Single Login Credentials -->
            <div style="background: #FFFFFF; border: 1.5px solid var(--color-border); border-radius: var(--radius-lg); padding: 22px; margin-bottom: 22px;">
              <h4 style="font-size: 1.05rem; color: var(--color-primary); margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                <?php echo getIcon('lock', '', 18); ?>
                Create Account Password (For Portal Login)
              </h4>
              <p style="font-size: 0.82rem; color: var(--color-text-secondary); margin-bottom: 16px;">
                You will use your registered email address or mobile number with this password to sign into your candidate dashboard.
              </p>
              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                  <label class="form-label" for="candPass">Create Account Password *</label>
                  <input type="password" id="candPass" class="form-control" placeholder="••••••••" required>
                  <span class="form-hint">Minimum 6 characters</span>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                  <label class="form-label" for="candConfirmPass">Confirm Password *</label>
                  <input type="password" id="candConfirmPass" class="form-control" placeholder="••••••••" required>
                  <span class="form-hint">Type your password again</span>
                </div>
              </div>
            </div>

            <!-- Healthcare Professional Honor Code & Declaration -->
            <div style="background: #FFFDF9; border: 1px solid #FEEBC8; padding: 16px; border-radius: var(--radius-md); margin-bottom: 22px;">
              <h5 style="color: #92400E; margin-bottom: 6px; font-size: 0.95rem;">Healthcare Professional Declaration</h5>
              <p style="font-size: 0.85rem; color: #78350F; line-height: 1.5; margin: 0;">
                I certify that the qualifications, professional status, and contact details submitted are accurate. I understand that hospital HRs and recruitment teams will match and contact me based on my profile details.
              </p>
              <label style="display: flex; align-items: center; gap: 8px; margin-top: 10px; cursor: pointer; font-size: 0.85rem; font-weight: 700; color: #92400E;">
                <input type="checkbox" required checked> I agree to terms, privacy policy & profile activation
              </label>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 24px;">
              <button type="button" class="btn btn-outline" onclick="goToStep(3, 'back')">Back</button>
              <button type="button" class="btn btn-primary btn-lg" id="candidateSubmitBtn" onclick="finishCandidateRegistration()">
                <span id="candidateSubmitBtnText">Complete Registration & Activate Profile</span>
                <?php echo getIcon('check-circle', '', 18); ?>
              </button>
            </div>
          </div>

        </form>

      </div>

      <!-- ======================================================================
           SECTION 2: HEALTHCARE EMPLOYER & INSTITUTION REGISTRATION
           ====================================================================== -->
      <div id="employerRegistrationSection" style="<?php echo $activeRole === 'employer' ? 'display: block;' : 'display: none;'; ?>">
        
        <!-- Header -->
        <div class="flex-between items-center" style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 20px; flex-wrap: wrap; gap: 12px;">
          <div>
            <span class="eyebrow" style="margin-bottom: 2px;">Healthcare Verticals & Placement Solutions</span>
            <h1 style="font-size: 1.85rem; color: var(--color-primary); margin: 0;">Employer Registration</h1>
            <p style="color: var(--color-text-secondary); font-size: 0.9rem; margin-top: 4px;">
              Providing verified healthcare candidates & institutional solutions to <strong>all healthcare verticals</strong> (Hospitals, Old Age Homes, Home Care & Geriatrics, Ambulance Services, Healthcare Research Firms, Medical Insurance Companies, Colleges, etc.) + <strong>Placement Agencies</strong>.
            </p>
          </div>
          <div style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 6px 14px; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary);">
            Employer Ref: <span style="font-family: monospace;"><?php echo $generatedHospitalId; ?></span>
          </div>
        </div>

        <!-- Multi-step Indicator Header for Employer Wizard -->
        <div class="wizard-steps-header" id="empWizardStepsHeader">
          <div class="step-indicator active" id="empStepIndicator1" style="cursor: pointer;" onclick="goToEmpStep(1)">
            <div class="step-bubble">1</div>
            <span class="step-label">Organization & Category</span>
          </div>
          <div class="step-indicator" id="empStepIndicator2" style="cursor: pointer;" onclick="goToEmpStep(2)">
            <div class="step-bubble">2</div>
            <span class="step-label">Campus & Contacts</span>
          </div>
          <div class="step-indicator" id="empStepIndicator3" style="cursor: pointer;" onclick="goToEmpStep(3)">
            <div class="step-bubble">3</div>
            <span class="step-label">Authorizing Admin</span>
          </div>
          <div class="step-indicator" id="empStepIndicator4" style="cursor: pointer;" onclick="goToEmpStep(4)">
            <div class="step-bubble">4</div>
            <span class="step-label">Services & Activation</span>
          </div>
        </div>

        <form id="employerMultiStepForm" onsubmit="event.preventDefault();">
          
          <!-- STEP 1: Institution Classification & Organization Profile -->
          <div class="wizard-step-content" id="empStepContent1">
            <div style="margin-bottom: 22px;">
              <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0 0 4px 0;">Step 1: Organization Profile & Category</h3>
              <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin: 0;">Specify your establishment name, healthcare vertical classification, and operational scale.</p>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="empOrgName">Organization / Institution Name *</label>
                <input type="text" id="empOrgName" class="form-control" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. Aster Medcity / Lisie Heart Hospital / Silver Oaks Geriatric Care" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="empOrgWebsite">Official Website URL</label>
                <input type="url" id="empOrgWebsite" class="form-control" placeholder="https://www.organization.com">
              </div>
            </div>

            <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="empOrgCategory">Organization Category *</label>
                <select id="empOrgCategory" class="form-control" required onchange="handleOrgCategoryChange()">
                  <option value="">Select Category *</option>
                  <option value="hospital" selected>1. Hospital (Multi-Specialty / Super-Specialty / Quaternary / Community)</option>
                  <option value="diagnostics">2. Healthcare Diagnostics (Pathology, Imaging & Radiology Laboratories)</option>
                  <option value="medical_colleges">3. Medical Colleges & Teaching Hospitals</option>
                  <option value="nursing_colleges">4. Nursing Colleges & Institutes</option>
                  <option value="allied_sciences">5. Healthcare Allied Sciences (Paramedical / Physiotherapy / Rehab Colleges)</option>
                  <option value="mgmt_institutions">6. Healthcare Management Institutions (MHA / MBA Hospital Admin Colleges)</option>
                  <option value="devices_tech">7. Medical Devices & Technology Companies</option>
                  <option value="insurance">8. Medical Insurance & Third-Party Administrators (TPA)</option>
                  <option value="research">9. Medical & Clinical Research Organizations (CROs)</option>
                  <option value="pharma">10. Pharmaceuticals & Life Sciences</option>
                  <option value="old_age_homes">11. Old Age Homes & Geriatric Care Centers</option>
                  <option value="home_care">12. Home Care & Geriatrics Health Services</option>
                  <option value="ambulance">13. Ambulance & Emergency Medical Transport Services</option>
                  <option value="placement_agency">14. Placement & Recruitment Agency (Healthcare Staffing / HR Partner)</option>
                  <option value="other_vertical">15. Other Healthcare Vertical</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="empScaleCapacity">Operating Capacity / Scale</label>
                <select id="empScaleCapacity" class="form-control">
                  <option value="large">Large (500+ Beds / 1000+ Students / Pan-India)</option>
                  <option value="medium" selected>Medium (100 - 500 Beds / 250+ Staff)</option>
                  <option value="compact">Specialty / Clinic / Under 100 Staff</option>
                  <option value="agency">Agency / Consulting Partner</option>
                </select>
              </div>
            </div>

            <div id="empOtherCategoryRow" style="display: none; margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="empOtherCategoryText">Specify Healthcare Vertical / Domain Name *</label>
                <input type="text" id="empOtherCategoryText" class="form-control" placeholder="e.g. Tele-medicine Network, Hospice Care, Blood Bank, Specialized Rehab">
              </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 28px;">
              <button type="button" class="btn btn-primary" onclick="validateAndGoToEmpStep2()">
                <span>Next: Campus & Contacts</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 2: Organization Address & Institutional Contacts (Separate from Administrator) -->
          <div class="wizard-step-content" id="empStepContent2" style="display: none;">
            <div style="margin-bottom: 22px;">
              <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0 0 4px 0;">Step 2: Organization Address & Institutional Contact Details</h3>
              <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin: 0;">
                Official institutional telephone, general email ID, and registered campus address (strictly distinct from Authorizing Administrator).
              </p>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="empOrgGeneralEmail">Organization General / Institutional Email ID *</label>
                <input type="email" id="empOrgGeneralEmail" class="form-control" placeholder="info@organization.org / hr@hospital.com" required>
                <span class="form-hint">Official reception or institutional mailbox (distinct from Administrator personal email)</span>
              </div>
              <div class="form-group">
                <label class="form-label" for="empOrgGeneralPhone">Organization Telephone / Board Line Number *</label>
                <input type="tel" id="empOrgGeneralPhone" class="form-control" placeholder="+91 484 290 5000 / 080-6712345" required>
                <span class="form-hint">Official landline or main reception switchboard (distinct from Administrator mobile)</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="empOrgAddress">Organization Registered Street / Campus Address *</label>
              <textarea id="empOrgAddress" class="form-control" rows="2" placeholder="e.g. Medical City Campus, NH-66 Bypass Road, Cheranalloor, South Chittoor" required></textarea>
            </div>

            <div class="grid" style="grid-template-columns: 2fr 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="empOrgCity">City & State *</label>
                <input type="text" id="empOrgCity" class="form-control" placeholder="e.g. Kochi, Kerala" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="empOrgPincode">Postal PIN Code *</label>
                <input type="text" id="empOrgPincode" class="form-control" placeholder="e.g. 682027" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="empOrgCountry">Country *</label>
                <select id="empOrgCountry" class="form-control">
                  <option value="India" selected>India</option>
                  <option value="UAE">United Arab Emirates</option>
                  <option value="Saudi Arabia">Saudi Arabia</option>
                  <option value="Oman">Oman</option>
                  <option value="Qatar">Qatar</option>
                  <option value="Kuwait">Kuwait</option>
                  <option value="Bahrain">Bahrain</option>
                  <option value="UK">United Kingdom</option>
                  <option value="Other">Other Country</option>
                </select>
              </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 28px;">
              <button type="button" class="btn btn-outline" onclick="goToEmpStep(1, 'back')">Back</button>
              <button type="button" class="btn btn-primary" onclick="validateAndGoToEmpStep3()">
                <span>Next: Authorizing Administrator</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 3: Authorizing Administrator Details -->
          <div class="wizard-step-content" id="empStepContent3" style="display: none;">
            <div style="margin-bottom: 22px;">
              <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0 0 4px 0;">Step 3: Authorizing Administrator</h3>
              <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin: 0;">
                The designated director, superintendent, or senior executive authorized to approve recruitment, enter agreements, and manage institutional services.
              </p>
            </div>

            <div style="display: flex; gap: 14px; align-items: center; background: #F8FAFC; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: 20px;">
              <div style="color: var(--color-secondary); flex-shrink: 0;">
                <?php echo getIcon('shield', '', 28); ?>
              </div>
              <div style="font-size: 0.82rem; color: var(--color-text-secondary); line-height: 1.45;">
                <strong>Executive Governance & Verification:</strong> Recruitment agreements, verified healthcare candidate shortlists, and official staffing orders will be executed through this authorizing executive.
              </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="empAdminName">Authorizing Administrator Full Name *</label>
                <input type="text" id="empAdminName" class="form-control" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. Dr. Radhakrishnan / Ms. Rekha Nair" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="empAdminDesignation">Administrator Official Designation *</label>
                <input type="text" id="empAdminDesignation" class="form-control" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. Medical Superintendent / Managing Director / Chief HR Officer / Principal" required>
              </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="empAdminEmail">Administrator Direct Official Email *</label>
                <input type="email" id="empAdminEmail" class="form-control" placeholder="dr.radhakrishnan@hospital.com" required>
                <span class="form-hint">Direct executive email address for contract execution & administrative notices</span>
              </div>
              <div class="form-group">
                <label class="form-label" for="empAdminPhone">Administrator Direct Mobile / WhatsApp *</label>
                <input type="tel" id="empAdminPhone" class="form-control" placeholder="+91 98460 99887" required>
                <span class="form-hint">Used for urgent recruitment escalations and candidate interview alerts</span>
              </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 28px;">
              <button type="button" class="btn btn-outline" onclick="goToEmpStep(2, 'back')">Back</button>
              <button type="button" class="btn btn-primary" onclick="validateAndGoToEmpStep4()">
                <span>Next: Services & Activation</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 4: Services Required & Account Activation -->
          <div class="wizard-step-content" id="empStepContent4" style="display: none;">
            <div style="margin-bottom: 22px;">
              <div class="flex-between items-center" style="margin-bottom: 4px;">
                <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0;">Step 4: Services Required & Account Activation</h3>
                <span style="font-size: 0.78rem; background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 700; padding: 2px 10px; border-radius: var(--radius-full);">Select all that apply</span>
              </div>
              <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin: 0;">
                Tell us which staffing, compliance, training, or outsourcing services your organization requires:
              </p>
            </div>

            <div class="services-looking-grid">
              <!-- Service 1: Accreditations -->
              <label class="service-select-card has-selected" id="cardServiceAccred">
                <input type="checkbox" name="servicesLookingFor[]" value="accreditations" checked onchange="toggleServiceCardStyle(this)" style="margin-top: 3px;">
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-primary);">1. Accreditations</div>
                  <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 2px;">NABH, JCI, NABL, ISO, & Nursing Council quality accreditations and hospital audit preparation.</div>
                </div>
              </label>

              <!-- Service 2: Candidate / document Verification -->
              <label class="service-select-card has-selected" id="cardServiceVerif">
                <input type="checkbox" name="servicesLookingFor[]" value="verification" checked onchange="toggleServiceCardStyle(this)" style="margin-top: 3px;">
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-primary);">2. Candidate / Document Verification</div>
                  <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 2px;">Primary source council checks (NMC, KNC, PCI, INC), degree authenticity, & background credentialing.</div>
                </div>
              </label>

              <!-- Service 3: Manpower Outsourcing, Recruitment Outsourcing -->
              <label class="service-select-card has-selected" id="cardServiceRpo">
                <input type="checkbox" name="servicesLookingFor[]" value="manpower_rpo" checked onchange="toggleServiceCardStyle(this)" style="margin-top: 3px;">
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-primary);">3. Manpower Outsourcing, Recruitment Outsourcing</div>
                  <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 2px;">Full-scale RPO, bulk doctor & ICU nurse hiring, specialist doctors, and permanent talent staffing.</div>
                </div>
              </label>

              <!-- Service 4: Outsourcing of services -->
              <label class="service-select-card" id="cardServiceOutsource">
                <input type="checkbox" name="servicesLookingFor[]" value="services_outsourcing" onchange="toggleServiceCardStyle(this)" style="margin-top: 3px;">
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-primary);">4. Outsourcing of Services</div>
                  <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 2px;">General Domestic Assistants (GDA), hospital housekeeping, security, CSSD, and ambulance fleet operations.</div>
                </div>
              </label>

              <!-- Service 5: Training : ACLS, BLS, NABH, Motivation etc. -->
              <label class="service-select-card" id="cardServiceTraining">
                <input type="checkbox" name="servicesLookingFor[]" value="training" onchange="toggleServiceCardStyle(this)" style="margin-top: 3px;">
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-primary);">5. Training: ACLS, BLS, NABH, Motivation etc.</div>
                  <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 2px;">AHA-certified BLS & ACLS drills, NABH quality protocols, soft skills, motivational & infection control workshops.</div>
                </div>
              </label>

              <!-- Service 6: Any other services -->
              <label class="service-select-card" id="cardServiceOther">
                <input type="checkbox" name="servicesLookingFor[]" value="other_services" id="checkOtherServices" onchange="toggleOtherServicesInput(this.checked); toggleServiceCardStyle(this);" style="margin-top: 3px;">
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-primary);">6. Any Other Services</div>
                  <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 2px;">Customized healthcare solutions, overseas placements, hospital commissioning advisory, or operational partnerships.</div>
                </div>
              </label>
            </div>

            <!-- Expandable input for Any other services -->
            <div id="otherServicesRemarkBox" style="display: none; background: var(--color-bg); padding: 14px 16px; border-radius: var(--radius-md); border: 1px dashed var(--color-border); margin-top: 14px; margin-bottom: 24px;">
              <label class="form-label" for="empOtherServicesDetails">Please Specify Details of Other Services Required:</label>
              <textarea id="empOtherServicesDetails" class="form-control" rows="2" placeholder="e.g. Seeking placement agency partnership for UAE recruitment / Need overseas nurse deployment / Ambulance fleet lease / Equipment AMC..."></textarea>
            </div>

            <!-- Account Security & Governance Declaration (No Password Required during Registration) -->
            <div style="border-top: 1px dashed var(--color-border); padding-top: 20px; margin-top: 20px;">
              <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>🛡️</span>
                <span>Account Governance & Verification Protocol</span>
              </h4>

              <div style="background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%); border: 1.5px solid #BAE6FD; border-radius: var(--radius-lg); padding: 18px 22px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.05);">
                <div style="display: flex; align-items: flex-start; gap: 14px;">
                  <span style="font-size: 1.6rem; line-height: 1;">⚡</span>
                  <div>
                    <div style="font-weight: 800; color: #0369A1; font-size: 0.95rem; margin-bottom: 4px;">
                      No Password Required at Registration
                    </div>
                    <div style="font-size: 0.86rem; color: #0C4A6E; line-height: 1.55;">
                      Your organization credentials will be submitted for administrative verification. <strong>The account will be active after verification</strong>. Upon approval, our system will automatically generate a default random temporary password and email it directly to the Authorizing Administrator.
                    </div>
                    <div style="margin-top: 10px; font-size: 0.8rem; color: #0284C7; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                      <span>✓</span>
                      <span>On your first sign-in, you will be prompted to replace the temporary password with your personal secure password.</span>
                    </div>
                  </div>
                </div>
              </div>

              <div style="background: #F0FDF4; border: 1px solid #BBF7D0; padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px;">
                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 0.85rem; color: #166534;">
                  <input type="checkbox" id="empGovernanceCheck" required checked style="margin-top: 2px;">
                  <span>
                    I certify as the <strong>Authorizing Administrator</strong> that this establishment is a licensed healthcare organization or accredited recruitment agency. We agree to adhere to ethical candidate hiring standards, regulatory compliance, and HealthJobsGlobal institutional terms.
                  </span>
                </label>
              </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 28px;">
              <button type="button" class="btn btn-outline" onclick="goToEmpStep(3, 'back')">Back</button>
              <button type="button" id="hospitalRegSubmitBtn" class="btn btn-primary btn-lg" onclick="finishEmployerRegistration()">
                <span>Complete Registration & Access Solutions</span>
                <?php echo getIcon('check-circle', '', 18); ?>
              </button>
            </div>
          </div>

        </form>

      </div>

    </div>

  </div>
</div>

<!-- Apple-Style "Waiting for Approval" Animation Overlay -->
<div id="waitingForApprovalOverlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: #FFFFFF; border-radius: 24px; padding: 42px 34px; max-width: 490px; width: 100%; text-align: center; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.9); transform: scale(0.95); animation: appleModalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
    
    <!-- Luminous Animated Loader Graphic -->
    <div style="position: relative; width: 84px; height: 84px; margin: 0 auto 20px auto; display: flex; align-items: center; justify-content: center;">
      <div style="position: absolute; inset: 0; border-radius: 50%; background: radial-gradient(circle, rgba(245, 158, 11, 0.25) 0%, rgba(245, 158, 11, 0) 70%); animation: pulse-ring 2s infinite;"></div>
      <div style="position: absolute; inset: 2px; border-radius: 50%; border: 3px solid #FEF3C7; border-top-color: #D97706; animation: spin-loader 1.2s linear infinite;"></div>
      <div style="width: 58px; height: 58px; border-radius: 18px; background: linear-gradient(135deg, #FEF3C7, #FDE68A); display: flex; align-items: center; justify-content: center; font-size: 1.7rem; box-shadow: 0 4px 14px rgba(217, 119, 6, 0.25);">
        ⏳
      </div>
    </div>

    <div style="display: inline-flex; align-items: center; gap: 8px; background: #FFFBEB; border: 1px solid #FCD34D; color: #92400E; padding: 5px 14px; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; margin-bottom: 12px; letter-spacing: 0.02em;">
      <span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #F59E0B; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.3);"></span>
      <span>Awaiting Administrative Verification</span>
    </div>

    <h3 style="font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif; font-size: 1.4rem; font-weight: 700; color: #0F172A; margin: 0 0 8px 0; letter-spacing: -0.02em;">
      Waiting for Approval
    </h3>

    <p style="font-size: 0.9rem; color: #64748B; line-height: 1.55; margin: 0 0 22px 0;">
      Registration for <strong id="approvalModalOrgName" style="color: #0F172A;">your organization</strong> has been submitted. The account will be active after verification by the administration.
    </p>

    <!-- Animated Smooth Progress Bar -->
    <div style="background: #F1F5F9; border-radius: 9999px; height: 6px; width: 100%; overflow: hidden; margin-bottom: 12px;">
      <div id="approvalProgressBar" style="background: linear-gradient(90deg, #D97706, #F59E0B); height: 100%; width: 0%; border-radius: 9999px; transition: width 3s linear;"></div>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem; color: #94A3B8; margin-bottom: 22px;">
      <span>Redirecting to homepage</span>
      <span><strong id="approvalCountdownText" style="color: #0F172A;">3</strong>s</span>
    </div>

    <div>
      <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary" style="border-radius: 9999px; padding: 9px 26px; font-size: 0.88rem; font-weight: 700; text-decoration: none;">
        <span>Return to Homepage Now</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </a>
    </div>

  </div>
</div>

<style>
@keyframes appleModalPop {
  0% { transform: scale(0.92); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
@keyframes spin-loader {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
@keyframes pulse-ring {
  0% { transform: scale(0.85); opacity: 0.6; }
  50% { transform: scale(1.25); opacity: 0.2; }
  100% { transform: scale(0.85); opacity: 0.6; }
}
</style>

<script>
let currentStep = 1;
let isMobileVerified = false;
let isEmailVerified = false;
let generatedMobileOtp = '482910';
let generatedEmailCode = '619284';

function setRegistrationRole(role) {
  const candBtn = document.getElementById('roleBtnCandidate');
  const empBtn = document.getElementById('roleBtnEmployer');
  const candSec = document.getElementById('candidateRegistrationSection');
  const empSec = document.getElementById('employerRegistrationSection');

  if (role === 'candidate') {
    if (candBtn) candBtn.className = 'btn btn-primary';
    if (empBtn) empBtn.className = 'btn btn-outline';
    if (candSec) candSec.style.display = 'block';
    if (empSec) empSec.style.display = 'none';
  } else {
    if (empBtn) empBtn.className = 'btn btn-primary';
    if (candBtn) candBtn.className = 'btn btn-outline';
    if (empSec) {
      empSec.style.display = 'block';
      goToEmpStep(1);
    }
    if (candSec) candSec.style.display = 'none';
  }
}

function syncWhatsappWithMobile() {
  const check = document.getElementById('syncWhatsappCheck');
  const whatsappInput = document.getElementById('regWhatsapp');
  const mobileInput = document.getElementById('regMobile');
  if (check && check.checked && whatsappInput && mobileInput) {
    whatsappInput.value = mobileInput.value;
  }
}

function highlightError(element) {
  element.classList.add('is-invalid-shake');
  setTimeout(() => {
    element.classList.remove('is-invalid-shake');
  }, 600);
}

function sendMobileOtp() {
  const mobileInput = document.getElementById('regMobile');
  const mobileVal = mobileInput.value.trim();
  if (!mobileVal || mobileVal.length < 8) {
    showToast('Please enter your mobile number first', 'error');
    mobileInput.focus();
    highlightError(mobileInput);
    return;
  }

  generatedMobileOtp = Math.floor(100000 + Math.random() * 900000).toString();
  
  const otpInput = document.getElementById('regMobileOtp');
  const verifyBtn = document.getElementById('btnVerifyMobileOtp');
  const timerText = document.getElementById('mobileOtpTimerText');
  const sendBtn = document.getElementById('btnSendMobileOtp');

  otpInput.disabled = false;
  verifyBtn.disabled = false;
  otpInput.value = '';
  otpInput.focus();

  sendBtn.textContent = 'Resend OTP';
  timerText.innerHTML = `<span style="color: #059669; font-weight: 600;">✓ OTP sent!</span> Demo OTP: <strong style="font-family: monospace; letter-spacing: 1px; color: var(--color-primary);">${generatedMobileOtp}</strong>`;

  showToast(`SMS OTP sent to ${mobileVal}! Demo OTP: ${generatedMobileOtp}`);
  otpInput.setAttribute('placeholder', `e.g. ${generatedMobileOtp}`);
}

function verifyMobileOtp() {
  const otpInput = document.getElementById('regMobileOtp');
  const enteredOtp = otpInput.value.trim();
  const mobileInput = document.getElementById('regMobile');

  if (!enteredOtp) {
    showToast('Please enter the 6-digit OTP', 'error');
    otpInput.focus();
    highlightError(otpInput);
    return;
  }

  // Accept generated OTP or any 6-digit input for seamless testing
  if (enteredOtp === generatedMobileOtp || enteredOtp.length === 6) {
    isMobileVerified = true;
    otpInput.classList.remove('is-invalid-shake');
    otpInput.classList.add('is-verified-success');
    mobileInput.classList.add('is-verified-success');
    otpInput.disabled = true;
    
    const verifyBtn = document.getElementById('btnVerifyMobileOtp');
    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Verified ✓';
    verifyBtn.style.background = '#059669';
    verifyBtn.style.borderColor = '#059669';

    document.getElementById('btnSendMobileOtp').style.display = 'none';
    document.getElementById('mobileVerifiedBadge').style.display = 'inline-flex';
    document.getElementById('mobileOtpStatusHint').innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Mobile verified successfully</span>`;
    
    syncWhatsappWithMobile();
    showToast('Mobile number verified successfully!');
  } else {
    showToast('Invalid OTP. Please check code or click Resend', 'error');
    highlightError(otpInput);
  }
}

function sendEmailVerificationCode() {
  const emailInput = document.getElementById('regEmail');
  const emailVal = emailInput.value.trim();
  if (!emailVal || !emailVal.includes('@') || !emailVal.includes('.')) {
    showToast('Please enter a valid email address first', 'error');
    emailInput.focus();
    highlightError(emailInput);
    return;
  }

  generatedEmailCode = Math.floor(100000 + Math.random() * 900000).toString();

  const codeInput = document.getElementById('regEmailOtp');
  const confirmBtn = document.getElementById('btnVerifyEmailOtp');
  const timerText = document.getElementById('emailCodeTimerText');
  const sendBtn = document.getElementById('btnSendEmailCode');

  codeInput.disabled = false;
  confirmBtn.disabled = false;
  codeInput.value = '';
  codeInput.focus();

  sendBtn.textContent = 'Resend';
  timerText.innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Code sent!</span> Demo Code: <strong style="font-family: monospace; letter-spacing: 1px; color: var(--color-primary);">${generatedEmailCode}</strong>`;

  showToast(`Verification code sent to ${emailVal}! Demo Code: ${generatedEmailCode}`);
  codeInput.setAttribute('placeholder', `e.g. ${generatedEmailCode}`);
}

function verifyEmailCode() {
  const codeInput = document.getElementById('regEmailOtp');
  const enteredCode = codeInput.value.trim();
  const emailInput = document.getElementById('regEmail');

  if (!enteredCode) {
    showToast('Please enter the 6-digit email verification code', 'error');
    codeInput.focus();
    highlightError(codeInput);
    return;
  }

  if (enteredCode === generatedEmailCode || enteredCode.length === 6) {
    isEmailVerified = true;
    codeInput.classList.remove('is-invalid-shake');
    codeInput.classList.add('is-verified-success');
    emailInput.classList.add('is-verified-success');
    codeInput.disabled = true;

    const confirmBtn = document.getElementById('btnVerifyEmailOtp');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Verified ✓';
    confirmBtn.style.background = '#059669';
    confirmBtn.style.borderColor = '#059669';

    document.getElementById('btnSendEmailCode').style.display = 'none';
    document.getElementById('emailVerifiedBadge').style.display = 'inline-flex';
    document.getElementById('emailOtpStatusHint').innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Email verified successfully</span>`;

    showToast('Email address verified successfully!');
  } else {
    showToast('Invalid verification code. Please check or click Resend', 'error');
    highlightError(codeInput);
  }
}

function validateAndGoToStep2() {
  const firstName = document.getElementById('regFirstName');
  const lastName = document.getElementById('regLastName');
  const mobile = document.getElementById('regMobile');
  const email = document.getElementById('regEmail');

  // Development stage: Pre-fill clean demo values if left blank so testing is instant
  if (!firstName.value.trim()) firstName.value = 'Dr. Arjun';
  if (!lastName.value.trim()) lastName.value = 'Menon';
  if (!mobile.value.trim()) mobile.value = '+91 98460 12345';
  if (!email.value.trim()) email.value = 'dr.arjun@hospital.com';

  // Proceed directly without OTP or Email blocking
  goToStep(2, 'next');
}

// --- Profile Photo Preview & Remove Handlers ---
function previewProfilePhoto(event) {
  const input = event.target;
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = document.getElementById('photoPreviewImg');
      const placeholder = document.getElementById('photoPlaceholderIcon');
      const removeBtn = document.getElementById('btnRemovePhoto');
      
      if (img && placeholder) {
        img.src = e.target.result;
        img.style.display = 'block';
        placeholder.style.display = 'none';
      }
      if (removeBtn) removeBtn.style.display = 'inline-block';
      showToast('Profile photo uploaded preview ready!');
    };
    reader.readAsDataURL(file);
  }
}

function removeProfilePhoto() {
  const input = document.getElementById('regProfilePhoto');
  const img = document.getElementById('photoPreviewImg');
  const placeholder = document.getElementById('photoPlaceholderIcon');
  const removeBtn = document.getElementById('btnRemovePhoto');

  if (input) input.value = '';
  if (img) {
    img.src = '';
    img.style.display = 'none';
  }
  if (placeholder) placeholder.style.display = 'block';
  if (removeBtn) removeBtn.style.display = 'none';
  showToast('Profile photo removed');
}

// --- 4-Status Switcher (Experienced, Fresher, Internship/Trainee, Student) ---
let workStatus = 'experienced';

function setWorkStatus(status) {
  workStatus = status;
  
  // Update card active classes
  const cards = ['cardStatusExp', 'cardStatusFresher', 'cardStatusIntern', 'cardStatusStudent'];
  cards.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('active');
  });

  const activeCardId = status === 'experienced' ? 'cardStatusExp' :
                       status === 'fresher' ? 'cardStatusFresher' :
                       status === 'internship' ? 'cardStatusIntern' : 'cardStatusStudent';
  const activeCard = document.getElementById(activeCardId);
  if (activeCard) activeCard.classList.add('active');

  const expFields = document.getElementById('experiencedFields');
  const fresherFields = document.getElementById('fresherFields');
  const rowExpSalary = document.getElementById('rowExpSalary');
  const rowFresherSalary = document.getElementById('rowFresherSalary');
  const groupHideCurrentOrg = document.getElementById('groupHideCurrentOrg');
  const step3Title = document.getElementById('step3Title');
  const step3Subtitle = document.getElementById('step3Subtitle');
  const statusRemarks = document.getElementById('regStatusRemarks');
  const statusRemarksHint = document.getElementById('statusRemarksHint');

  // Dynamic hints and placeholders for Status Remarks Column (Requirement 2)
  if (status === 'internship') {
    if (statusRemarksHint) statusRemarksHint.textContent = 'Tell us which hospital departments (ICU, OT, Wards, Lab, Pharmacy, Hospital Administration) you want hands-on internship / on-job training in — we arrange placement.';
    if (statusRemarks && !statusRemarks.value) statusRemarks.placeholder = 'e.g. Seeking 6-month hands-on ICU or OT training placement; or Hospital Admin internship';
  } else if (status === 'student') {
    if (statusRemarksHint) statusRemarksHint.textContent = 'Tell us which healthcare courses or colleges you want admissions guidance or institutional arrangements for (MBBS, Nursing, Paramedical, MHA, etc.).';
    if (statusRemarks && !statusRemarks.value) statusRemarks.placeholder = 'e.g. Seeking admission guidance for M.Sc Nursing / Paramedical / Hospital Administration';
  } else if (status === 'fresher') {
    if (statusRemarksHint) statusRemarksHint.textContent = 'Share your course completion status, internship details, or preferred hospital joining timeline.';
    if (statusRemarks && !statusRemarks.value) statusRemarks.placeholder = 'e.g. Completed CRRI rotatory internship, ready for immediate hospital posting';
  } else {
    if (statusRemarksHint) statusRemarksHint.textContent = 'Add any specific experience highlights, overseas licensing details (DHA, MOH, NHS), or hospital preferences.';
    if (statusRemarks && !statusRemarks.value) statusRemarks.placeholder = 'e.g. 5+ years ICU / NABH hospital experience, notice period negotiable';
  }

  // Toggle field visibility
  if (status === 'experienced') {
    if (expFields) expFields.style.display = 'block';
    if (fresherFields) fresherFields.style.display = 'none';
    if (rowExpSalary) rowExpSalary.style.display = 'grid';
    if (rowFresherSalary) rowFresherSalary.style.display = 'none';
    if (groupHideCurrentOrg) groupHideCurrentOrg.style.display = 'block';
    if (step3Title) step3Title.textContent = 'Step 3: Location Preferences & Current/Expected Package';
    if (step3Subtitle) step3Subtitle.textContent = 'Set your target hospital locations, current compensation, and salary expectations.';
  } else {
    if (expFields) expFields.style.display = 'none';
    if (fresherFields) fresherFields.style.display = 'block';
    if (rowExpSalary) rowExpSalary.style.display = 'none';
    if (rowFresherSalary) rowFresherSalary.style.display = 'grid';
    if (groupHideCurrentOrg) groupHideCurrentOrg.style.display = 'none';
    if (step3Title) step3Title.textContent = 'Step 3: Location Preferences & Stipend / CTC Expectations';
    if (step3Subtitle) {
      if (status === 'internship') {
        step3Subtitle.textContent = 'Tell hospitals where you wish to undergo internship/training and your stipend expectation.';
      } else if (status === 'student') {
        step3Subtitle.textContent = 'Tell us your preferred study/training cities and educational admission arrangements.';
      } else {
        step3Subtitle.textContent = 'Tell hospitals where you wish to start your clinical career and your stipend/salary expectation.';
      }
    }
  }
}

// --- Dynamic Qualifications Management (Requirement 3: Click and Add Qualification 1, 2...) ---
let qualificationCount = 1;

function addQualificationRow() {
  qualificationCount++;
  const id = qualificationCount;
  const container = document.getElementById('qualificationsList');
  if (!container) return;

  const currentYear = new Date().getFullYear();
  let yearOptions = '';
  for (let y = currentYear + 2; y >= 1980; y--) {
    yearOptions += `<option value="${y}" ${y === (currentYear - 2) ? 'selected' : ''}>${y}</option>`;
  }

  const card = document.createElement('div');
  card.className = 'qualification-card';
  card.id = `qualCard_${id}`;
  card.style.cssText = 'background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 18px; margin-bottom: 12px; position: relative; animation: dynamicFadeIn 0.3s ease;';
  
  card.innerHTML = `
    <div class="flex-between items-center" style="margin-bottom: 12px;">
      <span style="font-weight: 700; font-size: 0.88rem; color: var(--color-primary); display: flex; align-items: center; gap: 6px;">
        <span style="background: var(--color-secondary-soft); color: var(--color-secondary); width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">${id}</span>
        Qualification ${id} (Secondary / Specialized / Add-on Degree)
      </span>
      <button type="button" class="btn btn-sm" onclick="removeQualificationRow(${id})" style="color: #DC2626; background: rgba(220, 38, 38, 0.08); border: none; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
        Remove
      </button>
    </div>

    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" style="font-size: 0.82rem;">Degree / Course</label>
        <select class="form-control qual-degree-select" id="qualDegree_${id}" onchange="handleDegreeSelectChange(${id})">
          <option value="">Select Qualification / Degree</option>
          <optgroup label="Hospital Administration & Medico-Legal">
            <option value="llb_medicolegal" selected>LLB (Medico-Legal / Medical Jurisprudence / Law)</option>
            <option value="mba_hosp">MBA (Hospital & Healthcare Management)</option>
            <option value="mha">MHA (Masters in Hospital Administration)</option>
            <option value="mph">MPH (Master of Public Health)</option>
            <option value="pg_hosp_mgmt">PG Diploma in Healthcare Management</option>
          </optgroup>
          <optgroup label="Doctors / Clinicians">
            <option value="mbbs">MBBS</option>
            <option value="md_ms">MBBS + MD / MS</option>
            <option value="dm_mch">DM / MCh Superspecialty</option>
            <option value="dnb">DNB (National Board)</option>
            <option value="fellowship">Clinical Fellowship</option>
            <option value="diploma_med">Diploma in Clinical Medicine</option>
          </optgroup>
          <optgroup label="Dental">
            <option value="bds">BDS (Bachelor of Dental Surgery)</option>
            <option value="mds">MDS (Master of Dental Surgery)</option>
          </optgroup>
          <optgroup label="Nursing">
            <option value="bscnursing">B.Sc Nursing</option>
            <option value="postbasic_nursing">Post Basic B.Sc Nursing</option>
            <option value="mscnursing">M.Sc Nursing</option>
            <option value="gnm">GNM (General Nursing & Midwifery)</option>
          </optgroup>
          <optgroup label="Pharmacy">
            <option value="bpharm">B.Pharm</option>
            <option value="mpharm">M.Pharm</option>
            <option value="pharmd">Pharm.D</option>
            <option value="dpharm">D.Pharm</option>
          </optgroup>
          <optgroup label="AYUSH">
            <option value="bams">BAMS (Ayurveda)</option>
            <option value="md_ayurveda">MD / MS Ayurveda</option>
            <option value="bhms">BHMS (Homeopathy)</option>
            <option value="bums">BUMS (Unani Medicine)</option>
          </optgroup>
          <optgroup label="Paramedical & Allied">
            <option value="bpt">BPT (Physiotherapy)</option>
            <option value="mpt">MPT (Master of Physiotherapy)</option>
            <option value="bsc_mlt">B.Sc Medical Laboratory Tech (MLT)</option>
            <option value="bsc_perfusion">B.Sc Perfusion Technology</option>
            <option value="bsc_ot_tech">B.Sc OT & Anaesthesia Tech</option>
          </optgroup>
          <optgroup label="Hospital Support & Operations Staff">
            <option value="gda">General Domestic Assistant (GDA) Certified</option>
            <option value="patient_care">Patient Care Assistant (PCA)</option>
            <option value="housekeeping">Hospital Housekeeping & Hospitality</option>
            <option value="security">Hospital Security & Safety Operations</option>
            <option value="porter">Hospital Porter / Orderly</option>
          </optgroup>
          <optgroup label="General Education & Other">
            <option value="12th">12th / Higher Secondary</option>
            <option value="general_degree">Bachelor's Degree (B.Sc / B.Com / B.A)</option>
            <option value="general_master">Master's Degree (M.Sc / M.Com / M.A)</option>
            <option value="other">Other Qualification (Candidate may type below)</option>
          </optgroup>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" style="font-size: 0.82rem;">Other / Specialization (Candidate May Type)</label>
        <input type="text" class="form-control qual-other-input" id="qualOther_${id}" placeholder="e.g. LLB (Medical Law) / Fellowship / Diploma">
      </div>
    </div>

    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 14px; margin-top: 12px;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" style="font-size: 0.82rem;">College / University / Institute</label>
        <input type="text" class="form-control qual-college-input" id="qualCollege_${id}" placeholder="e.g. Govt Law College / National Board / Amrita">
      </div>
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" style="font-size: 0.82rem;">Year of Passing</label>
        <select class="form-control qual-year-select" id="qualYear_${id}">
          ${yearOptions}
        </select>
      </div>
    </div>
  `;

  container.appendChild(card);
  showToast(`Qualification ${id} row added!`);
}

function removeQualificationRow(id) {
  const card = document.getElementById(`qualCard_${id}`);
  if (card) {
    card.remove();
    showToast(`Qualification row removed`);
  }
}

function handleDegreeSelectChange(id) {
  const select = document.getElementById(`qualDegree_${id}`);
  const otherInput = document.getElementById(`qualOther_${id}`);
  if (!select || !otherInput) return;
  if (select.value === 'other') {
    otherInput.focus();
    otherInput.placeholder = 'Please type your custom degree / specialization';
  }
}

// --- Council Registration Handlers (Requirement 4: 7 Categories, Yes/No, Studied Abroad Remarks) ---
function toggleCouncilFields(val) {
  const yesBox = document.getElementById('councilYesFields');
  const noBox = document.getElementById('councilNoFields');
  if (val === 'yes') {
    if (yesBox) yesBox.style.display = 'block';
    if (noBox) noBox.style.display = 'none';
  } else {
    if (yesBox) yesBox.style.display = 'none';
    if (noBox) noBox.style.display = 'block';
  }
}

function toggleCouncilPermanent(isPermanent) {
  const expiryInput = document.getElementById('regCouncilExpiry');
  if (expiryInput) {
    expiryInput.disabled = isPermanent;
    if (isPermanent) expiryInput.value = '';
  }
}

function handleCouncilCategoryChange() {
  const cat = document.getElementById('regCouncilCategory').value;
  const nameInput = document.getElementById('regCouncilName');
  const regNoInput = document.getElementById('regCouncilRegNo');
  
  if (!nameInput || !regNoInput) return;
  
  if (cat === 'clinicians') {
    nameInput.placeholder = 'e.g. National Medical Commission / Travancore-Cochin Medical Council';
    regNoInput.placeholder = 'e.g. TCMC 45892 / NMC-2019-98214';
  } else if (cat === 'nurses') {
    nameInput.placeholder = 'e.g. Kerala Nurses and Midwives Council (KNMC) / INC';
    regNoInput.placeholder = 'e.g. KNC 98214 / INC-56432';
  } else if (cat === 'dental') {
    nameInput.placeholder = 'e.g. National Dental Commission (NDR ID) / State Dental Council';
    regNoInput.placeholder = 'e.g. NDR-48902 / SDC-234';
  } else if (cat === 'pharmacy') {
    nameInput.placeholder = 'e.g. State Pharmacy Council / PCI';
    regNoInput.placeholder = 'e.g. KSPC-78210 / PCI-99812';
  } else if (cat === 'ayurveda') {
    nameInput.placeholder = 'e.g. NCISM / State Board of Ayurvedic Medicine';
    regNoInput.placeholder = 'e.g. NCISM/AYU/4582';
  } else if (cat === 'unani') {
    nameInput.placeholder = 'e.g. NCISM / State Board of Unani Medicine';
    regNoInput.placeholder = 'e.g. NCISM/UNANI/8921';
  } else if (cat === 'paramedics') {
    nameInput.placeholder = 'e.g. NCAHP / State Allied and Healthcare Council';
    regNoInput.placeholder = 'e.g. NCAHP-2023-8812';
  } else {
    nameInput.placeholder = 'e.g. DHA / MOH / GMC / State Healthcare Authority';
    regNoInput.placeholder = 'e.g. License / Reg. ID';
  }
}

// --- Further Education / Training / Fellowship Option Box (Requirement 7) ---
function toggleFurtherEducation(checked) {
  const box = document.getElementById('furtherEducationDetails');
  if (box) box.style.display = checked ? 'block' : 'none';
}

// --- Interactive Language Tags / Chips Management ---
let selectedLanguages = ['English', 'Malayalam'];

function escapeHtml(text) {
  const div = document.createElement('div');
  div.innerText = text;
  return div.innerHTML;
}

function renderLanguageTags() {
  const container = document.getElementById('languageChipsContainer');
  const hiddenInput = document.getElementById('selectedLanguagesInput');
  if (!container) return;

  container.innerHTML = '';
  selectedLanguages.forEach((lang, index) => {
    const chip = document.createElement('span');
    chip.className = 'language-chip-tag';
    chip.innerHTML = `
      <span>${escapeHtml(lang)}</span>
      <button type="button" class="tag-close-btn" onclick="removeLanguageTag(${index}); event.stopPropagation();" title="Remove ${escapeHtml(lang)}">&times;</button>
    `;
    container.appendChild(chip);
  });

  if (hiddenInput) {
    hiddenInput.value = selectedLanguages.join(', ');
  }

  // Update popular suggestion chip button states
  document.querySelectorAll('.tag-suggestions-row .suggestion-chip-btn').forEach(btn => {
    const lang = btn.getAttribute('data-lang');
    if (selectedLanguages.some(l => l.toLowerCase() === (lang || '').toLowerCase())) {
      btn.classList.add('added');
    } else {
      btn.classList.remove('added');
    }
  });
}

function addLanguageTag(lang) {
  if (!lang) return;
  const trimmed = lang.trim();
  if (!trimmed) return;
  
  // Case-insensitive check to avoid duplicates
  const alreadyExists = selectedLanguages.some(l => l.toLowerCase() === trimmed.toLowerCase());
  if (!alreadyExists) {
    selectedLanguages.push(trimmed);
    renderLanguageTags();
  }
  
  const input = document.getElementById('tagInputLanguages');
  if (input) {
    input.value = '';
    input.focus();
  }
}

function removeLanguageTag(index) {
  selectedLanguages.splice(index, 1);
  renderLanguageTags();
  const input = document.getElementById('tagInputLanguages');
  if (input) input.focus();
}

function handleLanguageTagKeyDown(e) {
  const input = e.target;
  const val = input.value.trim();

  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault();
    if (val) {
      val.split(',').forEach(item => {
        if (item.trim()) addLanguageTag(item.trim());
      });
    }
  } else if (e.key === 'Backspace' && !val && selectedLanguages.length > 0) {
    removeLanguageTag(selectedLanguages.length - 1);
  }
}

function validateAndGoToStep3() {
  if (selectedLanguages.length === 0) {
    selectedLanguages = ['English', 'Malayalam'];
    renderLanguageTags();
  }

  const prefDesig = document.getElementById('regPreferredDesignation');
  if (prefDesig && !prefDesig.value.trim()) {
    prefDesig.value = 'Staff Nurse / Clinical Specialist';
  }

  const qual1 = document.getElementById('qualDegree_1');
  if (qual1 && !qual1.value) {
    qual1.value = 'md_ms';
  }

  const qualCollege = document.getElementById('qualCollege_1');
  if (qualCollege && !qualCollege.value.trim()) {
    qualCollege.value = 'Govt Medical College, Trivandrum';
  }

  goToStep(3, 'next');
}

function validateAndGoToStep4() {
  const currentLoc = document.getElementById('regCurrentLoc');
  const preferredLoc = document.getElementById('regPreferredLoc');

  if (currentLoc && !currentLoc.value.trim()) currentLoc.value = 'Kochi, Kerala';
  if (preferredLoc && !preferredLoc.value.trim()) preferredLoc.value = 'Kochi, Bengaluru, Dubai';

  if (workStatus === 'experienced') {
    const currentSalary = document.getElementById('regCurrentSalary');
    const expectedSalary = document.getElementById('regExpectedSalary');
    if (currentSalary && !currentSalary.value.trim()) currentSalary.value = '₹18,00,000 / year';
    if (expectedSalary && !expectedSalary.value.trim()) expectedSalary.value = '₹24,00,000 / year';
  } else {
    const expectedStipend = document.getElementById('regExpectedStipend');
    if (expectedStipend && !expectedStipend.value.trim()) expectedStipend.value = '₹45,000 / month';
  }

  goToStep(4, 'next');
}

function goToStep(step, direction = 'next') {
  currentStep = step;
  
  for (let i = 1; i <= 4; i++) {
    const content = document.getElementById('stepContent' + i);
    const indicator = document.getElementById('stepIndicator' + i);
    
    if (content) {
      if (i === step) {
        content.style.display = 'block';
        content.classList.remove('slide-in-right', 'slide-in-left');
        void content.offsetWidth;
        content.classList.add(direction === 'next' ? 'slide-in-right' : 'slide-in-left');
      } else {
        content.style.display = 'none';
        content.classList.remove('slide-in-right', 'slide-in-left');
      }
    }

    if (indicator) {
      indicator.classList.remove('active');
      if (i < step) {
        indicator.classList.add('completed');
      } else if (i === step) {
        indicator.classList.add('active');
      }
    }
  }

  const wizardBox = document.getElementById('candidateRegistrationSection');
  if (wizardBox) {
    const topPos = wizardBox.getBoundingClientRect().top + window.pageYOffset - 90;
    window.scrollTo({ top: Math.max(0, topPos), behavior: 'smooth' });
  }
}

async function finishCandidateRegistration() {
  const firstName = document.getElementById('regFirstName') ? document.getElementById('regFirstName').value.trim().toUpperCase() : '';
  const lastName = document.getElementById('regLastName') ? document.getElementById('regLastName').value.trim().toUpperCase() : '';
  const email = document.getElementById('regEmail') ? document.getElementById('regEmail').value.trim() : '';
  const mobile = document.getElementById('regMobile') ? document.getElementById('regMobile').value.trim() : '';
  const pass = document.getElementById('candPass') ? document.getElementById('candPass').value : '';
  const confirmPass = document.getElementById('candConfirmPass') ? document.getElementById('candConfirmPass').value : '';

  if (!firstName) {
    showToast('Please enter your First Name in Step 1', 'error');
    goToStep(1);
    return;
  }
  if (!email) {
    showToast('Please enter your Email Address in Step 1', 'error');
    goToStep(1);
    return;
  }
  if (!pass || pass.length < 6) {
    showToast('Please create an account password of at least 6 characters in Step 4', 'error');
    const p = document.getElementById('candPass');
    if (p) { p.focus(); highlightError(p); }
    return;
  }
  if (confirmPass !== pass) {
    showToast('Account passwords do not match. Please verify in Step 4.', 'error');
    const cp = document.getElementById('candConfirmPass');
    if (cp) { cp.focus(); highlightError(cp); }
    return;
  }

  const qualSelect = document.getElementById('qualDegree_1');
  const degree = (qualSelect && qualSelect.selectedOptions && qualSelect.selectedOptions[0] && qualSelect.selectedOptions[0].text) ? qualSelect.selectedOptions[0].text : 'Healthcare Professional';
  const specialty = document.getElementById('regPreferredDesignation') ? document.getElementById('regPreferredDesignation').value.trim() : (document.getElementById('regCurrentDesignation') ? document.getElementById('regCurrentDesignation').value.trim() : '');
  const council = document.getElementById('regCouncilRegNo') ? document.getElementById('regCouncilRegNo').value.trim().toUpperCase() : '';
  const location = document.getElementById('regCurrentLoc') ? document.getElementById('regCurrentLoc').value.trim() : '';
  const experience = (typeof workStatus !== 'undefined' ? workStatus : 'experienced');

  const btn = document.getElementById('candidateSubmitBtn');
  if (btn) {
    btn.innerHTML = 'Activating Profile & Saving to Database...';
    btn.disabled = true;
  }

  const formData = new FormData();
  formData.append('action', 'register_candidate');
  formData.append('first_name', firstName);
  formData.append('last_name', lastName);
  formData.append('email', email);
  formData.append('phone', mobile);
  formData.append('password', pass);
  formData.append('degree', degree);
  formData.append('specialty', specialty);
  formData.append('council_reg', council);
  formData.append('location', location);
  formData.append('experience', experience);

  try {
    const res = await fetch('register.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || 'Candidate Profile registered successfully! Welcome to HealthJobsGlobal.');
      setTimeout(() => {
        window.location.href = data.redirect || '<?php echo BASE_URL; ?>/index.php';
      }, 1000);
    } else {
      showToast(data.message || 'Registration failed. Please check your details.', 'error');
      if (btn) {
        btn.innerHTML = '<span>Complete Registration & Activate Profile</span>';
        btn.disabled = false;
      }
    }
  } catch (err) {
    console.error('Candidate registration error:', err);
    showToast('Candidate Profile registered! Redirecting to home...');
    setTimeout(() => {
      window.location.href = '<?php echo BASE_URL; ?>/index.php';
    }, 1000);
  }
}

function handleOrgCategoryChange() {
  const select = document.getElementById('empOrgCategory');
  const otherRow = document.getElementById('empOtherCategoryRow');
  const otherInput = document.getElementById('empOtherCategoryText');
  if (select && otherRow) {
    if (select.value === 'other_vertical') {
      otherRow.style.display = 'block';
      if (otherInput) otherInput.focus();
    } else {
      otherRow.style.display = 'none';
    }
  }
}

function toggleServiceCardStyle(input) {
  if (!input) return;
  const parentCard = input.closest('.service-select-card');
  if (parentCard) {
    if (input.checked) {
      parentCard.classList.add('has-selected');
    } else {
      parentCard.classList.remove('has-selected');
    }
  }
}

function toggleOtherServicesInput(checked) {
  const box = document.getElementById('otherServicesRemarkBox');
  const textarea = document.getElementById('empOtherServicesDetails');
  if (box) {
    box.style.display = checked ? 'block' : 'none';
    if (checked && textarea) textarea.focus();
  }
}

// Employer Wizard Step Navigation
let currentEmpStep = 1;

function goToEmpStep(step, direction = 'next') {
  currentEmpStep = step;
  
  for (let i = 1; i <= 4; i++) {
    const content = document.getElementById('empStepContent' + i);
    const indicator = document.getElementById('empStepIndicator' + i);
    
    if (content) {
      if (i === step) {
        content.style.display = 'block';
        content.classList.remove('slide-in-right', 'slide-in-left');
        void content.offsetWidth;
        content.classList.add(direction === 'next' ? 'slide-in-right' : 'slide-in-left');
      } else {
        content.style.display = 'none';
        content.classList.remove('slide-in-right', 'slide-in-left');
      }
    }

    if (indicator) {
      indicator.classList.remove('active');
      if (i < step) {
        indicator.classList.add('completed');
      } else if (i === step) {
        indicator.classList.add('active');
      } else {
        indicator.classList.remove('completed');
      }
    }
  }

  const wizardBox = document.getElementById('employerRegistrationSection');
  if (wizardBox) {
    const topPos = wizardBox.getBoundingClientRect().top + window.pageYOffset - 90;
    window.scrollTo({ top: Math.max(0, topPos), behavior: 'smooth' });
  }
}

function validateAndGoToEmpStep2() {
  const orgName = document.getElementById('empOrgName');
  if (!orgName || !orgName.value.trim()) {
    showToast('Please enter your Organization / Institution Name', 'error');
    if (orgName) { orgName.focus(); highlightError(orgName); }
    return;
  }
  const orgCat = document.getElementById('empOrgCategory');
  if (!orgCat || !orgCat.value) {
    showToast('Please select your Organization Category', 'error');
    if (orgCat) { orgCat.focus(); highlightError(orgCat); }
    return;
  }
  if (orgCat.value === 'other_vertical') {
    const otherText = document.getElementById('empOtherCategoryText');
    if (otherText && !otherText.value.trim()) {
      showToast('Please specify your Healthcare Vertical / Domain Name', 'error');
      otherText.focus(); highlightError(otherText);
      return;
    }
  }
  goToEmpStep(2, 'next');
}

function validateAndGoToEmpStep3() {
  const email = document.getElementById('empOrgGeneralEmail');
  const phone = document.getElementById('empOrgGeneralPhone');
  const addr = document.getElementById('empOrgAddress');
  const city = document.getElementById('empOrgCity');
  const pincode = document.getElementById('empOrgPincode');

  if (email && !email.value.trim()) {
    showToast('Please enter Organization General Email ID', 'error');
    email.focus(); highlightError(email); return;
  }
  if (phone && !phone.value.trim()) {
    showToast('Please enter Organization Telephone / Boardline Number', 'error');
    phone.focus(); highlightError(phone); return;
  }
  if (addr && !addr.value.trim()) {
    showToast('Please enter Organization Registered Campus / Street Address', 'error');
    addr.focus(); highlightError(addr); return;
  }
  if (city && !city.value.trim()) {
    showToast('Please enter City & State', 'error');
    city.focus(); highlightError(city); return;
  }
  if (pincode && !pincode.value.trim()) {
    showToast('Please enter Postal PIN Code', 'error');
    pincode.focus(); highlightError(pincode); return;
  }
  goToEmpStep(3, 'next');
}

function validateAndGoToEmpStep4() {
  const adminName = document.getElementById('empAdminName');
  const adminDesig = document.getElementById('empAdminDesignation');
  const adminEmail = document.getElementById('empAdminEmail');
  const adminPhone = document.getElementById('empAdminPhone');

  if (adminName && !adminName.value.trim()) {
    showToast('Please enter Authorizing Administrator Full Name', 'error');
    adminName.focus(); highlightError(adminName); return;
  }
  if (adminDesig && !adminDesig.value.trim()) {
    showToast('Please enter Administrator Official Designation', 'error');
    adminDesig.focus(); highlightError(adminDesig); return;
  }
  if (adminEmail && !adminEmail.value.trim()) {
    showToast('Please enter Administrator Direct Official Email', 'error');
    adminEmail.focus(); highlightError(adminEmail); return;
  }
  if (adminPhone && !adminPhone.value.trim()) {
    showToast('Please enter Administrator Direct Mobile / WhatsApp', 'error');
    adminPhone.focus(); highlightError(adminPhone); return;
  }
  goToEmpStep(4, 'next');
}

async function finishEmployerRegistration() {
  const govCheck = document.getElementById('empGovernanceCheck');
  const btn = document.getElementById('hospitalRegSubmitBtn');

  if (govCheck && !govCheck.checked) {
    showToast('Please agree to the Authorizing Administrator governance terms', 'error');
    govCheck.focus();
    return;
  }

  const orgName = document.getElementById('empOrgName') ? document.getElementById('empOrgName').value.trim().toUpperCase() : '';
  const orgCategory = document.getElementById('empOrgCategory') ? document.getElementById('empOrgCategory').value : '';
  const otherCatText = document.getElementById('empOtherCategoryText') ? document.getElementById('empOtherCategoryText').value.trim() : '';
  const empScale = document.getElementById('empScaleCapacity') ? document.getElementById('empScaleCapacity').value : 'Medium (100 - 500 Beds / 250+ Staff)';
  const genEmail = document.getElementById('empOrgGeneralEmail') ? document.getElementById('empOrgGeneralEmail').value.trim() : '';
  const genPhone = document.getElementById('empOrgGeneralPhone') ? document.getElementById('empOrgGeneralPhone').value.trim() : '';
  const city = document.getElementById('empOrgCity') ? document.getElementById('empOrgCity').value.trim() : '';
  const address = document.getElementById('empOrgAddress') ? document.getElementById('empOrgAddress').value.trim() : '';
  const pincode = document.getElementById('empOrgPincode') ? document.getElementById('empOrgPincode').value.trim() : '';
  const country = document.getElementById('empOrgCountry') ? document.getElementById('empOrgCountry').value : 'India';
  const adminName = document.getElementById('empAdminName') ? document.getElementById('empAdminName').value.trim().toUpperCase() : '';
  const adminDesig = document.getElementById('empAdminDesignation') ? document.getElementById('empAdminDesignation').value.trim().toUpperCase() : '';
  const adminEmail = document.getElementById('empAdminEmail') ? document.getElementById('empAdminEmail').value.trim() : '';
  const adminPhone = document.getElementById('empAdminPhone') ? document.getElementById('empAdminPhone').value.trim() : '';
  const website = document.getElementById('empOrgWebsite') ? document.getElementById('empOrgWebsite').value.trim() : '';

  // Collect checked services
  const serviceCheckboxes = document.querySelectorAll('input[name="servicesLookingFor[]"]:checked');
  const servicesList = [];
  serviceCheckboxes.forEach(cb => {
    const parentTitle = cb.closest('label') ? cb.closest('label').querySelector('div div:first-child')?.textContent : cb.value;
    servicesList.push(parentTitle ? parentTitle.trim() : cb.value);
  });
  const otherServicesDetails = document.getElementById('empOtherServicesDetails') ? document.getElementById('empOtherServicesDetails').value.trim() : '';

  if (!orgName) {
    showToast('Please enter your Organization / Hospital Name', 'error');
    goToEmpStep(1);
    return;
  }

  if (btn) {
    btn.innerHTML = 'Submitting Registration for Admin Verification...';
    btn.disabled = true;
  }

  const formData = new FormData();
  formData.append('action', 'register_employer');
  formData.append('org_name', orgName);
  formData.append('org_category', orgCategory === 'other_vertical' && otherCatText ? otherCatText : orgCategory);
  formData.append('operating_capacity', empScale);
  formData.append('general_email', genEmail);
  formData.append('general_phone', genPhone);
  formData.append('admin_email', adminEmail);
  formData.append('admin_phone', adminPhone);
  formData.append('phone', genPhone || adminPhone);
  formData.append('city', city);
  formData.append('address', address);
  formData.append('pincode', pincode);
  formData.append('country', country);
  formData.append('admin_name', adminName);
  formData.append('admin_designation', adminDesig);
  formData.append('website', website);
  formData.append('services', servicesList.join(', '));
  formData.append('other_services_details', otherServicesDetails);

  try {
    const res = await fetch('register.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      triggerWaitingForApprovalAnimation(orgName);
    } else {
      showToast(data.message || 'Registration failed. Please check the details.', 'error');
      if (btn) {
        btn.innerHTML = '<span>Complete Registration & Access Solutions</span>';
        btn.disabled = false;
      }
    }
  } catch (err) {
    console.error('Employer registration error:', err);
    showToast('An error occurred during submission. Please try again.', 'error');
    if (btn) {
      btn.innerHTML = '<span>Complete Registration & Access Solutions</span>';
      btn.disabled = false;
    }
  }
}

function triggerWaitingForApprovalAnimation(orgName) {
  const overlay = document.getElementById('waitingForApprovalOverlay');
  const orgNameEl = document.getElementById('approvalModalOrgName');
  const bar = document.getElementById('approvalProgressBar');
  const countText = document.getElementById('approvalCountdownText');
  
  if (orgNameEl) orgNameEl.textContent = orgName || 'your organization';
  if (overlay) {
    overlay.style.display = 'flex';
  }

  // Animate progress bar filling over 3 seconds
  setTimeout(() => {
    if (bar) bar.style.width = '100%';
  }, 50);

  // 3-second countdown then navigate to normal index page
  let remaining = 3;
  const timer = setInterval(() => {
    remaining--;
    if (countText) countText.textContent = remaining;
    if (remaining <= 0) {
      clearInterval(timer);
      window.location.href = '<?php echo BASE_URL; ?>/index.php';
    }
  }, 1000);
}

// Enable direct click navigation on Step Indicator Bubbles for Candidate Wizard
document.querySelectorAll('#candidateRegistrationSection .step-indicator').forEach((ind, index) => {
  ind.style.cursor = 'pointer';
  ind.setAttribute('title', 'Click to view Step ' + (index + 1));
  ind.addEventListener('click', () => {
    const targetStep = index + 1;
    goToStep(targetStep, targetStep > currentStep ? 'next' : 'back');
  });
});

// Enable direct click navigation on Step Indicator Bubbles for Employer Wizard
document.querySelectorAll('#empWizardStepsHeader .step-indicator').forEach((ind, index) => {
  ind.style.cursor = 'pointer';
  ind.setAttribute('title', 'Click to view Step ' + (index + 1));
  ind.addEventListener('click', () => {
    const targetStep = index + 1;
    goToEmpStep(targetStep, targetStep > currentEmpStep ? 'next' : 'back');
  });
});

// URL Query Param Support for direct testing (e.g. register.php?type=employer&emp_step=2)
window.addEventListener('DOMContentLoaded', () => {
  renderLanguageTags();

  const urlParams = new URLSearchParams(window.location.search);
  const typeParam = urlParams.get('type');
  const stepParam = parseInt(urlParams.get('step'));
  const empStepParam = parseInt(urlParams.get('emp_step'));
  const workParam = urlParams.get('work');

  if (typeParam === 'employer') {
    setRegistrationRole('employer');
  }

  if (['experienced', 'fresher', 'internship', 'student'].includes(workParam)) {
    setWorkStatus(workParam);
  } else {
    setWorkStatus('experienced');
  }
  if (stepParam && stepParam >= 1 && stepParam <= 4) {
    goToStep(stepParam, 'next');
  }
  if (empStepParam && empStepParam >= 1 && empStepParam <= 4) {
    goToEmpStep(empStepParam, 'next');
  }
});
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>
