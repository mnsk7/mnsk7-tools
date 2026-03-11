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

**✅ Выполнено (05_theme_ux_frontend):** Добавлены `mnsk7_is_plp()` (читает/кэширует `$GLOBALS['mnsk7_is_plp']`), **lazy-eval** при первом вызове — без хука wp priority 1, чтобы pluginy zmieniające query (filter_*, taxonomy, koszyk) nie były wyprzedzane. W `mnsk7_is_plp_archive()` na początku: **`is_search() → false`** — product search (?s=...&post_type=product) nigdy nie jest PLP, archive-product.php nie jest podsuwany. Fallback `if ( ! $obj )` w body_class (tax-* po ścieżce) pozostaje **tylko wewnątrz gałęzi** `if ( mnsk7_is_plp() )`. Wszystkie sprawdzenia używają tylko `mnsk7_is_plp()`.

---

### Задача 3. Критичный layout state промо и cookie bar — из JS в PHP/body_class

**Красный флаг:** classList.add для критичного layout state (промо, cookie bar).

**Что сделать:**
- **Промо-бар:** если решение «показан/скрыт» можно вынести на сервер (например по cookie/session или по наличию элемента в шаблоне), выводить класс `mnsk7-has-promo` на `<body>` в header.php при рендере (PHP), а не добавлять через JS после DOMContentLoaded. JS оставить только для dismiss (удаление блока и снятие класса при закрытии).
- **Cookie bar:** в mu-plugin (или теме), где показывается плашка, при рендере страницы, где бар виден, добавлять класс `mnsk7-cookie-bar-visible` на body из PHP; при скрытии/принятии — по возможности обновлять через перезагрузку или оставить снятие класса в JS. Цель — чтобы отступ `#page` не зависел от того, успел ли выполниться JS.

**Файлы:** `wp-content/themes/mnsk7-storefront/functions.php`, `footer.php`, `header.php`; при необходимости `wp-content/mu-plugins/` (cookie bar).

**Критерий приёмки:** При отключённом JS промо-бар и cookie bar (если показываются) не ломают отступ/верстку — класс на body уже в HTML там, где нужно.

**✅ Выполнено (05_theme_ux_frontend):** Промо уже добавлялся в body_class (priority 5). Dodano: klasa `mnsk7-cookie-bar-visible` z PHP gdy brak cookie `mnsk7_cookie_consent` (accept/reject). **Źródło prawdy po stronie serwera = cookie** — JS przy accept/reject ustawia `document.cookie` (footer.php setConsent), żeby kolejny request widział ten sam stan; nie używamy sessionStorage do visibility, first paint jest deterministyczny. JS dalej dodaje/usuwa klasę przy show/hide w tej samej sesji.

---

### Задача 4. Селекторы #content #primary → component class

**Красный флаг:** Селекторы вида #content #primary для общего UI.

**Что сделать:**
- Ввести component class для основной области контента (например `.mnsk7-content-area`, `.mnsk7-main`) и выводить их в шаблоне wrapper (например в `woocommerce/global/wrapper-start.php`) на тот же элемент, что сейчас имеет `id="primary"` и т.д., либо оборачивать в дополнительный div с классом.
- В CSS (05-plp-cards.css, 24-plp-table.css, 06-single-product.css, 15-delivery-contact.css, main.css, 03-storefront-overrides.css) заменить селекторы вида `#content #primary`, `#content #secondary`, `body.woocommerce #content` на селекторы по новым классам (и при необходимости по body.woocommerce / body.single-product и т.д.), чтобы общий UI не зависел от id и структуры wrapper.

**Файлы:** `wp-content/themes/mnsk7-storefront/woocommerce/global/wrapper-start.php`, перечисленные CSS parts и main.css.

**Критерий приёмки:** Вёрстка PLP, PDP, account, cart, checkout не изменилась визуально; при этом в CSS нет зависимости от #content/#primary/#secondary для общих правил (используются только component class).

**Status: Pending acceptance after visual regression.** Dowiedzione: jeden #content na stronach; product search nie podmieniany przez nasz archive-product.php. **Do akceptacji:** (1) Auto-check `scripts/task4-regression-check.sh` z stałym PDP URL. (2) Po deployu i cache purge — ręczna/Browser weryfikacja tabeli w [docs/TASK4-DOM-AND-DUAL-SUPPORT.md](./TASK4-DOM-AND-DUAL-SUPPORT.md) (layout/wrapper/spacing/header/sidebar dla każdej strony). Task 4 accepted wyłącznie po wypełnieniu tabeli (wszystkie ok).

