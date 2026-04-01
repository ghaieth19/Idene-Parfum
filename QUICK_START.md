# ⚡ IDENE Website - Quick Start Guide

## 🚀 Démarrage en 3 Minutes

### 1️⃣ Vérifier les Fichiers (30 secondes)

```bash
# Vérifier que tout est en place
ls src/Controller/PublicWebsiteController.php
ls src/Controller/PublicApiController.php
ls public/assets/css/public-website.css
ls public/assets/js/public-website.js
```

✅ Tous les fichiers doivent exister

### 2️⃣ Clear Cache Symfony (30 secondes)

```bash
php bin/console cache:clear
```

### 3️⃣ Lancer le Serveur (30 secondes)

```bash
# Option 1: Symfony CLI (recommandé)
symfony server:start

# Option 2: PHP Built-in Server
php -S localhost:8000 -t public
```

### 4️⃣ Ouvrir dans le Navigateur (30 secondes)

```
http://localhost:8000/accueil
```

### 5️⃣ Tester les Fonctionnalités (60 secondes)

✅ **Navbar**: Scroll pour voir l'effet sticky
✅ **Hero**: Vérifier les animations des stats
✅ **Catalogue**: Cliquer sur les filtres (Tous, Principal, Smart, Enfant)
✅ **Recherche**: Taper "luxe" dans la barre de recherche
✅ **Quick View**: Cliquer sur une carte produit
✅ **Menu Mobile**: Réduire la fenêtre et cliquer sur le hamburger
✅ **Scroll to Top**: Scroll en bas, cliquer sur le bouton ⬆️

---

## 🎯 Routes Principales

| URL | Description |
|-----|-------------|
| `/` | Redirige vers `/accueil` |
| `/accueil` | **Page publique principale** ⭐ |
| `/auth` | Page de connexion |
| `/dashboard` | Dashboard client (auth requis) |

---

## 🔧 Commandes Utiles

### Clear Cache
```bash
php bin/console cache:clear
```

### Vérifier les Routes
```bash
php bin/console debug:router | grep accueil
```

### Tester l'API
```bash
# Tous les produits
curl http://localhost:8000/api/public/products

# Filtrer par catégorie
curl http://localhost:8000/api/public/products?filter=principal

# Rechercher
curl http://localhost:8000/api/public/products?search=luxe

# Stats
curl http://localhost:8000/api/public/stats
```

---

## 🎨 Personnalisation Rapide

### Changer les Couleurs (2 minutes)

Éditer `public/assets/css/public-website.css` ligne 8:

```css
:root {
    --forest-green: #1B3A2F;  /* ← Votre couleur principale */
    --warm-ivory: #F5F0E8;    /* ← Votre couleur de fond */
    --terracotta: #C4622D;    /* ← Votre accent 1 */
    --gold: #C9A84C;          /* ← Votre accent 2 */
}
```

Sauvegarder et rafraîchir le navigateur (Ctrl+F5)

### Modifier le Titre Hero (1 minute)

Éditer `src/Controller/PublicWebsiteController.php` ligne 60:

```html
<h1 class="hero-title">Votre nouveau titre ici</h1>
<p class="hero-subtitle">Votre nouveau sous-titre</p>
```

### Ajouter un Produit (2 minutes)

Éditer `public/assets/js/public-website.js` ligne 40:

```javascript
const products = [
    // ... produits existants
    {
        id: 9,
        name: 'Votre Nouveau Parfum',
        brand: 'Votre Marque',
        segment: 'PRINCIPAL',
        price: '50.00',
        available: true,
        gamme: 'principal'
    }
];
```

---

## 📱 Test Responsive

### Desktop
```
Ouvrir: http://localhost:8000/accueil
Taille: 1920x1080
```

### Tablet
```
F12 → Toggle Device Toolbar
Sélectionner: iPad (768x1024)
```

### Mobile
```
F12 → Toggle Device Toolbar
Sélectionner: iPhone 12 Pro (390x844)
```

---

## 🐛 Problèmes Courants

### ❌ Page blanche
**Solution**:
```bash
# Vérifier les logs
tail -f var/log/dev.log

# Clear cache
php bin/console cache:clear

# Vérifier permissions
chmod -R 777 var/cache var/log
```

### ❌ CSS ne se charge pas
**Solution**:
```bash
# Vérifier que le fichier existe
ls -la public/assets/css/public-website.css

# Vérifier dans le navigateur
http://localhost:8000/assets/css/public-website.css
```

### ❌ JavaScript ne fonctionne pas
**Solution**:
```
1. Ouvrir la console (F12)
2. Vérifier les erreurs
3. Vérifier que le fichier se charge:
   http://localhost:8000/assets/js/public-website.js
```

### ❌ Produits ne s'affichent pas
**Solution**:
```
1. Ouvrir la console (F12)
2. Attendre 500ms (chargement différé)
3. Vérifier l'API:
   curl http://localhost:8000/api/public/products
```

---

## 📚 Documentation Complète

Pour plus de détails, consulter:

- **PUBLIC_WEBSITE_GUIDE.md** - Guide utilisateur complet
- **DEPLOYMENT_PUBLIC_WEBSITE.md** - Instructions de déploiement
- **SITE_STRUCTURE.md** - Structure visuelle du site
- **WEBSITE_SUMMARY.md** - Résumé complet

---

## ✅ Checklist de Vérification

Avant de déployer en production:

- [ ] Tous les fichiers sont créés
- [ ] Cache Symfony cleared
- [ ] Site accessible sur `/accueil`
- [ ] Navbar fonctionne
- [ ] Catalogue se charge
- [ ] Filtres fonctionnent
- [ ] Recherche fonctionne
- [ ] Quick-view modal s'ouvre
- [ ] Menu mobile fonctionne
- [ ] Responsive testé (mobile/tablet/desktop)
- [ ] API répond correctement
- [ ] Aucune erreur dans la console
- [ ] Performance acceptable (< 3s load)

---

## 🎉 C'est Prêt!

Votre site IDENE B2B est maintenant opérationnel!

### Prochaines Étapes

1. **Personnaliser** les couleurs et textes
2. **Connecter** à votre base de données
3. **Ajouter** vos vrais produits
4. **Tester** sur différents appareils
5. **Déployer** en production

### Support

- Email: idene.parfum@gmail.com
- Tél: +215 58 60 62 33 / +216 58 36 74 68

---

**Bon développement! 🚀**

*L'approvisionnement parfum, simplifié.*
