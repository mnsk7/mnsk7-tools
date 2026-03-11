# Refaktor mega menu w headerze — brak łamania słów + pass 2 (kompozycja)

**Data:** 2026-03-11  
**Plik:** `wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css`

---

## Pass 2 (kompozycja, gęstość, proporcje)

**Zmiany względem pass 1:**

| Element | Było (pass 1) | Jest (pass 2) |
|--------|----------------|----------------|
| **Dropdown** | min 520px, max 640px, padding 1rem 1.25rem 0.75rem, gap 1.25rem | min 420px, max 520px, padding 0.75rem 1rem 0.5rem, gap 0.75rem |
| **Kolumny kategorii** | 4 × minmax(11.5em, 1fr) | **3** × minmax(10.5em, 1fr) |
| **Kolumny tagów** | 3 × minmax(10em, 1fr) | 3 × minmax(9em, 1fr) |
| **List gap** | 0.125rem 1.25rem | 0.0625rem 1rem |
| **Link padding** | 0.375rem 0.5rem, line-height 1.4 | 0.25rem 0.5rem, line-height 1.35 |
| **Nagłówek sekcji** | 0.6875rem, color text-muted, border | 0.75rem, color text, border-strong |
| **Footer** | padding-top 0.75rem, margin-top 0.25rem | padding-top 0.5rem, margin-top 0.125rem |
| **Tablet ≤640px** | padding 0.75rem 1rem | padding 0.625rem 0.875rem, gap 0.625rem, min 300px |

**Liczba kolumn:** 4 → 3 dla kategorii — mniej pustej przestrzeni po prawej, układ bardziej zwarty przy ~16–19 pozycjach. Słowa nadal nie łamią się (min 10.5em).

**Bez zmian:** overflow-wrap/word-break normal, hyphens: none, hover/focus-visible, responsywność.

### Poprawki po pass 2 (luki pionowe + obcinanie tekstu)

- **Luki nad „Frez typ U” / „Frez typ V”:** grid domyślnie rozciąga komórki w wierszu (`align-items: stretch`), więc gdy jedna pozycja ma 2 linie, cały wiersz się rozciąga i w innych kolumnach powstaje pusta przestrzeń. **Rozwiązanie:** `align-items: start` na `.mnsk7-megamenu__list` — komórki nie rozciągają się, rytm pionowy równy.
- **Obcinanie słów po prawej** („kulowy”, „płytkami”): przy `minmax(10.5em, 1fr)` i wąskim dropdownie kolumny były za wąskie, a `overflow-x: hidden` obcinał treść. **Rozwiązanie:** kolumny kategorii `minmax(12em, 1fr)`, dropdown `min-width: 460px`, `max-width: 560px`, `overflow-x: auto` (w razie wąskiego viewportu — przewijanie zamiast obcięcia).

### Pass 4: scrollbar, viewport, waga, „Sklep” w tagach

**Problemy:** wewnętrzny horizontal scrollbar; otwarte menu rozsadza viewport (obcinanie logo z lewej, search z prawej); kontener za szeroki i za ciężki; layout nie dopasowuje się do szerokości; punkt „Sklep” w sekcji „Zastosowanie i materiały” zbędny.

**Zmiany:**

| Obszar | Zmiany |
|--------|--------|
| **Scrollbar** | `overflow-x: auto` → **`overflow-x: hidden`** — brak wewnętrznego paska przewijania. |
| **Viewport** | `min-width: 460px`, `max-width: 560px` → **`min-width: 280px`**, **`max-width: min(440px, calc(100vw - 2rem))`** — menu nie rozsadza strony, logo i search nie są obcinane. |
| **Waga** | Mniejszy padding (0.5rem 0.75rem 0.4rem), gap 0.5rem; nagłówki 0.6875rem, mniejsze marginesy; linki 0.2rem 0.4rem, line-height 1.3; footer węższy. |
| **Kolumny** | 3 kolumny `minmax(8.5em, 1fr)` / `minmax(7.5em, 1fr)` — mieszczą się w 440px, bez rozjeżdżania. Na viewport ≤419px: 2 kolumny `minmax(7em, 1fr)`. |
| **„Sklep” w tagach** | W `header.php` tagi z `slug === 'sklep'` są **wykluczane** z listy `$top_tags` (filtr po `get_terms`). |

**Kryteria:** brak wewnętrznego horizontal scroll; logo i search nie obcinane; menu w viewport; kolumny zwarte; czytelne mega menu.

### Pass 5: overlay panel, viewport, hover delay (NNG/Baymard/W3C APG)

**Root problem layoutowy:** Mega menu traktowano jako element wciśnięty w szerokość kontenera headera — albo ładna kompozycja wychodziła poza layout, albo mieściła się, ale wyglądała biednie (złe proporcje, puste przestrzenie).

**Założenie:** Mega menu = overlay panel powiązany z itemem „Sklep”, z **własną** szerokością zależną od viewportu, nie od wewnętrznego kontenera nav.

