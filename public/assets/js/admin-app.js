/* â”€â”€ Theme toggle â”€â”€ */
const themeToggleBtn = document.getElementById("themeToggleBtn");

const applyTheme = (theme) => {
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("idene-admin-theme", theme);
};

const savedTheme = localStorage.getItem("idene-admin-theme") || "light";
applyTheme(savedTheme);

themeToggleBtn?.addEventListener("click", () => {
    const current = document.documentElement.getAttribute("data-theme") || "light";
    applyTheme(current === "dark" ? "light" : "dark");
});

/* â”€â”€ DOM refs â”€â”€ */
const adminLinks = document.querySelectorAll(".admin-link");
const adminViews = document.querySelectorAll(".admin-view");
const adminLogoutBtn = document.getElementById("adminLogoutBtn");
const adminSidebar = document.getElementById("adminSidebar");
const mobileAdminSidebarToggle = document.getElementById("mobileAdminSidebarToggle");
const mobileAdminSidebarToggleInline = document.getElementById("mobileAdminSidebarToggleInline");
const mobileAdminSidebarBackdrop = document.getElementById("mobileAdminSidebarBackdrop");
const mobileAdminSidebarClose = document.getElementById("mobileAdminSidebarClose");

const adminMetricsGrid = document.getElementById("adminMetricsGrid");
const adminRecentOrdersBody = document.getElementById("adminRecentOrdersBody");
const adminRecentExpensesBody = document.getElementById("adminRecentExpensesBody");
const adminActivityChart = document.getElementById("adminActivityChart");

const productForm = document.getElementById("productForm");
const productFormPanel = document.getElementById("productFormPanel");
const showProductFormBtn = document.getElementById("showProductFormBtn");
const hideProductFormBtn = document.getElementById("hideProductFormBtn");
const productFormMessage = document.getElementById("productFormMessage");
const productId = document.getElementById("productId");
const productCatalogGroup = document.getElementById("productCatalogGroup");
const productSegment = document.getElementById("productSegment");
const productCode = document.getElementById("productCode");
const productName = document.getElementById("productName");
const productPrice = document.getElementById("productPrice");
const productStock = document.getElementById("productStock");
const productAlert = document.getElementById("productAlert");
const productRawMaterialStock = document.getElementById("productRawMaterialStock");
const productRawMaterialAlert = document.getElementById("productRawMaterialAlert");
const productSku = document.getElementById("productSku");
const productBarcode = document.getElementById("productBarcode");
const productActive = document.getElementById("productActive");
const productResetBtn = document.getElementById("productResetBtn");
const productSearch = document.getElementById("productSearch");
const adminProductsBody = document.getElementById("adminProductsBody");
const productSectionStats = document.getElementById("productSectionStats");
const productsPagination = document.getElementById("productsPagination");
const rawMaterialSearch = document.getElementById("rawMaterialSearch");
const rawMaterialAddBtn = document.getElementById("rawMaterialAddBtn");
const rawMaterialSectionStats = document.getElementById("rawMaterialSectionStats");
const adminRawMaterialsBody = document.getElementById("adminRawMaterialsBody");
const rawMaterialsPagination = document.getElementById("rawMaterialsPagination");
const perfumeStockSearch = document.getElementById("perfumeStockSearch");
const perfumeStockCategoryFilter = document.getElementById("perfumeStockCategoryFilter");
const perfumeStockSort = document.getElementById("perfumeStockSort");
const perfumeStockStats = document.getElementById("perfumeStockStats");
const perfumeStockBody = document.getElementById("perfumeStockBody");
const perfumeStockPagination = document.getElementById("perfumeStockPagination");
const formatBottleCount = (value) => `${Number(value || 0).toFixed(2)} bouteille${Number(value || 0) > 1 ? "s" : ""}`;

const orderSearch = document.getElementById("orderSearch");
const ordersAllBtn = document.getElementById("ordersAllBtn");
const ordersPartialBtn = document.getElementById("ordersPartialBtn");
const orderShopFilter = document.getElementById("orderShopFilter");
const orderStatusFilter = document.getElementById("orderStatusFilter");
const orderInvoiceFilter = document.getElementById("orderInvoiceFilter");
const orderDateFrom = document.getElementById("orderDateFrom");
const orderDateTo = document.getElementById("orderDateTo");
const orderFiltersResetBtn = document.getElementById("orderFiltersResetBtn");
const showOrderCreateBtn = document.getElementById("showOrderCreateBtn");
const orderCreatePanel = document.getElementById("orderCreatePanel");
const hideOrderCreateBtn = document.getElementById("hideOrderCreateBtn");
const orderCreateMessage = document.getElementById("orderCreateMessage");
const orderCreateForm = document.getElementById("orderCreateForm");
const orderCreateShop = document.getElementById("orderCreateShop");
const orderCreateShopList = document.getElementById("orderCreateShopList");
const orderCreateProductSearch = document.getElementById("orderCreateProductSearch");
const orderCreateProduct = document.getElementById("orderCreateProduct");
const orderCreateQty = document.getElementById("orderCreateQty");
const orderAddItemBtn = document.getElementById("orderAddItemBtn");
const orderCreateItemsBody = document.getElementById("orderCreateItemsBody");
const orderCreateTotal = document.getElementById("orderCreateTotal");
const adminOrdersBody = document.getElementById("adminOrdersBody");
const orderSectionStats = document.getElementById("orderSectionStats");
const ordersPagination = document.getElementById("ordersPagination");
const orderDetailPanel = document.getElementById("orderDetailPanel");
const orderDetailTitle = document.getElementById("orderDetailTitle");
const orderDetailSummary = document.getElementById("orderDetailSummary");
const orderDetailItemsBody = document.getElementById("orderDetailItemsBody");
const orderDetailMessage = document.getElementById("orderDetailMessage");
const hideOrderDetailBtn = document.getElementById("hideOrderDetailBtn");
const exportOrderPdfBtn = document.getElementById("exportOrderPdfBtn");
const orderEditForm = document.getElementById("orderEditForm");
const orderEditId = document.getElementById("orderEditId");
const orderEditLastName = document.getElementById("orderEditLastName");
const orderEditFirstName = document.getElementById("orderEditFirstName");
const orderEditPhone = document.getElementById("orderEditPhone");
const orderEditShop = document.getElementById("orderEditShop");
const paymentModal = document.getElementById("paymentModal");
const paymentModalBackdrop = document.getElementById("paymentModalBackdrop");
const paymentModalCloseBtn = document.getElementById("paymentModalCloseBtn");
const paymentModalCancelBtn = document.getElementById("paymentModalCancelBtn");
const paymentModalConfirmBtn = document.getElementById("paymentModalConfirmBtn");
const paymentAlreadyPaid = document.getElementById("paymentAlreadyPaid");
const paymentRemaining = document.getElementById("paymentRemaining");
const paymentAmountInput = document.getElementById("paymentAmountInput");
const paymentModalMessage = document.getElementById("paymentModalMessage");

const employeeForm = document.getElementById("employeeForm");
const employeeFormPanel = document.getElementById("employeeFormPanel");
const showEmployeeFormBtn = document.getElementById("showEmployeeFormBtn");
const hideEmployeeFormBtn = document.getElementById("hideEmployeeFormBtn");
const employeeFormMessage = document.getElementById("employeeFormMessage");
const employeeFirstName = document.getElementById("employeeFirstName");
const employeeLastName = document.getElementById("employeeLastName");
const employeeEmail = document.getElementById("employeeEmail");
const employeePhone = document.getElementById("employeePhone");
const employeeCode = document.getElementById("employeeCode");
const employeeJob = document.getElementById("employeeJob");
const employeeSalary = document.getElementById("employeeSalary");
const employeeHireDate = document.getElementById("employeeHireDate");
const employeePassword = document.getElementById("employeePassword");
const employeeSearch = document.getElementById("employeeSearch");
const adminEmployeesGrid = document.getElementById("adminEmployeesGrid");
const employeeSectionStats = document.getElementById("employeeSectionStats");

const userSearch = document.getElementById("userSearch");
const adminUsersBody = document.getElementById("adminUsersBody");
const userSectionStats = document.getElementById("userSectionStats");
const usersPagination = document.getElementById("usersPagination");
const userDetailPanel = document.getElementById("userDetailPanel");
const userDetailTitle = document.getElementById("userDetailTitle");
const userFormMessage = document.getElementById("userFormMessage");
const hideUserDetailBtn = document.getElementById("hideUserDetailBtn");
const userEditForm = document.getElementById("userEditForm");
const userEditId = document.getElementById("userEditId");
const userEditFirstName = document.getElementById("userEditFirstName");
const userEditLastName = document.getElementById("userEditLastName");
const userEditShop = document.getElementById("userEditShop");
const userEditPhone = document.getElementById("userEditPhone");
const userEditLocation = document.getElementById("userEditLocation");
const userEditEmail = document.getElementById("userEditEmail");
const userEditActive = document.getElementById("userEditActive");
const adminAccountSummary = document.getElementById("adminAccountSummary");
const adminFaceLabel = document.getElementById("adminFaceLabel");
const adminFaceOpenBtn = document.getElementById("adminFaceOpenBtn");
const adminFaceMessage = document.getElementById("adminFaceMessage");
const adminFaceProfilesList = document.getElementById("adminFaceProfilesList");
const adminInlineFaceCapture = document.getElementById("adminInlineFaceCapture");
const adminFaceVideo = document.getElementById("adminFaceVideo");
const adminFaceCanvas = document.getElementById("adminFaceCanvas");
const adminCaptureFaceBtn = document.getElementById("adminCaptureFaceBtn");
const adminCloseFaceModalBtn = document.getElementById("adminCloseFaceModalBtn");

const expenseForm = document.getElementById("expenseForm");
const expenseFormPanel = document.getElementById("expenseFormPanel");
const showExpenseFormBtn = document.getElementById("showExpenseFormBtn");
const hideExpenseFormBtn = document.getElementById("hideExpenseFormBtn");
const expenseFormMessage = document.getElementById("expenseFormMessage");
const expenseId = document.getElementById("expenseId");
const expenseType = document.getElementById("expenseType");
const expenseLabel = document.getElementById("expenseLabel");
const expenseAmount = document.getElementById("expenseAmount");
const expenseDate = document.getElementById("expenseDate");
const expenseNote = document.getElementById("expenseNote");
const expenseResetBtn = document.getElementById("expenseResetBtn");
const expenseSearch = document.getElementById("expenseSearch");
const adminExpensesBody = document.getElementById("adminExpensesBody");
const expensesPagination = document.getElementById("expensesPagination");

