# Chips / Filters UX — handoff (mobile catalog + PLP)

## Źródło danych (source of truth)

- **Kategorie:** `get_terms( 'product_cat', … )` — na Sklep: `mnsk7_get_megamenu_terms()['cats']` (top-level, cache w transient). Na archiwum kategorii: rodzeństwo (siblings) lub parent_id. Na archiwum tagu: top-level kategorie.
- **Tagi:** `get_terms( 'product_tag', … )` — na Sklep i PLP: `mnsk7_get_megamenu_terms()['tags']` (jak w megamenu). Na front-page katalog: osobne `get_terms` (number 20, orderby count).
- **Filtry atrybutów (PLP):** `mnsk7_get_archive_attribute_filter_chips()` — tylko atrybuty z terminami w bieżącym archiwum (kategoria/tag).

Etykiety grup (jak w megamenu): filtry `mnsk7_megamenu_heading_categories` (domyślnie „Rodzaje frezów”), `mnsk7_megamenu_heading_tags` („Zastosowanie i materiały”).

---

## Wzorzec chipów (chips pattern)

1. **Dwa scenariusze (nie mieszane):**
   - **A. Catalog chips** — strona główna, sekcja „Przeglądaj asortyment”: grupy = tagi + kategorie (dwie swipe rows).
   - **B. Filter chips na PLP** — sklep / kategoria / tag: grupy = kategorie (jedna row) + tagi (druga row) + wiersze filtrów atrybutów (Średnica, Dł. robocza itd.).

2. **Struktura grupy:** etykieta (`.mnsk7-plp-chips__label` / `.mnsk7-catalog-chips__label`) + poziomy scroll (`.mnsk7-plp-chips__scroll` / `.mnsk7-catalog-chips__scroll`). Na mobile: `overflow-x: auto`, `flex-wrap: nowrap`, `-webkit-overflow-scrolling: touch`, chipy `flex-shrink: 0`.

3. **„Więcej”:** jeśli w grupie > 8 chipów, pierwsze 8 w widoku, reszta w `<span class="mnsk7-plp-chips-more" hidden>` (lub `.mnsk7-catalog-chips-more`). Przycisk „Więcej” przełącza `hidden` i tekst na „Mniej”. Na PLP atrybuty: „Więcej filtrów” rozwija dodatkowe wiersze (istniejąca logika).

---

## Przejście do wyników po filtrze (after-filter landing)

- **Anchor:** `#mnsk7-plp-results` — docelowy kontener to pierwszy blok z listą produktów (`.mnsk7-plp-grid-mobile` na mobile lub `.mnsk7-product-table-wrap` na desktop). Oba mają `id="mnsk7-plp-results"`.
- **Linki:** Wszystkie linki chipów (kategorie, tagi, filtry atrybutów) oraz „Wyczyść wszystkie” / usuwanie pojedynczego filtra używają `mnsk7_plp_anchor_results( $url )`, która dopina `#mnsk7-plp-results` do URL.
- **Scroll po load:** W `wp_footer` na stronach PLP (sklep/kategoria/tag): jeśli w URL jest hash `#mnsk7-plp-results` albo jakikolwiek parametr `filter_*`, po załadowaniu DOM wywoływane jest `scrollIntoView({ behavior: 'smooth', block: 'start' })` na `#mnsk7-plp-results`. Dzięki temu użytkownik po wyborze filtra trafia do wyników, a nie zostaje przy filtrach.

Back/forward: standardowe zachowanie przeglądarki (hash w URL), bez AJAX.

---

## Zmienione pliki

| Plik | Zmiany |
|------|--------|
| `functions.php` | `mnsk7_plp_anchor_results()`, scroll do `#mnsk7-plp-results` w skrypcie PLP, init toggli „Więcej” dla catalog chips na front page |
| `woocommerce/archive-product.php` | Grupy chipów: rząd kategorii + rząd tagów (jak megamenu), `$render_plp_nav_row`, anchor na linkach filtrów i „Wyczyść”, `id="mnsk7-plp-results"` na grid/table |
| `front-page.php` | Katalog: dwie grupy (tagi, kategorie) z etykietą + `__scroll` + opcjonalne „Więcej” |
| `assets/css/parts/08-home-sections.css` | Style `.mnsk7-catalog-chips`, `.mnsk7-catalog-chips-group`, `__label`, `__scroll` (mobile: overflow-x), `.mnsk7-catalog-chips-more`, `.mnsk7-catalog-chips-toggle` |
| `assets/css/parts/24-plp-table.css` | Mobile: `.mnsk7-plp-chips--nav` — kolumna, label, poziomy scroll (jak `--attrs`) |
| `assets/css/main.css` | Zbudowany z parts (build-main-css.sh) |

---

## URL i viewporty do weryfikacji

- **URL:**  
  - Strona główna (katalog).  
  - Sklep (np. /sklep/).  
  - Kategoria (np. /product-category/…).  
  - Tag (np. /product-tag/…).  
  - URL z filtrem, np. `?filter_srednica=8`.
- **Viewporty:** 320, 360, 375, 390, 414, 430 px (mobile) + desktop (regresja).

Sprawdzić: chipy w grupach, poziomy scroll na mobile, „Więcej” rozwijane, po kliku w filtr / kategorię / tag — przeładowanie i scroll do bloku wyników; desktop bez rozjechania.

---

## Commit i push

- **Commit:** `12c64d3` — feat(PLP/catalog): chips UX — grouped swipe rows, tags+categories, scroll to results after filter.
- **Push:** `main -> main` (origin).
