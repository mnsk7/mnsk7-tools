# Kontakt, dostawa, lojalność — wymagania klienta

Źródło: ustalenia z klientem + notatki z PDF (główna strona, staging.kayer.pl). Do wdrożenia w headerze, stopce, stronie dostawy i w panelu klienta.

---

## 1. Kontakt (header / stopka)

| Pole      | Wartość |
|-----------|---------|
| **Email** | office@mnsk7.pl |
| **Telefon** | +48 451696511 |
| **Godziny** | pn.–pt. 9.00–17.00, sb. 10.00–12.00, nd. zamknięte |

Wyświetlać w stopce (i ewentualnie w „Kontakt” / górny bar).

---

## 2. Instagram (stopka)

- **Profil:** https://www.instagram.com/mnsk7tools/
- **Wymaganie:** widżet z postami z Insty; użytkownik może przejść do profilu.
- **Przykładowe embedy (do stopki / shortcode):**
  - https://www.instagram.com/mnsk7tools/p/DC9J3JjNobj/
  - https://www.instagram.com/mnsk7tools/p/DC4agmPtKoy/
  - https://www.instagram.com/mnsk7tools/p/DCzOqKqtjUe/
- **Uwaga z PDF:** „może tylko mniej postów żeby było widać (1 rząd)”.

Opcje: plugin (np. Instagram Feed), osadzenie oficjalnego embedu lub karuzela zdjęć z API (zgodnie z polityką Instagram). W specyfikacji UI: jeden rząd postów + przycisk/link „Obserwuj na Instagramie”.

ghj**Wdrożone technicznie (shortcode):**

- `[mnsk7_instagram_feed]` — blok Instagram + link do profilu.
- `[mnsk7_instagram_feed posts="https://www.instagram.com/p/.../,https://www.instagram.com/p/.../"]` — jeśli podasz URL-e postów, shortcode spróbuje osadzić je jako embed.

---

## 3. Zakres dostaw i dostawcy

- **Obszar:** na razie tylko **Polska** (zamówienia z witryny).
- **Dostawcy:** **InPost** i **DPD**.

---

## 4. Tabela warunków dostawy (czas dostawy)

Poniższa tabela ma być podstawą do **automatycznego wyświetlania orientacyjnego terminu dostawy** (np. na stronie dostawy, w koszyku lub przy wyborze metody).

| Kiedy zamówiono | Warunek (InPost) | Efekt |
|------------------|------------------|--------|
| pn.–pt. | Do 15:00 przez InPost | Dostawa następnego dnia |
| sb. | Do 11:00 przez InPost | Dostawa w poniedziałek |

| Kiedy zamówiono | Warunek (DPD) | Efekt |
|------------------|----------------|--------|
| pn.–czw. | Do 17:00 (DPD) | Dostawa następnego dnia |
| pt. | Do 17:00 (DPD) | Dostawa w poniedziałek |

**Skrót na stronie:** „Zamów pn.–pt. przez InPost do 15:00 — dostawa następnego dnia” itd. Pełna tabela — na stronie „Dostawa i płatności” lub w regulaminie.

---

## 5. Darmowa dostawa

- **Progi:** zamówienie od **300 zł** — **dostawa gratis**.

(Do ustawienia w WooCommerce: np. free shipping od 300 zł; ewentualnie komunikat w stopce / na stronie dostawy.)

---

## 6. System lojalności (rabat w panelu klienta)

Z notatek z PDF — **nakładająca się w ciągu roku** w panelu klienta:

| Suma zamówień w roku | Rabat |
|----------------------|--------|
| 1 000 zł | 5% |
| 3 000 zł | 10% |
| 5 000 zł | 15% |
| 10 000 zł | 20% |

**Wymaganie:** „warunki automatyczne w panelu” — czyli w **Moje konto** klient widzi swój poziom (progi) i naliczony rabat; rabat ma być stosowany automatycznie (np. przy kolejnych zakupach w danym roku).

