<?php
/**
 * Admin Panel - Hospitals & Healthcare Employers Management
 */
$pageTitle = "Hospitals & Employers — HealthJobsGlobal Admin";
$pageHeading = "Hospital Networks & Medical Centers";

require_once __DIR__ . '/includes/header.php';

// Handle Actions (Add, Edit, Delete, Toggle Verified)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add Hospital
    if ($action === 'create_hospital') {
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $badge = trim($_POST['badge'] ?? 'NABH & JCI Accredited');
        $type = trim($_POST['type'] ?? 'Quaternary Care Multi-Specialty Hospital');
        $location = trim($_POST['location'] ?? '');
        $email = trim($_POST['contact_email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $overview = trim($_POST['overview'] ?? '');
        $logo_text = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4));

        if (!empty($name)) {
            $stmt = $con->prepare("INSERT INTO `hospitals` (`name`, `slug`, `logo_text`, `badge`, `type`, `location`, `contact_email`, `phone`, `website`, `overview`, `verified`, `rating`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, '4.8')");
            if ($stmt) {
                $stmt->bind_param("ssssssssss", $name, $slug, $logo_text, $badge, $type, $location, $email, $phone, $website, $overview);
                $stmt->execute();
                $stmt->close();
                header('Location: employers.php?success=' . urlencode("Hospital '{$name}' registered successfully!"));
                exit;
            }
        }
    }

    // Approve Employer (Grant Login Access)
    if ($action === 'approve_employer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $con->prepare("UPDATE `hospitals` SET `verified` = 1 WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header('Location: employers.php?success=' . urlencode("Healthcare Employer approved successfully! Recruiter can now log in."));
                exit;
            }
        }
    }

    // Toggle Verified
    if ($action === 'toggle_verified') {
        $id = (int)($_POST['id'] ?? 0);
        $newVal = (int)($_POST['verified'] ?? 1);
        if ($id > 0) {
            $stmt = $con->prepare("UPDATE `hospitals` SET `verified` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $newVal, $id);
                $stmt->execute();
                $stmt->close();
                $msg = $newVal ? "Hospital approved and verified!" : "Hospital access set to unverified.";
                header('Location: employers.php?success=' . urlencode($msg));
                exit;
            }
        }
    }

    // Delete Hospital
    if ($action === 'delete_hospital') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $con->prepare("DELETE FROM `hospitals` WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header('Location: employers.php?success=' . urlencode("Hospital record deleted."));
                exit;
            }
        }
    }
}

// Filter and Statistics
$filter = $_GET['filter'] ?? 'all';
$whereClause = "";
if ($filter === 'pending') {
    $whereClause = "WHERE (h.verified = 0 OR h.verified IS NULL)";
} elseif ($filter === 'verified') {
    $whereClause = "WHERE h.verified = 1";
}

$totalHospCount = 0;
$pendingHospCount = 0;
$verifiedHospCount = 0;

if (!empty($con) && !$con->connect_error) {
    $resCounts = $con->query("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN verified = 0 OR verified IS NULL THEN 1 ELSE 0 END) as pending_c,
        SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as verified_c 
        FROM `hospitals`");
    if ($resCounts && $cRow = $resCounts->fetch_assoc()) {
        $totalHospCount = (int)$cRow['total'];
        $pendingHospCount = (int)($cRow['pending_c'] ?? 0);
        $verifiedHospCount = (int)($cRow['verified_c'] ?? 0);
    }
}

// Fetch Hospitals
$hospitals = [];
if (!empty($con) && !$con->connect_error) {
    $query = "SELECT h.*, (SELECT COUNT(*) FROM jobs WHERE hospital_id = h.id OR company = h.name) as live_openings 
              FROM `hospitals` h 
              {$whereClause} 
              ORDER BY (CASE WHEN h.verified = 0 OR h.verified IS NULL THEN 0 ELSE 1 END) ASC, h.id DESC";
    $res = $con->query($query);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $hospitals[] = $r;
        }
    }
}

$quickActionBtn = '<button class="btn btn-primary btn-sm" data-modal-target="addHospitalModal">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  <span>Add Hospital</span>
</button>';

require_once __DIR__ . '/includes/topbar.php';
?>

<div class="toolbar">
  <div class="search-box">
    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" placeholder="Search hospital name, location..." id="tableSearchInput">
  </div>
  <button class="btn btn-primary btn-sm" data-modal-target="addHospitalModal">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Register Healthcare Facility
  </button>
</div>

