<?php

namespace App\Controller;

use App\Support\AppContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicWebsiteController
{
    public function __construct(
        private readonly AppContext $app,
    ) {
    }
    #[Route('/accueil', name: 'app_public_home', methods: ['GET'])]
    public function home(): Response
    {
        $isLoggedIn = $this->app->currentUserId() !== null;

        $navActions = $isLoggedIn 
            ? '<a href="/dashboard" class="btn btn-sm btn-primary"><i class="bi bi-person-circle"></i> Mon Espace</a>'
            : '<a href="/auth" class="btn btn-sm btn-outline">Connexion</a><a href="/auth" class="btn btn-sm btn-primary">Compte Pro</a>';

        $heroAction = $isLoggedIn
            ? '<a href="/dashboard" class="btn btn-outline btn-lg" style="border-color:rgba(255,255,255,.3);color:#fff;">Mon Espace</a>'
            : '<a href="/auth" class="btn btn-outline btn-lg" style="border-color:rgba(255,255,255,.3);color:#fff;">Demander un compte pro</a>';

        $ctaAction = $isLoggedIn
            ? '<a href="/dashboard" class="btn btn-gold btn-xl">Accéder à mon espace</a>'
            : '<a href="/auth" class="btn btn-gold btn-xl">Demander mon accès professionnel</a>';

        $catalogLink = $isLoggedIn ? '/dashboard#shop' : '/catalogue';

        return new Response(<<<HTML
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IDENE — L'huile de parfum au plus près de la source</title>
    <meta name="description" content="Approvisionnement B2B direct d'usine pour les parfumeries professionnelles. Huiles de parfum en vrac, livraison directe et tarifs compétitifs.">
    <meta name="keywords" content="parfum, B2B, grossiste, parfumerie, huile de parfum, EDT, distribution, IDENE, approvisionnement, vrac">
    <meta name="author" content="IDENE PARFUM">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://idene-parfum.com/">
    <meta property="og:title" content="IDENE — L'huile de parfum au plus près de la source">
    <meta property="og:description" content="Approvisionnement B2B direct d'usine pour les parfumeries professionnelles.">
    <meta property="og:image" content="/assets/images/logo.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="IDENE — L'huile de parfum au plus près de la source">
    <meta property="twitter:description" content="Approvisionnement B2B direct d'usine pour les parfumeries professionnelles.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/logo.png">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "IDENE PARFUM",
        "description": "Approvisionnement B2B direct d'usine en huiles de parfum pour les parfumeries professionnelles",
        "url": "https://idene-parfum.com",
        "logo": "https://idene-parfum.com/assets/images/logo.png",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+21558606233",
            "contactType": "customer service",
            "areaServed": ["DZ", "TN"],
            "availableLanguage": ["French", "Arabic"]
        }
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/idene-design-system.css">
    <link rel="stylesheet" href="/assets/css/public-website.css">
</head>
<body>

    <!-- ═══ Navbar ═══ -->
    <nav class="pub-nav" id="pubNav">
        <div class="container">
            <a href="/accueil" class="pub-nav-brand">
                <img src="/assets/images/logo.png" alt="IDENE Logo">
                <span>IDENE</span>
            </a>
            <ul class="pub-nav-links">
                <li><a href="#pourquoi">Avantages</a></li>
                <li><a href="/catalogue">Catalogue</a></li>
                <li><a href="#comment">Comment commander</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="pub-nav-actions">
                {$navActions}
            </div>
            <button class="pub-nav-mobile" id="mobileMenuBtn" aria-label="Menu">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </nav>

    <!-- ═══ Hero ═══ -->
    <section class="pub-hero">
        <div class="pub-hero-shimmer"></div>
        <div class="container">
            <div class="pub-hero-grid">
                <div class="pub-hero-content">
                    <span class="pub-hero-badge">Plateforme B2B · Direct Usine</span>
                    <h1 class="pub-hero-title">IDENE — L'huile de parfum <em>au plus près de la source</em></h1>
                    <p class="pub-hero-subtitle">Approvisionnement B2B direct d'usine pour les parfumeries professionnelles. Commandez vos huiles de parfum en vrac, aux meilleurs tarifs.</p>
                    <div class="pub-hero-actions">
                        <a href="{$catalogLink}" class="btn btn-primary btn-lg">Voir le catalogue</a>
                        {$heroAction}
                    </div>
                    <div class="pub-hero-stats">
                        <div class="pub-hero-stat">
                            <strong data-count="200">0</strong>
                            <span>Parfums disponibles</span>
                        </div>
                        <div class="pub-hero-stat">
                            <strong>48h</strong>
                            <span>Livraison directe</span>
                        </div>
                        <div class="pub-hero-stat">
                            <strong>100%</strong>
                            <span>Qualité contrôlée</span>
                        </div>
                    </div>
                </div>
                <div class="pub-hero-bottle">
                    <div class="bottle-halo"></div>
                    <div class="bottle-float-stage">
                        <img src="/assets/images/perfume-bottle.png" alt="Bouteille de parfum IDENE" class="bottle-img">
                    </div>
                    <div class="bottle-shadow-spot"></div>
                    <div class="bottle-sparkles" id="bottleSparkles"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ 3D Essence du Parfum ═══ -->
    <section id="essence" class="pub-section pub-3d-essence">
        <canvas id="essenceCanvas"></canvas>
        <div class="essence-overlay container">
            <div class="essence-content reveal">
                <span class="kicker-gold">L'Art de la Création</span>
                <h2>Une essence pure, vibrante, intemporelle.</h2>
                <p>Découvrez notre sélection exclusive d'huiles de parfum. Chaque goutte est le fruit d'une sélection rigoureuse à la source pour une qualité irréprochable.</p>
                <a href="#comment" class="btn btn-outline-gold mt-4">Découvrir le processus <i class="bi bi-arrow-down"></i></a>
            </div>
        </div>
    </section>

    <!-- ═══ Pourquoi IDENE ═══ -->
    <section id="pourquoi" class="pub-section pub-why">
        <div class="container">
            <div class="pub-section-header reveal">
                <span class="kicker">Nos avantages</span>
                <h2>Pourquoi choisir IDENE</h2>
                <p>Une solution complète pensée pour les professionnels de la parfumerie</p>
            </div>
            <div class="pub-why-grid">
                <div class="pub-why-card green reveal">
                    <div class="icon-wrap"><i class="bi bi-box-seam-fill"></i></div>
                    <h3>Approvisionnement fiable</h3>
                    <p>Une base de parfums structurée par gamme et segment. Visualisez la disponibilité en temps réel et commandez en toute confiance.</p>
                </div>
                <div class="pub-why-card terra reveal">
                    <div class="icon-wrap"><i class="bi bi-truck"></i></div>
                    <h3>Livraison directe d'usine</h3>
                    <p>Meilleurs prix garantis, qualité contrôlée à la source. Expédition sous 48h pour toutes les commandes confirmées.</p>
                </div>
                <div class="pub-why-card gold reveal">
                    <div class="icon-wrap"><i class="bi bi-person-badge-fill"></i></div>
                    <h3>Compte professionnel dédié</h3>
                    <p>Tarifs GROS et DETAIL personnalisés, historique complet de vos commandes, factures PDF et suivi de paiement.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ Aperçu Catalogue ═══ -->
    <section class="pub-section pub-catalog-preview">
        <div class="container">
            <div class="pub-section-header reveal">
                <span class="kicker">Notre catalogue</span>
                <h2>Aperçu de nos parfums</h2>
                <p>Découvrez un extrait de notre gamme. Connectez-vous pour voir tous les prix et passer commande.</p>
            </div>
            <div class="pub-catalog-grid" id="previewGrid">
                <div style="grid-column:1/-1;text-align:center;padding:48px 0">
                    <div class="spinner" style="margin:0 auto 16px"></div>
                    <p class="text-muted">Chargement des produits...</p>
                </div>
            </div>
            <div style="text-align:center;margin-top:40px" class="reveal">
                <a href="/catalogue" class="btn btn-primary btn-lg">Voir tout le catalogue</a>
            </div>
        </div>
    </section>

    <!-- ═══ Comment commander ═══ -->
    <section id="comment" class="pub-section pub-steps">
        <div class="container">
            <div class="pub-section-header reveal">
                <span class="kicker">Mode d'emploi</span>
                <h2>Comment commander</h2>
                <p>Quatre étapes simples pour recevoir vos parfums</p>
            </div>
            <div class="pub-steps-grid reveal">
                <div class="pub-step">
                    <div class="pub-step-number">1</div>
                    <div class="pub-step-icon">👤</div>
                    <h4>Créez votre compte pro</h4>
                    <p>Inscription rapide avec validation de votre statut professionnel par notre équipe.</p>
                </div>
                <div class="pub-step">
                    <div class="pub-step-number">2</div>
                    <div class="pub-step-icon">🔍</div>
                    <h4>Choisissez vos parfums</h4>
                    <p>Filtrez par gamme, segment et disponibilité. Consultez les prix et le stock en temps réel.</p>
                </div>
                <div class="pub-step">
                    <div class="pub-step-number">3</div>
                    <div class="pub-step-icon">🛒</div>
                    <h4>Passez commande</h4>
                    <p>Ajoutez les quantités souhaitées au panier et validez votre commande en un clic.</p>
                </div>
                <div class="pub-step">
                    <div class="pub-step-number">4</div>
                    <div class="pub-step-icon">📦</div>
                    <h4>Recevez et payez</h4>
                    <p>Livraison sous 48h à votre parfumerie. Facture PDF générée automatiquement.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ CTA Banner ═══ -->
    <section class="pub-section pub-cta py-20">
        <div class="container reveal">
            <h2>Rejoignez les parfumeries qui font confiance à IDENE</h2>
            <p>Accédez à notre plateforme B2B et simplifiez vos approvisionnements dès aujourd'hui</p>
            {$ctaAction}
        </div>
    </section>

    <!-- ═══ Footer ═══ -->
    <footer id="contact" class="pub-footer">
        <div class="container">
            <div class="pub-footer-grid">
                <div class="pub-footer-brand">
                    <img src="/assets/images/logo.png" alt="IDENE Logo">
                    <h3>IDENE PARFUM</h3>
                    <p>L'huile de parfum au plus près de la source. Approvisionnement B2B direct d'usine pour les parfumeries professionnelles.</p>
                    <span class="pro-badge"><i class="bi bi-shield-check"></i> Réservé aux professionnels</span>
                </div>
                <div>
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="/accueil">Accueil</a></li>
                        <li><a href="/catalogue">Catalogue</a></li>
                        <li><a href="#pourquoi">Avantages</a></li>
                        <li><a href="#comment">Comment commander</a></li>
                        <li><a href="/auth">Connexion</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Informations</h4>
                    <ul>
                        <li><a href="#">À propos</a></li>
                        <li><a href="#">Conditions générales</a></li>
                        <li><a href="#">Politique de confidentialité</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Contact</h4>
                    <ul class="pub-footer-contact">
                        <li><i class="bi bi-telephone-fill"></i> +215 58 60 62 33</li>
                        <li><i class="bi bi-telephone-fill"></i> +216 58 36 74 68</li>
                        <li><i class="bi bi-envelope-fill"></i> idene.parfum@gmail.com</li>
                    </ul>
                    <div class="pub-footer-socials">
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="pub-footer-bottom">
                <p>&copy; 2025 IDENE PARFUM. Tous droits réservés. Plateforme réservée aux professionnels.</p>
            </div>
        </div>
    </footer>

    <!-- ═══ Mobile Menu ═══ -->
    <div class="pub-mobile-menu-backdrop" id="mobileMenuBackdrop"></div>
    <div class="pub-mobile-menu" id="mobileMenu">
        <div class="pub-mobile-menu-head">
            <span>IDENE</span>
            <button class="pub-mobile-menu-close" id="mobileMenuClose"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav>
            <a href="#pourquoi">Avantages</a>
            <a href="/catalogue">Catalogue</a>
            <a href="#comment">Comment commander</a>
            <a href="#contact">Contact</a>
        </nav>
        <div class="pub-mobile-menu-actions">
            {$navActions}
        </div>
    </div>

    <!-- ═══ Scroll to top ═══ -->
    <button class="pub-scroll-top" id="scrollTopBtn" aria-label="Retour en haut">
        <i class="bi bi-chevron-up"></i>
    </button>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="/assets/js/public-website.js"></script>
</body>
</html>
HTML);
    }

    #[Route('/catalogue', name: 'app_public_catalog', methods: ['GET'])]
    public function catalog(): Response
    {
        // Si l'utilisateur est connecté, le rediriger vers le dashboard qui contient le vrai shop professionnel
        if ($this->app->currentUserId() !== null) {
            return new RedirectResponse('/dashboard#shop');
        }
        return new Response(<<<'HTML'
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catalogue | IDENE PARFUM — Huiles de parfum professionnelles</title>
    <meta name="description" content="Consultez notre catalogue complet de parfums professionnels. Filtrez par gamme, segment et disponibilité.">

    <link rel="icon" type="image/png" href="/assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/idene-design-system.css">
    <link rel="stylesheet" href="/assets/css/public-website.css">
</head>
<body>

    <!-- ═══ Navbar ═══ -->
    <nav class="pub-nav scrolled" id="pubNav">
        <div class="container">
            <a href="/accueil" class="pub-nav-brand">
                <img src="/assets/images/logo.png" alt="IDENE Logo">
                <span>IDENE</span>
            </a>
            <ul class="pub-nav-links">
                <li><a href="/accueil#pourquoi">Avantages</a></li>
                <li><a href="/catalogue" style="color:var(--primary);font-weight:600">Catalogue</a></li>
                <li><a href="/accueil#comment">Comment commander</a></li>
                <li><a href="/accueil#contact">Contact</a></li>
            </ul>
            <div class="pub-nav-actions">
                <a href="/auth" class="btn btn-sm btn-outline">Connexion</a>
                <a href="/auth" class="btn btn-sm btn-primary">Compte Pro</a>
            </div>
            <button class="pub-nav-mobile" id="mobileMenuBtn" aria-label="Menu">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </nav>

    <!-- ═══ Catalog ═══ -->
    <section class="pub-section" style="padding-top:120px;min-height:100vh">
        <div class="container">
            <div class="pub-section-header" style="margin-bottom:32px">
                <span class="kicker">Catalogue complet</span>
                <h2>Nos parfums professionnels</h2>
                <p>Explorez notre gamme complète. Connectez-vous pour voir les prix et passer commande.</p>
            </div>

            <div class="pub-catalog-filters" id="catalogFilters">
                <div class="pub-catalog-search">
                    <i class="bi bi-search search-icon"></i>
                    <input type="search" class="form-input" id="catalogSearch" placeholder="Rechercher un parfum...">
                </div>
                <select class="form-select" id="catalogGroup" style="display: none;">
                    <option value="ALL">Toutes les gammes</option>
                    <option value="PRINCIPAL">Principal</option>
                    <option value="SECONDAIRE">Secondaire</option>
                </select>
                <select class="form-select" id="catalogSegment" style="display: none;">
                    <option value="ALL">Tous les segments</option>
                    <option value="HOMME">Homme</option>
                    <option value="FEMME">Femme</option>
                    <option value="MIXTE">Mixte</option>
                    <option value="ENFANT">Enfant</option>
                </select>
                <select class="form-select" id="catalogStock">
                    <option value="ALL">Toute disponibilité</option>
                    <option value="IN_STOCK">En stock</option>
                    <option value="LIMITED">Stock limité</option>
                    <option value="OUT">Rupture</option>
                </select>
            </div>

            <p class="pub-catalog-results" id="catalogResults"></p>

            <div class="pub-full-grid" id="catalogGrid">
                <div style="grid-column:1/-1;text-align:center;padding:48px 0">
                    <div class="spinner" style="margin:0 auto 16px"></div>
                    <p class="text-muted">Chargement du catalogue...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ CTA ═══ -->
    <section class="pub-section pub-cta py-16">
        <div class="container">
            <h2>Prêt à commander ?</h2>
            <p>Créez votre compte professionnel et accédez aux prix, au stock en temps réel et à la commande en ligne.</p>
            <a href="/auth" class="btn btn-gold btn-xl">Créer mon compte pro</a>
        </div>
    </section>

    <!-- ═══ Footer ═══ -->
    <footer class="pub-footer">
        <div class="container">
            <div class="pub-footer-grid">
                <div class="pub-footer-brand">
                    <img src="/assets/images/logo.png" alt="IDENE Logo">
                    <h3>IDENE PARFUM</h3>
                    <p>L'huile de parfum au plus près de la source.</p>
                    <span class="pro-badge"><i class="bi bi-shield-check"></i> Réservé aux professionnels</span>
                </div>
                <div>
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="/accueil">Accueil</a></li>
                        <li><a href="/catalogue">Catalogue</a></li>
                        <li><a href="/auth">Connexion</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Informations</h4>
                    <ul>
                        <li><a href="#">Conditions générales</a></li>
                        <li><a href="#">Politique de confidentialité</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Contact</h4>
                    <ul class="pub-footer-contact">
                        <li><i class="bi bi-telephone-fill"></i> +215 58 60 62 33</li>
                        <li><i class="bi bi-telephone-fill"></i> +216 58 36 74 68</li>
                        <li><i class="bi bi-envelope-fill"></i> idene.parfum@gmail.com</li>
                    </ul>
                </div>
            </div>
            <div class="pub-footer-bottom">
                <p>&copy; 2025 IDENE PARFUM. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- ═══ Mobile Menu ═══ -->
    <div class="pub-mobile-menu-backdrop" id="mobileMenuBackdrop"></div>
    <div class="pub-mobile-menu" id="mobileMenu">
        <div class="pub-mobile-menu-head">
            <span>IDENE</span>
            <button class="pub-mobile-menu-close" id="mobileMenuClose"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav>
            <a href="/accueil">Accueil</a>
            <a href="/catalogue">Catalogue</a>
            <a href="/accueil#pourquoi">Avantages</a>
            <a href="/accueil#contact">Contact</a>
        </nav>
        <div class="pub-mobile-menu-actions">
            <a href="/auth" class="btn btn-outline btn-block">Connexion</a>
            <a href="/auth" class="btn btn-primary btn-block">Compte Pro</a>
        </div>
    </div>

    <button class="pub-scroll-top" id="scrollTopBtn" aria-label="Retour en haut">
        <i class="bi bi-chevron-up"></i>
    </button>

    <script src="/assets/js/public-website.js"></script>
</body>
</html>
HTML);
    }
}
