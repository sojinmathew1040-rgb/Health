<?php
/**
 * Admin Panel - Clinical Jobs Management (CRUD)
 */
$pageTitle = "Clinical Jobs Directory — HealthJobsGlobal Admin";
$pageHeading = "Manage Clinical Job Postings";

require_once __DIR__ . '/includes/header.php';

// Handle Actions (Add, Edit, Delete, Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Add Job
    if ($action === 'create_job') {
        $title = trim($_POST['title'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $department = trim($_POST['department'] ?? 'Doctors & Clinical Specialists');
        $specialty = trim($_POST['specialty'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $salary = trim($_POST['salary'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $council_req = trim($_POST['council_req'] ?? 'Council Registered');
        $status = trim($_POST['status'] ?? 'Active');
        $urgent = isset($_POST['urgent']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $description = trim($_POST['description'] ?? '');

        if (!empty($title) && !empty($company)) {
            $stmt = $con->prepare("INSERT INTO `jobs` (`title`, `company`, `department`, `specialty`, `location`, `salary`, `experience`, `qualification`, `council_req`, `status`, `urgent`, `featured`, `description`, `posted_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("sssssssssiiss", $title, $company, $department, $specialty, $location, $salary, $experience, $qualification, $council_req, $status, $urgent, $featured, $description);
                $stmt->execute();
                $stmt->close();
                header('Location: jobs.php?success=' . urlencode("Job '{$title}' created successfully!"));
                exit;
            }
        }
    }

    // 2. Edit Job
    if ($action === 'update_job') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $salary = trim($_POST['salary'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $status = trim($_POST['status'] ?? 'Active');
        $urgent = isset($_POST['urgent']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $description = trim($_POST['description'] ?? '');

        if ($id > 0 && !empty($title)) {
            $stmt = $con->prepare("UPDATE `jobs` SET `title` = ?, `company` = ?, `department` = ?, `specialty` = ?, `location` = ?, `salary` = ?, `experience` = ?, `qualification` = ?, `status` = ?, `urgent` = ?, `featured` = ?, `description` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("sssssssssiisi", $title, $company, $department, $specialty, $location, $salary, $experience, $qualification, $status, $urgent, $featured, $description, $id);
                $stmt->execute();
                $stmt->close();
                header('Location: jobs.php?success=' . urlencode("Job updated successfully!"));
                exit;
            }
        }
    }

    // 3. Delete Job
    if ($action === 'delete_job') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $con->prepare("DELETE FROM `jobs` WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header('Location: jobs.php?success=' . urlencode("Job listing deleted."));
                exit;
            }
        }
    }

    // 4. Quick Status Toggle
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? 'Active');
        if ($id > 0) {
            $stmt = $con->prepare("UPDATE `jobs` SET `status` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("si", $newStatus, $id);
                $stmt->execute();
                $stmt->close();
                header('Location: jobs.php?success=' . urlencode("Status updated to {$newStatus}."));
                exit;
            }
        }
    }
}

// Search and Filter Handling
$filterDept = trim($_GET['department'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM `jobs` WHERE 1=1";
$params = [];
$types = "";

if (!empty($filterDept)) {
    $sql .= " AND `department` = ?";
    $params[] = $filterDept;
    $types .= "s";
}

if (!empty($filterStatus)) {
    $sql .= " AND `status` = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

if (!empty($searchQuery)) {
    $sql .= " AND (`title` LIKE ? OR `company` LIKE ? OR `specialty` LIKE ? OR `location` LIKE ?)";
    $likeQ = "%" . $searchQuery . "%";
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $types .= "ssss";
}

$sql .= " ORDER BY `id` DESC";

$jobs = [];
if (!empty($con) && !$con->connect_error) {
    if (!empty($params)) {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $jobs[] = $row;
            }
            $stmt->close();
        }
    } else {
        $res = $con->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $jobs[] = $row;
            }
        }
    }
}

// Single Job to Edit if requested via GET
$editingJob = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $con->prepare("SELECT * FROM `jobs` WHERE id = ?");
    if ($stmtEdit) {
        $stmtEdit->bind_param("i", $editId);
        $stmtEdit->execute();
        $resEdit = $stmtEdit->get_result();
        $editingJob = $resEdit->fetch_assoc();
        $stmtEdit->close();
    }
}

$quickActionBtn = '<button class="btn btn-primary btn-sm" data-modal-target="addJobModal">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  <span>Post New Job</span>
</button>';

require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Filter Toolbar -->
<div class="toolbar">
  <form method="GET" action="jobs.php" style="display: flex; gap: 12px; flex-wrap: wrap; flex: 1;">
    <div class="search-box">
      <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="q" placeholder="Search title, hospital, specialty..." value="<?php echo htmlspecialchars($searchQuery); ?>" id="tableSearchInput">
    </div>

    <div class="filter-group">
      <select name="department" class="select-filter" onchange="this.form.submit()">
        <option value="">All Departments</option>
        <option value="Doctors & Clinical Specialists" <?php echo ($filterDept === 'Doctors & Clinical Specialists') ? 'selected' : ''; ?>>Doctors & Clinical Specialists</option>
        <option value="Nursing Specialties" <?php echo ($filterDept === 'Nursing Specialties') ? 'selected' : ''; ?>>Nursing Specialties</option>
        <option value="Diagnostics, Pathology & Pharmacy" <?php echo ($filterDept === 'Diagnostics, Pathology & Pharmacy') ? 'selected' : ''; ?>>Diagnostics & Pharmacy</option>
        <option value="Allied Health & Paramedical" <?php echo ($filterDept === 'Allied Health & Paramedical') ? 'selected' : ''; ?>>Allied Health</option>
        <option value="Hospital Operations & Leadership" <?php echo ($filterDept === 'Hospital Operations & Leadership') ? 'selected' : ''; ?>>Operations & Admin</option>
      </select>

      <select name="status" class="select-filter" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="Active" <?php echo ($filterStatus === 'Active') ? 'selected' : ''; ?>>Active Only</option>
        <option value="Pending" <?php echo ($filterStatus === 'Pending') ? 'selected' : ''; ?>>Pending Review</option>
        <option value="Closed" <?php echo ($filterStatus === 'Closed') ? 'selected' : ''; ?>>Closed</option>
      </select>

      <?php if (!empty($searchQuery) || !empty($filterDept) || !empty($filterStatus)): ?>
        <a href="jobs.php" class="btn btn-secondary btn-sm">Clear Filters</a>
      <?php endif; ?>
    </div>
  </form>

  <div>
    <button class="btn btn-primary btn-sm" data-modal-target="addJobModal">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Job
    </button>
  </div>
</div>

<!-- Jobs Table Card -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Job Postings List (<?php echo count($jobs); ?> Records)</h3>
    <span style="font-size: 0.8rem; color: var(--text-muted);">Table: <code>healthjobs.jobs</code></span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Position & Hospital</th>
          <th>Department & Specialty</th>
          <th>Location</th>
          <th>Salary & Experience</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($jobs)): ?>
          <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No job records match the selected filter.</td></tr>
        <?php else: ?>
          <?php foreach ($jobs as $job): 
            $stCls = ($job['status'] === 'Active') ? 'badge-active' : (($job['status'] === 'Pending') ? 'badge-pending' : 'badge-closed');
          ?>
            <tr>
              <td><span style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">#<?php echo $job['id']; ?></span></td>
              <td>
                <span class="job-cell-title"><?php echo htmlspecialchars($job['title']); ?></span>
                <span class="job-cell-sub">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/></svg>
                  <?php echo htmlspecialchars($job['company']); ?>
                  <?php if (!empty($job['urgent'])): ?>
                    <span class="badge badge-urgent" style="font-size: 0.65rem; padding: 1px 6px;">Urgent</span>
                  <?php endif; ?>
                  <?php if (!empty($job['featured'])): ?>
                    <span class="badge badge-featured" style="font-size: 0.65rem; padding: 1px 6px;">Featured</span>
                  <?php endif; ?>
                </span>
              </td>
              <td>
                <div style="font-weight: 500;"><?php echo htmlspecialchars($job['specialty'] ?: 'General'); ?></div>
                <div style="font-size: 0.74rem; color: var(--text-muted);"><?php echo htmlspecialchars($job['department']); ?></div>
              </td>
              <td><?php echo htmlspecialchars($job['location']); ?></td>
              <td>
                <div style="font-size: 0.82rem; font-weight: 600; color: #0284C7;"><?php echo htmlspecialchars($job['salary']); ?></div>
                <div style="font-size: 0.74rem; color: var(--text-muted);"><?php echo htmlspecialchars($job['experience']); ?></div>
              </td>
              <td>
                <!-- Quick Toggle Form -->
                <form method="POST" action="jobs.php" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?php echo $job['id']; ?>">
                  <select name="new_status" onchange="this.form.submit()" style="padding: 3px 8px; border-radius: var(--radius-pill); font-size: 0.76rem; font-weight: 600; cursor: pointer; border: 1px solid var(--border-light); background: #fff;">
                    <option value="Active" <?php echo ($job['status'] === 'Active') ? 'selected' : ''; ?>>🟢 Active</option>
                    <option value="Pending" <?php echo ($job['status'] === 'Pending') ? 'selected' : ''; ?>>🟡 Pending</option>
                    <option value="Closed" <?php echo ($job['status'] === 'Closed') ? 'selected' : ''; ?>>🔴 Closed</option>
                  </select>
                </form>
              </td>
              <td>
                <div class="table-actions">
                  <a href="jobs.php?edit=<?php echo $job['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Job">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </a>
                  <a href="../job-details.php?id=<?php echo $job['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="View Public Page">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <!-- Delete Action -->
                  <form method="POST" action="jobs.php" onsubmit="return confirm('Are you sure you want to permanently delete this job listing?');" style="display:inline;">
                    <input type="hidden" name="action" value="delete_job">
                    <input type="hidden" name="id" value="<?php echo $job['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-rose);" title="Delete Job">
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

<!-- Modal: Add Job -->
<div class="modal-overlay" id="addJobModal">
  <div class="modal-card">
    <div class="modal-header">
      <h3>+ Post New Clinical Opening</h3>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="jobs.php">
      <input type="hidden" name="action" value="create_job">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Job Title / Clinical Role *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Senior Consultant – Interventional Cardiology" required>
          </div>

          <div class="form-group">
            <label class="form-label">Hospital / Network Name *</label>
            <input type="text" name="company" class="form-control" placeholder="e.g. Aster Medcity" required>
          </div>

          <div class="form-group">
            <label class="form-label">Clinical Department</label>
            <select name="department" class="form-control">
              <option value="Doctors & Clinical Specialists">Doctors & Clinical Specialists</option>
              <option value="Nursing Specialties">Nursing Specialties</option>
              <option value="Diagnostics, Pathology & Pharmacy">Diagnostics, Pathology & Pharmacy</option>
              <option value="Allied Health & Paramedical">Allied Health & Paramedical</option>
              <option value="Hospital Operations & Leadership">Hospital Operations & Leadership</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Specialty</label>
            <input type="text" name="specialty" class="form-control" placeholder="e.g. Cardiology, Critical Care">
          </div>

          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Kochi, Kerala, India">
          </div>

          <div class="form-group">
            <label class="form-label">Salary Package</label>
            <input type="text" name="salary" class="form-control" placeholder="e.g. ₹40,00,000 - ₹50,00,000 / year">
          </div>

          <div class="form-group">
            <label class="form-label">Experience Required</label>
            <input type="text" name="experience" class="form-control" placeholder="e.g. 5+ Years Post-MD/DNB">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Required Qualifications</label>
            <input type="text" name="qualification" class="form-control" placeholder="e.g. MBBS, MD (Med), DM (Cardiology)">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Medical Council / Regulatory Requirement</label>
            <input type="text" name="council_req" class="form-control" placeholder="e.g. Travancore-Cochin Medical Council / NMC Active">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Detailed Clinical Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Cath lab volume, outpatient expectations, emergency call rotations..."></textarea>
          </div>

          <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; font-weight: 600;">
              <input type="checkbox" name="urgent" value="1">
              Mark as Urgent Opening
            </label>
          </div>

          <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; font-weight: 600;">
              <input type="checkbox" name="featured" value="1" checked>
              Highlight as Featured Job
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Publish Job to MySQL</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Job (Triggered when $_GET['edit'] is present) -->
<?php if ($editingJob): ?>
<div class="modal-overlay active" id="editJobModal">
  <div class="modal-card">
    <div class="modal-header">
      <h3>Edit Job #<?php echo $editingJob['id']; ?></h3>
      <a href="jobs.php" class="modal-close">&times;</a>
    </div>
    <form method="POST" action="jobs.php">
      <input type="hidden" name="action" value="update_job">
      <input type="hidden" name="id" value="<?php echo $editingJob['id']; ?>">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Job Title *</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editingJob['title']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Hospital / Company *</label>
            <input type="text" name="company" class="form-control" value="<?php echo htmlspecialchars($editingJob['company']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department" class="form-control">
              <option value="Doctors & Clinical Specialists" <?php echo ($editingJob['department'] === 'Doctors & Clinical Specialists') ? 'selected' : ''; ?>>Doctors & Clinical Specialists</option>
              <option value="Nursing Specialties" <?php echo ($editingJob['department'] === 'Nursing Specialties') ? 'selected' : ''; ?>>Nursing Specialties</option>
              <option value="Diagnostics, Pathology & Pharmacy" <?php echo ($editingJob['department'] === 'Diagnostics, Pathology & Pharmacy') ? 'selected' : ''; ?>>Diagnostics, Pathology & Pharmacy</option>
              <option value="Allied Health & Paramedical" <?php echo ($editingJob['department'] === 'Allied Health & Paramedical') ? 'selected' : ''; ?>>Allied Health & Paramedical</option>
              <option value="Hospital Operations & Leadership" <?php echo ($editingJob['department'] === 'Hospital Operations & Leadership') ? 'selected' : ''; ?>>Hospital Operations & Leadership</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Specialty</label>
            <input type="text" name="specialty" class="form-control" value="<?php echo htmlspecialchars($editingJob['specialty']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($editingJob['location']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Salary</label>
            <input type="text" name="salary" class="form-control" value="<?php echo htmlspecialchars($editingJob['salary']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Experience</label>
            <input type="text" name="experience" class="form-control" value="<?php echo htmlspecialchars($editingJob['experience']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="Active" <?php echo ($editingJob['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
              <option value="Pending" <?php echo ($editingJob['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
              <option value="Closed" <?php echo ($editingJob['status'] === 'Closed') ? 'selected' : ''; ?>>Closed</option>
            </select>
          </div>

          <div class="form-group full-width">
            <label class="form-label">Required Qualifications</label>
            <input type="text" name="qualification" class="form-control" value="<?php echo htmlspecialchars($editingJob['qualification']); ?>">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editingJob['description']); ?></textarea>
          </div>

          <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
              <input type="checkbox" name="urgent" value="1" <?php echo (!empty($editingJob['urgent'])) ? 'checked' : ''; ?>>
              Urgent Opening
            </label>
          </div>

          <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
              <input type="checkbox" name="featured" value="1" <?php echo (!empty($editingJob['featured'])) ? 'checked' : ''; ?>>
              Featured Opening
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="jobs.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Job Listing</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