**Wdrożone w kodzie:** w **Moje konto** (dashboard) wyświetlany jest blok „System rabatów”: suma zamówień w bieżącym roku (status completed), aktualny % rabatu, brakująca kwota do następnego progu, lista progów (1000→5%, 3000→10%, 5000→15%, 10 000→20%). Shortcode `[mnsk7_loyalty]` — ten sam blok (dla zalogowanych). Automatyczne stosowanie rabatu przy zamówieniu (kupon / auto-rabat) — do ustalenia w kolejnym etapie; na razie wyświetlanie poziomu.

---

## 7. Strona główna (z PDF — staging.kayer.pl)

**Uwaga:** Notatki klienta z PDF traktujemy jako wskazówki. **Priorytet ma UX i standardy e-commerce** — np. kategorie w menu/headerze zostawiamy (ułatwiają nawigację), nawet jeśli w PDF było „bez listy kategorii”.

- **Górny bar:** usunąć; pierwszy rząd: **logo, wyszukiwarka, konto**.
- **Menu:** **Sklep** (z rozwijanymi **kategoriami**), O nas, Pomoc, Kontakt. Dolne podmenu uprościć, nie rozbudowywać.
- **Baner:** „Dlaczego warto kupować u nas” — bez zbędnych przycisków.
- **Blok kategorii:** wszystkie nasze kategorie towarów.
- **Jeden blok usunąć** (zgodnie z adnotacją w PDF).
- **Blok „przydatne info”** — zostawić; w przyszłości sekcje z pomocą/FAQ.
- **Karuzela:** „losowa” karuzela produktów (wylosowane, do wyboru).
- **Loyalty:** blok z opisem systemu rabatów (1000→5%, 3000→10%, 5000→15%, 10000→20%) — „na test” / informacyjnie.
- **Instagram:** zostawić; ewentualnie mniej postów — **1 rząd**.
- **Stopka:** bez długiej listy kategorii — zamiast tego **link do sklepu** (katalog).

---

## 8. Powiązane dokumenty

- **UI / wygląd:** [UI_SPEC.md](UI_SPEC.md) — spec header, footer, główna, karta produktu (09_ui_designer). Skills: `ecommerce_header_footer_guidelines`, `product_card_visual`, `visual_design_woo`.
- **Loyalty (logika):** [.agents/skills/woo_loyalty_design/SKILL.md](../.agents/skills/woo_loyalty_design/SKILL.md).
- **Zadania:** dopisać do backlog / sprintu: widżet Instagram, strona z tabelą dostaw, kontakt w stopce, automatyzacja lojalności w panelu, przebudowa głównej według PDF.

---

## 9. Allegro trust / opinie (social proof)

Klient chce przenieść „zaufanie z Allegro” na stronę. Do wdrożenia są 2 warstwy:

1. **Warstwa seller trust (wdrożona):**
   - Shortcode: `[mnsk7_allegro_trust]`
   - Pokazuje: % poleceń, pozytywne/negatywne oceny, status „potwierdzone zakupem”, link do profilu Allegro.
2. **Warstwa opinii produktowych (do decyzji):**
   - Opcja A: ręczna selekcja cytatów (legalnie i czytelnie) + link „zobacz wszystkie opinie”.
   - Opcja B: import do Woo review (jeśli regulamin/prawa do treści na to pozwalają).
   - Opcja C: osadzenie oficjalnego widgetu/sekcji Allegro, jeśli dostępne.

Rekomendacja: najpierw seller trust + CTA do Allegro, potem osobny mini-projekt „opinie produktowe” po weryfikacji prawnej i technicznej.

**Dodatkowo wdrożone shortcode:**

- `[mnsk7_allegro_reviews_pages from="1" to="20"]` — lista linków do stron ocen `page=1..20`.
- `[mnsk7_allegro_reviews]` — sekcja opinii (ręczne cytaty przez filtr) + automatyczny fallback do linków wszystkich stron ocen.

Uwaga techniczna: Allegro stosuje ochronę anti-bot (`Please enable JS...`), więc pełny automatyczny scraping HTML z serwera bywa niestabilny.
