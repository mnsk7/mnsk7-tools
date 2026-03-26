#!/usr/bin/env bash
set -euo pipefail

# L0 smoke Lighthouse run for key URLs.
#
# Usage:
#   BASE_URL=https://staging.mnsk7-tools.pl bash scripts/verify/lighthouse-smoke.sh
#
# Output:
#   artifacts/lighthouse/<slug>.json

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

BASE_URL="${BASE_URL:-https://staging.mnsk7-tools.pl}"
OUT_DIR="${ROOT}/artifacts/lighthouse"

mkdir -p "$OUT_DIR"

get_playwright_chrome () {
  node -e "try { const pw = require('@playwright/test'); process.stdout.write(pw.chromium.executablePath()); } catch (e) { process.exit(1); }" 2>/dev/null || true
}

CHROME_PATH="${CHROME_PATH:-}"
if [[ -z "${CHROME_PATH}" ]]; then
  CHROME_PATH="$(get_playwright_chrome)"
fi

run_one () {
  local name="$1"
  local url="$2"
  local out="${OUT_DIR}/${name}.json"
  echo "Lighthouse: ${url}"
  local chrome_args=()
  if [[ -n "${CHROME_PATH}" && -x "${CHROME_PATH}" ]]; then
    chrome_args+=( --chrome-path="${CHROME_PATH}" )
  fi
  local flags="${LIGHTHOUSE_CHROME_FLAGS:---headless --disable-gpu --no-sandbox --disable-dev-shm-usage}"
  npx lighthouse "$url" \
    --output=json --output-path="$out" \
    --chrome-flags="$flags" \
    "${chrome_args[@]}" \
    --quiet
  echo "  -> ${out}"
}

run_one "home" "${BASE_URL}/"
run_one "shop" "${BASE_URL}/sklep/"
run_one "cart" "${BASE_URL}/koszyk/"
run_one "checkout" "${BASE_URL}/zamowienie/"

echo "Done."

