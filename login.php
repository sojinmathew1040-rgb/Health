<?php
/**
 * HealthJobsGlobal.com - Login (Candidate & Employer)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Handle Server-Side Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'candidate';
    $identifier = trim($_POST['identifier'] ?? '');
    $redirectUrl = !empty($_POST['redirect']) ? trim($_POST['redirect']) : (!empty($_GET['redirect']) ? trim($_GET['redirect']) : (BASE_URL . '/index.php'));
    
    if ($role === 'employer') {
        $_SESSION['user'] = [
            'role' => 'employer',
            'name' => 'Aster Medcity HR',
            'hospital' => 'Aster Medcity Hospital',
            'title' => 'Head of Medical Talent Acquisition',
            'email' => (!empty($identifier) && filter_var($identifier, FILTER_VALIDATE_EMAIL)) ? $identifier : 'careers@astermedcity.com',
            'initials' => 'AS',
            'accreditation' => 'JCI & NABH Accredited'
        ];
        header("Location: " . $redirectUrl);
        exit;
    } else {
        $_SESSION['user'] = [
            'role' => 'candidate',
            'name' => 'Dr. Arjun Menon',
            'degree' => 'MD, DM (Cardiology)',
            'title' => 'Senior Consultant – Interventional Cardiology',
            'email' => (!empty($identifier) && filter_var($identifier, FILTER_VALIDATE_EMAIL)) ? $identifier : 'dr.arjun@hospital.com',
            'initials' => 'AM',
            'council' => 'TCMC 45892'
        ];
        header("Location: " . $redirectUrl);
        exit;
    }
}

$pageTitle = "Login — HealthJobsGlobal";
$pageDescription = "Sign in to your candidate healthcare profile or hospital employer recruitment dashboard.";

require_once __DIR__ . '/components/header.php';
?>

<div style="background: var(--color-bg); padding: 60px 0 80px 0; min-height: 80vh; display: flex; align-items: center;">
  <div class="container container-narrow" style="max-width: 500px; margin: 0 auto;">
    
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 40px; box-shadow: var(--shadow-xl);">
      
      <div class="text-center" style="margin-bottom: 28px;">
        <div class="logo-symbol" style="margin: 0 auto 16px auto; width: 48px; height: 48px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5"><path d="M12 2v20M2 12h20"/></svg>
        </div>
        <h2 style="font-size: 1.8rem; color: var(--color-primary); margin-bottom: 6px;">Welcome Back</h2>
        <p style="font-size: 0.9rem; color: var(--color-text-secondary);">Access your verified healthcare portal</p>
      </div>

      <!-- Login Role Selector Tabs -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: var(--color-bg); padding: 4px; border-radius: var(--radius-md); margin-bottom: 24px;">
        <button type="button" id="tabCandidateLogin" class="btn btn-sm btn-primary" style="font-size: 0.85rem;" onclick="setLoginRole('candidate')">
          Candidate
        </button>
        <button type="button" id="tabEmployerLogin" class="btn btn-sm btn-outline" style="font-size: 0.85rem;" onclick="setLoginRole('employer')">
          Hospital / Employer
        </button>
      </div>

      <form method="POST" action="login.php">
        <input type="hidden" name="role" id="loginRoleInput" value="candidate">
        <?php if (!empty($_GET['redirect'])): ?>
          <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label" for="loginIdentifier">Email or Registered Mobile Number *</label>
          <input type="text" name="identifier" id="loginIdentifier" class="form-control" placeholder="doctor@example.com or +91 98765 43210" value="dr.arjun@hospital.com" required>
        </div>

        <div class="form-group">
          <div class="flex-between items-center" style="margin-bottom: 6px;">
            <label class="form-label" for="loginPass" style="margin: 0;">Password *</label>
            <a href="#" onclick="alert('Password reset link sent to registered email.'); return false;" style="font-size: 0.8rem; color: var(--color-secondary); font-weight: 600;">Forgot Password?</a>
          </div>
          <input type="password" name="password" id="loginPass" class="form-control" placeholder="••••••••" value="password123" required>
        </div>

        <div style="margin-bottom: 20px; font-size: 0.85rem; color: var(--color-text-secondary); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" checked> Remember this device
          </label>
          <a href="#" onclick="alert('OTP sent via WhatsApp to registered mobile.'); return false;" style="color: #0E7490; font-weight: 600;">Login with WhatsApp OTP</a>
        </div>

        <button type="submit" id="loginSubmitBtn" class="btn btn-primary btn-lg" style="width: 100%;">
          <span>Sign In to Portal</span>
          <?php echo getIcon('arrow-right', '', 18); ?>
        </button>
      </form>

      <!-- Instant 1-Click Demo Login Shortcuts -->
      <div style="margin-top: 20px; padding: 14px; background: #F8FAFC; border: 1px dashed var(--color-border); border-radius: var(--radius-md); text-align: center;">
        <span style="font-size: 0.72rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">
          ⚡ 1-Click Demo Logins
        </span>
        <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
          <a href="<?php echo BASE_URL; ?>/index.php?demo_login=candidate" class="btn btn-sm btn-outline" style="font-size: 0.78rem;">
            Dr. Arjun (Candidate)
          </a>
          <a href="<?php echo BASE_URL; ?>/index.php?demo_login=employer" class="btn btn-sm btn-outline" style="font-size: 0.78rem;">
            Aster Medcity (Hospital)
          </a>
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
function setLoginRole(role) {
  document.getElementById('loginRoleInput').value = role;
  const candBtn = document.getElementById('tabCandidateLogin');
  const empBtn = document.getElementById('tabEmployerLogin');
  const identifier = document.getElementById('loginIdentifier');

  if (role === 'candidate') {
    candBtn.className = 'btn btn-sm btn-primary';
    empBtn.className = 'btn btn-sm btn-outline';
    identifier.placeholder = 'doctor@example.com or +91 98765 43210';
    identifier.value = 'dr.arjun@hospital.com';
  } else {
    empBtn.className = 'btn btn-sm btn-primary';
    candBtn.className = 'btn btn-sm btn-outline';
    identifier.placeholder = 'hr@hospital.com or Medical Director ID';
    identifier.value = 'careers@astermedcity.com';
  }
}
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>
