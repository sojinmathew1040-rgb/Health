<?php
/**
 * HealthJobsGlobal.com - Reusable Company Card Component
 * Requires $company array in scope
 */
if (!isset($company) || !is_array($company)) return;
$safeName = htmlspecialchars((string)($company['name'] ?? ''));
$compUrl = BASE_URL . '/company-details.php?id=' . (int)($company['id'] ?? 0);
?>
<div class="company-card">
  <div class="company-card-top">
    <div class="company-logo-avatar" style="width: 52px; height: 52px; font-size: 0.95rem; background: var(--color-primary);">
      <?php echo htmlspecialchars((string)($company['logo_text'] ?? '')); ?>
    </div>
    <?php echo renderBadge((string)($company['badge'] ?? ''), 'gold'); ?>
  </div>

  <h3 class="company-card-name">
    <a href="<?php echo $compUrl; ?>"><?php echo $safeName; ?></a>
  </h3>
  <div class="company-card-type"><?php echo htmlspecialchars((string)($company['type'] ?? '')); ?></div>

  <p class="company-card-overview">
    <?php echo htmlspecialchars((string)($company['overview'] ?? '')); ?>
  </p>

  <div class="company-card-meta">
    <div class="flex items-center gap-2" style="color: var(--color-text-secondary);">
      <?php echo getIcon('map-pin', '', 14); ?>
      <span><?php echo htmlspecialchars((string)($company['location'] ?? '')); ?></span>
    </div>
    <div style="font-weight: 700; color: var(--color-secondary);">
      <?php echo (int)($company['openings'] ?? 0); ?> Active Jobs
    </div>
  </div>

  <div style="margin-top: 16px;">
    <a href="<?php echo $compUrl; ?>" class="btn btn-outline btn-sm" style="width: 100%;">
      <span>View Organization Profile</span>
      <?php echo getIcon('chevron-right', '', 14); ?>
    </a>
  </div>
</div>
