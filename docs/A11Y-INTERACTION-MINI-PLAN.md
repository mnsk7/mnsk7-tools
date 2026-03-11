# A11Y + Interaction consistency — mini-plan i backlog

**Cel:** Doprowadzić temat mnsk7-storefront do podstawowego, współczesnego poziomu dostępności i przewidywalnego UX na desktop + mobile, w szczególności w ścieżce WooCommerce (przeglądanie → koszyk → checkout → konto).

**Zakres:** header/nawigacja, search, mega menu/dropdowny, breadcrumbs, filtry/chips/sortowanie, karty produktów, PDP, koszyk, checkout, my account/login/rejestracja, footer, przyciski/linki/formularze/komunikaty/modale.

**Uwaga:** Dokument niezależny od page-top spacing i archive vertical rhythm (osobne plany).

---

## 1. Audit summary (krótkie podsumowanie)

Audyt oparto na przeglądzie kodu theme (header.php, footer.php, woocommerce/*, functions.php, CSS parts) oraz wymaganiach WCAG 2.1 Level A/AA i spójności interakcji.

### Co działa dobrze

- **Landmarki:** `<header role="banner">`, `<nav aria-label="Menu główne">`, `<footer role="contentinfo">`, `<main role="main">` w wrapper-start, cookie bar `role="dialog"` + `aria-label`.
- **ARIA w headerze:** menu toggle i search toggle mają `aria-expanded`, `aria-controls`, `aria-label`; link do koszyka ma `aria-label="Koszyk"`.
- **Formularze w theme:** search (header + panel mobile) i newsletter mają `<label class="screen-reader-text">` i powiązane `for`/`id`; placeholder nie jest jedyną etykietą.
- **Focus visible:** w większości interaktywnych elementów jest `outline: none` na `:focus` i wyraźny outline na `:focus-visible` (header, footer, cookie bar, PLP chips, przyciski tabeli).
- **Tap targets:** min 44×44px dla kluczowych przycisków (header, cookie bar, PLP, touch-targets.css, przyciski WooCommerce).
- **Footer:** accordiony z `aria-expanded`, `aria-controls`, `role="region"`, `aria-labelledby`; JS synchronizuje `aria-expanded` przy resize.
- **Ikonki:** ikony w headerze (search, konto, koszyk) mają `aria-hidden="true"` i tekst/aria-label na triggerze.
- **Reset typografii:** globalne `:focus` (outline none) + `:focus-visible` (outline 2px) dla przycisków, inputów, linków, `[tabindex]`.
- **Ilość (quantity):** override quantity-input ma etykietę (screen-reader) i `aria-label` na inputcie.
- **PDP sticky CTA:** przycisk ma `aria-label` z kontekstem (przewiń do formularza).
- **Empty state PLP:** kontener ma `role="status"`.
- **Breadcrumbs:** `<nav aria-label="Nawigacja okruszków">`, separator `aria-hidden="true"`.

### Główne luki

| Obszar | Problem | Wpływ |
|--------|---------|--------|
| Skip link | Klasa `.mnsk7-skip-link` jest w CSS, ale **link nie jest nigdzie wyświetlany** (usunięto `storefront_skip_links`, theme nie dodaje własnego). | Użytkownik klawiatury nie może od razu przejść do treści głównej. |
| Cart dropdown | Trigger (link „Koszyk”) **nie ma `aria-expanded` ani `aria-controls`**; stan otwarcia nie jest ogłaszany. | SR nie wie, czy dropdown jest otwarty. |
| Menu „Sklep” (megamenu) | Link nad submenu **w statycznym HTML nie ma `aria-expanded`/`aria-haspopup`**; JS ustawia `aria-expanded` tylko przy hover. Przy nawigacji klawiaturową submenu pokazuje `:focus-within`, ale SR może nie ogłaszać rozwijania. | Niespójna informacja dla SR. |
| PLP chips toggle | Używane jest **`data-controls`**, w HTML brak **`aria-controls`**. JS nie ustawia `aria-controls`. | SR nie łączy przycisku „Więcej”/„Więcej filtrów” z rozwijaną treścią. |
| Breadcrumb ostatni element | W `woocommerce/global/breadcrumb.php` ostatni element (tekst, bez linku) **nie ma `aria-current="page"`**. | SR nie ogłasza bieżącej strony w nawigacji okruszkowej. |
| Menu / Search toggle | **Etykiety nie zmieniają się** przy otwarciu (zawsze „Otwórz menu”, „Szukaj”). | Gorsza zrozumiałość dla SR („Zamknij menu” gdy otwarte). |
| Cart link (SR) | `aria-label="Koszyk"` bez informacji o liczbie pozycji. | Można rozszerzyć do „Koszyk, X pozycji” (nice to have). |

### 3.1. Customer flow — co sprawdzić na każdym kroku

| Krok | Scenariusz | Tab order | Focus visible | Hover/focus/active | Błędy formularzy | Przyciski/linki | Mobile tap |
|------|------------|-----------|---------------|--------------------|------------------|-----------------|------------|
| Wejście | Strona główna/sklep | Brak skip linku → pierwszy Tab = logo lub menu (P1) | OK (focus-visible w theme) | OK | — | OK | OK (44px) |
| Otwarcie menu | Klik/Tab na menu toggle | Tab na toggle → lista linków | OK | Toggle bez zmiany etykiety przy otwarciu (P2) | — | Etykieta „Otwórz menu” stale (P2) | OK |
| Kategoria z megamenu | Hover/Tab na „Sklep” → submenu | Focus-within pokazuje submenu | OK | aria-expanded tylko przy hover, nie przy focus (P2) | — | Link bez aria-haspopup w HTML (P2) | — |
| Search | Toggle search (mobile/desktop) | Panel/formularz | OK | Etykieta bez „Zamknij” (P2) | Label + id OK | OK | OK |
| Filtry / chips (PLP) | Chip, „Więcej”, „Więcej filtrów” | Kolejność logiczna | OK (focus-visible) | — | — | Brak aria-controls (P2) | 44px OK |
| Karta / tabela produktu | Link do PDP, qty, „Dodaj do koszyka” | OK | OK | OK | — | aria-label na qty (OK) | OK |
| PDP | Tytuł, cena, qty, CTA, sticky CTA | OK | OK | OK | — | Sticky ma aria-label (OK) | OK |
| Koszyk (header) | Klik na koszyk → dropdown | Trigger bez aria-expanded (P2) | OK | — | — | aria-label bez liczby (P3) | OK |
| Cart page | Tabela, kupon, „Przejdź do płatności” | Zależne od WC | Zależne od WC | — | Weryfikacja WC (P1) | Link/button OK | OK |
| Checkout | Pola, Place order | Zależne od WC | Zależne od WC | — | Etykiety i błędy WC (P1) | — | OK |
| My account / login | Formularze WC | Zależne od WC | OK | — | Weryfikacja WC | Theme: h1 (OK) | OK |
| Footer | Accordiony, newsletter | OK, aria OK | OK | OK | Label + required OK | OK | 44px OK |
| Cookie bar | Akceptuj / Odrzuć | Brak przeniesienia focus (P3) | OK | — | — | OK | OK |

---

### Nie objęte audytem (WooCommerce core)

- Szablony cart/checkout (brak override w theme) — etykiety pól, komunikaty błędów i `aria-invalid`/`aria-describedby` zależą od WooCommerce; warto przetestować ręcznie.
- My account / login / rejestracja — głównie WooCommerce; theme dodaje nagłówek h1 (functions.php).

---

## 2. Tabela problemów według priorytetów

| ID | Priorytet | Obszar | Problem | Kryterium sukcesu |
|----|-----------|--------|---------|-------------------|
| A1 | **P1** | Global | Brak skip linku do treści głównej | Skip link w DOM (np. po `<body>`), widoczny tylko przy :focus, prowadzi do `#main` lub `#content`; pierwszy Tab przenosi na niego. |
| A2 | **P1** | Formularze (WC) | W theme: brak override dla błędów/required w checkout/cart — jeśli WC nie łączy błędów z polami (`aria-describedby`, `aria-invalid`), formularze mogą być niespełniające | Weryfikacja: pola wymagane mają etykiety; błędy są powiązane z polami (test ręczny + axe). |
| A3 | **P2** | Header – koszyk | Cart trigger bez `aria-expanded` / `aria-controls` przy otwartym dropdownie | Trigger ma `aria-expanded` i `aria-controls="id-dropdown"`; JS ustawia `aria-expanded="true/false"` przy otwarciu/zamknięciu. |
| A4 | **P2** | Header – menu Sklep | Link „Sklep” (menu-item-has-children) bez `aria-expanded`/`aria-haspopup` w HTML; brak synchronizacji z klawiaturą (focus) | W HTML: `aria-haspopup="true"`, `aria-expanded`; JS: ustawiać `aria-expanded` przy focus/blur (lub focus-within) na li, nie tylko przy hover. |
| A5 | **P2** | PLP – chips | Przyciski „Więcej” / „Więcej filtrów” mają `data-controls`, brak `aria-controls` w atrybucie | W template: `aria-controls="mnsk7-plp-more-..."` (ten sam id co w `data-controls`). Opcjonalnie: `aria-expanded` już jest. |
| A6 | **P2** | Breadcrumbs | Ostatni element (bieżąca strona) bez `aria-current="page"` | Gdy ostatni crumb to tekst (bez linku), opakować w `<span aria-current="page">` lub dodać atrybut do istniejącego elementu. |
| A7 | **P2** | Header – menu / search | Stała etykieta przycisku menu/search niezależnie od stanu | Menu toggle: gdy `aria-expanded="true"`, np. „Zamknij menu”; search analogicznie (np. „Zamknij wyszukiwanie”). |
| A8 | **P3** | Header – koszyk | Brak liczby pozycji w accessible name | Np. `aria-label="Koszyk, X pozycji"` (dynamicznie z PHP/JS). |
| A9 | **P3** | Cookie bar | Przy pierwszym wejściu focus nie jest przenoszony do paska | Opcjonalnie: gdy bar się pokaże, focus do pierwszego przycisku („Akceptuję wszystkie”) — poprawia flow dla SR. |
| A10 | **P3** | Ogólne | Spójność hover/focus/active na przyciskach i linkach w całym flow | Jednolity styl :focus-visible (już w dużej mierze); brak „znikającego” outline tylko na :focus (już zabezpieczone). |

---

## 3. Backlog zadań (dekompozycja)

### Task 1: Skip link

| Pole | Wartość |
|------|--------|
| **Tytuł** | Dodać skip link do treści głównej |
| **Problem** | Użytkownik nawigujący klawiaturą nie może pominąć nagłówka/nawigacji; pierwszy Tab powinien umożliwić skok do main content. |
| **Pliki** | `header.php` (wstawić link na początku `<div id="page">` lub tuż po `wp_body_open()`), ewentualnie `functions.php` jeśli skip ma być w hooku. |
| **Oczekiwane zachowanie** | Link w DOM, ukryty wizualnie (klasa `.mnsk7-skip-link` już w `02-reset-typography.css`), widoczny przy :focus. Klik/Enter przenosi do `#main` lub `#content`. |
| **Kryteria akceptacji** | (1) Tab z body przenosi focus na skip link; (2) po aktywacji focus jest na main/content; (3) link ma czytelny tekst (np. „Przejdź do treści”); (4) outline widoczny tylko przy :focus-visible. |

---

### Task 2: Cart dropdown – ARIA (aria-expanded, aria-controls)

| Pole | Wartość |
|------|--------|
| **Tytuł** | Dodać aria-expanded i aria-controls do triggera koszyka w headerze |
| **Problem** | Screen reader nie ogłasza, czy dropdown koszyka jest otwarty; brak powiązania trigger–panel. |
| **Pliki** | `header.php` (atrybuty na elemencie `.mnsk7-header__cart-trigger` lub opakowującym), `functions.php` (JS: ustawianie `aria-expanded` przy otwarciu/zamknięciu dropdownu). |
| **Oczekiwane zachowanie** | Trigger ma `aria-expanded="false"` domyślnie i `aria-controls="id-cart-dropdown"`; dropdown ma stałe `id`; przy otwarciu/zamknięciu (click, Escape, click outside) JS ustawia `aria-expanded="true"`/`"false"`. |
| **Kryteria akceptacji** | (1) W DOM trigger ma aria-controls i aria-expanded; (2) po otwarciu dropdownu aria-expanded="true"; (3) po zamknięciu aria-expanded="false"; (4) Escape zamyka i zwraca focus na trigger. |

---

### Task 3: Menu „Sklep” (megamenu) – aria-haspopup, aria-expanded, sync z klawiatury

| Pole | Wartość |
|------|--------|
| **Tytuł** | Uzupełnić ARIA i synchronizację stanu megamenu z focusem klawiatury |
| **Problem** | Link „Sklep” nie ma aria-haspopup/aria-expanded w HTML; aria-expanded jest ustawiane tylko przy hover; przy nawigacji Tab submenu jest widoczne (:focus-within), ale SR może nie wiedzieć, że jest „rozwarte”. |
| **Pliki** | `header.php` (dodać `aria-haspopup="true"` i początkowo `aria-expanded="false"` na `<a>` w li.menu-item-has-children), `functions.php` (JS: przy focus/blur na li lub submenu aktualizować aria-expanded na linku). |
| **Oczekiwane zachowanie** | Link ma aria-haspopup="true" i aria-expanded; przy focus (w tym Tab do linku lub do elementu w submenu) aria-expanded="true"; przy blur z całego li – "false". |
| **Kryteria akceptacji** | (1) W statycznym HTML link ma aria-haspopup i aria-expanded; (2) po Tab na „Sklep” lub do submenu SR ogłasza rozwarte menu; (3) po wyjściu focusem aria-expanded="false"; (4) Escape zamyka submenu i focus na linku (już jest w kodzie). |

---

### Task 4: PLP – aria-controls na przyciskach „Więcej” / „Więcej filtrów”

| Pole | Wartość |
|------|--------|
| **Tytuł** | Dodać atrybut aria-controls do mnsk7-plp-chips-toggle |
| **Problem** | Przyciski używają tylko `data-controls`; screen reader nie wiąże przycisku z rozwijanym blokiem. |
| **Pliki** | `woocommerce/archive-product.php` (w obu miejscach: przycisk „Więcej” w wierszu filtra oraz „Więcej filtrów”). |
| **Oczekiwane zachowanie** | Każdy przycisk ma `aria-controls="mnsk7-plp-more-..."` z tym samym id co docelowy blok (już używany w data-controls). |
| **Kryteria akceptacji** | (1) W HTML każdy .mnsk7-plp-chips-toggle ma aria-controls wskazujące na istniejący id; (2) aria-expanded pozostaje zarządzane przez istniejący JS. |

---

### Task 5: Breadcrumb – aria-current na bieżącej stronie

| Pole | Wartość |
|------|--------|
| **Tytuł** | Oznaczyć bieżącą stronę w breadcrumb atrybutem aria-current="page" |
| **Problem** | Ostatni element breadcrumb (gdy nie jest linkiem) nie jest oznaczony jako bieżąca strona. |
| **Pliki** | `woocommerce/global/breadcrumb.php`. |
| **Oczekiwane zachowanie** | Gdy ostatni crumb to zwykły tekst (bez URL), opakować go w element z `aria-current="page"` (np. `<span aria-current="page">`). |
| **Kryteria akceptacji** | (1) Ostatni element listy breadcrumb (tekst) ma aria-current="page"; (2) linki w breadcrumb bez aria-current; (3) nie łamać istniejącego wyglądu. |

---

### Task 6: Dynamiczne etykiety przycisku menu i search (SR)

| Pole | Wartość |
|------|--------|
| **Tytuł** | Zmieniać aria-label przycisku menu i search w zależności od stanu (otwarty/zamknięty) |
| **Problem** | Przycisk menu zawsze ma „Otwórz menu”, przycisk search „Szukaj” — przy otwartym panelu lepiej ogłaszać „Zamknij menu” / „Zamknij wyszukiwanie”. |
| **Pliki** | `header.php` (opcjonalnie data-atrybuty z tekstami), `functions.php` (JS: przy toggle ustawiać `aria-label` lub `aria-label` przez setAttribute w zależności od aria-expanded). |
| **Oczekiwane zachowanie** | Gdy menu otwarte: aria-label np. „Zamknij menu”; gdy zamknięte: „Otwórz menu”. Analogicznie dla search. |
| **Kryteria akceptacji** | (1) Po otwarciu menu aria-label zmienia się na „Zamknij menu”; (2) po zamknięciu wraca do „Otwórz menu”; (3) to samo dla search (np. „Zamknij wyszukiwanie” / „Szukaj”). |

---

### Task 7: Weryfikacja formularzy WooCommerce (cart, checkout) – etykiety i błędy

| Pole | Wartość |
|------|--------|
| **Tytuł** | Zweryfikować etykiety, required i powiązanie błędów z polami (cart/checkout) |
| **Problem** | Theme nie nadpisuje szablonów formularzy cart/checkout; trzeba potwierdzić, że pola mają etykiety i że błędy są dostępne (np. aria-describedby, aria-invalid). |
| **Pliki** | Brak override — weryfikacja ręczna + ewentualnie małe poprawki w CSS (np. nie ukrywać .required) lub w mu-plugin/child theme, jeśli WC nie spełnia wymagań. |
| **Oczekiwane zachowanie** | Wszystkie pola formularza mają widoczne lub powiązane etykiety; pola wymagane są oznaczone; komunikaty błędów powiązane z polami. |
| **Kryteria akceptacji** | (1) axe (lub równorzędny) nie zgłasza brakujących etykiet na checkout/cart; (2) przy błędzie walidacji focus/ogłoszenie wskazuje pole i błąd; (3) przyciski (Place order, Update cart) mają dostępne nazwy. |

---

### Task 8 (P3): Cart – accessible name z liczbą pozycji

| Pole | Wartość |
|------|--------|
| **Tytuł** | Rozszerzyć aria-label linku koszyka o liczbę pozycji |
| **Problem** | Obecnie „Koszyk”; lepiej „Koszyk, 3 pozycje” (lub ekwiwalent). |
| **Pliki** | `header.php` (PHP: generować aria-label z liczbą z WC()->cart), ewentualnie `functions.php` jeśli fragment jest w funkcji. |
| **Kryteria akceptacji** | (1) Gdy koszyk pusty: np. „Koszyk”; (2) gdy niepusty: np. „Koszyk, N pozycji” (z odpowiednią odmianą); (3) ikona/count wizualnie bez zmian. |

---

### Task 9 (P3): Cookie bar – opcjonalne przeniesienie focusu

| Pole | Wartość |
|------|--------|
| **Tytuł** | Przy pierwszym wyświetleniu cookie bar przenieść focus do pierwszego przycisku |
| **Problem** | Użytkownik SR może nie od razu trafić do akcji. |
| **Pliki** | `footer.php` (inline script: po show() ustawić focus na .mnsk7-cookie-bar-accept). |
| **Kryteria akceptacji** | (1) Gdy bar się pokaże, focus na „Akceptuję wszystkie”; (2) nie blokować możliwości Tab (brak focus trap — bar nie jest modalem pełnoekranowym). |

---

## 4. Mini-plan wdrożenia po fazach

### Phase 1 — Klawiatura, focus, formularze (must-fix)

- **Zakres:** Skip link (A1); weryfikacja formularzy WC cart/checkout (A2) i ewentualne minimalne poprawki.
- **Deliverables:**  
  - Skip link w headerze, działający z Tab i Enter.  
  - Krótki raport z testu cart/checkout (axe + ręcznie): etykiety, required, błędy.
- **Kryteria zakończenia fazy:**  
  - Tab od początku strony prowadzi do skip linku; aktywacja przenosi do treści.  
  - Formularze cart/checkout nie mają krytycznych naruszeń etykiet/powiązań błędów (lista ewentualnych poprawek w raporcie).

---

### Phase 2 — Nawigacja, filtry, koszyk, konto

- **Zakres:** Cart dropdown ARIA (A3); megamenu Sklep – ARIA i sync z focusem (A4); PLP aria-controls (A5); breadcrumb aria-current (A6); dynamiczne etykiety menu/search (A7).
- **Deliverables:**  
  - Wszystkie zadania z backlogu: Task 2–6.  
  - Spójne zachowanie: Tab przez header → menu → search → konto → koszyk; Escape zamyka dropdowny i zwraca focus.
- **Kryteria zakończenia fazy:**  
  - Cart trigger ma aria-expanded i aria-controls; stan się aktualizuje.  
  - Link „Sklep” ma aria-haspopup i aria-expanded zsynchronizowane z focusem; Escape zamyka submenu.  
  - Przyciski „Więcej”/„Więcej filtrów” mają aria-controls.  
  - Ostatni element breadcrumb ma aria-current="page".  
  - Etykiety przycisków menu i search zmieniają się przy otwarciu/zamknięciu.

---

### Phase 3 — Semantyka, ARIA, dopracowanie

- **Zakres:** Task 8 (aria-label koszyka z liczbą); Task 9 (focus w cookie bar); przegląd spójności hover/focus/active (A10).
- **Deliverables:**  
  - Ulepszone accessible name dla koszyka; opcjonalnie focus w cookie bar.  
  - Krótka checklista: przyciski/linki w flow (header → PLP → PDP → cart → checkout) mają widoczny :focus-visible i spójny styl.
- **Kryteria zakończenia fazy:**  
  - Koszyk ogłaszany z liczbą pozycji (jeśli wdrożone).  
  - Cookie bar przy pierwszym wejściu przenosi focus (jeśli wdrożone).  
  - Brak usuniętego outline bez zamiennika :focus-visible w kluczowych kontrolkach.

---

### Phase 4 — Regression QA (desktop + mobile)

- **Zakres:** Pełne przejście customer flow z klawiatury i z screen readerem (NVDA/VoiceOver); testy na urządzeniu mobilnym (tap targets, focus po otwarciu menu/search).
- **Scenariusze:**  
  - Wejście na stronę → Tab (skip link) → wejście w menu → wybór kategorii (megamenu) → PLP → filtry/chips → „Więcej filtrów” → otwarcie produktu → dodanie do koszyka → koszyk → checkout; osobno: logowanie / my account.  
  - Dla każdego kroku: kolejność Tab, widoczność focus, stan hover/focus/active, błędy formularzy, zrozumiałość przycisków; na mobile: wielkość obszaru dotyku i brak nakładania się.
- **Kryteria zakończenia fazy:**  
  - Żadnego P1 nie pozostaje otwarte.  
  - Zidentyfikowane P2 są naprawione lub udokumentowane jako zaplanowane.  
  - Lista testów (scenariusze + wynik) zapisana w docs (np. A11Y-REGression-QA.md).

---

## 5. Acceptance criteria dla faz (zbiorczo)

| Faza | Główne AC |
|------|-----------|
| **Phase 1** | Skip link obecny i działający; raport z testu formularzy WC (cart/checkout) z wnioskami. |
| **Phase 2** | Cart, megamenu, PLP toggles, breadcrumb i etykiety menu/search spełniają opisane w backlogu kryteria (ARIA, sync, accessible names). |
| **Phase 3** | Koszyk (i opcjonalnie cookie bar) ulepszone pod kątem a11y; brak regresji focus visible. |
| **Phase 4** | Customer flow przejście z klawiatury i SR bez blokerów; lista testów i wyników udokumentowana. |

---

## 6. Zależności i uwagi

- **Nie mieszamy** z zadaniami page-top spacing ani archive vertical rhythm — osobne pliki/commity.
- **Nie wymyślamy** naruszeń WCAG bez sprawdzenia w kodzie lub w przeglądarce; każdy finding w tabeli odnosi się do konkretnego miejsca w theme.
- **Must-fix:** P1 (skip link, formularze). **Nice to have:** P3 (liczba w koszyku, focus w cookie bar).
- Szablony cart/checkout i my account pozostają w WooCommerce; theme tylko weryfikuje i ewentualnie uzupełnia (np. przez filtry/hooki), nie duplikuje całych formularzy.
