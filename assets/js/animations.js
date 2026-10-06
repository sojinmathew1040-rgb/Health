/**
 * HealthJobsGlobal.com - Ultra-Fast GSAP & Scroll Animations
 * Uses native hardware-accelerated 60/120fps scrolling (no scroll hijacking or input lag).
 */

document.addEventListener('DOMContentLoaded', () => {
  initGSAPAnimations();
  initHeroParallax();
});

/**
 * GSAP ScrollTrigger Reveals & Counter Animations
 */
function initGSAPAnimations() {
  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

  gsap.registerPlugin(ScrollTrigger);

  // Hero Text Stagger reveal
  gsap.from('.hero-text-area > *', {
    y: 20,
    opacity: 0,
    duration: 0.6,
    stagger: 0.1,
    ease: 'power2.out',
    delay: 0.05
  });

  // Hero visual card reveal
  gsap.from('.hero-main-card', {
    scale: 0.96,
    opacity: 0,
    duration: 0.7,
    ease: 'power2.out',
    delay: 0.15
  });

  // Trust statistics counter animation
  const stats = document.querySelectorAll('.stat-number');
  stats.forEach((stat) => {
    const rawVal = stat.getAttribute('data-count') || '0';
    const target = parseInt(rawVal.replace(/[^0-9]/g, '')) || 0;
    const suffix = rawVal.replace(/[0-9]/g, '');

    ScrollTrigger.create({
      trigger: stat,
      start: 'top 90%',
      once: true,
      onEnter: () => {
        gsap.fromTo(
          { val: 0 },
          { val: target },
          {
            duration: 1.5,
            ease: 'power2.out',
            onUpdate: function () {
              stat.textContent = Math.floor(this.targets()[0].val).toLocaleString() + suffix;
            }
          }
        );
      }
    });
  });

  // Lightweight reveal for headers
  gsap.utils.toArray('.section-header').forEach((header) => {
    gsap.from(header, {
      scrollTrigger: {
        trigger: header,
        start: 'top 95%',
        once: true
      },
      y: 18,
      opacity: 0,
      duration: 0.5,
      ease: 'power2.out'
    });
  });

  // Card grids render cleanly with 100% native CSS visibility (no opacity tampering or scroll freeze)
  // Ensures all job cards, department cards, and hospital cards are instantly and crisp-clearly visible
}

/**
 * Hero Visual Tilt (Passive, zero-overhead)
 */
function initHeroParallax() {
  // Floating cards removed per user preference for a clean, non-cluttered layout
}
