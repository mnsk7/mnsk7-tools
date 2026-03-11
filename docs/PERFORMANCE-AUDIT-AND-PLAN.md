# Performance Audit & Hardening Plan — mnsk7-storefront

**Цель:** снизить стоимость первого рендера, убрать лишний CSS/JS/DOM, ускорить customer flow (mobile + desktop), вывести тему к приемлемому Lighthouse / Core Web Vitals без визуальной деградации.

**Область:** только тема (mnsk7-storefront) и её шаблоны; плагины WooCommerce/Storefront и хостинг — только в контексте рекомендаций.

---

## 1. Audit summary

### 1.1 Что проверялось (по коду и структуре)

- **Страницы:** home, product category archive, filtered archive, PDP, cart, checkout, my account.
- **Аспекты:** загрузка CSS/JS (условная/глобальная), размер DOM, блокирующие ресурсы, шрифты, изображения, фрагменты WooCommerce, header/mega menu, footer, cookie/notice UI.

### 1.2 Ключевые выводы

| Область | Факт | Влияние |
|--------|------|--------|
| **CSS** | Все 26 parts (~261 KB только тема) грузятся на каждой странице. Нет page-specific enqueue. | Render-blocking, лишний парсинг на cart/checkout/account/PDP без контента home/PLP. |
| **Header** | Критический inline в `header.php` есть; полный `04-header.css` (38 KB) + mega menu DOM (категории + теги) на каждой странице, в т.ч. mobile (скрыто CSS). | LCP/INP: тяжёлый DOM и CSS на mobile. |
| **Footer** | Один `footer.php` на всех страницах: 4 колонки + аккордеон + cookie bar HTML + 2 inline-скрипта (cookie + accordion). | Main-thread, разметка. |
| **WooCommerce** | `wc-cart-fragments` на всех не-админ страницах. Фрагменты обновляют иконку корзины и dropdown. | Блокирует интерактивность до выполнения. |
| **Шрифт** | Inter local woff2, `font-display: swap`. Preload для шрифта нет. | Риск FOIT/задержки LCP при первом рендере текста. |
| **Изображения** | В шаблонах темы — `get_image('woocommerce_thumbnail')` без явного `loading`/`sizes`. WooCommerce с 3.5+ добавляет lazy и srcset. | Зависит от Woo; в таблице PLP первая строка above-the-fold — lazy может быть излишним для первых 2–4 ячеек. |
| **Cookie/notice** | Один баннер темы; Cookie Law Info отключён фильтром — дубля нет. | Минимальный. |
| **Inline JS** | Один большой блок в `wp_footer` (меню, поиск, корзина, promo, shrink, Instagram) + условные блоки (cart, PDP sticky, PLP load more, chips, shipping notice). | Один парс на каждой странице; условные не разбиты по страницам. |
| **Archive** | Плотная вёрстка: promo, breadcrumbs, title, chips, фильтры, search, trust, таблица/карточки, footer, cookie. Все блоки в DOM сразу. | DOM size, LCP (таблица/картинки), INP (фильтры, «Pokaż więcej»). |

### 1.3 Размеры CSS темы (parts)

| Part | KB | Используется только на |
|------|-----|------------------------|
| 04-header.css | 38 | Везде (критичный above-the-fold) |
| 08-home-sections.css | 37 | Home |
| 24-plp-table.css | 35 | Archive (table layout) |
| 06-single-product.css | 17 | PDP |
| 09-footer.css | 12 | Везде |
| 05-plp-cards.css | 10 | Archive (mobile cards), home bestsellers |
| 15-delivery-contact.css | 9 | Dostawa, Kontakt |
| 18-cart-checkout.css | 8 | Cart, Checkout |
| 12-related-products.css | 6 | PDP |
| 07-mnsk7-blocks.css | 4 | Блоки/шорткоды |
| Остальные (tokens, reset, breadcrumbs, cookie, notices, buttons, responsive, print, global-layout…) | ~90 | Смешанно |

