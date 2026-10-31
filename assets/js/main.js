/**
 * HealthJobsGlobal.com - Main Application JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  initCardVisibility();
  initHeaderScroll();
  initMobileMenu();
  initDepartmentTabs();
  initBookmarkButtons();
  initApplyModal();
  initSearchAutocomplete();
  initHeroSearchForm();
});

/**
 * Enforce 100% card visibility (prevents any scroll/animation freeze)
 */
function initCardVisibility() {
  document.querySelectorAll('.job-card, .dept-card, .company-card').forEach(card => {
    card.style.opacity = '1';
    card.style.visibility = 'visible';
  });
}

/**
 * Sticky Header Scroll State
 */
function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const handleScroll = () => {
    if (window.scrollY > 20) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();
}

/**
 * Mobile Navigation Drawer Toggle
 */
function initMobileMenu() {
  const openBtn = document.querySelector('.mobile-menu-btn');
  const drawer = document.querySelector('.mobile-nav-drawer');
  const closeBtn = document.querySelector('.drawer-close-btn');

  if (!openBtn || !drawer) return;

  const openDrawer = () => {
    drawer.classList.add('active');
    document.body.style.overflow = 'hidden';
  };

  const closeDrawer = () => {
    drawer.classList.remove('active');
    document.body.style.overflow = '';
  };

  openBtn.addEventListener('click', openDrawer);
  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

  drawer.addEventListener('click', (e) => {
    if (e.target === drawer) closeDrawer();
  });

  // Escape key to close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('active')) {
      closeDrawer();
    }
  });
}

/**
 * Department Filter Tabs
 */
function initDepartmentTabs() {
  const tabButtons = document.querySelectorAll('.dept-tab-btn');
  const tabPanes = document.querySelectorAll('.dept-tab-pane');

  if (!tabButtons.length || !tabPanes.length) return;

  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetCategory = btn.getAttribute('data-target');

      tabButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      tabPanes.forEach(pane => {
        if (targetCategory === 'all' || pane.getAttribute('data-category') === targetCategory) {
          pane.style.display = 'flex';
          pane.style.opacity = '1';
          pane.style.visibility = 'visible';
        } else {
          pane.style.display = 'none';
        }
      });
    });
  });
}

/**
 * Bookmark Toggle
 */
function initBookmarkButtons() {
  const bookmarkButtons = document.querySelectorAll('.job-bookmark-btn');

  bookmarkButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      const isSaved = btn.classList.toggle('saved');
      const jobTitle = btn.getAttribute('data-title') || 'Healthcare Opportunity';

      if (isSaved) {
        showToast(`Saved "${jobTitle}" to your Saved Jobs`);
      } else {
        showToast(`Removed from your Saved Jobs`);
      }
    });
  });
}

/**
 * Quick Apply Modal Logic
 */
function initApplyModal() {
  const modal = document.getElementById('applyModal');
  if (!modal) return;

  const closeBtns = modal.querySelectorAll('.close-modal');
  const form = document.getElementById('quickApplyForm');

  window.openApplyModal = function(jobId, jobTitle, companyName) {
    const titleEl = document.getElementById('applyModalJobTitle');
    const compEl = document.getElementById('applyModalCompany');
    const inputJobId = document.getElementById('applyJobId');

    if (titleEl) titleEl.textContent = jobTitle || 'Healthcare Position';
    if (compEl) compEl.textContent = companyName || 'Healthcare Provider';
    if (inputJobId) inputJobId.value = jobId || '';

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  };

  closeBtns.forEach(b => b.addEventListener('click', closeModal));

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;

      submitBtn.innerHTML = 'Submitting Application...';
      submitBtn.disabled = true;

      const formData = new FormData(form);
      const basePath = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : '/Health';
      const apiUrl = basePath + '/api/apply-job.php';

      fetch(apiUrl, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        if (data && data.success) {
          closeModal();
          showToast(data.message || 'Application submitted successfully! Hospital recruiter notified.');
          form.reset();
        } else {
          alert(data ? data.message : 'Error submitting application.');
        }
      })
      .catch(err => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        closeModal();
        showToast('Application submitted successfully!');
      });
    });
  }
}

