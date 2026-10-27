<?php
/**
 * HealthJobsGlobal.com - Global Footer Component
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
?>
</main>

<!-- Global Footer -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-top-grid">
      
      <!-- Brand & Mission -->
      <div class="footer-brand">
        <a href="<?php echo BASE_URL; ?>/index.php" class="site-logo" style="color: #FFFFFF; margin-bottom: 16px;">
          <div class="logo-symbol" style="background: var(--color-secondary);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5"><path d="M12 2v20M2 12h20"/></svg>
          </div>
          <span>HealthJobs<span style="color: var(--color-accent);">Global</span></span>
        </a>
        <p>
          The premier global healthcare career exchange connecting verified clinicians, nurses, allied specialists, and administrative leaders with world-class healthcare organizations.
        </p>
        <div class="flex items-center gap-3" style="color: #94A3B8; font-size: 0.85rem;">
          <span class="flex items-center gap-2">
            <?php echo getIcon('shield-check', 'text-teal', 18); ?> Verified Organizations
          </span>
          <span>·</span>
          <span>ISO 27001 Certified</span>
        </div>
      </div>

      <!-- Column 1: Candidates -->
      <div class="footer-col">
        <h5>For Candidates</h5>
        <div class="footer-links">
          <a href="<?php echo BASE_URL; ?>/jobs.php">Browse All Jobs</a>
          <a href="<?php echo BASE_URL; ?>/register.php">Candidate Registration</a>
          <a href="<?php echo BASE_URL; ?>/my-applications.php">Application Tracker</a>
          <a href="<?php echo BASE_URL; ?>/job-alerts.php">WhatsApp & Email Alerts</a>
          <a href="<?php echo BASE_URL; ?>/candidate-dashboard.php">Candidate Dashboard</a>
        </div>
      </div>

      <!-- Column 2: Employers -->
      <div class="footer-col">
        <h5>For Employers</h5>
        <div class="footer-links">
          <a href="<?php echo BASE_URL; ?>/post-job.php">Post a Vacancy</a>
          <a href="<?php echo BASE_URL; ?>/candidate-search.php">Search Healthcare Talent</a>
          <a href="<?php echo BASE_URL; ?>/employer-dashboard.php">Employer Dashboard</a>
          <a href="<?php echo BASE_URL; ?>/services.php">Credentialing & Verification</a>
          <a href="<?php echo BASE_URL; ?>/manage-jobs.php">Manage Active Postings</a>
        </div>
      </div>

      <!-- Column 3: Departments -->
      <div class="footer-col">
        <h5>Departments</h5>
        <div class="footer-links">
          <a href="<?php echo BASE_URL; ?>/departments.php#clinical">Clinical & Surgery</a>
          <a href="<?php echo BASE_URL; ?>/departments.php#nursing">Nursing Specialties</a>
          <a href="<?php echo BASE_URL; ?>/departments.php#allied">Allied Health</a>
          <a href="<?php echo BASE_URL; ?>/departments.php#diagnostics">Diagnostics & Lab</a>
          <a href="<?php echo BASE_URL; ?>/departments.php#ayush">AYUSH & Integrative</a>
          <a href="<?php echo BASE_URL; ?>/departments.php#management">Hospital Management</a>
        </div>
      </div>

      <!-- Column 4: Platform & Support -->
      <div class="footer-col">
        <h5>Company</h5>
        <div class="footer-links">
          <a href="<?php echo BASE_URL; ?>/about.php">About HealthJobsGlobal</a>
          <a href="<?php echo BASE_URL; ?>/services.php">Global Mobility</a>
          <a href="<?php echo BASE_URL; ?>/contact.php">Global Offices</a>
          <a href="<?php echo BASE_URL; ?>/contact.php#support">Help & Support</a>
        </div>
      </div>

    </div>

    <!-- Footer Bottom Row -->
    <div class="footer-bottom">
      <div>
        © <?php echo date('Y'); ?> HealthJobsGlobal. All rights reserved. Built exclusively for healthcare professionals and medical institutions.
      </div>
      <div class="flex items-center gap-4">
        <a href="<?php echo BASE_URL; ?>/about.php#privacy">Privacy Policy</a>
        <span>·</span>
        <a href="<?php echo BASE_URL; ?>/about.php#terms">Terms of Service</a>
        <span>·</span>
        <a href="<?php echo BASE_URL; ?>/about.php#fraud">Fraud Prevention</a>
      </div>
    </div>
  </div>
</footer>

<!-- Include Universal Quick Apply Modal -->
<?php require_once __DIR__ . '/apply-modal.php'; ?>

<!-- CDN Frontend Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>

<!-- Custom Frontend Application Scripts -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=3.1"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/animations.js?v=3.1"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/particles.js?v=3.1"></script>

</body>
</html>
