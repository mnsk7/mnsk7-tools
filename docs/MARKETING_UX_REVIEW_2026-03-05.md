# Marketing/UX Review — staging (2026-03-05)

Cel: ocena strony jak e-commerce marketer (konwersja, zaufanie, czytelność, UX mobile) i zwrot do poprawy dla agentów.

Status: **DO PRZERÓBKI (global rework)**.

## Krytyczne problemy (P0)

1. **Kontrast i czytelność kart produktów są miejscami nieakceptowalne**  
   W kilku sekcjach tekst jest bardzo ciemny na ciemnym tle (header/listing/single), co utrudnia odczyt ceny i nazwy.  
   Wpływ: spadek CTR i dodawania do koszyka.

2. **Duplikaty treści/struktur nawigacyjnych w DOM i menu**  
   W snapshotach widać powielone listy kategorii i pozycje menu (m.in. jednocześnie "Dostawa i płatności" oraz "Dostawa i platnosci").  
   Wpływ: chaos informacyjny, błędy IA, słabsze UX mobile i SEO crawl budget.

3. **Header wygląda jak "staging/dev", nie jak sklep gotowy do sprzedaży**  
   Czerwony pasek z komunikatem staging, czarny header i słaba hierarchia elementów powodują wrażenie wersji roboczej.  
   Wpływ: spadek zaufania i konwersji na wejściu.

4. **Brak spójnego design systemu (kolory, typografia, komponenty)**  
   Różne style sekcji i komponentów powodują efekt "sklejki".  
   Wpływ: niski perceived quality, mniejsza wiarygodność B2B.

## Wysokie problemy (P1)

1. **Nadmierna długość i gęstość home bez priorytetyzacji CTA**  
   Dużo bloków trust/lojalność/opinie bez jednego dominującego flow zakupowego.

2. **Karta produktu: zbyt ciężka struktura nad foldem**  
   Dużo elementów informacyjnych konkuruje z głównym CTA i ceną.

3. **Niespójna struktura stron pomocniczych**  
   "Dostawa i płatności" oraz pozostałe strony mają poprawne dane, ale wizualnie są nadal niespójne z nowoczesnym e-commerce.

## Problemy średnie (P2)

1. Część copy i nagłówków jest techniczna, nie sprzedażowa.  
2. Brakuje mocniejszego social proof przy kartach produktów (opinia + liczba kupionych w czytelnej formie).  
3. Footer i sekcje pomocnicze są użyteczne, ale estetycznie wymagają uproszczenia i większej separacji wizualnej.

## Dowody w kodzie (obszary ryzyka)

- Duża liczba ręcznie zdefiniowanych sekcji i hardcoded statystyk na home: `wp-content/themes/mnsk7-storefront/front-page.php`.
- Bardzo rozbudowany, mieszany arkusz stylów dla wielu obszarów jednocześnie: `wp-content/themes/mnsk7-storefront/assets/css/mnsk7-product.css`.
- Dodatkowy blok footer doklejony do istniejącej struktury motywu parent: `wp-content/themes/mnsk7-storefront/footer.php`.

## Zwrot do agentów (mandatory rework)

### 09_ui_designer (P0)
- Przygotować **nowy UI_SPEC v2**: header, home, PLP, PDP, footer, strony pomocnicze.
- Definicja design systemu: kolory, typografia, spacing, przyciski, karty, badge.
- Wymusić mobile-first i WCAG AA (kontrast, czytelność, hit-area).

### 05_theme_ux_frontend (P0)
- Przebudować layout i style wg UI_SPEC v2, usunąć wizualny chaos.
- Zredukować liczbę sekcji "above the fold", wyeksponować CTA.
- Uporządkować warstwy stylów (split CSS: global/components/pages).

### 04_woo_engineer (P1)
- Uporządkować PDP pod konwersję: cena + stock + CTA + trust, potem specyfikacja.
- Dodać czytelny blok "X osób kupiło" + wiarygodne social proof na produktach.
- Sprawdzić konflikty pluginów wpływające na dublowanie elementów.

### 08_qa_security (P0 po wdrożeniu)
- Testy regresji UI/UX desktop+mobile (home, PLP, PDP, cart, checkout).
- Kontrola duplikatów sekcji/menu i kontrastu.

## Kryteria akceptacji reworku

1. Brak duplikatów bloków/menu i brak dubli stron pomocniczych w nawigacji.
2. Czytelność tekstu i ceny na każdym kluczowym widoku (AA).
3. Jeden jasny flow zakupowy na home i PDP.
4. Spójny wygląd (profesjonalny e-commerce, bez efektu "stara strona/2000").
