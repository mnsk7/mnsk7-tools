# Sprint 01

*(Выход агента 01_product_manager; актуализировано 2026-03-06)*

Реально сделать за 1 спринт. Фокус: стабильность (E1), технический задел для каталога и карточки (E7, часть E2/E3). Тема: **mnsk7-storefront** (child Storefront).

---

## Цель спринта

- Устранить риски P0 (платежи, кеш, безопасность, бэкапы).
- Заложить основу для каталога и карточки: один фильтр, аудит атрибутов, overrides в child-theme и при необходимости mini-plugin.

---

## Задачи

### E1. Стабильность и деньги (P0)

| ID | Задача | Критерий готовности | Как тестировать |
|----|--------|--------------------|-----------------|
| S1-01 | Уточнить плагины оплаты: Przelewy24 и Przelewy24 Raty (рассрочка) — разные приложения, оба оставить. Удалить только реальный дубль (два плагина на один шлюз) (P0-01) | Нет двух плагинов на один и тот же gateway; обычная оплата и рассрочка работают раздельно | Чекаут — оба метода при необходимости; тестовый платёж без дублей |
| S1-02 | Выбрать один кеш-плагин (LiteSpeed или WP Rocket), отключить остальные (P0-02) | Активен один кеш; Seraphinite и второй отключены | Корзина/чекаут не отдают закешированную страницу другого пользователя |
| S1-03 | Настроить исключения кеша: cart, checkout, my-account (P1-05) | В настройках кеша эти страницы в exclude | Менять кол-во в корзине, открыть чекаут — контент актуальный |
| S1-04 | ~~Заблокировать xmlrpc.php (P0-03)~~ | **Zrobione:** mu-plugin `mnsk7-tools.php` — przy `XMLRPC_REQUEST` → `status_header(403); exit;` | Po deploy: `curl -I https://staging.mnsk7-tools.pl/xmlrpc.php` → 403 |
| S1-05 | Проверить бэкапы; при отсутствии — добавить UpdraftPlus или аналог (P0-04) | Есть расписание бэкапов БД и файлов | Настройки плагина / хостинга |

### E7 + задел E2/E3. Технический фундамент

| ID | Задача | Критерий готовности | Как тестировать |
|----|--------|--------------------|-----------------|
| S1-06 | ~~Создать mu-plugin для бизнес-логики (P1-06)~~ | **Zrobione:** `wp-content/mu-plugins/mnsk7-tools.php` — key params, availability, trust, shortcodes, nav filter, xmlrpc block | Tema + MU-plugin działają |
| S1-07 | ~~Woo overrides карточки в child (P1-07)~~ | **Zrobione:** `mnsk7-storefront/woocommerce/single-product.php`, `content-single-product.php` — buybox, hooki (availability, key params, trust badges) | Karta produktu na stagingu |

**Dodatkowo (2026-03-06):** usunięto duplikaty w headerze (drugie menu, drugi logo, drugi blok Moje konto/Koszyk) — header.php bez zduplikowanego bloku Storefront.

**05_theme_ux_frontend + 04_woo_engineer (2026-03-06):** Wdrożenie zgodnie z UI_SPEC_V2 i ARCHITECTURE: (1) 06-single-product.css — usunięto zduplikowane/obcięte reguły, jedna spójna PDP; (2) 09-footer.css — tylko .mnsk7-footer*, usunięto legacy .site-footer--mnsk7; (3) 01-tokens.css — dodano --color-primary-pressed, --color-focus, skalę spacing (--space-4 … --space-64); (4) 17-buttons.css — min-height 44px (WCAG tap target), :focus-visible; (5) 04-header.css — tap target 44px i focus-visible dla menu, toggle, linków, koszyka; (6) footer.php — godziny „nd. zamknięte”; (7) wersja CSS 3.0.1.
| S1-08 | Оставить один фильтр-плагин, отключить три остальных (P1-01) | В админке активен один фильтр; перед отключением — проверить Search Console на URL фильтров (R02) | Список плагинов; при необходимости редиректы на новые URL |

### E2. Каталог — подготовка

| ID | Задача | Критерий готовности | Как тестировать |
|----|--------|--------------------|-----------------|
| S1-09 | Аудит атрибутов товаров: заполненность материал/Ø/хвостовик/длина/зубья (P1-02) | Отчёт или таблица: какие атрибуты есть, % заполнения по ключевым | WP Admin → Products → Attributes; выборочно товары; при необходимости скрипт по БД |
| S1-10 | ~~Woo override страницы категории (P3-01)~~ | **Zrobione:** `mnsk7-storefront/woocommerce/archive-product.php` — chips podkategorii, chips atrybutów, tabela produktów | Strona kategorii na stagingu |

---

## Не входят в Sprint 01 (следующие спринты)

- Перестройка структуры каталога (UX-01) и настройка фильтров по логике выбора (UX-02) — после аудита атрибутов.
- Контент карточки (блок параметров, «подходит для») — после S1-07, в Sprint 02.
- Отзывы, рейтинг, блок хитов (UX-04), наличие/доставка/VAT на видных местах (UX-06).
- SEO целевые страницы, раздел статей (UX-05).
- Конвертация изображений WebP (P1-03), снятие дублей GTM/Pixel (P2-05, P2-06).
- Удаление дублей вишлиста/builder/профиль (P1-04) — можно часть в S1, если не раздувать спринт.

---

## Риски и допущения

- **R02:** при отключении трёх фильтр-плагинов возможны 404 по старым URL. Перед S1-08 проверить Search Console; при необходимости настроить редиректы.
- **Assumption:** на стейдже тестовый платёж не обязателен (staging-safety отключает реальные платежи); достаточно проверки на проде или тестовом режиме Przelewy24.

---

## Итог спринта

- Сайт без дублей платежа и кеша; xmlrpc закрыт; бэкапы проверены/настроены.
- Один активный фильтр-плагин; аудит атрибутов выполнен.
- Child-theme с overrides карточки товара (и при желании категории); кастомная логика вынесена в plugin/mu-plugin.
- Основа для Sprint 02: настройка фильтров под логику выбора, блок параметров в карточке, доверие (отзывы/наличие/доставка).
