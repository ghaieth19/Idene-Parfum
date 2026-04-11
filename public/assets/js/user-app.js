(function () {
  'use strict';

  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const on = (el, ev, fn) => el?.addEventListener(ev, fn);
  const debounce = (fn, ms = 300) => {
    let t;
    return (...a) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...a), ms);
    };
  };
  const esc = (t) => {
    const d = document.createElement('div');
    d.textContent = t ?? '';
    return d.innerHTML;
  };
  const formatDT = (n) => new Intl.NumberFormat('fr-TN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Number(n || 0)) + ' DT';
  const fmtDate = (d) => {
    const dt = new Date(d);
    return isNaN(dt) ? '-' : dt.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
  };
  const fmtTime = (d) => {
    const dt = new Date(d);
    return isNaN(dt) ? '' : dt.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  };
  const bottleLabel = (qty) => `${qty} bouteille${qty > 1 ? 's' : ''}`;

  const themeToggleBtn = document.getElementById('themeToggleBtn');
  let themeToggleLock = false;
  const applyTheme = (theme) => {
    const root = document.documentElement;
    root.classList.add('theme-switching');
    root.setAttribute('data-theme', theme);
    localStorage.setItem('idene-user-theme', theme);
    if (themeToggleBtn) {
      const icon = themeToggleBtn.querySelector('i');
      if (icon) {
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        icon.style.color = theme === 'dark' ? '#FFF' : '';
      }
    }
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        root.classList.remove('theme-switching');
      });
    });
  };
  applyTheme(localStorage.getItem('idene-user-theme') || 'light');
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      if (themeToggleLock) return;
      themeToggleLock = true;
      const current = document.documentElement.getAttribute('data-theme') || 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark');
      setTimeout(() => {
        themeToggleLock = false;
      }, 300);
    });
  }

  const statusColors = {
    BROUILLON: 'status-brouillon',
    CONFIRMEE: 'status-confirmee',
    EN_PREPARATION: 'status-en_preparation',
    EXPEDIEE: 'status-expediee',
    LIVREE: 'status-livree',
    ANNULEE: 'status-annulee',
    NON_PAYE: 'status-non_paye',
    PARTIEL: 'status-partiel',
    PAYE: 'status-paye',
    ANNULE: 'status-annule'
  };
  const statusPill = (s) => `<span class="status-pill ${statusColors[s] || ''}">${String(s || '').replace(/_/g, ' ')}</span>`;
  const segBadge = (s) => {
    const cls = { HOMME: 'badge-info', FEMME: 'badge-accent', MIXTE: 'badge-primary', ENFANT: 'badge-gold' };
    return `<span class="badge ${cls[s] || 'badge-neutral'} badge-dot">${esc(s)}</span>`;
  };
  const stockBadge = (st) => {
    if (st === 'out_of_stock') return '<span class="badge badge-danger">Rupture</span>';
    if (st === 'limited') return '<span class="badge badge-warning">Limite</span>';
    return '<span class="badge badge-success">En stock</span>';
  };
  const regionSelect = $('#ckRegion');
  const regionMobileBtn = $('#ckRegionMobileBtn');
  const regionMobileLabel = $('#ckRegionMobileLabel');
  const regionSheet = $('#ckRegionSheet');
  const regionSheetBackdrop = $('#ckRegionSheetBackdrop');
  const regionSheetCloseBtn = $('#ckRegionCloseBtn');
  const regionOptions = $('#ckRegionOptions');
  const syncStatusChipGroup = (selectId) => {
    const select = document.getElementById(selectId);
    if (!(select instanceof HTMLSelectElement)) return;

    const chips = document.querySelectorAll(`[data-filter-chips="${selectId}"] .status-chip`);
    chips.forEach((chip) => {
      chip.classList.toggle('is-active', chip.dataset.filterValue === select.value);
    });
  };

  const initStatusChipGroup = (selectId, onChange) => {
    const select = document.getElementById(selectId);
    if (!(select instanceof HTMLSelectElement)) return;

    const group = document.querySelector(`[data-filter-chips="${selectId}"]`);
    if (!group) return;

    group.addEventListener('click', (event) => {
      const chip = event.target instanceof Element ? event.target.closest('.status-chip') : null;
      if (!(chip instanceof HTMLButtonElement)) return;

      const nextValue = chip.dataset.filterValue || 'ALL';
      if (select.value !== nextValue) {
        select.value = nextValue;
        onChange();
      }

      syncStatusChipGroup(selectId);
    });

    select.addEventListener('change', () => {
      syncStatusChipGroup(selectId);
    });

    syncStatusChipGroup(selectId);
  };
  const syncMobileRegionPicker = () => {
    if (!regionSelect || !regionMobileLabel) return;

    const selectedOption = regionSelect.options[regionSelect.selectedIndex];
    const label = selectedOption?.textContent?.trim() || 'Choisir un gouvernorat';
    regionMobileLabel.textContent = label;

    if (regionOptions) {
      regionOptions.querySelectorAll('.mobile-region-option').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.regionValue === regionSelect.value);
      });
    }
  };
  const closeMobileRegionSheet = () => {
    if (!regionSheet) return;
    regionSheet.classList.remove('is-open');
    regionSheet.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('region-sheet-open');
    regionMobileBtn?.setAttribute('aria-expanded', 'false');
  };
  const openMobileRegionSheet = () => {
    if (!regionSheet || !regionOptions || !(regionSelect instanceof HTMLSelectElement)) return;

    const optionsHtml = [...regionSelect.options].map((option) => {
      const value = String(option.value || '');
      const label = esc(option.textContent || '');
      const activeClass = value === regionSelect.value ? ' is-active' : '';

      return `<button type="button" class="mobile-region-option${activeClass}" data-region-value="${esc(value)}">${label}</button>`;
    }).join('');

    regionOptions.innerHTML = optionsHtml;
    regionSheet.classList.add('is-open');
    regionSheet.setAttribute('aria-hidden', 'false');
    document.body.classList.add('region-sheet-open');
    regionMobileBtn?.setAttribute('aria-expanded', 'true');
  };

  if (regionSelect instanceof HTMLSelectElement) {
    regionSelect.addEventListener('change', syncMobileRegionPicker);
    syncMobileRegionPicker();
  }

  regionMobileBtn?.addEventListener('click', openMobileRegionSheet);
  regionSheetBackdrop?.addEventListener('click', closeMobileRegionSheet);
  regionSheetCloseBtn?.addEventListener('click', closeMobileRegionSheet);
  regionOptions?.addEventListener('click', (event) => {
    const optionButton = event.target instanceof Element ? event.target.closest('.mobile-region-option') : null;
    if (!(optionButton instanceof HTMLButtonElement) || !(regionSelect instanceof HTMLSelectElement)) return;

    regionSelect.value = optionButton.dataset.regionValue || '';
    regionSelect.dispatchEvent(new Event('change', { bubbles: true }));
    closeMobileRegionSheet();
  });

  const views = {
    dashboard: 'viewDashboard',
    shop: 'viewShop',
    orders: 'viewOrders',
    invoices: 'viewInvoices',
    profile: 'viewProfile'
  };

  const profileMessage = $('#profileMessage');
  const passwordMessage = $('#passwordMessage');
  const faceAccountInfo = $('#faceAccountInfo');
  const faceAccountSetup = $('#faceAccountSetup');
  const faceAccountMessage = $('#faceAccountMessage');
  const accountFaceVideo = $('#accountFaceVideo');
  const accountFaceCanvas = $('#accountFaceCanvas');
  const accountFacePassword = $('#faceAccountPassword');
  const accountFaceOpenBtn = $('#accountFaceOpenBtn');
  const accountFaceSaveBtn = $('#accountFaceSaveBtn');

  const API = {
    profile: '/api/client/profile',
    password: '/api/client/password',
    faceStatus: '/api/client/account/face-status',
    faceCreate: '/api/client/account/face'
  };

  let shopProducts = [];
  let cart = [];
  let currentSegment = 'ALL';
  let allOrders = [];
  let allInvoices = [];
  let dashLoaded = false;
  let editingOrderId = null;
  let accountFaceStream = null;
  let accountFaceStatusLoaded = false;
  const FACE_MATRIX_SIZE = 32;
  const FACE_CAPTURE_SAMPLES = 5;
  const FACE_CAPTURE_DELAY_MS = 120;
  const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

  function renderBottomNav(name) {
    $$('.mobile-bottom-nav button').forEach((b) => b.classList.toggle('active', b.dataset.view === name));
  }

  window.switchView = function (name) {
    if (name !== 'profile') {
      stopAccountFaceCamera();
    }
    $$('.view').forEach((v) => v.classList.remove('active'));
    const el = $(`#${views[name]}`);
    if (el) el.classList.add('active');
    renderBottomNav(name);

    if (name === 'dashboard') loadDashboard();
    if (name === 'shop') loadShop();
    if (name === 'orders') loadOrders();
    if (name === 'invoices') loadInvoices();
    if (name === 'profile') loadFaceAccountStatus();
    window.location.hash = name;
  };

  const profileBtn = $('#profileDropdownBtn');
  const profileMenu = $('#profileDropdown');
  on($('.eco-brand'), 'click', (e) => {
    e.preventDefault();
    window.location.assign('/accueil');
  });
  on(profileBtn, 'click', (e) => {
    e.stopPropagation();
    profileMenu?.classList.toggle('show');
  });
  window.closeProfileDropdown = () => profileMenu?.classList.remove('show');
  on(document, 'click', (e) => {
    if (!profileBtn?.contains(e.target) && !profileMenu?.contains(e.target)) {
      window.closeProfileDropdown();
    }
  });
  on($('#mobileNavToggle'), 'click', () => $('#ecoMobileMenu')?.classList.toggle('open'));
  on($('#floatingCartBtn'), 'click', () => document.body.classList.add('cart-open'));
  window.logoutUser = async function () {
    try {
      await fetch('/api/auth/logout', { method: 'POST' });
    } finally {
      window.location.href = '/accueil';
    }
  };

  const setFeedback = (el, message, type = 'success') => {
    if (!el) return;
    if (!message) {
      el.innerHTML = '';
      return;
    }
    const cls = type === 'error' ? 'alert-danger' : 'alert-success';
    el.innerHTML = `<div class="alert ${cls} mt-2">${esc(message)}</div>`;
  };

  const fetchJson = async (url, options = {}) => {
    const response = await fetch(url, {
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) },
      ...options
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.error || data.message || 'Erreur serveur.');
    }
    return data;
  };

  const stopAccountFaceCamera = () => {
    if (accountFaceStream) {
      accountFaceStream.getTracks().forEach((track) => track.stop());
      accountFaceStream = null;
    }
    if (accountFaceVideo) {
      accountFaceVideo.srcObject = null;
    }
  };

  const explainCameraError = (error) => {
    const raw = typeof error?.message === 'string' ? error.message : '';
    const name = typeof error?.name === 'string' ? error.name : '';
    if (!window.isSecureContext) return 'La camera exige http://localhost ou https.';
    if (name === 'NotAllowedError' || name === 'PermissionDeniedError') return 'Acces a la camera refuse.';
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') return 'Aucune camera disponible.';
    return raw || 'Impossible d ouvrir la camera.';
  };

  const clampCrop = (crop, width, height) => {
    const cropWidth = Math.max(1, Math.min(crop.width, width));
    const cropHeight = Math.max(1, Math.min(crop.height, height));

    return {
      x: Math.min(Math.max(0, crop.x), Math.max(0, width - cropWidth)),
      y: Math.min(Math.max(0, crop.y), Math.max(0, height - cropHeight)),
      width: cropWidth,
      height: cropHeight
    };
  };

  const normalizeCapturedMatrix = (matrix) => {
    if (!Array.isArray(matrix) || matrix.length !== FACE_MATRIX_SIZE * FACE_MATRIX_SIZE) {
      throw new Error('Capture visage invalide.');
    }

    let min = Number.POSITIVE_INFINITY;
    let max = Number.NEGATIVE_INFINITY;
    for (const value of matrix) {
      min = Math.min(min, value);
      max = Math.max(max, value);
    }

    const range = max - min;
    if (range < 0.02) {
      throw new Error('Rapprochez votre visage et assurez un bon eclairage.');
    }

    return matrix.map((value) => Number(((value - min) / range).toFixed(6)));
  };

  const captureSingleFaceMatrix = async (videoEl, canvasEl) => {
    if (!(videoEl instanceof HTMLVideoElement) || !(canvasEl instanceof HTMLCanvasElement)) {
      throw new Error('Camera indisponible.');
    }

    const context = canvasEl.getContext('2d', { willReadFrequently: true });
    if (!context) throw new Error('Canvas indisponible.');

    const width = videoEl.videoWidth || 640;
    const height = videoEl.videoHeight || 480;
    canvasEl.width = width;
    canvasEl.height = height;
    context.drawImage(videoEl, 0, 0, width, height);

    const fallbackSize = Math.min(width * 0.62, height * 0.62);
    let crop = clampCrop({
      x: (width - fallbackSize) / 2,
      y: height * 0.14,
      width: fallbackSize,
      height: fallbackSize
    }, width, height);

    if ('FaceDetector' in window) {
      try {
        const detector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
        const faces = await detector.detect(canvasEl);
        if (faces.length > 0) {
          const box = faces[0].boundingBox;
          const size = Math.max(box.width, box.height) * 1.45;
          crop = clampCrop({
            x: box.x + (box.width - size) / 2,
            y: box.y + (box.height - size) / 2 - size * 0.08,
            width: size,
            height: size
          }, width, height);
        }
      } catch (_) {}
    }

    const matrixCanvas = document.createElement('canvas');
    matrixCanvas.width = FACE_MATRIX_SIZE;
    matrixCanvas.height = FACE_MATRIX_SIZE;
    const matrixContext = matrixCanvas.getContext('2d', { willReadFrequently: true });
    if (!matrixContext) throw new Error('Canvas de matrice indisponible.');

    matrixContext.drawImage(canvasEl, crop.x, crop.y, crop.width, crop.height, 0, 0, FACE_MATRIX_SIZE, FACE_MATRIX_SIZE);
    const { data } = matrixContext.getImageData(0, 0, FACE_MATRIX_SIZE, FACE_MATRIX_SIZE);
    const matrix = [];
    for (let i = 0; i < data.length; i += 4) {
      const grayscale = (0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2]) / 255;
      matrix.push(grayscale);
    }

    return normalizeCapturedMatrix(matrix);
  };

  const buildFaceMatrixFromElements = async (videoEl, canvasEl) => {
    const samples = [];
    let lastError = null;

    for (let index = 0; index < FACE_CAPTURE_SAMPLES; index += 1) {
      try {
        samples.push(await captureSingleFaceMatrix(videoEl, canvasEl));
      } catch (error) {
        lastError = error;
      }

      if (index < FACE_CAPTURE_SAMPLES - 1) {
        await wait(FACE_CAPTURE_DELAY_MS);
      }
    }

    if (samples.length < 3) {
      throw lastError || new Error('Capture visage impossible.');
    }

    return samples[0].map((_, matrixIndex) => {
      let total = 0;
      for (const sample of samples) {
        total += sample[matrixIndex];
      }

      return Number((total / samples.length).toFixed(6));
    });
  };

  async function loadFaceAccountStatus(force = false) {
    if (!faceAccountInfo) return;
    if (accountFaceStatusLoaded && !force) return;

    try {
      const data = await fetchJson(API.faceStatus);
      accountFaceStatusLoaded = true;
      if (data.has_face_profile) {
        faceAccountInfo.textContent = 'Votre visage est deja configure. Si la connexion ne marche pas, vous pouvez reconfigurer votre visage ici avec votre mot de passe.';
        if (faceAccountSetup) faceAccountSetup.style.display = '';
        if (accountFaceSaveBtn) accountFaceSaveBtn.textContent = 'Reconfigurer mon visage';
      } else {
        faceAccountInfo.textContent = 'Ajoutez votre visage une seule fois. Votre mot de passe actuel sera demande avant l enregistrement.';
        if (faceAccountSetup) faceAccountSetup.style.display = '';
        if (accountFaceSaveBtn) accountFaceSaveBtn.textContent = 'Valider mon visage';
      }
    } catch (error) {
      faceAccountInfo.textContent = error.message || 'Impossible de charger le statut biometrique.';
      if (faceAccountSetup) faceAccountSetup.style.display = 'none';
    }
  }

  function loadDashboard() {
    if (dashLoaded) return;
    Promise.all([
      fetch('/api/client/dashboard').then((r) => r.json()),
      fetch('/api/shop/products').then((r) => r.json())
    ]).then(([dash, prod]) => {
      dashLoaded = true;
      const stats = dash.stats || {};
      const products = prod.products || [];
      if (shopProducts.length === 0) shopProducts = products;

      $('#kpiOrders').textContent = stats.total_orders ?? 0;
      $('#kpiTotal').textContent = formatDT(stats.total_amount ?? 0);
      $('#kpiUnpaid').textContent = formatDT(stats.unpaid_amount ?? 0);
      $('#kpiMonth').textContent = formatDT(stats.month_amount ?? 0);

      const totalProducts = products.length;
      const inStock = products.filter((p) => p.stock_status === 'in_stock').length;
      const limited = products.filter((p) => p.stock_status === 'limited').length;
      const outStock = products.filter((p) => p.stock_status === 'out_of_stock').length;
      const pctIn = totalProducts ? Math.round(inStock / totalProducts * 100) : 0;
      const pctLimited = totalProducts ? Math.round(limited / totalProducts * 100) : 0;
      const pctOut = totalProducts ? Math.round(outStock / totalProducts * 100) : 0;
      const groups = {};
      products.forEach((p) => {
        groups[p.catalog_group] = (groups[p.catalog_group] || 0) + 1;
      });

      $('#dashProductStats').innerHTML = `
        <div class="dash-stat-card">
          <div class="dash-stat-number">${totalProducts}</div>
          <div class="dash-stat-label" data-i18n="dash.products">Produits au catalogue</div>
          <div class="dash-stat-detail">${Object.keys(groups).length} <span data-i18n="dash.categories">categories</span></div>
        </div>
        <div class="dash-stat-card dash-stat-success">
          <div class="dash-stat-number">${inStock}</div>
          <div class="dash-stat-label" data-i18n="dash.inStock">En stock</div>
          <div class="dash-stat-bar"><div class="dash-stat-fill dash-fill-success" style="width:${pctIn}%"></div></div>
          <div class="dash-stat-detail">${pctIn}% <span data-i18n="dash.ofCatalog">du catalogue</span></div>
        </div>
        <div class="dash-stat-card dash-stat-warning">
          <div class="dash-stat-number">${limited}</div>
          <div class="dash-stat-label" data-i18n="dash.limitedStock">Stock limite</div>
          <div class="dash-stat-bar"><div class="dash-stat-fill dash-fill-warning" style="width:${pctLimited}%"></div></div>
          <div class="dash-stat-detail">${pctLimited}% <span data-i18n="dash.ofCatalog">du catalogue</span></div>
        </div>
        <div class="dash-stat-card dash-stat-danger">
          <div class="dash-stat-number">${outStock}</div>
          <div class="dash-stat-label" data-i18n="dash.outOfStock">En rupture</div>
          <div class="dash-stat-bar"><div class="dash-stat-fill dash-fill-danger" style="width:${pctOut}%"></div></div>
          <div class="dash-stat-detail">${pctOut}% <span data-i18n="dash.ofCatalog">du catalogue</span></div>
        </div>`;

      const segments = {};
      products.forEach((p) => { segments[p.segment] = (segments[p.segment] || 0) + 1; });
      const segIcons = { HOMME: 'bi-gender-male', FEMME: 'bi-gender-female', MIXTE: 'bi-gender-ambiguous', ENFANT: 'bi-balloon' };
      const segColors = { HOMME: '#1565C0', FEMME: '#AD1457', MIXTE: '#6A1B9A', ENFANT: '#E65100' };
      let segHtml = '';
      Object.entries(segments).sort((a, b) => b[1] - a[1]).forEach(([seg, cnt]) => {
        const pct = Math.round(cnt / Math.max(totalProducts, 1) * 100);
        segHtml += `<div class="dash-breakdown-item">
          <div class="dash-breakdown-left">
            <i class="bi ${segIcons[seg] || 'bi-droplet'}" style="color:${segColors[seg] || '#333'}"></i>
            <span>${esc(seg)}</span>
          </div>
          <div class="dash-breakdown-right">
            <div class="dash-breakdown-bar-wrap"><div class="dash-breakdown-bar" style="width:${pct}%;background:${segColors[seg] || '#333'}"></div></div>
            <strong>${cnt}</strong>
          </div>
        </div>`;
      });
      $('#dashSegmentBreakdown').innerHTML = segHtml || '<p class="muted">Aucun produit.</p>';

      const recent = dash.recent_orders || [];
      if (!recent.length) {
        $('#dashRecentOrders').innerHTML = '<p class="muted" style="padding:16px" data-i18n="dash.noOrders">Aucune commande.</p>';
      } else {
        let html = '<table class="dash-mini-table"><thead><tr><th data-i18n="dash.number">Numero</th><th data-i18n="dash.amount">Montant</th><th data-i18n="dash.status">Statut</th></tr></thead><tbody>';
        recent.forEach((o) => {
          html += `<tr><td>${esc(o.order_number)}</td><td>${formatDT(o.total_dzd)}</td><td>${statusPill(o.status)}</td></tr>`;
        });
        html += '</tbody></table>';
        $('#dashRecentOrders').innerHTML = html;
      }

      const topPerfumes = dash.top_perfumes || [];
      if (!topPerfumes.length) {
        $('#dashTopPerfumes').innerHTML = '<p class="muted" style="padding:16px" data-i18n="dash.noSales">Aucune vente disponible pour le moment.</p>';
      } else {
        $('#dashTopPerfumes').innerHTML = topPerfumes.map((perfume, index) => `
          <article class="dash-top-card">
            <div class="dash-top-rank">#${index + 1}</div>
            <div class="dash-top-copy">
              <h4>${esc(perfume.name || '-')}</h4>
              <p>${esc(perfume.segment || 'AUTRE')} - ${esc(perfume.catalog_group || 'Catalogue')}</p>
            </div>
            <div class="dash-top-metrics">
              <strong>${Number(perfume.sold_qty || 0)} <span data-i18n="dash.sales">ventes</span></strong>
              <span>${Number(perfume.orders_count || 0)} <span data-i18n="dash.ordersMin">commandes</span></span>
            </div>
          </article>
        `).join('');
      }
    }).catch(() => {
      $('#dashProductStats').innerHTML = '<p class="text-danger">Erreur de chargement.</p>';
      $('#dashTopPerfumes').innerHTML = '<p class="text-danger">Erreur de chargement.</p>';
    });
  }

  function renderShopProducts() {
    const search = ($('#shopSearch')?.value || $('#shopSearchMobile')?.value || '').toLowerCase();
    let filtered = shopProducts;
    if (search) {
      filtered = filtered.filter((p) => p.name.toLowerCase().includes(search) || String(p.code || '').toLowerCase().includes(search));
    }
    if (currentSegment !== 'ALL') {
      filtered = filtered.filter((p) => p.segment === currentSegment);
    }

    $('#shopResultsCount').textContent = `${filtered.length} parfum${filtered.length !== 1 ? 's' : ''}`;
    if (!filtered.length) {
      $('#shopProductGrid').innerHTML = '<p class="muted" style="grid-column:1/-1;text-align:center;padding:40px;" data-i18n="dash.noMatch">Aucun parfum ne correspond a vos criteres.</p>';
      return;
    }

    $('#shopProductGrid').innerHTML = filtered.map((p, index) => {
      const isOut = p.stock_status === 'out_of_stock';
      return `<div class="pcard ${isOut ? 'is-out' : ''}" data-id="${p.id}" style="animation-delay:${index * 0.05}s">
        <div class="pcard-visual">
          <img src="/assets/images/perfume-bottle.png" alt="Parfum" class="perfume-image">
          <span class="seg-tag">${segBadge(p.segment)}</span>
          <span class="stk-tag">${stockBadge(p.stock_status)}</span>
        </div>
        <div class="pcard-body">
          <div class="pcard-name">${esc(p.name)}</div>
          <div class="pcard-meta">${esc(p.catalog_group)} - ${esc(p.code || '')}</div>
          <div class="pcard-row">
            <span class="pcard-price">${formatDT(p.price || 0)}</span>
          </div>
          <div class="pcard-actions">
            <div class="pcard-qty">
              <button onclick="adjQty(this,-1)">-</button>
              <input type="number" value="1" min="1" step="1" class="qty-input">
              <button onclick="adjQty(this,1)">+</button>
            </div>
            <button class="pcard-add" ${isOut ? 'disabled' : ''} onclick="addToCart(${p.id}, this)">
              <i class="bi bi-cart-plus"></i> <span data-i18n="dash.add">Ajouter</span>
            </button>
          </div>
        </div>
      </div>`;
    }).join('');
  }

  function loadShop() {
    if (!$('#shopProductGrid')) return;
    if (shopProducts.length === 0) {
      fetch('/api/shop/products').then((r) => r.json()).then((d) => {
        shopProducts = d.products || [];
        renderShopProducts();
      }).catch(() => {
        $('#shopProductGrid').innerHTML = '<p class="muted" style="grid-column:1/-1;text-align:center;">Erreur de chargement. Veuillez reessayer.</p>';
      });
    } else {
      renderShopProducts();
    }
  }

  function refreshClientProductStocks() {
    shopProducts = [];
    dashLoaded = false;
    loadShop();
  }

  window.filterSegment = function (seg, btn) {
    if (btn) {
      $$('.eco-nav-links button').forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
    }
    currentSegment = seg;
    renderShopProducts();
    if (!$('#viewShop').classList.contains('active')) switchView('shop');
  };

  on($('#shopSearch'), 'input', debounce(renderShopProducts, 200));
  on($('#shopSearchMobile'), 'input', debounce(renderShopProducts, 200));

  window.setQty = function (btn, val) {
    const inp = btn.closest('.pcard')?.querySelector('.qty-input');
    if (inp) inp.value = val;
  };
  window.adjQty = function (btn, delta) {
    const inp = btn.closest('.pcard-qty')?.querySelector('.qty-input');
    if (inp) inp.value = Math.max(1, parseInt(inp.value || '1', 10) + delta);
  };

  window.addToCart = function (productId, btnEl) {
    const p = shopProducts.find((x) => x.id === productId);
    if (!p) return;
    const qty = parseInt($(`.pcard[data-id="${productId}"] .qty-input`)?.value || '1', 10);
    const existing = cart.find((c) => c.product_id === p.product_id);
    if (existing) {
      existing.qty += qty;
    } else {
      cart.push({
        perfume_id: p.id,
        product_id: p.product_id,
        name: p.name,
        price: Number(p.price || 0),
        qty,
        segment: p.segment
      });
    }
    renderCart();

    if (btnEl) {
      btnEl.innerHTML = '<i class="bi bi-check"></i> <span data-i18n="dash.added">Ajoute !</span>';
      setTimeout(() => {
        btnEl.innerHTML = '<i class="bi bi-cart-plus"></i> <span data-i18n="dash.add">Ajouter</span>';
      }, 1000);
    }
  };

  function updateCartCounts() {
    const count = cart.reduce((sum, item) => sum + Number(item.qty || 0), 0);
    if ($('#cartCount')) $('#cartCount').textContent = count;
    if ($('#floatingCartCount')) $('#floatingCartCount').textContent = count;
  }

  function renderCart() {
    const body = $('#cartBody');
    const footer = $('#cartFooter');
    if (!body || !footer) return;

    if (!cart.length) {
      body.innerHTML = '<div class="cart-empty"><i class="bi bi-bag-x"></i><p>Votre panier est vide</p></div>';
      footer.style.display = 'none';
      updateCartCounts();
      return;
    }

    let total = 0;
    body.innerHTML = cart.map((c, i) => {
      const lineTotal = Number(c.qty || 0) * Number(c.price || 0);
      total += lineTotal;
      return `<div class="cart-item">
        <div class="cart-item-info">
          <div class="cart-item-name">${esc(c.name)}</div>
          <div class="cart-item-meta">${bottleLabel(c.qty)} - ${formatDT(c.price)}</div>
        </div>
        <div class="cart-item-actions">
          <div class="cart-item-qty">
            <button class="qty-btn" onclick="changeCartQty(${i}, -1)">-</button>
            <span>${c.qty}</span>
            <button class="qty-btn" onclick="changeCartQty(${i}, 1)">+</button>
          </div>
          <span class="cart-item-price">${formatDT(lineTotal)}</span>
          <button class="cart-item-remove" onclick="removeFromCart(${i})"><i class="bi bi-trash3"></i></button>
        </div>
      </div>`;
    }).join('');
    footer.style.display = 'block';
    $('#cartTotal').textContent = formatDT(total);
    updateCartCounts();
  }

  window.removeFromCart = function (idx) {
    cart.splice(idx, 1);
    renderCart();
  };
  window.changeCartQty = function (idx, delta) {
    const item = cart[idx];
    if (!item) return;
    item.qty = Math.max(1, Number(item.qty || 1) + delta);
    renderCart();
  };
  on($('#cartClear'), 'click', () => {
    cart = [];
    renderCart();
  });

  function shopStep(stepId) {
    $$('.wf-step').forEach((c) => c.classList.remove('active'));
    $$('.wf-tab').forEach((t) => t.classList.remove('active'));
    $(`#step${stepId.charAt(0).toUpperCase() + stepId.slice(1)}`)?.classList.add('active');
    $(`.wf-tab[data-step="${stepId}"]`)?.classList.add('active');
    $('#shopTabs').style.display = stepId !== 'browse' ? 'flex' : 'none';
    if (stepId !== 'browse') document.body.classList.remove('cart-open');
  }

  function renderCheckoutSummary() {
    if (!cart.length) return;
    let total = 0;
    const itemsHtml = cart.map((c) => {
      const lineTotal = Number(c.qty || 0) * Number(c.price || 0);
      total += lineTotal;
      return `<tr><td><strong>${esc(c.name)}</strong><br><small class="text-muted">${bottleLabel(c.qty)} - ${esc(c.segment || '')}</small></td>
        <td>${formatDT(c.price)}</td>
        <td style="text-align:right">${formatDT(lineTotal)}</td></tr>`;
    }).join('');
    $('#ckSummaryTable').innerHTML = `<table><thead><tr><th>Produit</th><th>P.U</th><th style="text-align:right">Total</th></tr></thead><tbody>${itemsHtml}</tbody></table>`;
    $('#ckTotals').innerHTML = `<div class="ck-total-row total"><span>Total net a payer</span><span>${formatDT(total)}</span></div>`;
  }

  on($('#goToCheckout'), 'click', () => {
    if (!cart.length) return alert('Votre panier est vide.');
    renderCheckoutSummary();
    shopStep('checkout');
  });
  on($('#backToBrowse'), 'click', () => shopStep('browse'));

  on($('#goToConfirm'), 'click', () => {
    if (!$('#ckLine1').value || !$('#ckCity').value) {
      alert("Veuillez remplir les champs obligatoires de l'adresse.");
      return;
    }
    const btn = $('#goToConfirm');
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner"></div> Validation...';

    const orderData = {
      items: cart.map((c) => ({
        perfume_id: Number(c.perfume_id || 0),
        product_id: Number(c.product_id || 0),
        qty: Number(c.qty || 0)
      })),
      shipping_address: {
        line1: $('#ckLine1').value,
        phone: $('#ckLine2').value,
        city: $('#ckCity').value,
        region: $('#ckRegion').value,
        country: $('#ckCountry').value,
        delivery_address: $('#ckDeliveryAddress')?.value || ''
      }
    };

    fetch(editingOrderId ? `/api/client/orders/${editingOrderId}` : '/api/client/orders', {
      method: editingOrderId ? 'PATCH' : 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(orderData)
    }).then((r) => r.json()).then((d) => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirmer la commande';
      if (!d.success) return alert(d.error || 'Erreur lors de la validation.');
      cart = [];
      editingOrderId = null;
      renderCart();
      refreshClientProductStocks();
      loadOrders();
      shopStep('confirm');
    }).catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirmer la commande';
      alert('Erreur reseau. Impossible de valider la commande.');
    });
  });

  window.resetShop = function () {
    editingOrderId = null;
    shopStep('browse');
    window.scrollTo(0, 0);
  };

  function loadOrders() {
    $('#ordersPanel').innerHTML = '<p class="muted">Chargement...</p>';
    fetch('/api/client/orders').then((r) => r.json()).then((d) => {
      allOrders = d.orders || [];
      renderOrders();
    }).catch(() => {
      $('#ordersPanel').innerHTML = '<p class="text-danger">Erreur serveur.</p>';
    });
  }

  function renderOrders() {
    const q = ($('#orderSearch')?.value || '').toLowerCase();
    const st = $('#orderStatusFilter')?.value || 'ALL';
    let filtered = allOrders;
    if (q) filtered = filtered.filter((o) => String(o.order_number || '').toLowerCase().includes(q));
    if (st !== 'ALL') filtered = filtered.filter((o) => o.status === st);

    if (!filtered.length) {
      $('#ordersPanel').innerHTML = '<div class="cart-empty"><i class="bi bi-box"></i><p data-i18n="dash.noOrdersFound">Aucune commande trouvee.</p></div>';
      return;
    }

    let html = `<div class="orders-table-wrap"><table class="orders-table"><thead><tr>
      <th data-i18n="dash.orderNum">No Commande</th><th data-i18n="dash.date">Date</th><th data-i18n="dash.amount">Montant</th><th data-i18n="dash.status">Statut</th><th data-i18n="dash.action">Action</th>
      </tr></thead><tbody>`;
    filtered.forEach((o) => {
      html += `<tr>
        <td><strong>${esc(o.order_number)}</strong></td>
        <td>${fmtDate(o.created_at)}</td>
        <td>${formatDT(o.total_dzd)}</td>
        <td>${statusPill(o.status)}</td>
        <td>${o.can_manage
          ? `<button class="btn btn-sm btn-outline" onclick="editOrder(${o.id})"><i class="bi bi-pencil"></i> <span data-i18n="dash.edit">Modifier</span></button>
             <button class="btn btn-sm btn-ghost" onclick="deleteOrder(${o.id})"><i class="bi bi-trash3"></i> <span data-i18n="dash.delete">Supprimer</span></button>`
          : '<span class="text-muted" data-i18n="dash.validatedTimeout">Validee / depassee 24h</span>'}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    $('#ordersPanel').innerHTML = html;
  }
  on($('#orderSearch'), 'input', renderOrders);
  on($('#orderStatusFilter'), 'change', renderOrders);
  initStatusChipGroup('orderStatusFilter', renderOrders);

  window.editOrder = function (orderId) {
    fetch(`/api/client/orders/${orderId}`).then((r) => r.json()).then((d) => {
      if (!d.success || !d.can_manage) {
        alert(d.error || 'Cette commande ne peut plus etre modifiee.');
        return;
      }
      editingOrderId = orderId;
      cart = (d.items || []).map((item) => ({
        perfume_id: Number(item.perfume_id || 0),
        product_id: Number(item.product_id || 0),
        name: item.product_name,
        price: Number(item.unit_price_dzd || 0),
        qty: Number(item.quantity_ml || 1),
        segment: item.segment || ''
      }));
      const shipping = d.shipping_address || {};
      $('#ckLine1').value = shipping.line1 || '';
      $('#ckLine2').value = shipping.phone || shipping.line2 || '';
      $('#ckCity').value = shipping.city || '';
      $('#ckRegion').value = shipping.region || '';
      syncMobileRegionPicker();
      $('#ckCountry').value = shipping.country || 'Tunisie';
      if ($('#ckDeliveryAddress')) $('#ckDeliveryAddress').value = shipping.delivery_address || '';
      renderCart();
      renderCheckoutSummary();
      switchView('shop');
      shopStep('checkout');
    }).catch(() => alert('Impossible de charger la commande.'));
  };

  window.deleteOrder = function (orderId) {
    if (!confirm('Supprimer cette commande ?')) return;
    fetch(`/api/client/orders/${orderId}`, { method: 'DELETE' }).then((r) => r.json()).then((d) => {
      if (!d.success) {
        alert(d.error || 'Suppression impossible.');
        return;
      }
      refreshClientProductStocks();
      loadOrders();
    }).catch(() => alert('Erreur reseau lors de la suppression.'));
  };

  function loadInvoices() {
    $('#invoicesPanel').innerHTML = '<p class="muted">Chargement...</p>';
    fetch('/api/client/invoices').then((r) => r.json()).then((d) => {
      allInvoices = d.invoices || [];
      renderInvoices();
    }).catch(() => {
      $('#invoicesPanel').innerHTML = '<p class="text-danger">Erreur serveur.</p>';
    });
  }

  function renderInvoices() {
    const q = ($('#invoiceSearch')?.value || '').toLowerCase();
    const st = $('#invoiceStatusFilter')?.value || 'ALL';
    let filtered = allInvoices;
    if (q) {
      filtered = filtered.filter((x) => [
        x.invoice_number,
        x.customer_name,
        x.customer_display_name,
        x.order_number
      ].some((value) => String(value || '').toLowerCase().includes(q)));
    }
    if (st !== 'ALL') filtered = filtered.filter((x) => x.payment_status === st || x.status === st);

    if (!filtered.length) {
      $('#invoicesPanel').innerHTML = '<div class="cart-empty"><i class="bi bi-receipt"></i><p data-i18n="dash.noInvoicesFound">Aucune facture trouvee.</p></div>';
      return;
    }
    let html = `<div class="orders-table-wrap"><table class="orders-table"><thead><tr>
      <th data-i18n="dash.invoice">Facture</th><th data-i18n="dash.issueDate">Date d'emission</th><th data-i18n="dash.name">Nom</th><th data-i18n="dash.paymentStatus">Statut Paiement</th>
      </tr></thead><tbody>`;
    filtered.forEach((x) => {
      const payStatus = x.payment_status || x.status;
      const customerName = x.customer_display_name || x.customer_name || '-';
      const issuedDate = fmtDate(x.issued_at);
      const issuedTime = fmtTime(x.issued_at);
      html += `<tr>
        <td><strong>${esc(x.invoice_number)}</strong></td>
        <td>${issuedDate}${issuedTime ? ` ${issuedTime}` : ''}</td>
        <td>${esc(customerName)}</td>
        <td>${statusPill(payStatus)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    $('#invoicesPanel').innerHTML = html;
  }
  on($('#invoiceSearch'), 'input', renderInvoices);
  on($('#invoiceStatusFilter'), 'change', renderInvoices);
  initStatusChipGroup('invoiceStatusFilter', renderInvoices);

  on($('#profileForm'), 'submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    btn.disabled = true;
    btn.textContent = 'Enregistrement...';
    setFeedback(profileMessage, '');
    try {
      await fetchJson(API.profile, {
        method: 'PATCH',
        body: JSON.stringify({
          first_name: $('#profFirstName')?.value?.trim() || '',
          last_name: $('#profLastName')?.value?.trim() || '',
          shop_name: $('#profShop')?.value?.trim() || '',
          phone: $('#profPhone')?.value?.trim() || '',
          email: $('#profEmail')?.value?.trim() || '',
          location: $('#profLocation')?.value?.trim() || ''
        })
      });
      setFeedback(profileMessage, 'Profil mis a jour avec succes.');
    } catch (error) {
      setFeedback(profileMessage, error.message || 'Mise a jour impossible.', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Enregistrer les modifications';
    }
  });

  on($('#passwordForm'), 'submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const currentPassword = $('#profCurrentPwd')?.value || '';
    const newPassword = $('#profNewPwd')?.value || '';
    const confirmPassword = $('#profConfirmPwd')?.value || '';
    if (newPassword !== confirmPassword) {
      setFeedback(passwordMessage, 'La confirmation du mot de passe ne correspond pas.', 'error');
      return;
    }
    btn.disabled = true;
    btn.textContent = 'Mise a jour...';
    setFeedback(passwordMessage, '');
    try {
      const result = await fetchJson(API.password, {
        method: 'PATCH',
        body: JSON.stringify({
          current_password: currentPassword,
          new_password: newPassword
        })
      });
      setFeedback(passwordMessage, result.message || 'Mot de passe modifie avec succes.');
      e.target.reset();
    } catch (error) {
      setFeedback(passwordMessage, error.message || 'Mise a jour impossible.', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Changer le mot de passe';
    }
  });

  on(accountFaceOpenBtn, 'click', async () => {
    setFeedback(faceAccountMessage, '');
    try {
      stopAccountFaceCamera();
      accountFaceStream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'user',
          width: { ideal: 640 },
          height: { ideal: 480 }
        },
        audio: false
      });
      if (accountFaceVideo) {
        accountFaceVideo.srcObject = accountFaceStream;
        await accountFaceVideo.play();
      }
      setFeedback(faceAccountMessage, 'Camera ouverte. Verifiez votre mot de passe puis validez votre visage.');
    } catch (error) {
      setFeedback(faceAccountMessage, explainCameraError(error), 'error');
    }
  });

  on(accountFaceSaveBtn, 'click', async () => {
    const password = accountFacePassword?.value || '';
    if (!password) {
      setFeedback(faceAccountMessage, 'Veuillez saisir votre mot de passe actuel.', 'error');
      return;
    }
    const originalText = accountFaceSaveBtn.textContent;
    accountFaceSaveBtn.disabled = true;
    accountFaceSaveBtn.textContent = 'Validation...';
    setFeedback(faceAccountMessage, '');
    try {
      const matrix = await buildFaceMatrixFromElements(accountFaceVideo, accountFaceCanvas);
      const result = await fetchJson(API.faceCreate, {
        method: 'POST',
        body: JSON.stringify({ password, matrix })
      });
      setFeedback(faceAccountMessage, result.message || 'Visage ajoute avec succes.');
      if (accountFacePassword) accountFacePassword.value = '';
      stopAccountFaceCamera();
      accountFaceStatusLoaded = false;
      await loadFaceAccountStatus(true);
    } catch (error) {
      setFeedback(faceAccountMessage, error.message || 'Enregistrement du visage impossible.', 'error');
    } finally {
      accountFaceSaveBtn.disabled = false;
      accountFaceSaveBtn.textContent = originalText;
    }
  });

  (function initOnboarding() {
    const key = 'idene_onboarding_done';
    const modal = $('#obModal');
    const backdrop = $('#obBackdrop');
    const slides = $$('.ob-slide');
    const dotsWrap = $('#obDots');
    const counter = $('#obCounter');
    const btnNext = $('#obNext');
    const btnPrev = $('#obPrev');
    const btnSkip = $('#obSkip');
    const arabicGuideBtn = $('#arabicGuideBtn');
    const shouldForceOpen = document.body?.dataset?.forceOnboarding === '1';
    const onboardingLang = document.body?.dataset?.onboardingLang || 'fr';
    const arabicNote = $('#obArabicNote');
    if (!modal || !slides.length) return;

    slides.forEach((_, i) => {
      const d = document.createElement('button');
      d.className = 'ob-dot';
      d.setAttribute('aria-label', `Etape ${i + 1}`);
      d.addEventListener('click', () => goTo(i));
      dotsWrap.appendChild(d);
    });

    let current = 0;
    function goTo(idx) {
      slides.forEach((s, i) => s.classList.toggle('active', i === idx));
      $$('.ob-dot', dotsWrap).forEach((d, i) => d.classList.toggle('active', i === idx));
      current = idx;
      counter.textContent = `${current + 1} / ${slides.length}`;
      btnPrev.disabled = current === 0;
      btnNext.innerHTML = current === slides.length - 1
        ? '<i class="bi bi-rocket-takeoff"></i> Demarrer !'
        : '<i class="bi bi-chevron-right"></i> Suivant';
    }
    function close() {
      modal.classList.remove('ob-enter');
      backdrop.classList.remove('ob-enter');
      modal.style.display = 'none';
      backdrop.style.display = 'none';
      localStorage.setItem(key, '1');
      if (shouldForceOpen) {
        const url = new URL(window.location.href);
        url.searchParams.delete('onboarding');
        url.searchParams.delete('lang');
        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
      }
    }
    function open(force = false) {
      if (force) {
        localStorage.removeItem(key);
      }
      modal.classList.remove('ob-enter');
      backdrop.classList.remove('ob-enter');
      modal.style.display = 'flex';
      backdrop.style.display = 'block';
      goTo(0);
      requestAnimationFrame(() => {
        modal.classList.add('ob-enter');
        backdrop.classList.add('ob-enter');
      });
    }
    on(btnNext, 'click', () => current < slides.length - 1 ? goTo(current + 1) : close());
    on(btnPrev, 'click', () => current > 0 && goTo(current - 1));
    on(btnSkip, 'click', close);
    on(backdrop, 'click', close);
    on(arabicGuideBtn, 'click', () => open(true));

    if (arabicGuideBtn && onboardingLang === 'ar') {
      arabicGuideBtn.classList.add('is-highlighted');
    }

    if (arabicNote) {
      arabicNote.hidden = onboardingLang !== 'ar';
    }

    if (shouldForceOpen || !localStorage.getItem(key)) {
      open(shouldForceOpen);
    }
  })();

  const hash = window.location.hash.substring(1);
  if (views[hash]) {
    switchView(hash);
  } else {
    switchView('shop');
  }
})();
