# 🚀 Démarrage Rapide - IDENE

## ⚠️ IMPORTANT: Démarrer le Serveur d'abord!

Avant d'utiliser le site, vous DEVEZ démarrer le serveur Symfony.

---

## 🎯 Méthode Simple (Windows)

### Double-cliquez sur ce fichier:
```
start-localhost.bat
```

Une fenêtre noire (terminal) va s'ouvrir avec:
```
PHP 8.x Development Server (http://localhost:8000) started
```

**⚠️ NE FERMEZ PAS CETTE FENÊTRE!**

---

## 🌐 Ouvrir le Site

Une fois le serveur démarré, ouvrez votre navigateur et allez sur:

### Page Publique (Nouveau site B2B)
```
http://localhost:8000/accueil
```

### Page de Connexion
```
http://localhost:8000/auth
```

### Dashboard Client
```
http://localhost:8000/dashboard
```

---

## ✅ Vérification

Si tout fonctionne, vous devriez voir:

1. **Sur `/accueil`**:
   - Navbar avec logo IDENE
   - Hero section "L'approvisionnement parfum, simplifié"
   - Catalogue de produits
   - Footer complet

2. **Clic sur "Connexion"**:
   - Redirige vers `/auth`
   - Page de connexion s'affiche
   - Pas d'erreur "ERR_CONNECTION_REFUSED"

---

## ❌ Si ça ne marche pas

### Erreur: "ERR_CONNECTION_REFUSED"
**Cause**: Le serveur n'est pas démarré
**Solution**: Double-cliquez sur `start-localhost.bat`

### Erreur: "Page blanche"
**Solution**:
```bash
php bin/console cache:clear
```
Puis redémarrez le serveur

### Erreur: "PHP not found"
**Solution**: Installez PHP 8.1+ depuis https://windows.php.net/download/

---

## 🎨 Test des Fonctionnalités

Une fois le serveur démarré, testez:

### Navbar
- ✅ Clic "Connexion" → Va sur `/auth`
- ✅ Clic "Demander un accès" → Va sur `/auth`
- ✅ Clic "Catalogue" → Scroll vers catalogue
- ✅ Menu mobile (réduire fenêtre)

### Catalogue
- ✅ Filtres: Tous, Principal, Smart, Enfant
- ✅ Recherche: Taper "luxe"
- ✅ Clic sur produit → Quick-view modal
- ✅ Clic bouton panier → Alert "Connectez-vous"

### Scroll
- ✅ Scroll en bas → Bouton ⬆️ apparaît
- ✅ Animations au scroll

---

## 📞 Besoin d'Aide?

Si le problème persiste:

1. Vérifiez que PHP est installé:
   ```bash
   php -v
   ```
   Devrait afficher: `PHP 8.1.x` ou plus

2. Vérifiez que vous êtes dans le bon dossier:
   ```bash
   dir
   ```
   Devrait lister: `composer.json`, `public/`, `src/`, etc.

3. Installez les dépendances:
   ```bash
   composer install
   ```

---

## 🎉 C'est Tout!

Une fois le serveur démarré avec `start-localhost.bat`, tous les boutons fonctionneront parfaitement!

**N'oubliez pas**: Le terminal doit rester ouvert pendant l'utilisation du site.

---

**Bon développement! 🚀**
