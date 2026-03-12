# Ревью кода: соответствие документации Storefront и ARCHITECTURE

**Дата:** 2026-03-11  
**Область:** тема mnsk7-storefront, скрипты деплоя, связка с родительской темой Storefront  
**Источники:** docs/STOREFRONT.md, docs/storefront-reference/*, docs/ARCHITECTURE.md

---

## Резюме

| Категория | Оценка | Комментарий |
|-----------|--------|-------------|
| Соответствие документации Storefront | ✅ Соответствует | Parent не редактируется, enqueue/remove_action по доке, деплой только child |
| Структура overrides WooCommerce | ✅ Соответствует | Имена и иерархия шаблонов корректны, только child |
| Рекомендации ARCHITECTURE (логика не в шаблонах) | ⚠️ Частично | Логика в functions.php и в шаблонах; папки inc/ нет |
| Безопасность и экранирование | ⚠️ Есть замечания | Отдельные phpcs:ignore, в целом вывод экранируется |
| Зависимость от версии Storefront | ⚠️ Учтено | Хуки storefront_* при смене версии parent требуют проверки |

---

## 1. Родительская тема не редактируется

**Документация:** «Не править файлы внутри storefront/; все изменения — в child или в плагине/mu-plugin.»

**Проверка кода:**

- В репозитории нет каталога `wp-content/themes/storefront/`.
- Обращения к parent только через API WordPress:
  - `get_template()` — проверка slug.
  - `get_template_directory()` — путь к `style.css` для `is_readable()` и fallback в `template_include` (чтение, не запись).
  - `get_template_directory_uri()` — URL для `storefront-style`.
- Включений/требований файлов из parent нет (`require`/`include` по путям parent не используются).

**Вердикт:** ✅ Соответствует. Родительская тема не правится и не подключается напрямую по путям.

---

## 2. Метаданные и проверка наличия parent

**Документация:** Template: storefront, совместимость с версией; при отсутствии parent не подключать storefront-style.

**Проверка кода:**

- `style.css`: `Template: storefront`, в комментарии указано `Compatible with Storefront 4.6`.
- `mnsk7_parent_storefront_available()` (functions.php ~112–118): проверка `get_template() === 'storefront'` и `is_readable(get_template_directory() . '/style.css')`.
- В `wp_enqueue_scripts`: при `mnsk7_parent_storefront_available()` подключаются `storefront-style` и child с зависимостью; иначе только child без зависимости.

**Вердикт:** ✅ Соответствует.

---

## 3. Подключение стилей и порядок

**Документация:** storefront-style → mnsk7-storefront-style → части 00…24 цепочкой.

**Проверка кода:**

- Порядок в functions.php (~628–660): при наличии parent — `storefront-style`, затем `mnsk7-storefront-style` с `array('storefront-style')`, затем цикл по `$parts` с зависимостью от предыдущего handle; при отсутствии parent — только child и parts.
- Список parts совпадает с файлами в `assets/css/parts/` (00-fonts-inter … 24-plp-table); отсутствующие файлы пропускаются через `file_exists`, при отсутствии всех parts подключается `main.css`.
- После частей добавляется inline (footer, insta, clearfix) к последнему handle.

**Вердикт:** ✅ Соответствует.

---

## 4. Отключение элементов Storefront (header, footer, PLP)

**Документация:** Свой header/footer в child; вывод Storefront в header/footer убирается через remove_action. На PLP один блок сортировки (после цикла), верхний убран.

**Проверка кода:**

- **Header/Footer:** в `init` при `mnsk7_parent_storefront_available()` снимаются все действия с `storefront_header` (skip_links, site_branding, secondary_navigation, primary_navigation_wrapper, primary_navigation, header_cart, primary_navigation_wrapper_close) и с `storefront_footer` (footer_widgets, credit). Комментарий явно запрещает добавлять элементы headera в этот блок.
- **PLP:** в хуке `wp` (приоритет 25) для shop/category/tag снимаются в `woocommerce_before_shop_loop`: storefront_sorting_wrapper, woocommerce_catalog_ordering, woocommerce_result_count, woocommerce_pagination, storefront_sorting_wrapper_close. В `woocommerce_after_shop_loop` добавлен result_count (5), сняты storefront_sorting_wrapper и storefront_sorting_wrapper_close. При `$GLOBALS['mnsk7_plp_use_table']` в after_shop_loop снимается woocommerce_pagination (только «Pokaż więcej»).
- **Шрифты:** при наличии parent вызывается `wp_dequeue_style('storefront-fonts')`; фильтр `storefront_google_font_families` возвращает пустой массив.
- **Категория:** снимаются `storefront_before_content` → woocommerce_category_image и `woocommerce_archive_description` → woocommerce_category_image.

**Вердикт:** ✅ Соответствует документации.

---

## 5. Overrides WooCommerce

**Документация:** Переопределения в `mnsk7-storefront/woocommerce/`, имена и иерархия как в Woo/Storefront.

**Проверка кода:**

- Наличие и имена: archive-product.php, single-product.php, content-single-product.php, global/breadcrumb.php, global/wrapper-start.php, wrapper-end.php, global/quantity-input.php, loop/price.php, loop/no-products-found.php, single-product/related.php, single-product/up-sells.php, cart/cart-empty.php, cart/proceed-to-checkout-button.php, content-product-table-row.php. Соответствуют структуре WooCommerce/Storefront.
- В шаблонах используются `get_header()`, `do_action('woocommerce_*')`, `wc_get_template()`, вывод через функции темы (например `mnsk7_get_archive_attribute_filter_chips`), без включения файлов parent.
- wrapper-start/wrapper-end содержат только разметку; в комментарии указана связь с header.php child.

**Вердикт:** ✅ Соответствует.

---

## 6. Header и Footer темы

**Документация:** Один источник правды — header.php и footer.php в child.

**Проверка кода:**

- `header.php`: полный вывод с `<html>`, `<head>`, критичные inline-стили, структура #page, промо-бар, нав, поиск, аккаунт, корзина. Без вызова parent.
- `footer.php`: колонки, контакты, часы, newsletter, cookie bar; текст домен `mnsk7-storefront`.
- `footer-shop.php` подключает только child: `require get_stylesheet_directory() . '/footer.php'`.

**Вердикт:** ✅ Соответствует.

---

## 7. Деплой

**Документация:** Синхронизировать только `mnsk7-storefront/`, не весь `themes/` с `--delete`.

**Проверка кода:**

- `scripts/deploy-rsync.sh`: rsync только `"$ROOT/wp-content/themes/mnsk7-storefront/"` в `.../wp-content/themes/mnsk7-storefront/`. Комментарий отсылает к docs/THEME-STACK-ROOT-CAUSE-AND-FIX.md.
- `.github/workflows/deploy-staging.yml`: rsync только `wp-content/themes/mnsk7-storefront/` в тот же путь на сервере; в комментарии указано, что parent не удаляется.

**Вердикт:** ✅ Соответствует.

---

## 8. ARCHITECTURE: логика не в шаблонах, вынос в плагин/inc

**Документация:** «В шаблоне — только разметка и вызовы Woo-функций; бизнес-логику выносить в плагин или inc/.» «Вынести из functions.php child-theme в mu-plugin или inc/».

**Проверка кода:**

- Папки `inc/` в теме нет; вся логика в `functions.php`.
- В `archive-product.php`: вызов `mnsk7_get_archive_attribute_filter_chips()` (функция в functions.php), циклы по терминам и фильтрам, замыкание `$render_filter_row` для вывода чипсов. Данные для вывода приходят из хелпера; разметка и циклы остаются в шаблоне — типично для переопределений Woo, но объём условной логики и замыкания выходит за рамки «только разметка».
- В `content-single-product.php`: вывод через хуки Woo и один вызов `mnsk7_single_product_schema_video_placeholder()`; разметка и один условный блок — в норме.
- Крупные функции (mnsk7_get_archive_attribute_filter_chips, mnsk7_is_plp_url_path, обработка контакта, фрагменты корзины и т.д.) находятся в `functions.php`, а не в mu-plugin или inc/.

**Вердикт:** ⚠️ Частично соответствует. Правило «не смешивать логику в шаблонах» соблюдается в том смысле, что тяжёлая логика вынесена в функции (functions.php), но: 1) папки inc/ нет, выноса в отдельные модули нет; 2) в archive-product остаётся заметная логика вывода (условия, замыкание). Рекомендация: при следующем рефакторинге вынести хелперы и бизнес-логику в mu-plugin или в `inc/` с подключением из functions.php; в archive-product по возможности оставить в основном цикл по готовым данным и разметку.

---

## 9. Безопасность и экранирование

**Проверка кода:**

- В шаблонах и functions в большинстве мест используется `esc_html`, `esc_attr`, `esc_url` для вывода; переводы через `__()/esc_html_e()` с text domain.
- Замечания:
  - `content-single-product.php` (~54–55): вывод `$extra` с `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` — предполагается, что `mnsk7_single_product_schema_video_placeholder()` возвращает безопасный HTML; при изменении функции нужно сохранять санитизацию.
  - Там же (~69): `get_price_html()` с phpcs:ignore — WooCommerce обычно возвращает отфильтрованный HTML; допустимо при условии, что не подставляются произвольные данные.
  - `functions.php` (~1294): в `mnsk7_shop_archive_description_stripped()` вывод `$description` с phpcs:ignore после `wc_format_content(wp_kses(...))` — контент санитизирован; комментарий про escape можно оставить для ясности.
- В archive-product.php: `$_GET` обрабатываются через `sanitize_text_field(wp_unslash(...))` перед использованием в URL и сравнениях; прямой вывод из $_GET в разметку только после esc_*.

**Вердикт:** ⚠️ В целом нормально; три явных отключения проверки вывода — осознанные, но при изменении связанного кода нужно проверять экранирование.

---

## 10. Зависимость от версии Storefront

**Документация:** Совместимость с Storefront 4.6; при смене версии parent проверять совместимость.

**Проверка кода:**

- Используются имена функций Storefront в `remove_action` и фильтрах:
  - storefront_sorting_wrapper, storefront_sorting_wrapper_close
  - storefront_skip_links, storefront_site_branding, storefront_secondary_navigation, storefront_primary_navigation_wrapper, storefront_primary_navigation, storefront_header_cart, storefront_primary_navigation_wrapper_close
  - storefront_footer_widgets, storefront_credit
  - storefront_before_content (woocommerce_category_image)
  - storefront_custom_header_args, storefront_google_font_families
- В style.css указано «Compatible with Storefront 4.6». При обновлении parent (например до 5.x) эти хуки/функции могут измениться или исчезнуть.

**Вердикт:** ⚠️ Соответствует задекларированной версии; при обновлении Storefront обязательно проверять перечисленные хуки и при необходимости править child (см. docs/STOREFRONT.md, раздел 9).

---

## 11. Прочие замечания

- **Breakpoint:** в коде используется `MNSK7_BREAKPOINT_MOBILE = 992`; в документации и комментариях ранее встречалось 768. Стоит убедиться, что 992 согласован с CSS (media queries в 04-header.css и др.) и с любыми JS, которые опираются на этот breakpoint.
- **Версия темы:** в enqueue используется `MNSK7_THEME_VERSION` или `'3.0.9'`; в style.css указано `Version: 1.0.0`. Имеет смысл держать одну источник истины (например, константу в functions.php и при необходимости выводить в style.css через фильтр или оставить только в style.css и читать оттуда).
- **template_include:** fallback на parent archive-product через `get_template_directory() . '/woocommerce/archive-product.php'` корректен: используется только при отсутствии шаблона в child и только для чтения.

---

## 12. Итоговая таблица соответствия

| Критерий (по документации) | Статус |
|----------------------------|--------|
| Не редактировать parent | ✅ |
| Template: storefront, совместимость 4.6 | ✅ |
| mnsk7_parent_storefront_available(), условный enqueue | ✅ |
| Порядок стилей: parent → child → parts | ✅ |
| Собственные header.php, footer.php | ✅ |
| remove_action Storefront header/footer | ✅ |
| Один блок сортировки на PLP (после цикла) | ✅ |
| Overrides Woo только в child, правильные имена | ✅ |
| Деплой только mnsk7-storefront/ | ✅ |
| Логика по возможности не в шаблонах / вынос в inc или плагин | ⚠️ Рекомендация |
| Экранирование вывода | ⚠️ Три точечных phpcs:ignore, в целом ок |
| Учёт версии Storefront при обновлении | ⚠️ Напоминание в доке выполнено |

---

## 13. Рекомендации

1. **ARCHITECTURE:** Постепенно выносить хелперы и бизнес-логику из `functions.php` в mu-plugin проекта или в `inc/` темы с подключением из `functions.php`; в `archive-product.php` по возможности оставить в основном вывод по готовым данным.
2. **Версия темы:** Свести к одному источнику версии (style.css или константа в functions.php) и при необходимости синхронизировать.
3. **Storefront:** При обновлении родительской темы выше 4.6 проверять перечень storefront_* хуков (раздел 10 отчёта) и при необходимости обновить child (см. docs/STOREFRONT.md).
4. **Экранирование:** При изменении `mnsk7_single_product_schema_video_placeholder` или логики описания архива сохранять санитизацию/экранирование вывода.

Код в целом соответствует документации Storefront и ARCHITECTURE; замечания носят характер улучшений и напоминаний при дальнейших правках.
