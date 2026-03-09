# Аудит Product Page (PDP) UX — staging.mnsk7-tools.pl

**Дата:** март 2026  
**Роль:** Senior WooCommerce PDP reviewer  
**Проверено:** несколько карточек товаров (короткое и длинное название, разные категории), код темы и mu-plugins, accessibility snapshot.

---

## 1. Above the fold

| Элемент | Статус | Замечание |
|--------|--------|-----------|
| **Галерея** | ⚠️ | Есть; миниатюры по аудиту 03 — обрезаны по краям; zoom/lightbox включены в теме |
| **Title** | ✅ | H1 присутствует; на длинных названиях возможен перенос (clamp 1.25rem–2.5vw) |
| **Breadcrumbs** | ❌ | Только «Strona główna › Sklep» — **категории товара нет** (фильтр убирает только последний элемент — название продукта) |
| **Цена** | ✅ | Крупно (--fs-2xl), после availability |
| **Наличие** | ✅ | «✓ 158 w magazynie» в одной строке с «X osób kupiło» при sales ≥ 5 |
| **Social proof / kupiło** | ⚠️ | Показ «X osób kupiło» только при `get_total_sales() >= 5`; при 0–4 продажах блока нет — слабый social proof для новинок |
| **Key parameters** | ✅ | Блок «Kluczowe parametry» с grid dt/dd; короткие подписи через short_labels |
| **Quantity** | ✅ | Spinbutton, 72px width; на мобиле форма в колонку, кнопка 100% |
| **Add to cart** | ✅ | Wyraźna przycisk (primary, min-height 48px) |
| **Trust row** | ✅ | 4 badge’y: Dostawa jutro, Faktura VAT, Darmowa dostawa od X zł, Zwroty 30 dni |
| **Category/tag links** | ✅ | Meta chips (Katalog / tagi) pod trust; jedna kategoria w snapshot (Frez z łożyskiem) |

**Проблемы above the fold:**  
- Breadcrumbs bez kategorii — gorsza nawigacja i SEO.  
- Brak lub słaby social proof przy małej liczbie sprzedaży.

---

## 2. Галерея

| Aspekt | Stan | Uwagi |
|--------|------|--------|
| **Logika miniaturek** | ✅ | Flex, gap 0.5rem; min-width 56px, object-fit: cover; aktywny border-primary |
| **Zoom / lightbox** | ✅ | `wc-product-gallery-zoom`, `wc-product-gallery-lightbox`, `wc-product-gallery-slider` w add_theme_support |
| **Swipe na mobile** | ⚠️ | Slider WC domyślnie; nie weryfikowano gestów na żywo |
| **Klik na desktop** | ✅ | Standard WC (główny obraz + miniatury) |
| **Rytm pionowy/poziomy** | ✅ | Obraz główny, pod spodem miniatury w jednym rzędzie (flex-wrap) |
| **Jednolita wysokość obrazów** | ⚠️ | Miniatury 56×56, object-fit cover — proporcje zachowane; **główny obraz** bez sztywnej wysokości — przy różnych proporcjach zdjęć wysokość bloku może skakać |
| **Proporcje** | ⚠️ | Z poprzedniego audytu: **miniatury obcinane po brzegach** (overflow); na głównym zdjęciu **watermark i wymiary na obrazie** — wygląd „nieprodukcyjny” |

**Podsumowanie galerii:**  
- Konieczne: brak obcinania miniaturek (overflow + szerokość kontenera lub karuzela).  
- Zalecane: usunięcie watermarku i wymiarów z głównego zdjęcia; opcjonalnie stała wysokość/aspect-ratio dla głównego obrazu.

---

## 3. Product info

