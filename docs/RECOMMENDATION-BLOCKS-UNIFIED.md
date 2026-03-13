# Recommendation blocks — unified system

## Pliki biorące udział w recommendation blocks

### Szablony (render)
| Blok | Plik | Sekcja / klasa |
|------|------|----------------|
| Podobne produkty (PDP) | `woocommerce/single-product/related.php` | `section.related.products` |
| Może spodoba się również… (PDP) | `woocommerce/single-product/up-sells.php` | `section.up-sells.upsells.products` |
| Cross-sells (koszyk) | WooCommerce core (brak override w theme) | `.cross-sells` |
| Bestsellery (główna) | `front-page.php` + shortcode `[mnsk7_bestsellers]` | `.mnsk7-section--bestsellers` + `ul.products` |

Wszystkie używają `wc_get_template_part( 'content', 'product' )` → ta sama struktura karty (obrazek, tytuł, cena, przycisk).

### Style — źródło prawdy
**Jeden moduł:** `assets/css/parts/12-related-products.css`

- Sekcje: nagłówki, odstępy — `.related.products`, `.upsells.products`, `.up-sells.products`, `.cross-sells`, `.mnsk7-section--bestsellers`
- Siatka i karta: te same selektory + `ul.products` / `li.product` dla wszystkich powyższych

### Style usunięte / uproszczone (były źródłem rozjazdu)
- **05-plp-cards.css** — usunięto `.related.products`, `.upsells.products`, `.cross-sells` z siatki; siatka tylko dla PLP/sklep.
- **06-single-product.css** — usunięto grid/card dla related/upsells; zostawiono tylko layout sekcji PDP (width, margin breakout).
- **21-responsive-mobile.css** — usunięto osobne reguły gridu dla related/upsells/cross-sells; breakpointy recommendation tylko w 12.
- **08-home-sections.css** — usunięto grid, kartę, przycisk i media dla bestsellerów; zostawiono obudowę sekcji i tytuł. Siatka i karta bestsellerów z 12.

## Wprowadzone breakpointy (wszystkie recommendation blocks)

| Szerokość | Kolumny | Plik |
|-----------|---------|------|
| ≥ 1200px | 3 | 12-related-products.css (default) |
| 768–1199px | 2 | 12, `@media (max-width: 1199px)` |
| < 768px | 2 | 12, `@media (max-width: 767px)` |
| ≤ 400px | 1 | 12, `@media (max-width: 400px)` |

Jedno zachowanie na wszystkich stronach: PDP, koszyk, główna.

## Wspólny komponent karty

- **Obrazek:** `aspect-ratio: 1`, `object-fit: cover`, kontener z `overflow: hidden`.
- **Tytuł:** `-webkit-line-clamp: 2`, `min-height: 2.8em`, ten sam font i padding we wszystkich blokach.
- **Cena:** `white-space: nowrap`, `text-overflow: ellipsis` (bez łamania po cyfrze).
- **CTA:** `writing-mode: horizontal-tb`, `white-space: normal`, `min-width: 7rem`, ten sam padding i styl — brak pionowego tekstu.
- **Karta:** `display: flex`, `flex-direction: column`, `min-height: 0`, `overflow: hidden`, równe wysokości w rzędzie.

## QA checklist

### Desktop (≥ 1200px)
- [ ] Podobne produkty (PDP): 3 karty w rzędzie.
- [ ] Może spodoba się również… (PDP): 3 karty w rzędzie.
- [ ] Cross-sells (koszyk): 3 karty w rzędzie.
- [ ] Bestsellery (główna): 3 karty w rzędzie.
- [ ] Wysokości kart w jednym rzędzie równe; tytuł max 2 linie; przycisk w jednej linii (lub dwa krótkie).

### Tablet (768–1199px)
- [ ] Wszystkie recommendation blocks: 2 karty w rzędzie.
- [ ] Spacing między kartami i sekcjami spójny.

### Mobile (< 768px)
- [ ] Wszystkie recommendation blocks: 2 karty w rzędzie (do 400px), potem 1 karta.
- [ ] Przycisk „Dodaj do koszyka” nie układa się w pionowy słup liter.
- [ ] Cena nie łamie się po jednej cyfrze.
- [ ] Karty nie są zbyt wąskie (min 1 kolumna od 400px w dół).

### Spójność
- [ ] Ten sam gap między kartami w każdym bloku na tym samym breakpoincie.
- [ ] Nagłówki sekcji (h2) i odstępy nad/dół sekcji zgodne między blokami.
- [ ] Brak slidera/carousel — zwykły CSS grid.
