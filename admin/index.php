<?php
/**
 * Admin Panel - Main Dashboard Overview
 * Route: http://localhost/Health/admin or http://localhost/Health/admin/index.php
 */
$pageTitle = "Medical Operations Dashboard — HealthJobsGlobal Admin";
$pageHeading = "Healthcare Operations Overview";

require_once __DIR__ . '/includes/header.php';

// Handle Add Job Form Submission from Dashboard Modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_add_job') {
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $department = trim($_POST['department'] ?? 'Doctors & Clinical Specialists');
    $specialty = trim($_POST['specialty'] ?? 'General Medicine');
    $location = trim($_POST['location'] ?? 'Kochi, Kerala');
    $salary = trim($_POST['salary'] ?? 'Competitive Standard');
    $experience = trim($_POST['experience'] ?? '3+ Years');
    $qualification = trim($_POST['qualification'] ?? 'MBBS / MD / B.Sc Nursing');
    $status = trim($_POST['status'] ?? 'Active');
    $urgent = isset($_POST['urgent']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $description = trim($_POST['description'] ?? '');

    if (!empty($title) && !empty($company)) {
        $stmt = $con->prepare("INSERT INTO `jobs` (`title`, `company`, `department`, `specialty`, `location`, `salary`, `experience`, `qualification`, `status`, `urgent`, `featured`, `description`, `posted_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("sssssssssiis", $title, $company, $department, $specialty, $location, $salary, $experience, $qualification, $status, $urgent, $featured, $description);
            $stmt->execute();
            $stmt->close();
            header('Location: index.php?success=' . urlencode("Job '{$title}' posted successfully to MySQL database!"));
            exit;
        }
    }
}

// Fetch Metrics from healthjobs
$totalJobs = 0;
$activeJobs = 0;
$urgentJobs = 0;
$totalHospitals = 0;
$totalCandidates = 0;
$totalApps = 0;
$shortlistedApps = 0;

if (!empty($con) && !$con->connect_error) {
    $res = $con->query("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN urgent=1 THEN 1 ELSE 0 END) as urgent
        FROM `jobs`");
    if ($res && $row = $res->fetch_assoc()) {
        $totalJobs = (int)($row['total'] ?? 0);
        $activeJobs = (int)($row['active'] ?? 0);
        $urgentJobs = (int)($row['urgent'] ?? 0);
    }

    $verifiedHospitals = 0;
    $pendingHospitals = 0;
    $resH = $con->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as verified_count,
        SUM(CASE WHEN verified = 0 OR verified IS NULL THEN 1 ELSE 0 END) as pending_count
        FROM `hospitals`");
    if ($resH && $rowH = $resH->fetch_assoc()) {
        $totalHospitals = (int)($rowH['total'] ?? 0);
        $verifiedHospitals = (int)($rowH['verified_count'] ?? 0);
        $pendingHospitals = (int)($rowH['pending_count'] ?? 0);
    }

    $resC = $con->query("SELECT COUNT(*) as c FROM `candidates`");
    if ($resC) { $totalCandidates = (int)$resC->fetch_assoc()['c']; }

    $resA = $con->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('Shortlisted', 'Interview Scheduled', 'Offered') THEN 1 ELSE 0 END) as shortlisted
        FROM `applications`");
    if ($resA && $rowA = $resA->fetch_assoc()) {
        $totalApps = (int)($rowA['total'] ?? 0);
        $shortlistedApps = (int)($rowA['shortlisted'] ?? 0);
    }

    // Fetch recent 6 jobs
    $recentJobs = [];
    $resJobs = $con->query("SELECT * FROM `jobs` ORDER BY id DESC LIMIT 6");
    if ($resJobs) {
        while ($r = $resJobs->fetch_assoc()) {
            $recentJobs[] = $r;
        }
    }

    // Fetch recent 5 applications
    $recentApps = [];
    $resRecentApps = $con->query("SELECT * FROM `applications` ORDER BY id DESC LIMIT 5");
    if ($resRecentApps) {
        while ($ra = $resRecentApps->fetch_assoc()) {
            $recentApps[] = $ra;
        }
    }

    // Fetch Departments with count
    $depts = [];
    $resDepts = $con->query("SELECT * FROM `departments` LIMIT 5");
    if ($resDepts) {
        while ($d = $resDepts->fetch_assoc()) {
            $depts[] = $d;
        }
    }
}

$quickActionBtn = '<button class="btn btn-primary btn-sm" data-modal-target="quickAddJobModal">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  <span>Post New Job</span>
</button>';

require_once __DIR__ . '/includes/topbar.php';
?>

