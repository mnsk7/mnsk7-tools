# Header runtime contract

Production header implementation is owned by the `mnsk7-storefront` child theme.

## Sources of truth

- Semantic markup and WooCommerce data: `wp-content/themes/mnsk7-storefront/header.php`
- Isolated styles: `wp-content/themes/mnsk7-storefront/assets/css/parts/04-header-isolated.css`
- Interaction state: `wp-content/themes/mnsk7-storefront/assets/js/header-isolated.js`
- Asset enqueue, version and Woo cart fragments: `wp-content/themes/mnsk7-storefront/functions.php`
- Compiled stylesheet order: `wp-content/themes/mnsk7-storefront/scripts/build_main_css.py`

The isolated header uses the `.mnsk7h` namespace. Legacy `.mnsk7-header` runtime
controllers must remain disabled and must not be extended for new behaviour.

## Interaction contract

- Below 1024 px the header shows logo, menu, search, account and cart controls in
  one row.
- The mobile navigation is a fixed, vertically scrollable drawer below the header.
- `Sklep` toggles the category tree without navigation. `Zobacz cały sklep` is the
  explicit shop link.
- Category groups use server-rendered native `details`/`summary`; child groups are
  collapsed initially and one sibling group stays open at a time.
- At 1024 px and above the full navigation and search form are visible. `Sklep`
  opens by click.
- Crossing the 1024 px breakpoint closes the drawer, search, cart dropdown and all
  open category groups.
- The header contains one search form. Product/category/cart URLs and cart state
  come from WordPress and WooCommerce.
- Cached legacy Woo cart fragments are normalized by `header-isolated.js` after
  `wc_fragments_loaded`, `wc_fragments_refreshed` and `added_to_cart`.

## Required verification

- `npm run verify:l0`
- Visual checks at 390, 768 and 1440 px
- Chromium and WebKit mobile drawer checks
- Search, filtered product archive and pagination smoke
- Add-to-cart, cart visibility and checkout entry; never place a test order

## Rollback

Revert the isolated header commits in reverse order and push `main`:

```text
git revert --no-edit edfb3ab 629e227 68aed4e 09190c4 70cf1a7
git push origin main
```

This restores the previously deployed header implementation without touching
WooCommerce data, customer accounts, orders or inventory.
