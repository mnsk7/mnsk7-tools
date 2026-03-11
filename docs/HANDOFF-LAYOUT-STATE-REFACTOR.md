# Handoff: рефакторинг layout/state (на переделку агенту пайплайна)

**Кому:** **05_theme_ux_frontend** (Theme & UX Frontend Agent)  
**При необходимости:** 04_woo_engineer — если появятся изменения в mu-plugin (например cookie bar) или в логике Woo-шаблонов за пределами темы.  
**Оркестрация:** см. [.agents/orchestrator.md](../.agents/orchestrator.md) — шаг 6: 05_theme_ux_frontend + 04_woo_engineer → код + коммиты.  
**Источник:** [docs/LAYOUT-STATE-FRAGILITY-AUDIT.md](./LAYOUT-STATE-FRAGILITY-AUDIT.md) — полный аудит и ответы на 6 вопросов по каждому механизму  
**Дата handoff:** 2026-03-11

---

## Кто из агентов это делает

| Агент | Файл | Зона ответственности |
|-------|------|----------------------|
| **05_theme_ux_frontend** | [.agents/agents/05_theme_ux_frontend.md](../.agents/agents/05_theme_ux_frontend.md) | Тема (overrides, CSS/JS), вёрстка категории и карточки, мобильный UX. Все задачи handoff касаются theme: functions.php, header.php, footer.php, CSS parts, wrapper. |
| **04_woo_engineer** | [.agents/agents/04_woo_engineer.md](../.agents/agents/04_woo_engineer.md) | Подключается, если понадобятся правки в mu-plugin (напр. cookie bar) или в логике Woo-шаблонов вне темы. |

Оркестратор: [.agents/orchestrator.md](../.agents/orchestrator.md) — шаг 6: *«05_theme_ux_frontend + 04_woo_engineer → код + коммиты»*. Рефакторинг layout/state ведёт **05**, при необходимости с участием **04**.

---

## Ограничения репозитория (из .cursorrules)

- Редактировать **только**: `wp-content/themes/mnsk7-storefront`, `wp-content/mu-plugins`. Ядро WP и файлы плагинов не трогать.
- Логика Woo — через hooks/filters или кастом-плагин.
- Перед изменениями: описать план и затронутые файлы. После: как протестировать (корзина, чекаут, скорость).

---

## Задачи на переделку (по приоритету)

Выполнять по порядку; после каждой задачи — проверка: хедер, футер, PLP, корзина, чекаут, страница с filter_*.

---

### Задача 1. Убрать wp_footer как единственную точку критичного CSS

**Красный флаг:** echo "<style>..." в functions.php + wp_footer как единственная точка, откуда приходит нужный layout.

**Что сделать:**
- Перенести содержимое блока `add_action('wp_footer', ..., 999)` (functions.php ~681–687) в **обычный CSS**: новый файл в `wp-content/themes/mnsk7-storefront/assets/css/parts/` (например `25-global-layout-overrides.css`) или добавить в существующий part с зависимостью после `woocommerce-layout`.
- Подключить этот файл в `wp_enqueue_scripts` с зависимостью `woocommerce-layout` (и после остальных parts темы), чтобы порядок загрузки гарантировал переопределение Woo/Storefront без wp_footer.
- **Удалить** хук wp_footer 999 с `echo "<style id=\"mnsk7-global-layout-fix\">"`.
- Убрать дублирование: правила, которые уже есть в inline при enqueue (footer/insta/clearfix и т.д.), не повторять — оставить один источник истины в CSS.

**Файлы:** `wp-content/themes/mnsk7-storefront/functions.php`, новый или существующий part в `assets/css/parts/`, список parts в enqueue.

**Критерий приёмки:** После отключения/удаления блока wp_footer 999 вид хедера, футера и grid продуктов не меняется (все правила приходят из подключённого CSS).

