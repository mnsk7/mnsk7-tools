# Home + Header + PDP Backlog

Date: 2026-03-31

## P0

### Header search architecture

- Keep one search pattern per breakpoint:
  - desktop `>= 1024`: inline header search
  - tablet `769-1023`: full-width search row below header
  - mobile `<= 768`: icon-triggered search panel below header
- Render real mobile/tablet panel markup in the header template.
- Make JS search state authority breakpoint-aware instead of dropdown-only.
- Keep mobile overlay rules deterministic: menu/search/cart do not compete.

Acceptance:
- search opens on mobile
- search row is visible on tablet without duplicate icon UI
- desktop search remains visible inline
- Escape closes active mobile search and returns focus to the trigger

## P1

### Homepage hierarchy

- Keep homepage order as:
  - hero
  - bestsellers
  - trust
  - catalog
  - loyalty
  - Instagram
- Keep one dominant hero CTA.
- Remove weak hero media instead of carrying decorative filler.
- Limit homepage bestsellers to a compact first-scroll set.
- Keep fallback links deterministic when material/tag mapping is missing.

Acceptance:
- first screen explains what is sold, why trust, and what to do next
- one primary CTA is visually dominant
- no hero illustration block remains
- bestsellers block includes a clear "see all" continuation

### CTA and copy consistency

- Use one primary CTA language/style family across homepage sections.
- Keep secondary actions visibly secondary.
- Keep homepage copy specific to CNC buying flow, not generic shop language.

## P1

### PDP validation and polish

- Preserve current above-the-fold order:
  - title
  - price + sold count
  - variants / add-to-cart
  - trust
- Verify that technical parameters are not presented as variant choices.
- Add smoke coverage for sticky CTA behavior on mobile.

Acceptance:
- price and add-to-cart are above fold on mobile and desktop
- sticky CTA leads to a real purchase action target
- sold count/social proof stays near the price

## P2

### Related cards and section polish

- Simplify related-product card scanability.
- Improve guest-facing loyalty messaging.
- Consider deeper homepage SEO content only after conversion-critical work is stable.

## Regression / Smoke

- Header:
  - menu open/close
  - search open/close
  - cart open/close
  - Escape
  - resize between mobile/tablet/desktop
- Homepage:
  - hero readable without scroll
  - trust precedes deeper catalog exploration
  - no broken chips or fallback links
- PDP:
  - title/price/cart visible above fold
  - sticky CTA behavior

## Implementation Files

- [header.php](C:\Users\CEM\Desktop\staging.mnsk7-tools.pl\wp-content\themes\mnsk7-storefront\header.php)
- [functions.php](C:\Users\CEM\Desktop\staging.mnsk7-tools.pl\wp-content\themes\mnsk7-storefront\functions.php)
- [front-page.php](C:\Users\CEM\Desktop\staging.mnsk7-tools.pl\wp-content\themes\mnsk7-storefront\front-page.php)
- [content-single-product.php](C:\Users\CEM\Desktop\staging.mnsk7-tools.pl\wp-content\themes\mnsk7-storefront\woocommerce\content-single-product.php)