const API = {
    summary: "/api/admin/summary",
    account: "/api/admin/account",
    accountFaces: "/api/admin/account/faces",
    products: "/api/admin/products",
    rawMaterials: "/api/admin/raw-materials",
    orders: "/api/admin/orders",
    employees: "/api/admin/employees",
    users: "/api/admin/users",
    expenses: "/api/admin/expenses",
    logout: "/api/auth/logout",
};

const ADMIN_ORDERS_API = "/api/admin/orders";

let state = {
    summary: null,
    adminAccount: null,
    adminFaceProfiles: [],
    products: [],
    rawMaterials: [],
    orders: [],
    pagination: {
        products: { page: 1, perPage: 12 },
        perfumeStock: { page: 1, perPage: 10 },
        rawMaterials: { page: 1, perPage: 8 },
        orders: { page: 1, perPage: 8 },
        users: { page: 1, perPage: 8 },
        expenses: { page: 1, perPage: 8 },
    },
    employees: [],
    users: [],
    expenses: [],
    orderViewMode: "ALL",
};

let adminFaceStream = null;
let pendingPartialPayment = null;

const rawMaterialDraft = {
    material_category: "BASE",
    item_name: "",
    unit_label: "piece",
    quantity_in_stock: 0,
    min_alert_quantity: 0,
    unit_cost_dzd: 0,
    purchase_date: new Date().toISOString().slice(0, 10),
    supplier_name: "",
    note: "",
};
let rawMaterialDraftVisible = false;

const resetRawMaterialDraft = () => {
    rawMaterialDraft.material_category = "BASE";
    rawMaterialDraft.item_name = "";
    rawMaterialDraft.unit_label = "piece";
    rawMaterialDraft.quantity_in_stock = 0;
    rawMaterialDraft.min_alert_quantity = 0;
    rawMaterialDraft.unit_cost_dzd = 0;
    rawMaterialDraft.purchase_date = new Date().toISOString().slice(0, 10);
    rawMaterialDraft.supplier_name = "";
    rawMaterialDraft.note = "";
};

const adminOrderCart = new Map();

const openOrderPdf = (orderId) => {
    if (!orderId) return;
    window.open(`/admin/orders/${orderId}/pdf`, "_blank");
};

const formatDT = (value) => `${Number(value || 0).toFixed(2)} DT`;

const setNote = (el, text, type = "") => {
    if (!el) return;
    el.textContent = text;
    el.classList.remove("error", "success");
    if (type) el.classList.add(type);
};

const fetchJson = async (url, options = {}) => {
    const response = await fetch(url, options);
    const raw = await response.text();
    let data = {};

    try {
        data = raw ? JSON.parse(raw) : {};
    } catch {
        data = {};
    }

    if (!response.ok) {
        if (data.error) {
            throw new Error(data.error);
        }
        throw new Error("Erreur serveur");
    }
    return data;
};

const explainCameraError = (error) => {
    const raw = typeof error?.message === "string" ? error.message : "";
    const name = typeof error?.name === "string" ? error.name : "";

    if (!window.isSecureContext) {
        return "La camera exige un contexte securise. Utilisez http://localhost ou https.";
    }

    if (name === "NotAllowedError" || name === "PermissionDeniedError") {
        return "L acces a la camera a ete refuse.";
    }

    if (name === "NotFoundError" || name === "DevicesNotFoundError") {
        return "Aucune camera disponible sur cet appareil.";
    }

    return raw || "Impossible d ouvrir la camera.";
};

const stopStream = (stream) => {
    if (!stream) return;
    stream.getTracks().forEach((track) => track.stop());
};

const closeAdminFaceModal = () => {
    stopStream(adminFaceStream);
    adminFaceStream = null;
    if (adminFaceVideo) {
        adminFaceVideo.srcObject = null;
    }
    if (adminInlineFaceCapture) {
        adminInlineFaceCapture.classList.add("admin-hidden");
    }
};

const openAdminFaceModal = async () => {
    closeAdminFaceModal();
    adminFaceStream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: "user",
            width: { ideal: 640 },
            height: { ideal: 480 },
        },
        audio: false,
    });

    if (adminFaceVideo) {
        adminFaceVideo.srcObject = adminFaceStream;
        await adminFaceVideo.play();
    }

    if (adminInlineFaceCapture) {
        adminInlineFaceCapture.classList.remove("admin-hidden");
        adminInlineFaceCapture.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
};

const buildFaceMatrixFromElements = async (videoEl, canvasEl) => {
    if (!(videoEl instanceof HTMLVideoElement) || !(canvasEl instanceof HTMLCanvasElement)) {
        throw new Error("Camera indisponible.");
    }

    const context = canvasEl.getContext("2d", { willReadFrequently: true });
    if (!context) {
        throw new Error("Canvas indisponible.");
    }

    const width = videoEl.videoWidth || 640;
    const height = videoEl.videoHeight || 480;
    canvasEl.width = width;
    canvasEl.height = height;
    context.drawImage(videoEl, 0, 0, width, height);

    let crop = {
        x: width * 0.25,
        y: height * 0.15,
        width: width * 0.5,
        height: height * 0.7,
    };

    if ("FaceDetector" in window) {
        try {
            const detector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
            const faces = await detector.detect(canvasEl);
            if (faces.length > 0) {
                const box = faces[0].boundingBox;
                const size = Math.max(box.width, box.height) * 1.3;
                crop = {
                    x: Math.max(0, box.x + (box.width - size) / 2),
                    y: Math.max(0, box.y + (box.height - size) / 2),
                    width: Math.min(size, width),
                    height: Math.min(size, height),
                };
            }
        } catch {
            // fallback
        }
    }

    const matrixCanvas = document.createElement("canvas");
    matrixCanvas.width = 32;
    matrixCanvas.height = 32;
    const matrixContext = matrixCanvas.getContext("2d", { willReadFrequently: true });
    if (!matrixContext) {
        throw new Error("Canvas de matrice indisponible.");
    }

    matrixContext.drawImage(
        canvasEl,
        crop.x,
        crop.y,
        crop.width,
        crop.height,
        0,
        0,
        32,
        32
    );

    const { data } = matrixContext.getImageData(0, 0, 32, 32);
    const matrix = [];
    for (let index = 0; index < data.length; index += 4) {
        const red = data[index];
        const green = data[index + 1];
        const blue = data[index + 2];
        const grayscale = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
        matrix.push(Number(grayscale.toFixed(6)));
    }

    return matrix;
};

const activateAdminView = (viewName) => {
    adminViews.forEach((view) => view.classList.toggle("active", view.id === `admin-view-${viewName}`));
    adminLinks.forEach((link) => link.classList.toggle("active", link.dataset.adminView === viewName));
    window.location.hash = `admin-${viewName}`;
    window.scrollTo({ top: 0, behavior: "smooth" });
};

const closeAdminMobileSidebar = () => {
    if (!adminSidebar || !mobileAdminSidebarBackdrop) return;
    adminSidebar.classList.remove("mobile-open");
    mobileAdminSidebarBackdrop.classList.remove("active");
    document.body.classList.remove("sidebar-open");
};

const openAdminMobileSidebar = () => {
    if (!adminSidebar || !mobileAdminSidebarBackdrop) return;
    adminSidebar.classList.add("mobile-open");
    mobileAdminSidebarBackdrop.classList.add("active");
    document.body.classList.add("sidebar-open");
};

const handleAdminLinkNavigation = (link, event) => {
    if (!(link instanceof HTMLElement)) return;
    const viewName = String(link.dataset.adminView || "").trim();
    if (!viewName) return;
    event?.preventDefault();
    event?.stopPropagation();
    activateAdminView(viewName);
    if (window.innerWidth <= 940) {
        window.setTimeout(() => closeAdminMobileSidebar(), 30);
    }
};

adminLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
        handleAdminLinkNavigation(link, event);
    });

    link.addEventListener("pointerup", (event) => {
        handleAdminLinkNavigation(link, event);
    }, { passive: false });

    link.addEventListener("touchend", (event) => {
        handleAdminLinkNavigation(link, event);
    }, { passive: false });
});

[mobileAdminSidebarToggle, mobileAdminSidebarToggleInline].forEach((button) => {
    button?.addEventListener("click", () => {
        if (adminSidebar?.classList.contains("mobile-open")) closeAdminMobileSidebar();
        else openAdminMobileSidebar();
    });
});

mobileAdminSidebarBackdrop?.addEventListener("click", closeAdminMobileSidebar);
mobileAdminSidebarClose?.addEventListener("click", closeAdminMobileSidebar);

window.addEventListener("resize", () => {
    if (window.innerWidth > 940) closeAdminMobileSidebar();
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeAdminMobileSidebar();
    if (event.key === "Escape") closePaymentModal();
});

if (adminLogoutBtn) {
    adminLogoutBtn.addEventListener("click", async () => {
        await fetch(API.logout, { method: "POST" });
        window.location.href = "/auth";
    });
}

const metricLabel = {
    today_revenue: "Recette du jour",
    month_revenue: "CA du mois",
    month_expenses: "Charges du mois",
    month_raw_material_expenses: "Achats matieres",
    month_payroll: "Salaires",
    month_profit: "Benefice estime",
    stock_value: "Valeur stock",
    raw_materials_stock_value: "Stock matieres",
    products_count: "Produits",
    out_of_stock_count: "Hors stock",
    employees_count: "Employes",
    pending_orders: "Commandes ouvertes",
    raw_materials_alert_count: "Alertes matieres",
};

