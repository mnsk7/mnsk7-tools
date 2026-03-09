# Аудит footer — staging.mnsk7-tools.pl (март 2026)

Senior e-commerce UX review: доверие, навигация, юридическая ясность, структура, дубли, мобильная читаемость, полезность для покупателя.

Проверены: homepage, category, product, cart, account, info/legal (по коду и снимкам).

---

## 1. Состав footer (текущий)

| Блок | Содержимое |
|------|------------|
| **Newsletter** | H3 «Newsletter», описание, форма (email + «Zapisz się»), текст о wypisie i polityce prywatności |
| **Klient** | Sklep, Moje konto, Dostawa i płatności, Regulamin, Polityka prywatności |
| **Kontakt** | Dane firmy (filtr `mnsk7_footer_legal_address`: MNSK7 Spółka z o.o., adres, KRS, NIP, REGON), email, telefon, godziny (pn–pt, sb, nd), Instagram |
| **Kategorie** | Do 8 kategorii produktów (top-level, `hide_empty`) |
| **Dostawa** | Dwa akapity: „Dostawa następnego dnia. Faktura VAT…” oraz „Darmowa dostawa od 300 zł. Tylko Polska.” |
| **Bottom** | © rok mnsk7-tools.pl |

**Grid desktop:** `1.2fr 1fr 1fr 1fr 1fr` (5 kolumn).  
**Mobile:** sekcje jako accordion; domyślnie otwarta tylko Newsletter (`is-open`, `data-accordion-open`).

---

## 2. Pełna lista problemów

### 2.1 Zaufanie i legal

- **Brak linku do strony Kontakt w footerze** — w sekcji „Klient” są Sklep, Moje konto, Dostawa, Regulamin, Polityka; brak „Kontakt” → użytkownik szukający formularza kontaktowego ma tylko email/telefon w kolumnie Kontakt, bez linku na dedykowaną stronę.
- **Dane firmy (NIP/KRS/REGON)** — są w bloku Kontakt, czytelne; rejestr w jednej linii z separatorem (·) może na wąskich ekranach być długi; brak ewentualnego skrótu „Dane firmowe” z linkiem do strony (np. regulamin/kontakt).
- **Brak wyraźnego „Zwroty / Reklamacje”** — nie ma osobnej pozycji w stopce; informacje pewno w Regulaminie/Dostawa, ale dla B2C/B2B 2026 często oczekiwana jest krótka wzmianka lub link w footerze.

### 2.2 Nawigacja i duplikaty

- **Pełne powtórzenie linków z headera w „Klient”** — Sklep, Moje konto, Dostawa i płatności są w menu głównym i znowu w footerze. To standard, ale footer nie dodaje nic nowego (np. Kontakt, Zwroty); wygląda jak „ta sama nawigacja drugi raz”.
- **Kategorie w footerze = podzbiór submenu „Sklep”** — te same lub bardzo podobne kategorie co w dropdownie; podwójna ekspozycja bez wyraźnej korzyści (SEO ok, UX — redundantne).
- **„Dostawa” w trzech miejscach** — promo bar („Darmowa dostawa od 300 zł…”), hero/chipy na głównej, kolumna Dostawa w footerze. Ten sam komunikat trzy razy osłabia przekaz i zajmuje miejsce.

### 2.3 Struktura i hierarchia

- **Newsletter na pierwszej pozycji** — pierwsza kolumna to od razu formularz zapisu; dla użytkownika szukającego linków (konto, dostawa, regulamin) to „przeszkoda”. Wzorce 2026: najpierw nawigacja / pomoc, potem kontakt, na końcu newsletter.
- **Sekcja „Dostawa” to tylko dwa zdania** — nie link do „Dostawa i płatności”, tylko suchy tekst; można by zastąpić jednym zdaniem + link „Szczegóły →” zamiast osobnej kolumny.
- **Brak grupowania „Informacje prawne”** — Regulamin i Polityka są w „Klient” razem z Sklep/Moje konto; logiczniej: „Dla klienta” (Sklep, Konto, Dostawa, Kontakt) i „Informacje” (Regulamin, Polityka, ewent. Zwroty).

### 2.4 Długość i „śmietnik linków”

- **Footer nie jest przesadnie długi** — 5 kolumn + jedna linia copyright; akceptowalne.
- **Ryzyko „śmietnika”** — na mobile 5 zwiniętych sekcji (Newsletter, Klient, Kontakt, Kategorie, Dostawa) + po rozwinięciu dużo linków/tekstu; brak wizualnego podziału na „nawigacja” vs „kontakt” vs „legal”.
- **Za dużo tekstu przed copyright** — w kolumnie Dostawa dwa akapity; w Kontakt — adres, rejestr, godziny, 4 elementy listy. Można skrócić Dostawa do jednej linii z linkiem.

