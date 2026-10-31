<?php
/**
 * HealthJobsGlobal.com - Quick Apply Modal Component
 */
$loggedCand = (isLoggedIn() && ((getLoggedInUser()['role'] ?? '') === 'candidate')) ? getLoggedInUser() : [];
?>
<div class="modal-backdrop" id="applyModal" aria-hidden="true" role="dialog">
  <div class="modal-window">
    <div class="modal-header">
      <div>
        <span class="eyebrow" style="margin-bottom: 2px;">Direct Application</span>
        <h4 id="applyModalJobTitle" style="color: var(--color-primary); font-size: 1.25rem;">Clinical Position</h4>
        <p id="applyModalCompany" style="font-size: 0.85rem; color: var(--color-text-secondary); margin-top: 2px;">Healthcare Organization</p>
      </div>
      <button class="btn-icon close-modal" aria-label="Close modal">
        <?php echo getIcon('x', '', 20); ?>
      </button>
    </div>

    <form id="quickApplyForm" method="POST" action="<?php echo BASE_URL; ?>/api/apply-job.php" enctype="multipart/form-data" onsubmit="submitCandidateDirectApplication(event)">
      <input type="hidden" id="applyJobId" name="job_id" value="">
      
      <div class="modal-body">
        <div style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px; font-size: 0.85rem; color: var(--color-text-secondary);">
          <strong style="color: var(--color-primary);">Direct Hospital Application:</strong> Your application and verified council credentials will be routed directly to the hospital's recruiter dashboard.
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label" for="applicantName">Full Name *</label>
            <input type="text" id="applicantName" name="name" class="form-control" placeholder="Dr. / Nurse / Professional Name" required value="<?php echo htmlspecialchars($loggedCand['name'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="applicantEmail">Email Address *</label>
            <input type="email" id="applicantEmail" name="email" class="form-control" placeholder="doctor@example.com" required value="<?php echo htmlspecialchars($loggedCand['email'] ?? ''); ?>">
          </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label" for="applicantPhone">Mobile / WhatsApp *</label>
            <input type="tel" id="applicantPhone" name="phone" class="form-control" placeholder="+91 98765 43210" required value="<?php echo htmlspecialchars($loggedCand['phone'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="applicantCouncil">Medical / Nursing Council No.</label>
            <input type="text" id="applicantCouncil" name="council_reg" class="form-control" placeholder="e.g. TCMC 45892 / NMC / DHA" value="<?php echo htmlspecialchars($loggedCand['council'] ?? ''); ?>">
            <span class="form-hint">Enter N/A if administrative or non-clinical</span>
          </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label" for="applicantExp">Total Experience *</label>
            <select id="applicantExp" name="experience" class="form-control" required>
              <option value="Fresher / Intern">Fresher / Intern</option>
              <option value="1 - 3 Years">1 - 3 Years</option>
              <option value="4 - 7 Years" selected>4 - 7 Years</option>
              <option value="8 - 12 Years">8 - 12 Years</option>
              <option value="12+ Years (Senior Consultant / Chief)">12+ Years (Senior Consultant / Chief)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="applicantNotice">Notice Period *</label>
            <select id="applicantNotice" name="notice_period" class="form-control" required>
              <option value="Immediate Available">Immediate Available</option>
              <option value="15 Days">15 Days</option>
              <option value="30 Days" selected>30 Days</option>
              <option value="60 Days">60 Days</option>
              <option value="90 Days">90 Days</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="applicantCV">Upload CV / Resume (PDF / DOCX)</label>
          <div style="border: 2px dashed var(--color-border); border-radius: var(--radius-md); padding: 18px; text-align: center; background: var(--color-bg); cursor: pointer;" onclick="document.getElementById('applicantCV').click()">
            <div style="color: var(--color-secondary); margin-bottom: 6px;">
              <?php echo getIcon('upload-cloud', '', 28); ?>
            </div>
            <div id="cvFileNameLabel" style="font-weight: 600; font-size: 0.9rem; color: var(--color-primary);">Click to upload resume or drag & drop</div>
            <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 4px;">Max file size 10MB (PDF preferred)</div>
            <input type="file" id="applicantCV" name="cv_file" style="display: none;" accept=".pdf,.doc,.docx" onchange="document.getElementById('cvFileNameLabel').textContent = this.files[0] ? this.files[0].name : 'Click to upload resume';">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline close-modal">Cancel</button>
        <button type="submit" id="submitApplyBtn" class="btn btn-primary">
          <span>Submit Application</span>
          <?php echo getIcon('arrow-right', '', 18); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Standalone Top-Right Toast Helper (Runs independently of any cached stylesheet or external script)
