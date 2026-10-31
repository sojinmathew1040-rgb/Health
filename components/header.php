<?php
/**
 * HealthJobsGlobal.com - Global Header Component
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Detect active page
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'HealthJobsGlobal — Premium Healthcare Career & Recruitment Platform'); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? 'Connecting verified doctors, nurses, allied health, diagnostics, and healthcare leaders with premier hospitals and medical institutions worldwide.'); ?>">

  <!-- Open Graph / Meta -->
  <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle ?? 'HealthJobsGlobal'); ?>">
  <meta property="og:description" content="Connecting verified doctors, nurses, allied health, diagnostics, and healthcare leaders with premier hospitals worldwide.">
  <meta property="og:type" content="website">

  <!-- Core Stylesheets -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=4.8">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/animations.css?v=4.8">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/responsive.css?v=4.8">
</head>
<body>

<?php
$isEmployerUser = isLoggedIn() && ((getLoggedInUser()['role'] ?? '') === 'employer');
$brandHomeUrl = $isEmployerUser ? (BASE_URL . '/employer-dashboard.php') : (BASE_URL . '/index.php');
?>
<!-- Global Header -->
<header class="site-header">
  <div class="container container-wide">
    <div class="header-inner">
      
      <!-- Brand Logo -->
      <a href="<?php echo $brandHomeUrl; ?>" class="site-logo" aria-label="HealthJobsGlobal Home">
        <div class="logo-symbol">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2v20M2 12h20"/>
            <circle cx="12" cy="12" r="9" stroke-width="1.8"/>
          </svg>
        </div>
        <span>HealthJobs<span class="logo-highlight">Global</span></span>
      </a>

      <!-- Desktop Navigation -->
      <nav class="nav-desktop" aria-label="Primary Navigation">
        <?php if ($isEmployerUser): ?>
          <!-- Clean Header: No navbar links for employers -->
        <?php else: ?>
          <?php foreach ($navLinks as $item): 
            $isActive = ($currentPage === basename($item['url']));
          ?>
            <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo $isActive ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($item['title']); ?>
              <?php if (!empty($item['badge'])): ?>
                <span class="nav-count-badge"><?php echo htmlspecialchars($item['badge']); ?></span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </nav>

      <!-- Header Actions -->
      <div class="header-actions">
        <?php if (isLoggedIn()): 
          $currentUser = getLoggedInUser();
        ?>
          <!-- Logged In User Pill & Dropdown -->
          <div class="user-profile-menu-container">
            <button type="button" class="user-profile-toggle-btn" id="userProfileToggleBtn" onclick="toggleUserHeaderDropdown(event)" aria-expanded="false" aria-haspopup="true">
              <div class="user-avatar-badge" style="<?php echo !empty($currentUser['logo_image']) ? 'padding:0; overflow:hidden; background:#FFFFFF;' : ''; ?>">
                <?php if (!empty($currentUser['logo_image'])): ?>
                  <img src="<?php echo BASE_URL . '/' . htmlspecialchars($currentUser['logo_image']); ?>" style="width:100%; height:100%; object-fit:cover; display:block;" alt="Logo">
                <?php else: ?>
                  <?php echo htmlspecialchars($currentUser['initials'] ?? 'AM'); ?>
                <?php endif; ?>
                <span class="user-status-dot"></span>
              </div>
              <div class="user-pill-text">
                <div class="user-pill-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'Healthcare User'); ?></div>
                <div class="user-pill-role"><?php echo ($currentUser['role'] ?? '') === 'employer' ? 'Hospital HR' : 'Verified Clinician'; ?></div>
              </div>
              <span class="user-pill-arrow">
                <?php echo getIcon('chevron-down', '', 14); ?>
              </span>
            </button>

            <!-- Dropdown Menu -->
            <div id="userHeaderDropdownMenu" class="user-header-dropdown" role="menu">
              <!-- Dropdown Header Info Card -->
              <div class="user-dd-header">
                <div class="user-dd-avatar" style="<?php echo !empty($currentUser['logo_image']) ? 'padding:0; overflow:hidden; background:#FFFFFF;' : ''; ?>">
                  <?php if (!empty($currentUser['logo_image'])): ?>
                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($currentUser['logo_image']); ?>" style="width:100%; height:100%; object-fit:cover; display:block;" alt="Logo">
                  <?php else: ?>
                    <?php echo htmlspecialchars($currentUser['initials'] ?? 'AM'); ?>
                  <?php endif; ?>
                </div>
                <div class="user-dd-info">
                  <div class="user-dd-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'Healthcare User'); ?></div>
                  <div class="user-dd-email"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></div>
                  <span class="user-dd-role-badge">
                    <?php echo getIcon('shield-check', '', 12); ?>
                    <span><?php echo ($currentUser['role'] ?? '') === 'employer' ? 'Hospital HR' : 'Verified Clinician'; ?></span>
                  </span>
                </div>
              </div>

              <!-- Dropdown Menu Links -->
              <div class="user-dd-body">
                <?php if (($currentUser['role'] ?? '') === 'employer'): ?>
                  <a href="<?php echo BASE_URL; ?>/employer-profile.php" class="user-dd-link" role="menuitem">
                    <span class="user-dd-icon"><?php echo getIcon('edit', '', 15); ?></span>
                    <span class="user-dd-label">Edit Profile</span>
                  </a>
                  <a href="<?php echo BASE_URL; ?>/employer-profile.php#password" class="user-dd-link" role="menuitem" onclick="if(typeof openChangePasswordModal==='function'){openChangePasswordModal(false); if(typeof toggleUserHeaderDropdown==='function')toggleUserHeaderDropdown(); return false;}">
                    <span class="user-dd-icon"><?php echo getIcon('lock', '', 15); ?></span>
                    <span class="user-dd-label">Change Password</span>
                  </a>
                <?php else: ?>
                  <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php" class="user-dd-link" role="menuitem">
                    <span class="user-dd-icon"><?php echo getIcon('user', '', 15); ?></span>
                    <span class="user-dd-label">Candidate Dashboard</span>
                  </a>
                  <a href="<?php echo BASE_URL; ?>/candidate-profile.php" class="user-dd-link" role="menuitem">
                    <span class="user-dd-icon"><?php echo getIcon('file-text', '', 15); ?></span>
                    <span class="user-dd-label">My Bio-Data / Profile</span>
                  </a>
                  <a href="<?php echo BASE_URL; ?>/my-applications.php" class="user-dd-link" role="menuitem">
                    <span class="user-dd-icon"><?php echo getIcon('check-circle', '', 15); ?></span>
                    <span class="user-dd-label">My Applications</span>
                  </a>
                  <a href="<?php echo BASE_URL; ?>/job-alerts.php" class="user-dd-link" role="menuitem">
                    <span class="user-dd-icon"><?php echo getIcon('bell', '', 15); ?></span>
                    <span class="user-dd-label">WhatsApp Alerts</span>
                  </a>
                <?php endif; ?>
              </div>

              <div class="user-dd-divider"></div>

              <!-- Sign Out Link -->
              <div class="user-dd-footer">
                <a href="<?php echo BASE_URL; ?>/logout.php" class="user-dd-link user-dd-logout" role="menuitem">
                  <span class="user-dd-icon user-dd-logout-icon"><?php echo getIcon('log-out', '', 15); ?></span>
                  <span class="user-dd-label">Sign Out</span>
                </a>
              </div>
            </div>
          </div>

          <?php if (($currentUser['role'] ?? '') === 'employer'): ?>
            <a href="<?php echo BASE_URL; ?>/post-job.php" class="btn btn-navy btn-sm header-post-job-btn">
              <?php echo getIcon('briefcase', 'btn-icon-svg', 16); ?>
              <span>Post a Job</span>
            </a>
          <?php endif; ?>

        <?php else: ?>
          <!-- Guest Header Actions -->
          <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline btn-sm header-login-btn">Login</a>
          <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-sm header-register-btn">Register</a>
        <?php endif; ?>

        <!-- Mobile Menu Hamburger -->
        <button class="mobile-menu-btn" aria-label="Open mobile menu">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
      </div>

    </div>
  </div>
</header>

<!-- Mobile Navigation Drawer -->
<div class="mobile-nav-drawer" id="mobileNavDrawer" aria-hidden="true">
  <div class="drawer-content">
    <div class="drawer-top">
      <div class="site-logo" style="font-size: 1.2rem;">
        <div class="logo-symbol" style="width: 32px; height: 32px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M2 12h20"/></svg>
        </div>
        <span>HealthJobs<span class="logo-highlight">Global</span></span>
      </div>
      <button class="drawer-close-btn btn-icon" aria-label="Close menu">
        <?php echo getIcon('x', '', 20); ?>
      </button>
    </div>

    <div class="drawer-links">
      <?php if ($isEmployerUser): ?>
        <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" class="drawer-link">
          <span>Employer Dashboard</span>
          <?php echo getIcon('chevron-right', '', 18); ?>
        </a>
        <a href="<?php echo BASE_URL; ?>/post-job.php" class="drawer-link">
          <span>Post New Vacancy</span>
          <?php echo getIcon('chevron-right', '', 18); ?>
        </a>
        <a href="<?php echo BASE_URL; ?>/employer-dashboard.php#jobs" class="drawer-link">
          <span>My Job Postings</span>
          <?php echo getIcon('chevron-right', '', 18); ?>
        </a>
        <a href="<?php echo BASE_URL; ?>/employer-dashboard.php#applications" class="drawer-link">
          <span>Candidate Applications</span>
          <?php echo getIcon('chevron-right', '', 18); ?>
        </a>
        <a href="<?php echo BASE_URL; ?>/candidate-search.php" class="drawer-link">
          <span>Search Verified Talent</span>
          <?php echo getIcon('chevron-right', '', 18); ?>
        </a>
      <?php else: ?>
        <?php foreach ($navLinks as $item): ?>
          <a href="<?php echo $item['url']; ?>" class="drawer-link">
            <span><?php echo htmlspecialchars($item['title']); ?></span>
            <?php echo getIcon('chevron-right', '', 18); ?>
          </a>
        <?php endforeach; ?>
        <?php if (isLoggedIn()): ?>
          <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php" class="drawer-link">
            <span>Candidate Dashboard</span>
            <?php echo getIcon('chevron-right', '', 18); ?>
          </a>
          <a href="<?php echo BASE_URL; ?>/candidate-profile.php" class="drawer-link">
            <span>My Bio-Data / Profile</span>
            <?php echo getIcon('chevron-right', '', 18); ?>
          </a>
          <a href="<?php echo BASE_URL; ?>/my-applications.php" class="drawer-link">
            <span>My Applications</span>
            <?php echo getIcon('chevron-right', '', 18); ?>
          </a>
          <a href="<?php echo BASE_URL; ?>/saved-jobs.php" class="drawer-link">
            <span>Saved Jobs</span>
            <?php echo getIcon('chevron-right', '', 18); ?>
          </a>
          <a href="<?php echo BASE_URL; ?>/job-alerts.php" class="drawer-link">
            <span>WhatsApp & Email Alerts</span>
            <?php echo getIcon('chevron-right', '', 18); ?>
          </a>
        <?php else: ?>
          <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php" class="drawer-link">
            <span>Candidate Portal</span>
            <?php echo getIcon('chevron-right', '', 18); ?>
          </a>
          <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" class="drawer-link">
            <span>Employer Portal</span>
            <?php echo getIcon('chevron-right', '', 18); ?>
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="drawer-actions">
      <?php if (isLoggedIn()): 
        $cu = getLoggedInUser();
        $isEmp = (($cu['role'] ?? '') === 'employer');
      ?>
        <div style="background: var(--color-bg); padding: 12px; border-radius: var(--radius-md); margin-bottom: 8px; border: 1px solid var(--color-border);">
          <div style="font-weight: 700; color: var(--color-primary); font-size: 0.95rem;"><?php echo htmlspecialchars($cu['name'] ?? ''); ?></div>
          <div style="font-size: 0.8rem; color: var(--color-secondary);"><?php echo htmlspecialchars($cu['email'] ?? ''); ?></div>
          <div style="font-size: 0.72rem; color: var(--color-text-muted); margin-top: 2px;">
            <?php echo $isEmp ? 'Hospital HR Recruiter' : 'Verified Healthcare Candidate'; ?>
          </div>
        </div>
        <a href="<?php echo $isEmp ? BASE_URL . '/employer-dashboard.php' : BASE_URL . '/candidate-dashboard.php'; ?>" class="btn btn-primary" style="width: 100%;">
          <?php echo $isEmp ? 'Employer Dashboard' : 'Candidate Dashboard'; ?>
        </a>
        <?php if ($isEmp): ?>
          <a href="<?php echo BASE_URL; ?>/post-job.php" class="btn btn-navy" style="width: 100%; margin-top: 8px;">Post a Healthcare Job</a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-outline" style="width: 100%; color: #DC2626; border-color: #FCA5A5; margin-top: 8px;">Sign Out</a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline" style="width: 100%;">Sign In</a>
        <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary" style="width: 100%;">Create Account</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function toggleUserHeaderDropdown(e) {
  if (e) e.stopPropagation();
  const menu = document.getElementById('userHeaderDropdownMenu');
  const btn = document.getElementById('userProfileToggleBtn');
  if (menu) {
    const isShowing = menu.classList.contains('show');
    if (isShowing) {
      menu.classList.remove('show');
      if (btn) {
        btn.classList.remove('active');
        btn.setAttribute('aria-expanded', 'false');
      }
    } else {
      menu.classList.add('show');
      if (btn) {
        btn.classList.add('active');
        btn.setAttribute('aria-expanded', 'true');
      }
    }
  }
}

document.addEventListener('click', function(e) {
  const menu = document.getElementById('userHeaderDropdownMenu');
  const btn = document.getElementById('userProfileToggleBtn');
  if (menu && menu.classList.contains('show')) {
    if (!menu.contains(e.target) && (!btn || !btn.contains(e.target))) {
      menu.classList.remove('show');
      if (btn) {
        btn.classList.remove('active');
        btn.setAttribute('aria-expanded', 'false');
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/auth-modal.php'; ?>

<main id="main-content">