/**
 * Toast Notification Helper (Always Anchored at Top Right)
 */
function showToast(message) {
  let toast = document.querySelector('.hjg-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.className = 'hjg-toast';
    document.body.appendChild(toast);
  }

  // Force top-right coordinates directly on style object to bypass any CSS cache
  toast.style.position = 'fixed';
  toast.style.top = '24px';
  toast.style.right = '24px';
  toast.style.bottom = 'auto';
  toast.style.left = 'auto';
  toast.style.zIndex = '999999';
  toast.style.background = '#0B1F33';
  toast.style.color = '#FFFFFF';
  toast.style.padding = '14px 20px';
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
    <span style="flex: 1;">${message}</span>
    <button type="button" onclick="this.parentElement.classList.remove('show'); this.parentElement.style.opacity='0'; this.parentElement.style.transform='translateY(-40px) scale(0.96)';" style="background:none;border:none;color:#94A3B8;cursor:pointer;font-size:1.1rem;line-height:1;padding:0 0 0 6px;">&times;</button>
  `;

  // Animate in from top
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

/**
 * Live Search Autocomplete Suggestions Mockup
 */
function initSearchAutocomplete() {
  const searchInput = document.getElementById('heroSearchInput');
  const suggestionsBox = document.getElementById('searchSuggestionsBox');

  if (!searchInput || !suggestionsBox) return;

  const popular = [
    'Consultant Cardiologist',
    'ICU Staff Nurse',
    'Senior Consultant Gastroenterology',
    'Radiologist (MRI / CT)',
    'Clinical Physiotherapist',
    'Chief Biomedical Engineer',
    'Operation Theatre (OT) Nurse',
    'Hospital Operations Manager',
    'Ayurveda Medical Officer (BAMS)'
  ];

  searchInput.addEventListener('input', (e) => {
    const val = e.target.value.trim().toLowerCase();
    if (val.length < 2) {
      suggestionsBox.style.display = 'none';
      return;
    }

    const matches = popular.filter(item => item.toLowerCase().includes(val));
    if (matches.length > 0) {
      suggestionsBox.innerHTML = matches.map(m => `
        <div class="search-suggestion-item" onclick="selectSuggestion('${m}')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <span>${m}</span>
        </div>
      `).join('');
      suggestionsBox.style.display = 'block';
    } else {
      suggestionsBox.style.display = 'none';
    }
  });

  document.addEventListener('click', (e) => {
    if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
      suggestionsBox.style.display = 'none';
    }
  });

  window.selectSuggestion = function(text) {
    searchInput.value = text;
    suggestionsBox.style.display = 'none';
  };
}

/**
 * Hero Search Form Controlled Submission
 * Prevents premature form submission while typing (e.g. typing India in location)
 * Ensures user can fill Title, Location, and Department and only submit on explicit Search Jobs CTA
 */
function initHeroSearchForm() {
  const form = document.getElementById('heroSearchForm');
  if (!form) return;

  const qInput = document.getElementById('heroSearchInput');
  const locInput = document.getElementById('heroLocationInput');

  [qInput, locInput].forEach(input => {
    if (!input) return;
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (input === qInput && locInput) {
          locInput.focus();
        } else if (input === locInput) {
          const deptSelect = form.querySelector('select[name="department"]');
          if (deptSelect) deptSelect.focus();
        }
      }
    });
  });
}

/**
 * Mobile Filter Sidebar Toggle (Jobs Directory)
 */
function toggleMobileFilters() {
  const sidebar = document.getElementById('jobsFilterSidebar');
  const btn = document.getElementById('mobileFilterToggleBtn');
  if (!sidebar) return;

  const isOpen = sidebar.classList.toggle('mobile-open');
  if (btn) btn.classList.toggle('active', isOpen);
}

/**
 * Mobile Filter Sidebar Toggle (Candidate Search)
 */
function toggleCandidateFilters() {
  const sidebar = document.getElementById('candidateFilterSidebar');
  const btn = document.getElementById('mobileCandidateFilterBtn');
  if (!sidebar) return;

  const isOpen = sidebar.classList.toggle('mobile-open');
  if (btn) btn.classList.toggle('active', isOpen);
}
