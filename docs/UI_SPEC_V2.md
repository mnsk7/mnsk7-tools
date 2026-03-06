# UI SPEC V2 — mnsk7-tools.pl (staging)

Data: 2026-03-05  
Owner: `09_ui_designer`  
Zakres: tylko `theme/mu/custom docs/tasks` (bez zmian w core WP i zewnętrznych pluginach)

## 1) Cel biznesowy i zasady

- Cel: podnieść konwersję e-commerce przez czytelną hierarchię informacji, spójny wygląd i redukcję szumu.
- KPI UX: wyższy CTR z home do PLP/PDP, wyższy Add-to-Cart na PDP, niższy bounce na mobile.
- Priorytet: mobile-first, potem desktop.
- Standard dostępności: WCAG AA (kontrast, focus, tap targets, semantyka nagłówków).

## 2) Mobile-first layout (breakpointy)

- `xs`: 320-479 (bazowy projekt)
- `sm`: 480-767
- `md`: 768-1023
- `lg`: 1024-1279
- `xl`: 1280+

Zasady:
- Najpierw projekt dla jednej kolumny i krótkich bloków.
- Na `md+` dopuszczone 2-4 kolumny tylko tam, gdzie rośnie skanowalność (PLP, trust badges, footer).
- Sticky elementy tylko dla krytycznych akcji (header, CTA na PDP), bez nachalnych overlay.

## 3) Design tokens

### 3.1 Kolory

```css
:root {
  --color-bg: #0B0F14;
  --color-surface: #121821;
  --color-surface-2: #1A2230;
  --color-text: #EAF0F7;
  --color-text-muted: #A9B4C5;
  --color-border: #2A3445;

  --color-primary: #3B82F6;
  --color-primary-hover: #2563EB;
  --color-primary-pressed: #1D4ED8;
  --color-accent: #22C55E;
  --color-warning: #F59E0B;
  --color-danger: #EF4444;

  --color-focus: #93C5FD;
  --color-success-bg: #0F2A1C;
  --color-warning-bg: #2B210F;
  --color-danger-bg: #2A1114;
}
```

Wymagania:
- Tekst główny tylko na tle `--color-bg` lub `--color-surface`, nigdy ciemny tekst na ciemnym tle.
- Cena i CTA muszą mieć kontrast min. AA w każdym stanie.
- Jeden kolor akcentu CTA w całym sklepie (`--color-primary`).

### 3.2 Typografia

- Font bazowy: `Inter`, fallback `system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif`.
- Skala:
  - `--fs-12`: 12px (meta)
  - `--fs-14`: 14px (secondary)
  - `--fs-16`: 16px (body)
  - `--fs-18`: 18px (body-strong)
  - `--fs-20`: 20px (h4)
  - `--fs-24`: 24px (h3)
  - `--fs-30`: 30px (h2)
  - `--fs-38`: 38px (h1 desktop), 30px mobile
- Line-height:
  - body 1.5
  - heading 1.2-1.3

### 3.3 Spacing

- Skala odstępów: `4, 8, 12, 16, 24, 32, 40, 48, 64`.
- Zasada: odstępy pionowe sekcji minimum `32` na mobile i `48` na desktop.
- Grid kontenera:
  - mobile: padding poziomy 16
  - tablet: 24
  - desktop: max-width 1200 + padding 24

### 3.4 Radii i cienie

- Radius:
  - `--r-sm`: 8px
  - `--r-md`: 12px
  - `--r-lg`: 16px
  - `--r-pill`: 999px
- Shadow:
  - `--shadow-sm`: `0 2px 8px rgba(0,0,0,.25)`
  - `--shadow-md`: `0 6px 20px rgba(0,0,0,.30)`
- Karty i dropdowny: tylko jeden poziom cienia (`sm` lub `md`), bez mieszania wielu efektów.

## 4) Komponenty i stany

### 4.1 Przycisk

- Warianty: `primary`, `secondary`, `ghost`, `danger`.
- Rozmiary: `sm (36)`, `md (44)`, `lg (52)`.
- Stany: `default`, `hover`, `focus-visible`, `active`, `disabled`, `loading`.
- Tap target: min 44x44.

### 4.2 Pole input / search

- Jednolity styl dla search, qty, formularzy kontaktowych.
- Stany: `default`, `focus`, `error`, `success`, `disabled`.
- Error text pod polem (nie tylko kolor obramowania).

### 4.3 Product card (PLP/Home)

- Obowiązkowe elementy: foto 1:1, nazwa (2 linie max), cena, status stock, CTA.
- Opcjonalne: ocena + liczba opinii, badge promo/new.
- Stany: hover (desktop), pressed (mobile), out-of-stock.

### 4.4 PDP buy box

- Elementy w kolejności: tytuł -> cena -> warianty -> stock/trust -> qty + CTA.
- Sticky mini buy bar na mobile po scrollu (nazwa skrócona + cena + CTA).
- Stany CTA: aktywne / brak na stanie / loading add-to-cart / success feedback.

### 4.5 Nawigacja

- Header desktop: logo | wyszukiwarka | konto/koszyk + menu główne pod spodem.
- Header mobile: logo + ikony (search/account/cart) + burger.
- Menu bez duplikatów i bez zdublowanych etykiet językowych/diakrytycznych.

### 4.6 Informacyjne komponenty trust

- Badge: dostawa, zwroty, bezpieczna płatność.
- Alert: sukces / warning / error.
- Accordion: FAQ, dostawa i płatności, kontakt.

## 5) UX hierarchia konwersji (end-to-end)