---

### Задача 5. Уменьшить зависимость от REQUEST_URI

**Красный флаг:** Логика через $_SERVER['REQUEST_URI'].

**Что сделать:**
- Везде, где возможно, использовать нормальные conditional: `is_shop()`, `is_product_taxonomy()`, `get_queried_object()`. Разбор `mnsk7_is_plp_url_path()` по REQUEST_URI оставить только как **fallback**, когда из-за плагина (filter_*) main query уже изменён и is_shop()/is_product_category() дают false.
- После Задачи 2 вызовы «это PLP» уже идут из одного места; убедиться, что внутри этого места приоритет у стандартных условных, path — только fallback.

**Файлы:** `wp-content/themes/mnsk7-storefront/functions.php` (mnsk7_is_plp_url_path, body_class, template_include, breadcrumbs).

**Критерий приёмки:** Поведение на shop/category/tag и с ?filter_* сохранено; код явно разделяет «основная проверка» и «fallback по path».

**Status: Accepted only if confirmed by actual code path** (nie przez docblock/komentarze). Dowód — sekcja Proof Task 5 poniżej.

---

### Задача 6. Уменьшить дублирование CSS (footer/header/clearfix)

**Красный флаг:** Несколько независимых мест, где определяется один и тот же state (фон футера/хедера, clearfix, десктопное меню/поиск).

**Что сделать:**
- После Задачи 1 критичный блок уже вынесен из wp_footer в один CSS. Проверить, что inline при wp_enqueue_scripts (тема + woocommerce-layout) и critical в header.php не дублируют одни и те же правила с другими значениями. Оставить **один** источник для каждого визуального состояния: например фон футера/хедера — только в одном файле или одном inline-блоке (лучше в parts), не в трёх местах.

**Файлы:** `wp-content/themes/mnsk7-storefront/functions.php` (wp_enqueue_scripts, inline), `header.php` (critical), новые/существующие parts.

**Критерий приёмки:** Нет двух разных мест, где задаётся один и тот же стиль для одного и того же элемента (фон футера, фон хедера, clearfix ul.products, десктопное отображение меню/поиска).

**Status: Partially accepted with known exception.** Single source of truth dla footer/header/desktop menu/PLP trust/buttons — 25-global-layout.css; reguły te nie są duplikowane w theme enqueue ani w wp_footer. **Wyjątek:** clearfix `ul.products::before` jest celowo zduplikowany w inline do handle `woocommerce-layout` (functions.php), żeby wygrać z woocommerce-layout.css ładującym się później. Dowód — sekcja Proof Task 6 poniżej.

---

### Задача 7. Снизить количество !important и убрать фикс через скрытие (по мере рефакторинга)

**Красный флаг:** Большое количество !important; «fix» через скрытие, а не через перестройку layout.

**Что сделать:**
- **!important:** В новых и затрагиваемых при рефакторинге файлах повышать специфичность селекторов или выносить переопределения в один слой после Woo так, чтобы не нужен был !important. Постепенно уменьшать количество в 04-header.css, 15-delivery-contact.css, 17-buttons.css, 24-plp-table.css, 05-plp-cards.css, 06-single-product.css, main.css, 21-responsive-mobile.css, 09-footer.css.
- **Скрытие:** Для clearfix `ul.products::before`: рассмотреть переход на grid без псевдоэлемента (если Woo не добавляет ::before в ключевых местах после наших правок). Для мобильного меню: по возможности не полагаться на display:none + .is-open display:flex как единственный способ — документировать или оставить как есть, если перестройка разметки слишком затратна.

**Файлы:** перечисленные CSS parts; при необходимости шаблоны хедера/футера.

**Критерий приёмки:** Меньше правил с !important; где переделано — layout не держится на скрытии (display:none/visibility) без необходимости.

**Status: Reviewed/documented, deferred, not completed.** Dodanie komentarzy w CSS ≠ wykonanie zadania. W kodzie tylko komentarze (25-global-layout, 05-plp-cards). Redukcja !important i rezygnacja z fixów przez ukrywanie — w **hardening backlog**.

---

### Задача 8. Overflow: hidden — только там, где необходимо

**Красный флаг:** Глобальные overflow: hidden/visible.

