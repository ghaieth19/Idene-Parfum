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
    <link rel="stylesheet" href="/assets/css/admin-app.css?v=5">
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
                <strong>Navigation</strong>
            </div>
            <div class="admin-brand">
                <img src="/assets/images/logo.png" alt="Logo Idene Parfum">
                <div>
                    <p class="eyebrow">Administration</p>
                    <h1>IDENE PARFUM</h1>
                    <p class="muted">Gestion centrale</p>
                </div>
            </div>

            <section class="admin-sidebar-highlight">
                <p class="eyebrow">Pilotage clair</p>
                <strong>Back-office plus lisible</strong>
                <p class="muted">Le design met en avant les bons indicateurs et simplifie l'acces aux actions principales.</p>
            </section>

            <nav class="admin-nav">
                <button class="admin-link active" data-admin-view="overview">Vue generale</button>
                <button class="admin-link" data-admin-view="account">Mon compte</button>
                <button class="admin-link" data-admin-view="products">Produits</button>
                <button class="admin-link" data-admin-view="raw-materials">Stock matieres</button>
                <button class="admin-link" data-admin-view="orders">Commandes</button>
                <button class="admin-link" data-admin-view="users">Users</button>
                <button class="admin-link" data-admin-view="employees">Employes</button>
                <button class="admin-link" data-admin-view="expenses">Charges</button>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-profile">
                    <span class="role-pill">__ADMIN_ROLE__</span>
                    <strong>__ADMIN_NAME__</strong>
                </div>
                <a href="/dashboard" class="ghost-link">Vue client</a>
                <button id="adminLogoutBtn" class="danger-btn" type="button">Deconnexion</button>
            </div>
        </aside>

        <main class="admin-main">
            <section class="admin-topbar">
                <div>
                    <p class="eyebrow">Administration UX</p>
                    <h2 class="admin-topbar-title">Tableau de pilotage</h2>
                </div>
                <div class="admin-topbar-tags">
                    <span>Vision globale</span>
                    <span>Actions rapides</span>
                    <span>Design pro</span>
                </div>
            </section>

            <section id="admin-view-overview" class="admin-view active">
                <section class="admin-hero">
                    <div>
                        <p class="eyebrow">Pilotage financier et operationnel</p>
                        <h2>Dashboard admin plus clair et plus professionnel</h2>
                        <p class="lead">Recette du jour, chiffre du mois, charges, achats de matieres premieres, stock et benefice estime dans une presentation plus nette et plus facile a lire.</p>
                    </div>
                    <div class="hero-accent">
                        <span class="hero-badge">Nouvelle direction visuelle</span>
                        <strong>Flux admin complet</strong>
                        <p class="section-copy">Une interface organisee pour suivre les commandes, la rentabilite et les operations sans surcharge visuelle.</p>
                    </div>
                </section>

                <section class="metrics-grid" id="adminMetricsGrid"></section>

                <section class="admin-grid two">
                    <article class="admin-card">
                        <div class="section-head">
                            <h3>Commandes recentes</h3>
                        </div>
                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Commande</th>
                                        <th>Client</th>
                                        <th>Montant</th>
                                        <th>Livraison</th>
                                        <th>Paiement</th>
                                    </tr>
                                </thead>
                                <tbody id="adminRecentOrdersBody"></tbody>
                            </table>
                        </div>
                    </article>

                    <article class="admin-card">
                        <div class="section-head">
                            <h3>Activite commandes</h3>
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
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Libelle</th>
                                        <th>Montant</th>
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

            <section id="admin-view-products" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker">Catalogue central</p>
                            <h3>Produits et stock</h3>
                            <p class="section-copy">Consultez le catalogue, surveillez le stock par bouteilles et les matieres premieres, puis ouvrez le formulaire pour ajouter ou modifier un produit.</p>
                        </div>
                        <div class="section-actions">
                            <input id="productSearch" class="search-input" type="search" placeholder="Rechercher un produit...">
                            <button id="showProductFormBtn" class="primary-btn" type="button">Ajouter un produit</button>
                        </div>
                    </div>
                    <div id="productSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Famille</th>
                                    <th>Prix</th>
                                    <th>Stock bouteilles</th>
                                    <th>Stock base</th>
                                    <th>Actions</th>
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
                            <p class="section-kicker">Formulaire produit</p>
                            <h3>Ajouter ou modifier un produit</h3>
                        </div>
                        <div class="section-actions">
                            <p id="productFormMessage" class="form-note"></p>
                            <button id="hideProductFormBtn" class="soft-btn" type="button">Retour liste</button>
                        </div>
                    </div>
                    <form id="productForm" class="admin-form">
                        <input type="hidden" id="productId">
                        <div class="form-grid">
                            <label>Categorie<select id="productCatalogGroup"><option value="PRINCIPAL">PRINCIPAL</option><option value="SMART">SMART</option><option value="ENFANT">ENFANT</option><option value="LUXE">LUXE</option><option value="MIXTE">MIXTE</option><option value="AUTRE">AUTRE</option></select></label>
                            <label>Segment<select id="productSegment"><option value="HOMME">HOMME</option><option value="FEMME">FEMME</option><option value="UNISEX">UNISEX</option><option value="ENFANT">ENFANT</option><option value="MIXTE">MIXTE</option><option value="AUTRE">AUTRE</option></select></label>
                            <label>Code<input id="productCode" type="text"></label>
                            <label>Nom parfum<input id="productName" type="text" required></label>
                            <label>Prix DT<input id="productPrice" type="number" min="0" step="0.01" required></label>
                            <label>Stock bouteilles<input id="productStock" type="number" min="0" step="1" required></label>
                            <label>Seuil alerte bouteilles<input id="productAlert" type="number" min="0" step="1"></label>
                            <label>Stock matieres premieres (ml)<input id="productRawMaterialStock" type="number" min="0" step="0.01"></label>
                            <label>Seuil alerte matieres premieres (ml)<input id="productRawMaterialAlert" type="number" min="0" step="0.01"></label>
                            <label>SKU<input id="productSku" type="text"></label>
                            <label>Barcode<input id="productBarcode" type="text"></label>
                            <label>Actif<select id="productActive"><option value="1">Oui</option><option value="0">Non</option></select></label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit">Enregistrer produit</button>
                            <button id="productResetBtn" class="soft-btn" type="button">Nouveau</button>
                        </div>
                    </form>
                </section>
            </section>

            <section id="admin-view-account" class="admin-view">
                <section class="admin-card admin-account-hero">
                    <div>
                        <p class="section-kicker">Acces biometrique admin</p>
                        <h3>Controlez les visages autorises avec une presentation plus professionnelle</h3>
                        <p class="section-copy">Enregistrez proprement chaque collaborateur autorise sur le compte admin, gardez une vue claire des acces actifs et ouvrez la camera dans une fenetre plus elegante.</p>
                    </div>
                    <div class="admin-account-hero-badge">
                        <span class="hero-badge">Securite partagee</span>
                        <strong>Equipe admin</strong>
                        <p class="muted">Un meme compte peut etre utilise par plusieurs personnes autorisees, chacune avec son visage enregistre.</p>
                    </div>
                </section>

                <section class="admin-grid two">
                    <article class="admin-card">
                        <div class="section-head">
                            <div>
                                <p class="section-kicker">Compte admin</p>
                                <h3>Informations du compte</h3>
                                <p class="section-copy">Retrouvez les informations du compte administrateur actuellement connecte.</p>
                            </div>
                        </div>
                        <div id="adminAccountSummary" class="inline-stats"></div>
                    </article>

                    <article class="admin-card">
                        <div class="section-head">
                            <div>
                                <p class="section-kicker">Acces partages</p>
                                <h3>Visages autorises</h3>
                                <p class="section-copy">Ajoutez plusieurs visages pour que plusieurs personnes de la societe puissent acceder au meme compte admin.</p>
                            </div>
                        </div>
                        <div class="admin-face-composer">
                            <label class="admin-face-label">
                                <span>Nom de la personne ou du poste</span>
                                <input id="adminFaceLabel" class="search-input" type="text" maxlength="120" placeholder="Ex: Directeur, Responsable boutique, Comptable">
                            </label>
                            <div class="admin-face-composer-actions">
                                <button id="adminFaceOpenBtn" class="primary-btn" type="button">Ajouter un visage</button>
                            </div>
                        </div>
                        <div id="adminInlineFaceCapture" class="admin-inline-face-capture admin-hidden">
                            <div class="admin-inline-face-head">
                                <div>
                                    <p class="employee-label">Camera integree</p>
                                    <h4>Cadrez le visage puis capturez</h4>
                                </div>
                                <span class="face-profile-chip">Ajout en cours</span>
                            </div>
                            <div class="camera-shell admin-inline-camera-shell">
                                <video id="adminFaceVideo" class="face-video" autoplay playsinline muted></video>
                                <div class="camera-frame"></div>
                            </div>
                            <canvas id="adminFaceCanvas" class="face-canvas" width="320" height="240"></canvas>
                            <div class="face-modal-actions admin-inline-face-actions">
                                <button id="adminCaptureFaceBtn" type="button" class="primary-btn">Capturer ce visage</button>
                                <button id="adminCloseFaceModalBtn" type="button" class="soft-btn">Annuler</button>
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
                            <p class="section-kicker">Stock parfums</p>
                            <h3>Tableau de tous les parfums</h3>
                            <p class="section-copy">Consultez tous les parfums et laissez le directeur renseigner le stock de base uniquement en nombre de bouteilles.</p>
                        </div>
                        <div class="section-actions">
                            <input id="perfumeStockSearch" class="search-input" type="search" placeholder="Rechercher un parfum...">
                            <select id="perfumeStockCategoryFilter" class="search-input">
                                <option value="ALL">Toutes categories</option>
                                <option value="PRINCIPAL">PRINCIPAL</option>
                                <option value="SMART">SMART</option>
                                <option value="ENFANT">ENFANT</option>
                            </select>
                            <select id="perfumeStockSort" class="search-input">
                                <option value="name_asc">Tri: nom A-Z</option>
                                <option value="name_desc">Tri: nom Z-A</option>
                                <option value="category_asc">Tri: categorie</option>
                                <option value="base_desc">Tri: stock base desc</option>
                                <option value="base_asc">Tri: stock base asc</option>
                            </select>
                        </div>
                    </div>
                    <div id="perfumeStockStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Parfum</th>
                                    <th>Categorie</th>
                                    <th>Code</th>
                                    <th>Stock base</th>
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
                            <p class="section-kicker">Direction achats et stock</p>
                            <h3>Stock et matieres premieres</h3>
                            <p class="section-copy">Ajoutez les bases de parfum, alcool, colorants, bouteilles fragiles, tickets et bouchons dans un tableau editable avec calcul automatique du total en dinars.</p>
                        </div>
                        <div class="section-actions">
                            <input id="rawMaterialSearch" class="search-input" type="search" placeholder="Rechercher base, alcool, bouteille...">
                            <button id="rawMaterialAddBtn" class="primary-btn" type="button">Ajouter une ligne</button>
                        </div>
                    </div>
                    <div id="rawMaterialSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table raw-material-table">
                            <thead>
                                <tr>
                                    <th>Categorie</th>
                                    <th>Article</th>
                                    <th>Unite</th>
                                    <th>Stock</th>
                                    <th>Alerte</th>
                                    <th>Cout unitaire</th>
                                    <th>Total</th>
                                    <th>Date achat</th>
                                    <th>Fournisseur</th>
                                    <th>Note</th>
                                    <th>Actions</th>
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
                            <p class="section-kicker">Gestion des comptes</p>
                            <h3>Users et profils</h3>
                            <p class="section-copy">Consultez les profils utilisateurs, modifiez leurs informations et desactivez les comptes si necessaire.</p>
                        </div>
                        <input id="userSearch" class="search-input" type="search" placeholder="Rechercher un user...">
                    </div>
                    <div id="userSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nom complet</th>
                                    <th>Role</th>
                                    <th>Parfumerie</th>
                                    <th>Contact</th>
                                    <th>Localisation</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
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
                            <p class="section-kicker">Consultation user</p>
                            <h3 id="userDetailTitle">Profil utilisateur</h3>
                        </div>
                        <div class="section-actions">
                            <p id="userFormMessage" class="form-note"></p>
                            <button id="hideUserDetailBtn" class="soft-btn" type="button">Fermer</button>
                        </div>
                    </div>
                    <form id="userEditForm" class="admin-form">
                        <input type="hidden" id="userEditId">
                        <div class="form-grid">
                            <label>Prenom<input id="userEditFirstName" type="text" required></label>
                            <label>Nom<input id="userEditLastName" type="text" required></label>
                            <label>Parfumerie<input id="userEditShop" type="text" required></label>
                            <label>Telephone<input id="userEditPhone" type="text" required></label>
                            <label>Localisation<input id="userEditLocation" type="text" required></label>
                            <label>Email<input id="userEditEmail" type="email" required></label>
                            <label>Statut
                                <select id="userEditActive">
                                    <option value="1">Actif</option>
                                    <option value="0">Inactif</option>
                                </select>
                            </label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit">Enregistrer user</button>
                        </div>
                    </form>
                </section>
            </section>

            <section id="admin-view-orders" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker">Pilotage commandes</p>
                            <h3>Commandes et paiements</h3>
                            <p class="section-copy">Consultez les commandes, voyez le total paye, la recette du jour et gerez les actions de suivi sur chaque dossier.</p>
                        </div>
                        <div class="section-actions orders-toolbar">
                            <input id="orderSearch" class="search-input" type="search" placeholder="Rechercher commande, client, parfumerie, facture...">
                            <select id="orderShopFilter" class="search-input orders-filter">
                                <option value="ALL">Toutes parfumeries</option>
                            </select>
                            <select id="orderStatusFilter" class="search-input orders-filter">
                                <option value="ALL">Tous statuts livraison</option>
                                <option value="CONFIRMEE">CONFIRMEE</option>
                                <option value="EN_PREPARATION">EN_PREPARATION</option>
                                <option value="EXPEDIEE">EXPEDIEE</option>
                                <option value="LIVREE">LIVREE</option>
                                <option value="ANNULEE">ANNULEE</option>
                            </select>
                            <select id="orderInvoiceFilter" class="search-input orders-filter">
                                <option value="ALL">Tous statuts paiement</option>
                                <option value="NON_PAYE">NON_PAYE</option>
                                <option value="PARTIEL">PARTIEL</option>
                                <option value="PAYE">PAYE</option>
                            </select>
                            <label class="orders-date-filter">
                                <span>Du</span>
                                <input id="orderDateFrom" class="search-input orders-filter" type="date">
                            </label>
                            <label class="orders-date-filter">
                                <span>Au</span>
                                <input id="orderDateTo" class="search-input orders-filter" type="date">
                            </label>
                            <button id="showOrderCreateBtn" class="primary-btn" type="button">Ajouter une commande</button>
                            <button id="orderFiltersResetBtn" class="soft-btn" type="button">Tout afficher</button>
                        </div>
                    </div>
                    <div id="orderSectionStats" class="inline-stats"></div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Commande</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Livraison</th>
                                    <th>Paiement</th>
                                    <th>Actions</th>
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
                            <p class="section-kicker">Creation commande admin</p>
                            <h3>Creer une commande pour une parfumerie</h3>
                            <p class="section-copy">Choisissez une parfumerie existante, ajoutez les parfums souhaites puis enregistrez la commande sous son compte.</p>
                        </div>
                        <div class="section-actions">
                            <p id="orderCreateMessage" class="form-note"></p>
                            <button id="hideOrderCreateBtn" class="soft-btn" type="button">Fermer</button>
                        </div>
                    </div>

                    <form id="orderCreateForm" class="admin-form">
                        <div class="form-grid">
                            <label>Nom de la parfumerie
                                <input id="orderCreateShop" type="search" list="orderCreateShopList" placeholder="Chercher une parfumerie..." required>
                                <datalist id="orderCreateShopList"></datalist>
                            </label>
                            <label>Recherche parfum
                                <input id="orderCreateProductSearch" type="search" placeholder="Chercher un parfum...">
                            </label>
                            <label>Produit
                                <select id="orderCreateProduct"></select>
                            </label>
                            <label>Quantite
                                <input id="orderCreateQty" type="number" min="1" step="1" value="1">
                            </label>
                        </div>

                        <div class="form-actions">
                            <button id="orderAddItemBtn" class="soft-btn" type="button">Ajouter au panier</button>
                            <span id="orderCreateTotal" class="order-create-total">0.00 DT</span>
                        </div>

                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Categorie</th>
                                        <th>Prix</th>
                                        <th>Quantite</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="orderCreateItemsBody">
                                    <tr><td colspan="6">Aucun produit ajoute pour le moment.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="form-actions">
                            <button class="primary-btn" type="submit">Enregistrer la commande</button>
                        </div>
                    </form>
                </section>

                <section id="orderDetailPanel" class="admin-card admin-hidden">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker">Consultation commande</p>
                            <h3 id="orderDetailTitle">Detail commande</h3>
                        </div>
                        <div class="section-actions">
                            <p id="orderDetailMessage" class="form-note"></p>
                            <button id="exportOrderPdfBtn" class="soft-btn" type="button">Exporter PDF</button>
                            <button id="hideOrderDetailBtn" class="soft-btn" type="button">Fermer</button>
                        </div>
                    </div>
                    <div id="orderDetailSummary" class="inline-stats"></div>
                    <form id="orderEditForm" class="admin-form">
                        <input type="hidden" id="orderEditId">
                        <div class="form-grid">
                            <label>Nom client<input id="orderEditLastName" type="text" required></label>
                            <label>Prenom client<input id="orderEditFirstName" type="text" required></label>
                            <label>Telephone<input id="orderEditPhone" type="text" required></label>
                            <label>Parfumerie<input id="orderEditShop" type="text" required></label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit">Enregistrer modifications</button>
                        </div>
                    </form>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Famille</th>
                                    <th>Quantite</th>
                                    <th>Prix</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="orderDetailItemsBody"></tbody>
                        </table>
                    </div>
                </section>
            </section>

            <section id="admin-view-employees" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker">Gestion RH</p>
                            <h3>Employes et salaires</h3>
                            <p class="section-copy">Consultez rapidement chaque employe, son statut et son salaire. Ajoutez un nouveau profil via le formulaire dedie.</p>
                        </div>
                        <div class="section-actions">
                            <input id="employeeSearch" class="search-input" type="search" placeholder="Rechercher un employe...">
                            <button id="showEmployeeFormBtn" class="primary-btn" type="button">Ajouter un employe</button>
                        </div>
                    </div>
                    <div id="employeeSectionStats" class="inline-stats"></div>
                    <div id="adminEmployeesGrid" class="employee-card-grid"></div>
                </section>

                <section id="employeeFormPanel" class="admin-card admin-hidden">
                    <div class="section-head">
                        <h3>Formulaire employe</h3>
                        <div class="section-actions">
                            <p id="employeeFormMessage" class="form-note"></p>
                            <button id="hideEmployeeFormBtn" class="soft-btn" type="button">Retour liste</button>
                        </div>
                    </div>
                    <form id="employeeForm" class="admin-form">
                        <div class="form-grid">
                            <label>Prenom<input id="employeeFirstName" type="text" required></label>
                            <label>Nom<input id="employeeLastName" type="text" required></label>
                            <label>Email<input id="employeeEmail" type="email" required></label>
                            <label>Telephone<input id="employeePhone" type="text" required></label>
                            <label>Code employe<input id="employeeCode" type="text" required></label>
                            <label>Poste<input id="employeeJob" type="text" required></label>
                            <label>Salaire DT<input id="employeeSalary" type="number" min="0" step="0.01" required></label>
                            <label>Date embauche<input id="employeeHireDate" type="date" required></label>
                            <label>Mot de passe<input id="employeePassword" type="password" minlength="8" required></label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit">Ajouter employe</button>
                        </div>
                    </form>
                </section>
            </section>

            <section id="admin-view-expenses" class="admin-view">
                <section class="admin-card">
                    <div class="section-head">
                        <div>
                            <p class="section-kicker">Suivi des charges</p>
                            <h3>Historique des charges</h3>
                            <p class="section-copy">Affichez les charges existantes, recherchez-les et ouvrez le formulaire seulement quand vous voulez en ajouter ou modifier.</p>
                        </div>
                        <div class="section-actions">
                            <input id="expenseSearch" class="search-input" type="search" placeholder="Rechercher une charge...">
                            <button id="showExpenseFormBtn" class="primary-btn" type="button">Ajouter une charge</button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Libelle</th>
                                    <th>Montant</th>
                                    <th>Actions</th>
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
                            <p class="section-kicker">Formulaire charge</p>
                            <h3>Ajouter ou modifier une charge</h3>
                        </div>
                        <div class="section-actions">
                            <p id="expenseFormMessage" class="form-note"></p>
                            <button id="hideExpenseFormBtn" class="soft-btn" type="button">Retour liste</button>
                        </div>
                    </div>
                    <form id="expenseForm" class="admin-form">
                        <input type="hidden" id="expenseId">
                        <div class="form-grid">
                            <label>Type<select id="expenseType"><option value="RAW_MATERIAL">Matiere premiere</option><option value="SALARY">Salaire</option><option value="TRANSPORT">Transport</option><option value="RENT">Loyer</option><option value="OTHER">Autre</option></select></label>
                            <label>Libelle<input id="expenseLabel" type="text" required></label>
                            <label>Montant DT<input id="expenseAmount" type="number" min="0" step="0.01" required></label>
                            <label>Date<input id="expenseDate" type="date" required></label>
                            <label class="full">Note<input id="expenseNote" type="text"></label>
                        </div>
                        <div class="form-actions">
                            <button class="primary-btn" type="submit">Enregistrer charge</button>
                            <button id="expenseResetBtn" class="soft-btn" type="button">Nouvelle</button>
                        </div>
                    </form>
                </section>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/assets/js/admin-app.js?v=2"></script>
</body>
</html>
HTML
        );

        return new Response($html);
    }
}
