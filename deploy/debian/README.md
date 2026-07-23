# Déploiement Debian sans nom de domaine

La cible est `/var/www/moneyminder`, avec PostgreSQL, PHP-FPM, Nginx et Tailscale Funnel. Tailscale fournit l’adresse HTTPS gratuite en `*.ts.net`; aucun port de la box et aucun domaine acheté ne sont nécessaires.

## 1. Paquets

```bash
sudo apt update
sudo apt install nginx postgresql php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip unzip git curl nodejs npm
curl -fsSL https://tailscale.com/install.sh | sh
sudo tailscale up
```

Installer Composer depuis sa procédure officielle, puis cloner le dépôt dans `/var/www/moneyminder`.

## 2. Application

```bash
cd /var/www/moneyminder
composer install --no-dev --optimize-autoloader
npm ci
npm run build
cp .env.example .env
php artisan key:generate
```

Configurer `.env` avec `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://nom-machine.nom-tailnet.ts.net`, `DB_CONNECTION=pgsql` et les identifiants PostgreSQL. Ne jamais remplacer `APP_KEY` lors d’une mise à jour : elle protège notamment le jeton Telegram chiffré.

```bash
php artisan migrate --force
php artisan money-minder:setup-owner
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 3. Nginx et adresse HTTPS gratuite

```bash
sudo cp deploy/debian/nginx.conf.example /etc/nginx/sites-available/moneyminder
sudo ln -s /etc/nginx/sites-available/moneyminder /etc/nginx/sites-enabled/moneyminder
sudo nginx -t
sudo systemctl reload nginx
sudo tailscale funnel --bg 8080
tailscale funnel status
```

L’URL affichée par la dernière commande devient `APP_URL`. Après modification, lancer `php artisan optimize`. Le bouton « Activer le bot Telegram » pourra ensuite enregistrer le webhook HTTPS.

## 4. Résumés et sauvegardes automatiques

```bash
sudo chmod 750 deploy/debian/backup.sh
sudo cp deploy/debian/moneyminder-scheduler.service /etc/systemd/system/
sudo cp deploy/debian/moneyminder-backup.service /etc/systemd/system/
sudo cp deploy/debian/moneyminder-backup.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now moneyminder-scheduler.service
sudo systemctl enable --now moneyminder-backup.timer
```

Les sauvegardes PostgreSQL sont conservées 14 jours dans `/var/backups/moneyminder`. Une copie sur un autre disque devra être ajoutée avant de considérer le serveur comme totalement protégé.

## 5. Mise à jour

```bash
cd /var/www/moneyminder
git pull --ff-only
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
sudo systemctl reload php8.3-fpm nginx
```

## 6. Démarrage, surveillance et mises à jour automatiques

Le gardien systemd démarre les services au boot, vérifie `/up` toutes les cinq minutes et redémarre PHP-FPM/Nginx si nécessaire. Il consulte aussi `origin/main` et déploie uniquement les mises à jour en avance rapide lorsque le dépôt local est propre.

```bash
cd /var/www/moneyminder
sudo bash deploy/debian/install-guardian.sh over /var/www/moneyminder 8081
```

Vérification et journaux :

```bash
systemctl list-timers moneyminder-guardian.timer
sudo systemctl status moneyminder-guardian.timer --no-pager
sudo journalctl -u moneyminder-guardian.service -n 100 --no-pager
```

Le déploiement automatique exécute Composer, reconstruit les ressources avec Node/NVM, applique les migrations et rétablit toujours le mode accessible. Une modification locale non commitée ou une divergence Git bloque volontairement le pull automatique.
