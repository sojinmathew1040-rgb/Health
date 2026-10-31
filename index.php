<?php
/**
 * HealthJobsGlobal.com - Homepage
 * Premium Healthcare Recruitment Platform
 */
$pageTitle = "HealthJobsGlobal — Premium Healthcare Career & Recruitment Platform";
$pageDescription = "Connecting verified doctors, nurses, allied specialists, and healthcare administrators with top hospitals and clinics worldwide.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// If logged in as Employer, automatically redirect directly to Employer Dashboard
if (isLoggedIn()) {
    $currentUser = getLoggedInUser();
    if (($currentUser['role'] ?? '') === 'employer') {
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }
}

require_once __DIR__ . '/components/header.php';
?>

<!-- ==========================================================================
     HERO SECTION
     ========================================================================== -->
<section class="hero-section">
  <!-- Subtle WebGL Network Canvas -->
  <div id="canvas-container"></div>

  <div class="container hero-content">
    <div class="hero-grid <?php echo isLoggedIn() ? 'hero-grid-auth' : 'hero-grid-guest'; ?>">
      
      <!-- Main Column: Headline, Search, Popular tags -->
      <div class="hero-text-area">
        <div class="hero-badge-pill">
          <span class="pill-dot"></span>
          <span>Verified Healthcare Talent Exchange</span>
          <span style="color: var(--color-gold); font-weight: 700;">★ Global Network</span>
        </div>

        <h1 class="hero-title">
          Build Your Future in <span class="title-accent">Healthcare.</span>
        </h1>

        <p class="hero-subtitle">
          Discover clinical opportunities, connect with leading healthcare organizations, and take the next step in your professional journey.
        </p>

        <!-- Hero Search Form -->
        <div class="hero-search-box">
          <form id="heroSearchForm" action="<?php echo BASE_URL; ?>/jobs.php" method="GET" class="search-form-row">
            
            <!-- Job title / specialty -->
            <div class="search-field search-field-title" style="position: relative;">
              <?php echo getIcon('stethoscope', '', 18); ?>
              <input type="text" name="q" id="heroSearchInput" placeholder="Specialty or Job Title (e.g. Cardiologist, ICU Nurse)" autocomplete="off">
              <!-- Autocomplete suggestions dropdown -->
              <div id="searchSuggestionsBox" style="display: none; position: absolute; top: calc(100% + 8px); left: 0; right: 0; background: #FFFFFF; border-radius: var(--radius-md); box-shadow: var(--shadow-xl); border: 1px solid var(--color-border); z-index: 100; max-height: 220px; overflow-y: auto;"></div>
            </div>

            <!-- Location -->
            <div class="search-field search-field-loc">
              <?php echo getIcon('map-pin', '', 18); ?>
              <input type="text" name="location" id="heroLocationInput" placeholder="City / Country (e.g. Kochi, Dubai)" autocomplete="off">
            </div>

            <!-- Department -->
            <div class="search-field search-field-dept">
              <?php echo getIcon('briefcase', '', 18); ?>
              <select name="department" aria-label="Select Healthcare Department">
                <option value="">All Departments</option>
                <?php foreach ($healthcareDepartments as $k => $d): ?>
                  <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Submit CTA -->
            <button type="submit" class="btn btn-primary hero-submit-btn">
              <span>Search Jobs</span>
              <?php echo getIcon('search', '', 18); ?>
            </button>
          </form>
        </div>

        <!-- Popular Searches -->
        <div class="popular-searches">
          <span style="font-weight: 600;">Popular searches:</span>
          <a href="<?php echo BASE_URL; ?>/jobs.php?q=Doctors" class="popular-tag">Doctors</a>
          <a href="<?php echo BASE_URL; ?>/jobs.php?q=Nurses" class="popular-tag">Nurses</a>
          <a href="<?php echo BASE_URL; ?>/jobs.php?q=Physiotherapists" class="popular-tag">Physiotherapists</a>
          <a href="<?php echo BASE_URL; ?>/jobs.php?q=Radiology" class="popular-tag">Radiology</a>
          <a href="<?php echo BASE_URL; ?>/jobs.php?q=Laboratory" class="popular-tag">Laboratory</a>
          <a href="<?php echo BASE_URL; ?>/jobs.php?q=Healthcare+Management" class="popular-tag">Healthcare Management</a>
          <a href="<?php echo BASE_URL; ?>/jobs.php?q=Cardiology" class="popular-tag">Cardiology</a>
          <a href="<?php echo BASE_URL; ?>/jobs.php?q=ICU" class="popular-tag">Critical Care</a>
        </div>
      </div>

      <!-- Right Column: ONLY RENDERED WHEN LOGGED IN -->
      <?php if (isLoggedIn()): 
        $loggedInUser = getLoggedInUser();
      ?>
      <div class="hero-visual">
        <div class="hero-main-card">
          
          <!-- Card Header: Doctor Avatar, Name & Verification -->
          <div class="hero-doctor-badge">
            <div class="doctor-avatar-wrap">
              <span><?php echo htmlspecialchars($loggedInUser['initials'] ?? 'AM'); ?></span>
              <div class="doctor-verified-check" title="Verified Medical Council License">
                <?php echo getIcon('check', '', 14); ?>
              </div>
            </div>
            <div style="flex-grow: 1;">
              <div class="flex-between items-center" style="margin-bottom: 2px;">
                <span class="eyebrow" style="margin: 0; font-size: 0.72rem;"><?php echo ($loggedInUser['role'] ?? '') === 'employer' ? 'Verified Hospital' : 'Verified Clinician'; ?></span>
                <span class="hjg-badge badge-verified" style="font-size: 0.7rem;">
                  <?php echo getIcon('shield-check', '', 12); ?> <?php echo ($loggedInUser['role'] ?? '') === 'employer' ? 'NABH / JCI' : ($loggedInUser['council'] ?? 'TCMC / NMC'); ?>
                </span>
              </div>
              <h4 style="font-size: 1.2rem; color: var(--color-primary); margin: 0 0 2px 0;"><?php echo htmlspecialchars($loggedInUser['name'] ?? 'Dr. Arjun Menon'); ?></h4>
              <p style="font-size: 0.88rem; color: var(--color-secondary); font-weight: 600; margin: 0;"><?php echo htmlspecialchars($loggedInUser['title'] ?? 'Senior Consultant – Interventional Cardiology'); ?></p>
            </div>
          </div>

          <!-- Key Metrics Grid -->
          <div class="hero-metrics-grid">
            <div class="metric-pill">
              <div class="metric-pill-title">Clinical Exp.</div>
              <div class="metric-pill-val">12+ Years</div>
            </div>
            <div class="metric-pill">
              <div class="metric-pill-title">Procedures</div>
              <div class="metric-pill-val">3,500+ PCI</div>
            </div>
            <div class="metric-pill">
              <div class="metric-pill-title">Notice Period</div>
              <div class="metric-pill-val" style="color: #059669;">Immediate</div>
            </div>
          </div>

          <!-- Clinical Competencies Tags -->
          <div style="margin-bottom: 18px;">
            <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700; margin-bottom: 6px; letter-spacing: 0.04em;">Core Competencies</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
              <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 600;">Primary PCI</span>
              <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 600;">TAVI / Structural</span>
              <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 600;">Cath Lab Director</span>
              <span class="skill-tag" style="background: var(--color-secondary-soft); color: var(--color-secondary); font-weight: 600;">Level-3 ICU</span>
            </div>
          </div>

          <!-- Hospital Endorsement Note -->
          <div style="background: var(--color-bg); padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border); font-size: 0.82rem; color: var(--color-text-secondary); margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <span class="flex items-center gap-2">
              <?php echo getIcon('building', 'text-teal', 16); ?>
              <span>Shortlisted by <strong>Aster & Cleveland</strong></span>
            </span>
            <span class="hjg-badge badge-gold" style="font-size: 0.7rem;">Direct Interview</span>
          </div>

          <!-- Card Actions Footer -->
          <div class="flex-between items-center" style="border-top: 1px solid var(--color-border); padding-top: 16px; flex-wrap: wrap; gap: 10px;">
            <span style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 500;">Hospital Medical Board Verified</span>
            <a href="<?php echo BASE_URL; ?>/candidate-profile.php" class="btn btn-primary btn-sm">
              <span>View Full Bio-Data</span>
              <?php echo getIcon('arrow-right', '', 14); ?>
            </a>
          </div>

        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</section>

