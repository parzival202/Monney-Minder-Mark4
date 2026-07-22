# MoneyMinder Mark 4

MoneyMinder Mark 4 est un assistant de décision financière personnel. Son objectif n'est pas seulement d'enregistrer les dépenses, mais d'aider l'utilisateur à décider avant de dépenser, protéger son épargne et réserver de l'argent pour ses projets.

## Principes produit

- afficher l'argent réellement dépensable après les engagements, l'épargne protégée et les réservations ;
- évaluer l'impact d'un achat sur le reste à vivre quotidien ;
- proposer des alternatives concrètes lorsqu'un projet est risqué ;
- permettre de réserver immédiatement ou progressivement un budget ;
- utiliser le même moteur de décision dans l'application et dans Telegram ;
- isoler strictement les données de chaque utilisateur.

## Socle actuel

- Laravel 12 ;
- React et TypeScript avec Inertia ;
- SQLite en développement, PostgreSQL prévu pour Debian ;
- authentification Laravel ;
- inscription publique désactivée ;
- moteur de décision financier testé ;
- bot Telegram décisionnel avec boutons de réservation et historique des échanges ;
- import des messages et notifications d’un export Telegram Mark 3.

Tous les montants sont stockés sous forme d'entiers afin d'éviter les erreurs d'arrondi.

## Installation locale

```powershell
C:\xampp\php\php.exe -d extension=zip composer.phar install
npm install
Copy-Item .env.example .env
C:\xampp\php\php.exe artisan key:generate
New-Item -ItemType File database\database.sqlite -Force
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan money-minder:setup-owner
npm run dev
```

La commande de création du propriétaire demande le nom, l'adresse e-mail et un mot de passe d'au moins 12 caractères. Elle refuse de s'exécuter dès qu'un utilisateur existe déjà.

## Vérifications

```powershell
C:\xampp\php\php.exe artisan test
npm run build
```

## Import Telegram Mark 3

Dans Telegram Desktop, exporter la conversation du bot au format JSON, puis lancer :

```powershell
C:\xampp\php\php.exe artisan money-minder:import-telegram-export "C:\chemin\result.json" --user=franck@example.com
```

L’import est réexécutable : un même message n’est pas dupliqué. Les anciennes notifications deviennent consultables dans la page Telegram.

Le bot accepte aussi `/depense 2500 Déjeuner`, puis propose les catégories et la nature de l’achat avec des boutons. `/solde` affiche le disponible actuel et `/projets` les projets actifs. Un résumé automatique est envoyé chaque matin à 7 h 30 lorsque cette préférence est activée.

## Déploiement Debian

La procédure sans nom de domaine, avec Tailscale Funnel, PostgreSQL, démarrage automatique et sauvegardes quotidiennes, se trouve dans `deploy/debian/README.md`.

## Prochaines tranches

1. onboarding financier de Franck Olivier ;
2. comptes, revenus, charges et dépenses ;
3. tableau de bord du reste réellement dépensable ;
4. interface projets et réservations ;
5. assistant de décision dans l'application (terminé) ;
6. bot Telegram interactif et import de l’historique Mark 3 (première version terminée) ;
7. invitations pour les proches ;
8. déploiement et sauvegardes Debian.
