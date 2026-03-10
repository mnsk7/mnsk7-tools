# Mobile UX/UI Audit — staging.mnsk7-tools.pl (2026-03-10)

## 1. Карта проблем и первопричины

### 1.1 Footer accordion не открывается на mobile
- **Симптом:** Секции футера (аккордеон) не раскрываются по тапу на mobile.
- **Причина:** В `footer.php` скрипт инициализации выполняется с условием `if (!cols.length || window.innerWidth > 768) return;`. При первой загрузке на desktop (или при viewport > 768) обработчики не вешаются; при переходе на mobile или при реальном открытии на телефоне скрипт уже отработал и listeners не добавлены.
- **Решение:** Всегда вешать обработчики клика на заголовки секций; в обработчике переключать только при viewport ≤ 768. Либо инициализировать при `matchMedia('(max-width: 768px)').matches` и по `change` при переходе в mobile.

### 1.2 Страницы с `?filter_...` — другой/ломаный header
- **Гипотеза:** Один и тот же `header.php` и критические inline-стили уже используются; различие может давать кэш (разный ключ для URL с query) или задержка загрузки CSS. В коде условных веток по GET-параметрам в header не найдено.
- **Действие:** Критические стили уже в head; порядок enqueue в `functions.php` не зависит от URL. Дополнительно гарантировать единый рендер не требуется.

### 1.3 Утечка desktop/tablet table на mobile
- **Симптом:** На mobile видна таблица или desktop-layout.
- **Причина:** В `24-plp-table.css` таблица скрывается и grid показывается в `@media (max-width: 767px)`, а в `@media (max-width: 768px)` задаётся только `min-width: 560px` для таблицы. При 768px таблица остаётся видимой.
- **Решение:** Унифицировать брейкпоинт: скрывать таблицу и показывать `.mnsk7-plp-grid-mobile` при `max-width: 768px`.

### 1.4 Chips / filter chips — белая полоса, обрезка, свайп
- **Белая полоса:** Метка (например «Długość:») имеет `position: sticky; left: 0; background: var(--color-bg)`. При горизонтальном скролле метка остаётся на месте, а чипсы уезжают под неё — визуально «полоса» перекрывает значения.
- **Обрезка:** Контейнер chips не имеет отступов по краям скролл-зоны, первый/последний чип обрезается.
- **Свайп неочевиден:** Нет визуального намёка (градиент, тень), что контент листается.
- **Решение:** Обернуть чипсы (и кнопку «Więcej») в отдельный скроллируемый блок `.mnsk7-plp-chips__scroll`, чтобы метка не перекрывала чипсы; задать padding скролл-контейнеру; добавить лёгкий градиент справа как affordance.

### 1.5 Кнопки местами квадратные
- **Причина:** Стили в `17-buttons.css` применяются только к `.woocommerce a.button, ...`. Кнопки из блоков, других шаблонов или без контекста .woocommerce могут не получать `border-radius`. Отдельные компоненты (например search submit) имеют свой радиус; сброса на 0 в mobile не найдено.
- **Решение:** Добавить глобальное правило для `button, .button, input[type="submit"], .add_to_cart_button` с `border-radius: var(--r-md)` (и при необходимости повысить специфичность для перебивания).

### 1.6 Карточки товаров — несогласованность, giant cards на mobile
- **Причина:** В `21-responsive-mobile.css` при 480px все product grids принудительно в 1 колонку (`grid-template-columns: 1fr`). В `24-plp-table.css` PLP mobile grid тоже 1 колонка. Bestsellers в `08-home-sections.css` на 480px — 1 колонка.
- **Решение:** На mobile (до 480px) оставить 2 колонки для product grids (bestsellers, related, upsells, PLP grid), одну колонку только на очень узких экранах (например ≤360px).

---

## 2. Внесённые изменения (wykonane)

| Файл | Zmiany |
|------|--------|
| `footer.php` | Accordion: init wywoływany przy matchMedia('(max-width: 768px)') oraz w addEventListener('change'); guard data-mnsk7-accordion zapobiega podwójnemu podpięciu; klik tylko gdy viewport ≤768. |
| `woocommerce/archive-product.php` | W każdej render_filter_row dodana obwiednia `<div class="mnsk7-plp-chips__scroll">` wokół chipów i przycisku „Więcej” — etykieta (np. Długość:) nie nakłada się na chipy przy scrollu. |
| `assets/css/parts/24-plp-table.css` | Breakpoint 767→768 (ukrycie tabeli, pokazanie gridu); .mnsk7-plp-chips__scroll na mobile: overflow-x auto, padding, ::after gradient (affordance); desktop: flex-wrap; PLP grid 2 kolumny do 360px, 1 kolumna ≤360px; osobne style dla wiersza bez --attrs (kategorie). |
| `assets/css/parts/17-buttons.css` | Wspólna reguła border-radius dla .button, .add_to_cart_button, .woocommerce .button, mnsk7-plp-chips-toggle, mnsk7-plp-reset. |
| `assets/css/parts/21-responsive-mobile.css` | Do 480px: 2 kolumny (zamiast 1); nowy @media (max-width: 360px): 1 kolumna. |
| `assets/css/parts/08-home-sections.css` | Bestsellery: 2 kolumny przy 480px; @media (max-width: 360px): 1 kolumna. |
