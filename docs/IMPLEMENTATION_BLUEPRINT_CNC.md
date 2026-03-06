# IMPLEMENTATION BLUEPRINT — CNC Catalog (10k SKUs ready)

**Version:** 1.0  
**Objective:** move from "theme website" to "tool catalog" with minimal plugins and deterministic UX.

**Must read with:** [DESIGN_CONTRACT_CNC.md](DESIGN_CONTRACT_CNC.md)  
**Staging vs prod (droga użytkownika, pliki):** [USER_JOURNEY_STAGING_VS_PROD.md](USER_JOURNEY_STAGING_VS_PROD.md)  
**Reference (tabela na kategorii, PDP):** [REFERENCE_SANDVIK_COROMANT.md](REFERENCE_SANDVIK_COROMANT.md) — Sandvik Coromant: kategoria z tabelą produktów, PDP z jedną tabelą „Dane produktu”.

---

## Current state (staging) — co już jest w repozytorium

Na stagingu już wdrożone (zgodnie z opisem drogi użytkownika):

- **Główna:** hero, CTA «Przejdź do sklepu», kategorie, trust block — `tech-storefront/front-page.php`.
- **PDP:** tabela key params, trust badges (Dostawa jutro, Faktura VAT, 300 zł, Zwroty 30 dni), «X osób kupiło» — `mu-plugins/inc/product-card.php` (hooki Woo summary).
- **Koszyk/checkout:** komunikat „do darmowej dostawy brakuje X zł” — `mu-plugins/inc/delivery.php`, `MNK7_FREE_SHIPPING_MIN` w `constants.php`.
- **Checkout:** trust line, firma/NIP opcjonalne — `mu-plugins/inc/checkout.php`.

**Brakuje:** filtry (średnica/material/typ) bez pluginów, key spec line na kartach PLP/related, pełna SEO-struktura katalogu. Przed dalszymi krokami: **potwierdzić rozdzielenie DB staging/prod** (Phase A).

---

## Phase A — Safety & staging correctness (blocker)

**A1. Confirm staging DB is separate**

- `staging` wp-config.php has `DB_NAME` ≠ production `DB_NAME`
- `wp_options` (template/stylesheet) changes on staging do not affect prod

If not separate → **STOP**. Create separate DB, clone prod into staging DB, update wp-config.

**A2. Freeze prod**

- No plugin/theme changes on prod until staging plan validated.

---

## Phase B — Create "Catalog Core" as code (no plugins)

**B1. Create MU-plugin:** `/wp-content/mu-plugins/mnsk7-catalog-core.php`

Responsibilities:

- Register filter query parsing (URL params)
- Add filter UI helper / shortcodes (optional)
- Safe sanitization of URL params
- "Key spec line" helpers for product cards
- "Key specs table" renderer for product page

**B2. URL params spec (v1)**

- `diameter` (slug or numeric mapping)
- `shank`
- `type`
- `material`

Rules: `sanitize_text_field` on each; allowlist terms via `term_exists` in taxonomy.

**B3. Filter query integration**

- Hook: `pre_get_posts`
- Only on main query + product archives (`is_shop` / `is_product_taxonomy` / `is_product_category`)
- Build `tax_query` for `pa_diameter`, `pa_shank`, `pa_type`, `pa_material`
- Relation: AND
- If params empty → do nothing

**B4. Filter UI (v1 non-AJAX)**

- Output filter chips (links) that rewrite URL with chosen param
- Selected state: active style when param equals term
- "Clear filters" link to archive base URL
- Filter section compact, above grid

---

## Phase C — Child theme UI overrides

**C1. Storefront child theme only**

Files:

- `wp-content/themes/<child>/style.css`
- `wp-content/themes/<child>/assets/css/catalog.css`
- `wp-content/themes/<child>/assets/css/product.css`
- `wp-content/themes/<child>/assets/css/filters.css`
- `wp-content/themes/<child>/assets/css/buttons.css`

**C2. Product card layout**

- Override Woo templates only if needed: `content-product.php` in child `woocommerce/`
- Strict order: Image → Title → Spec line → Price → CTA
- Add "spec line" via helper from MU-plugin

**C3. Product page above fold**

