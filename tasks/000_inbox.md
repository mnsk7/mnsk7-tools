# Inbox

Задачи и фиксы от агентов (QA, PM и др.). Переносим в спринты по приоритету.

---

## Pipeline 2026-03-10 (staging jako źródło prawdy)

- **STAGING_PROGRESS.md** — stan strony https://staging.mnsk7-tools.pl: co zrobione vs REQUIREMENTS. Zamiast „wywiad klienta” używamy stagingu do oceny postępu.
- **Kolejność agentów:** as-is (gotowe) → client_discovery (gotowe) → 02_growth_seo, 03_wp_architect (dokumenty gotowe) → **01_product_manager** — zaktualizowane 020/030 pod staging. **Następni:** 09_ui_designer (UI_SPEC_V2), 05_theme_ux_frontend + 04_woo_engineer (kod), 08_qa_security (QA_REPORT), 06_devops_github + 07_server_ops (deploy).
- **2026-03-10:** Uruchomiono **09_ui_designer** — zaktualizowano UI_SPEC_V2: §11 weryfikacja na staging, §12 checklista WCAG AA (W1–W7) dla QA. Uruchomiono **08_qa_security** — QA_REPORT: odniesienie do STAGING_PROGRESS i UI_SPEC_V2 §12 (WCAG).
- **2026-03-10:** Uruchomiono **05_theme_ux_frontend + 04_woo_engineer** (kod): (1) **focus-visible** dla input/select/textarea (17-buttons.css, main.css) — WCAG 2.4.7, UI_SPEC_V2 §12 W3; (2) **touch targets 44px** — paginacja, .woocommerce-ordering select, .quantity .qty (22-touch-targets.css, main.css). Do weryfikacji na staging: QA_REPORT §5 (UI-1–UI-5) i §12 (W1–W7).

---

## Zrobione (2026-03) — wrappers, tabela PLP, pluginy

- [x] **Обёртки (tech-storefront → Storefront):** header/footer + Woo wrapper sprawdzone i poprawione. Jeden `</div>` zamyka `#content`, drugi `#page` po stopce. Dodane: `woocommerce/global/wrapper-start.php`, `wrapper-end.php` w mnsk7-storefront; dokument `docs/WRAPPERS_LAYOUT.md`.
- [x] **Tabela w kategoriach + чипсы:** Na archiwum kategorii/tagu (product_cat, product_tag) — tabela towarów (zdjęcie, nazwa, cena, akcja) + rząd чипсов (podkategorie lub top kategorie). Sklep główny = siatka. Pliki: `archive-product.php`, `content-product-table-row.php`, `24-plp-table.css`.
- [x] **Wyłączenie pluginów:** Product Filter (WBW) i Product Table Lite — wyłączane jednorazowo przez mu-plugin `mnsk7-tools.php` (opcja `mnsk7_plugins_filter_table_deactivated`). Po wejściu w admina pluginy są dezaktywowane.
- [x] **Terminologia:** W dokumentacji i komentarzach: чипсы (nie „чипы”).

---

## Z feedbacku 2026-03-06 (zrzuty, as_is)

- [x] **FB-02 (chipsy):** dla kategorii „Zestawy” ukryty rząd Średnica — w `mnsk7_get_archive_attribute_filter_chips()` pomijamy `pa_srednica`, gdy slug/nazwa termu zawiera „zestaw”.
- [x] **FB-01 (header):** w menu głównym max 7 pozycji najwyższego poziomu (+ dzieci) — filtr `wp_nav_menu_objects` w theme.
- [x] **FB-05 (tabela + szukaj):** nad tabelą PLP dodany formularz wyszukiwarki (input + przycisk Szukaj); style w 24-plp-table.css.
- [x] **FB-03:** chipy atrybutów — tylko wartości mające produkty w bieżącej kategorii (w magazynie); `mnsk7_get_archive_product_ids_for_chips()` + get_terms z object_ids.
- [ ] **FB-04:** na stagingu wyłączyć wtyczki filtrów dublujące blok „Filtruj” — skrypt: `make deactivate-filter-plugins` lub `./scripts/staging-deactivate-filter-plugins.sh` (SSH + WP-CLI z .env).
- [ ] **FB-06:** sprawdzić na stagingu, że header jest taki sam na shop/archiwum/wynik wyszukiwania — w temacie tylko header.php, get_header() i get_header('shop') ładują ten sam plik.

