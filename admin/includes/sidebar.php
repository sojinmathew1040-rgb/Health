<?php
/**
 * Admin Panel - Apple Level Pro Sidebar Navigation Component
 */
$jobCount = 0;
$hospCount = 0;
$pendingHospCount = 0;
$verifiedHospCount = 0;

if (!empty($con) && !$con->connect_error) {
    $resJ = $con->query("SELECT COUNT(*) as c FROM `jobs`");
    if ($resJ) { $jobCount = (int)$resJ->fetch_assoc()['c']; }

    $resH = $con->query("SELECT 
        COUNT(*) as c, 
        SUM(CASE WHEN verified = 0 OR verified IS NULL THEN 1 ELSE 0 END) as p,
        SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as v 
        FROM `hospitals`");
    if ($resH && $hRow = $resH->fetch_assoc()) { 
        $hospCount = (int)$hRow['c']; 
        $pendingHospCount = (int)($hRow['p'] ?? 0);
        $verifiedHospCount = (int)($hRow['v'] ?? 0);
    }
}
?>
<!-- Apple Level Pro Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <a href="index.php" class="brand-badge">
      <div class="brand-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M2 12h20"/>
          <circle cx="12" cy="12" r="9" stroke-width="1.8"/>
        </svg>
      </div>
      <div class="brand-text">
        HealthJobs<span>Admin</span>
        <span class="brand-tag">PRO</span>
      </div>
    </a>
  </div>

  <div class="sidebar-content">
    <div>
      <div class="nav-section-title">Core Operations</div>
      <ul class="sidebar-nav">
        <li class="nav-item <?php echo ($currentAdminPage === 'index.php') ? 'active' : ''; ?>">
          <a href="index.php">
            <span class="nav-icon">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            </span>
            <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item <?php echo ($currentAdminPage === 'jobs.php') ? 'active' : ''; ?>">
          <a href="jobs.php">
            <span class="nav-icon">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </span>
            <span>Clinical Jobs</span>
            <span class="nav-count"><?php echo $jobCount; ?></span>
          </a>
        </li>
        <li class="nav-item <?php echo ($currentAdminPage === 'employer-verifications.php') ? 'active' : ''; ?>">
          <a href="employer-verifications.php">
            <span class="nav-icon">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
            </span>
            <span>Employer Verifications</span>
            <?php if ($pendingHospCount > 0): ?>
              <span class="nav-count nav-count-pending" title="<?php echo $pendingHospCount; ?> awaiting admin review">
                <span class="nav-pulse-dot"></span>
                <span><?php echo $pendingHospCount; ?> pending</span>
              </span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item <?php echo ($currentAdminPage === 'employers.php') ? 'active' : ''; ?>">
          <a href="employers.php">
            <span class="nav-icon">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
            </span>
            <span>Hospitals & Networks</span>
            <span class="nav-count"><?php echo $verifiedHospCount; ?></span>
          </a>
        </li>
      </ul>
    </div>

    <div>
      <div class="nav-section-title">Clinical Governance</div>
      <ul class="sidebar-nav">
        <li class="nav-item <?php echo ($currentAdminPage === 'departments.php') ? 'active' : ''; ?>">
          <a href="departments.php">
            <span class="nav-icon">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
            </span>
            <span>Clinical Departments</span>
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
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
      </a>
    </div>
  </div>
</aside>
