#!/usr/bin/env bash
set -euo pipefail

BACKUP_DIR=/var/backups/moneyminder
STAMP=$(date +%Y-%m-%d_%H-%M-%S)
export PGPASSWORD="${DB_PASSWORD:-}"
umask 077
install -d -m 0700 "$BACKUP_DIR"
pg_dump --host="${DB_HOST:-127.0.0.1}" --port="${DB_PORT:-5432}" --username="${DB_USERNAME}" --format=custom --file="$BACKUP_DIR/database-$STAMP.dump" "${DB_DATABASE}"
find "$BACKUP_DIR" -type f -name 'database-*.dump' -mtime +14 -delete
