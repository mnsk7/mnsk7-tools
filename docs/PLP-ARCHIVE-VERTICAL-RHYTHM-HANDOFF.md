# Handoff: компактный вертикальный ритм на архивах товаров (PLP)

## Формулировка

**По сайту:** общая система page-top уже есть (25-global-layout.css, 05-plp-cards.css, токены в 01-tokens.css). Она задаёт отступ от header до контента для страниц, account, contact, PDP и т.д.

**Для архивов товаров** поверх неё добавлен отдельный **compact archive rhythm**: реализован локально в 24-plp-table.css, без правок остальных шаблонов. Один source of truth действует **только в узком контексте** — вертикальный ритм страниц архивов (tag/category/filtered). Для всей темы единого источника правды по page-top по-прежнему нет: дублирование #content между 25 и 05 не убиралось (см. п. 7).

---

## Целевые шаблоны

- `/tag-produktu/*`
- `/kategoria-produktu/*`
- `/kategoria-produktu/*?filter_...` (архивы с активными фильтрами)

Классы body: `post-type-archive-product`, `tax-product_cat`, `tax-product_tag`.  
**Не затрагиваются:** PDP, cart, checkout, my-account, обычные страницы.

---

## 1. Владельцы вертикального ритма (archive)

Кто за что отвечает в compact archive rhythm:

| Селектор | Владеет |
|----------|---------|
| `#content`, `.mnsk7-content` | Зазор **header → зона архива**: `padding-top: var(--archive-gap-page-top)` |
| `.mnsk7-breadcrumb-wrap` | Зазор **breadcrumbs → H1**: `margin-bottom: var(--archive-gap-breadcrumbs-title)`; внутренний отступ блока: `padding` через `--archive-inner-breadcrumb` |
| `.woocommerce-products-header` | Зазор **H1 → chips**: `margin-bottom: var(--archive-gap-title-chips)` |
| `.mnsk7-plp-chips:first-of-type` | Зазор **chips (категории) → первый ряд фильтров**: `margin-bottom: var(--archive-gap-chips-filters)` |
| `.mnsk7-plp-chips--attrs` | Зазор **между рядами фильтров**: `margin-bottom: var(--archive-gap-filter-rows)` |
| `.mnsk7-plp-search` | Зазор **фильтры → search/таблица**: `margin-top: var(--archive-gap-filters-results)`; отступ под search: `margin-bottom: var(--archive-after-search)` |
| `.mnsk7-plp-trust-wrap` | Отступ под trust: `margin-bottom: var(--archive-after-trust)` |
| `.mnsk7-product-table-wrap` | Отступ под таблицей: `margin-bottom: var(--archive-after-table)` |

Один участок = одна переменная (не сумма margin соседа + padding).

---

## 2. Переменные archive (24-plp-table.css)

Задаются на `body.post-type-archive-product`, `body.tax-product_cat`, `body.tax-product_tag`:

| Переменная | Desktop | Mobile (≤768px) | Назначение |
|------------|---------|-----------------|------------|
| `--archive-gap-page-top` | 1.25rem (20px) | 1rem (16px) | header → зона архива |
| `--archive-gap-breadcrumbs-title` | 1.5rem (24px) | 1rem (16px) | breadcrumbs → H1 |
| `--archive-gap-title-chips` | 1.5rem (24px) | 1.25rem (20px) | H1 → chips |
| `--archive-gap-chips-filters` | 1.5rem (24px) | 1rem (16px) | chips → первый ряд фильтров |
| `--archive-gap-filter-rows` | 0.75rem (12px) | 0.625rem (10px) | между рядами фильтров |
| `--archive-gap-filters-results` | 1.25rem (20px) | 1rem (16px) | фильтры → search/таблица |
| `--archive-inner-breadcrumb` | 0.5rem (8px) | 0.5rem (8px) | внутренний padding breadcrumb |
| `--archive-after-search` | 0.75rem (12px) | 0.5rem (8px) | под search |
| `--archive-after-trust` | 0.75rem (12px) | 0.5rem (8px) | под trust |
| `--archive-after-table` | 1rem (16px) | 1rem (16px) | под таблицей |

