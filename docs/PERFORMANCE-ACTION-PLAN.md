# Performance — Action Plan (P1 / P2 / P3)

Krótki plan zadań wyciągnięty z [PERFORMANCE-AUDIT-14.md](PERFORMANCE-AUDIT-14.md). Nie rozszerzamy audytu — lista zadań z plikami, efektem i ryzykiem.

---

## P1 — największy wpływ na metryki

| # | Zadanie | Plik | Efekt | Ryzyko UX | Typ |
|---|---------|------|--------|-----------|-----|
| **1** | Nie ładować `wc-cart-fragments` na **home**. | `functions.php` | Mniej JS, mniej TBT na home. | Na home licznik w headerze nie odświeży się po add-to-cart (np. z innej karty) bez przeładowania. | quick win |
| **2** | Strategia podziału `main.css`: (a) co krytyczne dla home, (b) co dla archive, (c) czego nie ładować globalnie. | Doc + ewent. `functions.php` (enqueue) | Mniejszy blocking CSS, szybszy FCP/LCP. | Błędny podział = brak stylów na stronie. | medium effort |
| **3** | Na **mobile** nie renderować pełnego megamenu (get_terms + duży fragment DOM). Tylko link „Sklep”. | `header.php` | Mniej węzłów DOM, mniej zapytań DB na mobile. | Na mobile brak listy kategorii/tagów w menu; użytkownik idzie w „Sklep” i dopiero tam wybiera. | quick win |
| **4** | Ustalić Pass 1 jako baseline; w dokach wymienić, co z Pass 2/2b **nie** ma być bazą (np. synchroniczny runCritical). | `PERFORMANCE-STATUS.md`, `PERFORMANCE-PASS-2b.md` | Jasna punkt odniesienia, unikanie powrotu do złych wzorców. | Brak. | quick win |
| **5** | Osobny plan optymalizacji **archive LCP** jako problemu promo/header/text (TTFB, opóźnienie renderu, font). | Nowy doc lub § w PASS-2b | Cel: LCP archive w dół bez psucia TBT. | Zależne od konkretnych zmian. | medium effort |

---

## P2 — drugi etap

| # | Zadanie | Plik | Efekt | Ryzyko UX | Typ |
|---|---------|------|--------|-----------|-----|
| **6** | Conditional load `mnsk7-footer-accordion.js` (np. tylko gdy w footerze jest accordion). | `functions.php` | Mniej JS na stronach bez accordionu. | Jeśli warunek błędny — brak działania accordionu w footerze. | medium effort |
| **7** | Cache (transient) dla `get_terms` w headerze — kategorie/tagi megamenu. Inwalidacja przy zapisie/usunięciu termu. | `functions.php`, `header.php` | Mniej zapytań DB przy każdym request (desktop). | Stary cache po zmianie kategorii/tagów — clear transient. | quick win |
| **8** | Uproszczony mobile header / nav bez pełnego megamenu w DOM (spójne z P1.3). | `header.php` | Mniejszy DOM, szybszy parse. | Jak P1.3. | quick win |
| **9** | Rozbić duży inline init na małe niezależne kawałki (np. menu tylko, search tylko, cart tylko; każdy w osobnym wywołaniu rIC lub setTimeout). | `functions.php` | Krótsze pojedyncze zadania, niższy TBT. | Pierwszy klik w menu/search/cart może być opóźniony inaczej niż dziś. | heavy refactor |

---

## P3 — dopracowanie

| # | Zadanie | Plik | Efekt | Ryzyko UX | Typ |
|---|---------|------|--------|-----------|-----|
| **10** | Usunąć drobne reflow: shipping notice (scrollY już raz; OK), Instagram fallback — unikać offsetHeight w pętli. | `functions.php` (shortcode Instagram) | Mniej layout thrash. | Niewielkie. | quick win |
| **11** | Sprawdzić drugorzędne CSS pod kątem deferred / non-critical. | Doc + ewent. enqueue | Mniej blocking CSS. | Jak P1.2. | medium effort |
| **12** | Ustalić image priority (eager/fetchpriority) tylko tam, gdzie potwierdzą to pomiary (LCP element). | Szablony / shortcode | Unikanie niepotrzebnego priorytetu. | Słaby priorytet = gorszy LCP, jeśli to był LCP. | quick win |

---

## Legenda

- **quick win** — mała zmiana, niski nakład, mierzalny lub możliwy do zmierzenia efekt.
- **medium effort** — wymaga doprecyzowania (np. lista partów CSS) lub ostrożnego warunku (conditional load).
- **heavy refactor** — duża zmiana (np. rozbicie init), wymaga testów i pomiarów.

---

## Zależności

- P1.4 i P1.5 — dokumentacja; nie blokują kodu.
- P1.2 — strategia CSS; implementacja w kolejnym kroku.
- P1.1, P1.3, P2.7, P2.8 — wdrożone w tym passie (kod).
- P3.10 (shipping) — już zrobione (scrollY raz); Instagram — opcjonalnie.
