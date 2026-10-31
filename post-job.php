<?php
/**
 * HealthJobsGlobal.com - Post a Healthcare Job (Hospital Recruiter)
 * Apple Level Pro Redesign - SF Pro Typography, macOS Materials, Precision Forms
 */
$pageTitle = "Post a Healthcare Job — HealthJobsGlobal";
$pageDescription = "Create a verified healthcare vacancy with council registration criteria and clinical parameters.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Access Control: Only logged-in hospital employers can post vacancies
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode(BASE_URL . "/post-job.php"));
    exit;
}

$activeUser = getLoggedInUser();
if (($activeUser['role'] ?? '') !== 'employer') {
    header("Location: " . BASE_URL . "/employer-dashboard.php");
    exit;
}

$hospitalId = (int)($activeUser['id'] ?? 0);
$hospitalName = !empty($activeUser['hospital']) ? $activeUser['hospital'] : ($activeUser['name'] ?? 'Healthcare Hospital');

$errorMsg = '';
$successMsg = '';

// Handle Job Submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $deptKey = trim($_POST['department'] ?? '');
    $department = $healthcareDepartments[$deptKey]['name'] ?? $deptKey;
    $specialty = trim($_POST['specialty'] ?? '');
    $employment_type = trim($_POST['employment_type'] ?? 'Full Time · On-Site');
    $location = trim($_POST['location'] ?? '');
    $council_req = trim($_POST['council_req'] ?? 'Medical / Nursing Council Active');
    $qualification = trim($_POST['qualification'] ?? '');
    $experience = trim($_POST['experience'] ?? '3 - 6 Years');
    $salary = trim($_POST['salary'] ?? 'Competitive');
    $salary_vis = trim($_POST['salary_visibility'] ?? 'public');
    if ($salary_vis === 'confidential') {
        $salary = 'Competitive / Confidential';
    }
    $description = trim($_POST['description'] ?? '');
    $urgent = !empty($_POST['urgent']) ? 1 : 0;
    
    // Auto-generate tags from specialty & department
    $tagItems = [];
    if (!empty($specialty)) $tagItems[] = $specialty;
    if (!empty($department)) $tagItems[] = $department;
    if (!empty($experience)) $tagItems[] = $experience;
    $tags = implode(',', array_slice($tagItems, 0, 4));

    if (empty($title) || empty($department) || empty($specialty) || empty($location) || empty($qualification)) {
        $errorMsg = "Please fill in all mandatory fields marked with an asterisk (*).";
    } elseif ($con && !$con->connect_error) {
        $stmt = $con->prepare("INSERT INTO `jobs` 
            (`title`, `hospital_id`, `company`, `location`, `department`, `specialty`, `experience`, `employment_type`, `salary`, `council_req`, `qualification`, `description`, `tags`, `status`, `featured`, `urgent`, `posted_at`, `created_at`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 0, ?, NOW(), NOW())");
        if ($stmt) {
            $stmt->bind_param("sisssssssssssi", 
                $title, 
                $hospitalId, 
                $hospitalName, 
                $location, 
                $department, 
                $specialty, 
                $experience, 
                $employment_type, 
                $salary, 
                $council_req, 
                $qualification, 
                $description, 
                $tags, 
                $urgent
            );
            if ($stmt->execute()) {
                $newJobId = $stmt->insert_id;
                $stmt->close();

                // Update hospital's active openings count in database
                $con->query("UPDATE `hospitals` SET `openings` = (SELECT COUNT(*) FROM `jobs` WHERE (`hospital_id` = {$hospitalId} OR `company` = '" . $con->real_escape_string($hospitalName) . "') AND `status` = 'Active') WHERE `id` = {$hospitalId}");

                $_SESSION['flash_success'] = "Healthcare vacancy for '" . htmlspecialchars($title) . "' published successfully! It is now live in My Vacancies and visible to all candidates.";
                header("Location: " . BASE_URL . "/employer-dashboard.php#jobs");
                exit;
            } else {
                $errorMsg = "Database error publishing vacancy: " . $stmt->error;
                $stmt->close();
            }
        } else {
            $errorMsg = "Failed to prepare job creation statement: " . $con->error;
        }
    } else {
        $errorMsg = "Database connection unavailable. Please check MySQL service.";
    }
}

require_once __DIR__ . '/components/header.php';
?>

