#!/usr/bin/env bash
# Usuwa z sidebar-1 (Panel boczny) widżet filtrów produktów (wpfwoofilterswidget / WOOF / WBW).
# Po usunięciu blok „Filtruj: Średnica: …” znika ze stron kategorii. Użycie: ./scripts/staging-remove-filter-widget.sh [--dry-run]

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"
[[ -f .env ]] || { echo "Brak .env"; exit 1; }

SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)
SSH_PASS=$(grep '^cyberfolks_ssh_password=' .env | cut -d= -f2-)
WP_PATH="domains/mnsk7-tools.pl/public_html/staging"
WP="cd $WP_PATH && /opt/alt/php82/usr/bin/php -d memory_limit=512M /usr/local/bin/wp"

DRY_RUN=""
[[ "${1:-}" == "--dry-run" ]] && DRY_RUN="1"

run_ssh() { sshpass -p "$SSH_PASS" ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "$1"; }

# Identyfikatory widżetów filtrów (nazwa lub id zawiera)
FILTER_PATTERNS="wpfwoofilterswidget|woof|wpf.*filter|product.*filter"

echo ">>> Widżety w sidebar-1 <<<"
run_ssh "$WP widget list sidebar-1 --format=csv" 2>/dev/null | head -20

IDS=$(run_ssh "$WP widget list sidebar-1 --format=csv --fields=id,name" 2>/dev/null | tail -n +2 | while IFS=, read -r id name; do
  if echo "$id $name" | grep -qiE "$FILTER_PATTERNS"; then
    echo "$id"
  fi
done)

if [[ -z "$IDS" ]]; then
  echo "Brak widżetów filtrów w sidebar-1. Gotowe."
  exit 0
fi

echo ""
echo ">>> Usunę widżet(y): $IDS <<<"
if [[ -n "$DRY_RUN" ]]; then
  echo "[DRY-RUN] Uruchom bez --dry-run, aby usunąć."
  exit 0
fi

for id in $IDS; do
  run_ssh "$WP widget delete $id" 2>/dev/null && echo "Usunięto: $id" || true
done
echo "Gotowe. Odśwież stronę kategorii."