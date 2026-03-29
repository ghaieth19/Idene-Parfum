const revealItems = document.querySelectorAll(".reveal");
const viewLinks = document.querySelectorAll("[data-view-link]");
const workflowMenuLinks = document.querySelectorAll("[data-workflow-step]");
const themeToggle = document.getElementById("themeToggle");
const logoutBtn = document.getElementById("logoutBtn");
const clientSidebar = document.getElementById("clientSidebar");
const mobileSidebarToggle = document.getElementById("mobileSidebarToggle");
const mobileSidebarToggleInline = document.getElementById("mobileSidebarToggleInline");
const mobileSidebarBackdrop = document.getElementById("mobileSidebarBackdrop");
const mobileSidebarClose = document.getElementById("mobileSidebarClose");
const commandMenuToggle = document.getElementById("commandMenuToggle");
const commandSubmenu = document.getElementById("commandSubmenu");

const homePerfumeSearch = document.getElementById("homePerfumeSearch");
const homeCategorySelect = document.getElementById("homeCategorySelect");
const homePerfumeGrid = document.getElementById("homePerfumeGrid");
const homePerfumeEmpty = document.getElementById("homePerfumeEmpty");

const facturesSearch = document.getElementById("facturesSearch");
const facturesDateSearch = document.getElementById("facturesDateSearch");
const facturesTableBody = document.getElementById("facturesTableBody");
const facturesEmpty = document.getElementById("facturesEmpty");
const outOfStockGrid = document.getElementById("outOfStockGrid");
const outOfStockEmpty = document.getElementById("outOfStockEmpty");

const profilSearch = document.getElementById("profilSearch");
const profilItems = document.querySelectorAll("#view-profil .searchable");
const profileMessage = document.getElementById("profileMessage");
const profileLastName = document.getElementById("profileLastName");
const profileFirstName = document.getElementById("profileFirstName");
const profileShopName = document.getElementById("profileShopName");
const profilePhone = document.getElementById("profilePhone");
const profileLocation = document.getElementById("profileLocation");
const profileEmail = document.getElementById("profileEmail");
const saveProfileBtn = document.getElementById("saveProfileBtn");

const storeSearch = document.getElementById("storeSearch");
const storeCategory = document.getElementById("storeCategory");
const storeProducts = document.getElementById("storeProducts");
const storePagination = document.getElementById("storePagination");
const cartItemsEl = document.getElementById("cartItems");
const cartTotalEl = document.getElementById("cartTotal");

const wfTabs = document.querySelectorAll(".wf-tab");
const wfSteps = {
    catalogue: document.getElementById("step-catalogue"),
    coordonnees: document.getElementById("step-coordonnees"),
    facture: document.getElementById("step-facture"),
    historique: document.getElementById("step-historique"),
};

const toCoordonneesBtn = document.getElementById("toCoordonneesBtn");
const backToStoreBtn = document.getElementById("backToStoreBtn");
const toFactureBtn = document.getElementById("toFactureBtn");
const editCoordonneesBtn = document.getElementById("editCoordonneesBtn");
const exportInvoicePdfBtn = document.getElementById("exportInvoicePdfBtn");
const confirmOrderBtn = document.getElementById("confirmOrderBtn");

const clientLastName = document.getElementById("clientLastName");
const clientFirstName = document.getElementById("clientFirstName");
const clientPhone = document.getElementById("clientPhone");
const clientShop = document.getElementById("clientShop");

const invoiceNumber = document.getElementById("invoiceNumber");
const invoiceClient = document.getElementById("invoiceClient");
const invoiceLines = document.getElementById("invoiceLines");
const invoiceTotal = document.getElementById("invoiceTotal");

const ordersSearch = document.getElementById("ordersSearch");
const ordersTableBody = document.getElementById("ordersTableBody");
const ordersEmpty = document.getElementById("ordersEmpty");

const views = {
    home: document.getElementById("view-home"),
    commandes: document.getElementById("view-commandes"),
    factures: document.getElementById("view-factures"),
    "hors-stock": document.getElementById("view-hors-stock"),
    profil: document.getElementById("view-profil"),
};

const isMobileViewport = () => window.innerWidth <= 920;

