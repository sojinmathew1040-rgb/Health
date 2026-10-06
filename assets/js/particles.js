/**
 * HealthJobsGlobal.com - Ultra-Lightweight Three.js Particle Field
 * Optimized for 60/120fps with Zero CPU/GPU bottleneck and viewport pausing.
 */

(function () {
  // Exit early for small screens or reduced-motion
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || window.innerWidth < 768) {
    return;
  }

  const container = document.getElementById('canvas-container');
  if (!container || typeof THREE === 'undefined') return;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(60, container.clientWidth / container.clientHeight, 1, 1000);
  camera.position.z = 220;

  const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: false, powerPreference: 'low-power' });
  renderer.setSize(container.clientWidth, container.clientHeight);
  renderer.setPixelRatio(1); // Standard pixel ratio for maximum performance
  container.appendChild(renderer.domElement);

  // 24 Connected Talent & Hospital Nodes
  const particleCount = 24;
  const geometry = new THREE.BufferGeometry();
  const positions = new Float32Array(particleCount * 3);
  const velocities = [];

  for (let i = 0; i < particleCount; i++) {
    positions[i * 3] = (Math.random() - 0.5) * 320;
    positions[i * 3 + 1] = (Math.random() - 0.5) * 180;
    positions[i * 3 + 2] = (Math.random() - 0.5) * 60;

    velocities.push({
      x: (Math.random() - 0.5) * 0.15,
      y: (Math.random() - 0.5) * 0.15
    });
  }

  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

  // Point Material - Healthcare Teal
  const pMaterial = new THREE.PointsMaterial({
    color: 0x0E7490,
    size: 4,
    transparent: true,
    opacity: 0.6
  });

  const particles = new THREE.Points(geometry, pMaterial);
  scene.add(particles);

  // Pre-allocated Line Buffer (Max connections)
  const maxLines = particleCount * 4;
  const linePositions = new Float32Array(maxLines * 6);
  const lineGeometry = new THREE.BufferGeometry();
  lineGeometry.setAttribute('position', new THREE.BufferAttribute(linePositions, 3));

  const lineMaterial = new THREE.LineBasicMaterial({
    color: 0x22A6A1,
    transparent: true,
    opacity: 0.2
  });

  const linesMesh = new THREE.LineSegments(lineGeometry, lineMaterial);
  scene.add(linesMesh);

  // Mouse tilt tracking
  let mouseX = 0;
  let mouseY = 0;
  window.addEventListener('mousemove', (e) => {
    mouseX = (e.clientX - window.innerWidth / 2) * 0.03;
    mouseY = (e.clientY - window.innerHeight / 2) * 0.03;
  }, { passive: true });

  // Pause rendering when hero is scrolled out of viewport
  let isVisible = true;
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        isVisible = entry.isIntersecting;
      });
    }, { threshold: 0.05 });
    observer.observe(container);
  }

  // Animation Loop with zero allocation
  let reqId;
  function animate() {
    reqId = requestAnimationFrame(animate);

    if (!isVisible) return; // Skip work when scrolled down!

    const pos = geometry.attributes.position.array;
    const lPos = lineGeometry.attributes.position.array;
    let lineIdx = 0;

    for (let i = 0; i < particleCount; i++) {
      pos[i * 3] += velocities[i].x;
      pos[i * 3 + 1] += velocities[i].y;

      if (pos[i * 3] > 160 || pos[i * 3] < -160) velocities[i].x *= -1;
      if (pos[i * 3 + 1] > 100 || pos[i * 3 + 1] < -100) velocities[i].y *= -1;

      for (let j = i + 1; j < particleCount; j++) {
        const dx = pos[i * 3] - pos[j * 3];
        const dy = pos[i * 3 + 1] - pos[j * 3 + 1];
        const distSq = dx * dx + dy * dy;

        if (distSq < 3600 && lineIdx < maxLines) {
          const base = lineIdx * 6;
          lPos[base] = pos[i * 3];
          lPos[base + 1] = pos[i * 3 + 1];
          lPos[base + 2] = pos[i * 3 + 2];
          lPos[base + 3] = pos[j * 3];
          lPos[base + 4] = pos[j * 3 + 1];
          lPos[base + 5] = pos[j * 3 + 2];
          lineIdx++;
        }
      }
    }

    // Clear unused lines
    for (let k = lineIdx * 6; k < linePositions.length; k++) {
      lPos[k] = 0;
    }

    geometry.attributes.position.needsUpdate = true;
    lineGeometry.attributes.position.needsUpdate = true;

    camera.position.x += (mouseX - camera.position.x) * 0.03;
    camera.position.y += (-mouseY - camera.position.y) * 0.03;
    camera.lookAt(scene.position);

    renderer.render(scene, camera);
  }

  animate();

  // Resize handler
  window.addEventListener('resize', () => {
    if (!container) return;
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
  }, { passive: true });
})();
