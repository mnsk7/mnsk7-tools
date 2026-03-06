# UI Spec — mnsk7-tools.pl

**⚠️ SUPERSEDED — zastąpiony przez docs/UI_SPEC_V2.md.** Ten plik tylko do odniesienia historycznego. Wszystkie nowe decyzje i implementacja: UI_SPEC_V2.

*(Wyjście 09_ui_designer; wejście: REQUIREMENTS, CONTACT_DELIVERY_LOYALTY, skills)*

Specyfikacja układu i treści: header, footer, strona główna, karta produktu. **Priorytet: wygoda użytkownika i standardy e-commerce** — życzenia klienta stosujemy tylko gdy nie kolidują z UX.

---

## 1. Zasada priorytetu

- **Najpierw:** nawigacja, konwersja, czytelność (e-commerce best practices).
- **Potem:** notatki z PDF / życzenia klienta.
- Przykład: kategorie w menu głównym — **robimy** (ułatwiają znalezienie towaru), nawet jeśli w notatkach było „bez listy kategorii w stopce”.

---

## 2. Header (górny pasek)

### 2.1 Struktura (od lewej do prawej)

| Element | Opis |
|--------|------|
| **Logo** | Link do strony głównej. |
| **Menu główne** | Na desktop: w jednym rzędzie. **Sklep** — rozwijane (mega-menu lub dropdown) z **głównymi kategoriami** (typy narzędzi: frezy do drewna, do metalu, fazowniki, itd.). Następnie: **O nas**, **Pomoc**, **Kontakt**. Bez drugiego rzędu podmenu (uproszczenie). |
| **Wyszukiwarka** | Pole wyszukiwania (ikona lub rozwijane). |
| **Konto** | Ikona „Moje konto” (link do my-account). |
| **Koszyk** | Ikona koszyka z licznikiem. |

**Bez:** górnego bara z wieloma linkami (np. „Ubierz się w nas”, promocje w górze). Pierwszy widoczny blok = logo + menu + search + konto + koszyk.

### 2.2 Mobile

- Hamburger menu (menu główne + Sklep z kategoriami w drawrze).
- Wyszukiwarka i koszyk dostępne od razu (ikony w headerze).
- Logo wycentrowane lub z lewej.

---

## 3. Footer (stopka)

### 3.1 Bloki (kolejność dowolna, np. 3–4 kolumny na desktop)