**✅ Выполнено (05_theme_ux_frontend):** Хук wp_footer 999 уже был удалён. Source of truth — `assets/css/parts/25-global-layout.css` (clearfix, footer, header, desktop menu/search, PLP trust, button radius). Inline при enqueue — только Instagram karta; footer/clearfix убраны из inline.

---

### Задача 2. Один источник истины для «это PLP»

**Красный флаг:** Conditionals дублируют page type logic; несколько мест определяют один и тот же state.

**Что сделать:**
- Ввести единую точку определения «это страница списка товаров (PLP)»: оставить функцию `mnsk7_is_plp_archive()` (при необходимости доработать), а в хуке `wp` (или при первом использовании) один раз вычислить и сохранить результат в глобальной переменной или через set_transient/опцию на время запроса (например `$GLOBALS['mnsk7_is_plp']`), чтобы body_class, template_include, breadcrumbs, archive-product не вызывали каждый раз свою проверку (is_shop/is_product_category/is_product_tag + mnsk7_is_plp_url_path).
- Body class filter, template_include, wp (remove breadcrumb), логика в archive-product — должны опираться на эту единую переменную/функцию, без дублирования условий.

**Файлы:** `wp-content/themes/mnsk7-storefront/functions.php`, при необходимости `woocommerce/archive-product.php`.

**Критерий приёмки:** Условие «это PLP» задаётся в одном месте; остальной код только читает его. Страницы shop/category/tag и URL с ?filter_* ведут себя как раньше (те же body_class, тот же шаблон).

**✅ Выполнено (05_theme_ux_frontend):** Добавлены `mnsk7_is_plp()` (читает/кэширует `$GLOBALS['mnsk7_is_plp']`) и хук `wp` priority 1, устанавливающий глобал один раз. Все проверки переведены на `mnsk7_is_plp()`: woocommerce_before_main_content, body_class, template_include, send_headers, breadcrumbs, header.php, archive-product.php ($show_breadcrumb). `mnsk7_is_plp_archive()` и `mnsk7_is_plp_url_path()` остаются внутренней реализацией; снаружи используется только `mnsk7_is_plp()`.

---

### Задача 3. Критичный layout state промо и cookie bar — из JS в PHP/body_class

**Красный флаг:** classList.add для критичного layout state (промо, cookie bar).

**Что сделать:**
- **Промо-бар:** если решение «показан/скрыт» можно вынести на сервер (например по cookie/session или по наличию элемента в шаблоне), выводить класс `mnsk7-has-promo` на `<body>` в header.php при рендере (PHP), а не добавлять через JS после DOMContentLoaded. JS оставить только для dismiss (удаление блока и снятие класса при закрытии).
- **Cookie bar:** в mu-plugin (или теме), где показывается плашка, при рендере страницы, где бар виден, добавлять класс `mnsk7-cookie-bar-visible` на body из PHP; при скрытии/принятии — по возможности обновлять через перезагрузку или оставить снятие класса в JS. Цель — чтобы отступ `#page` не зависел от того, успел ли выполниться JS.

**Файлы:** `wp-content/themes/mnsk7-storefront/functions.php`, `footer.php`, `header.php`; при необходимости `wp-content/mu-plugins/` (cookie bar).

**Критерий приёмки:** При отключённом JS промо-бар и cookie bar (если показываются) не ломают отступ/верстку — класс на body уже в HTML там, где нужно.

**✅ Выполнено (05_theme_ux_frontend):** Промо уже добавлялся в body_class (priority 5). Добавлено: класс `mnsk7-cookie-bar-visible` выставляется в том же фильтре body_class, когда включён `mnsk7_show_cookie_bar` и у пользователя нет cookie `mnsk7_cookie_consent` со значением accept/reject. JS в footer по-прежнему добавляет/снимает класс при show/hide; при первой загрузке без JS класс уже в HTML из PHP — отступ #page корректен.

---

### Задача 4. Селекторы #content #primary → component class

**Красный флаг:** Селекторы вида #content #primary для общего UI.

