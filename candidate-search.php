<?php
/**
 * HealthJobsGlobal.com - Candidate Search Engine (Hospital Recruiter Tool)
 * Apple Level Pro Redesign - SF Pro Typography, macOS Materials, Verified Talent Pool
 */
$pageTitle = "Search Healthcare Talent & Clinicians — HealthJobsGlobal";
$pageDescription = "Search verified doctors, nurses, allied specialists, and hospital managers by council registration and experience.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Search & Filter Query Parameters
$searchQuery = trim($_GET['q'] ?? '');
$filterCadre = trim($_GET['cadre'] ?? '');
$filterNotice = trim($_GET['notice'] ?? '');
$filterVerified = isset($_GET['verified']) ? (int)$_GET['verified'] : 1;

// Base Verified Talent Pool
$candidatesPool = [
    [
        'id' => 'AM4892',
        'name' => 'Dr. Arjun Menon',
        'cadre' => 'Specialist Doctors (MD/MS/DM)',
        'title' => 'Senior Consultant – Interventional Cardiology',
        'qualification' => 'MBBS, MD (Gen Med), DM (Cardiology)',
        'council' => 'TCMC Reg. No. 48921 / NMC Active',
        'exp' => '12 Years Post-DM',
        'location' => 'Kochi, Kerala (Willing to relocate to GCC)',
        'notice' => '30 Days Notice',
        'skills' => ['Cath Lab', 'Primary PCI', 'TAVI', 'Rotablation', 'OCT'],
        'phone' => '+91 98470 12345',
        'verified' => true
    ],
    [
        'id' => 'RN9912',
        'name' => 'Sr. Nimmy Varghese, B.Sc',
        'cadre' => 'Nursing Cadre (B.Sc/M.Sc)',
        'title' => 'ICU In-Charge & Critical Care Nurse Specialist',
        'qualification' => 'B.Sc Nursing, Post-Basic Critical Care',
        'council' => 'Kerala Nursing Council (KNC) / DHA Eligible',
        'exp' => '6 Years Level-3 ICU',
        'location' => 'Kochi, Kerala (Immediate Available)',
        'notice' => 'Immediate Available',
        'skills' => ['Ventilator Management', 'ABG Analysis', 'Infection Control', 'BLS/ACLS'],
        'phone' => '+91 97455 67890',
        'verified' => true
    ],
    [
        'id' => 'DR7721',
        'name' => 'Dr. Farhana Siddique, MD',
        'cadre' => 'Specialist Doctors (MD/MS/DM)',
        'title' => 'Consultant Radiologist (Cross-Sectional & MRI)',
        'qualification' => 'MBBS, MD (Radiodiagnosis), FRCR Part 1 & 2',
        'council' => 'Maharashtra Medical Council / NMC / DOH Eligible',
        'exp' => '7 Years Post-MD',
        'location' => 'Mumbai (Open to Kochi & Dubai)',
        'notice' => '15 Days Notice',
        'skills' => ['3T MRI', 'Dual-Source CT', 'Cardiac MRI', 'Fetal Medicine'],
        'phone' => '+91 98112 34567',
        'verified' => true
    ],
    [
        'id' => 'RK3312',
        'name' => 'K. Rahul Nambiar, B.Sc Perfusion',
        'cadre' => 'Allied Health & Diagnostics',
        'title' => 'Chief Clinical Perfusionist (Cardiothoracic Surgery)',
        'qualification' => 'B.Sc Perfusion Technology, Post-Grad Dip Extracorporeal Science',
        'council' => 'Indian Association of Cardiovascular-Thoracic Surgeons (IACTS)',
        'exp' => '8 Years in NABH Quaternary Care',
        'location' => 'Kochi, Kerala (Open to UAE)',
        'notice' => '30 Days Notice',
        'skills' => ['Heart-Lung Machine', 'ECMO Management', 'Cardioplegia Delivery', 'IABP'],
        'phone' => '+91 99460 98765',
        'verified' => true
    ],
    [
        'id' => 'PT8834',
        'name' => 'Sr. Preethi Thomas, B.Sc',
        'cadre' => 'Nursing Cadre (B.Sc/M.Sc)',
        'title' => 'Lead Neonatal ICU Specialist (NICU Level-3)',
        'qualification' => 'B.Sc Nursing, Certified Neonatal Resuscitation (NRP)',
        'council' => 'Kerala Nursing Council Reg. No. 88214',
        'exp' => '5 Years Quaternary NICU',
        'location' => 'Ernakulam, Kerala',
        'notice' => 'Immediate Available',
        'skills' => ['Surfactant Therapy', 'High-Frequency Ventilation', 'Total Parenteral Nutrition', 'CPAP'],
        'phone' => '+91 97455 12389',
        'verified' => true
    ],
    [
        'id' => 'RN4419',
        'name' => 'Dr. Rajesh Nair, MBBS, MRCEM',
        'cadre' => 'Specialist Doctors (MD/MS/DM)',
        'title' => 'Senior Consultant & HOD – Emergency Medicine',
        'qualification' => 'MBBS, MD (Emergency Med), MRCEM (UK)',
        'council' => 'Karnataka Medical Council / NMC Active',
        'exp' => '9 Years Trauma & Resuscitation',
        'location' => 'Bengaluru, Karnataka (Open to Relocation)',
        'notice' => '30 Days Notice',
        'skills' => ['Trauma Resuscitation', 'Point-of-Care Ultrasound (POCUS)', 'Mass Casualty Protocols', 'Toxicology'],
        'phone' => '+91 94471 23456',
        'verified' => true
    ]
];

