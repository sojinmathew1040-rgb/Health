<?php
/**
 * HealthJobsGlobal.com - Leading Healthcare Organizations Directory
 */
$pageTitle = "Leading Healthcare Organizations & Hospitals — HealthJobsGlobal";
$pageDescription = "Explore accredited quaternary care hospitals, specialty clinics, and diagnostic chains hiring medical talent.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Employers only need their own recruitment dashboard, not external hospital directories
if (isLoggedIn() && ((getLoggedInUser()['role'] ?? '') === 'employer')) {
    header("Location: " . BASE_URL . "/employer-dashboard.php");
    exit;
}
$searchComp = trim($_GET['q'] ?? '');
$displayCompanies = $featuredCompanies;
if (!empty($searchComp)) {
    $qLower = strtolower($searchComp);
    $displayCompanies = array_filter($featuredCompanies, function($c) use ($qLower) {
        return stripos((string)($c['name'] ?? ''), $qLower) !== false 
            || stripos((string)($c['location'] ?? ''), $qLower) !== false
            || stripos((string)($c['type'] ?? ''), $qLower) !== false;
    });
}

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

      <form method="GET" action="companies.php" style="display: flex; gap: 8px; background: var(--color-bg); padding: 6px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); max-width: 380px; width: 100%;">
        <div style="display: flex; align-items: center; gap: 8px; padding-left: 10px; flex-grow: 1;">
          <?php echo getIcon('search', '', 18); ?>
          <input type="text" name="q" value="<?php echo htmlspecialchars($searchComp); ?>" placeholder="Search hospital name or city..." style="width: 100%; font-size: 0.9rem; border: none; background: transparent; outline: none;">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
      </form>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-10); padding-bottom: var(--space-20);">
  <?php if (empty($displayCompanies)): ?>
    <div style="text-align: center; padding: 64px 24px; background: #FFFFFF; border-radius: var(--radius-xl); border: 1px dashed var(--color-border);">
      <div style="font-size: 3rem; margin-bottom: 12px;">🏥</div>
      <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 8px;">No Healthcare Organizations Found</h3>
      <p style="color: var(--color-text-secondary); max-width: 480px; margin: 0 auto 20px auto;">
        <?php echo !empty($searchComp) ? 'No verified hospitals matched your search query "' . htmlspecialchars($searchComp) . '".' : 'There are currently no active verified healthcare organizations listed.'; ?>
      </p>
      <?php if (!empty($searchComp)): ?>
        <a href="companies.php" class="btn btn-secondary">Clear Search Filter</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="companies-grid">
      <?php foreach ($displayCompanies as $company): ?>
        <?php require __DIR__ . '/components/company-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
