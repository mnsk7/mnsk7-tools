# SEO runtime contract

The production SEO behavior is implemented in `mu-plugins/inc/seo.php` and
`mu-plugins/inc/term-seo.php`.

## Indexation

- WooCommerce attribute archives (`pa_*`) are `noindex, follow` and excluded
  from Yoast and WordPress core sitemaps.
- Catalog URLs with filter, price, rating, stock or ordering parameters are
  `noindex, follow` and canonicalize to the clean shop/category URL.
- Cart, checkout, account/login/register/edit-profile/password reset, wishlist
  and ShopEngine template URLs are `noindex, follow` and excluded from Yoast
  sitemaps.
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

## Controlled material facets

Only the product tags `aluminium`, `mdf` and `stal` are indexable. They use the
same WooCommerce taxonomy template, live product loop and term-meta content as
product categories. All other product tags remain under the Yoast taxonomy
policy. Because the taxonomy is globally noindex in Yoast, the three allowed
URLs are published through `material-facets-sitemap.xml`; the broad native
product-tag sitemap stays disabled.

The following thin legacy pages redirect only to exact live taxonomy
equivalents:

- `frez-spiralny`, `frez-prosty`, `frez-spiralny-stozkowo-kulowy`,
  `frez-grawerski`, `frez-kulowy`, `plytki-wieloostrzowe`,
  `tuleje-zaciskowe`, `frez-diamentowy` -> matching `product_cat`;
- `frezy-do-aluminium`, `frezy-mdf`, `frezy-do-stali` -> the controlled
  `product_tag` facets above.

The redirect is emitted only if WordPress resolves the configured target term.
`frezy-do-szlifierki` and `frez-z-wymiennymi-plytkami` do not have one exact
equivalent, so they stay functional as `noindex, follow` and remain outside the
sitemap.

## Page semantics and metadata

The default page template buffers the already server-rendered content and adds
a screen-reader H1 only when the content contains no H1. Cart and checkout keep
their existing dedicated H1 handling. Yoast remains the source of explicit
metadata; the runtime supplies a description only when Yoast has none.
Indexable brand archives receive the same empty-only fallback.

## Exact rollback for the 2026-07-23 material-facet migration

First revert the implementation commit and deploy it. Then restore the three
term records from the backup created before migration:

```bash
wp eval '$keys=["after"=>"_mnsk7_term_seo_after","faq"=>"_mnsk7_term_seo_faq","title"=>"_mnsk7_term_seo_title","meta"=>"_mnsk7_term_seo_metadesc","version"=>"_mnsk7_term_seo_version"]; foreach(["aluminium","mdf","stal"] as $slug){$term=get_term_by("slug",$slug,"product_tag"); if(!$term){continue;} $backup=get_term_meta($term->term_id,"_mnsk7_term_seo_backup_20260723",true); if(!is_array($backup)){continue;} wp_update_term($term->term_id,"product_tag",["description"=>$backup["description"]]); foreach($keys as $field=>$meta_key){if($backup[$field]===""){delete_term_meta($term->term_id,$meta_key);}else{update_term_meta($term->term_id,$meta_key,$backup[$field]);}} delete_term_meta($term->term_id,"_mnsk7_term_seo_backup_20260723");}'
```
