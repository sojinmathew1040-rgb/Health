<?php
/**
 * HealthJobsGlobal.com - Company / Hospital Profile
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$companyId = intval($_GET['id'] ?? 1);
$company = getCompanyById($companyId);

$pageTitle = $company['name'] . " — Healthcare Profile & Jobs | HealthJobsGlobal";
$pageDescription = "Explore clinical and administrative career opportunities at " . $company['name'] . " in " . $company['location'];

// Filter jobs for this company
$companyJobs = array_filter($featuredJobs, function($j) use ($company) {
    return $j['company_id'] === $company['id'];
});
if (empty($companyJobs)) {
    $companyJobs = array_slice($featuredJobs, 0, 3);
}

require_once __DIR__ . '/components/header.php';
?>

<!-- Hospital Header Banner -->
<div class="hospital-profile-banner">
  <div class="container">
    
    <!-- Breadcrumbs -->
    <div class="hospital-breadcrumbs">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
      <span class="bc-sep">/</span>
      <a href="<?php echo BASE_URL; ?>/companies.php">Hospitals</a>
      <span class="bc-sep">/</span>
      <span class="bc-current"><?php echo htmlspecialchars($company['name']); ?></span>
    </div>

    <!-- Header Grid: Logo Emblem + Main Info + CTA Button -->
    <div class="hospital-header-layout">
      
      <!-- Hospital Emblem / Logo Box -->
      <div class="hospital-emblem-card">
        <div class="emblem-inner">
          <span class="emblem-icon">
            <?php echo getIcon('building', '', 22); ?>
          </span>
          <span class="emblem-text"><?php echo htmlspecialchars($company['logo_text']); ?></span>
        </div>
        <div class="emblem-verified-tick" title="Accredited Healthcare Provider">
          <?php echo getIcon('check', '', 12); ?>
        </div>
      </div>

      <!-- Main Hospital Information -->
      <div class="hospital-info-column">
        <div class="hospital-title-row">
          <h1 class="hospital-name"><?php echo htmlspecialchars($company['name']); ?></h1>
          <span class="hospital-badge-gold">
            <?php echo getIcon('shield-check', '', 14); ?>
            <span><?php echo htmlspecialchars($company['badge']); ?></span>
          </span>
        </div>

        <div class="hospital-subtitle">
          <?php echo htmlspecialchars($company['type']); ?>
        </div>

        <div class="hospital-meta-row">
          <div class="hospital-meta-item">
            <?php echo getIcon('map-pin', '', 15); ?>
            <span><?php echo htmlspecialchars($company['location']); ?></span>
          </div>
          <span class="meta-dot">·</span>
          <div class="hospital-meta-item meta-rating">
            <span class="star-icon">★</span>
            <strong><?php echo $company['rating']; ?></strong>
            <span class="meta-sub">Provider Rating</span>
          </div>
          <span class="meta-dot">·</span>
          <div class="hospital-meta-item meta-openings">
            <?php echo getIcon('briefcase', '', 15); ?>
            <strong><?php echo $company['openings']; ?> Active Vacancies</strong>
          </div>
        </div>
      </div>

      <!-- CTA Action Button (Rigidly right-aligned and vertically centered) -->
      <div class="hospital-action-column">
        <a href="#active-jobs" class="btn btn-primary btn-lg hospital-cta-btn">
          <span>View Open Positions</span>
          <?php echo getIcon('arrow-right', '', 16); ?>
        </a>
      </div>

    </div>

  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr 340px; gap: var(--space-8); align-items: start;">
    
    <!-- Left Main Column: Hospital Overview & Jobs -->
    <main>
      
      <!-- Overview Box -->
      <section style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 32px; margin-bottom: 32px;">
        <h3 style="font-size: 1.4rem; color: var(--color-primary); margin-bottom: 12px;">Institutional Profile</h3>
        <p style="color: var(--color-text-secondary); line-height: 1.7; font-size: 1rem;">
          <?php echo htmlspecialchars($company['overview']); ?>
        </p>

        <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 24px;">
          <div style="background: var(--color-bg); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Capacity</div>
            <div style="font-size: 1.2rem; font-weight: 800; color: var(--color-primary); margin-top: 4px;">670+ Beds</div>
            <div style="font-size: 0.8rem; color: var(--color-text-secondary);">Quaternary Multi-Specialty</div>
          </div>
          <div style="background: var(--color-bg); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Critical Care</div>
            <div style="font-size: 1.2rem; font-weight: 800; color: var(--color-primary); margin-top: 4px;">120 ICU Beds</div>
            <div style="font-size: 0.8rem; color: var(--color-text-secondary);">Level-3 Specialized Units</div>
          </div>
          <div style="background: var(--color-bg); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Surgical Suites</div>
            <div style="font-size: 1.2rem; font-weight: 800; color: var(--color-primary); margin-top: 4px;">18 OTs & Cath</div>
            <div style="font-size: 0.8rem; color: var(--color-text-secondary);">Robotic Da Vinci & Hybrid</div>
          </div>
        </div>
      </section>

      <!-- Active Vacancies at this Organization -->
      <section id="active-jobs">
        <div class="flex-between items-center" style="margin-bottom: 20px;">
          <div>
            <h3 style="font-size: 1.4rem; color: var(--color-primary);">Open Positions at <?php echo htmlspecialchars($company['name']); ?></h3>
            <p style="color: var(--color-text-secondary); font-size: 0.9rem;">Direct applications to hospital recruitment committee.</p>
          </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr; gap: 16px;">
          <?php foreach ($companyJobs as $job): ?>
            <?php require __DIR__ . '/components/job-card.php'; ?>
          <?php endforeach; ?>
        </div>
      </section>

    </main>

    <!-- Right Sidebar -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 16px;">Hospital Accreditations</h4>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <div class="flex items-center gap-3">
            <span class="hjg-badge badge-verified"><?php echo getIcon('check', '', 14); ?> JCI USA</span>
            <span style="font-size: 0.85rem; color: var(--color-text-secondary);">Joint Commission International</span>
          </div>
          <div class="flex items-center gap-3">
            <span class="hjg-badge badge-verified"><?php echo getIcon('check', '', 14); ?> NABH</span>
            <span style="font-size: 0.85rem; color: var(--color-text-secondary);">National Hospital Board</span>
          </div>
          <div class="flex items-center gap-3">
            <span class="hjg-badge badge-verified"><?php echo getIcon('check', '', 14); ?> NABL</span>
            <span style="font-size: 0.85rem; color: var(--color-text-secondary);">Diagnostic & Clinical Lab</span>
          </div>
        </div>
      </div>

      <!-- Recruiter Connect -->
      <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 8px;">HR Recruitment Cell</h4>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary); line-height: 1.5; margin-bottom: 16px;">
          Verified medical talent with active council registrations may submit direct expressions of interest to the Medical Superintendent.
        </p>
        <button class="btn btn-primary btn-sm" style="width: 100%;" onclick="openApplyModal('', 'General Clinical Expression of Interest', '<?php echo addslashes($company['name']); ?>')">
          Submit General Bio-Data
        </button>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