<!-- Filter Tabs (All / Pending Approval / Verified) -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
  <a href="employers.php?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>" style="border-radius: var(--radius-pill); padding: 7px 16px;">
    All Hospitals (<?php echo $totalHospCount; ?>)
  </a>
  <a href="employers.php?filter=pending" class="btn btn-sm" style="border-radius: var(--radius-pill); padding: 7px 16px; <?php echo $filter === 'pending' ? 'background: #D97706; color: #FFF; font-weight: 700;' : ($pendingHospCount > 0 ? 'background: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D; font-weight: 700;' : 'background: #F1F5F9; color: var(--text-muted);'); ?>">
    ⏳ Pending Admin Approval (<?php echo $pendingHospCount; ?>)
  </a>
  <a href="employers.php?filter=verified" class="btn btn-sm <?php echo $filter === 'verified' ? 'btn-primary' : 'btn-secondary'; ?>" style="border-radius: var(--radius-pill); padding: 7px 16px;">
    ✓ Verified Partners (<?php echo $verifiedHospCount; ?>)
  </a>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Hospital Networks & Medical Centers (<?php echo count($hospitals); ?> Facilities)</h3>
    <span style="font-size: 0.8rem; color: var(--text-muted);">Table: <code>healthjobs.hospitals</code></span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Hospital Name</th>
          <th>Accreditation & Type</th>
          <th>Location</th>
          <th>Contact & Website</th>
          <th>Openings</th>
          <th>Verification</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($hospitals)): ?>
          <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No hospitals match this filter.</td></tr>
        <?php else: ?>
          <?php foreach ($hospitals as $hosp): 
            $isPending = empty($hosp['verified']) || $hosp['verified'] == 0;
          ?>
            <tr style="<?php echo $isPending ? 'background: #FFFDF5;' : ''; ?>">
              <td>
                <div class="table-hospital-cell">
                  <div class="hospital-avatar" style="<?php echo $isPending ? 'background: #D97706;' : ''; ?>">
                    <?php echo htmlspecialchars($hosp['logo_text'] ?: substr($hosp['name'], 0, 3)); ?>
                  </div>
                  <div>
                    <span class="job-cell-title">
                      <?php echo htmlspecialchars($hosp['name']); ?>
                      <?php if ($isPending): ?>
                        <span style="font-size: 0.7rem; background: #FEF3C7; color: #92400E; padding: 2px 6px; border-radius: 4px; font-weight: 700; margin-left: 6px;">NEW REGISTRATION</span>
                      <?php endif; ?>
                    </span>
                    <span class="job-cell-sub">
                      <?php echo !empty($hosp['admin_name']) ? ('Admin: ' . htmlspecialchars($hosp['admin_name'])) : ('★ ' . htmlspecialchars($hosp['rating'] ?? '4.8') . ' Rating'); ?>
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-size: 0.82rem; font-weight: 600; color: #0284C7;"><?php echo htmlspecialchars($hosp['badge']); ?></div>
                <div style="font-size: 0.74rem; color: var(--text-muted);"><?php echo htmlspecialchars($hosp['type']); ?></div>
              </td>
              <td><?php echo htmlspecialchars($hosp['location']); ?></td>
              <td>
                <div style="font-size: 0.82rem;"><?php echo htmlspecialchars($hosp['contact_email'] ?: ($hosp['admin_email'] ?? 'N/A')); ?></div>
                <?php if (!empty($hosp['website'])): ?>
                  <a href="<?php echo htmlspecialchars($hosp['website']); ?>" target="_blank" style="font-size: 0.74rem; color: var(--primary); text-decoration: underline;">
                    Visit Website &nearr;
                  </a>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-active" style="font-size: 0.78rem;">
                  <?php echo $hosp['live_openings'] ?: $hosp['openings']; ?> Active Jobs
                </span>
              </td>
              <td>
                <?php if ($isPending): ?>
                  <span class="badge" style="background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; font-weight: 700;">
                    ⏳ Pending Approval
                  </span>
                <?php else: ?>
                  <form method="POST" action="employers.php" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_verified">
                    <input type="hidden" name="id" value="<?php echo $hosp['id']; ?>">
                    <input type="hidden" name="verified" value="0">
                    <button type="submit" class="badge badge-verified" style="border:none; cursor:pointer;" title="Click to unverify">
                      ✓ Verified Partner
                    </button>
                  </form>
                <?php endif; ?>
              </td>
              <td>
                <div class="table-actions">
                  <?php if ($isPending): ?>
                    <form method="POST" action="employers.php" style="display:inline;">
                      <input type="hidden" name="action" value="approve_employer">
                      <input type="hidden" name="id" value="<?php echo $hosp['id']; ?>">
                      <button type="submit" class="btn btn-primary btn-sm" style="background: #059669; border-color: #047857; font-size: 0.78rem; padding: 5px 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" title="Approve and grant login access">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Approve & Grant Login</span>
                      </button>
                    </form>
                  <?php endif; ?>

                  <a href="../company-details.php?slug=<?php echo htmlspecialchars($hosp['slug']); ?>" target="_blank" class="btn btn-secondary btn-sm" title="View Public Page">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <form method="POST" action="employers.php" onsubmit="return confirm('Delete this hospital record?');" style="display:inline;">
                    <input type="hidden" name="action" value="delete_hospital">
                    <input type="hidden" name="id" value="<?php echo $hosp['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-rose);" title="Delete Hospital">
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

<!-- Modal: Add Hospital -->
<div class="modal-overlay" id="addHospitalModal">
  <div class="modal-card">
    <div class="modal-header">
      <h3>+ Register Healthcare Facility / Hospital</h3>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="employers.php">
      <input type="hidden" name="action" value="create_hospital">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Hospital / Healthcare Organization Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Lisie Heart Institute" required>
          </div>

          <div class="form-group">
            <label class="form-label">Accreditation Badge</label>
            <input type="text" name="badge" class="form-control" placeholder="e.g. JCI & NABH Accredited" value="NABH & NABL Accredited">
          </div>

          <div class="form-group">
            <label class="form-label">Facility Classification</label>
            <input type="text" name="type" class="form-control" placeholder="e.g. Quaternary Care Teaching Hospital" value="Quaternary Care Multi-Specialty Hospital">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Campus Location(s)</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Kochi, Kerala, India" required>
          </div>

          <div class="form-group">
            <label class="form-label">HR / Careers Email</label>
            <input type="email" name="contact_email" class="form-control" placeholder="careers@hospital.com">
          </div>

          <div class="form-group">
            <label class="form-label">Official Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="+91 484 285 0000">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Official Website URL</label>
            <input type="url" name="website" class="form-control" placeholder="https://www.hospital.com">
          </div>

          <div class="form-group full-width">
            <label class="form-label">Hospital Overview & Specialties</label>
            <textarea name="overview" class="form-control" rows="3" placeholder="Brief clinical background, bed capacity, robotic surgery centers, organ transplant excellence..."></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Save to MySQL</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