### 2.5 Mobile

- **Newsletter domyślnie otwarty** — na mobile jedyna otwarta sekcja to Newsletter; użytkownik od razu widzi formularz zamiast linków (Sklep, Konto, Regulamin). Może to być celowe (konwersja), ale kosztem użyteczności nawigacji.
- **Czytelność** — font 1rem, kontrast (jasny tekst na ciemnym tle) OK; godziny w `<dl>` czytelne; długi adres + KRS/NIP/REGON w jednym bloku może się zawijać na 360px.
- **Accordion** — działa (klik w H3); brak aria-expanded przy pierwszym ładowaniu dla sekcji domyślnie zamkniętych (ustawiane w JS).

### 2.6 Newsletter

- **Miejsce** — pierwsza kolumna; przyciąga uwagę, ale odciąga od linków.
- **Treść** — „Otrzymuj informacje o promocjach, nowościach i poradach” + pole email + „Zapisz się” + „Możesz w każdej chwili wypisać się. Zobacz politykę prywatności” — zgodne z RODO, czytelne.
- **Czy potrzebny w tej formie** — tak dla B2C/B2B light; sensowne jest jednak przeniesienie na koniec (ostatnia kolumna) lub jeden wiersz nad copyright („Newsletter: [pole] [Zapisz]”) zamiast dużej pierwszej kolumny.

### 2.7 E-commerce i użyteczność

- **Pomoc w decyzji** — kolumna Dostawa (następny dzień, VAT, darmowa od 300 zł) daje sygnały zaufania, ale powiela hero/promo; brak np. „Bezpieczne płatności”, „Zwroty 14 dni” w stopce.
- **Legal / reassurance** — Regulamin i Polityka są; brak krótkiej wzmianki o zwrotach/reklamacjach.
- **Co podnieść wyżej** — „Darmowa dostawa od 300 zł” już jest w promo i na stronie; w footerze wystarczy jedna linia + link. Trust (VAT, zwroty) warto wzmocnić przy koszyku/checkout, a nie tylko w footerze.

### 2.8 Inne

- **Stałe dane** — email (office@mnsk7.pl), telefon (+48 451696511), godziny — w kodzie na sztywno; warto przenieść do filtra/opcji (np. `mnsk7_footer_contact`).
- **Instagram** — w Kontakt; OK. Brak innych sociali — nie problem, jeśli nie ma.
- **Cookie bar** — poza footerem; dwa banki (tema + plugin) — już ujęte w głównym audicie; w kontekście footera nie duplikuje treści.

---

## 3. Co usunąć

- **Osobna kolumna „Dostawa” w obecnej formie** — zastąpić jednym zdaniem (np. „Darmowa dostawa od 300 zł. [Szczegóły →]”) w jednym miejscu (np. w „Klient” jako ostatni element listy z linkiem do /dostawa-i-platnosci/, albo jedna linijka nad copyright).
- **Druga i trzecia ekspozycja „Darmowa dostawa od 300 zł”** — w footerze zostawić tylko jedną, z linkiem; nie powtarzać tego samego zdania co w promo/hero.

---

## 4. Co połączyć

- **„Klient” + ewent. Dostawa** — w jednej sekcji: Sklep, Moje konto, Dostawa i płatności, Regulamin, Polityka prywatności, (Kontakt), (opcjonalnie jedna linia „Darmowa dostawa od 300 zł” z linkiem). Kolumnę „Dostawa” zlikwidować lub zredukować do jednej linii w „Klient”.
- **Regulamin + Polityka** — można zgrupować wizualnie (np. podtytuł „Informacje prawne” lub osobna kolumna „Informacje” z dwoma linkami), bez zmiany linków.

---

## 5. Co przebudować

- **Kolejność kolumn** — proponowana: (1) Klient / Dla klienta (Sklep, Konto, Dostawa, Kontakt, Regulamin, Polityka), (2) Kategorie (skrót katalogu), (3) Kontakt (dane firmy, email, tel, godziny, Instagram), (4) Newsletter (na końcu lub nad copyright). Usunąć osobną kolumnę „Dostawa” lub zastąpić ją jedną linijką.
- **Newsletter** — albo ostatnia kolumna, albo jeden wiersz nad paskiem copyright („Zapisz się do newslettera: [pole] [Zapisz]”), żeby nie dominował nad linkami.
- **Mobile: domyślny stan accordionu** — rozważyć domyślnie otwartą sekcję „Klient” (nawigacja) zamiast Newsletter; albo wszystkie zamknięte poza „Klient”.

---

## 6. Jakie bloki zachować

