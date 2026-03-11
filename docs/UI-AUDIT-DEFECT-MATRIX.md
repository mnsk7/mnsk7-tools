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
  - **B1:** functions.php wp_footer — global override `border-radius: var(--r-md) !important` dla .woocommerce .button, .button, input[type=submit], .single_add_to_cart_button, .add_to_cart_button.
  - **C1:** 24-plp-table.css — usunięto duplikat `.mnsk7-table-addcart-form { display: inline-block }` (zostaje inline-flex + align-items: center).
