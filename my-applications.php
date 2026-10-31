<?php
/**
 * HealthJobsGlobal.com - My Applications Pipeline (Live Database)
 */
$pageTitle = "My Applications & Status Pipeline — HealthJobsGlobal";
$pageDescription = "Track the status of your clinical job applications with verified hospitals.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Employer role guard
if (isLoggedIn()) {
    $currentUser = getLoggedInUser();
    if (($currentUser['role'] ?? '') === 'employer') {
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }
}

$candEmail = isLoggedIn() ? (getLoggedInUser()['email'] ?? '') : '';

// Query Real Inbound Applications from Database for this Candidate
$myApplications = [];
if (!empty($candEmail) && !empty($con) && !$con->connect_error) {
    $stmtMy = $con->prepare("SELECT a.*, j.location as job_location, j.salary as job_salary, j.department as job_department 
        FROM `applications` a
        LEFT JOIN `jobs` j ON a.job_id = j.id
        WHERE a.candidate_email = ?
        ORDER BY a.id DESC");
    if ($stmtMy) {
        $stmtMy->bind_param("s", $candEmail);
        $stmtMy->execute();
        $resMy = $stmtMy->get_result();
        while ($row = $resMy->fetch_assoc()) {
            $row['applied_date'] = !empty($row['applied_at']) ? date('d M Y', strtotime($row['applied_at'])) : 'Recently';
            $myApplications[] = $row;
        }
        $stmtMy->close();
    }
}

require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">My Applications</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 4px;">Application Tracker (<?php echo count($myApplications); ?>)</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Track review statuses, council audits, and interview invitations across hospital recruitment boards.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-primary btn-sm">Explore More Opportunities</a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  
  <?php if (empty($myApplications)): ?>
    <div style="background: #FFFFFF; border: 2px dashed var(--color-border); border-radius: var(--radius-xl); padding: 50px 24px; text-align: center; margin: 20px 0;">
      <div style="width: 64px; height: 64px; border-radius: 50%; background: #E0F2FE; color: #0284C7; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 16px;">
        <?php echo getIcon('file-text', '', 32); ?>
      </div>
      <h3 style="font-size: 1.35rem; color: var(--color-primary); margin-bottom: 8px;">No Active Applications Submitted</h3>
      <p style="color: var(--color-text-secondary); max-width: 500px; margin: 0 auto 24px auto; font-size: 0.95rem;">
        You have not submitted applications to any hospital vacancies yet. Explore accredited opportunities and apply directly using your clinical credentials.
      </p>
      <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-primary btn-lg">
        <span>Browse Healthcare Openings</span>
        <?php echo getIcon('arrow-right', '', 18); ?>
      </a>
    </div>
  <?php else: ?>
    <div class="grid" style="grid-template-columns: 1fr; gap: 18px;">
      <?php foreach ($myApplications as $app): 
        $statusCls = 'badge-verified';
        if ($app['status'] === 'Shortlisted') $statusCls = 'badge-verified';
        elseif ($app['status'] === 'Interview Scheduled') $statusCls = 'badge-gold';
        elseif ($app['status'] === 'Rejected') $statusCls = 'badge-default';
      ?>
        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm);">
          <div class="flex-between items-center" style="margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <div class="flex items-center gap-3">
              <span class="hjg-badge <?php echo $statusCls; ?>"><?php echo htmlspecialchars($app['status']); ?></span>
              <span style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 700;"><?php echo htmlspecialchars($app['hospital_name']); ?></span>
            </div>
            <div style="font-size: 0.8rem; color: var(--color-text-muted);">Applied <?php echo htmlspecialchars($app['applied_date']); ?></div>
          </div>

          <div class="flex-between items-center" style="margin-bottom: 16px; flex-wrap: wrap; gap: 16px;">
            <div>
              <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 4px;">
                <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $app['job_id']; ?>"><?php echo htmlspecialchars($app['job_title']); ?></a>
              </h3>
              <div class="flex items-center gap-4" style="font-size: 0.85rem; color: var(--color-text-secondary);">
                <span>📍 <?php echo htmlspecialchars($app['job_location'] ?? 'Hospital Campus'); ?></span>
                <?php if (!empty($app['job_salary'])): ?>
                  <span>·</span>
                  <span>💰 <?php echo htmlspecialchars($app['job_salary']); ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div style="text-align: right;">
              <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $app['job_id']; ?>" class="btn btn-outline btn-sm">
                View Vacancy Details &rarr;
              </a>
            </div>
          </div>

          <div style="border-top: 1px solid var(--color-border); padding-top: 14px; font-size: 0.85rem; color: var(--color-text-secondary); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div class="flex items-center gap-2">
              <span style="color: var(--color-primary); font-weight: 600;">Submitted Council Reg:</span>
              <span><?php echo htmlspecialchars($app['council_reg'] ?: 'Under Verification'); ?></span>
            </div>
            <div>
              Status: <strong style="color: var(--color-primary);"><?php echo htmlspecialchars($app['status']); ?></strong>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