**Итого только тема:** ~261 KB (parts). Плюс parent Storefront style.css и WooCommerce (general, layout, etc.) — полный объём не мерялся здесь.

---

## 2. Таблица проблем: P1 / P2 / P3

Формат: **Problem → Source (file/context) → Fix → Expected metric impact**.

### P1 — сильно влияет на CWV / first render / customer flow

| # | Problem | Source | Fix | Expected metric impact |
|---|---------|--------|-----|------------------------|
| P1.1 | Весь CSS темы (26 parts) грузится на всех страницах | `functions.php` ~672: массив `$parts` без условий | Ввести условный enqueue: home-only (08), PDP-only (06, 12), cart/checkout-only (18), archive-only (05, 24), delivery/contact-only (15), seo/faq (13, 14) по `is_*()` | Сокращение render-blocking CSS на cart/checkout/account/PDP/home на 50–120 KB (оценка); улучшение LCP/FCP |
| P1.2 | `wc-cart-fragments` блокирует интерактивность до выполнения | `functions.php` 754–758: enqueue на всех не-админ страницах | Defer или conditional load: подключать только там, где есть header с корзиной; при необходимости — lazy load после FCP или исключить с checkout/cart (fragments там не нужны для иконки в header) | Снижение TBT/INP, быстрее интерактивность |
| P1.3 | Нет preload для основного шрифта (Inter) | Шрифт в `00-fonts-inter.css`, в head только через общий CSS | Добавить `<link rel="preload" href="…/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>` в `header.php` перед `wp_head()` или через `wp_head` hook | Раннее начало загрузки шрифта, меньше FOIT, стабильнее LCP текста |
| P1.4 | Mega menu: полный DOM категорий+тегов в header на каждой странице и на mobile (скрыт CSS) | `header.php` 76–131: get_terms для product_cat/product_tag, вывод списков | На mobile не рендерить содержимое mega menu в PHP (переменная `mnsk7_is_mobile_request()` или JS/CSS-only скрытие без разметки подменю); либо ленивая подстановка по первому hover/focus на desktop | Меньше DOM на mobile, меньше парсинга; на desktop — без потери UX |
| P1.5 | Критический CSS header’а только в inline; остальные 38 KB header’а — в отдельном файле | `header.php` inline block `#mnsk7-header-critical`; `04-header.css` целиком | Оставить/расширить critical inline только для above-the-fold header; остальной 04-header.css подгружать async (media="print" onload) или оставить блокирующим, но не дублировать | Быстрее First Paint, меньше блокировки (если async) |

### P2 — заметно, но не блокирует

| # | Problem | Source | Fix | Expected metric impact |
|---|---------|--------|-----|------------------------|
| P2.1 | Один большой inline-скрипт в footer (меню, поиск, корзина, promo, shrink, Instagram) на всех страницах | `functions.php` ~784–1032: один `wp_footer` callback | Разбить на 2: (1) критичный для LCP/INP — только открытие меню, закрытие promo, shrink; (2) остальное (search, cart, Instagram) — в отдельном скрипте с defer или после DOMContentLoaded | Меньше блокировки main thread при парсинге; точечное улучшение INP |
| P2.2 | Footer: 4 колонки + аккордеон + 2 скрипта на каждой странице | `footer.php`: разметка + cookie bar script + accordion script | Cookie bar: скрипт выполнять только если баннер видим (`mnsk7-cookie-bar-visible`). Accordion: подключать только при width < 768 или один раз с проверкой `matchMedia` внутри | Небольшое снижение TBT на desktop |
| P2.3 | На archive: все блоки (chips, фильтры, trust, таблица) в DOM сразу | `archive-product.php`: весь вывод в одном потоке | Trust row и «Więcej filtrów» уже частично скрыты/раскрываются. Рассмотреть отложенный рендер блока «Więcej filtrów» (скрытая часть) через JS после FCP или оставить как есть | Небольшое снижение DOM/парсинга при большом числе chipów |
| P2.4 | Таблица PLP: изображения первых строк — кандидаты LCP; без явных dimensions/sizes в теме | `content-product-table-row.php`: `get_image('woocommerce_thumbnail')` | Убедиться, что Woo выставляет width/height и srcset; для первой строки (первые N продуктов) опционально `loading="eager"` и явные `sizes` (например, для 4 колонок) | Стабильнее LCP, меньше CLS |
| P2.5 | Inline Instagram card styles добавляются к последнему part на всех страницах | `functions.php` 690: `wp_add_inline_style( $prev, $insta_inline )` | Подключать inline только на страницах с шорткодом `mnsk7_instagram_feed` (проверка в enqueue) | Несколько KB парсинга меньше на не-home |

