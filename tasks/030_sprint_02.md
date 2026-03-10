# Sprint 02

*(Выход агента 01_product_manager; stan na staging 2026-03-10: docs/STAGING_PROGRESS.md)*

**Źródło prawdy:** staging.mnsk7-tools.pl. Większość zadań E3/E4 wdrożona (Kluczowe parametry, Zastosowanie, availability, trust badges, bestsellery, shortcodes). Pozostało: dopracowanie katalogu (chipsy na stronie kategorii), SEO-landingi, loyalty w panelu.

Фокус: каталог и подбор (E2), карточка товара (E3), начало доверия (E4). После Sprint 01 есть один фильтр, заполненные атрибуты и overrides карточки.

---

## Цель спринта

- Настроить каталог и фильтры по логике «тип → параметры» (UX-01, UX-02).
- Реализовать в карточке товара структурированный блок параметров и при возможности «подходит для / не подходит для» (UX-03).
- Добавить элементы доверия: блок популярных/хитов, явное отображение наличия и доставки на следующий день, информация о фактуре VAT (UX-04, UX-06).

---

## Задачи

### E2. Каталог и подбор

| ID | Задача | Критерий готовности | Как тестировать |
|----|--------|--------------------|-----------------|
| S2-01 | Перестроить/настроить структуру каталога: тип инструмента → параметры (UX-01) | Категории или таксономии отражают тип; нет смешения тип/материал/параметры в одну кучу | Навигация по каталогу; мнение клиента |
| S2-02 | Настроить фильтры по логике выбора: тип, Ø, хвостовик, длина, зубья, материал (UX-02) | На странице категории фильтры в нужном порядке и с нужными атрибутами | Выбрать тип → сузить по диаметру/хвостовику → результат релевантный |
| S2-03 | Проверить/добавить Woo override страницы категории при необходимости (P3-01) | Верстка категории под контролем child-theme | Отображение категории и фильтров на мобильном и десктопе |

**Zrobione:** S2-03 — w **mnsk7-storefront**: `woocommerce/archive-product.php` (chips podkategorii, chips atrybutów, tabela), `content-product-table-row.php`, `parts/24-plp-table.css`. Sklep (bez taksonomii) = siatka kart.

### E3. Карточка товара

| ID | Задача | Критерий готовности | Как тестировать |
|----|--------|--------------------|-----------------|
| S2-04 | Блок ключевых параметров в карточке: Ø, хвостовик, длина, зубья, материал (UX-03) | Параметры выведены структурированно; инженер видит их за 10–15 сек | Открыть 3–5 карточек, засечь время восприятия |
| S2-05 | Блок «подходит для / не подходит для» по материалу/операции (UX-03, частые вопросы) | Текст или атрибуты выводятся в карточке | Проверка на товарах с заполненным атрибутом применения |
| S2-06 | (По возможности) Схемы параметров или место под видео — без обязательной загрузки контента в спринте | Разметка/блок готов; контент можно добавить позже | Наличие блока в шаблоне |

**Zrobione (04/05):** S2-04, S2-05 — w `mnsk7-tools.php`: `mnsk7_single_product_key_params()`, `mnsk7_single_product_zastosowanie()`; wywołanie w `content-single-product.php`. Atrybuty: srednica, fi, długości, r, typ, zastosowanie (zgodnie z AS_IS_AUDIT F). S2-06 — blok `.mnsk7-product-extra-media` + `mnsk7_single_product_schema_video_placeholder()` (miejsce na schemat/wideo). S2-02 — helper `mnsk7_get_filter_attribute_order()` (kolejność atrybutów do konfiguracji filtra).

### E4. Доверие

| ID | Задача | Критерий готовности | Как тестировать |
|----|--------|--------------------|-----------------|
| S2-07 | Блок популярных/хитов продаж на главной или в категориях (UX-04) | Виджет или блок с товарами (ручная подборка или по продажам) | Главная / категория — блок виден |
| S2-08 | Отзывы о товарах: интеграция или место под отзывы (UX-04) | Woo отзывы включены и отображаются, или подключён плагин отзывов | Карточка товара — секция отзывов |
| S2-09 | Рейтинг магазина на сайте (UX-04) | Блок с рейтингом (если есть данные) или заглушка «Рейтинг на Allegro» / готовность к подключению | Главная или подвал |
| S2-10 | Явно показывать наличие на складе (UX-06) | В карточке и/или в списке товаров виден статус «В наличии» / «Под заказ» | Каталог и карточка |
| S2-11 | Явно показывать доставку на следующий день и информацию о фактуре VAT (UX-06) | Текст на странице доставки, в футере или в чекауте | Страница доставки; чекаут или отдельная страница «Оплата и доставка» |

**Zrobione (04/05):** S2-07 — shortcode `[mnsk7_bestsellers]` (produkty po popularności). S2-08 — recenzje Woo w zakładkach (domyślnie); sprawdzić w Ustawienia → Produkty. S2-09 — shortcode `[mnsk7_rating]` (url="" opcjonalnie, np. link do Allegro). S2-10 — `mnsk7_single_product_availability()` w karcie. S2-11 — tekst „Dostawa następnego dnia. Faktura VAT…” w karcie (hook 35) + shortcode `[mnsk7_dostawa_vat]` na stronę/footer.

---

## Не входят в Sprint 02 (далее)

- Целевые SEO-страницы и раздел статей (E5, UX-05) — Sprint 03 или параллельно с 02.
- Массовая конвертация изображений WebP (P1-03), доработка мобильной версии и визуала (UX-07) — по приоритету после каталога и карточки.
- Дубли GTM/Facebook (P2-05, P2-06), schema/robots/alt (P2-01–P2-04) — по договорённости с 02_growth_seo.

---

## Зависимости

- Sprint 01 выполнен: один фильтр, атрибуты проверены/заполнены, overrides карточки есть.
- Решение по плагину отзывов и источнику рейтинга (Woo, Allegro, вручную) — желательно до S2-08, S2-09.

---

## Итог спринта

- Каталог с понятной структурой и фильтрами по логике выбора.
- Карточка с быстрым сканированием параметров и блоком «подходит для».
- На сайте видны элементы доверия: хиты, отзывы/место под отзывы, рейтинг, наличие, доставка на следующий день, фактура VAT.
- Готовность к Sprint 03: SEO-страницы, контент, производительность и мобильная версия.

---

## Co dalej (po weryfikacji staging 2026-03-10)

Źródło: [STAGING_PROGRESS.md](../docs/STAGING_PROGRESS.md). Na stagingu wdrożone: header/footer bez duplikatów, PDP z Kluczowe parametry/Zastosowanie/availability/trust, bestsellery, shortcodes. **Do zrobienia w kolejnych krokach:**

- **E5 (SEO):** landingi pod zapytania (frezy do aluminium/MDF/drewna, CNC), audyt Title/H1/alt, jeden schema (P2-01).
- **E6:** WebP/obrazki, mobile (tap targets, kontrast — zweryfikować na żywym stagingu).
- **P0/P1 z AS_IS:** jeden filtr (S1-08), wyłączenie duplikatów filtrów na serwerze (FB-04), backupy (S1-05), cache exclusions (S1-02, S1-03).
- **09_ui_designer:** UI_SPEC_V2 jako single source of truth; potem 05/04 — wdrożenie wizualne i konwersja (inbox: Rework po audycie marketing/UX).
- **08_qa_security:** smoke + regres po każdym deploy; QA_REPORT §5 (UI-1–UI-5).
