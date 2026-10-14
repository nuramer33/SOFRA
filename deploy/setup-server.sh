#!/usr/bin/env bash
#
# Sofra: one-time server setup.
#
# For a fresh Ubuntu 22.04 or 24.04 server (x86_64 or ARM), for example an Oracle
# Cloud "Always Free" VM. Everything it installs is free software; it buys nothing
# and creates no cloud resources: it only configures THIS machine.
#
#   git clone https://github.com/nuramer33/SOFRA.git && cd SOFRA
#   sudo bash deploy/setup-server.sh your-name.duckdns.org
#
# Safe to run again: it reuses the database password and skips what already exists.
# After it finishes, run deploy/enable-https.sh to get the free HTTPS certificate.

set -Eeuo pipefail

DOMAIN="${1:?Usage: sudo bash deploy/setup-server.sh <domain> [repo-url]}"
REPO_URL="${2:-https://github.com/nuramer33/SOFRA.git}"
APP_DIR="/var/www/sofra"
APP_USER="${SUDO_USER:-ubuntu}"
CRED_FILE="/root/sofra-credentials.txt"
SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

export DEBIAN_FRONTEND=noninteractive

say() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
die() { printf '\n\033[1;31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }
as_app() { sudo -u "$APP_USER" -H env "PATH=$PATH" "$@"; }

[ "$(id -u)" -eq 0 ] || die "Run with sudo: sudo bash deploy/setup-server.sh $DOMAIN"
id "$APP_USER" >/dev/null 2>&1 || die "User '$APP_USER' does not exist. Run this with sudo from your normal login user."
[ -r /etc/os-release ] && . /etc/os-release
[ "${ID:-}" = "ubuntu" ] || die "This script is written for Ubuntu 22.04 / 24.04 (found: ${PRETTY_NAME:-unknown})."

# --- 1. Swap on small machines (building the assets needs memory) --------------
MEM_MB=$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)
if [ "$MEM_MB" -lt 2000 ] && ! swapon --show | grep -q .; then
    say "Only ${MEM_MB} MB of RAM: adding a 2 GB swap file"
    fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
    grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# --- 2. Packages ------------------------------------------------------------------
say "Installing system packages"
apt-get update -y
apt-get install -y ca-certificates curl git unzip acl cron software-properties-common \
    nginx mysql-server certbot python3-certbot-nginx unattended-upgrades

# --- 3. PHP 8.3 -------------------------------------------------------------------
if ! apt-cache policy php8.3-fpm | grep -q 'Candidate: [0-9]'; then
    say "Adding the PHP package source (Ubuntu ${VERSION_ID:-} does not ship PHP 8.3)"
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
fi
say "Installing PHP 8.3"
apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl
apt-get install -y php8.3-opcache || true   # bundled in some Ubuntu versions
install -m 644 "$SRC_DIR/php-sofra.ini" /etc/php/8.3/fpm/conf.d/99-sofra.ini
install -m 644 "$SRC_DIR/php-sofra.ini" /etc/php/8.3/cli/conf.d/99-sofra.ini
systemctl enable --now php8.3-fpm
systemctl restart php8.3-fpm

# --- 4. Composer (installer checked against the published signature) ---------------
if ! command -v composer >/dev/null 2>&1; then
    say "Installing Composer"
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [ "$EXPECTED" = "$ACTUAL" ] || { rm -f /tmp/composer-setup.php; die "Composer installer checksum mismatch"; }
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

# --- 5. Node.js 22 (only needed to build the CSS/JS) --------------------------------
if ! command -v node >/dev/null 2>&1 || [ "$(node -p 'process.versions.node.split(".")[0]')" -lt 20 ]; then
    say "Installing Node.js 22"
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
fi

# --- 6. Database ----------------------------------------------------------------------
say "Setting up MySQL"
systemctl enable --now mysql
if [ -f "$CRED_FILE" ] && grep -q '^DB_PASSWORD=' "$CRED_FILE"; then
    DB_PASSWORD="$(grep '^DB_PASSWORD=' "$CRED_FILE" | cut -d= -f2-)"
else
    DB_PASSWORD="$(openssl rand -hex 16)"
