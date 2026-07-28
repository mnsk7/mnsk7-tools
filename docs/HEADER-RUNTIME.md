# Header runtime contract

Updated: 2026-07-28.

- `wp-content/themes/mnsk7-storefront/header.php` is the only markup source.
- Mobile accordion controls are server-rendered. JavaScript does not create navigation structure.
- `wp-content/themes/mnsk7-storefront/assets/js/header.js` is the only active header controller.
- `1024px` is the behavior breakpoint: desktop starts at `1024px`; mobile/tablet ends at `1023px`.
- Mobile states are mutually exclusive: closed, menu, or search. The desktop cart dropdown is controlled by the same state owner.
- Desktop and mobile use the same product-search form.
- WooCommerce owns cart fragments, prices, stock, URLs, and checkout behavior. Header code only presents the cart and closes stale UI after a fragment refresh.
- Header source styles live in `assets/css/parts/04-header.css`. Rebuild `assets/css/main.css` after every source change.

## Required verification

- Widths: 390, 768, 1023, 1024, and 1440 pixels.
- Pages: home, product archive, product detail, cart, and checkout.
- Interactions: menu, nested accordion, search, desktop mega-menu, cart, Escape, focus loop, resize across the breakpoint, and browser back/forward restoration.
- Guards: add-to-cart, cart visibility/update, checkout entry, and visible checkout form.
