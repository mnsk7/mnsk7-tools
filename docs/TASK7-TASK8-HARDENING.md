# Task 7 & Task 8 — wykonane (hardening)

**Źródło:** HANDOFF-LAYOUT-STATE-REFACTOR.md (Deferred backlog).  
**Data:** 2026-03-11

---

## Task 7: Redukcja !important i display:none

### Wykonane

- **04-header.css:** Usunięto `!important` z `text-decoration: none` we wszystkich linkach headera (brand, menu, megamenu, search, account, cart). Selektorów nie zmieniano na wyższą specyficzność — w naszej temie Storefront nie targetuje `.mnsk7-header__*`, więc zwykła reguła wygrywa.
- **24-plp-table.css:** Usunięto `!important` z `text-decoration: none` w linkach tabeli PLP (komórka tytułu, przyciski).
- **05-plp-cards.css:** Usunięto `!important` z `text-decoration: none` i `border-bottom: none` w linkach kart produktów.

### Pozostawione celowo (bez zmian)

- **25-global-layout.css, 09-footer.css:** `!important` przy kolorach tła (footer, header) — nadpisanie Woo/Storefront (kolejność ładowania).
- **25-global-layout.css:** clearfix `ul.products::before` (content/display: none), desktop menu/search (display: none / flex), PLP trust (display: flex), przyciski (border-radius) — nadpisanie WC.
- **04-header.php critical, 04-header.css:** `display: none` / `display: flex` dla burgera i search na desktop/tablet — wymagane dla responsive (jedna rozdzielczość = jeden layout).
- **24-plp-table.css:** `display: none !important` na sidebarze i filtrach Woo na PLP — celowe ukrycie (layout pełnoszerokości); zmiana wymagałaby nierenderowania w PHP.
- **functions.php inline (woocommerce-layout):** clearfix `ul.products::before` — celowa duplikacja, żeby wygrać z woocommerce-layout.css.

### display:none — kiedy zostawione

| Miejsce | Powód |
|--------|--------|
| `ul.products::before` | WooCommerce dodaje pseudo-element; bez ukrycia psułby grid. Nie da się usunąć w HTML. |
| Burger / search toggle (desktop) | Responsywność: ≥1025px pokazujemy inline search i pełne menu, &lt;1025px — ikony. Standardowy wzorzec. |
| Menu główne (tablet/mobile) | Drawer: domyślnie ukryte, .is-open pokazuje. Bez zmiany HTML/JS — zostawione. |
| Sidebar/filtry na PLP | Pełna szerokość listingu; sidebar Woo nie jest renderowany w naszym layout. Ukrycie w CSS — świadomy wybór. |
| Cookie bar `[hidden]` | Stan UI (zaakceptowane/odrzucone). |
| 11-hidden.css (utility) | Klasy .screen-reader-text itd. — a11y. |

---

## Task 8: Overflow — tylko tam, gdzie potrzebne

### Zasady

- **Zostawione z komentarzem Task 8:** obcięcie do border-radius, brak poziomego scrollbara (megamenu, body na mobile), text-overflow: ellipsis, skip-link (a11y), ograniczenie rozlania w komórce grid (megamenu li).
- **Nie usuwano:** żadnego overflow, który już miał uzasadnienie (border-radius, ellipsis, a11y). Nie znaleziono overflowu, który wyraźnie „maskował” błąd layoutu — w razie wątpliwości dodano komentarz.

### Zmiany

- **04-header.css:** Dodano komentarze Task 8 przy `overflow-x: hidden` (megamenu), `overflow: hidden` (megamenu li, .mnsk7-header__inner na tablet).
- **02-reset-typography.css:** Komentarz Task 8 przy `html { overflow-x: hidden }` (zapobieganie poziomemu przewijaniu).
- **24-plp-table.css, 05-plp-cards.css, 06-single-product.css, 07-mnsk7-blocks.css:** Komentarze Task 8 już były lub doprecyzowano (overflow przy border-radius / line-clamp).

### Pozostawione bez zmian (uzasadnione)

- **21-responsive-mobile.css:** `overflow-x: hidden` na body/#page/#content przy max-width: 768px — zapobieganie przewijaniu w poziomie na wąskich ekranach.
- **02-reset-typography.css:** skip-link — overflow: hidden + clip (wzorzec a11y).
- Wszystkie overflow przy kartach/ramkach z border-radius — obcięcie zawartości do zaokrąglenia.

---

## Pliki zmienione

| Plik | Task 7 | Task 8 |
|------|--------|--------|
| assets/css/parts/04-header.css | text-decoration: none bez !important (9 miejsc) | Komentarze przy overflow (megamenu, inner) |
| assets/css/parts/24-plp-table.css | text-decoration: none bez !important (3) | Doprecyzowany komentarz |
| assets/css/parts/05-plp-cards.css | text-decoration/border-bottom bez !important (4) | — |
| assets/css/parts/02-reset-typography.css | — | Komentarz przy html overflow-x |

---

## Kryteria przyjęcia (HANDOFF)

- **Task 7:** Mniej reguł z !important (wykonane w headerze, PLP table, PLP cards). display:none pozostawione tam, gdzie konieczne (clearfix, responsive, drawer, sidebar PLP) — udokumentowane powyżej.
- **Task 8:** Brak usuniętych „nieuzasadnionych” overflow (nie znaleziono oczywistych przypadków maskowania). Pozostałe overflow opisane komentarzem Task 8 lub już wcześniej.