<!-- ==========================================================================
     TRUST & STATISTICS SECTION
     ========================================================================== -->
<section class="trust-section">
  <div class="container">
    <div class="stats-grid">
      
      <div class="stat-item stat-item-blue">
        <div class="stat-icon">
          <?php echo getIcon('user', '', 24); ?>
        </div>
        <div class="stat-content">
          <div class="stat-number" data-count="10000+">10,000+</div>
          <div class="stat-label">Healthcare Professionals</div>
        </div>
      </div>

      <div class="stat-item stat-item-teal">
        <div class="stat-icon">
          <?php echo getIcon('briefcase', '', 24); ?>
        </div>
        <div class="stat-content">
          <div class="stat-number" data-count="2500+">2,500+</div>
          <div class="stat-label">Active Opportunities</div>
        </div>
      </div>

      <div class="stat-item stat-item-indigo">
        <div class="stat-icon">
          <?php echo getIcon('building', '', 24); ?>
        </div>
        <div class="stat-content">
          <div class="stat-number" data-count="500+">500+</div>
          <div class="stat-label">Healthcare Organizations</div>
        </div>
      </div>

      <div class="stat-item stat-item-amber">
        <div class="stat-icon">
          <?php echo getIcon('globe', '', 24); ?>
        </div>
        <div class="stat-content">
          <div class="stat-number" data-count="45+">45+</div>
          <div class="stat-label">Specialties & AYUSH</div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ==========================================================================
     DEPARTMENTS SECTION
     ========================================================================== -->