<?php if ($pendingHospitals > 0): ?>
<!-- Pending Hospital Review Alert Banner -->
<div style="background: linear-gradient(135deg, #FEF3C7 0%, #FFFBEB 100%); border: 1.5px solid #F59E0B; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.12); flex-wrap: wrap; gap: 14px;">
  <div style="display: flex; align-items: center; gap: 14px;">
    <div style="width: 44px; height: 44px; border-radius: 10px; background: #D97706; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; box-shadow: 0 2px 6px rgba(217, 119, 6, 0.35);">
      ⏳
    </div>
    <div>
      <div style="font-weight: 700; color: #92400E; font-size: 0.98rem; display: flex; align-items: center; gap: 8px;">
        <span><?php echo $pendingHospitals; ?> Hospital / Employer Registration<?php echo $pendingHospitals > 1 ? 's' : ''; ?> Awaiting Admin Approval</span>
        <span class="badge" style="background: #FEE2E2; color: #991B1B; font-size: 0.72rem; padding: 2px 8px; border: 1px solid #FCA5A5;">Login Locked</span>
      </div>
      <div style="font-size: 0.85rem; color: #78350F; margin-top: 3px;">
        New employer accounts from the frontend cannot log in until approved by an administrator here.
      </div>
    </div>
  </div>
  <div>
    <a href="employers.php?filter=pending" class="btn btn-primary btn-sm" style="background: #D97706; border-color: #B45309; color: #fff; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(180, 83, 9, 0.35); padding: 8px 16px;">
      <span>Review & Approve Now (<?php echo $pendingHospitals; ?>)</span>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
  </div>
</div>
<?php endif; ?>

<!-- KPI Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-content">
      <div class="stat-label">Active Clinical Jobs</div>
      <div class="stat-value"><?php echo $activeJobs; ?></div>
      <div class="stat-sub">
        <span style="color: #10B981; font-weight: 600;">✓ Live</span> across all specialties
      </div>
    </div>
    <div class="stat-icon blue">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-content">
      <div class="stat-label">Verified Hospitals</div>
      <div class="stat-value"><?php echo $verifiedHospitals; ?></div>
      <div class="stat-sub">
        <?php if ($pendingHospitals > 0): ?>
          <a href="employers.php?filter=pending" style="color: #D97706; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
            <span>⚠️ <?php echo $pendingHospitals; ?> Pending Approval</span>
          </a>
        <?php else: ?>
          <span style="color: #0284C7; font-weight: 600;">NABH & JCI</span> Quaternary networks
        <?php endif; ?>
      </div>
    </div>
    <div class="stat-icon emerald">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-content">
      <div class="stat-label">Candidate Pool</div>
      <div class="stat-value"><?php echo $totalCandidates; ?></div>
      <div class="stat-sub">
        <span style="color: #7C3AED; font-weight: 600;">Council Verified</span> Specialists
      </div>
    </div>
    <div class="stat-icon purple">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-content">
      <div class="stat-label">Inbound Applications</div>
      <div class="stat-value"><?php echo $totalApps; ?></div>
      <div class="stat-sub">
        <span style="color: #D97706; font-weight: 600;"><?php echo $shortlistedApps; ?> Shortlisted</span> candidates
      </div>
    </div>
    <div class="stat-icon amber">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
  </div>
</div>

<!-- Quick Actions Banner -->
<div class="action-banner">
  <div>
    <h2>HealthJobsGlobal Command Console</h2>
    <p>Managing verified clinical openings, medical council compliance, and talent pipelines in real-time.</p>
  </div>
  <div class="banner-buttons">
    <button class="btn btn-primary" data-modal-target="quickAddJobModal">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      <span>Post Clinical Job</span>
    </button>
    <a href="employers.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.12); color:#fff; border-color: rgba(255,255,255,0.25);">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/></svg>
      Manage Hospitals
    </a>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 28px;">
  <!-- Recent Postings Card -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Latest Clinical Job Postings</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Synchronized live from MySQL database</p>
      </div>
      <a href="jobs.php" class="btn btn-secondary btn-sm">
        View All Jobs (<?php echo $totalJobs; ?>)
      </a>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Position & Hospital</th>
            <th>Department</th>
            <th>Location</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentJobs)): ?>
            <tr><td colspan="5" style="text-align:center; padding: 30px; color: var(--text-muted);">No job postings found. Click "Post New Job" to create one.</td></tr>
          <?php else: ?>
            <?php foreach ($recentJobs as $job): 
              $statusVal = !empty($job['status']) ? $job['status'] : 'Active';
              $statusCls = ($statusVal === 'Active') ? 'badge-active' : (($statusVal === 'Pending') ? 'badge-pending' : 'badge-closed');
            ?>
              <tr>
                <td>
                  <span class="job-cell-title"><?php echo htmlspecialchars($job['title']); ?></span>
                  <span class="job-cell-sub">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/></svg>
                    <?php echo htmlspecialchars($job['company']); ?>
                    <?php if (!empty($job['urgent'])): ?>
                      <span class="badge badge-urgent" style="font-size: 0.65rem; padding: 1px 6px;">Urgent</span>
                    <?php endif; ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($job['specialty'] ?? $job['department']); ?></td>
                <td><?php echo htmlspecialchars($job['location']); ?></td>
                <td>
                  <span class="badge <?php echo $statusCls; ?>"><?php echo htmlspecialchars($statusVal); ?></span>
                </td>
                <td>
                  <div class="table-actions">
                    <a href="jobs.php?edit=<?php echo $job['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Job">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </a>
                    <a href="../job-details.php?id=<?php echo $job['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="Preview Public Page">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Clinical Departments Sidebar Card -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Departments Overview</h3>
      <a href="departments.php" class="btn btn-secondary btn-sm">Manage</a>
    </div>
    <div class="card-body" style="padding: 16px 20px;">
      <div style="display: flex; flex-direction: column; gap: 14px;">
        <?php foreach ($depts as $dept): ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #F1F5F9;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="width: 32px; height: 32px; border-radius: var(--radius-md); background: #E0F2FE; color: var(--primary); display: flex; align-items: center; justify-content: center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/></svg>
              </div>
              <div>
                <div style="font-weight: 600; font-size: 0.86rem;"><?php echo htmlspecialchars($dept['name']); ?></div>
                <div style="font-size: 0.74rem; color: var(--text-muted);"><?php echo htmlspecialchars($dept['slug']); ?></div>
              </div>
            </div>
            <span class="badge badge-active" style="font-size: 0.75rem;"><?php echo $dept['openings']; ?>+ jobs</span>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-top: 18px; padding: 14px; background: #F8FAFC; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
        <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-main); margin-bottom: 4px;">Database Connection Status</div>
        <div style="font-size: 0.75rem; color: #166534; display: flex; align-items: center; gap: 6px;">
          <span class="db-dot" style="width:6px; height:6px;"></span>
          Host: <code>localhost</code> · DB: <code><?php echo htmlspecialchars($dbname ?? 'healthjobs'); ?></code>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Inbound Applications Card -->
