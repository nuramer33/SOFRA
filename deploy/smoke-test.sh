#!/usr/bin/env bash
#
# Sofra: quick automatic check of a running site (public pages and security basics).
# It only reads pages; it changes nothing.
#
#   bash deploy/smoke-test.sh https://your-name.duckdns.org
#   bash deploy/smoke-test.sh http://localhost:8000
#
# Prints PASS/FAIL per check and exits with an error if anything failed.

set -uo pipefail

BASE="${1:?Usage: bash deploy/smoke-test.sh <base-url>}"
BASE="${BASE%/}"
FAILED=0

status() { curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$@"; }
pass() { printf '  \033[32mPASS\033[0m  %s\n' "$1"; }
fail() { printf '  \033[31mFAIL\033[0m  %s\n' "$1"; FAILED=$((FAILED + 1)); }

expect() { # expect <description> <expected-code> <path>
    local got
    got="$(status "$BASE$3")"
    if [ "$got" = "$2" ]; then pass "$1 ($3 -> $got)"; else fail "$1 ($3 -> $got, expected $2)"; fi
}

echo "Checking $BASE"
echo
echo "Public pages"
expect "Home page" 200 /
expect "Login" 200 /login
expect "Register" 200 /register
expect "Privacy" 200 /privacy
expect "Terms" 200 /terms
expect "Sitemap" 200 /sitemap.xml
expect "Robots" 200 /robots.txt
expect "Health check" 200 /up
expect "Unknown page is a 404" 404 /this-page-does-not-exist

echo
echo "Private areas must ask for a login"
for path in /dashboard /dashboard/today /dashboard/billing /admin; do
    got="$(status "$BASE$path")"
    if [ "$got" = "302" ] || [ "$got" = "301" ]; then pass "$path redirects ($got)"; else fail "$path returned $got (expected a redirect to login)"; fi
done

echo
echo "Secrets must not be reachable"
for path in /.env /.git/config /composer.json /storage/logs/laravel.log /artisan; do
    got="$(status "$BASE$path")"
    case "$got" in
        403|404) pass "$path is blocked ($got)" ;;
        *) fail "$path returned $got (must be 403 or 404)" ;;
    esac
done

echo
echo "Errors must not leak details"
body="$(curl -s --max-time 20 "$BASE/this-page-does-not-exist")"
if echo "$body" | grep -qiE 'whoops|stack trace|vendor/laravel|APP_KEY'; then fail "Error page shows debug details (is APP_DEBUG still true?)"; else pass "Error page shows no debug details"; fi

echo
echo "Security headers"
headers="$(curl -s -D - -o /dev/null --max-time 20 "$BASE/")"
for header in x-content-type-options x-frame-options referrer-policy; do
    if echo "$headers" | grep -qi "^$header:"; then pass "$header is set"; else fail "$header is missing"; fi
done
if echo "$headers" | grep -qi '^x-powered-by:'; then fail "X-Powered-By reveals the PHP version"; else pass "X-Powered-By is hidden"; fi

if [[ "$BASE" == https://* ]]; then
    echo
    echo "HTTPS"
    http_url="http://${BASE#https://}"
    got="$(status "$http_url/")"
    if [ "$got" = "301" ] || [ "$got" = "308" ]; then pass "http redirects to https ($got)"; else fail "http returned $got (expected a redirect to https)"; fi
    if curl -sS -o /dev/null --max-time 20 "$BASE/" 2>/dev/null; then pass "Certificate is valid"; else fail "Certificate problem"; fi
fi

echo
if [ "$FAILED" -eq 0 ]; then
    printf '\033[1;32mAll checks passed.\033[0m\n'
else
    printf '\033[1;31m%s check(s) failed.\033[0m\n' "$FAILED"
    exit 1
fi
