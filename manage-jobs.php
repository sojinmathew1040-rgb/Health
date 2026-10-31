<?php
/**
 * HealthJobsGlobal.com - Manage Hospital Job Postings (Live MySQL Data)
 */
$pageTitle = "My Vacancies & Active Postings — HealthJobsGlobal";
$pageDescription = "Monitor applicant inflow, pause or renew clinical postings, and review applications.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Access Control
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode(BASE_URL . "/manage-jobs.php"));
    exit;
}

$activeUser = getLoggedInUser();
if (($activeUser['role'] ?? '') !== 'employer') {
    header("Location: " . BASE_URL . "/employer-dashboard.php");
    exit;
}

$hospitalId = (int)($activeUser['id'] ?? 0);
$hospitalName = !empty($activeUser['hospital']) ? $activeUser['hospital'] : ($activeUser['name'] ?? '');

// Handle Job Status Toggle (Pause / Resume / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $targetJobId = (int)($_POST['job_id'] ?? 0);
    
    if ($action === 'toggle_status' && $targetJobId > 0 && !empty($con)) {
        $newStatus = ($_POST['current_status'] ?? '') === 'Active' ? 'Closed' : 'Active';
        $stmtT = $con->prepare("UPDATE `jobs` SET `status` = ? WHERE `id` = ? AND (`hospital_id` = ? OR `company` = ?)");
        if ($stmtT) {
            $stmtT->bind_param("siis", $newStatus, $targetJobId, $hospitalId, $hospitalName);
            $stmtT->execute();
            $stmtT->close();
            // Update hospital active openings count
            $con->query("UPDATE `hospitals` SET `openings` = (SELECT COUNT(*) FROM `jobs` WHERE (`hospital_id` = {$hospitalId} OR `company` = '" . $con->real_escape_string($hospitalName) . "') AND `status` = 'Active') WHERE `id` = {$hospitalId}");
        }
        header("Location: " . BASE_URL . "/manage-jobs.php");
        exit;
    }
}

