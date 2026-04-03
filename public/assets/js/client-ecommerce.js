// ===================================
// IDENE CLIENT E-COMMERCE
// Interface shopping moderne
// ===================================

(function() {
    'use strict';

    // === STATE ===
    const state = {
        products: [],
        cart: new Map(),
        currentView: 'shop',
        filters: {
            category: 'ALL',
            search: '',
            sort: 'name'
        },
        pagination: {
            page: 1,
            perPage: 12
        }
    };

    // === DOM ELEMENTS ===
    const elements = {
        sidebar: document.querySelector('.shop-sidebar'),
        cartSidebar: document.querySelector('.cart-sidebar'),
        cartOverlay: document.querySelector('.cart-overlay'),
        cartCount: document.querySelector('.cart-count'),
        cartItems: document.querySelector('.cart-items'),
        cartTotal: document.querySelector('.cart-total-amount'),
        productsGrid: document.querySelector('.products-grid'),
        searchInput: document.querySelector('.shop-search-input'),
        categoryFilter: document.querySelector('#categoryFilter'),
        sortFilter: document.querySelector('#sortFilter'),
        cartBtn: document.querySelector('[data-view="cart"]'),
        cartCloseBtn: document.querySelector('.cart-close'),
        checkoutBtn: document.querySelector('.cart-checkout-btn'),
        mobileMenuBtn: document.querySelector('.mobile-menu-btn')
    };

    // === API ===
    const API = {
        products: '/api/perfumes',
        cart: '/api/cart',
        checkout: '/api/orders'
    };

    // === UTILITIES ===
    const formatPrice = (price) => {
        return `${Number(price).toFixed(2)} DT`;
    };

    const getStockStatus = (stock) => {
        const qty = Number(stock || 0);
        if (qty === 0) return { class: 'out-stock', label: '<span data-i18n="shop.outOfStockBtn">Rupture</span>' };
        if (qty < 10) return { class: 'low-stock', label: `${qty} restants` };
        return { class: 'in-stock', label: 'En stock' };
    };

    const debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // === CART FUNCTIONS ===
    const updateCartCount = () => {
        if (!elements.cartCount) return;
        const totalItems = Array.from(state.cart.values()).reduce((sum, item) => sum + item.qty, 0);
        elements.cartCount.textContent = totalItems;
        elements.cartCount.style.display = totalItems > 0 ? 'block' : 'none';
    };

    const addToCart = (product) => {
        const key = String(product.id);
        const existing = state.cart.get(key);
        
        if (existing) {
            existing.qty += 1;
        } else {
            state.cart.set(key, {
                id: product.id,
                name: product.name,
                category: product.catalog_group,
                price: Number(product.price_dzd || 0),
                qty: 1,
                stock: Number(product.stock_bottles || 0)
            });
        }
        
        updateCartCount();
        renderCart();
        showNotification('Produit ajouté au panier', 'success');
    };

    const removeFromCart = (productId) => {
        state.cart.delete(String(productId));
        updateCartCount();
        renderCart();
    };

    const updateCartQty = (productId, delta) => {
        const key = String(productId);
        const item = state.cart.get(key);
        
        if (!item) return;
        
        item.qty += delta;
        
        if (item.qty <= 0) {
            removeFromCart(productId);
        } else if (item.qty > item.stock) {
            item.qty = item.stock;
            showNotification('Stock maximum atteint', 'warning');
        } else {
            updateCartCount();
            renderCart();
        }
    };

    const getCartTotal = () => {
        let total = 0;
        state.cart.forEach(item => {
            total += item.price * item.qty;
        });
        return total;
    };

    const clearCart = () => {
        state.cart.clear();
        updateCartCount();
        renderCart();
    };

    // === RENDER FUNCTIONS ===
    const renderProducts = () => {
        if (!elements.productsGrid) return;

        let filtered = [...state.products];

        // Apply filters
        if (state.filters.category !== 'ALL') {
            filtered = filtered.filter(p => p.catalog_group === state.filters.category);
        }

        if (state.filters.search) {
            const query = state.filters.search.toLowerCase();
            filtered = filtered.filter(p => 
                p.name.toLowerCase().includes(query) ||
                (p.catalog_group || '').toLowerCase().includes(query)
            );
        }

        // Apply sorting
        filtered.sort((a, b) => {
            switch (state.filters.sort) {
                case 'price-asc':
                    return Number(a.price_dzd) - Number(b.price_dzd);
                case 'price-desc':
                    return Number(b.price_dzd) - Number(a.price_dzd);
                case 'name':
                default:
                    return a.name.localeCompare(b.name);
            }
        });

        // Pagination
        const start = (state.pagination.page - 1) * state.pagination.perPage;
        const paged = filtered.slice(start, start + state.pagination.perPage);

        if (paged.length === 0) {
            elements.productsGrid.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="text-6xl mb-4">🔍</div>
                    <p class="text-muted">Aucun produit trouvé</p>
                </div>
            `;
            return;
        }

        elements.productsGrid.innerHTML = paged.map(product => {
            const stock = getStockStatus(product.stock_bottles);
            const inStock = stock.class === 'in-stock' || stock.class === 'low-stock';
            
            return `
                <article class="product-card" data-product-id="${product.id}">
                    <div class="product-image">
                        <div class="product-image-icon">💧</div>
                        <span class="product-badge ${stock.class}">${stock.label}</span>
                    </div>
                    <div class="product-info">
                        <div class="product-category">${product.catalog_group || 'Parfum'}</div>
                        <h3 class="product-name">${product.name}</h3>
                        <p class="product-description">${product.segment || 'Parfum de qualité premium'}</p>
                        <div class="product-footer">
                            <span class="product-price">${formatPrice(product.price_dzd)}</span>
                            <button 
                                class="product-add-btn" 
                                data-add-to-cart="${product.id}"
                                ${!inStock ? 'disabled' : ''}
                            >
                                <i class="bi bi-cart-plus"></i>
                                ${inStock ? '<span data-i18n="shop.add">Ajouter</span>' : '<span data-i18n="shop.outOfStockBtn">Rupture</span>'}
                            </button>
                        </div>
                    </div>
                </article>
            `;
        }).join('');

        // Add event listeners
        elements.productsGrid.querySelectorAll('[data-add-to-cart]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const productId = Number(btn.dataset.addToCart);
                const product = state.products.find(p => p.id === productId);
                if (product) addToCart(product);
            });
        });
    };

    const renderCart = () => {
        if (!elements.cartItems || !elements.cartTotal) return;

        if (state.cart.size === 0) {
            elements.cartItems.innerHTML = `
                <div class="cart-empty">
                    <div class="cart-empty-icon">🛒</div>
                    <p data-i18n="cart.empty">Votre panier est vide</p>
                    <button class="btn btn-primary mt-lg" onclick="document.querySelector('.cart-close').click()">
                        <span data-i18n="checkout.continueShopping">Continuer mes achats</span>
                    </button>
                </div>
            `;
            elements.cartTotal.textContent = formatPrice(0);
            return;
        }

        elements.cartItems.innerHTML = Array.from(state.cart.values()).map(item => `
            <div class="cart-item">
                <div class="cart-item-image">
                    <div class="cart-item-image-icon">💧</div>
                </div>
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-category">${item.category}</div>
                    <div class="cart-item-controls">
                        <button class="qty-btn" data-qty-dec="${item.id}">-</button>
                        <span class="qty-value">${item.qty}</span>
                        <button class="qty-btn" data-qty-inc="${item.id}">+</button>
                        <button class="cart-item-remove" data-remove="${item.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="cart-item-price">${formatPrice(item.price * item.qty)}</div>
                </div>
            </div>
        `).join('');

        elements.cartTotal.textContent = formatPrice(getCartTotal());

        // Add event listeners
        elements.cartItems.querySelectorAll('[data-qty-dec]').forEach(btn => {
            btn.addEventListener('click', () => {
                updateCartQty(Number(btn.dataset.qtyDec), -1);
            });
        });

        elements.cartItems.querySelectorAll('[data-qty-inc]').forEach(btn => {
            btn.addEventListener('click', () => {
                updateCartQty(Number(btn.dataset.qtyInc), 1);
            });
        });

        elements.cartItems.querySelectorAll('[data-remove]').forEach(btn => {
            btn.addEventListener('click', () => {
                removeFromCart(Number(btn.dataset.remove));
            });
        });
    };

    // === CART SIDEBAR ===
    const openCart = () => {
        if (elements.cartSidebar && elements.cartOverlay) {
            elements.cartSidebar.classList.add('open');
            elements.cartOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    const closeCart = () => {
        if (elements.cartSidebar && elements.cartOverlay) {
            elements.cartSidebar.classList.remove('open');
            elements.cartOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // === NOTIFICATIONS ===
    const showNotification = (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };

    // === LOAD DATA ===
    const loadProducts = async () => {
        try {
            const response = await fetch(API.products);
            if (!response.ok) throw new Error('Failed to load products');
            
            const data = await response.json();
            state.products = data.perfumes || [];
            renderProducts();
        } catch (error) {
            console.error('Error loading products:', error);
            showNotification('Erreur de chargement des produits', 'danger');
        }
    };

    // === EVENT LISTENERS ===
    const initEventListeners = () => {
        // Cart button
        if (elements.cartBtn) {
            elements.cartBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openCart();
            });
        }

        // Cart close
        if (elements.cartCloseBtn) {
            elements.cartCloseBtn.addEventListener('click', closeCart);
        }

        // Cart overlay
        if (elements.cartOverlay) {
            elements.cartOverlay.addEventListener('click', closeCart);
        }

        // Search
        if (elements.searchInput) {
            elements.searchInput.addEventListener('input', debounce((e) => {
                state.filters.search = e.target.value;
                state.pagination.page = 1;
                renderProducts();
            }, 300));
        }

        // Category filter
        if (elements.categoryFilter) {
            elements.categoryFilter.addEventListener('change', (e) => {
                state.filters.category = e.target.value;
                state.pagination.page = 1;
                renderProducts();
            });
        }

        // Sort filter
        if (elements.sortFilter) {
            elements.sortFilter.addEventListener('change', (e) => {
                state.filters.sort = e.target.value;
                renderProducts();
            });
        }

        // Checkout
        if (elements.checkoutBtn) {
            elements.checkoutBtn.addEventListener('click', () => {
                if (state.cart.size === 0) {
                    showNotification('Votre panier est vide', 'warning');
                    return;
                }
                // Redirect to checkout
                window.location.href = '/dashboard#commandes';
            });
        }

        // Mobile menu
        if (elements.mobileMenuBtn) {
            elements.mobileMenuBtn.addEventListener('click', () => {
                elements.sidebar?.classList.toggle('mobile-open');
            });
        }

        // Navigation
        document.querySelectorAll('.shop-nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const view = item.dataset.view;
                if (view === 'cart') {
                    e.preventDefault();
                    openCart();
                }
                
                // Update active state
                document.querySelectorAll('.shop-nav-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeCart();
            }
        });
    };

    // === INIT ===
    const init = async () => {
        console.log('🛒 IDENE E-Commerce initialized');
        initEventListeners();
        await loadProducts();
        updateCartCount();
        renderCart();
    };

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for debugging
    window.IDENE_SHOP = {
        state,
        addToCart,
        removeFromCart,
        clearCart,
        openCart,
        closeCart
    };

})();

