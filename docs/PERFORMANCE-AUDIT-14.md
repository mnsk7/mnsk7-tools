# Performance Audit — 14 punktów (home / archive)

Przegląd tematów mnsk7-storefront pod kątem: zbędne zasoby, inline JS, cart-fragments, LCP, obrazy, DOM, CSS, fonty, jQuery, reflow, warunkowy init, TTFB, zmiany Pass 2/2b, plan pomiarów.

---

## 1. Co ładuje się na home i archive — zbędne zasoby

### Enqueue globalne (functions.php)

| Zasób | Gdzie | Warunek |
|-------|--------|---------|
| `storefront-style` | parent | zawsze (jeśli parent dostępny) |
| `mnsk7-storefront-style` (style.css) | child | zawsze |
| `mnsk7-main` (main.css) | child | **zawsze** — jeden plik na wszystkie strony |
| `mnsk7-footer-accordion.js` | child | **zawsze** — w footerze |
| Inline CSS Instagram | `mnsk7-main` | tylko `is_front_page()` lub shortcode w treści |
| `woocommerce-layout` override (inline) | Woo | zawsze (clearfix + account styles) |
| **wc-cart-fragments** | Woo | **nie** na `is_cart()` / `is_checkout()`; **tak** na home, archive, PDP (defer) |

**Wnioski:**
- Na **home** i **archive** ładują się: pełny main.css (wszystkie parts: header, PLP table, cart-checkout, cookie-bar, breadcrumbs, single-product, FAQ, delivery, print…), footer-accordion.js, wc-cart-fragments (defer).
- **Cart/checkout/account** — skrypty Woo ładuje Woo; tema nie enqueue’uje osobno skryptów checkout/cart/account. Woo sam ładuje m.in. checkout.js na checkout, cart-fragments tema wyłącza na cart/checkout.
- **Filtry PLP** — nie ma osobnego „skryptu filtrów”; logika chips/load more jest w inline w `wp_footer`, warunkowana `is_shop() || is_product_category() || is_product_tag()` — czyli na archive się ładuje, na home nie (tam nie ma PLP).
- **Skrypty pluginów** (PWA, UM, order-attribution) — tema ich nie rejestruje; conditional load po stronie pluginów. Tema nie może ich wyłączyć na home/archive bez mu-plugin lub filtra.
- **Duplikaty CSS/JS:** jeden main.css (zbudowany z parts) — brak osobnych plików per-strona; brak literalnych duplikatów w temacie. Woo + pluginy mogą dodawać swoje.

**Rekomendacje:**
- Rozważyć conditional load `mnsk7-footer-accordion.js` tylko tam, gdzie jest footer z accordion (np. nie na czystym archive bez footer widgets).
- wc-cart-fragments: patrz §3 — czy na home/archive w ogóle potrzebny.

---

## 2. Duży inline JS w footerze / headerze

### Wszystkie wp_footer (functions.php)

| Priorytet | Warunek | Zawartość |
|-----------|---------|-----------|
| 5 | zawsze | Alert diagnostyczny (wc_add_to_cart_params) — tylko w określonych trybach, zwykle wyłączony |
| 20 | zawsze | **Główny blok:** runCritical() (menu, search, cart, megamenu) + runDeferred() (promo, shrink, Instagram). **Pass 2b:** runCritical w requestIdleCallback(timeout 100), runDeferred w rIC(timeout 2000). |
| 5 | tylko `is_cart()` | Fallback przycisku checkout (jedna obsługa kliku) |
| 6 | tylko `is_singular('product')` | PDP sticky CTA (IntersectionObserver, scroll, sync ceny) |
| 20 | tylko PLP (is_shop/cat/tag) | Load more (fetch, insertAdjacentHTML, result_count) |
| 19 | tylko PLP | Chips „Więcej” / „Więcej filtrów” (querySelectorAll toggles) |
| 25 | zawsze | Shipping zone notice (querySelectorAll notices, scrollY, scrollTo) |

### Inline w footer.php

