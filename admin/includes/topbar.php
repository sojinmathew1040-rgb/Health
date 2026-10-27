<?php
/**
 * Admin Panel - Top Bar Component
 */
?>
<div class="admin-main">
  <header class="admin-topbar">
    <div class="topbar-left">
      <button class="mobile-toggle" id="mobileSidebarToggle" aria-label="Toggle Navigation">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
      </button>

      <div>
        <div class="page-breadcrumb">
          <a href="index.php">Admin</a>
          <span>/</span>
          <span><?php echo htmlspecialchars($pageTitle ?? 'Overview'); ?></span>
        </div>
        <h1 class="page-title"><?php echo htmlspecialchars($pageHeading ?? 'Dashboard'); ?></h1>
      </div>
    </div>

    <div class="topbar-right">
      <a href="../index.php" target="_blank" class="btn btn-secondary btn-sm" title="Open Main Website">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
        <span class="btn-hide-mobile">Live Portal</span>
      </a>

      <?php if (!empty($quickActionBtn)): ?>
        <?php echo $quickActionBtn; ?>
      <?php endif; ?>
    </div>
  </header>

  <main class="admin-body">
    <?php if (!empty($_GET['success'])): ?>
      <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 14px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span><?php echo htmlspecialchars($_GET['success']); ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
      <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 14px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
        <span><?php echo htmlspecialchars($_GET['error']); ?></span>
      </div>
    <?php endif; ?>
