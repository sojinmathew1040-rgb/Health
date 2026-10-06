<?php
/**
 * HealthJobsGlobal.com - Healthcare Auth Gate Modal (Guest Mode Prompt)
 */
?>
<div class="modal-backdrop" id="authGateModal" aria-hidden="true" role="dialog" style="z-index: 2500;">
  <div class="modal-window" style="max-width: 520px; border-radius: var(--radius-xl); overflow: hidden;">
    
    <!-- Modal Top Header with Medical Accent Banner -->
    <div style="background: linear-gradient(135deg, #0B1F33 0%, #0E7490 100%); padding: 24px; color: #FFFFFF; position: relative;">
      <button class="btn-icon close-auth-modal" aria-label="Close modal" onclick="closeAuthModal()" style="position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.15); border: none; color: #FFFFFF; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
        <?php echo getIcon('x', '', 18); ?>
      </button>
      <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.15); padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 10px;">
        <span>🔒 Verified Healthcare Access</span>
      </div>
      <h3 id="authModalTitle" style="color: #FFFFFF; font-size: 1.4rem; margin: 0 0 6px 0; font-weight: 700;">Account Required</h3>
      <p id="authModalSubtitle" style="color: rgba(255,255,255,0.85); font-size: 0.88rem; margin: 0; line-height: 1.4;">
        Direct applications and verified clinician bio-data require an authenticated healthcare account.
      </p>
    </div>

    <!-- Modal Body -->
    <div style="padding: 24px 28px;">
      
      <!-- Key Benefits List -->
      <div style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 14px 16px; margin-bottom: 22px;">
        <div style="font-size: 0.8rem; font-weight: 700; color: var(--color-primary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.04em;">Why create a HealthJobsGlobal account?</div>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; font-size: 0.82rem; color: var(--color-text-secondary);">
          <li style="display: flex; align-items: center; gap: 8px;">
            <span style="color: #059669; font-weight: 700;">✓</span>
            <span>Direct contact with NABH & JCI accredited hospital HRs</span>
          </li>
          <li style="display: flex; align-items: center; gap: 8px;">
            <span style="color: #059669; font-weight: 700;">✓</span>
            <span>Verified medical & nursing council registration status</span>
          </li>
          <li style="display: flex; align-items: center; gap: 8px;">
            <span style="color: #059669; font-weight: 700;">✓</span>
            <span>Real-time WhatsApp interview & vacancy notifications</span>
          </li>
        </ul>
      </div>

      <!-- Main Action Buttons -->
      <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 22px;">
        <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-lg" style="width: 100%; justify-content: center; font-size: 0.95rem;">
          <span>Register New Healthcare Account</span>
          <?php echo getIcon('arrow-right', '', 18); ?>
        </a>
        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline btn-lg" style="width: 100%; justify-content: center; font-size: 0.95rem;">
          <span>Already have an account? Sign In</span>
        </a>
      </div>

      <!-- Instant Fast Demo Testing Bar -->
      <div style="border-top: 1px dashed var(--color-border); padding-top: 16px; text-align: center;">
        <span style="font-size: 0.75rem; color: var(--color-text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 10px;">
          Instant Demo Logins (Evaluation Mode)
        </span>
        <div style="display: flex; gap: 8px; justify-content: center;">
          <a href="<?php echo BASE_URL; ?>/index.php?demo_login=candidate" class="btn btn-sm btn-outline" style="font-size: 0.78rem; padding: 6px 12px; border-radius: var(--radius-full);">
            ⚡ Log in as Doctor (Dr. Arjun)
          </a>
          <a href="<?php echo BASE_URL; ?>/index.php?demo_login=employer" class="btn btn-sm btn-outline" style="font-size: 0.78rem; padding: 6px 12px; border-radius: var(--radius-full);">
            ⚡ Log in as Hospital (Aster)
          </a>
        </div>
      </div>

    </div>

  </div>
</div>

<script>
window.openAuthModal = function(title, subtitle) {
  const modal = document.getElementById('authGateModal');
  if (!modal) return;

  if (title) document.getElementById('authModalTitle').textContent = title;
  if (subtitle) document.getElementById('authModalSubtitle').textContent = subtitle;

  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
};

window.closeAuthModal = function() {
  const modal = document.getElementById('authGateModal');
  if (!modal) return;
  modal.classList.remove('active');
  document.body.style.overflow = '';
};

// Close on backdrop click
document.addEventListener('click', function(e) {
  const modal = document.getElementById('authGateModal');
  if (modal && e.target === modal) {
    closeAuthModal();
  }
});
</script>
