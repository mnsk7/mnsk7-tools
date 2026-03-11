# Header/Footer — gdzie może być rozjazd layoutu

Dokument: przegląd kodu pod kątem **różnego renderu headera/footera** na różnych typach stron (/, /koszyk/, kategoria, tag, ?filter_*, PDP).  
Jedna `get_header()` / `get_footer()` nie gwarantuje identycznego finalnego DOM — wpływ mają: template path, wrapper, body_class, conditionals, CSS/JS.

---

## 1. Template path (który plik się ładuje)

| URL / kontekst | Template (hierarchy) | get_header | get_footer |
|----------------|----------------------|------------|------------|
| `/` (front) | `front-page.php` | tak | tak |
| `/koszyk/` | `page.php` (child) lub parent `page.php` | tak | tak |
| `/sklep/`, `/kategoria-produktu/...`, `/tag-produktu/...` | `woocommerce/archive-product.php` (wymuszony przez `template_include`) | tak | `get_footer('shop')` → footer.php |
| `?filter_*` na archiwum | ten sam `archive-product.php` (template_include po `mnsk7_is_plp_url_path()`) | tak | get_footer('shop') |
| PDP | `woocommerce/single-product.php` | tak | get_footer('shop') |
| Moje konto | WooCommerce — zależnie od parent theme | tak | zależnie od theme |
| Kontakt, Dostawa | page-kontakt.php, page-dostawa.php (Template Name) | tak | tak |

**Miejsce w kodzie:**  
- `functions.php`: `add_filter( 'template_include', ... )` (ok. 1363) — wymusza `archive-product.php`, gdy `mnsk7_is_plp_url_path()` (ścieżka sklep/kategoria/tag), żeby przy zmienionym main query (np. filter_*) nie wjechał index.php i był ten sam header/layout.

---

## 2. Wrapper / container (DOM pod #content)

Header w `header.php` zawsze kończy na:

```html
<div id="content" class="site-content">
```

Zamknięcie `</div><!-- #content -->` jest w **footer.php** (linia ~30). Wszystkie szablony muszą zostawić otwarte `#content` aż do footer.

### Różnica w strukturze

| Typ strony | Co jest wewnątrz #content (przed footer) |
|------------|------------------------------------------|
| **WooCommerce (archive, single)** | `do_action('woocommerce_before_main_content')` → **wrapper-start.php**: `<div id="primary" class="content-area"><main id="main" class="site-main">` → treść → wrapper-end: `</main></div>` |
| **front-page, page.php, single (Przewodnik), page-kontakt, page-dostawa** | Bez wrapper-start. Bezpośrednio `<main id="main" class="site-main ...">` + `.col-full` (brak `#primary`) |

Efekt:  
- Na **archiwum / PDP**: `#content` > `#primary` > `#main` > …  
- Na **stronie (koszyk, kontakt, główna)**: `#content` > `main#main` > … (brak `#primary`).

**Pliki:**  
- `woocommerce/global/wrapper-start.php` — dodaje `#primary` i `#main`  
- `woocommerce/global/wrapper-end.php` — zamyka oba  
- `footer.php` — zamyka `#content`

