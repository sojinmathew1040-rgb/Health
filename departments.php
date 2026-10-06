<?php
/**
 * HealthJobsGlobal.com - Healthcare Departments Directory
 */
$pageTitle = "Healthcare Departments & Clinical Specialties — HealthJobsGlobal";
$pageDescription = "Browse medical and hospital departments: Clinical Medicine, Surgery, Nursing, Allied Health, Diagnostics, AYUSH, and Hospital Management.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 36px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a> / <span style="color: var(--color-primary); font-weight: 600;">Departments</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 2.2rem; margin-bottom: 4px;">Healthcare Departments & Specialties</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Detailed career pathways and active vacancies across all 6 accredited hospital verticals and 35+ clinical subspecialties.
        </p>
      </div>

      <div>
        <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-primary">
          <span>Search All Openings</span>
          <?php echo getIcon('arrow-right', '', 16); ?>
        </a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-10); padding-bottom: var(--space-20);">
  <div style="display: flex; flex-direction: column; gap: 48px;">
    
    <?php foreach ($healthcareDepartments as $deptKey => $dept): ?>
      <section id="<?php echo $deptKey; ?>" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm);">
        <div class="flex-between items-center" style="margin-bottom: 24px; border-bottom: 1px solid var(--color-border); padding-bottom: 16px; flex-wrap: wrap; gap: 12px;">
          <div class="flex items-center gap-4">
            <div class="dept-icon-box" style="width: 54px; height: 54px;">
              <?php echo getIcon($dept['icon'], '', 28); ?>
            </div>
            <div>
              <h2 style="font-size: 1.6rem; color: var(--color-primary);"><?php echo htmlspecialchars($dept['name']); ?></h2>
              <div style="font-size: 0.9rem; color: var(--color-secondary); font-weight: 600;"><?php echo htmlspecialchars($dept['count']); ?> Available</div>
            </div>
          </div>

          <a href="<?php echo BASE_URL; ?>/jobs.php?department=<?php echo $deptKey; ?>" class="btn btn-outline btn-sm">
            <span>Browse All <?php echo count($dept['specialties']); ?> Specialties</span>
            <?php echo getIcon('chevron-right', '', 14); ?>
          </a>
        </div>

        <!-- Specialties Grid -->
        <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 16px;">
          <?php foreach ($dept['specialties'] as $spec): ?>
            <a href="<?php echo BASE_URL; ?>/jobs.php?q=<?php echo urlencode($spec['name']); ?>" class="flex-between items-center" style="background: var(--color-bg); padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--color-border); transition: all var(--transition-fast);">
              <span style="font-size: 0.95rem; font-weight: 600; color: var(--color-primary);"><?php echo htmlspecialchars($spec['name']); ?></span>
              <span class="hjg-badge badge-default" style="font-size: 0.75rem;"><?php echo $spec['jobs']; ?> Jobs</span>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
