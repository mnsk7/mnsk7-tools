# Handoff: компактный вертикальный ритм на архивах товаров (PLP)

## Целевые шаблоны

- `/tag-produktu/*`
- `/kategoria-produktu/*`
- `/kategoria-produktu/*?filter_...` (архивы с активными фильтрами)

Классы body: `post-type-archive-product`, `tax-product_cat`, `tax-product_tag`.  
**Не затрагиваются:** PDP, cart, checkout, my-account, обычные страницы.

---

## 1. Источники лишнего вертикального whitespace (до изменений)

| Селектор / источник | Файл | Было | Эффект |
|--------------------|------|------|--------|
| `#content`, `.mnsk7-content` | 25-global-layout.css, 05-plp-cards.css | `padding-top: var(--space-page-top)` = **2rem (32px)** | Большой отступ от header до первого контента |
| `.mnsk7-breadcrumb-wrap` | 19-breadcrumbs.css | `padding: 0.75rem 0` (12px вверху/внизу), `margin-bottom: 1rem` | Блок хлебных крошек высокий; зазор до H1 = 12+16 = 28px |
| `.woocommerce-products-header` | 24-plp-table.css | `margin-bottom: var(--space-title-bottom)` = **1.25rem (20px)** | H1 → chips |
| `.mnsk7-plp-chips:first-of-type` | 24-plp-table.css | `margin-top: 0.25rem`, `margin-bottom: var(--space-section-gap)` = **1.5rem (24px)** | Chips (категории) → первый ряд фильтров: 24px + следующий margin-top |
| `.mnsk7-plp-chips--attrs` | 24-plp-table.css | `margin-top: 0.5rem`, `margin-bottom: 0.5rem`; соседний `+ .mnsk7-plp-chips--attrs` `margin-top: 0.25rem` | Между рядами фильтров: 8+4 = 12px (но первый отступ от chips = 24+8 = 32px) |
| `.mnsk7-plp-filters-toggle-wrap` | 24-plp-table.css | `margin-top: 0.25rem`, `margin-bottom: 0.5rem` | Доп. зазор вокруг кнопки «Więcej filtrów» |
| `.mnsk7-plp-selected` | 24-plp-table.css | `margin-bottom: 1rem` | Блок «Wybrane» |
| `.mnsk7-plp-search` | 24-plp-table.css | `margin-bottom: 1rem`, **нет margin-top** | Фактический зазор фильтры → search зависел от предыдущего блока (0.5rem) = 8px |
| `.mnsk7-plp-trust-wrap` | 24-plp-table.css | `margin-bottom: 1rem` | Trust badges |
| `.mnsk7-product-table-wrap` | 24-plp-table.css | `margin-bottom: 1.5rem` | Под таблицей |

**Дублирование:** верхний отступ задавался и в **25-global-layout.css** (`#content` padding-top), и в **05-plp-cards.css** (body.woocommerce `#content` padding с `--space-page-top`). На архивах оба давали 32px. Для архивов введён один источник правды и уменьшен отступ.

---

## 2. Изменённые файлы

| Файл | Изменения |
|------|-----------|
| **assets/css/parts/24-plp-table.css** | Добавлен один блок «Archive vertical rhythm (compact)»: переопределения только для `body.post-type-archive-product`, `body.tax-product_cat`, `body.tax-product_tag`. В том же блоке — медиазапрос `@media (max-width: 768px)` с ещё более компактными значениями для мобильных. |

Остальные файлы (19-breadcrumbs.css, 25-global-layout.css, 05-plp-cards.css, 01-tokens.css) **не менялись**: глобальные токены и стили для не-архивов сохранены; для архивов применяются более специфичные правила из 24-plp-table.css (порядок загрузки: 24 после 05 и 19, поэтому переопределения срабатывают).

---

## 3. Удалённые / переопределённые правила

Никакие правила в других файлах **не удалялись**. Для архивов добавлены более специфичные селекторы в 24-plp-table.css, которые переопределяют:

- `#content` / `.mnsk7-content`: `padding-top` (вместо 2rem → 1.25rem desktop, 1rem mobile).
- `.mnsk7-breadcrumb-wrap`: `margin-bottom`, `padding-top`, `padding-bottom` (только в контексте archive body).
- `.woocommerce-products-header`: `margin-bottom` (1.25rem → 1rem desktop, 0.75rem mobile).
- `.mnsk7-plp-chips:first-of-type`: `margin-top`, `margin-bottom` (section-gap 1.5rem → 1rem; margin-top 0.25rem → 0.5rem).
- `.mnsk7-plp-chips--attrs` и `.mnsk7-plp-chips--attrs + .mnsk7-plp-chips--attrs`: `margin-top`, `margin-bottom` (между рядами фильтров 12px).
- `.mnsk7-plp-filters-toggle-wrap`: `margin-top`, `margin-bottom`.
- `.mnsk7-plp-selected`: `margin-bottom` (1rem → 0.75rem).
- `.mnsk7-plp-search`: добавлен `margin-top: 1.25rem` (20px), `margin-bottom` 1rem → 0.75rem (desktop), 0.5rem (mobile).
- `.mnsk7-plp-trust-wrap`: `margin-bottom` (1rem → 0.75rem desktop, 0.5rem mobile).
- `.mnsk7-product-table-wrap`: `margin-bottom` (1.5rem → 1rem).

Визуальный стиль чипсов, кнопок и таблицы не менялся — только вертикальные отступы.

---

## 4. Итоговые значения (spacing map)

### Desktop (≥769px)

