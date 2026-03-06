# AS-Is Audit — mnsk7-tools.pl

**Date:** 2026-03-06  
**Agent:** 00_as_is_audit  
**Method:** File system scan (wp-content/themes/mnsk7-storefront, mu-plugins, docs), VERIFICATION_CHECKLIST, MARKETING_UX_REVIEW, PDP_IMPROVEMENTS_REVIEW. Live/staging not crawled in this run; items unchecked on production are marked **ASSUMPTION**.

---

## 1. Key pages

| Page type | URL / template | Source / note |
|-----------|----------------|---------------|
| **Home** | `/` | `mnsk7-storefront/front-page.php` |
| **Shop (catalog)** | WooCommerce shop base (e.g. `/sklep/`) | WooCommerce; category uses `archive-product.php` |
| **Category** | `/product-category/{slug}/` | `mnsk7-storefront/woocommerce/archive-product.php` (chips, attribute filters, table) |
| **Product** | `/product/{slug}/` | `mnsk7-storefront/woocommerce/single-product.php`, `content-single-product.php` |
| **Cart** | `/koszyk/` (Polish) or `/cart/` | WooCommerce; blocked from index in `robots.txt` |
| **Checkout** | `/zamowienie/` or `/checkout/` | WooCommerce; blocked from index |
| **Account** | `/moje-konto/` or `/my-account/` | WooCommerce; Disallow in `robots.txt` |
| **Search** | `/?s=...` or `/search/` | Disallow in `robots.txt` |
| **Delivery** | Page with template "Dostawa i płatności" | `mnsk7-storefront/page-dostawa.php` |
| **Contact** | "Kontakt" | `mnsk7-storefront/page-kontakt.php` |
| **SEO landing (CNC)** | e.g. `/cnc-frezy/` | `mnsk7-storefront/page-cnc-frezy.php` |
| **Material landings** | frezy-aluminium, frezy-mdf, frezy-stali | `page-frezy-*.php`, `page-category-landing.php` |

**ASSUMPTION:** Exact shop/cart/checkout/account slugs depend on WooCommerce permalink settings on server.

### Тема (актуальное состояние)

- **Parent:** Storefront (official WooCommerce theme). В репо: `wp-content/themes/storefront` — деплоится вместе с child.
- **Child:** `mnsk7-storefront` (Template: storefront). Child-theme есть — правки не теряются при обновлении parent.
- Кастом: header/footer свои (mnsk7-header, mnsk7-footer), Woo overrides в child (archive-product, content-single-product, single-product, global wrappers, content-product-table-row), CSS parts (01–24 в `assets/css/parts/`).

### Woo overrides (mnsk7-storefront)

- `woocommerce/archive-product.php` — категория: chips подкатегорий, chips атрибутов (`mnsk7_get_archive_attribute_filter_chips`), таблица товаров.
- `woocommerce/single-product.php`, `content-single-product.php` — PDP: buybox (title, price, availability, key params, CTA, trust badges).
- `woocommerce/global/wrapper-start.php`, `wrapper-end.php`, `content-product-table-row.php` — обёртки и строка таблицы каталога.

**Staging vs prod:** [USER_JOURNEY_STAGING_VS_PROD.md](USER_JOURNEY_STAGING_VS_PROD.md). Staging: https://staging.mnsk7-tools.pl; отдельная БД; staging-safety mu-plugin (no mail, payments off, blog_public=0).

---

## 2. Catalog / content

### 2.1 Attributes

- **From code (mnsk7_get_key_param_attributes, mnsk7_get_filter_attribute_order):** srednica, fi (trzpienia), dlugosc-robocza-h, dlugosc-calkowita-l, r, typ, ksztalt, zastosowanie (pa_* variants). **ASSUMPTION:** заполненность по каталогу и наличие материал/покрытие/зубья — проверить в Admin → Attributes и на 3–5 товарах.

### 2.2 SKU

