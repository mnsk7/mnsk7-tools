# PLP: parity URL z `?filter_*` i bez (header/layout/body_class)

## Problem

Ta sama strona archiwum (sklep, kategoria, tag) **bez** parametrów `?filter_...` i **z** nimi nie może różnić się:

- zestawem klas na `<body>`,
- użytym headerem / partialem,
- wyborem layoutu (tabela vs siatka kart) w zależności od czegoś innego niż user-agent.

Jeśli przy `?filter_*` pluginy (np. Filter Everything) oznaczają request jako „filter request” i zmieniają body_class lub inne warunki, layout/header może „przełączyć się” (np. brak `tax-product_cat` → inne style w `24-plp-table.css`). Na mobile cache może oddać wersję wygenerowaną dla desktopa → tabela zamiast siatki.

## Co jest uruchamiane przy `?filter_*`

1. **WooCommerce**  
   - `woocommerce_product_query`: tema dodaje `tax_query` z parametrów `filter_*` (np. `filter_srednica` → `pa_srednica`).  
   - Nie zmienia: template hierarchy, queried object, body_class.  
   - Ten sam szablon: `woocommerce/archive-product.php`, ten sam `get_header('shop')`.

2. **Filter Everything (jeśli aktywny)**  
   - `parse_request`: jeśli wykryje „filter request” (swoje skonfigurowane slugi), ustawia `wpc_is_filter_request` i dodaje do `body_class` m.in. `wpc_is_filter_request`.  
   - Nie zmienia template_include.  
   - Może (w zależności od wersji) dodawać/odejmować inne klasy.

3. **Tema mnsk7-storefront**  
   - `archive-product.php`: czyta `$_GET[filter_*]` tylko do chipów, „Wybrane filtry”, empty state.  
   - Layout (tabela vs siatka) zależy wyłącznie od `mnsk7_is_mobile_request()` (user-agent), nie od obecności `?filter_*`.  
   - Aby uniknąć rozjazdu przy „filter request”, tema **normalizuje** kontekst.

## Wprowadzone poprawki (root cause)

1. **`mnsk7_is_plp_archive()`**  
   Jedno miejsce wykrywania „strona archiwum produktów” (sklep / kategoria / tag), z fallbackiem na `get_queried_object()` (term product_cat/product_tag), żeby wynik był taki sam z `?filter_*` i bez.

2. **Filtr `body_class` (priority 999)**  
   Na stronach PLP (gdy `mnsk7_is_plp_archive()`) wymusza obecność klas potrzebnych do layoutu:  
   `woocommerce`, `woocommerce-page`, `post-type-archive`, `post-type-archive-product` oraz dla taxonomii `tax-product_cat` / `tax-product_tag`.  
   Dzięki temu nawet gdy plugin przy „filter request” coś zmieni, style z `24-plp-table.css` dalej się stosują.

3. **Header (menu „Sklep”)**  
   Zamiast osobno `is_shop()` / `is_product_category()` / `is_product_tag()` używane jest `mnsk7_is_plp_archive()`, żeby „current” w menu było spójne z `?filter_*` i bez.

4. **`Vary: User-Agent` na archiwum**  
   W `send_headers` na stronach PLP ustawiany jest nagłówek `Vary: User-Agent`, żeby cache (CDN/LiteSpeed itd.) nie serwował wersji desktop użytkownikom mobile.

## Macierz testów (akceptacja)

| Typ strony   | Bez filtra     | Z `?filter_*`   | Kryterium akceptacji                          |
|-------------|----------------|-----------------|-----------------------------------------------|
| Sklep       | header, layout | header, layout  | Ten sam masthead, ten sam wrapper, layout po UA |
| Kategoria   | body_class, UA | body_class, UA  | `tax-product_cat`, brak tabeli na mobile      |
| Tag         | body_class, UA | body_class, UA  | `tax-product_tag`, brak tabeli na mobile      |

- Filtrowane URL-e **nie** przełączają na inny, „zepsuty” header/layout.  
- Filtrowane strony **nie** pokazują tabeli desktop/tablet na mobile.  
- Filtrowane i zwykłe archiwum używają **tego samego** zestawu komponentów (header, body classes, layout po UA).

Testy E2E: `e2e/plp-filter-url-parity.spec.js` (oraz zmienna `PLP_CATEGORY_SLUG` dla kategorii).
