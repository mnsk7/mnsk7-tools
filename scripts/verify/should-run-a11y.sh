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

SHOULD_RUN=0
HAS_NON_TOOLING=0

while IFS= read -r f; do
  case "$f" in
    # verify plumbing / docs-only: не является причиной запускать a11y,
    # но и НЕ должно отменять запуск, если в diff есть UI/Woo изменения.
    package.json|playwright.config.*|scripts/verify/*|docs/*|.cursor/*)
      ;;

    # Theme / templates / CSS / JS
    wp-content/themes/*/*.php|wp-content/themes/*/header.php|wp-content/themes/*/footer.php)
      SHOULD_RUN=1
      HAS_NON_TOOLING=1
      ;;
    wp-content/themes/*/assets/css/*|wp-content/themes/*/assets/css/parts/*)
      SHOULD_RUN=1
      HAS_NON_TOOLING=1
      ;;
    wp-content/themes/*/assets/js/*)
      SHOULD_RUN=1
      HAS_NON_TOOLING=1
      ;;

    # Woo templates in theme
    wp-content/themes/*/woocommerce/*)
      SHOULD_RUN=1
      HAS_NON_TOOLING=1
      ;;

    # Runtime plugins can affect frontend UI too (messages/buttons)
    wp-content/mu-plugins/*|wp-content/plugins/*)
      SHOULD_RUN=1
      HAS_NON_TOOLING=1
      ;;

    *)
      HAS_NON_TOOLING=1
      ;;
  esac
done <<< "$CHANGED"

if [[ "$SHOULD_RUN" == "1" ]]; then
  exit 0
fi

exit 1

