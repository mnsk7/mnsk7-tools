# AS-IS Audit — mnsk7-tools.pl

**Date:** 2026-03-05  
**Agent:** 00_as_is_audit  
**Method:** File system scan (wp-content, themes, mu-plugins, docs), existing DB/catalog notes. Live site not crawled; items unchecked on production are marked **ASSUMPTION**.

---

## 1. Key pages

| Page type | URL / template | Source / note |
|-----------|----------------|---------------|
| **Home** | `/` | `wp-content/themes/tech-storefront/front-page.php` |
| **Shop (catalog)** | WooCommerce shop base (e.g. `/sklep/`) | WooCommerce; category listing uses `archive-product.php` |
| **Category** | `/product-category/{slug}/` | `tech-storefront/woocommerce/archive-product.php` |
| **Product** | `/product/{slug}/` | `tech-storefront/woocommerce/single-product.php`, `content-single-product.php` |
| **Cart** | `/koszyk/` (Polish) or `/cart/` | WooCommerce; blocked from index in `robots.txt` |
| **Checkout** | `/zamowienie/` or `/checkout/` | WooCommerce; blocked from index in `robots.txt` |
| **Account** | `/moje-konto/` or `/my-account/` | WooCommerce; `Disallow: /moje-konto/` and `/my-account/` in `robots.txt` |
| **Search** | `/?s=...` or `/search/` | `Disallow: /search/` and `Disallow: /*?s=` in `robots.txt` |
| **Policy / service** | | |
| — Delivery & payments | Page using template "Dostawa i płatności" | `tech-storefront/page-dostawa.php` |
| — Contact | Page using template "Kontakt" | `tech-storefront/page-kontakt.php` |
| — SEO landing (CNC) | e.g. `/cnc-frezy/` or page with template | `tech-storefront/page-cnc-frezy.php` |
| — Material landings | e.g. frezy-aluminium, frezy-mdf, frezy-stali | `page-frezy-aluminium.php`, `page-frezy-mdf.php`, `page-frezy-stali.php` |

**ASSUMPTION:** Exact shop/cart/checkout/account slugs depend on WooCommerce permalink settings on server; Polish slugs are documented in repo and `robots.txt`.

**Staging vs production:** Porównanie drogi użytkownika (główna, kategoria, PDP, koszyk, checkout) oraz mapowanie plików — [USER_JOURNEY_STAGING_VS_PROD.md](USER_JOURNEY_STAGING_VS_PROD.md). **Krytyczne:** staging musi mieć osobną bazę (DB_NAME); inaczej zmiana motywu/opcji na staging zmienia prod.

---

## 2. Catalog / content

### 2.1 Attributes

- **Observed (from prior DB check, staging):** 17 global attributes, e.g. `fi` (Średnica trzpienia), `srednica`, `r`, `er`, `typ-pilnika`, `kat-skosu`, `typ`, `dlugosc-calkowita-l`, `dlugosc-robocza-h`, `zastosowanie`, `wymiary-trzpienia`, `czolo`, `ksztalt`, `kat`. Diameter, shank, length, radius, type present; **ASSUMPTION:** "material" / "coating" / "flutes" may exist under other names or need to be verified in Admin → Attributes.

### 2.2 SKU

- **Observed:** 423 product meta records with `_sku`; 0 empty. Format mixed (numeric and codes e.g. H0410070101). Good fill rate.

### 2.3 Descriptions (structure: benefit vs parameters)

- **ASSUMPTION:** Not verified from code; requires manual check on 3–5 product pages for consistent structure (benefit vs specs vs compatibility). Client interview noted cards are text-heavy without quick access to key parameters.

### 2.4 Photos

- **Alt/title:** Staging DB: 1690 attachments; 56 with non-empty alt, **1634 with empty/missing alt** (~97%) — high SEO/accessibility risk.
- **Style/count/size:** **ASSUMPTION.** Repo/docs note ~34k files in uploads, large PNGs (3–4 MB), hashed filenames; WebP rare (~0.5%). Consistent style and per-product count not verified in code.

