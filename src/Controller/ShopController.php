<?php

namespace App\Controller;

use App\Support\AppContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShopController
{
    public function __construct(private readonly AppContext $app)
    {
    }

    #[Route('/shop', name: 'app_shop', methods: ['GET'])]
    public function shop(): Response
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
    <title>IDENE PARFUM | Boutique</title>
    <meta name="description" content="Boutique en ligne IDENE - Commandez vos parfums professionnels">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/idene-design-system.css?v=600">
    <link rel="stylesheet" href="/assets/css/user-app.css?v=605">
    <link rel="stylesheet" href="/assets/css/client-ecommerce.css?v=600">
</head>
<body class="ecommerce-layout">
    <div class="ecommerce-layout">
        <!-- Sidebar -->
        <aside class="shop-sidebar">
            <div class="shop-logo">
                <img src="/assets/images/logo.png" alt="IDENE Logo">
                <h1>IDENE PARFUM</h1>
                <p>Boutique Professionnelle</p>
            </div>
            
            <nav class="shop-nav">
                <a href="#" class="shop-nav-item active" data-view="shop">
                    <span class="shop-nav-icon"><i class="bi bi-shop"></i></span>
                    <span>Boutique</span>
                </a>
                <a href="#" class="shop-nav-item cart-badge" data-view="cart">
                    <span class="shop-nav-icon"><i class="bi bi-cart3"></i></span>
                    <span>Panier</span>
                    <span class="cart-count" style="display: none;">0</span>
                </a>
                <a href="/dashboard#commandes" class="shop-nav-item">
                    <span class="shop-nav-icon"><i class="bi bi-bag-check"></i></span>
                    <span>Mes Commandes</span>
                </a>
                <a href="/dashboard#factures" class="shop-nav-item">
                    <span class="shop-nav-icon"><i class="bi bi-receipt"></i></span>
                    <span>Mes Factures</span>
                </a>
                <a href="/dashboard#profil" class="shop-nav-item">
                    <span class="shop-nav-icon"><i class="bi bi-person"></i></span>
                    <span>Mon Compte</span>
                </a>
                <a href="/dashboard" class="shop-nav-item">
                    <span class="shop-nav-icon"><i class="bi bi-grid"></i></span>
                    <span>Dashboard</span>
                </a>
                <button type="button" id="logoutBtn" class="shop-nav-item" style="margin-top: auto;">
                    <span class="shop-nav-icon"><i class="bi bi-box-arrow-right"></i></span>
                    <span>DÃƒÆ’Ã‚Â©connexion</span>
                </button>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="shop-main">
            <!-- Header -->
            <header class="shop-header">
                <div class="shop-header-content">
                    <div class="shop-search">
                        <i class="bi bi-search shop-search-icon"></i>
                        <input 
                            type="search" 
                            class="shop-search-input" 
                            placeholder="Rechercher un parfum..."
                        >
                    </div>
                    <div class="shop-user">
                        <div class="shop-user-avatar">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="shop-user-info">
                            <div class="shop-user-name">Client Professionnel</div>
                            <div class="shop-user-role">Compte B2B</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Breadcrumb -->
            <div class="shop-breadcrumb">
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="/dashboard">Accueil</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-separator">/</span></li>
                    <li class="breadcrumb-item">Boutique</li>
                </ul>
            </div>

            <!-- Content -->
            <div class="shop-content">
                <div class="shop-section-header">
                    <h1 class="shop-section-title">Catalogue Professionnel</h1>
                    <p class="shop-section-subtitle">DÃƒÆ’Ã‚Â©couvrez notre sÃƒÆ’Ã‚Â©lection de parfums premium pour votre boutique</p>
                </div>

                <!-- Filters -->
                <div class="shop-filters">
                    <div class="filter-group">
                        <label class="filter-label">CatÃƒÆ’Ã‚Â©gorie</label>
                        <select class="filter-select" id="categoryFilter">
                            <option value="ALL">Toutes les catÃƒÆ’Ã‚Â©gories</option>
                            <option value="PRINCIPAL">Principal</option>
                            <option value="SMART">Smart</option>
                            <option value="ENFANT">Enfant</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Trier par</label>
                        <select class="filter-select" id="sortFilter">
                            <option value="name">Nom (A-Z)</option>
                            <option value="price-asc">Prix croissant</option>
                            <option value="price-desc">Prix dÃƒÆ’Ã‚Â©croissant</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="products-grid">
                    <div class="col-span-full text-center py-12">
                        <div class="loading-spinner"></div>
                        <p class="text-muted mt-lg">Chargement des produits...</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="shop-pagination">
                    <button class="pagination-btn" disabled>PrÃƒÆ’Ã‚Â©cÃƒÆ’Ã‚Â©dent</button>
                    <span class="pagination-info">Page 1 / 1</span>
                    <button class="pagination-btn" disabled>Suivant</button>
                </div>
            </div>
        </main>
    </div>

    <!-- Cart Sidebar -->
    <aside class="cart-sidebar">
        <div class="cart-header">
            <h2 class="cart-title">Mon Panier</h2>
            <button class="cart-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="cart-items">
            <div class="cart-empty">
                <div class="cart-empty-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂºÃ¢â‚¬â„¢</div>
                <p>Votre panier est vide</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-subtotal">
                <span>Sous-total</span>
                <span class="cart-total-amount">0.00 DT</span>
            </div>
            <div class="cart-total">
                <span>Total</span>
                <span class="cart-total-amount">0.00 DT</span>
            </div>
            <button class="cart-checkout-btn">
                <i class="bi bi-check-circle"></i>
                Passer la commande
            </button>
        </div>
    </aside>

    <!-- Cart Overlay -->
    <div class="cart-overlay"></div>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn">
        <i class="bi bi-list"></i>
    </button>

    <button id="themeToggleBtn" class="floating-theme-btn" title="Activer/DÃƒÆ’Ã‚Â©sactiver le mode sombre">ÃƒÂ°Ã…Â¸Ã…â€™Ã¢â€žÂ¢</button>

    <!-- Scripts -->
    <script>
        // Init Dark Mode
        const initTheme = () => {
            const saved = localStorage.getItem("idene-user-theme") || "light";
            document.documentElement.setAttribute("data-theme", saved);
            const btn = document.getElementById("themeToggleBtn");
            if(btn) btn.innerHTML = saved === "dark" ? '<i class="bi bi-sun-fill" style="color:#FFF;"></i>' : 'ÃƒÂ°Ã…Â¸Ã…â€™Ã¢â€žÂ¢';
        };
        initTheme();

        document.getElementById('themeToggleBtn')?.addEventListener('click', () => {
            const current = document.documentElement.getAttribute("data-theme") || "light";
            const newTheme = current === "dark" ? "light" : "dark";
            document.documentElement.setAttribute("data-theme", newTheme);
            localStorage.setItem("idene-user-theme", newTheme);
            document.getElementById("themeToggleBtn").innerHTML = newTheme === "dark" ? '<i class="bi bi-sun-fill" style="color:#FFF;"></i>' : 'ÃƒÂ°Ã…Â¸Ã…â€™Ã¢â€žÂ¢';
        });

        // Logout functionality
        document.getElementById('logoutBtn')?.addEventListener('click', async () => {
            const response = await fetch('/api/auth/logout', { method: 'POST' });
            if (response.ok) {
                window.location.href = '/auth';
            }
        });
    </script>
    <script src="/assets/js/client-ecommerce.js?v=600"></script>
    <script src="/assets/js/user-app.js?v=606"></script>
</body>
</html>
HTML);
    }
}
