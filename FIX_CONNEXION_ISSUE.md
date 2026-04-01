# 🔧 Résolution du Problème de Connexion

## ❌ Problème Actuel

Lorsque vous cliquez sur "Connexion", vous obtenez:
```
127.0.0.1 a refusé de se connecter
ERR_CONNECTION_REFUSED
```

## ✅ Solution

Le serveur Symfony n'est **pas démarré**. Voici comment le démarrer:

### Option 1: Symfony CLI (Recommandé)

```bash
# Ouvrir un terminal dans le dossier du projet
cd C:\Users\rayen\Downloads\Idene-Parfum-main\Idene-Parfum-main

# Démarrer le serveur
symfony server:start
```

Le serveur démarrera sur `http://127.0.0.1:8000`

### Option 2: PHP Built-in Server

```bash
# Ouvrir un terminal dans le dossier du projet
cd C:\Users\rayen\Downloads\Idene-Parfum-main\Idene-Parfum-main

# Démarrer le serveur
php -S 127.0.0.1:8000 -t public
```

### Option 3: Utiliser le fichier .bat existant

Double-cliquer sur:
```
start-localhost.bat
```

---

## 🔍 Vérification

Une fois le serveur démarré, vous devriez voir:

```
[OK] Web server listening on http://127.0.0.1:8000
```

Ensuite, ouvrez votre navigateur et allez sur:
- `http://127.0.0.1:8000/accueil` - Page publique
- `http://127.0.0.1:8000/auth` - Page de connexion

---

## ✅ Test des Boutons

Une fois le serveur démarré, testez:

1. **Navbar "Connexion"** → Doit rediriger vers `/auth`
2. **Navbar "Demander un accès"** → Doit rediriger vers `/auth`
3. **Hero "Demander un accès pro"** → Doit rediriger vers `/auth`
4. **CTA Banner "Demander mon accès"** → Doit rediriger vers `/auth`
5. **Footer "Connexion"** → Doit rediriger vers `/auth`
6. **Quick-view modal "Se connecter"** → Doit rediriger vers `/auth`

Tous ces liens sont **corrects** dans le code. Le problème était simplement que le serveur n'était pas démarré.

---

## 🚀 Commandes Utiles

### Démarrer le serveur
```bash
symfony server:start
# ou
php -S 127.0.0.1:8000 -t public
```

### Arrêter le serveur
```bash
symfony server:stop
# ou
Ctrl+C dans le terminal
```

### Vérifier si le serveur tourne
```bash
symfony server:status
```

### Clear cache (si problème)
```bash
php bin/console cache:clear
```

---

## 📝 Note Importante

Le serveur doit **rester actif** dans le terminal pendant que vous utilisez le site. Ne fermez pas le terminal!

Si vous voyez des erreurs au démarrage, vérifiez:
1. PHP est installé: `php -v`
2. Composer est installé: `composer -V`
3. Les dépendances sont installées: `composer install`

---

## 🎯 Résumé

**Le problème n'est PAS dans le code des boutons.**
**Le problème est que le serveur Symfony n'est pas démarré.**

**Solution**: Démarrer le serveur avec `symfony server:start` ou `php -S 127.0.0.1:8000 -t public`

Une fois le serveur démarré, tous les boutons fonctionneront parfaitement! ✅
