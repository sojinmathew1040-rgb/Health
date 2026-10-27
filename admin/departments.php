<?php
/**
 * Admin Panel - Clinical Departments Management
 */
$pageTitle = "Clinical Departments — HealthJobsGlobal Admin";
$pageHeading = "Healthcare Specialization Departments";

require_once __DIR__ . '/includes/header.php';

// Handle Actions (Add, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_dept') {
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $openings = (int)($_POST['openings'] ?? 0);
        $desc = trim($_POST['description'] ?? '');

        if (!empty($name)) {
            $stmt = $con->prepare("INSERT INTO `departments` (`name`, `slug`, `openings`, `description`) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssis", $name, $slug, $openings, $desc);
                $stmt->execute();
                $stmt->close();
                header('Location: departments.php?success=' . urlencode("Department '{$name}' created!"));
                exit;
            }
        }
    }

    if ($action === 'delete_dept') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $con->prepare("DELETE FROM `departments` WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header('Location: departments.php?success=' . urlencode("Department removed."));
                exit;
            }
        }
    }
}

// Fetch Departments
$departments = [];
if (!empty($con) && !$con->connect_error) {
    $res = $con->query("SELECT d.*, (SELECT COUNT(*) FROM jobs WHERE department = d.name) as live_jobs FROM `departments` d ORDER BY id ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $departments[] = $r;
        }
    }
}

$quickActionBtn = '<button class="btn btn-primary btn-sm" data-modal-target="addDeptModal">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  <span>Add Department</span>
</button>';

require_once __DIR__ . '/includes/topbar.php';
?>

<div class="toolbar">
  <div class="search-box">
    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" placeholder="Search departments..." id="tableSearchInput">
  </div>
  <button class="btn btn-primary btn-sm" data-modal-target="addDeptModal">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Department
  </button>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Configured Healthcare Departments</h3>
    <span style="font-size: 0.8rem; color: var(--text-muted);">Table: <code>healthjobs.departments</code></span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Department Name</th>
          <th>URL Slug</th>
          <th>Description</th>
          <th>Live Postings</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($departments as $dept): ?>
          <tr>
            <td>
              <div style="font-weight: 600; color: var(--text-main); font-size: 0.92rem;"><?php echo htmlspecialchars($dept['name']); ?></div>
            </td>
            <td><code><?php echo htmlspecialchars($dept['slug']); ?></code></td>
            <td style="max-width: 320px; color: var(--text-muted); font-size: 0.82rem;"><?php echo htmlspecialchars($dept['description']); ?></td>
            <td>
              <span class="badge badge-active"><?php echo $dept['live_jobs']; ?> Active Jobs</span>
            </td>
            <td>
              <form method="POST" action="departments.php" onsubmit="return confirm('Delete department?');" style="display:inline;">
                <input type="hidden" name="action" value="delete_dept">
                <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-rose);">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Department -->
<div class="modal-overlay" id="addDeptModal">
  <div class="modal-card">
    <div class="modal-header">
      <h3>+ Add Healthcare Department</h3>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="departments.php">
      <input type="hidden" name="action" value="create_dept">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Department Title *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. AYUSH & Integrative Medicine" required>
          </div>
          <div class="form-group full-width">
            <label class="form-label">Estimated Openings Count</label>
            <input type="number" name="openings" class="form-control" value="50">
          </div>
          <div class="form-group full-width">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Scope of medical faculties in this department..."></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Save Department</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