<section class="departments-section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Career Verticals</span>
      <h2>Explore Healthcare Careers</h2>
      <p class="lead-text">
        From superspecialty surgery and critical care nursing to diagnostic imaging and hospital administration — find verified opportunities across accredited healthcare institutions.
      </p>
    </div>

    <!-- Filterable Category Pills -->
    <div class="department-tabs">
      <button type="button" class="dept-tab-btn active" data-target="all">All Departments</button>
      <button type="button" class="dept-tab-btn" data-target="clinical">Doctors & Surgery</button>
      <button type="button" class="dept-tab-btn" data-target="nursing">Nursing Specialties</button>
      <button type="button" class="dept-tab-btn" data-target="allied">Allied Health</button>
      <button type="button" class="dept-tab-btn" data-target="diagnostics">Diagnostics & Lab</button>
      <button type="button" class="dept-tab-btn" data-target="hospital_admin">Hospital Operations</button>
      <button type="button" class="dept-tab-btn" data-target="ayush">AYUSH</button>
    </div>

    <!-- Departments Grid -->
    <div class="departments-grid">
      <?php foreach ($healthcareDepartments as $deptKey => $dept): ?>
        <?php require __DIR__ . '/components/department-card.php'; ?>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: var(--space-10);">
      <a href="<?php echo BASE_URL; ?>/departments.php" class="btn btn-outline btn-lg">
        <span>Browse All 45+ Healthcare Specialties</span>
        <?php echo getIcon('arrow-right', '', 18); ?>
      </a>
    </div>
  </div>
</section>

<!-- ==========================================================================
     FEATURED JOBS SECTION
     ========================================================================== -->
<section class="jobs-section">
  <div class="container">
    <div class="flex-between items-center" style="margin-bottom: var(--space-8); flex-wrap: wrap; gap: 16px;">
      <div>
        <span class="eyebrow">Verified Openings</span>
        <h2>Latest Healthcare Opportunities</h2>
        <p class="lead-text" style="font-size: 1rem;">Direct openings from NABH & JCI accredited hospitals with verified council standards.</p>
      </div>
      <div>
        <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-secondary">
          <span>View All 2,500+ Jobs</span>
          <?php echo getIcon('arrow-right', '', 16); ?>
        </a>
      </div>
    </div>

    <!-- Jobs Grid -->
    <div class="jobs-grid">
      <?php 
      $homeJobs = getFeaturedJobs(6);
      foreach ($homeJobs as $job): 
        require __DIR__ . '/components/job-card.php';
      endforeach; 
      ?>
    </div>

    <div style="text-align: center; margin-top: var(--space-10);">
      <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-primary btn-lg">
        <span>Explore All Opportunities</span>
        <?php echo getIcon('arrow-right', '', 18); ?>
      </a>
    </div>
  </div>
</section>

<!-- ==========================================================================
     LEADING HEALTHCARE ORGANIZATIONS
     ========================================================================== -->
