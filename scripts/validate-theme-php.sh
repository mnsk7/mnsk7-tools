#!/usr/bin/env bash
# Walczy wszystkie .php w motywie mnsk7-storefront przez php -l.
# Uruchom z katalogu głównego: bash scripts/validate-theme-php.sh

set -e
ROOT="${1:-.}"
THEME="${ROOT}/wp-content/themes/mnsk7-storefront"
FAIL=0

if ! command -v php >/dev/null 2>&1; then
  echo "Brak php w PATH — pomijam walidację."
  exit 0
fi

if [[ ! -d "$THEME" ]]; then
  echo "Katalog motywu nie istnieje: $THEME"
  exit 1
fi

echo "Walidacja PHP: $THEME"
while IFS= read -r -d '' f; do
  if ! php -l "$f" >/dev/null 2>&1; then
    echo "FAIL: $f"
    php -l "$f" 2>&1 || true
    FAIL=1
  fi
done < <(find "$THEME" -name "*.php" -print0)

if [[ $FAIL -eq 1 ]]; then
  echo "Niektóre pliki mają błędy składni."
  exit 1
fi
echo "OK — wszystkie pliki przechodzą php -l."
