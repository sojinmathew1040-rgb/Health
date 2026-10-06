<?php
/**
 * HealthJobsGlobal.com - Candidate Search Engine (Hospital Recruiter Tool)
 */
$pageTitle = "Search Healthcare Talent & Clinicians — HealthJobsGlobal";
$pageDescription = "Search verified doctors, nurses, allied specialists, and hospital managers by council registration and experience.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';

$mockCandidates = [
    [
        'id' => 'AM4892',
        'name' => 'Dr. Arjun Menon',
        'title' => 'Senior Consultant – Interventional Cardiology',
        'qualification' => 'MBBS, MD (Gen Med), DM (Cardiology)',
        'council' => 'TCMC Reg. No. 48921 / NMC Active',
        'exp' => '12 Years Post-DM',
        'location' => 'Kochi, Kerala (Willing to relocate to GCC)',
        'notice' => '30 Days Notice',
        'skills' => ['Cath Lab', 'Primary PCI', 'TAVI', 'Rotablation', 'OCT'],
        'verified' => true
    ],
    [
        'id' => 'RN9912',
        'name' => 'Sr. Nimmy Varghese, B.Sc',
        'title' => 'ICU In-Charge & Critical Care Nurse Specialist',
        'qualification' => 'B.Sc Nursing, Post-Basic Critical Care',
        'council' => 'Kerala Nursing Council (KNC) / DHA Eligible',
        'exp' => '6 Years Level-3 ICU',
        'location' => 'Kochi, Kerala (Immediate Available)',
        'notice' => 'Immediate Available',
        'skills' => ['Ventilator Management', 'ABG Analysis', 'Infection Control', 'BLS/ACLS'],
        'verified' => true
    ],
    [
        'id' => 'DR7721',
        'name' => 'Dr. Farhana Siddique, MD',
        'title' => 'Consultant Radiologist (Cross-Sectional & MRI)',
        'qualification' => 'MBBS, MD (Radiodiagnosis), FRCR Part 1 & 2',
        'council' => 'Maharashtra Medical Council / NMC / DOH Eligible',
        'exp' => '7 Years Post-MD',
        'location' => 'Mumbai (Open to Kochi & Dubai)',
        'notice' => '15 Days Notice',
        'skills' => ['3T MRI', 'Dual-Source CT', 'Cardiac MRI', 'Fetal Medicine'],
        'verified' => true
    ],
    [
        'id' => 'RK3312',
        'name' => 'K. Rahul Nambiar, B.Sc Perfusion',
        'title' => 'Chief Clinical Perfusionist (Cardiothoracic Surgery)',
        'qualification' => 'B.Sc Perfusion Technology, Post-Grad Dip Extracorporeal Science',
        'council' => 'Indian Association of Cardiovascular-Thoracic Surgeons (IACTS)',
        'exp' => '8 Years in NABH Quaternary Care',
        'location' => 'Kochi, Kerala (Open to UAE)',
        'notice' => '30 Days Notice',
        'skills' => ['Heart-Lung Machine', 'ECMO Management', 'Cardioplegia Delivery', 'IABP'],
        'verified' => true
    ]
];
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 28px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php">Dashboard</a> / <span style="color: var(--color-primary); font-weight: 600;">Talent Search</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 1.85rem; color: var(--color-primary); margin-bottom: 4px;">Search Healthcare Talent</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Filter by clinical council registrations, surgical skills, and notice period.
        </p>
      </div>

      <div style="display: flex; gap: 8px; background: var(--color-bg); padding: 6px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); max-width: 420px; width: 100%;">
        <div style="display: flex; align-items: center; gap: 8px; padding-left: 10px; flex-grow: 1;">
          <?php echo getIcon('search', '', 18); ?>
          <input type="text" placeholder="Specialty, doctor, council no..." style="width: 100%; font-size: 0.9rem;">
        </div>
        <button class="btn btn-primary btn-sm">Search</button>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  
  <!-- Mobile Filter Toggle Bar (Visible on mobile/tablet <= 992px) -->
  <div class="mobile-filter-bar">
    <button type="button" class="mobile-filter-toggle-btn" id="mobileCandidateFilterBtn" onclick="toggleCandidateFilters()">
      <span class="flex items-center gap-2">
        <?php echo getIcon('filter', 'text-teal', 18); ?>
        <span>Filter Verified Talent</span>
      </span>
      <span class="filter-toggle-icon" id="candidateFilterToggleArrow">
        <?php echo getIcon('chevron-down', '', 16); ?>
      </span>
    </button>
  </div>

  <div class="grid layout-with-sidebar" style="grid-template-columns: 280px 1fr; gap: var(--space-8); align-items: start;">
    
    <!-- Left Filter Sidebar -->
    <aside class="candidate-filter-sidebar" id="candidateFilterSidebar" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
      <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 16px;">Filter Talent</h4>

      <div class="form-group">
        <label class="form-label">Council Verification</label>
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.85rem; color: var(--color-text-secondary);">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" checked> Council Verified Only
          </label>
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox"> Middle East License Eligible
          </label>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Professional Cadre</label>
        <select class="form-control" style="font-size: 0.85rem;">
          <option>All Healthcare Cadres</option>
          <option>Specialist Doctors (MD/MS/DM)</option>
          <option>Nursing Cadre (B.Sc/M.Sc)</option>
          <option>Allied Health & Diagnostics</option>
          <option>Biomedical Engineers</option>
          <option>AYUSH Practitioners</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Notice Period</label>
        <select class="form-control" style="font-size: 0.85rem;">
          <option>Any Notice Period</option>
          <option>Immediate Available</option>
          <option>Under 30 Days</option>
        </select>
      </div>

      <button type="button" class="btn btn-primary btn-sm" style="width: 100%;">Filter Database</button>
    </aside>

    <!-- Candidate Results Grid -->
    <main>
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php foreach ($mockCandidates as $cand): ?>
          <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
            <div class="flex-between items-center" style="margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
              <div class="flex items-center gap-3">
                <div class="doctor-avatar-wrap" style="width: 52px; height: 52px; font-size: 1.1rem;">
                  <?php echo substr($cand['name'], 0, 2); ?>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0;">
                      <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=<?php echo $cand['id']; ?>"><?php echo htmlspecialchars($cand['name']); ?></a>
                    </h3>
                    <span class="hjg-badge badge-verified"><?php echo getIcon('shield-check', '', 14); ?> Council Verified</span>
                  </div>
                  <div style="font-size: 0.9rem; color: var(--color-secondary); font-weight: 600;"><?php echo htmlspecialchars($cand['title']); ?></div>
                </div>
              </div>

              <span class="hjg-badge badge-default"><?php echo htmlspecialchars($cand['notice']); ?></span>
            </div>

            <!-- Credentials Grid -->
            <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 12px; background: var(--color-bg); padding: 12px 16px; border-radius: var(--radius-md); font-size: 0.85rem; margin-bottom: 14px;">
              <div>
                <span style="color: var(--color-text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Council:</span>
                <div style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($cand['council']); ?></div>
              </div>
              <div>
                <span style="color: var(--color-text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Education:</span>
                <div style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($cand['qualification']); ?></div>
              </div>
              <div>
                <span style="color: var(--color-text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Experience:</span>
                <div style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($cand['exp']); ?></div>
              </div>
            </div>

            <!-- Skills -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
              <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <?php foreach ($cand['skills'] as $skill): ?>
                  <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                <?php endforeach; ?>
              </div>

              <div class="flex items-center gap-2">
                <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=<?php echo $cand['id']; ?>" class="btn btn-outline btn-sm">
                  <span>View Verified Bio-Data</span>
                  <?php echo getIcon('chevron-right', '', 14); ?>
                </a>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    </main>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