<section class="companies-section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Hospital Partners</span>
      <h2>Leading Healthcare Organizations</h2>
      <p class="lead-text">
        Trusted quaternary care groups, academic medical centres, and diagnostic networks hiring through HealthJobsGlobal.
      </p>
    </div>

    <div class="companies-grid">
      <?php foreach ($featuredCompanies as $company): ?>
        <?php require __DIR__ . '/components/company-card.php'; ?>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: var(--space-10);">
      <a href="<?php echo BASE_URL; ?>/companies.php" class="btn btn-outline btn-lg">
        <span>Explore All Healthcare Employers</span>
        <?php echo getIcon('arrow-right', '', 18); ?>
      </a>
    </div>
  </div>
</section>

<!-- ==========================================================================
     CANDIDATE & EMPLOYER DUAL ACTION BANNERS
     ========================================================================== -->
<section class="cta-dual-section">
  <div class="container">
    <div class="cta-grid">
      
      <!-- Candidate CTA -->
      <div class="cta-box cta-box-candidate">
        <span class="eyebrow eyebrow-gold">For Healthcare Professionals</span>
        <h3>Your Next Opportunity Starts Here</h3>
        <p>
          Create your professional healthcare profile, verify your council credentials, and let verified quaternary care hospitals discover your clinical expertise.
        </p>

        <div class="cta-benefits-list">
          <div class="cta-benefit-item">
            <?php echo getIcon('check', '', 18); ?>
            <span>Council registration verification badge (NMC / TCMC / DHA / MOH)</span>
          </div>
          <div class="cta-benefit-item">
            <?php echo getIcon('check', '', 18); ?>
            <span>Instant WhatsApp & Email alerts tailored to your specialty</span>
          </div>
          <div class="cta-benefit-item">
            <?php echo getIcon('check', '', 18); ?>
            <span>Confidentiality toggle: Hide profile from current hospital employer</span>
          </div>
        </div>

        <div class="flex items-center gap-4" style="flex-wrap: wrap;">
          <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-gold btn-lg">Create Your Profile</a>
          <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-outline" style="border-color: rgba(255,255,255,0.3); color: #FFFFFF;">Browse Jobs</a>
        </div>
      </div>

      <!-- Employer CTA -->
      <div class="cta-box cta-box-employer">
        <span class="eyebrow" style="color: #A5F3FC;">For Hospitals & Clinics</span>
        <h3>Find the Right Healthcare Talent</h3>
        <p>
          Accelerate your clinical hiring with pre-screened medical practitioners, JCI-experienced nurses, and biomedical engineers ready for deployment.
        </p>

        <div class="cta-benefits-list">
          <div class="cta-benefit-item">
            <?php echo getIcon('check', '', 18); ?>
            <span>Filter candidates by post-MD/MS experience and immediate notice</span>
          </div>
          <div class="cta-benefit-item">
            <?php echo getIcon('check', '', 18); ?>
            <span>Automated interview scheduling and applicant tracking</span>
          </div>
          <div class="cta-benefit-item">
            <?php echo getIcon('check', '', 18); ?>
            <span>Global credential verification for overseas deployments</span>
          </div>
        </div>

        <div class="flex items-center gap-4" style="flex-wrap: wrap;">
          <a href="<?php echo BASE_URL; ?>/register.php?type=employer" class="btn btn-primary btn-lg" style="background: #FFFFFF; color: var(--color-primary);">Register as Employer</a>
          <a href="<?php echo BASE_URL; ?>/post-job.php" class="btn btn-outline" style="border-color: rgba(255,255,255,0.3); color: #FFFFFF;">Post a Job</a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ==========================================================================
     INTERNSHIP & ON-THE-JOB TRAINING
     ========================================================================== -->