---

## 3. UX / conversion

### 3.1 CTA, price, delivery, returns near CTA

- **Observed:** Theme uses `woocommerce_single_product_summary` (price, add-to-cart). MU-plugin `inc/delivery.php` provides free-shipping notice in cart/checkout; product-card and checkout logic in `mu-plugins/inc/product-card.php`, `checkout.php`. Docs (UI_SPEC, WOO_CONVERSION_REWORK_PLAN) require single primary CTA "Dodaj do koszyka" and delivery/VAT visible — **ASSUMPTION:** placement and clarity on live PDP need visual check.

### 3.2 Filters / sort

- **Observed:** Four filter plugins cited in docs (filter-everything, woo-product-filter, woocommerce-products-filter, woof-by-category) — conflict and duplicate URL risk. Child theme hides `.woocommerce-ordering` in `.custom_product_widget` (style.css). **ASSUMPTION:** Which filter is primary and whether sort/filters are useful/sufficient on category pages — manual check.

### 3.3 Mobile

- **ASSUMPTION:** Client feedback: "poor mobile version"; not verified in repo (no viewport or mobile-specific audit in code).

### 3.4 Trust (policies, guarantee, reviews)

- **Observed:** Delivery page template (`page-dostawa.php`), Contact template (`page-kontakt.php`). Shortcodes for Allegro trust/rating and reviews (`[mnsk7_rating]`, `[mnsk7_allegro_reviews]`, `[mnsk7_allegro_trust]`) in `mu-plugins/inc/shortcodes.php`. FAQ sets in `inc/faq.php` (dostawa, produkt, sklep). **ASSUMPTION:** Visibility of policies and reviews on key pages (PDP, footer) not confirmed on live site.

---

## 4. SEO

### 4.1 Title / H1 on categories and products

- **ASSUMPTION:** Not derivable from code; needs browser/Search Console check for unique, non-template Title/H1 on category and product pages.

### 4.2 Duplicates, thin pages (filters)

- **Observed:** Multiple filter plugins can create many parameter URLs. **ASSUMPTION:** Whether filter URLs are indexed and whether noindex/canonical is set depends on plugin and Yoast; needs GSC/URL check.

### 4.3 Indexing (robots.txt, sitemap)

- **Observed (repo root `robots.txt`):**  
  Disallow: `/wp-admin/`, `/xmlrpc.php`, `/wp-login.php`, `/cart/`, `/checkout/`, `/my-account/`, `/moje-konto/`, `/author/`, `*/feed/`, `*/embed/`, `*/trackback/`, `/search/`, `/*?s=`, `/*?add-to-cart=`, `/*?utm_*`, `/*?openstat=`.  
  Allow: `/wp-content/uploads/`, `/wp-content/themes/`, `/wp-content/plugins/`, `/*.js$`, `/*.css$`.  
  Sitemap: `https://mnsk7-tools.pl/sitemap_index.xml`.
- **Note:** No `Disallow: /?` in repo; production may differ. Yoast and sitemap referenced in docs.

### 4.4 Schema (Product, BreadcrumbList, FAQ)

- **Observed:** Yoast SEO used; duplicate schema from `schema-and-structured-data-for-wp` noted in docs — risk of double JSON-LD. BreadcrumbList/FAQ not verified in code; **ASSUMPTION:** Yoast handles Product/BreadcrumbList; FAQ schema depends on FAQ blocks/shortcodes.

---

## 5. Performance

### 5.1 Heavy images, CLS/LCP sources

- **Observed:** Docs and prior audit: ~34k files in uploads, large PNGs (3–4 MB), few WebP. Product images are likely LCP elements. **ASSUMPTION:** Actual LCP/CLS values and which elements cause layout shift need real-device or Lighthouse run.

