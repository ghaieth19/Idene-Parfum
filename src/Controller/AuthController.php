<?php

namespace App\Controller;

use App\Support\AppContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController
{
    public function __construct(private readonly AppContext $app)
    {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): RedirectResponse
    {
        return new RedirectResponse('/accueil');
    }

    #[Route('/auth', name: 'app_auth', methods: ['GET'])]
    public function auth(): Response
    {
        if ($this->app->currentUserId()) {
            return new RedirectResponse($this->app->isAdminSession() ? '/admin' : '/dashboard');
        }

        return new Response(<<<'HTML'
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IDENE PARFUM | Login</title>
    <meta name="description" content="Connexion et inscription client Idene Parfum">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/idene-design-system.css?v=2">
    <link rel="stylesheet" href="/assets/css/auth.css?v=6">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card auth-layout">
            
            <!-- Left Panel Showcase -->
            <aside class="auth-showcase">
                <header class="card-header-left">
                    <div class="logo-wrap">
                        <img src="/assets/images/logo.png" alt="IDENE PARFUM" class="logo-image">
                    </div>
                    <p class="hero-tag">Acheter en gros</p>
                    <h1>L'huile de parfum<br>au plus près de la source.</h1>
                    <p class="intro-text">Créez votre compte B2B pour accéder instantanément aux tarifs professionnels et aux commandes directes usine.</p>
                </header>

                <div class="showcase-grid">
                    <div class="showcase-card">
                        <span>Sélection</span>
                        <strong>+200 Parfums en stock</strong>
                        <p>Disponible en 250ml, 500ml et 1 Litre pour s'adapter à vos besoins.</p>
                    </div>
                    <div class="showcase-card">
                        <span>Qualité & Service</span>
                        <strong>Expédition prioritaire 48h</strong>
                        <p>Commandez en quelques clics, suivez votre livraison en temps réel.</p>
                    </div>
                </div>
            </aside>

            <!-- Right Panel Form -->
            <section class="auth-form-panel">
                <div class="switcher">
                    <button class="tab active" data-target="login">Connexion</button>
                    <button class="tab" data-target="signup">Créer un compte</button>
                </div>

                <form id="loginForm" class="form active" action="#" method="post">
                    <div class="form-intro">
                        <p class="form-kicker">Bienvenue</p>
                        <h2>Accédez à votre espace</h2>
                    </div>
                    <p id="loginMessage" class="helper-text form-message"></p>
                    <div class="floating-label">
                        <input type="email" name="email" id="loginEmail" placeholder=" " required>
                        <label for="loginEmail">Email</label>
                    </div>
                    <div class="floating-label">
                        <input type="password" name="password" id="loginPassword" placeholder=" " required minlength="8">
                        <label for="loginPassword">Mot de passe</label>
                    </div>
                    
                    <a href="/verify-reset-code" id="forgotToggle" class="muted-link align-right">Mot de passe oublié ?</a>
                    
                    <button type="submit" class="btn-primary auth-submit">Se connecter <i class="bi bi-arrow-right"></i></button>

                    <div class="divider"><span>ou</span></div>

                    <button id="faceLoginBtn" type="button" class="btn-outline face-btn">
                        <i class="bi bi-person-bounding-box"></i> Connexion Faciale
                    </button>
                    <p id="faceStatus" class="helper-text"></p>
                </form>

                <form id="signupForm" class="form" action="#" method="post">
                    <div class="form-intro text-center">
                        <p class="form-kicker">Nouveau client</p>
                        <h2>Créer un compte professionnel</h2>
                    </div>
                    <p id="signupMessage" class="helper-text form-message"></p>
                    <div class="grid">
                        <div class="floating-label">
                            <input type="text" name="last_name" id="regLastName" placeholder=" " required>
                            <label for="regLastName">Nom</label>
                        </div>
                        <div class="floating-label">
                            <input type="text" name="first_name" id="regFirstName" placeholder=" " required>
                            <label for="regFirstName">Prénom</label>
                        </div>
                    </div>
                    <div class="floating-label">
                        <input type="text" name="shop_name" id="regShopName" placeholder=" " required>
                        <label for="regShopName">Nom de la parfumerie</label>
                    </div>
                    <div class="grid">
                        <div class="floating-label">
                            <input type="tel" name="phone" id="regPhone" placeholder=" " required>
                            <label for="regPhone">Téléphone</label>
                        </div>
                        <div class="floating-label">
                            <input type="text" name="location" id="regLocation" placeholder=" " required>
                            <label for="regLocation">Ville / Localisation</label>
                        </div>
                    </div>
                    <div class="floating-label">
                        <input type="email" name="email" id="regEmail" placeholder=" " required>
                        <label for="regEmail">Email professionnel</label>
                    </div>
                    <div class="floating-label">
                        <input type="password" name="password" id="regPassword" placeholder=" " required minlength="8">
                        <label for="regPassword">Créer un mot de passe</label>
                    </div>

                    <label class="check custom-checkbox">
                        <input id="faceSignupOptIn" type="checkbox" name="face_signup_opt_in" checked>
                        <span>Activer la reconnaissance faciale pour les prochaines connexions </span>
                    </label>

                    <section class="biometric-panel glass-panel mt-3 mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-camera-video" style="font-size:1.2rem; margin-right:8px; color:var(--primary);"></i>
                            <p class="panel-title mb-0" style="margin:0;">Enregistrement biométrique</p>
                        </div>
                        <p class="panel-text mb-3">Capturez votre visage une seule fois pour accélérer les connexions futures.</p>
                        
                        <div class="inline-face-box">
                            <div class="inline-camera-shell" style="border-radius:var(--radius-md); overflow:hidden; border:1px solid rgba(0,0,0,0.1); margin-bottom:12px;">
                                <video id="inlineFaceVideo" class="face-video" autoplay playsinline muted style="width:100%; display:block;"></video>
                                <div class="camera-frame"></div>
                            </div>
                            <canvas id="inlineFaceCanvas" class="face-canvas" width="320" height="240" style="display:none"></canvas>
                            <div class="inline-face-actions" style="display:flex; gap:10px;">
                                <button id="faceSignupBtn" type="button" class="btn-outline" style="flex:1;"><i class="bi bi-camera"></i> Ouvrir la camera</button>
                                <button id="validateFaceSignupBtn" type="button" class="btn-primary" style="flex:1;"><i class="bi bi-check2-circle"></i> Valider le visage</button>
                            </div>
                        </div>
                    </section>
                    <p id="faceSignupStatus" class="helper-text"></p>

                    <button type="submit" class="btn-primary auth-submit mt-4" style="width:100%;">S'inscrire <i class="bi bi-person-plus"></i></button>
                </form>

                <p class="legal">En continuant, vous acceptez les conditions d'utilisation et la politique de confidentialite.</p>
            </section>
        </section>
    </main>

    <div id="faceModal" class="face-modal" hidden>
        <div class="face-modal-card">
            <h3>Scan du visage</h3>
            <p id="faceModalText" class="helper-text">Positionnez votre visage dans le cadre puis capturez.</p>
            <div class="camera-shell">
                <video id="faceVideo" class="face-video" autoplay playsinline muted></video>
                <div class="camera-frame"></div>
            </div>
            <canvas id="faceCanvas" class="face-canvas" width="320" height="240"></canvas>
            <div class="face-modal-actions">
                <button id="captureFaceBtn" type="button" class="btn-primary">Capturer</button>
                <button id="closeFaceModalBtn" type="button" class="btn-secondary">Annuler</button>
            </div>
        </div>
    </div>

    <footer class="site-contact-bar">
        <div class="container-fluid">
            <div class="site-contact-inner">
                <span class="contact-label">Contact Idene Parfum</span>
                <a href="tel:+21558606233">+21558606233</a>
                <a href="tel:+21658367468">+21658367468</a>
                <a href="mailto:idene.parfum@gmail.com">idene.parfum@gmail.com</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/assets/js/auth.js?v=10"></script>
</body>
</html>
HTML);
    }

    #[Route('/verify-reset-code', name: 'app_verify_reset_code', methods: ['GET'])]
    public function verifyResetCodePage(): Response
    {
        return new Response(<<<'HTML'
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IDENE PARFUM | Verifier le code</title>
    <meta name="description" content="Verification du code de reinitialisation Idene Parfum">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css?v=9">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card auth-compact">
            <div class="auth-form-panel auth-form-panel--compact">
                <header class="card-header">
                    <div class="logo-wrap">
                        <img src="/assets/images/logo.png" alt="Logo Idene Parfum" class="logo-image">
                    </div>
                    <p class="hero-tag">Verification securisee</p>
                    <h1>IDENE PARFUM</h1>
                    <p class="intro-text">Entrez le code recu pour continuer la reinitialisation.</p>
                </header>

                <form id="verifyResetCodeForm" class="form active" action="#" method="post">
                    <div class="form-intro">
                        <p class="form-kicker">Recuperation</p>
                        <h2>Verifier le code</h2>
                    </div>
                    <p id="verifyResetCodeMessage" class="helper-text form-message"></p>

                    <div class="floating-label">
                        <input type="email" name="email" id="verifyEmail" placeholder=" " required>
                        <label for="verifyEmail">Email</label>
                    </div>

                    <button id="sendResetCodeBtn" type="button" class="btn-primary auth-submit auth-submit-secondary">Envoyer le code</button>

                    <div class="floating-label">
                        <input type="text" name="code" id="verifyCode" inputmode="numeric" maxlength="6" placeholder=" " required>
                        <label for="verifyCode">Code</label>
                    </div>

                    <button type="submit" class="btn-primary auth-submit">Verifier le code</button>
                    <a href="/auth" class="muted-link muted-link-center">Retour au login</a>
                </form>
            </div>
        </section>
    </main>

    <footer class="site-contact-bar">
        <div class="container-fluid">
            <div class="site-contact-inner">
                <span class="contact-label">Contact Idene Parfum</span>
                <a href="tel:+21558606233">+21558606233</a>
                <a href="tel:+21658367468">+21658367468</a>
                <a href="mailto:idene.parfum@gmail.com">idene.parfum@gmail.com</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/assets/js/verify-reset-code.js?v=3"></script>
</body>
</html>
HTML);
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET'])]
    public function resetPasswordPage(): Response
    {
        return new Response(<<<'HTML'
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IDENE PARFUM | Nouveau mot de passe</title>
    <meta name="description" content="Reinitialisation du mot de passe Idene Parfum">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css?v=9">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card auth-compact">
            <div class="auth-form-panel auth-form-panel--compact">
                <header class="card-header">
                    <div class="logo-wrap">
                        <img src="/assets/images/logo.png" alt="Logo Idene Parfum" class="logo-image">
                    </div>
                    <p class="hero-tag">Mise a jour securisee</p>
                    <h1>IDENE PARFUM</h1>
                    <p class="intro-text">Choisissez un nouveau mot de passe pour votre compte.</p>
                </header>

                <form id="resetPasswordForm" class="form active" action="#" method="post">
                    <div class="form-intro">
                        <p class="form-kicker">Reinitialisation</p>
                        <h2>Nouveau mot de passe</h2>
                    </div>
                    <p id="resetPasswordMessage" class="helper-text form-message"></p>
                    <input id="resetToken" type="hidden" value="">

                    <div class="floating-label">
                        <input type="password" name="password" id="resetPassword" minlength="8" placeholder=" " required>
                        <label for="resetPassword">Nouveau mot de passe</label>
                    </div>

                    <div class="floating-label">
                        <input type="password" name="password_confirm" id="resetPasswordConfirm" minlength="8" placeholder=" " required>
                        <label for="resetPasswordConfirm">Confirmer le mot de passe</label>
                    </div>

                    <button type="submit" class="btn-primary auth-submit">Mettre a jour</button>
                    <a href="/auth" class="muted-link muted-link-center">Retour au login</a>
                </form>
            </div>
        </section>
    </main>

    <footer class="site-contact-bar">
        <div class="container-fluid">
            <div class="site-contact-inner">
                <span class="contact-label">Contact Idene Parfum</span>
                <a href="tel:+21558606233">+21558606233</a>
                <a href="tel:+21658367468">+21658367468</a>
                <a href="mailto:idene.parfum@gmail.com">idene.parfum@gmail.com</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/assets/js/reset-password.js?v=5"></script>
</body>
</html>
HTML);
    }
}