| Участок | Селектор / логика | Итоговый зазор |
|---------|-------------------|----------------|
| Header → первый контент | `#content` / `.mnsk7-content` padding-top | **20px** (1.25rem) |
| Breadcrumbs → H1 | `.mnsk7-breadcrumb-wrap` padding-bottom + margin-bottom | **24px** (8px + 16px) |
| H1 → блок чипсов (категории) | `.woocommerce-products-header` margin-bottom + `.mnsk7-plp-chips:first-of-type` margin-top | **24px** (16px + 8px) |
| Chips (категории) → первый ряд фильтров | `.mnsk7-plp-chips:first-of-type` margin-bottom + `.mnsk7-plp-chips--attrs` margin-top | **24px** (16px + 8px) |
| Между рядами фильтров | `.mnsk7-plp-chips--attrs` margin-bottom + следующий margin-top | **12px** (6px + 6px) |
| Фильтры → search / таблица | `.mnsk7-plp-search` margin-top | **20px** (1.25rem) |
| Search → trust / trust → таблица | margin-bottom: search 12px, trust 12px, table 16px | 12px, 12px, 16px |

### Mobile (≤768px)

| Участок | Итоговый зазор |
|---------|----------------|
| Header → первый контент | **16px** (1rem) padding-top `#content` |
| Breadcrumbs → H1 | **~18px** (7px + 12px: padding 0.35rem, margin-bottom 0.75rem) |
| H1 → chips | **20px** (12px + 8px) |
| Chips → фильтры | **16px** (12px + 4px) |
| Между группами фильтров | **10px** (0.25rem + 0.25rem) |
| Фильтры → search | **16px** (1rem margin-top `.mnsk7-plp-search`) |

---

## 5. Before / After — селекторы

### Before (общие правила без контекста archive)

- `#content`, `.mnsk7-content` — `padding-top: 2rem` (25-global-layout, 05-plp-cards).
- `.mnsk7-breadcrumb-wrap` — `margin-bottom: 1rem`, `padding: 0.75rem 0` (19-breadcrumbs).
- `.woocommerce-products-header` — `margin-bottom: 1.25rem` (24-plp-table).
- `.mnsk7-plp-chips:first-of-type` — `margin-top: 0.25rem`, `margin-bottom: 1.5rem` (24-plp-table).
- `.mnsk7-plp-chips--attrs` — `margin-top: 0.5rem`, `margin-bottom: 0.5rem` (24-plp-table).
- `.mnsk7-plp-search` — `margin-bottom: 1rem`, без margin-top (24-plp-table).

### After (только архивы: body.post-type-archive-product, body.tax-product_cat, body.tax-product_tag)

- `body.post-type-archive-product #content`, `body.tax-product_cat #content`, `body.tax-product_tag #content` (и `.mnsk7-content`) — `padding-top: 1.25rem`; на mobile `1rem`.
- `body.post-type-archive-product .mnsk7-breadcrumb-wrap`, … — `margin-bottom: 1rem`, `padding: 0.5rem 0`; на mobile `margin-bottom: 0.75rem`, `padding: 0.35rem 0`.
- `body.* .woocommerce-products-header` — `margin-bottom: 1rem`; на mobile `0.75rem`.
- `body.* .mnsk7-plp-chips:first-of-type` — `margin-top: 0.5rem`, `margin-bottom: 1rem`; на mobile `margin-bottom: 0.75rem`.
- `body.* .mnsk7-plp-chips--attrs` — `margin-top: 0.375rem`, `margin-bottom: 0.375rem`; на mobile `0.25rem`.
- `body.* .mnsk7-plp-search` — `margin-top: 1.25rem`, `margin-bottom: 0.75rem`; на mobile `margin-top: 1rem`, `margin-bottom: 0.5rem`.
- Аналогично: `body.* .mnsk7-plp-filters-toggle-wrap`, `.mnsk7-plp-selected`, `.mnsk7-plp-trust-wrap`, `.mnsk7-product-table-wrap` — значения как в таблице выше.

---

## 6. Подтверждение области применения

Изменения применяются к:

- **/tag-produktu/*** — у body класс `tax-product_tag` (и `post-type-archive-product`).
- **/kategoria-produktu/*** — у body класс `tax-product_cat` (и `post-type-archive-product`).
- **Архивы с фильтрами** (`?filter_...`) — тот же шаблон и те же классы body; фильтры не меняют body class.

Главная страница магазина (например `/sklep/`) имеет `post-type-archive-product` и тоже получает компактный ритм. PDP, корзина, checkout, my-account и обычные страницы не попадают под эти селекторы и остаются на глобальном page-top и прежних отступах.

---

## 7. Конфликты и один source of truth

- **Дублирование page-top:** на архивах `padding-top` для `#content`/`.mnsk7-content` теперь задаётся только блоком в **24-plp-table.css** (селектор с body class). 25-global-layout и 05-plp-cards по-прежнему задают 2rem для остальных страниц; на архивах выигрывает правило из 24-plp-table за счёт специфичности.
- **Breadcrumbs:** в 19-breadcrumbs.css остаётся общее правило; на архивах его переопределяет правило в 24-plp-table с контекстом body.
- **Отступы между секциями PLP** (header, chips, фильтры, search, trust, таблица) на архивах сосредоточены в одном блоке в 24-plp-table.css — без изменения токенов в 01-tokens.css и без правок 19-breadcrumbs / 25-global-layout для этих страниц.

Итог: первый экран на архивах отдаёт меньше места пустоте, больше — фильтрам и товарам, при сохранении читаемого ритма и без изменения внешнего вида самих элементов (чипсы, кнопки, таблица).
