# Аудит хрупких зависимостей layout/state (mnsk7-storefront)

**Дата:** 2026-03-11  
**Handoff на переделку:** [docs/HANDOFF-LAYOUT-STATE-REFACTOR.md](./HANDOFF-LAYOUT-STATE-REFACTOR.md) — задачи для агента пайплайна.  
**Цель:** отметить все места, где вёрстка или состояние интерфейса зависят от JS-only классов, позднего inline-CSS, структуры шаблона, URL/query string, body_class, порядка ресурсов, симптоматических фиксов (overflow/!important/скрытие) или конкретного hook priority.

Для каждого механизма ниже даны ответы на 6 вопросов: **source of truth**, **когда state появляется**, **что если слой не доедет**, **от чего зависит**, **deterministic или хрупкий**, **можно ли перенести выше**.

---

## 1. Зависимость от JS-only класса

Класс добавляется только через `classList.add`/`toggle`/`remove` в скриптах (чаще в `wp_footer`). Без выполнения JS разметка/состояние уже другие.

| Место | Класс | Назначение | Файл |
|-------|--------|------------|------|
| Хедер: мобильное меню | `.mnsk7-header__nav.is-open` | Показ меню на ≤1024px. | `functions.php` ~770; `04-header.css` + critical inline в `header.php` |
| Хедер: подменю «Sklep» | `li.menu-item-has-children.is-open` | Раскрытие подменю на desktop. | `functions.php` ~784 |
| Хедер: поиск (mobile) | `body.mnsk7-search-open` | Панель поиска под хедером. | `functions.php` ~806, 814, 824; `04-header.css` |
| Хедер: корзина dropdown | `.mnsk7-header__cart.is-open` | Показ мини-корзины (desktop hover, mobile click). | `functions.php` ~882–905; `04-header.css` ~817, 832 |
| Промо-бар | `body.mnsk7-has-promo` | Смещение контента (--mnsk7-promo-h), стики хедера. | `functions.php` ~923, 929; `04-header.css` ~101 |
| Хедер при скролле | `.mnsk7-header--scrolled` | Визуальное «сжатие» хедера. | `functions.php` ~945–947; `04-header.css` ~88, 92 |
| Карусель | `.is-active` на слайдах/дотсах | Текущий слайд. | `functions.php` ~964, 966 |
| PDP sticky CTA | `.mnsk7-pdp-sticky-cta.is-visible` | Липкая кнопка на mobile. | `functions.php` ~1022–1024; `06-single-product.css` ~647 |
| Cookie bar | `body.mnsk7-cookie-bar-visible` | Отступ `#page` при видимой плашке. | `footer.php` ~143–144; `10-cookie-bar.css` ~27; mu-plugins/woo-ux.php |
| Футер: аккордеон | `.mnsk7-footer__col.is-open` | Раскрытие колонки на mobile. | `footer.php` ~192; `09-footer.css` ~351, 367–393 |
| PLP chips «Więcej» | `.is-open` на контейнере чипов/фильтров | Доп. чипы и блок фильтров. | `functions.php` (1e bis); `24-plp-table.css` |
| FAQ | `.mnsk7-faq__item.is-open` | Раскрытие ответа. | `faq.php` mu-plugin; `14-faq.css` ~50, 62 |
| Кнопка «Pokaż więcej» | `.loading` | Состояние загрузки. | `functions.php` ~1076, 1102 |
| Shipping zone placeholder | `.has-notice` | Уведомление о зоне доставки. | `functions.php` ~1177 |

### 6 вопросов: JS-only классы (критичные для layout — меню, поиск, корзина, промо, cookie bar)