const setSidebarState = (open) => {
    if (!clientSidebar || !mobileSidebarBackdrop) return;
    const mobile = isMobileViewport();
    if (mobile) {
        clientSidebar.classList.toggle("mobile-open", open);
        clientSidebar.classList.toggle("sidebar-hidden", !open);
        mobileSidebarBackdrop.classList.toggle("active", open);
        document.body.classList.toggle("sidebar-open", open);
    } else {
        clientSidebar.classList.remove("mobile-open");
        clientSidebar.classList.remove("sidebar-hidden");
        mobileSidebarBackdrop.classList.remove("active");
        document.body.classList.remove("sidebar-open");
    }
    document.body.classList.remove("sidebar-collapsed");
};

const closeMobileSidebar = () => setSidebarState(false);
const openMobileSidebar = () => setSidebarState(true);
const toggleSidebar = () => {
    if (!isMobileViewport()) return;
    const isOpen = clientSidebar?.classList.contains("mobile-open");
    setSidebarState(!isOpen);
};

const API = {
    perfumes: "/api/perfumes",
    history: "/api/orders/history",
    me: "/api/auth/me",
    logout: "/api/auth/logout",
    profile: "/api/profile",
};

let allPerfumes = [];
let historyItems = [];
let currentInvoice = null;
let currentUser = null;
const cart = new Map();
const pagination = {
    store: { page: 1, perPage: 12 },
};

const formatDT = (n) => `${Number(n).toFixed(2)} DT`;

const getDeliveryLabel = (status) => {
    const normalized = String(status || "").toUpperCase();
    if (normalized === "LIVREE") return "Livree";
    if (normalized === "EXPEDIEE") return "Expediee";
    if (normalized === "CONFIRMEE") return "Confirmee";
    if (normalized === "ANNULEE") return "Annulee";
    return "En preparation";
};

const toFamily = (p) => {
    if (p.catalog_group === "PRINCIPAL" && p.segment === "HOMME") return "PRINCIPAL_HOMME";
    if (p.catalog_group === "PRINCIPAL" && p.segment === "FEMME") return "PRINCIPAL_FEMME";
    return p.catalog_group;
};

const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) entry.target.classList.add("in");
        });
    },
    { threshold: 0.2 }
);
revealItems.forEach((item) => revealObserver.observe(item));

const animateCounter = (el) => {
    const target = Number(el.dataset.counter || 0);
    const duration = 850;
    const start = performance.now();
    const step = (time) => {
        const progress = Math.min((time - start) / duration, 1);
        el.textContent = Math.floor(progress * target).toLocaleString("fr-FR");
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
};
document.querySelectorAll("[data-counter]").forEach(animateCounter);

const bindTextSearch = (input, items) => {
    if (!input) return;
    input.addEventListener("input", () => {
        const query = input.value.trim().toLowerCase();
        items.forEach((item) => {
            item.style.display = item.innerText.toLowerCase().includes(query) ? "" : "none";
        });
    });
};
bindTextSearch(profilSearch, profilItems);

const setProfileMessage = (text, type = "") => {
    if (!profileMessage) return;
    profileMessage.textContent = text;
    profileMessage.classList.remove("is-error", "is-success");
    if (type) {
        profileMessage.classList.add(type);
    }
};

const setCommandMenuState = (open) => {
    commandMenuToggle?.classList.toggle("active", open);
    commandSubmenu?.classList.toggle("open", open);
};

const activateView = (name) => {
    Object.entries(views).forEach(([key, el]) => {
        if (!el) return;
        el.classList.toggle("active", key === name);
        if (key === name) {
            el.querySelectorAll(".reveal").forEach((item) => item.classList.add("in"));
        }
    });
    viewLinks.forEach((link) => {
        const isWorkflowLink = Boolean(link.dataset.workflowStep);
        link.classList.toggle("active", link.dataset.viewLink === name && !isWorkflowLink);
    });
    setCommandMenuState(name === "commandes");
};

commandMenuToggle?.addEventListener("click", () => {
    const isOpen = commandSubmenu?.classList.contains("open");
    const nextState = !isOpen;
    setCommandMenuState(nextState);
    if (nextState) {
        activateView("commandes");
        setWorkflowStep("catalogue");
    }
});

viewLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
        event.preventDefault();
        activateView(link.dataset.viewLink);
        if (link.dataset.workflowStep) {
            setWorkflowStep(link.dataset.workflowStep);
        }
        if (window.innerWidth <= 920) closeMobileSidebar();
    });
});

