# Унификация верхних отступов (page-top spacing)

## Проблема

Разные типы страниц имели разный вертикальный ритм между header и основным контентом:
- `/dostawa-i-platnosci/`, `/kontakt/` — `padding: 2.5rem 1rem 3rem` на `main`
- `/moje-konto/` — `padding: var(--space-32) 1.5rem` на `.entry-content` / `.col-full`
- архивы категорий/тегов — `padding: 1.5rem` на `#content` + `padding-top: 1.75rem` на `main`
- breadcrumbs — `margin-top: 1rem`, разный `margin-bottom`
- разные `margin-bottom` у `.entry-header` и `.woocommerce-products-header`

## Решение

Введена единая система spacing tokens и один источник верхнего отступа — `#content` / `.mnsk7-content`.

### 1. Токены (01-tokens.css, main.css)

В `:root` добавлены:

- **`--space-page-top`** = `2rem` — отступ от header (или promo bar) до первого контента на всех типах страниц
- **`--space-breadcrumbs-bottom`** = `1rem` — отступ под breadcrumbs до заголовка/контента
- **`--space-title-bottom`** = `1.25rem` — отступ под H1 / page title до следующего блока
- **`--space-section-gap`** = `1.5rem` — интервал между крупными секциями (chips → listing и т.д.)

### 2. Глобальный page-top — один canonical (ARCH-21)

**Единственный источник правды для `padding-top` у `#content` / `.mnsk7-content`:**

- **25-global-layout.css:** блок для `#content`, `.site-content`, `.mnsk7-content`:  
  `padding-top: var(--space-page-top)`;  
  `padding-left/right: 1.5rem` (с 768px — `2rem`).  
  Применяется ко всем страницам (page, WooCommerce, account, архивы).  

- **05-plp-cards.css** для WooCommerce **не задаёт** `padding-top`: только `padding-left`, `padding-right`, `padding-bottom` (и flex/gap). Верхний отступ Woo-страниц идёт из 25. Дублирование убрано.

### 3. Унифицированные селекторы

| Селектор / контекст | Было | Стало |
|---------------------|------|--------|
| `.mnsk7-breadcrumb-wrap` | `margin-top: 1rem`, `margin-bottom: 1rem` | `margin-top: 0`, `margin-bottom: var(--space-breadcrumbs-bottom)` |
| `.woocommerce-products-header` | `margin-bottom: 1.25rem` | `margin-bottom: var(--space-title-bottom)` |
| `.mnsk7-page-dostawa .entry-header`, `.mnsk7-page-kontakt .entry-header` | `margin-bottom: var(--space-24)` | `margin-bottom: var(--space-title-bottom)` |
| `body.woocommerce #content` (05-plp-cards) | `padding: 1.5rem` | ARCH-21: **padding-top не задаётся** (canonical 25); только padding-left/right/bottom |
| Woo mobile (05-plp-cards) | `padding: var(--space-16)` | ARCH-21: **padding-top не задаётся**; только padding-left/right/bottom |
| PLP main (#primary / .mnsk7-main) | `padding-top: 1.75rem` | `padding-top: 0` |
| `.mnsk7-page-dostawa`, `.mnsk7-page-kontakt` | `padding: 2.5rem 1rem 3rem` | `padding: 0 1rem 3rem` |
| `.woocommerce-account .entry-content`, `.woocommerce-MyAccount-content`, `.woocommerce-account .col-full` | `padding: var(--space-32) 1.5rem` | `padding: 0 1.5rem var(--space-32)` |
| `.mnsk7-plp-chips:first-of-type` | `margin-bottom: 0.75rem` | `margin-bottom: var(--space-section-gap)` |
| WooCommerce `.page-title` / `h1.page-title` (05-plp-cards) | `margin: 0 0 1rem` | `margin: 0 0 var(--space-title-bottom)` |

### 4. Шаблоны, использующие общий page-top

- **Обычные страницы** (`page.php`) — отступ задаётся только через `#content` (25-global-layout).
- **Dostawa / Kontakt** (`page-dostawa.php`, `page-kontakt.php`) — у `main` убран верхний padding, верхний отступ даёт `#content`.
- **My Account / login / register** — у внутренних блоков убран верхний padding, верхний отступ даёт `#content`.
- **Product category / tag archive** (`woocommerce/archive-product.php`) — у `#primary`/`main` убран `padding-top`, верхний отступ даёт `#content`; порядок: breadcrumbs → title → chips/filters → listing с едиными токенами.
- **Promo bar** — не меняет layout; отступ от низа promo/header до контента по-прежнему задаётся `--space-page-top` у `#content`.

### 5. Файлы изменений

| Файл | Изменения |
|------|-----------|
| **assets/css/parts/01-tokens.css** | Добавлены переменные `--space-page-top`, `--space-breadcrumbs-bottom`, `--space-title-bottom`, `--space-section-gap`. |
| **assets/css/parts/25-global-layout.css** | Добавлен блок для `#content`, `.site-content`, `.mnsk7-content`: `padding-top`, `padding-left/right`, медиа для 768px. |
| **assets/css/parts/05-plp-cards.css** | ARCH-21: Woo `#content`/`.mnsk7-content` — **не задаёт padding-top** (canonical в 25); только padding-left, padding-right, padding-bottom и flex/gap. Mobile — то же. `.page-title`/`h1.page-title`: `margin-bottom: var(--space-title-bottom)`. |
| **assets/css/parts/24-plp-table.css** | У архива main/#primary: `padding-top: 0`. `.woocommerce-products-header`: `margin-bottom: var(--space-title-bottom)`. `.mnsk7-plp-chips:first-of-type`: `margin-bottom: var(--space-section-gap)`. |
| **assets/css/parts/19-breadcrumbs.css** | `.mnsk7-breadcrumb-wrap`: `margin-top: 0`, `margin-bottom: var(--space-breadcrumbs-bottom)`. |
| **assets/css/parts/15-delivery-contact.css** | Dostawa/Kontakt: у контейнеров убран верхний padding (`padding: 0 1rem 3rem`). `.entry-header`: `margin-bottom: var(--space-title-bottom)`. Account: у контентных блоков `padding: 0 1.5rem var(--space-32)`. |
| **assets/css/main.css** | В `:root` добавлены те же четыре токена. Добавлен тот же блок для `#content`/`.site-content`/`.mnsk7-content` (fallback при отсутствии parts). |

### 6. Mobile и desktop

- Один и тот же `--space-page-top` (2rem) используется на всех брейкпоинтах.
- Breadcrumbs не прилипают к header за счёт `padding-top` у `#content`.
- H1 / page title имеют единый отступ снизу через `--space-title-bottom`.
- Секция chips/filters после заголовка использует `--space-section-gap`.
- Account/login используют общий page-top без отдельной логики отступов.

При необходимости уменьшить отступ на мобильных можно в `01-tokens.css` или `25-global-layout.css` добавить, например:

```css
@media (max-width: 480px) {
  :root { --space-page-top: 1.5rem; }
}
```

### 7. Проверка

Проверить визуально:

- `/dostawa-i-platnosci/` — расстояние от header до H1 такое же, как на других страницах.
- `/moje-konto/` (и login/register) — тот же верхний ритм.
- `/kontakt/` — тот же верхний ритм.
- `/kategoria-produktu/...` и `/tag-produktu/...?filter_*` — порядок breadcrumbs → title → chips → listing, без лишних скачков.
- Включить promo bar и убедиться, что отступ от него до контента стабилен.
- Mobile: те же проверки, breadcrumbs и H1 не прилипают к header.
