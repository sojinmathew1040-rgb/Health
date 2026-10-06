<?php
/**
 * HealthJobsGlobal.com - Leading Healthcare Organizations Directory
 */
$pageTitle = "Leading Healthcare Organizations & Hospitals — HealthJobsGlobal";
$pageDescription = "Explore accredited quaternary care hospitals, specialty clinics, and diagnostic chains hiring medical talent.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 32px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a> / <span style="color: var(--color-primary); font-weight: 600;">Healthcare Organizations</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 2.2rem; margin-bottom: 4px;">Leading Healthcare Organizations</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Discover certified hospitals, academic medical centers, and healthcare groups worldwide.
        </p>
      </div>

      <div style="display: flex; gap: 8px; background: var(--color-bg); padding: 6px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); max-width: 380px; width: 100%;">
        <div style="display: flex; align-items: center; gap: 8px; padding-left: 10px; flex-grow: 1;">
          <?php echo getIcon('search', '', 18); ?>
          <input type="text" placeholder="Search hospital name or city..." style="width: 100%; font-size: 0.9rem;">
        </div>
        <button class="btn btn-primary btn-sm">Search</button>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-10); padding-bottom: var(--space-20);">
  <div class="companies-grid">
    <?php foreach ($featuredCompanies as $company): ?>
      <?php require __DIR__ . '/components/company-card.php'; ?>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