**Model pozycjonowania:**
- Overlay jest pozycjonowany względem top-level itemu „Sklep” (trigger). Szerokość panelu ograniczona viewportem, nie kontenerem nawigacji.
- **Bazowe wyrównanie:** od lewej krawędzi triggera (`position: absolute; left: 0; top: 100%` względem `li`). **Ograniczenie:** `max-width: min(560px, calc(100vw - 2rem))`, żeby panel nie wychodził poza ekran.
- Uwaga: przy przesuniętym triggerze i szerokim panelu `left: 0` względem punktu może dawać słabą „posadkę” — problem przepełnienia jest rozwiązany **tylko częściowo** (max-width zapobiega wyjechaniu w prawo, ale idealne centrowanie / dopasowanie do prawej krawędzi wymagałoby np. JS lub container queries).

**Viewport fit:**
- Panel **nie wychodzi poza viewport** dzięki `max-width`.
- Panel **nie konfliktuje wizualnie** z logo, wyszukiwarką i pozostałymi elementami headera (overlay nie ma ich „obcinać” — to wymóg, nie efekt uboczny menu).

**Tablet / mobile:**
- Na tablet/mobile **desktopowa realizacja megamenu jest wyłączona** (panel hover nie jest używany).
- Nawigacja przechodzi w **uproszczony pattern mobilny** (drawer, link „Sklep” do sklepu; submenu w nav na małych ekranach jest ukryte w CSS).
- Desktop hover-panel **nie jest używany na urządzeniach dotykowych**.

**Width / padding / gap:**
- **Panel:** min-width 360px, max-width jak wyżej; padding `0.75rem 1rem 0.6rem`; gap między sekcjami `0.6rem`.
- **Listy:** gap `0.125rem 0.875rem`; **górny blok** (kategorie, np. „Rodzaje frezów”) 3×`minmax(11em, 1fr)`; **dolny blok** (zastosowanie/materiały, np. „Zastosowanie i materiały”) 3×`minmax(9em, 1fr)` — w tekście opisu używamy sensu treści, nie literalnie „tagi”.
- **Nagłówki:** 0.6875rem, font-weight 700, border-bottom strong; margin-bottom 0.35rem.
- **Footer „Wszystkie produkty”:** padding-top 0.5rem, margin-top 0.2rem.
- **Linki:** overflow-wrap/word-wrap/word-break normal, hyphens none — zawijanie tylko między słowami.

**Hover delay (Baymard/NNG) — UX i kryteria akceptacji:**
- **Otwarcie:** na `mouseenter` (trigger lub cały `li`) z **opóźnieniem 400 ms** — dopiero potem panel widoczny (klasa `.mnsk7-megamenu-open`).
- **Zamknięcie:** na `mouseleave` z **opóźnieniem 150 ms** — unikamy flicker przy zjechaniu kursorem z triggera.
- **Przejście trigger → panel:** ruch kursora z „Sklep” do panelu **nie może zrywać stanu open** (panel jest wewnątrz `li`, więc `mouseleave` nie występuje przy samym przejściu).
- **:focus-within** — otwarty panel przy focusie wewnątrz; dostępność klawiaturowa zachowana.
- **Escape** — zamyka panel i **zwraca focus na trigger** (link „Sklep”).

**Pliki:** `04-header.css` (panel, desktop media, kompozycja), `functions.php` (hover delay + Escape).

**Self-QA:** desktop 1440+/1280, tablet, mobile; brak overflow; brak łamania słów; hover 400 ms / focus / Escape.

**Rzeczy do weryfikacji ręcznej (na co uważać):**
- **Czy hover nie urywa się między triggerem a panelem** — jeśli między przyciskiem „Sklep” a dropdownem jest choć 1–2 px luzu, menu może migać nawet przy delay.
- **Czy panel nie jest obcinany z prawej przy 1280 px i mniej** — zwłaszcza przy szerokim searchu i gęstym headerze.
- **Zoom 110% / 125%** — tam często wychodzi prawda o minmax i przepełnieniu.
- **iPad / touch laptop** — logika hover na urządzeniach hybrydowych często zachowuje się niestabilnie.
- **Długie tłumaczenia / nowe punkty** — układ może być OK tylko na obecnym zestawie linków; przy dłuższych nazwach lub nowych sekcjach warto sprawdzić ponownie.

---

## 1. Przyczyna problemu (pass 1)

1. **`grid-template-columns: repeat(4, minmax(0, 1fr))`** — `minmax(0, 1fr)` pozwala kolumnom skurczyć się do zera, więc przeglądarka łamała tekst w środku słów (np. „diament/owy”, „łożyskie/m”).
2. **`overflow-wrap: break-word` + `word-wrap: break-word`** na linkach — przy wąskim kontenerze dozwalało łamanie w dowolnym miejscu.
3. **`min-width: 0`** na `.mnsk7-megamenu__list a` — w kontekście gridu pozwalało elementowi kurczyć się poniżej rozmiaru treści i wymuszało łamanie.
4. **Zbyt mała `min-width` dropdownu (360px)** — przy 4 kolumnach każda była zbyt wąska dla „Frez diamentowy”, „Frez spiralny stożkowo kulowy” itd.

