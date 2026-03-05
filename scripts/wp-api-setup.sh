#!/usr/bin/env bash
# Sprawdzenie dostępu do WP REST API i WooCommerce API (staging).
# Używa .env: WP_BASE_URL, WP_USER, WP_APP_PASSWORD, Woo_Klucz_konsumenta, Woo_Tajny_konsumenta
# Uruchom z katalogu głównego: ./scripts/wp-api-setup.sh

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"

if [[ -f .env ]]; then
  set -a
  source .env
  set +a
fi

WP_BASE="${WP_BASE_URL:-}"
WP_USER="${WP_USER:-}"
WP_PASS="${WP_APP_PASSWORD:-}"
WOO_KEY="${Woo_Klucz_konsumenta:-}"
WOO_SECRET="${Woo_Tajny_konsumenta:-}"

if [[ -z "$WP_BASE" ]] || [[ -z "$WP_USER" ]] || [[ -z "$WP_PASS" ]]; then
  echo "Brak w .env: WP_BASE_URL, WP_USER, WP_APP_PASSWORD"
  exit 1
fi

WP_BASE="${WP_BASE%/}"
AUTH="$(echo -n "$WP_USER:$WP_PASS" | base64)"
WP_AUTH_HEADER="Authorization: Basic $AUTH"

echo "=== Test WP REST API ($WP_BASE) ==="
HTTP=$(curl -s -o /dev/null -w "%{http_code}" -H "$WP_AUTH_HEADER" -H "Accept: application/json" "$WP_BASE/wp-json/wp/v2/users/me" 2>/dev/null || echo "000")
if [[ "$HTTP" != "200" ]]; then
  echo "Błąd auth WP API: HTTP $HTTP"
  exit 1
fi
echo "WP API OK (HTTP 200)"

if [[ -n "$WOO_KEY" ]] && [[ -n "$WOO_SECRET" ]]; then
  echo "=== Test WooCommerce API ==="
  WOO_AUTH="$(echo -n "$WOO_KEY:$WOO_SECRET" | base64)"
  HTTP=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Basic $WOO_AUTH" -H "Accept: application/json" "$WP_BASE/wp-json/wc/v3/system_status" 2>/dev/null || echo "000")
  if [[ "$HTTP" != "200" ]]; then
    echo "WooCommerce API: HTTP $HTTP (możliwy błąd klucza)"
  else
    echo "WooCommerce API OK"
  fi
fi

echo ""
echo "Dostep OK. Mozna uzywac API do tworzenia stron / ustawien."