| Aspekt | Stan | Uwagi |
|--------|------|--------|
| **Zrozumiałość parametrów** | ✅ | Krótkie etykiety (Średnica robocza, Trzpień, Dł. robocza itd.); grid dt/dd |
| **Śmieci techniczne** | ⚠️ | W snapshot: **„ilość” + pełna nazwa produktu w jednym kontekście** (np. e66) — możliwy źle powiązany label dla quantity lub duplikat nazwy w ARIA |
| **Labels** | ✅ | Krótkie etykiety; brak widocznych „skoszonych” w kodzie |
| **Wybrane vs pełna lista atrybutów** | ⚠️ | Dla variable: w key_params są selecty z **pełną listą wariantów**; równocześnie **form.cart .variations { display: none }** — wybór tylko w bloku Kluczowe parametry. Ryzyko: użytkownik nie widzi standardowego formularza wariacji WC; zależnie od implementacji może brakować synchronizacji selected value → add to cart |
| **Kluczowe wymiary** | ✅ | Średnica, trzpień, długości w pierwszym bloku; na mobile key_params w jednej kolumnie |
| **Czytelność tytułu na mobile** | ⚠️ | font-size clamp(1.25rem, 2.5vw, var(--fs-2xl)) — przy bardzo długiej nazwie na wąskim ekranie może być wiele linii; nie weryfikowano line-clamp |
| **Przeciążenie pierwszego ekranu** | ⚠️ | Kolejność: breadcrumb, tytuł, availability+sales, cena, key params (może być dużo), zastosowanie, quantity+CTA, trust, meta. Na produktach z wieloma parametrami i „Zastosowanie” **pierwszy ekran jest gęsty**; CTA może być poniżej fold na małych ekranach |

**Rekomendacje:**  
- Sprawdzić powiązanie label „Ilość” z polem quantity (for/id, aria-label) i usunąć zbędne powtórzenie nazwy produktu w tym kontekście.  
- Upewnić się, że wybór wariantu w key_params poprawnie ustawia add-to-cart (np. przez WC variation form w tle).  
- Rozważyć na mobile skrócenie lub zwinięcie „Zastosowanie” (accordion) albo przeniesienie poniżej CTA.

---

## 4. CTA block

| Aspekt | Stan | Uwagi |
|--------|------|--------|
| **Widoczność przycisku** | ✅ | Duży przycisk (padding 1rem 2rem), kolor primary, min-height 48px |
| **Siła CTA** | ✅ | Jeden główny CTA; na mobile width 100%, flex-direction column |
| **Konflikt z elementami drugoplanowymi** | ✅ | Trust badges i meta chips poniżej; brak konkurencyjnych przycisków obok |
| **„Co dalej”** | ⚠️ | Po dodaniu do koszyka brak w snapshot wyraźnego sticky / powiadomienia w widocznym miejscu (standard WC notice); **brak sticky CTA** — przy długiej stronie użytkownik musi scrollować w górę, żeby zobaczyć ponownie „Dodaj do koszyka” |
| **Instant reassurance przy CTA** | ✅ | Trust row (dostawa, faktura, zwroty) tuż nad meta; availability i „X kupiło” nad CTA |

**Główny brak:** brak sticky paska z ceną i „Dodaj do koszyka” na mobile — ryzyko utraty konwersji przy długim scrollu.

---

## 5. Description / tabs / accordions

| Aspekt | Stan | Uwagi |
|--------|------|--------|
| **Wystarczająco opisu** | ⚠️ | Zależne od treści; short description **wyzerowane na PDP** (filtr woocommerce_short_description) — cały opis w harmonijce |
| **Rozwinięcie** | ✅ | Accordion „Pokaż opis” (details/summary); domyślnie zwinięty |
| **Duplikacja z parametrami** | ✅ | Short description usunięty celowo, żeby nie dublować key_params i opisu |
| **Ważna informacja nisko** | ⚠️ | Pełny opis **zawsze poniżej** buyboxu i related; na długich stronach użytkownik może nie scrollować — ale to typowe dla PDP; można rozważyć 1–2 zdania „above the fold” bez pełnego opisu |

**Wniosek:** Opis w accordion jest OK; brak short description na PDP może zmniejszyć zaufanie, jeśli w key_params nie ma wszystkich istotnych informacji.

---

## 6. Related / Podobne / Cross-sell