[mobileSidebarToggle, mobileSidebarToggleInline].forEach((button) => {
    button?.addEventListener("click", toggleSidebar);
});

mobileSidebarBackdrop?.addEventListener("click", closeMobileSidebar);
mobileSidebarClose?.addEventListener("click", closeMobileSidebar);

window.addEventListener("resize", () => {
    setSidebarState(false);
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeMobileSidebar();
});

const syncThemeToggle = () => {
    if (!themeToggle) return;
    const isLight = document.body.classList.contains("theme-light");
    themeToggle.classList.toggle("active", !isLight);
    themeToggle.textContent = isLight ? "Mode premium" : "Mode clair";
};

if (themeToggle) {
    syncThemeToggle();
    themeToggle.addEventListener("click", () => {
        document.body.classList.toggle("theme-light");
        syncThemeToggle();
    });
}

const setWorkflowStep = (stepName) => {
    Object.entries(wfSteps).forEach(([key, el]) => {
        if (!el) return;
        el.classList.toggle("active", key === stepName);
    });
    wfTabs.forEach((tab) => tab.classList.toggle("active", tab.dataset.step === stepName));
    workflowMenuLinks.forEach((link) => link.classList.toggle("active", link.dataset.workflowStep === stepName));
};

wfTabs.forEach((tab) => {
    tab.addEventListener("click", () => setWorkflowStep(tab.dataset.step));
});

const renderHomePerfumes = () => {
    if (!homePerfumeGrid) return;
    const query = (homePerfumeSearch?.value ?? "").trim().toLowerCase();
    const category = homeCategorySelect?.value ?? "ALL";
    const filtered = allPerfumes.filter((p) => {
        const matchQuery = p.name.toLowerCase().includes(query);
        const matchCategory = category === "ALL" || p.catalog_group === category;
        return matchQuery && matchCategory;
    });

    homePerfumeGrid.innerHTML = filtered
        .slice(0, 120)
        .map((p) => `<article class="perfume-chip"><h3>${p.name}</h3><p>${p.catalog_group} ${p.segment ?? ""}</p></article>`)
        .join("");

    if (homePerfumeEmpty) {
        homePerfumeEmpty.style.display = filtered.length === 0 ? "" : "none";
        homePerfumeEmpty.textContent = filtered.length === 0 ? "Aucun parfum trouve." : "";
    }
};

const renderOutOfStock = () => {
    if (!outOfStockGrid || !outOfStockEmpty) return;
    const items = allPerfumes.filter((p) => Number(p.stock_bottles || 0) <= 0);
    outOfStockGrid.innerHTML = items
        .slice(0, 14)
        .map((p) => `
            <article class="perfume-chip out-stock-chip">
                <h3>${p.name}</h3>
                <p>${p.catalog_group} ${p.segment ?? ""}</p>
                <span class="stock-badge out">Hors stock</span>
            </article>
        `)
        .join("");

    outOfStockEmpty.style.display = items.length === 0 ? "" : "none";
    outOfStockEmpty.textContent = items.length === 0 ? "Aucun produit hors stock." : "";
};

const renderStorePerfumes = () => {
    if (!storeProducts) return;
    const query = (storeSearch?.value ?? "").trim().toLowerCase();
    const category = storeCategory?.value ?? "ALL";
    const filtered = allPerfumes.filter((p) => {
        const family = toFamily(p);
        const matchCategory = category === "ALL" || family === category;
        const matchQuery = p.name.toLowerCase().includes(query);
        return matchCategory && matchQuery;
    });
    const totalPages = Math.max(1, Math.ceil(filtered.length / pagination.store.perPage));
    if (pagination.store.page > totalPages) pagination.store.page = totalPages;
    const start = (pagination.store.page - 1) * pagination.store.perPage;
    const paged = filtered.slice(start, start + pagination.store.perPage);

    storeProducts.innerHTML = paged
        .map((p) => {
            const family = toFamily(p);
            const stockQty = Number(p.stock_bottles || 0);
            const inStock = stockQty > 0;
            return `
            <article class="store-item" data-perfume-id="${p.id}" data-name="${p.name}" data-category="${family}" data-price="${p.price_dzd}" data-stock="${inStock ? "in" : "out"}">
                <h3>${p.name}</h3>
                <p>${family.replace("_", " ")}</p>
                <strong>${formatDT(p.price_dzd)}</strong>
                <small class="store-stock-count">${stockQty} bouteille${stockQty > 1 ? "s" : ""}</small>
                <span class="stock-badge ${inStock ? "in" : "out"}">${inStock ? "En stock" : "Hors stock"}</span>
                <button class="btn btn-primary add-to-cart" type="button" ${inStock ? "" : "disabled"}>${inStock ? "Ajouter" : "Indisponible"}</button>
            </article>`;
        })
        .join("");

    if (filtered.length === 0) {
        storeProducts.innerHTML = "<p class='muted'>Aucun parfum trouve.</p>";
    }

    if (storePagination) {
        storePagination.innerHTML = renderClientPagination(filtered.length, pagination.store.page, totalPages, "store");
    }
};

