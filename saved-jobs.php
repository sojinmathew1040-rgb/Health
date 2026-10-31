<?php
/**
 * HealthJobsGlobal.com - Saved Healthcare Jobs
 */
$pageTitle = "Saved Jobs & Bookmarks — HealthJobsGlobal";
$pageDescription = "Manage bookmarked healthcare jobs and review clinical compensation packages.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Employer role guard
if (isLoggedIn()) {
    $currentUser = getLoggedInUser();
    if (($currentUser['role'] ?? '') === 'employer') {
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }
}

require_once __DIR__ . '/components/header.php';

$savedJobsList = array_slice($featuredJobs, 0, 4);
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">Saved Jobs</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 4px;">Saved Jobs (<?php echo count($savedJobsList); ?>)</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Saved opportunities ready for review and immediate application.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-outline btn-sm">Find More Jobs</a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr; gap: 16px;">
    <?php foreach ($savedJobsList as $job): ?>
      <?php require __DIR__ . '/components/job-card.php'; ?>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
