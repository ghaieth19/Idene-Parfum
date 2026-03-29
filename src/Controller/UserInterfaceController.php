<?php

namespace App\Controller;

use App\Support\AppContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserInterfaceController
{
    public function __construct(private readonly AppContext $app)
    {
    }

    #[Route('/dashboard', name: 'app_user_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        if (!$this->app->currentUserId()) {
            return new RedirectResponse('/auth');
        }

        if ($this->app->isAdminSession()) {
            return new RedirectResponse('/admin');
        }

        return new Response(<<<'HTML'
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IDENE PARFUM | Espace Client</title>
    <meta name="description" content="Espace client moderne pour commandes, factures, historique et profil.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/user-app.css?v=5">
</head>
<body>
    <button type="button" id="mobileSidebarToggle" class="mobile-sidebar-toggle hamburger-toggle" aria-label="Ouvrir ou fermer le menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div id="mobileSidebarBackdrop" class="mobile-sidebar-backdrop"></div>
    <div class="app-shell container-fluid px-3 px-lg-4">
        <aside id="clientSidebar" class="sidebar">
            <div class="mobile-sidebar-head">
                <button type="button" id="mobileSidebarClose" class="mobile-sidebar-close">Fermer</button>
            </div>
            <div class="brand brand-stack">
                <div class="brand-mark">
                    <img src="/assets/images/logo.png" alt="Logo Idene Parfum">
                </div>
                <div class="brand-copy">
                    <p class="brand-title">IDENE PARFUM</p>
                    <p class="brand-subtitle">Espace Client</p>
                </div>
            </div>

            <section class="sidebar-profile">
                <div class="sidebar-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div>
                    <p class="sidebar-user-label">Compte client</p>
                    <strong>Tableau de bord</strong>
                </div>
            </section>

            <nav class="side-menu">
                <a href="#home" class="active" data-view-link="home"><span class="menu-icon"><i class="bi bi-grid-1x2-fill"></i></span><span>Accueil</span></a>
                <div class="menu-group">
                    <button type="button" id="commandMenuToggle" class="menu-group-toggle">
                        <span class="menu-icon"><i class="bi bi-bag-check-fill"></i></span>
                        <span>Commandes</span>
                        <span class="menu-caret"><i class="bi bi-chevron-down"></i></span>
                    </button>
                    <div id="commandSubmenu" class="menu-submenu">
                        <a href="#commandes" data-view-link="commandes" data-workflow-step="catalogue">Passer une commande</a>
                        <a href="#commandes" data-view-link="commandes" data-workflow-step="historique">Voir historique des commandes</a>
                    </div>
                </div>
                <a href="#factures" data-view-link="factures"><span class="menu-icon"><i class="bi bi-receipt-cutoff"></i></span><span>Factures</span></a>
                <a href="#hors-stock" data-view-link="hors-stock"><span class="menu-icon"><i class="bi bi-exclamation-diamond-fill"></i></span><span>Hors stock</span></a>
                <a href="#profil" data-view-link="profil"><span class="menu-icon"><i class="bi bi-person-vcard-fill"></i></span><span>Mon Compte</span></a>
            </nav>
            <div class="sidebar-footer">
                <button type="button" id="themeToggle" class="menu-btn design-toggle active"><span class="menu-icon"><i class="bi bi-stars"></i></span><span>Mode clair</span></button>
                <button type="button" id="logoutBtn" class="logout-btn"><span class="menu-icon"><i class="bi bi-box-arrow-right"></i></span><span>Deconnexion</span></button>
            </div>
        </aside>

        <main class="main-content">
            <section class="topbar reveal">
                <div>
                    <p class="topbar-kicker">Experience UX repensee</p>
                    <h2>Tableau de bord client</h2>
                </div>
                <div class="topbar-badges">
                    <span>Commande fluide</span>
                    <span>Facture rapide</span>
                    <span>Profil centralise</span>
                </div>
            </section>

            <section id="view-home" class="view active">
                <section class="hero-panel reveal">
                    <div class="hero-copy">
                        <p class="hero-kicker">Maison de parfum en gros et en detail</p>
                        <h1>Un espace client clair, moderne et rassurant des la premiere visite.</h1>
                        <p class="hero-text">Retrouvez rapidement vos parfums, suivez vos commandes, consultez vos factures et gerez votre compte dans un espace clair et pratique.</p>
                        <div class="hero-actions">
                            <a href="#commandes" class="btn btn-primary" data-view-link="commandes" data-workflow-step="catalogue">Commander maintenant</a>
                            <a href="#factures" class="btn btn-ghost" data-view-link="factures">Voir mes factures</a>
                        </div>
                    </div>
                    <div class="hero-side">
                        <article class="hero-badge-card">
                            <span class="hero-badge-label">Nouveau parcours client</span>
                            <strong>Collection professionnelle</strong>
                            <p>Accedez a l'essentiel en un coup d'oeil: catalogue disponible, suivi des commandes, factures et informations de votre compte.</p>
                            <div class="hero-contact-block">
                                <p class="hero-contact-kicker">Contact direct</p>
                                <div class="hero-contact-links">
                                    <a href="tel:+21558606233"><i class="bi bi-telephone-fill"></i><span>+21558606233</span></a>
                                    <a href="tel:+21658367468"><i class="bi bi-telephone-fill"></i><span>+21658367468</span></a>
                                    <a href="mailto:idene.parfum@gmail.com"><i class="bi bi-envelope-fill"></i><span>idene.parfum@gmail.com</span></a>
                                </div>
                            </div>
                        </article>
                        <div class="hero-metrics">
                            <article class="metric-card">
                                <span>Catalogue</span>
                                <strong data-counter="205">0</strong>
                                <small>parfums actifs</small>
                            </article>
                            <article class="metric-card">
                                <span>Service</span>
                                <strong data-counter="36">0</strong>
                                <small>commandes ce mois</small>
                            </article>
                            <article class="metric-card">
                                <span>Suivi</span>
                                <strong data-counter="29">0</strong>
                                <small>factures payees</small>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="panel reveal home-panel">
                    <div class="panel-head">
                        <h2>Pourquoi choisir IDENE</h2>
                        <p class="section-note">Des blocs plus nets, une meilleure hierarchie visuelle et une lecture immediate des informations.</p>
                    </div>
                    <div class="home-grid home-grid-strong">
                        <article class="home-card accent-gold">
                            <h3>Approvisionnement fiable</h3>
                            <p>Une base de parfums structuree par gamme, segment et disponibilite pour simplifier les commandes des parfumeries.</p>
                        </article>
                        <article class="home-card accent-blue">
                            <h3>Suivi centralise</h3>
                            <p>Chaque utilisateur retrouve ses commandes, ses factures et l'historique de sa parfumerie dans un seul espace.</p>
                        </article>
                        <article class="home-card accent-red">
                            <h3>Gestion rapide</h3>
                            <p>Recherche dynamique, consultation du stock et generation de facture PDF sans perdre de temps.</p>
                        </article>
                    </div>
                </section>

                <section class="panel reveal">
                    <div class="panel-head">
                        <div>
                            <h2>Recherche Parfum</h2>
                            <p class="section-note">Offrez une consultation simple et rapide du catalogue avec une interface plus claire pour le client.</p>
                        </div>
                    </div>
                    <div class="search-row">
                        <select id="homeCategorySelect" class="pro-select">
                            <option value="ALL">Toutes categories</option>
                            <option value="PRINCIPAL">Principal</option>
                            <option value="SMART">Smart</option>
                            <option value="ENFANT">Enfant</option>
                        </select>
                        <input id="homePerfumeSearch" class="pro-search" type="search" placeholder="Rechercher un parfum par nom...">
                    </div>
                    <div class="perfume-search-grid" id="homePerfumeGrid"></div>
                    <p id="homePerfumeEmpty" class="muted">Chargement des parfums...</p>
                </section>
            </section>

            <section id="view-commandes" class="view">
                <section id="commandes" class="panel">
                <div class="panel-head">
                    <div>
                        <h2>Commandes Store</h2>
                        <p class="section-note">Chaque etape guide clairement le client: choix des parfums, coordonnees, facture et historique.</p>
                    </div>
                </div>

                <div class="workflow-tabs">
                    <button class="wf-tab active" data-step="catalogue">1. Parfums</button>
                    <button class="wf-tab" data-step="coordonnees">2. Coordonnees</button>
                    <button class="wf-tab" data-step="facture">3. Facture</button>
                    <button class="wf-tab" data-step="historique">4. Historique des commandes</button>
                </div>

                <section id="step-catalogue" class="wf-step active">
                    <div class="search-row">
                        <select id="storeCategory" class="pro-select">
                            <option value="ALL">Toutes categories</option>
                            <option value="PRINCIPAL_HOMME">Principal Homme</option>
                            <option value="PRINCIPAL_FEMME">Principal Femme</option>
                            <option value="SMART">Smart</option>
                            <option value="ENFANT">Enfant</option>
                        </select>
                        <input id="storeSearch" class="pro-search" type="search" placeholder="Rechercher un parfum...">
                    </div>

                    <div class="store-layout">
                        <section>
                            <div class="store-products" id="storeProducts"></div>
                            <div id="storePagination" class="table-pagination"></div>
                        </section>

                        <section class="cart-panel">
                            <h3>Panier</h3>
                            <div id="cartItems" class="cart-items"></div>
                            <p class="cart-total">Total: <strong id="cartTotal">0.00 DT</strong></p>
                            <button id="toCoordonneesBtn" class="btn btn-primary" type="button">Continuer</button>
                        </section>
                    </div>
                </section>

                <section id="step-coordonnees" class="wf-step">
                    <div class="checkout-grid">
                        <label>Nom
                            <input id="clientLastName" class="pro-search" type="text" placeholder="Nom">
                        </label>
                        <label>Prenom
                            <input id="clientFirstName" class="pro-search" type="text" placeholder="Prenom">
                        </label>
                        <label>Telephone
                            <input id="clientPhone" class="pro-search" type="text" placeholder="+213 ...">
                        </label>
                        <label>Nom de la parfumerie
                            <input id="clientShop" class="pro-search" type="text" placeholder="Nom parfumerie">
                        </label>
                        <button id="backToStoreBtn" class="btn btn-ghost" type="button">Retour</button>
                        <button id="toFactureBtn" class="btn btn-primary" type="button">Passer a la facture</button>
                    </div>
                </section>

                <section id="step-facture" class="wf-step">
                    <section id="invoiceBox" class="invoice-box">
                        <div class="invoice-head">
                            <h3>Facture</h3>
                            <p id="invoiceNumber">-</p>
                        </div>
                        <div id="invoiceClient"></div>
                        <div id="invoiceLines" class="invoice-lines"></div>
                        <p class="invoice-total">Total facture: <strong id="invoiceTotal">0.00 DT</strong></p>
                        <div class="invoice-actions">
                            <button id="editCoordonneesBtn" class="btn btn-ghost" type="button">Modifier coordonnees</button>
                            <button id="exportInvoicePdfBtn" class="btn btn-ghost" type="button">Exporter PDF</button>
                            <button id="confirmOrderBtn" class="btn btn-primary" type="button">Confirmer commande</button>
                        </div>
                    </section>
                </section>

                <section id="step-historique" class="wf-step">
                    <div class="panel-head">
                        <h2>Historique des commandes</h2>
                        <input id="ordersSearch" class="pro-search" type="search" placeholder="Rechercher une commande...">
                    </div>
                    <div class="orders-table-wrap">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Commande</th>
                                    <th>Facture</th>
                                    <th>Montant</th>
                                    <th>Coordonnees facture</th>
                                    <th>Livraison</th>
                                    <th>Paiement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody"></tbody>
                        </table>
                        <p id="ordersEmpty" class="muted">Aucune commande pour le moment.</p>
                    </div>
                </section>
                
                </section>
            </section>

            <section id="view-factures" class="view">
                <section id="factures" class="panel">
                <div class="panel-head">
                    <div>
                        <h2>Factures</h2>
                        <p class="section-note">Retrouvez tous les documents dans une presentation plus ordonnee et plus simple a filtrer.</p>
                    </div>
                    <div class="facture-filters">
                        <input id="facturesDateSearch" class="pro-search" type="date">
                        <input id="facturesSearch" class="pro-search" type="search" placeholder="Rechercher numero/client/montant...">
                    </div>
                </div>
                <div class="orders-table-wrap">
                    <table class="orders-table" id="facturesTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Numero</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Export</th>
                            </tr>
                        </thead>
                        <tbody id="facturesTableBody"></tbody>
                    </table>
                    <p id="facturesEmpty" class="muted">Aucune facture pour ce filtre.</p>
                </div>
                </section>
            </section>

            <section id="view-hors-stock" class="view">
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h2>Produits hors stock</h2>
                            <p class="section-note">Visualisez rapidement les references indisponibles et anticipez vos prochaines commandes.</p>
                        </div>
                    </div>
                    <div id="outOfStockGrid" class="perfume-search-grid"></div>
                    <p id="outOfStockEmpty" class="muted">Chargement des produits hors stock...</p>
                </section>
            </section>

            <section id="view-profil" class="view">
                <section id="profil" class="panel">
                <div class="panel-head">
                    <div>
                        <h2>Mon Compte</h2>
                        <p class="section-note">Mettez a jour les informations de la parfumerie dans un espace plus propre et plus rassurant.</p>
                    </div>
                    <input id="profilSearch" class="pro-search" type="search" placeholder="Rechercher un champ profil...">
                </div>
                <form class="profile-form">
                    <p id="profileMessage" class="helper-text form-message profile-message"></p>
                    <label class="searchable">Nom<input id="profileLastName" type="text" value=""></label>
                    <label class="searchable">Prenom<input id="profileFirstName" type="text" value=""></label>
                    <label class="searchable">Parfumerie<input id="profileShopName" type="text" value=""></label>
                    <label class="searchable">Telephone<input id="profilePhone" type="text" value=""></label>
                    <label class="searchable">Localisation<input id="profileLocation" type="text" value=""></label>
                    <label class="searchable">Email<input id="profileEmail" type="email" value=""></label>
                    <button id="saveProfileBtn" class="btn btn-primary" type="button">Enregistrer</button>
                </form>
                </section>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/assets/js/user-app.js"></script>
</body>
</html>
HTML);
    }
}