- Cookie bar: init tylko gdy `$show_cookie_bar_markup` (brak zgody). Sprawdza `getElementById('mnsk7-cookie-bar')`, potem listeners. Niewielki.

### Inline w header.php

- Brak `<script>` w headerze. Jest tylko `<style id="mnsk7-header-critical">` (krytyczny wygląd headera).

### Inline przy shortcode Instagram (embed)

- `wp_add_inline_script('mnsk7-instagram-embed', ...)` — process() + setTimeout 2s/5s.
- W HTML shortcode: `querySelectorAll('.mnsk7-instagram-feed__post')`, po 3s `offsetHeight` w pętli — potencjalny reflow w pętli (patrz §10).

### Analiza głównego bloku (runCritical + runDeferred)

- **runCritical:** menu toggle, `querySelectorAll('li.menu-item-has-children')`, megamenu (hover, focus, keydown), search (panel, resize, click, keydown), cart (dropdown, hover, click). Około 220 linii. **Krytyczne dla first paint:** same przyciski menu/search/cart — bez tego użytkownik nie otworzy menu. Reszta (megamenu, search panel logic) może być opóźniona.
- **runDeferred:** promo bar (sessionStorage, close, `--mnsk7-promo-h` z **promoBar.offsetHeight** — jeden odczyt layoutu), header shrink (scrollY, classList), Instagram carousel (dots, slides). Nie krytyczne dla first paint.
- **Ciężkie wzorce:**  
  - `menu.querySelectorAll('li.menu-item-has-children')` — zwykle kilkanaście węzłów.  
  - `carousel.querySelectorAll('.mnsk7-instagram-feed__dot')` / `slides` — tylko gdy jest carousel.  
  - `document.querySelectorAll('.woocommerce-result-count')` w load-more callback.  
  - `document.querySelectorAll('.mnsk7-plp-chips-toggle')` — tylko na PLP.  
  - Brak setek listenerów na kartach; brak pętli po wszystkich wierszach tabeli przy init.
- **Reflow:** `promoBar.offsetHeight` w runDeferred (po idle). `scrollY` w scroll handler (passive). Shipping notice: `scrollY` + `scrollTo` w pętli po notices — możliwy layout thrash przy wielu notices.

**Rekomendacje:**
- Rozbić runCritical na: (1) minimalny init — tylko menu toggle + cart trigger + search toggle (pojedyncze addEventListener); (2) reszta (megamenu, search panel, cart dropdown) w osobnym kawałku, np. rIC z krótkim timeout.
- Shipping notice: nie czytać scrollY w pętli; zapisać raz przed pętlą.

---

## 3. wc-cart-fragments

### Stan

- **Enqueue:** `wp_enqueue_script('wc-cart-fragments')` tylko gdy `! is_cart() && ! is_checkout()` (functions.php, priorytet 5).
- **Defer:** `script_loader_tag` ustawia `defer` na wc-cart-fragments.
- **Filter `woocommerce_add_to_cart_fragments`:** tema zwraca fragmenty dla `a.mnsk7-header__cart-trigger` (ikona + licznik) i `.mnsk7-header__cart-summary` (dropdown). Używane po AJAX add-to-cart do podmiany DOM w headerze.

### Pytania

- **Czy potrzebny na home?** Tak tylko jeśli po add-to-cart z innej strony (np. PDP) użytkownik wraca na home i ma odświeżyć licznik bez przeładowania. Jeśli licznik może się zaktualizować dopiero po przeładowaniu lub przy wejściu na stronę z formularzem — można rozważyć **wyłączenie na home** (conditional enqueue `! is_front_page()` lub lazy: enqueue dopiero po pierwszym hover na ikonie koszyka).
- **Czy potrzebny na archive?** Tak — na archive jest przycisk „Dodaj do koszyka”; po dodaniu licznik w headerze powinien się zaktualizować. Wyłączenie na archive wymaga innego mechanizmu (np. pełne przeładowanie lub własny lekki endpoint).
- **Czy na PDP?** Tak — po add-to-cart licznik i mini-koszyk w headerze muszą się zaktualizować.
- **Lżejsza zamiana:** Woo domyślnie robi request fragmentów po każdym add-to-cart. Można by (w osobnym zadaniu) zastąpić pełnym przeładowaniem tylko na home albo lazy init skryptu (np. ładowanie wc-cart-fragments dopiero przy pierwszym hover na koszyk).

