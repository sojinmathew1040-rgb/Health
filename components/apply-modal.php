<?php
/**
 * HealthJobsGlobal.com - Quick Apply Modal Component
 */
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

    <form id="quickApplyForm">
      <input type="hidden" id="applyJobId" name="job_id" value="">
      
      <div class="modal-body">
        <div style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px; font-size: 0.85rem; color: var(--color-text-secondary);">
          <strong style="color: var(--color-primary);">Quick Apply:</strong> Your application and council credentials will be routed directly to the verified hospital HR/Medical Director.
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label" for="applicantName">Full Name *</label>
            <input type="text" id="applicantName" class="form-control" placeholder="Dr. / Nurse / Professional Name" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="applicantEmail">Email Address *</label>
            <input type="email" id="applicantEmail" class="form-control" placeholder="doctor@example.com" required>
          </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label" for="applicantPhone">Mobile / WhatsApp *</label>
            <input type="tel" id="applicantPhone" class="form-control" placeholder="+91 98765 43210" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="applicantCouncil">Medical / Nursing Council No.</label>
            <input type="text" id="applicantCouncil" class="form-control" placeholder="e.g. TCMC 45892 / NMC / DHA">
            <span class="form-hint">Enter N/A if administrative or non-clinical</span>
          </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label" for="applicantExp">Total Experience *</label>
            <select id="applicantExp" class="form-control" required>
              <option value="">Select Experience</option>
              <option value="fresher">Fresher / Intern</option>
              <option value="1-3">1 - 3 Years</option>
              <option value="4-7">4 - 7 Years</option>
              <option value="8-12">8 - 12 Years</option>
              <option value="12+">12+ Years (Senior Consultant / Chief)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="applicantNotice">Notice Period *</label>
            <select id="applicantNotice" class="form-control" required>
              <option value="immediate">Immediate Available</option>
              <option value="15days">15 Days</option>
              <option value="30days" selected>30 Days</option>
              <option value="60days">60 Days</option>
              <option value="90days">90 Days</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="applicantCV">Upload CV / Resume (PDF / DOCX) *</label>
          <div style="border: 2px dashed var(--color-border); border-radius: var(--radius-md); padding: 20px; text-align: center; background: var(--color-bg); cursor: pointer;" onclick="document.getElementById('applicantCV').click()">
            <div style="color: var(--color-secondary); margin-bottom: 8px;">
              <?php echo getIcon('upload-cloud', '', 32); ?>
            </div>
            <div style="font-weight: 600; font-size: 0.9rem; color: var(--color-primary);">Click to upload resume or drag & drop</div>
            <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 4px;">Max file size 5MB (PDF preferred)</div>
            <input type="file" id="applicantCV" name="cv_file" style="display: none;" accept=".pdf,.doc,.docx" required onchange="this.previousElementSibling.previousElementSibling.textContent = this.files[0] ? this.files[0].name : 'File selected';">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline close-modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <span>Submit Application</span>
          <?php echo getIcon('arrow-right', '', 18); ?>
        </button>
      </div>
    </form>
  </div>
</div>
