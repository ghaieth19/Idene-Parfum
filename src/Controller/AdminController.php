<?php

namespace App\Controller;

use App\Support\AppContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController
{
    public function __construct(private readonly AppContext $app)
    {
    }

    #[Route('/admin', name: 'app_admin_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return new RedirectResponse('/auth');
        }

        if (!$this->app->isAdminSession()) {
            return new RedirectResponse('/dashboard');
        }

        $user = $this->app->fetchUserWithRoleById($userId);
        if (!$user) {
            $this->app->logoutUser();

            return new RedirectResponse('/auth');
        }

        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $role = (string) ($user['role_name'] ?? 'ADMIN');

        $html = str_replace(
            ['__ADMIN_NAME__', '__ADMIN_ROLE__'],
            [htmlspecialchars($name, ENT_QUOTES), htmlspecialchars($role, ENT_QUOTES)],
            <<<'HTML'
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IDENE PARFUM | Admin</title>
    <meta name="description" content="Espace admin pour produits, stock, commandes, paiements, employes et rentabilite.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin-app.css?v=22">
</head>
<body data-admin-name="__ADMIN_NAME__" data-admin-role="__ADMIN_ROLE__">
    <button type="button" id="mobileAdminSidebarToggle" class="mobile-sidebar-toggle hamburger-toggle" aria-label="Ouvrir ou fermer le menu admin">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div id="mobileAdminSidebarBackdrop" class="mobile-sidebar-backdrop"></div>
    <div class="admin-shell container-fluid px-3 px-lg-4">
        <aside id="adminSidebar" class="admin-sidebar">
            <div class="mobile-sidebar-head">
                <strong data-i18n="admin.navTitle">Navigation</strong>
            </div>
            <div class="admin-brand">
                <img src="/assets/images/logo.png" alt="Logo Idene Parfum">
                <div>
                    <p class="eyebrow" data-i18n="admin.administration">Administration</p>
                    <h1>IDENE PARFUM</h1>
                    <p class="muted" data-i18n="admin.centralMgmt">Gestion centrale</p>
                </div>
            </div>

            <nav class="admin-nav">
                <button type="button" class="admin-link" data-admin-view="overview"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span data-i18n="admin.overview">Vue generale</span></button>
                <button type="button" class="admin-link" data-admin-view="account"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span data-i18n="admin.myAccount">Mon compte</span></button>
                <button type="button" class="admin-link active" data-admin-view="products"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg><span data-i18n="admin.products">Produits</span></button>
                <button type="button" class="admin-link" data-admin-view="raw-materials"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg><span data-i18n="admin.materialStock">Stock matieres</span></button>
                <button type="button" class="admin-link" data-admin-view="orders"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg><span data-i18n="admin.orders">Commandes</span></button>
                <button type="button" class="admin-link" data-admin-view="documents"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><line x1="8" y1="9" x2="10" y2="9"/></svg><span data-i18n="admin.documents">Documents</span></button>
                <button type="button" class="admin-link" data-admin-view="users"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><span data-i18n="admin.users">Users</span></button>
                <button type="button" class="admin-link" data-admin-view="employees"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg><span data-i18n="admin.employees">Employes</span></button>
                <button type="button" class="admin-link" data-admin-view="expenses"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><span data-i18n="admin.expenses">Charges</span></button>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-profile">
                    <span class="role-pill">__ADMIN_ROLE__</span>
                    <strong>__ADMIN_NAME__</strong>
                </div>
                <a href="/accueil" class="ghost-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg><span data-i18n="admin.clientView">Vue client</span></a>
                <button id="adminLogoutBtn" class="danger-btn" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span data-i18n="dash.logout">Deconnexion</span></button>
            </div>
        </aside>

        <main class="admin-main">
            <section class="admin-topbar">
                <div>
                    <p class="eyebrow" data-i18n="admin.uxTitle">Administration UX</p>
                    <h2 class="admin-topbar-title" data-i18n="admin.pilotBoard">Tableau de pilotage</h2>
                </div>
                <div class="admin-topbar-actions">
                    <div class="admin-topbar-tags">
                        <span data-i18n="admin.globalVision">Vision globale</span>
                        <span data-i18n="admin.quickActions">Actions rapides</span>
                        <span data-i18n="admin.proDesign">Design pro</span>
                    </div>
                    <button class="lang-toggle-btn" type="button" aria-label="Switch language" style="margin-right: 12px; height: 38px;">
                        <span class="lang-toggle-flag">????</span>
                        <span class="lang-toggle-label" style="display:none;">???????</span>
                    </button>
                    <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Changer le theme">
                        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    </button>
                </div>
            </section>

            <section id="admin-view-overview" class="admin-view">
                <section class="admin-hero">
                    <div>
                        <p class="eyebrow" data-i18n="admin.financialOps">Pilotage financier et operationnel</p>
                        <h2 data-i18n="admin.clearDashboard">Dashboard admin plus clair et plus professionnel</h2>
                        <p class="lead" data-i18n="admin.leadText">Recette du jour, chiffre du mois, charges, achats de matieres premieres, stock et benefice estime dans une presentation plus nette et plus facile a lire.</p>
                    </div>
                    <div class="hero-accent">
                        <span class="hero-badge" data-i18n="admin.newVisual">Nouvelle direction visuelle</span>
                        <strong data-i18n="admin.fullFlux">Flux admin complet</strong>
                        <p class="section-copy" data-i18n="admin.organizedInterface">Une interface organisee pour suivre les commandes, la rentabilite et les operations sans surcharge visuelle.</p>
                    </div>
                </section>

                <section class="metrics-grid" id="adminMetricsGrid"></section>

                <section class="admin-grid two">
                    <article class="admin-card">
                        <div class="section-head">
                            <h3 data-i18n="admin.recentOrders">Commandes recentes</h3>
                        </div>
                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th data-i18n="admin.order">Commande</th>
                                        <th data-i18n="admin.client">Client</th>
                                        <th data-i18n="admin.amount">Montant</th>
                                        <th data-i18n="admin.delivery">Livraison</th>
                                        <th data-i18n="admin.payment">Paiement</th>
                                    </tr>
                                </thead>
                                <tbody id="adminRecentOrdersBody"></tbody>
                            </table>
                        </div>
                    </article>

                    <article class="admin-card">
                        <div class="section-head">
                            <h3 data-i18n="admin.orderActivity">Activite commandes</h3>
                        </div>
                        <div id="adminActivityChart" class="activity-chart"></div>
                    </article>
                </section>

                <section class="admin-grid two">
                    <article class="admin-card">
                        <div class="section-head">
                            <h3>Charges recentes</h3>
                        </div>
                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th data-i18n="admin.date">Date</th>
                                        <th data-i18n="admin.type">Type</th>
                                        <th data-i18n="admin.label">Libelle</th>
                                        <th data-i18n="admin.amount">Montant</th>
                                    </tr>
                                </thead>
                                <tbody id="adminRecentExpensesBody"></tbody>
                            </table>
                        </div>
                    </article>

                    <article class="admin-card">
                        <div class="section-head">
                            <h3>Repere rapide</h3>
                        </div>
                        <p class="section-copy">Surveillez les pics de commandes par jour pour comparer l'activite avec les charges et les paiements valides.</p>
                    </article>
                </section>
            </section>

            <section id="admin-view-products" class="admin-view active">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.centralCatalog">Catalogue central</p>
                            <h3 data-i18n="admin.productsStock">Produits et stock</h3>
                            <p class="section-copy" data-i18n="admin.productsStockDesc">Consultez le catalogue, surveillez le stock par bouteilles et les matieres premieres, puis ouvrez le formulaire pour ajouter ou modifier un produit.</p>
                        </div>
                        <div class="section-actions">
                            <input id="productSearch" class="search-input" type="search" placeholder="Rechercher un produit..." data-i18n-placeholder="admin.searchProduct">
                            <button id="showProductFormBtn" class="primary-btn" type="button"><span data-i18n="admin.addProduct">Ajouter un produit</span></button>
                        </div>
                    </div>
                    <div id="productSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th data-i18n="admin.product">Produit</th>
                                    <th data-i18n="admin.family">Famille</th>
                                    <th data-i18n="admin.price">Prix</th>
                                    <th data-i18n="admin.bottleStock">Stock bouteilles</th>
                                    <th data-i18n="admin.baseStock">Stock base</th>
                                    <th data-i18n="admin.actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminProductsBody"></tbody>
                        </table>
                    </div>
                    <div id="productsPagination" class="table-pagination"></div>
                </section>

                <section id="productFormPanel" class="admin-card admin-hidden">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.productForm">Formulaire produit</p>
                            <h3 data-i18n="admin.addOrEditProduct">Ajouter ou modifier un produit</h3>
                        </div>
                        <div class="section-actions">
                            <p id="productFormMessage" class="form-note"></p>
                            <button id="hideProductFormBtn" class="soft-btn" type="button"><span data-i18n="admin.backToList">Retour liste</span></button>
                        </div>
                    </div>
                    <form id="productForm" class="admin-form">
                        <input type="hidden" id="productId">
                        <div class="form-grid">
                            <label data-i18n="admin.category"><select id="productCatalogGroup"><option value="PRINCIPAL">PRINCIPAL</option><option value="SMART">SMART</option><option value="ENFANT">ENFANT</option><option value="LUXE">LUXE</option><option value="MIXTE">MIXTE</option><option value="AUTRE">AUTRE</option></select></label>
                            <label data-i18n="admin.segment"><select id="productSegment"><option value="HOMME">HOMME</option><option value="FEMME">FEMME</option><option value="UNISEX">UNISEX</option><option value="ENFANT">ENFANT</option><option value="MIXTE">MIXTE</option><option value="AUTRE">AUTRE</option></select></label>
                            <label data-i18n="admin.code"><input id="productCode" type="text"></label>
                            <label data-i18n="admin.perfumeName"><input id="productName" type="text" required></label>
                            <label data-i18n="admin.priceDT"><input id="productPrice" type="number" min="0" step="0.01" required></label>
                            <label data-i18n="admin.bottleStock"><input id="productStock" type="number" min="0" step="1" required></label>
                            <label data-i18n="admin.bottleAlertThreshold"><input id="productAlert" type="number" min="0" step="1"></label>
                            <label data-i18n="admin.rawMaterialStockMl"><input id="productRawMaterialStock" type="number" min="0" step="0.01"></label>
                            <label data-i18n="admin.rawMaterialAlertMl"><input id="productRawMaterialAlert" type="number" min="0" step="0.01"></label>
                            <label data-i18n="admin.sku"><input id="productSku" type="text"></label>
                            <label data-i18n="admin.barcode"><input id="productBarcode" type="text"></label>
                            <label data-i18n="admin.active"><select id="productActive"><option value="1">Oui</option><option value="0">Non</option></select></label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit"><span data-i18n="admin.saveProduct">Enregistrer produit</span></button>
                            <button id="productResetBtn" class="soft-btn" type="button"><span data-i18n="admin.new">Nouveau</span></button>
                        </div>
                    </form>
                </section>
            </section>

            <section id="admin-view-account" class="admin-view">
                <section class="admin-card admin-account-hero">
                    <div>
                        <p class="section-kicker" data-i18n="admin.biometricAccess">Acces biometrique admin</p>
                        <h3 data-i18n="admin.controlAuthorizedFaces">Controlez les visages autorises avec une presentation plus professionnelle</h3>
                        <p class="section-copy" data-i18n="admin.biometricDesc">Enregistrez proprement chaque collaborateur autorise sur le compte admin, gardez une vue claire des acces actifs et ouvrez la camera dans une fenetre plus elegante.</p>
                    </div>
                    <div class="admin-account-hero-badge">
                        <span class="hero-badge" data-i18n="admin.sharedSecurity">Securite partagee</span>
                        <strong data-i18n="admin.adminTeam">Equipe admin</strong>
                        <p class="muted" data-i18n="admin.sharedAccountDesc">Un meme compte peut etre utilise par plusieurs personnes autorisees, chacune avec son visage enregistre.</p>
                    </div>
                </section>

                <section class="admin-grid two">
                    <article class="admin-card">
                        <div class="section-head">
                            <div>
                                <p class="section-kicker" data-i18n="admin.adminAccount">Compte admin</p>
                                <h3 data-i18n="admin.accountInfo">Informations du compte</h3>
                                <p class="section-copy" data-i18n="admin.accountInfoDesc">Retrouvez les informations du compte administrateur actuellement connecte.</p>
                            </div>
                        </div>
                        <div id="adminAccountSummary" class="inline-stats"></div>
                    </article>

                    <article class="admin-card">
                        <div class="section-head">
                            <div>
                                <p class="section-kicker" data-i18n="admin.sharedAccess">Acces partages</p>
                                <h3 data-i18n="admin.authorizedFaces">Visages autorises</h3>
                                <p class="section-copy" data-i18n="admin.addMultipleFaces">Ajoutez plusieurs visages pour que plusieurs personnes de la societe puissent acceder au meme compte admin.</p>
                            </div>
                        </div>
                        <div class="admin-face-composer">
                            <label class="admin-face-label">
                                <span data-i18n="admin.personOrJobName">Nom de la personne ou du poste</span>
                                <input id="adminFaceLabel" class="search-input" type="text" maxlength="120" placeholder="Ex: Directeur, Responsable boutique, Comptable" data-i18n-placeholder="admin.faceLabelPlaceholder">
                            </label>
                            <div class="admin-face-composer-actions">
                                <button id="adminFaceOpenBtn" class="primary-btn" type="button"><span data-i18n="admin.addFace">Ajouter un visage</span></button>
                            </div>
                        </div>
                        <div id="adminInlineFaceCapture" class="admin-inline-face-capture admin-hidden">
                            <div class="admin-inline-face-head">
                                <div>
                                    <p class="employee-label" data-i18n="admin.integratedCamera">Camera integree</p>
                                    <h4 data-i18n="admin.frameAndCapture">Cadrez le visage puis capturez</h4>
                                </div>
                                <span class="face-profile-chip" data-i18n="admin.addingInProgress">Ajout en cours</span>
                            </div>
                            <div class="camera-shell admin-inline-camera-shell">
                                <video id="adminFaceVideo" class="face-video" autoplay playsinline muted></video>
                                <div class="camera-frame"></div>
                            </div>
                            <canvas id="adminFaceCanvas" class="face-canvas" width="320" height="240"></canvas>
                            <div class="face-modal-actions admin-inline-face-actions">
                                <button id="adminCaptureFaceBtn" type="button" class="primary-btn"><span data-i18n="admin.captureThisFace">Capturer ce visage</span></button>
                                <button id="adminCloseFaceModalBtn" type="button" class="soft-btn"><span data-i18n="admin.cancel">Annuler</span></button>
                            </div>
                        </div>
                        <p id="adminFaceMessage" class="form-note"></p>
                        <div id="adminFaceProfilesList" class="face-profile-list"></div>
                    </article>
                </section>
            </section>

            <section id="admin-view-raw-materials" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.perfumeStock">Stock parfums</p>
                            <h3 data-i18n="admin.allPerfumesTable">Tableau de tous les parfums</h3>
                            <p class="section-copy" data-i18n="admin.allPerfumesDesc">Consultez tous les parfums et laissez le directeur renseigner le stock de base uniquement en nombre de bouteilles.</p>
                        </div>
                        <div class="section-actions">
                            <input id="perfumeStockSearch" class="search-input" type="search" placeholder="Rechercher un parfum..." data-i18n-placeholder="admin.searchPerfume">
                            <select id="perfumeStockCategoryFilter" class="search-input">
                                <option value="ALL" data-i18n="admin.allCategories">Toutes categories</option>
                                <option value="PRINCIPAL">PRINCIPAL</option>
                                <option value="SMART">SMART</option>
                                <option value="ENFANT">ENFANT</option>
                            </select>
                            <select id="perfumeStockSort" class="search-input">
                                <option value="name_asc" data-i18n="admin.sortNameAZ">Tri: nom A-Z</option>
                                <option value="name_desc" data-i18n="admin.sortNameZA">Tri: nom Z-A</option>
                                <option value="category_asc" data-i18n="admin.sortCategory">Tri: categorie</option>
                                <option value="base_desc" data-i18n="admin.sortBaseDesc">Tri: stock base desc</option>
                                <option value="base_asc" data-i18n="admin.sortBaseAsc">Tri: stock base asc</option>
                            </select>
                        </div>
                    </div>
                    <div id="perfumeStockStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th data-i18n="admin.perfume">Parfum</th>
                                    <th data-i18n="admin.category">Categorie</th>
                                    <th data-i18n="admin.code">Code</th>
                                    <th data-i18n="admin.baseStock">Stock base</th>
                                </tr>
                            </thead>
                            <tbody id="perfumeStockBody"></tbody>
                        </table>
                    </div>
                    <div id="perfumeStockPagination" class="table-pagination"></div>
                </section>

                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.purchaseDirection">Direction achats et stock</p>
                            <h3 data-i18n="admin.materialStockTitle">Stock et matieres premieres</h3>
                            <p class="section-copy" data-i18n="admin.materialStockDesc2">Ajoutez les bases de parfum, alcool, colorants, bouteilles fragiles, tickets et bouchons dans un tableau editable avec calcul automatique du total en dinars.</p>
                        </div>
                        <div class="section-actions">
                            <input id="rawMaterialSearch" class="search-input" type="search" placeholder="Rechercher base, alcool, bouteille..." data-i18n-placeholder="admin.searchMaterial">
                            <button id="rawMaterialAddBtn" class="primary-btn" type="button"><span data-i18n="admin.addLine">Ajouter une ligne</span></button>
                        </div>
                    </div>
                    <div id="rawMaterialSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table raw-material-table">
                            <thead>
                                <tr>
                                    <th data-i18n="admin.category">Categorie</th>
                                    <th data-i18n="admin.article">Article</th>
                                    <th data-i18n="admin.unit">Unite</th>
                                    <th data-i18n="admin.stock">Stock</th>
                                    <th data-i18n="admin.alert">Alerte</th>
                                    <th data-i18n="admin.unitCost">Cout unitaire</th>
                                    <th data-i18n="admin.total">Total</th>
                                    <th data-i18n="admin.purchaseDate">Date achat</th>
                                    <th data-i18n="admin.supplier">Fournisseur</th>
                                    <th data-i18n="admin.note">Note</th>
                                    <th data-i18n="admin.actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminRawMaterialsBody"></tbody>
                        </table>
                    </div>
                    <div id="rawMaterialsPagination" class="table-pagination"></div>
                </section>
            </section>

            <section id="admin-view-users" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.accountMgmt">Gestion des comptes</p>
                            <h3 data-i18n="admin.usersProfiles">Users et profils</h3>
                            <p class="section-copy" data-i18n="admin.usersProfilesDesc">Consultez les profils utilisateurs, modifiez leurs informations et desactivez les comptes si necessaire.</p>
                        </div>
                        <input id="userSearch" class="search-input" type="search" placeholder="Rechercher un user..." data-i18n-placeholder="admin.searchUser">
                    </div>
                    <div id="userSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th data-i18n="admin.fullName">Nom complet</th>
                                    <th data-i18n="admin.role">Role</th>
                                    <th data-i18n="admin.perfumery">Parfumerie</th>
                                    <th data-i18n="admin.contact">Contact</th>
                                    <th data-i18n="admin.location">Localisation</th>
                                    <th data-i18n="admin.status">Statut</th>
                                    <th data-i18n="admin.actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminUsersBody"></tbody>
                        </table>
                    </div>
                    <div id="usersPagination" class="table-pagination"></div>
                </section>

                <section id="userDetailPanel" class="admin-card admin-hidden">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.userConsultation">Consultation user</p>
                            <h3 id="userDetailTitle" data-i18n="admin.userProfile">Profil utilisateur</h3>
                        </div>
                        <div class="section-actions">
                            <p id="userFormMessage" class="form-note"></p>
                            <button id="hideUserDetailBtn" class="soft-btn" type="button"><span data-i18n="admin.close">Fermer</span></button>
                        </div>
                    </div>
                    <form id="userEditForm" class="admin-form">
                        <input type="hidden" id="userEditId">
                        <div class="form-grid">
                            <label data-i18n="admin.firstName"><input id="userEditFirstName" type="text" required></label>
                            <label data-i18n="admin.lastName"><input id="userEditLastName" type="text" required></label>
                            <label data-i18n="admin.perfumery"><input id="userEditShop" type="text" required></label>
                            <label data-i18n="admin.phone"><input id="userEditPhone" type="text" required></label>
                            <label data-i18n="admin.location"><input id="userEditLocation" type="text" required></label>
                            <label data-i18n="admin.email"><input id="userEditEmail" type="email" required></label>
                            <label data-i18n="admin.status">
                                <select id="userEditActive">
                                    <option value="1" data-i18n="admin.active">Actif</option>
                                    <option value="0" data-i18n="admin.inactive">Inactif</option>
                                </select>
                            </label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit"><span data-i18n="admin.saveUser">Enregistrer user</span></button>
                        </div>
                    </form>
                </section>
            </section>

            <section id="admin-view-orders" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.orderPilot">Pilotage commandes</p>
                            <h3 data-i18n="admin.ordersPayments">Commandes et paiements</h3>
                            <p class="section-copy" data-i18n="admin.ordersPaymentsDesc">Consultez les commandes, voyez le total paye, la recette du jour et gerez les actions de suivi sur chaque dossier.</p>
                        </div>
                        <div class="orders-view-switch" role="tablist" aria-label="Vue commandes">
                            <button id="ordersAllBtn" class="soft-btn is-active" type="button"><span data-i18n="admin.allOrders">Toutes les commandes</span></button>
                            <button id="ordersPartialBtn" class="soft-btn" type="button"><span data-i18n="admin.partialPayments">Paiements partiels</span></button>
                        </div>
                        <div class="section-actions orders-toolbar">
                            <input id="orderSearch" class="search-input" type="search" placeholder="Rechercher commande, client, parfumerie, facture..." data-i18n-placeholder="admin.searchOrder">
                            <select id="orderTypeFilter" class="search-input orders-filter">
                                <option value="ALL" data-i18n="admin.allTypes">Tous types</option>
                                <option value="DETAIL">BON_COMMANDE_SITE</option>
                                <option value="GROS">FACTURE_STOCK</option>
                            </select>
                            <select id="orderShopFilter" class="search-input orders-filter">
                                <option value="ALL" data-i18n="admin.allPerfumery">Toutes parfumeries</option>
                            </select>
                            <select id="orderStatusFilter" class="search-input orders-filter">
                                <option value="ALL" data-i18n="admin.allDeliveryStatus">Tous statuts livraison</option>
                                <option value="CONFIRMEE">CONFIRMEE</option>
                                <option value="EN_PREPARATION">EN_PREPARATION</option>
                                <option value="EXPEDIEE">EXPEDIEE</option>
                                <option value="LIVREE">LIVREE</option>
                                <option value="ANNULEE">ANNULEE</option>
                            </select>
                            <select id="orderInvoiceFilter" class="search-input orders-filter">
                                <option value="ALL" data-i18n="admin.allPaymentStatus">Tous statuts paiement</option>
                                <option value="NON_PAYE">NON_PAYE</option>
                                <option value="PARTIEL">PARTIEL</option>
                                <option value="PAYE">PAYE</option>
                            </select>
                            <label class="orders-date-filter">
                                <span data-i18n="admin.from">Du</span>
                                <input id="orderDateFrom" class="search-input orders-filter" type="date">
                            </label>
                            <label class="orders-date-filter">
                                <span data-i18n="admin.to">Au</span>
                                <input id="orderDateTo" class="search-input orders-filter" type="date">
                            </label>
                            <button id="showOrderCreateBtn" class="primary-btn" type="button"><span data-i18n="admin.createOrder">Creer une commande</span></button>
                            <button id="orderFiltersResetBtn" class="soft-btn" type="button"><span data-i18n="admin.showAll">Tout afficher</span></button>
                        </div>
                    </div>
                    <div id="orderSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th data-i18n="admin.order">Commande</th>
                                    <th data-i18n="admin.client">Client</th>
                                    <th data-i18n="admin.amount">Montant</th>
                                    <th data-i18n="admin.delivery">Livraison</th>
                                    <th data-i18n="admin.payment">Paiement</th>
                                    <th data-i18n="admin.actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminOrdersBody"></tbody>
                        </table>
                    </div>
                    <div id="ordersPagination" class="table-pagination"></div>
                </section>

                <section id="orderCreatePanel" class="admin-card admin-hidden">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.orderCreation">Creation commande admin</p>
                            <h3 id="orderCreatePanelTitle" data-i18n="admin.createOrderForPerfumery">Creer une commande pour une parfumerie</h3>
                            <p id="orderCreatePanelCopy" class="section-copy" data-i18n="admin.createOrderDesc">Choisissez une parfumerie existante, ajoutez les parfums souhaites puis enregistrez la commande sous son compte.</p>
                        </div>
                        <div class="section-actions">
                            <p id="orderCreateMessage" class="form-note"></p>
                            <button id="hideOrderCreateBtn" class="soft-btn" type="button"><span data-i18n="admin.close">Fermer</span></button>
                        </div>
                    </div>

                    <form id="orderCreateForm" class="admin-form invoice-workspace">
                        <div id="orderCreateWorkspace" class="invoice-workspace-sheet">
                            <div class="invoice-software-head">
                                <div class="invoice-software-badge">exec</div>
                                <div class="invoice-software-actions">
                                    <button id="orderCreateSubmitTopBtn" class="primary-btn" type="submit"><span data-i18n="admin.validateDocument">Valider document</span></button>
                                </div>
                            </div>

                            <div class="invoice-layout-grid">
                                <section class="invoice-panel">
                                    <div class="invoice-panel-grid invoice-panel-grid-left">
                                        <label data-i18n="admin.document">
                                            <input id="orderCreateDocumentLabel" type="text" value="Facture stock parfumerie" readonly>
                                        </label>
                                        <label data-i18n="admin.number">
                                            <input id="orderCreateDocumentNumber" type="text" value="Auto" placeholder="Auto">
                                        </label>
                                        <label data-i18n="admin.date">
                                            <input id="orderCreateDocumentDate" type="date" required>
                                        </label>
                                        <label data-i18n="admin.nature">
                                            <select id="orderCreateSaleType" required>
                                                <option value="DETAIL" data-i18n="admin.siteOrder">Bon de commande site</option>
                                                <option value="GROS" data-i18n="admin.stockInvoice">Facture stock parfumerie</option>
                                            </select>
                                        </label>
                                        <label data-i18n="admin.depot">
                                            <input id="orderCreateDepot" type="text" value="PRINCIPAL">
                                        </label>
                                        <label data-i18n="admin.orderNum">
                                            <input id="orderCreateOrderCode" type="text" placeholder="Bon / code interne">
                                        </label>
                                        <label data-i18n="admin.discountRate">
                                            <input id="orderCreateGlobalDiscount" type="number" min="0" step="0.001" value="0.000">
                                        </label>
                                        <label data-i18n="admin.exceptionalRate">
                                            <input id="orderCreateExceptionalTax" type="number" min="0" step="0.001" value="0.000">
                                        </label>
                                    </div>
                                </section>

                                <section class="invoice-panel">
                                    <div class="invoice-panel-grid invoice-panel-grid-right">
                                        <label data-i18n="admin.perfumery">
                                            <input id="orderCreateShop" type="search" list="orderCreateShopList" placeholder="Chercher une parfumerie..." data-i18n-placeholder="admin.searchPerfumery" required>
                                            <datalist id="orderCreateShopList"></datalist>
                                        </label>
                                        <label data-i18n="admin.clientCode">
                                            <input id="orderCreateClientCode" type="text" placeholder="Code client">
                                        </label>
                                        <label data-i18n="admin.contactName">
                                            <input id="orderCreateContactName" type="text" placeholder="Nom du contact">
                                        </label>
                                        <label data-i18n="admin.phone">
                                            <input id="orderCreatePhone" type="text" placeholder="Telephone client">
                                        </label>
                                        <label data-i18n="admin.address">
                                            <input id="orderCreateAddress" type="text" placeholder="Adresse client">
                                        </label>
                                        <label data-i18n="admin.city">
                                            <input id="orderCreateCity" type="text" placeholder="Ville">
                                        </label>
                                        <label data-i18n="admin.postalCode">
                                            <input id="orderCreatePostalCode" type="text" placeholder="Code postal">
                                        </label>
                                        <label data-i18n="admin.fiscalCode">
                                            <input id="orderCreateFiscalCode" type="text" placeholder="Matricule fiscal">
                                        </label>
                                        <label data-i18n="admin.representative">
                                            <input id="orderCreateRepresentative" type="text" placeholder="Representant">
                                        </label>
                                    </div>
                                </section>

                                <aside class="invoice-side-summary">
                                    <article class="invoice-side-box">
                                        <span data-i18n="admin.balance">Solde</span>
                                        <strong id="orderCreateSidebarBalance">0.000</strong>
                                    </article>
                                    <article class="invoice-side-box">
                                        <span data-i18n="admin.pending">Encours</span>
                                        <strong id="orderCreateSidebarPending">0.000</strong>
                                    </article>
                                    <article class="invoice-side-box">
                                        <span data-i18n="admin.dueDate">Echeance</span>
                                        <strong id="orderCreateSidebarDue">0.000</strong>
                                    </article>
                                    <article class="invoice-side-box">
                                        <span data-i18n="admin.commitment">Engagement</span>
                                        <strong id="orderCreateSidebarCommitment">0.000</strong>
                                    </article>
                                </aside>
                            </div>

                            <section class="invoice-observation-box">
                                <label data-i18n="admin.observations">
                                    <textarea id="orderCreateObservation" rows="2" placeholder="Observation, details livraison, note interne..."></textarea>
                                </label>
                            </section>

                            <section class="invoice-lines-card">
                                <div class="invoice-line-entry">
                                    <label data-i18n="admin.quickType">
                                        <select id="orderCreateQuickGroup">
                                            <option value="">Type optionnel</option>
                                            <option value="PRINCIPAL">PRINCIPAL</option>
                                            <option value="SMART">SMART</option>
                                            <option value="ENFANT">ENFANT</option>
                                        </select>
                                    </label>
                                    <label data-i18n="admin.profileLabel">
                                        <select id="orderCreateQuickSegment">
                                            <option value="">Profil optionnel</option>
                                            <option value="FEMME">FEMME</option>
                                            <option value="HOMME">HOMME</option>
                                            <option value="UNISEX">UNISEX</option>
                                            <option value="ENFANT">ENFANT</option>
                                        </select>
                                    </label>
                                    <label data-i18n="admin.product">
                                        <select id="orderCreateProduct">
                                            <option value="" data-i18n="admin.optionalChoice">Choix facultatif</option>
                                        </select>
                                    </label>
                                    <label data-i18n="admin.packageCount">
                                        <input id="orderCreatePackageCount" type="number" min="0" step="1" value="0">
                                    </label>
                                    <label data-i18n="admin.qty">
                                        <input id="orderCreateQty" type="number" min="1" step="1" value="1">
                                    </label>
                                    <label data-i18n="admin.stock">
                                        <input id="orderCreateStockPreview" type="text" value="0.00" readonly>
                                    </label>
                                    <label data-i18n="admin.price">
                                        <input id="orderCreateUnitPrice" type="number" min="0.001" step="0.001" value="0.000">
                                    </label>
                                    <label data-i18n="admin.discount">
                                        <input id="orderCreateItemDiscount" type="number" min="0" step="0.001" value="0.000">
                                    </label>
                                    <label data-i18n="admin.fodec">
                                        <input id="orderCreateItemFodec" type="number" min="0" step="0.001" value="0.000">
                                    </label>
                                    <label data-i18n="admin.consumptionTax">
                                        <input id="orderCreateItemConsumption" type="number" min="0" step="0.001" value="0.000">
                                    </label>
                                    <label data-i18n="admin.vat">
                                        <input id="orderCreateItemTva" type="number" min="0" step="0.001" value="19.000">
                                    </label>
                                    <button id="orderAddItemBtn" class="soft-btn invoice-add-line-btn" type="button"><span data-i18n="admin.add">Ajouter</span></button>
                                </div>

                                <div class="table-wrap invoice-lines-table-wrap">
                                    <table class="admin-table invoice-lines-table">
                                        <thead>
                                            <tr>
                                                <th data-i18n="admin.article">Article</th>
                                                <th data-i18n="admin.designation">Designation</th>
                                                <th data-i18n="admin.packageCount">Nb Col</th>
                                                <th data-i18n="admin.qty">Qte</th>
                                                <th data-i18n="admin.stock">Stock</th>
                                                <th data-i18n="admin.price">Prix</th>
                                                <th data-i18n="admin.discount">Rm%</th>
                                                <th data-i18n="admin.fodec">Fodec%</th>
                                                <th data-i18n="admin.consumptionTax">D.C %</th>
                                                <th data-i18n="admin.vat">TVA%</th>
                                                <th data-i18n="admin.totalHt">Total HT</th>
                                                <th data-i18n="admin.totalTtc">Total TTC</th>
                                                <th data-i18n="admin.action">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="orderCreateItemsBody">
                                            <tr><td colspan="13" data-i18n="admin.noProductAdded">Aucun produit ajoute pour le moment.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <div class="invoice-bottom-grid">
                                <section class="invoice-payment-box">
                                    <div class="invoice-payment-tabs">
                                        <span class="is-active" data-i18n="admin.payment">Paiement</span>
                                        <span data-i18n="admin.transport">Transport</span>
                                        <span data-i18n="admin.weightPackages">Poids/Colis</span>
                                    </div>
                                    <div class="invoice-payment-grid">
                                        <label data-i18n="admin.paymentMode">
                                            <select id="orderCreatePaymentMode">
                                                <option value="Espece" data-i18n="admin.cash">Espece</option>
                                                <option value="Cheque" data-i18n="admin.check">Cheque</option>
                                                <option value="Virement" data-i18n="admin.transfer">Virement</option>
                                                <option value="Traite" data-i18n="admin.draft">Traite</option>
                                            </select>
                                        </label>
                                        <label data-i18n="admin.paidAmount">
                                            <input id="orderCreatePaidAmount" type="number" min="0" step="0.001" value="0.000">
                                        </label>
                                        <label data-i18n="admin.pieceRef">
                                            <input id="orderCreatePieceRef" type="text" placeholder="Piece">
                                        </label>
                                        <label data-i18n="admin.bank">
                                            <input id="orderCreateBank" type="text" placeholder="Banque">
                                        </label>
                                        <label data-i18n="admin.dueDate">
                                            <input id="orderCreateDueDate" type="date">
                                        </label>
                                        <label data-i18n="admin.newBalance">
                                            <input id="orderCreateNewBalance" type="text" value="0.000" readonly>
                                        </label>
                                    </div>
                                </section>

                                <section class="invoice-total-box">
                                    <div class="invoice-total-grid">
                                        <div><span data-i18n="admin.fodecCict">Fodec/Cict</span><strong id="orderCreateTotalFodec">0.000</strong></div>
                                        <div><span data-i18n="admin.totalHt">Total H.T</span><strong id="orderCreateTotalHt">0.000</strong></div>
                                        <div><span data-i18n="admin.consumptionTax">Droit de consommation</span><strong id="orderCreateTotalConsumption">0.000</strong></div>
                                        <div><span data-i18n="admin.discount">Remise</span><strong id="orderCreateTotalDiscount">0.000</strong></div>
                                        <div><span data-i18n="admin.withholdingBase">Base retenue</span><strong id="orderCreateWithholdingBase">0.000</strong></div>
                                        <div><span data-i18n="admin.totalVat">Total TVA</span><strong id="orderCreateTotalTva">0.000</strong></div>
                                        <div><span data-i18n="admin.withholdingTax">Retenue source</span><strong id="orderCreateWithholdingAmount">0.000</strong></div>
                                        <div><span data-i18n="admin.totalPayable">Total a payer</span><strong id="orderCreateTotalTtc">0.000</strong></div>
                                    </div>
                                    <div class="invoice-total-footer">
                                        <span id="orderCreateTotal" class="order-create-total">0.00 DT</span>
                                        <button id="orderCreateSubmitBtn" class="primary-btn" type="submit"><span data-i18n="admin.saveOrder">Enregistrer la commande</span></button>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </form>
                </section>

                <section id="orderDetailPanel" class="admin-card admin-hidden">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.orderConsultation">Consultation commande</p>
                            <h3 id="orderDetailTitle" data-i18n="admin.orderDetail">Detail commande</h3>
                        </div>
                        <div class="section-actions">
                            <p id="orderDetailMessage" class="form-note"></p>
                            <button id="generateInvoiceFromOrderBtn" class="soft-btn admin-hidden" type="button"><span data-i18n="admin.generateInvoice">Generer facture</span></button>
                            <button id="exportOrderPdfBtn" class="soft-btn" type="button"><span data-i18n="admin.exportPdf">Exporter PDF</span></button>
                            <button id="hideOrderDetailBtn" class="soft-btn" type="button"><span data-i18n="admin.close">Fermer</span></button>
                        </div>
                    </div>
                    <div id="orderDetailSummary" class="inline-stats"></div>
                    <form id="orderEditForm" class="admin-form">
                        <input type="hidden" id="orderEditId">
                        <div class="form-grid">
                            <label data-i18n="admin.clientLastName"><input id="orderEditLastName" type="text" required></label>
                            <label data-i18n="admin.clientFirstName"><input id="orderEditFirstName" type="text" required></label>
                            <label data-i18n="admin.phone"><input id="orderEditPhone" type="text" required></label>
                            <label data-i18n="admin.perfumery"><input id="orderEditShop" type="text" required></label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit"><span data-i18n="admin.saveChanges">Enregistrer modifications</span></button>
                        </div>
                    </form>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th data-i18n="admin.product">Produit</th>
                                    <th data-i18n="admin.family">Famille</th>
                                    <th data-i18n="admin.quantity">Quantite</th>
                                    <th data-i18n="admin.price">Prix</th>
                                    <th data-i18n="admin.total">Total</th>
                                </tr>
                            </thead>
                            <tbody id="orderDetailItemsBody"></tbody>
                        </table>
                    </div>
                </section>
            </section>

            <section id="admin-view-documents" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.docMgmt">Gestion documents</p>
                            <h3 data-i18n="admin.invoicesOrders">Factures et bandes de commande</h3>
                            <p class="section-copy" data-i18n="admin.docMgmtDesc">Retrouvez tous les documents admin avec pagination, recherche rapide et actions directes par parfumerie, telephone, nom, prenom et date.</p>
                        </div>
                        <div class="section-actions orders-toolbar">
                            <input id="documentSearch" class="search-input" type="search" placeholder="Recherche globale document, facture, parfumerie..." data-i18n-placeholder="admin.searchDoc">
                            <select id="documentTypeFilter" class="search-input orders-filter">
                                <option value="ALL" data-i18n="admin.allTypes">Tous types</option>
                                <option value="DETAIL">BON_COMMANDE_SITE</option>
                                <option value="GROS">FACTURE_STOCK</option>
                            </select>
                            <input id="documentShopSearch" class="search-input orders-filter" type="search" placeholder="Parfumerie" data-i18n-placeholder="admin.perfumery">
                            <input id="documentPhoneSearch" class="search-input orders-filter" type="search" placeholder="Telephone" data-i18n-placeholder="admin.phone">
                            <input id="documentFirstNameSearch" class="search-input orders-filter" type="search" placeholder="Prenom" data-i18n-placeholder="admin.firstName">
                            <input id="documentLastNameSearch" class="search-input orders-filter" type="search" placeholder="Nom" data-i18n-placeholder="admin.lastName">
                            <label class="orders-date-filter">
                                <span data-i18n="admin.from">Du</span>
                                <input id="documentDateFrom" class="search-input orders-filter" type="date">
                            </label>
                            <label class="orders-date-filter">
                                <span data-i18n="admin.to">Au</span>
                                <input id="documentDateTo" class="search-input orders-filter" type="date">
                            </label>
                            <button id="documentFiltersResetBtn" class="soft-btn" type="button"><span data-i18n="admin.showAll">Tout afficher</span></button>
                        </div>
                    </div>
                    <div id="documentSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th data-i18n="admin.document">Document</th>
                                    <th data-i18n="admin.perfumery">Parfumerie</th>
                                    <th data-i18n="admin.contact">Contact</th>
                                    <th data-i18n="admin.phone">Telephone</th>
                                    <th data-i18n="admin.date">Date</th>
                                    <th data-i18n="admin.amount">Montant</th>
                                    <th data-i18n="admin.actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminDocumentsBody"></tbody>
                        </table>
                    </div>
                    <div id="documentsPagination" class="table-pagination"></div>
                </section>
            </section>

            <section id="admin-view-employees" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.hrMgmt">Gestion RH</p>
                            <h3 data-i18n="admin.employeesSalaries">Employes et salaires</h3>
                            <p class="section-copy" data-i18n="admin.employeesSalariesDesc">Consultez rapidement chaque employe, son statut et son salaire. Ajoutez un nouveau profil via le formulaire dedie.</p>
                        </div>
                        <div class="section-actions">
                            <input id="employeeSearch" class="search-input" type="search" placeholder="Rechercher un employe..." data-i18n-placeholder="admin.searchEmployee">
                            <button id="showEmployeeFormBtn" class="primary-btn" type="button"><span data-i18n="admin.addEmployee">Ajouter un employe</span></button>
                        </div>
                    </div>
                    <div id="employeeSectionStats" class="inline-stats"></div>
                    <div id="adminEmployeesGrid" class="employee-card-grid"></div>
                </section>

                <section id="employeeFormPanel" class="admin-card admin-hidden">
                    <div class="section-head">
                        <h3 data-i18n="admin.employeeForm">Formulaire employe</h3>
                        <div class="section-actions">
                            <p id="employeeFormMessage" class="form-note"></p>
                            <button id="hideEmployeeFormBtn" class="soft-btn" type="button"><span data-i18n="admin.backToList">Retour liste</span></button>
                        </div>
                    </div>
                    <form id="employeeForm" class="admin-form">
                        <div class="form-grid">
                            <label data-i18n="admin.firstName"><input id="employeeFirstName" type="text" required></label>
                            <label data-i18n="admin.lastName"><input id="employeeLastName" type="text" required></label>
                            <label data-i18n="admin.email"><input id="employeeEmail" type="email" required></label>
                            <label data-i18n="admin.phone"><input id="employeePhone" type="text" required></label>
                            <label data-i18n="admin.employeeCode"><input id="employeeCode" type="text" required></label>
                            <label data-i18n="admin.jobTitle"><input id="employeeJob" type="text" required></label>
                            <label data-i18n="admin.salaryDT"><input id="employeeSalary" type="number" min="0" step="0.01" required></label>
                            <label data-i18n="admin.hireDate"><input id="employeeHireDate" type="date" required></label>
                            <label data-i18n="admin.password"><input id="employeePassword" type="password" minlength="8" required></label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit"><span data-i18n="admin.addEmployee">Ajouter employe</span></button>
                        </div>
                    </form>
                </section>
            </section>

            <section id="admin-view-expenses" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.expenseTracking">Suivi des charges</p>
                            <h3 data-i18n="admin.expenseHistory">Historique des charges</h3>
                            <p class="section-copy" data-i18n="admin.expenseHistoryDesc">Affichez les charges existantes, recherchez-les et ouvrez le formulaire seulement quand vous voulez en ajouter ou modifier.</p>
                        </div>
                        <div class="section-actions">
                            <input id="expenseSearch" class="search-input" type="search" placeholder="Rechercher une charge..." data-i18n-placeholder="admin.searchExpense">
                            <button id="showExpenseFormBtn" class="primary-btn" type="button"><span data-i18n="admin.addExpense">Ajouter une charge</span></button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th data-i18n="admin.date">Date</th>
                                    <th data-i18n="admin.type">Type</th>
                                    <th data-i18n="admin.label">Libelle</th>
                                    <th data-i18n="admin.amount">Montant</th>
                                    <th data-i18n="admin.actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminExpensesBody"></tbody>
                        </table>
                    </div>
                    <div id="expensesPagination" class="table-pagination"></div>
                </section>

                <section id="expenseFormPanel" class="admin-card admin-hidden">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker" data-i18n="admin.expenseForm">Formulaire charge</p>
                            <h3 data-i18n="admin.addOrEditExpense">Ajouter ou modifier une charge</h3>
                        </div>
                        <div class="section-actions">
                            <p id="expenseFormMessage" class="form-note"></p>
                            <button id="hideExpenseFormBtn" class="soft-btn" type="button"><span data-i18n="admin.backToList">Retour liste</span></button>
                        </div>
                    </div>
                    <form id="expenseForm" class="admin-form">
                        <input type="hidden" id="expenseId">
                        <div class="form-grid">
                            <label data-i18n="admin.type"><select id="expenseType"><option value="RAW_MATERIAL" data-i18n="admin.rawMaterial">Matiere premiere</option><option value="SALARY" data-i18n="admin.salary">Salaire</option><option value="TRANSPORT" data-i18n="admin.transport">Transport</option><option value="RENT" data-i18n="admin.rent">Loyer</option><option value="OTHER" data-i18n="admin.other">Autre</option></select></label>
                            <label data-i18n="admin.label"><input id="expenseLabel" type="text" required></label>
                            <label data-i18n="admin.amountDT"><input id="expenseAmount" type="number" min="0" step="0.01" required></label>
                            <label data-i18n="admin.date"><input id="expenseDate" type="date" required></label>
                            <label class="full" data-i18n="admin.note"><input id="expenseNote" type="text"></label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit"><span data-i18n="admin.saveExpense">Enregistrer charge</span></button>
                            <button id="expenseResetBtn" class="soft-btn" type="button"><span data-i18n="admin.new">Nouvelle</span></button>
                        </div>
                    </form>
                </section>
            </section>
        </main>
    </div>

    <div id="paymentModal" class="payment-modal admin-hidden" aria-hidden="true">
        <div id="paymentModalBackdrop" class="payment-modal-backdrop"></div>
        <div class="payment-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
            <div class="payment-modal-head">
                <div>
                    <p class="section-kicker" data-i18n="admin.partialPayment">Paiement partiel</p>
                    <h3 id="paymentModalTitle" data-i18n="admin.recordPartialPayment">Enregistrer un paiement partiel</h3>
                </div>
                <button id="paymentModalCloseBtn" class="soft-btn" type="button"><span data-i18n="admin.close">Fermer</span></button>
            </div>
            <div class="payment-modal-body">
                <div class="payment-modal-stats">
                    <article class="payment-stat-card">
                        <span data-i18n="admin.alreadyPaid">Deja paye</span>
                        <strong id="paymentAlreadyPaid">0.00 DT</strong>
                    </article>
                    <article class="payment-stat-card">
                        <span data-i18n="admin.remainingToPay">Reste a payer</span>
                        <strong id="paymentRemaining">0.00 DT</strong>
                    </article>
                </div>
                <label class="payment-modal-field">
                    <span data-i18n="admin.amountPaidNow">Montant paye maintenant</span>
                    <input id="paymentAmountInput" class="search-input payment-modal-input" type="number" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00">
                </label>
                <p id="paymentModalMessage" class="form-note"></p>
            </div>
            <div class="payment-modal-actions">
                <button id="paymentModalCancelBtn" class="soft-btn" type="button"><span data-i18n="admin.cancel">Annuler</span></button>
                <button id="paymentModalConfirmBtn" class="primary-btn" type="button"><span data-i18n="admin.validatePayment">Valider le paiement</span></button>
            </div>
        </div>
    </div>

    <div id="overviewAccessModal" class="overview-lock-modal admin-hidden" aria-hidden="true">
        <div id="overviewAccessModalBackdrop" class="overview-lock-backdrop"></div>
        <div class="overview-lock-dialog" role="dialog" aria-modal="true" aria-labelledby="overviewAccessTitle">
            <div class="overview-lock-head">
                <div>
                    <p class="section-kicker" data-i18n="admin.secureAccess">Acces securise</p>
                    <h3 id="overviewAccessTitle" data-i18n="admin.openDashboard">Ouvrir le tableau de bord</h3>
                </div>
                <button id="overviewAccessCloseBtn" class="soft-btn" type="button"><span data-i18n="admin.close">Fermer</span></button>
            </div>
            <div class="overview-lock-body">
                <p class="section-copy" data-i18n="admin.enterAdminCode">Saisissez le code admin pour afficher la vue generale.</p>
                <label class="overview-lock-field">
                    <span data-i18n="admin.accessCode">Code d'acces</span>
                    <input id="overviewAccessInput" class="search-input overview-lock-input" type="password" inputmode="numeric" placeholder="Code">
                </label>
                <p id="overviewAccessMessage" class="form-note"></p>
            </div>
            <div class="overview-lock-actions">
                <button id="overviewAccessCancelBtn" class="soft-btn" type="button"><span data-i18n="admin.cancel">Annuler</span></button>
                <button id="overviewAccessConfirmBtn" class="primary-btn" type="button"><span data-i18n="admin.open">Ouvrir</span></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/assets/js/i18n.js"></script><script src="/assets/js/admin-app.js?v=999"></script>
</body>
</html>
HTML
        );

        return new Response($html);
    }
}


