Значения mobile точные (без «~»), выводятся из этих переменных в CSS.

---

## 3. Spacing map (итоговые значения)

### Desktop (≥769px)

| Участок | Переменная | Значение |
|---------|------------|----------|
| Header → первый контент | `--archive-gap-page-top` | **20px** |
| Breadcrumbs → H1 | `--archive-gap-breadcrumbs-title` | **24px** |
| H1 → chips | `--archive-gap-title-chips` | **24px** |
| Chips → первый ряд фильтров | `--archive-gap-chips-filters` | **24px** |
| Между рядами фильтров | `--archive-gap-filter-rows` | **12px** |
| Фильтры → search/таблица | `--archive-gap-filters-results` | **20px** |
| Под search / под trust / под таблицей | `--archive-after-*` | 12px, 12px, 16px |

### Mobile (≤768px)

| Участок | Переменная | Значение |
|---------|------------|----------|
| Header → первый контент | `--archive-gap-page-top` | **16px** |
| Breadcrumbs → H1 | `--archive-gap-breadcrumbs-title` | **16px** |
| H1 → chips | `--archive-gap-title-chips` | **20px** |
| Chips → фильтры | `--archive-gap-chips-filters` | **16px** |
| Между группами фильтров | `--archive-gap-filter-rows` | **10px** |
| Фильтры → search | `--archive-gap-filters-results` | **16px** |

Без тильды и «примерно» — числа заданы переменными выше.

---

## 4. Изменённые файлы

| Файл | Изменения |
|------|-----------|
| **assets/css/parts/24-plp-table.css** | Блок «Archive vertical rhythm»: переменные `--archive-gap-*`, `--archive-inner-breadcrumb`, `--archive-after-*` на body архивов; все отступы архива через эти переменные. В медиа ≤768px — переопределение переменных под mobile. |

Остальные файлы (19-breadcrumbs, 25-global-layout, 05-plp-cards, 01-tokens) не менялись.

---

## 5. До / после (архив)

- **До:** те же блоки получали margin/padding из глобальных токенов или «собранные» (например 24px = 8+16 из двух свойств). Не было одного владельца на участок.
- **После:** у каждого участка один владелец (селектор в §1) и одна переменная. Смена ритма = правка переменных в одном месте в 24-plp-table.css.

---

## 6. Область применения

- **/tag-produktu/*** — body: `tax-product_tag`, `post-type-archive-product`.
- **/kategoria-produktu/*** — body: `tax-product_cat`, `post-type-archive-product`.
- Архивы с `?filter_...` — тот же шаблон и классы body.

Главная магазина (/sklep/) имеет `post-type-archive-product` и использует compact archive rhythm. PDP, корзина, checkout, my-account и обычные страницы — без изменений, default page rhythm.

---

## 7. Дублирование #content (page-top) — не устранено

**Факт:** `padding-top` для `#content` / `.mnsk7-content` по-прежнему задают **два** файла:

- **25-global-layout.css** — `#content`, `.site-content`, `.mnsk7-content` → `padding-top: var(--space-page-top)` (2rem).
- **05-plp-cards.css** — `body.woocommerce #content`, `body.woocommerce .mnsk7-content` → `padding: var(--space-page-top) 1.5rem 1.5rem`.

На архивах побеждает правило из **24-plp-table.css** (селектор с body class), поэтому визуально всё ок. Архитектурно по-прежнему два источника для page-top в WooCommerce; изменение в 05 или 25 может затронуть остальные страницы.

**Рекомендация (на потом):** зафиксировать один файл как canonical для page-top (#content), второй убрать или оставить только fallback; при необходимости описать в отдельном документе (например PAGE-TOP-SPACING-REFACTOR.md), какой файл считать приоритетным.

---

## 8. Итог

- **Default page rhythm** — для всего сайта (25, 05, токены).
- **Compact archive rhythm** — только для архивов товаров, один source of truth **в рамках этого контекста**: переменные и правила в 24-plp-table.css.
- Отступы архива заданы переменными `--archive-gap-*` и `--archive-after-*`, без «~» и без арифметики margin+padding в документации.
- Владельцы участков явно перечислены в таблице в §1.
