#!/usr/bin/env bash
set -euo pipefail

# L0 link check for key URLs.
#
# Usage:
#   BASE_URL=https://staging.mnsk7-tools.pl bash scripts/verify/link-check.sh
#
# Output:
#   artifacts/linkcheck/linkinator.json

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

BASE_URL="${BASE_URL:-https://staging.mnsk7-tools.pl}"
OUT_DIR="${ROOT}/artifacts/linkcheck"
mkdir -p "$OUT_DIR"

echo "Linkinator (limited crawl): ${BASE_URL}"

# Keep this shallow to avoid hammering staging.
# Default: check a small set of key URLs (no recursion).
# To force recursion: LINKCHECK_RECURSE=1
LINKCHECK_RECURSE="${LINKCHECK_RECURSE:-0}"

URLS=(
  "${BASE_URL}/"
  "${BASE_URL}/kontakt/"
  "${BASE_URL}/dostawa-i-platnosci/"
)

node -e "require('linkinator').LinkChecker" >/dev/null 2>&1 || true

ARGS=(
  --timeout 20000
  --concurrency 4
  --redirects warn
  --skip '.*\\.(jpg|jpeg|png|gif|webp|svg|woff2?|ttf|eot|mp4|webm)(\\?.*)?$'
  --format json
)
if [[ "${LINKCHECK_RECURSE}" == "1" ]]; then
  ARGS+=( --recurse )
fi

npx linkinator "${URLS[@]}" "${ARGS[@]}" > "${OUT_DIR}/linkinator.json"

echo "  -> ${OUT_DIR}/linkinator.json"
echo "Done."