const renderSummary = () => {
    if (!state.summary || !adminMetricsGrid) return;
    const cards = state.summary.cards || {};
    const order = [
        "today_revenue",
        "month_revenue",
        "month_expenses",
        "month_raw_material_expenses",
        "month_profit",
        "stock_value",
        "raw_materials_stock_value",
        "employees_count",
        "pending_orders",
        "raw_materials_alert_count",
        "out_of_stock_count",
        "products_count",
    ];

    adminMetricsGrid.innerHTML = order.map((key) => {
        const value = cards[key] ?? 0;
        const number = typeof value === "number" ? formatDT(value) : String(value);
        const isMoney = ["today_revenue", "month_revenue", "month_expenses", "month_raw_material_expenses", "month_profit", "stock_value", "raw_materials_stock_value", "month_payroll"].includes(key);
        const klass = key === "month_profit" ? (Number(value) >= 0 ? "profit-positive" : "profit-negative") : "";
        return `
            <article class="metric-tile ${klass}">
                <span>${metricLabel[key]}</span>
                <strong>${isMoney ? number : value}</strong>
            </article>
        `;
    }).join("");

    adminRecentOrdersBody.innerHTML = (state.summary.recent_orders || []).map((row) => `
        <tr>
            <td>${row.order_number}</td>
            <td>${row.perfume_shop_name || "-"}</td>
            <td>${formatDT(row.total_dzd)}</td>
            <td><span class="status-pill ${row.status === "LIVREE" ? "ok" : "warn"}">${row.status}</span></td>
            <td><span class="status-pill ${row.invoice_status === "PAYE" ? "ok" : row.invoice_status === "PARTIEL" ? "warn" : "bad"}">${row.invoice_status || "NON_PAYE"}</span></td>
        </tr>
    `).join("");

    adminRecentExpensesBody.innerHTML = (state.summary.recent_expenses || []).map((row) => `
        <tr>
            <td>${row.expense_date}</td>
            <td>${row.expense_type}</td>
            <td>${row.label}</td>
            <td>${formatDT(row.amount_dzd)}</td>
        </tr>
    `).join("");

    if (adminActivityChart) {
        const items = state.summary.activity || [];
        const maxOrders = Math.max(...items.map((row) => Number(row.orders_count || 0)), 1);
        adminActivityChart.innerHTML = items.map((row) => {
            const height = Math.max(16, Math.round((Number(row.orders_count || 0) / maxOrders) * 120));
            return `
                <article class="chart-bar-card">
                    <div class="chart-bar-wrap">
                        <div class="chart-bar" style="height:${height}px"></div>
                    </div>
                    <strong>${row.orders_count}</strong>
                    <span>${row.activity_day}</span>
                </article>
            `;
        }).join("");
    }
};

const productRow = (row) => `
    <tr>
        <td>${row.name}</td>
        <td>${row.catalog_group} / ${row.segment}</td>
        <td>${formatDT(row.price_dzd)}</td>
        <td>${Number(row.stock_bottles || 0)} bouteille${Number(row.stock_bottles || 0) > 1 ? "s" : ""}</td>
        <td>${Number(row.raw_material_stock_ml || 0).toFixed(2)} ml</td>
        <td>
            <div class="table-actions">
                <button class="mini-btn" data-product-edit="${row.id}">Modifier</button>
                <button class="mini-btn bad" data-product-delete="${row.id}">Supprimer</button>
            </div>
        </td>
    </tr>
`;

const renderProducts = () => {
    const q = (productSearch?.value || "").trim().toLowerCase();
    const filtered = state.products
        .filter((row) => `${row.name} ${row.catalog_group} ${row.segment}`.toLowerCase().includes(q));
    const pagination = state.pagination.products;
    const totalPages = Math.max(1, Math.ceil(filtered.length / pagination.perPage));
    if (pagination.page > totalPages) pagination.page = totalPages;
    const start = (pagination.page - 1) * pagination.perPage;
    const paged = filtered.slice(start, start + pagination.perPage);

    if (productSectionStats) {
        const active = state.products.filter((row) => Number(row.is_active) === 1).length;
        const out = state.products.filter((row) => Number(row.stock_bottles) <= 0).length;
        const lowRawMaterial = state.products.filter((row) => Number(row.raw_material_stock_ml || 0) <= Number(row.raw_material_alert_ml || 0)).length;
        const totalValue = state.products.reduce((sum, row) => sum + Number(row.price_dzd || 0) * Number(row.stock_bottles || 0), 0);
        productSectionStats.innerHTML = `
            <article class="inline-stat">
                <span>Produits actifs</span>
                <strong>${active}</strong>
            </article>
            <article class="inline-stat">
                <span>Hors stock</span>
                <strong>${out}</strong>
            </article>
            <article class="inline-stat">
                <span>Valeur stock bouteilles</span>
                <strong>${formatDT(totalValue)}</strong>
            </article>
            <article class="inline-stat">
                <span>Bases a surveiller</span>
                <strong>${lowRawMaterial}</strong>
            </article>
        `;
    }

    adminProductsBody.innerHTML = paged
        .map(productRow)
        .join("");

    if (productsPagination) {
        productsPagination.innerHTML = renderPaginationControls(filtered.length, pagination.page, totalPages, "products");
    }
};

const getFilteredPerfumeStock = () => {
    const q = (perfumeStockSearch?.value || "").trim().toLowerCase();
    const category = perfumeStockCategoryFilter?.value || "ALL";
    const sort = perfumeStockSort?.value || "name_asc";

    const filtered = state.products
        .filter((row) => `${row.name} ${row.catalog_group} ${row.segment} ${row.code || ""}`.toLowerCase().includes(q))
        .filter((row) => category === "ALL" || row.catalog_group === category);

    filtered.sort((a, b) => {
        if (sort === "name_desc") return String(b.name || "").localeCompare(String(a.name || ""), "fr", { sensitivity: "base" });
        if (sort === "category_asc") {
            const byCategory = `${a.catalog_group} ${a.segment}`.localeCompare(`${b.catalog_group} ${b.segment}`, "fr", { sensitivity: "base" });
            return byCategory !== 0 ? byCategory : String(a.name || "").localeCompare(String(b.name || ""), "fr", { sensitivity: "base" });
        }
        if (sort === "base_desc") return Number(b.raw_material_stock_ml || 0) - Number(a.raw_material_stock_ml || 0);
        if (sort === "base_asc") return Number(a.raw_material_stock_ml || 0) - Number(b.raw_material_stock_ml || 0);
        return String(a.name || "").localeCompare(String(b.name || ""), "fr", { sensitivity: "base" });
    });

    return filtered;
};

const renderPerfumeStock = () => {
    if (!perfumeStockBody) return;

    const filtered = getFilteredPerfumeStock();
    const pagination = state.pagination.perfumeStock;
    const totalPages = Math.max(1, Math.ceil(filtered.length / pagination.perPage));
    if (pagination.page > totalPages) pagination.page = totalPages;
    const start = (pagination.page - 1) * pagination.perPage;
    const paged = filtered.slice(start, start + pagination.perPage);

    if (perfumeStockStats) {
        const totalBaseStock = filtered.reduce((sum, row) => sum + Number(row.raw_material_stock_ml || 0), 0);
        const lowBase = filtered.filter((row) => Number(row.raw_material_stock_ml || 0) <= Number(row.raw_material_alert_ml || 0)).length;
        perfumeStockStats.innerHTML = `
            <article class="inline-stat">
                <span>Parfums visibles</span>
                <strong>${filtered.length}</strong>
            </article>
            <article class="inline-stat">
                <span>Stock base visible</span>
                <strong>${formatBottleCount(totalBaseStock)}</strong>
            </article>
            <article class="inline-stat">
                <span>Bases a surveiller</span>
                <strong>${lowBase}</strong>
            </article>
            <article class="inline-stat">
                <span>Saisie directeur</span>
                <strong>Par bouteille</strong>
            </article>
        `;
    }

    perfumeStockBody.innerHTML = paged.map((row) => {
        return `
            <tr data-perfume-stock-row="${row.id}">
                <td>${row.name}</td>
                <td>${row.catalog_group} / ${row.segment}</td>
                <td>${row.code || "-"}</td>
                <td><input class="table-input perfume-stock-input" data-field="raw_material_stock_ml" type="number" min="0" step="0.01" value="${Number(row.raw_material_stock_ml || 0)}"></td>
            </tr>
        `;
    }).join("");

    if (perfumeStockPagination) {
        perfumeStockPagination.innerHTML = renderPaginationControls(filtered.length, pagination.page, totalPages, "perfumeStock");
    }
};

const rawMaterialCategoryOptions = (selected) => [
    "BASE",
    "ALCOOL",
    "COLORANT",
    "BOUTEILLE",
    "TICKET",
    "BOUCHON",
    "AUTRE",
].map((value) => `<option value="${value}" ${value === selected ? "selected" : ""}>${value}</option>`).join("");

const rawMaterialRow = (row, isDraft = false) => {
    const total = Number(row.quantity_in_stock || 0) * Number(row.unit_cost_dzd || 0);
    const actionButtons = isDraft
        ? `<button class="mini-btn" type="button" data-raw-material-create="1">Creer</button>`
        : `<button class="mini-btn" type="button" data-raw-material-save="${row.id}">Enregistrer</button>
           <button class="mini-btn bad" type="button" data-raw-material-delete="${row.id}">Supprimer</button>`;

    return `
        <tr data-raw-material-row="${isDraft ? "new" : row.id}">
            <td><select class="table-input" data-field="material_category">${rawMaterialCategoryOptions(row.material_category || "BASE")}</select></td>
            <td><input class="table-input" data-field="item_name" type="text" value="${row.item_name || ""}"></td>
            <td><input class="table-input" data-field="unit_label" type="text" value="${row.unit_label || "piece"}"></td>
            <td><input class="table-input" data-field="quantity_in_stock" type="number" min="0" step="0.01" value="${Number(row.quantity_in_stock || 0)}"></td>
            <td><input class="table-input" data-field="min_alert_quantity" type="number" min="0" step="0.01" value="${Number(row.min_alert_quantity || 0)}"></td>
            <td><input class="table-input" data-field="unit_cost_dzd" type="number" min="0" step="0.01" value="${Number(row.unit_cost_dzd || 0)}"></td>
            <td><strong class="raw-material-total">${formatDT(total)}</strong></td>
            <td><input class="table-input" data-field="purchase_date" type="date" value="${row.purchase_date || ""}"></td>
            <td><input class="table-input" data-field="supplier_name" type="text" value="${row.supplier_name || ""}"></td>
            <td><input class="table-input" data-field="note" type="text" value="${row.note || ""}"></td>
            <td><div class="table-actions">${actionButtons}</div></td>
        </tr>
    `;
};

