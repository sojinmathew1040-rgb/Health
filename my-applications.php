<?php
/**
 * HealthJobsGlobal.com - My Applications Pipeline
 */
$pageTitle = "My Applications & Status Pipeline — HealthJobsGlobal";
$pageDescription = "Track the status of your clinical job applications with verified hospitals.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';

$mockApplications = [
    [
        'id' => 'APP-8901',
        'title' => 'Senior Consultant – Interventional Cardiology',
        'company' => 'Aster Medcity',
        'location' => 'Kochi, Kerala',
        'applied_date' => '3 days ago',
        'status' => 'interview',
        'status_text' => 'Video Interview Scheduled',
        'badge' => 'badge-verified',
        'salary' => '₹42,00,000 - ₹55,00,000 / year',
        'next_step' => 'Clinical Panel Interview: Tomorrow 10:30 AM'
    ],
    [
        'id' => 'APP-8902',
        'title' => 'Consultant Cardiologist (Cath Lab Director)',
        'company' => 'Apollo Hospitals Enterprise',
        'location' => 'Chennai, Tamil Nadu',
        'applied_date' => '1 week ago',
        'status' => 'review',
        'status_text' => 'Under Medical Director Review',
        'badge' => 'badge-featured',
        'salary' => '₹45,00,000 - ₹58,00,000 / year',
        'next_step' => 'Credential verification in progress'
    ],
    [
        'id' => 'APP-8903',
        'title' => 'Consultant Interventionalist (DOH Eligible)',
        'company' => 'Cleveland Clinic Abu Dhabi',
        'location' => 'Abu Dhabi, UAE',
        'applied_date' => '2 weeks ago',
        'status' => 'interview',
        'status_text' => 'Shortlisted for UAE Mobility',
        'badge' => 'badge-gold',
        'salary' => 'AED 75,000 / month (Tax Free)',
        'next_step' => 'DOH licensing compliance check'
    ],
    [
        'id' => 'APP-8904',
        'title' => 'Associate Director – Cardiology Sciences',
        'company' => 'Fortis Healthcare Ltd.',
        'location' => 'Gurugram, Delhi NCR',
        'applied_date' => '3 weeks ago',
        'status' => 'applied',
        'status_text' => 'Application Received',
        'badge' => 'badge-default',
        'salary' => '₹48,00,000 / year',
        'next_step' => 'Awaiting initial hospital HR review'
    ]
];
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">My Applications</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 4px;">Application Tracker</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Real-time recruitment status for your active hospital applications.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-primary btn-sm">Browse More Jobs</a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  
  <!-- Pipeline Status Tabs -->
  <div class="pipeline-tabs" style="display: flex; gap: 10px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 6px;">
    <button class="btn btn-sm btn-primary pipeline-tab-btn">All Applications (4)</button>
    <button class="btn btn-sm btn-outline pipeline-tab-btn">Interviews Scheduled (2)</button>
    <button class="btn btn-sm btn-outline pipeline-tab-btn">Under Review (1)</button>
    <button class="btn btn-sm btn-outline pipeline-tab-btn">Archived</button>
  </div>

  <div class="grid" style="grid-template-columns: 1fr; gap: 16px;">
    <?php foreach ($mockApplications as $app): ?>
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px; transition: all var(--transition-smooth);">
        
        <div class="flex-between items-center" style="margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
          <div class="flex items-center gap-3">
            <span class="hjg-badge <?php echo $app['badge']; ?>"><?php echo htmlspecialchars($app['status_text']); ?></span>
            <span style="font-size: 0.8rem; font-family: monospace; color: var(--color-text-muted);">Ref: <?php echo $app['id']; ?></span>
          </div>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);">Applied <?php echo $app['applied_date']; ?></div>
        </div>

        <div class="flex-between items-center" style="margin-bottom: 14px; flex-wrap: wrap; gap: 16px;">
          <div>
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 4px;"><?php echo htmlspecialchars($app['title']); ?></h3>
            <div style="font-size: 0.95rem; color: var(--color-secondary); font-weight: 600;"><?php echo htmlspecialchars($app['company']); ?> · <?php echo htmlspecialchars($app['location']); ?></div>
          </div>
          <div style="font-size: 1.1rem; font-weight: 800; color: var(--color-primary);">
            <?php echo htmlspecialchars($app['salary']); ?>
          </div>
        </div>

        <div style="background: var(--color-bg); padding: 12px 16px; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--color-text-secondary); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
          <span class="flex items-center gap-2">
            <?php echo getIcon('clock', 'text-teal', 16); ?>
            <strong>Next Action:</strong> <?php echo htmlspecialchars($app['next_step']); ?>
          </span>
          <div class="app-card-actions flex items-center gap-2">
            <button class="btn btn-outline btn-sm" onclick="alert('Viewing application transmission log.');">View Submission</button>
            <?php if ($app['status'] === 'interview'): ?>
              <button class="btn btn-primary btn-sm" onclick="alert('Connecting to hospital interview lobby...');">Join Interview</button>
            <?php endif; ?>
          </div>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