| Blok | Zawartość |
|------|-----------|
| **Kontakt** | **Email:** office@mnsk7.pl (link mailto). **Tel:** +48 451696511 (link tel). **Godziny:** pn.–pt. 9.00–17.00, sb. 10.00–12.00, nd. zamknięte. |
| **Sklep / Kategorie** | Albo **zwięzła lista głównych kategorii** (5–7 linków), albo jedna link „Sklep” → katalog. Unikać długiej listy wszystkich podkategorii. |
| **Linki prawne i pomoc** | Pomoc, Dostawa i płatności, Regulamin, Polityka prywatności, Kontakt (strona). |
| **Dostawa (skrót)** | Tekst typu: „InPost i DPD. Dostawa następnego dnia przy zamówieniu do 15:00 (InPost) / 17:00 (DPD). Darmowa dostawa od 300 zł.” + link do strony z pełną tabelą. |
| **Instagram** | **Widżet:** jeden rząd zdjęć z profilu [@mnsk7tools](https://www.instagram.com/mnsk7tools/). Przycisk/link: „Obserwuj na Instagramie” → https://www.instagram.com/mnsk7tools/ . |

### 3.2 Wymagania

- Kontakt (email, tel, godziny) zawsze widoczny w stopce.
- Instagram: jeden rząd; nie przeładowywać.
- Na mobile: bloki w kolumnie, kontakt i linki nad foldem lub po krótkim scrollu.

---

## 4. Strona główna (kolejność bloków)

| # | Blok | Zawartość |
|---|------|-----------|
| 1 | **Baner** | Jedna sekcja „Dlaczego warto kupować u nas” (dostawa następnego dnia, jakość, faktura VAT). Bez zbędnych przycisków. |
| 2 | **Kategorie** | Wszystkie główne kategorie towarów (kafelki lub lista z ikonami) — linki do archiwum kategorii. |
| 3 | **Karuzela produktów** | Losowe / polecane / bestsellery (np. shortcode `[mnsk7_bestsellers]` lub osobna karuzela „Wylosowane dla Ciebie”). Produkty do kliknięcia. |
| 4 | **Loyalty (informacja)** | Blok tekstowy: system rabatów w panelu (1000 zł → 5%, 3000 → 10%, 5000 → 15%, 10 000 → 20% w roku). Informacyjnie, bez formularza. |
| 5 | **Przydatne info** | Sekcja na przyszłe FAQ / linki do Pomoc, Dostawa. Na razie można placeholder lub 1–2 linki. |
| 6 | **Instagram** | Jeden rząd postów + „Obserwuj na Instagramie”. Ten sam widżet co w stopce lub osobna sekcja. |

**Bez:** drugiego górnego bara, rozbudowanego podmenu pod menu głównym.

---

## 5. Karta produktu (single product)

### 5.1 Kolejność sekcji (od góry)

| # | Sekcja | Zawartość | Uwagi wizualne |
|---|--------|-----------|----------------|
| 1 | **Zdjęcia** | Galeria (główne + miniatury). | Lewa kolumna (desktop). Zoom opcjonalnie. |
| 2 | **Tytuł + cena** | Tytuł produktu, cena. | Wyraźna hierarchia; cena duża/czytelna. |
| 3 | **Kluczowe parametry** | Średnica, trzpień, długość, kąt, itd. — z atrybutów Woo. | Zwarty blok (np. tabela 2 kolumny, `<dl>`). Nie w długim tekście. |
| 4 | **Dostępność + dostawa** | Jedna linia: „W magazynie” / „Dostawa następnego dnia” / „Faktura VAT na życzenie”. | Blisko CTA; ikony opcjonalnie. |
| 5 | **CTA** | Przycisk „Dodaj do koszyka” (główny styl). Obok: wishlist, porównanie (jeśli są). | Jeden dominujący przycisk. |
| 6 | **Zastosowanie (Do czego)** | Lista zastosowań (z atrybutu / opisu). | Osobny blok, wizualnie oddzielony (ramka/tło). |
| 7 | **Schemat / wideo** | Placeholder lub przyszła treść (schemat parametrów, wideo). | Sekcja `.mnsk7-product-extra-media` — już w szablonie. |
| 8 | **Zakładki Woo** | Opis, Dodatkowe informacje, Recenzje. | Standard Woo. Bez dublowania pełnej listy parametrów w wielu miejscach. |

### 5.2 Zasady

- **Odstępy:** wyraźne marginesy między sekcjami (np. 1–1.5rem), żeby nie zlewało się w jeden blok.
- **Jedna główna akcja:** „Dodaj do koszyka” — reszta (wishlist, porównanie) drugoplanowa.
- **Mobile:** zdjęcie i CTA nad foldem; parametry i „Zastosowanie” do przewinięcia.

---

## 6. Rekomendacje wizualne (dla 05_theme_ux_frontend)

- **Kolory:** jeden kolor akcentu (np. przycisk „Dodaj do koszyka”, linki); tło neutralne (biały / jasnoszary).
- **Typografia:** jedna–dwie czcionki; nagłówki wyraźnie większe od ciała.
- **Przyciski:** główny CTA — większy, kontrastowy; linki i przyciski drugoplanowe — mniejszy nacisk.
- **Białe przestrzenie:** nie zagęszczać; odstępy między blokami ułatwiają skanowanie.
- **Zaufanie:** ikony lub krótki tekst przy CTA (dostawa, faktura, dostępność) — już w CONTACT_DELIVERY_LOYALTY i w shortcode `[mnsk7_dostawa_vat]`.

---

## 7. Powiązane pliki

- Dane kontaktowe i dostawy: [CONTACT_DELIVERY_LOYALTY.md](CONTACT_DELIVERY_LOYALTY.md).
- Wymagania funkcjonalne: [REQUIREMENTS.md](REQUIREMENTS.md).
- Implementacja: 05_theme_ux_frontend (tema, CSS, szablony), 04_woo_engineer (mu-plugin, shortcode’i, hooki). Karta produktu: overrides w `woocommerce/`, style w `assets/css/mnsk7-product.css`.
