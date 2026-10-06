<?php
/**
 * HealthJobsGlobal.com - Reusable Department Card Component
 * Requires $deptKey, $dept in scope
 */
if (!isset($dept) || !isset($deptKey)) return;
$deptUrl = BASE_URL . '/departments.php#' . $deptKey;
?>
<div class="dept-card dept-tab-pane" data-category="<?php echo $deptKey; ?>">
  <div class="dept-card-top">
    <div class="dept-icon-box">
      <?php echo getIcon($dept['icon'], '', 24); ?>
    </div>
    <span class="dept-openings-count"><?php echo htmlspecialchars($dept['count']); ?></span>
  </div>

  <h3 class="dept-card-title"><?php echo htmlspecialchars($dept['name']); ?></h3>

  <div class="dept-specialties-list">
    <?php 
    $previewSpecs = array_slice($dept['specialties'], 0, 4);
    foreach ($previewSpecs as $spec): 
    ?>
      <span class="dept-specialty-chip"><?php echo htmlspecialchars($spec['name']); ?></span>
    <?php endforeach; ?>
    <?php if (count($dept['specialties']) > 4): ?>
      <span class="dept-specialty-chip" style="color: var(--color-secondary); font-weight: 600;">+<?php echo count($dept['specialties']) - 4; ?> More</span>
    <?php endif; ?>
  </div>

  <div class="dept-card-footer">
    <a href="<?php echo $deptUrl; ?>" class="flex items-center gap-2" style="width: 100%; justify-content: space-between;">
      <span>Explore Department Jobs</span>
      <?php echo getIcon('arrow-right', '', 16); ?>
    </a>
  </div>
</div>