**Что сделать:**
- Пройти по 04-header.css, 05-plp-cards.css, 02-reset-typography.css, 24-plp-table.css, 06-single-product.css, main.css, 07-mnsk7-blocks.css: где overflow: hidden маскирует проблему layout (обрезка вместо нормальной сетки/флекса), заменить на нормальный layout (flex/grid). Там, где overflow: hidden обоснован (карусель, обрезка изображения), оставить с комментарием.

**Файлы:** перечисленные CSS.

**Критерий приёмки:** Нет лишних глобальных overflow: hidden; оставшиеся — обоснованы и по возможности локализованы.

**Status: Reviewed/documented, deferred, not completed.** Dodanie komentarzy ≠ wykonanie zadania. W kodzie tylko komentarze przy overflow. Usunięcie nieuzasadnionych overflow i poprawa layoutu — w **hardening backlog**.

---

## Proof Task 5 (standard conditional first, REQUEST_URI fallback)

**Kod:** `functions.php`, funkcja `mnsk7_is_plp_archive()` (linie 36–53).

- **Najpierw standard conditional:** `is_shop()` \|\| `is_product_category()` \|\| `is_product_tag()` (43–44) → return true.
- **Potem:** `get_queried_object()` — jeśli `WP_Term` i taxonomy `product_cat`/`product_tag` (46–48) → return true.
- **Na końcu fallback:** `return mnsk7_is_plp_url_path();` (53) — parsowanie REQUEST_URI tylko gdy powyższe nie dały true.

`mnsk7_is_plp_url_path()` (61–108) wywoływane **tylko** wewnątrz `mnsk7_is_plp_archive()`. Na zewnątrz używana wyłącznie `mnsk7_is_plp()` (cache $GLOBALS, wywołuje `mnsk7_is_plp_archive()`).

**Wszystkie miejsca używające tej logiki (wyłącznie `mnsk7_is_plp()`):**

| Plik | Miejsce |
|------|--------|
| `header.php` | ok. 70: `$is_shop_archive = … mnsk7_is_plp()` |
| `functions.php` | ok. 1329: `$is_plp = … mnsk7_is_plp()` (woocommerce_before_main_content) |
| `functions.php` | ok. 1363: `if ( ! mnsk7_is_plp() ) return $template` (template_include) |
| `functions.php` | ok. 1406: `if ( ! mnsk7_is_plp() ) return $classes` (body_class 999) |
| `functions.php` | ok. 1439: `if ( … mnsk7_is_plp() )` (send_headers) |
| `functions.php` | ok. 1546: `mnsk7_is_plp() && …` (woocommerce_get_breadcrumb) |
| `woocommerce/archive-product.php` | ok. 17: `$show_breadcrumb = … mnsk7_is_plp()` |

---

## Proof Task 6 (single source CSS — co usunięto, gdzie jest)

**Usunięte z kodu:** W `functions.php` w callbacku `wp_enqueue_scripts` (ok. 688): do ostatniego partu tematy dodawany jest **tylko** `$insta_inline` (Instagram). Komentarz: „Footer/clearfix/header — source of truth w 25-global-layout.css”. Zmiennych `$footer_inline` / `$clearfix_inline` **nie ma** — nie są dodawane do żadnego handle. Blok `wp_footer` z `<style>` (priority 999) z regułami footer/header/clearfix **został usunięty** (Task 1).

**Gdzie te reguły są teraz (tylko 25-global-layout.css):**

| Reguły | Plik (linie) |
|--------|---------------|
| `ul.products::before` — content: none; display: none | 25-global-layout.css (22–30) |
| `#colophon.mnsk7-footer`, `.mnsk7-footer` — background, color | 25-global-layout.css (32–52) |
| `#masthead.mnsk7-header` — background | 25-global-layout.css (54–57) |
| Desktop 1025px: menu/search toggle display:none, dropdown visible | 25-global-layout.css (59–75) |
| `.mnsk7-plp-trust` — flex | 25-global-layout.css (77–91) |
| Przyciski `.button` — border-radius | 25-global-layout.css (93–108) |

