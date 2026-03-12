# UI Audit — Defect Matrix (Targeted)
**Дата:** 2026-03-11  
**Направления:** Interaction, Component consistency, Alignment/layout, Responsive

---

## A. Interaction audit

| # | URL | Viewport | Component | Defect type | Actual | Expected | Root cause | File/selector | Priority |
|---|-----|----------|-----------|-------------|--------|----------|------------|---------------|----------|
| A1 | all | ≤768px | Footer accordion | JS/state | Accordion не разворачивается по тапу на заголовок | Клик по «Klient»/«Kategorie»/«Kontakt»/«Newsletter» раскрывает блок | Breakpoint в JS 1024px, в CSS 768px; возможен порядок выполнения скрипта или конфликт touchend/click | footer.php inline script, 09-footer.css @media (max-width:768px) | P0 |
| A2 | all | <1025px | Header search | — | OK (toggle + panel) | — | — | functions.php wp_footer script | — |
| A3 | all | <1025px | Burger menu | — | OK (nav.is-open) | — | — | functions.php wp_footer script | — |
| A4 | all | all | Cart dropdown | — | OK (hover/click) | — | — | functions.php | — |
| A5 | PLP | all | Chips «Więcej» / filters | — | OK (aria-expanded, target.hidden) | — | — | functions.php 1e bis | — |

---

## B. Component consistency audit

| # | URL | Viewport | Component | Defect type | Actual | Expected | Root cause | File/selector | Priority |
|---|-----|----------|-----------|-------------|--------|----------|------------|---------------|----------|
| B1 | various | all | WooCommerce buttons | CSS override | Квадратные или r-sm кнопки (Dodaj do koszyka, Kontynuuj, itd.) | Единый border-radius var(--r-md) | WooCommerce/Storefront или main.css дают border-radius: var(--r-sm) / 0 после 17-buttons | main.css ~1790; WC/Storefront | P0 |
| B2 | PLP table | desktop | .mnsk7-table-addcart-btn | — | Уже в 24-plp-table.css var(--r-md) | — | — | 24-plp-table.css | — |
| B3 | footer | all | .mnsk7-footer__newsletter-btn | — | var(--r-md) в 09-footer.css | — | — | 09-footer.css | — |

---

## C. Alignment/layout audit

| # | URL | Viewport | Component | Defect type | Actual | Expected | Root cause | File/selector | Priority |
|---|-----|----------|-----------|-------------|--------|----------|------------|---------------|----------|
| C1 | /sklep/, category, tag | desktop | Table row: qty + action | CSS conflict | Кнопка «Dodaj do koszyka» чуть выше по вертикали | Кнопка и поле qty в одной линии по центру ячейки | .mnsk7-table-addcart-form переопределён на display:inline-block (второе правило в 24-plp-table.css), из-за чего baseline alignment | 24-plp-table.css .mnsk7-table-addcart-form (дубль display: inline-block) | P0 |
| C2 | PLP | all | .mnsk7-table-cell--qty, .mnsk7-table-cell--action | vertical-align | vertical-align: middle задан | — | — | 24-plp-table.css | — |

---

## D. Responsive audit

| # | URL | Viewport | Component | Defect type | Actual | Expected | Root cause | File/selector | Priority |
|---|-----|----------|-----------|-------------|--------|----------|------------|---------------|----------|
| D1 | all | 320–430 | Footer accordion | Interaction | См. A1 | Раскрытие по тапу | См. A1 | — | P0 |
| D2 | 768 | all | Footer | CSS/JS sync | Accordion JS при 1024, layout при 768 | Один breakpoint 768 для и поведения, и раскладки | footer.php breakpointPx = MNSK7_BREAKPOINT_MOBILE (1024) | footer.php | P1 |

---

## Summary

- **P0:** A1 (footer accordion), B1 (button radius), C1 (table button alignment).
- **P1:** D2 (footer breakpoint sync).
- **Исправления (2026-03-11):**
  - **A1:** footer.php — breakpoint 768px (sync z CSS), init w DOMContentLoaded.
  - **B1:** 25-global-layout.css — global override `border-radius: var(--r-md) !important` dla .woocommerce .button, .button, input[type=submit], .single_add_to_cart_button, .add_to_cart_button.
  - **C1:** 24-plp-table.css — usunięto duplikat `.mnsk7-table-addcart-form { display: inline-block }` (zostaje inline-flex + align-items: center).

---

## Разбор оставшихся пунктов (status w kodzie)

### P0 — wszystkie zamknięte w kodzie

| # | Defekt | Oczekiwane | Stan w kodzie | Plik / fragment |
|---|--------|------------|----------------|------------------|
| **A1** | Footer accordion nie otwiera się na tap | Accordion działa przy ≤768px | **Fix:** JS używa `FOOTER_ACCORDION_BREAKPOINT = 768`, `matchMedia(mq)` przed toggle; `click` + `touchend` z deduplikacją; init w DOM. | `footer.php` ok. 184–222: `var FOOTER_ACCORDION_BREAKPOINT = 768`, `handleAccordion`, `toggleAccordion` |
| **D1** | Jak A1 (320–430px) | Jak A1 | Ten sam fix co A1 — jeden breakpoint 768. | — |
| **B1** | Różne border-radius przycisków Woo | Jednolity `var(--r-md)` | **Fix:** Globalny override w 25-global-layout.css dla .woocommerce .button, .button, input[type=submit], .single_add_to_cart_button, .add_to_cart_button. | `25-global-layout.css` ok. 95–110: blok „UI Audit: jeden border-radius” |
| **C1** | Przycisk „Dodaj do koszyka” wyżej niż pole qty w tabeli PLP | Jedna linia, wyśrodkowanie | **Fix:** Brak `display: inline-block` na formularzu; tylko `inline-flex` + `align-items: center`. | `24-plp-table.css` ok. 1085–1089: `.mnsk7-table-addcart-form`; komentarz ok. 1097 „Alignment: keep inline-flex” |

### P1 — zamknięte

| # | Defekt | Oczekiwane | Stan w kodzie | Plik / fragment |
|---|--------|------------|----------------|------------------|
| **D2** | JS accordion przy 1024px, CSS layout przy 768px | Jeden breakpoint 768 dla zachowania i layoutu | **Fix:** W footer.php używany jest `768`, nie 1024. CSS w 09-footer.css: `@media (max-width: 768px)` — zgodność. | `footer.php` ok. 185: `FOOTER_ACCORDION_BREAKPOINT = 768`; `09-footer.css` ok. 308: `@media (max-width: 768px)` |

### Weryfikacja ręczna (opcjonalnie)

- **A1/D1:** Na urządzeniu lub DevTools (≤768px) — klik/tap w „Klient”, „Kategorie”, „Kontakt”, „Newsletter” w stopce: sekcja powinna się rozwinąć/zwinąć.
- **B1:** Strony z przyciskami Woo (sklep, koszyk, checkout, konto): wszystkie przyciski z zaokrągleniem `var(--r-md)`.
- **C1:** PLP w widoku tabeli (desktop): w wierszu produktu pole qty i przycisk „Dodaj do koszyka” w jednej linii, wyrównane pionowo.

### Podsumowanie

- **P0 (A1, B1, C1) i P1 (D2):** zaimplementowane w repozytorium; brak otwartych punktów z matrycy.
- **A2, A3, A4, A5, B2, B3, C2:** od początku OK lub już spełnione (bez defektu).
