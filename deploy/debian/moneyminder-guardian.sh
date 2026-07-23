#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/moneyminder}"
DEPLOY_USER="${DEPLOY_USER:-over}"
BRANCH="${BRANCH:-main}"
LOCAL_PORT="${LOCAL_PORT:-8081}"
LOCK_FILE="/run/lock/moneyminder-guardian.lock"

exec 9>"$LOCK_FILE"
flock -n 9 || exit 0

log() { logger -t moneyminder-guardian -- "$*"; printf '[MoneyMinder] %s\n' "$*"; }
as_deployer() { runuser -u "$DEPLOY_USER" -- "$@"; }
artisan() { as_deployer /usr/bin/php "$APP_DIR/artisan" "$@"; }

PHP_SERVICE="$(systemctl list-unit-files 'php*-fpm.service' --no-legend 2>/dev/null | awk '{print $1}' | sort -V | tail -n 1)"
[[ -n "$PHP_SERVICE" ]] || { log 'Aucun service PHP-FPM détecté.'; exit 1; }

ensure_service() {
    local service="$1"
    if ! systemctl is-active --quiet "$service"; then
        log "Redémarrage de $service"
        systemctl restart "$service"
    fi
}

healthcheck() { curl -fsS --max-time 12 "http://127.0.0.1:${LOCAL_PORT}/up" >/dev/null; }

ensure_running() {
    ensure_service postgresql.service
    ensure_service "$PHP_SERVICE"
    ensure_service nginx.service
    ensure_service tailscaled.service

    if ! healthcheck; then
        log 'Application indisponible, redémarrage de PHP-FPM et Nginx.'
        systemctl restart "$PHP_SERVICE" nginx.service
        sleep 3
        healthcheck || { log 'ÉCHEC : MoneyMinder reste indisponible après redémarrage.'; return 1; }
    fi
    log 'Application opérationnelle.'
}

update_application() {
    [[ -d "$APP_DIR/.git" ]] || { log "Dépôt Git absent de $APP_DIR"; return 1; }
    if [[ -n "$(as_deployer git -C "$APP_DIR" status --porcelain)" ]]; then
        log 'Mise à jour ignorée : le dépôt contient des modifications locales.'
        return 0
    fi

    as_deployer git -C "$APP_DIR" fetch --quiet origin "$BRANCH"
    local current remote
    current="$(as_deployer git -C "$APP_DIR" rev-parse HEAD)"
    remote="$(as_deployer git -C "$APP_DIR" rev-parse "origin/$BRANCH")"
    [[ "$current" != "$remote" ]] || { log 'Aucune mise à jour GitHub.'; return 0; }
    as_deployer git -C "$APP_DIR" merge-base --is-ancestor "$current" "$remote" || {
        log 'Mise à jour ignorée : la branche locale a divergé de GitHub.'
        return 0
    }

    log "Nouvelle version détectée : ${current:0:7} -> ${remote:0:7}"
    artisan down --retry=60 || true
    local update_ok=0
    trap 'artisan up >/dev/null 2>&1 || true' EXIT

    if as_deployer git -C "$APP_DIR" pull --ff-only origin "$BRANCH" \
        && as_deployer /usr/bin/composer --working-dir="$APP_DIR" install --no-dev --no-interaction --optimize-autoloader \
        && runuser -u "$DEPLOY_USER" -- env HOME="$(getent passwd "$DEPLOY_USER" | cut -d: -f6)" APP_DIR="$APP_DIR" bash -c '[[ ! -s "$HOME/.nvm/nvm.sh" ]] || source "$HOME/.nvm/nvm.sh"; cd "$APP_DIR"; npm ci; npm run build' \
        && artisan migrate --force \
        && artisan optimize:clear \
        && artisan optimize; then
        update_ok=1
    fi

    artisan up || true
    trap - EXIT
    if [[ "$update_ok" -ne 1 ]]; then
        log 'ÉCHEC de la mise à jour. Consulte journalctl -u moneyminder-guardian.service.'
        return 1
    fi

    chown -R "$DEPLOY_USER":www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 2775 {} +
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} +
    systemctl restart "$PHP_SERVICE" nginx.service
    systemctl try-restart moneyminder-scheduler.service || true
    log "Mise à jour ${remote:0:7} déployée avec succès."
}

ensure_running
update_application
ensure_running
