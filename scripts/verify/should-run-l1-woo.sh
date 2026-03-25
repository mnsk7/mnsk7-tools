#!/usr/bin/env bash
set -euo pipefail

# Decide whether Woo L1 flow should run for the current changes.
#
# Env overrides:
# - VERIFY_L1=1 => run
# - VERIFY_L1=0 => skip
#
# Default policy:
# - RUN for Woo runtime changes (plugins/mu-plugins/woocommerce templates/theme JS).
# - RUN for high-impact global theme styling changes (main.css, tokens).
# - SKIP for header-only CSS changes (e.g. parts/04-header.css) unless forced.
#
# Exit codes:
# - 0 => should run
# - 1 => should skip

if [[ "${VERIFY_L1:-}" == "1" ]]; then exit 0; fi
if [[ "${VERIFY_L1:-}" == "0" ]]; then exit 1; fi

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

if ! command -v git >/dev/null 2>&1; then
  # Fail-safe: if git is unavailable, run.
  exit 0
fi

CHANGED="$(
  {
    git diff --name-only --diff-filter=ACMRTUXB 2>/dev/null || true
    git diff --name-only --cached --diff-filter=ACMRTUXB 2>/dev/null || true
  } | awk 'NF' | sort -u
)"

if [[ -z "$CHANGED" ]]; then
  exit 1
fi

while IFS= read -r f; do
  case "$f" in
    # verify plumbing / tests: НЕ запускаем Woo-flow автоматически
    # (слишком дорого и тормозит агентов). Форс — через VERIFY_L1=1.
    package.json|playwright.config.*|scripts/verify/*|e2e/woo-flow.spec.js|docs/*|.cursor/*)
      continue
      ;;
    # Woo runtime
    wp-content/mu-plugins/*|wp-content/plugins/*)
      exit 0
      ;;
    wp-content/themes/*/woocommerce/*)
      exit 0
      ;;
    wp-content/themes/*/functions.php)
      exit 0
      ;;
    wp-content/themes/*/assets/js/*)
      exit 0
      ;;
    # global styling that can affect Woo CTAs/forms
    wp-content/themes/*/assets/css/main.css|wp-content/themes/*/assets/css/parts/01-tokens.css)
      exit 0
      ;;
    # Woo-risk CSS areas (конверсия): cart/checkout/product/PDP.
    # PLP намеренно НЕ триггерит Woo-flow — это отдельный узел, не checkout.
    wp-content/themes/*/assets/css/*checkout*|wp-content/themes/*/assets/css/*cart*|wp-content/themes/*/assets/css/*product*|wp-content/themes/*/assets/css/*pdp*)
      exit 0
      ;;
    wp-content/themes/*/assets/css/parts/*checkout*|wp-content/themes/*/assets/css/parts/*cart*|wp-content/themes/*/assets/css/parts/*product*|wp-content/themes/*/assets/css/parts/*pdp*)
      exit 0
      ;;
  esac
done <<< "$CHANGED"

exit 1
