#!/usr/bin/env bash
#
# Sofra: keep a free DuckDNS name pointing at this server.
#
# DuckDNS (duckdns.org) gives free names like your-name.duckdns.org. You create
# the name on their website (sign in with a GitHub or Google account); this script
# only keeps its IP address up to date.
#
#   sudo bash deploy/duckdns.sh install your-name        (asks for your DuckDNS token)
#   sudo bash deploy/duckdns.sh update                   (runs by itself every 5 minutes)
#
# The token is stored in /etc/sofra/duckdns.env, readable by root only.

set -Eeuo pipefail

CONF="/etc/sofra/duckdns.env"
MODE="${1:-}"

case "$MODE" in
    install)
        [ "$(id -u)" -eq 0 ] || { echo "Run with sudo." >&2; exit 1; }
        SUBDOMAIN="${2:?Usage: sudo bash deploy/duckdns.sh install <subdomain-without-.duckdns.org>}"
        SUBDOMAIN="${SUBDOMAIN%.duckdns.org}"
        [[ "$SUBDOMAIN" =~ ^[a-z0-9-]+$ ]] || { echo "Use only lowercase letters, digits and hyphens." >&2; exit 1; }
        read -rsp "DuckDNS token (hidden while you type): " TOKEN
        echo
        [ -n "$TOKEN" ] || { echo "No token given." >&2; exit 1; }
        install -d -m 700 /etc/sofra
        umask 077
        printf 'DUCKDNS_SUBDOMAIN=%s\nDUCKDNS_TOKEN=%s\n' "$SUBDOMAIN" "$TOKEN" > "$CONF"
        SCRIPT="$(readlink -f "${BASH_SOURCE[0]}")"
        cat > /etc/cron.d/sofra-duckdns <<CRON
*/5 * * * * root bash $SCRIPT update >> /var/log/sofra-duckdns.log 2>&1
CRON
        chmod 644 /etc/cron.d/sofra-duckdns
        bash "$SCRIPT" update
        ;;
    update)
        # shellcheck disable=SC1090
        . "$CONF"
        RESULT="$(curl -fsS --max-time 20 "https://www.duckdns.org/update?domains=${DUCKDNS_SUBDOMAIN}&token=${DUCKDNS_TOKEN}&ip=")"
        echo "$(date -Is) ${DUCKDNS_SUBDOMAIN}.duckdns.org -> ${RESULT}"
        [ "$RESULT" = "OK" ] || exit 1
        ;;
    *)
        echo "Usage: sudo bash deploy/duckdns.sh install <subdomain>   |   sudo bash deploy/duckdns.sh update" >&2
        exit 1
        ;;
esac