- Hook into single product summary to insert:
  - Key specs table under price
  - Trust chips under CTA
- Keep variants selector above CTA
- Social proof ("N osób kupiło") directly under price

---

## Phase D — Data standardization (BaseLinker-safe)

**D1.** Do NOT rename SKUs or change product IDs.

**D2.** Standardize attributes: `pa_diameter`, `pa_shank`, `pa_type`, `pa_material`, `pa_flutes`, `pa_coating`.

**D3.** If current products have messy attributes: create mapping sheet later; v1 support existing where possible.

---

## Phase E — Plugin reduction plan (hard rule)

- **E1.** Remove all filter plugins (target: 0).
- **E2.** Remove duplicate UI plugins (shortcodes, widgets).
- **E3.** Keep only one cache plugin.
- **E4.** Keep only one SEO plugin.
- **E5.** Final target: ≤ 15 plugins total.

---

## Phase F — QA / Regression checks (mandatory)

- Category page: filters apply / persist / clear
- Product page: above-fold contains title, price, specs, variants, CTA, trust
- Related products cards follow contract (image, title, key spec line, price, button)
- Mobile 360px: no overflow, CTA ≥ 44px
- No prod DB/theme changes

---

## Phase G (reference) — Category table view (Sandvik-style)

**Reference:** [Sandvik Coromant — turning-tools](https://www.sandvik.coromant.com/pl-pl/tools/turning-tools): kategoria z **tabelą produktów** (kolumny: obraz, nazwa/kod, parametry, cena, akcja) zamiast tylko siatki kart. Docelowo dla mnsk7: opcjonalny **widok tabela** na stronie kategorii (obok lub zamiast grid), filtry nad tabelą, sortowanie kolumn. Szczegóły w [REFERENCE_SANDVIK_COROMANT.md](REFERENCE_SANDVIK_COROMANT.md). V1: grid + filtry; widok tabela w kolejnej fazie po stabilizacji filtrów.

---

## V1 Delivery — Task list

| # | Task | Owner |
|---|------|--------|
| 1 | MU-plugin **mnsk7-catalog-core**: URL-param filters on product archives (diameter, shank, type, material), sanitized + allowlisted, `pre_get_posts` on main query, "Clear filters" helper | 04_woo_engineer |
| 2 | Filter UI block above product grid on category pages: chips/links for diameter + shank (v1), active state from URL params | 05_theme_ux_frontend |
| 3 | Product cards (archive/related): strict order Image → Title → Key spec line → Price → Full-width CTA; key spec line from MU-plugin | 05 + 04 |
| 4 | Product page: key specs table (2–6 rows) under price; trust chips under CTA; social proof under price | 04 + 05 |
| 5 | Split CSS: catalog.css, product.css, filters.css, buttons.css; wire in child theme | 05_theme_ux_frontend |
| 6 | Verification checklist: mobile 360px, filters, product card order, no parent edits, no new plugins | 08_qa_security |

---

## Allegro seller ratings (separate MU-plugin)

**Task:** Add Allegro seller ratings badge to product pages (and optionally product cards), **without plugins**.

**Constraints:**

- No new plugin. Use MU-plugin (`wp-content/mu-plugins/`) + caching.
- Never call Allegro API on frontend (only cron/background + cache).

**Implementation:**

1. Create **mnsk7-allegro-ratings.php** (MU-plugin).
2. WP-Cron daily job: fetch Allegro `GET /sale/user-ratings`, aggregate (total, positive %, last updated), cache in options/transient 24h.
3. Shortcode `[mnsk7_allegro_badge]`: "★★★★★ {positive_percent}% pozytywnych", "{ratings_count} ocen", link "Zobacz opinie na Allegro".
4. Hook shortcode under Add to cart on single product page.
5. Minimal CSS for badge.

**Deliverables:** files created/modified; where to put Allegro API credentials (wp-config / env); verification checklist.

---

## CRITICAL RULES (for agents)

1. Do NOT change production. Do NOT touch prod DB. Prod must remain unchanged.
2. Do NOT add filter plugins. Implement filters via code (MU-plugin + WP_Query).
3. Do NOT edit parent theme. Only Storefront child theme and MU-plugin.
4. Deterministic output: only what is in the contract; no "nice to have" fluff.