<section class="internship-section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Early Career & Clinical Fellowships</span>
      <h2>Start. Learn. Grow.</h2>
      <p class="lead-text">
        Structured clinical internships, rotatory residency pathways, and on-the-job fellowships in premiere teaching hospitals.
      </p>
    </div>

    <div class="internship-cards-grid">
      <div class="internship-card">
        <div class="internship-icon">
          <?php echo getIcon('award', '', 28); ?>
        </div>
        <h4>Clinical Internships</h4>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem; margin-top: 8px;">
          CRMI and compulsory rotatory training for MBBS, BDS, BPT, and B.Sc Nursing graduates at top multi-specialty centers.
        </p>
        <div style="margin-top: 16px;">
          <a href="<?php echo BASE_URL; ?>/jobs.php?type=internship" class="btn btn-outline btn-sm">View Internships</a>
        </div>
      </div>

      <div class="internship-card">
        <div class="internship-icon">
          <?php echo getIcon('activity', '', 28); ?>
        </div>
        <h4>On-the-Job Clinical Training</h4>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem; margin-top: 8px;">
          Cath Lab, Dialysis, Advanced Critical Care, and Operation Theatre specialized training programs with stipends.
        </p>
        <div style="margin-top: 16px;">
          <a href="<?php echo BASE_URL; ?>/jobs.php?type=training" class="btn btn-outline btn-sm">Explore Programs</a>
        </div>
      </div>

      <div class="internship-card">
        <div class="internship-icon">
          <?php echo getIcon('briefcase', '', 28); ?>
        </div>
        <h4>Healthcare Leadership Fellowships</h4>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem; margin-top: 8px;">
          Fellowships in Hospital Operations, Health Informatics, and Clinical Quality Management for ambitious leaders.
        </p>
        <div style="margin-top: 16px;">
          <a href="<?php echo BASE_URL; ?>/jobs.php?type=fellowship" class="btn btn-outline btn-sm">Learn More</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==========================================================================
     HOW IT WORKS (3-STEP EXPERIENCE)
     ========================================================================== -->
<section class="how-it-works-section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Seamless Process</span>
      <h2>How HealthJobsGlobal Works</h2>
      <p class="lead-text">
        Transparent, credential-verified recruitment designed specifically for the rigorous requirements of modern medicine.
      </p>
    </div>

    <div class="steps-grid">
      <div class="step-card">
        <div class="step-number">01</div>
        <h4>Create Your Profile</h4>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem; margin-top: 8px;">
          Enter your medical/nursing qualifications, council registration number, subspecialties, and clinical competencies.
        </p>
      </div>

      <div class="step-card">
        <div class="step-number">02</div>
        <h4>Discover Opportunities</h4>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem; margin-top: 8px;">
          Search by department, NABH/JCI accreditation, salary scale, or receive direct alerts on WhatsApp & email.
        </p>
      </div>

      <div class="step-card">
        <div class="step-number">03</div>
        <h4>Apply and Grow</h4>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem; margin-top: 8px;">
          Connect directly with hospital medical superintendents and HR leadership with 100% verified application delivery.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ==========================================================================
     PROFESSIONAL PROFILE PREVIEW (SECTION 19)
     ========================================================================== -->
<section class="profile-preview-section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Candidate Profile Quality</span>
      <h2>Designed for Healthcare Credentials</h2>
      <p class="lead-text">
        Every profile highlights the parameters medical directors care about: council numbers, clinical fellowships, procedures performed, and availability.
      </p>
    </div>

    <!-- Profile Mockup -->
    <div class="profile-mockup-wrapper">
      <div class="profile-mockup-top">
        <div class="profile-avatar-lockup">
          <div class="profile-avatar-large">DA</div>
          <div class="profile-info">
            <div class="flex items-center gap-2" style="margin-bottom: 4px;">
              <h3>Dr. Ananya Varma</h3>
              <span class="hjg-badge badge-verified"><?php echo getIcon('shield-check', '', 14); ?> Council Verified</span>
            </div>
            <div class="profile-specialty-sub">Senior Consultant – Critical Care & Pulmonology</div>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary); margin-top: 4px;">Aster Medcity · Kochi, Kerala</div>
          </div>
        </div>

        <div>
          <a href="<?php echo BASE_URL; ?>/candidate-profile.php" class="btn btn-primary btn-sm">
            <span>Explore Profile Editor</span>
            <?php echo getIcon('arrow-right', '', 14); ?>
          </a>
        </div>
      </div>

      <div class="profile-mockup-details-grid">
        <div class="profile-detail-cell">
          <div class="cell-label">Qualifications</div>
          <div class="cell-value">MBBS · MD (Pulm) · EDIC (Europe)</div>
        </div>
        <div class="profile-detail-cell">
          <div class="cell-label">Council Registration</div>
          <div class="cell-value">TCMC Reg. No: 48921 / NMC</div>
        </div>
        <div class="profile-detail-cell">
          <div class="cell-label">Experience</div>
          <div class="cell-value">11 Years Post-MD</div>
        </div>
        <div class="profile-detail-cell">
          <div class="cell-label">Notice Period</div>
          <div class="cell-value">30 Days (Negotiable)</div>
        </div>
        <div class="profile-detail-cell">
          <div class="cell-label">Languages</div>
          <div class="cell-value">English, Hindi, Malayalam, Arabic</div>
        </div>
        <div class="profile-detail-cell">
          <div class="cell-label">Preferred Location</div>
          <div class="cell-value">Kochi, Dubai, Bengaluru</div>
        </div>
      </div>

      <div style="background: var(--color-bg); padding: 14px; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--color-text-secondary); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <span><strong>Key Clinical Competencies:</strong> ECMO Management, Bronchoscopy, Level-3 ICU Governance, ARDS Ventilation</span>
        <span class="hjg-badge badge-gold">Pre-Screened</span>
      </div>
    </div>
  </div>