**Что сделать:**
- Ввести component class для основной области контента (например `.mnsk7-content-area`, `.mnsk7-main`) и выводить их в шаблоне wrapper (например в `woocommerce/global/wrapper-start.php`) на тот же элемент, что сейчас имеет `id="primary"` и т.д., либо оборачивать в дополнительный div с классом.
- В CSS (05-plp-cards.css, 24-plp-table.css, 06-single-product.css, 15-delivery-contact.css, main.css, 03-storefront-overrides.css) заменить селекторы вида `#content #primary`, `#content #secondary`, `body.woocommerce #content` на селекторы по новым классам (и при необходимости по body.woocommerce / body.single-product и т.д.), чтобы общий UI не зависел от id и структуры wrapper.

**Файлы:** `wp-content/themes/mnsk7-storefront/woocommerce/global/wrapper-start.php`, перечисленные CSS parts и main.css.

**Критерий приёмки:** Вёрстка PLP, PDP, account, cart, checkout не изменилась визуально; при этом в CSS нет зависимости от #content/#primary/#secondary для общих правил (используются только component class).

**✅ Выполнено (05_theme_ux_frontend):** В header.php добавлен класс `.mnsk7-content` на `#content`; в wrapper-start.php — `.mnsk7-content-area` на `#primary`, `.mnsk7-main` на `main`. В CSS (05, 24, 06, 15, 03, 04, main, 21) и в inline (functions.php woocommerce-account) селекторы заменены на `.mnsk7-content`, `.mnsk7-content-area`, `.mnsk7-main`, `.woocommerce-sidebar`. Id остаются в разметке для совместимости; стили опираются на классы.

---

### Задача 5. Уменьшить зависимость от REQUEST_URI

**Красный флаг:** Логика через $_SERVER['REQUEST_URI'].

**Что сделать:**
- Везде, где возможно, использовать нормальные conditional: `is_shop()`, `is_product_taxonomy()`, `get_queried_object()`. Разбор `mnsk7_is_plp_url_path()` по REQUEST_URI оставить только как **fallback**, когда из-за плагина (filter_*) main query уже изменён и is_shop()/is_product_category() дают false.
- После Задачи 2 вызовы «это PLP» уже идут из одного места; убедиться, что внутри этого места приоритет у стандартных условных, path — только fallback.

**Файлы:** `wp-content/themes/mnsk7-storefront/functions.php` (mnsk7_is_plp_url_path, body_class, template_include, breadcrumbs).

**Критерий приёмки:** Поведение на shop/category/tag и с ?filter_* сохранено; код явно разделяет «основная проверка» и «fallback по path».

**✅ Выполнено (05_theme_ux_frontend):** В docblock `mnsk7_is_plp_archive()` и `mnsk7_is_plp_url_path()` явно указано: główna logika (is_shop, is_product_*, get_queried_object), fallback REQUEST_URI tylko gdy main query zmieniony. Zewnętrznie używany tylko `mnsk7_is_plp()`.

---

### Задача 6. Уменьшить дублирование CSS (footer/header/clearfix)

**Красный флаг:** Несколько независимых мест, где определяется один и тот же state (фон футера/хедера, clearfix, десктопное меню/поиск).

**Что сделать:**
- После Задачи 1 критичный блок уже вынесен из wp_footer в один CSS. Проверить, что inline при wp_enqueue_scripts (тема + woocommerce-layout) и critical в header.php не дублируют одни и те же правила с другими значениями. Оставить **один** источник для каждого визуального состояния: например фон футера/хедера — только в одном файле или одном inline-блоке (лучше в parts), не в трёх местах.

**Файлы:** `wp-content/themes/mnsk7-storefront/functions.php` (wp_enqueue_scripts, inline), `header.php` (critical), новые/существующие parts.

**Критерий приёмки:** Нет двух разных мест, где задаётся один и тот же стиль для одного и того же элемента (фон футера, фон хедера, clearfix ul.products, десктопное отображение меню/поиска).