### P3 — polish / cleanup

| # | Problem | Source | Fix | Expected metric impact |
|---|---------|--------|-----|------------------------|
| P3.1 | Печать (23-print.css) грузится для всех | `functions.php` список parts | Подключать только при `media="print"` (атрибут у link) или conditionally для страниц с кнопкой печати | Минимальный |
| P3.2 | Дублирование стилей Woo clearfix/account в inline у `woocommerce-layout` | `functions.php` 694–707 | Оставить как есть (нужно для переопределения); при рефакторе Woo — проверить актуальность | — |
| P3.3 | Скрипт «shipping zone notice» в footer на каждой странице | `functions.php` 1222–1240 | Уже точечный; оставить или переместить в один общий «theme-utils» с условным выполнением | Минимальный |
| P3.4 | Множество мелких inline-скриптов (cart button, PDP sticky, PLP load more, chips, shipping) | Отдельные `wp_footer` callbacks | Собрать в один минифицированный `theme-conditional.js`, подгружаемый по need (cart, single product, archive) | Чуть меньше количества скриптов и запросов |

---

## 3. Archive-specific performance

### 3.1 Блоки и их влияние

| Блок | Above-the-fold? | Влияние на LCP/INP/DOM | Рекомендация |
|------|------------------|------------------------|--------------|
| Promo bar | Да | Высота/CLS при dismiss | Оставить; высота зарезервирована через `--mnsk7-promo-h` после JS; критичный CSS уже inline в header |
| Breadcrumbs | Да | Небольшое | Оставить |
| H1 (title) | Да | Небольшое | Оставить |
| Chips (kategorie) | Да | Среднее (много ссылок) | Оставить; без тяжёлого JS |
| Chips (atrybuty: Średnica, Dł. robocza…) | Частично | «Więcej» раскрывает скрытый DOM | Уже есть скрытие; при желании — lazy-render скрытой части после FCP |
| Search | Да (desktop table) | Небольшое | Оставить |
| Trust row | Да | Небольшое | Оставить |
| Таблица / карточки (mobile) | Первые строки — да | **LCP** (изображения), DOM size | Фиксированные размеры изображений, `loading="eager"` для первых 2–4 товаров, остальные lazy |
| «Pokaż więcej» (desktop) | Нет | INP при клике (fetch) | Уже AJAX; оставить |
| Footer | Нет | DOM, скрипты | Общее решение по footer (P2.2) |
| Cookie bar | Нет (внизу экрана или overlay) | Скрипт при показе | Уже один баннер; скрипт только при видимости (P2.2) |

### 3.2 Что можно упростить без потери UX

- Не рендерить полный mega menu на mobile (только ссылка «Sklep» без подменю в DOM) — см. P1.4.
- Trust row на archive можно оставить как есть; отложенный рендер даст малый выигрыш.
- «Więcej filtrów»: скрытый блок уже в DOM; при очень большом числе атрибутов можно подгружать скрытую часть по клику (сложнее), иначе оставить.

### 3.3 Above-the-fold vs below-the-fold (archive)

- **Above-the-fold (критичный путь):** promo (если есть), header, breadcrumbs, H1, первые chips (kategorie), первый ряд фильтров (атрыбуты), search, trust, заголовок таблицы + первая строка товаров (или первые 2–4 карточки на mobile).
- **Below-the-fold:** остальные строки таблицы/карточки, «Pokaż więcej», панель сортировки/result count внизу, footer, cookie bar.
- **Conditional load для archive:** части 05-plp-cards, 24-plp-table грузить только на `mnsk7_is_plp()` (и при необходимости на search product).

