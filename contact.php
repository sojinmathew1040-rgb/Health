<?php
/**
 * HealthJobsGlobal.com - Contact & Global Offices
 */
$pageTitle = "Contact & Global Offices — HealthJobsGlobal";
$pageDescription = "Connect with HealthJobsGlobal offices in Dubai, Kochi, Mumbai, and London for hospital recruitment support and candidate inquiries.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 36px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a> / <span style="color: var(--color-primary); font-weight: 600;">Contact Us</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: 2.2rem; margin-bottom: 4px;">Connect With HealthJobsGlobal</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Direct lines for hospital recruiters, medical directors, and healthcare professionals.
        </p>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding-top: var(--space-10); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: 1fr 380px; gap: var(--space-10); align-items: start;">
    
    <!-- Contact Form -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 36px; box-shadow: var(--shadow-sm);">
      <h3 style="font-size: 1.45rem; color: var(--color-primary); margin-bottom: 6px;">Send Us an Inquiry</h3>
      <p style="font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 24px;">
        Our clinical recruitment team typically replies within 3 business hours.
      </p>

      <form onsubmit="event.preventDefault(); showToast('Message sent! Our healthcare desk will connect via email & WhatsApp.'); this.reset();">
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label" for="contactName">Your Name *</label>
            <input type="text" id="contactName" class="form-control" placeholder="Dr. / Ms. / Mr. Name" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="contactEmail">Work / Personal Email *</label>
            <input type="email" id="contactEmail" class="form-control" placeholder="name@hospital.com" required>
          </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label" for="contactPhone">Phone / WhatsApp *</label>
            <input type="tel" id="contactPhone" class="form-control" placeholder="+91 98765 43210" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="contactSubject">Inquiry Category *</label>
            <select id="contactSubject" class="form-control" required>
              <option value="employer">Hospital Job Posting & Recruiter Access</option>
              <option value="candidate">Candidate Profile & Application Support</option>
              <option value="credentialing">Council Credential Verification Services</option>
              <option value="global">Overseas Healthcare Mobility (GCC / UK)</option>
              <option value="fraud">Report Suspected Fake Job / Fraud</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="contactMsg">Message / Specific Requirements *</label>
          <textarea id="contactMsg" class="form-control" rows="5" placeholder="Please describe the hospital department, specialties, or query details..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
          <span>Submit Inquiry</span>
          <?php echo getIcon('arrow-right', '', 18); ?>
        </button>
      </form>
    </div>

    <!-- Global Office Locations & Hotline -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
      
      <!-- WhatsApp Direct Desk -->
      <div style="background: linear-gradient(135deg, #0B1F33, #0E7490); color: #FFFFFF; border-radius: var(--radius-lg); padding: 24px;">
        <div class="flex items-center gap-3" style="margin-bottom: 12px;">
          <div style="color: #22A6A1;">
            <?php echo getIcon('whatsapp', '', 28); ?>
          </div>
          <div>
            <div style="font-size: 0.8rem; color: #A5F3FC; font-weight: 700;">WHATSAPP RECRUITMENT DESK</div>
            <div style="font-size: 1.1rem; font-weight: 800;">+91 98460 12345</div>
          </div>
        </div>
        <p style="font-size: 0.85rem; color: #CBD5E1; line-height: 1.5; margin-bottom: 16px;">
          Instant assistance for urgent clinical shifts, hospital onboarding, and verification queries.
        </p>
        <a href="https://wa.me/919846012345" target="_blank" rel="noopener" class="btn btn-gold btn-sm" style="width: 100%;">
          <span>Chat on WhatsApp</span>
        </a>
      </div>

      <!-- Office Cards -->
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px;">
        <h4 style="font-size: 1.15rem; color: var(--color-primary); margin-bottom: 16px;">Global Headquarters & Desks</h4>
        
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <div>
            <div style="font-weight: 700; color: var(--color-primary); font-size: 0.95rem;">India Operations (Kochi Hub)</div>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary); margin-top: 2px;">Level 4, Infopark Technology Tower, Kochi, Kerala, India</div>
          </div>

          <div style="border-top: 1px solid var(--color-border); padding-top: 12px;">
            <div style="font-weight: 700; color: var(--color-primary); font-size: 0.95rem;">Middle East Office (Dubai)</div>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary); margin-top: 2px;">Building 64, Dubai Healthcare City (DHCC), Dubai, UAE</div>
          </div>

          <div style="border-top: 1px solid var(--color-border); padding-top: 12px;">
            <div style="font-weight: 700; color: var(--color-primary); font-size: 0.95rem;">UK Healthcare Liaison (London)</div>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary); margin-top: 2px;">Harley Street Medical Quarter, Marylebone, London, UK</div>
          </div>
        </div>
      </div>

    </aside>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