<!-- ==========================================================================
     APPLE LEVEL PRO STYLING (SF Pro & Precision Materials)
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
    transition: box-shadow 0.25s ease, border-color 0.25s ease;
  }

  /* Apple Pro Inputs */
  .apple-form-control {
    width: 100%;
    background: #FFFFFF;
    border: 1px solid rgba(203, 213, 225, 0.85);
    border-radius: 12px;
    padding: 11px 16px;
    font-size: 0.92rem;
    font-family: var(--apple-font);
    color: var(--apple-text-primary);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
  }

  .apple-form-control:focus {
    outline: none;
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.18);
  }

  .apple-form-label {
    display: block;
    font-weight: 700;
    font-size: 0.88rem;
    color: var(--apple-text-primary);
    margin-bottom: 6px;
    letter-spacing: -0.01em;
  }

  .apple-form-hint {
    font-size: 0.78rem;
    color: var(--apple-text-muted);
    margin-top: 4px;
    display: block;
  }

  /* Apple Pill Button */
  .apple-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 9999px;
    font-size: 0.88rem;
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

  /* Section Step Pill */
  .apple-step-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(0, 113, 227, 0.08);
    color: var(--apple-blue);
    font-weight: 800;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 4px 12px;
    border-radius: 9999px;
    margin-bottom: 8px;
  }

  /* Obsidian Hero in Sidebar */
  .apple-titanium-widget {
    background: linear-gradient(145deg, #0B1727 0%, #17283E 50%, #0F1E32 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: var(--apple-card-radius);
    padding: 26px 24px;
    color: #FFFFFF;
    box-shadow: 0 16px 36px -8px rgba(11, 23, 39, 0.35);
    position: relative;
    overflow: hidden;
  }

  @media (max-width: 992px) {
    .apple-post-grid {
      grid-template-columns: 1fr !important;
    }
  }
</style>

<!-- ==========================================================================
     APPLE PRO PAGE HEADER
     ========================================================================== -->
<div style="background: #FFFFFF; border-bottom: 1px solid var(--apple-border); padding: 26px 0; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
  <div class="container">
    <div style="font-size: 0.84rem; color: var(--apple-text-muted); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" style="color: var(--apple-blue); text-decoration: none; font-weight: 600;">Dashboard</a>
      <span style="color: #CBD5E1;">/</span>
      <span style="color: var(--apple-text-primary); font-weight: 600;">Post Vacancy</span>
    </div>
    
    <div>
      <h1 style="font-size: 1.95rem; color: var(--apple-text-primary); margin: 0 0 4px 0; font-weight: 800; font-family: var(--apple-font); letter-spacing: -0.03em;">
        Post a Healthcare Vacancy
      </h1>
      <p style="color: var(--apple-text-secondary); font-size: 0.94rem; margin: 0;">
        Publish your opening on behalf of <strong style="color: var(--apple-text-primary);"><?php echo htmlspecialchars($hospitalName); ?></strong> with council accreditation parameters.
      </p>
    </div>
  </div>
</div>

<!-- ==========================================================================
     MAIN POST JOB FORM SECTION
     ========================================================================== -->
<div class="container" style="padding-top: 28px; padding-bottom: 60px;">
  
  <!-- Common Top Action Row (Identical to Vacancies & Applications views) -->
  <div class="flex-between items-center" style="margin-bottom: 22px; flex-wrap: wrap; gap: 14px;">
    <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" class="apple-pill-btn apple-pill-btn-outline" style="font-weight: 700; padding: 10px 20px; font-size: 0.9rem; box-shadow: var(--apple-shadow-sm);">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      <span>Back to Dashboard Hub</span>
    </a>
    <div class="flex items-center gap-2">
      <span class="apple-council-capsule" style="font-size: 0.85rem; padding: 6px 16px;">
        ✦ New Job Posting
      </span>
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php?view=jobs" class="apple-pill-btn apple-pill-btn-outline" style="padding: 10px 20px; font-weight: 600;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        <span>View My Vacancies</span>
      </a>
    </div>
  </div>
  
  <?php if (!empty($errorMsg)): ?>
    <div style="background: rgba(254, 242, 242, 0.95); backdrop-filter: blur(8px); border: 1px solid rgba(239, 68, 68, 0.3); color: #991B1B; padding: 16px 22px; border-radius: 18px; margin-bottom: 24px; font-weight: 600; font-size: 0.92rem; display: flex; align-items: center; gap: 12px; box-shadow: var(--apple-shadow-sm);">
      <span style="width: 26px; height: 26px; border-radius: 50%; background: #EF4444; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">!</span>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <div class="grid apple-post-grid" style="grid-template-columns: 1fr 340px; gap: 28px; align-items: start;">
    
    <!-- Main Form Column -->
    <main class="apple-pro-card" style="padding: 36px;">
      <form method="POST" action="<?php echo BASE_URL; ?>/post-job.php">
        
        <!-- Section 1: Role & Department -->
        <section style="margin-bottom: 36px; border-bottom: 1px solid var(--apple-border); padding-bottom: 28px;">
          <div class="apple-step-pill">Step 01</div>
          <h3 style="font-size: 1.35rem; color: var(--apple-text-primary); margin: 0 0 18px 0; font-weight: 800; letter-spacing: -0.02em;">Role & Clinical Department</h3>
          
          <div class="form-group" style="margin-bottom: 18px;">
            <label class="apple-form-label" for="job_title">Job Title *</label>
            <input type="text" id="job_title" name="title" class="apple-form-control" placeholder="e.g. Senior Consultant – Interventional Cardiology" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            <span class="apple-form-hint">Specify exact clinical designation or consultant rank</span>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
            <div class="form-group">
              <label class="apple-form-label" for="job_dept">Healthcare Department *</label>
              <select id="job_dept" name="department" class="apple-form-control" required>
                <option value="">Select Department</option>
                <?php foreach ($healthcareDepartments as $k => $d): ?>
                  <option value="<?php echo $k; ?>" <?php echo (($_POST['department'] ?? '') === $k) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($d['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="apple-form-label" for="job_spec">Clinical Specialty / Subspecialty *</label>
              <input type="text" id="job_spec" name="specialty" class="apple-form-control" placeholder="e.g. Cath Lab, Critical Care, Radiology" required value="<?php echo htmlspecialchars($_POST['specialty'] ?? ''); ?>">
            </div>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 18px;">
            <div class="form-group">
              <label class="apple-form-label" for="job_emp_type">Employment Type *</label>
              <select id="job_emp_type" name="employment_type" class="apple-form-control" required>
                <option value="Full Time · On-Site">Full Time · On-Site</option>
                <option value="Rotational Shifts (ICU/Ward)">Rotational Shifts (ICU/Ward)</option>
                <option value="Visiting Consultant / On-Call">Visiting Consultant / On-Call</option>
                <option value="Clinical Fellowship / Registrar">Clinical Fellowship / Registrar</option>
                <option value="Locum Tenens">Locum Tenens</option>
              </select>
            </div>
            <div class="form-group">
              <label class="apple-form-label" for="job_loc">Hospital Location / Campus *</label>
              <input type="text" id="job_loc" name="location" class="apple-form-control" placeholder="e.g. Kochi, Kerala, India" required value="<?php echo htmlspecialchars($_POST['location'] ?? ($activeUser['location'] ?? '')); ?>">
            </div>
          </div>
        </section>

        <!-- Section 2: Clinical Council Credentials Required -->
        <section style="margin-bottom: 36px; border-bottom: 1px solid var(--apple-border); padding-bottom: 28px;">
          <div class="apple-step-pill">Step 02</div>
          <h3 style="font-size: 1.35rem; color: var(--apple-text-primary); margin: 0 0 18px 0; font-weight: 800; letter-spacing: -0.02em;">Mandatory Council & Education Criteria</h3>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
            <div class="form-group">
              <label class="apple-form-label" for="job_council">Mandatory Council Registration *</label>
              <input type="text" id="job_council" name="council_req" class="apple-form-control" placeholder="e.g. TCMC / NMC / State Nursing Council / DHA" required value="<?php echo htmlspecialchars($_POST['council_req'] ?? 'Travancore-Cochin Medical Council / NMC Active'); ?>">
              <span class="apple-form-hint">Candidates without this license will be verified before shortlisting</span>
            </div>
            <div class="form-group">
              <label class="apple-form-label" for="job_qual">Minimum Educational Degree *</label>
              <input type="text" id="job_qual" name="qualification" class="apple-form-control" placeholder="e.g. MBBS, MD, DM or B.Sc Nursing" required value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>">
            </div>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 18px;">
            <div class="form-group">
              <label class="apple-form-label" for="job_exp">Minimum Post-Qualification Experience *</label>
              <select id="job_exp" name="experience" class="apple-form-control" required>
                <option value="Fresher / Intern">Fresher / Intern</option>
                <option value="1 - 3 Years">1 - 3 Years</option>
                <option value="4 - 7 Years (Specialist)">4 - 7 Years (Specialist)</option>
                <option value="8+ Years (Senior Consultant)" selected>8+ Years (Senior Consultant)</option>
              </select>
            </div>
            <div class="form-group">
              <label class="apple-form-label" for="job_urgent">Hiring Priority</label>
              <select id="job_urgent" name="urgent" class="apple-form-control">
                <option value="0">Standard Hiring</option>
                <option value="1">Urgent Opening (Highlighted Gold Badge)</option>
              </select>
            </div>
          </div>
        </section>

        <!-- Section 3: Compensation & Scope -->
        <section style="margin-bottom: 32px; border-bottom: 1px solid var(--apple-border); padding-bottom: 28px;">
          <div class="apple-step-pill">Step 03</div>
          <h3 style="font-size: 1.35rem; color: var(--apple-text-primary); margin: 0 0 18px 0; font-weight: 800; letter-spacing: -0.02em;">Compensation & Scope</h3>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
            <div class="form-group">
              <label class="apple-form-label" for="job_salary">Salary Range / Monthly Compensation *</label>
              <input type="text" id="job_salary" name="salary" class="apple-form-control" placeholder="e.g. ₹36,00,000 - ₹48,00,000 / year or AED 50,000 / mo" required value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label class="apple-form-label" for="job_sal_vis">Salary Visibility</label>
              <select id="job_sal_vis" name="salary_visibility" class="apple-form-control">
                <option value="public">Show salary publicly to attract verified specialists</option>
                <option value="confidential">Show 'Competitive / Confidential'</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="apple-form-label" for="job_desc">Role Description & Key Clinical Scope *</label>
            <textarea id="job_desc" name="description" class="apple-form-control" rows="5" placeholder="Detail surgical throughput, bed count, equipment, clinical protocols, call schedules, and JCI compliance..." required style="line-height: 1.5;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
          </div>
        </section>

        <!-- Apple WhatsApp Notification Card -->
        <div style="background: rgba(37, 211, 102, 0.08); border: 1px solid rgba(37, 211, 102, 0.25); padding: 20px 24px; border-radius: 18px; margin-bottom: 32px; display: flex; align-items: flex-start; gap: 16px;">
          <div style="width: 40px; height: 40px; border-radius: 12px; background: #25D366; color: #FFFFFF; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 3px 10px rgba(37, 211, 102, 0.3);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
          </div>
          <div>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: #065F46; font-size: 0.95rem; margin: 0;">
              <input type="checkbox" checked name="broadcast_whatsapp" value="1" style="width: 17px; height: 17px; accent-color: #25D366;">
              <span>Broadcast instantly to WhatsApp Alert Subscribers (Verified Clinicians)</span>
            </label>
            <div style="font-size: 0.82rem; color: #047857; margin-top: 4px; line-height: 1.4;">
              Directly alerts qualified doctors and nurses who match your council license and department specialty.
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 14px;">
          <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" class="apple-pill-btn apple-pill-btn-outline" style="padding: 12px 24px;">Cancel</a>
          <button type="submit" class="apple-pill-btn apple-pill-btn-primary" style="padding: 12px 28px; font-weight: 700; font-size: 0.94rem;">
            <span>Publish Vacancy Now</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
        </div>

      </form>
    </main>

    <!-- Right Sidebar Column -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- Obsidian Titanium Standards Widget -->
      <div class="apple-titanium-widget">
        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.15); padding: 4px 12px; border-radius: 9999px; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: #38BDF8; font-weight: 700; margin-bottom: 14px;">
          <span>✦ Clinical Governance</span>
        </div>
        <h4 style="color: #FFFFFF; font-size: 1.22rem; margin: 0 0 10px 0; font-weight: 800; font-family: var(--apple-font); letter-spacing: -0.02em;">
          Healthcare Posting Standards
        </h4>
        <p style="color: #CBD5E1; font-size: 0.88rem; line-height: 1.5; margin: 0 0 18px 0;">
          All clinical openings are instantly published under your accredited organization profile. Mandatory council registrations are cross-referenced on all applications.
        </p>
        <div style="background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); padding: 12px 16px; border-radius: 14px; font-size: 0.82rem; color: #94A3B8;">
          <strong style="color: #FFFFFF; display: block; margin-bottom: 2px;">Direct Verified Hospital:</strong>
          <span><?php echo htmlspecialchars($hospitalName); ?></span>
        </div>
      </div>

      <!-- Quick Guidance Card -->
      <div class="apple-pro-card" style="padding: 24px;">
        <h4 style="font-size: 1.05rem; font-weight: 800; color: var(--apple-text-primary); margin: 0 0 12px 0;">
          Recruiter Best Practices
        </h4>
        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.86rem; color: var(--apple-text-secondary); line-height: 1.45;">
          <div style="display: flex; gap: 10px;">
            <span style="color: var(--apple-blue); font-weight: 700;">✓</span>
            <span>Specify council numbers (TCMC, NMC, KNC) to filter unverified applicants.</span>
          </div>
          <div style="display: flex; gap: 10px;">
            <span style="color: var(--apple-blue); font-weight: 700;">✓</span>
            <span>Include surgical throughput, bed count, and ICU tier for specialist clarity.</span>
          </div>
          <div style="display: flex; gap: 10px;">
            <span style="color: var(--apple-blue); font-weight: 700;">✓</span>
            <span>Enable WhatsApp broadcasts for instant alerts to accredited doctors.</span>
          </div>
        </div>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
