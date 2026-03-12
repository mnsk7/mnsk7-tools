#!/usr/bin/env bash
# E2E workflow: run tests → show results → suggest fixes.
# Usage: ./scripts/e2e-run-and-suggest.sh [spec...]
# Example: ./scripts/e2e-run-and-suggest.sh
# Example: ./scripts/e2e-run-and-suggest.sh e2e/header-layout.spec.js

set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

BASE_URL="${BASE_URL:-https://staging.mnsk7-tools.pl}"
REPORT_DIR="${ROOT}/test-results"
LOG_FILE="${REPORT_DIR}/e2e-run.log"
SUGGEST_FILE="${REPORT_DIR}/e2e-suggestions.md"

mkdir -p "$REPORT_DIR"

SPECS="${*:-e2e/header-layout.spec.js e2e/mobile-design.spec.js}"
echo "Running: BASE_URL=$BASE_URL npx playwright test $SPECS --project=chromium --reporter=list"
echo "Log: $LOG_FILE"
echo ""

BASE_URL="$BASE_URL" npx playwright test $SPECS --project=chromium --reporter=list 2>&1 | tee "$LOG_FILE" || true

echo ""
echo "--- Parsing results and writing suggestions to $SUGGEST_FILE ---"
node "$ROOT/scripts/e2e-suggest.js" "$LOG_FILE" "$SUGGEST_FILE"

echo ""
echo "Done. Open $SUGGEST_FILE for full suggestions."
