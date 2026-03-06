# Screen Review Pack — gate przed zamknięciem implementacji

Cel: Jeden twardy gate po etapie UI. Każdy ekran ma review z głównym CTA, hierarchią, mobile, listą defektów i statusem approved / rejected.

Źródło: UI_SPEC_V2.md. Referencja katalogu (tabele): docs/REFERENCE_SANDVIK_COROMANT.md.

---

## Szablon per ekran

- Screenshot/mock: link lub URL staging + data
- Main CTA: jeden główny przycisk/akcja
- Hierarchy: poziom 1/2/3 (nagłówek, sekcje, tekst)
- Mobile: menu burger, CTA min 44px tap target
- Critical defects: lista (pusta = brak)
- Status: [ ] Approved  [ ] Rejected

---

## 1. Header

Wymagania: Brak w publicznym menu: Edit Profile, Login, Password Reset, Register, Wishlist, Zamówienie. Tylko: Sklep, O nas, Pomoc, Kontakt (ewent. Dostawa).

---

## 2. Home

Wymagania: Jedna dominująca ścieżka do PLP/PDP; bez duplikatu CTA Allegro; bez duplikatu Kontakt w treści.

---

## 3. PLP (Category)

Wymagania: Card anatomy (image, title, key spec line, price, CTA); referencja tabel: Sandvik (REFERENCE_SANDVIK_COROMANT.md).

---

## 4. PDP (Product)

Wymagania: Jeden primary CTA; krótkie etykiety key params; trust strip bez duplikatów.

---

## 5. Cart — Main CTA: Przejdź do kasy

---

## 6. Checkout — Main CTA: Złóż zamówienie. Brak rozpraszaczy; jeden blok trust przy CTA.

---

## 7. Footer

Wymagania: Brak duplikatu Kontakt; jeden blok dostawy; Instagram jeden rząd.

---

Wszystkie ekrany Approved = etap implemented and reviewed może być zamknięty. Rejected = backlog defektów; ponowny review po poprawkach.
