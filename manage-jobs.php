<?php
/**
 * HealthJobsGlobal.com - Manage Hospital Job Postings
 */
$pageTitle = "Manage Active Job Postings — HealthJobsGlobal";
$pageDescription = "Monitor applicant inflow, pause or renew clinical postings, and review applications.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';

$myPostings = [
    [
        'id' => 101,
        'title' => 'Senior Consultant – Interventional Cardiology',
        'dept' => 'Clinical Medicine & Surgery',
        'location' => 'Kochi, Kerala',
        'posted' => '3 days ago',
        'applicants' => 18,
        'shortlisted' => 4,
        'status' => 'Active',
        'status_cls' => 'badge-verified'
    ],
    [
        'id' => 102,
        'title' => 'Nurse In-Charge – Intensive Care Unit (ICU)',
        'dept' => 'Nursing Specialties',
        'location' => 'Kochi, Kerala',
        'posted' => '1 week ago',
        'applicants' => 34,
        'shortlisted' => 8,
        'status' => 'Active',
        'status_cls' => 'badge-verified'
    ],
    [
        'id' => 106,
        'title' => 'Assistant General Manager – Hospital Operations',
        'dept' => 'Hospital Management & Support',
        'location' => 'Kochi, Kerala',
        'posted' => '2 weeks ago',
        'applicants' => 22,
        'shortlisted' => 5,
        'status' => 'Active',
        'status_cls' => 'badge-verified'
    ],
    [
        'id' => 107,
        'title' => 'Consultant Physician – Ayurveda (Integrative)',
        'dept' => 'AYUSH & Integrative Medicine',
        'location' => 'Thiruvananthapuram, Kerala',
        'posted' => '3 weeks ago',
        'applicants' => 14,
        'shortlisted' => 3,
        'status' => 'Active',
        'status_cls' => 'badge-verified'
    ]
];
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">Manage Postings</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 4px;">Active Hospital Postings (<?php echo count($myPostings); ?>)</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Track candidate pipelines, interview schedules, and listing statuses.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo BASE_URL; ?>/post-job.php" class="btn btn-primary btn-sm">
          <?php echo getIcon('briefcase', '', 16); ?>
          <span>Post New Vacancy</span>
        </a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr; gap: 16px;">
    <?php foreach ($myPostings as $posting): ?>
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <div class="flex-between items-center" style="margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
          <div class="flex items-center gap-3">
            <span class="hjg-badge <?php echo $posting['status_cls']; ?>"><?php echo $posting['status']; ?></span>
            <span style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600;"><?php echo htmlspecialchars($posting['dept']); ?></span>
          </div>
          <div style="font-size: 0.8rem; color: var(--color-text-muted);">Posted <?php echo $posting['posted']; ?></div>
        </div>

        <div class="flex-between items-center" style="margin-bottom: 16px; flex-wrap: wrap; gap: 16px;">
          <div>
            <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 4px;">
              <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $posting['id']; ?>"><?php echo htmlspecialchars($posting['title']); ?></a>
            </h3>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary);"><?php echo htmlspecialchars($posting['location']); ?></div>
          </div>

          <!-- Application Counts -->
          <div class="flex items-center gap-6">
            <div style="text-align: center;">
              <div style="font-size: 1.4rem; font-weight: 800; color: var(--color-primary);"><?php echo $posting['applicants']; ?></div>
              <div style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase; font-weight: 700;">Applicants</div>
            </div>
            <div style="text-align: center;">
              <div style="font-size: 1.4rem; font-weight: 800; color: #059669;"><?php echo $posting['shortlisted']; ?></div>
              <div style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase; font-weight: 700;">Shortlisted</div>
            </div>
          </div>
        </div>

        <div class="flex-between items-center" style="border-top: 1px solid var(--color-border); padding-top: 14px; font-size: 0.85rem; flex-wrap: wrap; gap: 10px;">
          <div class="flex items-center gap-3">
            <a href="<?php echo BASE_URL; ?>/candidate-search.php?job_id=<?php echo $posting['id']; ?>" class="btn btn-outline btn-sm">Review <?php echo $posting['applicants']; ?> Applicants</a>
            <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $posting['id']; ?>" class="btn btn-outline btn-sm">Public View</a>
          </div>
          <div class="flex items-center gap-2">
            <button class="btn btn-sm btn-outline" onclick="showToast('Listing paused.');">Pause Posting</button>
            <button class="btn btn-sm btn-outline" onclick="showToast('Link copied to clipboard!');">Share Link</button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
