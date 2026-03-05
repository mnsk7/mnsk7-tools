# WOO Conversion Rework Plan (PDP / PLP / Checkout)

Data: 2026-03-05  
Rola: `04_woo_engineer`  
Zakres: plan i backlog bez refaktoru kodu produkcyjnego.

## Cel

Podniesienie konwersji sklepu WooCommerce przez przebudowę logiki i hierarchii informacji na:
- PDP (product detail page),
- PLP (product listing page),
- checkout.

Plan bazuje na audycie: `docs/MARKETING_UX_REVIEW_2026-03-05.md`.

## Priorytetowe problemy do rozwiązania

1. Nad foldem PDP zbyt dużo równorzędnych bloków; cena i CTA tracą priorytet.
2. Słaby, niespójny social proof na poziomie produktu i listingu.
3. Powielone lub konkurujące sekcje informacyjne (trust/dostawa/spec) obniżają czytelność decyzji zakupowej.
4. Rozmyta granica odpowiedzialności: co robić przez Woo hooks/filters w `mu-plugin`, a co przez template/theme.

## Docelowa hierarchia PDP (nad foldem)

Kolejność od góry do dołu:
1. Nazwa produktu + krótka informacja wartości (1 linia).
2. Cena (regular/sale, oszczędność kwotowa lub procentowa).
3. Dostępność (stock status + ETA wysyłki, jeśli dostępne dane).
4. Warianty (jeśli variable product) bez dublowania etykiet i walidacji.
5. Główny CTA (`Dodaj do koszyka`) jako jedyny przycisk pierwszego wyboru.
6. Secondary CTA (`Kup teraz`) tylko jeśli nie konkuruje wizualnie z ATC.
7. Trust strip (zwroty, bezpieczna płatność, dostawa, gwarancja) w kompaktowej formie.
8. Social proof (rating + liczba opinii + liczba kupionych/ostatnio kupione).

Sekcje poniżej folda:
- Szczegółowy opis/specyfikacja,
- FAQ produktu,
- Rozszerzone informacje o dostawie i płatnościach,
- Produkty powiązane/cross-sell.

## Social proof: model i rozmieszczenie

### PDP
- Blok przy cenie/CTA:
  - `Ocena: X.X/5 (N opinii)`,
  - `Kupione: N w ostatnich 30 dniach` (jeśli dane wiarygodne),
  - fallback: `Popularny wybór w tej kategorii` (gdy brak danych ilościowych).
- Blok nie może dublować się w kilku miejscach nad foldem.

### PLP
- Na każdej karcie: rating + liczba opinii.
- Badge social proof (`Bestseller` / `Najczęściej kupowane`) przez jeden, spójny mechanizm.
- Bez ciężkich bloków tekstowych na kartach; priorytet dla ceny i szybkiego dodania do koszyka.

### Checkout
- Mikro-trust obok podsumowania:
  - bezpieczna płatność,
  - czas wysyłki,
  - kontakt do wsparcia.
- Social proof oszczędny: 1 krótka linia, bez odciągania od finalizacji zamówienia.

## Uproszczenie duplikatów i overloadu

1. Jedna sekcja trust nad foldem PDP (zamiast kilku podobnych bloków).
2. Jedna sekcja dostawa/płatność per widok, bez powtórzeń treści.
3. Jeden primary CTA na PDP; wszystkie inne akcje jako drugorzędne.
4. Ograniczenie liczby badge/ikon na kartach PLP do najważniejszych 1-2.
5. Usunięcie powielonych etykiet menu/sekcji pomocniczych wpływających na ścieżkę zakupową.

## Granice implementacji: `mu-plugin` vs template/theme