- **ASSUMPTION:** 423 product meta with `_sku` (from prior audit); формат смешанный. Проверка: скрипт `scripts/check-db-catalog.sh` или phpMyAdmin.

### 2.3 Descriptions (structure: benefit vs parameters)

- **ASSUMPTION:** Не проверено по коду; нужна ручная проверка 3–5 карточек (польза vs спецификации vs совместимость). Клиент: карточки перегружены текстом, нет быстрого доступа к параметрам — частично закрыто блоками «Kluczowe parametry» и «Do czego» в PDP.

### 2.4 Photos

- **ASSUMPTION (prior):** ~97% изображений без alt — высокий SEO/accessibility риск. Размеры/WebP: docs отмечают тяжёлые PNG (3–4 MB), мало WebP.

---

## 3. UX / conversion

### 3.1 CTA, price, delivery, returns near CTA

- **Observed:** PDP — `woocommerce_single_product_summary`: price (15), availability (8), key params (21), add-to-cart (30), trust badges (32). MU-plugin: `mnsk7_single_product_availability`, `mnsk7_single_product_trust_badges`, `mnsk7_dostawa_vat_html`. «X osób kupiło» (total_sales) в trust badges — рядом с CTA. **ASSUMPTION:** визуальная заметность и порядок на живой странице — проверить на staging.

### 3.2 Filters / sort

- **Observed:** В теме — chips по таксономии и атрибутам в `archive-product.php`; `woocommerce_product_query` в theme добавляет `tax_query` по `filter_<attr>`. MU-plugin деактивирует woo-product-filter и wc-product-table-lite при первом заходе в admin. **ASSUMPTION:** на проде — какие фильтры активны и индексируются ли URL фильтров — ручная проверка.

### 3.3 Mobile

- **Observed:** Viewport в header; CSS parts 20 (tablet), 21 (mobile), 22 (touch targets). **ASSUMPTION:** качество мобильного UX и Core Web Vitals — Lighthouse/ручная проверка.

### 3.4 Trust (policies, guarantee, reviews)

- **Observed:** Шаблоны dostawa, kontakt; shortcodes `[mnsk7_rating]`, `[mnsk7_bestsellers]`, `[mnsk7_dostawa_vat]`; trust badges в PDP и footer на shop/product/cart/checkout. **ASSUMPTION:** видимость политик и отзывов в футере/на ключевых страницах — проверить на сайте.

---

## 4. SEO

### 4.1 Title / H1 on categories and products

- **Observed:** Theme: `document_title_parts` fallback для главной (AS_IS_STAGING_TOP5). **ASSUMPTION:** уникальные Title/H1 по категориям и товарам — GSC или View Source.

### 4.2 Duplicates, thin pages (filters)

- **ASSUMPTION:** URL фильтров (query params) — индексация и noindex/canonical зависят от плагинов и Yoast; проверить в GSC.

### 4.3 Indexing (robots.txt, sitemap)

- **Repo `robots.txt`:** Disallow: wp-admin, xmlrpc, wp-login, cart, checkout, my-account, moje-konto, author, feed, embed, trackback, search, ?s=, add-to-cart, utm_*, openstat. Sitemap: sitemap_index.xml. **ASSUMPTION:** прод может отличаться.

### 4.4 Schema (Product, BreadcrumbList, FAQ)

- **ASSUMPTION:** Yoast SEO; дубли schema с schema-and-structured-data-for-wp отмечены в docs — риск двойного JSON-LD.

---

## 5. Performance

### 5.1 Heavy images, CLS/LCP

- **From docs:** большие PNG, мало WebP. PDP/PLP изображения — кандидаты в LCP. **ASSUMPTION:** фактические LCP/CLS — Lighthouse/Real User Monitoring.

### 5.2 Caching (exclusions for cart/checkout)

- **ASSUMPTION:** Исключения cart/checkout/my-account должны быть в настройках активного кеш-плагина на сервере.

---