**Rekomendacja:** Na home i archive **obecnie jest ładowany**. Decyzja produktowa: czy na home akceptowalne jest brak natychmiastowej aktualizacji licznika bez przeładowania; jeśli tak — conditional enqueue `! is_front_page()` zmniejszy TBT na home. Archive i PDP zostawić z fragmentami, chyba że pojawi się lżejszy mechanizm.

---

## 4. Realny LCP na archive i home

### Z Lighthouse (Pass 2 / 2b)

- **Archive:** W przebiegu `lighthouse-archive-after.json` LCP = **promo bar** (`span.mnsk7-promo-bar__text`), nie pierwsza miniatura. Breakdown: TTFB ~1637 ms, element render delay ~757 ms. Szczegóły: PERFORMANCE-PASS-2b.md.
- **Home:** W dostępnych przebiegach LCP bywał ~3,0–3,1 s; element w audit nie zawsze ujawniony w JSON (items puste). Hero to tekst + chipy + USPs — bez dużej obrazkowej „hero image”; bestsellery ładują obrazy przez shortcode.

### Szablony

- **Archive:** `archive-product.php` → pętla `content-product-table-row.php` (tabela) lub `content-product.php` (karty). Promo bar jest w **header.php** (na początku `<div id="page">`), przed `<header id="masthead">`. Render w PHP, bez JS. Nie lazy. Font: Inter (preload w header). Szerokość/wysokość: promo bar to tekst, nie img — brak width/height na elemencie LCP.
- **Home:** `front-page.php` → hero (tekst), bestsellery (shortcode), trust, Instagram itd. Hero nie ma jednego dominującego obrazu; pierwszy „duży” blok to tekst hero. Obrazy bestsellerów — shortcode, rozmiary z Woo.

**Rekomendacje:**
- **Archive:** Optymalizacja LCP = optymalizacja **promo bar** (TTFB, opóźnienie renderu, font). Pierwsza miniatura tabeli (eager/fetchpriority) zostaje pod perceived load listingu, nie jako główny lever LCP.
- **Home:** Weryfikacja w trace/DevTools: który węzeł jest LCP (hero title? chip? pierwszy obraz bestsellerów?). Po ustaleniu — to samo: width/height, eager, preload, unikanie blokowania.

---

## 5. Obrazy above the fold

### Home

- Hero: brak obrazu w szablonie (tylko tekst, chipy, USPs).
- Bestsellery: `[mnsk7_bestsellers]` — obrazy przez shortcode; rozmiary Woo. Brak jawnego `loading="eager"` / `fetchpriority="high"` w shortcode w temacie (shortcode w pluginie/bloku — nie przeglądane tutaj). Jeśli bestsellery są above the fold, pierwszy obraz powinien mieć eager + fetchpriority high.
- Front-page: sekcja kategorii (front-page.php) — `wp_get_attachment_image(..., 'medium', ...)` bez loading/fetchpriority.

### Archive

- Pierwszy wiersz tabeli: `content-product-table-row.php` — **eager + fetchpriority="high"** tylko dla `$mnsk7_plp_row_index === 1`. Reszta domyślna (lazy).
- Karty (content-product): brak w repozytorium osobnego content-product.php z kartami; jeśli jest w Woo — domyślne lazy. Na mobile archive (karty) pierwszy produkt — kandydat na eager/fetchpriority (obecnie tylko tabela ma to w temacie).

### Ogólne

