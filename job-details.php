<?php
/**
 * HealthJobsGlobal.com - Job Details Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$jobId = intval($_GET['id'] ?? 101);
$job = getJobById($jobId);
$company = getCompanyById($job['company_id'] ?? 1);

$pageTitle = $job['title'] . " — " . $job['company'] . " | HealthJobsGlobal";
$pageDescription = "Apply for " . $job['title'] . " at " . $job['company'] . " in " . $job['location'] . ". " . $job['salary'];

require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 24px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 12px;">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a> / 
      <a href="<?php echo BASE_URL; ?>/jobs.php">Jobs</a> / 
      <span style="color: var(--color-primary); font-weight: 600;"><?php echo htmlspecialchars($job['title']); ?></span>
    </div>

    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 20px;">
      <div class="flex items-center gap-4">
        <div class="company-logo-avatar" style="width: 64px; height: 64px; font-size: 1.1rem; border-radius: var(--radius-lg);">
          <?php echo strtoupper(substr($job['company'], 0, 2)); ?>
        </div>
        <div>
          <div class="flex items-center gap-2" style="margin-bottom: 4px;">
            <span style="font-size: 1.1rem; font-weight: 700; color: var(--color-secondary);"><?php echo htmlspecialchars($job['company']); ?></span>
            <span class="hjg-badge badge-verified"><?php echo getIcon('shield-check', '', 14); ?> NABH / JCI Accredited</span>
          </div>
          <h1 style="font-size: 2rem; color: var(--color-primary); line-height: 1.2;"><?php echo htmlspecialchars($job['title']); ?></h1>
          <div class="flex items-center gap-4" style="color: var(--color-text-secondary); font-size: 0.9rem; margin-top: 6px;">
            <span class="flex items-center gap-1">
              <?php echo getIcon('map-pin', '', 16); ?>
              <?php echo htmlspecialchars($job['location']); ?>
            </span>
            <span>·</span>
            <span class="flex items-center gap-1">
              <?php echo getIcon('calendar', '', 16); ?>
              Posted <?php echo htmlspecialchars($job['posted']); ?>
            </span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button class="btn btn-outline job-bookmark-btn" data-title="<?php echo htmlspecialchars($job['title']); ?>">
          <?php echo getIcon('bookmark', '', 18); ?>
          <span>Save Job</span>
        </button>
        <button type="button" class="btn btn-primary btn-lg" onclick="openApplyModal('<?php echo $job['id']; ?>', '<?php echo addslashes($job['title']); ?>', '<?php echo addslashes($job['company']); ?>')">
          <span>Apply for this Position</span>
          <?php echo getIcon('arrow-right', '', 18); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr 340px; gap: var(--space-8); align-items: start;">
    
    <!-- Main Content: Role Overview & Specifications -->
    <main style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 32px;">
      
      <!-- Key Parameters Grid -->
      <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 16px; background: var(--color-bg); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--color-border); margin-bottom: 32px;">
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Compensation</div>
          <div style="font-size: 1.1rem; font-weight: 800; color: var(--color-primary); margin-top: 4px;"><?php echo htmlspecialchars($job['salary']); ?></div>
        </div>
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Experience Required</div>
          <div style="font-size: 1.05rem; font-weight: 700; color: var(--color-primary); margin-top: 4px;"><?php echo htmlspecialchars($job['experience']); ?></div>
        </div>
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Employment Type</div>
          <div style="font-size: 1.05rem; font-weight: 700; color: var(--color-primary); margin-top: 4px;"><?php echo htmlspecialchars($job['employment_type']); ?></div>
        </div>
        <div style="grid-column: span 3; border-top: 1px solid var(--color-border); padding-top: 12px;">
          <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Mandatory Council Registration</div>
          <div style="font-size: 0.95rem; font-weight: 700; color: var(--color-secondary); margin-top: 4px; display: flex; align-items: center; gap: 6px;">
            <?php echo getIcon('shield-check', 'text-teal', 18); ?>
            <span><?php echo htmlspecialchars($job['council_req']); ?></span>
          </div>
        </div>
      </div>

      <!-- Section: Role Overview -->
      <section style="margin-bottom: 32px;">
        <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Position Overview</h3>
        <p style="color: var(--color-text-secondary); line-height: 1.7; font-size: 1rem;">
          <?php echo htmlspecialchars($job['description']); ?>
        </p>
        <p style="color: var(--color-text-secondary); line-height: 1.7; font-size: 1rem; margin-top: 12px;">
          The chosen specialist will collaborate closely with surgical teams, diagnostic imaging faculties, and post-operative intensive care units to deliver exceptional clinical outcomes and uphold world-class patient safety benchmarks.
        </p>
      </section>

      <!-- Section: Key Responsibilities -->
      <section style="margin-bottom: 32px;">
        <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Key Clinical Responsibilities</h3>
        <ul style="display: flex; flex-direction: column; gap: 10px; color: var(--color-text-secondary); font-size: 0.95rem;">
          <li class="flex items-center gap-3">
            <?php echo getIcon('check', 'text-teal', 18); ?>
            <span>Execute inpatient rounds, outpatient specialty clinics, and critical consultations.</span>
          </li>
          <li class="flex items-center gap-3">
            <?php echo getIcon('check', 'text-teal', 18); ?>
            <span>Strict adherence to hospital antibiotic stewardship, infection control, and JCI quality audits.</span>
          </li>
          <li class="flex items-center gap-3">
            <?php echo getIcon('check', 'text-teal', 18); ?>
            <span>Mentor clinical fellows, post-graduate residents, and specialized nursing cadre.</span>
          </li>
          <li class="flex items-center gap-3">
            <?php echo getIcon('check', 'text-teal', 18); ?>
            <span>Participate in emergency call schedules and code blue resuscitation pathways.</span>
          </li>
        </ul>
      </section>

      <!-- Section: Qualifications & Credentials -->
      <section style="margin-bottom: 32px;">
        <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Qualifications & Council Eligibility</h3>
        <div style="background: var(--color-bg); padding: 16px; border-radius: var(--radius-md); border-left: 4px solid var(--color-secondary);">
          <div style="font-weight: 700; color: var(--color-primary); font-size: 1rem;">Degree / Fellowship:</div>
          <div style="color: var(--color-text-secondary); margin-top: 4px;"><?php echo htmlspecialchars($job['qualification']); ?></div>
          
          <div style="font-weight: 700; color: var(--color-primary); font-size: 1rem; margin-top: 12px;">Council Registration:</div>
          <div style="color: var(--color-text-secondary); margin-top: 4px;"><?php echo htmlspecialchars($job['council_req']); ?></div>
        </div>
      </section>

      <!-- Section: Compensation & Benefits -->
      <section style="margin-bottom: 32px;">
        <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Perks & Benefits</h3>
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="flex items-center gap-2" style="font-size: 0.9rem; color: var(--color-text-secondary);">
            <?php echo getIcon('check-circle', 'text-teal', 16); ?>
            <span>Comprehensive Family Health Cover</span>
          </div>
          <div class="flex items-center gap-2" style="font-size: 0.9rem; color: var(--color-text-secondary);">
            <?php echo getIcon('check-circle', 'text-teal', 16); ?>
            <span>Paid CME Leave & Conference Grant</span>
          </div>
          <div class="flex items-center gap-2" style="font-size: 0.9rem; color: var(--color-text-secondary);">
            <?php echo getIcon('check-circle', 'text-teal', 16); ?>
            <span>Performance Bonus & Incentive Share</span>
          </div>
          <div class="flex items-center gap-2" style="font-size: 0.9rem; color: var(--color-text-secondary);">
            <?php echo getIcon('check-circle', 'text-teal', 16); ?>
            <span>Subsidized Hospital Housing / Allowance</span>
          </div>
        </div>
      </section>

      <!-- Apply Callout Footer -->
      <div style="border-top: 1px solid var(--color-border); padding-top: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);">Ready to apply?</div>
          <div style="font-weight: 700; color: var(--color-primary);">Send your verified CV directly to the hiring committee.</div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openApplyModal('<?php echo $job['id']; ?>', '<?php echo addslashes($job['title']); ?>', '<?php echo addslashes($job['company']); ?>')">
          Apply Now
        </button>
      </div>

    </main>

    <!-- Right Sidebar: Organization Profile & Recruiter Info -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- Organization Profile Box -->
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <h4 style="font-size: 1.15rem; color: var(--color-primary); margin-bottom: 12px;">About the Hospital</h4>
        <div class="flex items-center gap-3" style="margin-bottom: 12px;">
          <div class="company-logo-avatar" style="width: 44px; height: 44px; font-size: 0.85rem;">
            <?php echo htmlspecialchars($company['logo_text'] ?? 'MED'); ?>
          </div>
          <div>
            <div style="font-weight: 700; color: var(--color-primary); font-size: 0.95rem;"><?php echo htmlspecialchars($company['name']); ?></div>
            <div style="font-size: 0.8rem; color: var(--color-secondary); font-weight: 600;"><?php echo htmlspecialchars($company['type']); ?></div>
          </div>
        </div>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary); line-height: 1.6; margin-bottom: 16px;">
          <?php echo htmlspecialchars($company['overview']); ?>
        </p>
        <div style="border-top: 1px solid var(--color-border); padding-top: 14px; font-size: 0.85rem;">
          <a href="<?php echo BASE_URL; ?>/company-details.php?id=<?php echo $company['id']; ?>" class="flex items-center justify-between" style="color: var(--color-secondary); font-weight: 600;">
            <span>View All Hospital Openings</span>
            <?php echo getIcon('chevron-right', '', 14); ?>
          </a>
        </div>
      </div>

      <!-- Verified Employer Guarantee -->
      <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: var(--radius-lg); padding: 20px;">
        <div class="flex items-center gap-2" style="color: #059669; font-weight: 700; margin-bottom: 6px;">
          <?php echo getIcon('shield-check', '', 20); ?>
          <span>Verified Healthcare Opening</span>
        </div>
        <p style="font-size: 0.85rem; color: #065F46; line-height: 1.5;">
          HealthJobsGlobal verifies every job posting directly with accredited hospital HR directors. No middleman agency fees.
        </p>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