if (homePerfumeSearch) homePerfumeSearch.addEventListener("input", renderHomePerfumes);
if (homeCategorySelect) homeCategorySelect.addEventListener("change", renderHomePerfumes);
if (storeSearch) storeSearch.addEventListener("input", () => {
    pagination.store.page = 1;
    renderStorePerfumes();
});
if (storeCategory) storeCategory.addEventListener("change", () => {
    pagination.store.page = 1;
    renderStorePerfumes();
});

const renderClientPagination = (totalItems, currentPage, totalPages, key) => {
    const startItem = totalItems === 0 ? 0 : ((currentPage - 1) * pagination[key].perPage) + 1;
    const endItem = Math.min(currentPage * pagination[key].perPage, totalItems);
    return `
        <div class="pagination-summary">
            <span>${startItem}-${endItem} sur ${totalItems}</span>
        </div>
        <div class="pagination-actions">
            <button class="btn-small" type="button" data-page-key="${key}" data-page-action="prev" ${currentPage <= 1 ? "disabled" : ""}>Precedent</button>
            <span class="pagination-index">Page ${currentPage} / ${totalPages}</span>
            <button class="btn-small" type="button" data-page-key="${key}" data-page-action="next" ${currentPage >= totalPages ? "disabled" : ""}>Suivant</button>
        </div>
    `;
};

storePagination?.addEventListener("click", (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    const key = target.dataset.pageKey;
    const action = target.dataset.pageAction;
    if (key !== "store" || !action) return;
    if (action === "prev" && pagination.store.page > 1) pagination.store.page -= 1;
    if (action === "next") pagination.store.page += 1;
    renderStorePerfumes();
});

const getCartTotal = () => {
    let total = 0;
    cart.forEach((item) => {
        total += item.price * item.qty;
    });
    return total;
};

const renderCart = () => {
    if (!cartItemsEl || !cartTotalEl) return;
    cartItemsEl.innerHTML = "";
    if (cart.size === 0) {
        cartItemsEl.innerHTML = "<p class='muted'>Panier vide.</p>";
        cartTotalEl.textContent = formatDT(0);
        return;
    }

    cart.forEach((item) => {
        const lineTotal = item.price * item.qty;
        const row = document.createElement("article");
        row.className = "cart-line";
        row.innerHTML = `
            <div class="cart-line-head">
                <strong>${item.name}</strong>
                <button type="button" class="remove-btn" data-remove="${item.key}">Supprimer</button>
            </div>
            <div class="qty-row">
                <button type="button" class="qty-btn" data-dec="${item.key}">-</button>
                <span>Quantite: ${item.qty}</span>
                <button type="button" class="qty-btn" data-inc="${item.key}">+</button>
            </div>
            <small>${formatDT(item.price)} x ${item.qty} = ${formatDT(lineTotal)}</small>
        `;
        cartItemsEl.appendChild(row);
    });

    cartTotalEl.textContent = formatDT(getCartTotal());
};

if (storeProducts) {
    storeProducts.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement) || !target.classList.contains("add-to-cart")) return;
        const card = target.closest(".store-item");
        if (!card || card.dataset.stock === "out") return;
        const perfumeId = Number(card.dataset.perfumeId);
        const key = String(perfumeId);
        const existing = cart.get(key);
        if (existing) existing.qty += 1;
        else {
            cart.set(key, {
                key,
                perfume_id: perfumeId,
                name: card.dataset.name,
                category: card.dataset.category,
                price: Number(card.dataset.price || 0),
                qty: 1,
            });
        }
        renderCart();
    });
}

