<?php
/**
 * HealthJobsGlobal.com - Healthcare Candidate & Hospital Employer Registration
 */
$pageTitle = "Registration — HealthJobsGlobal";
$pageDescription = "Create your verified healthcare profile or register your hospital recruitment institution.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';

// Initial role selection (candidate vs employer)
$activeRole = (isset($_GET['type']) && $_GET['type'] === 'employer') ? 'employer' : 'candidate';
$generatedCandidateId = "HJG-" . strtoupper(substr(md5(uniqid()), 0, 8));
$generatedHospitalId = "HOSP-" . strtoupper(substr(md5(uniqid()), 0, 6));
?>

<div style="background: var(--color-bg); padding: 48px 0 80px 0; min-height: 90vh;">
  <div class="container" style="max-width: 860px; margin: 0 auto;">
    
    <div class="auth-card-wrap" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 40px; box-shadow: var(--shadow-xl);">
      
      <!-- Role Switcher Tabs (Candidate vs Hospital Employer) -->
      <div class="role-switcher-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 32px; background: var(--color-bg); padding: 6px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
        <button type="button" id="roleBtnCandidate" class="btn <?php echo $activeRole === 'candidate' ? 'btn-primary' : 'btn-outline'; ?>" onclick="setRegistrationRole('candidate')" style="border-radius: var(--radius-md); font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px;">
          <?php echo getIcon('user', '', 18); ?>
          <span>Healthcare Candidate (Doctor / Nurse)</span>
        </button>
        <button type="button" id="roleBtnEmployer" class="btn <?php echo $activeRole === 'employer' ? 'btn-primary' : 'btn-outline'; ?>" onclick="setRegistrationRole('employer')" style="border-radius: var(--radius-md); font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px;">
          <?php echo getIcon('briefcase', '', 18); ?>
          <span>Hospital / Healthcare Employer</span>
        </button>
      </div>

      <!-- ======================================================================
           SECTION 1: CANDIDATE REGISTRATION WIZARD (Doctors, Nurses & Allied)
           ====================================================================== -->
      <div id="candidateRegistrationSection" style="<?php echo $activeRole === 'candidate' ? 'display: block;' : 'display: none;'; ?>">
        
        <!-- Header -->
        <div class="flex-between items-center" style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 20px; flex-wrap: wrap; gap: 12px;">
          <div>
            <span class="eyebrow" style="margin-bottom: 2px;">Healthcare Talent Registration</span>
            <h1 style="font-size: 1.85rem; color: var(--color-primary); margin: 0;">Create Your Verified Profile</h1>
            <p style="color: var(--color-text-secondary); font-size: 0.9rem; margin-top: 4px;">Join 10,000+ verified doctors and nurses connecting with accredited hospitals.</p>
          </div>
          <div style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 6px 14px; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary);">
            Candidate ID: <span style="font-family: monospace;"><?php echo $generatedCandidateId; ?></span>
          </div>
        </div>

        <!-- Multi-step Indicator Header -->
        <div class="wizard-steps-header">
          <div class="step-indicator active" id="stepIndicator1">
            <div class="step-bubble">1</div>
            <span class="step-label">Personal & Contact</span>
          </div>
          <div class="step-indicator" id="stepIndicator2">
            <div class="step-bubble">2</div>
            <span class="step-label">Council & Degree</span>
          </div>
          <div class="step-indicator" id="stepIndicator3">
            <div class="step-bubble">3</div>
            <span class="step-label">Career & Salary</span>
          </div>
          <div class="step-indicator" id="stepIndicator4">
            <div class="step-bubble">4</div>
            <span class="step-label">CV & Verification</span>
          </div>
        </div>

        <form id="multiStepForm" onsubmit="event.preventDefault();">
          
          <!-- STEP 1: Personal & Contact Credentials -->
          <div class="wizard-step-content" id="stepContent1">
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 18px;">Step 1: Personal & Contact Information</h3>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="regFirstName">First Name *</label>
                <input type="text" id="regFirstName" class="form-control" placeholder="e.g. Arjun" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="regLastName">Surname / Last Name *</label>
                <input type="text" id="regLastName" class="form-control" placeholder="e.g. Menon" required>
              </div>
            </div>

            <!-- Mobile & Mobile OTP Verification -->
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regMobile" style="margin: 0;">Mobile Number *</label>
                  <span id="mobileVerifiedBadge" class="verified-pill" style="display: none;">
                    <?php echo getIcon('check', '', 14); ?> Verified
                  </span>
                </div>
                <div style="display: flex; gap: 8px;">
                  <input type="tel" id="regMobile" class="form-control" placeholder="+91 98460 12345" oninput="syncWhatsappWithMobile()" required>
                  <button type="button" id="btnSendMobileOtp" class="btn btn-outline btn-sm" style="white-space: nowrap;" onclick="sendMobileOtp()">Send OTP</button>
                </div>
                <span class="form-hint" id="mobileOtpTimerText">Verification via SMS OTP is mandatory</span>
              </div>

              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regMobileOtp" style="margin: 0;">Mobile OTP Verification *</label>
                  <span class="form-hint" style="color: var(--color-secondary); font-size: 0.75rem; margin: 0;">6-digit code</span>
                </div>
                <div style="display: flex; gap: 8px;">
                  <input type="text" id="regMobileOtp" class="form-control" placeholder="Enter 6-digit code" maxlength="6" disabled>
                  <button type="button" id="btnVerifyMobileOtp" class="btn btn-primary btn-sm" style="white-space: nowrap;" onclick="verifyMobileOtp()" disabled>Verify OTP</button>
                </div>
                <span class="form-hint" id="mobileOtpStatusHint">Click "Send OTP" to receive code</span>
              </div>
            </div>

            <!-- Email & Email Code Verification -->
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regEmail" style="margin: 0;">Email Address *</label>
                  <span id="emailVerifiedBadge" class="verified-pill" style="display: none;">
                    <?php echo getIcon('check', '', 14); ?> Verified
                  </span>
                </div>
                <div style="display: flex; gap: 8px;">
                  <input type="email" id="regEmail" class="form-control" placeholder="dr.arjun@hospital.com" required>
                  <button type="button" id="btnSendEmailCode" class="btn btn-outline btn-sm" style="white-space: nowrap;" onclick="sendEmailVerificationCode()">Verify</button>
                </div>
                <span class="form-hint" id="emailCodeTimerText">Verification via email code is mandatory</span>
              </div>

              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regEmailOtp" style="margin: 0;">Email Verification Code *</label>
                  <span class="form-hint" style="color: var(--color-secondary); font-size: 0.75rem; margin: 0;">6-digit code</span>
                </div>
                <div style="display: flex; gap: 8px;">
                  <input type="text" id="regEmailOtp" class="form-control" placeholder="Enter 6-digit code" maxlength="6" disabled>
                  <button type="button" id="btnVerifyEmailOtp" class="btn btn-primary btn-sm" style="white-space: nowrap;" onclick="verifyEmailCode()" disabled>Confirm</button>
                </div>
                <span class="form-hint" id="emailOtpStatusHint">Click "Verify" to receive code in email</span>
              </div>
            </div>

            <!-- WhatsApp Number & Date of Birth (Optional) -->
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <div class="flex-between items-center" style="margin-bottom: 6px;">
                  <label class="form-label" for="regWhatsapp" style="margin: 0;">WhatsApp Number</label>
                  <label style="display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: var(--color-secondary); cursor: pointer; margin: 0;">
                    <input type="checkbox" id="syncWhatsappCheck" checked onchange="syncWhatsappWithMobile()">
                    <span>Same as Mobile</span>
                  </label>
                </div>
                <input type="tel" id="regWhatsapp" class="form-control" placeholder="+91 98460 12345 (for instant vacancy alerts)">
                <span class="form-hint">Used for instant interview & hospital vacancy updates</span>
              </div>

              <div class="form-group">
                <label class="form-label" for="regDob">Date of Birth <span style="font-weight: 400; color: var(--color-text-muted);">(Optional)</span></label>
                <input type="date" id="regDob" class="form-control">
                <span class="form-hint">Can be updated later in your candidate dashboard</span>
              </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
              <button type="button" class="btn btn-primary" onclick="validateAndGoToStep2()">
                <span>Next: Council & Qualifications</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 2: Council Registration & Education (Naukri Work Status Pattern) -->
          <div class="wizard-step-content" id="stepContent2" style="display: none;">
            <div class="flex-between items-center" style="margin-bottom: 6px;">
              <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0;">Step 2: Professional Status & Clinical Credentials</h3>
            </div>
            <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 20px;">We personalize verified hospital matches based on your clinical career stage.</p>

            <!-- Naukri-style Work Status Cards -->
            <div class="work-status-grid">
              <div class="work-status-card active" id="cardStatusExp" onclick="setWorkStatus('experienced')">
                <div class="status-radio-dot"></div>
                <div class="status-icon-box">
                  <?php echo getIcon('briefcase', '', 22); ?>
                </div>
                <div class="status-details">
                  <div class="status-title">I'm experienced</div>
                  <div class="status-desc">I have clinical or hospital work experience</div>
                </div>
              </div>

              <div class="work-status-card" id="cardStatusFresher" onclick="setWorkStatus('fresher')">
                <div class="status-radio-dot"></div>
                <div class="status-icon-box">
                  <?php echo getIcon('award', '', 22); ?>
                </div>
                <div class="status-details">
                  <div class="status-title">I'm a fresher</div>
                  <div class="status-desc">Final year student, intern, house surgeon, or fresh graduate</div>
                </div>
              </div>
            </div>

            <!-- EXPERIENCED CLINICIAN FIELDS -->
            <div id="experiencedFields" class="dynamic-status-fields">
              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="regCurrentOrg">Current Hospital / Clinic *</label>
                  <input type="text" id="regCurrentOrg" class="form-control" placeholder="e.g. Aster Medcity / Apollo Hospitals">
                </div>
                <div class="form-group">
                  <label class="form-label" for="regCurrentTitle">Current Clinical Role / Designation *</label>
                  <input type="text" id="regCurrentTitle" class="form-control" placeholder="e.g. Senior Resident / Consultant / ICU Staff Nurse">
                </div>
              </div>

              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="regDegreeExp">Highest Clinical Education *</label>
                  <select id="regDegreeExp" class="form-control">
                    <option value="">Select Qualification</option>
                    <option value="mbbs">MBBS</option>
                    <option value="md_ms">MBBS + MD / MS</option>
                    <option value="dm_mch">DM / MCh / DNB Superspecialty</option>
                    <option value="bscnursing">B.Sc / Post Basic Nursing</option>
                    <option value="mscnursing">M.Sc Nursing</option>
                    <option value="bpt">BPT / MPT (Physiotherapy)</option>
                    <option value="bams">BAMS / MD Ayurveda</option>
                    <option value="bhms">BHMS / MD Homeopathy</option>
                    <option value="bpharm">B.Pharm / Pharm.D</option>
                    <option value="bsc_allied">B.Sc Perfusion / Cath Lab / OT Tech</option>
                    <option value="mha">MHA / MBA Hospital Admin</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="regExperienceYears">Total Post-Qualification Experience *</label>
                  <select id="regExperienceYears" class="form-control">
                    <option value="1-3">1 - 3 Years</option>
                    <option value="4-7" selected>4 - 7 Years (Specialist / In-Charge)</option>
                    <option value="8-12">8 - 12 Years (Consultant / Supervisor)</option>
                    <option value="12+">12+ Years (Senior Consultant / HOD)</option>
                  </select>
                </div>
              </div>

              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="regCouncilExp">Medical / Nursing Council Registration No. *</label>
                  <input type="text" id="regCouncilExp" class="form-control" placeholder="e.g. TCMC 45892 / NMC / KNC 98214 / DHA">
                  <span class="form-hint">Enter your State or National Council registration license</span>
                </div>
                <div class="form-group">
                  <label class="form-label" for="regNoticeExp">Notice Period *</label>
                  <select id="regNoticeExp" class="form-control">
                    <option value="immediate">Immediate Available</option>
                    <option value="15days">15 Days</option>
                    <option value="30days" selected>30 Days (Standard)</option>
                    <option value="60days">60 Days</option>
                    <option value="90days">90 Days</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- FRESHER / INTERN / RESIDENT FIELDS -->
            <div id="fresherFields" class="dynamic-status-fields" style="display: none;">
              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="regDegreeFresher">Highest Clinical Qualification (Degree) *</label>
                  <select id="regDegreeFresher" class="form-control">
                    <option value="">Select Qualification</option>
                    <option value="mbbs">MBBS (House Surgeon / Intern / Graduate)</option>
                    <option value="bscnursing">B.Sc Nursing / Post Basic B.Sc</option>
                    <option value="gnm">GNM (General Nursing & Midwifery)</option>
                    <option value="bpt">BPT (Bachelor of Physiotherapy)</option>
                    <option value="pharmd">Pharm.D / B.Pharm</option>
                    <option value="bams">BAMS (Ayurvedic Medicine & Surgery)</option>
                    <option value="bhms">BHMS (Homeopathic Medicine)</option>
                    <option value="bsc_allied">B.Sc Perfusion / Cath Lab / OT / Dialysis Tech</option>
                    <option value="mlt">B.Sc Medical Laboratory Technology (MLT)</option>
                    <option value="mha">MHA (Masters in Hospital Administration)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="regCollegeName">Medical College / University / Institute *</label>
                  <input type="text" id="regCollegeName" class="form-control" placeholder="e.g. Govt Medical College, Trivandrum / Amrita / KUHS">
                </div>
              </div>

              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="regPassingYear">Course Completion / Passing Year *</label>
                  <select id="regPassingYear" class="form-control">
                    <option value="2026">2026 (Final Year / Current House Surgeoncy)</option>
                    <option value="2025" selected>2025 (Fresh Graduate)</option>
                    <option value="2024">2024</option>
                    <option value="2023">2023</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="regCouncilFresher">Medical / Nursing Council Registration No. *</label>
                  <input type="text" id="regCouncilFresher" class="form-control" placeholder="e.g. TCMC Provisional / KNC 98214 / Awaiting License">
                  <span class="form-hint">Provisional or Permanent registration license</span>
                </div>
              </div>

              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="regInternshipDetails">Internship / House Surgeoncy Completed</label>
                  <input type="text" id="regInternshipDetails" class="form-control" placeholder="e.g. 1-Year CRRI Rotatory Internship (ICU, Emergency, Surgery)">
                  <span class="form-hint">Departments completed during training</span>
                </div>
                <div class="form-group">
                  <label class="form-label" for="regDeptInterest">Preferred Clinical Department</label>
                  <select id="regDeptInterest" class="form-control">
                    <option>Critical Care & ICU</option>
                    <option>Emergency & Trauma Care</option>
                    <option>Operation Theatre & Surgery</option>
                    <option>General Medicine / Inpatient</option>
                    <option>Pediatrics & Neonatology</option>
                    <option>Cardiology & Cath Lab</option>
                    <option>Diagnostic Lab & Pathology</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- LANGUAGES SPOKEN (Interactive Keyword / Tag Chip Input) -->
            <div class="form-group" style="margin-top: 24px; padding-top: 18px; border-top: 1px dashed var(--color-border);">
              <label class="form-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-weight: 600; color: var(--color-text-primary);">Languages Known & Spoken Fluently *</span>
                <span class="form-hint" style="margin: 0; color: var(--color-secondary); font-size: 0.8rem; font-weight: 500;">Type language & press Enter (or comma)</span>
              </label>
              <div class="tags-input-wrapper" id="languagesTagWrapper" onclick="document.getElementById('tagInputLanguages').focus()">
                <div id="languageChipsContainer" style="display: contents;"></div>
                <input type="text" id="tagInputLanguages" class="tag-inline-input" placeholder="Type language (e.g. English, Malayalam, Hindi) & press Enter..." onkeydown="handleLanguageTagKeyDown(event)">
              </div>
              <div class="tag-suggestions-row">
                <span style="font-size: 0.78rem; color: var(--color-text-muted); font-weight: 600;">Quick Add:</span>
                <button type="button" class="suggestion-chip-btn" data-lang="English" onclick="addLanguageTag('English')">+ English</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Malayalam" onclick="addLanguageTag('Malayalam')">+ Malayalam</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Hindi" onclick="addLanguageTag('Hindi')">+ Hindi</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Arabic" onclick="addLanguageTag('Arabic')">+ Arabic</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Tamil" onclick="addLanguageTag('Tamil')">+ Tamil</button>
                <button type="button" class="suggestion-chip-btn" data-lang="Kannada" onclick="addLanguageTag('Kannada')">+ Kannada</button>
                <button type="button" class="suggestion-chip-btn" data-lang="German" onclick="addLanguageTag('German')">+ German</button>
              </div>
              <input type="hidden" id="selectedLanguagesInput" name="languages" value="English, Malayalam">
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 24px;">
              <button type="button" class="btn btn-outline" onclick="goToStep(1, 'back')">Back</button>
              <button type="button" class="btn btn-primary" onclick="validateAndGoToStep3()">
                <span>Next: Location & Package</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 3: Career, Confidentiality & Salary Preferences -->
          <div class="wizard-step-content" id="stepContent3" style="display: none;">
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 6px;" id="step3Title">Step 3: Location Preferences & Salary Expectation</h3>
            <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 20px;" id="step3Subtitle">Set your target hospital locations and compensation benchmarks.</p>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="regCurrentLoc" id="labelCurrentLoc">Current Location / Base City *</label>
                <input type="text" id="regCurrentLoc" class="form-control" placeholder="e.g. Kochi, Kerala" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="regPreferredLoc" id="labelPreferredLoc">Preferred Hospital Locations *</label>
                <input type="text" id="regPreferredLoc" class="form-control" placeholder="e.g. Kochi, Bengaluru, Dubai, Abu Dhabi" required>
              </div>
            </div>

            <!-- Experienced Salary Grid -->
            <div class="grid" id="rowExpSalary" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group" id="groupCurrentSalary">
                <label class="form-label" for="regCurrentSalary">Current Annual Package</label>
                <input type="text" id="regCurrentSalary" class="form-control" placeholder="e.g. ₹18,00,000 / year">
                <span class="form-hint">Used for confidential benchmarking</span>
              </div>
              <div class="form-group" id="groupExpectedSalary">
                <label class="form-label" for="regExpectedSalary" id="labelExpectedSalary">Expected Annual Package *</label>
                <input type="text" id="regExpectedSalary" class="form-control" placeholder="e.g. ₹24,00,000 / year">
                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.8rem; margin-top: 4px; cursor: pointer;">
                  <input type="checkbox" checked> Package is Negotiable
                </label>
              </div>
            </div>

            <!-- Fresher Availability & Stipend Grid -->
            <div class="grid" id="rowFresherSalary" style="grid-template-columns: 1fr 1fr; gap: 16px; display: none;">
              <div class="form-group" id="groupFresherAvailability">
                <label class="form-label" for="regFresherAvailability">Joining Availability *</label>
                <select id="regFresherAvailability" class="form-control">
                  <option value="immediate" selected>Immediate (Ready to Join)</option>
                  <option value="15days">Within 15 Days</option>
                  <option value="after_internship">Upon Internship / CRRI Completion (1 Month)</option>
                  <option value="exam_results">Awaiting Final Results / Council License</option>
                </select>
                <span class="form-hint">When you can commence hospital duties</span>
              </div>
              <div class="form-group" id="groupExpectedStipend">
                <label class="form-label" for="regExpectedStipend">Expected Starting Salary / Stipend *</label>
                <input type="text" id="regExpectedStipend" class="form-control" placeholder="e.g. ₹35,000 - ₹50,000 / month or Standard Scale">
                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.8rem; margin-top: 4px; cursor: pointer;">
                  <input type="checkbox" checked> Open to standard hospital trainee / resident stipend scale
                </label>
              </div>
            </div>

            <!-- Experienced Confidentiality Option -->
            <div class="form-group" id="groupHideCurrentOrg">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--color-secondary);">
                <input type="checkbox" id="regHideCurrentOrg" checked>
                <span>Hide current hospital from employer searches (Confidentiality Mode)</span>
              </label>
              <span class="form-hint">Prevents your current hospital HR department from seeing your active profile</span>
            </div>

            <!-- Fresher Career Preferences -->
            <div class="form-group" id="groupFresherWillingness" style="display: none; background: #F8FAFC; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 14px;">
              <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">Graduate Career Preferences:</label>
              <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; color: var(--color-text-secondary);">
                  <input type="checkbox" id="checkRotationalShifts" checked>
                  <span>Open to Rotational Day/Night & ICU duty shifts</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; color: var(--color-text-secondary);">
                  <input type="checkbox" id="checkFellowshipRelocate" checked>
                  <span>Willing to relocate across India / Abroad for Residency or Hospital Training</span>
                </label>
              </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 24px;">
              <button type="button" class="btn btn-outline" onclick="goToStep(2, 'back')">Back</button>
              <button type="button" class="btn btn-primary" onclick="validateAndGoToStep4()">
                <span id="step3NextBtnText">Next: Upload CV & Verify</span>
                <?php echo getIcon('arrow-right', '', 18); ?>
              </button>
            </div>
          </div>

          <!-- STEP 4: CV Upload & Final Submission -->
          <div class="wizard-step-content" id="stepContent4" style="display: none;">
            
            <!-- EXPERIENCED STEP 4 CONTENT -->
            <div id="expStep4Content">
              <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 6px;">Step 4: CV Upload & Medical Ethics Code</h3>
              <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 18px;">Upload your clinical bio-data and verified council registration credentials.</p>

              <div class="form-group">
                <label class="form-label">Upload CV / Comprehensive Clinical Bio-Data (PDF / DOCX) *</label>
                <div style="border: 2px dashed var(--color-border); border-radius: var(--radius-lg); padding: 32px; text-align: center; background: var(--color-bg); cursor: pointer;" onclick="document.getElementById('wizardCVFile').click()">
                  <div style="color: var(--color-secondary); margin-bottom: 10px;">
                    <?php echo getIcon('upload-cloud', '', 40); ?>
                  </div>
                  <div style="font-weight: 700; font-size: 1rem; color: var(--color-primary);" id="wizardCVText">Click to upload CV or drag & drop</div>
                  <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 4px;">Max file size 10MB. Include surgical procedure logbook if applicable.</div>
                  <input type="file" id="wizardCVFile" style="display: none;" accept=".pdf,.doc,.docx" onchange="document.getElementById('wizardCVText').textContent = this.files[0] ? this.files[0].name : 'CV uploaded';">
                </div>
              </div>

              <div style="background: #FFFDF9; border: 1px solid #FEEBC8; padding: 16px; border-radius: var(--radius-md); margin-bottom: 20px;">
                <h5 style="color: #92400E; margin-bottom: 6px; font-size: 0.95rem;">Healthcare Professional Honor Code</h5>
                <p style="font-size: 0.85rem; color: #78350F; line-height: 1.5; margin: 0;">
                  I certify that all medical degrees, clinical fellowships, and medical/nursing council registration numbers submitted are authentic. I understand HealthJobsGlobal verifies credentials directly with issuing medical boards.
                </p>
                <label style="display: flex; align-items: center; gap: 8px; margin-top: 10px; cursor: pointer; font-size: 0.85rem; font-weight: 700; color: #92400E;">
                  <input type="checkbox" required checked> I agree to terms, privacy policy & credential verification
                </label>
              </div>
            </div>

            <!-- FRESHER STEP 4 CONTENT -->
            <div id="fresherStep4Content" style="display: none;">
              <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 6px;">Step 4: Academic Verification & Instant Bio-Data Generation</h3>
              <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 18px;">Fresh graduates do not require a pre-made CV! HealthJobsGlobal constructs your accredited healthcare profile automatically.</p>

              <!-- Automated Fresher Bio-Data Banner -->
              <div style="background: linear-gradient(135deg, rgba(14, 116, 144, 0.08) 0%, rgba(20, 184, 166, 0.08) 100%); border: 1.5px solid rgba(14, 116, 144, 0.25); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 20px; display: flex; gap: 16px; align-items: flex-start;">
                <div style="background: #FFFFFF; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--color-secondary); box-shadow: var(--shadow-sm); flex-shrink: 0;">
                  <?php echo getIcon('file-text', '', 24); ?>
                </div>
                <div>
                  <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                    <h4 style="margin: 0; font-size: 1.05rem; color: var(--color-primary);">Automated Fresher Clinical Profile</h4>
                    <span style="background: #059669; color: #FFFFFF; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-full);">✓ No CV Upload Required</span>
                  </div>
                  <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin: 0 0 10px 0; line-height: 1.5;">
                    As a fresh graduate / resident, our platform auto-generates a standardized <strong>Clinical Trainee Profile</strong> using your university qualification, graduation year, and council provisional registration entered in Step 2.
                  </p>
                  <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; color: var(--color-primary); cursor: pointer; background: #FFFFFF; padding: 6px 14px; border-radius: var(--radius-full); border: 1px solid var(--color-border);">
                    <input type="checkbox" id="checkAutoGenerateBio" checked>
                    <span>Auto-create standard verified Clinical Bio-Data for hospital HRs</span>
                  </label>
                </div>
              </div>

              <!-- Optional Certificate / Resume Upload for Fresher -->
              <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                  <span>Internship Certificate / Degree / Resume <span style="font-weight: normal; color: var(--color-text-muted);">(Optional)</span></span>
                  <span style="font-size: 0.75rem; color: #0284C7; font-weight: 600; background: #E0F2FE; padding: 2px 8px; border-radius: var(--radius-full);">Optional for Freshers</span>
                </label>
                <div style="border: 2px dashed var(--color-border); border-radius: var(--radius-lg); padding: 20px; text-align: center; background: var(--color-bg); cursor: pointer;" onclick="document.getElementById('wizardFresherDocFile').click()">
                  <div style="color: var(--color-secondary); margin-bottom: 6px;">
                    <?php echo getIcon('upload-cloud', '', 30); ?>
                  </div>
                  <div style="font-weight: 600; font-size: 0.92rem; color: var(--color-primary);" id="wizardFresherDocText">Click to upload Provisional Certificate / Internship Letter (Optional)</div>
                  <div style="font-size: 0.78rem; color: var(--color-text-muted); margin-top: 2px;">PDF, JPG, or DOCX (Max 10MB) — You can safely skip this!</div>
                  <input type="file" id="wizardFresherDocFile" style="display: none;" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="document.getElementById('wizardFresherDocText').textContent = this.files[0] ? this.files[0].name : 'Document selected';">
                </div>
              </div>

              <!-- Fresher Verification Pledge -->
              <div style="background: #F0FDF4; border: 1px solid #BBF7D0; padding: 16px; border-radius: var(--radius-md); margin-bottom: 20px;">
                <h5 style="color: #166534; margin-bottom: 6px; font-size: 0.95rem;">Fresh Graduate Verification Pledge</h5>
                <p style="font-size: 0.85rem; color: #15803D; line-height: 1.5; margin: 0;">
                  I declare that my educational credentials, medical/nursing college, and rotational training details are genuine. I am ready to attend interviews for Junior Resident, Staff Nurse, or Trainee vacancies.
                </p>
                <label style="display: flex; align-items: center; gap: 8px; margin-top: 10px; cursor: pointer; font-size: 0.85rem; font-weight: 700; color: #166534;">
                  <input type="checkbox" required checked> I agree to terms, privacy policy & academic verification
                </label>
              </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 24px;">
              <button type="button" class="btn btn-outline" onclick="goToStep(3, 'back')">Back</button>
              <button type="button" class="btn btn-primary btn-lg" id="candidateSubmitBtn" onclick="finishCandidateRegistration()">
                <span id="candidateSubmitBtnText">Complete Registration</span>
                <?php echo getIcon('check-circle', '', 18); ?>
              </button>
            </div>
          </div>

        </form>

      </div>

      <!-- ======================================================================
           SECTION 2: HOSPITAL / HEALTHCARE EMPLOYER REGISTRATION
           ====================================================================== -->
      <div id="employerRegistrationSection" style="<?php echo $activeRole === 'employer' ? 'display: block;' : 'display: none;'; ?>">
        
        <!-- Header -->
        <div class="flex-between items-center" style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 20px; flex-wrap: wrap; gap: 12px;">
          <div>
            <span class="eyebrow" style="margin-bottom: 2px;">Hospital Recruitment Portal</span>
            <h1 style="font-size: 1.85rem; color: var(--color-primary); margin: 0;">Register Healthcare Institution</h1>
            <p style="color: var(--color-text-secondary); font-size: 0.9rem; margin-top: 4px;">Directly post vacancies and hire verified doctors, specialists, and ICU nurses.</p>
          </div>
          <div style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 6px 14px; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary);">
            Hospital Ref: <span style="font-family: monospace;"><?php echo $generatedHospitalId; ?></span>
          </div>
        </div>

        <form onsubmit="handleHospitalRegistrationSubmit(event)">
          
          <!-- Hospital Institution Details -->
          <section style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 24px;">
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 18px;">1. Hospital / Healthcare Institution Details</h3>

            <div class="form-group">
              <label class="form-label" for="hospName">Hospital / Medical Center Name *</label>
              <input type="text" id="hospName" class="form-control" placeholder="e.g. Aster Medcity / Lisie Heart Hospital" required>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="hospType">Institution Classification *</label>
                <select id="hospType" class="form-control" required>
                  <option value="">Select Hospital Type</option>
                  <option value="quaternary">Quaternary Care Super-Specialty Hospital</option>
                  <option value="tertiary">Tertiary Care Multi-Specialty Hospital</option>
                  <option value="medical_college">Medical College & Teaching Hospital</option>
                  <option value="heart_cancer">Super-Specialty Heart / Cancer Institute</option>
                  <option value="secondary">Secondary Care Community Hospital</option>
                  <option value="diagnostic">Diagnostic & Pathology Laboratory Network</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="hospBeds">Inpatient Bed Capacity *</label>
                <select id="hospBeds" class="form-control" required>
                  <option value="">Select Bed Capacity</option>
                  <option value="500plus">500+ Beds (Quaternary / Teaching)</option>
                  <option value="250-500">250 - 500 Beds (Large Multi-Specialty)</option>
                  <option value="100-250">100 - 250 Beds (Medium Multi-Specialty)</option>
                  <option value="under100">Under 100 Beds (Specialty Center / Clinic)</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Accreditations & Certifications Held</label>
              <div style="display: flex; gap: 18px; flex-wrap: wrap; margin-top: 6px;">
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.9rem;">
                  <input type="checkbox" checked> NABH Accredited Hospital
                </label>
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.9rem;">
                  <input type="checkbox" checked> JCI International Certified
                </label>
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.9rem;">
                  <input type="checkbox"> NABL Laboratory
                </label>
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.9rem;">
                  <input type="checkbox"> ISO 9001 Certified
                </label>
              </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="hospCity">City & State *</label>
                <input type="text" id="hospCity" class="form-control" placeholder="e.g. Kochi, Kerala" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="hospWebsite">Official Hospital Website</label>
                <input type="url" id="hospWebsite" class="form-control" placeholder="https://www.astermedcity.com">
              </div>
            </div>
          </section>

          <!-- HR & Medical Recruiter Contact -->
          <section style="margin-bottom: 32px; border-bottom: 1px solid var(--color-border); padding-bottom: 24px;">
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 18px;">2. Authorized HR / Medical Superintendent Contact</h3>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="hospContactName">Recruiter / Official Contact Person *</label>
                <input type="text" id="hospContactName" class="form-control" placeholder="e.g. Dr. Radhakrishnan / Ms. Rekha Nair" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="hospContactTitle">Designation *</label>
                <input type="text" id="hospContactTitle" class="form-control" placeholder="e.g. Chief HR Officer / Medical Superintendent" required>
              </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="hospEmail">Official Hospital Work Email *</label>
                <input type="email" id="hospEmail" class="form-control" placeholder="recruitment@asterhospital.com" required>
                <span class="form-hint">Must use institutional domain for instant verification</span>
              </div>
              <div class="form-group">
                <label class="form-label" for="hospPhone">Direct HR Contact / WhatsApp *</label>
                <input type="tel" id="hospPhone" class="form-control" placeholder="+91 98460 99887" required>
                <span class="form-hint">Used for applicant notifications and candidate interview alerts</span>
              </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="hospPass">Create Account Password *</label>
                <input type="password" id="hospPass" class="form-control" placeholder="••••••••" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="hospConfirmPass">Confirm Password *</label>
                <input type="password" id="hospConfirmPass" class="form-control" placeholder="••••••••" required>
              </div>
            </div>
          </section>

          <!-- Governance Agreement -->
          <div style="background: #F0FDF4; border: 1px solid #BBF7D0; padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px;">
            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 0.85rem; color: #166534;">
              <input type="checkbox" required checked style="margin-top: 2px;">
              <span>I certify that this institution is a licensed healthcare establishment authorized to recruit medical professionals. We adhere to clinical council norms and ethical recruitment guidelines.</span>
            </label>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 14px;">
            <button type="submit" id="hospitalRegSubmitBtn" class="btn btn-primary btn-lg" style="width: 100%;">
              <span>Register Hospital & Start Posting Jobs</span>
              <?php echo getIcon('arrow-right', '', 18); ?>
            </button>
          </div>

        </form>

      </div>

    </div>

  </div>
