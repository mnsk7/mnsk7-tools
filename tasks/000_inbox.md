# Inbox

Задачи и фиксы от агентов (QA, PM и др.). Переносим в спринты по приоритету.

---

## Z QA / 08_qa_security (2026-03-05)

- [ ] Przed S1-08 (wyłączenie 3 filtrów): sprawdzić w Search Console, które URL-e filtrów są w indeksie; zaplanować przekierowania (R02).
- [ ] Po wdrożeniu S1-07: w smoke dodać weryfikację strony pojedynczego produktu (200, brak błędu PHP, hooki Woo).

---

## Z wymagań klienta (kontakt, dostawa, lojalność, UI)

- [x] **Kontakt w stopce:** w kodzie (mu-plugin) — email, tel, godziny, Instagram; wyświetlane globalnie w stopce. Źródło: [CONTACT_DELIVERY_LOYALTY.md](../docs/CONTACT_DELIVERY_LOYALTY.md). W WP: ewentualnie dodać stronę Kontakt z `[mnsk7_contact_info]`.
- [x] **Widżet Instagram** w stopce: shortcode `[mnsk7_instagram_feed]`; w stopce jest skrót (limit 1). Na głównej wstawić pełny blok — [HOMEPAGE_AND_PAGES.md](../docs/HOMEPAGE_AND_PAGES.md).
- [ ] **Strona z tabelą dostaw:** utworzyć stronę „Dostawa i płatności” i wstawić `[mnsk7_delivery_rules]`. InPost/DPD + free od 300 zł. Instrukcja: [HOMEPAGE_AND_PAGES.md](../docs/HOMEPAGE_AND_PAGES.md).
- [ ] **Loyalty w panelu:** automatyczne progi w roku (1000→5%, 3000→10%, 5000→15%, 10000→20%); wyświetlanie w Moje konto, auto-rabat przy zamówieniach.
- [ ] **Główna (z PDF):** górny bar off; logo + search + konto; menu Sklep / O nas / Pomoc / Kontakt; baner „dlaczego my”; kategorie; karuzela produktów; blok lojalności; Instagram 1 rząd; stopka bez listy kategorii — link „Sklep”.
- [ ] **Karta produktu:** redesign wg [product_card_visual](../.agents/skills/product_card_visual/SKILL.md) — hierarchia, odstępy, jeden CTA; spec od 09_ui_designer → wdrożenie 05/04.

---