if (cartItemsEl) {
    cartItemsEl.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const dec = target.dataset.dec;
        const inc = target.dataset.inc;
        const remove = target.dataset.remove;
        if (remove && cart.has(remove)) cart.delete(remove);
        if (dec && cart.has(dec)) {
            const item = cart.get(dec);
            if (item.qty > 1) item.qty -= 1;
            else cart.delete(dec);
        }
        if (inc && cart.has(inc)) cart.get(inc).qty += 1;
        renderCart();
    });
}

const buildInvoiceView = (invoiceNo) => {
    if (!invoiceNumber || !invoiceClient || !invoiceLines || !invoiceTotal) return null;
    invoiceNumber.textContent = invoiceNo;
    invoiceClient.innerHTML = `
        <p><strong>Nom:</strong> ${clientLastName.value}</p>
        <p><strong>Prenom:</strong> ${clientFirstName.value}</p>
        <p><strong>Telephone:</strong> ${clientPhone.value}</p>
        <p><strong>Parfumerie:</strong> ${clientShop.value}</p>
    `;
    invoiceLines.innerHTML = "";
    cart.forEach((item) => {
        const lineTotal = item.price * item.qty;
        const line = document.createElement("div");
        line.className = "invoice-line";
        line.innerHTML = `<span>${item.name} x ${item.qty}</span><strong>${formatDT(lineTotal)}</strong>`;
        invoiceLines.appendChild(line);
    });
    invoiceTotal.textContent = formatDT(getCartTotal());
};

const openInvoicePdf = (invoiceId) => {
    if (!invoiceId) return;
    window.open(`/invoice/${invoiceId}/pdf`, "_blank");
};

const populateProfile = (user) => {
    currentUser = user;
    if (profileLastName) profileLastName.value = user.last_name ?? "";
    if (profileFirstName) profileFirstName.value = user.first_name ?? "";
    if (profileShopName) profileShopName.value = user.perfume_shop_name ?? "";
    if (profilePhone) profilePhone.value = user.phone ?? "";
    if (profileLocation) profileLocation.value = user.location ?? "";
    if (profileEmail) profileEmail.value = user.email ?? "";

    if (clientLastName && !clientLastName.value) clientLastName.value = user.last_name ?? "";
    if (clientFirstName && !clientFirstName.value) clientFirstName.value = user.first_name ?? "";
    if (clientPhone && !clientPhone.value) clientPhone.value = user.phone ?? "";
    if (clientShop && !clientShop.value) clientShop.value = user.perfume_shop_name ?? "";
};

const loadMe = async () => {
    const response = await fetch(API.me);
    if (response.status === 401) {
        window.location.href = "/auth";
        return false;
    }

    const result = await response.json();
    if (!result.authenticated || !result.user) {
        window.location.href = "/auth";
        return false;
    }

    populateProfile(result.user);
    return true;
};

const loadHistory = async () => {
    const response = await fetch(API.history);
    if (response.status === 401) {
        window.location.href = "/auth";
        return;
    }
    const data = await response.json();
    historyItems = data.items || [];
    renderOrdersTable();
    renderFacturesTable();
};

const parseClientNotes = (notes) => {
    try {
        return JSON.parse(notes || "{}");
    } catch {
        return {};
    }
};

const renderOrdersTable = () => {
    if (!ordersTableBody || !ordersEmpty) return;
    ordersTableBody.innerHTML = "";
    if (historyItems.length === 0) {
        ordersEmpty.style.display = "";
        return;
    }
    ordersEmpty.style.display = "none";

    historyItems.forEach((row) => {
        const client = parseClientNotes(row.notes);
        const paid = Number(row.paid_amount || 0);
        const total = Number(row.invoice_total || row.total_dzd || 0);
        const paymentLabel = paid >= total && total > 0 ? "Paye" : paid > 0 ? "Partiel" : "Non paye";
        const deliveryLabel = getDeliveryLabel(row.order_status);
        const tr = document.createElement("tr");
        tr.className = "order-row";
        tr.innerHTML = `
            <td>${row.order_number}</td>
            <td>${row.invoice_number ?? "-"}</td>
            <td>${formatDT(total)}</td>
            <td>${client.last_name ?? ""} ${client.first_name ?? ""} - ${client.phone ?? ""} - ${client.shop ?? ""}</td>
            <td><span class="invoice-tag ${deliveryLabel === "Livree" ? "paid" : deliveryLabel === "Annulee" ? "unpaid" : "partial"}">${deliveryLabel}</span></td>
            <td><span class="invoice-tag ${paymentLabel === "Paye" ? "paid" : paymentLabel === "Partiel" ? "partial" : "unpaid"}">${paymentLabel}</span></td>
            <td>
                <div class="table-actions">
                    <button type="button" class="btn-small" data-edit="${row.order_id}">Modifier</button>
                    <button type="button" class="btn-small danger" data-delete="${row.order_id}">Supprimer</button>
                    ${row.invoice_id ? `<button type="button" class="btn-small" data-pdf="${row.invoice_id}">PDF</button>` : ""}
                </div>
            </td>
        `;
        ordersTableBody.appendChild(tr);
    });
};

