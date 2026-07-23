#!/usr/bin/env bash
set -Eeuo pipefail

[[ "${EUID}" -eq 0 ]] || { echo 'Lance ce script avec sudo.' >&2; exit 1; }
DEPLOY_USER="${1:-over}"
APP_DIR="${2:-/var/www/moneyminder}"
LOCAL_PORT="${3:-8081}"
[[ -d "$APP_DIR/deploy/debian" ]] || { echo "Dossier MoneyMinder introuvable : $APP_DIR" >&2; exit 1; }
id "$DEPLOY_USER" >/dev/null 2>&1 || { echo "Utilisateur inconnu : $DEPLOY_USER" >&2; exit 1; }

install -m 0750 "$APP_DIR/deploy/debian/moneyminder-guardian.sh" /usr/local/sbin/moneyminder-guardian
install -m 0644 "$APP_DIR/deploy/debian/moneyminder-guardian.service" /etc/systemd/system/moneyminder-guardian.service
install -m 0644 "$APP_DIR/deploy/debian/moneyminder-guardian.timer" /etc/systemd/system/moneyminder-guardian.timer
cat > /etc/default/moneyminder-guardian <<EOF
APP_DIR=$APP_DIR
DEPLOY_USER=$DEPLOY_USER
BRANCH=main
LOCAL_PORT=$LOCAL_PORT
EOF

systemctl daemon-reload
systemctl enable --now postgresql nginx tailscaled
PHP_SERVICE="$(systemctl list-unit-files 'php*-fpm.service' --no-legend | awk '{print $1}' | sort -V | tail -n 1)"
[[ -n "$PHP_SERVICE" ]] && systemctl enable --now "$PHP_SERVICE"
systemctl enable --now moneyminder-guardian.timer
systemctl start moneyminder-guardian.service
echo 'Gardien MoneyMinder installé.'
systemctl --no-pager status moneyminder-guardian.timer
