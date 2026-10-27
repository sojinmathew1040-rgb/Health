<?php
/**
 * Admin Panel - Sidebar Navigation Component
 */
$jobCount = 0;
$candCount = 0;
$appCount = 0;
$hospCount = 0;

if (!empty($con) && !$con->connect_error) {
    $resJ = $con->query("SELECT COUNT(*) as c FROM `jobs`");
    if ($resJ) { $jobCount = $resJ->fetch_assoc()['c']; }

    $resC = $con->query("SELECT COUNT(*) as c FROM `candidates`");
    if ($resC) { $candCount = $resC->fetch_assoc()['c']; }

    $resA = $con->query("SELECT COUNT(*) as c FROM `applications`");
    if ($resA) { $appCount = $resA->fetch_assoc()['c']; }

    $resH = $con->query("SELECT COUNT(*) as c FROM `hospitals`");
    if ($resH) { $hospCount = $resH->fetch_assoc()['c']; }
}
?>
<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <a href="index.php" class="brand-badge">
      <div class="brand-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M2 12h20"/>
          <circle cx="12" cy="12" r="9" stroke-width="1.8"/>
        </svg>
      </div>
      <div class="brand-text">
        HealthJobs<span>Admin</span>
        <span class="brand-tag">v3.5</span>
      </div>
    </a>
  </div>

  <div class="sidebar-content">
    <div>
      <div class="nav-section-title">Core Management</div>
      <ul class="sidebar-nav">
        <li class="nav-item <?php echo ($currentAdminPage === 'index.php') ? 'active' : ''; ?>">
          <a href="index.php">
            <span class="nav-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            </span>
            <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item <?php echo ($currentAdminPage === 'jobs.php') ? 'active' : ''; ?>">
          <a href="jobs.php">
            <span class="nav-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </span>
            <span>Clinical Jobs</span>
            <span class="nav-count"><?php echo $jobCount; ?></span>
          </a>
        </li>
        <li class="nav-item <?php echo ($currentAdminPage === 'employers.php') ? 'active' : ''; ?>">
          <a href="employers.php">
            <span class="nav-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
            </span>
            <span>Hospitals & Networks</span>
            <span class="nav-count"><?php echo $hospCount; ?></span>
          </a>
        </li>
        <li class="nav-item <?php echo ($currentAdminPage === 'candidates.php') ? 'active' : ''; ?>">
          <a href="candidates.php">
            <span class="nav-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <span>Candidates Directory</span>
            <span class="nav-count"><?php echo $candCount; ?></span>
          </a>
        </li>
        <li class="nav-item <?php echo ($currentAdminPage === 'applications.php') ? 'active' : ''; ?>">
          <a href="applications.php">
            <span class="nav-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
            </span>
            <span>Inbound Applications</span>
            <span class="nav-count"><?php echo $appCount; ?></span>
          </a>
        </li>
      </ul>
    </div>

    <div>
      <div class="nav-section-title">Configuration</div>
      <ul class="sidebar-nav">
        <li class="nav-item <?php echo ($currentAdminPage === 'departments.php') ? 'active' : ''; ?>">
          <a href="departments.php">
            <span class="nav-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
            </span>
            <span>Clinical Departments</span>
          </a>
        </li>
        <li class="nav-item <?php echo ($currentAdminPage === 'settings.php') ? 'active' : ''; ?>">
          <a href="settings.php">
            <span class="nav-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </span>
            <span>Database & System</span>
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="sidebar-footer">
    <div class="db-pill">
      <div class="db-dot"></div>
      <span>MySQL: <strong>healthjobs</strong></span>
    </div>

    <div class="admin-profile-card">
      <div class="admin-avatar">
        <?php echo strtoupper(substr($adminUser['name'] ?? 'AD', 0, 2)); ?>
      </div>
      <div class="admin-info">
        <div class="admin-name"><?php echo htmlspecialchars($adminUser['name'] ?? 'Admin'); ?></div>
        <div class="admin-role"><?php echo htmlspecialchars($adminUser['role'] ?? 'Super Admin'); ?></div>
      </div>
      <a href="logout.php" class="admin-logout-btn" title="Sign Out">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
      </a>
    </div>
  </div>
</aside>
