<?php
/**
 * HealthJobsGlobal.com - Healthcare Job Search & Discovery
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// URL parameters
$searchQ = trim($_GET['q'] ?? '');
$searchLoc = trim($_GET['location'] ?? '');
$rawDept = $_GET['dept'] ?? ($_GET['department'] ?? []);
$searchDepts = is_array($rawDept) ? $rawDept : (empty($rawDept) ? [] : [$rawDept]);
$searchCouncils = isset($_GET['council']) ? (is_array($_GET['council']) ? $_GET['council'] : [$_GET['council']]) : [];
$searchExp = trim($_GET['experience'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'recent');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 4; // 4 jobs per page for rich multi-page experience

// Department Mapping Definition
$deptOptions = [
    'doctors' => ['label' => 'Doctors & Clinical Specialists', 'match' => 'Doctors'],
    'nursing' => ['label' => 'Nursing Specialties', 'match' => 'Nursing'],
    'allied' => ['label' => 'Allied Health & Paramedical', 'match' => 'Allied'],
    'diagnostics' => ['label' => 'Diagnostics, Pathology & Pharmacy', 'match' => ['Diagnostics', 'Pharmacy']],
    'hospital_admin' => ['label' => 'Hospital Operations & Leadership', 'match' => 'Operations'],
    'ayush' => ['label' => 'AYUSH & Integrative Medicine', 'match' => 'AYUSH'],
];

// Council Mapping Definition
$councilOptions = [
    'nmc' => ['label' => 'National Medical Commission (NMC)', 'keywords' => ['NMC', 'Medical Council']],
    'state_medical' => ['label' => 'State Medical Council (TCMC/KMC/MMC)', 'keywords' => ['Travancore', 'Karnataka', 'State Medical']],
    'state_nursing' => ['label' => 'State Nursing Council', 'keywords' => ['Nursing Council', 'KNC', 'Telangana']],
    'dha_gulf' => ['label' => 'Middle East (DHA / DOH / MOH)', 'keywords' => ['DOH', 'DHA', 'MOH', 'HAAD']],
    'non_clinical' => ['label' => 'Non-Clinical / Healthcare Admin', 'keywords' => ['MHA', 'MBA', 'Hospital Management']],
];

// Compute counts for filter badges across total jobs
$deptCounts = [];
foreach ($deptOptions as $dKey => $dMeta) {
    $count = 0;
    foreach ($featuredJobs as $j) {
        if (is_array($dMeta['match'])) {
            foreach ($dMeta['match'] as $m) {
                if (stripos($j['department'], $m) !== false) { $count++; break; }
            }
        } else {
            if (stripos($j['department'], $dMeta['match']) !== false) $count++;
        }
    }
    $deptCounts[$dKey] = $count;
}

// Filter dataset based on active queries
$filteredJobs = array_filter($featuredJobs, function($j) use ($searchQ, $searchLoc, $searchDepts, $searchCouncils, $searchExp, $deptOptions, $councilOptions) {
    // 1. Keyword search (Title, Company, Specialty, Description, Tags, Qualification)
    if (!empty($searchQ)) {
        $q = strtolower($searchQ);
        $titleMatch = stripos($j['title'], $q) !== false;
        $compMatch = stripos($j['company'], $q) !== false;
        $specMatch = stripos($j['specialty'], $q) !== false;
        $descMatch = stripos($j['description'], $q) !== false;
        $qualMatch = stripos($j['qualification'] ?? '', $q) !== false;
        $tagMatch = false;
        if (!empty($j['tags']) && is_array($j['tags'])) {
            foreach ($j['tags'] as $t) {
                if (stripos($t, $q) !== false) { $tagMatch = true; break; }
            }
        }
        if (!$titleMatch && !$compMatch && !$specMatch && !$descMatch && !$qualMatch && !$tagMatch) {
            return false;
        }
    }

    // 2. Location
    if (!empty($searchLoc)) {
        if (stripos($j['location'], $searchLoc) === false) {
            return false;
        }
    }

    // 3. Departments
    if (!empty($searchDepts)) {
        $deptMatched = false;
        foreach ($searchDepts as $dKey) {
            if (isset($deptOptions[$dKey])) {
                $target = $deptOptions[$dKey]['match'];
                if (is_array($target)) {
                    foreach ($target as $m) {
                        if (stripos($j['department'], $m) !== false) { $deptMatched = true; break; }
                    }
                } else {
                    if (stripos($j['department'], $target) !== false) { $deptMatched = true; }
                }
            }
        }
        if (!$deptMatched) return false;
    }

    // 4. Council Registration
    if (!empty($searchCouncils)) {
        $councilMatched = false;
        $cReq = $j['council_req'] ?? '';
        foreach ($searchCouncils as $cKey) {
            if (isset($councilOptions[$cKey])) {
                foreach ($councilOptions[$cKey]['keywords'] as $kw) {
                    if (stripos($cReq, $kw) !== false) {
                        $councilMatched = true;
                        break;
                    }
                }
            }
        }
        if (!$councilMatched) return false;
    }

    // 5. Experience Bracket
    if (!empty($searchExp)) {
        $expText = $j['experience'] ?? '';
        if ($searchExp === 'fresher' && (stripos($expText, '0 - 1') !== false || stripos($expText, 'Intern') !== false)) {
            // match
        } elseif ($searchExp === '1-3' && (stripos($expText, '1 - 3') !== false || stripos($expText, '2 - 5') !== false || stripos($expText, '3 - 6') !== false)) {
            // match
        } elseif ($searchExp === '4-7' && (stripos($expText, '4 - 7') !== false || stripos($expText, '4+') !== false || stripos($expText, '5+') !== false || stripos($expText, '3 - 6') !== false || stripos($expText, '6 - 10') !== false)) {
            // match
        } elseif ($searchExp === '8+' && (stripos($expText, '8+') !== false || stripos($expText, '6 - 10') !== false)) {
            // match
        } else {
            return false;
        }
    }

    return true;
});

// Re-index array
$filteredJobs = array_values($filteredJobs);

// Sort
if ($sortBy === 'salary_high') {
    usort($filteredJobs, function($a, $b) {
        return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
    });
} elseif ($sortBy === 'experience') {
    usort($filteredJobs, function($a, $b) {
        return strcmp($a['experience'] ?? '', $b['experience'] ?? '');
    });
}

// Pagination Calculation
$totalJobsCount = count($filteredJobs);
$totalPages = max(1, (int)ceil($totalJobsCount / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$pagedJobs = array_slice($filteredJobs, $offset, $perPage);

// Helper function to build query string preserving existing filters
function getJobFilterUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $query = http_build_query($params);
    return BASE_URL . '/jobs.php' . ($query ? '?' . $query : '');
}

// Check if any filter is active
$hasActiveFilters = !empty($searchQ) || !empty($searchLoc) || !empty($searchDepts) || !empty($searchCouncils) || !empty($searchExp);

$pageTitle = "Healthcare Jobs & Vacancies — HealthJobsGlobal";
$pageDescription = "Browse verified clinical, nursing, allied health, and medical administration vacancies.";
require_once __DIR__ . '/components/header.php';
?>

<!-- Directory Header Bar -->
<div style="background: #FFFFFF; border-bottom: 1px solid var(--color-border); padding: 26px 0;">
  <div class="container">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 8px;">
      <a href="<?php echo BASE_URL; ?>/index.php" style="color: var(--color-text-secondary);">Home</a> / 
      <span style="color: var(--color-primary); font-weight: 600;">Healthcare Jobs</span>
    </div>
    
    <div class="flex-between items-center" style="flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="font-size: clamp(1.8rem, 3vw, 2.2rem); margin-bottom: 4px; color: var(--color-primary);">Explore Healthcare Opportunities</h1>
        <p style="color: var(--color-text-secondary); font-size: 0.95rem;">
          Showing <strong style="color: var(--color-secondary);"><?php echo $totalJobsCount; ?> verified positions</strong> across accredited medical centers.
        </p>
      </div>

      <!-- Quick Search Bar inside Job Directory -->
      <form action="<?php echo BASE_URL; ?>/jobs.php" method="GET" style="display: flex; gap: 8px; background: var(--color-bg); padding: 6px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); max-width: 460px; width: 100%;">
        <?php if (!empty($searchLoc)): ?><input type="hidden" name="location" value="<?php echo htmlspecialchars($searchLoc); ?>"><?php endif; ?>
        <div style="display: flex; align-items: center; gap: 8px; padding-left: 10px; flex-grow: 1;">
          <?php echo getIcon('search', '', 18); ?>
          <input type="text" name="q" value="<?php echo htmlspecialchars($searchQ); ?>" placeholder="Search doctor, nurse, OT, cardiology..." style="width: 100%; font-size: 0.88rem;">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
      </form>
    </div>
  </div>
</div>

<!-- Main Directory Content -->
<div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-20);">
  
  <!-- Mobile Filter Toggle Bar (Visible on mobile/tablet <= 992px) -->
  <div class="mobile-filter-bar">
    <button type="button" class="mobile-filter-toggle-btn" id="mobileFilterToggleBtn" onclick="toggleMobileFilters()">
      <span class="flex items-center gap-2">
        <?php echo getIcon('filter', 'text-teal', 18); ?>
        <span>Filter Healthcare Vacancies</span>
        <?php if ($hasActiveFilters): ?>
          <span class="hjg-badge badge-verified" style="font-size: 0.72rem; padding: 2px 7px;">Active</span>
        <?php endif; ?>
      </span>
      <span class="filter-toggle-icon" id="filterToggleArrow">
        <?php echo getIcon('chevron-down', '', 16); ?>
      </span>
    </button>
  </div>

  <div class="jobs-directory-grid">
    
    <!-- Left Sidebar: Advanced Healthcare Filters -->
    <aside class="jobs-filter-sidebar" id="jobsFilterSidebar">
      <form id="jobsFilterForm" action="<?php echo BASE_URL; ?>/jobs.php" method="GET">
        <!-- Preserve text query & sort if present -->
        <?php if (!empty($searchQ)): ?>
          <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQ); ?>">
        <?php endif; ?>
        <?php if (!empty($sortBy) && $sortBy !== 'recent'): ?>
          <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
        <?php endif; ?>

        <!-- Sidebar Top Header with Reset All -->
        <div class="flex-between items-center" style="margin-bottom: 18px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px;">
          <div class="flex items-center gap-2">
            <?php echo getIcon('filter', 'text-teal', 18); ?>
            <h4 style="font-size: 1.05rem; color: var(--color-primary); margin: 0;">Filter Jobs</h4>
          </div>
          <a href="<?php echo BASE_URL; ?>/jobs.php" class="filter-reset-btn" title="Clear all selected filters">Reset All</a>
        </div>

        <!-- Filter: Department -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Department</span>
            <span style="font-size: 0.72rem; color: var(--color-text-muted); font-weight: 500;">Clinical & Allied</span>
          </div>
          <div style="display: flex; flex-direction: column; gap: 6px;">
            <?php foreach ($deptOptions as $dKey => $dMeta): 
              $isDeptChecked = in_array($dKey, $searchDepts);
            ?>
              <label class="filter-checkbox-label">
                <input type="checkbox" name="dept[]" value="<?php echo $dKey; ?>" <?php echo $isDeptChecked ? 'checked' : ''; ?> onchange="document.getElementById('jobsFilterForm').submit()">
                <span style="flex-grow: 1;"><?php echo htmlspecialchars($dMeta['label']); ?></span>
                <span style="font-size: 0.75rem; color: var(--color-text-muted); font-weight: 600;">(<?php echo $deptCounts[$dKey] ?? 0; ?>)</span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Filter: Medical / Nursing Council Registration -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Council Registration</span>
            <span style="font-size: 0.72rem; color: var(--color-text-muted); font-weight: 500;">Licensing Body</span>
          </div>
          <div style="display: flex; flex-direction: column; gap: 6px;">
            <?php foreach ($councilOptions as $cKey => $cMeta): 
              $isCouncilChecked = in_array($cKey, $searchCouncils);
            ?>
              <label class="filter-checkbox-label">
                <input type="checkbox" name="council[]" value="<?php echo $cKey; ?>" <?php echo $isCouncilChecked ? 'checked' : ''; ?> onchange="document.getElementById('jobsFilterForm').submit()">
                <span style="flex-grow: 1;"><?php echo htmlspecialchars($cMeta['label']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Filter: Experience Bracket -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Experience Bracket</span>
          </div>
          <select name="experience" class="form-control" style="font-size: 0.85rem; padding: 8px 12px; background: #FFFFFF;" onchange="document.getElementById('jobsFilterForm').submit()">
            <option value="" <?php echo empty($searchExp) ? 'selected' : ''; ?>>Any Experience Level</option>
            <option value="fresher" <?php echo $searchExp === 'fresher' ? 'selected' : ''; ?>>Fresher / Intern (0 - 1 Yr)</option>
            <option value="1-3" <?php echo $searchExp === '1-3' ? 'selected' : ''; ?>>1 - 3 Years (Junior Specialist)</option>
            <option value="4-7" <?php echo $searchExp === '4-7' ? 'selected' : ''; ?>>4 - 7 Years (Specialist / In-Charge)</option>
            <option value="8+" <?php echo $searchExp === '8+' ? 'selected' : ''; ?>>8+ Years (Senior Consultant)</option>
          </select>
        </div>

        <!-- Filter: Location -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Location</span>
          </div>
          <div class="filter-loc-tags">
            <?php 
            $popularLocations = ['Kochi', 'Dubai', 'Abu Dhabi', 'Bengaluru', 'Hyderabad', 'Chennai'];
            foreach ($popularLocations as $loc): 
              $isLocActive = (stripos($searchLoc, $loc) !== false);
            ?>
              <a href="<?php echo getJobFilterUrl(['location' => $isLocActive ? '' : $loc, 'page' => 1]); ?>" 
                 class="filter-loc-tag <?php echo $isLocActive ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($loc); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 8px; margin-top: 14px;">
          <button type="submit" class="btn btn-primary btn-sm" style="flex-grow: 1;">Apply Filters</button>
          <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-outline btn-sm" style="padding: 0 12px;">Clear</a>
        </div>
      </form>
    </aside>

    <!-- Right Column: Results List -->
    <main class="jobs-results-main">
      
      <!-- Results Toolbar (Sort & Count) -->
      <div class="flex-between items-center" style="margin-bottom: 18px; background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 12px 18px; box-shadow: var(--shadow-xs);">
        <div style="font-size: 0.88rem; color: var(--color-text-secondary);">
          Showing <strong><?php echo $totalJobsCount ? ($offset + 1) : 0; ?>–<?php echo min($offset + $perPage, $totalJobsCount); ?></strong> of <strong><?php echo $totalJobsCount; ?></strong> positions
        </div>
        <div class="flex items-center gap-2">
          <span style="font-size: 0.84rem; color: var(--color-text-muted);">Sort by:</span>
          <select id="sortSelect" onchange="window.location.href = this.value" style="font-size: 0.84rem; font-weight: 600; color: var(--color-primary); cursor: pointer; border: 1px solid var(--color-border); border-radius: 6px; padding: 4px 8px; background: #FFFFFF;">
            <option value="<?php echo getJobFilterUrl(['sort' => 'recent', 'page' => 1]); ?>" <?php echo $sortBy === 'recent' ? 'selected' : ''; ?>>Most Recent First</option>
            <option value="<?php echo getJobFilterUrl(['sort' => 'salary_high', 'page' => 1]); ?>" <?php echo $sortBy === 'salary_high' ? 'selected' : ''; ?>>Salary: High to Low</option>
            <option value="<?php echo getJobFilterUrl(['sort' => 'experience', 'page' => 1]); ?>" <?php echo $sortBy === 'experience' ? 'selected' : ''; ?>>Experience Bracket</option>
          </select>
        </div>
      </div>

      <!-- Active Filter Badges -->
      <?php if ($hasActiveFilters): ?>
        <div class="active-filters-bar" style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
          <span style="font-size: 0.8rem; color: var(--color-text-muted); font-weight: 600;">Active Filters:</span>
          
          <?php if (!empty($searchQ)): ?>
            <a href="<?php echo getJobFilterUrl(['q' => '', 'page' => 1]); ?>" class="active-filter-pill">
              Keyword: "<?php echo htmlspecialchars($searchQ); ?>" <?php echo getIcon('x', '', 12); ?>
            </a>
          <?php endif; ?>

          <?php if (!empty($searchLoc)): ?>
            <a href="<?php echo getJobFilterUrl(['location' => '', 'page' => 1]); ?>" class="active-filter-pill">
              Location: <?php echo htmlspecialchars($searchLoc); ?> <?php echo getIcon('x', '', 12); ?>
            </a>
          <?php endif; ?>

          <?php foreach ($searchDepts as $dKey): 
            if (isset($deptOptions[$dKey])):
              // Remove this specific dept
              $newDepts = array_diff($searchDepts, [$dKey]);
          ?>
            <a href="<?php echo getJobFilterUrl(['dept' => $newDepts, 'page' => 1]); ?>" class="active-filter-pill">
              <?php echo htmlspecialchars($deptOptions[$dKey]['label']); ?> <?php echo getIcon('x', '', 12); ?>
            </a>
          <?php endif; endforeach; ?>

          <?php foreach ($searchCouncils as $cKey): 
            if (isset($councilOptions[$cKey])):
              $newCouncils = array_diff($searchCouncils, [$cKey]);
          ?>
            <a href="<?php echo getJobFilterUrl(['council' => $newCouncils, 'page' => 1]); ?>" class="active-filter-pill">
              Council: <?php echo htmlspecialchars($councilOptions[$cKey]['label']); ?> <?php echo getIcon('x', '', 12); ?>
            </a>
          <?php endif; endforeach; ?>

          <?php if (!empty($searchExp)): ?>
            <a href="<?php echo getJobFilterUrl(['experience' => '', 'page' => 1]); ?>" class="active-filter-pill">
              Exp: <?php echo htmlspecialchars($searchExp); ?> <?php echo getIcon('x', '', 12); ?>
            </a>
          <?php endif; ?>

          <a href="<?php echo BASE_URL; ?>/jobs.php" style="font-size: 0.78rem; color: #DC2626; font-weight: 700; text-decoration: underline; margin-left: 4px;">
            Clear All
          </a>
        </div>
      <?php endif; ?>

      <!-- Job List Grid -->
      <div class="grid" style="grid-template-columns: 1fr; gap: 16px;">
        <?php if (!empty($pagedJobs)): ?>
          <?php foreach ($pagedJobs as $job): ?>
            <?php require __DIR__ . '/components/job-card.php'; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- No results found -->
          <div style="background: #FFFFFF; border: 1px dashed var(--color-border); border-radius: var(--radius-lg); padding: 48px 24px; text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-secondary-soft); color: var(--color-secondary); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
              <?php echo getIcon('search', '', 24); ?>
            </div>
            <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 8px;">No matching healthcare positions found</h3>
            <p style="color: var(--color-text-secondary); font-size: 0.92rem; max-width: 480px; margin: 0 auto 20px auto;">
              Try clearing selected filters, broadening your search term, or browsing all open hospital openings.
            </p>
            <a href="<?php echo BASE_URL; ?>/jobs.php" class="btn btn-primary btn-sm">Reset All Filters</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Functional Pagination (Pages 1, 2, 3...) -->
      <?php if ($totalPages > 1): ?>
        <nav class="jobs-pagination" aria-label="Job Results Navigation">
          <!-- Previous Button -->
          <?php if ($page > 1): ?>
            <a href="<?php echo getJobFilterUrl(['page' => $page - 1]); ?>" class="btn btn-outline btn-sm">
              <?php echo getIcon('chevron-left', '', 14); ?>
              <span>Previous</span>
            </a>
          <?php else: ?>
            <button class="btn btn-outline btn-sm" disabled aria-disabled="true">
              <?php echo getIcon('chevron-left', '', 14); ?>
              <span>Previous</span>
            </button>
          <?php endif; ?>

          <!-- Numeric Page Links -->
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?php echo getJobFilterUrl(['page' => $i]); ?>" 
               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm <?php echo $i === $page ? 'active-page' : ''; ?>"
               <?php echo $i === $page ? 'aria-current="page"' : ''; ?>>
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>

          <!-- Next Button -->
          <?php if ($page < $totalPages): ?>
            <a href="<?php echo getJobFilterUrl(['page' => $page + 1]); ?>" class="btn btn-outline btn-sm">
              <span>Next</span>
              <?php echo getIcon('chevron-right', '', 14); ?>
            </a>
          <?php else: ?>
            <button class="btn btn-outline btn-sm" disabled aria-disabled="true">
              <span>Next</span>
              <?php echo getIcon('chevron-right', '', 14); ?>
            </button>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

    </main>

  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