**Fix:** W 24-plp-table.css, 05-plp-cards.css, 06-single-product.css selektory rozszerzone o `#content > main.site-main` — ten sam layout dla Woo (#primary) i page (bezpośrednio main w #content).

---

## 3. Body classes

Źródła: WordPress (post type, taxonomy, page slug), WooCommerce (woocommerce, woocommerce-page, woocommerce-cart, itd.), **filtr w functions.php**.

### Filtr theme (functions.php, ok. 1320)

```php
add_filter( 'body_class', function ( $classes ) {
    if ( ! mnsk7_is_plp_archive() ) return $classes;
    $ensure = array( 'woocommerce', 'woocommerce-page', 'post-type-archive', 'post-type-archive-product' );
    // + tax-product_cat / tax-product_tag z get_queried_object() lub po REQUEST_URI (fallback przy filter_*)
    // ...
}, 999 );
```

Cel: przy URL sklep/kategoria/tag (także z `?filter_*`, gdy plugin zmieni main query) **zawsze** dopisać ten sam zestaw klas, żeby PLP layout i style (24-plp-table.css) działały tak samo.

Klasy krytyczne dla 24-plp-table.css:  
`post-type-archive-product`, `tax-product_cat`, `tax-product_tag`, `woocommerce`, `woocommerce-page`.

### Klasa mnsk7-has-promo (serwerowo)

- **body_class (PHP):** gdy `apply_filters('mnsk7_header_promo_text', '') !== ''`, dopisywana jest `mnsk7-has-promo` — header/layout nie zależą od JS przy first paint.  
- JS w `wp_footer`: tylko usuwa klasę po zamknięciu paska (sessionStorage).  
- CSS: `body.mnsk7-has-promo .mnsk7-header.mnsk7-header--sticky` (04-header.css).

---

## 4. Conditionals w header.php

- **Menu „Sklep” (current):**  
  `$is_shop_archive = mnsk7_is_plp_archive();`  
  → `current-menu-item` na `<li>` Sklep tylko na archiwum (sklep/kategoria/tag).  
  Używa `mnsk7_is_plp_archive()` (is_shop() + is_product_category() + is_product_tag() + fallback get_queried_object() + **mnsk7_is_plp_url_path()**), żeby przy filter_* nie stracić „Sklep” jako current.

- **Przewodnik (current):**  
  `is_page('przewodnik') || is_home() || is_singular('post')`

- **Dostawa (current):**  
  `is_page('dostawa-i-platnosci')`

- **Kontakt (current):**  
  `is_page('kontakt')`

- **Treść headera (logo, menu, search, konto, koszyk):** bez branchowania po typie strony — jeden markup.  
Różnice wizualne mogą wynikać tylko z **body_class** (np. `.woocommerce-account`) lub **CSS/JS** zależnych od strony.

---

## 5. Conditionals w functions.php (layout / header / footer)

- **Promo bar (mnsk7_header_promo_text):**  
  Pusty na: `is_front_page()`, `is_cart()`, `is_checkout()`, `is_page('dostawa-i-platnosci')`, `is_page('kontakt')`, oraz strony z szablonem Kontakt/Dostawa.  
  Na pozostałych (w tym archiwum, tag, filter_*) — tekst promocyjny. Różnica w **obecności** bloku promo, nie w samym headerze.

- **Cart content (the_content):**  
  Tylko `is_cart()` — fallback shortcode `[woocommerce_cart]` gdy strona pusta.

- **woocommerce_before_main_content (priority 5):**  
  Na PLP i wyszukiwaniu produktów usuwa breadcrumb z hooka (breadcrumb jest w archive-product.php w szablonie).

- **wp (hook):**  
  Na `is_shop() || is_product_category() || is_product_tag()` — usuwa drugi toolbar (sortowanie/paginacja z góry).  
  Nie zmienia headera/footera.

- **wp_footer (999):**  
  Inline style `#mnsk7-global-layout-fix`: wymusza m.in. tło headera/footera, ukrycie .products::before, desktopowe menu/search.  
  Ma działać na wszystkich stronach; jeśli na części requestów (np. cache, inny template) ten blok nie trafi do HTML, layout może się rozjechać.

---

## 6. CSS zależny od body / kontekstu

- **24-plp-table.css:**  
  `body.tax-product_cat`, `body.tax-product_tag`, `body.post-type-archive-product` — nagłówek kategorii, toolbar, primary/sidebar, chips, trust, grid.  
  Bez tych klas (np. błędna strona lub inny kontekst) te style nie działają.

- **15-delivery-contact.css (09-footer.css):**  
  `body.woocommerce-account` — search w headerze, przyciski, #content, .site-main, .col-full, footer.  
  `body.page` — footer (np. .mnsk7-footer).

- **18-cart-checkout.css:**  
  `.woocommerce-cart`, `.woocommerce-checkout` — style koszyka i checkoutu (nie sam header/footer).

- **04-header.css:**  
  `body.mnsk7-has-promo` — header sticky; `body.mnsk7-search-open` — panel wyszukiwania.

- **10-cookie-bar.css:**  
  `body.mnsk7-cookie-bar-visible` — #page.

Jeśli na jakimś URL body_class będzie inny (np. brak `woocommerce`, `tax-product_cat`, `page`), odpowiednie style nie zadziałają i header/footer mogą wyglądać inaczej.

---

## 7. Cache / różny HTML (z komentarza w functions.php)

W `send_headers` jest komentarz (ok. 1391–1395):  
NIE ustawiać `Cache-Control: no-cache` dla URL z `?filter_*`, bo wtedy odpowiedź nie trafia do cache, a plugin (WP Rocket itd.) nie przetwarza HTML (minify, lazy load) — strona z filtrem dostawała „raw” output, bez filtra — przetworzony; **header/footer wyglądały inaczej**.  
Po deployu trzeba czyścić **pełny cache**, żeby wszystkie URL (w tym z filter_*) dostały tę samą wersję.

---

## 8. Podsumowanie: gdzie szukać przyczyny rozjazdu

| Obszar | Co sprawdzić |
|--------|----------------|
| **Template** | Czy dla problematycznego URL rzeczywiście ładuje się oczekiwany plik (archive-product vs index vs page) — logować `template_include` lub sprawdzić View Source / komentarz w headerze. |
| **Wrapper** | Na koszyku/stronie brak `#primary`; na archiwum/PDP jest. CSS używający `#content #primary` tylko na Woo. |
| **body_class** | View Source: czy na category/tag/filter_* są `woocommerce`, `post-type-archive-product`, `tax-product_cat` / `tax-product_tag`. Czy na koszyku jest `page`, `woocommerce-cart`. |
| **Conditionals** | Promo bar tylko gdy nie front/cart/checkout/kontakt/dostawa. Menu current — zależne od mnsk7_is_plp_archive() i is_page(). |
| **Cache** | Jedna wersja HTML dla wszystkich URL (pełne czyszczenie cache po deployu); unikać no-cache tylko dla filter_*. |
| **JS** | Klasa `mnsk7-has-promo` dodawana z JS; jeśli skrypt nie załaduje się na części stron, brak klasy i inny wygląd headera. |
| **Krytyczne layouty** | Przeniesione do `parts/25-global-layout.css` (header, desktop menu/search, plp-trust) — ładowane jak zwykły CSS, nie zależą od wp_footer. |

---

## 9. Pliki do dalszej weryfikacji

- `header.php` — linie 70–95 (menu current), 38–49 (promo).
- `functions.php` — mnsk7_is_plp_archive (37–50), mnsk7_is_plp_url_path (58–106), body_class (1320–1356), template_include (1363–1386), mnsk7_header_promo_text (430–463), wp_footer inline (680–689).
- `footer.php` — zamknięcie `#content` (30).
- `woocommerce/global/wrapper-start.php`, `wrapper-end.php` — obecność `#primary` tylko na Woo.
- CSS: `24-plp-table.css` (body.tax-*, post-type-archive-product), `15-delivery-contact.css` / `09-footer.css` (body.woocommerce-account, body.page), `04-header.css` (body.mnsk7-has-promo, body.mnsk7-search-open).
