# Performance Pass 2 — TBT i archive LCP

**Status: nie przyjęty.** Wyniki po wdrożeniu: regresja TBT na home (1668 → 2990 ms), regresja LCP na archive (2,68 → 4,4 s). Analiza przyczyn i plan naprawy: **→ [PERFORMANCE-PASS-2b.md](PERFORMANCE-PASS-2b.md)**.

**Cel (oryginalny):** obniżyć TBT na home i archive, dociągnąć archive LCP < 2,5 s, bez regresji UX.

**Bazowy stan (po Pass 1):**
- Home: Performance 58, FCP 1,79 s, LCP 3,13 s, **TBT 1668 ms**
- Archive: Performance 69, FCP 1,78 s, LCP 2,68 s, **TBT 1087 ms**
- CLS stabilny

---

## 1. Audit: główne źródła TBT

Źródło: Lighthouse (mobile simulation) — `lighthouse-*-after.json`, audyty long-tasks, bootup-time, mainthread-work-breakdown.

### Home

| Źródło | Bootup / long task | Działanie |
|--------|--------------------|-----------|
| **Strona (inline)** | ~2844 ms bootup; long tasks 646 ms, 308 ms, 168 ms | Jeden duży blok inline w `wp_footer` (menu, search, cart, promo, shrink, Instagram) — parsowanie + wykonanie blokuje main thread. |
| **Unattributable** | ~1223 ms | Często eval/inline; może obejmować fragmenty Woo/cart. |
| **jQuery** | ~513 ms | Boot cost; zależność Woo i pluginów. |
| **Woo order-attribution** | ~183 ms | Skrypt Woo — nie wyłączamy z poziomu tematu. |
| **Ultimate Member (um-account)** | ~169 ms | Plugin — conditional load po stronie pluginu. |
| **PWA / WP Rocket lazyload** | w zestawie | Pluginy. |

**Main thread breakdown (home):** Other 1862 ms, Script Evaluation 1286 ms, Style & Layout 1056 ms, Parse HTML 812 ms.

### Archive

| Źródło | Bootup / long task | Działanie |
|--------|--------------------|-----------|
| **Strona (inline)** | ~2171 ms bootup; long tasks 339 ms, 331 ms, 131 ms, 121 ms, 103 ms | Ten sam blok inline co na home. |
| **Unattributable** | ~1175 ms | Jak wyżej. |
| **jQuery** | ~403 ms | Jak wyżej. |
| **PWA / Woo order-attribution / UM** | w zestawie | Jak wyżej. |

**Main thread breakdown (archive):** Other 1879 ms, Script Evaluation 950 ms, Style & Layout 733 ms, Parse HTML 652 ms.

### Top 3–5 źródeł TBT (do adresowania w temacie)

1. **Inline script w footerze (document URL)** — jeden duży blok: menu toggle, search, cart dropdown, promo, header shrink, Instagram carousel. Wykonuje się synchronicznie przy parsowaniu.
2. **wc-cart-fragments** — po defer wykonuje się później; XHR + aktualizacja DOM daje długi task. Na cart/checkout nie jest potrzebny (korzyń = treść strony).
3. **jQuery / Woo / pluginy (UM, PWA, order-attribution)** — z poziomu tematu nie wyłączamy; ewentualnie conditional load w pluginach.

**Co zrobiono w temacie:** (1) główny blok inline uruchamiany w `requestIdleCallback` (timeout 1,5 s), żeby nie blokować pierwszego parse; (2) `wc-cart-fragments` nie jest ładowany na cart i checkout.

---

## 2. Wdrożone zmiany (po plikach)

