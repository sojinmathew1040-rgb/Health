<?php
/**
 * HealthJobsGlobal.com - Post a Healthcare Job (Hospital Recruiter)
 */
$pageTitle = "Post a Healthcare Job — HealthJobsGlobal";
$pageDescription = "Create a verified healthcare vacancy with council registration criteria and clinical parameters.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">Post Vacancy</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 4px;">Post a Healthcare Vacancy</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Specify mandatory council registrations and clinical competencies to receive pre-screened applications.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo BASE_URL; ?>/manage-jobs.php" class="btn btn-outline btn-sm">Manage Active Jobs</a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr 340px; gap: var(--space-8); align-items: start;">
    
    <main style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 36px; box-shadow: var(--shadow-sm);">
      <form onsubmit="event.preventDefault(); showToast('Healthcare vacancy published successfully! Dispatched to matching candidates on WhatsApp.'); setTimeout(() => window.location.href = '<?php echo BASE_URL; ?>/manage-jobs.php', 1200);">
        
        <!-- Role Details -->
        <section style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 24px;">
          <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 16px;">1. Role & Department</h3>
          
          <div class="form-group">
            <label class="form-label">Job Title *</label>
            <input type="text" class="form-control" placeholder="e.g. Senior Consultant – Interventional Cardiology" required>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label class="form-label">Healthcare Department *</label>
              <select class="form-control" required>
                <option value="">Select Department</option>
                <?php foreach ($healthcareDepartments as $k => $d): ?>
                  <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Clinical Specialty / Subspecialty *</label>
              <input type="text" class="form-control" placeholder="e.g. Cath Lab, Critical Care, MRI" required>
            </div>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label class="form-label">Employment Type *</label>
              <select class="form-control" required>
                <option>Full Time · On-Site</option>
                <option>Rotational Shifts (ICU/Ward)</option>
                <option>Visiting Consultant / On-Call</option>
                <option>Clinical Fellowship / Registrar</option>
                <option>Locum Tenens</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Hospital Location / Branch *</label>
              <input type="text" class="form-control" placeholder="e.g. Aster Medcity, Kochi, Kerala" required>
            </div>
          </div>
        </section>

        <!-- Clinical Council Credentials Required -->
        <section style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 24px;">
          <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 16px;">2. Mandatory Council & Education Criteria</h3>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label class="form-label">Mandatory Council Registration *</label>
              <input type="text" class="form-control" placeholder="e.g. TCMC / NMC / State Nursing Council / DHA" required>
              <span class="form-hint">Candidates without this council license will be flagged</span>
            </div>
            <div class="form-group">
              <label class="form-label">Minimum Educational Degree *</label>
              <input type="text" class="form-control" placeholder="e.g. MBBS, MD, DM or B.Sc Nursing" required>
            </div>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label class="form-label">Minimum Post-Qualification Experience *</label>
              <select class="form-control" required>
                <option>Fresher / Intern</option>
                <option>1 - 3 Years</option>
                <option>4 - 7 Years (Specialist)</option>
                <option selected>8+ Years (Senior Consultant)</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Maximum Notice Period Accepted</label>
              <select class="form-control">
                <option>Immediate Joiners Preferred</option>
                <option selected>30 Days</option>
                <option>60 Days</option>
                <option>90 Days</option>
              </select>
            </div>
          </div>
        </section>

        <!-- Compensation & Perks -->
        <section style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 24px;">
          <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 16px;">3. Compensation & Hospital Perks</h3>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label class="form-label">Annual Salary Range / Monthly Package *</label>
              <input type="text" class="form-control" placeholder="e.g. ₹40,00,000 - ₹50,00,000 / year or AED 60,000 / mo" required>
            </div>
            <div class="form-group">
              <label class="form-label">Salary Visibility</label>
              <select class="form-control">
                <option>Show salary publicly to attract 3x more specialists</option>
                <option>Show 'Competitive / Confidential'</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Role Description & Key Clinical Scope *</label>
            <textarea class="form-control" rows="5" placeholder="Detail surgical throughput, bed count, equipment (e.g. Philips Azurion Cath Lab), call schedules, and JCI compliance..." required></textarea>
          </div>
        </section>

        <!-- WhatsApp Broadcast Option -->
        <div style="background: var(--color-bg); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--color-border); margin-bottom: 24px;">
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: var(--color-primary);">
            <input type="checkbox" checked>
            <span>Broadcast instantly to WhatsApp Alert Subscribers (2,400+ Verified Specialists)</span>
          </label>
          <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 4px; padding-left: 24px;">
            Directly alerts doctors and nurses who match your council and specialty criteria within minutes.
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
          <button type="button" class="btn btn-outline" onclick="history.back()">Cancel</button>
          <button type="submit" class="btn btn-primary btn-lg">
            <span>Publish Vacancy</span>
            <?php echo getIcon('arrow-right', '', 18); ?>
          </button>
        </div>

      </form>
    </main>

    <!-- Right Sidebar -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 12px;">Healthcare Posting Standards</h4>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary); line-height: 1.5; margin-bottom: 14px;">
          All vacancies posted on HealthJobsGlobal are reviewed for NABH/JCI compliance and medical ethics. Vacancies must not demand security deposits from candidates.
        </p>
        <span class="hjg-badge badge-verified">Direct Verified Employer</span>
      </div>
    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
