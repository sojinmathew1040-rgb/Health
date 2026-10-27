<?php
/**
 * Admin Panel - Candidates Directory & Medical Council Verification
 */
$pageTitle = "Candidates Directory — HealthJobsGlobal Admin";
$pageHeading = "Healthcare Candidate Pool & Verification";

require_once __DIR__ . '/includes/header.php';

// Handle Actions (Add, Delete, Status Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add Candidate
    if ($action === 'create_candidate') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $degree = trim($_POST['degree'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $council_reg = trim($_POST['council_reg'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $status = trim($_POST['status'] ?? 'Verified');

        if (!empty($name) && !empty($email)) {
            $stmt = $con->prepare("INSERT INTO `candidates` (`name`, `email`, `phone`, `degree`, `specialty`, `experience`, `council_reg`, `location`, `status`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("sssssssss", $name, $email, $phone, $degree, $specialty, $experience, $council_reg, $location, $status);
                $stmt->execute();
                $stmt->close();
                header('Location: candidates.php?success=' . urlencode("Candidate '{$name}' registered successfully!"));
                exit;
            }
        }
    }

    // Toggle Status
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'Verified');
        if ($id > 0) {
            $stmt = $con->prepare("UPDATE `candidates` SET `status` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("si", $newStatus, $id);
                $stmt->execute();
                $stmt->close();
                header('Location: candidates.php?success=' . urlencode("Candidate verification updated."));
                exit;
            }
        }
    }

    // Delete Candidate
    if ($action === 'delete_candidate') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $con->prepare("DELETE FROM `candidates` WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header('Location: candidates.php?success=' . urlencode("Candidate profile removed."));
                exit;
            }
        }
    }
}

// Fetch Candidates
$candidates = [];
if (!empty($con) && !$con->connect_error) {
    $res = $con->query("SELECT * FROM `candidates` ORDER BY id DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $candidates[] = $r;
        }
    }
}

$quickActionBtn = '<button class="btn btn-primary btn-sm" data-modal-target="addCandidateModal">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  <span>Add Candidate</span>
</button>';

require_once __DIR__ . '/includes/topbar.php';
?>

<div class="toolbar">
  <div class="search-box">
    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" placeholder="Search candidate, council reg, degree..." id="tableSearchInput">
  </div>
  <button class="btn btn-primary btn-sm" data-modal-target="addCandidateModal">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Register Healthcare Professional
  </button>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Registered Candidates (<?php echo count($candidates); ?> Specialists)</h3>
    <span style="font-size: 0.8rem; color: var(--text-muted);">Table: <code>healthjobs.candidates</code></span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Candidate Name</th>
          <th>Qualifications & Specialty</th>
          <th>Council Registration</th>
          <th>Experience</th>
          <th>Contact Info</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($candidates)): ?>
          <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No candidates recorded yet.</td></tr>
        <?php else: ?>
          <?php foreach ($candidates as $cand): 
            $cStatusCls = ($cand['status'] === 'Verified') ? 'badge-verified' : (($cand['status'] === 'Pending') ? 'badge-pending' : 'badge-closed');
          ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                  <div style="width: 34px; height: 34px; border-radius: 50%; background: #E0F2FE; color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;">
                    <?php echo strtoupper(substr($cand['name'], 0, 2)); ?>
                  </div>
                  <div>
                    <span class="job-cell-title"><?php echo htmlspecialchars($cand['name']); ?></span>
                    <span class="job-cell-sub"><?php echo htmlspecialchars($cand['location'] ?: 'Location Not Specified'); ?></span>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-weight: 600; font-size: 0.84rem;"><?php echo htmlspecialchars($cand['degree']); ?></div>
                <div style="font-size: 0.74rem; color: var(--text-muted);"><?php echo htmlspecialchars($cand['specialty']); ?></div>
              </td>
              <td>
                <span style="font-family: monospace; font-size: 0.8rem; background: #F1F5F9; padding: 3px 8px; border-radius: 4px; border: 1px solid #E2E8F0;">
                  <?php echo htmlspecialchars($cand['council_reg'] ?: 'Under Review'); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($cand['experience'] ?: 'Fresh / 1+ yr'); ?></td>
              <td>
                <div style="font-size: 0.82rem;"><?php echo htmlspecialchars($cand['email']); ?></div>
                <div style="font-size: 0.74rem; color: var(--text-muted);"><?php echo htmlspecialchars($cand['phone'] ?: 'N/A'); ?></div>
              </td>
              <td>
                <form method="POST" action="candidates.php" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?php echo $cand['id']; ?>">
                  <select name="status" onchange="this.form.submit()" style="padding: 3px 8px; border-radius: var(--radius-pill); font-size: 0.76rem; font-weight: 600; cursor: pointer; border: 1px solid var(--border-light); background: #fff;">
                    <option value="Verified" <?php echo ($cand['status'] === 'Verified') ? 'selected' : ''; ?>>🟢 Council Verified</option>
                    <option value="Pending" <?php echo ($cand['status'] === 'Pending') ? 'selected' : ''; ?>>🟡 Under Review</option>
                    <option value="Suspended" <?php echo ($cand['status'] === 'Suspended') ? 'selected' : ''; ?>>🔴 Suspended</option>
                  </select>
                </form>
              </td>
              <td>
                <div class="table-actions">
                  <a href="../candidate-profile-view.php" target="_blank" class="btn btn-secondary btn-sm" title="Preview Candidate Profile">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <form method="POST" action="candidates.php" onsubmit="return confirm('Remove candidate profile?');" style="display:inline;">
                    <input type="hidden" name="action" value="delete_candidate">
                    <input type="hidden" name="id" value="<?php echo $cand['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-rose);" title="Delete Profile">
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

<!-- Modal: Add Candidate -->
<div class="modal-overlay" id="addCandidateModal">
  <div class="modal-card">
    <div class="modal-header">
      <h3>+ Register Healthcare Professional</h3>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="candidates.php">
      <input type="hidden" name="action" value="create_candidate">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Malini Varma" required>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" class="form-control" placeholder="malini.varma@medmail.com" required>
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="+91 98470 54321">
          </div>

          <div class="form-group">
            <label class="form-label">Degree / Clinical Qualification *</label>
            <input type="text" name="degree" class="form-control" placeholder="e.g. MBBS, MS (General Surgery)" required>
          </div>

          <div class="form-group">
            <label class="form-label">Specialty / Subspecialty</label>
            <input type="text" name="specialty" class="form-control" placeholder="e.g. Laparoscopic Surgery">
          </div>

          <div class="form-group">
            <label class="form-label">Medical Council Reg. Number</label>
            <input type="text" name="council_reg" class="form-control" placeholder="e.g. TCMC 54210 / NMC Active">
          </div>

          <div class="form-group">
            <label class="form-label">Total Experience</label>
            <input type="text" name="experience" class="form-control" placeholder="e.g. 6+ Years Post-MS">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Current City / Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Thiruvananthapuram, Kerala">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Save Candidate to MySQL</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
