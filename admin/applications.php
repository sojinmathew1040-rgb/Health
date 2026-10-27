<?php
/**
 * Admin Panel - Inbound Applications & ATS Workflow
 */
$pageTitle = "Inbound Applications ATS — HealthJobsGlobal Admin";
$pageHeading = "Applicant Tracking System (ATS)";

require_once __DIR__ . '/includes/header.php';

// Handle Actions (Change Status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update Application Status
    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'Applied');
        if ($id > 0) {
            $stmt = $con->prepare("UPDATE `applications` SET `status` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("si", $newStatus, $id);
                $stmt->execute();
                $stmt->close();
                header('Location: applications.php?success=' . urlencode("Application status updated to {$newStatus}."));
                exit;
            }
        }
    }

    // Delete Application
    if ($action === 'delete_app') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $con->prepare("DELETE FROM `applications` WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header('Location: applications.php?success=' . urlencode("Application record removed."));
                exit;
            }
        }
    }
}

// Fetch Applications
$filterStatus = trim($_GET['status'] ?? '');
$sql = "SELECT * FROM `applications` WHERE 1=1";
$params = [];
$types = "";

if (!empty($filterStatus)) {
    $sql .= " AND `status` = ?";
    $params[] = $filterStatus;
    $types .= "s";
}
$sql .= " ORDER BY id DESC";

$applications = [];
if (!empty($con) && !$con->connect_error) {
    if (!empty($params)) {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $applications[] = $r;
            }
            $stmt->close();
        }
    } else {
        $res = $con->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $applications[] = $r;
            }
        }
    }
}

// Application to view detail modal
$viewApp = null;
if (isset($_GET['view'])) {
    $vId = (int)$_GET['view'];
    $stmtV = $con->prepare("SELECT * FROM `applications` WHERE id = ?");
    if ($stmtV) {
        $stmtV->bind_param("i", $vId);
        $stmtV->execute();
        $viewApp = $stmtV->get_result()->fetch_assoc();
        $stmtV->close();
    }
}

require_once __DIR__ . '/includes/topbar.php';
?>