---

## 2. Wprowadzone zmiany

### Dropdown
- **min-width:** 360px → **520px**
- **max-width:** 600px → **640px**
- **padding:** 1rem 1.25rem 0.75rem, **gap:** 1.25rem

### Kolumny (bez łamania słów)
- **.mnsk7-megamenu__list--cols:** `minmax(0, 1fr)` → **`minmax(11.5em, 1fr)`** (4 kolumny)
- **.mnsk7-megamenu__list--tags:** `minmax(0, 1fr)` → **`minmax(10em, 1fr)`** (3 kolumny)

### Linki
- Usunięto **min-width: 0**.
- **overflow-wrap: normal**, **word-wrap: normal**, **word-break: normal**, **hyphens: none** — tylko przenoszenie między słowami.
- **line-height: 1.4**, lekko większy **padding: 0.375rem 0.5rem**.

### Nagłówki sekcji
- Mniejszy font (**0.6875rem**), **letter-spacing: 0.06em**, **border-bottom** dla odseparowania, **line-height: 1.3**.

### Stopka „Wszystkie produkty”
- **padding-top: 0.75rem**, **margin-top: 0.25rem** — wyraźne oddzielenie od list.

### Responsywność
- **≤640px:** 3 kolumny kategorii (min 9em), 2 kolumny tagów (min 8em); min-width dropdown 320px.
- **≤480px:** 2+2 kolumny (min 8em / 7em); min-width dropdown 280px.

---

## 3. Zmienione pliki

| Plik | Zmiany |
|------|--------|
| `wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css` | Blok `.sub-menu.mnsk7-megamenu` + `.mnsk7-megamenu__*`: grid, linki, nagłówki, footer, media 640px i 480px. |

---

## 4. Self-QA

- **Długie nazwy:** „Frez spiralny stożkowo kulowy”, „Frez z wymiennymi płytkami” — zawijanie tylko między słowami, max 2–3 linie.
- **Desktop:** 4 kolumny kategorii, 3 tagi, równa siatka, czytelne odstępy.
- **Tablet (≤640px):** 3+2 kolumny, szersze min kolumn.
- **Mobile (≤480px):** 2+2 kolumny.
- **Hover / focus:** Bez zmian w logice; :hover i :focus-visible pozostawione.
- **Dostępność:** Struktura i kontrast bez zmian; nawigacja klawiaturą i screen readery bez zmian.

---

## 5. Kryteria spełnione

- Brak łamania w środku słów (np. diament/owy).
- Brak `word-break: break-all`, `overflow-wrap: anywhere` na linkach mega menu.
- Przenoszenie tylko między słowami; długie nazwy w 2–3 liniach.
- Kolumny w sensownej siatce (minmax z em).
- Szerszy dropdown, czytelne nagłówki i stopka „Wszystkie produkty”.
- Zachowany hover, focus i responsywność.

---

## Ograniczenia bieżącego rozwiązania

- Wyrównanie od lewej krawędzi triggera **nie rozwiązuje kwestii idealnie** — przy przesuniętym triggerze lub wąskim viewport panel może nadal nie „siedzieć” optymalnie.
- **Truly smart repositioning** (np. dopasowanie do prawej krawędzi viewport) wymagałoby **JS** (obliczenie pozycji / max-width w zależności od pozycji triggera).
- **Mobile pattern** jest opisany funkcjonalnie (drawer, link do sklepu, brak desktop hover-panelu), ale może wymagać **osobnej dopracowania jako samodzielny komponent UX** (np. accordion „Sklep” z listą kategorii w drawerze).
- Obecne rozwiązanie jest **ważne dla aktualnej struktury menu i aktualnej liczby punktów** — przy znaczącej zmianie treści lub liczby sekcji warto zweryfikować układ i ewentualnie minmax/kolumny.

---

## Out of scope

Świadomie **nie wchodzi** w zakres tego refaktoru (żeby później nie traktować tego jako „niedoróbki”):

- **Dynamiczne repositioning** względem viewport (np. panel „przesuwa się” w lewo, gdy brak miejsca z prawej).
- **Full touch-first megamenu** — osobny, zoptymalizowany pod dotyk panel/ekran na tablet/mobile.
- **Adaptive alignment oparty o container queries** (np. zmiana left/right w zależności od kontenera).
- **Automatyczna zmiana liczby kolumn** w zależności od faktycznej długości treści (obecnie stała siatka 3+3 z minmax).

---

## Definition of done / status

| Element | Status |
|--------|--------|
| Layout refactor (overlay panel, viewport width, 3+3 kolumny, kompozycja) | **done** |
| Viewport constraint (max-width, brak wyjeżdżania poza ekran) | **done** |
| Hover delay (400 ms open, 150 ms close, brak flicker) | **done** |
| Keyboard / focus / Escape (focus-within, Escape zamyka i zwraca focus na trigger) | **done** |
| Manual QA: 1280 px, zoom 110% / 125%, iPad, długie etykiety | **wymagane** (nie zautomatyzowane) |