---

## 4. Front-end loading strategy (рекомендации)

| Область | Рекомендация |
|--------|---------------|
| **Critical CSS** | Расширить блок в `header.php` только для header+promo (above-the-fold). Остальной 04-header и глобальные токены/типографику оставить в одном блокирующем файле или разнести: critical inline, non-critical async (media="print" onload). |
| **Non-critical CSS** | Page-specific: 06, 08, 12, 18, 24, 13, 14, 15 — подключать только на нужных страницах. |
| **JS** | `wc-cart-fragments`: defer или conditional. Остальные скрипты темы: по возможности defer; inline — только минимальный набор для меню/promo/shrink. |
| **Preload / preconnect** | Preload для Inter (woff2). Preconnect к домену CDN/статики при необходимости (если шрифты/картинки с другого домена). |
| **Font** | Оставить `font-display: swap`. Preload — см. P1.3. |
| **Images** | Следовать Woo (srcset, sizes). Для PLP: явные dimensions, для первых N в таблице — `loading="eager"` и корректные `sizes`. |
| **Layout shifts** | Promo: высота через CSS variable после первого frame. Header: фиксированная min-height. Таблица: резерв высоты строк или aspect-ratio для thumb. Cookie bar: фиксировать позицию, не сдвигать контент при появлении (уже внизу). |
| **Server/cache/CDN** | За пределами темы; при наличии — cache for static assets, Vary: User-Agent уже для PLP (functions.php). |

---

## 5. Backlog задач (краткий список с критериями)

Для каждой задачи: **title**, **why**, **affected files**, **expected change**, **risk**, **acceptance criteria**.

| ID | Title | Why | Affected files | Expected change | Risk | Acceptance criteria |
|----|--------|-----|----------------|-----------------|------|---------------------|
| T1 | Page-specific CSS enqueue | Меньше render-blocking на страницах без контента | `functions.php` | Подключать 06, 08, 12, 18, 24, 13, 14, 15 только на нужных страницах | Регрессия стилей на edge-cases (например, shortcode на «чужой» странице) | На cart/checkout/account нет 08, 24, 06, 12; на home есть 08; на PDP есть 06, 12; на archive есть 05, 24; визуально без изменений |
| T2 | Defer or conditional wc-cart-fragments | Быстрее интерактивность | `functions.php` | Defer или enqueue только при наличии блока корзины в header | Корзина в header не обновляется после add-to-cart — проверить сценарий | После добавления в корзину счётчик и dropdown обновляются; TBT не растёт |
| T3 | Preload Inter font | Ранняя загрузка шрифта, стабильнее LCP | `header.php` или hook `wp_head` | Добавить preload для `inter-latin-wght-normal.woff2` | Нет | В Network шрифт запрашивается рано; визуально без регрессии |
| T4 | Reduce mega menu DOM on mobile | Меньше DOM и парсинга на mobile | `header.php` | На mobile не выводить списки категорий/тегов внутри mega menu (или выводить упрощённый вариант) | На mobile при клике «Sklep» переход на shop — проверить | На mobile нет лишних списков в DOM; переход в sklep работает |
| T5 | Critical CSS: only above-the-fold header | Меньше блокировки при сохранении вида | `header.php`, опционально новый part | Сузить inline critical до минимума; при необходимости async для 04-header | Визуальная регрессия при медленной загрузке 04 | First Paint без «прыжков»; Lighthouse не показывает регрессию LCP |
| T6 | Split footer inline scripts | Меньше блокировки main thread | `functions.php`, `footer.php` | Cookie script только при видимости баннера; accordion — один раз с matchMedia | Регрессия accordion/cookie на мобильных | Cookie bar и accordion работают как раньше на mobile/desktop |
| T7 | Eager loading + sizes for first PLP row | Стабильнее LCP на archive | `content-product-table-row.php`, возможно Woo filters | Для первых N продуктов (например 4) — `loading="eager"`, явные sizes | Нет | LCP на archive не хуже; CLS не растёт |
| T8 | Conditional Instagram inline CSS | Меньше парсинга на страницах без Instagram | `functions.php` | Добавить проверку контента/shortcode перед `wp_add_inline_style` для insta | Блок Instagram на странице без shortcode (вставка вручную) — редкий кейс | На страницах без шорткода Instagram нет inline insta-стилей |
| T9 | Consolidate conditional inline scripts | Меньше запросов/парсов | `functions.php` | Один `theme-conditional.js` для cart, PDP, PLP, shipping с условной загрузкой | Регрессия поведения кнопок/sticky | Cart, PDP sticky, PLP load more, chips, shipping notice работают как сейчас |