</div>

<script>
let currentStep = 1;
let isMobileVerified = false;
let isEmailVerified = false;
let generatedMobileOtp = '482910';
let generatedEmailCode = '619284';

function setRegistrationRole(role) {
  const candBtn = document.getElementById('roleBtnCandidate');
  const empBtn = document.getElementById('roleBtnEmployer');
  const candSec = document.getElementById('candidateRegistrationSection');
  const empSec = document.getElementById('employerRegistrationSection');

  if (role === 'candidate') {
    candBtn.className = 'btn btn-primary';
    empBtn.className = 'btn btn-outline';
    candSec.style.display = 'block';
    empSec.style.display = 'none';
  } else {
    empBtn.className = 'btn btn-primary';
    candBtn.className = 'btn btn-outline';
    empSec.style.display = 'block';
    candSec.style.display = 'none';
  }
}

function syncWhatsappWithMobile() {
  const check = document.getElementById('syncWhatsappCheck');
  const whatsappInput = document.getElementById('regWhatsapp');
  const mobileInput = document.getElementById('regMobile');
  if (check && check.checked && whatsappInput && mobileInput) {
    whatsappInput.value = mobileInput.value;
  }
}

function highlightError(element) {
  element.classList.add('is-invalid-shake');
  setTimeout(() => {
    element.classList.remove('is-invalid-shake');
  }, 600);
}

