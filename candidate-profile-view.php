<?php
/**
 * HealthJobsGlobal.com - Candidate Profile View (Employer Recruiter Perspective)
 * Apple Level Pro Redesign - SF Pro Typography, macOS Materials
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$candId = $_GET['id'] ?? 'AM4892';

$pageTitle = "Verified Candidate Bio-Data — HealthJobsGlobal";
$pageDescription = "Verified candidate bio-data, council registration, and clinical competencies.";

require_once __DIR__ . '/components/header.php';
?>

<style>
  :root {
    --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    --apple-surface: #FFFFFF;
    --apple-bg: #F8FAFC;
    --apple-border: rgba(226, 232, 240, 0.9);
    --apple-text-primary: #0F172A;
    --apple-text-secondary: #475569;
    --apple-text-muted: #94A3B8;
    --apple-blue: #0071E3;
    --apple-blue-hover: #0077ED;
    --apple-card-radius: 24px;
    --apple-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.02), 0 4px 12px rgba(15, 23, 42, 0.03);
    --apple-shadow-md: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 28px -4px rgba(15, 23, 42, 0.06);
  }

  body {
    background-color: var(--apple-bg) !important;
    font-family: var(--apple-font) !important;
    letter-spacing: -0.011em;
  }

  .apple-pro-card {
    background: var(--apple-surface);
    border: 1px solid var(--apple-border);
    border-radius: var(--apple-card-radius);
    box-shadow: var(--apple-shadow-sm);
  }

  .apple-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 9px 20px;
    border-radius: 9999px;
    font-size: 0.86rem;
    font-weight: 600;
    font-family: var(--apple-font);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    text-decoration: none !important;
    border: none;
  }

  .apple-pill-btn-primary {
    background: var(--apple-blue);
    color: #FFFFFF !important;
    box-shadow: 0 2px 8px rgba(0, 113, 227, 0.25);
  }
  .apple-pill-btn-primary:hover {
    background: var(--apple-blue-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0, 113, 227, 0.35);
  }

  .apple-pill-btn-outline {
    background: #FFFFFF;
    color: var(--apple-text-secondary) !important;
    border: 1px solid rgba(203, 213, 225, 0.85);
  }
  .apple-pill-btn-outline:hover {
    background: #F8FAFC;
    color: var(--apple-text-primary) !important;
    border-color: #94A3B8;
  }

  .apple-pill-btn-whatsapp {
    background: #25D366;
    color: #FFFFFF !important;
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.25);
  }
  .apple-pill-btn-whatsapp:hover {
    background: #20BA59;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.35);
  }

  .apple-council-capsule {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #065F46;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.74rem;
    font-weight: 700;
  }

  .apple-skill-pill {
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--apple-text-secondary);
    background: #F1F5F9;
    padding: 5px 14px;
    border-radius: 9999px;
    border: 1px solid rgba(226, 232, 240, 0.8);
  }
</style>

<!-- Profile Header Banner -->
<div style="background: #FFFFFF; border-bottom: 1px solid var(--apple-border); padding: 26px 0; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
  <div class="container">
    <div style="font-size: 0.84rem; color: var(--apple-text-muted); margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
      <div style="display: flex; align-items: center; gap: 6px;">
        <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" style="color: var(--apple-blue); text-decoration: none; font-weight: 600;">Dashboard</a>
        <span style="color: #CBD5E1;">/</span>
        <a href="<?php echo BASE_URL; ?>/candidate-search.php" style="color: var(--apple-blue); text-decoration: none; font-weight: 600;">Talent Search</a>
        <span style="color: #CBD5E1;">/</span>
        <span style="color: var(--apple-text-primary); font-weight: 600;">Dr. Arjun Menon</span>
      </div>
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" class="apple-pill-btn apple-pill-btn-outline" style="font-weight: 700; padding: 7px 18px; font-size: 0.84rem; box-shadow: var(--apple-shadow-sm);">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        <span>Back to Dashboard Hub</span>
      </a>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 20px;">
      <div class="flex items-center gap-4">
        <div style="width: 72px; height: 72px; border-radius: 20px; background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%); color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; box-shadow: 0 6px 18px rgba(2, 132, 199, 0.25);">
          <span>AM</span>
        </div>
        <div>
          <div class="flex items-center gap-2" style="margin-bottom: 4px; flex-wrap: wrap;">
            <h1 style="font-size: 1.85rem; color: var(--apple-text-primary); margin: 0; font-weight: 800; font-family: var(--apple-font); letter-spacing: -0.03em;">
              Dr. Arjun Menon, MD, DM
            </h1>
            <span class="apple-council-capsule">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span>Council Verified</span>
            </span>
          </div>
          <div style="color: var(--apple-blue); font-size: 1.05rem; font-weight: 600;">
            Senior Consultant – Interventional Cardiology
          </div>
          <div style="color: var(--apple-text-secondary); font-size: 0.88rem; margin-top: 3px;">
            Kochi, Kerala · Open to Relocation (GCC / Bengaluru)
          </div>
        </div>
      </div>

      <div class="candidate-header-actions flex items-center gap-3">
        <button class="apple-pill-btn apple-pill-btn-outline" onclick="alert('Downloading verified surgical bio-data (PDF)...');">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <span>Download CV (PDF)</span>
        </button>
        <button class="apple-pill-btn apple-pill-btn-primary" onclick="if(typeof showToast==='function') showToast('Interview invitation dispatched on WhatsApp & Email!'); else alert('Interview invitation sent!');">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
          <span>Invite to Interview</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: 32px; padding-bottom: 60px;">
  <div class="grid" style="grid-template-columns: 1fr 340px; gap: 28px; align-items: start;">
    
    <!-- Candidate Profile Body -->
    <main class="apple-pro-card" style="padding: 34px;">
      
      <!-- Verified Council Registration -->
      <section style="background: #F8FAFC; padding: 22px 24px; border-radius: 18px; border: 1px solid var(--apple-border); margin-bottom: 32px;">
        <div class="flex-between items-center" style="margin-bottom: 14px;">
          <h4 style="font-size: 1.15rem; color: var(--apple-text-primary); margin: 0; font-weight: 800; font-family: var(--apple-font);">Primary Source Council Verification</h4>
          <span class="apple-council-capsule">Status: Good Standing Active</span>
        </div>
        <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 16px; font-size: 0.88rem;">
          <div>
            <div style="color: var(--apple-text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.06em;">Medical Council:</div>
            <div style="font-weight: 700; color: var(--apple-text-primary); margin-top: 3px;">Travancore-Cochin Medical Council</div>
          </div>
          <div>
            <div style="color: var(--apple-text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.06em;">Registration Number:</div>
            <div style="font-weight: 700; color: var(--apple-blue); font-family: monospace; font-size: 0.96rem; margin-top: 3px;">TCMC 48921 / 2012</div>
          </div>
          <div>
            <div style="color: var(--apple-text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.06em;">National Register:</div>
            <div style="font-weight: 700; color: var(--apple-text-primary); margin-top: 3px;">NMC Enrolled Active</div>
          </div>
        </div>
      </section>

      <!-- Clinical Education & Training -->
      <section style="margin-bottom: 32px;">
        <h3 style="font-size: 1.3rem; color: var(--apple-text-primary); margin-bottom: 16px; font-weight: 800;">Clinical Qualifications & Fellowships</h3>
        <div style="display: flex; flex-direction: column; gap: 14px;">
          <div style="border-left: 3px solid var(--apple-blue); padding-left: 16px;">
            <div style="font-weight: 800; color: var(--apple-text-primary); font-size: 1.05rem;">DM in Cardiology</div>
            <div style="color: var(--apple-text-secondary); font-size: 0.88rem; font-weight: 600;">Government Medical College, Trivandrum (2015 - 2018)</div>
          </div>
          <div style="border-left: 3px solid var(--apple-blue); padding-left: 16px;">
            <div style="font-weight: 800; color: var(--apple-text-primary); font-size: 1.05rem;">MD in General Medicine</div>
            <div style="color: var(--apple-text-secondary); font-size: 0.88rem; font-weight: 600;">Madras Medical College (2012 - 2015)</div>
          </div>
          <div style="border-left: 3px solid var(--apple-blue); padding-left: 16px;">
            <div style="font-weight: 800; color: var(--apple-text-primary); font-size: 1.05rem;">MBBS</div>
            <div style="color: var(--apple-text-secondary); font-size: 0.88rem; font-weight: 600;">Calicut Medical College (2006 - 2012)</div>
          </div>
        </div>
      </section>

      <!-- Procedural Competencies -->
      <section style="margin-bottom: 32px;">
        <h3 style="font-size: 1.3rem; color: var(--apple-text-primary); margin-bottom: 12px; font-weight: 800;">Cath Lab & Interventional Competencies</h3>
        <p style="color: var(--apple-text-secondary); line-height: 1.6; font-size: 0.94rem; margin-bottom: 16px;">
          Over 3,500+ independent coronary angiographies and 1,800+ angioplasties performed with high procedural success rate and low complication index.
        </p>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
          <span class="apple-skill-pill" style="background: rgba(0, 113, 227, 0.08); color: var(--apple-blue); font-weight: 700; border-color: rgba(0, 113, 227, 0.2);">Primary PCI (Radial Access)</span>
          <span class="apple-skill-pill" style="background: rgba(0, 113, 227, 0.08); color: var(--apple-blue); font-weight: 700; border-color: rgba(0, 113, 227, 0.2);">Complex Bifurcation Stenting</span>
          <span class="apple-skill-pill" style="background: rgba(0, 113, 227, 0.08); color: var(--apple-blue); font-weight: 700; border-color: rgba(0, 113, 227, 0.2);">Rotablation</span>
          <span class="apple-skill-pill" style="background: rgba(0, 113, 227, 0.08); color: var(--apple-blue); font-weight: 700; border-color: rgba(0, 113, 227, 0.2);">OCT / IVUS Imaging</span>
          <span class="apple-skill-pill" style="background: rgba(0, 113, 227, 0.08); color: var(--apple-blue); font-weight: 700; border-color: rgba(0, 113, 227, 0.2);">TAVI / Structural Heart</span>
          <span class="apple-skill-pill" style="background: rgba(0, 113, 227, 0.08); color: var(--apple-blue); font-weight: 700; border-color: rgba(0, 113, 227, 0.2);">Permanent Pacemaker / ICD</span>
        </div>
      </section>

      <!-- Direct Contact Recruiter Action -->
      <div style="background: #F8FAFC; border: 1px solid var(--apple-border); padding: 22px 24px; border-radius: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
          <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--apple-text-muted); font-weight: 700;">Direct Recruiter Connect</div>
          <div style="font-weight: 800; color: var(--apple-text-primary); margin-top: 2px;">Connect directly with Dr. Arjun Menon on WhatsApp.</div>
        </div>
        <a href="https://wa.me/919847012345?text=Hello%20Dr.%20Arjun%20Menon,%20we%20reviewed%20your%20verified%20profile%20on%20HealthJobsGlobal." target="_blank" class="apple-pill-btn apple-pill-btn-whatsapp">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
          <span>Contact on WhatsApp</span>
        </a>
      </div>

    </main>

    <!-- Right Sidebar -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- Key Career Snapshot -->
      <div class="apple-pro-card" style="padding: 26px;">
        <h4 style="font-size: 1.15rem; color: var(--apple-text-primary); margin: 0 0 18px 0; font-weight: 800; font-family: var(--apple-font);">Career Parameters</h4>
        
        <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.88rem;">
          <div>
            <div style="color: var(--apple-text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.06em;">Total Experience:</div>
            <div style="font-weight: 800; color: var(--apple-text-primary); font-size: 1.05rem; margin-top: 2px;">12+ Years Post-DM</div>
          </div>
          <div>
            <div style="color: var(--apple-text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.06em;">Notice Period:</div>
            <div style="font-weight: 800; color: var(--apple-blue); margin-top: 2px;">30 Days Notice</div>
          </div>
          <div>
            <div style="color: var(--apple-text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.06em;">Current Compensation:</div>
            <div style="font-weight: 800; color: var(--apple-text-primary); margin-top: 2px;">₹42,00,000 / year</div>
          </div>
          <div>
            <div style="color: var(--apple-text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.06em;">Expected Compensation:</div>
            <div style="font-weight: 800; color: var(--apple-text-primary); margin-top: 2px;">₹48,00,000 - ₹55,00,000 / yr</div>
          </div>
          <div>
            <div style="color: var(--apple-text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.06em;">Languages:</div>
            <div style="font-weight: 700; color: var(--apple-text-secondary); margin-top: 2px;">English, Malayalam, Hindi, Arabic</div>
          </div>
        </div>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