- `get_image( 'woocommerce_thumbnail', $img_attr )` — Woo generuje srcset; tema przekazuje atrybuty. Sizes — z Woo.
- Brak preload konkretnego obrazu LCP w headerze (jest preload fontu). Gdy LCP to obraz — dodać `<link rel="preload" as="image" href="...">` pod ten obraz.

**Rekomendacje:**
- Home: jeśli LCP = pierwszy obraz bestsellerów — w shortcode/bloku dodać eager + fetchpriority dla pierwszego elementu.
- Archive (karty): jeśli używany jest szablon kart w pętli — pierwszy produkt w pętli: eager + fetchpriority (analogicznie do pierwszego wiersza tabeli).
- Sprawdzić, czy obrazy mają width/height (Woo zwykle dodaje). Sizes odpowiedni do viewportu.

---

## 6. Rozmiar DOM i ciężkie szablony

### Header

- **Megamenu:** Pełna lista (get_terms: do 16 kategorii, do 10 tagów) renderowana **zawsze** w header.php — także na mobile. Brak warunku `mnsk7_is_mobile_request()` w obecnym header.php; megamenu w DOM na każdej stronie. Duży fragment HTML (dziesiątki linków).
- **Sticky:** header ma klasę `mnsk7-header--sticky`; jeden nav, jeden search, jeden cart.

### Footer

- footer.php — standardowy footer z widgetami, accordion (footer-accordion.js). Bez duplikacji całego menu.

### Archive

- Tabela: jeden `<table>`, wiersze w pętli. Chipsy filtrów, toolbar — w archive-product.php. Brak podwójnej tabeli desktop/mobile w tym samym czasie (layout przełączany CSS).

### Cookie / promo / notices

- Cookie bar: jeden blok, warunkowy (tylko gdy brak zgody). Promo bar: jeden blok w header. Woo notices — standardowo nad contentem; tema ma placeholder na shipping notice i skrypt przenoszący.

**Rekomendacje:**
- **Megamenu:** Na mobile nie wyświetlać pełnej listy kategorii/tagów w DOM (np. tylko link „Sklep” bez submenu) — wymaga warunku w header.php (np. `mnsk7_is_mobile_request()`) i ewentualnie innego linku. Zmniejszy rozmiar DOM i czas parse na mobile.
- Reszta: bez oczywistych duplikatów.

---

## 7. Render-blocking CSS

### Co się ładuje

- **storefront-style** (parent), **mnsk7-storefront-style** (child style.css), **mnsk7-main** (main.css) — wszystkie w `<head>`, bez `media="print"` ani async. **woocommerce-layout** — Woo, w head.
- **main.css** = konkatenacja 26 parts (00-fonts-inter … 25-global-layout): fonty, tokeny, reset, header, PLP, single-product, bloki, home, footer, cookie, cart-checkout, breadcrumbs, responsive, print, PLP table, layout. **Jeden plik na wszystkie strony** — na home i archive ładują się też style cart-checkout, single-product, FAQ, delivery itd.

### Krytyczne dla first screen

- Dla first paint: header (w tym promo bar), hero/home content, tabela/karty na archive. Część parts (np. 18-cart-checkout, 06-single-product, 14-faq) nie jest krytyczna na home/archive.
- W header.php jest **inline `<style id="mnsk7-header-critical">`** — minimalny wygląd headera, żeby przy opóźnionym main.css header nie „skoczył”.

### Duble

- Brak literalnego duplikatu main.css i parts w temacie. Woo i pluginy mogą dodawać swoje arkusze.

**Rekomendacje:**
- Rozważyć podział CSS na critical (above-the-fold) i deferred (np. cart-checkout, single-product ładowane tylko na odpowiednich szablonach) — wymaga zmiany strategii enqueue (np. osobne handle per kontekst). Duża zmiana.
- Krótkoterminowo: nie ładować nic dodatkowego na home/archive; ewentualnie `media="print"` dla 23-print.css i ładanie go asynchronicznie (mały zysk).

---

## 8. Fonty

