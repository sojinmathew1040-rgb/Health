<?php
/**
 * HealthJobsGlobal.com - Recruitment & Healthcare Institutional Services
 */
$pageTitle = "Recruitment & Credentialing Services — HealthJobsGlobal";
$pageDescription = "Bespoke healthcare talent acquisition, primary source credential verification, and global healthcare mobility for hospitals.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: linear-gradient(180deg, #0B1F33 0%, #153250 100%); color: #FFFFFF; padding: 64px 0 48px 0;">
  <div class="container text-center" style="max-width: 800px; margin: 0 auto;">
    <span class="eyebrow eyebrow-gold">Institutional Solutions</span>
    <h1 style="color: #FFFFFF; font-size: 2.8rem; margin-bottom: 16px;">Specialized Healthcare Talent Solutions</h1>
    <p style="color: #94A3B8; font-size: 1.15rem; line-height: 1.6;">
      From emergency ward staffing to international clinical mobility and council credential verification — we power the workforce behind premier medical institutions.
    </p>
  </div>
</div>

<div class="container" style="padding-top: var(--space-16); padding-bottom: var(--space-20);">
  <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: var(--space-8);">
    
    <!-- Service 1 -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 32px; display: flex; flex-direction: column;">
      <div class="dept-icon-box" style="margin-bottom: 20px;">
        <?php echo getIcon('briefcase', '', 28); ?>
      </div>
      <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Executive Medical Search</h3>
      <p style="color: var(--color-text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
        Discreet, peer-driven recruitment for Chiefs of Surgery, Medical Superintendents, Laboratory Directors, and Hospital Chief Executive Officers.
      </p>
      <a href="<?php echo BASE_URL; ?>/contact.php?service=executive" class="btn btn-outline btn-sm">Inquire Search</a>
    </div>

    <!-- Service 2 -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 32px; display: flex; flex-direction: column;">
      <div class="dept-icon-box" style="margin-bottom: 20px;">
        <?php echo getIcon('shield-check', '', 28); ?>
      </div>
      <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Primary Source Credentialing</h3>
      <p style="color: var(--color-text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
        Rigorous verification of medical degrees, post-doctoral fellowships, council good standing, and logbooks directly with licensing boards.
      </p>
      <a href="<?php echo BASE_URL; ?>/contact.php?service=credentialing" class="btn btn-outline btn-sm">Request Audit</a>
    </div>

    <!-- Service 3 -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 32px; display: flex; flex-direction: column;">
      <div class="dept-icon-box" style="margin-bottom: 20px;">
        <?php echo getIcon('globe', '', 28); ?>
      </div>
      <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Global Healthcare Mobility</h3>
      <p style="color: var(--color-text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
        Seamless migration pathways for doctors and specialized nurses relocating to UAE (DHA/DOH), Saudi Arabia (MOH), and UK NHS trusts.
      </p>
      <a href="<?php echo BASE_URL; ?>/contact.php?service=mobility" class="btn btn-outline btn-sm">Explore Pathways</a>
    </div>

    <!-- Service 4 -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 32px; display: flex; flex-direction: column;">
      <div class="dept-icon-box" style="margin-bottom: 20px;">
        <?php echo getIcon('heart-pulse', '', 28); ?>
      </div>
      <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Critical Care & OT Nursing Corps</h3>
      <p style="color: var(--color-text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
        Turnkey deployment of pre-evaluated ICU and surgical scrub nurses ready for new quaternary hospital commissioning.
      </p>
      <a href="<?php echo BASE_URL; ?>/contact.php?service=nursing" class="btn btn-outline btn-sm">Learn More</a>
    </div>

    <!-- Service 5 -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 32px; display: flex; flex-direction: column;">
      <div class="dept-icon-box" style="margin-bottom: 20px;">
        <?php echo getIcon('briefcase', '', 28); ?>
      </div>
      <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Hospital Operations & Leadership Search</h3>
      <p style="color: var(--color-text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
        Recruiting Medical Superintendents, Chief Operating Officers, Nursing Directors, and Quality Accreditation Heads for NABH & JCI quaternary hospitals.
      </p>
      <a href="<?php echo BASE_URL; ?>/contact.php?service=hospital_admin" class="btn btn-outline btn-sm">Hire Leadership</a>
    </div>

    <!-- Service 6 -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 32px; display: flex; flex-direction: column;">
      <div class="dept-icon-box" style="margin-bottom: 20px;">
        <?php echo getIcon('award', '', 28); ?>
      </div>
      <h3 style="font-size: 1.35rem; margin-bottom: 12px; color: var(--color-primary);">Locum Tenens & Emergency Coverage</h3>
      <p style="color: var(--color-text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
        Temporary specialist and consultant coverage for holiday leave, maternity, or sudden surgical volume surges.
      </p>
      <a href="<?php echo BASE_URL; ?>/contact.php?service=locum" class="btn btn-outline btn-sm">Request Roster</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
