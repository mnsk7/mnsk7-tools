# PLP Archive — Visual QA Report & Single-Owner Confirmation

**Scope:** compact archive rhythm (24-plp-table.css).  
**Status:** archive spacing contract — accepted after QA confirmation.

---

## 1. Visual QA — кейсы

Проверка на staging (desktop 2025-03): структура DOM и порядок блоков без наложений.

| Кейс | URL / условие | Результат |
|------|----------------|-----------|
| **Product category без фильтров** | `/kategoria-produktu/frez-fazownik/` | OK. Порядок: promo (Warunki dostawy) → header → breadcrumbs (okruszki) → H1 «Frez fazownik» → Kategorie (chips) → Filtruj: Dł. całkowita, Średnica, Kąt skosu, Średnica → search → karty. Зазоры визуально компактные, без слипания. |
| **Product category с фильтрами** | `/kategoria-produktu/frez-fazownik/?filter_fi=6-mm` | OK. Та же структура; блок «Wybrane» (6 mm ×, Wyczyść wszystkie) между фильтрами и search. Ритм сохранён. |
| **Product tag с фильтрами** | `/tag-produktu/freza-dwuostrzowa/` | OK. Breadcrumbs → H1 «Frez dwupiórowy» → Kategorie → много рядов атрибутов (Długość całkowita, Dł. całkowita (L), Dł. robocza, Średnica, Kąt skosu…) → Więcej filtrów → search «Szukaj w tagu…» → таблица/карточки. Promo bar «Warunki dostawy» сверху; отступ под ним не ломает архивную зону. |
| **Длинный H1 в 2 строки** | Не проверялся отдельным URL (нет категории с очень длинным названием в 2 строки на staging). | Рекомендация: проверить вручную категорию/тег с длинным названием — убедиться, что зазор H1 → chips остаётся 24px (desktop) и не «схлопывается». |
| **Mobile archive** | Те же URL при viewport ≤768px | Переменные переопределяются в @media (max-width: 768px): page-top 16px, breadcrumbs→title 16px, title→chips 20px, chips→filters 16px, filter rows 10px, filters→search 16px. Рекомендация: визуально проверить на устройстве/эмуляторе — нет слипания, touch-targets читаемые. |
| **Archive с promo bar / notices сверху** | `/kategoria-produktu/frez-fazownik/`, `/tag-produktu/freza-dwuostrzowa/` с включённым promo | OK. Promo «Warunki dostawy» + Zamknij отображается над header; контент архива начинается с #content (padding-top: var(--archive-gap-page-top)). Доп. блоки сверху не дублируют вертикальный отступ архива. |

**Итог QA:** базовые кейсы (category без/с фильтрами, tag, promo) — без дефектов. Длинный H1 в 2 строки и mobile — рекомендована ручная проверка при возможности.

---

## 2. Подтверждение: один владелец vertical spacing в archive context

В контексте архивов (`body.post-type-archive-product`, `body.tax-product_cat`, `body.tax-product_tag`) для каждого участка вертикального ритма задаёт отступ **только** один селектор из блока «Archive vertical rhythm» в 24-plp-table.css. Общие правила из 19-breadcrumbs, 05-plp-cards и общие блоки в 24-plp-table (без body class) переопределяются более специфичными правилами с body class.

| Участок | Владелец (единственный в archive) | Где переопределяет |
|---------|-----------------------------------|---------------------|
| **Breadcrumbs** (зазор до H1) | `body.* .mnsk7-breadcrumb-wrap` → `margin-bottom: var(--archive-gap-breadcrumbs-title)` | 19-breadcrumbs: .mnsk7-breadcrumb-wrap (margin-bottom, padding) — без body class; специфичность ниже. |
| **Title** (зазор до chips) | `body.* .woocommerce-products-header` → `margin-bottom: var(--archive-gap-title-chips)` | 24-plp-table: .woocommerce-products-header (margin-bottom) — общее правило; архивное с body class выигрывает. |
| **Chips** (категории; зазор до первого ряда фильтров) | `body.* .mnsk7-plp-chips:first-of-type` → `margin-bottom: var(--archive-gap-chips-filters)` | 24-plp-table: .mnsk7-plp-chips:first-of-type (margin-top, margin-bottom) — общее; архивное переопределяет. |
| **Attribute filter rows** (между рядами) | `body.* .mnsk7-plp-chips--attrs` → `margin-bottom: var(--archive-gap-filter-rows)`; смежные с `+ .mnsk7-plp-chips--attrs` margin-top: 0 | 24-plp-table: .mnsk7-plp-chips--attrs (margin-top, margin-bottom) — общее; архивное с body class переопределяет. |
| **Search / results block** (зазор фильтры → search/таблица) | `body.* .mnsk7-plp-search` → `margin-top: var(--archive-gap-filters-results)` | 24-plp-table: .mnsk7-plp-search (margin-bottom) — общее правило не задаёт margin-top; архивное задаёт зазор сверху единолично. |

**Итог:** второго владельца vertical spacing для breadcrumbs, title, chips, attribute filter rows, search/results block в archive context нет. Все зазоры управляются переменными `--archive-gap-*` в одном блоке 24-plp-table.css.

---

## 3. Ссылки

- Контракт и переменные: [PLP-ARCHIVE-VERTICAL-RHYTHM-HANDOFF.md](PLP-ARCHIVE-VERTICAL-RHYTHM-HANDOFF.md)
- Глобальный page-top (вне архива): отдельная задача [PAGE-TOP-SPACING-REFACTOR (ARCH-21)](backlog/PAGE-TOP-SPACING-REFACTOR.md) в [BACKLOG.md](BACKLOG.md).
