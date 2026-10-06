<?php
/**
 * HealthJobsGlobal.com - Candidate Profile View (Employer Recruiter Perspective)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Candidate Profile: Dr. Arjun Menon — HealthJobsGlobal";
$pageDescription = "Verified candidate bio-data, council registration, and clinical competencies.";

require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php">Dashboard</a> / 
      <a href="<?php echo BASE_URL; ?>/candidate-search.php">Talent Search</a> / 
      <span style="color: var(--color-primary); font-weight: 600;">Dr. Arjun Menon</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div class="flex items-center gap-4">
        <div class="doctor-avatar-wrap" style="width: 72px; height: 72px; font-size: 1.4rem;">
          <span>AM</span>
        </div>
        <div>
          <div class="flex items-center gap-2" style="margin-bottom: 4px;">
            <h1 style="font-size: 1.85rem; color: var(--color-primary); margin: 0;">Dr. Arjun Menon, MD, DM</h1>
            <span class="hjg-badge badge-verified"><?php echo getIcon('shield-check', '', 14); ?> Council Verified</span>
          </div>
          <div style="color: var(--color-secondary); font-size: 1rem; font-weight: 600;">
            Senior Consultant – Interventional Cardiology
          </div>
          <div style="color: var(--color-text-secondary); font-size: 0.85rem; margin-top: 2px;">
            Kochi, Kerala · Open to Relocation (GCC / Bengaluru)
          </div>
        </div>
      </div>

      <div class="candidate-header-actions flex items-center gap-3">
        <button class="btn btn-outline btn-sm" onclick="alert('Downloading verified surgical bio-data (PDF)...');">
          <?php echo getIcon('file-text', '', 16); ?>
          <span>Download CV (PDF)</span>
        </button>
        <button class="btn btn-primary btn-sm" onclick="showToast('Interview invitation dispatched directly to candidate on WhatsApp & Email!');">
          <?php echo getIcon('calendar', '', 16); ?>
          <span>Invite to Interview</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr 340px; gap: var(--space-8); align-items: start;">
    
    <!-- Candidate Profile Body -->
    <main style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 32px;">
      
      <!-- Verified Council Registration -->
      <section style="background: var(--color-bg); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--color-border); margin-bottom: 32px;">
        <div class="flex-between items-center" style="margin-bottom: 12px;">
          <h4 style="font-size: 1.1rem; color: var(--color-primary); margin: 0;">Primary Source Council Verification</h4>
          <span class="hjg-badge badge-verified">Status: Good Standing Active</span>
        </div>
        <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 16px; font-size: 0.85rem;">
          <div>
            <div style="color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Medical Council:</div>
            <div style="font-weight: 700; color: var(--color-primary); margin-top: 2px;">Travancore-Cochin Medical Council</div>
          </div>
          <div>
            <div style="color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Registration Number:</div>
            <div style="font-weight: 700; color: var(--color-secondary); font-family: monospace; font-size: 0.95rem; margin-top: 2px;">TCMC 48921 / 2012</div>
          </div>
          <div>
            <div style="color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">National Register:</div>
            <div style="font-weight: 700; color: var(--color-primary); margin-top: 2px;">NMC Enrolled</div>
          </div>
        </div>
      </section>

      <!-- Clinical Education & Training -->
      <section style="margin-bottom: 32px;">
        <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 14px;">Clinical Qualifications & Fellowships</h3>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <div style="border-left: 3px solid var(--color-secondary); padding-left: 14px;">
            <div style="font-weight: 700; color: var(--color-primary); font-size: 1rem;">DM in Cardiology</div>
            <div style="color: var(--color-secondary); font-size: 0.85rem; font-weight: 600;">Government Medical College, Trivandrum (2015 - 2018)</div>
          </div>
          <div style="border-left: 3px solid var(--color-secondary); padding-left: 14px;">
            <div style="font-weight: 700; color: var(--color-primary); font-size: 1rem;">MD in General Medicine</div>
            <div style="color: var(--color-secondary); font-size: 0.85rem; font-weight: 600;">Madras Medical College (2012 - 2015)</div>
          </div>
          <div style="border-left: 3px solid var(--color-secondary); padding-left: 14px;">
            <div style="font-weight: 700; color: var(--color-primary); font-size: 1rem;">MBBS</div>
            <div style="color: var(--color-secondary); font-size: 0.85rem; font-weight: 600;">Calicut Medical College (2006 - 2012)</div>
          </div>
        </div>
      </section>

      <!-- Procedural Competencies -->
      <section style="margin-bottom: 32px;">
        <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 14px;">Cath Lab & Interventional Competencies</h3>
        <p style="color: var(--color-text-secondary); line-height: 1.6; font-size: 0.95rem; margin-bottom: 14px;">
          Over 3,500+ independent coronary angiographies and 1,800+ angioplasties performed with high procedural success rate and low complication index.
        </p>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
          <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 700;">Primary PCI (Radial Access)</span>
          <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 700;">Complex Bifurcation Stenting</span>
          <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 700;">Rotablation</span>
          <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 700;">OCT / IVUS Imaging</span>
          <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 700;">TAVI / Structural Heart</span>
          <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 700;">Permanent Pacemaker / ICD</span>
        </div>
      </section>

      <!-- Direct Contact Recruiter Action -->
      <div style="background: var(--color-bg); border-top: 1px solid var(--color-border); padding: 24px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);">Direct Recruiter Connect</div>
          <div style="font-weight: 700; color: var(--color-primary);">Connect directly with Dr. Arjun Menon on WhatsApp.</div>
        </div>
        <button class="btn btn-primary" onclick="showToast('Candidate contacted directly via verified WhatsApp notification.');">
          <?php echo getIcon('whatsapp', '', 18); ?>
          <span>Contact on WhatsApp</span>
        </button>
      </div>

    </main>

    <!-- Right Sidebar -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- Key Career Snapshot -->
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 16px;">Career Parameters</h4>
        
        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.85rem;">
          <div>
            <div style="color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Total Experience:</div>
            <div style="font-weight: 700; color: var(--color-primary); font-size: 1rem; margin-top: 2px;">12+ Years Post-DM</div>
          </div>
          <div>
            <div style="color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Notice Period:</div>
            <div style="font-weight: 700; color: var(--color-secondary); margin-top: 2px;">30 Days Notice</div>
          </div>
          <div>
            <div style="color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Current Compensation:</div>
            <div style="font-weight: 700; color: var(--color-primary); margin-top: 2px;">₹42,00,000 / year</div>
          </div>
          <div>
            <div style="color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Expected Compensation:</div>
            <div style="font-weight: 700; color: var(--color-primary); margin-top: 2px;">₹48,00,000 - ₹55,00,000 / yr</div>
          </div>
          <div>
            <div style="color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Languages:</div>
            <div style="font-weight: 700; color: var(--color-primary); margin-top: 2px;">English, Malayalam, Hindi, Arabic</div>
          </div>
        </div>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