fi
mysql <<SQL
CREATE DATABASE IF NOT EXISTS sofra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'sofra'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER 'sofra'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON sofra.* TO 'sofra'@'localhost';
FLUSH PRIVILEGES;
SQL
umask 077
printf 'DOMAIN=%s\nDB_PASSWORD=%s\n' "$DOMAIN" "$DB_PASSWORD" > "$CRED_FILE"
umask 022

# --- 7. The application -------------------------------------------------------------------
say "Getting the code into $APP_DIR"
mkdir -p "$(dirname "$APP_DIR")"
if [ -d "$APP_DIR/.git" ]; then
    as_app git -C "$APP_DIR" pull --ff-only
else
    [ ! -e "$APP_DIR" ] || [ -z "$(ls -A "$APP_DIR")" ] || die "$APP_DIR exists and is not empty."
    mkdir -p "$APP_DIR" && chown "$APP_USER":www-data "$APP_DIR"
    as_app git clone "$REPO_URL" "$APP_DIR"
fi
chown -R "$APP_USER":www-data "$APP_DIR"

if [ ! -f "$APP_DIR/.env" ]; then
    say "Writing the production .env"
    sed -e "s|__DOMAIN__|$DOMAIN|g" -e "s|__DB_PASSWORD__|$DB_PASSWORD|g" \
        "$SRC_DIR/env.production.example" > "$APP_DIR/.env"
    chown "$APP_USER":www-data "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
fi

say "Installing dependencies and building (a few minutes)"
cd "$APP_DIR"
as_app composer install --no-dev --optimize-autoloader --no-interaction
grep -q '^APP_KEY=base64:' .env || as_app php artisan key:generate --force
as_app npm ci --no-audit --no-fund
as_app npm run build

say "Creating the database tables"
as_app php artisan migrate --force
as_app php artisan storage:link || true

# Folders PHP-FPM (www-data) must write to, also for files created later by the deploy user.
chgrp -R www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod g+s {} +
setfacl -R -m g:www-data:rwx storage bootstrap/cache
setfacl -R -d -m g:www-data:rwx storage bootstrap/cache

as_app php artisan config:cache
as_app php artisan route:cache
as_app php artisan view:cache

# --- 8. Web server ----------------------------------------------------------------------------
if [ -d "/etc/letsencrypt/live/$DOMAIN" ]; then
    say "A certificate already exists: keeping the Nginx file certbot edited"
else
    say "Configuring Nginx for $DOMAIN"
    sed -e "s|__DOMAIN__|$DOMAIN|g" -e "s|__APP_DIR__|$APP_DIR|g" \
        "$SRC_DIR/nginx-sofra.conf" > /etc/nginx/sites-available/sofra
fi
ln -sf /etc/nginx/sites-available/sofra /etc/nginx/sites-enabled/sofra
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now nginx
systemctl reload nginx

# --- 9. Firewall (Oracle's Ubuntu images block everything except SSH by default) -----------------
if command -v iptables >/dev/null 2>&1; then
    say "Opening ports 80 and 443 on this machine"
    for port in 80 443; do
        iptables -C INPUT -p tcp --dport "$port" -j ACCEPT 2>/dev/null \
            || iptables -I INPUT 1 -p tcp --dport "$port" -j ACCEPT
    done
    echo iptables-persistent iptables-persistent/autosave_v4 boolean true | debconf-set-selections
    echo iptables-persistent iptables-persistent/autosave_v6 boolean true | debconf-set-selections
    apt-get install -y iptables-persistent
    netfilter-persistent save
fi

# --- 10. Scheduler, updates ---------------------------------------------------------------------------
say "Installing the scheduler (backups, reminders, cleanup) and automatic security updates"
cat > /etc/cron.d/sofra <<CRON
# Laravel scheduler: runs sofra:backup, sofra:subscription-reminders, sofra:prune-analytics
* * * * * www-data cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1
CRON
chmod 644 /etc/cron.d/sofra
dpkg-reconfigure -f noninteractive unattended-upgrades || true

say "Done"
cat <<MSG

The site is installed at http://$DOMAIN (HTTPS comes next).

Database password: saved in $CRED_FILE (only root can read it).

Next:
  1. Create your administrator account:
       cd $APP_DIR && php artisan sofra:admin you@example.com
  2. Turn on free HTTPS (the domain must already point at this server):
       sudo bash $APP_DIR/deploy/enable-https.sh $DOMAIN you@example.com
  3. Check everything:
       bash $APP_DIR/deploy/smoke-test.sh https://$DOMAIN

MSG