## 6. Tech

### 6.1 Theme

- **Parent:** Storefront. **Child:** mnsk7-storefront. Кастомизация: свой header/footer, отключение части storefront_header/footer, свои Woo шаблоны и CSS parts.

### 6.2 Woo overrides (child)

- archive-product.php, single-product.php, content-single-product.php, global/wrapper-start|end, content-product-table-row.php.

### 6.3 Plugins (from docs; not re-scanned)

- Конфликты/дубли ранее отмечены: несколько фильтров, 2× Przelewy24, 2× wishlist, 2× page builder, 2× profile, 2× GTM, 2× schema, 2× Facebook. Кеш: LiteSpeed/Seraphinite/WP Rocket — уточнять на сервере. **ASSUMPTION:** полный список активных плагинов только на хосте.

### 6.4 Custom code

- **Theme:** `mnsk7-storefront/functions.php` — enqueue, fonts, storefront hooks, header actions, breadcrumbs, body class; page templates (dostawa, kontakt, cnc-frezy, frezy-*, category-landing, seo).
- **MU-plugins (repo):** `mnsk7-tools.php` (bootstrap, xmlrpc block, deactivate filter/table plugins, parent theme notice, nav menu filter, key params, availability, trust/dostawa/VAT, shortcodes rating/bestsellers/instagram), `staging-safety.php`, `automation-by-installatron.php`. Отдельной папки `inc/` в репо нет — вся логика в одном MU-файле.

### 6.5 Security

- xmlrpc blocked in mu-plugin (403 on XMLRPC_REQUEST). **ASSUMPTION:** limit-login-attempts, backups, DISALLOW_FILE_EDIT — на сервере.

---

## 7. Вёрстка и CSS (as-is)

Цель: зафиксировать все недочёты по внешнему виду для последующего исправления (в т.ч. «поле поиска меньше слова поиск», «X osób kupiło» незаметно — уже исправлено в коде: trust рядом с CTA).

### 7.1 Наблюдения из кода и отчётов

| Проблема | Где | Приоритет |
|----------|-----|-----------|
| **Дубли в header** | В `header.php` внутри `.mnsk7-header__nav` продублированы: второй логотип (site-branding), второе меню (main-navigation), второй блок Moje konto + koszyk (mnsk7-header-actions). Есть лишний `<?php endif; ?>` без пары `if`. В DOM два меню и два набора действий. | P0 (конверсия, доверие, доступность) |
| Контраст и читаемость | MARKETING_UX_REVIEW: тёмный текст на тёмном фоне в ряде секций (header/listing/single). | P0 |
| Header выглядит как staging | Красный staging-баннер, слабая иерархия — «не готовый к продаже» вид. | P1 |
| Нет единого design system | Разные стили секций — эффект «склейки». | P1 |
| Дубли пунктов меню | «Dostawa i płatności» и «Dostawa i platnosci» (разное написание) в навигации. | P1 |
| Поле поиска | Агентское требование: «поле для поиска меньше чем слово поиск» — проверить на staging (где выводится поиск). | P2 |
| Длина заголовка карточки | Исправлено: line-clamp 2 в 05-plp-cards.css. | — |
| «X osób kupiło» | В коде уже в buybox (priority 32), рядом с CTA; визуальная заметность — на усмотрение UI. | P2 |

### 7.2 Что проверить вручную (staging/prod)

- Размер и читаемость поля поиска vs подпись «Поиск».
- Контраст текста и цен на всех ключевых экранах (AA).
- Отсутствие дублей меню/лого/actions после правки header.
- Мобильный вид: touch targets, порядок блоков на PDP.

---

## 8. Report file paths

| Document | Path |
|----------|------|
| AS-IS Audit | `/docs/AS_IS_AUDIT.md` |
| AS-IS Backlog | `/docs/AS_IS_BACKLOG.md` |
| AS-IS Risks | `/docs/AS_IS_RISKS.md` |

---