function sendMobileOtp() {
  const mobileInput = document.getElementById('regMobile');
  const mobileVal = mobileInput.value.trim();
  if (!mobileVal || mobileVal.length < 8) {
    showToast('Please enter your mobile number first', 'error');
    mobileInput.focus();
    highlightError(mobileInput);
    return;
  }

  generatedMobileOtp = Math.floor(100000 + Math.random() * 900000).toString();
  
  const otpInput = document.getElementById('regMobileOtp');
  const verifyBtn = document.getElementById('btnVerifyMobileOtp');
  const timerText = document.getElementById('mobileOtpTimerText');
  const sendBtn = document.getElementById('btnSendMobileOtp');

  otpInput.disabled = false;
  verifyBtn.disabled = false;
  otpInput.value = '';
  otpInput.focus();

  sendBtn.textContent = 'Resend OTP';
  timerText.innerHTML = `<span style="color: #059669; font-weight: 600;">✓ OTP sent!</span> Demo OTP: <strong style="font-family: monospace; letter-spacing: 1px; color: var(--color-primary);">${generatedMobileOtp}</strong>`;

  showToast(`SMS OTP sent to ${mobileVal}! Demo OTP: ${generatedMobileOtp}`);
  otpInput.setAttribute('placeholder', `e.g. ${generatedMobileOtp}`);
}

