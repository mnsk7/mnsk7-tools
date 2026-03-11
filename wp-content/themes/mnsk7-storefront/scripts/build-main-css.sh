#!/usr/bin/env bash
# Build main.css from parts (fallback when parts/ not deployed).
# Order must match functions.php $parts array.
set -e
PARTS_DIR="$(dirname "$0")/../assets/css/parts"
OUT="$PARTS_DIR/../main.css"
PARTS=(00-fonts-inter 01-tokens 02-reset-typography 03-storefront-overrides 04-header 05-plp-cards 06-single-product 07-mnsk7-blocks 08-home-sections 09-footer 10-cookie-bar 11-hidden 12-related-products 13-seo-landing 14-faq 15-delivery-contact 16-woo-notices 17-buttons 18-cart-checkout 19-breadcrumbs 20-responsive-tablet 21-responsive-mobile 22-touch-targets 23-print 24-plp-table 25-global-layout)

{
  echo "/* =================================================================
   MNK7 Storefront — main.css (built from parts)
   Build: $(date -u +%Y-%m-%d)
   Run: scripts/build-main-css.sh
   ================================================================= */"
  for p in "${PARTS[@]}"; do
    f="$PARTS_DIR/${p}.css"
    if [[ -f "$f" ]]; then
      echo "/* === $p.css === */"
      cat "$f"
      echo ""
    fi
  done
} > "$OUT"
echo "Built $OUT ($(wc -l < "$OUT") lines)"
