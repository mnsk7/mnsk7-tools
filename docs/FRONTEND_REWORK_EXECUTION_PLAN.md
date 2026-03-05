# FRONTEND Rework Execution Plan

Projekt: `mnsk7-tools.pl`  
Rola wykonawcza: `05_theme_ux_frontend` (zakres: theme only, bez zmian w WP core/plugins)  
Data: 2026-03-05  
Wejście: `docs/MARKETING_UX_REVIEW_2026-03-05.md`

## 1) Cele reworku (P0/P1)

- P0: usunąć duplikaty struktur/nawigacji i zredukować chaos wizualny.
- P0: poprawić kontrast i czytelność (tekst, cena, CTA) do poziomu WCAG AA na kluczowych widokach.
- P0: uprościć system wizualny (spójne kolory, typografia, odstępy, komponenty).
- P1: odchudzić home i PDP pod jeden dominujący flow zakupowy.

## 2) Fazy prac (P0 first)

### Faza 0 — Baseline i zabezpieczenie rollback (P0)

- Snapshot obecnego UI (desktop + mobile): home, PLP, PDP, cart, checkout.
- Zamrożenie zakresu: brak zmian funkcjonalnych backend/Woo, tylko warstwa prezentacji i template theme.
- Przygotowanie rollback: punkt przywrócenia na poziomie plików theme.

### Faza 1 — Usunięcie duplikatów i porządek IA/nawigacji (P0)

- Audyt i eliminacja zduplikowanych pozycji menu oraz powielonych sekcji pomocniczych.
- Ujednolicenie etykiet i slugów nawigacji (jedna wersja nazw, bez dubli ortograficznych).
- Weryfikacja, że każda sekcja kluczowa występuje tylko raz w DOM.

### Faza 2 — Kontrast/czytelność i hierarchia treści (P0)

- Poprawa kontrastu dla typografii, cen, linków, badge i elementów pomocniczych.
- Uproszczenie hierarchii wizualnej nagłówków, CTA i bloków trust.
- Redukcja nadmiaru akcentów kolorystycznych do jednego spójnego systemu.

### Faza 3 — Uproszczenie home + visual system refactor (P0/P1)

- Ograniczenie liczby sekcji above-the-fold i eliminacja powtórzeń komunikatów trust.
- Wydzielenie warstw stylów: global/components/pages, wygaszanie legacy reguł.
- Uporządkowanie footeru pod spójny layout i czytelność mobilną.

### Faza 4 — PLP/PDP, cart/checkout smoke i polish (P1)

- PLP/PDP: porządek informacji pod konwersję (bez ingerencji w logikę Woo core/plugins).
- Cart/checkout: regresja wizualna, czytelność formularzy i komunikatów.
- Końcowe poprawki mobilne i stany edge-case.

## 3) Plan po plikach (co zmieniamy)

### `wp-content/themes/tech-storefront/front-page.php`

- Przepisanie struktury sekcji home pod jednoznaczny flow: hero -> kategorie/produkty -> trust -> loyalty -> social.
- Usunięcie lub scalenie dublujących się bloków trust/review, które konkurują o uwagę nad foldem.
- Ograniczenie liczby hardcoded statystyk i ręcznych wstawek utrudniających utrzymanie.

### `wp-content/themes/tech-storefront/footer.php`

- Uporządkowanie stopki do jednej spójnej struktury informacyjnej (kontakt/dostawa/social bez duplikacji treści).
- Eliminacja nakładających się układów footer-top + dodatkowy custom block, jeśli powodują wizualne duplikaty.
- Normalizacja linków pomocniczych i kolejności sekcji.

### `wp-content/themes/tech-storefront/assets/css/mnsk7-product.css`

- Refactor monolitu CSS do warstw logicznych:
  - global tokens (kolor, typografia, spacing),
  - komponenty (karty, przyciski, badge, sekcje),
  - strony (home, PLP/PDP, pomocnicze).
- Usunięcie legacy i dublujących się definicji selektorów.
- Przepisanie problematycznych kolorów/tła pod kontrast AA.
- Ograniczenie liczby wariantów gradientów/akcentów, uproszczenie systemu wizualnego.

### `wp-content/themes/tech-storefront/woocommerce/archive-product.php`