**Wyjątek (celowa duplikacja):** Inline do handle `woocommerce-layout` (functions.php 694–707) zawiera **tę samą** regułę clearfix `ul.products::before`, żeby wygrać z woocommerce-layout.css. Plus reguły `body.woocommerce-account` (przyciski, padding #content) — nie są to reguły footer/header/clearfix z 25.

**Critical w header.php:** Tylko header (first paint). Pełny zestaw w 25.

**Exact files touched:** `assets/css/parts/25-global-layout.css`, `functions.php` (enqueue: tylko $insta_inline; osobny callback: inline do woocommerce-layout), `header.php` (critical inline).

---

## Deferred backlog (Task 7, Task 8)

- **Task 7:** Redukcja `!important`; rezygnacja z fixów przez ukrywanie (display:none, .is-open). **Nie wykonane.** Komentarze w CSS ≠ wykonanie.
- **Task 8:** Usunięcie nieuzasadnionych `overflow: hidden`; poprawa layoutu zamiast maskowania. **Nie wykonane.** Komentarze przy overflow ≠ wykonanie.

---

## Stop point

**Handoff fragility-refactor kończy się tutaj.** Nie kontynuować nowych zadań architektonicznych w tym handoffie. Następny blok roboczy: **HEADER UI FIXES**, nie fragility-refactor.

---

## Podsumowanie: co realnie zmienione / co udokumentowane / co w backlogu

| Obszar | Status | Uwagi |
|--------|--------|-------|
| **Task 4** | Pending acceptance after visual regression | Tabela w TASK4-DOM-AND-DUAL-SUPPORT.md; auto-check + ręczna weryfikacja. |
| **Task 5** | Accepted only if confirmed by actual code path | Dowód: Proof Task 5 (kolejność w `mnsk7_is_plp_archive()`, miejsca wywołań `mnsk7_is_plp()`). Nie przez docblock/komentarze. |
| **Task 6** | Partially accepted with known exception | Single source 25-global-layout; wyjątek: intentional clearfix duplication in inline for woocommerce-layout. Dowód: Proof Task 6. |
| **Task 7** | Reviewed/documented, **deferred, not completed** | Komentarze w CSS ≠ refaktor. Backlog: redukcja !important, rezygnacja z fixów przez ukrywanie. |
| **Task 8** | Reviewed/documented, **deferred, not completed** | Komentarze przy overflow ≠ refaktor. Backlog: usunięcie nieuzasadnionych overflow, poprawa layoutu. |

Dodanie komentarzy **nie** uznaje się za wykonanie zadania 7 ani 8.

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
| 7 | Меньше !important, меньше fix через скрытие | **Udokumentowane / odłożone.** W kodzie tylko komentarze; redukcja w backlogu. |
| 8 | Overflow только где нужно | **Udokumentowane / odłożone.** W kodzie tylko komentarze; przegląd/cleanup w backlogu. |

После всех задач: полная проверка корзины, чекаута, скорости загрузки; при деплое — очистка полного кэша страницы (в т.ч. URL с filter_*).

---

## Weryfikacja (uwagi 2026-03-11)

| Pytanie | Odpowiedź / zmiana |
|--------|---------------------|
| **Product search** — czy ?s=…&post_type=product nie jest traktowany jak PLP? | W `mnsk7_is_plp_archive()` na początku: **`is_search() → false`**. Search (zwykły i product) nigdy nie jest PLP; `template_include` nie podsuwa archive-product.php. |
| **body_class fallback** `if ( ! $obj )` — tylko wewnątrz PLP? | Tak. Ten blok jest wewnątrz filtra, który wcześniej robi `if ( ! mnsk7_is_plp() ) return $classes;`. Dopisano komentarz w kodzie. |
| **wp priority 1** — czy main query gotowy? Ryzyko wczesnego cache? | Usunięto `add_action('wp', ..., 1)`. **Tylko lazy-eval** przy pierwszym wywołaniu `mnsk7_is_plp()` — cache w $GLOBALS w momencie pierwszego konsumenta (template_include, body_class itd.), więc po ewentualnych zmianach query przez pluginy. |
| **Cookie bar** — źródło prawdy: cookie czy sessionStorage? | **Cookie.** PHP (body_class) czyta tylko cookie. JS w `setConsent()` ustawia cookie (i localStorage); przy następnym request PHP widzi ten sam stan. W kodzie: komentarze w functions.php i footer.php. |

---

## Ссылки

- **Полный аудит и 6 вопросов по каждому механизму:** [docs/LAYOUT-STATE-FRAGILITY-AUDIT.md](./LAYOUT-STATE-FRAGILITY-AUDIT.md)
- **Правила репозитория:** [.cursorrules](../.cursorrules) (только theme, mu-plugins; план до изменений, тесты после)
