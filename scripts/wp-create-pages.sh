#!/usr/bin/env bash
set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"
[[ -f .env ]] && set -a && source .env && set +a

WP_BASE="${WP_BASE_URL%/}"
AUTH=$(echo -n "$WP_USER:$WP_APP_PASSWORD" | base64)

get_page_id() {
  curl -s -H "Authorization: Basic $AUTH" -H "Accept: application/json" \
    "$WP_BASE/wp-json/wp/v2/pages?slug=$1&_fields=id" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d[0]['id'] if d else '')" 2>/dev/null || echo ""
}

create_page() {
  local slug="$1" title="$2" template="$3"
  local id=$(get_page_id "$slug")
  if [[ -n "$id" ]]; then
    echo "Page exists: $title (id=$id), setting template."
    curl -s -X POST -H "Authorization: Basic $AUTH" -H "Accept: application/json" -H "Content-Type: application/json" \
      -d "{\"template\": \"$template\"}" "$WP_BASE/wp-json/wp/v2/pages/$id" > /dev/null
    echo "  OK"
    return
  fi
  echo "Creating: $title"
  local res=$(curl -s -X POST -H "Authorization: Basic $AUTH" -H "Accept: application/json" -H "Content-Type: application/json" \
    -d "{\"title\": \"$title\", \"slug\": \"$slug\", \"status\": \"publish\", \"template\": \"$template\", \"content\": \"\"}" "$WP_BASE/wp-json/wp/v2/pages")
  local new_id=$(echo "$res" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('id',''))" 2>/dev/null)
  [[ -n "$new_id" ]] && echo "  Created id=$new_id" || echo "  Error: $res"
}

create_page "dostawa-i-platnosci" "Dostawa i platnosci" "page-dostawa.php"
create_page "kontakt" "Kontakt" "page-kontakt.php"
create_page "frezy-do-aluminium" "Frezy do aluminium CNC" "page-frezy-aluminium.php"
create_page "frezy-mdf" "Frezy do drewna i MDF" "page-frezy-mdf.php"
create_page "frezy-do-stali" "Frezy do stali i metalu" "page-frezy-stali.php"
create_page "frezy-cnc" "Frezy CNC" "page-cnc-frezy.php"
echo "Done."
