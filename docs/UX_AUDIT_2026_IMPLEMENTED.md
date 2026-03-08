# Wdrożone rekomendacje UX‑audytu (marzec 2026)

Tabela w kategoriach **zostaje** — tylko ulepszenia (chipsy, wybrane filtry, reset).

## 1. Header
- Sticky header i promo‑bar już były (mnsk7-header--sticky, filtr `mnsk7_header_promo_text`).
- **Dodane:** Domyślny tekst promocyjny „Darmowa dostawa od 300 zł. Tylko Polska.” gdy filtr nie zwraca nic (functions.php).

## 2. Footer
- Ciemne tło #1e293b (już wdrożone wcześniej).
- **Dodane:** Rozmiar czcionki kolumn 1rem zamiast var(--fs-sm) — czytelność (09-footer.css).

## 3. Główna
- Hero z CTA „Przejdź do sklepu” był.
- **Dodane:** Pierwszy USP zmieniony na „Darmowa dostawa od 300 zł”; przywitanie „Witaj, [imię]!” dla zalogowanych (front-page.php). Style .mnsk7-hero__welcome (08-home-sections.css).

## 4. Korzyń
- **Dodane:** Przycisk „Kontynuuj zakupy” nad tabelą koszyka (hook woocommerce_before_cart, functions.php). Style .mnsk7-cart-continue, min-height 44px (18-cart-checkout.css).

## 5. Kategorie/archiwum (tabela zostaje)
- **Dodane:** Blok „Wybrane: [chip] [chip] ×” + link „Wyczyść wszystkie” gdy są aktywne filtry GET (archive-product.php).
- Chipsy: min-height/min-width 44px (touch), na mobile poziomy scroll (flex-wrap: nowrap, overflow-x: auto) — 24-plp-table.css.
- Style .mnsk7-plp-selected, .mnsk7-plp-reset.

## 6. PDP
- Bez zmian w tym batchu (kluczowe parametry i trust strip już są w motywie).

## 7. Mobilne
- Chipsy z min 44px i przewijaniem poziomym (jak wyżej).
