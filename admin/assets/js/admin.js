/**
 * HealthJobsGlobal — Admin Interactive Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Sidebar Toggle
  const mobileToggle = document.getElementById('mobileSidebarToggle');
  const sidebar = document.getElementById('adminSidebar');

  if (mobileToggle && sidebar) {
    mobileToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });

    // Close when clicking outside on mobile
    document.addEventListener('click', function (e) {
      if (window.innerWidth <= 1024 && sidebar.classList.contains('open')) {
        if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
          sidebar.classList.remove('open');
        }
      }
    });
  }

  // 2. Generic Modal Open & Close Handler
  const modalTriggers = document.querySelectorAll('[data-modal-target]');
  const modalCloses = document.querySelectorAll('[data-modal-close]');

  modalTriggers.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const targetId = this.getAttribute('data-modal-target');
      const modal = document.getElementById(targetId);
      if (modal) {
        modal.classList.add('active');
      }
    });
  });

  modalCloses.forEach(btn => {
    btn.addEventListener('click', function () {
      const modal = this.closest('.modal-overlay');
      if (modal) {
        modal.classList.remove('active');
      }
    });
  });

  // Close modal when clicking backdrop
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === this) {
        this.classList.remove('active');
      }
    });
  });

  // 3. Live Table Search Filter
  const tableSearchInput = document.getElementById('tableSearchInput');
  if (tableSearchInput) {
    tableSearchInput.addEventListener('input', function () {
      const query = this.value.toLowerCase().trim();
      const table = document.querySelector('.admin-table tbody');
      if (!table) return;

      const rows = table.querySelectorAll('tr');
      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        if (text.includes(query)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  }

  // 4. Toast notification helper
  window.showToast = function (message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  };
});