| Plik | Zmiana | Cel |
|------|--------|-----|
| `functions.php` | **wc-cart-fragments:** enqueue tylko gdy `! is_cart() && ! is_checkout()`. | Mniej JS na cart/checkout; mniej TBT z fragmentów na tych stronach. Na home/archive/PDP fragment nadal jest (header z koszykiem). |
| `functions.php` | **Blok inline:** podział na `runCritical()` (menu toggle, search, cart, mega menu) — wywołane od razu w footerze; `runDeferred()` (promo bar, header shrink, Instagram carousel) — w `requestIdleCallback(timeout 2s)`. | Krytyczna nawigacja (menu/search/cart) reaguje od razu; mniej ryzyka opóźnionego pierwszego kliku. TBT nadal obniżany przez odłożenie promo/shrink/Instagram. |
| `woocommerce/content-product-table-row.php` | **Pierwszy wiersz tabeli:** `get_image( 'woocommerce_thumbnail', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) )`. Static `$mnsk7_plp_row_index` — tylko pierwsza miniatura. | LCP na archive (desktop table): pierwsza miniatura = LCP candidate; eager + high priority przyspieszają jej załadowanie. |

---

## 3. Co jest wyłączone / odłożone / warunkowe

- **Wyłączone na wybranych stronach:** `wc-cart-fragments` nie ładuje się na cart ani checkout.
- **Odłożone w czasie:** tylko `runDeferred()` (promo, shrink, Instagram) w `requestIdleCallback` (max 2 s). **Critical UI** (menu, search, cart) wykonuje się od razu przy parsowaniu footera.
- **Bez zmian:** cart/checkout/account/PDP/PLP‑specific inline (małe bloki) — nadal tylko na odpowiednich szablonach. Cookie bar + skrypt tylko przy braku zgody (z Pass 1).

---

## 4. Archive LCP — co zrobiono (hipoteza niepotwierdzona)

- **Założenie Pass 2:** LCP na archive = pierwsza miniatura w tabeli (desktop). W `content-product-table-row.php` dla pierwszego wiersza ustawiono `loading="eager"` i `fetchpriority="high"`.
- **Faktyczny LCP po pomiarze (Lighthouse):** w przebiegu zapisanym w `lighthouse-archive-after.json` LCP to **tekst paska promocyjnego** (`span.mnsk7-promo-bar__text`), nie pierwsza miniatura. Szczegóły i plan naprawy: **→ [PERFORMANCE-PASS-2b.md](PERFORMANCE-PASS-2b.md)**.
- **Uwaga:** na mobile archive używana jest siatka kart (`content-product`), nie tabela — LCP tam zależy od pierwszego produktu w pętli.

---

## 4b. Następne kroki i ograniczenia (Pass 2)

**1. wc-cart-fragments na home/archive**  
Obecnie fragmenty są **wyłączone tylko na cart i checkout**. Na home, archive i PDP nadal się ładują (żeby licznik i mini‑koszyk w headerze odświeżały się po add-to-cart).  
**Otwarte:** Czy na home/archive naprawdę trzeba odświeżać licznik od razu przez AJAX? Jeśli mini‑koszyk można aktualizować dopiero po przejściu na inną stronę lub przez prostszy mechanizm — **kolejny krok** to conditional load fragmentów tylko tam, gdzie jest formularz „Dodaj do koszyka” (np. archive, PDP), albo lazy init (np. po pierwszym hoverze na ikonie koszyka). To wymaga decyzji produktowej i testów (add-to-cart z archive → licznik).

**2. requestIdleCallback — rozbicie na critical vs deferred (zrobione)**  
W Pass 2 cały blok był w jednym `run()` w idle. **Zaktualizowano:** blok podzielony na `runCritical()` (menu toggle, search, cart, mega menu) — wywołane **od razu** w footerze — oraz `runDeferred()` (promo bar, header shrink, Instagram carousel) — w `requestIdleCallback(timeout 2s)`. Dzięki temu pierwszy klik w menu/search/cart nie czeka na idle.

