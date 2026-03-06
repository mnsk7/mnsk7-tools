# Weryfikacja w kodzie — co jest zaimplementowane

Sprawdzone w repozytorium (main). Po deployu warto zweryfikować na stagingu w przeglądarce.

## Błąd krytyczny po deployu
W `wp-config.php` włącz tymczasowo: `define('WP_DEBUG', true);` i `define('WP_DEBUG_LOG', true);` — w `wp-content/debug.log` pojawi się dokładny komunikat (np. brakująca funkcja, parse error). Motyw ma zabezpieczenia: fallback menu jako nazwa funkcji, sprawdzenie `function_exists` przed `mnsk7_get_archive_attribute_filter_chips`, `is_object`/`method_exists` zamiast `instanceof WP_Query`, `is_readable` dla parent theme.

## Header i footer
- [x] **header.php** — klasa `mnsk7-header`, `mnsk7-header__inner`, logo + `wp_nav_menu` + Moje konto + koszyk. Nie zależy od `do_action('storefront_header')`.
- [x] **footer.php** — klasa `mnsk7-footer`, `mnsk7-footer__top`, `mnsk7-footer__inner`, trzy kolumny (Kontakt, Dostawa, Informacje) + pasek copyright. Stała treść, jasny tekst.
- [x] **04-header.css** — style dla `.mnsk7-header*`, sticky, mobile menu toggle.
- [x] **09-footer.css** — tło #0f172a, tekst #e2e8f0/#93c5fd, `.mnsk7-footer*`.
- [x] **functions.php** — skrypt w `wp_footer` dla `.mnsk7-header__menu-toggle` → toggle `.is-open` na `.mnsk7-header__nav`.
- [x] **20/21 responsive** — `.mnsk7-footer__inner` (grid 1 col na wąskich ekranach).

## Karty produktów — jedna siatka
- [x] **05-plp-cards.css** — jedna reguła grid dla `.woocommerce ul.products`, `.related.products ul.products`, `.upsells.products ul.products`, `.cross-sells ul.products`: 4 kolumny, gap 1.25rem, te same style karty.
- [x] **08-home-sections.css** — sekcja bestsellers *bez* nadpisania siatki (usunięty flex/width), dziedziczy z 05.
- [x] **20-responsive-tablet.css** — 2 kolumny dla tych samych selektorów przy max-width 768px.
- [x] **21-responsive-mobile.css** — 1 kolumna przy max-width 480px.

## Kategorie / archiwum
- [x] **archive-product.php** — na taxonomy: chips (podkategorie), drugi rząd chipsów z `mnsk7_get_archive_attribute_filter_chips()` (filtr atrybutów), tabela produktów.
- [x] **functions.php** — `woocommerce_product_query`: dodaje `tax_query` gdy w URL jest `filter_<atrybut>=slug`. `mnsk7_get_archive_attribute_filter_chips()` zwraca pierwszy atrybut z terminami (pa_srednica, pa_srednica-trzpienia, itd.).
- [x] **24-plp-table.css** — `.mnsk7-plp-chips`, `.mnsk7-plp-chip`, tabela, mobile (mniejszy padding, label na pełną szerokość).

## Strony SEO
- [x] **page-seo.php** — szablon "Strona SEO": H1, treść.
- [x] **scripts/wp-create-pages.py** — tworzy strony m.in. przewodnik, regulamin, polityka-prywatnosci z szablonem `page-seo.php`. Uruchomienie: z katalogu głównego projektu `python3 scripts/wp-create-pages.py` (wymaga .env: WP_BASE_URL, WP_USER, WP_APP_PASSWORD).
- [x] **scripts/wp-assign-cat-templates.py** — ustawia szablon `page-category-landing.php` dla podanych ID stron (env z katalogu nad scripts).

## Cache
- [x] **functions.php** — wersja stylów `$v = '3.0.0'` — po zmianach zwiększana, żeby przeglądarka pobrała nowe CSS.

## Co sprawdzić na żywo (staging)
1. Header: logo, menu, Moje konto, Koszyk; na mobile przycisk Menu otwiera listę.
2. Footer: ciemne tło, cały tekst czytelny (jasny).
3. Sklep / kategoria: karty w siatce 4→2→1, ta sama wysokość karty; w kategorii chipsy + opcjonalnie filtry atrybutów.
4. Strona główna: hero, bestsellery w tej samej siatce co sklep.
5. Po deployu: uruchomić `python3 scripts/wp-create-pages.py` — strony SEO (przewodnik, regulamin, polityka) mają być utworzone lub zaktualizowane.