---

## 6. Mini-plan внедрения по фазам

| Фаза | Задачи | Содержание | Риск |
|------|--------|------------|------|
| **Phase 1 — Quick wins, low risk** | T3, T8 | Preload шрифта; conditional Instagram inline CSS | Низкий |
| **Phase 2 — Page-specific assets** | T1 | Условный enqueue CSS по типам страниц | Средний (тщательный QA) |
| **Phase 3 — Archive, PDP, cart, checkout** | T4, T5, T7, T6 | Mega menu DOM на mobile; critical CSS; eager/sizes первая строка PLP; footer scripts | Средний |
| **Phase 4 — JS и polish** | T2, T9 | Cart fragments defer/conditional; объединение условных скриптов | Средний (проверка корзины и фрагментов) |
| **Phase 5 — QA и замеры** | — | Регрессия по страницам; замер LCP, INP, CLS, TBT | — |

Порядок выполнения: Phase 1 → замеры → Phase 2 → замеры → Phase 3 → замеры → Phase 4 → полный QA.

---

## 7. Before/after measurement plan

### 7.1 Страницы для замера

- Home
- Product category archive (один фиксированный URL)
- Filtered archive (категория + один filter_*)
- PDP (один фиксированный товар)
- Cart (с 1 товаром)
- Checkout (гость)
- My account (или логин)

### 7.2 Метрики

- **LCP** (target: < 2.5 s)
- **INP** (target: < 200 ms) или FID где доступно
- **CLS** (target: < 0.1)
- **TBT** / блокировка main thread
- **FCP**
- Размер DOM (количество узлов) на archive и home
- Объём CSS (KB), загружаемый на каждой из страниц (после Phase 2 — сравнить с текущим)

### 7.3 Условия замера

- Один и тот же хостинг/staging; без лишних плагинов.
- Mobile: throttling (например Lighthouse Mobile).
- Desktop: без throttling.
- Повторить 3–5 раз, брать медиану.

### 7.4 Критерии успеха

- LCP на archive и PDP не ухудшается (желательно улучшение на 5–15%).
- INP/TBT не растут после изменений (особенно после T2, T6, T9).
- CLS остаётся < 0.1.
- На cart/checkout/account объём загружаемого CSS темы уменьшается (после T1) без визуальной регрессии.
- Количество узлов DOM на archive (mobile) уменьшается после T4.

---

## 8. Сводная таблица: problem → source → fix → expected impact

Ниже — плоская таблица для быстрого поиска по файлам.