function showTopRightToast(message) {
  let toast = document.querySelector('.hjg-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.className = 'hjg-toast';
    document.body.appendChild(toast);
  }

  // Force top-right coordinates directly to bypass old browser cache
  toast.style.position = 'fixed';
  toast.style.top = '24px';
  toast.style.right = '24px';
  toast.style.bottom = 'auto';
  toast.style.left = 'auto';
  toast.style.zIndex = '999999';
  toast.style.background = '#0B1F33';
  toast.style.color = '#FFFFFF';
  toast.style.padding = '14px 22px';
  toast.style.borderRadius = '12px';
  toast.style.boxShadow = '0 20px 40px -10px rgba(0,0,0,0.45), 0 0 0 1px rgba(255,255,255,0.15)';
  toast.style.display = 'flex';
  toast.style.alignItems = 'center';
  toast.style.gap = '12px';
  toast.style.fontSize = '0.92rem';
  toast.style.fontWeight = '600';
  toast.style.borderLeft = '4px solid #22A6A1';
  toast.style.maxWidth = 'min(480px, calc(100vw - 32px))';
  toast.style.boxSizing = 'border-box';
  toast.style.transition = 'transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease';

  toast.innerHTML = `
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22A6A1" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <span style="flex:1;">${message}</span>
    <button type="button" onclick="this.parentElement.style.opacity='0'; this.parentElement.style.transform='translateY(-40px) scale(0.96)';" style="background:none;border:none;color:#94A3B8;cursor:pointer;font-size:1.15rem;line-height:1;padding:0 0 0 8px;">&times;</button>
  `;

  toast.style.transform = 'translateY(0) scale(1)';
  toast.style.opacity = '1';
  toast.style.pointerEvents = 'auto';
  toast.classList.add('show');

  if (window.toastTimeout) clearTimeout(window.toastTimeout);
  window.toastTimeout = setTimeout(() => {
    toast.classList.remove('show');
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-40px) scale(0.96)';
    toast.style.pointerEvents = 'none';
  }, 4500);
}

// Override or assign showToast
window.showToast = showTopRightToast;

function submitCandidateDirectApplication(e) {
  e.preventDefault();
  const form = document.getElementById('quickApplyForm');
  const submitBtn = document.getElementById('submitApplyBtn');
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = 'Submitting Application...';
  submitBtn.disabled = true;

  const formData = new FormData(form);
  const targetUrl = form.getAttribute('action') || '<?php echo BASE_URL; ?>/api/apply-job.php';

  fetch(targetUrl, {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(res => {
    if (!res.ok) {
      throw new Error('Server returned status: ' + res.status);
    }
    return res.json();
  })
  .then(data => {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
    
    if (data && data.success) {
      const modal = document.getElementById('applyModal');
      if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      }
      showTopRightToast(data.message || 'Application submitted successfully! Recruiter notified via WhatsApp & Email.');
      form.reset();
      document.getElementById('cvFileNameLabel').textContent = 'Click to upload resume or drag & drop';
    } else {
      alert('Application could not be submitted:\n\n' + (data ? data.message : 'Unknown response from server.'));
    }
  })
  .catch(err => {
    console.warn('Fetch fallback to direct form submit:', err);
    // If AJAX failed due to network or script caching, submit form directly so database save is 100% guaranteed
    form.submit();
  });
}
</script>
