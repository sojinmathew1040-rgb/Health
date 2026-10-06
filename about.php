<?php
/**
 * HealthJobsGlobal.com - About Us
 */
$pageTitle = "About HealthJobsGlobal — Global Healthcare Recruitment Platform";
$pageDescription = "Dedicated exclusively to the healthcare sector, connecting verified clinicians, nurses, and hospital administrators with accredited medical institutions.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/components/header.php';
?>

<div style="background: linear-gradient(180deg, #0B1F33 0%, #153250 100%); color: #FFFFFF; padding: 64px 0 48px 0;">
  <div class="container text-center" style="max-width: 800px; margin: 0 auto;">
    <span class="eyebrow eyebrow-gold">Our Purpose</span>
    <h1 style="color: #FFFFFF; font-size: 2.8rem; margin-bottom: 16px;">Elevating Healthcare Careers Globally</h1>
    <p style="color: #94A3B8; font-size: 1.15rem; line-height: 1.6;">
      HealthJobsGlobal was founded to solve a critical global challenge: connecting specialized medical talent with accredited institutions through transparent, credential-verified recruitment.
    </p>
  </div>
</div>

<div class="container" style="padding-top: var(--space-16); padding-bottom: var(--space-20);">
  
  <div class="grid" style="grid-template-columns: 1fr 1fr; gap: var(--space-12); align-items: center; margin-bottom: var(--space-20);">
    <div>
      <span class="eyebrow">The Challenge & Vision</span>
      <h2 style="font-size: 2rem; margin-bottom: 16px;">Healthcare is not a generic industry. Neither should its hiring be.</h2>
      <p style="color: var(--color-text-secondary); line-height: 1.7; margin-bottom: 16px;">
        Unlike standard corporate hiring, healthcare recruitment directly affects patient safety, clinical quality, and institutional accreditations. A single appointment requires verification of medical council licenses, surgical logbooks, post-doctoral qualifications, and hospital infection control compliance.
      </p>
      <p style="color: var(--color-text-secondary); line-height: 1.7;">
        HealthJobsGlobal replaces chaotic classified portals with an intelligent, credential-first platform designed exclusively around the terminology and governance of modern hospitals.
      </p>
    </div>
    
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-lg);">
      <h4 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 20px;">Our Four Pillars</h4>
      
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <div class="flex items-center gap-3">
          <div class="stat-icon" style="width: 44px; height: 44px;">
            <?php echo getIcon('shield-check', '', 22); ?>
          </div>
          <div>
            <div style="font-weight: 700; color: var(--color-primary);">Primary Source Verification</div>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary);">Direct checks with NMC, State Councils, DHA & MOH.</div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="stat-icon" style="width: 44px; height: 44px;">
            <?php echo getIcon('globe', '', 22); ?>
          </div>
          <div>
            <div style="font-weight: 700; color: var(--color-primary);">Ethical Global Mobility</div>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary);">Adherence to WHO Global Code of Practice on International Recruitment.</div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="stat-icon" style="width: 44px; height: 44px;">
            <?php echo getIcon('heart-pulse', '', 22); ?>
          </div>
          <div>
            <div style="font-weight: 700; color: var(--color-primary);">Zero Fee to Candidates</div>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary);">We never charge candidates for job discovery or applications.</div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="stat-icon" style="width: 44px; height: 44px;">
            <?php echo getIcon('activity', '', 22); ?>
          </div>
          <div>
            <div style="font-weight: 700; color: var(--color-primary);">Specialty Precision</div>
            <div style="font-size: 0.85rem; color: var(--color-text-secondary);">Covering all accredited hospital disciplines: medicine, surgery, nursing, diagnostics & operations.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Medical Advisory Leadership -->
  <section>
    <div class="section-header">
      <span class="eyebrow">Clinical Governance</span>
      <h2>Guided by Medical Leadership</h2>
      <p class="lead-text">
        Our workflows are advised by senior clinicians, hospital chief executives, and nursing directors to maintain the highest standards of healthcare ethics.
      </p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: var(--space-6);">
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px; text-align: center;">
        <div class="doctor-avatar-wrap" style="margin: 0 auto 16px auto; width: 72px; height: 72px;">
          <span>DM</span>
        </div>
        <h4 style="font-size: 1.15rem; color: var(--color-primary);">Dr. Devadas Menon</h4>
        <div style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600; margin-bottom: 8px;">Former Medical Director</div>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary); line-height: 1.5;">
          Over 30 years overseeing clinical governance and surgical standards across leading quaternary teaching hospitals in South India.
        </p>
      </div>

      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px; text-align: center;">
        <div class="doctor-avatar-wrap" style="margin: 0 auto 16px auto; width: 72px; height: 72px; background: linear-gradient(135deg, #0B1F33, #0E7490);">
          <span>SJ</span>
        </div>
        <h4 style="font-size: 1.15rem; color: var(--color-primary);">Prof. Sarah Jenkins</h4>
        <div style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600; margin-bottom: 8px;">Head of Nursing Governance</div>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary); line-height: 1.5;">
          International critical care educator and advisor on trans-national nursing mobility across GCC and UK healthcare systems.
        </p>
      </div>

      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px; text-align: center;">
        <div class="doctor-avatar-wrap" style="margin: 0 auto 16px auto; width: 72px; height: 72px; background: linear-gradient(135deg, #C8A96B, #9F8246);">
          <span>RK</span>
        </div>
        <h4 style="font-size: 1.15rem; color: var(--color-primary);">Rajesh Kulkarni, MHA</h4>
        <div style="font-size: 0.85rem; color: var(--color-secondary); font-weight: 600; margin-bottom: 8px;">Hospital Operations Fellow</div>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary); line-height: 1.5;">
          Specialist in JCI hospital commissioning, NABH accreditations, and clinical asset lifecycle management across hospital networks.
        </p>
      </div>
    </div>
  </section>

</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
