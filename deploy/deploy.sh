#!/usr/bin/env bash
# Mise à jour de la boutique en production : ./deploy/deploy.sh
# Met le site en maintenance quelques secondes, récupère le code, met à jour
# les dépendances et la base, reconstruit les caches puis remet le site en ligne.
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/boutique/current}"
BRANCH="${BRANCH:-main}"

cd "$APP_DIR"

echo "→ Mode maintenance"
php artisan down --retry=30 || true
trap 'php artisan up' EXIT

echo "→ Récupération du code ($BRANCH)"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "→ Dépendances PHP"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "→ Compilation CSS/JS"
npm ci --no-audit --no-fund
npm run build

echo "→ Base de données"
php artisan migrate --force

echo "→ Caches de production"
php artisan optimize

echo "→ Redémarrage des workers"
php artisan queue:restart

echo "✓ Déploiement terminé"
