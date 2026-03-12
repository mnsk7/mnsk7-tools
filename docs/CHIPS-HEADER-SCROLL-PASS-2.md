# Chips / Header / Scroll — pass 2 (mobile composition, swipe affordance, results zone landing)

## 1. Zmienione pliki

| Plik | Zmiany |
|------|--------|
| `header.php` | Opakowanie nav + actions w `.mnsk7-header__controls` (jedna strefa kontroli). |
| `assets/css/parts/04-header.css` | Mobile: zmienna `--header-mobile-gap`, `.mnsk7-header__controls` (flex, ten sam gap), desktop: controls flex:1. Jednolity spacing burger/search/account/cart. |
| `assets/css/parts/24-plp-table.css` | Chips: mniejszy padding/gap, `margin-inline: -0.5rem`, fade po prawej (`mask-image`). Anchor `.mnsk7-plp-results-anchor` + `scroll-margin-top`. |
| `assets/css/parts/08-home-sections.css` | Catalog chips scroll: ten sam wzorzec (compact + fade). |
| `woocommerce/archive-product.php` | Anchor `#mnsk7-plp-results` przeniesiony na początek strefy wyników (przed chips). Usunięty id z grid/table. |
| `assets/css/main.css` | Zbudowany z parts. |

---

## 2. Scroll target po zastosowaniu filtra

- **Selector / kontener:** `#mnsk7-plp-results` (klasa `.mnsk7-plp-results-anchor`).
- **Lokalizacja w DOM:** na początku `.mnsk7-plp-content`, zaraz po ewentualnym bloku „Wyczyść wyszukiwanie”, **przed** pierwszym rzędem chipów.
- **Zachowanie:** `scrollIntoView({ behavior: 'smooth', block: 'start' })` + `scroll-margin-top: var(--header-h, 52px)` — użytkownik ląduje tak, że w viewport widać:
  - aktywne chipy filtrów,
  - wyszukiwarkę w kategorii,
  - krótkie USP (trust),
  - początek listy produktów poniżej.
- **Cel:** „results context”, a nie skok od razu do pierwszej karty produktu.

---

## 3. Header spacing (mobile)

- **Jedna zmienna:** `--header-mobile-gap` (0.5rem → 0.35rem/0.25rem/0.2rem na węższych viewportach).
- **Strefy:** (1) `.mnsk7-header__brand` — logo; (2) `.mnsk7-header__controls` — burger, search, account, cart w jednym rzędzie z **jednakowym** odstępem.
- **`.mnsk7-header__controls`:** `display: flex`, `gap: var(--header-mobile-gap)`, `margin-left: auto` (mobile). Burger i wszystkie elementy w `__actions` (search, account, cart) mają ten sam gap.
- **Wysokość:** zachowana jednolita min 44px (40px/36px na wąskich) dla wszystkich kontroli.

---

## 4. Chips swipe affordance

- **Mniej pustego miejsca:** `padding-inline: 0.5rem 1.5rem`, `margin-inline: -0.5rem 0` na `.mnsk7-plp-chips__scroll` i `.mnsk7-catalog-chips__scroll` (mobile).
- **Fade po prawej:** `mask-image: linear-gradient(to right, black calc(100% - 1.5rem), transparent 100%)` — widać, że lista ciągnie się w prawo.
- **Mniejszy gap:** 0.35rem między chipami (zamiast 0.4/0.5).
- **Efekt:** rząd chipów wygląda jak świadoma „swipe row”, a nie przypadkowy overflow.

---

## 5. Weryfikacja (screenshots / proof)

Do zrobienia po wdrożeniu:

1. **Mobile header** — viewport 375px: logo | burger | search | account | cart w jednym rytmie, bez „przyklejonych” elementów.
2. **Chips row** — ten sam viewport: rząd chipów z fade po prawej, mniej pustego miejsca po bokach.
3. **Landing po filtrze** — wejście na URL z `?filter_*` lub klik w filtr: strona ładuje się z pozycją scroll tak, że widać chipy + search + USP + początek listy (nie od razu pierwszą kartę).

URL do testu: np. strona kategorii + `?filter_srednica=8`, lub wybór filtra na PLP.
