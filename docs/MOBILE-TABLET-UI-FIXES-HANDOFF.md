# Mobile/Tablet UI Fixes — handoff

**Data:** 2026-03-12  
**Zakres:** tylko staging.mnsk7-tools.pl, bez ogólnego refaktoringu.

---

## 1. Root cause i zmiany

### 1. Header (mobile/tablet)
- **Przyczyna:** Różne rozmiary przycisków i cart na 319px vs 549px, cart mógł uciekać w prawo, brak jednego systemu wymiarów.
- **Zmiany:** Ujednolicone action buttons (burger/search/account/cart): 44px od 431px, 40px do 430px, 36px do 360px, 34px do 320px. Cart jako jeden blok (`flex-shrink: 0`, `min-width: 0`). Na ≤400px dropdown koszyka full-width, ukryte `.mnsk7-header__cart-summary`. Na 320px badge mniejszy (14px), bez ukrywania — nic nie ucieka. `max-width` dla `.mnsk7-header__brand` na wąskich viewportach.

### 2. Mobile mega menu „Sklep”
- **Przyczyna:** Płaska lista, słaba hierarchia, mało „catalog pattern”.
- **Zmiany:** Wzmocnione nagłówki sekcji (uppercase, mniejszy font, kolor muted), większy odstęp między grupami (`gap: 0.75rem`), lewa obwódka w `--color-primary`. „Wszystkie produkty” jako CTA (tło `rgba(12,125,219,0.08)`, font-weight 700).

### 3. Search normalization
- **Przyczyna:** `fi 4mm` vs `fi 4 mm` dawały różne wyniki (brak spacji przed jednostką).
- **Zmiany:** W mu-plugin `mnsk7-tools.php` dodany `pre_get_posts`: dla wyszukiwania produktów (`post_type=product`) normalizacja `s` — wstawienie spacji między liczbą a jednostką (mm, cm, g, kg, ml) przez `preg_replace`. Np. `fi 4mm` → `fi 4 mm` przed zapytaniem WP.

### 4. Search UX (seam + purple focus)
- **Przyczyna:** Różny wygląd input+button na mobile/desktop, purple glow z przeglądarki.
- **Zmiany:** Dla inputów w header search i search-panel: `:focus { outline: none; box-shadow: none }`, `:focus-visible { outline: 2px solid var(--color-focus); outline-offset: 2px; box-shadow: none }`. `-webkit-appearance: none; appearance: none` na polach, żeby jeden input-group bez artefaktów.

### 5. Gap pod headerem / hero
- **Przyczyna:** Za duży pionowy odstęp na mobile.
- **Zmiany:** W `25-global-layout.css` dla `max-width: 768px`: `padding-top: 1.25rem` i `padding-left/right: 1rem` dla `#content`, `.site-content`, `.mnsk7-content`.

### 6. Białe/beżowe paski po bokach
- **Przyczyna:** Tło strony vs tło contentu — na bokach widać inną barwę.
- **Zmiany:** Dla `#content`, `.site-content`, `.mnsk7-content` ustawione `background: var(--color-bg, #fff)` w `25-global-layout.css`, żeby brak „obcych” pasków.

### 7. Section spacing (pustota na mobile)
- **Przyczyna:** Za duży padding boczny na mobile.
- **Zmiany:** Już w pkt 5: na ≤768px `padding-left/right: 1rem` zamiast 1.5rem.

### 8. Related / recommended products na mobile
- **Przyczyna:** 3 karty w rzędzie lub zbyt wąskie karty, łamanie przycisków.
- **Zmiany:** W `21-responsive-mobile.css` do ≤400px: 1 kolumna dla `.related`, `.upsells`, `.cross-sells`. W `06-single-product.css` breakpoint 400px dla PDP related/upsells → 1 kolumna. Od 401px do 768px pozostają 2 kolumny.

### 9. Stock info przy CTA i w sticky CTA
- **Przyczyna:** „✓ 158 w magazynie” nie było traktowane jako jeden blok z CTA; brak stock w sticky.
- **Zmiany:** W mu-plugin `mnsk7_single_product_availability()` output opakowany w `<div class="mnsk7-product-availability-row">` (żeby order 19 w summary działał). W `06-single-product.css`: `margin-bottom: 0.5rem` dla availability-row, `margin-top: 0` dla `form.cart`. W `content-single-product.php`: sticky CTA rozszerzony o `.mnsk7-pdp-sticky-cta__left` z ceną i `.mnsk7-pdp-sticky-cta__stock` (tekst z `$product->get_availability()`). Style dla `__stock` (fs-xs, font-weight 600, color accent).

---

## 2. Zmienione pliki

| Plik | Opis |
|------|------|
| `wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css` | Header: cart block, action buttons, dropdown summary hide, megamenu mobile hierarchy, search focus |
| `wp-content/themes/mnsk7-storefront/assets/css/parts/25-global-layout.css` | Page-top i padding na mobile, tło content |
| `wp-content/themes/mnsk7-storefront/assets/css/parts/21-responsive-mobile.css` | Related/upsells/cross-sells: 1 kolumna ≤400px |
| `wp-content/themes/mnsk7-storefront/assets/css/parts/06-single-product.css` | Availability-row/CTA spacing, sticky CTA stock, related 400px |
| `wp-content/themes/mnsk7-storefront/woocommerce/content-single-product.php` | Sticky CTA: lewa kolumna cena + stock |
| `mu-plugins/inc/woo-ux.php` | Search normalization `pre_get_posts` (tracked, deploy z repo) |
| `wp-content/themes/mnsk7-storefront/functions.php` | Search normalization `pre_get_posts` (fallback przy deploy samej theme) |
| `wp-content/themes/mnsk7-storefront/assets/css/main.css` | Zbudowany ponownie (`scripts/build-main-css.sh`) |
| Availability row | Już w `mu-plugins/inc/product-card.php` (mnsk7_single_product_availability) — bez zmian |

---

## 3. Viewporty do weryfikacji

- 319×1100  
- 360×800  
- 375×812  
- 390×844  
- 414×896  
- 549×1100  
- 768×1024  
- desktop 1280+

Sprawdzić: header (bez obcięcia), mobile „Sklep”, search `fi 4mm` vs `fi 4 mm`, gap pod headerem, brak pasków po bokach, szew search input/button i focus, blok related products, stock przy CTA i w sticky CTA.

---

## 4. Po wdrożeniu

1. Wyczyścić cache (WP Rocket / CDN).
2. Przetestować wyszukiwanie: `fi 4mm` i `fi 4 mm` — ten sam zestaw wyników.
3. Zrobić zrzuty ekranu na 319 i 549 (header, menu Sklep, search, PDP sticky CTA) i dołączyć do akceptacji.

---

## 5. Commit i push

**Commit:** `334ce67` — fix(mobile): header, megamenu Sklep, search UX, gap, stripes, related cols, stock CTA.

Push: `git push` (po zatwierdzeniu).

**Wszystkie zmiany w repo:** normalizacja wyszukiwania w `functions.php` (theme) i w `mu-plugins/inc/woo-ux.php`; availability row już w `mu-plugins/inc/product-card.php`. Deploy: push na main → GitHub Actions rsync mu-plugins + theme na staging.