- **Inter:** jeden plik woff2, `assets/fonts/inter-latin-wght-normal.woff2`. W header.php: **`<link rel="preload" href="...inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>`**. W main.css (00-fonts-inter.css): `@font-face` z `font-display: swap`. Jedno naчертание (variable lub normal).
- Tema dequeuje `storefront-fonts` (parent). Brak Google Fonts.
- Brak preload innych fontów.

**Wnioski:** Jeden font, preload, swap — OK. Możliwy wpływ na LCP tekstu (promo bar, hero) jeśli render czeka na font; preload powinien to ograniczać.

---

## 9. Zależności jQuery

- W **temat** nie ma `$(` ani `jQuery(` w PHP/JS. Używane jest `document.querySelector`, `addEventListener`, `fetch`, `classList`, `requestIdleCallback`. footer-accordion.js — vanilla.
- Woo i pluginy (UM, PWA, order-attribution) ładują jQuery; tema nie dodaje własnego kodu opartego na jQuery.

---

## 10. Layout thrashing / forced reflow

- **promoBar.offsetHeight** (functions.php, runDeferred) — jeden odczyt przy init promo bar (w idle). Akceptowalne.
- **scrollY** w obsłudze scroll (shrink header) — passive listener. OK.
- **Shipping notice:** pętla po `notices`, wewnątrz `scrollY` i `scrollTo` — teoretycznie możliwy thrash przy wielu notices; w praktyce zwykle jeden. Warto `scrollY` odczytać raz przed pętlą.
- **Instagram shortcode:** po 3 s pętla `posts.forEach` z `ifr.offsetHeight` — odczyt layoutu w pętli. Przy wielu postach lepiej zbierać do tablicy i jeden requestAnimationFrame.
- Brak offsetWidth/scrollHeight w tight loop w głównym init.

**Rekomendacje:** Drobne: (1) shipping — jeden odczyt scrollY; (2) Instagram fallback — unikać offsetHeight w pętli lub batch w rAF.

---

## 11. Warunkowa inicjalizacja komponentów

- **Cart checkout button:** wp_footer tylko `is_cart()`.
- **PDP sticky CTA:** wp_footer tylko `is_singular('product')`; wewnątrz `if (!sticky || !form || !mainBtn) return`.
- **PLP load more:** wp_footer tylko `is_shop() || is_product_category() || is_product_tag()`; wewnątrz `if (!wrap) return`, `if (!btn || !tbody) return`.
- **PLP chips:** to samo warunek PLP; wewnątrz `querySelectorAll('.mnsk7-plp-chips-toggle')` — init tylko gdy są toggle’e.
- **Shipping notice:** zawsze wstawiony skrypt; wewnątrz `if (!placeholder) return` i pętla po notices.
- **Główny blok (menu/search/cart):** zawsze; wewnątrz `if (menuToggle && nav)`, `if (menu)`, `if (searchToggle && searchDropdown)`, `if (cartWrap)` — init tylko przy istnieniu elementów. **Promo, shrink, Instagram carousel** — w runDeferred, każdy blok ma `if (promoBar)`, `if (header)`, `if (carousel)`.

Zasada „najpierw sprawdź obecność, potem init” jest zachowana. Nie ma masowego init na nieistniejące selektory.

---

## 12. Wkład serwera w LCP

- **TTFB:** Po stronie tematu nie mierzony; z Lighthouse (archive) TTFB ~1,6 s — istotna część LCP. Cache strony (page cache), opcache, CDN — po stronie serwera/hostingu.
- **Zapytania PHP na archive:** header.php wywołuje `get_terms` (product_cat, product_tag) na każde żądanie — dwa zapytania do bazy. Archive-product.php — główna pętla Woo (products), ewentualne filtry. Można cache’ować listę kategorii/tagów (transient) lub ograniczyć wywołania na mobile (np. gdy megamenu nie renderowane).
- **Header/cart:** Fragmenty koszyka (HTML w headerze) generowane w PHP przy każdym request; Woo może cache’ować fragmenty. Brak ciężkiej dynamiki w headerze poza standardem Woo.

