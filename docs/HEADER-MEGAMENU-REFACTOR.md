# Refaktor mega menu w headerze — brak łamania słów

**Data:** 2026-03-11  
**Plik:** `wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css`

---

## 1. Przyczyna problemu

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
