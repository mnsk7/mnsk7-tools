#!/usr/bin/env bash
set -euo pipefail

# Select Playwright spec files to run based on current git diff.
#
# Output: one spec path per line (relative to repo root).
# Exit codes:
# - 0: printed one or more specs
# - 1: no specs selected (safe to SKIP)

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

if ! command -v git >/dev/null 2>&1; then
  # Fail-safe: if we cannot inspect changes, do not guess.
  exit 1
fi

CHANGED="$(
  {
    git diff --name-only --diff-filter=ACMRTUXB 2>/dev/null || true
    git diff --name-only --cached --diff-filter=ACMRTUXB 2>/dev/null || true
  } | awk 'NF' | sort -u
)"

if [[ -z "${CHANGED}" ]]; then
  exit 1
fi

# bash 3.2 compat: de-dup via a temp file + sort -u
SPECS_OUT="$(
  mktemp 2>/dev/null || echo "$ROOT/.tmp.verify-changed-specs.$$"
)"
cleanup () { rm -f "$SPECS_OUT" >/dev/null 2>&1 || true; }
trap cleanup EXIT

add_spec () { echo "$1" >> "$SPECS_OUT"; }

while IFS= read -r f; do
  case "$f" in
    # Direct spec edits: run the edited spec(s)
    e2e/*.spec.js)
      add_spec "$f"
      ;;

    # Header node
    wp-content/themes/*/assets/css/parts/04-header.css|wp-content/themes/*/header.php|wp-content/themes/*/assets/js/*header*)
      add_spec "e2e/header-layout.spec.js"
      ;;

    # Footer node
    wp-content/themes/*/assets/css/parts/09-footer.css|wp-content/themes/*/footer.php)
      add_spec "e2e/footer-accordion.spec.js"
      ;;

    # PLP node
    wp-content/themes/*/assets/css/parts/05-plp-cards.css|wp-content/themes/*/assets/css/parts/24-plp-table.css)
      add_spec "e2e/plp-layout.spec.js"
      add_spec "e2e/plp-filter-url-parity.spec.js"
      ;;
    wp-content/themes/*/assets/css/*plp*|wp-content/themes/*/assets/css/parts/*plp*)
      add_spec "e2e/plp-layout.spec.js"
      ;;

    # Mobile layout / responsive regressions (broad UI surface)
    wp-content/themes/*/assets/css/parts/21-responsive-mobile.css|wp-content/themes/*/assets/css/parts/20-responsive-tablet.css|wp-content/themes/*/assets/css/parts/25-global-layout.css)
      add_spec "e2e/mobile-design.spec.js"
      ;;

    # Design tokens can cascade widely; keep it conversion-safe + layout sanity.
    # NOTE: We do NOT auto-add Woo-flow here; L1 is gated separately by should-run-l1-woo.sh.
    wp-content/themes/*/assets/css/main.css|wp-content/themes/*/assets/css/parts/01-tokens.css|wp-content/themes/*/assets/css/parts/02-reset-typography.css)
      add_spec "e2e/mobile-design.spec.js"
      ;;
  esac
done <<< "${CHANGED}"

if [[ ! -s "$SPECS_OUT" ]]; then
  exit 1
fi

sort -u "$SPECS_OUT"
