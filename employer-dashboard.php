<?php
/**
 * HealthJobsGlobal.com - Employer Recruitment Dashboard
 */
$pageTitle = "Hospital Recruiter Dashboard — HealthJobsGlobal";
$pageDescription = "Manage hospital job postings, track verified medical applicants, and search council-registered clinicians.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div class="flex items-center gap-4">
        <div class="company-logo-avatar" style="width: 64px; height: 64px; font-size: 1.1rem; background: var(--color-secondary);">
          ASTER
        </div>
        <div>
          <div class="flex items-center gap-2" style="margin-bottom: 2px;">
            <h1 style="font-size: 1.65rem; color: var(--color-primary); margin: 0;">Aster Medcity & Aster Hospitals</h1>
            <span class="hjg-badge badge-gold">Quaternary Partner</span>
          </div>
          <div style="color: var(--color-text-secondary); font-size: 0.9rem;">
            Hospital Recruitment Cell · JCI & NABH Accredited · Kochi & Dubai
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo BASE_URL; ?>/candidate-search.php" class="btn btn-outline btn-sm">Search Candidate Database</a>
        <a href="<?php echo BASE_URL; ?>/post-job.php" class="btn btn-primary btn-sm">
          <?php echo getIcon('briefcase', '', 16); ?>
          <span>Post New Healthcare Job</span>
        </a>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  
  <!-- Hospital Stats Row -->
  <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
      <div style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Active Vacancies</div>
      <div style="font-size: 1.8rem; font-weight: 800; color: var(--color-primary); margin-top: 4px;">8</div>
      <div style="font-size: 0.8rem; color: var(--color-secondary); margin-top: 4px;"><a href="<?php echo BASE_URL; ?>/manage-jobs.php">Manage active postings →</a></div>
    </div>

    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
      <div style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Total Applicants</div>
      <div style="font-size: 1.8rem; font-weight: 800; color: var(--color-secondary); margin-top: 4px;">142</div>
      <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 4px;">+18 received this week</div>
    </div>

    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
      <div style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Shortlisted Clinicians</div>
      <div style="font-size: 1.8rem; font-weight: 800; color: #059669; margin-top: 4px;">28</div>
      <div style="font-size: 0.8rem; color: var(--color-success); margin-top: 4px;">6 interviews today</div>
    </div>

    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
      <div style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase;">Council Verified Rate</div>
      <div style="font-size: 1.8rem; font-weight: 800; color: var(--color-gold-dark); margin-top: 4px;">98.4%</div>
      <div style="font-size: 0.8rem; color: var(--color-text-secondary); margin-top: 4px;">Pre-screened licenses</div>
    </div>
  </div>

  <div class="grid" style="grid-template-columns: 1fr 320px; gap: var(--space-8); align-items: start;">
    
    <!-- Main: Recent Candidates Table -->
    <main style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 28px;">
      <div class="flex-between items-center" style="margin-bottom: 20px;">
        <div>
          <h3 style="font-size: 1.3rem; color: var(--color-primary); margin: 0;">Recent Clinical Applicants</h3>
          <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-top: 2px;">Direct applications across your active postings.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/candidate-search.php" class="btn btn-outline btn-sm">Search All 10,000+ Candidates</a>
      </div>

      <!-- Applicant List -->
      <div style="display: flex; flex-direction: column; gap: 14px;">
        
        <!-- Applicant 1 -->
        <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
          <div class="flex items-center gap-3">
            <div class="doctor-avatar-wrap" style="width: 48px; height: 48px; font-size: 1rem;">
              <span>AM</span>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=AM4892" style="font-weight: 700; color: var(--color-primary); font-size: 1.05rem;">Dr. Arjun Menon</a>
                <span class="hjg-badge badge-verified">TCMC / NMC Verified</span>
              </div>
              <div style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600;">Interventional Cardiologist · 12 Years Exp</div>
              <div style="font-size: 0.8rem; color: var(--color-text-muted);">Applied for: Senior Consultant – Cardiology</div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=AM4892" class="btn btn-outline btn-sm">Review Bio-Data</a>
            <button class="btn btn-primary btn-sm" onclick="showToast('Interview invitation dispatched via WhatsApp!');">Schedule Interview</button>
          </div>
        </div>

        <!-- Applicant 2 -->
        <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
          <div class="flex items-center gap-3">
            <div class="doctor-avatar-wrap" style="width: 48px; height: 48px; font-size: 1rem; background: linear-gradient(135deg, #0B1F33, #0E7490);">
              <span>RN</span>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=RN9912" style="font-weight: 700; color: var(--color-primary); font-size: 1.05rem;">Sr. Nimmy Varghese, B.Sc</a>
                <span class="hjg-badge badge-verified">KNC Registered</span>
              </div>
              <div style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600;">ICU In-Charge Specialist · 6 Years Exp</div>
              <div style="font-size: 0.8rem; color: var(--color-text-muted);">Applied for: Nurse In-Charge – ICU</div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=RN9912" class="btn btn-outline btn-sm">Review Bio-Data</a>
            <button class="btn btn-primary btn-sm" onclick="showToast('Candidate shortlisted for Clinical Ward round!');">Shortlist</button>
          </div>
        </div>

        <!-- Applicant 3 -->
        <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
          <div class="flex items-center gap-3">
            <div class="doctor-avatar-wrap" style="width: 48px; height: 48px; font-size: 1rem; background: linear-gradient(135deg, #C8A96B, #9F8246);">
              <span>RK</span>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=RK3312" style="font-weight: 700; color: var(--color-primary); font-size: 1.05rem;">Er. Rohit Kumar, B.Tech</a>
                <span class="hjg-badge badge-default">NABH Experienced</span>
              </div>
              <div style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600;">Lead Biomedical Engineer · 8 Years Exp</div>
              <div style="font-size: 0.8rem; color: var(--color-text-muted);">Applied for: Chief Biomedical Engineer</div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=RK3312" class="btn btn-outline btn-sm">Review Bio-Data</a>
            <button class="btn btn-primary btn-sm" onclick="showToast('Candidate shortlisted!');">Shortlist</button>
          </div>
        </div>

      </div>
    </main>

    <!-- Right Sidebar -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- Quick Post Job Card -->
      <div style="background: linear-gradient(135deg, #0B1F33, #153250); color: #FFFFFF; border-radius: var(--radius-xl); padding: 24px;">
        <h4 style="color: #FFFFFF; font-size: 1.15rem; margin-bottom: 8px;">Need Urgent Medical Staffing?</h4>
        <p style="color: #CBD5E1; font-size: 0.85rem; line-height: 1.5; margin-bottom: 16px;">
          Post specialized openings for Cath Lab, OT, or emergency shift coverage with instant WhatsApp notification broadcast to verified specialists.
        </p>
        <a href="<?php echo BASE_URL; ?>/post-job.php" class="btn btn-gold btn-sm" style="width: 100%;">
          Post Vacancy Now
        </a>
      </div>

      <!-- Quick Navigation -->
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px;">
        <h4 style="font-size: 1rem; color: var(--color-primary); margin-bottom: 14px;">Employer Tools</h4>
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
          <a href="<?php echo BASE_URL; ?>/manage-jobs.php" class="flex-between items-center" style="padding: 8px 10px; border-radius: var(--radius-sm); background: var(--color-bg);">
            <span class="flex items-center gap-2"><?php echo getIcon('briefcase', '', 16); ?> Active Postings</span>
            <span class="hjg-badge badge-default">8</span>
          </a>
          <a href="<?php echo BASE_URL; ?>/candidate-search.php" class="flex-between items-center" style="padding: 8px 10px; border-radius: var(--radius-sm); background: var(--color-bg);">
            <span class="flex items-center gap-2"><?php echo getIcon('search', '', 16); ?> Candidate Search</span>
            <span class="hjg-badge badge-verified">Talent Pool</span>
          </a>
          <a href="<?php echo BASE_URL; ?>/company-details.php?id=1" class="flex-between items-center" style="padding: 8px 10px; border-radius: var(--radius-sm); background: var(--color-bg);">
            <span class="flex items-center gap-2"><?php echo getIcon('building', '', 16); ?> Hospital Profile</span>
            <?php echo getIcon('external-link', '', 14); ?>
          </a>
        </div>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