const applyFactureFilters = () => {
    if (!facturesTableBody || !facturesEmpty) return;
    const query = (facturesSearch?.value ?? "").trim().toLowerCase();
    const date = facturesDateSearch?.value ?? "";
    let visible = 0;
    facturesTableBody.querySelectorAll(".facture-row").forEach((row) => {
        const matchQuery = row.innerText.toLowerCase().includes(query);
        const matchDate = !date || row.dataset.date === date;
        const show = matchQuery && matchDate;
        row.style.display = show ? "" : "none";
        if (show) visible += 1;
    });
    facturesEmpty.style.display = visible === 0 ? "" : "none";
};

const renderFacturesTable = () => {
    if (!facturesTableBody || !facturesEmpty) return;
    facturesTableBody.innerHTML = "";
    historyItems.forEach((row) => {
        const client = parseClientNotes(row.notes);
        const total = Number(row.invoice_total || row.total_dzd || 0);
        const paid = Number(row.paid_amount || 0);
        const status = paid >= total && total > 0 ? "Paye" : paid > 0 ? "Partiel" : "Non paye";
        const tr = document.createElement("tr");
        tr.className = "facture-row searchable";
        tr.dataset.date = String(row.issued_at || row.order_date || "").slice(0, 10);
        tr.innerHTML = `
            <td>${tr.dataset.date || "-"}</td>
            <td>${row.invoice_number ?? "-"}</td>
            <td>${client.shop ?? `${client.last_name ?? ""} ${client.first_name ?? ""}`}</td>
            <td>${formatDT(total)}</td>
            <td>
                <span class="invoice-tag ${status === "Paye" ? "paid" : status === "Partiel" ? "partial" : "unpaid"}">${status}</span>
            </td>
            <td>${row.invoice_id ? `<button type="button" class="btn-small facture-pdf-btn" data-pdf="${row.invoice_id}">Exporter PDF</button>` : "-"}</td>
        `;
        facturesTableBody.appendChild(tr);
    });
    applyFactureFilters();
};

if (facturesSearch) facturesSearch.addEventListener("input", applyFactureFilters);
if (facturesDateSearch) facturesDateSearch.addEventListener("change", applyFactureFilters);

if (toCoordonneesBtn) {
    toCoordonneesBtn.addEventListener("click", () => {
        if (cart.size === 0) return alert("Votre panier est vide.");
        setWorkflowStep("coordonnees");
    });
}

if (backToStoreBtn) backToStoreBtn.addEventListener("click", () => setWorkflowStep("catalogue"));

if (toFactureBtn) {
    toFactureBtn.addEventListener("click", () => {
        if (!clientLastName?.value.trim() || !clientFirstName?.value.trim() || !clientPhone?.value.trim() || !clientShop?.value.trim()) {
            return alert("Veuillez remplir nom, prenom, telephone et parfumerie.");
        }
        currentInvoice = { invoiceNo: `FAC-${new Date().getFullYear()}-PENDING` };
        buildInvoiceView(currentInvoice.invoiceNo);
        setWorkflowStep("facture");
    });
}

if (editCoordonneesBtn) editCoordonneesBtn.addEventListener("click", () => setWorkflowStep("coordonnees"));

if (exportInvoicePdfBtn) {
    exportInvoicePdfBtn.addEventListener("click", () => {
        if (!currentInvoice?.invoiceId) {
            alert("Confirmez d'abord la commande pour obtenir une facture PDF.");
            return;
        }
        openInvoicePdf(currentInvoice.invoiceId);
    });
}

