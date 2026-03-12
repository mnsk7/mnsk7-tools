#!/usr/bin/env bash
# Task 4 regression: sprawdza, że na kluczowych stronach jest dokładnie jeden #content.
# Uruchomić po deployu na staging. Visual/layout — osobno, ręcznie (docs/TASK4-DOM-AND-DUAL-SUPPORT.md).
#
# Usage:
#   BASE_URL=https://staging.mnsk7-tools.pl ./scripts/task4-regression-check.sh
#   PDP_URL=https://staging.mnsk7-tools.pl/produkt/frez-prosty-4p ./scripts/task4-regression-check.sh

set -e
BASE="${BASE_URL:-https://staging.mnsk7-tools.pl}"
# Jeden stały PDP URL: domyślnie poniższy path (zmień na istniejący produkt lub ustaw PDP_URL).
FIXED_PDP_PATH="/produkt/frez-prosty-do-drewna-4p-trzpien-8-mm-fi-16-x-50-vhm-z-lozyskiem"
PDP_URL_FIXED="${PDP_URL:-$BASE$FIXED_PDP_PATH}"

FAIL=0

check_content_count() {
  local url="$1"
  local name="$2"
  local n
  n=$(curl -sL "$url" | grep -c 'id="content"' || true)
  if [ "$n" -eq 1 ]; then
    echo "OK $name: id=\"content\" count = 1"
  else
    echo "FAIL $name: id=\"content\" count = $n (expected 1)"
    FAIL=1
  fi
}

echo "Task 4 regression check — BASE=$BASE"
echo "---"

check_content_count "$BASE/" "Home"
check_content_count "$BASE/koszyk/" "Cart"
check_content_count "$BASE/zamowienie/" "Checkout"
check_content_count "$BASE/moje-konto/" "Account"
check_content_count "$BASE/sklep/" "PLP (sklep)"
check_content_count "$BASE/?s=frezy&post_type=product" "Product search"
check_content_count "$BASE/sklep/?filter_typ-freza=frezy-do-aluminium" "PLP + filter_*"

# PDP: stały URL (FIXED_PDP_PATH w skrypcie lub zmienna PDP_URL)
check_content_count "$PDP_URL_FIXED" "PDP (single product)"

echo "---"
if [ $FAIL -eq 0 ]; then
  echo "All checks passed."
  exit 0
else
  echo "Some checks failed."
  exit 1
fi
