# Główna i kluczowe strony — co wstawić (shortcode’y)

Krótki przewodnik: jakie shortcode’y wstawić na stronę główną i na stronę „Dostawa i płatności”, żeby od razu mieć bloki z UI_SPEC i CONTACT_DELIVERY_LOYALTY.

---

## Strona główna

Kolejność bloków (wg UI_SPEC i notatek z PDF):

1. **Baner** — treść w edytorze (np. „Dlaczego warto kupować u nas” + dostawa następnego dnia, faktura VAT). Bez zbędnych przycisków.
2. **Kategorie** — zwykle widget „Kategorie produktów” lub lista linków w bloku. Kategorie Woo są też w menu pod „Sklep”.
3. **Karuzela / polecane:**  
   `[mnsk7_bestsellers]`  
   Opcjonalnie: `[mnsk7_bestsellers limit="8" title="Polecane produkty"]`
4. **Trust Allegro:**  
   `[mnsk7_allegro_trust]`  
   (100%, 383 pozytywne, link do profilu sprzedawcy.)
5. **Orientacyjny czas dostawy (na głównej):**  
   `[mnsk7_delivery_eta]`  
   (tekst zależny od wyboru kuriera w sesji; na głównej może być ogólny.)
6. **Loyalty (informacja):**  
   Wpisz w bloku tekst: system rabatów w panelu — 1000 zł → 5%, 3000 → 10%, 5000 → 15%, 10 000 → 20% w roku. Link do „Moje konto”.
7. **Opinie Allegro (strony 1–20):**  
   `[mnsk7_allegro_reviews]`  
   (fallback: linki do wszystkich stron ocen; jeśli dodasz cytaty przez filtr `mnsk7_allegro_review_quotes` — pokaże je.)
8. **Instagram:**  
   `[mnsk7_instagram_feed]`  
   (opcjonalnie `[mnsk7_instagram_feed posts="url1,url2,url3"]` jeśli masz konkretne posty do embedu.)

**Stopka:** kontakt, dostawa+VAT, ETA i Instagram są już wstrzykiwane globalnie w stopce (mu-plugin). W widgecie stopki możesz dodatkowo dać np. `[mnsk7_rating url="https://allegro.pl/uzytkownik/mnsk7-tools_pl"]`.

---

## Strona „Dostawa i płatności” (lub „Dostawa”)

W treści strony wstaw:

1. **Tabela warunków dostawy:**  
   `[mnsk7_delivery_rules]`  
   (InPost/DPD, kiedy zamówić → kiedy dostawa; darmowa dostawa od 300 zł.)
2. **Krótki tekst** (np. tylko Polska, InPost i DPD, faktura VAT na życzenie).
3. Opcjonalnie:  
   `[mnsk7_delivery_eta]`  
   (na stronie dostawy można zostawić bez parametru — w koszyku/checkout będzie ETA pod wybrany kurier.)

Link do tej strony warto dać w menu stopki (Pomoc / Dostawa i płatności).

---

## Inne shortcode’y (przypomnienie)

| Shortcode | Gdzie użyć |
|-----------|------------|
| `[mnsk7_contact_info]` | Stopka / strona Kontakt (jeśli chcesz osobny blok; w stopce jest już globalnie). |
| `[mnsk7_dostawa_vat]` | Karta produktu ma to wbudowane; ewentualnie w widgecie. |
| `[mnsk7_rating url="https://allegro.pl/uzytkownik/mnsk7-tools_pl"]` | Stopka lub sekcja „Opinie”. |
| `[mnsk7_allegro_reviews_pages from="1" to="20"]` | Osobna sekcja „Wszystkie strony z ocenami” (page 1–20). |

---

## Powiązane

- [UI_SPEC.md](UI_SPEC.md) — pełna spec header/footer/główna/karta.
- [CONTACT_DELIVERY_LOYALTY.md](CONTACT_DELIVERY_LOYALTY.md) — dane kontaktowe, reguły dostawy, lojalność, Allegro.
