# HEADER UI FIXES — plan (header only)

**Scope:** Header state system, breakpoints, burger alignment, tablet search, no broken intermediate state, mega menu „Sklep” (layout, no scrollbar, no clipping, structured columns, remove stray „Sklep”, reduce visual weight).

**Fragility-refactor:** zakończony (stop point). Następny blok: tylko HEADER UI FIXES.

---

## 1. Header state system

| State | Width | Opis |
|-------|--------|------|
| **Desktop** | ≥1025px | Pełna nawigacja w jednej linii (Sklep + dropdown, Przewodnik, Dostawa, Kontakt); search jako inline input-group w headerze; brak burgera. |
| **Tablet + mobile** | ≤1024px | Jedna linia: logo | burger, search (ikona), account, cart. Menu główne w overlay/drawer (burger otwiera); search jako dropdown lub panel pod headerem. |

Stany UI:
- **Nav closed** — menu ukryte (display: none na .mnsk7-header__menu w tablet/mobile).
- **Nav open** — .mnsk7-header__nav.is-open → menu widoczne (flex).
- **Search closed** — search dropdown hidden lub panel hidden.
- **Search open** — search toggle aria-expanded=true, dropdown/panel widoczny.
- **Scrolled** — .mnsk7-header--scrolled (opcjonalnie mniejszy header).

Bez stanu „pół-desktop”: przy 1025px od razu pełny desktop; przy 1024px od razu pełny tablet (burger + ikony).

---

## 2. Breakpoints

| Breakpoint | Użycie |
|------------|--------|
| **1025px** | min-width: desktop (pełna nawigacja, search inline, burger ukryty). |
| **1024px** | max-width: tablet + mobile (burger, ikony, menu w overlay, search w dropdown). |
| **768px** | opcjonalnie: mniejsze paddingi / rozmiary dla „mobile” vs „tablet”; search panel pełna szerokość. |
| **430px, 360px** | już w 04-header: wąskie ekrany (mniejsze logo, gap). |

Używamy **jednego** przełączenia layoutu: **1025px** (desktop) vs **≤1024px** (tablet+mobile). Unikamy trzeciego stanu „między” (np. 769–1024 inaczej niż 768).

---

## 3. Pliki do zmiany

| Plik | Zmiany |
|------|--------|
| **wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css** | Breakpointy spójne 1025/1024; burger optical alignment; tablet search jako input-group; megamenu: brak horizontal scrollbar, brak clipping, kolumny, mniejszy visual weight; overflow/position tak, żeby logo i search nie były obcinane. |
| **wp-content/themes/mnsk7-storefront/header.php** | Tylko jeśli trzeba: filtr/usunięcie zbędnego „Sklep” z sekcji tagów (np. po nazwie), lub brak zmian jeśli filtr slug wystarczy. |
| **wp-content/themes/mnsk7-storefront/header.php** (critical inline) | Ewentualnie dopasowanie do tych samych breakpointów (1025/1024). |

Żadnych zmian w functions.php ani w innych partach CSS poza headerem, chyba że jedna zmienna (np. --header-h) w tokens.

---

## 4. Konkretne zadania

1. **Burger icon optical alignment** — wyśrodkować ikonę hamburger (22×18) w przycisku 44×44 (align-items: center, justify-content: center na .mnsk7-header__menu-toggle; ewentualnie drobna korekta marginesu na .mnsk7-header__hamburger).
2. **Tablet search as unified input-group** — na ≤1024px dropdown search: input + submit w jednej wizualnej grupie. W 04-header.css już: `.mnsk7-header__search-dropdown .mnsk7-header__search-form` (gap: 0), input border-right: none, submit border-left: none, border-radius łączone — jeden blok wizualny.
3. **No broken intermediate state** — sprawdzić przy 1024px i 1025px: brak podwójnego menu, brak dwóch pól search, burger tylko ≤1024px, search inline tylko ≥1025px.
4. **Mega menu „Sklep”:**
   - **No horizontal scrollbar** — overflow-x: hidden; max-width nie większe niż viewport; ewentualnie overflow-x: auto tylko gdy konieczne, z ukrytym scrollbarem na desktop.
   - **No clipping of logo/search** — dropdown max-width: min(560px, calc(100vw - 2rem)); left: 0; nie wychodzi w prawo; header__inner bez overflow: hidden na desktop (już overflow: visible od 1025px).
   - **Structured columns** — zachować 3 kolumny kategorii, 3 tagi; align-items: start; minmax(12em, 1fr) jeśli potrzeba zapobiec obcinaniu słów.
   - **Remove stray „Sklep” from materials/applications** — w header.php filtr tagów: wykluczyć slug === 'sklep' **oraz** name równy „Sklep” (jeśli występuje).
   - **Reduce visual weight** — mniejszy font linków (już var(--fs-xs)), mniejsze paddingi w dropdown, headingi sekcji mniej wyraziste (np. font-weight 600 zamiast 700, lub mniejszy font).

---

## 5. Kolejność

1. Plan (ten dokument) + aktualizacja Task 6 w handoff (zrobione).
2. Wdrożenie: 04-header.css (breakpoints, burger, search input-group, megamenu), ewentualnie header.php (filtr Sklep).
3. Weryfikacja wizualna: desktop 1025px+, tablet 768–1024px, mobile &lt;768px; brak scrollbara w megamenu, brak obcinania.
4. Commit.