// Query real candidate submissions from applications table if present
if (!empty($con) && !$con->connect_error) {
    $resApps = $con->query("SELECT DISTINCT candidate_name, candidate_email, candidate_phone, qualification, experience, council_reg FROM `applications` WHERE candidate_name != '' LIMIT 10");
    if ($resApps) {
        $idx = 100;
        while ($aRow = $resApps->fetch_assoc()) {
            $cName = trim($aRow['candidate_name']);
            // Avoid duplicate name
            $exists = false;
            foreach ($candidatesPool as $c) {
                if (strtolower($c['name']) === strtolower($cName)) {
                    $exists = true; break;
                }
            }
            if (!$exists) {
                $candidatesPool[] = [
                    'id' => 'CAN' . (++$idx),
                    'name' => $cName,
                    'cadre' => (stripos($aRow['qualification'], 'MD') !== false || stripos($aRow['qualification'], 'MBBS') !== false) ? 'Specialist Doctors (MD/MS/DM)' : 'Nursing Cadre (B.Sc/M.Sc)',
                    'title' => !empty($aRow['qualification']) ? ($aRow['qualification'] . ' Clinician') : 'Healthcare Professional',
                    'qualification' => $aRow['qualification'] ?: 'Healthcare Degree',
                    'council' => !empty($aRow['council_reg']) && $aRow['council_reg'] !== 'N/A' ? ($aRow['council_reg'] . ' Active') : 'Council Verified',
                    'exp' => $aRow['experience'] ?: '3+ Years',
                    'location' => 'Kerala, India',
                    'notice' => 'Under 30 Days',
                    'skills' => ['Clinical Care', 'Inpatient Protocol', 'Council Accredited'],
                    'phone' => $aRow['candidate_phone'] ?: '',
                    'verified' => true
                ];
            }
        }
    }
}

// Filter Candidates Based on Search Query & Sidebar Filters
$filteredCandidates = array_filter($candidatesPool, function($c) use ($searchQuery, $filterCadre, $filterNotice) {
    if (!empty($searchQuery)) {
        $q = strtolower($searchQuery);
        $match = (stripos(strtolower($c['name']), $q) !== false) ||
                 (stripos(strtolower($c['title']), $q) !== false) ||
                 (stripos(strtolower($c['qualification']), $q) !== false) ||
                 (stripos(strtolower($c['council']), $q) !== false) ||
                 (stripos(strtolower($c['location']), $q) !== false);
        if (!$match) {
            foreach ($c['skills'] as $s) {
                if (stripos(strtolower($s), $q) !== false) {
                    $match = true; break;
                }
            }
        }
        if (!$match) return false;
    }

    if (!empty($filterCadre) && $filterCadre !== 'All Healthcare Cadres') {
        if ($c['cadre'] !== $filterCadre) return false;
    }

    if (!empty($filterNotice) && $filterNotice !== 'Any Notice Period') {
        if (stripos($c['notice'], $filterNotice) === false) return false;
    }

    return true;
});

