# SEO runtime contract

The production SEO behavior is implemented in `mu-plugins/inc/seo.php` and
`mu-plugins/inc/term-seo.php`.

## Indexation

- WooCommerce attribute archives (`pa_*`) are `noindex, follow` and excluded
  from Yoast and WordPress core sitemaps.
- Catalog URLs with filter, price, rating, stock or ordering parameters are
  `noindex, follow` and canonicalize to the clean shop/category URL.
- Cart, checkout, account/login/password reset, wishlist and ShopEngine template
  URLs are `noindex, follow` and excluded from Yoast sitemaps.
- Category pagination remains crawlable. Page 2+ uses a self-referencing
  canonical and is linked with ordinary pagination links.
- Do not block these URLs in `robots.txt`; crawlers must be able to see the
  `noindex` directive.

## Offer variant pages

Pages matching `/oferta-*-warianty/` use `_mnsk7_offer_seo_state` with one of:

- `ready`: indexable commercial page;
- `draft`: `noindex, follow` and excluded from the sitemap;
- `duplicate`: redirects only when `_mnsk7_offer_redirect_product_id` or
  `_mnsk7_offer_redirect_url` points to an exact equivalent.

Without explicit meta, a visible `w przygotowaniu` notice is treated as
`draft`; other pages stay `ready`. A WordPress numeric duplicate such as
`*-warianty-2` redirects to the published base page only when both pages have
the same title. Ready pages are self-canonical. There is no blanket redirect
to the home or category page.

## Product category content

The WooCommerce taxonomy template renders one H1, a short term description
above the live product loop, and `_mnsk7_term_seo_after` plus visible FAQ below
it. FAQ schema is emitted only for questions rendered on the page. Prices,
stock, SKU and product cards always come from WooCommerce.

The initial curated profiles are stored into term description/meta by the
versioned migration in `mu-plugins/inc/term-seo.php`. CSS is scoped to
`.mnsk7-term-seo`.