const collectRawMaterialPayload = (tr) => ({
    material_category: tr.querySelector('[data-field="material_category"]')?.value || "BASE",
    item_name: tr.querySelector('[data-field="item_name"]')?.value.trim() || "",
    unit_label: tr.querySelector('[data-field="unit_label"]')?.value.trim() || "",
    quantity_in_stock: Number(tr.querySelector('[data-field="quantity_in_stock"]')?.value || 0),
    min_alert_quantity: Number(tr.querySelector('[data-field="min_alert_quantity"]')?.value || 0),
    unit_cost_dzd: Number(tr.querySelector('[data-field="unit_cost_dzd"]')?.value || 0),
    purchase_date: tr.querySelector('[data-field="purchase_date"]')?.value || "",
    supplier_name: tr.querySelector('[data-field="supplier_name"]')?.value.trim() || "",
    note: tr.querySelector('[data-field="note"]')?.value.trim() || "",
});

const refreshRawMaterialRowTotal = (tr) => {
    if (!tr) return;
    const qty = Number(tr.querySelector('[data-field="quantity_in_stock"]')?.value || 0);
    const unitCost = Number(tr.querySelector('[data-field="unit_cost_dzd"]')?.value || 0);
    const totalEl = tr.querySelector(".raw-material-total");
    if (totalEl) totalEl.textContent = formatDT(qty * unitCost);
};

const renderRawMaterials = () => {
    if (!adminRawMaterialsBody) return;
    const q = (rawMaterialSearch?.value || "").trim().toLowerCase();
    const filtered = state.rawMaterials.filter((row) =>
        `${row.item_name} ${row.material_category} ${row.supplier_name || ""} ${row.note || ""}`.toLowerCase().includes(q)
    );
    const pagination = state.pagination.rawMaterials;
    const totalPages = Math.max(1, Math.ceil(filtered.length / pagination.perPage));
    if (pagination.page > totalPages) pagination.page = totalPages;
    const start = (pagination.page - 1) * pagination.perPage;
    const paged = filtered.slice(start, start + pagination.perPage);

    const currentMonth = new Date().toISOString().slice(0, 7);
    const monthCost = state.rawMaterials
        .filter((row) => String(row.purchase_date || "").slice(0, 7) === currentMonth)
        .reduce((sum, row) => sum + Number(row.total_cost_dzd || 0), 0);
    const totalValue = state.rawMaterials.reduce((sum, row) => sum + Number(row.total_cost_dzd || 0), 0);
    const alerts = state.rawMaterials.filter((row) => Number(row.quantity_in_stock || 0) <= Number(row.min_alert_quantity || 0)).length;

    if (rawMaterialSectionStats) {
        rawMaterialSectionStats.innerHTML = `
            <article class="inline-stat">
                <span>Lignes stock</span>
                <strong>${state.rawMaterials.length}</strong>
            </article>
            <article class="inline-stat">
                <span>Achats du mois</span>
                <strong>${formatDT(monthCost)}</strong>
            </article>
            <article class="inline-stat">
                <span>Valeur stock matieres</span>
                <strong>${formatDT(totalValue)}</strong>
            </article>
            <article class="inline-stat">
                <span>Alertes stock</span>
                <strong>${alerts}</strong>
            </article>
        `;
    }

    adminRawMaterialsBody.innerHTML = [
        ...(rawMaterialDraftVisible ? [rawMaterialRow(rawMaterialDraft, true)] : []),
        ...paged.map((row) => rawMaterialRow(row)),
    ].join("");

    if (rawMaterialsPagination) {
        rawMaterialsPagination.innerHTML = renderPaginationControls(filtered.length, pagination.page, totalPages, "rawMaterials");
    }
};

const resetProductForm = () => {
    productForm?.reset();
    productId.value = "";
    productActive.value = "1";
    setNote(productFormMessage, "");
};

