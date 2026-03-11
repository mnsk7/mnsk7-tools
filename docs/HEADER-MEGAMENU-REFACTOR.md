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
