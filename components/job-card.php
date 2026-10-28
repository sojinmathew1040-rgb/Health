<?php
/**
 * HealthJobsGlobal.com - Reusable Job Card Component
 * Requires $job array in scope
 */
if (!isset($job) || !is_array($job)) return;
$safeTitle = htmlspecialchars((string)($job['title'] ?? ''));
$safeCompany = htmlspecialchars((string)($job['company'] ?? ''));
$jobUrl = BASE_URL . '/job-details.php?id=' . (int)($job['id'] ?? 0);
?>
<div class="job-card" data-id="<?php echo (int)($job['id'] ?? 0); ?>">
  
  <!-- Header: Company & Bookmark -->
  <div class="job-card-header">
    <div class="job-company-lockup">
      <div class="company-logo-avatar">
        <?php echo strtoupper(substr((string)($job['company'] ?? 'HJ'), 0, 2)); ?>
      </div>
      <div>
        <div class="job-company-name">
          <span><?php echo $safeCompany; ?></span>
          <span style="color: var(--color-success);" title="Verified Healthcare Provider">
            <?php echo getIcon('check-circle', '', 14); ?>
          </span>
        </div>
        <div class="job-company-location">
          <?php echo getIcon('map-pin', '', 14); ?>
          <span><?php echo htmlspecialchars((string)($job['location'] ?? '')); ?></span>
        </div>
      </div>
    </div>

    <!-- Bookmark Action -->
    <?php if (isLoggedIn()): ?>
      <button class="job-bookmark-btn" data-title="<?php echo $safeTitle; ?>" aria-label="Save this job">
        <?php echo getIcon('bookmark', '', 18); ?>
      </button>
    <?php else: ?>
      <button class="job-bookmark-btn" onclick="openAuthModal('Save Healthcare Opportunity', 'Please register or sign in to save <?php echo addslashes($safeTitle); ?> to your personalized tracker.')" aria-label="Save this job">
        <?php echo getIcon('bookmark', '', 18); ?>
      </button>
    <?php endif; ?>
  </div>

  <!-- Job Title -->
  <h3 class="job-card-title">
    <a href="<?php echo $jobUrl; ?>"><?php echo $safeTitle; ?></a>
  </h3>

  <!-- Specs & Council Registration Badges -->
  <div class="job-specs-pills">
    <div class="spec-pill">
      <?php echo getIcon('briefcase', '', 14); ?>
      <span><?php echo htmlspecialchars((string)($job['experience'] ?? '')); ?></span>
    </div>
    <div class="spec-pill">
      <?php echo getIcon('clock', '', 14); ?>
      <span><?php echo htmlspecialchars((string)($job['employment_type'] ?? '')); ?></span>
    </div>
    <?php if (!empty($job['council_req'])): ?>
      <div class="spec-pill council-pill" title="Council Registration Requirement">
        <?php echo getIcon('shield-check', '', 14); ?>
        <span><?php echo htmlspecialchars((string)$job['council_req']); ?></span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Excerpt -->
  <p class="job-description-excerpt">
    <?php echo htmlspecialchars((string)($job['description'] ?? '')); ?>
  </p>

  <!-- Tags -->
  <?php if (!empty($job['tags']) && is_array($job['tags'])): ?>
    <div class="job-skills-tags">
      <?php foreach ($job['tags'] as $tag): ?>
        <span class="skill-tag"><?php echo htmlspecialchars((string)$tag); ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Card Footer: Salary, Date & Quick Apply -->
  <div class="job-card-bottom">
    <div>
      <div class="job-salary-block"><?php echo htmlspecialchars((string)($job['salary'] ?? '')); ?></div>
      <div class="job-posted-time">Posted <?php echo htmlspecialchars((string)($job['posted'] ?? '')); ?></div>
    </div>

    <div class="flex items-center gap-2">
      <a href="<?php echo $jobUrl; ?>" class="btn btn-outline btn-sm">Details</a>
      <?php if (isLoggedIn()): ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="openApplyModal('<?php echo $job['id']; ?>', '<?php echo addslashes($safeTitle); ?>', '<?php echo addslashes($safeCompany); ?>')">
          Apply Now
        </button>
      <?php else: ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="openAuthModal('Direct Hospital Application', 'To apply for <?php echo addslashes($safeTitle); ?> at <?php echo addslashes($safeCompany); ?>, please register or sign in to verify your council credentials.')">
          Apply Now
        </button>
      <?php endif; ?>
    </div>
  </div>

</div>
