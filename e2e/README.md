# E2E tests (Playwright)

Krytyczne scenariusze po zmianach mobile UX: layout PLP (tabela vs karty), akordeon w stopce, **parity stron z ?filter_***.

## Wymagania

- Node.js 18+
- `npm install` w katalogu głównym repozytorium

## Uruchomienie

```bash
# Domyślnie: https://staging.mnsk7-tools.pl
npm run test:e2e

# Własny URL
BASE_URL=https://staging.mnsk7-tools.pl npm run test:e2e

# Kategoria do testów filter parity (domyślnie frezy-cnc)
PLP_CATEGORY_SLUG=moja-kategoria npm run test:e2e

# Z UI
npm run test:e2e:ui
```

## Testy

- **plp-layout.spec.js** — na desktop UA w DOM jest tabela (`.mnsk7-product-table-wrap`), brak siatki mobilnej; na mobile UA — siatka (`.mnsk7-plp-grid-mobile`), brak tabeli.
- **plp-filter-url-parity.spec.js** — ta sama strona archiwum z `?filter_*` i bez: ten sam header, te same body classes (tax-product_cat, post-type-archive-product), ten sam layout (desktop = tabela, mobile = siatka); brak „wycieku” tabeli na mobile przy URL z filtrem.
- **footer-accordion.spec.js** — viewport mobile; klik w tytuł sekcji stopki otwiera sekcję (`is-open`, `aria-expanded="true"`).

Browsery: Chromium, Mobile Chrome (Pixel 5). Konfiguracja: `playwright.config.js`.

## Macierz testów: URL z filtrem vs bez (filter_* parity)

| Strona              | Bez filtra     | Z ?filter_*        | Oczekiwanie                          |
|---------------------|----------------|--------------------|--------------------------------------|
| Sklep (/sklep/)     | header + layout| header + layout    | ten sam masthead, ten sam wrapper    |
| Kategoria           | body_class + UA| body_class + UA    | tax-product_cat, layout po UA        |
| Tag                 | jak kategoria  | jak kategoria      | tax-product_tag, layout po UA        |

Warunek: `?filter_...` nie może przełączać na inny szablon ani zmieniać body_class w sposób usuwający klasy krytyczne (post-type-archive-product, tax-product_cat, tax-product_tag). Temat normalizuje klasy w `body_class` (priority 999) i używa `mnsk7_is_plp_archive()` w headerze.
