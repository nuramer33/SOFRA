#!/usr/bin/env bash
#
# Sofra: free HTTPS with Let's Encrypt.
#
#   sudo bash deploy/enable-https.sh your-name.duckdns.org you@example.com
#
# The domain must already point at THIS server's public IP, and ports 80 and 443
# must be open (in the cloud provider's firewall too). Certificates renew by
# themselves (certbot installs a timer); nothing here costs money.

set -Eeuo pipefail

DOMAIN="${1:?Usage: sudo bash deploy/enable-https.sh <domain> <email>}"
EMAIL="${2:?Usage: sudo bash deploy/enable-https.sh <domain> <email>}"
APP_DIR="/var/www/sofra"
APP_USER="${SUDO_USER:-ubuntu}"

say() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
die() { printf '\n\033[1;31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "Run with sudo."
[ -f "$APP_DIR/.env" ] || die "Run deploy/setup-server.sh first."

say "Checking that $DOMAIN points at this server"
PUBLIC_IP="$(curl -4 -fsS --max-time 10 https://api.ipify.org || true)"
DNS_IP="$(getent ahostsv4 "$DOMAIN" | awk '{print $1; exit}' || true)"
echo "This server : ${PUBLIC_IP:-unknown}"
echo "$DOMAIN : ${DNS_IP:-not found}"
if [ -z "$DNS_IP" ] || [ -z "$PUBLIC_IP" ] || [ "$DNS_IP" != "$PUBLIC_IP" ]; then
    die "The domain does not point at this server yet. Fix the DNS record, wait a minute and run this again."
fi

say "Asking Let's Encrypt for a certificate"
certbot --nginx -d "$DOMAIN" -m "$EMAIL" --agree-tos --no-eff-email --non-interactive --redirect

say "Switching the app to https"
sed -i "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" "$APP_DIR/.env"
grep -q '^SESSION_SECURE_COOKIE=' "$APP_DIR/.env" \
    || printf '\nSESSION_SECURE_COOKIE=true\n' >> "$APP_DIR/.env"
cd "$APP_DIR"
sudo -u "$APP_USER" -H php artisan config:cache
nginx -t && systemctl reload nginx

say "Checking automatic renewal"
certbot renew --dry-run

say "Done: https://$DOMAIN"
