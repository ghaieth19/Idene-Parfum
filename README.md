# Idene-Parfum

## 🌸 Plateforme B2B Professionnelle de Distribution de Parfums

IDENE est une plateforme e-commerce B2B de luxe pour parfumeries et revendeurs professionnels. Commandez votre gamme complète de parfums en quelques clics.

---

## ✨ Nouveau: Site Web Public + Design Harmonisé

Un site web B2B de luxe complet avec design harmonisé sur toutes les interfaces:

- ✅ **Design System Unifié** - Palette IDENE cohérente (Forest Green, Warm Ivory, Terracotta, Gold)
- ✅ **Site Public** - 9 sections professionnelles avec animations fluides
- ✅ **Espace Client** - Design harmonisé avec la nouvelle palette
- ✅ **Dashboard Admin** - Interface cohérente avec le reste de la plateforme
- ✅ Catalogue interactif avec filtres et recherche en temps réel
- ✅ Quick-view modal sophistiqué pour les produits
- ✅ Menu mobile fluide et responsive
- ✅ Animations subtiles et performantes
- ✅ 100% responsive (mobile-first)
- ✅ SEO optimisé avec structured data
- ✅ Accessible (WCAG AA)

### 🚀 Démarrage Rapide

**⚠️ IMPORTANT: Démarrez le serveur d'abord!**

#### Méthode Simple (Windows)
```bash
# Double-cliquez sur:
DEMARRER_SERVEUR.bat
```

#### Méthode Ligne de Commande
```bash
# 1. Clear cache
php bin/console cache:clear

# 2. Lancer le serveur
symfony server:start
# ou
php -S localhost:8000 -t public

# 3. Ouvrir dans le navigateur
http://localhost:8000/accueil
```

**⚠️ Ne fermez pas le terminal! Le serveur doit rester actif.**

#### Problème "ERR_CONNECTION_REFUSED"?
Voir **[PROBLEME_RESOLU.md](PROBLEME_RESOLU.md)** pour la solution complète.

### 📚 Documentation

- **[GUIDE_COMPLET_FINAL.md](GUIDE_COMPLET_FINAL.md)** - 🎉 Guide complet final (COMMENCEZ ICI!)
- **[DESIGN_HARMONISE.md](DESIGN_HARMONISE.md)** - 🎨 Design harmonisé expliqué
- **[DEMARRAGE_RAPIDE.md](DEMARRAGE_RAPIDE.md)** - Guide de démarrage simple 🚀
- **[PROBLEME_RESOLU.md](PROBLEME_RESOLU.md)** - Solution aux problèmes de connexion 🔧
- **[QUICK_START.md](QUICK_START.md)** - Démarrage en 3 minutes ⚡
- **[WEBSITE_SUMMARY.md](WEBSITE_SUMMARY.md)** - Résumé complet du site
- **[PUBLIC_WEBSITE_GUIDE.md](PUBLIC_WEBSITE_GUIDE.md)** - Guide utilisateur détaillé
- **[DEPLOYMENT_PUBLIC_WEBSITE.md](DEPLOYMENT_PUBLIC_WEBSITE.md)** - Instructions de déploiement
- **[SITE_STRUCTURE.md](SITE_STRUCTURE.md)** - Structure visuelle

### 🎯 Routes Principales

| Route | Description |
|-------|-------------|
| `/` | Redirige vers `/accueil` |
| `/accueil` | **Page publique B2B** ⭐ |
| `/auth` | Connexion/Inscription |
| `/dashboard` | Dashboard client (auth requis) |
| `/admin` | Dashboard admin (auth requis) |

### 📁 Fichiers Créés

