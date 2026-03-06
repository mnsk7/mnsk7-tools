# Deliverables — jednostki dostawy (nie dokumenty, ekrany i shell)

Dekompozycja po **kontrolowanych jednostkach dostawy**, nie po rolach ani typach dokumentów. Każdy deliverable ma acceptance i jest zamykany dopiero po review (SCREEN_REVIEW_PACK / QA sign-off).

Źródło: QUALITY_GATES.md, UI_SPEC_V2.md. Referencja katalogu (tabele): docs/REFERENCE_SANDVIK_COROMANT.md.

---

## Deliverable 1 — Public shell

- Header: skład, kolejność, mobile (burger), search/cart/account ikony.
- Public nav: tylko Sklep, O nas, Pomoc, Kontakt (bez Edit Profile, Login, Register, Wishlist, Zamówienie).
- Mobile menu: zachowanie, bez śmieci.
- Footer: jeden blok kontaktowy, jeden infoblok, dostawa/opłata, Instagram (jeden rząd).
- Search / cart / account states (pusto, nie pusto, błędy).
- Typography, spacing, button system, badges (design tokens z UI_SPEC_V2).

**Acceptance:** Screen Review Pack — Header, Footer approved. IA sign-off: brak account-flow w menu.

---

## Deliverable 2 — PLP (category)

- Szablon kategorii: H1, opis, breadcrumb.
- Filtry (URL lub drawer): diameter, shank, type, material.
- Siatka kart: card anatomy (image, title, key spec line, price, CTA).
- Sortowanie, puste stany.
- Referencja: tabele w kategoriach (Sandvik) — docs/REFERENCE_SANDVIK_COROMANT.md.

**Acceptance:** Screen Review Pack — PLP approved. Checklist BACKLOG_BY_TEMPLATES (category) ready.

---

## Deliverable 3 — PDP (product)

- Buy box: galeria, tytuł, cena, warianty, stock, trust, qty + CTA.
- Jeden primary CTA (Dodaj do koszyka).
- Key params — krótkie etykiety.
- Trust strip bez duplikatów.
- Opis, powiązane (ta sama anatomia karty co PLP).

**Acceptance:** Screen Review Pack — PDP approved. Conversion sign-off: brak konkurencyjnych CTA.

---

## Deliverable 4 — Checkout path

- Cart: lista, suma, próg darmowej dostawy, przycisk do checkout.
- Checkout: minimalne pola, trust przy CTA, brak rozpraszaczy.
- Form logic, walidacja.

**Acceptance:** Screen Review Pack — Cart, Checkout approved. Smoke: ścieżka cart → checkout przechodzi.

---

## Deliverable 5 — Supporting pages

- Kontakt: H1, kanały (tel, email, IG), godziny, formularz.
- Dostawa i płatności: H1, tabele, FAQ (accordion).
- Legal/help: regulamin, polityka, pomoc — jeden link w menu, bez duplikatów.

**Acceptance:** Screen Review Pack — Footer approved. Jeden blok Kontakt w stopce; jeden blok dostawy.

---

Kolejność: D1 (shell) → D2 (PLP) → D3 (PDP) → D4 (checkout) → D5 (supporting). Dopiero po zamknięciu D1–D4 oceniamy „czy sklep jest normalny”.
