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
        return new RedirectResponse('/auth');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css?v=5">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card auth-layout">
            <section class="auth-showcase">
                <header class="card-header card-header-left">
                    <div class="logo-wrap">
                        <img src="/assets/images/logo.png" alt="Logo Idene Parfum" class="logo-image">
                    </div>
                    <p class="hero-tag">Experience digitale premium</p>
                    <h1>IDENE PARFUM</h1>
                    <p class="intro-text">Une interface claire pour commander, suivre vos factures et gerer votre relation commerciale avec une presentation plus professionnelle.</p>
                </header>
                <div class="showcase-grid">
                    <article class="showcase-card">
                        <span>Commande rapide</span>
                        <strong>Catalogue lisible</strong>
                        <p>Navigation simple, recherche directe et etat du stock visible au premier regard.</p>
                    </article>
                    <article class="showcase-card">
                        <span>Suivi intelligent</span>
                        <strong>Factures centralisees</strong>
                        <p>Retrouvez vos documents, l'historique des commandes et les actions importantes sans confusion.</p>
                    </article>
                    <article class="showcase-card">
                        <span>Acces securise</span>
                        <strong>Connexion moderne</strong>
                        <p>Email, mot de passe et reconnaissance faciale dans une experience plus rassurante pour le client.</p>
                    </article>
                </div>
            </section>

            <section class="auth-form-panel">
                <div class="switcher">
                    <button class="tab active" data-target="login">Connexion</button>
                    <button class="tab" data-target="signup">Creer un compte</button>
                </div>

                <form id="loginForm" class="form active" action="#" method="post">
                    <div class="form-intro">
                        <p class="form-kicker">Bienvenue</p>
                        <h2>Accedez a votre espace client</h2>
                    </div>
                    <p id="loginMessage" class="helper-text form-message"></p>
                    <label>Email
                        <input type="email" name="email" placeholder="client@idene.dz" required>
                    </label>
                    <label>Mot de passe
                        <input type="password" name="password" placeholder="********" required minlength="8">
                    </label>
                    <section class="biometric-panel">
                        <div>
                            <p class="panel-title">Connexion par reconnaissance faciale</p>
                            <p class="panel-text">Ouvrez la camera, capturez votre visage puis laissez la plateforme verifier automatiquement votre acces.</p>
                        </div>
                        <button id="faceLoginBtn" type="button" class="btn-secondary">Utiliser mon visage</button>
                    </section>
                    <p id="faceStatus" class="helper-text"></p>
                    <button type="submit" class="btn-primary">Se connecter</button>
                    <a href="/verify-reset-code" id="forgotToggle" class="muted-link">Mot de passe oublie ?</a>
                </form>

                <form id="signupForm" class="form" action="#" method="post">
                    <div class="form-intro">
                        <p class="form-kicker">Nouvelle parfumerie</p>
                        <h2>Creer un compte professionnel</h2>
                    </div>
                    <p id="signupMessage" class="helper-text form-message"></p>
                    <div class="grid">
                        <label>Nom
                            <input type="text" name="last_name" required>
                        </label>
                        <label>Prenom
                            <input type="text" name="first_name" required>
                        </label>
                    </div>
                    <label>Nom de la parfumerie
                        <input type="text" name="shop_name" required>
                    </label>
                    <div class="grid">
                        <label>Telephone
                            <input type="tel" name="phone" required>
                        </label>
                        <label>Localisation
                            <input type="text" name="location" required>
                        </label>
                    </div>
                    <label>Email
                        <input type="email" name="email" required>
                    </label>
                    <label>Mot de passe
                        <input type="password" name="password" minlength="8" required>
                    </label>
                    <label class="check">
                        <input id="faceSignupOptIn" type="checkbox" name="face_signup_opt_in" checked>
                        <span>Lier ce compte a la reconnaissance faciale de mon appareil</span>
                    </label>
                    <section class="biometric-panel">
                        <div>
                            <p class="panel-title">Enregistrement biometrique</p>
                            <p class="panel-text">Capturez votre visage une seule fois pour accelerer les prochaines connexions et renforcer la securite du compte.</p>
                        </div>
                        <div class="inline-face-box">
                            <div class="inline-camera-shell">
                                <video id="inlineFaceVideo" class="face-video" autoplay playsinline muted></video>
                                <div class="camera-frame"></div>
                            </div>
                            <canvas id="inlineFaceCanvas" class="face-canvas" width="320" height="240"></canvas>
                            <div class="inline-face-actions">
                                <button id="faceSignupBtn" type="button" class="btn-secondary">Ouvrir la camera</button>
                                <button id="validateFaceSignupBtn" type="button" class="btn-primary">Valider le visage</button>
                            </div>
                        </div>
                    </section>
                    <p id="faceSignupStatus" class="helper-text"></p>
                    <button type="submit" class="btn-primary">Creer mon compte</button>
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
    <script src="/assets/js/auth.js?v=8"></script>
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
    <link rel="stylesheet" href="/assets/css/auth.css?v=5">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card auth-compact">
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
                <label>Email
                    <input type="email" name="email" required>
                </label>
                <button id="sendResetCodeBtn" type="button" class="btn-primary">Envoyer le code</button>
                <label>Code
                    <input type="text" name="code" inputmode="numeric" maxlength="6" placeholder="123456" required>
                </label>
                <button type="submit" class="btn-primary">Verifier le code</button>
                <a href="/auth" class="muted-link">Retour au login</a>
            </form>
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
    <script src="/assets/js/verify-reset-code.js?v=2"></script>
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
    <link rel="stylesheet" href="/assets/css/auth.css?v=5">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card auth-compact">
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
                <label>Nouveau mot de passe
                    <input type="password" name="password" minlength="8" required>
                </label>
                <label>Confirmer le mot de passe
                    <input type="password" name="password_confirm" minlength="8" required>
                </label>
                <button type="submit" class="btn-primary">Mettre a jour</button>
                <a href="/auth" class="muted-link">Retour au login</a>
            </form>
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
    <script src="/assets/js/reset-password.js?v=4"></script>
</body>
</html>
HTML);
    }
}
