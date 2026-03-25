#!/usr/bin/env bash
set -euo pipefail

# Decide whether a11y smoke should run for the current changes.
#
# Env overrides:
# - VERIFY_A11Y=1 => run
# - VERIFY_A11Y=0 => skip
#
# Default policy:
# - RUN for theme/template/CSS/JS changes that can affect UI/CTA/contrast.
# - RUN for Woo templates (buttons/messages/checkout UI surface).
# - SKIP for verify tooling/docs-only changes unless forced.
#
# Exit codes:
# - 0 => should run
# - 1 => should skip

if [[ "${VERIFY_A11Y:-}" == "1" ]]; then exit 0; fi
if [[ "${VERIFY_A11Y:-}" == "0" ]]; then exit 1; fi

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
    # verify plumbing / docs-only: НЕ запускаем a11y автоматически
    # (слишком дорого и тормозит агентов). Форс — через VERIFY_A11Y=1.
    package.json|playwright.config.*|scripts/verify/*|docs/*|.cursor/*)
      exit 1
      ;;

    # Theme / templates / CSS / JS
    wp-content/themes/*/*.php|wp-content/themes/*/header.php|wp-content/themes/*/footer.php)
      exit 0
      ;;
    wp-content/themes/*/assets/css/*|wp-content/themes/*/assets/css/parts/*)
      exit 0
      ;;
    wp-content/themes/*/assets/js/*)
      exit 0
      ;;

    # Woo templates in theme
    wp-content/themes/*/woocommerce/*)
      exit 0
      ;;

    # Runtime plugins can affect frontend UI too (messages/buttons)
    wp-content/mu-plugins/*|wp-content/plugins/*)
      exit 0
      ;;
  esac
done <<< "$CHANGED"

exit 1