<div class="card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Recent Inbound Candidate Applications</h3>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Direct applicant submissions from doctors, nurses and specialists</p>
    </div>
    <a href="applications.php" class="btn btn-secondary btn-sm">Open ATS Pipeline (<?php echo $totalApps; ?>)</a>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Applicant Candidate</th>
          <th>Applying Position</th>
          <th>Hospital</th>
          <th>Council Registration</th>
          <th>Current Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentApps)): ?>
          <tr><td colspan="6" style="text-align:center; padding: 30px; color: var(--text-muted);">No candidate applications recorded yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentApps as $app): 
            $appStatusCls = ($app['status'] === 'Shortlisted' || $app['status'] === 'Interview Scheduled') ? 'badge-shortlisted' : (($app['status'] === 'Offered') ? 'badge-verified' : 'badge-pending');
          ?>
            <tr>
              <td>
                <span class="job-cell-title"><?php echo htmlspecialchars($app['candidate_name']); ?></span>
                <span class="job-cell-sub"><?php echo htmlspecialchars($app['candidate_email']); ?></span>
              </td>
              <td><?php echo htmlspecialchars($app['job_title']); ?></td>
              <td><?php echo htmlspecialchars($app['hospital_name']); ?></td>
              <td>
                <span style="font-family: monospace; font-size: 0.8rem; background: #F1F5F9; padding: 2px 6px; border-radius: 4px;">
                  <?php echo htmlspecialchars($app['council_reg'] ?: 'Under Review'); ?>
                </span>
              </td>
              <td>
                <span class="badge <?php echo $appStatusCls; ?>"><?php echo htmlspecialchars($app['status']); ?></span>
              </td>
              <td>
                <a href="applications.php" class="btn btn-secondary btn-sm">Review ATS</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Quick Add Job Modal -->
<div class="modal-overlay" id="quickAddJobModal">
  <div class="modal-card">
    <div class="modal-header">
      <h3>+ Post New Clinical Opening</h3>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="index.php">
      <input type="hidden" name="action" value="quick_add_job">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Job Title / Clinical Designation *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Senior Consultant – Neonatology" required>
          </div>

          <div class="form-group">
            <label class="form-label">Hospital / Healthcare Facility *</label>
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
            <input type="text" name="location" class="form-control" placeholder="e.g. Kochi, Kerala, India" value="Kochi, Kerala, India">
          </div>

          <div class="form-group">
            <label class="form-label">Experience Required</label>
            <input type="text" name="experience" class="form-control" placeholder="e.g. 5+ Years Post-MD/DNB">
          </div>

          <div class="form-group">
            <label class="form-label">Salary Package</label>
            <input type="text" name="salary" class="form-control" placeholder="e.g. ₹35,00,000 - ₹45,00,000 / year">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Required Qualifications</label>
            <input type="text" name="qualification" class="form-control" placeholder="e.g. MBBS, MD, DM / DNB in Specialty">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Job Description & Responsibilities</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Outline clinical duties, OPD/IPD coverage, surgical responsibilities..."></textarea>
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
        <button type="submit" class="btn btn-primary">Save & Publish Job</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