**Instagram (posty się nie wyświetlają):** shortcode teraz (1) używa opcji `mnsk7_instagram_post_urls` przed scrapingiem, (2) zawsze ma fallback na domyślne 3 linki, (3) gdy oEmbed nie działa — pokazuje karty z linkiem (ikona IG + „Zobacz post"). Style w 08-home-sections.css dla `.mnsk7-instagram-feed__link`. Doc: INSTAGRAM_EMBED.md.

---

## Z 06_devops_github + 07_server_ops (2026-03-06)

- [x] Zaktualizować dokumentację: theme w repozytorium = storefront + mnsk7-storefront (DEPLOY_PLAYBOOK, STAGING_AND_GITHUB, SERVER_OPS).
- [x] Poprawka: deploy trigger = push do `main` (nie staging) w SERVER_OPS_CYBERFOLKS.
- [x] Rollback w DEPLOY_PLAYBOOK: mnsk7-storefront (zamiast tech-storefront).
- [x] Po wdrożeniu: dopisać w checklist link do QA_REPORT §5 (weryfikacja UI).

---

## Z QA / 08_qa_security (2026-03-05, 2026-03-06)

- [ ] Przed S1-08 (wyłączenie 3 filtrów): sprawdzić w Search Console, które URL-e filtrów są w indeksie; zaplanować przekierowania (R02).
- [x] Po wdrożeniu S1-07: w smoke dodać weryfikację strony pojedynczego produktu — w QA_REPORT jako S8.
- [ ] Po deploy na staging: wykonać smoke S1–S9 oraz weryfikację UI (QA_REPORT §5: UI-1–UI-5 — header, PDP, footer, tap targets, mobile).

---

## Z wymagań klienta (kontakt, dostawa, lojalność, UI)

- [x] **Kontakt w stopce:** w kodzie (mu-plugin) — email, tel, godziny, Instagram; wyświetlane globalnie w stopce. Źródło: [CONTACT_DELIVERY_LOYALTY.md](../docs/CONTACT_DELIVERY_LOYALTY.md). W WP: ewentualnie dodać stronę Kontakt z `[mnsk7_contact_info]`.
- [x] **Widżet Instagram** w stopce: shortcode `[mnsk7_instagram_feed]`; w stopce jest skrót (limit 1). Na głównej wstawić pełny blok — [HOMEPAGE_AND_PAGES.md](../docs/HOMEPAGE_AND_PAGES.md).
- [x] **Strona z tabelą dostaw:** utworzona przez API + template `page-dostawa.php` (id 35161).
- [ ] **Loyalty w panelu:** automatyczne progi w roku (1000→5%, 3000→10%, 5000→15%, 10000→20%); wyświetlanie w Moje konto, auto-rabat przy zamówieniach.
- [ ] **Główna (z PDF):** górny bar off; logo + search + konto; menu Sklep / O nas / Pomoc / Kontakt; baner „dlaczego my”; kategorie; karuzela produktów; blok lojalności; Instagram 1 rząd; stopka bez listy kategorii — link „Sklep”.
- [ ] **Karta produktu:** redesign wg [product_card_visual](../.agents/skills/product_card_visual/SKILL.md) — hierarchia, odstępy, jeden CTA; spec od 09_ui_designer → wdrożenie 05/04.

---

## Rework po audycie marketing/UX (2026-03-05) — RETURN TO AGENTS

Źródło: [MARKETING_UX_REVIEW_2026-03-05.md](../docs/MARKETING_UX_REVIEW_2026-03-05.md)

- [ ] **P0 / 09_ui_designer:** przygotować UI_SPEC v2 (header/home/PLP/PDP/footer), spójny design system, mobile-first, WCAG AA.
- [ ] **P0 / 09_ui_designer:** utworzyć i utrzymywać `docs/UI_SPEC_V2.md` jako single source of truth dla Header/Home/PLP/PDP/Footer/Kontakt/Dostawa i płatności (mobile-first).
- [ ] **P0 / 09_ui_designer:** zdefiniować design tokens (kolory, typografia, spacing, radii, shadows) i mapowanie na CSS variables do wdrożenia przez `05_theme_ux_frontend`.
- [ ] **P0 / 09_ui_designer:** opisać komponenty krytyczne dla konwersji i ich stany (button, input/search, product card, buy box PDP, menu, trust badges, alert, accordion).
- [ ] **P0 / 09_ui_designer:** rozpisać UX hierarchy (home -> PLP -> PDP -> cart) i wskazać jeden główny CTA per ekran; ograniczyć konkurencyjne akcje.
- [ ] **P0 / 09_ui_designer:** przygotować listę elementów do usunięcia/uproszczenia (duplikaty menu, nadmiar sekcji home, niespójne style, słaby kontrast).
- [ ] **P1 / 09_ui_designer:** doprecyzować wzorzec stron pomocniczych (`Kontakt`, `Dostawa i płatności`) z responsywnymi tabelami/accordion i spójnym stylem informacyjnym.
- [ ] **P1 / 09_ui_designer:** dostarczyć checklistę WCAG AA do QA (kontrast, focus-visible, tap-target >= 44x44, czytelność cen i CTA na mobile).
- [ ] **P0 / 05_theme_ux_frontend:** przebudować warstwę wizualną i layout (usunąć chaos, wzmocnić CTA, uprościć sekcje above-the-fold).
- [ ] **P0 / 05_theme_ux_frontend + 04_woo_engineer:** usunąć duplikaty w nawigacji i stronach pomocniczych (w menu nie może być jednocześnie „Dostawa i płatności” i „Dostawa i platnosci”).
- [ ] **P0 / 05_theme_ux_frontend:** naprawić kontrast tekstu/ceny (ciemny tekst na ciemnym tle) na home/PLP/PDP.
- [ ] **P1 / 04_woo_engineer:** uprościć PDP pod konwersję (cena+stock+CTA+trust na górze, parametry niżej), dodać czytelny social proof.
- [ ] **P0 / 08_qa_security:** pełny regres UI/UX desktop+mobile po wdrożeniu, kontrola duplikatów i kontrastu.

---

## Engineering backlog — Frontend rework execution (2026-03-05)

Źródło planu: [FRONTEND_REWORK_EXECUTION_PLAN.md](../docs/FRONTEND_REWORK_EXECUTION_PLAN.md)

- [ ] **P0 / 05_theme_ux_frontend:** przygotować baseline screenshot pack (home/PLP/PDP/cart/checkout + mobile) jako punkt odniesienia przed zmianami.
- [ ] **P0 / 05_theme_ux_frontend:** wykonać cleanup duplikatów IA/nawigacji (unikalne etykiety, brak powtórzeń sekcji/menu w DOM).
- [ ] **P0 / 05_theme_ux_frontend:** przebudować `front-page.php` pod skrócony flow zakupowy i ograniczyć sekcje konkurujące o CTA.
- [ ] **P0 / 05_theme_ux_frontend:** przepisać strukturę `footer.php` do jednego spójnego bloku informacji (kontakt/dostawa/social), bez dubli.
- [ ] **P0 / 05_theme_ux_frontend:** przeprowadzić refactor `assets/css/mnsk7-product.css` (global/components/pages) oraz usunąć legacy/duble selektorów.
- [ ] **P0 / 05_theme_ux_frontend:** domknąć kontrast i czytelność (WCAG AA) dla tekstów, cen, linków i CTA na home/PLP/PDP.
- [ ] **P1 / 05_theme_ux_frontend + 04_woo_engineer:** uprościć wizualną hierarchię PDP (`content-single-product.php`) pod cena+stock+CTA+trust nad foldem.
- [ ] **P1 / 05_theme_ux_frontend:** dopracować PLP/PDP/cart/checkout mobile (44px hit-area, brak overflow, spójny spacing).
- [ ] **P0 / 08_qa_security:** wykonać smoke regresji po każdej fazie (home/PLP/PDP/cart/checkout, desktop+mobile) i potwierdzić brak regresji.

---

## 04_woo_engineer — WOO conversion rework backlog (PDP/PLP/Checkout, 2026-03-05)

Źródło planu: [WOO_CONVERSION_REWORK_PLAN.md](../docs/WOO_CONVERSION_REWORK_PLAN.md)

### P0

- [ ] **P0 / 04_woo_engineer:** zaprojektować docelową kolejność bloków PDP nad foldem (title -> price -> stock -> variants -> primary CTA -> trust -> social proof) i mapę hooków Woo.
- [ ] **P0 / 04_woo_engineer:** przygotować implementację logiki social proof w `mu-plugin` (rating/opinie, kupione 30d, fallback bez danych).
- [ ] **P0 / 04_woo_engineer + 05_theme_ux_frontend:** usunąć duplikaty trust/dostawa/płatność na PDP (jedna sekcja per kontekst).
- [ ] **P0 / 04_woo_engineer:** ograniczyć konkurujące CTA na PDP do jednego primary (`Dodaj do koszyka`) i spójnych secondary.
- [ ] **P0 / 04_woo_engineer + 05_theme_ux_frontend:** ujednolicić PLP card flow (nazwa -> cena -> stock/rating -> CTA) bez overloadu badge/ikon.
- [ ] **P0 / 04_woo_engineer:** przygotować event taxonomy pomiaru konwersji (ATC, PDP->checkout start, checkout complete) bez zmian w core/pluginach.

### P1

- [ ] **P1 / 04_woo_engineer:** uporządkować checkout info hierarchy (mikro-trust przy podsumowaniu, bez elementów rozpraszających finalizację).
- [ ] **P1 / 04_woo_engineer:** wdrożyć feature flags dla etapowego rollout changes (PDP/PLP/checkout).
- [ ] **P1 / 04_woo_engineer + 08_qa_security:** przygotować porównanie before/after dla KPI konwersyjnych (14d vs 14d).
- [ ] **P1 / 04_woo_engineer:** opisać fallbacki danych (brak opinii, brak sprzedaży, brak ETA) i scenariusze edge-case.

### Acceptance criteria + metryki (Definition of Done)

- [ ] **AC-01:** Na PDP cena, stock i primary CTA są widoczne bez scrolla (desktop + mobile).
- [ ] **AC-02:** Na PDP nad foldem jest dokładnie 1 blok trust i 1 blok social proof (brak duplikatów).
- [ ] **AC-03:** Na PLP wszystkie karty mają spójny porządek informacji i CTA.
- [ ] **AC-04:** Checkout trust copy nie konkuruje z CTA finalizacji zamówienia.
- [ ] **AC-05:** Zmiany ograniczone do theme/mu-plugin; brak zmian w WP core i pluginach third-party.
- [ ] **METRIC-01:** CTR ATC na PDP >= +8% (target +15%).
- [ ] **METRIC-02:** Bounce PDP <= -5% (target -10%).
- [ ] **METRIC-03:** PDP -> checkout start >= +6% (target +12%).
- [ ] **METRIC-04:** Checkout completion (start -> order) >= +3% (target +7%).
- [ ] **METRIC-05:** PLP -> PDP click-through >= +5% (target +10%).
