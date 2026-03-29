# Checklist de mise en production

Ce projet peut etre heberge, mais seulement apres cette verification minimale.

## 1. Variables d'environnement

Sur le serveur, definir au minimum:

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=changer-par-une-cle-longue-et-secrete

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=idene_parfum
DB_CHARSET=utf8mb4
DB_USER=utilisateur_sql_prod
DB_PASSWORD=mot_de_passe_sql_fort

MAILER_DSN=smtp://utilisateur:motdepasse@smtp-relay.brevo.com:587
BREVO_API_KEY=cle_api_brevo
MAILER_FROM_EMAIL=contact@votredomaine.com
MAILER_FROM_NAME="Societe IDENE"
```

Ne jamais reutiliser les secrets vus en local si le projet a ete partage.

## 2. Commandes avant mise en ligne

```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## 3. Base de donnees

- Creer la base MySQL de production.
- Importer les fichiers SQL du dossier `database/`.
- Verifier que l'utilisateur SQL de production n'est pas `root`.
- Faire un test de connexion reel depuis le serveur web.

## 4. Verification fonctionnelle

Tester au minimum:

- connexion client
- connexion admin
- creation de commande client
- creation de commande admin
- paiement facture
- edition profil
- PDF facture
- PDF commande admin
- envoi d'email de reinitialisation

## 5. Points de securite

- rotation des secrets mail si les anciennes valeurs ont ete exposees
- HTTPS actif sur le domaine
- permissions d'ecriture uniquement sur `var/`
- ne jamais exposer `.env.local`

## 6. Surveillance apres mise en ligne

- verifier les logs PHP / Symfony
- surveiller CPU, RAM et MySQL
- tester les ecrans admin avec des donnees reelles
- prevoir de la pagination si le volume devient important