| Problem | Source file / context | Fix | Expected metric impact |
|---------|----------------------|-----|------------------------|
| Все CSS parts на всех страницах | `functions.php` ~672 `$parts` | Условный enqueue по is_front_page, is_shop, is_product_category, is_product_tag, is_singular('product'), is_cart, is_checkout, is_account_page, is_page('dostawa-i-platnosci'), is_page('kontakt') | −50…120 KB CSS на части страниц; лучше LCP/FCP |
| wc-cart-fragments везде | `functions.php` 754–758 | Defer или enqueue только при отображении header cart | Меньше TBT/INP |
| Нет preload шрифта | Нет в теме | `header.php` или hook: preload Inter woff2 | Раньше LCP текста, меньше FOIT |
| Mega menu полный DOM на mobile | `header.php` 76–131 | Не выводить списки категорий/тегов на mobile (или по флагу) | Меньше DOM, меньше парсинга на mobile |
| 04-header весь блокирующий | `header.php` + parts 04 | Оставить/расширить critical inline; опционально async для остального 04 | Быстрее First Paint |
| Один большой inline script footer | `functions.php` ~784–1032 | Разделить на критичный (меню, promo, shrink) и остальное (search, cart, Instagram) | Меньше блокировки парсинга |
| Footer: cookie + accordion scripts всегда | `footer.php` 154–239, 241–273 | Cookie — только при видимости; accordion — один раз с matchMedia | Небольшое снижение TBT |
| Trust / «Więcej filtrów» в DOM сразу | `archive-product.php` | Опционально lazy-render скрытой части фильтров | Небольшое снижение DOM |
| PLP первая строка изображений | `content-product-table-row.php` | loading="eager" и sizes для первых N; dimensions от Woo | Стабильнее LCP, меньше CLS |
| Instagram inline на всех страницах | `functions.php` 690 | Добавить условие (has shortcode / post content check) | Несколько KB меньше на не-home |
| 23-print.css для всех | `functions.php` $parts | media="print" для link или conditional | Минимальный |
| Много мелких conditional scripts | `functions.php` несколько wp_footer | Один theme-conditional.js по need | Меньше скриптов |

---

*Документ подготовлен по коду темы mnsk7-storefront. Фактические цифры LCP/INP/TBT следует снять на стенде до и после изменений; в документе указаны только ожидаемые направления влияния.*

---

## 9. Wdrożone zmiany (performance fixes)

| Data | Zadanie | Pliki | Uwagi |
|------|---------|-------|--------|
| 2026-03 | **T3** Preload Inter | `header.php` | Dodano `<link rel="preload" href="…/assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>` przed `wp_head()`. |
| 2026-03 | **T8** Conditional Instagram inline | `functions.php` | Inline CSS karty Instagram dodawany tylko gdy `is_front_page()` lub treść strony zawiera shortcode `[mnsk7_instagram_feed]`. |
| 2026-03 | **T2** Defer wc-cart-fragments | `functions.php` | Filtr `script_loader_tag`: atrybut `defer` dla `wc-cart-fragments` — mniej TBT, szybsza interaktywność. |
| 2026-03 | **T4** Mega menu: mniej DOM na mobile | `header.php` | Gdy `mnsk7_is_mobile_request()`: nie wywołujemy `get_terms`, nie renderujemy `<ul class="sub-menu mnsk7-megamenu">`. Link „Sklep” nadal prowadzi do /sklep/. |
| 2026-03 | **Cookie bar tylko gdy widoczny** | `footer.php` | Bar + skrypt cookie wyświetlane tylko gdy `mnsk7_show_cookie_bar` i brak zgody (`mnsk7_get_cookie_consent()` !== accept/reject). Przy już ustawionej zgodzie nie wysyłamy HTML ani skryptu. |
| — | **T1** Page-specific CSS | — | Nie wdrożone: temat ładuje jeden plik `main.css` (zbudowany z parts), nie parts osobno. Aby włączyć T1, trzeba by wrócić do ładowania parts z warunkami lub podzielić main.css na critical + page-specific. |

### Pomiar baseline i po optymalizacjach

Lighthouse na staging (mobile simulation). Before: `lighthouse-*-before.json`; After: `lighthouse-*-after.json` (po pushu z T2, T4, cookie bar, preload, conditional Instagram).

| Strona | Pomiar | Performance | FCP | LCP | TBT | CLS |
|--------|--------|-------------|-----|-----|-----|-----|
| **Home** (/) | Before | 53 | 2,18 s | 3,47 s | 1881 ms | 0,004 |
| **Home** (/) | After | **58** | **1,79 s** | **3,13 s** | **1668 ms** | 0,004 |
| **Archive** (/sklep/) | Before | 60 | 2,38 s | 3,95 s | 673 ms | 0,009 |
| **Archive** (/sklep/) | After | **69** | **1,78 s** | **2,68 s** | **1087 ms** | 0,005 |