- **Kontakt** — adres, NIP/KRS/REGON, email, telefon, godziny, Instagram — zostawić; ewent. dodać link „Strona Kontakt” jeśli istnieje.
- **Klient (nawigacja)** — Sklep, Moje konto, Dostawa i płatności, Regulamin, Polityka; dodać Kontakt.
- **Kategorie** — zostawić (SEO, drugi punkt wejścia); można ograniczyć do 6–8 lub zostawić 8.
- **Newsletter** — zostawić, ale zmienić pozycję i/lub formę (ostatnia kolumna lub pasek nad copyright).
- **Bottom** — copyright tylko; bez dodatkowego tekstu.

---

## 7. Docelowy footer dla WooCommerce B2C/B2B light (2026)

- **Warstwa 1 (górny blok)**  
  - Lewa / środek: **Dla klienta** — Sklep, Moje konto, Dostawa i płatności, Kontakt, Regulamin, Polityka prywatności; opcjonalnie jedna linia „Darmowa dostawa od 300 zł” z linkiem.  
  - Środek: **Kategorie** — 6–8 linków (top categories).  
  - Prawa: **Kontakt** — firma (nazwa, adres, KRS/NIP/REGON), email, telefon, godziny, Instagram (bez drugiej kolumny „Dostawa” z dwoma akapitami).

- **Warstwa 2 (opcjonalnie)**  
  - **Newsletter** — jedna linia: „Newsletter: [email] [Zapisz się]” + krótka informacja o wypisie i polityce; albo ostatnia kolumna z formularzem (nie pierwsza).

- **Warstwa 3**  
  - **Copyright** — © rok, nazwa domeny; ewent. link „Polityka cookie” obok.

- **Trust** — nie wymaga osobnej kolumny; wystarczy jeden komunikat (dostawa od 300 zł) z linkiem; zwroty/reklamacje — link w „Dla klienta” lub w Regulaminie.

- **Mobile** — accordion; domyślnie otwarta sekcja „Dla klienta” (lub wszystkie zamknięte); bez dominującego Newsletter na górze.

---

## 8. 15–25 zadań dla Cursor

1. **Footer: dodać link „Kontakt” w sekcji Klient** — obok Regulamin, Polityka dodać `<a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a>`. AC: W „Klient” widać link do strony Kontakt.

2. **Footer: usunąć osobną kolumnę „Dostawa”** — usunąć `<div class="mnsk7-footer__col mnsk7-footer__col--dostawa">` z footer.php; przenieść jedną linijkę („Darmowa dostawa od 300 zł. Tylko Polska.”) z linkiem do /dostawa-i-platnosci/ do sekcji Klient (jako ostatni element listy lub osobny wiersz pod listą). AC: Brak kolumny „Dostawa”; w „Klient” jest jedna linia z linkiem do strony dostawy.

3. **Footer: zmienić kolejność kolumn** — w `.mnsk7-footer__inner` ustawić kolejność: Klient, Kategorie, Kontakt, Newsletter (zamiast Newsletter, Klient, Kontakt, Kategorie, Dostawa). AC: Na desktop pierwsza kolumna to „Klient”, ostatnia „Newsletter”.

4. **Footer: Newsletter na mobile domyślnie zamknięty** — usunąć klasy `is-open` i atrybut `data-accordion-open` z `.mnsk7-footer__col--newsletter`. AC: Na mobile po załadowaniu sekcja Newsletter jest zwinięta.

5. **Footer: domyślnie otwarta sekcja „Klient” na mobile** — dodać `is-open` i `data-accordion-open` do `.mnsk7-footer__col--client`. AC: Na mobile po załadowaniu pierwsza rozwinięta sekcja to „Klient”.

6. **Footer: zgrupować „Informacje prawne”** — w sekcji Klient wizualnie oddzielić (np. `<span class="mnsk7-footer__subtitle">Informacje prawne</span>`) lub listę: Sklep, Moje konto, Dostawa, Kontakt; potem Regulamin, Polityka. AC: W kodzie/strukturze widać logiczny podział „nawigacja” vs „informacje prawne”.