<div class="toolbar">
  <div class="search-box">
    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" placeholder="Search applicant, job, council reg..." id="tableSearchInput">
  </div>

  <div class="filter-group">
    <form method="GET" action="applications.php">
      <select name="status" class="select-filter" onchange="this.form.submit()">
        <option value="">All Workflow Stages</option>
        <option value="Applied" <?php echo ($filterStatus === 'Applied') ? 'selected' : ''; ?>>Applied</option>
        <option value="Reviewing" <?php echo ($filterStatus === 'Reviewing') ? 'selected' : ''; ?>>Under Review</option>
        <option value="Shortlisted" <?php echo ($filterStatus === 'Shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
        <option value="Interview Scheduled" <?php echo ($filterStatus === 'Interview Scheduled') ? 'selected' : ''; ?>>Interview Scheduled</option>
        <option value="Offered" <?php echo ($filterStatus === 'Offered') ? 'selected' : ''; ?>>Offered</option>
        <option value="Rejected" <?php echo ($filterStatus === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
      </select>
      <?php if (!empty($filterStatus)): ?>
        <a href="applications.php" class="btn btn-secondary btn-sm">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Inbound Applications Stream (<?php echo count($applications); ?> Records)</h3>
    <span style="font-size: 0.8rem; color: var(--text-muted);">Table: <code>healthjobs.applications</code></span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Candidate</th>
          <th>Applied For</th>
          <th>Hospital</th>
          <th>Qualification & Experience</th>
          <th>Council Number</th>
          <th>ATS Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($applications)): ?>
          <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No candidate applications found for this filter.</td></tr>
        <?php else: ?>
          <?php foreach ($applications as $app): 
            $statusBadge = 'badge-applied';
            if ($app['status'] === 'Reviewing') $statusBadge = 'badge-reviewing';
            elseif ($app['status'] === 'Shortlisted') $statusBadge = 'badge-shortlisted';
            elseif ($app['status'] === 'Interview Scheduled') $statusBadge = 'badge-interview';
            elseif ($app['status'] === 'Offered') $statusBadge = 'badge-offered';
            elseif ($app['status'] === 'Rejected') $statusBadge = 'badge-rejected';
          ?>
            <tr>
              <td>
                <span class="job-cell-title"><?php echo htmlspecialchars($app['candidate_name']); ?></span>
                <span class="job-cell-sub"><?php echo htmlspecialchars($app['candidate_email']); ?></span>
              </td>
              <td>
                <span style="font-weight: 600;"><?php echo htmlspecialchars($app['job_title']); ?></span>
              </td>
              <td><?php echo htmlspecialchars($app['hospital_name']); ?></td>
              <td>
                <div style="font-weight: 500; font-size: 0.82rem;"><?php echo htmlspecialchars($app['qualification']); ?></div>
                <div style="font-size: 0.74rem; color: var(--text-muted);"><?php echo htmlspecialchars($app['experience']); ?></div>
              </td>
              <td>
                <span style="font-family: monospace; font-size: 0.8rem; background: #F1F5F9; padding: 2px 6px; border-radius: 4px;">
                  <?php echo htmlspecialchars($app['council_reg'] ?: 'Under Review'); ?>
                </span>
              </td>
              <td>
                <!-- Inline Status Changer -->
                <form method="POST" action="applications.php" style="display:inline;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                  <select name="status" onchange="this.form.submit()" style="padding: 4px 8px; border-radius: var(--radius-pill); font-size: 0.76rem; font-weight: 600; cursor: pointer; border: 1px solid var(--border-light); background: #fff;">
                    <option value="Applied" <?php echo ($app['status'] === 'Applied') ? 'selected' : ''; ?>>Applied</option>
                    <option value="Reviewing" <?php echo ($app['status'] === 'Reviewing') ? 'selected' : ''; ?>>Reviewing</option>
                    <option value="Shortlisted" <?php echo ($app['status'] === 'Shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                    <option value="Interview Scheduled" <?php echo ($app['status'] === 'Interview Scheduled') ? 'selected' : ''; ?>>Interview Scheduled</option>
                    <option value="Offered" <?php echo ($app['status'] === 'Offered') ? 'selected' : ''; ?>>Offered</option>
                    <option value="Rejected" <?php echo ($app['status'] === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                  </select>
                </form>
              </td>
              <td>
                <div class="table-actions">
                  <a href="applications.php?view=<?php echo $app['id']; ?>" class="btn btn-secondary btn-sm" title="View Application Note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <form method="POST" action="applications.php" onsubmit="return confirm('Delete this application record?');" style="display:inline;">
                    <input type="hidden" name="action" value="delete_app">
                    <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-rose);" title="Delete Application">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: View Application Details -->
<?php if ($viewApp): ?>
<div class="modal-overlay active" id="viewAppModal">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h3>Application #<?php echo $viewApp['id']; ?> — <?php echo htmlspecialchars($viewApp['candidate_name']); ?></h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Applied for <?php echo htmlspecialchars($viewApp['job_title']); ?> at <?php echo htmlspecialchars($viewApp['hospital_name']); ?></p>
      </div>
      <a href="applications.php" class="modal-close">&times;</a>
    </div>
    <div class="modal-body">
      <div style="background: #F8FAFC; border-radius: var(--radius-md); padding: 18px; margin-bottom: 20px; border: 1px solid var(--border-light);">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; font-size: 0.88rem;">
          <div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Email Address</div>
            <div style="font-weight: 600;"><?php echo htmlspecialchars($viewApp['candidate_email']); ?></div>
          </div>
          <div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Phone Number</div>
            <div style="font-weight: 600;"><?php echo htmlspecialchars($viewApp['candidate_phone'] ?: 'N/A'); ?></div>
          </div>
          <div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Qualifications</div>
            <div style="font-weight: 600;"><?php echo htmlspecialchars($viewApp['qualification']); ?></div>
          </div>
          <div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Council Registration</div>
            <div style="font-weight: 600; font-family: monospace;"><?php echo htmlspecialchars($viewApp['council_reg'] ?: 'Not Provided'); ?></div>
          </div>
        </div>
      </div>

      <div style="margin-bottom: 20px;">
        <label class="form-label">Candidate Statement / Cover Note</label>
        <div style="padding: 14px; background: #FFFFFF; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem; line-height: 1.6; color: #334155;">
          <?php echo nl2br(htmlspecialchars($viewApp['cover_letter'] ?: 'No statement submitted.')); ?>
        </div>
      </div>

      <!-- Quick Update Form -->
      <form method="POST" action="applications.php" style="display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--border-light);">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?php echo $viewApp['id']; ?>">
        <div style="display: flex; align-items: center; gap: 10px;">
          <label style="font-size: 0.84rem; font-weight: 600;">Update Status:</label>
          <select name="status" class="form-control" style="width: auto; padding: 6px 12px;">
            <option value="Applied" <?php echo ($viewApp['status'] === 'Applied') ? 'selected' : ''; ?>>Applied</option>
            <option value="Reviewing" <?php echo ($viewApp['status'] === 'Reviewing') ? 'selected' : ''; ?>>Reviewing</option>
            <option value="Shortlisted" <?php echo ($viewApp['status'] === 'Shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
            <option value="Interview Scheduled" <?php echo ($viewApp['status'] === 'Interview Scheduled') ? 'selected' : ''; ?>>Interview Scheduled</option>
            <option value="Offered" <?php echo ($viewApp['status'] === 'Offered') ? 'selected' : ''; ?>>Offered</option>
            <option value="Rejected" <?php echo ($viewApp['status'] === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
          </select>
          <button type="submit" class="btn btn-primary btn-sm">Save Status</button>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <a href="applications.php" class="btn btn-secondary">Close</a>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