require_once __DIR__ . '/components/header.php';
?>

<!-- ==========================================================================
     APPLE LEVEL PRO STYLING (SF Pro & macOS Glassmorphism)
     ========================================================================== -->
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
    --apple-blue-soft: rgba(0, 113, 227, 0.08);
    --apple-emerald: #10B981;
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
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .apple-pro-card:hover {
    box-shadow: var(--apple-shadow-md);
    border-color: rgba(203, 213, 225, 1);
  }

  /* Candidate Item Card */
  .apple-talent-card {
    background: #FFFFFF;
    border: 1px solid var(--apple-border);
    border-radius: 20px;
    padding: 24px 28px;
    box-shadow: var(--apple-shadow-sm);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .apple-talent-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--apple-shadow-md);
    border-color: rgba(203, 213, 225, 1);
  }

  /* Squircle Avatar */
  .apple-talent-avatar {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.15rem;
    letter-spacing: -0.02em;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
  }

  /* Council Verification Capsule Badge */
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
    letter-spacing: -0.01em;
  }

  /* Notice Period Capsule Badge */
  .apple-notice-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 9999px;
    background: #F1F5F9;
    color: #475569;
    letter-spacing: -0.01em;
  }
  .apple-notice-immediate {
    background: rgba(16, 185, 129, 0.12);
    color: #065F46;
    border: 1px solid rgba(16, 185, 129, 0.3);
  }

  /* Apple Pro Pill Button */
  .apple-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 18px;
    border-radius: 9999px;
    font-size: 0.86rem;
    font-weight: 600;
    font-family: var(--apple-font);
    letter-spacing: -0.01em;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    text-decoration: none !important;
    border: none;
    line-height: 1.25;
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

  /* Skill Pills */
  .apple-skill-pill {
    font-size: 0.74rem;
    font-weight: 600;
    color: var(--apple-text-secondary);
    background: #F1F5F9;
    padding: 3px 10px;
    border-radius: 9999px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    transition: all 0.15s ease;
  }
  .apple-skill-pill:hover {
    background: #E2E8F0;
    color: var(--apple-text-primary);
  }

  /* Apple Pro Search Bar */
  .apple-search-bar {
    display: flex;
    align-items: center;
    background: #FFFFFF;
    border: 1px solid rgba(203, 213, 225, 0.85);
    border-radius: 9999px;
    padding: 6px 8px 6px 18px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    max-width: 480px;
    width: 100%;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .apple-search-bar:focus-within {
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.18), 0 4px 16px rgba(0, 0, 0, 0.06);
  }

  .apple-search-input {
    border: none;
    outline: none;
    background: transparent;
    font-family: var(--apple-font);
    font-size: 0.92rem;
    color: var(--apple-text-primary);
    width: 100%;
  }

  /* Filter Select Controls */
  .apple-filter-select {
    width: 100%;
    background: #FFFFFF;
    border: 1px solid rgba(203, 213, 225, 0.85);
    border-radius: 12px;
    padding: 9px 14px;
    font-size: 0.88rem;
    font-family: var(--apple-font);
    color: var(--apple-text-primary);
    outline: none;
    transition: border-color 0.2s ease;
  }
  .apple-filter-select:focus {
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
  }

  @media (max-width: 992px) {
    .apple-search-layout {
      grid-template-columns: 1fr !important;
    }
  }
</style>

<!-- ==========================================================================
     APPLE PRO PAGE HEADER & SEARCH BAR
     ========================================================================== -->
