# Głęboki audyt header i górnej nawigacji — staging.mnsk7-tools.pl (marzec 2026)

Senior UX/UI auditor, WooCommerce 2026, Storefront child theme. Sprawdzono: desktop/mobile, homepage/kategoria/produkt/koszyk/konto, logged out, koszyk z produktem; kod header.php, 04-header.css, skrypty w functions.php.

**Uwaga (repo):** W repozytorium brakuje nadrzędnego motywu **Storefront** (`wp-content/themes/storefront/`). W katalogu `themes` są tylko: mnsk7-storefront, tech-storefront, best-shop. Zgodnie z .cursorrules Storefront powinien być w repo i deployowany razem z dzieckiem — bez niego na czystym deployu strona może się nie ładować poprawnie. Należy dodać Storefront do repo lub udokumentować wymóg instalacji na serwerze.

---

## 1. Co w headerze działa źle

- **Desktop: klik w ikonę koszyka nie prowadzi do /koszyk/** — przy `window.innerWidth >= 769` na klik w `.mnsk7-header__cart-trigger` wywoływane jest `e.preventDefault()`; użytkownik otwiera tylko dropdown. Żeby wejść do koszyka, musi kliknąć link w dropdownie. Dla użytkownika oczekującego „klik w koszyk = strona koszyka” to nieintuicyjne.
- **Mini-cart dropdown bez listy produktów** — `.cart_list`, `.product_list_widget` są ukryte (`display: none !important`). W dropdownie widać tylko podsumowanie (np. „1 produkt · 44,78 zł”) i przyciski. Brak podglądu „co jest w koszyku” w headerze; w 2026 często zostawia się 1–3 pozycje lub „View cart” jako główną akcję.
- **Niespójność: empty cart na desktop** — przy pustym koszyku klik w ikonę nadal otwiera dropdown („Koszyk jest pusty” + link). Na mobile ten sam element prowadzi do /koszyk/ (brak preventDefault). Różne zachowanie tej samej kontrolki w zależności od viewport.
- **Search: dwa formularze w DOM** — jeden inline (ukryty), drugi w dropdownie; dwa różne `id` pól (mnsk7-header-search-input vs mnsk7-header-search-input-mobile). Ryzyko pomyłek przy focus/aria i duplikacja.
- **Brak zamknięcia dropdownu wyszukiwania po submit** — po wysłaniu formularza search dropdown może pozostać otwarty; brak jawnego zamykania przy submit/nawigacji.
- **Sticky: dwie warstwy (promo + header)** — obie sticky; gdy promo jest widoczny, header ma `top: var(--mnsk7-promo-h)`. Po zamknięciu promo zmienia się `--mnsk7-promo-h` (promo usuwany z DOM), ale body nadal ma klasę `mnsk7-has-promo`. Możliwy „skok” headera po zamknięciu paska.
- **Wysokość headera: konflikt tokenów** — w `01-tokens.css` jest `--header-h: 52px`, w `main.css` (skompilowany?) `--header-h: 64px`. Kolejność ładowania decyduje o faktycznej wysokości; możliwa niespójność między stronami.
- **Mobile: submenu „Sklep” (kategorie)** — w menu mobilnym kategorie są w `.sub-menu` pod „Sklep”. Brak w kodzie JS obsługi rozwijania/zwijania submenu (accordion); jeśli submenu jest zawsze rozwinięte, na wąskim ekranie lista może być długa i niewygodna.
- **Brak focus trap w dropdownach** — przy otwartym search lub mini-cart brak opisu focus trap / zamykania Escape; dostępność klawiaturowa może być niepełna.
- **Aria: search dropdown** — `id="mnsk7-header-search"` jest używany w `aria-controls`, ale kontener ma atrybut `hidden`; przy otwarciu `hidden` jest usuwany. Należy upewnić się, że `aria-expanded` na przycisku jest zgodny ze stanem.

---

## 2. Co w headerze jest przestarzałe dla WooCommerce 2026

- **Tylko „Szukaj” (ikona) bez paska w headerze na desktop** — W 2026 wiele sklepów ma widoczny pasek wyszukiwania w headerze (np. 200–300 px) z placeholderem „Szukaj produktów…”. Obecne „ikona + dropdown po kliku” to wzorzec starszy; zmniejsza discoverability wyszukiwarki.
- **Brak predictive / instant search** — Brak podpowiedzi podczas wpisywania (sugestie produktów, kategorii, „ostatnie wyszukiwania”). Dla sklepu z dziesiątkami produktów to standard; brak tego wybija w dół w porównaniu z konkurencją.
- **Mini-cart bez listy pozycji** — Współczesne headery często pokazują 2–4 ostatnie pozycje + „View cart” / „Checkout”. Całkowite ukrycie listy w dropdownie jest podejściem „minimalistycznym” z lat wcześniejszych.
- **Submenu kategorii tylko na hover (desktop)** — `:hover` i `:focus-within`; brak obsługi klawiatury (Enter/Space do rozwijania) i brak wyraźnego wizualnego wskaźnika „rozwiń” (strzałka) przy „Sklep”. W 2026 oczekuje się keyboard + screen reader friendly z jasnym wskaźnikiem rozwijania.
- **Brak wyraźnego „current” w menu po stronie wizualnej** — Jest `.current-menu-item > a` (kolor), ale np. na stronie kategorii „Frez spiralny” w głównym menu nie ma automatycznego current (menu budowane z PHP po stronach, nie po taxonomy). Użytkownik może nie wiedzieć, w której „części” sklepu jest.
- **Promo bar tylko z tekstem** — Brak CTA (np. „Zobacz warunki”) lub linku w promce; wygląda na statyczny pasek informacyjny. W 2026 często łączy się z akcją (np. link do dostawy).

---

## 3. Elementy zbędne / powielone / konfliktowe

- **Dwa formularze search** — Jeden `#mnsk7-header-search-input` (inline, hidden), drugi `#mnsk7-header-search-input-mobile` w dropdownie. Wystarczy jeden formularz w dropdownie (używany na desktop i mobile); drugi można usunąć.
- **Podwójna etykieta „Szukaj produktów”** — W snapshotach widać dwa elementy „Szukaj produktów” (np. ref e72, e73); prawdopodobnie dwa `<label>` lub dwa pola z tym samym opisem. Należy zostawić jedną etykietę powiązaną z jednym polem.
- **Promo bar + treść „Darmowa dostawa od 300 zł” w hero i footer** — Ta sama informacja w trzech miejscach (promo, hero chips, footer Dostawa). Nie jest to błąd, ale można rozważyć skrócenie promo lub hero, żeby nie powtarzać tego samego trzykrotnie nad foldem.
- **Konflikt: cart trigger** — Na desktop „klik = dropdown”, na mobile „klik = nawigacja”. Ta sama kontrolka, różne zachowanie; użytkownik może się pogubić przy przełączaniu widoku (np. resize) lub przy porównaniu z innymi sklepami.
- **Brak secondary nav** — Nie ma osobnego paska (np. „Konto | Zamówienia | Pomoc”). To świadoma decyzja (prosty header); w audycie uznajemy za brak, nie błąd — ewentualnie w przyszłości jeden wiersz linków „pomocowych” pod głównym menu.

---

## 4. Konkretny backlog zadań dla Cursor

### Zadanie 1 — Jednolity cel kliku w ikonę koszyka (desktop)

- **Problem:** Na desktop klik w ikonę koszyka otwiera tylko dropdown (preventDefault), nie przechodzi do /koszyk/.
- **Gdzie:** `functions.php` — skrypt w `wp_footer`: `trigger.addEventListener('click', function(e) { if (window.innerWidth >= 769) { e.preventDefault(); ... } }`.
- **Dlaczego źle:** Użytkownik oczekuje, że ikona koszyka prowadzi do strony koszyka; zmuszenie do drugiego kliku w dropdown wydłuża ścieżkę i jest niespójne z mobile.
- **Co zrobić:** Usunąć `e.preventDefault()` na desktop; zawsze nawigować do `wc_get_cart_url()` przy kliku w trigger. Albo: na desktop przy pustym koszyku — nawigacja, przy niepustym — opcjonalnie dropdown (wtedy trzeba to jasno oznaczyć w UI, np. „Zobacz koszyk” vs „Otwórz podgląd”).
- **AC:** Na desktop klik w ikonę koszyka w headerze prowadzi do strony /koszyk/. Ewentualny dropdown można zostawić tylko po hover (bez przechwycania kliku) lub usunąć na desktop.

### Zadanie 2 — Jeden formularz wyszukiwania w headerze

- **Problem:** Dwa pola search (inline + w dropdownie), dwa ID, ryzyko duplikacji etykiet.
- **Gdzie:** `header.php` — formularz inline (hidden) i formularz w `#mnsk7-header-search`.
- **Dlaczego źle:** Zbędna złożoność, możliwe rozjazdy placeholder/action, gorsza dostępność (dwa label „Szukaj produktów”).
- **Co zrobić:** Zostawić tylko formularz w dropdownie. Jedno pole, jeden label, jeden `id`. Na desktop i mobile: klik w „Szukaj” otwiera dropdown z tym samym formularzem; po submit strona wyników ładuje się, dropdown można zamknąć w JS po submit.
- **AC:** W DOM jest jeden formularz wyszukiwania w headerze, jedno pole z jednym label, placeholder „Szukaj produktów…”, action na homepage z `post_type=product`.

### Zadanie 3 — Zamknięcie dropdownu wyszukiwania po submit

- **Problem:** Po wysłaniu formularza search dropdown może pozostać otwarty.
- **Gdzie:** `functions.php` — obsługa `searchToggle` i `searchDropdown`; brak nasłuchu na submit formularza.
- **Dlaczego źle:** Strona wyników ładuje się z otwartym dropdownem; wygląd niechlujnie, focus może zostać w środku.
- **Co zrobić:** W JS po submit formularza search ustawić `searchDropdown.hidden = true` i `searchToggle.setAttribute('aria-expanded', 'false')`. Ewentualnie przed submit (form submit i tak odświeży stronę).
- **AC:** Po kliku „Szukaj” w dropdownie i załadowaniu strony wyników dropdown jest zamknięty; po wejściu na stronę z wynikami search dropdown w headerze nie jest otwarty.

### Zadanie 4 — Rozwijane submenu „Sklep” na mobile (accordion)

- **Problem:** Na mobile pod „Sklep” jest lista kategorii; brak JS do zwijania/rozwijania. Długa lista może przewijać całe menu.
- **Gdzie:** `header.php` — `ul.sub-menu` wewnątrz `li.menu-item-has-children`; `functions.php` — brak obsługi kliku w „Sklep” na mobile dla toggle submenu.
- **Dlaczego źle:** UX jednej ręki: użytkownik powinien móc rozwinąć „Sklep”, zobaczyć kategorie, wybrać jedną, bez przewijania całego ekranu.
- **Co zrobić:** Na viewport &lt;769px dodać klik na link „Sklep” (lub przycisk obok) — toggle klasy na `li.menu-item-has-children` (np. `is-expanded`); CSS: `.sub-menu` domyślnie `display: none`, przy `li.is-expanded .sub-menu` — `display: block`. Dla a11y: `aria-expanded` na elemencie rozwijanym, klawisz Enter/Space.
- **AC:** Na szerokości &lt;769px po kliku „Sklep” w menu mobilnym lista kategorii się rozwija/zwija; wizualnie widać stan (np. strzałka); po wyborze kategorii strona się ładuje.

### Zadanie 5 — Wysokość headera: jedna wartość --header-h

- **Problem:** `--header-h` zdefiniowane w `01-tokens.css` (52px) i w `main.css` (64px) — konflikt.
- **Gdzie:** `01-tokens.css` i `main.css` (lub inny plik nadpisujący tokeny).
- **Dlaczego źle:** Różne strony mogą ładować style w innej kolejności; header może „skakać” między 52 a 64 px.
- **Co zrobić:** Ustalić jedną wartość (np. 52px lub 56px) w jednym miejscu (np. tylko w `01-tokens.css`); usunąć lub nie ładować definicji z `main.css` dla `--header-h` / `--header-h-scrolled`.
- **AC:** W całej witrynie header ma stałą, zdefiniowaną w jednym pliku wysokość (np. 52px); po scrollu może się zmniejszać (--header-h-scrolled), ale bez konfliktu źródeł.

### Zadanie 6 — Po zamknięciu promo baru odświeżyć top headera

- **Problem:** Po kliku „Zamknij” w promo bar body nadal ma `mnsk7-has-promo`, a `--mnsk7-promo-h` mógł zostać ustawiony; header może mieć nieaktualny `top`.
- **Gdzie:** `functions.php` — skrypt zamykający promo (sessionStorage, remove); `04-header.css` — `body.mnsk7-has-promo .mnsk7-header { top: var(--mnsk7-promo-h) }`.
- **Dlaczego źle:** Wizualny „skok” lub odstęp nad headerem po zamknięciu paska.
- **Co zrobić:** W handlerze zamknięcia promo: usunąć klasę `mnsk7-has-promo` z body, ustawić `--mnsk7-promo-h: 0` lub usunąć tę właściwość; ewentualnie usunąć promo z DOM dopiero po krótkiej animacji i wtedy zaktualizować body.
- **AC:** Po zamknięciu promo baru header od razu „przesuwa się” do góry (top: 0), bez pustego pasa i bez klasy `mnsk7-has-promo` na body.

### Zadanie 7 — Focus i Escape w dropdownach (search, mini-cart)

- **Problem:** Brak opisu zamykania dropdownu klawiszem Escape i ewentualnego focus trap.
- **Gdzie:** `functions.php` — skrypty dla search toggle i cart dropdown.
- **Dlaczego źle:** Dostępność klawiaturowa i screen reader; użytkownik z klawiatury powinien móc zamknąć overlay bez myszy.
- **Co zrobić:** Dla search dropdown i cart dropdown: nasłuch na `keydown` → Escape zamyka dropdown i przywraca focus na przycisk (search toggle / cart trigger). Opcjonalnie: focus trap wewnątrz dropdownu (Tab cyklicznie w obrębie), jeśli dropdown jest duży.
- **AC:** Gdy search lub mini-cart jest otwarty, naciśnięcie Escape zamyka go i zwraca focus na przycisk otwierający.

### Zadanie 8 — Wskaźnik rozwijania submenu „Sklep” (desktop)

- **Problem:** Submenu „Sklep” pojawia się na hover; brak ikony strzałki lub „expand” przy linku „Sklep”.
- **Gdzie:** `header.php` — `<li class="menu-item-has-children">` i link „Sklep”; `04-header.css` — style submenu.
- **Dlaczego źle:** Użytkownik może nie wiedzieć, że „Sklep” ma podmenu; w 2026 oczekuje się jasnego wizualnego wskaźnika.
- **Co zrobić:** Dodać po tekście „Sklep” ikonę (np. chevron down) z CSS; tylko wizualnie, hover/focus-within bez zmiany (już działa). Dla a11y: `aria-expanded` na linku lub na li, aktualizowany przez JS przy focus/hover (opcjonalnie).
- **AC:** Przy „Sklep” w menu głównym widać ikonę strzałki w dół (lub podobną); submenu nadal otwiera się na hover/focus.

### Zadanie 9 — Current state dla stron kategorii/tagu w menu

- **Problem:** W menu głównym `current-menu-item` jest tylko dla stron (Przewodnik, Dostawa, Kontakt) i `is_shop()`. Na stronie kategorii (np. „Frez spiralny”) żaden element menu nie jest oznaczony jako bieżący.
- **Gdzie:** `header.php` — warunki dla `current-menu-item`; brak sprawdzenia `is_product_category()` / taxonomy.
- **Dlaczego źle:** Użytkownik nie widzi, w której części sklepu jest (np. że jest „w Sklepie” lub w konkretnej kategorii).
- **Co zrobić:** Dla archiwum WooCommerce (kategoria, tag): dodać klasę `current-menu-item` do „Sklep” gdy `is_shop() || is_product_category() || is_product_tag()`; ewentualnie podświetlić w submenu konkretną kategorię, jeśli jest w drzewie.
- **AC:** Na stronie kategorii produktów (np. /kategoria-produktu/frez-spiralny/) link „Sklep” w menu ma klasę/stan wizualny „current”; ewentualnie w submenu „Frez spiralny” jest podświetlone.

### Zadanie 10 — Mini-cart: opcjonalnie pokazać 1–3 ostatnie pozycje

- **Problem:** W dropdownie mini-cart lista produktów jest całkowicie ukryta; widać tylko podsumowanie i przyciski.
- **Gdzie:** `04-header.css` — `.mnsk7-header__cart-dropdown .cart_list, .product_list_widget { display: none !important; ... }`.
- **Dlaczego źle:** Wzorce 2026: użytkownik często chce zobaczyć „co mam w koszyku” bez przechodzenia na stronę; zwiększa zaufanie i skraca ścieżkę do checkout.
- **Co zrobić:** Usunąć lub złagodzić ukrywanie listy (np. pokazać max 3 pozycje, krótka nazwa + cena); zachować podsumowanie i przyciski Koszyk / Płatność. Dostosować max-height dropdownu i scroll.
- **AC:** W dropdownie mini-cart (desktop) widać do 3 ostatnich pozycji koszyka (nazwa + cena) lub jasny komunikat „Koszyk jest pusty” oraz przyciski „Koszyk” i „Przejdź do płatności”. Lista nie zasłania przycisków.

### Zadanie 11 — Promo bar: opcjonalny CTA

- **Problem:** Promo bar zawiera tylko tekst „Darmowa dostawa od 300 zł. Tylko Polska.” i przycisk Zamknij; brak linku do szczegółów.
- **Gdzie:** `header.php` — `mnsk7-promo-bar__text`; filtr `mnsk7_header_promo_text`.
- **Dlaczego źle:** W 2026 często daje się jeden klik („Zobacz warunki”, „Sprawdź”) dla zaangażowania.
- **Co zrobić:** W treści promo dodać link do strony „Dostawa i płatności” (np. „Darmowa dostawa od 300 zł. [Warunki →]”). Styl: ten sam kolor, podkreślenie lub bold.
- **AC:** W tekście promo baru jest klikalny link prowadzący do strony z warunkami dostawy; przycisk Zamknij nadal zamyka pasek.

### Zadanie 12 — Opcjonalnie: widoczny pasek wyszukiwania na desktop

- **Problem:** Na desktop wyszukiwarka jest tylko pod ikoną (dropdown po kliku); brak stale widocznego pola.
- **Gdzie:** `header.php` — struktura search; `04-header.css` — media desktop.
- **Dlaczego źle:** Wzorce 2026: stały pasek wyszukiwania w headerze zwiększa użycie wyszukiwarki i poczucie „sklepu”.
- **Co zrobić:** Na min-width np. 1024px pokazać w headerze pole search (np. 220–280 px szerokości) z placeholder „Szukaj produktów…”; ikonę zostawić jako submit lub przycisk „Szukaj”. Na węższych ekranach zostawić tylko ikonę + dropdown.
- **AC:** Na viewport ≥1024px w headerze widać pole wyszukiwania (bez konieczności kliku); placeholder „Szukaj produktów…”; submit prowadzi do strony wyników z post_type=product.

---

## 5. Jak powinien wyglądać i zachowywać się dobry header (Storefront, bez pełnego redizajnu)

- **Struktura (z góry na dół):**  
  - Opcjonalny announcement (promo) — jeden wiersz, z możliwością zamknięcia, ewentualnie z jednym linkiem CTA.  
  - Główny header: logo (lewa) | menu (środek lub lewa po logo) | search + konto + koszyk (prawa).  
  - Bez drugiego paska nawigacji, chyba że celowo wprowadzany „utility bar”.

- **Logo:** Zawsze link do homepage; na mobile nie obcinane; stała max-height (np. 40–44px na mobile, 48px desktop).

- **Menu główne:**  
  - Desktop: Sklep (z submenu kategorii), Przewodnik, Dostawa i płatności, Kontakt. Submenu „Sklep” na hover/focus, z wyraźną strzałką w dół. Current state dla strony głównej sklepu i dla kategorii („Sklep” lub konkretna kategoria w submenu).  
  - Mobile: burger otwiera pełnoekranowe lub pod headerem menu; „Sklep” rozwijane (accordion); po wyborze kategorii przejście na stronę i zamknięcie menu.

- **Wyszukiwarka:**  
  - Desktop (opcjonalnie): krótki pasek w headerze (200–280 px) z placeholder „Szukaj produktów…”; submit = strona wyników.  
  - Wszędzie: ikona otwierająca dropdown z polem to minimum; po submit dropdown się zamyka. Jedno pole, jeden formularz, bez duplikatów.  
  - Docelowo: predictive search (sugestie podczas wpisywania) — osobny task.

- **Konto:** Ikona + etykieta („Moje konto” / imię); etykieta z max-width i ellipsis przy długim imieniu; zawsze link do /moje-konto/.

- **Koszyk:**  
  - Ikona + liczba (badge) przy niepustym koszyku.  
  - Klik: zawsze nawigacja do /koszyk/ (spójnie na desktop i mobile). Ewentualnie na desktop: hover pokazuje mini-podgląd (1–3 pozycje + suma + „Koszyk” / „Checkout”), bez przechwytywania kliku — klik = przejście do koszyka.  
  - W mini-podglądzie (jeśli zostanie): wyraźne przyciski „Koszyk” i „Przejdź do płatności”; opcjonalnie krótka lista pozycji.

- **Sticky:** Header sticky z `top: 0` (albo `top: wysokość promo`, gdy promo widoczny). Po zamknięciu promo — brak skoku, `top: 0`. Jedna, spójna wysokość headera (--header-h) w całej witrynie.

- **Dostępność:**  
  - Focus visible na wszystkich interaktywnych elementach.  
  - Escape zamyka dropdown (search, ewentualny mini-podgląd).  
  - Submenu „Sklep” z aria-expanded i obsługą klawiatury.  
  - Jedna etykieta na pole search, powiązana z jednym id.

- **Bez zbędnego szumu:** Jeden announcement, jeden poziom menu, bez powielania tych samych linków w headerze; brak drugiego paska z tymi samymi pozycjami.

---

*Audyt wykonany 2026-03-09. Po wdrożeniu zadań zaleca się ponowną weryfikację na viewport 360, 768, 1024, 1440 px oraz w trybie keyboard-only i z czytnikiem ekranu.*
