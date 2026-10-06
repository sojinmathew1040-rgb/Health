<?php
/**
 * HealthJobsGlobal.com - Candidate Profile Editor & View
 */
$pageTitle = "My Clinical Profile — HealthJobsGlobal";
$pageDescription = "View and manage your medical council credentials, clinical competencies, and resume.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">Clinical Profile</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div class="flex items-center gap-4">
        <div class="doctor-avatar-wrap" style="width: 72px; height: 72px; font-size: 1.4rem;">
          <span>AM</span>
        </div>
        <div>
          <div class="flex items-center gap-2" style="margin-bottom: 4px;">
            <h1 style="font-size: 1.8rem; color: var(--color-primary); margin: 0;">Dr. Arjun Menon, MD, DM</h1>
            <span class="hjg-badge badge-verified"><?php echo getIcon('shield-check', '', 14); ?> Council Verified</span>
          </div>
          <div style="color: var(--color-text-secondary); font-size: 0.95rem;">
            Senior Consultant – Interventional Cardiology · TCMC No. 48921 / NMC Registered
          </div>
        </div>
      </div>

      <div class="candidate-header-actions flex items-center gap-3">
        <button type="button" class="btn btn-primary" onclick="showToast('Profile credentials saved successfully!');">
          <?php echo getIcon('check', '', 16); ?>
          <span>Save Changes</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr 340px; gap: var(--space-8); align-items: start;">
    
    <!-- Profile Edit Form -->
    <main style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 32px;">
      
      <!-- Section: Personal Information -->
      <section style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 24px;">
        <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 16px;">Personal & Contact Details</h3>
        
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" value="Dr. Arjun Menon">
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" value="dr.arjun.menon@hospital.com">
          </div>
          <div class="form-group">
            <label class="form-label">WhatsApp Number</label>
            <input type="tel" class="form-control" value="+91 98460 55432">
          </div>
          <div class="form-group">
            <label class="form-label">Current City & State</label>
            <input type="text" class="form-control" value="Kochi, Kerala, India">
          </div>
        </div>
      </section>

      <!-- Section: Council Credentials -->
      <section style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 24px;">
        <div class="flex-between items-center" style="margin-bottom: 16px;">
          <h3 style="font-size: 1.3rem; color: var(--color-primary); margin: 0;">Medical Council & Accreditations</h3>
          <span class="hjg-badge badge-verified">Status: Verified with TCMC</span>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label">Primary Medical Council</label>
            <input type="text" class="form-control" value="Travancore-Cochin Medical Council (TCMC)">
          </div>
          <div class="form-group">
            <label class="form-label">Council Registration Number</label>
            <input type="text" class="form-control" value="48921 / 2012" style="font-family: monospace; font-weight: 700;">
          </div>
          <div class="form-group">
            <label class="form-label">Overseas Licensing Eligibility</label>
            <input type="text" class="form-control" value="DHA Consultant Letter / MOH UAE Eligible">
          </div>
          <div class="form-group">
            <label class="form-label">Good Standing Certificate</label>
            <input type="text" class="form-control" value="Valid through December 2026">
          </div>
        </div>
      </section>

      <!-- Section: Clinical Competencies -->
      <section style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 24px;">
        <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 16px;">Clinical Competencies & Procedural Skills</h3>
        <div class="form-group">
          <label class="form-label">Specialist Skills (comma separated)</label>
          <textarea class="form-control" rows="3">Primary Percutaneous Coronary Intervention (PCI), Complex Bifurcation Stenting, Transcatheter Aortic Valve Implantation (TAVI), Rotablation, Optical Coherence Tomography (OCT), Pacemaker & ICD Implantation</textarea>
        </div>
      </section>

      <!-- Section: Employment & Notice Period -->
      <section>
        <h3 style="font-size: 1.3rem; color: var(--color-primary); margin-bottom: 16px;">Current Employment & Availability</h3>
        
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label">Current Organization</label>
            <input type="text" class="form-control" value="Aster Medcity, Kochi">
          </div>
          <div class="form-group">
            <label class="form-label">Notice Period</label>
            <select class="form-control">
              <option value="30" selected>30 Days Notice</option>
              <option value="60">60 Days Notice</option>
              <option value="immediate">Immediate Available</option>
            </select>
          </div>
        </div>

        <div style="margin-top: 10px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--color-secondary);">
            <input type="checkbox" checked>
            <span>Confidentiality Mode: Hide my profile from Aster Medcity HR recruiters</span>
          </label>
        </div>
      </section>

    </main>

    <!-- Right Sidebar -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- Resume Upload Card -->
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 12px;">Curriculum Vitae</h4>
        <div class="flex items-center gap-3" style="background: var(--color-bg); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--color-border); margin-bottom: 14px;">
          <div style="color: var(--color-secondary);">
            <?php echo getIcon('file-text', '', 28); ?>
          </div>
          <div style="overflow: hidden;">
            <div style="font-size: 0.85rem; font-weight: 700; color: var(--color-primary); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">Dr_Arjun_Menon_CV_2026.pdf</div>
            <div style="font-size: 0.75rem; color: var(--color-text-muted);">Uploaded 2 weeks ago · 2.4 MB</div>
          </div>
        </div>
        <button class="btn btn-outline btn-sm" style="width: 100%;" onclick="alert('Upload replacement CV file dialog.');">
          Replace CV File
        </button>
      </div>

      <!-- Quick Action -->
      <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
        <h4 style="font-size: 1rem; color: var(--color-primary); margin-bottom: 8px;">Public Profile Preview</h4>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary); line-height: 1.5; margin-bottom: 14px;">
          See how your verified credentials appear to hospital medical superintendents.
        </p>
        <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=AM4892" class="btn btn-navy btn-sm" style="width: 100%;">
          View Employer View
        </a>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
