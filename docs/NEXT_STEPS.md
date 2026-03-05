# Co dalej — mnsk7-tools.pl

Krótki przegląd: co jest zrobione w kodzie, co zostało (admin, konfiguracja, content), w jakiej kolejności to zrobić.

---

## Zrobione w kodzie

- **Sprint 01:** mu-plugin `mnsk7-tools.php`, overrides karty produktu (single + content-single), blokada xmlrpc (P0-03), override archiwum kategorii (`archive-product.php`).
- **Sprint 02:** blok parametrów i „Do czego” w karcie, dostępność, dostawa+VAT (karta + stopka + shortcode), shortcode bestsellery i rating, placeholder pod schemat/wideo, CSS (`assets/css/mnsk7-product.css`), helper kolejności atrybutów do filtrów.
- **DevOps:** PR template, PHP lint (Actions), DEPLOY_PLAYBOOK, sekrety GitHub → deploy przy pushu na `main`.

---

## Najbliższe kroki (kolejność)

### 1. Deploy na staging i smoke

- Wypchnąć zmiany na `main` (lub zrobić `make deploy-files`), żeby na stagingu były: mu-plugins, theme (w tym `assets/css/mnsk7-product.css`).
- Przejść [QA_REPORT.md](QA_REPORT.md) (smoke): katalog, karta produktu, koszyk, checkout; sprawdzić, że xmlrpc zwraca 403.

### 2. Sprint 01 — konfiguracja (admin WP / hosting)

| Zadanie | Gdzie | Uwagi |
|--------|--------|--------|
| **S1-01** | Pluginy | Ustalić: Przelewy24 vs Przelewy24 Raty — czy to dwa osobne gatewaye. Usunąć tylko ewentualny duplikat tego samego. |
| **S1-02** | Pluginy | Zostawić jeden cache (LiteSpeed **lub** WP Rocket), wyłączyć Seraphinite i drugi. |
| **S1-03** | Ustawienia cache | Wykluczyć z cache: cart, checkout, my-account. |
| **S1-05** | Hosting / pluginy | Sprawdzić backupy; w razie braku — np. UpdraftPlus. |
| **S1-08** | Pluginy | Przed wyłączeniem 3 filtrów: Search Console → które URL filtrów są w indeksie → zaplanować przekierowania. Potem zostawić jeden filtr. |
| **S1-09** | WP Admin | Audyt atrybutów: Products → Attributes + kilka produktów; ewentualnie `DB_PASS='...' make check-db`. |

### 3. Sprint 02 — katalog i treści (admin + content)

| Zadanie | Gdzie | Uwagi |
|--------|--------|--------|
| **S2-01** | Kategorie w WP | Uporządkować drzewo kategorii: typ narzędzia → ewentualnie materiał; unikać mieszania typu, materiału i parametrów w jednym poziomie. |
| **S2-02** | Plugin filtrów | W ustawieniach wybranego pluginu ustawić atrybuty w kolejności (np. z `mnsk7_get_filter_attribute_order()`): typ, średnica, trzpień, długość, zastosowanie. |
| **S2-07** | Strona główna | Dodać shortcode `[mnsk7_bestsellers]` w treści strony głównej lub w widgecie. |
| **S2-09** | Stopka / strona | Dodać `[mnsk7_rating url="https://allegro.pl/..."]` tam, gdzie ma być link do opinii (stopka lub osobna sekcja). |
| **S2-08** | WooCommerce → Ustawienia → Produkty | Upewnić się, że recenzje są włączone; na karcie produktu zakładka z recenzjami jest domyślna w Woo. |

### 3b. UI według specyfikacji (05/04)

- **[UI_SPEC.md](UI_SPEC.md)** jest gotowy: header (logo, menu z kategoriami, search, konto, koszyk), footer (kontakt, Instagram 1 rząd, linki, skrót dostawy), główna (baner, kategorie, karuzela, loyalty, Instagram), karta produktu (kolejność sekcji, CTA, odstępy).
- Do wdrożenia: blok kontaktu w stopce (email, tel, godziny), ewentualnie menu z kategoriami w headerze (zależnie od motywu), strona z tabelą dostaw, widżet Instagram.

### 4. Później (Sprint 03 / backlog)

- **SEO (E5):** strony docelowe pod zapytania (frezy do aluminium, MDF, drewna, CNC), struktura meta (SEO_PLAN), ewentualnie blog/instrukcje (CONTENT_PLAN).
- **Obrazy:** masowa konwersja do WebP (P1-03), uzupełnienie alt (P2-04).
- **Mobilny i wizual:** poprawa widoku mobilnego i „profesjonalnego” wyglądu (UX-07).
- **Uporządkowanie:** jeden GTM, jeden Facebook Pixel (P2-05, P2-06), jeden schema (P2-01), robots.txt (P2-02), duplikaty wishlist/builder/profil (P1-04).

---

## Gdzie co znaleźć

- **Smoke i bezpieczeństwo:** [docs/QA_REPORT.md](QA_REPORT.md)
- **Deploy i rollback:** [docs/DEPLOY_PLAYBOOK.md](DEPLOY_PLAYBOOK.md)
- **Sprinty:** [tasks/020_sprint_01.md](../tasks/020_sprint_01.md), [tasks/030_sprint_02.md](../tasks/030_sprint_02.md)
- **Backlog techniczny:** [docs/AS_IS_BACKLOG.md](AS_IS_BACKLOG.md)
- **Inbox:** [tasks/000_inbox.md](../tasks/000_inbox.md)