| Aspekt | Stan | Uwagi |
|--------|------|--------|
| **Tytuł** | ✅ | „Podobne produkty” (H2) |
| **Relewancja** | ✅ | WC related by category/tag; w snapshot 3 produkty z tej samej kategorii (Frez z łożyskiem / prosty) |
| **Liczba** | ✅ | 4 (woo-ux.php); na stronie testowej 3 — pewnie mniej dopasowań |
| **Design** | ✅ | Grid 4 kolumny (desktop), 2 (≤900px), 1 (≤480px); karty z cieniem, hover lift |
| **Rozmiar kart** | ✅ | minmax(0,1fr), spójne z PLP |
| **CTA na karcie** | ✅ | „Dodaj do koszyka”, writing-mode horizontal-tb, pełna szerokość |
| **Swipe/grid na mobile** | ✅ | 1 kolumna na 480px; grid, nie karuzela |
| **Wygląd „przypadkowy”** | ⚠️ | Brak podtytułu (np. „Z tej samej kategorii”); jeden H2 może być mało kontekstu |
| **Siła bloku** | ⚠️ | Blok na pełną szerokość, ale **pod accordion opisu** — kolejność: opis → related. Część użytkowników może nie dojść do related; nie ma upsellów w snapshot (prosty produkt) |
| **Zagłówek / kolejność / priorytet** | ⚠️ | Można rozważyć: related przed pełnym opisem (np. Opis skrócony → Related → Opis pełny w accordion) albo wyraźniejszy podtytuł |

**Podsumowanie related:**  
- Działanie i wygląd OK.  
- Ulepszenia: ewentualna zmiana kolejności (related wyżej) lub wzmocnienie kontekstu nagłówka; sprawdzenie upsellów dla variable.

---

## 7. Sticky / mobile UX

| Aspekt | Stan | Uwagi |
|--------|------|--------|
| **Sticky add-to-cart** | ❌ | **Brak** — nie ma paska sticky z ceną i CTA na dole ekranu przy scrollu |
| **Utrata CTA przy scrollu** | ⚠️ | Na długim PDP (dużo parametrów, opis, related) CTA znika w górę; użytkownik musi wracać scrollując |
| **Wygoda zakupu z mobile** | ⚠️ | Form.cart w kolumnie, przycisk 100% — OK; brak sticky obniża wygodę przy długiej stronie |

**Rekomendacja:** Dodać sticky bar na mobile (np. ≤768px): cena + „Dodaj do koszyka”, pokazywany gdy oryginalny CTA jest poza viewportem.

---

## 8. WooCommerce correctness

| Aspekt | Stan | Uwagi |
|--------|------|--------|
| **Add to cart flow** | ✅ | Form.cart z quantity i single_add_to_cart_button |
| **Notice po dodaniu** | ✅ | Standard WC (nie weryfikowano w teście E2E); w functions.php jest obsługa fragmentów koszyka |
| **Quantity** | ✅ | Spinbutton, value 1; brak widocznych ograniczeń min/max w snapshot |
| **Breadcrumbs** | ⚠️ | WooCommerce breadcrumb z filtrem — ostatni element (nazwa produktu) usunięty; **brak kategorii** w łańcuchu (WC domyślnie może jej nie dodawać w zależności od ustawień) |
| **Stock notice** | ✅ | Własny blok availability (in-stock/out-of-stock); woocommerce_get_stock_html wyzerowany na PDP |
| **Spójność related** | ✅ | 4 kolumny, ten sam layout co w 06-single-product; kolumny related/upsells spójne |

**Uwaga:** Dla variable product variations są ukryte (display: none); wybór wariantu odbywa się przez selecty w „Kluczowe parametry”. Trzeba potwierdzić, że WC variation script poprawnie aktualizuje cenę i add-to-cart (np. data-product_id, variation_id).

---

## 9. Lista 15–25 problemów PDP