### 5.2 Caching (exclusions for cart/checkout)

- **Observed:** LiteSpeed Cache and Seraphinite Accelerator mentioned as active; WP Rocket config folder empty; Redis object cache. **ASSUMPTION:** Cart/checkout/my-account exclusion must be verified in active cache plugin settings on server.

---

## 6. Tech

### 6.1 Theme

- **Parent:** `best-shop` (gradientthemes.com), commercial Woo theme.
- **Child:** `tech-storefront` (Template: best-shop). Child theme present; customisation via `add_filter('best_shop_settings', ...)` (colors, fonts, header layout `woocommerce-bar`, preloader off, logo width, layout, footer, sidebar for Woo left).

### 6.2 Woo overrides

- **best-shop:** `woocommerce/content-product.php` (loop item).
- **tech-storefront:** `woocommerce/archive-product.php`, `woocommerce/single-product.php`, `woocommerce/content-single-product.php`. No loop override in child; product loop styling in `assets/css/mnsk7-product.css`.

### 6.3 Plugins (from docs; not re-scanned)

- **Conflicts/duplicates:** 3+ filter plugins; 2× Przelewy24 (gateway vs Raty to confirm); 2× wishlist; 2× page builder (Elementor, Beaver Builder); 2× profile (Ultimate Member, Profile Builder); 2× GTM; 2× schema (Yoast + schema-and-structured-data-for-wp); 2× Facebook (facebook-for-woocommerce, official-facebook-pixel).
- **Cache:** LiteSpeed + Seraphinite + (WP Rocket unclear); Redis object cache. **ASSUMPTION:** Full plugin list and active state only on server.

### 6.4 Child theme custom code

- **Location:** `wp-content/themes/tech-storefront/`  
  `functions.php`: theme settings, footer copyright override, custom header, parent/Inter font, preloader script, mnsk7-product.css enqueue, menu filter adding product categories under "Sklep".  
  `footer.php`, `front-page.php`, page templates (dostawa, kontakt, cnc-frezy, frezy-aluminium, frezy-mdf, frezy-stali).  
  `style.css`: CSS for product widget, pagination, add-to-cart, hover, wishlist, accessibility, Woo bar, search, preloader, newsletter, currency, custom widget.

### 6.5 Custom code outside theme

- **Repo root `mu-plugins/`:**  
  `mnsk7-tools.php` (bootstrap), `staging-safety.php`; `inc/`: constants, loyalty, product-card, delivery, shortcodes, seo, faq, checkout, woo-ux, performance.  
  **ASSUMPTION:** Deploy syncs this folder to `wp-content/mu-plugins/` on server (see DEPLOY_PLAYBOOK / STAGING_AND_GITHUB). xmlrpc blocked in mu-plugin (P0-03).

### 6.6 Security

- **Observed:** limit-login-attempts-reloaded in docs; xmlrpc blocked in mu-plugin (403 on `XMLRPC_REQUEST`). user-role-editor mentioned. **ASSUMPTION:** Backups (plugin or host), `DISALLOW_FILE_EDIT`, and role review not confirmed in repo.

---

## 7. Report file paths (saved)

| Document | Path |
|----------|------|
| AS-IS Audit | `/docs/AS_IS_AUDIT.md` |
| AS-IS Backlog | `/docs/AS_IS_BACKLOG.md` |
| AS-IS Risks | `/docs/AS_IS_RISKS.md` |

---

## 8. Progress (staging, deploy, scripts)

- Staging: https://staging.mnsk7-tools.pl; separate DB; `WP_ENVIRONMENT_TYPE=staging`; staging-safety mu-plugin (no mail, payments off, blog_public=0).
- Deploy: GitHub Actions push → rsync mu-plugins + themes to staging.
- Catalog check: `scripts/check-db-catalog.sh` (run with `DB_PASS` or use printed SQL in phpMyAdmin); results used in §2.