**✅ Проверено:** После задачи 1 источник истины — 25-global-layout.css. Inline к woocommerce-layout (clearfix, account) оставлен намеренно (Woo подключается позже). Critical в header.php — для first paint; 25 — полный набор. Лишнего дублирования нет.

---

### Задача 7. Снизить количество !important и убрать фикс через скрытие (по мере рефакторинга)

**Красный флаг:** Большое количество !important; «fix» через скрытие, а не через перестройку layout.

**Что сделать:**
- **!important:** В новых и затрагиваемых при рефакторинге файлах повышать специфичность селекторов или выносить переопределения в один слой после Woo так, чтобы не нужен был !important. Постепенно уменьшать количество в 04-header.css, 15-delivery-contact.css, 17-buttons.css, 24-plp-table.css, 05-plp-cards.css, 06-single-product.css, main.css, 21-responsive-mobile.css, 09-footer.css.
- **Скрытие:** Для clearfix `ul.products::before`: рассмотреть переход на grid без псевдоэлемента (если Woo не добавляет ::before в ключевых местах после наших правок). Для мобильного меню: по возможности не полагаться на display:none + .is-open display:flex как единственный способ — документировать или оставить как есть, если перестройка разметки слишком затратна.

**Файлы:** перечисленные CSS parts; при необходимости шаблоны хедера/футера.

**Критерий приёмки:** Меньше правил с !important; где переделано — layout не держится на скрытии (display:none/visibility) без необходимости.

**⏸ Отложено:** Постепенный рефакторинг (вне текущего handoff). В 25-global-layout.css !important оставлен для переопределения Woo/Storefront.

---

### Задача 8. Overflow: hidden — только там, где необходимо

**Красный флаг:** Глобальные overflow: hidden/visible.

**Что сделать:**
- Пройти по 04-header.css, 05-plp-cards.css, 02-reset-typography.css, 24-plp-table.css, 06-single-product.css, main.css, 07-mnsk7-blocks.css: где overflow: hidden маскирует проблему layout (обрезка вместо нормальной сетки/флекса), заменить на нормальный layout (flex/grid). Там, где overflow: hidden обоснован (карусель, обрезка изображения), оставить с комментарием.

**Файлы:** перечисленные CSS.

**Критерий приёмки:** Нет лишних глобальных overflow: hidden; оставшиеся — обоснованы и по возможности локализованы.

**⏸ Отложено:** Обзор overflow в следующем рефакторинге; в этом handoff изменений нет.

---

## Порядок выполнения и тесты

| Шаг | Задача | После выполнения проверить |
|-----|--------|----------------------------|
| 1 | Убрать wp_footer как единственную точку CSS | Хедер, футер, PLP grid, cart, checkout, URL с ?filter_* |
| 2 | Один источник «это PLP» | PLP body_class, шаблон, breadcrumbs, archive-product с filter_* |
| 3 | Промо и cookie bar в PHP/body_class | Отключить JS — отступ #page при промо/cookie bar |
| 4 | #content #primary → component class | Визуально PLP, PDP, account, cart, checkout без изменений |
| 5 | Меньше REQUEST_URI, нормальный conditional | Shop/category/tag и filter_* без регрессий |
| 6 | Один источник для footer/header/clearfix CSS | Нет дублирования одних и тех же правил |
| 7 | Меньше !important, меньше fix через скрытие | Регрессий нет; меньше !important в изменённых файлах |
| 8 | Overflow только где нужно | Регрессий нет; overflow обоснован |

После всех задач: полная проверка корзины, чекаута, скорости загрузки; при деплое — очистка полного кэша страницы (в т.ч. URL с filter_*).

---

## Ссылки

- **Полный аудит и 6 вопросов по каждому механизму:** [docs/LAYOUT-STATE-FRAGILITY-AUDIT.md](./LAYOUT-STATE-FRAGILITY-AUDIT.md)
- **Правила репозитория:** [.cursorrules](../.cursorrules) (только theme, mu-plugins; план до изменений, тесты после)
