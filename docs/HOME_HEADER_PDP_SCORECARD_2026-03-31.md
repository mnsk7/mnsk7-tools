# Home + Header + PDP Scorecard

Date: 2026-03-31  
Target: `https://staging.mnsk7-tools.pl`

## Summary

The current storefront is directionally solid: it communicates the CNC niche quickly, exposes category entry points, and keeps trust and commerce blocks on the homepage. The biggest confirmed blocker was mobile header search: CSS/JS were built around a below-header search panel, but the markup was missing from the live header template. That made the mobile search trigger unreliable.

PDP is structurally stronger than home: price, sold-count social proof, key specs, add-to-cart, and trust are already organized around the buybox. The remaining PDP work is mostly hierarchy and validation, not a full redesign.

## Homepage

### SEO

- H1 is niche-specific and answers what is sold.
- Hero now explains why this store is faster to use, not just that it exists.
- Homepage still depends heavily on internal navigation blocks rather than deeper editorial SEO content.
- Category entry is good, but homepage is still more conversion-first than search-first.

### Marketing / CRO

- Hero has one dominant CTA now.
- Trust sits high enough to support early conversion.
- Bestsellers are useful, but should stay compact; six items is a better first-scroll load than eight.
- Loyalty is useful for repeat buyers, but secondary for first-session conversion.

### Technical

- Confirmed architectural issue: mobile search panel markup was missing while CSS/JS expected it.
- Header search now has a breakpoint-specific contract:
  - desktop: inline dropdown/form in header
  - tablet: full-width row below header
  - mobile: icon-triggered panel below header
- Hero media asset was weak relative to the copy and layout, so the v1 decision is to remove it rather than keep decorative filler.

### Taste / Heuristics / Copy

- Homepage is cleaner with text-first hero and without side illustration noise.
- CTA hierarchy is better after demoting the secondary action from button weight.
- Copy is more task-oriented and less generic.
- Remaining polish work is mostly consistency between section CTAs and card language.

## Header

### Behavior

- Header keeps one search pattern per breakpoint.
- Tablet no longer shows both search icon and search row at the same time.
- Mobile overlay model remains: menu, search, and cart should not compete visually.

### Known Invariants

- Desktop search stays always available in the header row.
- Tablet search stays visible below header.
- Mobile search stays hidden until the icon is pressed.
- Escape should close active mobile overlays and return focus to the trigger.

### Open Risks

- Need live verification on iOS Safari and Android Chrome for focus/keyboard behavior on mobile search.
- Need regression check that header cart and menu still coexist correctly with the new search state authority.

## PDP

### Conversion Hierarchy

- Above-the-fold structure is already close to target:
  - title
  - price + sold count
  - key specs
  - add-to-cart area with inline availability
  - trust badges
- This is stronger than the homepage and does not need a structural rewrite in this cycle.

### Current Gaps

- Need verification that variants are always clearly separated from technical parameters.
- Need smoke coverage for sticky CTA honesty on mobile.
- Need a simpler scan pattern in related product cards.

## Priority Findings

### P0

- Missing `#mnsk7-header-search-panel` markup made mobile search unreliable.

### P1

- Homepage hero previously over-indexed on decoration and equal-weight actions.
- CTA system is still not fully unified across all homepage sections.
- PDP sticky CTA still needs behavior validation, not just visual validation.

### P2

- Homepage could still gain from stronger content depth for SEO.
- Related product cards can be made more scannable.
- Loyalty block can better differentiate guest vs logged-in messaging.

## Evidence

- Header markup: [header.php](C:\Users\CEM\Desktop\staging.mnsk7-tools.pl\wp-content\themes\mnsk7-storefront\header.php)
- Header interaction script: [functions.php](C:\Users\CEM\Desktop\staging.mnsk7-tools.pl\wp-content\themes\mnsk7-storefront\functions.php)
- Homepage template: [front-page.php](C:\Users\CEM\Desktop\staging.mnsk7-tools.pl\wp-content\themes\mnsk7-storefront\front-page.php)
- PDP template: [content-single-product.php](C:\Users\CEM\Desktop\staging.mnsk7-tools.pl\wp-content\themes\mnsk7-storefront\woocommerce\content-single-product.php)
