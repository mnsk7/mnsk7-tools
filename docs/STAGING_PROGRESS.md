# Прогресс по staging.mnsk7-tools.pl

**Источник:** живой сайт https://staging.mnsk7-tools.pl (не интервью).  
**Дата снимка:** 2026-03-10.  
**Цель:** зафиксировать, что уже сделано относительно REQUIREMENTS и пайплайна, чтобы двигаться дальше с агентами.

---

## 1. Что уже есть на staging (по просмотру)

### Главная
- **Header:** логотип, меню (Sklep, Przewodnik, Dostawa i płatności, Kontakt), поиск (Szukaj produktów), Moje konto, корзина. Один набор элементов — без дублей меню/лого.
- **Контент:** H1 «Frezy CNC i narzędzia skrawające», блок «Bestsellery i polecane» с карточками и «Dodaj do koszyka», ссылка «Zobacz profil i opinie na Allegro», блок «Dlaczego kupujący nam ufają» (цитаты/отзывы), «Przeglądaj asortyment», «Program rabatowy dla stałych klientów», «Obserwuj nas na Instagramie».
- **Footer:** категории (Frez diamentowy, Frez spiralny…), Kontakt (formularz, email, tel, godziny), newsletter, polityки (cookie, prywatność), NIP/KRS/REGON.

### Каталог (Sklep)
- **URL:** /sklep/. Breadcrumbs (Strona główna › Sklep), H1 «Sklep».
- **Навигация:** блок «Kategorie» с ссылками на категории (Frez diamentowy, Frez spiralny, Frez do aluminium…), сортировка (Zamówienie w sklepie), пагинация (28 stron), «Wyświetlanie 1–12 z 325 wyników».
- **Сетка товаров:** карточки с изображением, названием, количеством, кнопкой «Dodaj do koszyka». Popup «Warunki dostawy» при заходе.

### Карточка товара (PDP)
- **Пример:** Frez palcowy do aluminium 1P | fi 1.5 x 4 x 38 | CNC VHM HRC 55 | DLC.
- **Есть:** хлебные крошки (Strona główna › Sklep › Frez spiralny › Frez do aluminium), H1, галерея изображений, выбор вариации (Średnica: 1,5 mm), **«✓ 4 w magazynie»**, цена (23,00 zł), кнопка «Dodaj do koszyka», блок **«Kluczowe parametry»** (Wymiary, Zastosowanie, Zalety technologiczne), кнопка «Pokaż opis», блок «Podobne produkty» (related).
- **Ссылки в карточке:** Katalog (chip «Sklep» переименован в «Katalog» по AS_IS pipeline).

### Доверие и конверсия
- На главной: отзывы/цитаты («Dlaczego kupujący nam ufają»), ссылка на Allegro, программа лояльности (rabat), Instagram.
- В футере: Dostawa i płatności, Kontakt, Zwroty i reklamacje, Regulamin, Polityka prywatności.

### Техническое
- Тема: mnsk7-storefront (child Storefront). Overrides: header, footer, archive-product, single-product, content-single-product.
- MU-plugin: mnsk7-tools (key params, availability, trust, shortcodes, xmlrpc block), staging-safety (no mail/payments, noindex).
- Staging: отдельная БД, deploy через Makefile (deploy-files, staging-refresh).

---

## 2. Соответствие REQUIREMENTS (кратко)

| Требование | Статус на staging |
|------------|-------------------|
| Каталог: тип → параметры, фильтры по логике выбора | Частично: категории по типу есть; chips/таблица на странице категории — в коде (archive-product.php); на /sklep/ — сетка без chips. Нужно проверить страницу категории (np. /product-category/frez-do-aluminium/) на chips + tabelę. |
| Карточка: ключевые параметры, наличие, доставка | **Zrobione:** Kluczowe parametry, „X w magazynie”, CTA. Доставка na następny dzień — w treściach/footer. |
| Доверие: отзывы, рейтинг, хиты | **Zrobione:** blok cytatów na głównej, link do Allegro, bestsellery. |
| Наличие, doставка, VAT | **Zrobione:** availability w PDP; Dostawa i płatności w menu i footer. |
| Header/footer kompaktowe, bez duplikatów | **Zrobione** (po poprawce z AS_IS). |
| SEO: целевые страницы, artykuły | Nie wdrożone — brak dedykowanych landingów pod zapytania (frezy do aluminium/MDF/drewna, CNC). |
| Мобильная версия | Do weryfikacji (viewport, touch targets — w kodzie 04-header.css 44px). |

---

## 3. Co dalej (kolejność agentów)

- **02_growth_seo:** SEO_PLAN, CONTENT_PLAN, TRACKING — już są; można zaktualizować pod staging (np. aktualne URL, sitemap).
- **03_wp_architect:** ARCHITECTURE, BACKLOG — już są; utrzymać spójność z kodem na staging.
- **01_product_manager:** zaktualizować **tasks/010_epics.md**, **020_sprint_01.md**, **030_sprint_02.md** — uwzględnić, co już zrobione na staging (S1-04, S1-06, S1-07, S1-10, header/footer/PDP), i wypisać **pozostałe zadania** na kolejne sprinty.
- **09_ui_designer:** UI_SPEC_V2 — jeśli potrzeba doprecyzowania pod staging.
- **05_theme_ux_frontend + 04_woo_engineer:** kod — dalsze taski z backlogu.
- **08_qa_security:** QA_REPORT po wdrożeniach.
- **06_devops_github + 07_server_ops:** deploy, staging refresh.

---

## 4. Pliki odniesienia

| Dokument | Ścieżka |
|----------|---------|
| Wymagania | docs/REQUIREMENTS.md |
| As-is audit | docs/AS_IS_AUDIT.md |
| As-is backlog | docs/AS_IS_BACKLOG.md |
| Architektura | docs/ARCHITECTURE.md |
| SEO plan | docs/SEO_PLAN.md |
| Sprint 01 | tasks/020_sprint_01.md |
| Sprint 02 | tasks/030_sprint_02.md |

Staging jest źródłem prawdy dla „co już działa”; dokumenty z agencji (REQUIREMENTS, AS_IS_*, ARCHITECTURE, SEO_PLAN) — dla „co ma być” i „co dalej”.