1. **Breadcrumbs bez kategorii** — tylko Strona główna › Sklep; brak np. Sklep › Frez prosty › [nazwa].  
2. **Miniatury galerii obcinane** po brzegach kontenera (overflow / szerokość).  
3. **Watermark i wymiary na głównym zdjęciu** — obniżają jakość wizerunku.  
4. **Brak sticky CTA na mobile** — CTA znika przy scrollu.  
5. **Możliwy błąd accessibility:** „ilość” + pełna nazwa produktu w jednym kontekście (label/aria).  
6. **Social proof „X osób kupiło”** tylko przy ≥5 sprzedaży — przy 0–4 brak.  
7. **Pierwszy ekran przeciążony** przy wielu parametrach i „Zastosowanie” — CTA może być poniżej fold na małych ekranach.  
8. **Brak short description na PDP** — cały opis w accordion; zero tekstu above the fold poza parametrami.  
9. **Related zawsze poniżej opisu** — część użytkowników może nie dojść; kolejność do rozważenia.  
10. **Brak wyraźnego podtytułu w related** (np. „Z tej samej kategorii”).  
11. **Główny obraz bez sztywnego aspect-ratio** — różna wysokość bloku przy różnych proporcjach zdjęć.  
12. **Dwa cookie banners** (tema + plugin) — wspólny problem, na PDP też widoczne.  
13. **URL produktu:** `/sklep/product-slug/` zamiast standardowego `/product/slug/` — zależnie od konfiguracji; może być celowe.  
14. **Variable: variations ukryte** — wybór tylko w key_params; wymaga weryfikacji synchronizacji z formularzem.  
15. **Brak weryfikacji zoom/lightbox** w działaniu (tylko theme_support).  
16. **Brak weryfikacji swipe** galerii na mobile w teście.  
17. **Trust badges** — stały tekst (np. „Dostawa jutro”); brak personalizacji np. od progów koszyka.  
18. **Meta chips** — wiele tagów może rozciągać blok; brak limitu lub „Pokaż więcej”.

---

## 10. Top 5 conversion blockers

1. **Brak sticky „Dodaj do koszyka” na mobile** — przy długim PDP użytkownik traci CTA i może nie wracać.  
2. **Breadcrumbs bez kategorii** — gorsza nawigacja i zaufanie („gdzie jestem”).  
3. **Watermark i wymiary na głównym zdjęciu** — obniża postrzeganą jakość i profesjonalizm.  
4. **Obcinane miniatury galerii** — wrażenie niedopracowania i utrudniony wybór widoku.  
5. **Przeciążony pierwszy ekran (mobile)** — CTA może być poniżej fold; brak short description above the fold.

---

## 11. Related / cross-sell — osobne wnioski

- **Relewancja i wygląd:** OK; grid, karty, CTA.  
- **Słabe strony:**  
  - Blok „Podobne produkty” jest nisko (po accordion opisu).  
  - Brak kontekstu w nagłówku (np. „Z tej samej kategorii” lub nazwa kategorii).  
  - Nie sprawdzono upsellów dla variable (np. warianty tego samego produktu).  
- **Rekomendacje:**  
  - Rozważyć przeniesienie related wyżej (np. przed pełnym opisem) lub skrócony opis + related + opis w accordion.  
  - Dodać podtytuł do H2 (opcjonalnie).  
  - Na mobile upewnić się, że karty related nie obcinają długich nazw (line-clamp / tooltip).

---

## 12. Gotowe zadania dla Cursor

### Zadanie 1 — Breadcrumbs: dodać kategorię produktu na PDP

- **Plik:** `wp-content/themes/mnsk7-storefront/functions.php` (filtr `woocommerce_get_breadcrumb`).  
- **Aktualnie:** `array_pop($crumbs)` usuwa ostatni element (nazwa produktu).  
- **Zadanie:** Nie usuwać przedostatniego elementu (kategoria); ewentualnie po `array_pop` dodać jeden crumb z nazwą kategorii produktu, jeśli WC go nie dodał. Sprawdzić, czy WC domyślnie zwraca [Home, Shop, Category, Product] i tylko usuwać Product; jeśli zwraca [Home, Shop, Product], dodać kategorię (pierwsza przypisana) między Shop a Product, potem usunąć Product.  
- **Kryteria:** Na PDP breadcrumbs w formie: Strona główna › Sklep › [Nazwa kategorii] (bez nazwy produktu na końcu).

### Zadanie 2 — Galeria: naprawić obcinanie miniaturek