// Fetch Real Job Postings from Database
$myPostings = [];
if (!empty($con) && !$con->connect_error) {
    $stmt = $con->prepare("SELECT j.*, 
        (SELECT COUNT(*) FROM `applications` WHERE `job_id` = j.id) as real_applicants,
        (SELECT COUNT(*) FROM `applications` WHERE `job_id` = j.id AND `status` IN ('Shortlisted', 'Interview Scheduled')) as real_shortlisted
        FROM `jobs` j 
        WHERE j.hospital_id = ? OR j.company = ?
        ORDER BY j.id DESC");
    if ($stmt) {
        $stmt->bind_param("is", $hospitalId, $hospitalName);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $row['posted'] = !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : (!empty($row['posted_at']) ? date('d M Y', strtotime($row['posted_at'])) : 'Recently');
            $row['applicants'] = (int)($row['real_applicants'] ?? 0);
            $row['shortlisted'] = (int)($row['real_shortlisted'] ?? 0);
            $row['status_cls'] = ($row['status'] === 'Active') ? 'badge-verified' : 'badge-default';
            $myPostings[] = $row;
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">My Vacancies</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 4px;">My Healthcare Vacancies (<?php echo count($myPostings); ?>)</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Track live postings and candidate applicant pipelines for <strong style="color: var(--color-primary);"><?php echo htmlspecialchars($hospitalName); ?></strong>.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo BASE_URL; ?>/post-job.php" class="btn btn-primary btn-sm">
          <?php echo getIcon('briefcase', '', 16); ?>
          <span>Post New Vacancy</span>
        </a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  
  <?php if (empty($myPostings)): ?>
    <div style="background: #FFFFFF; border: 2px dashed var(--color-border); border-radius: var(--radius-xl); padding: 48px 24px; text-align: center; margin: 20px 0;">
      <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--color-secondary-soft); color: var(--color-secondary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 16px;">
        <?php echo getIcon('briefcase', '', 32); ?>
      </div>
      <h3 style="font-size: 1.35rem; color: var(--color-primary); margin-bottom: 8px;">No Healthcare Vacancies Posted Yet</h3>
      <p style="color: var(--color-text-secondary); max-width: 500px; margin: 0 auto 24px auto; font-size: 0.95rem;">
        Publish your first medical vacancy to broadcast to thousands of verified doctors, nurses, and healthcare specialists worldwide.
      </p>
      <a href="<?php echo BASE_URL; ?>/post-job.php" class="btn btn-primary btn-lg">
        <span>Post First Vacancy Now</span>
        <?php echo getIcon('arrow-right', '', 18); ?>
      </a>
    </div>
  <?php else: ?>
    <div class="grid" style="grid-template-columns: 1fr; gap: 16px;">
      <?php foreach ($myPostings as $posting): ?>
        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm);">
          <div class="flex-between items-center" style="margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <div class="flex items-center gap-3">
              <span class="hjg-badge <?php echo $posting['status_cls']; ?>"><?php echo htmlspecialchars($posting['status']); ?></span>
              <span style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600;"><?php echo htmlspecialchars($posting['department']); ?></span>
              <?php if (!empty($posting['urgent'])): ?>
                <span class="hjg-badge badge-gold">⚡ Urgent Hiring</span>
              <?php endif; ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--color-text-muted);">Posted <?php echo htmlspecialchars($posting['posted']); ?></div>
          </div>

          <div class="flex-between items-center" style="margin-bottom: 16px; flex-wrap: wrap; gap: 16px;">
            <div>
              <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 4px;">
                <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $posting['id']; ?>"><?php echo htmlspecialchars($posting['title']); ?></a>
              </h3>
              <div class="flex items-center gap-4" style="font-size: 0.85rem; color: var(--color-text-secondary);">
                <span><?php echo htmlspecialchars($posting['location']); ?></span>
                <span>·</span>
                <span><strong>Experience:</strong> <?php echo htmlspecialchars($posting['experience']); ?></span>
                <span>·</span>
                <span><strong>Salary:</strong> <?php echo htmlspecialchars($posting['salary']); ?></span>
              </div>
            </div>

            <!-- Application Counts -->
            <div class="flex items-center gap-6">
              <div style="text-align: center; background: var(--color-bg); padding: 8px 16px; border-radius: var(--radius-md);">
                <div style="font-size: 1.4rem; font-weight: 800; color: var(--color-primary);"><?php echo $posting['applicants']; ?></div>
                <div style="font-size: 0.72rem; color: var(--color-text-muted); text-transform: uppercase; font-weight: 700;">Applicants</div>
              </div>
              <div style="text-align: center; background: #ECFDF5; padding: 8px 16px; border-radius: var(--radius-md);">
                <div style="font-size: 1.4rem; font-weight: 800; color: #059669;"><?php echo $posting['shortlisted']; ?></div>
                <div style="font-size: 0.72rem; color: #059669; text-transform: uppercase; font-weight: 700;">Shortlisted</div>
              </div>
            </div>
          </div>

          <div class="flex-between items-center" style="border-top: 1px solid var(--color-border); padding-top: 14px; font-size: 0.85rem; flex-wrap: wrap; gap: 10px;">
            <div class="flex items-center gap-3">
              <a href="<?php echo BASE_URL; ?>/employer-dashboard.php?view_job=<?php echo $posting['id']; ?>#vacancy-apps-<?php echo $posting['id']; ?>" class="btn btn-primary btn-sm">
                View <?php echo $posting['applicants']; ?> Applicants &darr;
              </a>
              <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $posting['id']; ?>" target="_blank" class="btn btn-outline btn-sm">
                Public View ↗
              </a>
            </div>
            <div class="flex items-center gap-2">
              <form method="POST" action="<?php echo BASE_URL; ?>/manage-jobs.php" style="display: inline;">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="job_id" value="<?php echo $posting['id']; ?>">
                <input type="hidden" name="current_status" value="<?php echo $posting['status']; ?>">
                <button type="submit" class="btn btn-sm btn-outline">
                  <?php echo ($posting['status'] === 'Active') ? 'Pause Listing' : 'Reactivate Listing'; ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
