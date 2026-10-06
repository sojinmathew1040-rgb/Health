<?php
/**
 * HealthJobsGlobal.com - Job Alerts & Notification Preferences
 */
$pageTitle = "Job Alerts & Notifications — HealthJobsGlobal";
$pageDescription = "Configure real-time WhatsApp and Email vacancy alerts matching your healthcare specialty.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">Job Alerts</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 4px;">WhatsApp & Email Job Alerts</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Instant vacancy alerts dispatched directly to your mobile for verified hospital requirements.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('createAlertBox').scrollIntoView({behavior:'smooth'});">
          <?php echo getIcon('bell', '', 16); ?>
          <span>Create New Alert</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr 340px; gap: var(--space-8); align-items: start;">
    
    <!-- Active Alerts List -->
    <main>
      
      <div style="margin-bottom: 24px;">
        <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 16px;">Your Active Subscriptions (3)</h3>

        <!-- Alert Item 1 -->
        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 16px;">
          <div class="flex-between items-center" style="margin-bottom: 8px;">
            <div class="flex items-center gap-2">
              <span class="hjg-badge badge-verified"><?php echo getIcon('whatsapp', '', 14); ?> Instant WhatsApp</span>
              <span class="hjg-badge badge-default">Email Digest</span>
            </div>
            <span style="font-size: 0.8rem; color: var(--color-text-muted);">Matches: 4 jobs this week</span>
          </div>

          <h4 style="font-size: 1.15rem; color: var(--color-primary); margin-bottom: 6px;">Interventional Cardiology · Kochi & Bengaluru</h4>
          <div style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 14px;">
            Salary: > ₹35,00,000 / year · Senior Consultant / HOD Roles · JCI / NABH Hospitals
          </div>

          <div class="flex-between items-center" style="border-top: 1px solid var(--color-border); padding-top: 12px; font-size: 0.85rem; flex-wrap: wrap; gap: 8px;">
            <a href="<?php echo BASE_URL; ?>/jobs.php?q=Cardiology&location=Kochi" style="color: var(--color-secondary); font-weight: 600;">View 12 Current Matches →</a>
            <button class="btn btn-outline btn-sm" onclick="showToast('Alert paused.');">Pause Alert</button>
          </div>
        </div>

        <!-- Alert Item 2 -->
        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 16px;">
          <div class="flex-between items-center" style="margin-bottom: 8px;">
            <div class="flex items-center gap-2">
              <span class="hjg-badge badge-gold"><?php echo getIcon('globe', '', 14); ?> Middle East Mobility</span>
              <span class="hjg-badge badge-verified">WhatsApp Active</span>
            </div>
            <span style="font-size: 0.8rem; color: var(--color-text-muted);">Matches: 2 jobs this week</span>
          </div>

          <h4 style="font-size: 1.15rem; color: var(--color-primary); margin-bottom: 6px;">Consultant Cardiologist · UAE (Dubai / Abu Dhabi)</h4>
          <div style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 14px;">
            Salary: > AED 60,000 / month · DOH / DHA Consultant License Eligible
          </div>

          <div class="flex-between items-center" style="border-top: 1px solid var(--color-border); padding-top: 12px; font-size: 0.85rem; flex-wrap: wrap; gap: 8px;">
            <a href="<?php echo BASE_URL; ?>/jobs.php?q=Cardiology&location=Dubai" style="color: var(--color-secondary); font-weight: 600;">View 4 Current Matches →</a>
            <button class="btn btn-outline btn-sm" onclick="showToast('Alert paused.');">Pause Alert</button>
          </div>
        </div>

      </div>

      <!-- Create New Alert Card -->
      <div id="createAlertBox" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 28px;">
        <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 6px;">Create Custom Healthcare Alert</h3>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 20px;">
          Specify clinical specialty keywords and delivery channels.
        </p>

        <form onsubmit="event.preventDefault(); showToast('New alert configured! Vacancies will be pushed to your WhatsApp.'); this.reset();">
          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label class="form-label">Specialty / Clinical Keywords *</label>
              <input type="text" class="form-control" placeholder="e.g. ICU Nurse, Radiologist, Medical Oncology" required>
            </div>
            <div class="form-group">
              <label class="form-label">Preferred Location</label>
              <input type="text" class="form-control" placeholder="e.g. Kochi, Mumbai, Dubai, London">
            </div>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label class="form-label">Minimum Expected Salary</label>
              <input type="text" class="form-control" placeholder="e.g. ₹15,00,000 / year">
            </div>
            <div class="form-group">
              <label class="form-label">Alert Frequency</label>
              <select class="form-control">
                <option value="instant">Instant WhatsApp (As soon as hospital posts)</option>
                <option value="daily">Daily Digest (8:00 AM)</option>
                <option value="weekly">Weekly Clinical Summary</option>
              </select>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">
            <span>Activate Alert</span>
            <?php echo getIcon('check', '', 16); ?>
          </button>
        </form>
      </div>

    </main>

    <!-- Right Sidebar -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- WhatsApp Delivery Status -->
      <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: var(--radius-lg); padding: 24px;">
        <div class="flex items-center gap-3" style="margin-bottom: 12px; color: #059669;">
          <?php echo getIcon('whatsapp', '', 28); ?>
          <div>
            <div style="font-weight: 700; font-size: 0.95rem;">WhatsApp Connected</div>
            <div style="font-size: 0.8rem; color: #047857;">+91 98460 55432</div>
          </div>
        </div>
        <p style="font-size: 0.85rem; color: #065F46; line-height: 1.5;">
          Direct job openings with verified medical directors are pushed directly into your WhatsApp.
        </p>
      </div>

      <!-- Fraud Warning Banner -->
      <div style="background: #FFFDF9; border: 1px solid #FEEBC8; border-radius: var(--radius-lg); padding: 20px;">
        <div class="flex items-center gap-2" style="color: #92400E; font-weight: 700; margin-bottom: 6px;">
          <?php echo getIcon('shield-check', '', 18); ?>
          <span>Verified Alerts Only</span>
        </div>
        <p style="font-size: 0.8rem; color: #78350F; line-height: 1.5;">
          We never dispatch sponsored agency ads. Every alert is an active vacancy in a registered hospital.
        </p>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