- **Plik:** `wp-content/themes/mnsk7-storefront/assets/css/parts/06-single-product.css` (sekcja `.single-product .images .flex-control-thumbs`).  
- **Zadanie:** Dla kontenera miniaturek ustawić `overflow: hidden` i upewnić się, że miniatury mieszczą się w szerokości (np. max-width kontenera, lub miniatury w wewnętrznym wrapperze z odpowiednim gap). Ewentualnie dodać poziomą karuzelę ze strzałkami na mobile zamiast jednego rzędu.  
- **Kryteria:** Żadna miniatura nie jest obcięta po lewej/prawej krawędzi; na mobile albo wszystkie widoczne, albo czytelna karuzela.

### Zadanie 3 — Sticky CTA na mobile (PDP)

- **Pliki:** nowy fragment w `06-single-product.css` lub `21-responsive-mobile.css`; ewentualnie mały JS w temacie/mu-plugin.  
- **Zadanie:** Na viewport ≤768px dodać sticky bar na dole ekranu: aktualna cena + przycisk „Dodaj do koszyka”. Pokazywać bar, gdy oryginalny form.cart jest poza viewport (IntersectionObserver lub scroll). Ukrywać, gdy użytkownik jest w zasięgu oryginalnego CTA. Dla variable — w sticky pokazać „Wybierz wariant” lub aktualną cenę po wyborze.  
- **Kryteria:** Na mobile przy scrollu w dół pojawia się dolny pasek z ceną i CTA; klik dodaje do koszyka tak jak główny przycisk; pasek znika, gdy główny CTA jest widoczny.

### Zadanie 4 — Accessibility: label „Ilość” i nazwa produktu

- **Plik:** szablon WooCommerce single product (form.cart) — jeśli tema nadpisuje, tam; inaczej sprawdzić, czy WC wyświetla `<label for="quantity">` i czy w tym samym kontekście nie pojawia się pełna nazwa produktu (np. w aria-label).  
- **Zadanie:** Upewnić się, że pole quantity ma poprawne `id` i że `<label for="...">` wskazuje na to pole; usunąć zbędne powtórzenie pełnej nazwy produktu w tym samym elemencie/labelu.  
- **Kryteria:** Screen reader czyta np. „Ilość, pole edycji, 1”; bez duplikatu długiej nazwy produktu w tym kontekście.

### Zadanie 5 — Related: opcjonalny podtytuł i kolejność

- **Plik:** `mu-plugins/inc/woo-ux.php` (related args) oraz szablon `woocommerce/after_single_product_summary` lub hook `woocommerce_after_single_product_summary`.  
- **Zadanie (opcjonalne):** (a) Dodać podtytuł pod H2 „Podobne produkty”, np. „Z tej samej kategorii” lub nazwę kategorii (filtr do tytułu related). (b) Rozważyć zmianę kolejności: wyświetlić related przed blokiem opisu (accordion) — wymaga zmiany priorytetów hooków (np. related na 9, description accordion na 15).  
- **Kryteria:** Tytuł related ma kontekst; ewentualnie related jest wyżej na stronie.

### Zadanie 6 — Usunięcie watermarku i wymiarów z głównego zdjęcia (content)

- **Uwaga:** To zadanie contentowe, nie kodowe.  
- **Zadanie:** W media WP usunąć lub wymienić główne zdjęcia produktów tak, aby nie zawierały logo/watermarku ani tekstowych wymiarów na obrazie; wymiary trzymać w „Kluczowe parametry” lub w opisie.  
- **Kryteria:** Główne zdjęcie produktu bez tekstu i logo na obrazie.

### Zadanie 7 — Short description above the fold (opcjonalne)

- **Plik:** `mu-plugins/inc/product-card.php` — filtr `woocommerce_short_description` zwraca '' na PDP.  
- **Zadanie:** Zamiast całkowicie zerować short description, pozwolić na wyświetlenie 1–2 zdań (np. pierwsze 200 znaków) pod trust badges lub nad „Pokaż opis”; reszta w accordion. Wymaga ewentualnego własnego hooka wyświetlającego skrócony excerpt.  
- **Kryteria:** Na PDP above the fold widać 1–2 zdania opisu (jeśli produkt ma short description); bez duplikacji z key_params.

---

**Koniec audytu PDP.**
