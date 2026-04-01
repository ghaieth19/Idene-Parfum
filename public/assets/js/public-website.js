/* ═══════════════════════════════════════════════════════════════
   IDENE PARFUM — Public Website JS v2.0
   Landing Page + Catalogue Public
   ═══════════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  // ── Helpers ──────────────────────────────────────────────
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const on = (el, ev, fn, opts) => el?.addEventListener(ev, fn, opts);

  function formatDZD(amount) {
    return new Intl.NumberFormat('fr-DZ', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount) + ' DZD';
  }

  function debounce(fn, ms = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), ms);
    };
  }

  // ── Navbar scroll effect ────────────────────────────────
  const nav = $('#pubNav');
  if (nav && !nav.classList.contains('scrolled')) {
    function checkNavScroll() {
      if (window.scrollY > 40) {
        nav.classList.add('scrolled');
      } else {
        nav.classList.remove('scrolled');
      }
    }
    window.addEventListener('scroll', checkNavScroll, { passive: true });
    checkNavScroll();
  }

  // ── Mobile menu ─────────────────────────────────────────
  const mobileBtn = $('#mobileMenuBtn');
  const mobileMenu = $('#mobileMenu');
  const mobileBackdrop = $('#mobileMenuBackdrop');
  const mobileClose = $('#mobileMenuClose');

  function openMobile() {
    mobileMenu?.classList.add('open');
    mobileBackdrop?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeMobile() {
    mobileMenu?.classList.remove('open');
    mobileBackdrop?.classList.remove('open');
    document.body.style.overflow = '';
  }

  on(mobileBtn, 'click', openMobile);
  on(mobileClose, 'click', closeMobile);
  on(mobileBackdrop, 'click', closeMobile);
  $$('.pub-mobile-menu nav a').forEach(a => on(a, 'click', closeMobile));

  // ── Scroll to top ───────────────────────────────────────
  const scrollBtn = $('#scrollTopBtn');
  if (scrollBtn) {
    window.addEventListener('scroll', () => {
      scrollBtn.classList.toggle('visible', window.scrollY > 500);
    }, { passive: true });
    on(scrollBtn, 'click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  // ── Scroll reveal ───────────────────────────────────────
  const revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );
  $$('.reveal').forEach((el) => revealObserver.observe(el));

  // ── Counter animation ───────────────────────────────────
  function animateCounters() {
    $$('[data-count]').forEach((el) => {
      const target = parseInt(el.dataset.count, 10);
      if (isNaN(target) || el.dataset.counted) return;
      el.dataset.counted = '1';

      const duration = 1800;
      const start = performance.now();
      
      function tick(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(eased * target);
        el.textContent = current + (target > 99 ? '+' : '');
        if (progress < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }

  const counterObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCounters();
          counterObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );
  $$('.pub-hero-stats').forEach((el) => counterObserver.observe(el));

  // ── Segment & Stock badge helpers ───────────────────────
  function segmentBadge(segment) {
    const badges = {
      HOMME: '<span class="badge badge-info badge-dot">Homme</span>',
      FEMME: '<span class="badge badge-accent badge-dot">Femme</span>',
      MIXTE: '<span class="badge badge-primary badge-dot">Mixte</span>',
      ENFANT: '<span class="badge badge-gold badge-dot">Enfant</span>',
    };
    return badges[segment] || `<span class="badge badge-neutral">${segment}</span>`;
  }

  function stockBadge(status) {
    if (status === 'out_of_stock') return '<span class="badge badge-danger">🔴 Rupture</span>';
    if (status === 'limited') return '<span class="badge badge-warning">⚠️ Stock limité</span>';
    return '<span class="badge badge-success">✅ En stock</span>';
  }

  function perfumeEmoji(segment) {
    const emojis = { HOMME: '🧴', FEMME: '🌸', ENFANT: '🧸', MIXTE: '💧' };
    return emojis[segment] || '💧';
  }

  // ── Product card HTML ───────────────────────────────────
  function productCard(p, showPrice = false) {
    const isOut = p.stock_status === 'out_of_stock';
    const cardClass = isOut ? 'pub-product-card' : 'pub-product-card';
    const opacity = isOut ? 'style="opacity:.55"' : '';

    return `
      <article class="${cardClass}" ${opacity}>
        <div class="pub-product-visual">
          <span class="perfume-emoji">${perfumeEmoji(p.segment)}</span>
          <div class="segment-tag">${segmentBadge(p.segment)}</div>
          <div class="stock-badge">${stockBadge(p.stock_status)}</div>
        </div>
        <div class="pub-product-body">
          <h3 class="pub-product-name">${escapeHtml(p.name)}</h3>
          <p class="pub-product-group">${p.catalog_group} · ${p.code || ''}</p>
          ${showPrice
            ? `<p class="pub-product-price">${formatDZD(p.price || 0)}</p>`
            : `<p class="pub-product-price hidden-price">🔒 Connectez-vous pour voir le prix</p>`
          }
          ${!showPrice ? `
            <div class="pub-product-cta">
              <a href="/auth" class="btn btn-sm btn-outline btn-block">Se connecter pour commander</a>
            </div>
          ` : ''}
        </div>
      </article>
    `;
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // ── Landing page: Preview grid (4 featured products) ───
  const previewGrid = $('#previewGrid');
  if (previewGrid) {
    fetch('/api/public/products?featured=1&limit=8')
      .then((r) => r.json())
      .then((data) => {
        if (!data.success || !data.products.length) {
          previewGrid.innerHTML = '<p class="text-muted" style="grid-column:1/-1;text-align:center;padding:40px 0">Aucun produit disponible.</p>';
          return;
        }
        const featured = data.products.slice(0, 4);
        previewGrid.innerHTML = featured.map((p) => productCard(p, false)).join('');
      })
      .catch(() => {
        previewGrid.innerHTML = '<p class="text-muted" style="grid-column:1/-1;text-align:center;padding:40px 0">Erreur de chargement.</p>';
      });
  }

  // ── Catalog page: Full filterable grid ──────────────────
  const catalogGrid = $('#catalogGrid');
  const catalogSearch = $('#catalogSearch');
  const catalogGroup = $('#catalogGroup');
  const catalogSegment = $('#catalogSegment');
  const catalogStock = $('#catalogStock');
  const catalogResults = $('#catalogResults');

  let allProducts = [];

  if (catalogGrid) {
    loadCatalog();

    on(catalogSearch, 'input', debounce(filterAndRenderCatalog, 250));
    on(catalogGroup, 'change', filterAndRenderCatalog);
    on(catalogSegment, 'change', filterAndRenderCatalog);
    on(catalogStock, 'change', filterAndRenderCatalog);
  }

  function loadCatalog() {
    fetch('/api/public/products')
      .then((r) => r.json())
      .then((data) => {
        if (!data.success) throw new Error();
        allProducts = data.products;
        filterAndRenderCatalog();
      })
      .catch(() => {
        catalogGrid.innerHTML = '<p class="text-muted" style="grid-column:1/-1;text-align:center;padding:48px 0">Erreur de chargement du catalogue.</p>';
      });
  }

  function filterAndRenderCatalog() {
    const search = (catalogSearch?.value || '').toLowerCase().trim();
    const group = catalogGroup?.value || 'ALL';
    const segment = catalogSegment?.value || 'ALL';
    const stock = catalogStock?.value || 'ALL';

    let filtered = allProducts;

    if (search) {
      filtered = filtered.filter(
        (p) => p.name.toLowerCase().includes(search) || (p.code || '').toLowerCase().includes(search)
      );
    }

    if (group !== 'ALL') {
      filtered = filtered.filter((p) => p.catalog_group === group);
    }

    if (segment !== 'ALL') {
      filtered = filtered.filter((p) => p.segment === segment);
    }

    if (stock === 'IN_STOCK') {
      filtered = filtered.filter((p) => p.stock_status === 'in_stock');
    } else if (stock === 'LIMITED') {
      filtered = filtered.filter((p) => p.stock_status === 'limited');
    } else if (stock === 'OUT') {
      filtered = filtered.filter((p) => p.stock_status === 'out_of_stock');
    }

    if (catalogResults) {
      catalogResults.textContent = `${filtered.length} parfum${filtered.length !== 1 ? 's' : ''} trouvé${filtered.length !== 1 ? 's' : ''}`;
    }

    if (filtered.length === 0) {
      catalogGrid.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:64px 0">
          <div style="font-size:48px;margin-bottom:16px;opacity:.4">🔍</div>
          <p class="text-muted">Aucun parfum ne correspond à vos critères.</p>
          <button class="btn btn-sm btn-outline" style="margin-top:16px" onclick="document.getElementById('catalogSearch').value='';document.getElementById('catalogGroup').value='ALL';document.getElementById('catalogSegment').value='ALL';document.getElementById('catalogStock').value='ALL';filterAndRenderCatalog?.()">Réinitialiser les filtres</button>
        </div>`;
      return;
    }

    catalogGrid.innerHTML = filtered.map((p) => productCard(p, false)).join('');

    // Staggered reveal
    $$('.pub-product-card', catalogGrid).forEach((card, i) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(16px)';
      setTimeout(() => {
        card.style.transition = 'opacity .4s ease, transform .4s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, i * 40);
    });
  }

  // Expose for inline onclick
  window.filterAndRenderCatalog = filterAndRenderCatalog;

  // ── Hero Bottle Sparkles ───────────────────────────────
  const sparkleBox = $('#bottleSparkles');
  if (sparkleBox) {
    const SPARKLE_N = 10;
    const palette = [
      'rgba(215,170,77,.7)',
      'rgba(215,170,77,.45)',
      'rgba(255,255,255,.35)',
      'rgba(232,199,106,.5)',
      'rgba(255,240,200,.3)',
    ];

    function createSparkles() {
      sparkleBox.innerHTML = '';
      for (let i = 0; i < SPARKLE_N; i++) {
        const s = document.createElement('span');
        s.className = 'bottle-sparkle';
        const sz = 2 + Math.random() * 4;
        s.style.cssText =
          `--x:${32 + Math.random() * 36}%;` +
          `--y:${20 + Math.random() * 55}%;` +
          `--s:${sz}px;` +
          `--c:${palette[Math.floor(Math.random() * palette.length)]};` +
          `--travel:${-(60 + Math.random() * 90)}px;` +
          `--dur:${3 + Math.random() * 3.5}s;` +
          `--del:${Math.random() * 6}s;`;
        sparkleBox.appendChild(s);
      }
    }
    createSparkles();
    setInterval(createSparkles, 10000);
  }

  // ── Smooth anchor scrolling ─────────────────────────────
  $$('a[href^="#"]').forEach((a) => {
    on(a, 'click', (e) => {
      const target = $(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        closeMobile();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ── 3D Essence Animation (Three.js) ─────────────────────
  const essenceCanvas = document.getElementById('essenceCanvas');
  if (essenceCanvas && window.THREE) {
    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x000000, 0.001);

    const camera = new THREE.PerspectiveCamera(75, essenceCanvas.clientWidth / essenceCanvas.clientHeight, 0.1, 1000);
    camera.position.z = 30;

    const renderer = new THREE.WebGLRenderer({ canvas: essenceCanvas, alpha: true, antialias: true });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(essenceCanvas.clientWidth, essenceCanvas.clientHeight);

    // Particles
    const geometry = new THREE.BufferGeometry();
    const count = 1500;
    const positions = new Float32Array(count * 3);
    const colors = new Float32Array(count * 3);

    const color1 = new THREE.Color(0xd7aa4d); // Gold
    const color2 = new THREE.Color(0xffffff);

    for(let i=0; i<count*3; i+=3) {
      positions[i] = (Math.random() - 0.5) * 100;
      positions[i+1] = (Math.random() - 0.5) * 100;
      positions[i+2] = (Math.random() - 0.5) * 100;
      
      const mixRatio = Math.random();
      const mixedColor = color1.clone().lerp(color2, mixRatio * 0.4);
      colors[i] = mixedColor.r;
      colors[i+1] = mixedColor.g;
      colors[i+2] = mixedColor.b;
    }

    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

    const material = new THREE.PointsMaterial({
      size: 0.25,
      vertexColors: true,
      transparent: true,
      opacity: 0.9,
      blending: THREE.AdditiveBlending
    });

    const particles = new THREE.Points(geometry, material);
    scene.add(particles);

    // Mouse Interaction
    let mouseX = 0;
    let mouseY = 0;

    const windowHalfX = window.innerWidth / 2;
    const windowHalfY = window.innerHeight / 2;

    document.addEventListener('mousemove', (event) => {
      mouseX = (event.clientX - windowHalfX) * 0.05;
      mouseY = (event.clientY - windowHalfY) * 0.05;
    });

    // Resize
    window.addEventListener('resize', () => {
      camera.aspect = essenceCanvas.clientWidth / essenceCanvas.clientHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(essenceCanvas.clientWidth, essenceCanvas.clientHeight);
    });

    const clock = new THREE.Clock();

    function animate() {
      requestAnimationFrame(animate);
      const elapsedTime = clock.getElapsedTime();

      particles.rotation.y += 0.0015;
      particles.rotation.x += 0.0005;

      camera.position.x += (mouseX - camera.position.x) * 0.05;
      camera.position.y += (-mouseY - camera.position.y) * 0.05;
      camera.lookAt(scene.position);

      const positionsAttr = geometry.attributes.position;
      const posArray = positionsAttr.array;
      for(let i=0; i<count; i++) {
        const i3 = i * 3;
        const x = posArray[i3];
        const currentY = posArray[i3 + 1];
        posArray[i3 + 1] = currentY + Math.sin(elapsedTime * 1.5 + x) * 0.012;
      }
      positionsAttr.needsUpdate = true;

      renderer.render(scene, camera);
    }

    animate();
  }

})();
