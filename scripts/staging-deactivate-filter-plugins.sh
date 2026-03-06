#!/usr/bin/env bash
# FB-04: dezaktywacja pluginów filtrów dublujących blok „Filtruj” na stagingu (zostawiamy chipsy w temacie).
# Użycie: ./scripts/staging-deactivate-filter-plugins.sh
# Wymaga: .env (cyberfolks_ssh_*), na serwerze WP-CLI w PATH i katalogu staging.

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"

if [[ ! -f .env ]]; then
  echo "Brak .env"
  exit 1
fi

SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)
WP_PATH="domains/mnsk7-tools.pl/public_html/staging"

# Slugi pluginów, które mogą dublować blok „Filtruj” (średnica, trzpień itd.)
FILTER_PLUGIN_SLUGS=(
  "filter-everything"
  "woo-product-filter"
  "wbw-product-filter"
  "woocommerce-products-filter"
  "woof"
  "woof-by-category"
)

echo ">>> Lista aktywnych pluginów na stagingu (wp plugin list --path=~/$WP_PATH) <<<"
ssh -p "${SSH_PORT}" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "cd $WP_PATH && wp plugin list --status=active --format=table" 2>/dev/null || true

echo ""
echo ">>> Dezaktywacja pluginów filtrów (jeśli są aktywne) <<<"
TO_DEACTIVATE=()
for slug in "${FILTER_PLUGIN_SLUGS[@]}"; do
  if ssh -p "${SSH_PORT}" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "cd $WP_PATH && wp plugin is-active $slug" 2>/dev/null; then
    TO_DEACTIVATE+=("$slug")
  fi
done

if [[ ${#TO_DEACTIVATE[@]} -eq 0 ]]; then
  echo "Żaden z znanych pluginów filtrów nie jest aktywny. Gotowe."
  exit 0
fi

echo "Wyłączam: ${TO_DEACTIVATE[*]}"
ssh -p "${SSH_PORT}" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "cd $WP_PATH && wp plugin deactivate ${TO_DEACTIVATE[*]} && wp cache flush 2>/dev/null || true"
echo "Gotowe. Odśwież stronę kategorii — blok „Filtruj” powinien być tylko jeden (chipsy z motywu)."
