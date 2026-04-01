/* ═══════════════════════════════════════════════════════════════
   IDENE PARFUM — Client E-Commerce JS v2.1
   Shop + Panier + Commandes + Factures + Profil
   ═══════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const on = (el, ev, fn) => el?.addEventListener(ev, fn);

  const DZD = (n) => new Intl.NumberFormat('fr-DZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + ' DZD';
  const fmtDate = (d) => { const dt = new Date(d); return isNaN(dt) ? '—' : dt.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }); };
  const debounce = (fn, ms = 300) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

  // ── Status helpers ──────────────────────────────────
  const statusColors = {
    BROUILLON: 'status-brouillon', CONFIRMEE: 'status-confirmee', EN_PREPARATION: 'status-en_preparation',
    EXPEDIEE: 'status-expediee', LIVREE: 'status-livree', ANNULEE: 'status-annulee',
    NON_PAYE: 'status-non_paye', PARTIEL: 'status-partiel', PAYE: 'status-paye', ANNULE: 'status-annule'
  };
  const statusLabel = (s) => (s || '').replace(/_/g, ' ');
  const statusPill = (s) => `<span class="status-pill ${statusColors[s] || ''}">${statusLabel(s)}</span>`;

  const segEmoji = { HOMME: '🧴', FEMME: '🌸', ENFANT: '🧸', MIXTE: '💧' };
  const segBadge = (s) => {
    const cls = { HOMME: 'badge-info', FEMME: 'badge-accent', MIXTE: 'badge-primary', ENFANT: 'badge-gold' };
    return `<span class="badge ${cls[s] || 'badge-neutral'} badge-dot">${s}</span>`;
  };
  const stockBadge = (st) => {
    if (st === 'out_of_stock') return '<span class="badge badge-danger">Rupture</span>';
    if (st === 'limited') return '<span class="badge badge-warning">Limité</span>';
    return '<span class="badge badge-success">En stock</span>';
  };
  function esc(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

  // ── Layout logic ──────────────────────────────────────
  const views = { dashboard: 'viewDashboard', shop: 'viewShop', orders: 'viewOrders', invoices: 'viewInvoices', profile: 'viewProfile' };

  window.switchView = function (name) {
    $$('.view').forEach(v => v.classList.remove('active'));
    const el = $(`#${views[name]}`);
    if (el) el.classList.add('active');
    
    // Update active state in bottom nav
    $$('.mobile-bottom-nav button').forEach(b => {
      b.classList.toggle('active', b.dataset.view === name);
    });

    if (name === 'dashboard') loadDashboard();
    if (name === 'shop') loadShop();
    if (name === 'orders') loadOrders();
    if (name === 'invoices') loadInvoices();
    window.location.hash = name;
  };

  // Nav actions
  const profileBtn = $('#profileDropdownBtn');
  const profileMenu = $('#profileDropdown');
  const brandLink = $('.eco-brand');
  on(brandLink, 'click', (e) => {
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

  const mobNavToggle = $('#mobileNavToggle');
  const mobMenu = $('#ecoMobileMenu');
  on(mobNavToggle, 'click', () => {
      mobMenu?.classList.toggle('open');
  });

  // Slide-out cart
  const cartBtn = $('#floatingCartBtn');
  on(cartBtn, 'click', () => {
      document.body.classList.add('cart-open');
  });

  // Logout
  window.logoutUser = async function() {
      try {
          await fetch('/api/auth/logout', { method: 'POST' });
          window.location.href = '/accueil';
      } catch (e) {
          console.error('Logout error', e);
          window.location.href = '/accueil';
      }
  };

  // ══════════════════════════════════════════════════════
  //  DASHBOARD
  // ══════════════════════════════════════════════════════
  let dashLoaded = false;

  function loadDashboard() {
    if (dashLoaded) return;

    Promise.all([
      fetch('/api/client/dashboard').then(r => r.json()),
      fetch('/api/shop/products').then(r => r.json())
    ]).then(([dash, prod]) => {
      dashLoaded = true;
      const stats = dash.stats || {};
      const products = prod.products || [];

      if (shopProducts.length === 0) shopProducts = products;

      $('#kpiOrders').textContent = stats.total_orders ?? 0;
      $('#kpiTotal').textContent = DZD(stats.total_amount ?? 0);
      $('#kpiUnpaid').textContent = DZD(stats.unpaid_amount ?? 0);
      $('#kpiMonth').textContent = DZD(stats.month_amount ?? 0);

      const totalProducts = products.length;
      const inStock = products.filter(p => p.stock_status === 'in_stock').length;
      const limited = products.filter(p => p.stock_status === 'limited').length;
      const outStock = products.filter(p => p.stock_status === 'out_of_stock').length;
      const groups = {};
      products.forEach(p => { groups[p.catalog_group] = (groups[p.catalog_group] || 0) + 1; });

      const pctIn = totalProducts ? Math.round(inStock / totalProducts * 100) : 0;
      const pctLimited = totalProducts ? Math.round(limited / totalProducts * 100) : 0;
      const pctOut = totalProducts ? Math.round(outStock / totalProducts * 100) : 0;

      $('#dashProductStats').innerHTML = `
        <div class="dash-stat-card">
          <div class="dash-stat-number">${totalProducts}</div>
          <div class="dash-stat-label">Produits au catalogue</div>
          <div class="dash-stat-detail">${Object.keys(groups).length} catégories</div>
        </div>
        <div class="dash-stat-card dash-stat-success">
          <div class="dash-stat-number">${inStock}</div>
          <div class="dash-stat-label">En stock</div>
          <div class="dash-stat-bar"><div class="dash-stat-fill dash-fill-success" style="width:${pctIn}%"></div></div>
          <div class="dash-stat-detail">${pctIn}% du catalogue</div>
        </div>
        <div class="dash-stat-card dash-stat-warning">
          <div class="dash-stat-number">${limited}</div>
          <div class="dash-stat-label">Stock limité</div>
          <div class="dash-stat-bar"><div class="dash-stat-fill dash-fill-warning" style="width:${pctLimited}%"></div></div>
          <div class="dash-stat-detail">${pctLimited}% du catalogue</div>
        </div>
        <div class="dash-stat-card dash-stat-danger">
          <div class="dash-stat-number">${outStock}</div>
          <div class="dash-stat-label">En rupture</div>
          <div class="dash-stat-bar"><div class="dash-stat-fill dash-fill-danger" style="width:${pctOut}%"></div></div>
          <div class="dash-stat-detail">${pctOut}% du catalogue</div>
        </div>`;

      // Segment breakdown
      const segments = {};
      products.forEach(p => { segments[p.segment] = (segments[p.segment] || 0) + 1; });
      const segIcons = { HOMME: 'bi-gender-male', FEMME: 'bi-gender-female', MIXTE: 'bi-gender-ambiguous', ENFANT: 'bi-balloon' };
      const segColors = { HOMME: '#1565C0', FEMME: '#AD1457', MIXTE: '#6A1B9A', ENFANT: '#E65100' };
      let segHtml = '';
      Object.entries(segments).sort((a, b) => b[1] - a[1]).forEach(([seg, cnt]) => {
        const pct = Math.round(cnt / totalProducts * 100);
        segHtml += `<div class="dash-breakdown-item">
          <div class="dash-breakdown-left">
            <i class="bi ${segIcons[seg] || 'bi-droplet'}" style="color:${segColors[seg] || '#333'}"></i>
            <span>${seg}</span>
          </div>
          <div class="dash-breakdown-right">
            <div class="dash-breakdown-bar-wrap"><div class="dash-breakdown-bar" style="width:${pct}%;background:${segColors[seg] || '#333'}"></div></div>
            <strong>${cnt}</strong>
          </div>
        </div>`;
      });

      // Category breakdown appended
      Object.entries(groups).sort((a, b) => b[1] - a[1]).forEach(([grp, cnt]) => {
        const pct = Math.round(cnt / totalProducts * 100);
        segHtml += `<div class="dash-breakdown-item">
          <div class="dash-breakdown-left">
            <i class="bi bi-tag" style="color:#546E7A"></i>
            <span>${grp}</span>
          </div>
          <div class="dash-breakdown-right">
            <div class="dash-breakdown-bar-wrap"><div class="dash-breakdown-bar" style="width:${pct}%;background:#546E7A"></div></div>
            <strong>${cnt}</strong>
          </div>
        </div>`;
      });
      $('#dashSegmentBreakdown').innerHTML = segHtml || '<p class="muted">Aucun produit.</p>';

      // Recent orders
      const recent = dash.recent_orders || [];
      if (!recent.length) {
        $('#dashRecentOrders').innerHTML = '<p class="muted" style="padding:16px">Aucune commande.</p>';
      } else {
        let html = '<table class="dash-mini-table"><thead><tr><th>N°</th><th>Montant</th><th>Statut</th></tr></thead><tbody>';
        recent.forEach(o => {
          html += `<tr><td>${esc(o.order_number)}</td><td>${DZD(o.total_dzd)}</td><td>${statusPill(o.status)}</td></tr>`;
        });
        html += '</tbody></table>';
        $('#dashRecentOrders').innerHTML = html;
      }
    }).catch(() => {
      $('#dashProductStats').innerHTML = '<p class="text-danger">Erreur de chargement.</p>';
    });
  }

  // ══════════════════════════════════════════════════════
  //  SHOP + CART
  // ══════════════════════════════════════════════════════
  let shopProducts = [], cart = [], currentSegment = 'ALL';

  function loadShop() {
    if (!$('#shopProductGrid')) return;
    if (shopProducts.length === 0) {
        fetch('/api/shop/products').then(r => r.json()).then(d => {
          shopProducts = d.products || [];
          renderShopProducts();
        }).catch(() => {
          $('#shopProductGrid').innerHTML = '<p class="muted" style="grid-column:1/-1;text-align:center;">Erreur de chargement. Veuillez réessayer.</p>';
        });
    } else {
        renderShopProducts();
    }
  }

  // Segment Filter helper
  window.filterSegment = function(seg, btn) {
      if (btn) {
          $$('.eco-nav-links button').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
      }
      currentSegment = seg;
      renderShopProducts();
      // Ensure we are in shop view
      if (!$('#viewShop').classList.contains('active')) {
          switchView('shop');
      }
  };

  function renderShopProducts() {
    const search = ($('#shopSearch')?.value || $('#shopSearchMobile')?.value || '').toLowerCase();
    
    let filtered = shopProducts;
    if (search) filtered = filtered.filter(p => p.name.toLowerCase().includes(search) || (p.code || '').toLowerCase().includes(search));
    if (currentSegment !== 'ALL') filtered = filtered.filter(p => p.segment === currentSegment);

    $('#shopResultsCount').textContent = `${filtered.length} parfum${filtered.length !== 1 ? 's' : ''}`;

    if (!filtered.length) {
      $('#shopProductGrid').innerHTML = '<p class="muted" style="grid-column:1/-1;text-align:center;padding:40px;">Aucun parfum ne correspond à vos critères.</p>';
      return;
    }

    $('#shopProductGrid').innerHTML = filtered.map(p => {
      const isOut = p.stock_status === 'out_of_stock';
      return `<div class="pcard ${isOut ? 'is-out' : ''}" data-id="${p.id}">
        <div class="pcard-visual">
          <span class="emoji">${segEmoji[p.segment] || '💧'}</span>
          <span class="seg-tag">${segBadge(p.segment)}</span>
          <span class="stk-tag">${stockBadge(p.stock_status)}</span>
        </div>
        <div class="pcard-body">
          <div class="pcard-name">${esc(p.name)}</div>
          <div class="pcard-meta">${p.catalog_group} · ${p.code || ''}</div>
          <div class="pcard-row">
            <span class="pcard-price">${DZD(p.price || 0)}</span>
          </div>
          <div class="pcard-quick-btns">
            <button onclick="setQty(this,250)">250ml</button>
            <button onclick="setQty(this,500)">500ml</button>
            <button onclick="setQty(this,1000)">1L</button>
          </div>
          <div class="pcard-actions">
            <div class="pcard-qty">
              <button onclick="adjQty(this,-50)">−</button>
              <input type="number" value="250" min="50" step="50" class="qty-input">
              <button onclick="adjQty(this,50)">+</button>
            </div>
            <button class="pcard-add" ${isOut ? 'disabled' : ''} onclick="addToCart(${p.id}, this)">
              <i class="bi bi-cart-plus"></i> Ajouter
            </button>
          </div>
        </div>
      </div>`;
    }).join('');
  }

  on($('#shopSearch'), 'input', debounce(renderShopProducts, 200));
  on($('#shopSearchMobile'), 'input', debounce(renderShopProducts, 200));

  window.setQty = function (btn, val) {
    const card = btn.closest('.pcard');
    const inp = card?.querySelector('.qty-input');
    if (inp) inp.value = val;
  };
  window.adjQty = function (btn, delta) {
    const card = btn.closest('.pcard-qty');
    const inp = card?.querySelector('.qty-input');
    if (inp) { let v = parseInt(inp.value, 10) + delta; inp.value = Math.max(50, v); }
  };

  window.addToCart = function (productId, btnEl) {
    const p = shopProducts.find(x => x.id === productId);
    if (!p) return;
    const card = $(`.pcard[data-id="${productId}"]`);
    const qty = parseFloat(card?.querySelector('.qty-input')?.value || 250);
    const existing = cart.find(c => c.product_id === p.product_id);
    if (existing) {
      existing.qty += qty;
    } else {
      cart.push({
        perfume_id: p.id,          // perfume_catalog.id
        product_id: p.product_id,  // products.id
        name: p.name,
        price: p.price || 0,
        qty,
        segment: p.segment
      });
    }
    renderCart();
    
    if (btnEl) {
      btnEl.innerHTML = '<i class="bi bi-check"></i> Ajouté !'; 
      setTimeout(() => { btnEl.innerHTML = '<i class="bi bi-cart-plus"></i> Ajouter'; }, 1000);
      
      // Auto open cart on mobile? Or just pulse icon.
      const icon = $('#floatingCartBtn');
      if (icon) {
          icon.style.transform = 'scale(1.2)';
          setTimeout(() => icon.style.transform = 'scale(1)', 200);
      }
    }
  };

  function renderCart() {
    const body = $('#cartBody'), footer = $('#cartFooter');
    if (!cart.length) {
      body.innerHTML = '<div class="cart-empty"><i class="bi bi-bag-x"></i><p>Votre panier est vide</p></div>';
      footer.style.display = 'none';
      updateCartCounts();
      return;
    }
    let total = 0;
    body.innerHTML = cart.map((c, i) => {
      const lineTotal = c.qty * c.price;
      total += lineTotal;
      return `<div class="cart-item">
        <div class="cart-item-info">
          <div class="cart-item-name">${esc(c.name)}</div>
          <div class="cart-item-meta">${c.qty} ml × ${DZD(c.price)}</div>
        </div>
        <div class="cart-item-actions">
          <span class="cart-item-price">${DZD(lineTotal)}</span>
          <button class="cart-item-remove" onclick="removeFromCart(${i})"><i class="bi bi-trash3"></i></button>
        </div>
      </div>`;
    }).join('');
    footer.style.display = 'block';
    $('#cartTotal').textContent = DZD(total);
    updateCartCounts();
  }

  function updateCartCounts() {
    const count = cart.length;
    const el = $('#cartCount'); if (el) el.textContent = count;
    const fl = $('#floatingCartCount'); if (fl) fl.textContent = count;
  }

  window.removeFromCart = function (idx) {
    cart.splice(idx, 1);
    renderCart();
  };

  on($('#cartClear'), 'click', () => { cart = []; renderCart(); });

  // ══════════════════════════════════════════════════════
  //  CHECKOUT WORKFLOW
  // ══════════════════════════════════════════════════════
  function shopStep(stepId) {
    $$('.wf-step').forEach(c => c.classList.remove('active'));
    $$('.wf-tab').forEach(t => t.classList.remove('active'));
    $(`#step${stepId.charAt(0).toUpperCase() + stepId.slice(1)}`)?.classList.add('active');
    $(`.wf-tab[data-step="${stepId}"]`)?.classList.add('active');
    if (stepId !== 'browse') {
      $('#shopTabs').style.display = 'flex';
      document.body.classList.remove('cart-open');
    } else {
      $('#shopTabs').style.display = 'none';
    }
  }

  on($('#goToCheckout'), 'click', () => {
    if (!cart.length) return alert("Votre panier est vide.");
    let total = 0;
    const itemsHtml = cart.map(c => {
      const lineTotal = c.qty * c.price;
      total += lineTotal;
      return `<tr><td><strong>${esc(c.name)}</strong><br><small class="text-muted">${c.qty}ml - ${c.segment}</small></td>
              <td>${DZD(c.price)}</td><td style="text-align:right">${DZD(lineTotal)}</td></tr>`;
    }).join('');
    
    $('#ckSummaryTable').innerHTML = `<table><thead><tr><th>Produit</th><th>P.U</th><th style="text-align:right">Total</th></tr></thead><tbody>${itemsHtml}</tbody></table>`;
    $('#ckTotals').innerHTML = `<div class="ck-total-row"><span>Sous-total HT</span><span>${DZD(total)}</span></div>
                                <div class="ck-total-row total"><span>Total à payer</span><span>${DZD(total)}</span></div>`;
    shopStep('checkout');
  });

  on($('#backToBrowse'), 'click', () => shopStep('browse'));

  on($('#goToConfirm'), 'click', () => {
    if (!$('#ckLine1').value || !$('#ckCity').value) {
      alert("Veuillez remplir les champs obligatoires (*) de l'adresse.");
      return;
    }
    const btn = $('#goToConfirm');
    btn.disabled = true; btn.innerHTML = '<div class="spinner"></div> Validation...';

    const orderData = {
      items: cart.map(c => ({
        perfume_id: Number(c.perfume_id ?? c.id ?? 0),
        qty: Number(c.qty ?? 0)
      })),
      shipping_address: {
        line1: $('#ckLine1').value, line2: $('#ckLine2').value,
        city: $('#ckCity').value, region: $('#ckRegion').value,
        postal_code: $('#ckPostal').value, country: $('#ckCountry').value
      }
    };

    fetch('/api/client/orders', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(orderData)
    }).then(r => r.json()).then(d => {
      btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirmer la commande';
      if (!d.success) return alert(d.error || 'Erreur lors de la validation.');
      cart = []; renderCart();
      shopStep('confirm');
    }).catch(e => {
      console.error(e);
      alert('Erreur réseau. Impossible de valider la commande.');
      btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirmer la commande';
    });
  });

  window.resetShop = function () { shopStep('browse'); window.scrollTo(0,0); };

  // ══════════════════════════════════════════════════════
  //  ORDERS
  // ══════════════════════════════════════════════════════
  let allOrders = [];
  function loadOrders() {
    $('#ordersPanel').innerHTML = '<p class="muted">Chargement...</p>';
    fetch('/api/client/orders').then(r => r.json()).then(d => {
      allOrders = d.orders || [];
      renderOrders();
    }).catch(() => { $('#ordersPanel').innerHTML = '<p class="text-danger">Erreur serveur.</p>'; });
  }

  function renderOrders() {
    const q = ($('#orderSearch')?.value || '').toLowerCase();
    const st = $('#orderStatusFilter')?.value || 'ALL';
    let f = allOrders;
    if (q) f = f.filter(o => o.order_number.toLowerCase().includes(q));
    if (st !== 'ALL') f = f.filter(o => o.status === st);

    if (!f.length) {
      $('#ordersPanel').innerHTML = '<div class="cart-empty"><i class="bi bi-box"></i><p>Aucune commande trouvée.</p></div>';
      return;
    }
    let html = `<div class="orders-table-wrap"><table class="orders-table"><thead><tr>
      <th>N° Commande</th><th>Date</th><th>Montant</th><th>Statut</th><th>Action</th>
      </tr></thead><tbody>`;
    f.forEach(o => {
      html += `<tr>
        <td><strong>${esc(o.order_number)}</strong></td>
        <td>${fmtDate(o.created_at)}</td>
        <td>${DZD(o.total_dzd)}</td>
        <td>${statusPill(o.status)}</td>
        <td><button class="btn btn-sm btn-outline"><i class="bi bi-eye"></i> Voir</button></td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    $('#ordersPanel').innerHTML = html;
  }
  on($('#orderSearch'), 'input', renderOrders);
  on($('#orderStatusFilter'), 'change', renderOrders);

  // ══════════════════════════════════════════════════════
  //  INVOICES
  // ══════════════════════════════════════════════════════
  let allInvoices = [];
  function loadInvoices() {
    $('#invoicesPanel').innerHTML = '<p class="muted">Chargement...</p>';
    fetch('/api/client/invoices').then(r => r.json()).then(d => {
      allInvoices = d.invoices || [];
      renderInvoices();
    }).catch(() => { $('#invoicesPanel').innerHTML = '<p class="text-danger">Erreur serveur.</p>'; });
  }

  function renderInvoices() {
    const q = ($('#invoiceSearch')?.value || '').toLowerCase();
    const st = $('#invoiceStatusFilter')?.value || 'ALL';
    let f = allInvoices;
    if (q) f = f.filter(x => x.invoice_number.toLowerCase().includes(q) || x.order_number.toLowerCase().includes(q));
    if (st !== 'ALL') f = f.filter(x => x.payment_status === st);

    if (!f.length) {
      $('#invoicesPanel').innerHTML = '<div class="cart-empty"><i class="bi bi-receipt"></i><p>Aucune facture trouvée.</p></div>';
      return;
    }
    let html = `<div class="orders-table-wrap"><table class="orders-table"><thead><tr>
      <th>N° Facture</th><th>Date d'émission</th><th>Commande liée</th><th>À Payer</th><th>Statut Paiement</th><th>PDF</th>
      </tr></thead><tbody>`;
    f.forEach(x => {
      html += `<tr>
        <td><strong>${esc(x.invoice_number)}</strong></td>
        <td>${fmtDate(x.issued_at)}</td>
        <td>${esc(x.order_number || '—')}</td>
        <td><strong>${DZD(x.total_dzd)}</strong></td>
        <td>${statusPill(x.payment_status)}</td>
        <td><button class="btn btn-sm btn-ghost"><i class="bi bi-download"></i></button></td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    $('#invoicesPanel').innerHTML = html;
  }
  on($('#invoiceSearch'), 'input', renderInvoices);
  on($('#invoiceStatusFilter'), 'change', renderInvoices);

  // ══════════════════════════════════════════════════════
  //  PROFILE
  // ══════════════════════════════════════════════════════
  on($('#profileForm'), 'submit', (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button'); btn.disabled = true; btn.textContent = 'Enregistrement...';
    // Simulation since no endpoint implemented yet
    setTimeout(() => {
        $('#profileMessage').innerHTML = '<div class="alert alert-success mt-2">Profil mis à jour avec succès.</div>';
        btn.disabled = false; btn.textContent = 'Enregistrer les modifications';
    }, 1000);
  });

  on($('#passwordForm'), 'submit', (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button'); btn.disabled = true; btn.textContent = 'Mise à jour...';
    // Simulation
    setTimeout(() => {
        $('#passwordMessage').innerHTML = '<div class="alert alert-success mt-2">Mot de passe modifié avec succès.</div>';
        btn.disabled = false; btn.textContent = 'Changer le mot de passe';
        e.target.reset();
    }, 1000);
  });

  // ══════════════════════════════════════════════════════
  //  ONBOARDING GUIDE
  // ══════════════════════════════════════════════════════
  (function initOnboarding() {
    const STORAGE_KEY = 'idene_onboarding_done';
    const modal   = document.getElementById('obModal');
    const backdrop= document.getElementById('obBackdrop');
    const slides  = document.querySelectorAll('.ob-slide');
    const dotsWrap= document.getElementById('obDots');
    const counter = document.getElementById('obCounter');
    const btnNext = document.getElementById('obNext');
    const btnPrev = document.getElementById('obPrev');
    const btnSkip = document.getElementById('obSkip');
    const TOTAL   = slides.length;

    if (!modal || !slides.length) return;

    // Build dots
    slides.forEach((_, i) => {
      const d = document.createElement('button');
      d.className = 'ob-dot';
      d.setAttribute('aria-label', `Étape ${i + 1}`);
      d.addEventListener('click', () => goTo(i));
      dotsWrap.appendChild(d);
    });

    let current = 0;

    function goTo(idx) {
      slides[current].classList.remove('active', 'prev');
      slides[current].classList.add(idx > current ? 'prev' : 'next-out');
      current = idx;
      slides[current].classList.remove('prev', 'next-out');
      slides[current].classList.add('active');

      dotsWrap.querySelectorAll('.ob-dot').forEach((d, i) => d.classList.toggle('active', i === current));
      counter.textContent = `${current + 1} / ${TOTAL}`;
      btnPrev.disabled = current === 0;

      const isLast = current === TOTAL - 1;
      btnNext.innerHTML = isLast
        ? '<i class="bi bi-rocket-takeoff"></i> Démarrer !'
        : '<i class="bi bi-chevron-right"></i> Suivant';
      btnNext.classList.toggle('ob-btn-final', isLast);
    }

    function close() {
      modal.classList.add('ob-exit');
      backdrop.classList.add('ob-exit');
      localStorage.setItem(STORAGE_KEY, '1');
      setTimeout(() => {
        modal.style.display = 'none';
        backdrop.style.display = 'none';
      }, 350);
    }

    btnNext.addEventListener('click', () => {
      if (current < TOTAL - 1) goTo(current + 1);
      else close();
    });
    btnPrev.addEventListener('click', () => {
      if (current > 0) goTo(current - 1);
    });
    btnSkip.addEventListener('click', close);
    backdrop.addEventListener('click', close);

    // Show only if not already seen
    if (!localStorage.getItem(STORAGE_KEY)) {
      modal.style.display = 'flex';
      backdrop.style.display = 'block';
      goTo(0);
      setTimeout(() => {
        modal.classList.add('ob-enter');
        backdrop.classList.add('ob-enter');
      }, 50);
    }
  })();

  // ══════════════════════════════════════════════════════
  //  INIT
  // ══════════════════════════════════════════════════════
  const hash = window.location.hash.substring(1);
  if (views[hash]) {
    switchView(hash);
  } else {
    switchView('shop');
  }

})();