const showProductForm = () => {
    productFormPanel?.classList.remove("admin-hidden");
    productForm?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const hideProductForm = () => {
    productFormPanel?.classList.add("admin-hidden");
};

const fillProductForm = (row) => {
    productId.value = row.id;
    productCatalogGroup.value = row.catalog_group;
    productSegment.value = row.segment;
    productCode.value = row.code || "";
    productName.value = row.name;
    productPrice.value = row.price_dzd;
    productStock.value = row.stock_bottles;
    productAlert.value = row.min_alert_bottles;
    productRawMaterialStock.value = row.raw_material_stock_ml || 0;
    productRawMaterialAlert.value = row.raw_material_alert_ml || 0;
    productSku.value = row.sku || "";
    productBarcode.value = row.barcode || "";
    productActive.value = String(row.is_active);
    activateAdminView("products");
    showProductForm();
};

const formatShopUserLabel = (row) => {
    const shop = String(row.perfume_shop_name || "").trim() || "Parfumerie sans nom";
    const person = [row.first_name, row.last_name].filter(Boolean).join(" ").trim();
    return person ? `${shop} - ${person}` : shop;
};

const showOrderCreatePanel = () => {
    orderCreatePanel?.classList.remove("admin-hidden");
    orderCreatePanel?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const hideOrderCreatePanel = () => {
    orderCreatePanel?.classList.add("admin-hidden");
};

const resetAdminOrderBuilder = () => {
    adminOrderCart.clear();
    if (orderCreateQty) orderCreateQty.value = "1";
    if (orderCreateProductSearch) orderCreateProductSearch.value = "";
    if (orderCreateShop) orderCreateShop.value = "";
    setNote(orderCreateMessage, "");
    renderOrderCreateProductOptions();
    renderAdminOrderCart();
};

const renderOrderCreateUserOptions = () => {
    if (!orderCreateShopList) return;
    const shops = [...new Set(
        state.users
            .filter((row) => Number(row.is_active) === 1)
            .map((row) => String(row.perfume_shop_name || "").trim())
            .filter(Boolean)
    )].sort((a, b) => a.localeCompare(b, "fr", { sensitivity: "base" }));

    orderCreateShopList.innerHTML = shops.map((shop) => `<option value="${shop}"></option>`).join("");
};

const renderOrderCreateProductOptions = () => {
    if (!orderCreateProduct) return;
    const q = (orderCreateProductSearch?.value || "").trim().toLowerCase();
    const products = state.products.filter((row) => {
        const haystack = `${row.name} ${row.catalog_group} ${row.segment}`.toLowerCase();
        return q === "" || haystack.includes(q);
    });

    orderCreateProduct.innerHTML = [
        '<option value="">Choisir un produit</option>',
        ...products.map((row) => `<option value="${row.id}">${row.name} - ${row.catalog_group}/${row.segment} - ${formatDT(row.price_dzd)}</option>`),
    ].join("");
};

const getAdminOrderTotal = () => {
    let total = 0;
    adminOrderCart.forEach((item) => {
        total += Number(item.price || 0) * Number(item.qty || 0);
    });
    return total;
};

const renderAdminOrderCart = () => {
    if (!orderCreateItemsBody || !orderCreateTotal) return;

    if (adminOrderCart.size === 0) {
        orderCreateItemsBody.innerHTML = '<tr><td colspan="6">Aucun produit ajoute pour le moment.</td></tr>';
    } else {
        orderCreateItemsBody.innerHTML = Array.from(adminOrderCart.values()).map((item) => `
            <tr>
                <td>${item.name}</td>
                <td>${item.catalog_group} / ${item.segment}</td>
                <td>${formatDT(item.price)}</td>
                <td>${item.qty}</td>
                <td>${formatDT(item.price * item.qty)}</td>
                <td><button class="mini-btn bad" type="button" data-order-create-remove="${item.id}">Retirer</button></td>
            </tr>
        `).join("");
    }

    orderCreateTotal.textContent = formatDT(getAdminOrderTotal());
};

const getSelectedAdminUser = () => {
    const shopName = String(orderCreateShop?.value || "").trim().toLowerCase();
    if (shopName === "") return null;
    return state.users.find((row) => Number(row.is_active) === 1 && String(row.perfume_shop_name || "").trim().toLowerCase() === shopName) || null;
};

const addAdminOrderItem = () => {
    const productId = Number(orderCreateProduct?.value || 0);
    const qty = Number(orderCreateQty?.value || 0);
    const product = state.products.find((row) => Number(row.id) === productId);

    if (!product) {
        setNote(orderCreateMessage, "Choisissez un produit.", "error");
        return;
    }
    if (qty <= 0) {
        setNote(orderCreateMessage, "Quantite invalide.", "error");
        return;
    }

    const existing = adminOrderCart.get(String(product.id));
    if (existing) {
        existing.qty += qty;
    } else {
        adminOrderCart.set(String(product.id), {
            id: product.id,
            product_id: product.id,
            name: product.name,
            catalog_group: product.catalog_group,
            segment: product.segment,
            price: Number(product.price_dzd || 0),
            qty,
        });
    }

    setNote(orderCreateMessage, "Produit ajoute au panier admin.", "success");
    if (orderCreateQty) orderCreateQty.value = "1";
    renderAdminOrderCart();
};

const normalizeOrderInvoiceStatus = (value) => {
    const status = String(value || "").trim().toUpperCase();
    return status !== "" ? status : "NON_PAYE";
};

const setPaymentModalMessage = (message = "", type = "") => {
    if (!paymentModalMessage) return;
    paymentModalMessage.textContent = message;
    paymentModalMessage.classList.remove("error", "success");
    if (type) paymentModalMessage.classList.add(type);
};

const closePaymentModal = () => {
    pendingPartialPayment = null;
    paymentModal?.classList.add("admin-hidden");
    paymentModal?.setAttribute("aria-hidden", "true");
    document.body.classList.remove("sidebar-open");
    if (paymentAmountInput) {
        paymentAmountInput.value = "";
    }
    setPaymentModalMessage("");
};

const openPaymentModal = (row) => {
    const total = Number(row.invoice_total || row.total_dzd || 0);
    const paid = Number(row.paid_amount || 0);
    const remaining = Number(row.remaining_amount ?? Math.max(0, total - paid));

    pendingPartialPayment = {
        invoiceId: row.invoice_id,
        remaining,
    };

    if (paymentAlreadyPaid) {
        paymentAlreadyPaid.textContent = formatDT(paid);
    }
    if (paymentRemaining) {
        paymentRemaining.textContent = formatDT(remaining);
    }
    if (paymentAmountInput) {
        paymentAmountInput.value = remaining > 0 ? remaining.toFixed(2) : "";
    }
    setPaymentModalMessage("");
    paymentModal?.classList.remove("admin-hidden");
    paymentModal?.setAttribute("aria-hidden", "false");
    setTimeout(() => paymentAmountInput?.focus(), 40);
};

const getFilteredOrders = () => {
    const q = (orderSearch?.value || "").trim().toLowerCase();
    const shop = orderShopFilter?.value || "ALL";
    const deliveryStatus = orderStatusFilter?.value || "ALL";
    const invoiceStatus = orderInvoiceFilter?.value || "ALL";
    const dateFrom = orderDateFrom?.value || "";
    const dateTo = orderDateTo?.value || "";

    return state.orders.filter((row) => {
        const rowInvoiceStatus = normalizeOrderInvoiceStatus(row.invoice_status);
        const rowDate = String(row.created_at || "").slice(0, 10);
        const searchBlob = [
            row.order_number,
            row.perfume_shop_name,
            row.invoice_number,
            row.first_name,
            row.last_name,
            row.order_status,
            rowInvoiceStatus,
            String(row.created_at || "").slice(0, 10),
        ].join(" ").toLowerCase();

        const matchSearch = q === "" || searchBlob.includes(q);
        const matchShop = shop === "ALL" || (row.perfume_shop_name || "") === shop;
        const matchDelivery = deliveryStatus === "ALL" || row.order_status === deliveryStatus;
        const matchInvoice = invoiceStatus === "ALL" || rowInvoiceStatus === invoiceStatus;
        const matchMode = state.orderViewMode !== "PARTIAL_ONLY" || rowInvoiceStatus === "PARTIEL";
        const matchDateFrom = dateFrom === "" || rowDate >= dateFrom;
        const matchDateTo = dateTo === "" || rowDate <= dateTo;

        return matchSearch && matchShop && matchDelivery && matchInvoice && matchMode && matchDateFrom && matchDateTo;
    });
};

const renderOrderShopFilter = () => {
    if (!orderShopFilter) return;

    const currentValue = orderShopFilter.value || "ALL";
    const shops = [...new Set(
        state.orders
            .map((row) => String(row.perfume_shop_name || "").trim())
            .filter(Boolean)
    )].sort((a, b) => a.localeCompare(b, "fr", { sensitivity: "base" }));

    orderShopFilter.innerHTML = [
        '<option value="ALL">Toutes parfumeries</option>',
        ...shops.map((shop) => `<option value="${shop}">${shop}</option>`),
    ].join("");

    orderShopFilter.value = shops.includes(currentValue) ? currentValue : "ALL";
};

const renderOrders = () => {
    const filtered = getFilteredOrders();
    const partialOrders = state.orders.filter((row) => normalizeOrderInvoiceStatus(row.invoice_status) === "PARTIEL");
    const partialRemainingTotal = partialOrders.reduce((sum, row) => sum + Number(row.remaining_amount || 0), 0);
    const pagination = state.pagination.orders;
    const totalPages = Math.max(1, Math.ceil(filtered.length / pagination.perPage));
    if (pagination.page > totalPages) pagination.page = totalPages;
    const start = (pagination.page - 1) * pagination.perPage;
    const paged = filtered.slice(start, start + pagination.perPage);

    if (orderSectionStats) {
        const summary = state.ordersSummary || {};
        const uniqueShops = new Set(filtered.map((row) => String(row.perfume_shop_name || "").trim()).filter(Boolean)).size;
        orderSectionStats.innerHTML = `
            <article class="inline-stat">
                <span>Total commandes payees</span>
                <strong>${formatDT(summary.paid_orders_total || 0)}</strong>
            </article>
            <article class="inline-stat">
                <span>Recette du jour</span>
                <strong>${formatDT(summary.today_revenue || 0)}</strong>
            </article>
            <article class="inline-stat">
                <span>Resultats filtres</span>
                <strong>${filtered.length} / ${summary.orders_count || 0}</strong>
            </article>
            <article class="inline-stat">
                <span>Commandes partielles</span>
                <strong>${partialOrders.length}</strong>
            </article>
            <article class="inline-stat">
                <span>Total reste a payer</span>
                <strong>${formatDT(partialRemainingTotal)}</strong>
            </article>
            <article class="inline-stat">
                <span>Parfumeries visibles</span>
                <strong>${uniqueShops}</strong>
            </article>
            <article class="inline-stat">
                <span>Vue active</span>
                <strong>${state.orderViewMode === "PARTIAL_ONLY" ? "Paiements partiels" : "Toutes les commandes"}</strong>
            </article>
        `;
    }

    adminOrdersBody.innerHTML = paged
        .map((row) => `
            <tr>
                <td>${row.order_number}<br><small>${row.created_at.slice(0, 10)}</small></td>
                <td>${row.perfume_shop_name || "-"}<br><small>${row.first_name} ${row.last_name}</small></td>
                <td>
                    <strong>${formatDT(row.invoice_total || row.total_dzd || 0)}</strong>
                    <br><small>Paye: ${formatDT(row.paid_amount || 0)}</small>
                    <br><small>Reste: ${formatDT(row.remaining_amount || 0)}</small>
                </td>
                <td>
                    <select class="inline-select" data-order-status="${row.id}">
                        ${["CONFIRMEE","EN_PREPARATION","EXPEDIEE","LIVREE","ANNULEE"].map((status) => `<option value="${status}" ${status === row.order_status ? "selected" : ""}>${status}</option>`).join("")}
                    </select>
                </td>
                <td>
                    <select class="inline-select" data-invoice-status="${row.invoice_id}">
                        ${["NON_PAYE","PARTIEL","PAYE"].map((status) => `<option value="${status}" ${status === normalizeOrderInvoiceStatus(row.invoice_status) ? "selected" : ""}>${status}</option>`).join("")}
                    </select>
                </td>
                <td>
                    <div class="table-actions">
                        <button class="mini-btn" data-order-view="${row.id}">Consulter</button>
                        <button class="mini-btn" data-order-edit-open="${row.id}">Modifier</button>
                        <button class="mini-btn" data-order-pdf="${row.id}">PDF</button>
                        <button class="mini-btn bad" data-order-delete="${row.id}">Supprimer</button>
                        <button class="mini-btn warn" data-order-apply="${row.id}">Valider livraison</button>
                        ${row.invoice_id ? `<button class="mini-btn" data-invoice-apply="${row.invoice_id}">Valider paiement</button>` : ""}
                    </div>
                </td>
            </tr>
        `)
        .join("");

    if (ordersPagination) {
        ordersPagination.innerHTML = renderPaginationControls(filtered.length, pagination.page, totalPages, "orders");
    }
};

const setOrderViewMode = (mode) => {
    state.orderViewMode = mode === "PARTIAL_ONLY" ? "PARTIAL_ONLY" : "ALL";
    ordersAllBtn?.classList.toggle("is-active", state.orderViewMode === "ALL");
    ordersPartialBtn?.classList.toggle("is-active", state.orderViewMode === "PARTIAL_ONLY");
    if (orderInvoiceFilter) {
        orderInvoiceFilter.value = state.orderViewMode === "PARTIAL_ONLY" ? "PARTIEL" : "ALL";
    }
    state.pagination.orders.page = 1;
    renderOrders();
};

const renderPaginationControls = (totalItems, currentPage, totalPages, key) => {
    const startItem = totalItems === 0 ? 0 : ((currentPage - 1) * state.pagination[key].perPage) + 1;
    const endItem = Math.min(currentPage * state.pagination[key].perPage, totalItems);
    return `
        <div class="pagination-summary">
            <span>${startItem}-${endItem} sur ${totalItems}</span>
        </div>
        <div class="pagination-actions">
            <button class="mini-btn" type="button" data-page-key="${key}" data-page-action="prev" ${currentPage <= 1 ? "disabled" : ""}>Precedent</button>
            <span class="pagination-index">Page ${currentPage} / ${totalPages}</span>
            <button class="mini-btn" type="button" data-page-key="${key}" data-page-action="next" ${currentPage >= totalPages ? "disabled" : ""}>Suivant</button>
        </div>
    `;
};

const renderEmployees = () => {
    const q = (employeeSearch?.value || "").trim().toLowerCase();
    const filtered = state.employees
        .filter((row) => `${row.first_name} ${row.last_name} ${row.job_title} ${row.employee_code}`.toLowerCase().includes(q));

    if (employeeSectionStats) {
        const active = state.employees.filter((row) => row.employment_status === "ACTIF").length;
        const payroll = state.employees.reduce((sum, row) => sum + Number(row.salary_dzd || 0), 0);
        employeeSectionStats.innerHTML = `
            <article class="inline-stat">
                <span>Employes actifs</span>
                <strong>${active}</strong>
            </article>
            <article class="inline-stat">
                <span>Masse salariale</span>
                <strong>${formatDT(payroll)}</strong>
            </article>
            <article class="inline-stat">
                <span>Resultats</span>
                <strong>${filtered.length}</strong>
            </article>
        `;
    }

    adminEmployeesGrid.innerHTML = filtered
        .map((row) => `
            <article class="employee-card">
                <div class="employee-card-head">
                    <span class="role-pill">${row.employee_code}</span>
                    <span class="status-pill ${row.employment_status === "ACTIF" ? "ok" : row.employment_status === "SUSPENDU" ? "warn" : "bad"}">${row.employment_status}</span>
                </div>
                <div class="employee-identity">
                    <p class="employee-label">Employe</p>
                    <h4>${row.last_name} ${row.first_name}</h4>
                    <p class="employee-job">${row.job_title}</p>
                </div>
                <div class="employee-meta">
                    <div>
                        <span class="employee-label">Salaire mensuel</span>
                        <strong class="employee-salary">${formatDT(row.salary_dzd)}</strong>
                    </div>
                    <div>
                        <span class="employee-label">Contact</span>
                        <p class="employee-contact">${row.email}<br>${row.phone}</p>
                    </div>
                </div>
                <div class="table-actions">
                    <button class="mini-btn" data-employee-edit="${row.id}">Modifier</button>
                    <button class="mini-btn bad" data-employee-delete="${row.id}">Supprimer</button>
                </div>
            </article>
        `)
        .join("");
};

const renderUsers = () => {
    const q = (userSearch?.value || "").trim().toLowerCase();
    const filtered = state.users
        .filter((row) => `${row.first_name} ${row.last_name} ${row.email} ${row.perfume_shop_name} ${row.role_name}`.toLowerCase().includes(q));
    const pagination = state.pagination.users;
    const totalPages = Math.max(1, Math.ceil(filtered.length / pagination.perPage));
    if (pagination.page > totalPages) pagination.page = totalPages;
    const start = (pagination.page - 1) * pagination.perPage;
    const paged = filtered.slice(start, start + pagination.perPage);

    if (userSectionStats) {
        const active = state.users.filter((row) => Number(row.is_active) === 1).length;
        const clients = state.users.filter((row) => row.role_name === "CLIENT").length;
        userSectionStats.innerHTML = `
            <article class="inline-stat">
                <span>Users actifs</span>
                <strong>${active}</strong>
            </article>
            <article class="inline-stat">
                <span>Clients</span>
                <strong>${clients}</strong>
            </article>
            <article class="inline-stat">
                <span>Resultats</span>
                <strong>${filtered.length}</strong>
            </article>
        `;
    }

    adminUsersBody.innerHTML = paged.map((row) => `
        <tr>
            <td>${row.last_name} ${row.first_name}</td>
            <td><span class="role-pill">${row.role_name}</span></td>
            <td>${row.perfume_shop_name || "-"}</td>
            <td>${row.email}<br><small>${row.phone}</small></td>
            <td>${row.location || "-"}</td>
            <td><span class="status-pill ${Number(row.is_active) === 1 ? "ok" : "bad"}">${Number(row.is_active) === 1 ? "ACTIF" : "INACTIF"}</span></td>
            <td>
                <div class="table-actions">
                    <button class="mini-btn" data-user-view="${row.id}">Consulter</button>
                    <button class="mini-btn" data-user-edit="${row.id}">Modifier</button>
                    <button class="mini-btn bad" data-user-delete="${row.id}">Supprimer</button>
                </div>
            </td>
        </tr>
    `).join("");

    if (usersPagination) {
        usersPagination.innerHTML = renderPaginationControls(filtered.length, pagination.page, totalPages, "users");
    }
};

const fillUserForm = (row) => {
    userDetailTitle.textContent = `Profil ${row.first_name} ${row.last_name}`;
    userEditId.value = row.id;
    userEditFirstName.value = row.first_name || "";
    userEditLastName.value = row.last_name || "";
    userEditShop.value = row.perfume_shop_name || "";
    userEditPhone.value = row.phone || "";
    userEditLocation.value = row.location || "";
    userEditEmail.value = row.email || "";
    userEditActive.value = String(Number(row.is_active || 0));
    setNote(userFormMessage, "");
    userDetailPanel?.classList.remove("admin-hidden");
    userDetailPanel?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const renderAdminAccount = () => {
    const user = state.adminAccount;

    if (adminAccountSummary) {
        if (!user) {
            adminAccountSummary.innerHTML = "";
        } else {
            adminAccountSummary.innerHTML = `
                <article class="inline-stat">
                    <span>Compte</span>
                    <strong>${user.first_name || ""} ${user.last_name || ""}</strong>
                </article>
                <article class="inline-stat">
                    <span>Role</span>
                    <strong>${user.role_name || "ADMIN"}</strong>
                </article>
                <article class="inline-stat">
                    <span>Email</span>
                    <strong>${user.email || "-"}</strong>
                </article>
                <article class="inline-stat">
                    <span>Telephone</span>
                    <strong>${user.phone || "-"}</strong>
                </article>
                <article class="inline-stat">
                    <span>Societe / parfumerie</span>
                    <strong>${user.perfume_shop_name || "-"}</strong>
                </article>
                <article class="inline-stat">
                    <span>Localisation</span>
                    <strong>${user.location || "-"}</strong>
                </article>
                <article class="inline-stat">
                    <span>Visages autorises</span>
                    <strong>${state.adminFaceProfiles.length}</strong>
                </article>
            `;
        }
    }

    if (adminFaceProfilesList) {
        if (!state.adminFaceProfiles.length) {
            adminFaceProfilesList.innerHTML = `
                <article class="face-profile-card empty">
                    <div class="face-profile-card-copy">
                        <p class="employee-label">Aucun acces partage</p>
                        <strong>Aucun visage enregistre</strong>
                        <p class="muted">Ajoutez un premier visage pour autoriser un ou plusieurs collaborateurs a se connecter au compte admin.</p>
                    </div>
                </article>
            `;
            return;
        }

        adminFaceProfilesList.innerHTML = state.adminFaceProfiles.map((profile) => `
            <article class="face-profile-card">
                <div class="face-profile-card-copy">
                    <span class="face-profile-chip">Visage autorise</span>
                    <h4>${profile.profile_label || "Acces sans nom"}</h4>
                    <p class="muted">Ajoute le ${String(profile.created_at || "").replace(" ", " a ")}</p>
                </div>
                <div class="face-profile-card-actions">
                    <span class="face-profile-id">ID ${profile.id}</span>
                    <button class="mini-btn bad" type="button" data-face-profile-delete="${profile.id}">Supprimer</button>
                </div>
            </article>
        `).join("");
    }
};

const showEmployeeForm = () => {
    employeeFormPanel?.classList.remove("admin-hidden");
    employeeForm?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const hideEmployeeForm = () => {
    employeeFormPanel?.classList.add("admin-hidden");
};

const renderExpenses = () => {
    const q = (expenseSearch?.value || "").trim().toLowerCase();
    const filtered = state.expenses
        .filter((row) => `${row.expense_type} ${row.label} ${row.note || ""}`.toLowerCase().includes(q));
    const pagination = state.pagination.expenses;
    const totalPages = Math.max(1, Math.ceil(filtered.length / pagination.perPage));
    if (pagination.page > totalPages) pagination.page = totalPages;
    const start = (pagination.page - 1) * pagination.perPage;
    const paged = filtered.slice(start, start + pagination.perPage);

    adminExpensesBody.innerHTML = paged
        .map((row) => `
            <tr>
                <td>${row.expense_date}</td>
                <td>${row.expense_type}</td>
                <td>${row.label}</td>
                <td>${formatDT(row.amount_dzd)}</td>
                <td>
                    <div class="table-actions">
                        <button class="mini-btn" data-expense-edit="${row.id}">Modifier</button>
                        <button class="mini-btn bad" data-expense-delete="${row.id}">Supprimer</button>
                    </div>
                </td>
            </tr>
        `)
        .join("");

    if (expensesPagination) {
        expensesPagination.innerHTML = renderPaginationControls(filtered.length, pagination.page, totalPages, "expenses");
    }
};

const showExpenseForm = () => {
    expenseFormPanel?.classList.remove("admin-hidden");
    expenseForm?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const hideExpenseForm = () => {
    expenseFormPanel?.classList.add("admin-hidden");
};

const loadSummary = async () => {
    state.summary = await fetchJson(API.summary);
    renderSummary();
};

const loadProducts = async () => {
    const data = await fetchJson(API.products);
    state.products = data.items || [];
    renderOrderCreateProductOptions();
    renderProducts();
    renderPerfumeStock();
};

const loadRawMaterials = async () => {
    const data = await fetchJson(API.rawMaterials);
    state.rawMaterials = data.items || [];
    renderRawMaterials();
};

const loadOrders = async () => {
    const data = await fetchJson(API.orders);
    state.orders = data.items || [];
    state.ordersSummary = data.summary || {};
    renderOrderShopFilter();
    renderOrders();
};

const parseOrderNotes = (notes) => {
    try {
        return JSON.parse(notes || "{}");
    } catch {
        return {};
    }
};

const fillOrderDetail = (payload) => {
    const order = payload.order || {};
    const items = payload.items || [];
    const client = parseOrderNotes(order.notes);

    orderDetailTitle.textContent = `Commande ${order.order_number || ""}`;
    orderEditId.value = order.id || "";
    orderEditLastName.value = client.last_name || order.last_name || "";
    orderEditFirstName.value = client.first_name || order.first_name || "";
    orderEditPhone.value = client.phone || order.phone || "";
    orderEditShop.value = client.shop || order.perfume_shop_name || "";

    orderDetailSummary.innerHTML = `
        <article class="inline-stat">
            <span>Facture</span>
            <strong>${order.invoice_number || "-"}</strong>
        </article>
        <article class="inline-stat">
            <span>Montant</span>
            <strong>${formatDT(order.invoice_total || order.total_dzd || 0)}</strong>
        </article>
        <article class="inline-stat">
            <span>Paye / Reste</span>
            <strong>${formatDT(order.paid_amount || 0)} / ${formatDT(order.remaining_amount || 0)}</strong>
        </article>
        <article class="inline-stat">
            <span>Statuts</span>
            <strong>${order.order_status || "-"} / ${order.invoice_status || "-"}</strong>
        </article>
    `;

    orderDetailItemsBody.innerHTML = items.map((item) => `
        <tr>
            <td>${item.name}</td>
            <td>${item.catalog_group} / ${item.segment}</td>
            <td>${Number(item.quantity_bottles || 0)} bouteille${Number(item.quantity_bottles || 0) > 1 ? "s" : ""}</td>
            <td>${formatDT(item.unit_price_dzd || 0)}</td>
            <td>${formatDT(item.line_total_dzd || 0)}</td>
        </tr>
    `).join("");

    setNote(orderDetailMessage, "");
    orderDetailPanel?.classList.remove("admin-hidden");
    orderDetailPanel?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const loadEmployees = async () => {
    const data = await fetchJson(API.employees);
    state.employees = data.items || [];
    renderEmployees();
};

const loadUsers = async () => {
    const data = await fetchJson(API.users);
    state.users = data.items || [];
    renderOrderCreateUserOptions();
    renderUsers();
};

const loadAdminAccount = async () => {
    const data = await fetchJson(API.account);
    state.adminAccount = data.user || null;
    state.adminFaceProfiles = data.face_profiles || [];
    renderAdminAccount();
};

const loadExpenses = async () => {
    const data = await fetchJson(API.expenses);
    state.expenses = data.items || [];
    renderExpenses();
};

productSearch?.addEventListener("input", () => {
    state.pagination.products.page = 1;
    renderProducts();
});
rawMaterialSearch?.addEventListener("input", () => {
    state.pagination.rawMaterials.page = 1;
    renderRawMaterials();
});
perfumeStockSearch?.addEventListener("input", () => {
    state.pagination.perfumeStock.page = 1;
    renderPerfumeStock();
});
perfumeStockCategoryFilter?.addEventListener("change", () => {
    state.pagination.perfumeStock.page = 1;
    renderPerfumeStock();
});
perfumeStockSort?.addEventListener("change", () => {
    state.pagination.perfumeStock.page = 1;
    renderPerfumeStock();
});
orderSearch?.addEventListener("input", () => {
    state.pagination.orders.page = 1;
    renderOrders();
});
ordersAllBtn?.addEventListener("click", () => setOrderViewMode("ALL"));
ordersPartialBtn?.addEventListener("click", () => setOrderViewMode("PARTIAL_ONLY"));
orderShopFilter?.addEventListener("change", () => {
    state.pagination.orders.page = 1;
    renderOrders();
});
orderStatusFilter?.addEventListener("change", () => {
    state.pagination.orders.page = 1;
    renderOrders();
});
orderInvoiceFilter?.addEventListener("change", () => {
    state.pagination.orders.page = 1;
    renderOrders();
});
orderDateFrom?.addEventListener("change", () => {
    state.pagination.orders.page = 1;
    renderOrders();
});
orderDateTo?.addEventListener("change", () => {
    state.pagination.orders.page = 1;
    renderOrders();
});
orderFiltersResetBtn?.addEventListener("click", () => {
    if (orderSearch) orderSearch.value = "";
    if (orderShopFilter) orderShopFilter.value = "ALL";
    if (orderStatusFilter) orderStatusFilter.value = "ALL";
    if (orderInvoiceFilter) orderInvoiceFilter.value = "ALL";
    if (orderDateFrom) orderDateFrom.value = "";
    if (orderDateTo) orderDateTo.value = "";
    state.pagination.orders.page = 1;
    renderOrders();
});
showOrderCreateBtn?.addEventListener("click", () => {
    activateAdminView("orders");
    showOrderCreatePanel();
});
hideOrderCreateBtn?.addEventListener("click", () => {
    hideOrderCreatePanel();
});
orderCreateProductSearch?.addEventListener("input", renderOrderCreateProductOptions);
orderAddItemBtn?.addEventListener("click", addAdminOrderItem);
employeeSearch?.addEventListener("input", renderEmployees);
userSearch?.addEventListener("input", () => {
    state.pagination.users.page = 1;
    renderUsers();
});
expenseSearch?.addEventListener("input", () => {
    state.pagination.expenses.page = 1;
    renderExpenses();
});
productResetBtn?.addEventListener("click", resetProductForm);
showProductFormBtn?.addEventListener("click", showProductForm);
hideProductFormBtn?.addEventListener("click", hideProductForm);
rawMaterialAddBtn?.addEventListener("click", () => {
    activateAdminView("raw-materials");
    if (rawMaterialSearch) rawMaterialSearch.value = "";
    resetRawMaterialDraft();
    rawMaterialDraftVisible = true;
    state.pagination.rawMaterials.page = 1;
    renderRawMaterials();
    const draftRow = adminRawMaterialsBody?.querySelector('[data-raw-material-row="new"]');
    draftRow?.scrollIntoView({ behavior: "smooth", block: "center" });
    const firstInput = draftRow?.querySelector('[data-field="item_name"]');
    if (firstInput instanceof HTMLInputElement) {
        firstInput.focus();
    }
});
showEmployeeFormBtn?.addEventListener("click", showEmployeeForm);
hideEmployeeFormBtn?.addEventListener("click", hideEmployeeForm);
showExpenseFormBtn?.addEventListener("click", showExpenseForm);
hideExpenseFormBtn?.addEventListener("click", hideExpenseForm);

[productsPagination, perfumeStockPagination, rawMaterialsPagination, ordersPagination, usersPagination, expensesPagination].forEach((paginationEl) => {
    paginationEl?.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const key = target.dataset.pageKey;
        const action = target.dataset.pageAction;
        if (!key || !action || !state.pagination[key]) return;
        if (action === "prev" && state.pagination[key].page > 1) {
            state.pagination[key].page -= 1;
        }
        if (action === "next") {
            state.pagination[key].page += 1;
        }
        if (key === "products") renderProducts();
        if (key === "perfumeStock") renderPerfumeStock();
        if (key === "rawMaterials") renderRawMaterials();
        if (key === "orders") renderOrders();
        if (key === "users") renderUsers();
        if (key === "expenses") renderExpenses();
    });
});

adminRawMaterialsBody?.addEventListener("input", (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    refreshRawMaterialRowTotal(target.closest("tr"));
});

adminRawMaterialsBody?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    const tr = target.closest("tr");
    if (!tr) return;

    if (target.dataset.rawMaterialCreate) {
        try {
            await fetchJson(API.rawMaterials, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(collectRawMaterialPayload(tr)),
            });
            resetRawMaterialDraft();
            rawMaterialDraftVisible = false;
            await loadRawMaterials();
            await loadSummary();
        } catch (error) {
            alert(error.message);
        }
        return;
    }

    if (target.dataset.rawMaterialSave) {
        try {
            await fetchJson(`${API.rawMaterials}/${target.dataset.rawMaterialSave}`, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(collectRawMaterialPayload(tr)),
            });
            await loadRawMaterials();
            await loadSummary();
        } catch (error) {
            alert(error.message);
        }
        return;
    }

    if (target.dataset.rawMaterialDelete) {
        await fetchJson(`${API.rawMaterials}/${target.dataset.rawMaterialDelete}`, { method: "DELETE" });
        await loadRawMaterials();
        await loadSummary();
    }
});

