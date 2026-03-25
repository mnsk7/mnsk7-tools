#!/usr/bin/env bash
set -euo pipefail

# Run only the Playwright specs relevant to current changes.
#
# Usage:
#   BASE_URL=https://staging.mnsk7-tools.pl bash scripts/verify/run-changed-e2e.sh

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

SPECS="$(bash scripts/verify/select-e2e-specs.sh || true)"

if [[ -z "${SPECS}" ]]; then
  echo "=== VERIFY: changed-e2e ==="
  echo "=== RESULT: changed-e2e: SKIP (no relevant changes detected) ==="
  exit 0
fi

echo "=== VERIFY: changed-e2e ==="
echo "${SPECS}" | sed 's/^/spec: /'

# shellcheck disable=SC2086
npx playwright test ${SPECS} --project=chromium