1. **Wejście (Home/PLP)**: od razu pokazać kategorię i produkty, nie "ścianę" contentu.
2. **Wybór produktu (PLP)**: szybkie skanowanie kart -> czytelna cena i dostępność.
3. **Decyzja (PDP)**: cena + CTA + trust above-the-fold; specyfikacja i dłuższy content niżej.
4. **Zaufanie**: jasne linki do Kontakt, Dostawa i płatności, zwroty/pomoc.
5. **Działanie**: minimalna liczba konkurencyjnych CTA na ekranie (jeden główny cel).

## 6) Specyfikacja ekranów

## 6.1 Header

Wymagania:
- Usunąć staging-like belki i techniczne komunikaty z widoku usera.
- Jedna warstwa utility max (np. darmowa dostawa od X), nie więcej.
- Search prominent na mobile i desktop.
- Koszyk zawsze widoczny; licznik produktów jako badge.
- Sticky header zredukowany przy scrollu (mniejsza wysokość, zachowana czytelność).

Kryteria:
- Brak duplikatów pozycji menu.
- CTA "Sklep" i "Kontakt" łatwo dostępne w 1 tap.

## 6.2 Home

Sekcje w kolejności:
1) Hero: krótka wartość + 1 CTA do sklepu.  
2) Kafelki kategorii (max 6).  
3) Produkty polecane / bestsellery (1 dominująca sekcja produktowa).  
4) Trust (3-4 badge).  
5) Lojalność (krótki blok + CTA "Zobacz progi").  
6) Instagram (1 rząd).  
7) Footer.

Zasady:
- Jedna dominująca ścieżka kliknięcia do PLP/PDP.
- Ograniczyć ilość tekstu marketingowego nad foldem.

## 6.3 PLP (Product Listing Page)

Wymagania:
- Nad listą: breadcrumb + H1 + krótki opis kategorii (opcjonalnie).
- Toolbar: sortowanie + filtry (drawer na mobile, sidebar na desktop).
- Karty produktów:
  - mobile: 2 kolumny (gdy szerokość pozwala), inaczej 1 kolumna
  - desktop: 3-4 kolumny
- Szybkie informacje: cena, dostępność, wariant podstawowy.
- Paginacja lub "Załaduj więcej" (spójnie w całym sklepie).

## 6.4 PDP (Product Detail Page)

Above-the-fold:
- Galeria produktu.
- Buy box: nazwa, cena, VAT/info B2B, warianty, dostępność, CTA.
- Krótkie trust pointy (dostawa, płatności, kontakt).

Below-the-fold:
- Opis i specyfikacja (accordion/taby).
- Opinie/social proof.
- Produkty powiązane (max 1 sekcja).

Zasady:
- Jeden główny CTA "Dodaj do koszyka".
- Usuń konkurencyjne akcje równorzędne z ATC.

## 6.5 Footer

Kolumny:
- Sklep (link główny + kluczowe kategorie)
- Pomoc (Dostawa i płatności, zwroty, FAQ)
- Firma (O nas, Kontakt, regulaminy)
- Kontakt (tel, email, godziny, social)

Wymagania:
- Bez powielania całych list kategorii i bez dubli linków.
- Linki pomocnicze czytelne, krótkie, logicznie pogrupowane.

## 6.6 Kontakt

Struktura:
- H1 + krótka instrukcja kontaktu.
- Karty kanałów (telefon, email, IG) + godziny.
- Formularz kontaktowy (krótki, 4-6 pól max).
- FAQ mini (opcjonalnie, 3 pytania).

UX:
- Na mobile kanały kontaktu jako first-class action (tap-to-call, mailto).
- Jasna informacja o czasie odpowiedzi.

## 6.7 Dostawa i płatności

Struktura:
- H1 + data aktualizacji.
- Tabela metod dostawy (czas, koszt, warunki free shipping).
- Tabela płatności (BLIK/karta/przelew/pobranie jeśli dotyczy).
- Sekcja "Najczęstsze pytania" (accordion).

UX:
- Jedna, spójna nazwa strony i jeden link w menu.
- Tabela responsywna: na mobile jako karty/stack, nie overflow bez kontroli.

## 7) Co usunąć / uprościć z obecnego UI

1. Duplikaty pozycji menu i stron pomocniczych (np. warianty tej samej etykiety).  
2. Nadmiar sekcji na home, które nie prowadzą do produktu/zakupu.  
3. Ciemny tekst na ciemnym tle i niespójne style nagłówków/cen.  
4. Rozproszone CTA o równym priorytecie na PDP.  
5. Dodatkowe, wizualnie konkurujące bloki w footer/header bez wartości konwersyjnej.  
6. Różne style komponentów tego samego typu (button/input/card) w różnych sekcjach.

## 8) Definition of Done (dla 09_ui_designer)

- Dostarczone makiety low/high-fi dla: Header, Home, PLP, PDP, Footer, Kontakt, Dostawa i płatności.
- Spis tokenów gotowy do implementacji w CSS variables.
- Komponenty mają opisane stany i zachowanie mobile/desktop.
- Każdy ekran ma wskazany główny CTA i hierarchię informacji.
- Checklista WCAG AA (kontrast, focus, tap targets) w specyfikacji.

## 9) Handoff do 05/04

- `09_ui_designer` przekazuje:
  - spec komponentów + layoutów,
  - mapę sekcji do usunięcia,
  - priorytet wdrożenia: P0 (header/home/PLP/PDP), P1 (footer/kontakt/dostawa).
- `05_theme_ux_frontend` implementuje warstwę UI w theme.
- `04_woo_engineer` dopina logikę PDP/PLP i zgodność z WooCommerce.