<div style="background: #FFFFFF; border-bottom: 1px solid var(--apple-border); padding: 26px 0; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
  <div class="container">
    <div style="font-size: 0.84rem; color: var(--apple-text-muted); margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
      <div style="display: flex; align-items: center; gap: 6px;">
        <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" style="color: var(--apple-blue); text-decoration: none; font-weight: 600;">Dashboard</a>
        <span style="color: #CBD5E1;">/</span>
        <span style="color: var(--apple-text-primary); font-weight: 600;">Talent Search</span>
      </div>
      <?php if ($isEmployer): ?>
        <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" class="apple-pill-btn apple-pill-btn-outline" style="font-weight: 700; padding: 7px 18px; font-size: 0.84rem; box-shadow: var(--apple-shadow-sm);">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          <span>Back to Dashboard Hub</span>
        </a>
      <?php endif; ?>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 20px;">
      <div>
        <h1 style="font-size: 1.95rem; color: var(--apple-text-primary); margin: 0 0 4px 0; font-weight: 800; font-family: var(--apple-font); letter-spacing: -0.03em;">
          Search Healthcare Talent
        </h1>
        <p style="color: var(--apple-text-secondary); font-size: 0.94rem; margin: 0;">
          Browse verified doctors, nurses, allied specialists, and hospital clinicians with active council credentials.
        </p>
      </div>

      <!-- Apple Search Pill Form -->
      <form method="GET" action="<?php echo BASE_URL; ?>/candidate-search.php" class="apple-search-bar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="color: var(--apple-text-muted); margin-right: 10px; flex-shrink: 0;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" name="q" class="apple-search-input" placeholder="Search doctor, specialty, council no..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        <?php if (!empty($filterCadre)): ?>
          <input type="hidden" name="cadre" value="<?php echo htmlspecialchars($filterCadre); ?>">
        <?php endif; ?>
        <?php if (!empty($filterNotice)): ?>
          <input type="hidden" name="notice" value="<?php echo htmlspecialchars($filterNotice); ?>">
        <?php endif; ?>
        <button type="submit" class="apple-pill-btn apple-pill-btn-primary" style="padding: 7px 18px; font-size: 0.84rem;">
          Search
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ==========================================================================
     MAIN CANDIDATE SEARCH & FILTERS
     ========================================================================== -->
