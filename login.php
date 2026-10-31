<?php
/**
 * HealthJobsGlobal.com - Single Unified Login (Candidate & Hospital Employer)
 * The system automatically detects whether the account is Candidate or Employer.
 * Candidates are redirected to the homepage (index.php) and Employers to employer-dashboard.php.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to respective destination
if (isLoggedIn()) {
    $curr = getLoggedInUser();
    if (($curr['role'] ?? '') === 'employer') {
        header("Location: " . BASE_URL . "/employer-dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/index.php");
    }
    exit;
}

$errorMessage = '';
$identifier = '';

// Handle Server-Side Login POST (Single Unified Login)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $redirectUrl = !empty($_POST['redirect']) ? trim($_POST['redirect']) : (!empty($_GET['redirect']) ? trim($_GET['redirect']) : '');

    if (empty($identifier) || empty($password)) {
        $errorMessage = "Please enter your registered Email / Mobile and Password.";
    } else {
        $authenticated = false;

        // 1. Check in candidates table
        if ($con && !$con->connect_error) {
            $stmt = $con->prepare("SELECT * FROM `candidates` WHERE `email` = ? OR `phone` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $candRes = $stmt->get_result();
                if ($candRes && $cand = $candRes->fetch_assoc()) {
                    if (!empty($cand['password']) && password_verify($password, $cand['password'])) {
                        $authenticated = true;
                    } elseif (empty($cand['password']) && $password === 'password123') {
                        $authenticated = true;
                    } elseif ($cand['email'] === 'dr.arjun@hospital.com' && $password === 'password123') {
                        $authenticated = true;
                    }

                    if ($authenticated) {
                        $_SESSION['user'] = [
                            'id' => $cand['id'],
                            'role' => 'candidate',
                            'name' => $cand['name'],
                            'degree' => $cand['degree'] ?? '',
                            'title' => !empty($cand['specialty']) ? ($cand['specialty'] . (!empty($cand['degree']) ? ' · ' . $cand['degree'] : '')) : 'Healthcare Professional',
                            'email' => $cand['email'],
                            'phone' => $cand['phone'] ?? '',
                            'initials' => !empty($cand['name']) ? strtoupper(substr($cand['name'], 0, 1) . substr(strrchr($cand['name'], ' ') ?: ' ', 1, 1)) : 'AM',
                            'council' => $cand['council_reg'] ?? ''
                        ];
                        $dest = !empty($redirectUrl) ? $redirectUrl : (BASE_URL . '/index.php');
                        header("Location: " . $dest);
                        exit;
                    }
                }
                $stmt->close();
            }
        }

        // 2. Check in hospitals table (Employers)
        if (!$authenticated && $con && !$con->connect_error) {
            $stmt = $con->prepare("SELECT * FROM `hospitals` WHERE `contact_email` = ? OR `admin_email` = ? OR `phone` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("sss", $identifier, $identifier, $identifier);
                $stmt->execute();
                $hospRes = $stmt->get_result();
                if ($hospRes && $hosp = $hospRes->fetch_assoc()) {
                    $passOk = false;
                    if (!empty($hosp['password']) && password_verify($password, $hosp['password'])) {
                        $passOk = true;
                    } elseif (!empty($hosp['initial_random_password']) && hash_equals((string)$hosp['initial_random_password'], (string)$password)) {
                        $passOk = true;
                    } elseif (empty($hosp['password']) && $password === 'password123') {
                        $passOk = true;
                    } elseif ($hosp['contact_email'] === 'careers@astermedcity.com' && $password === 'password123') {
                        $passOk = true;
                    }

                    if ($passOk) {
                        $authenticated = true; // Valid credentials found

                        // Check Admin Approval (verified = 1)
                        $isApproved = isset($hosp['verified']) && ((int)$hosp['verified'] === 1);
                        if (!$isApproved) {
                            $errorMessage = "Your Healthcare Employer account ('" . htmlspecialchars($hosp['name']) . "') is currently pending review & verification by the HealthJobsGlobal Admin. The account will be active after verification. An activation email with your system-generated login password will be sent once approved.";
                        } else {
                            $_SESSION['user'] = [
                                'id' => $hosp['id'],
                                'role' => 'employer',
                                'name' => !empty($hosp['admin_name']) ? $hosp['admin_name'] : ($hosp['name'] . ' HR'),
                                'hospital' => $hosp['name'],
                                'title' => !empty($hosp['admin_designation']) ? $hosp['admin_designation'] : 'Head of Medical Talent Acquisition',
                                'email' => !empty($hosp['contact_email']) ? $hosp['contact_email'] : ($hosp['admin_email'] ?? ''),
                                'phone' => $hosp['phone'] ?? '',
                                'initials' => !empty($hosp['logo_text']) ? substr($hosp['logo_text'], 0, 2) : strtoupper(substr($hosp['name'], 0, 2)),
                                'accreditation' => $hosp['badge'] ?? 'JCI & NABH Accredited',
                                'must_change_password' => (int)($hosp['must_change_password'] ?? 0),
                                'temp_password' => $password
                            ];
                            $dest = BASE_URL . '/employer-dashboard.php';
                            if (!empty($redirectUrl) && !str_contains($redirectUrl, 'index.php') && !str_contains($redirectUrl, 'login.php') && !str_contains($redirectUrl, 'candidate-') && !str_contains($redirectUrl, 'register.php')) {
                                $dest = $redirectUrl;
                            }
                            header("Location: " . $dest);
                            exit;
                        }
                    }
                }
                $stmt->close();
            }
        }

        // 3. Fallback demo check if database offline
        if (!$authenticated) {
            if ($identifier === 'dr.arjun@hospital.com' && $password === 'password123') {
                $_SESSION['user'] = [
                    'role' => 'candidate',
                    'name' => 'Dr. Arjun Menon',
                    'degree' => 'MD, DM (Cardiology)',
                    'title' => 'Senior Consultant – Interventional Cardiology',
                    'email' => 'dr.arjun@hospital.com',
                    'initials' => 'AM',
                    'council' => 'TCMC 45892'
                ];
                header("Location: " . (!empty($redirectUrl) ? $redirectUrl : (BASE_URL . '/index.php')));
                exit;
            } elseif ($identifier === 'careers@astermedcity.com' && $password === 'password123') {
                $_SESSION['user'] = [
                    'role' => 'employer',
                    'name' => 'Aster Medcity HR',
                    'hospital' => 'Aster Medcity Hospital',
                    'title' => 'Head of Medical Talent Acquisition',
                    'email' => 'careers@astermedcity.com',
                    'initials' => 'AS',
                    'accreditation' => 'JCI & NABH Accredited'
                ];
                header("Location: " . (!empty($redirectUrl) ? $redirectUrl : (BASE_URL . '/employer-dashboard.php')));
                exit;
            }

            $errorMessage = "Invalid login credentials. No matching Candidate or Hospital Employer account found for this Email / Mobile and Password.";
        }
    }
}

$pageTitle = "Login — HealthJobsGlobal";
$pageDescription = "Sign in to HealthJobsGlobal healthcare portal. Single unified access for Candidates and Hospital Employers.";

require_once __DIR__ . '/components/header.php';
?>

<div style="background: var(--color-bg); padding: 60px 0 80px 0; min-height: 80vh; display: flex; align-items: center;">
  <div class="container container-narrow" style="max-width: 480px; margin: 0 auto;">
    
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 40px; box-shadow: var(--shadow-xl);">
      
      <div class="text-center" style="margin-bottom: 26px;">
        <div class="logo-symbol" style="margin: 0 auto 16px auto; width: 48px; height: 48px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5"><path d="M12 2v20M2 12h20"/></svg>
        </div>
        <h2 style="font-size: 1.8rem; color: var(--color-primary); margin-bottom: 6px;">Sign In to Portal</h2>
        <p style="font-size: 0.9rem; color: var(--color-text-secondary);">
          Unified access for Candidates and Hospital Employers
        </p>
      </div>

      <?php if (!empty($errorMessage)): ?>
        <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: var(--radius-md); font-size: 0.88rem; margin-bottom: 22px; display: flex; align-items: center; gap: 10px;">
          <?php echo getIcon('alert-triangle', '', 18); ?>
          <span><?php echo htmlspecialchars($errorMessage); ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" id="singleLoginForm">
        <?php if (!empty($_GET['redirect'])): ?>
          <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
        <?php endif; ?>

        <div class="form-group" style="margin-bottom: 18px;">
          <label class="form-label" for="loginIdentifier">Email Address or Registered Mobile *</label>
          <input type="text" name="identifier" id="loginIdentifier" class="form-control" placeholder="e.g. doctor@hospital.com or careers@hospital.com" value="<?php echo htmlspecialchars($identifier); ?>" required autofocus>
          <span class="form-hint" style="font-size: 0.78rem; color: var(--color-text-muted);">Enter the email or phone number you registered with</span>
        </div>

        <div class="form-group" style="margin-bottom: 18px;">
          <div class="flex-between items-center" style="margin-bottom: 6px;">
            <label class="form-label" for="loginPass" style="margin: 0;">Password *</label>
            <a href="#" onclick="alert('Password reset link sent to your registered email address.'); return false;" style="font-size: 0.8rem; color: var(--color-secondary); font-weight: 600;">Forgot Password?</a>
          </div>
          <div style="position: relative;">
            <input type="password" name="password" id="loginPass" class="form-control" placeholder="••••••••" style="padding-right: 44px;" required>
            <button type="button" onclick="togglePassView('loginPass', this)" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--color-text-muted); display: flex; align-items: center; justify-content: center; padding: 6px;" title="See password while typing">
              <?php echo getIcon('eye', '', 18); ?>
            </button>
          </div>
        </div>

        <div style="margin-bottom: 22px; font-size: 0.85rem; color: var(--color-text-secondary); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="remember" checked> Remember this device
          </label>
          <a href="#" onclick="alert('WhatsApp OTP verification code sent to your registered mobile number.'); return false;" style="color: #0E7490; font-weight: 600;">Login with WhatsApp OTP</a>
        </div>

        <button type="submit" id="loginSubmitBtn" class="btn btn-primary btn-lg" style="width: 100%;">
          <span>Sign In to Portal</span>
          <?php echo getIcon('arrow-right', '', 18); ?>
        </button>
      </form>

      <!-- Instant 1-Click Demo Login Shortcuts -->
      <div style="margin-top: 24px; padding: 14px; background: #F8FAFC; border: 1px dashed var(--color-border); border-radius: var(--radius-md); text-align: center;">
        <span style="font-size: 0.72rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">
          ⚡ 1-Click Demo Logins
        </span>
        <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
          <button type="button" onclick="fillAndSubmitDemo('dr.arjun@hospital.com', 'password123')" class="btn btn-sm btn-outline" style="font-size: 0.78rem;">
            Dr. Arjun (Candidate)
          </button>
          <button type="button" onclick="fillAndSubmitDemo('careers@astermedcity.com', 'password123')" class="btn btn-sm btn-outline" style="font-size: 0.78rem;">
            Aster Medcity (Hospital)
          </button>
        </div>
      </div>

      <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--color-border); text-align: center; font-size: 0.9rem; color: var(--color-text-secondary);">
        Don't have an account? 
        <a href="<?php echo BASE_URL; ?>/register.php" style="color: var(--color-secondary); font-weight: 700;">Register Now</a>
      </div>

    </div>

  </div>
</div>

<script>
function togglePassView(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    btn.innerHTML = '<?php echo getIcon('eye-off', '', 18); ?>';
    btn.title = 'Hide password';
  } else {
    input.type = 'password';
    btn.innerHTML = '<?php echo getIcon('eye', '', 18); ?>';
    btn.title = 'See password while typing';
  }
}

function fillAndSubmitDemo(identifier, password) {
  document.getElementById('loginIdentifier').value = identifier;
  document.getElementById('loginPass').value = password;
  document.getElementById('singleLoginForm').submit();
}
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>
