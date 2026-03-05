# Czyszczenie pluginów — co wyłączyć i w jakiej kolejności

Po wdrożeniu kodu (footer w temacie, cookie bar w mu-plugin, główna w front-page.php) można wyłączyć zbędne pluginy. **Zrób backup i test na stagingu przed prod.**

---

## 1. Cookie / RODO

- **Wyłączyć:** plugin do cookies (np. Cookie Notice, Cookiebot, RGPD itp.), jeśli jest.
- **Dlaczego:** w mu-pluginie jest minimalny pasek cookie (`mnsk7_cookie_ok`); po kliknięciu „Przyjmuję” znika. Można go rozbudować (link do polityki, ustawienia) — na razie zastępuje zewnętrzny plugin.

---

## 2. Filtry (zostaw jeden)

- **Wyłączyć:** 3 z 4: `filter-everything`, `woo-product-filter`, `woocommerce-products-filter`, `woof-by-category`. Zostaw **jeden** (np. ten, którego URL-e są w Search Console — przed wyłączeniem sprawdź indeks i przekierowania).
- **Dlaczego:** 3+ filtry = konflikt, duplikaty URL, słaba wydajność. Jeden filtr + kolejność atrybutów z `mnsk7_get_filter_attribute_order()`.

---

## 3. Wishlist (zostaw jeden)

- **Wyłączyć:** jeden z: `flexible-wishlist`, `woo-smart-wishlist`.
- **Dlaczego:** dwa pluginy do listy życzeń — zbędny duplikat.

---

## 4. Page buildery (zostaw jeden lub zero)

- **Wyłączyć:** `elementor` **lub** `beaver-builder-lite-version` — zostaw maks. jeden, jeśli w ogóle potrzebny. Jeśli cała treść w szablonach/ shortcode’ach — można oba wyłączyć.
- **Dlaczego:** dwa buildery = konflikt i ciężar.

---

## 5. Profil / rejestracja (zostaw jeden)

- **Wyłączyć:** `ultimate-member` **lub** `profile-builder` — zostaw jeden.
- **Dlaczego:** dwa pluginy do profili — duplikat.

---

## 6. GTM (zostaw jeden)

- **Wyłączyć:** `duracelltomi-google-tag-manager` **lub** `gtm-ecommerce-woo` — zostaw jeden.
- **Dlaczego:** dwa GTM = podwójne tagi, ryzyko błędów w danych.

---

## 7. Schema (zostaw Yoast)

- **Wyłączyć:** `schema-and-structured-data-for-wp` (Schema & Structured Data).
- **Dlaczego:** Yoast SEO już daje schema; drugi plugin = duplikaty JSON-LD.

---

## 8. Facebook (zostaw jeden)

- **Wyłączyć:** `official-facebook-pixel` **lub** zostaw tylko `facebook-for-woocommerce` (jeśli integruje pixel). Jeden pixel, jedna konfiguracja.
- **Dlaczego:** dwa źródła piksela = duplikaty zdarzeń.

---

## 9. Cache (zostaw jeden)

- **Zostaw:** LiteSpeed **albo** WP Rocket. **Wyłączyć:** Seraphinite, drugi cache.
- **Ustaw:** wykluczenia cache dla cart, checkout, my-account.
- **Dlaczego:** wiele warstw cache = błędy na koszyku/checkout.

---

## Kolejność (rekomendowana)

1. Cookie plugin → wyłączyć (nasz bar w mu-plugin).
2. Schema plugin → wyłączyć (Yoast).
3. Jeden GTM, jeden Facebook, jeden wishlist, jeden builder, jeden profil → wyłączyć duplikaty.
4. Filtry → po analizie Search Console wyłączyć 3 filtry, zostawić 1.
5. Cache → zostawić jeden, wyłączyć resztę i ustawić wykluczenia.

Po każdym kroku: sprawdzić stronę główną, katalog, kartę produktu, koszyk, checkout (smoke z [QA_REPORT.md](QA_REPORT.md)).

---

## Powiązane

- [AS_IS_AUDIT.md](AS_IS_AUDIT.md) — pełna lista pluginów i konfliktów.
- [AS_IS_BACKLOG.md](AS_IS_BACKLOG.md) — P0/P1/P2.