## 9. Feedback ze zrzutów ekranu (2026-03-06)

Źródło: 5 zrzutów stagingu + komentarze użytkownika.

| Problem | Opis | Działanie |
|--------|------|-----------|
| **Header wygląda okropnie** | Brak wyraźnego paska nagłówka; menu rozwalone — ogromna lista kategorii (Frez do drewna, do metalu, do kamienia…), logo „MNSK7 tools” w dziwnym miejscu, „Moje konto” i „Brak produktów w koszyku” wtopione w treść. Na innym ekranie w headerze tylko pole wyszukiwania i pusta przestrzeń. | Kompaktowy header: logo góra-lewo, zwarte menu (np. Sklep, Dostawa, Kontakt — bez wylewania całego drzewa kategorii), search, konto, koszyk. W WP: nie dodawać wszystkich kategorii do menu głównego. |
| **Chipsy OK, ale kontekst** | Np. przy wyborze **Zestawy** (Sets) filtr **Średnica** nie ma sensu — ma całkiem znikać. | Logika warunkowa: dla kategorii typu „Zestawy” nie pokazywać rzędu chipów Średnica (w kodzie: pominąć `pa_srednica` dla termu zestawy). |
| **Atrybuty tylko w wybranej kategorii** | Tagi i atrybuty (Średnica, Trzpień itd.) przy wybranej kategorii mają być **tylko te, które są w ofercie** (w tej kategorii). Nie wypisywać wszystkich możliwych wartości z bazy. | *Zrobione:* `mnsk7_get_archive_product_ids_for_chips()` pobiera ID produktów w kategorii (w magazynie, z uwzgl. bieżących filtrów); `get_terms( object_ids )` zwraca tylko termy przypisane do tych produktów. |
| **Wszystko się duplikuje** | Trzeci ekran: pod chipami długa linia tekstu „Filtruj: Średnica: … Trzpień: …” z listą wszystkich wartości; powtórzenia linków (Sklep, Dostawa i płatności, Kontakt w wielu miejscach). | Wyłączyć pluginy filtrów, które dublują UI (np. drugi blok „Filtruj”, drugi zestaw chipów). Zostawić jeden mechanizm filtrów (np. tylko chipsy w temacie). Sprawdzić w WP listę aktywnych pluginów filtrów. |
| **Tabela + filtr + wyszukiwarka** | Tabelę produktów zrobić lepiej; dodać filtr i **wyszukiwarkę** (np. nad tabelą). | Ulepszyć tabelę (style, czytelność); dodać pole wyszukiwania produktów nad tabelą (np. formularz ?s= lub parametr w URL sklepu). |
| **Header na ostatnim ekranie** | Na jednym z ekranów w headerze „coś nie tak” — minimalny header (tylko Szukaj), reszta pusta. | Jednolity header na wszystkich szablonach (shop, archiwum, strona, wyszukiwarka); unikać szablonów, które nie wywołują pełnego header.php lub nadpisują go. |

---

## 10. Pipeline: as-is + fix (po jednej części → kod → push)

| Część | As-is / cel | Status |
|-------|-------------|--------|
| **Header** | Krótszy pasek: mniejsza wysokość, logo, padding, menu/actions. | Zrobione: `--header-h` 48px, padding 0.75rem, logo max 36px, menu/links zwarte (04-header.css, 01-tokens). |

---

## 11. Progress (staging, deploy, scripts)

- Staging: https://staging.mnsk7-tools.pl; separate DB; `WP_ENVIRONMENT_TYPE=staging`; staging-safety mu-plugin.
- Deploy: rsync theme + mu-plugins (Makefile: deploy-files, staging-refresh).
- Catalog: `scripts/check-db-catalog.sh` (DB_PASS); `scripts/wp-create-pages.py` для SEO-страниц.
- Weryfikacja w kodzie: [VERIFICATION_CHECKLIST.md](VERIFICATION_CHECKLIST.md).
