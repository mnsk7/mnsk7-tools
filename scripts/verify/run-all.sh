#!/usr/bin/env bash
set -euo pipefail

# Run verify suites with an explicit summary.
#
# Usage:
#   BASE_URL=https://staging.mnsk7-tools.pl bash scripts/verify/run-all.sh
#
# Exit code:
# - 0 if everything passed (skips are allowed locally)
# - 1 if any suite failed

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

BASE_URL="${BASE_URL:-https://staging.mnsk7-tools.pl}"
export BASE_URL

FAIL=0
REQUIRE_A11Y="${REQUIRE_A11Y:-}"
ALLOW_SKIP_A11Y="${ALLOW_SKIP_A11Y:-0}"

run_step () {
  local name="$1"
  shift
  echo ""
  echo "=== VERIFY: ${name} ==="
  if "$@"; then
    echo "=== RESULT: ${name}: PASS ==="
    return 0
  else
    echo "=== RESULT: ${name}: FAIL ==="
    FAIL=1
    return 1
  fi
}

run_step_capture () {
  local name="$1"
  shift
  echo ""
  echo "=== VERIFY: ${name} ==="

  local out
  out="$("$@" 2>&1)" || true
  echo "$out"

  if echo "$out" | grep -qiE '(^|[^a-z])flaky([^a-z]|$)'; then
    echo "=== RESULT: ${name}: FAIL (flaky) ==="
    FAIL=1
    return 1
  fi

  if echo "$out" | grep -qiE '(^|[^a-z])failed([^a-z]|$)'; then
    echo "=== RESULT: ${name}: FAIL ==="
    FAIL=1
    return 1
  fi

  echo "=== RESULT: ${name}: PASS ==="
  return 0
}

echo "BASE_URL=${BASE_URL}"

run_step "preflight" npm run verify:preflight || true

echo ""
echo "=== VERIFY: l0 (static/smoke) ==="
if npm run verify:l0; then
  echo "=== RESULT: l0 (static/smoke): PASS ==="
else
  echo "=== RESULT: l0 (static/smoke): FAIL ==="
  FAIL=1
fi

if bash scripts/verify/should-run-l1-woo.sh; then
  run_step_capture "l1 (woo flow)" npm run verify:l1 || true
else
  echo ""
  echo "=== VERIFY: l1 (woo flow) ==="
  echo "=== RESULT: l1 (woo flow): SKIP (no relevant changes; set VERIFY_L1=1 to force) ==="
fi

if [[ -z "${REQUIRE_A11Y}" ]]; then
  if bash scripts/verify/should-run-a11y.sh; then
    REQUIRE_A11Y="1"
  else
    REQUIRE_A11Y="0"
  fi
fi
export REQUIRE_A11Y

if [[ "${VERIFY_A11Y:-0}" == "1" || "${REQUIRE_A11Y}" == "1" ]]; then
  if [[ "${ALLOW_SKIP_A11Y}" == "1" && "${VERIFY_A11Y:-0}" != "1" ]]; then
    echo ""
    echo "=== VERIFY: a11y ==="
    echo "=== RESULT: a11y: SKIP (required by policy, but ALLOW_SKIP_A11Y=1) ==="
  else
    run_step "a11y" npm run verify:a11y || true
  fi
else
  echo ""
  echo "=== VERIFY: a11y ==="
  echo "=== RESULT: a11y: SKIP (no relevant changes; set VERIFY_A11Y=1 to force) ==="
fi

if [[ "${VERIFY_L2:-0}" == "1" ]]; then
  run_step "l2 (visual/regression)" npm run verify:l2 || true
else
  echo ""
  echo "=== VERIFY: l2 (visual/regression) ==="
  echo "=== RESULT: l2 (visual/regression): SKIP (set VERIFY_L2=1) ==="
fi

echo ""
if [[ "${FAIL}" -eq 0 ]]; then
  echo "=== VERIFY ALL: OK ==="
  exit 0
fi

echo "=== VERIFY ALL: FAILURES PRESENT ==="
exit 1