**Obserwacje po optymalizacjach:**  
- **Home:** poprawa we wszystkich metrykach (FCP, LCP, TBT, score).  
- **Archive:** poprawa FCP/LCP i score, **ale TBT się pogorszyło** (673 ms → 1087 ms).  
- **CLS:** bez zmian (w normie).  

Czyli: render speed się poprawił; na archive interactivity / main-thread cost (TBT) uległo pogorszeniu i wymaga osobnej analizy.

Kolejne pomiary (np. po cache purge lub Phase 3):

```bash
npx lighthouse https://staging.mnsk7-tools.pl --only-categories=performance --output=json --output-path=docs/lighthouse-home-after.json
npx lighthouse https://staging.mnsk7-tools.pl/sklep/ --only-categories=performance --output=json --output-path=docs/lighthouse-archive-after.json
```

---

### Follow-up: regresja TBT na archive

**Aktualny status (bez upiększania):**
- **Home:** improvement potwierdzony (FCP, LCP, TBT, score w górę).
- **Archive:** rendering lepszy (FCP, LCP, score), **ale TBT się pogorszyło** (673 → 1087 ms).
- **CLS:** stabilny.

**1. Co dało lepszy FCP/LCP na archive**  
- Mniej DOM na mobile (T4: brak mega menu) → szybszy parse i mniej Style & Layout (breakdown: styleLayout 929 → 733 ms).  
- Preload fontu → szybsze pierwsze malowanie tekstu.  
- Defer `wc-cart-fragments` → mniej blokowania w fazie parsowania; LCP może się skończyć wcześniej, zanim skrypt się wykona.

**2. Co mogło podnieść TBT na archive**  
- **Defer cart-fragments:** skrypt wykonuje się *po* parse; ta sama praca (XHR + aktualizacja fragmentów) przesuwa się w czasie i może dać długi task (≈300+ ms) w oknie TBT. W breakdown „Other” rośnie (1358 → 1879 ms) — tam często lądują callbacki i aktualizacje DOM.  
- Liczba long tasks bez zmian (17), ale po zmianach są 3 zadania ~330–370 ms (vs wcześniej jedno 471 ms); suma czasu blokowania rośnie.  
- Inline skrypty w footerze (menu, search, cart toggle, accordion) nadal wykonują się na archive; nie były redukowane.

**3. Long tasks / JS po zmianach**  
- Archive after: najdłuższe zadania to „Unattributable” i dokument (sklep/) — inline / strona.  
- Bootup: ~2171 ms z samej strony (inline), potem jQuery, PWA, Ultimate Member, Woo order-attribution.  
- Brak dowodu na „cięższą” hydratację; główna różnica to **przesunięcie** wykonania cart-fragments (defer) w czasie, co może zwiększyć TBT przy tej samej lub większej sumarycznej pracy main thread.

**4. Następny quickest fix (archive: LCP &lt; 2,5 s i TBT w dół)**  
- **TBT:** Rozważyć **conditional load** `wc-cart-fragments` tylko na stronach z formularzem „Dodaj do koszyka” (np. archive, PDP), albo ładować go **lazy** (np. po `requestIdleCallback` / po FCP), żeby nie blokował w pierwszych 5 s. Alternatywa: zostawić defer, ale rozbić duży blok inline w footerze (P2.1), żeby krótsze taski.  
- **LCP archive:** Już 2,68 s; do celu &lt; 2,5 s: priorytet dla LCP elementu (obraz/tekst w pierwszym wierszu tabeli) — `fetchpriority="high"`, ewentualnie `loading="eager"` dla pierwszych 2–4 miniatur; upewnić się, że rozmiary obrazków (width/height/sizes) są ustawione, żeby uniknąć reflow.  
- **Pomiar:** Po wdrożeniu conditional/lazy cart-fragments lub rozbiciu inline — ponowny Lighthouse archive i porównanie TBT oraz LCP.