### Do `mu-plugin` (hooks/filters, logika i dane)
- Obliczenia i dostarczanie danych social proof (np. kupione 30d, bestseller).
- Normalizacja i fallback danych (brak opinii, brak sprzedaży, brak ETA).
- Filtry WooCommerce dla kolejności i obecności bloków na PDP/PLP/checkout.
- Feature flags dla etapowego rolloutu zmian konwersyjnych.
- Telemetria zdarzeń konwersyjnych (ATC, checkout start, checkout complete) bez ingerencji w core/pluginy.

Powód: logika biznesowa powinna być niezależna od konkretnego widoku motywu i możliwa do testowania oraz iteracji.

### Do template/theme (warstwa prezentacji)
- Finalny układ komponentów PDP/PLP/checkout (markup, spacing, visual hierarchy).
- Stylowanie trust strip, badge social proof i stanów CTA.
- Responsywność i reguły RWD/mobile-first.
- Usuwanie dublujących się elementów wynikających z layoutu.

Powód: to warstwa wizualna i semantyka HTML zależna od design systemu.

### Czego nie robić
- Brak zmian w WordPress core i kodzie zewnętrznych pluginów.
- Brak hardcode danych biznesowych bez fallbacku i źródła.

## Etapy wdrożenia (plan)

### Etap 1 (P0): PDP conversion skeleton
- Ustalenie kolejności bloków nad foldem.
- Jednoznaczny primary CTA.
- Trust strip i social proof w kompaktowej wersji.
- Usunięcie powielonych sekcji informacyjnych.

### Etap 2 (P0): PLP conversion cleanup
- Ujednolicenie kart produktowych (price > stock > quick ATC).
- Jeden mechanizm badge i rating.
- Redukcja elementów rozpraszających na listingu.

### Etap 3 (P1): Checkout friction reduction
- Uproszczenie informacji wspierających decyzję (mikro-trust).
- Ograniczenie elementów odciągających uwagę od finalizacji.
- Walidacja czytelności i logicznej kolejności pól.

### Etap 4 (P1): Pomiar i iteracja
- Wdrożenie metryk i dashboardu porównawczego before/after.
- Iteracje copy i placementu social proof na bazie danych.

## Acceptance criteria

1. PDP: cena, stock i primary CTA są widoczne bez scrolla na standardowych viewportach desktop/mobile.
2. PDP: nad foldem istnieje dokładnie jeden blok trust i jeden blok social proof (bez duplikatów treści).
3. PLP: każda karta ma spójną kolejność informacji (nazwa -> cena -> stock/rating -> CTA).
4. Checkout: sekcja trust jest obecna, ale nie konkuruje wizualnie z podsumowaniem i CTA finalizacji.
5. Brak zmian w WP core i zewnętrznych pluginach; zmiany realizowane wyłącznie przez theme/mu-plugin.
6. Wszystkie nowe elementy mają fallback przy braku danych (opinie/sprzedaż/ETA).

## Metryki sukcesu (mierzalne)

Podstawowe KPI (porównanie 14 dni before vs 14 dni after, z kontrolą ruchu):
- CTR ATC na PDP: +15% (minimum +8%).
- Współczynnik odrzuceń PDP (bounce): -10% (minimum -5%).
- Przejście PDP -> checkout start: +12% (minimum +6%).
- Checkout completion rate (start -> order): +7% (minimum +3%).
- PLP -> PDP click-through: +10% (minimum +5%).
- Revenue per session dla ruchu produktowego: +8% (minimum +3%).

Metryki jakościowe:
- 0 krytycznych duplikatów sekcji/menu na PDP/PLP/checkout.
- 0 krytycznych błędów czytelności CTA/ceny w smoke testach mobile i desktop.

## Artefakty i odpowiedzialność

- `04_woo_engineer`: logika Woo hooks/filters, dane social proof, kolejność bloków funkcjonalnych.
- `05_theme_ux_frontend`: layout i warstwa wizualna komponentów.
- `09_ui_designer`: finalna specyfikacja UI komponentów i hierarchii.
- `08_qa_security`: regresja konwersyjna i UX po wdrożeniu.