- Zachowanie logiki Woo loop; tylko korekty struktury wrapperów/hook order, jeśli potrzebne dla czytelności PLP.
- Kontrola, by nie duplikować nagłówków/listing controls i nie łamać sidebar flow.

### `wp-content/themes/tech-storefront/woocommerce/content-single-product.php`

- Reorganizacja wizualna sekcji summary pod priorytet: tytuł/cena/stock/CTA/trust.
- Ograniczenie konkurujących bloków informacyjnych nad foldem.
- Uporządkowanie spacing i typografii metadanych/dodatkowych sekcji.

### `wp-content/themes/tech-storefront/woocommerce/single-product.php`

- Tylko minimalne korekty kontenerów układu, bez zmian logiki Woo i bez ingerencji w pluginy.
- Zapewnienie spójnego layoutu z PLP/home.

## 4) Co usuwamy vs co przepisujemy

### Do usunięcia/wygaszenia

- Dublujące wpisy menu/etykiety stron pomocniczych (jedna nazwa, jedna pozycja).
- Powielone sekcje trust/informacyjne, które nie budują nowej wartości.
- Legacy CSS i dublowane selektory w `mnsk7-product.css` (szczególnie reguły konkurujące o te same elementy).

### Do przepisania

- Sekcjonowanie home (`front-page.php`) pod krótszy, czytelniejszy scenariusz zakupowy.
- Struktura i wizualna hierarchia footeru (`footer.php`).
- Style komponentowe i page-level (`mnsk7-product.css`) pod jeden design language.
- Priorytety informacji na PDP (`content-single-product.php`) w warstwie frontowej.

## 5) Ryzyka i rollback

### Główne ryzyka

- Ryzyko regresji hooków Woo w PDP/PLP przy zmianie struktury wrapperów.
- Ryzyko niezamierzonego ukrycia treści przez usuwanie duplikatów w DOM.
- Ryzyko kolizji CSS specificity po refactorze monolitu.
- Ryzyko różnic mobilnych (nawigacja/footer/CTA hit-area).

### Mitigacje

- Zmiany etapami (P0 -> P1), z testem smoke po każdej fazie.
- Zasada "no backend behavior change": tylko layout/styling.
- Kontrola porównawcza screenshot przed/po dla kluczowych ścieżek.
- Dedykowany pass mobile-first po każdej większej zmianie CSS.

### Rollback

- Rollback plikowy: szybkie przywrócenie poprzednich wersji `front-page.php`, `footer.php`, `mnsk7-product.css`, Woo templates.
- Rollback etapowy: wycofanie tylko ostatniej fazy (bez cofania wcześniejszych stabilnych poprawek).
- Kryterium rollback: blokada checkout/add-to-cart, utrata czytelności krytycznych CTA/ceny, lub widoczne duplikaty po wdrożeniu.

## 6) Smoke checklist (must pass)

### Home

- Brak zduplikowanych sekcji/menu w DOM.
- Hero + główny CTA czytelne na desktop i mobile.
- Sekcje trust/social nie konkurują nad foldem.

### PLP (shop/category)

- Nagłówek/listing/filter/sort bez duplikatów i bez nakładania.
- Ceny, nazwy, CTA czytelne (kontrast AA).
- Karty produktowe spójne wizualnie i responsywne.

### PDP

- Nad foldem: cena + stock + CTA + trust są czytelne i priorytetowe.
- Brak przeładowania informacją i brak duplikatów bloków.
- Gallery/summary/meta zachowują poprawny układ mobile.

### Cart

- Tabela produktów, sumy i CTA checkout czytelne.
- Komunikaty rabat/dostawa mają poprawny kontrast.

### Checkout

- Formularze, labelki, stany błędu i CTA są czytelne na mobile.
- Brak regresji wizualnej podsumowania zamówienia.

### Mobile cross-check (global)

- 360px/390px/430px: brak overflow poziomego.
- Tap targets min. 44px dla kluczowych CTA.
- Header/footer/menus bez dubli i bez kolizji spacingu.

## 7) Definition of Done (frontend rework)

- P0 zamknięte: duplikaty usunięte, kontrast/czytelność poprawione, visual system uproszczony i spójny.
- Smoke checklist zaliczony na home/PLP/PDP/cart/checkout + mobile.
- Brak zmian w WP core/plugins i brak regresji krytycznych ścieżek zakupowych.
