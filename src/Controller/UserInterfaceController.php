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

        $userId = $this->app->currentUserId();
        $db     = $this->app->db();

        $user = $db->prepare("SELECT first_name, last_name, email, phone, perfume_shop_name FROM users WHERE id = :id");
        $user->execute(['id' => $userId]);
        $userData = $user->fetch();

        $firstName = htmlspecialchars($userData['first_name'] ?? 'Client', ENT_QUOTES);
        $lastName  = htmlspecialchars($userData['last_name'] ?? '', ENT_QUOTES);
        $email     = htmlspecialchars($userData['email'] ?? '', ENT_QUOTES);
        $phone     = htmlspecialchars($userData['phone'] ?? '', ENT_QUOTES);
        $shopName  = htmlspecialchars($userData['perfume_shop_name'] ?? '', ENT_QUOTES);

        return new Response(<<<HTML
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espace Client | IDENE PARFUM</title>
    <meta name="description" content="Passez commande d'huiles de parfum en vrac depuis votre espace professionnel IDENE.">
    <link rel="icon" type="image/png" href="/assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/idene-design-system.css?v=600">
    <link rel="stylesheet" href="/assets/css/user-app.css?v=600">
    <link rel="stylesheet" href="/assets/css/client-ecommerce.css?v=600">
</head>
<body class="ecommerce-layout">

    <!-- ═══ Top Navbar ═══ -->
    <header class="eco-nav">
        <div class="eco-nav-container">
            <a href="/accueil" class="eco-brand">
                <img src="/assets/images/logo.png" alt="IDENE">
                <span>IDENE</span>
            </a>
            
            <!-- Categories removed -->
            <nav class="eco-nav-links" style="display: none;">
            </nav>

            <div class="eco-nav-actions">
                <div class="eco-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="search" placeholder="Rechercher..." id="shopSearch">
                </div>
                
                <div class="dropdown-wrap">
                    <button class="icon-btn profile-btn" id="profileDropdownBtn">
                        <i class="bi bi-person"></i>
                    </button>
                    <div class="dropdown-menu" id="profileDropdown">
                        <div class="dropdown-header">
                            <strong>{$firstName} {$lastName}</strong>
                            <span>{$shopName}</span>
                        </div>
                        <a href="#dashboard" onclick="switchView('dashboard'); window.closeProfileDropdown();"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
                        <a href="#orders" onclick="switchView('orders'); window.closeProfileDropdown();"><i class="bi bi-box"></i> Mes commandes</a>
                        <a href="#invoices" onclick="switchView('invoices'); window.closeProfileDropdown();"><i class="bi bi-file-text"></i> Mes factures</a>
                        <a href="#profile" onclick="switchView('profile'); window.closeProfileDropdown();"><i class="bi bi-gear"></i> Paramètres</a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="text-danger" onclick="window.logoutUser(); return false;"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                    </div>
                </div>

                <button class="icon-btn cart-toggle-btn" id="floatingCartBtn">
                    <i class="bi bi-bag"></i>
                    <span class="cart-badge" id="floatingCartCount">0</span>
                </button>
            </div>
            
            <button class="mobile-nav-toggle" id="mobileNavToggle"><i class="bi bi-list"></i></button>
        </div>
        
        <!-- Mobile Dropdown Menu -->
        <div class="eco-mobile-menu" id="ecoMobileMenu">
            <div class="mob-search">
                <i class="bi bi-search"></i>
                <input type="search" placeholder="Rechercher..." id="shopSearchMobile">
            </div>
            <!-- Category links removed -->
            <div class="mob-links" style="display: none;">
            </div>
        </div>
    </header>

    <!-- ═══ Mobile bottom nav ═══ -->
    <nav class="mobile-bottom-nav">
        <button onclick="switchView('dashboard')" data-view="dashboard"><i class="bi bi-grid-1x2"></i><br>Stats</button>
        <button onclick="switchView('shop')" class="active" data-view="shop"><i class="bi bi-shop"></i><br>Boutique</button>
        <button onclick="switchView('orders')" data-view="orders"><i class="bi bi-box"></i><br>Commandes</button>
        <button onclick="switchView('invoices')" data-view="invoices"><i class="bi bi-receipt"></i><br>Factures</button>
        <button onclick="switchView('profile')" data-view="profile"><i class="bi bi-person"></i><br>Profil</button>
    </nav>

    <!-- ═══ Main Content ═══ -->
    <main class="eco-main">

        <!-- ═══ DASHBOARD VIEW ═══ -->
        <div id="viewDashboard" class="view">
            <div class="eco-page-header">
                <h2><i class="bi bi-grid-1x2"></i> Tableau de bord</h2>
                <p class="text-muted">Bienvenue, <strong>{$firstName}</strong> — {$shopName}</p>
            </div>

            <!-- KPI Cards -->
            <div class="dash-kpi-grid" id="dashKpiGrid">
                <div class="dash-kpi-card">
                    <div class="dash-kpi-icon" style="background:var(--primary-50);color:var(--primary-600)"><i class="bi bi-bag-check"></i></div>
                    <div class="dash-kpi-body"><span class="dash-kpi-label">Commandes</span><strong class="dash-kpi-value" id="kpiOrders">—</strong></div>
                </div>
                <div class="dash-kpi-card">
                    <div class="dash-kpi-icon" style="background:#FFF3E0;color:#E65100"><i class="bi bi-currency-exchange"></i></div>
                    <div class="dash-kpi-body"><span class="dash-kpi-label">Montant total</span><strong class="dash-kpi-value" id="kpiTotal">—</strong></div>
                </div>
                <div class="dash-kpi-card">
                    <div class="dash-kpi-icon" style="background:#FBE9E7;color:#BF360C"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="dash-kpi-body"><span class="dash-kpi-label">Impayé</span><strong class="dash-kpi-value" id="kpiUnpaid">—</strong></div>
                </div>
                <div class="dash-kpi-card">
                    <div class="dash-kpi-icon" style="background:#E8F5E9;color:#2E7D32"><i class="bi bi-calendar-check"></i></div>
                    <div class="dash-kpi-body"><span class="dash-kpi-label">Ce mois</span><strong class="dash-kpi-value" id="kpiMonth">—</strong></div>
                </div>
            </div>

            <!-- Product Statistics -->
            <div class="dash-section">
                <h3 class="dash-section-title"><i class="bi bi-bar-chart-line"></i> Statistiques Produits</h3>
                <div class="dash-product-stats-grid" id="dashProductStats">
                    <p class="muted">Chargement...</p>
                </div>
            </div>

            <!-- Products by segment breakdown -->
            <div class="dash-two-col">
                <div class="panel">
                    <div class="panel-head"><h3><i class="bi bi-pie-chart"></i> Par Segment</h3></div>
                    <div id="dashSegmentBreakdown" class="dash-breakdown-list"><p class="muted">Chargement...</p></div>
                </div>
                <div class="panel">
                    <div class="panel-head"><h3><i class="bi bi-clock-history"></i> Commandes récentes</h3></div>
                    <div id="dashRecentOrders"><p class="muted">Chargement...</p></div>
                </div>
            </div>
        </div>

        <!-- ═══ SHOP VIEW (Default) ═══ -->
        <div id="viewShop" class="view active">
            <!-- Workflow tabs (Hidden mostly, shown conceptually during checkout) -->
            <div class="workflow-tabs" id="shopTabs" style="display:none;">
                <button class="wf-tab active" data-step="browse">1. Catalogue</button>
                <button class="wf-tab" data-step="checkout">2. Validation</button>
                <button class="wf-tab" data-step="confirm">3. Confirmation</button>
            </div>
            
            <div id="stepBrowse" class="wf-step active">
                <div class="eco-shop-hero">
                    <div class="eco-shop-header">
                        <h1 class="page-title">CATALOGUE PARFUMS</h1>
                    </div>
                    <p class="text-muted" id="shopResultsCount" style="margin-top: 16px; font-weight: 600; font-size: 0.9rem; letter-spacing: 0.05em; text-transform: uppercase; background: rgba(var(--primary-rgb), 0.1); padding: 4px 12px; border-radius: var(--radius-full);"></p>
                </div>
                
                <div class="eco-shop-grid">
                    <div class="product-grid" id="shopProductGrid">
                        <p class="muted" style="grid-column:1/-1; text-align:center; padding: 48px 0;">Chargement du catalogue...</p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Checkout -->
            <div id="stepCheckout" class="wf-step">
                <div class="eco-checkout-header">
                    <h2><i class="bi bi-lock-fill"></i> Validation de la commande</h2>
                </div>
                <div class="checkout-grid">
                    <div class="checkout-form-col">
                        <div class="panel">
                            <div class="panel-head"><h3>Adresse de livraison</h3></div>
                            <div class="form-row">
                                <div class="form-group"><label>Adresse *</label><input type="text" id="ckLine1" class="form-control" name="line1" required></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label>Complément</label><input type="text" id="ckLine2" class="form-control" name="line2"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label>Ville *</label><input type="text" id="ckCity" class="form-control" name="city" required></div>
                                <div class="form-group"><label>Wilaya</label><input type="text" id="ckRegion" class="form-control" name="region"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label>Code postal</label><input type="text" id="ckPostal" class="form-control" name="postal_code"></div>
                                <div class="form-group">
                                    <label>Pays</label>
                                    <select id="ckCountry" class="form-control"><option value="Algérie">Algérie</option><option value="Tunisie">Tunisie</option></select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="checkout-summary-col">
                        <div class="panel sticky-panel">
                            <div class="panel-head"><h3>Résumé</h3></div>
                            <div id="ckSummaryTable" class="ck-summary-table-wrap"></div>
                            <div id="ckTotals" class="ck-totals"></div>
                            <button class="btn btn-primary btn-block" id="goToConfirm"><i class="bi bi-check-circle"></i> Confirmer la commande</button>
                            <button class="btn btn-ghost btn-block mt-2" id="backToBrowse">Continuer mes achats</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Confirm -->
            <div id="stepConfirm" class="wf-step">
                <div class="confirm-box">
                    <div class="confirm-icon"><i class="bi bi-bag-check-fill"></i></div>
                    <h2>Merci pour votre commande !</h2>
                    <p id="confirmMsg" class="mb-4">Votre commande a été enregistrée avec succès.</p>
                    <div class="confirm-actions">
                        <button class="btn btn-primary" onclick="resetShop()">Nouvelle commande</button>
                        <button class="btn btn-outline" onclick="switchView('orders')">Voir mes commandes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ Slide-out Cart (Always available) ═══ -->
        <div class="eco-cart-overlay" id="cartOverlay" onclick="document.body.classList.remove('cart-open')"></div>
        <div class="eco-cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h3><i class="bi bi-bag"></i> Mon Panier (<span id="cartCount">0</span>)</h3>
                <button class="cart-close" id="cartCloseBtn" onclick="document.body.classList.remove('cart-open')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="cart-body" id="cartBody">
                <div class="cart-empty"><i class="bi bi-bag-x"></i><p>Votre panier est vide</p></div>
            </div>
            <div class="cart-footer" id="cartFooter" style="display:none">
                <div class="cart-total-row"><span>Total</span><strong id="cartTotal">0,00 DZD</strong></div>
                <button class="btn btn-primary btn-block" id="goToCheckout">Valider la commande</button>
                <button class="btn btn-ghost btn-block mt-2" id="cartClear">Vider le panier</button>
            </div>
        </div>

        <!-- ═══ ORDERS VIEW ═══ -->
        <div id="viewOrders" class="view">
            <div class="eco-page-header">
                <h2>Mes Commandes</h2>
            </div>
            <div class="panel">
                <div class="filters-row mb-4">
                    <input type="search" id="orderSearch" class="form-control auto-w" placeholder="N° de commande...">
                    <select id="orderStatusFilter" class="form-control auto-w">
                        <option value="ALL">Tous les statuts</option>
                        <option value="EN_PREPARATION">En préparation</option>
                        <option value="LIVREE">Livrée</option>
                        <option value="ANNULEE">Annulée</option>
                    </select>
                </div>
                <div id="ordersPanel"><p class="muted">Chargement de l'historique...</p></div>
            </div>
        </div>

        <button id="themeToggleBtn" class="floating-theme-btn" title="Activer/Désactiver le mode sombre">🌙</button>

        <!-- ═══ INVOICES VIEW ═══ -->
        <div id="viewInvoices" class="view">
            <div class="eco-page-header">
                <h2>Mes Factures</h2>
            </div>
            <div class="panel">
                <div class="filters-row mb-4">
                    <input type="search" id="invoiceSearch" class="form-control auto-w" placeholder="N° de facture...">
                    <select id="invoiceStatusFilter" class="form-control auto-w">
                        <option value="ALL">Tous les statuts</option>
                        <option value="NON_PAYE">Non Payée</option>
                        <option value="PARTIEL">Paiement Partiel</option>
                        <option value="PAYE">Payée</option>
                    </select>
                </div>
                <div id="invoicesPanel"><p class="muted">Chargement des factures...</p></div>
            </div>
        </div>

        <!-- ═══ PROFILE VIEW ═══ -->
        <div id="viewProfile" class="view">
            <div class="eco-page-header">
                <h2>Paramètres du Profil</h2>
            </div>
            <div class="profile-grid checkout-grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                <div class="panel">
                    <div class="panel-head"><h3>Informations professionnelles</h3></div>
                    <form id="profileForm">
                        <div class="form-row">
                            <div class="form-group"><label>Prénom</label><input type="text" class="form-control" id="profFirstName" value="{$firstName}" required></div>
                            <div class="form-group"><label>Nom</label><input type="text" class="form-control" id="profLastName" value="{$lastName}" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Nom de la parfumerie</label><input type="text" class="form-control" id="profShop" value="{$shopName}" required></div>
                            <div class="form-group"><label>Téléphone</label><input type="text" class="form-control" id="profPhone" value="{$phone}" required></div>
                        </div>
                        <div id="profileMessage"></div>
                        <div style="text-align:right; margin-top:20px;">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
                <div class="panel">
                    <div class="panel-head"><h3>Sécurité</h3></div>
                    <form id="passwordForm">
                        <div class="form-group"><label>Mot de passe actuel</label><input type="password" class="form-control" id="profCurrentPwd" required></div>
                        <div class="form-group"><label>Nouveau mot de passe</label><input type="password" class="form-control" id="profNewPwd" required></div>
                        <div class="form-group"><label>Confirmer le mot de passe</label><input type="password" class="form-control" id="profConfirmPwd" required></div>
                        <div id="passwordMessage"></div>
                        <div style="text-align:right; margin-top:20px;">
                            <button type="submit" class="btn btn-outline">Mettre à jour le mot de passe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <!-- ═══ ONBOARDING GUIDE ═══ -->
    <div class="ob-backdrop" id="obBackdrop"></div>
    <div class="ob-modal" id="obModal" role="dialog" aria-modal="true" aria-label="Guide de bienvenue">

        <!-- Progress dots -->
        <div class="ob-dots" id="obDots"></div>

        <!-- Slides wrapper -->
        <div class="ob-slides" id="obSlides">

            <!-- Step 1 : Bienvenue -->
            <div class="ob-slide active" data-step="0">
                <div class="ob-visual ob-visual--welcome">
                    <div class="ob-welcome-icon">
                        <img src="/assets/images/logo.png" alt="IDENE" class="ob-logo-big">
                    </div>
                    <div class="ob-welcome-sparkles">
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                </div>
                <div class="ob-body">
                    <h2 class="ob-title">Bienvenue sur IDENE&nbsp;! 👋</h2>
                    <p class="ob-desc">Votre espace professionnel pour commander vos huiles de parfum en vrac, directement depuis la source. Ce guide rapide vous montre comment tout fonctionne.</p>
                </div>
            </div>

            <!-- Step 2 : Catalogue -->
            <div class="ob-slide" data-step="1">
                <div class="ob-visual">
                    <figure class="ob-shot">
                        <img src="/assets/images/onboarding/01-catalogue.png" alt="Catalogue parfums" class="ob-shot-img">
                    </figure>
                    <div class="ob-visual-icon ob-visual-icon--shop"><i class="bi bi-shop"></i></div>
                </div>
                <div class="ob-body">
                    <h2 class="ob-title"><i class="bi bi-grid-3x3-gap"></i> Catalogue Parfums</h2>
                    <p class="ob-desc">Parcourez plus de <strong>200 parfums</strong> classés par gamme et segment. Filtrez par HOMME, FEMME, ENFANT ou MIXTE. Le badge indique la disponibilité en temps réel.</p>
                </div>
            </div>

            <!-- Step 3 : Panier -->
            <div class="ob-slide" data-step="2">
                <div class="ob-visual">
                    <figure class="ob-shot ob-shot--tall">
                        <img src="/assets/images/onboarding/02-panier.png" alt="Mon panier" class="ob-shot-img">
                    </figure>
                    <div class="ob-visual-icon ob-visual-icon--cart"><i class="bi bi-bag"></i></div>
                </div>
                <div class="ob-body">
                    <h2 class="ob-title"><i class="bi bi-bag"></i> Mon Panier</h2>
                    <p class="ob-desc">Choisissez la quantité (250 ml, 500 ml ou 1 L) et ajoutez au panier. Le panier s'ouvre depuis l'icône en haut à droite. Vous pouvez modifier ou vider à tout moment.</p>
                </div>
            </div>

            <!-- Step 4 : Commande -->
            <div class="ob-slide" data-step="3">
                <div class="ob-visual">
                    <figure class="ob-shot">
                        <img src="/assets/images/onboarding/03-validation.png" alt="Validation de la commande" class="ob-shot-img">
                    </figure>
                    <div class="ob-visual-icon ob-visual-icon--order"><i class="bi bi-truck"></i></div>
                </div>
                <div class="ob-body">
                    <h2 class="ob-title"><i class="bi bi-lock-fill"></i> Validation</h2>
                    <p class="ob-desc">Renseignez votre adresse de livraison et confirmez en un clic. Votre commande est enregistrée instantanément et un récapitulatif apparaît à l'écran.</p>
                </div>
            </div>

            <!-- Step 5 : Tableau de bord -->
            <div class="ob-slide" data-step="4">
                <div class="ob-visual">
                    <figure class="ob-shot">
                        <img src="/assets/images/onboarding/05-dashboard.png" alt="Tableau de bord" class="ob-shot-img">
                    </figure>
                    <div class="ob-visual-icon ob-visual-icon--dash"><i class="bi bi-bar-chart-line"></i></div>
                </div>
                <div class="ob-body">
                    <h2 class="ob-title"><i class="bi bi-grid-1x2"></i> Tableau de bord</h2>
                    <p class="ob-desc">Suivez vos statistiques en un coup d'œil : nombre de commandes, montant total, impayés du mois et répartition des produits par segment.</p>
                </div>
            </div>

            <!-- Step 6 : Commandes & Factures -->
            <div class="ob-slide" data-step="5">
                <div class="ob-visual">
                    <figure class="ob-shot">
                        <img src="/assets/images/onboarding/06-commandes.png" alt="Mes commandes" class="ob-shot-img">
                    </figure>
                    <div class="ob-visual-icon ob-visual-icon--invoice"><i class="bi bi-file-text"></i></div>
                </div>
                <div class="ob-body">
                    <h2 class="ob-title"><i class="bi bi-box"></i> Commandes & Factures</h2>
                    <p class="ob-desc">Retrouvez tout votre historique de commandes avec les statuts, et téléchargez vos factures PDF directement depuis l'espace "Mes Factures".</p>
                </div>
            </div>

            <!-- Step 7 : C'est parti ! -->
            <div class="ob-slide" data-step="6">
                <div class="ob-visual ob-visual--final">
                    <div class="ob-final-circle">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="ob-final-rings"><span></span><span></span></div>
                </div>
                <div class="ob-body">
                    <h2 class="ob-title">Vous êtes prêt&nbsp;! 🚀</h2>
                    <p class="ob-desc">Vous connaissez maintenant toutes les fonctionnalités de votre espace IDENE. Cliquez sur <strong>"Démarrer"</strong> pour accéder à votre catalogue et passer votre première commande.</p>
                </div>
            </div>

        </div><!-- /ob-slides -->

        <!-- Footer actions -->
        <div class="ob-footer">
            <button class="ob-btn-skip" id="obSkip">Passer</button>
            <div class="ob-footer-center" id="obFooterCenter">
                <button class="ob-btn-prev" id="obPrev" disabled><i class="bi bi-chevron-left"></i></button>
                <span class="ob-counter" id="obCounter">1 / 7</span>
                <button class="ob-btn-next" id="obNext"><i class="bi bi-chevron-right"></i> Suivant</button>
            </div>
        </div>

    </div><!-- /ob-modal -->

    <script src="/assets/js/user-app.js?v=600"></script>
    <script>
        // Init Dark Mode User
        const initUserTheme = () => {
            const saved = localStorage.getItem("idene-user-theme") || "light";
            document.documentElement.setAttribute("data-theme", saved);
            const btn = document.getElementById("themeToggleBtn");
            if(btn) btn.innerHTML = saved === "dark" ? '<i class="bi bi-sun-fill" style="color:#FFF;"></i>' : '🌙';
        };
        initUserTheme();
    </script>
</body>
</html>
HTML);
    }
}