| Вопрос | Ответ |
|--------|--------|
| **Где source of truth?** | **JS** (скрипты в `wp_footer`, functions.php и footer.php). Состояние «открыто/закрыто» живёт только в DOM после выполнения скрипта. CSS только реагирует на класс. |
| **Когда state появляется?** | **После загрузки JS** и **после interaction** (клик по toggle, hover, scroll). Промо и cookie bar — сразу после DOMContentLoaded, если элемент есть. |
| **Что будет, если этот слой не доедет?** | **Хедер:** без JS меню и поиск на mobile остаются скрыты (по умолчанию так и задумано в critical CSS), dropdown корзины не откроется по клику — интерактивность потеряна, но layout не «ломается». **Промо/cookie bar:** без класса не будет отступа у `#page` — возможен наезд контента на плашку. **Футер:** колонки на mobile не раскроются. |
| **От чего зависит?** | От наличия нужных DOM-узлов (селекторы в скрипте), от порядка выполнения скриптов в `wp_footer`, от того, что CSS с правилами для этих классов загружен (частично — critical inline в header). |
| **Deterministic или хрупкий?** | **Хрупкий:** состояние не гарантировано до выполнения JS; при ошибке в скрипте или блокировке JS часть UI перестаёт реагировать. Для «закрытого» вида хедера — условно deterministic за счёт critical inline. |
| **Можно ли перенести выше и надёжнее?** | **Да, частично.** Для промо-бара: если «показан/скрыт» решается на сервере (например, cookie/session), можно выводить класс на `body` в PHP и убрать classList. Для cookie bar — то же (уже есть логика в mu-plugin, но класс ставится из JS). Мобильное меню/поиск/корзина по природе интерактивны — перенос в PHP не подходит; можно оставить progressive enhancement и не считать красным флагом, если критичный «закрытый» layout задаётся в CSS без класса. |

---

## 2. Зависимость от footer/head inline-injection

Критичный CSS или layout-fix выводится поздно (в т.ч. через `wp_footer`/`wp_head`). Без этого блока страница уже ломается или выглядит иначе.

