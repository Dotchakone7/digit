#!/usr/bin/env bash
# Sauvegarde quotidienne : base PostgreSQL + images uploadées. Conserve 14 jours.
# Les identifiants sont lus dans le .env de l'application (rien en dur ici).
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/boutique/current}"
BACKUP_DIR="${BACKUP_DIR:-/var/www/boutique/backups}"
KEEP_DAYS="${KEEP_DAYS:-14}"
STAMP="$(date +%Y-%m-%d_%H%M)"

read_env() { grep -E "^$1=" "$APP_DIR/.env" | tail -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }

mkdir -p "$BACKUP_DIR"

PGPASSWORD="$(read_env DB_PASSWORD)" pg_dump \
    -h "$(read_env DB_HOST)" -p "$(read_env DB_PORT)" -U "$(read_env DB_USERNAME)" \
    -Fc "$(read_env DB_DATABASE)" > "$BACKUP_DIR/db_$STAMP.dump"

tar -czf "$BACKUP_DIR/media_$STAMP.tar.gz" -C "$APP_DIR/storage/app" public

find "$BACKUP_DIR" -name 'db_*.dump' -mtime +"$KEEP_DAYS" -delete
find "$BACKUP_DIR" -name 'media_*.tar.gz' -mtime +"$KEEP_DAYS" -delete

echo "$(date '+%F %T') sauvegarde OK : db_$STAMP.dump, media_$STAMP.tar.gz"
