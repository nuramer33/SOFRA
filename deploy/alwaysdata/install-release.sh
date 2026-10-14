#!/usr/bin/env bash
#
# Sofra on alwaysdata: install or update from the release package.
#
# Run it over SSH on the alwaysdata server, after uploading sofra-release.tar.gz
# and this script to your home folder:
#
#   bash install-release.sh
#
# First run: creates .env and asks you to fill in the database values, then run it
# again. Later runs update the code and keep your .env, photos and logs.

set -Eeuo pipefail

APP_DIR="${APP_DIR:-$HOME/sofra}"
ARCHIVE="${1:-$HOME/sofra-release.tar.gz}"
ACCOUNT="$(id -un)"

say() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
die() { printf '\n\033[1;31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }

php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' \
    || die "PHP on the command line is $(php -r 'echo PHP_VERSION;'), Sofra needs 8.3 or newer. In the alwaysdata admin open Environment > PHP and choose 8.3 or newer."
[ -f "$ARCHIVE" ] || die "Package not found: $ARCHIVE (upload sofra-release.tar.gz to your home folder first)."

FIRST_INSTALL=0
[ -f "$APP_DIR/artisan" ] || FIRST_INSTALL=1

if [ "$FIRST_INSTALL" -eq 0 ]; then
    (cd "$APP_DIR" && php artisan down --retry=30) || true
fi

say "Unpacking the release into $APP_DIR"
mkdir -p "$APP_DIR"
tar -xzf "$ARCHIVE" -C "$APP_DIR" --exclude='.env' --exclude='storage/app/public' --exclude='storage/app/backups' --exclude='storage/logs'
cd "$APP_DIR"
mkdir -p storage/app/public storage/app/backups storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R u+rwX storage bootstrap/cache

if [ ! -f .env ]; then
    say "Creating .env"
    sed -e "s|__ACCOUNT__|$ACCOUNT|g" deploy/alwaysdata/env.alwaysdata.example > .env
    chmod 600 .env
    php artisan key:generate --force
    cat <<MSG

.env was created. Now put your database details in it (create the database first in
the alwaysdata admin: Databases > MySQL). Edit these four lines:

    nano $APP_DIR/.env       (DB_DATABASE, DB_USERNAME, DB_PASSWORD; check DB_HOST)

Then run this script again:   bash install-release.sh
MSG
    exit 0
fi

if grep -q '__DB_' .env; then
    die "The database values in $APP_DIR/.env are not filled in yet (they still contain __DB_...__)."
fi
grep -q '^APP_DEBUG=false' .env || die "APP_DEBUG must be false in .env for a public site."

say "Updating the database"
php artisan migrate --force

say "Linking public storage (for photos)"
php artisan storage:link 2>/dev/null || echo "(already linked)"

say "Warming caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up || true

say "Installed"
cat <<MSG

Next (only the first time):
  1. Site settings in the alwaysdata admin: Web > Sites: the root directory must be
       /sofra/public        and "Force HTTPS" should be on.
  2. Create your administrator:   cd $APP_DIR && php artisan sofra:admin you@example.com
  3. Scheduled task (Advanced > Scheduled tasks), every minute, command:
       php $APP_DIR/artisan schedule:run
  4. Check it:   bash $APP_DIR/deploy/smoke-test.sh https://$ACCOUNT.alwaysdata.net

MSG
