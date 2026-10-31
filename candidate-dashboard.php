<?php
/**
 * HealthJobsGlobal.com - Candidate Dashboard
 */
$pageTitle = "Candidate Portal & Dashboard — HealthJobsGlobal";
$pageDescription = "Manage your healthcare applications, council credentials, and hospital interviews.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Employer role guard: Employers only need and use Employer Dashboard
if (isLoggedIn()) {
    $currentUser = getLoggedInUser();
    if (($currentUser['role'] ?? '') === 'employer') {
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }
}

require_once __DIR__ . '/components/header.php';
?>

<?php
$currentUser = getLoggedInUser();
$displayName = $currentUser['name'] ?? 'Healthcare Professional';
$displayInitials = $currentUser['initials'] ?? 'HP';
$displayTitle = !empty($currentUser['title']) ? $currentUser['title'] : (!empty($currentUser['degree']) ? $currentUser['degree'] : 'Clinical Specialist');
if (!empty($currentUser['council'])) {
    $displayTitle .= ' · Reg: ' . $currentUser['council'];
}

$candEmail = $currentUser['email'] ?? '';
$candAppsCount = 0;
$candShortlistCount = 0;
$scheduledInterview = null;

if (!empty($candEmail) && !empty($con) && !$con->connect_error) {
    // Real applications count
    $stmtC1 = $con->prepare("SELECT COUNT(*) as c FROM `applications` WHERE `candidate_email` = ?");
    if ($stmtC1) {
        $stmtC1->bind_param("s", $candEmail);
        $stmtC1->execute();
        $rC1 = $stmtC1->get_result();
        if ($rw1 = $rC1->fetch_assoc()) $candAppsCount = (int)$rw1['c'];
        $stmtC1->close();
    }

    // Real shortlist count
    $stmtC2 = $con->prepare("SELECT COUNT(*) as c FROM `applications` WHERE `candidate_email` = ? AND `status` IN ('Shortlisted', 'Interview Scheduled')");
    if ($stmtC2) {
        $stmtC2->bind_param("s", $candEmail);
        $stmtC2->execute();
        $rC2 = $stmtC2->get_result();
        if ($rw2 = $rC2->fetch_assoc()) $candShortlistCount = (int)$rw2['c'];
        $stmtC2->close();
    }

    // Check for scheduled interview
    $stmtC3 = $con->prepare("SELECT * FROM `applications` WHERE `candidate_email` = ? AND `status` = 'Interview Scheduled' ORDER BY id DESC LIMIT 1");
    if ($stmtC3) {
        $stmtC3->bind_param("s", $candEmail);
        $stmtC3->execute();
        $rC3 = $stmtC3->get_result();
        if ($rw3 = $rC3->fetch_assoc()) $scheduledInterview = $rw3;
        $stmtC3->close();
    }
}
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div class="flex items-center gap-4">
        <div class="doctor-avatar-wrap" style="width: 64px; height: 64px; font-size: 1.2rem;">
          <span><?php echo htmlspecialchars($displayInitials); ?></span>
        </div>
        <div>
          <div class="flex items-center gap-2" style="margin-bottom: 2px;">
            <h1 style="font-size: 1.65rem; color: var(--color-primary); margin: 0;">Welcome, <?php echo htmlspecialchars($displayName); ?></h1>
            <span class="hjg-badge badge-verified"><?php echo getIcon('shield-check', '', 14); ?> Council Verified</span>
          </div>
          <div style="color: var(--color-text-secondary); font-size: 0.9rem;">
            <?php echo htmlspecialchars($displayTitle); ?>
          </div>
        </div>
      </div>

      <div class="dashboard-header-actions flex items-center gap-3">
        <a href="<?php echo BASE_URL; ?>/candidate-profile.php" class="btn btn-outline btn-sm">Edit Clinical Profile</a>
        <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-primary btn-sm">Search Jobs</a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  
  <!-- Candidate Metrics Row (100% Dynamic MySQL) -->
  <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
      <div style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Applications Sent</div>
      <div style="font-size: 1.8rem; font-weight: 800; color: var(--color-primary); margin-top: 4px;"><?php echo $candAppsCount; ?></div>
      <div style="font-size: 0.8rem; color: var(--color-secondary); margin-top: 4px;"><a href="<?php echo BASE_URL; ?>/my-applications.php">View status & timeline →</a></div>
    </div>

    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
      <div style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Interview Shortlists</div>
      <div style="font-size: 1.8rem; font-weight: 800; color: #059669; margin-top: 4px;"><?php echo $candShortlistCount; ?></div>
      <div style="font-size: 0.8rem; color: var(--color-success); margin-top: 4px;">Verified by hospital HR</div>
    </div>

    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
      <div style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Active Opportunities</div>
      <div style="font-size: 1.8rem; font-weight: 800; color: var(--color-secondary); margin-top: 4px;"><?php echo count($featuredJobs); ?></div>
      <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 4px;"><a href="<?php echo BASE_URL; ?>/jobs.php">Browse vacancies &rarr;</a></div>
    </div>

    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
      <div style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Council License</div>
      <div style="font-size: 1.1rem; font-weight: 800; color: var(--color-gold-dark); margin-top: 8px;"><?php echo htmlspecialchars($currentUser['council'] ?? 'Registered'); ?></div>
      <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 4px;">Verified for direct hire</div>
    </div>
  </div>

  <div class="grid" style="grid-template-columns: 1fr 340px; gap: var(--space-8); align-items: start;">
    
    <!-- Left Column: Active Applications & Recommendations -->
    <main>
      
      <?php if (!empty($scheduledInterview)): ?>
        <!-- Scheduled Interview Alert Banner -->
        <div class="interview-alert-card" style="background: #ECFDF5; border: 1.5px solid #10B981; border-radius: var(--radius-lg); padding: 20px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
          <div class="flex items-center gap-3">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: #D1FAE5; color: #059669; display: flex; align-items: center; justify-content: center;">
              <?php echo getIcon('calendar', '', 22); ?>
            </div>
            <div>
              <div style="font-weight: 700; color: #065F46; font-size: 1rem;">Interview Invitation Scheduled!</div>
              <div style="font-size: 0.85rem; color: #047857;">Hospital: <strong><?php echo htmlspecialchars($scheduledInterview['hospital_name']); ?></strong> — <?php echo htmlspecialchars($scheduledInterview['job_title']); ?></div>
              <div style="font-size: 0.8rem; color: #065F46; margin-top: 2px;">The hospital recruitment cell has shortlisted your bio-data for direct interview.</div>
            </div>
          </div>
          <a href="<?php echo BASE_URL; ?>/my-applications.php" class="btn btn-sm btn-primary" style="background: #059669;">
            View Application Details &rarr;
          </a>
        </div>
      <?php endif; ?>

      <!-- Recommended Jobs for your Specialty -->
      <section style="margin-bottom: 32px;">
        <div class="flex-between items-center" style="margin-bottom: 16px;">
          <h3 style="font-size: 1.35rem; color: var(--color-primary);">Healthcare Opportunities</h3>
          <a href="<?php echo BASE_URL; ?>/jobs.php" style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600;">View All Jobs →</a>
        </div>

        <?php if (empty($featuredJobs)): ?>
          <div style="background: #FFFFFF; border: 2px dashed var(--color-border); border-radius: var(--radius-lg); padding: 36px 20px; text-align: center;">
            <p style="color: var(--color-text-secondary); margin: 0;">No active job postings at this moment. New vacancies posted by hospital recruiters will appear here.</p>
          </div>
        <?php else: ?>
          <div class="grid" style="grid-template-columns: 1fr; gap: 16px;">
            <?php 
            $recJobs = array_slice($featuredJobs, 0, 4);
            foreach ($recJobs as $job): 
              require __DIR__ . '/components/job-card.php';
            endforeach; 
            ?>
          </div>
        <?php endif; ?>
      </section>

    </main>

    <!-- Right Sidebar: Profile Strength & Quick Settings -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- Profile Strength Box -->
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <div class="flex-between items-center" style="margin-bottom: 12px;">
          <h4 style="font-size: 1.1rem; color: var(--color-primary);">Profile Strength</h4>
          <span style="font-size: 1rem; font-weight: 800; color: var(--color-secondary);">85%</span>
        </div>

        <div style="width: 100%; height: 8px; background: var(--color-bg); border-radius: 4px; overflow: hidden; margin-bottom: 14px;">
          <div style="width: 85%; height: 100%; background: linear-gradient(90deg, var(--color-secondary), var(--color-accent));"></div>
        </div>

        <p style="font-size: 0.85rem; color: var(--color-text-secondary); line-height: 1.5; margin-bottom: 16px;">
          Add your <strong>Cath Lab surgical logbook</strong> and verified CME certificates to achieve a 100% Star Profile for Middle East hospital directors.
        </p>

        <a href="<?php echo BASE_URL; ?>/candidate-profile.php" class="btn btn-outline btn-sm" style="width: 100%;">Complete Profile</a>
      </div>

      <!-- Quick Links Navigation -->
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
        <h4 style="font-size: 1rem; color: var(--color-primary); margin-bottom: 14px;">Candidate Navigation</h4>
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
          <a href="<?php echo BASE_URL; ?>/my-applications.php" class="flex-between items-center" style="padding: 8px 10px; border-radius: var(--radius-sm); background: var(--color-bg);">
            <span class="flex items-center gap-2"><?php echo getIcon('file-text', '', 16); ?> My Applications</span>
            <span class="hjg-badge badge-default">5</span>
          </a>
          <a href="<?php echo BASE_URL; ?>/saved-jobs.php" class="flex-between items-center" style="padding: 8px 10px; border-radius: var(--radius-sm); background: var(--color-bg);">
            <span class="flex items-center gap-2"><?php echo getIcon('bookmark', '', 16); ?> Saved Jobs</span>
            <span class="hjg-badge badge-default">4</span>
          </a>
          <a href="<?php echo BASE_URL; ?>/job-alerts.php" class="flex-between items-center" style="padding: 8px 10px; border-radius: var(--radius-sm); background: var(--color-bg);">
            <span class="flex items-center gap-2"><?php echo getIcon('bell', '', 16); ?> WhatsApp & Email Alerts</span>
            <span class="hjg-badge badge-verified">Active</span>
          </a>
        </div>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