| Место | Что инжектится | Когда | Риск |
|-------|----------------|--------|------|
| **wp_footer, priority 999** | `<style id="mnsk7-global-layout-fix">`: clearfix `ul.products::before`, фон футера/хедера, десктопное отображение поиска/меню, PLP trust. | В конце body | Если футер не выполнится (ошибка, обрезка HTML) или порядок ресурсов изменится — фон футера/хедера и grid продуктов могут вернуться к дефолту Woo/Storefront. |
| **header.php после wp_head** | `<style id="mnsk7-header-critical">`: базовый хедер (фон, sticky, бренд, media для desktop/mobile меню и поиска). | В <head> | Меньше: в head, выполняется рано. Риск — при кэше без full CSS (см. п. 6). |
| **wp_enqueue_scripts** | Inline к последнему handle темы: footer, insta, clearfix. | При выводе стилей | Зависит от порядка подключения и минификации (п. 6). |
| **wp_enqueue_scripts, priority 20** | Inline к `woocommerce-layout`: clearfix, body.woocommerce-account (поиск, кнопки, #content). | После Woo layout | Если Woo изменит порядок регистрации или handle — наши переопределения могут не сработать. |

Цитата из кода (functions.php ~680): *«Ostateczne nadpisanie: na końcu strony, wygrywa z WooCommerce/Storefront/cache»* — явная зависимость от того, что этот блок идёт последним.

### 6 вопросов: footer/head inline (в т.ч. wp_footer 999)

| Вопрос | Ответ |
|--------|--------|
| **Где source of truth?** | **Footer-inline** (wp_footer 999) и **PHP в header.php** (critical header). Часть правды также в **обычном CSS** темы и в **wp_enqueue_scripts** (inline к handle темы и к woocommerce-layout). Истины нет в одном месте: один и тот же layout (фон футера, clearfix, десктопное меню) дублируется в inline при enqueue и в блоке wp_footer. |
| **Когда state появляется?** | **На сервере сразу** при рендере (header critical в head; inline при enqueue — при выводе `<link>`); блок **wp_footer** — **после всего контента**, в конце body. Состояние «страница выглядит правильно» появляется только после того, как браузер обработает этот блок. |
| **Что будет, если этот слой не доедет?** | **wp_footer 999 не доедет:** фон футера/хедера и grid продуктов могут вернуться к Woo/Storefront (светлый фон, сломанная сетка из-за clearfix). **Header critical не доедет:** при кэше без полного CSS хедер может потерять базовый вид. **Inline при enqueue не доедет:** то же, что и footer, но раньше в cascade — тогда блок 999 всё равно перебивает. |
| **От чего зависит?** | От **hook priority** (999 для wp_footer), от **порядка вывода** head/body, от того, что **ни один плагин не выведет свой стиль после** нашего блока, от **cache** (полная страница vs только HTML). |
| **Deterministic или хрупкий?** | **Хрупкий:** «обычно работает», пока порядок футера и приоритеты не меняются. При смене темы/плагинов или обрезке HTML блок может не выполниться или не перебить другие стили. |
| **Можно ли перенести выше и надёжнее?** | **Да.** Перенести правила из `echo "<style>..."` в wp_footer → в **обычный CSS** (отдельный файл или часть parts), подключённый с поздней зависимостью (после woocommerce-layout) и без сырого echo в functions.php. Critical header оставить в head, но минимизировать дублирование с 04-header.css. |

---

## 3. Зависимость от структуры конкретного шаблона

CSS завязан на селекторы `#content`, `#primary`, `#secondary` или вложенность, которой может не быть на части страниц.

| Селектор / контекст | Где используется | Где структура есть / нет |
|--------------------|------------------|---------------------------|
| `#content`, `#content.site-content` | `header.php` открывает `<div id="content">`, `footer.php` закрывает. | Есть на всех страницах с header/footer. |
| `#content #primary`, `#content #secondary` | `wrapper-start.php` даёт `#primary` + `.site-main`; сайдбар — в шаблоне архива/одного товара. | **Есть:** archive-product, single-product, страница магазина, категория, тег. **Нет:** страница корзины и чекаута рендерятся через shortcode внутри того же wrapper — там `#primary` и `.site-main` есть; сайдбара (`#secondary`) на cart/checkout нет. |
| `body.woocommerce-page #content`, `body.woocommerce #content` | PLP, карточки, сетки. | Cart/checkout имеют `body.woocommerce-cart` / `woocommerce-checkout` и тот же `#content` + `#primary`. |
| `body.post-type-archive-product #content #primary`, `body.tax-product_cat #content #primary` и т.д. | `24-plp-table.css`, `05-plp-cards.css` | Только на архивах товаров (shop, category, tag). На cart/checkout этих body class нет — селекторы не применяются, что корректно. |
| `body.single-product #content #primary`, `#content .woocommerce-sidebar` | `06-single-product.css` | Только single product; на cart/checkout сайдбара нет. |

**Вывод:** Явной «поломки» из-за отсутствия `#secondary` на cart/checkout нет — стили таргетируют архивы и PDP. Риск: если когда-нибудь вывести cart в шаблоне без `#content`/`#primary` (например, кастомный layout), часть стилей перестанет применяться.

### 6 вопросов: структура шаблона (#content #primary #secondary)

| Вопрос | Ответ |
|--------|--------|
| **Где source of truth?** | **PHP-шаблоны:** header.php открывает `#content`, footer.php закрывает; wrapper-start.php даёт `#primary` и `.site-main`; сайдбар выводится в archive/single. Истина размазана по шаблонам, а CSS **предполагает** эту структуру. |
| **Когда state появляется?** | **На сервере сразу** при рендере HTML. Структура не зависит от JS или футера. |
| **Что будет, если этот слой не доедет?** | Если на какой-то странице не будет `#content` или `#primary` (кастомный layout) — селекторы вроде `#content #primary` не сработают: **spacing, grid, сайдбар** для этой страницы сломаются. Сейчас все страницы используют один wrapper — поломки нет. |
| **От чего зависит?** | От **wrapper** (какой шаблон какой разметкой пользуется), от того, что Woo shortcodes (cart, checkout) рендерятся внутри того же wrapper. |
| **Deterministic или хрупкий?** | **Условно deterministic** на текущем наборе страниц. **Хрупкий** при добавлении новых шаблонов без этой структуры или при выводе контента вне стандартного wrapper. |
| **Можно ли перенести выше и надёжнее?** | **Да.** Заменить селекторы вида **#content #primary** на **component class** (например `.mnsk7-content-area`, `.mnsk7-main`), которые выводятся в шаблоне и не привязаны к id/структуре. Тогда layout не зависит от конкретной вложенности. |

---

## 4. Зависимость от REQUEST_URI, query string, ?filter_*

Состояние или контент определяются разбором URL/query, а не только серверной логикой шаблона (условиями типа `is_shop()`).

| Место | Что делается по URL/query | Файл |
|-------|----------------------------|------|
| `mnsk7_is_plp_archive()` | Сначала проверка `is_shop()` / `is_product_category()` / `is_product_tag()`; fallback — `mnsk7_is_plp_url_path()` по `REQUEST_URI` (path). | `functions.php` ~41–52 |
| `mnsk7_is_plp_url_path()` | Разбор пути: пустой path + front page = shop; иначе первый сегмент сравнивается с slug страницы магазина и с rewrite slug таксономий. | `functions.php` ~60–95 |
| Body class (filter 999) | При пустом `get_queried_object()` (типично при filter_*) дописываются классы `tax-product_cat` / `tax-product_tag` по разбору пути из `REQUEST_URI`. | `functions.php` ~1331–1349 |
| `template_include` | При `mnsk7_is_plp_url_path()` принудительно подставляется archive-product.php. | `functions.php` ~1363–1386 |
| Breadcrumbs | На PDP проверка `$_GET['product_cat']` / `$_GET['product_tag']` и cookie `mnsk7_tag_back` для «назад» к списку. | `functions.php` ~1540–1545, 1411 |
| Newsletter/contact сообщения | Вывод alert в `wp_footer` при `$_GET['mnsk7_newsletter']` / `$_GET['mnsk7_contact']`. | `functions.php` ~551–560, 602–610 |
| Redirect после контакта | `$_POST` + redirect с query `mnsk7_contact=ok|error`. | `functions.php` ~563–599 |
| Archive-product.php | Активные фильтры и чипы по `$_GET[ $param ]` для каждого `filter_*`. | `archive-product.php` ~85–152, 298–309 |
| `mnsk7_get_archive_attribute_filter_chips()` и др. | Чтение `$_GET` по именам `filter_*` для атрибутов. | `functions.php` ~1852–2018 |

Комментарий в коде (functions.php ~30, 48–49): один и тот же контекст для body_class и меню независимо от `?filter_*`, чтобы при смене main query плагинами layout не переключался. То есть зависимость от URL осознанная и частично смягчена fallback’ами по path.

### 6 вопросов: REQUEST_URI / query string / filter_*

| Вопрос | Ответ |
|--------|--------|
| **Где source of truth?** | **PHP:** функции `mnsk7_is_plp_archive()`, `mnsk7_is_plp_url_path()` и фильтры body_class/template_include в **functions.php**; шаблон **archive-product.php** читает `$_GET` для чипов. Истина частично в **REQUEST_URI** (path) и в **$_GET** (filter_*, mnsk7_newsletter, mnsk7_contact). |
| **Когда state появляется?** | **На сервере сразу** при обработке запроса: определение «это PLP» и подстановка body_class/шаблона — до вывода HTML. Состояние фильтров и чипов — при рендере archive-product. Alert для newsletter/contact — в wp_footer при наличии query. |
| **Что будет, если этот слой не доедет?** | Если перестать вызывать fallback по path: при **filter_*** `get_queried_object()` может быть пуст → не будет **body_class** PLP → **layout PLP** (таблица, сайдбар, стили) сломается. Без template_include fallback может загрузиться index вместо archive-product → **другой шаблон**. Breadcrumbs и чипы без $_GET — просто не покажут активные фильтры. |
| **От чего зависит?** | От **query string** и **REQUEST_URI**, от **rewrite rules** (slug магазина, таксономий), от того, что плагины фильтров меняют main query. От **hook priority** (body_class 999, template_include 5). |
| **Deterministic или хрупкий?** | **Хрупкий:** «обычно работает» при стандартных путях и настройках. При смене slug страницы магазина, отключении таксономий или нестандартном URL логика по path может дать сбой. |
| **Можно ли перенести выше и надёжнее?** | **Да.** Логику через **$_SERVER['REQUEST_URI']** и разбор path заменить на **нормальный conditional**: по возможности опираться на `is_shop()`, `is_product_taxonomy()`, `get_queried_object()` и только при их сбое (из-за плагина) использовать path как fallback. Сократить дублирование: один раз определить «это PLP» и использовать везде. |

---

## 5. Зависимость от body_class, которая может не добавиться

Классы на `body` используются в CSS и логике; если условие добавления не выполнится, классов не будет и вёрстка/поведение изменятся.

| Класс / группа | Условие добавления | Риск |
|----------------|--------------------|------|
| `woocommerce`, `woocommerce-page`, `post-type-archive-product`, `tax-product_cat`, `tax-product_tag` | Filter `body_class` (priority 999): только при `mnsk7_is_plp_archive()`. Внутри — при пустом `get_queried_object()` дополнение по `mnsk7_is_plp_url_path()` и разбору сегментов пути. | Если плагин или конфиг изменит путь/rewrite или вызовет страницу «как архив» без срабатывания `mnsk7_is_plp_archive()`/`mnsk7_is_plp_url_path()` — классов не будет, стили 24-plp-table.css и 05-plp-cards.css не применятся. |
| `body.woocommerce-account` | Стандартный WooCommerce. | Риск только если Woo перестанет выставлять класс на странице «Моё учётная запись». |
| `body.single-product` | Стандартный WP/Woo. | Аналогично. |

Строгая привязка: `body.post-type-archive-product`, `body.tax-product_cat`, `body.tax-product_tag` — основа для layout PLP (таблица, карточки, сайдбар, trust, фильтры). Их дополнение по REQUEST_URI (п. 4) как раз снижает риск отсутствия при filter_*.

### 6 вопросов: body_class, которая может не добавиться

| Вопрос | Ответ |
|--------|--------|
| **Где source of truth?** | **PHP:** фильтр `body_class` (priority 999) в **functions.php**; стандартные классы Woo (woocommerce-account, single-product) — **plugin** (WooCommerce). |
| **Когда state появляется?** | **На сервере сразу** при выводе `<body ...>` в header.php. Классы уже в HTML. |
| **Что будет, если этот слой не доедет?** | Если наш filter не сработает или не допишет PLP-классы (напр. при filter_*): **body_class** на архиве будет неполной → стили **24-plp-table.css**, **05-plp-cards.css** не применятся → **layout PLP** (сетка, сайдбар, trust) сломается. |
| **От чего зависит?** | От **body_class** (фильтр 999), от **mnsk7_is_plp_archive()** и **mnsk7_is_plp_url_path()** (т.е. от REQUEST_URI при пустом get_queried_object()). От **plugin markup** (Woo даёт базовые классы). |
| **Deterministic или хрупкий?** | **Хрупкий:** гарантирован только пока наш filter с priority 999 выполняется и fallback по path срабатывает. При смене приоритета или логики path классы могут не добавиться. |
| **Можно ли перенести выше и надёжнее?** | **Частично.** Единый источник «это PLP» уже есть (mnsk7_is_plp_archive); важно не дублировать условие в других местах. Классы на body — нормальный механизм WP; надёжность повышается за счёт уменьшения зависимости от REQUEST_URI (см. п. 4). |

---

## 6. Зависимость от cache / minify / порядка ресурсов

| Проблема | Где | Описание |
|----------|-----|----------|
| Critical CSS не полный | Комментарий в header.php: критичные стили хедера гарантируют тот же вид при параметрах `?filter_*` (когда cache/CDN может отдать страницу без полного CSS). | Если при кэшировании отдаётся HTML с только critical inline без основных таблиц стилей — хедер будет как в critical, остальная страница — без наших переопределений (футер, сетки, кнопки и т.д.). |
| Порядок переопределений | Inline в theme (priority 10), inline в woocommerce-layout (20), блок в wp_footer (999). | При минификации/объединении CSS порядок правил может измениться; блок в footer с 999 рассчитан на то, что он выведется после всех стилей и «победит». |
| Комментарий в коде (functions.php ~1390–1393) | Cache-Control: no-cache для URL с filter_* не ставить — иначе финальный DOM отличался (raw vs обработанный плагином), разный вид header/footer. | Явная зависимость: после деплоя нужна полная очистка кэша страницы, чтобы все URL (в т.ч. с filter_*) получили одну версию. |

### 6 вопросов: cache / minify / порядок ресурсов

| Вопрос | Ответ |
|--------|--------|
| **Где source of truth?** | Нет одного источника: порядок задаётся **PHP** (приоритеты enqueue, wp_footer 999), а финальный порядок правил зависит от **cache/minify** (плагин или хостинг). |
| **Когда state появляется?** | «Правильный» вид — **после** загрузки и применения всех CSS в нужном порядке. При full page cache — при первом запросе; при изменении порядка минификации — при следующем билде. |
| **Что будет, если этот слой не доедет?** | При кэше без полного CSS: **хедер** выживает за счёт critical inline, **footer** и **spacing/grid** — могут быть дефолтными Woo. При смене порядка: наши переопределения могут проиграть → снова **footer**, **header**, **body class**-зависимые стили. |
| **От чего зависит?** | От **порядка вывода** стилей и wp_footer, от **hook priority**, от настроек **cache/minify** (объединение, порядок файлов). |
| **Deterministic или хрупкий?** | **Хрупкий:** «обычно работает» при текущей конфигурации. После деплоя без очистки кэша или при смене плагина кэша порядок может измениться. |
| **Можно ли перенести выше и надёжнее?** | **Да.** Убрать зависимость от «последнего блока в footer»: перенести критические правила в **обычный CSS** с явной зависимостью (после Woo) и не полагаться на wp_footer как единственную точку переопределения. Тогда minify не поменяет относительный порядок внутри нашего бандла. |

---

## 7. Держится на overflow: hidden, !important, скрытии

Симптоматические фиксы (переопределение через !important или скрытие вместо нормального layout).

| Место | Приём | Файл |
|-------|--------|------|
| Clearfix Woo для `ul.products` | `content:none!important; display:none!important` для `::before` — убираем псевдоэлемент, ломающий grid. | functions.php (inline), 05-plp-cards.css, 24-plp-table.css |
| Фон футера/хедера | `#colophon.mnsk7-footer, .mnsk7-footer { background: #1e293b !important }`, аналогично хедер. | functions.php (footer inline, wp_footer block), 04-header.css |
| Десктоп: показ меню и поиска | `display:none!important` для toggle, `display:flex!important; visibility:visible!important` для dropdown поиска и меню. | header.php critical, functions.php wp_footer, 04-header.css |
| Мобильное меню | `.mnsk7-header__nav .mnsk7-header__menu { display:none!important }`, `.mnsk7-header__nav.is-open .mnsk7-header__menu { display:flex!important }`. | header.php, 04-header.css |
| Многочисленные переопределения в частях CSS | `!important` для border-radius, цветов кнопок, grid, отступов (особенно 04-header, 15-delivery-contact, 17-buttons, 24-plp-table, 05-plp-cards, 06-single-product, main.css). | Почти все parts |
| Overflow | `overflow: hidden` на хедере (внутренний контейнер, меню, карточки товаров) для обрезки dropdown/текста. | 04-header.css, 05-plp-cards.css, 02-reset-typography.css и др. |

Комментарий в 15-delivery-contact.css ~323: *«WooCommerce/Storefront ładują się później — wygrywamy !important»* — явное признание борьбы с порядком загрузки.

### 6 вопросов: overflow: hidden, !important, скрытие

| Вопрос | Ответ |
|--------|--------|
| **Где source of truth?** | **CSS** (файлы parts и main.css) и **footer-inline/PHP** (functions.php: echo стилей в wp_footer и wp_enqueue_scripts inline). Переопределения размазаны по многим файлам. |
| **Когда state появляется?** | **На сервере сразу** при выводе CSS (в head или при выводе link); блок в wp_footer — **после загрузки страницы**, при выполнении wp_footer. |
| **Что будет, если этот слой не доедет?** | Без !important/overflow: Woo/Storefront перебивут наши стили → **footer** светлый, **header** не тот, **grid** продуктов сломается (clearfix), **spacing** и кнопки — дефолтные. Без скрытия (display:none) мобильное меню и поиск могли бы «вылезти» до применения media query. |
| **От чего зависит?** | От **порядка cascade** (кто последний), от **wrapper** и **body_class** (многие селекторы привязаны к body.woocommerce-*), от **plugin markup** (Woo даёт классы и разметку, мы перебиваем !important). |
| **Deterministic или хрупкий?** | **Хрупкий:** работает, пока наш CSS идёт после Woo и мы перебиваем через !important. Любое усиление специфичности или порядка со стороны плагина ломает предсказуемость. **Фикс через скрытие** вместо перестройки layout — симптоматический. |
| **Можно ли перенести выше и надёжнее?** | **Да.** Уменьшить **количество !important**: повысить специфичность селекторов или вынести переопределения в отдельный слой (один файл после Woo) без !important. **overflow: hidden** заменить там, где возможно, на нормальный layout (flex/grid, не обрезать контент). **Фикс через скрытие** переделать в перестройку разметки/CSS, чтобы не зависеть от display:none. |

---

## 8. Держится на конкретном hook priority

Поведение или порядок вывода рассчитаны на то, что наш хук выполнится с заданным приоритетом относительно других.

| Hook | Priority | Назначение | Риск |
|------|----------|------------|------|
| `wp_footer` (global layout fix) | **999** | Вывод `<style id="mnsk7-global-layout-fix">` в конце страницы, чтобы перебить Woo/Storefront/cache. | Другой плагин/тема с 1000 или выше перебьёт; при смене порядка футера блок может оказаться не последним. |
| `body_class` (PLP классы) | **999** | Дополнение классов для архива при filter_* после остальных фильтров. | При приоритете ниже классы могут быть перезаписаны или не применены в нужном порядке. |
| `wp_enqueue_scripts` (inline theme) | 10 | Inline после частей темы. | Стандартно. |
| `wp_enqueue_scripts` (inline woocommerce-layout) | 20 | Inline к Woo layout. | Зависит от того, что woocommerce-layout регистрируется и выводится до нашего 20. |
| Остальные (template_redirect, wp, send_headers и т.д.) | 5, 6, 20 | Логика редиректов, шаблонов, заголовков. | Обычные приоритеты; конфликт возможен только с такими же 5/20 у других. |

### 6 вопросов: hook priority

| Вопрос | Ответ |
|--------|--------|
| **Где source of truth?** | **PHP:** приоритет задаётся в `add_action`/`add_filter` в **functions.php**. Кто последний в очереди — тот и задаёт итог (wp_footer 999, body_class 999). |
| **Когда state появляется?** | **На сервере:** при выполнении хуков в порядке приоритета. wp_footer 999 — в конце body; body_class 999 — при генерации атрибута body. |
| **Что будет, если этот слой не доедет?** | Если другой плагин/тема выведет стиль с **priority > 999** или body_class с более высоким приоритетом: наш **layout fix** в footer перебьётся → **footer/header** и grid; наши **body_class** могут не попасть в итоговый список → **layout PLP**. |
| **От чего зависит?** | От **hook priority** (999 как «последний»), от порядка регистрации хуков другими темами/плагинами. |
| **Deterministic или хрупкий?** | **Хрупкий:** «обычно работает», пока никто не вешает на wp_footer/body_class приоритет 1000 и выше. При смене темы/плагинов возможна регрессия. |
| **Можно ли перенести выше и надёжнее?** | **Да.** Вместо wp_footer 999 — выводить критичные стили через **обычный CSS** с поздним enqueue (зависимость после woocommerce-layout), без привязки к порядку футера. body_class 999 оставить (нужно после Woo), но минимизировать дублирование логики «это PLP» в других местах. |

---

## Сводная таблица по категориям

| Категория | Количество отмеченных мест | Критичность для «поломки» без них |
|-----------|----------------------------|------------------------------------|
| 1. JS-only класс | 12+ сценариев | Без JS теряется интерактивность; первый рендер частично прикрыт critical inline. |
| 2. Footer/head inline | 4 блока | Footer 999 критичен для фона и grid; head — для хедера при урезанном CSS. |
| 3. Структура шаблона | #content/#primary везде; #secondary только архивы/PDP | Низкий, если не менять способ вывода cart/checkout. |
| 4. REQUEST_URI / filter_* | 8+ мест | Осознанная; fallback по path уменьшает расхождения. |
| 5. body_class | PLP-классы при 999 + path fallback | Высокая для PLP layout без fallback. |
| 6. Cache/minify/order | 3 типа рисков | Разный вид при разном кэше/минификации. |
| 7. overflow/!important/скрытие | Массово в CSS и inline | Симптоматические фиксы; рефакторинг сложнее. |
| 8. Hook priority | 2 критичных (999) | Footer style и body_class — при смене порядка возможны регрессии. |

---

## Рекомендации (кратко)

1. **JS-only:** Оставить как есть с progressive enhancement; при необходимости добавить в критичный inline минимальные правила для «закрытого» состояния (уже есть для меню/поиска).
2. **Footer inline (999):** Рассмотреть перенос правил в отдельный стиль с поздним enqueue вместо сырого вывода в wp_footer, чтобы не зависеть от порядка футера.
3. **body_class (999):** Держать fallback по REQUEST_URI; при добавлении новых страниц «как PLP» учитывать необходимость тех же классов.
4. **#content/#primary:** Не выводить cart/checkout вне стандартного wrapper; при кастомных шаблонах дублировать структуру или вводить общие классы контейнера.
5. **!important/overflow:** Постепенно заменять на более специфичные селекторы или CSS-переменные/слои, где возможно.
6. **Кэш:** После деплоя чистить полный кэш страницы (в т.ч. URL с filter_*), не полагаться на no-cache для filter_*.

---

## Красные флаги — в список на переделку

Если видишь что-то из этого — это сразу в список на переделку.

| Красный флаг | Где встречается | Действие |
|--------------|-----------------|----------|
| **classList.add('...') для критичного layout state** | functions.php: `mnsk7-has-promo`, `mnsk7-search-open`, `mnsk7-header--scrolled`, `mnsk7-cookie-bar-visible`; footer.php: cookie bar, футер-аккордеон. Header: меню `.is-open`, корзина `.is-open` — интерактивны, не критичны для первого кадра при наличии critical CSS. | Критичные для layout (промо, cookie bar): по возможности переносить в PHP/body_class (класс на body при рендере по условию). |
| **echo "<style>..." в functions.php для важных layout fix** | functions.php ~681–687: `add_action('wp_footer', function() { echo "<style id=\"mnsk7-global-layout-fix\">"; ... }, 999);` — фон футера/хедера, clearfix, десктопное меню/поиск, PLP trust. | Перенести правила в обычный CSS (отдельный файл или часть parts), подключить с зависимостью после woocommerce-layout; убрать сырой echo из functions. |
| **wp_footer как единственная точка, откуда приходит нужный CSS/state** | Тот же блок wp_footer 999 — без него фон футера/хедера и grid продуктов ломаются. Inline при wp_enqueue_scripts дублирует часть правил, но «последнее слово» за footer. | Сделать так, чтобы критичный layout не зависел от wp_footer: один источник истины в CSS с корректным порядком загрузки. |
| **Селекторы вида #content #primary ... для общего UI** | 05-plp-cards.css, 24-plp-table.css, 06-single-product.css, 15-delivery-contact.css, main.css, 03-storefront-overrides.css — везде `#content`, `#content #primary`, `#content #secondary`, `body.woocommerce ... #content`. | Заменить на component class (например `.mnsk7-content-area`, `.mnsk7-main`), выводимые в шаблоне; не привязывать общий UI к id/структуре wrapper. |
| **Логика через $_SERVER['REQUEST_URI']** | functions.php: `mnsk7_is_plp_url_path()` (~60), body_class filter (~1331–1349), template_include (~1363), breadcrumbs/cookie (~1411, 1504), archive-product и хелперы фильтров (filter_* по $_GET). | По возможности опираться на нормальные conditional (is_shop(), is_product_taxonomy(), get_queried_object()); REQUEST_URI только как fallback при сломанном query. Сократить дублирование «это PLP» в одном месте. |
| **Глобальные overflow: hidden/visible** | 04-header.css (inner, nav, cart, dropdown), 05-plp-cards.css, 02-reset-typography.css, 24-plp-table.css, 06-single-product.css, main.css, 07-mnsk7-blocks.css и др. — много overflow: hidden для обрезки. | Там, где это маскирует проблему layout, перейти на нормальный layout (flex/grid, не обрезать контент). |
| **Большое количество !important** | 04-header.css, 15-delivery-contact.css, 17-buttons.css, 24-plp-table.css, 05-plp-cards.css, 06-single-product.css, main.css, 21-responsive-mobile.css, 09-footer.css — десятки переопределений с !important. | Увеличить специфичность или вынести переопределения в один слой после Woo без !important; уменьшать количество по мере рефакторинга. |
| **Conditionals, которые дублируют page type logic в нескольких местах** | «Это PLP» определяется в mnsk7_is_plp_archive(), mnsk7_is_plp_url_path(), в body_class filter, в template_include, в wp (remove breadcrumb), в archive-product (is_shop/is_product_category/is_product_tag). | Один источник истины для «это PLP» (например функция + константа/глобал при wp), остальное — вызовы этой логики. |
| **Несколько независимых мест, где определяется один и тот же state** | Фон футера/хедера и clearfix: inline при wp_enqueue_scripts (тема + woocommerce-layout) и снова в wp_footer 999. Десктопное меню/поиск: header.php critical и wp_footer block. | Один источник истины для каждого визуального состояния: один файл или один inline-блок, без дублирования в footer. |
| **«Fix» через скрытие, а не через перестройку layout** | Clearfix: content:none!important; display:none!important для ul.products::before вместо перехода на grid без псевдоэлемента. Мобильное меню: display:none / .is-open display:flex вместо одного контейнера с разным layout. | Где возможно — перестроить разметку/CSS так, чтобы не нужны были display:none/visibility для «исправления» вида. |