**Rekomendacje:** TTFB i cache — audyt po stronie serwera/hostingu. W temacie: rozważyć transient dla get_terms megamenu lub warunek mobile, żeby nie robić get_terms gdy megamenu nie jest renderowane.

---

## 13. Co dokładnie pogorszyło się po Pass 2 / 2b (vs Pass 1)

### Pass 2

- **functions.php:** (1) wc-cart-fragments: enqueue tylko gdy `! is_cart() && ! is_checkout()` — **zostawić**. (2) Blok inline: podział na runCritical (wywołany **synchronicznie**) i runDeferred (requestIdleCallback). **To podniosło home TBT** — runCritical ~220 linii wykonywanych przy parsowaniu footera = długie zadanie (document URL w Lighthouse). (3) content-product-table-row.php: eager + fetchpriority dla pierwszego wiersza — **zostawić** (nie szkodzi; na archive LCP = promo bar, nie miniatura).
- **Archive LCP:** W Pass 2 założono LCP = pierwsza miniatura; w pomiarze LCP = promo bar. Eager na pierwszej miniaturze nie poprawił LCP; równocześnie inne zmiany (np. timing init) mogły wpłynąć na render. **Nie cofać** eager/fetchpriority; **doprecyzować** optymalizację pod promo bar.

### Pass 2b

- **functions.php:** runCritical już **nie** synchronicznie, tylko `requestIdleCallback(runCritical, { timeout: 100 })` (lub setTimeout 0). **Cel:** obniżyć TBT na home. W pomiarach 2b home TBT wciąż gorsze niż Pass 1 (2410 vs 1668), archive słabsze (LCP 2,9, TBT 1200). Czyli **opóźnienie runCritical** nie przywróciło TBT do Pass 1; możliwe że sam rozmiar bloku (parse + wykonanie w jednym tasku po 100 ms) wciąż daje długie zadanie, albo wpływ mają inne skrypty (fragments, jQuery, pluginy).

### Co odwołać, co zostawić (vs Pass 1)

| Zmiana | Akcja |
|--------|--------|
| wc-cart-fragments tylko !cart && !checkout | **Zostawić** |
| runCritical w rIC(100) / runDeferred w rIC(2000) | **Do decyzji:** albo pełny rollback do Pass 1 (cały blok w jednym rIC, jak przed Pass 2), albo dalsze rozbicie na mniejsze kawałki i mierzenie. |
| Eager/fetchpriority pierwszy wiersz tabeli | **Zostawić** |
| Dokumentacja LCP = promo bar na archive | **Zostawić** |

**Odwołanie do Pass 1 (baseline):** Przywrócić w functions.php stan init headera **sprzed Pass 2** (jeden blok `run()` w requestIdleCallback z timeout 1,5 s, bez podziału runCritical/runDeferred). To przywróci zachowanie z Pass 1; TBT home może wrócić do ~1668 ms przy możliwym opóźnieniu pierwszego kliku menu/search/cart.

---

## 14. Co mierzyć po każdej zmianie

Po **każdej** celowej zmianie (np. conditional enqueue, rozbicie init, zmiana LCP):

1. **Lighthouse (mobile, staging):**  
   - **Home:** Performance, FCP, LCP, TBT, CLS.  
   - **Archive:** Performance, FCP, LCP, TBT, CLS.
2. **LCP element:** Z audytu LCP (lub trace) — który węzeł jest LCP na home, który na archive; czy się nie zmienił.
3. **Long tasks:** Czy nowe długie zadania nie pojawiły się (Lighthouse long-tasks / Performance panel).
4. **UX:** Menu (burger), search, cart, add-to-cart z listingu, filtry, „Pokaż więcej”, logowanie / linki do konta — działają bez błędów i bez wyraźnego opóźnienia.

Zapisywać wyniki w docs (np. PERFORMANCE-AUDIT-AND-PLAN.md lub PERFORMANCE-STATUS.md), bez wymyślania „after” przed pomiarem.