```
src/Controller/
├── PublicWebsiteController.php  (17 KB) - Page d'accueil publique
└── PublicApiController.php      (5 KB)  - API publique

public/assets/css/
├── idene-design-system.css      (15 KB) - 🆕 Système de design unifié
├── public-website.css           (26 KB) - Site public
├── user-app.css                 (harmonisé) - Espace client
└── admin-app.css                (harmonisé) - Dashboard admin

public/assets/js/
└── public-website.js            (23 KB) - Interactivité

Documentation/
├── GUIDE_COMPLET_FINAL.md       - 🆕 Guide complet (COMMENCEZ ICI!)
├── DESIGN_HARMONISE.md          - 🆕 Design harmonisé
├── DEMARRAGE_RAPIDE.md
├── PROBLEME_RESOLU.md
├── QUICK_START.md
├── WEBSITE_SUMMARY.md
├── PUBLIC_WEBSITE_GUIDE.md
├── DEPLOYMENT_PUBLIC_WEBSITE.md
└── SITE_STRUCTURE.md
```

---

## 🛠️ Technologies

- **Backend**: Symfony 6.4 + PHP 8.1+
- **Frontend**: HTML5 + CSS3 + JavaScript ES6+
- **Database**: MySQL/PostgreSQL
- **Fonts**: Cormorant Garamond (serif) + Inter (sans-serif)
- **Icons**: Bootstrap Icons

---

## 🎨 Identité Visuelle

### Palette de Couleurs
- **Forest Green** (#1B3A2F) - Couleur principale
- **Warm Ivory** (#F5F0E8) - Fond chaleureux
- **Terracotta** (#C4622D) - Accent énergique
- **Gold** (#C9A84C) - Accent luxe

### Style
Luxury editorial meets professional B2B catalog

---

## 📱 Fonctionnalités

### Site Public
- Hero section avec animations
- Catalogue produits avec filtres
- Recherche en temps réel
- Quick-view modal
- Collections vedettes
- Témoignages clients
- Menu mobile responsive
- Scroll to top
- Newsletter

### Espace Client
- Dashboard personnalisé
- Commandes en ligne
- Historique des commandes
- Gestion des factures
- Profil utilisateur
- Suivi des livraisons

### Espace Admin
- Gestion des produits
- Gestion des commandes
- Gestion des clients
- Statistiques
- Génération de factures PDF

---

## 🔐 Sécurité

- Authentification sécurisée
- Reconnaissance faciale (WebAuthn)
- Réinitialisation de mot de passe
- Sessions sécurisées
- Protection CSRF

---

## 📊 API Endpoints

### Public
- `GET /api/public/products` - Liste des produits
- `GET /api/public/stats` - Statistiques publiques

### Client (Auth Required)
- `POST /api/auth/login` - Connexion
- `POST /api/auth/register` - Inscription
- `GET /api/products` - Catalogue complet
- `POST /api/orders` - Créer une commande
- `GET /api/orders` - Historique des commandes

### Admin (Auth Required)
- `GET /api/admin/orders` - Toutes les commandes
- `PUT /api/admin/orders/{id}` - Mettre à jour une commande
- `GET /api/admin/stats` - Statistiques complètes

---

## 🚀 Déploiement

### Prérequis
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Node.js (optionnel, pour minification)

### Installation

```bash
# 1. Cloner le projet
git clone https://github.com/votre-repo/idene-parfum.git
cd idene-parfum

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres

# 4. Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Clear cache
php bin/console cache:clear

# 6. Lancer le serveur
symfony server:start
```

### Production

Voir **[DEPLOYMENT_PUBLIC_WEBSITE.md](DEPLOYMENT_PUBLIC_WEBSITE.md)** pour les instructions complètes.

---

## 📞 Contact

### IDENE PARFUM
- **Email**: idene.parfum@gmail.com
- **Tél 1**: +215 58 60 62 33
- **Tél 2**: +216 58 36 74 68

---

## 📄 Licence

Proprietary - © 2024 IDENE PARFUM. Tous droits réservés.

---

## 🎉 Statut

✅ **Site public opérationnel**
✅ **Espace client fonctionnel**
✅ **Espace admin fonctionnel**
✅ **API complète**
✅ **Documentation complète**
✅ **Prêt pour la production**

---

*L'approvisionnement parfum, simplifié.*