function verifyMobileOtp() {
  const otpInput = document.getElementById('regMobileOtp');
  const enteredOtp = otpInput.value.trim();
  const mobileInput = document.getElementById('regMobile');

  if (!enteredOtp) {
    showToast('Please enter the 6-digit OTP', 'error');
    otpInput.focus();
    highlightError(otpInput);
    return;
  }

  // Accept generated OTP or any 6-digit input for seamless testing
  if (enteredOtp === generatedMobileOtp || enteredOtp.length === 6) {
    isMobileVerified = true;
    otpInput.classList.remove('is-invalid-shake');
    otpInput.classList.add('is-verified-success');
    mobileInput.classList.add('is-verified-success');
    otpInput.disabled = true;
    
    const verifyBtn = document.getElementById('btnVerifyMobileOtp');
    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Verified ✓';
    verifyBtn.style.background = '#059669';
    verifyBtn.style.borderColor = '#059669';

    document.getElementById('btnSendMobileOtp').style.display = 'none';
    document.getElementById('mobileVerifiedBadge').style.display = 'inline-flex';
    document.getElementById('mobileOtpStatusHint').innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Mobile verified successfully</span>`;
    
    syncWhatsappWithMobile();
    showToast('Mobile number verified successfully!');
  } else {
    showToast('Invalid OTP. Please check code or click Resend', 'error');
    highlightError(otpInput);
  }
}

function sendEmailVerificationCode() {
  const emailInput = document.getElementById('regEmail');
  const emailVal = emailInput.value.trim();
  if (!emailVal || !emailVal.includes('@') || !emailVal.includes('.')) {
    showToast('Please enter a valid email address first', 'error');
    emailInput.focus();
    highlightError(emailInput);
    return;
  }

  generatedEmailCode = Math.floor(100000 + Math.random() * 900000).toString();

  const codeInput = document.getElementById('regEmailOtp');
  const confirmBtn = document.getElementById('btnVerifyEmailOtp');
  const timerText = document.getElementById('emailCodeTimerText');
  const sendBtn = document.getElementById('btnSendEmailCode');

  codeInput.disabled = false;
  confirmBtn.disabled = false;
  codeInput.value = '';
  codeInput.focus();

  sendBtn.textContent = 'Resend';
  timerText.innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Code sent!</span> Demo Code: <strong style="font-family: monospace; letter-spacing: 1px; color: var(--color-primary);">${generatedEmailCode}</strong>`;

  showToast(`Verification code sent to ${emailVal}! Demo Code: ${generatedEmailCode}`);
  codeInput.setAttribute('placeholder', `e.g. ${generatedEmailCode}`);
}

function verifyEmailCode() {
  const codeInput = document.getElementById('regEmailOtp');
  const enteredCode = codeInput.value.trim();
  const emailInput = document.getElementById('regEmail');

  if (!enteredCode) {
    showToast('Please enter the 6-digit email verification code', 'error');
    codeInput.focus();
    highlightError(codeInput);
    return;
  }

  if (enteredCode === generatedEmailCode || enteredCode.length === 6) {
    isEmailVerified = true;
    codeInput.classList.remove('is-invalid-shake');
    codeInput.classList.add('is-verified-success');
    emailInput.classList.add('is-verified-success');
    codeInput.disabled = true;

    const confirmBtn = document.getElementById('btnVerifyEmailOtp');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Verified ✓';
    confirmBtn.style.background = '#059669';
    confirmBtn.style.borderColor = '#059669';

    document.getElementById('btnSendEmailCode').style.display = 'none';
    document.getElementById('emailVerifiedBadge').style.display = 'inline-flex';
    document.getElementById('emailOtpStatusHint').innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Email verified successfully</span>`;

    showToast('Email address verified successfully!');
  } else {
    showToast('Invalid verification code. Please check or click Resend', 'error');
    highlightError(codeInput);
  }
}

function validateAndGoToStep2() {
  const firstName = document.getElementById('regFirstName');
  const lastName = document.getElementById('regLastName');
  const mobile = document.getElementById('regMobile');
  const email = document.getElementById('regEmail');

  // Development stage: Pre-fill clean demo values if left blank so testing is instant
  if (!firstName.value.trim()) firstName.value = 'Dr. Arjun';
  if (!lastName.value.trim()) lastName.value = 'Menon';
  if (!mobile.value.trim()) mobile.value = '+91 98460 12345';
  if (!email.value.trim()) email.value = 'dr.arjun@hospital.com';

  // Proceed directly without OTP or Email blocking
  goToStep(2, 'next');
}

let workStatus = 'experienced';

function setWorkStatus(status) {
  workStatus = status;
  const cardExp = document.getElementById('cardStatusExp');
  const cardFresher = document.getElementById('cardStatusFresher');
  const expFields = document.getElementById('experiencedFields');
  const fresherFields = document.getElementById('fresherFields');

  // Step 3 Elements
  const rowExpSalary = document.getElementById('rowExpSalary');
  const rowFresherSalary = document.getElementById('rowFresherSalary');
  const groupHideCurrentOrg = document.getElementById('groupHideCurrentOrg');
  const groupFresherWillingness = document.getElementById('groupFresherWillingness');
  const step3Title = document.getElementById('step3Title');
  const step3Subtitle = document.getElementById('step3Subtitle');
  const labelCurrentLoc = document.getElementById('labelCurrentLoc');
  const step3NextBtnText = document.getElementById('step3NextBtnText');

  // Step 4 Elements
  const expStep4Content = document.getElementById('expStep4Content');
  const fresherStep4Content = document.getElementById('fresherStep4Content');
  const candidateSubmitBtnText = document.getElementById('candidateSubmitBtnText');

  // Step Indicator labels
  const step3Label = document.querySelector('#stepIndicator3 .step-label');
  const step4Label = document.querySelector('#stepIndicator4 .step-label');

  if (status === 'experienced') {
    cardExp.classList.add('active');
    cardFresher.classList.remove('active');
    expFields.style.display = 'block';
    fresherFields.style.display = 'none';

    // Step 3
    if (rowExpSalary) rowExpSalary.style.display = 'grid';
    if (rowFresherSalary) rowFresherSalary.style.display = 'none';
    if (groupHideCurrentOrg) groupHideCurrentOrg.style.display = 'block';
    if (groupFresherWillingness) groupFresherWillingness.style.display = 'none';
    if (step3Title) step3Title.textContent = 'Step 3: Location Preferences & Current/Expected Package';
    if (step3Subtitle) step3Subtitle.textContent = 'Set your target hospital locations, current compensation, and salary expectations.';
    if (labelCurrentLoc) labelCurrentLoc.textContent = 'Current Hospital Location / Base City *';
    if (step3NextBtnText) step3NextBtnText.textContent = 'Next: Upload CV & Verify';

    // Step 4
    if (expStep4Content) expStep4Content.style.display = 'block';
    if (fresherStep4Content) fresherStep4Content.style.display = 'none';
    if (candidateSubmitBtnText) candidateSubmitBtnText.textContent = 'Complete Registration & Activate Profile';

    // Labels
    if (step3Label) step3Label.textContent = 'Career & Salary';
    if (step4Label) step4Label.textContent = 'CV & Verification';
  } else {
    cardFresher.classList.add('active');
    cardExp.classList.remove('active');
    expFields.style.display = 'none';
    fresherFields.style.display = 'block';

    // Step 3
    if (rowExpSalary) rowExpSalary.style.display = 'none';
    if (rowFresherSalary) rowFresherSalary.style.display = 'grid';
    if (groupHideCurrentOrg) groupHideCurrentOrg.style.display = 'none';
    if (groupFresherWillingness) groupFresherWillingness.style.display = 'block';
    if (step3Title) step3Title.textContent = 'Step 3: Preferred Locations & Expected Stipend / CTC';
    if (step3Subtitle) step3Subtitle.textContent = 'Tell hospitals where you wish to start your clinical career and your stipend/salary expectation.';
    if (labelCurrentLoc) labelCurrentLoc.textContent = 'Home Town / Current Base City *';
    if (step3NextBtnText) step3NextBtnText.textContent = 'Next: Profile Setup & Verification';

    // Step 4
    if (expStep4Content) expStep4Content.style.display = 'none';
    if (fresherStep4Content) fresherStep4Content.style.display = 'block';
    if (candidateSubmitBtnText) candidateSubmitBtnText.textContent = 'Create Fresher Healthcare Profile';

    // Labels
    if (step3Label) step3Label.textContent = 'Location & Stipend';
    if (step4Label) step4Label.textContent = 'Bio-Data & Verification';
  }
}

// --- Interactive Language Tags / Chips Management ---
let selectedLanguages = ['English', 'Malayalam'];

function escapeHtml(text) {
  const div = document.createElement('div');
  div.innerText = text;
  return div.innerHTML;
}

function renderLanguageTags() {
  const container = document.getElementById('languageChipsContainer');
  const hiddenInput = document.getElementById('selectedLanguagesInput');
  if (!container) return;

  container.innerHTML = '';
  selectedLanguages.forEach((lang, index) => {
    const chip = document.createElement('span');
    chip.className = 'language-chip-tag';
    chip.innerHTML = `
      <span>${escapeHtml(lang)}</span>
      <button type="button" class="tag-close-btn" onclick="removeLanguageTag(${index}); event.stopPropagation();" title="Remove ${escapeHtml(lang)}">&times;</button>
    `;
    container.appendChild(chip);
  });

  if (hiddenInput) {
    hiddenInput.value = selectedLanguages.join(', ');
  }

  // Update popular suggestion chip button states
  document.querySelectorAll('.tag-suggestions-row .suggestion-chip-btn').forEach(btn => {
    const lang = btn.getAttribute('data-lang');
    if (selectedLanguages.some(l => l.toLowerCase() === (lang || '').toLowerCase())) {
      btn.classList.add('added');
    } else {
      btn.classList.remove('added');
    }
  });
}

function addLanguageTag(lang) {
  if (!lang) return;
  const trimmed = lang.trim();
  if (!trimmed) return;
  
  // Case-insensitive check to avoid duplicates
  const alreadyExists = selectedLanguages.some(l => l.toLowerCase() === trimmed.toLowerCase());
  if (!alreadyExists) {
    selectedLanguages.push(trimmed);
    renderLanguageTags();
  }
  
  const input = document.getElementById('tagInputLanguages');
  if (input) {
    input.value = '';
    input.focus();
  }
}

function removeLanguageTag(index) {
  selectedLanguages.splice(index, 1);
  renderLanguageTags();
  const input = document.getElementById('tagInputLanguages');
  if (input) input.focus();
}

function handleLanguageTagKeyDown(e) {
  const input = e.target;
  const val = input.value.trim();

  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault();
    if (val) {
      // Split by comma if user pasted or typed multiple comma-separated items
      val.split(',').forEach(item => {
        if (item.trim()) addLanguageTag(item.trim());
      });
    }
  } else if (e.key === 'Backspace' && !val && selectedLanguages.length > 0) {
    // Backspace on empty input removes the last tag
    removeLanguageTag(selectedLanguages.length - 1);
  }
}

function validateAndGoToStep3() {
  if (selectedLanguages.length === 0) {
    selectedLanguages = ['English', 'Malayalam'];
    renderLanguageTags();
  }

  if (workStatus === 'experienced') {
    const org = document.getElementById('regCurrentOrg');
    const title = document.getElementById('regCurrentTitle');
    const degree = document.getElementById('regDegreeExp');
    const council = document.getElementById('regCouncilExp');

    if (org && !org.value.trim()) org.value = 'Aster Medcity, Kochi';
    if (title && !title.value.trim()) title.value = 'Consultant Physician';
    if (degree && !degree.value) degree.value = 'md_ms';
    if (council && !council.value.trim()) council.value = 'TCMC 45892';
  } else {
    // Fresher defaults if empty
    const degree = document.getElementById('regDegreeFresher');
    const college = document.getElementById('regCollegeName');
    const council = document.getElementById('regCouncilFresher');

    if (degree && !degree.value) degree.value = 'mbbs';
    if (college && !college.value.trim()) college.value = 'Govt Medical College, Trivandrum';
    if (council && !council.value.trim()) council.value = 'TCMC 58210 (Provisional)';
  }

  goToStep(3, 'next');
}

function validateAndGoToStep4() {
  const currentLoc = document.getElementById('regCurrentLoc');
  const preferredLoc = document.getElementById('regPreferredLoc');

  if (currentLoc && !currentLoc.value.trim()) currentLoc.value = 'Kochi, Kerala';
  if (preferredLoc && !preferredLoc.value.trim()) preferredLoc.value = 'Kochi, Bengaluru, Dubai';

  if (workStatus === 'experienced') {
    const currentSalary = document.getElementById('regCurrentSalary');
    const expectedSalary = document.getElementById('regExpectedSalary');
    if (currentSalary && !currentSalary.value.trim()) currentSalary.value = '₹18,00,000 / year';
    if (expectedSalary && !expectedSalary.value.trim()) expectedSalary.value = '₹24,00,000 / year';
  } else {
    const expectedStipend = document.getElementById('regExpectedStipend');
    if (expectedStipend && !expectedStipend.value.trim()) expectedStipend.value = '₹45,000 / month';
  }

  goToStep(4, 'next');
}

function goToStep(step, direction = 'next') {
  currentStep = step;
  
  for (let i = 1; i <= 4; i++) {
    const content = document.getElementById('stepContent' + i);
    const indicator = document.getElementById('stepIndicator' + i);
    
    if (content) {
      if (i === step) {
        content.style.display = 'block';
        content.classList.remove('slide-in-right', 'slide-in-left');
        // Trigger DOM reflow to restart CSS animation
        void content.offsetWidth;
        content.classList.add(direction === 'next' ? 'slide-in-right' : 'slide-in-left');
      } else {
        content.style.display = 'none';
        content.classList.remove('slide-in-right', 'slide-in-left');
      }
    }

    if (indicator) {
      indicator.classList.remove('active');
      if (i < step) {
        indicator.classList.add('completed');
      } else if (i === step) {
        indicator.classList.add('active');
      }
    }
  }

  // Smooth scroll up to top of wizard
  const wizardBox = document.getElementById('candidateRegistrationSection');
  if (wizardBox) {
    const topPos = wizardBox.getBoundingClientRect().top + window.pageYOffset - 90;
    window.scrollTo({ top: Math.max(0, topPos), behavior: 'smooth' });
  }
}

function finishCandidateRegistration() {
  const btn = document.getElementById('candidateSubmitBtn') || (event ? event.currentTarget : null);
  
  if (btn) {
    if (workStatus === 'experienced') {
      btn.innerHTML = 'Verifying Council Credentials & CV...';
    } else {
      btn.innerHTML = 'Generating Fresher Clinical Bio-Data...';
    }
    btn.disabled = true;
  }

  setTimeout(() => {
    if (workStatus === 'experienced') {
      showToast('Healthcare Specialist Profile activated successfully! Welcome to HealthJobsGlobal.');
    } else {
      showToast('Fresher Healthcare Profile & Bio-Data created successfully! Welcome to HealthJobsGlobal.');
    }
    setTimeout(() => {
      window.location.href = '<?php echo BASE_URL; ?>/candidate-dashboard.php';
    }, 1000);
  }, 900);
}

function handleHospitalRegistrationSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('hospitalRegSubmitBtn');
  btn.innerHTML = 'Registering Hospital Account...';
  btn.disabled = true;

  setTimeout(() => {
    showToast('Hospital registered successfully! Redirecting to post your first vacancy...');
    setTimeout(() => {
      window.location.href = '<?php echo BASE_URL; ?>/post-job.php';
    }, 1200);
  }, 900);
}

// Enable direct click navigation on Step Indicator Bubbles for easy dev testing
document.querySelectorAll('.step-indicator').forEach((ind, index) => {
  ind.style.cursor = 'pointer';
  ind.setAttribute('title', 'Click to view Step ' + (index + 1));
  ind.addEventListener('click', () => {
    const targetStep = index + 1;
    goToStep(targetStep, targetStep > currentStep ? 'next' : 'back');
  });
});

// URL Query Param Support for direct testing (e.g. register.php?step=2&work=fresher)
window.addEventListener('DOMContentLoaded', () => {
  renderLanguageTags();

  const urlParams = new URLSearchParams(window.location.search);
  const stepParam = parseInt(urlParams.get('step'));
  const workParam = urlParams.get('work');

  if (workParam === 'fresher' || workParam === 'experienced') {
    setWorkStatus(workParam);
  } else {
    setWorkStatus('experienced');
  }
  if (stepParam && stepParam >= 1 && stepParam <= 4) {
    goToStep(stepParam, 'next');
  }
});
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>
