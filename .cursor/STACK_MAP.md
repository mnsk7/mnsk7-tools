# STACK MAP — mnsk7-storefront (staging.mnsk7-tools.pl)

## Стек

- **CMS:** WordPress
- **E‑commerce:** WooCommerce
- **Parent theme:** Storefront (не в репозитории; sync только child)
- **Child theme:** mnsk7-storefront (Template: storefront), style.css v1.0.0

## Критичные файлы темы

| Назначение | Файл |
|------------|------|
| Header (единственный источник) | `wp-content/themes/mnsk7-storefront/header.php` |
| Footer | `footer.php` |
| Footer WooCommerce | `footer-shop.php` |
| Точка входа стилей | `assets/css/main.css` (части: 00–24, включая 04-header, 21-responsive-mobile, 09-footer) |
| Критичный inline CSS header | В `header.php`: `<style id="mnsk7-header-critical">` (breakpoint 769px / 768px) |
| Логика header (menu, search, cart, promo, scroll) | `functions.php` → `wp_footer` inline script (~761–979) |
| Footer accordion (mobile) | `footer.php` → inline script (breakpoint 768px) |

## WooCommerce overrides

- `woocommerce/archive-product.php` — get_header(), get_footer('shop')
- `woocommerce/single-product.php` — get_header(), get_footer('shop')
- `woocommerce/global/wrapper-start.php`, `wrapper-end.php`
- `woocommerce/loop/*`, `cart/*`, `single-product/*`

## Render paths

- **Homepage:** `front-page.php` → get_header(), get_footer()
- **Koszyk:** WooCommerce cart template → get_header() (общий), get_footer('shop')
- **PDP:** `woocommerce/single-product.php` → get_header(), get_footer('shop')
- **Archiwum (sklep, kategoria, tag):** `woocommerce/archive-product.php` → get_header(), get_footer('shop')
- **Strony:** `page-*.php`, `single.php`, `page-seo.php` → get_header(), get_footer() или get_footer('shop')

Все шаблоны вызывают один и тот же `get_header()` → один физический header.

## Условия и divergent state

1. **body_class:**  
   Фильтр `body_class` (приоритет 999) при `mnsk7_is_plp_archive()` дописывает:  
   `woocommerce`, `woocommerce-page`, `post-type-archive`, `post-type-archive-product`, при необходимости `tax-product_cat` / `tax-product_tag`.  
   При URL-path fallback (gdy plugin z `?filter_*` zmienia main query) dopisywane są klasy po ścieżке REQUEST_URI.

2. **template_include:**  
   Przy `mnsk7_is_plp_url_path()` wymusza szablon `archive-product.php`, żeby przy `?filter_*` nie ładować index.php i zachować ten sam header/layout.

3. **Storefront header:**  
   W `init` przy `mnsk7_parent_storefront_available()` usunięte są wszystkie akcje `storefront_header` i część `storefront_footer` — header = tylko header.php (mnsk7-header).

4. **Legacy:**  
   W komentarzu: "Legacy storefront_header output usunięty; nie dodawać tu żadnych elementów headera." Drugi header nie jest renderowany z kodu; ewentualne różnice mogą pochodzić z cache (np. inna wersja HTML dla URL z `?filter_*`).

## Breakpoint

- **Mobile:** max-width: 768px (w CSS i w JS `window.innerWidth <= 768` / `< 768`).
- **Desktop:** min-width: 769px (w critical CSS w header.php), 768px w 04-header.css.

## Pluginy (wp-content)

W repozytorium nie ma listy aktywnych pluginów; mogą wpływać: filtry (np. WPF), cache (WP Rocket itp. — w kodzie uwaga o no-cache i divergent DOM), WooCommerce.

## Miejsca wpływające na mobile header / search / menu / cart

- **HTML:** header.php (struktura: brand, nav + menu toggle + menu, actions: search wrap, account, cart).
- **CSS:** 04-header.css (media max-width: 768px, 430px, 360px), 21-responsive-mobile.css (body/site overflow-x, grid 2 col).
- **JS:** functions.php wp_footer — menu toggle, search toggle + panel (body class `mnsk7-search-open`), cart dropdown, promo dismiss, header shrink.
- **Critical inline:** header.php — podstawowy layout i ukrycie menu na mobile, pokazanie dropdownu search na desktop.

## Wpływ na footer (mobile accordion)

- footer.php: sekcje z `.mnsk7-footer__col`, na mobile role="button", aria-expanded, toggle is-open.
- Script w footer.php: breakpoint 768px, click/keydown na `.mnsk7-footer__title` toggluje `.is-open` na `.mnsk7-footer__col`.