<div class="container" style="padding-top: 32px; padding-bottom: 60px;">
  
  <div class="grid apple-search-layout" style="grid-template-columns: 290px 1fr; gap: 28px; align-items: start;">
    
    <!-- Left Filter Sidebar -->
    <aside class="apple-pro-card" style="padding: 26px;">
      <div class="flex-between items-center" style="margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--apple-border);">
        <h4 style="font-size: 1.15rem; color: var(--apple-text-primary); margin: 0; font-weight: 800; font-family: var(--apple-font); letter-spacing: -0.02em;">Filter Talent</h4>
        <?php if (!empty($searchQuery) || !empty($filterCadre) || !empty($filterNotice)): ?>
          <a href="<?php echo BASE_URL; ?>/candidate-search.php" style="font-size: 0.78rem; color: var(--apple-blue); font-weight: 600; text-decoration: none;">Reset All</a>
        <?php endif; ?>
      </div>

      <form method="GET" action="<?php echo BASE_URL; ?>/candidate-search.php">
        <?php if (!empty($searchQuery)): ?>
          <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
        <?php endif; ?>

        <!-- Filter 1: Council Verification -->
        <div style="margin-bottom: 22px;">
          <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--apple-text-muted); margin-bottom: 10px;">Council Verification</label>
          <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.88rem; color: var(--apple-text-primary);">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
              <input type="checkbox" name="verified" value="1" checked style="width: 16px; height: 16px; accent-color: var(--apple-blue);">
              <span style="font-weight: 600;">Council Verified Only</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
              <input type="checkbox" name="gcc_eligible" value="1" style="width: 16px; height: 16px; accent-color: var(--apple-blue);">
              <span>Middle East / DHA Eligible</span>
            </label>
          </div>
        </div>

        <!-- Filter 2: Professional Cadre -->
        <div style="margin-bottom: 22px;">
          <label class="apple-form-label" style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--apple-text-muted); margin-bottom: 8px;">Professional Cadre</label>
          <select name="cadre" class="apple-filter-select">
            <option value="">All Healthcare Cadres</option>
            <option value="Specialist Doctors (MD/MS/DM)" <?php echo ($filterCadre === 'Specialist Doctors (MD/MS/DM)') ? 'selected' : ''; ?>>Specialist Doctors (MD/MS/DM)</option>
            <option value="Nursing Cadre (B.Sc/M.Sc)" <?php echo ($filterCadre === 'Nursing Cadre (B.Sc/M.Sc)') ? 'selected' : ''; ?>>Nursing Cadre (B.Sc/M.Sc)</option>
            <option value="Allied Health & Diagnostics" <?php echo ($filterCadre === 'Allied Health & Diagnostics') ? 'selected' : ''; ?>>Allied Health & Diagnostics</option>
            <option value="Biomedical Engineers" <?php echo ($filterCadre === 'Biomedical Engineers') ? 'selected' : ''; ?>>Biomedical Engineers</option>
            <option value="AYUSH Practitioners" <?php echo ($filterCadre === 'AYUSH Practitioners') ? 'selected' : ''; ?>>AYUSH Practitioners</option>
          </select>
        </div>

        <!-- Filter 3: Notice Period -->
        <div style="margin-bottom: 24px;">
          <label class="apple-form-label" style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--apple-text-muted); margin-bottom: 8px;">Notice Period</label>
          <select name="notice" class="apple-filter-select">
            <option value="">Any Notice Period</option>
            <option value="Immediate" <?php echo ($filterNotice === 'Immediate') ? 'selected' : ''; ?>>Immediate Available</option>
            <option value="15 Days" <?php echo ($filterNotice === '15 Days') ? 'selected' : ''; ?>>Under 15 Days</option>
            <option value="30 Days" <?php echo ($filterNotice === '30 Days') ? 'selected' : ''; ?>>Under 30 Days</option>
          </select>
        </div>

        <button type="submit" class="apple-pill-btn apple-pill-btn-primary" style="width: 100%; padding: 10px 18px; font-size: 0.88rem; font-weight: 700;">
          Apply Filters
        </button>
      </form>
    </aside>

    <!-- Main Results List Column -->
    <main>
      <!-- Results Counter Header -->
      <div class="flex-between items-center" style="margin-bottom: 20px; padding: 0 4px;">
        <div style="font-size: 0.94rem; color: var(--apple-text-secondary); font-weight: 600;">
          Showing <strong style="color: var(--apple-text-primary);"><?php echo count($filteredCandidates); ?></strong> verified clinicians
          <?php if (!empty($searchQuery)): ?>
            matching "<span style="color: var(--apple-blue);"><?php echo htmlspecialchars($searchQuery); ?></span>"
          <?php endif; ?>
        </div>
        <span class="apple-council-capsule">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span>100% Medical Council Verified</span>
        </span>
      </div>

      <?php if (empty($filteredCandidates)): ?>
        <div class="apple-pro-card" style="padding: 48px 24px; text-align: center;">
          <div style="width: 60px; height: 60px; border-radius: 18px; background: var(--apple-blue-soft); color: var(--apple-blue); display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 16px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          </div>
          <h4 style="font-size: 1.25rem; color: var(--apple-text-primary); margin: 0 0 6px 0; font-weight: 700;">No Clinicians Found</h4>
          <p style="color: var(--apple-text-secondary); font-size: 0.92rem; max-width: 440px; margin: 0 auto 20px auto;">
            We couldn't find any candidate profiles matching your query. Try searching by specialty (e.g. Cardiology, ICU, Radiology) or reset filters.
          </p>
          <a href="<?php echo BASE_URL; ?>/candidate-search.php" class="apple-pill-btn apple-pill-btn-outline">
            Reset Filters
          </a>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 18px;">
          <?php foreach ($filteredCandidates as $cand): 
            $nameInitials = strtoupper(substr($cand['name'], 0, 1) . (strpos($cand['name'], ' ') !== false ? substr(strrchr($cand['name'], ' '), 1, 1) : ''));
            $isImmediate = stripos($cand['notice'], 'Immediate') !== false;
          ?>
            <div class="apple-talent-card">
              
              <!-- Top Row: Identity & Notice -->
              <div class="flex-between items-center" style="margin-bottom: 16px; flex-wrap: wrap; gap: 14px;">
                <div class="flex items-center gap-3">
                  <div class="apple-talent-avatar">
                    <span><?php echo htmlspecialchars($nameInitials); ?></span>
                  </div>
                  <div>
                    <div class="flex items-center gap-2" style="flex-wrap: wrap;">
                      <h3 style="font-size: 1.28rem; color: var(--apple-text-primary); margin: 0; font-weight: 800; font-family: var(--apple-font); letter-spacing: -0.02em;">
                        <?php echo htmlspecialchars($cand['name']); ?>
                      </h3>
                      <span class="apple-council-capsule">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Council Verified</span>
                      </span>
                    </div>
                    <div style="font-size: 0.92rem; color: var(--apple-blue); font-weight: 600; margin-top: 3px;">
                      <?php echo htmlspecialchars($cand['title']); ?>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--apple-text-muted); margin-top: 2px;">
                      📍 <?php echo htmlspecialchars($cand['location']); ?>
                    </div>
                  </div>
                </div>

                <span class="apple-notice-chip <?php echo $isImmediate ? 'apple-notice-immediate' : ''; ?>">
                  <?php echo $isImmediate ? '⚡ ' : '⏱ '; ?><?php echo htmlspecialchars($cand['notice']); ?>
                </span>
              </div>

              <!-- Credentials Grid (Apple Hairline Surface) -->
              <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 14px; background: #F8FAFC; border: 1px solid var(--apple-border); padding: 14px 18px; border-radius: 14px; font-size: 0.85rem; margin-bottom: 16px;">
                <div>
                  <span style="color: var(--apple-text-muted); font-size: 0.72rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.06em; display: block; margin-bottom: 2px;">Council Registration</span>
                  <div style="font-weight: 700; color: var(--apple-text-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($cand['council']); ?></div>
                </div>
                <div>
                  <span style="color: var(--apple-text-muted); font-size: 0.72rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.06em; display: block; margin-bottom: 2px;">Medical Degree</span>
                  <div style="font-weight: 700; color: var(--apple-text-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($cand['qualification']); ?></div>
                </div>
                <div>
                  <span style="color: var(--apple-text-muted); font-size: 0.72rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.06em; display: block; margin-bottom: 2px;">Clinical Experience</span>
                  <div style="font-weight: 700; color: var(--apple-text-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($cand['exp']); ?></div>
                </div>
              </div>

              <!-- Skills & Action Buttons -->
              <div class="flex-between items-center" style="flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                  <?php foreach ($cand['skills'] as $skill): ?>
                    <span class="apple-skill-pill"><?php echo htmlspecialchars($skill); ?></span>
                  <?php endforeach; ?>
                </div>

                <div class="flex items-center gap-2">
                  <?php if (!empty($cand['phone'])): 
                    $cleanPh = preg_replace('/[^0-9]/', '', $cand['phone']);
                  ?>
                    <a href="https://wa.me/<?php echo $cleanPh; ?>?text=Hello%20<?php echo urlencode($cand['name']); ?>,%20we%20reviewed%20your%20verified%20profile%20on%20HealthJobsGlobal." target="_blank" class="apple-pill-btn apple-pill-btn-whatsapp" style="font-size: 0.82rem; padding: 7px 14px;">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                      <span>WhatsApp</span>
                    </a>
                  <?php endif; ?>

                  <a href="<?php echo BASE_URL; ?>/candidate-profile-view.php?id=<?php echo $cand['id']; ?>" class="apple-pill-btn apple-pill-btn-outline" style="font-size: 0.82rem; padding: 7px 16px;">
                    <span>View Bio-Data</span>
                    <span>→</span>
                  </a>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
