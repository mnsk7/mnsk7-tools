#!/usr/bin/env bash
# Аудит плагинов: деактивация лишних/замедляющих (см. docs/PLUGINS_AUDIT.md).
# Требует: .env (cyberfolks_ssh_*), на сервере WP-CLI и php с memory_limit (скрипт поднимает до 512M).
# Использование: ./scripts/staging-deactivate-plugins-audit.sh [--dry-run]
#            или: ./scripts/staging-deactivate-plugins-audit.sh --optional [--dry-run]

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
SSH_PASS=$(grep '^cyberfolks_ssh_password=' .env | cut -d= -f2-)
WP_PATH="domains/mnsk7-tools.pl/public_html/staging"
WP_CMD="cd $WP_PATH && /opt/alt/php82/usr/bin/php -d memory_limit=512M /usr/local/bin/wp"

DRY_RUN=""
OPTIONAL=""
for arg in "$@"; do
  [[ "$arg" == "--dry-run" ]] && DRY_RUN="1"
  [[ "$arg" == "--optional" ]] && OPTIONAL="1"
done

# Основной набор: дубли темы, лишняя нагрузка (см. PLUGINS_AUDIT.md)
TO_DEACTIVATE=(
  woo-product-filter
  wc-product-table-lite
  unlimited-elements-for-elementor
  optimization-detective
  sticky-menu-or-anything-on-scroll
  embed-optimizer
  load-more-products-for-woocommerce
)

# Опциональный набор: «не знаю зачем» — PWA, конструктор, слайдер, фиды, скидки по правилам и т.д.
OPTIONAL_DEACTIVATE=(
  pwa-for-wp
  shopengine
  performance-lab
  speculation-rules
  yith-woocommerce-product-slider-carousel
  ultimate-member
  customize-my-account-for-woocommerce
  google-listings-and-ads
  webtoffee-product-feed
  woo-discount-rules
)

[[ -n "$OPTIONAL" ]] && TO_DEACTIVATE=("${OPTIONAL_DEACTIVATE[@]}")

run_ssh() {
  sshpass -p "$SSH_PASS" ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "$1"
}

echo ">>> Aktywne pluginy (przed) <<<"
run_ssh "$WP_CMD plugin list --status=active --format=table" 2>/dev/null || true

echo ""
[[ -n "$OPTIONAL" ]] && echo ">>> Tryb: opcjonalne pluginy (--optional) <<<"
echo ">>> Kandidaci do dezaktywacji: ${TO_DEACTIVATE[*]} <<<"
ACTIVE=()
for slug in "${TO_DEACTIVATE[@]}"; do
  if run_ssh "$WP_CMD plugin is-active $slug" 2>/dev/null; then
    ACTIVE+=("$slug")
  fi
done

if [[ ${#ACTIVE[@]} -eq 0 ]]; then
  echo "Żaden z tych pluginów nie jest aktywny. Koniec."
  exit 0
fi

echo "Wyłączam: ${ACTIVE[*]}"
if [[ -n "$DRY_RUN" ]]; then
  echo "[DRY-RUN] Odpal bez --dry-run, żeby dezaktywować."
  exit 0
fi

run_ssh "cd $WP_PATH && /opt/alt/php82/usr/bin/php -d memory_limit=512M /usr/local/bin/wp plugin deactivate ${ACTIVE[*]} --quiet && /opt/alt/php82/usr/bin/php -d memory_limit=512M /usr/local/bin/wp cache flush 2>/dev/null || true"
echo "Gotowe. Sprawdź stronę kategorii i front."
echo ""
echo ">>> Aktywne pluginy (po) <<<"
run_ssh "$WP_CMD plugin list --status=active --format=table" 2>/dev/null || true
