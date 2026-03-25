#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

# Local developer default: don't hard-fail on missing PHP.
# CI/release pipelines should explicitly set REQUIRE_PHP=1.
REQUIRE_PHP="${REQUIRE_PHP:-0}"

pass () { echo "=== RESULT: $1: PASS ==="; }
fail () { echo "=== RESULT: $1: FAIL ==="; return 1; }
skip () { echo "=== RESULT: $1: SKIP ==="; return 0; }

FAIL=0
VERIFY_LINKCHECK="${VERIFY_LINKCHECK:-0}"
VERIFY_LIGHTHOUSE="${VERIFY_LIGHTHOUSE:-0}"

echo ""
echo "=== L0: php-lint ==="
if command -v php >/dev/null 2>&1; then
  if bash scripts/validate-theme-php.sh; then pass "l0:php-lint"; else fail "l0:php-lint"; FAIL=1; fi
else
  if [[ "$REQUIRE_PHP" == "1" ]]; then
    echo "php not found in PATH."
    fail "l0:php-lint"; FAIL=1
  else
    echo "php not found in PATH (allowed)."
    skip "l0:php-lint"
  fi
fi

echo ""
echo "=== L0: link-check ==="
if [[ "$VERIFY_LINKCHECK" == "1" ]]; then
  if bash scripts/verify/link-check.sh; then pass "l0:link-check"; else fail "l0:link-check"; FAIL=1; fi
else
  echo "Link check is disabled by default. Enable with VERIFY_LINKCHECK=1"
  skip "l0:link-check"
fi

echo ""
echo "=== L0: lighthouse-smoke ==="
if [[ "$VERIFY_LIGHTHOUSE" == "1" ]]; then
  if bash scripts/verify/lighthouse-smoke.sh; then pass "l0:lighthouse-smoke"; else fail "l0:lighthouse-smoke"; FAIL=1; fi
else
  echo "Lighthouse smoke is disabled by default. Enable with VERIFY_LIGHTHOUSE=1"
  skip "l0:lighthouse-smoke"
fi

if [[ "$FAIL" -eq 0 ]]; then
  exit 0
fi
exit 1

