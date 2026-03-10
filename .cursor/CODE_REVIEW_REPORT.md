# CODE REVIEW REPORT — Mobile UX / responsive

## Root causes (привязка к стеку)

### 1. Header — jeden źródłowy plik
- **Plik:** `header.php`
- **Potwierdzenie:** Wszystkie szablony (front-page, archive-product, single-product, page-*, cart) wywołują `get_header()` → ten sam plik. Storefront header output jest usunięty w `functions.php` (init: remove_action storefront_header/storefront_footer). Legacy branches nie są aktywne w kodzie.
- **Conditionals:** Header nie zależy od `is_shop()` / `is_page()` dla struktury HTML — tylko dla current-menu-item w menu. Final render (DOM, klasy) jest ten sam.

### 2. ?filter_* i spójność layoutu
- **Pliki:** `functions.php` — `body_class` (999), `template_include` (5), `mnsk7_is_plp_archive()`, `mnsk7_is_plp_url_path()`
- **Mechanizm:** Przy URL typu /sklep/ lub /kategoria-produktu/... z lub bez `?filter_*`:
  - `template_include` wymusza `archive-product.php`, gdy `mnsk7_is_plp_url_path()` (ścieżka), żeby plugin zmieniający main query nie załadował index.php.
  - `body_class` dopisuje `woocommerce`, `post-type-archive-product`, `tax-product_cat`/`tax-product_tag` przy `mnsk7_is_plp_archive()` lub fallback po REQUEST_URI.
- **Ryzyko:** Cache (np. no-cache dla ?filter_*) może dać inny przetworzony HTML (minify/lazy) niż dla URL bez filtra — komentarz w functions.php (send_headers): nie ustawiać no-cache dla filter_*; po deploy pełne czyszczenie cache.

### 3. Breakpoint
- **Źródło:** `MNSK7_BREAKPOINT_MOBILE` = 768 (functions.php); w CSS: 768px / 769px.
- **Spójność:** Mobile = max-width: 768px; desktop = min-width: 769px (critical) lub min-width: 768px (04-header.css). Przy 768px oba media mogą teoretycznie pasować; w 04-header.css reguły mobile mają `display: none !important` dla search dropdown — wygrywają. Brak konfliktów.

### 4. Search na mobile (Pattern B)
- **Pliki:** `header.php` (#mnsk7-header-search-panel), `functions.php` (setSearchOpen, updateSearchDesktop, resize), `04-header.css` (.mnsk7-header-search-panel)
- **Logika:** Na mobile (< 768) dropdown w headerze jest ukryty; body.mnsk7-search-open pokazuje panel pod headerem; focus w input, Escape i klik poza zamykają. Jedna ścieżka, bez legacy.

### 5. Menu mobile
- **Pliki:** `header.php` (button .mnsk7-header__menu-toggle, #mnsk7-primary-menu), `functions.php` (toggle is-open na .mnsk7-header__nav), `04-header.css` (@media max-width: 768px — menu display:none, .is-open display:flex)
- **Submenu Sklep:** Na mobile w CSS .sub-menu display: none; link „Sklep” prowadzi do sklepu. Brak martwych kontrolek.

### 6. CSS cascade / specificity
- **Critical inline:** header.php (mnsk7-header-critical) + functions.php (mnsk7-global-layout-fix, priorytet 999) — nadpisują późne ładowanie lub cache. Nie ma kolizji z pluginami w zakresie headera (Storefront usunięty).

### 7. Overflow
- **21-responsive-mobile.css:** body, .site, #page, .site-content, #content — `overflow-x: hidden` na mobile. Użyte jako zabezpieczenie przed poziomym scrollem całej strony, nie jako „leczenie” layoutu wewnętrznych bloków. Zgodne z regułami (nie traktować jako maska defektu na pojedynczym komponencie).

### 8. Footer accordion
- **Pliki:** `footer.php` (inline script — breakpoint z MNSK7_BREAKPOINT_MOBILE), `09-footer.css` (@media max-width: 768px). Jedna obsługa delegowana; role/aria ustawiane przy matchMedia. Brak duplikatów.

## Podsumowanie

- **Root causes dla „inny header na różnych stronach”:** W kodzie nie ma rozgałęzień renderu headera; ewentualne różnice mogą wynikać tylko z cache/CDN lub niezaładowanego CSS — critical CSS w headerze i w footerze to łagodzą.
- **Root causes dla „?filter_* zmienia stan”:** Zapobieganie przez template_include i body_class; cache musi serwować spójną wersję po deploy.
- **Mobile fixes nie łamią desktopa:** Burger i search toggle są ukryte przy min-width: 769px (critical) i 768px (04-header.css); dropdown search i menu na desktop mają oddzielne reguły.