if (confirmOrderBtn) {
    confirmOrderBtn.addEventListener("click", async () => {
        if (cart.size === 0) return alert("Panier vide.");
        const payload = {
            client: {
                last_name: clientLastName.value.trim(),
                first_name: clientFirstName.value.trim(),
                phone: clientPhone.value.trim(),
                shop: clientShop.value.trim(),
            },
            items: Array.from(cart.values()).map((x) => ({
                perfume_id: x.perfume_id,
                qty: x.qty,
                unit_price: x.price,
            })),
        };

        const response = await fetch("/api/orders", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) return alert(result.error || "Erreur creation commande.");

        currentInvoice = { invoiceId: result.invoice_id ?? null, invoiceNo: result.invoice_number };
        buildInvoiceView(result.invoice_number);
        cart.clear();
        renderCart();
        await loadHistory();
        setWorkflowStep("historique");
    });
}

if (ordersTableBody) {
    ordersTableBody.addEventListener("click", async (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;

        if (target.dataset.delete) {
            const orderId = Number(target.dataset.delete);
            await fetch(`/api/orders/${orderId}`, { method: "DELETE" });
            await loadHistory();
            return;
        }

        if (target.dataset.pdf) {
            const invoiceId = Number(target.dataset.pdf);
            openInvoicePdf(invoiceId);
            return;
        }

        if (target.dataset.edit) {
            const orderId = Number(target.dataset.edit);
            const row = historyItems.find((x) => Number(x.order_id) === orderId);
            if (!row) return;
            const current = parseClientNotes(row.notes);
            const newPhone = prompt("Nouveau telephone:", current.phone ?? "");
            if (newPhone === null) return;
            const newShop = prompt("Nouveau nom parfumerie:", current.shop ?? "");
            if (newShop === null) return;
            await fetch(`/api/orders/${orderId}/client`, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    last_name: current.last_name ?? "",
                    first_name: current.first_name ?? "",
                    phone: newPhone,
                    shop: newShop,
                }),
            });
            await loadHistory();
        }
    });
}

if (saveProfileBtn) {
    saveProfileBtn.addEventListener("click", async () => {
        const payload = {
            last_name: profileLastName?.value.trim() ?? "",
            first_name: profileFirstName?.value.trim() ?? "",
            perfume_shop_name: profileShopName?.value.trim() ?? "",
            phone: profilePhone?.value.trim() ?? "",
            location: profileLocation?.value.trim() ?? "",
            email: profileEmail?.value.trim() ?? "",
        };

        saveProfileBtn.disabled = true;
        setProfileMessage("");
        try {
            const response = await fetch(API.profile, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (!response.ok) {
                setProfileMessage(result.error || "Erreur mise a jour profil.", "is-error");
                return;
            }
            setProfileMessage("Profil mis a jour.", "is-success");
            populateProfile({
                ...(currentUser || {}),
                ...payload,
            });
        } catch {
            setProfileMessage("Connexion serveur impossible.", "is-error");
        } finally {
            saveProfileBtn.disabled = false;
        }
    });
}

if (logoutBtn) {
    logoutBtn.addEventListener("click", async () => {
        await fetch(API.logout, { method: "POST" });
        window.location.href = "/auth";
    });
}

if (facturesTableBody) {
    facturesTableBody.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.dataset.pdf) {
            const invoiceId = Number(target.dataset.pdf);
            openInvoicePdf(invoiceId);
        }
    });
}

if (ordersSearch) {
    ordersSearch.addEventListener("input", () => {
        const q = ordersSearch.value.trim().toLowerCase();
        document.querySelectorAll(".order-row").forEach((row) => {
            row.style.display = row.innerText.toLowerCase().includes(q) ? "" : "none";
        });
    });
}

const init = async () => {
    setSidebarState(false);
    setCommandMenuState(false);
    const ok = await loadMe();
    if (!ok) return;
    const perfumesRes = await fetch(API.perfumes);
    const perfumesData = await perfumesRes.json();
    allPerfumes = perfumesData.items || [];
    renderHomePerfumes();
    renderOutOfStock();
    renderStorePerfumes();
    renderCart();
    await loadHistory();
};

init();