7. **Footer: dodać opcjonalny link „Zwroty / Reklamacje”** — w sekcji Klient dodać link do strony (np. /regulamin/#zwroty lub dedykowana strona) z tekstem „Zwroty i reklamacje”. AC: W stopce jest link do informacji o zwrotach (jeśli strona istnieje).

8. **Footer: dane kontaktowe z filtra** — wprowadzić filtr `mnsk7_footer_contact` (np. tablica: email, phone, hours_html, instagram_url) i w footerze wyświetlać dane z filtra zamiast na sztywno; domyślna wartość filtra = obecne office@mnsk7.pl, +48 451696511, godziny, Instagram. AC: Email, telefon, godziny, Instagram można nadpisać z filtra.

9. **Footer: skrócić blok Dostawa przed usunięciem kolumny** — jeśli kolumna Dostawa zostanie na razie, zastąpić dwa `<p>` jednym: „Dostawa następnego dnia. Faktura VAT na życzenie. [Darmowa dostawa od 300 zł.](link).” AC: Jedna krótsza treść z linkiem.

10. **Footer: aria-expanded na sekcjach accordion** — w JS inicjalizującym accordion ustawiać `aria-expanded` na `.mnsk7-footer__title` zgodnie z stanem (true/false) przy ładowaniu. AC: Screen reader widzi poprawny stan rozwinięcia.

11. **Footer: opcjonalny wariant Newsletter w jednej linii** — dodać klasę/modifier (np. `.mnsk7-footer--newsletter-inline`) i CSS: w tym wariancie formularz newslettera w jednym wierszu (input + przycisk) nad paskiem copyright, bez osobnej kolumny. AC: Można włączyć wariant „newsletter inline” bez dużej pierwszej kolumny.

12. **Footer: limit kategorii do 6** — w `get_terms` dla `mnsk7-footer` ustawić `'number' => 6` zamiast 8. AC: W „Kategorie” maks. 6 linków.

13. **Footer: link „Kontakt” w kolumnie Kontakt** — pod danymi firmy dodać link „Formularz kontaktowy” / „Strona Kontakt” do /kontakt/. AC: Z kolumny Kontakt można przejść na stronę Kontakt.

14. **Footer: styl „Dostawa” w Klient** — po przeniesieniu linii „Darmowa dostawa od 300 zł” do Klient: stylować jako mały blok lub list item z ikoną; nie wyglądać jak zwykły link. AC: Wizualnie odróżnialna informacja trust.

15. **Footer: brak duplikatu tytułu „Dostawa”** — po usunięciu kolumny Dostawa usunąć z CSS style `.mnsk7-footer__col--dostawa` (lub zostawić na wypadek gdyby block wrócił). AC: Brak nieużywanych stylów.

16. **Footer: mobile — czytelność adresu** — w `.mnsk7-footer__address` na max-width 360px upewnić się, że KRS/NIP/REGON się zawijają (`word-break` / `overflow-wrap`); nie obcinać. AC: Na 360px cały tekst widoczny.

17. **Footer: bottom bar — ewent. link do polityki cookie** — obok copyright dodać link „Polityka cookie” do `#cookies` lub odpowiedniej sekcji polityki. AC: W bottom bar jest link do ustawień cookie (opcjonalnie).

18. **Footer: semantyka** — sekcje opatrzyć `<section>` lub aria-label przy nav/listach (np. `aria-label="Linki dla klienta"`). AC: Lepsza dostępność struktury.

19. **Footer: kolejność kolumn w gridzie** — po zmianie kolejności w HTML zaktualizować `grid-template-columns` w 09-footer.css (np. 1fr 1fr 1.2fr 1fr dla Klient, Kategorie, Kontakt, Newsletter). AC: Proporcje kolumn pasują do nowej kolejności.

20. **Footer: jeden komunikat „Darmowa dostawa”** — po przeniesieniu do Klient upewnić się, że w całym footerze nie ma drugiego identycznego zdania (tylko jeden blok z linkiem). AC: Brak powtórzenia tego samego tekstu w stopce.

21. **Footer: test na stronie kategorii** — sprawdzić, że footer wyświetla się tak samo (bez obcięć, bez duplików) na /kategoria-produktu/... AC: Wizualnie spójny footer.

22. **Footer: test na checkout** — sprawdzić, że stopka nie nakłada się na przycisk „Kupuję i płacę” i że accordion działa. AC: Brak nakładania, accordion działa.

23. **Footer: opcjonalnie „Bezpieczne płatności”** — w bottom bar lub w „Klient” dodać krótką wzmiankę / ikonkę (np. „Bezpieczne płatności”) z linkiem do Dostawa i płatności. AC: Wzmacnia zaufanie (niski priorytet).

24. **Footer: usunąć nadmiarowy padding na bardzo wąskich ekranach** — na 320px sprawdzić padding `.mnsk7-footer__inner` (np. 0.75rem). AC: Brak poziomego scrolla.

25. **Footer: dokumentacja** — w komentarzu w footer.php opisać aktualną strukturę (po zmianach): kolumny, filtry (mnsk7_footer_legal_address, mnsk7_footer_contact), kolejność. AC: Kolejny developer wie, co gdzie jest.

---

*Audyt wykonany 2026-03. Po wdrożeniu zadań zaleca się weryfikację na viewport 360, 768, 1024 px oraz na stronach: główna, kategoria, produkt, koszyk, checkout, moje konto, regulamin, polityka, kontakt.*