</section>

<!-- ==========================================================================
     JOB ALERTS SECTION (SECTION 20)
     ========================================================================== -->
<section style="padding: var(--space-20) 0; background: #FFFFFF;">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Real-Time Notifications</span>
      <h2>Never Miss the Right Opportunity</h2>
      <p class="lead-text">
        Get immediate notifications when premier hospitals post vacancies matching your council registration and specialty.
      </p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: var(--space-6);">
      <!-- Card 1: WhatsApp -->
      <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: var(--space-6);">
        <div class="flex items-center gap-3" style="margin-bottom: 12px; color: #10B981;">
          <?php echo getIcon('whatsapp', '', 26); ?>
          <span style="font-weight: 700; color: var(--color-primary); font-size: 1.05rem;">WhatsApp Vacancy Push</span>
        </div>
        <p style="font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 16px;">
          Receive salary details, duty shift timings, and 1-tap direct apply links straight into your WhatsApp messenger.
        </p>
        <span class="hjg-badge badge-verified">Instant 1-Tap Connect</span>
      </div>

      <!-- Card 2: Email Digest -->
      <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: var(--space-6);">
        <div class="flex items-center gap-3" style="margin-bottom: 12px; color: var(--color-secondary);">
          <?php echo getIcon('bell', '', 26); ?>
          <span style="font-weight: 700; color: var(--color-primary); font-size: 1.05rem;">Customized Job Alerts</span>
        </div>
        <p style="font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 16px;">
          Set up keyword-based filters for your preferred city, hospital chain, and minimum compensation bracket.
        </p>
        <span class="hjg-badge badge-default">Daily or Weekly Digest</span>
      </div>

      <!-- Card 3: Hospital Recruiter Discovery -->
      <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: var(--space-6);">
        <div class="flex items-center gap-3" style="margin-bottom: 12px; color: var(--color-gold-dark);">
          <?php echo getIcon('shield-check', '', 26); ?>
          <span style="font-weight: 700; color: var(--color-primary); font-size: 1.05rem;">Direct Headhunting</span>
        </div>
        <p style="font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 16px;">
          Let verified Medical Directors and Hospital Chief Executives send direct private invitations to your inbox.
        </p>
        <span class="hjg-badge badge-gold">Discreet & Confidential</span>
      </div>
    </div>
  </div>
</section>

<!-- ==========================================================================
     SAFETY / FRAUD PREVENTION NOTICE (SECTION 21)
     ========================================================================== -->
<section class="safety-section">
  <div class="container">
    <div class="safety-card">
      <div class="safety-icon-box">
        <?php echo getIcon('alert-triangle', '', 32); ?>
      </div>
      <div class="safety-content" style="flex-grow: 1;">
        <span class="eyebrow" style="color: #B45309; margin-bottom: 4px;">Security & Integrity Advisory</span>
        <h3>Stay Safe From Fake Job Offers</h3>
        <p>
          <strong>HealthJobsGlobal will never ask candidates for money, registration fees, or processing charges in exchange for employment.</strong> 
          We partner exclusively with accredited healthcare institutions with genuine vacancies. Please protect your personal security:
        </p>

        <div class="safety-guidelines-grid">
          <div class="safety-rule-item">Never share bank or debit/credit card details</div>
          <div class="safety-rule-item">Do NOT disclose Aadhaar / PAN to unverified parties</div>
          <div class="safety-rule-item">Reject unrealistic salary claims without interview</div>
          <div class="safety-rule-item">Report fraudulent offers to fraud@healthjobsglobal.com</div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/components/footer.php'; ?>