**3. LCP na archive tylko desktop (tabela)**  
Eager + fetchpriority są ustawione tylko dla **pierwszego wiersza tabeli** (desktop). Na **mobile** archive jest siatka **kart** (`content-product`), nie tabela — LCP tam nie był w tym passie zmieniany.  
Jeśli mobile jest ważny: w kolejnym kroku trzeba zrobić to samo dla pierwszego produktu w pętli kart (np. filtr `woocommerce_product_get_image_attr` w zależności od pozycji w pętli).

---

## 5. Before/After Lighthouse

Przed Pass 2 ( = „after” z Pass 1):

- Home: Performance 58, FCP 1,79 s, LCP 3,13 s, TBT 1668 ms  
- Archive: Performance 69, FCP 1,78 s, LCP 2,68 s, TBT 1087 ms  

**Po wdrożeniu Pass 2** uruchomić na staging:

```bash
npx lighthouse https://staging.mnsk7-tools.pl --only-categories=performance --output=json --output-path=docs/lighthouse-home-pass2.json
npx lighthouse https://staging.mnsk7-tools.pl/sklep/ --only-categories=performance --output=json --output-path=docs/lighthouse-archive-pass2.json
```

Wyniki wpisać poniżej (i ewentualnie zaktualizować tabelę w §9 PERFORMANCE-AUDIT-AND-PLAN.md).

| Strona | Pomiar | Performance | FCP | LCP | TBT | CLS |
|--------|--------|-------------|-----|-----|-----|-----|
| Home | Pass 1 (after) | 58 | 1,79 s | 3,13 s | 1668 ms | 0,004 |
| Home | Pass 2 | 54 | 2,41 s | 3,06 s | 2994 ms | 0,004 |
| Archive | Pass 1 (after) | 69 | 1,78 s | 2,68 s | 1087 ms | 0,005 |
| Archive | Pass 2 | **80** | 2,05 s | 4,42 s | **21 ms** | 0,004 |

*Źródło Pass 2: `docs/lighthouse-home-after.json`, `docs/lighthouse-archive-after.json` (Lighthouse 13.x, mobile simulation).*

---

## 6. Kryteria akceptacji i uczciwy wniosek

**Kryteria:**
- Archive LCP < 2,5 s lub maksymalnie zbliżone (z jasnym bottleneckem, jeśli nie osiągnięte).
- TBT archive niższe od 1087 ms.
- TBT home wyraźnie niższe od 1668 ms.
- Brak regresji: add to cart, licznik/mini‑koszyk, mobile menu, filtry, wyszukiwanie, logowanie/linki do konta.
- Brak regresji wizualnej.

**Wniosek po pomiarze (Pass 2 nie przyjęty):**
- Co **się poprawiło:** TBT archive (1087 → 20 ms), Performance archive (69 → 80).
- Co **się pogorszyło:** TBT home (1668 → 2990 ms), LCP archive (2,68 → 4,4 s).
- **Główny bottleneck home:** synchroniczne wykonanie `runCritical()` w footerze (długie zadania, document URL). **Archive LCP:** realny element = promo bar, nie pierwsza miniatura.
- **Dalsze kroki:** [PERFORMANCE-PASS-2b.md](PERFORMANCE-PASS-2b.md) — analiza regresji i plan naprawy.

---

## 7. Weryfikacja UX po Pass 2

Sprawdzić ręcznie:

- [ ] Home: hero, bestsellery, menu (burger), wyszukiwarka, ikona koszyka, promo bar (zamknięcie).
- [ ] Archive: tabela, pierwszy wiersz (obrazek ładuje się od razu), filtry, „Pokaż więcej”, add to cart z tabeli.
- [ ] Po dodaniu do koszyka: licznik w headerze i ewent. dropdown aktualizują się (na home/archive/PDP — fragment jest ładowany).
- [ ] Cart: przycisk checkout, brak błędów w konsoli.
- [ ] Checkout: brak brakującego skryptu (fragment celowo nie ładujemy).
- [ ] Mobile: menu, wyszukiwarka, koszyk, nawigacja.
