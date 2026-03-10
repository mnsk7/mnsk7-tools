# E2E tests (Playwright)

Krytyczne scenariusze po zmianach mobile UX: layout PLP (tabela vs karty), akordeon w stopce.

## Wymagania

- Node.js 18+
- `npm install` w katalogu głównym repozytorium

## Uruchomienie

```bash
# Domyślnie: https://staging.mnsk7-tools.pl
npm run test:e2e

# Własny URL
BASE_URL=https://staging.mnsk7-tools.pl npm run test:e2e

# Z UI
npm run test:e2e:ui
```

## Testy

- **plp-layout.spec.js** — na desktop UA w DOM jest tabela (`.mnsk7-product-table-wrap`), brak siatki mobilnej; na mobile UA — siatka (`.mnsk7-plp-grid-mobile`), brak tabeli.
- **footer-accordion.spec.js** — viewport mobile; klik w tytuł sekcji stopki otwiera sekcję (`is-open`, `aria-expanded="true"`).

Browsery: Chromium, Mobile Chrome (Pixel 5). Konfiguracja: `playwright.config.js`.
