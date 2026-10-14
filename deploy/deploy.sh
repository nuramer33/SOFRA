#!/usr/bin/env bash
#
# Sofra: update the running site to the latest code on GitHub.
#
#   cd /var/www/sofra && bash deploy/deploy.sh
#
# Run as your normal login user (not root). The site shows a short "back soon"
# page while the database is migrated.

set -Eeuo pipefail
umask 0002   # files stay writable by the web server's group

cd "$(dirname "${BASH_SOURCE[0]}")/.."

say() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }

[ -f .env ] || { echo "No .env here: run deploy/setup-server.sh first." >&2; exit 1; }

say "Getting the latest code"
git pull --ff-only

say "Installing dependencies"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund

php artisan down --retry=30 || true
trap 'php artisan up' EXIT

say "Building assets and updating the database"
npm run build
php artisan migrate --force
php artisan storage:link 2>/dev/null || true

say "Refreshing caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

say "Reloading PHP"
sudo -n systemctl reload php8.3-fpm 2>/dev/null || echo "(could not reload PHP-FPM automatically: run: sudo systemctl reload php8.3-fpm)"

say "Updated"