perfumeStockBody?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
});

perfumeStockBody?.addEventListener("change", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) return;
    if (target.dataset.field !== "raw_material_stock_ml") return;

    const tr = target.closest("tr");
    const productId = tr?.getAttribute("data-perfume-stock-row");
    const row = state.products.find((item) => String(item.id) === String(productId));
    if (!tr || !productId || !row) return;

    await fetchJson(`${API.products}/${productId}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            catalog_group: row.catalog_group,
            segment: row.segment,
            code: row.code || "",
            name: row.name,
            price_dzd: Number(row.price_dzd || 0),
            stock_bottles: Number(row.stock_bottles || 0),
            min_alert_bottles: Number(row.min_alert_bottles || 0),
            raw_material_stock_ml: Number(target.value || 0),
            raw_material_alert_ml: Number(row.raw_material_alert_ml || 0),
            sku: row.sku || "",
            barcode: row.barcode || "",
            is_active: Number(row.is_active || 1),
        }),
    });

    await loadProducts();
    await loadSummary();
});

productForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const payload = {
        catalog_group: productCatalogGroup.value,
        segment: productSegment.value,
        code: productCode.value.trim(),
        name: productName.value.trim(),
        price_dzd: Number(productPrice.value),
        stock_bottles: Number(productStock.value),
        min_alert_bottles: Number(productAlert.value || 0),
        raw_material_stock_ml: Number(productRawMaterialStock.value || 0),
        raw_material_alert_ml: Number(productRawMaterialAlert.value || 0),
        sku: productSku.value.trim(),
        barcode: productBarcode.value.trim(),
        is_active: Number(productActive.value),
    };

    try {
        const method = productId.value ? "PATCH" : "POST";
        const url = productId.value ? `${API.products}/${productId.value}` : API.products;
        await fetchJson(url, {
            method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        setNote(productFormMessage, "Produit enregistre.", "success");
        resetProductForm();
        hideProductForm();
        await loadProducts();
        await loadSummary();
    } catch (error) {
        setNote(productFormMessage, error.message, "error");
    }
});

adminProductsBody?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.dataset.productEdit) {
        const row = state.products.find((item) => String(item.id) === target.dataset.productEdit);
        if (row) fillProductForm(row);
    }
    if (target.dataset.productDelete) {
        await fetchJson(`${API.products}/${target.dataset.productDelete}`, { method: "DELETE" });
        await loadProducts();
        await loadSummary();
    }
});

adminOrdersBody?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.dataset.orderView || target.dataset.orderEditOpen) {
        const id = target.dataset.orderView || target.dataset.orderEditOpen;
        const detail = await fetchJson(`${API.orders}/${id}`);
        fillOrderDetail(detail);
        return;
    }
    if (target.dataset.orderPdf) {
        openOrderPdf(target.dataset.orderPdf);
        return;
    }
    if (target.dataset.orderDelete) {
        await fetchJson(`${API.orders}/${target.dataset.orderDelete}`, { method: "DELETE" });
        orderDetailPanel?.classList.add("admin-hidden");
        await loadOrders();
        await loadSummary();
        return;
    }
    if (target.dataset.orderApply) {
        const select = adminOrdersBody.querySelector(`[data-order-status="${target.dataset.orderApply}"]`);
        await fetchJson(`${API.orders}/${target.dataset.orderApply}/status`, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ status: select.value }),
        });
        await loadOrders();
        await loadSummary();
    }
    if (target.dataset.invoiceApply) {
        const invoiceId = target.dataset.invoiceApply;
        const select = adminOrdersBody.querySelector(`[data-invoice-status="${target.dataset.invoiceApply}"]`);
        const row = state.orders.find((item) => String(item.invoice_id) === String(invoiceId));
        const payload = { status: select.value };
        if (select.value === "PARTIEL") {
            if (!row) return;
            openPaymentModal(row);
            return;
        }
        await fetchJson(`/api/admin/invoices/${invoiceId}/status`, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        await loadOrders();
        await loadSummary();
    }
});

orderCreateItemsBody?.addEventListener("click", (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (!target.dataset.orderCreateRemove) return;
    adminOrderCart.delete(String(target.dataset.orderCreateRemove));
    renderAdminOrderCart();
    setNote(orderCreateMessage, "Produit retire du panier admin.", "success");
});

hideOrderDetailBtn?.addEventListener("click", () => {
    orderDetailPanel?.classList.add("admin-hidden");
});

exportOrderPdfBtn?.addEventListener("click", () => {
    openOrderPdf(orderEditId.value);
});

orderEditForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
        await fetchJson(`${API.orders}/${orderEditId.value}`, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                last_name: orderEditLastName.value.trim(),
                first_name: orderEditFirstName.value.trim(),
                phone: orderEditPhone.value.trim(),
                shop: orderEditShop.value.trim(),
            }),
        });
        setNote(orderDetailMessage, "Commande modifiee.", "success");
        const detail = await fetchJson(`${API.orders}/${orderEditId.value}`);
        fillOrderDetail(detail);
        await loadOrders();
    } catch (error) {
        setNote(orderDetailMessage, error.message, "error");
    }
});

orderCreateForm?.addEventListener("submit", async (event) => {
    event.preventDefault();

    const selectedUser = getSelectedAdminUser();
    if (!selectedUser) {
        setNote(orderCreateMessage, "Choisissez une parfumerie.", "error");
        return;
    }
    if (adminOrderCart.size === 0) {
        setNote(orderCreateMessage, "Ajoutez au moins un produit.", "error");
        return;
    }

    const payload = {
        user_id: Number(selectedUser.id),
        client: {
            last_name: selectedUser.last_name || "",
            first_name: selectedUser.first_name || "",
            phone: selectedUser.phone || "",
            shop: selectedUser.perfume_shop_name || "",
        },
        items: Array.from(adminOrderCart.values()).map((item) => ({
            product_id: item.product_id,
            qty: item.qty,
            unit_price: item.price,
        })),
    };

    try {
        await fetchJson(ADMIN_ORDERS_API, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        setNote(orderCreateMessage, "Commande admin enregistree.", "success");
        resetAdminOrderBuilder();
        hideOrderCreatePanel();
        await loadOrders();
        await loadSummary();
    } catch (error) {
        setNote(orderCreateMessage, error.message, "error");
    }
});

employeeForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const payload = {
        first_name: employeeFirstName.value.trim(),
        last_name: employeeLastName.value.trim(),
        email: employeeEmail.value.trim(),
        phone: employeePhone.value.trim(),
        employee_code: employeeCode.value.trim(),
        job_title: employeeJob.value.trim(),
        salary_dzd: Number(employeeSalary.value),
        hire_date: employeeHireDate.value,
        password: employeePassword.value,
    };

    try {
        await fetchJson(API.employees, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        setNote(employeeFormMessage, "Employe ajoute.", "success");
        employeeForm.reset();
        hideEmployeeForm();
        await loadEmployees();
        await loadSummary();
    } catch (error) {
        setNote(employeeFormMessage, error.message, "error");
    }
});

adminEmployeesGrid?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.dataset.employeeEdit) {
        const row = state.employees.find((item) => String(item.id) === target.dataset.employeeEdit);
        if (!row) return;
        const job_title = prompt("Poste", row.job_title);
        if (job_title === null) return;
        const salary = prompt("Salaire DT", row.salary_dzd);
        if (salary === null) return;
        const employment_status = prompt("Statut ACTIF / INACTIF / SUSPENDU", row.employment_status);
        if (employment_status === null) return;
        await fetchJson(`${API.employees}/${row.id}`, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                job_title,
                salary_dzd: Number(salary),
                employment_status,
            }),
        });
        await loadEmployees();
        await loadSummary();
    }
    if (target.dataset.employeeDelete) {
        await fetchJson(`${API.employees}/${target.dataset.employeeDelete}`, { method: "DELETE" });
        await loadEmployees();
        await loadSummary();
    }
});

adminUsersBody?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    const userId = target.dataset.userView || target.dataset.userEdit || target.dataset.userDelete;
    if (!userId) return;
    const row = state.users.find((item) => String(item.id) === userId);
    if (!row) return;

    if (target.dataset.userView || target.dataset.userEdit) {
        fillUserForm(row);
        return;
    }

    if (target.dataset.userDelete) {
        await fetchJson(`${API.users}/${userId}`, { method: "DELETE" });
        userDetailPanel?.classList.add("admin-hidden");
        await loadUsers();
        await loadSummary();
    }
});

hideUserDetailBtn?.addEventListener("click", () => {
    userDetailPanel?.classList.add("admin-hidden");
});

adminFaceOpenBtn?.addEventListener("click", async () => {
    try {
        await openAdminFaceModal();
        setNote(adminFaceMessage, "Camera ouverte. Capturez le visage a autoriser.", "success");
    } catch (error) {
        setNote(adminFaceMessage, explainCameraError(error), "error");
    }
});

paymentModalBackdrop?.addEventListener("click", closePaymentModal);
paymentModalCloseBtn?.addEventListener("click", closePaymentModal);
paymentModalCancelBtn?.addEventListener("click", closePaymentModal);

paymentAmountInput?.addEventListener("input", () => {
    setPaymentModalMessage("");
});

paymentAmountInput?.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        paymentModalConfirmBtn?.click();
    }
});

paymentModalConfirmBtn?.addEventListener("click", async () => {
    if (!pendingPartialPayment || !paymentAmountInput) return;

    const amount = Number(String(paymentAmountInput.value || "").replace(",", ".").trim());
    if (!Number.isFinite(amount) || amount <= 0) {
        setPaymentModalMessage("Saisissez un montant valide.", "error");
        return;
    }
    if (amount >= Number(pendingPartialPayment.remaining || 0)) {
        setPaymentModalMessage("Le montant partiel doit rester inferieur au reste a payer.", "error");
        return;
    }

    paymentModalConfirmBtn.disabled = true;
    paymentModalConfirmBtn.textContent = "Validation...";
    try {
        await fetchJson(`/api/admin/invoices/${pendingPartialPayment.invoiceId}/status`, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ status: "PARTIEL", amount_paid: amount }),
        });
        closePaymentModal();
        await loadOrders();
        await loadSummary();
    } catch (error) {
        setPaymentModalMessage(error.message || "Validation impossible.", "error");
    } finally {
        paymentModalConfirmBtn.disabled = false;
        paymentModalConfirmBtn.textContent = "Valider le paiement";
    }
});

adminCloseFaceModalBtn?.addEventListener("click", closeAdminFaceModal);

adminCaptureFaceBtn?.addEventListener("click", async () => {
    if (!adminCaptureFaceBtn) return;
    adminCaptureFaceBtn.disabled = true;

    try {
        const matrix = await buildFaceMatrixFromElements(adminFaceVideo, adminFaceCanvas);
        const payload = {
            label: adminFaceLabel?.value.trim() || "",
            matrix,
        };
        const data = await fetchJson(API.accountFaces, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });

        state.adminAccount = data.user || state.adminAccount;
        state.adminFaceProfiles = data.face_profiles || [];
        renderAdminAccount();
        closeAdminFaceModal();
        if (adminFaceLabel) {
            adminFaceLabel.value = "";
        }
        setNote(adminFaceMessage, "Nouveau visage admin enregistre.", "success");
    } catch (error) {
        setNote(adminFaceMessage, error.message || "Enregistrement du visage impossible.", "error");
    } finally {
        adminCaptureFaceBtn.disabled = false;
    }
});

adminFaceProfilesList?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    const profileId = target.dataset.faceProfileDelete;
    if (!profileId) return;

    try {
        const data = await fetchJson(`${API.accountFaces}/${profileId}`, { method: "DELETE" });
        state.adminAccount = data.user || state.adminAccount;
        state.adminFaceProfiles = data.face_profiles || [];
        renderAdminAccount();
        setNote(adminFaceMessage, "Visage supprime.", "success");
    } catch (error) {
        setNote(adminFaceMessage, error.message || "Suppression impossible.", "error");
    }
});

userEditForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
        await fetchJson(`${API.users}/${userEditId.value}`, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                first_name: userEditFirstName.value.trim(),
                last_name: userEditLastName.value.trim(),
                perfume_shop_name: userEditShop.value.trim(),
                phone: userEditPhone.value.trim(),
                location: userEditLocation.value.trim(),
                email: userEditEmail.value.trim(),
                is_active: Number(userEditActive.value),
            }),
        });
        setNote(userFormMessage, "Profil user modifie.", "success");
        await loadUsers();
        await loadSummary();
    } catch (error) {
        setNote(userFormMessage, error.message, "error");
    }
});

const resetExpenseForm = () => {
    expenseForm?.reset();
    expenseId.value = "";
    setNote(expenseFormMessage, "");
};

expenseResetBtn?.addEventListener("click", resetExpenseForm);

expenseForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const payload = {
        expense_type: expenseType.value,
        label: expenseLabel.value.trim(),
        amount_dzd: Number(expenseAmount.value),
        expense_date: expenseDate.value,
        note: expenseNote.value.trim(),
    };

    try {
        const method = expenseId.value ? "PATCH" : "POST";
        const url = expenseId.value ? `${API.expenses}/${expenseId.value}` : API.expenses;
        await fetchJson(url, {
            method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        setNote(expenseFormMessage, "Charge enregistree.", "success");
        resetExpenseForm();
        hideExpenseForm();
        await loadExpenses();
        await loadSummary();
    } catch (error) {
        setNote(expenseFormMessage, error.message, "error");
    }
});

adminExpensesBody?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.dataset.expenseEdit) {
        const row = state.expenses.find((item) => String(item.id) === target.dataset.expenseEdit);
        if (!row) return;
        expenseId.value = row.id;
        expenseType.value = row.expense_type;
        expenseLabel.value = row.label;
        expenseAmount.value = row.amount_dzd;
        expenseDate.value = row.expense_date;
        expenseNote.value = row.note || "";
        activateAdminView("expenses");
        showExpenseForm();
    }
    if (target.dataset.expenseDelete) {
        await fetchJson(`${API.expenses}/${target.dataset.expenseDelete}`, { method: "DELETE" });
        await loadExpenses();
        await loadSummary();
    }
});

const init = async () => {
    await Promise.all([loadSummary(), loadAdminAccount(), loadProducts(), loadRawMaterials(), loadOrders(), loadUsers(), loadEmployees(), loadExpenses()]);
};

init();
