# Аудит соответствия проекта документации Storefront

**Дата:** 2026-03-11  
**Источники:** docs/STOREFRONT.md, docs/storefront-reference/GITHUB-WIKI-STOREFRONT.md, docs/storefront-reference/WOOCOMMERCE-STOREFRONT-DOCS.md

Проверка файлов проекта на соответствие правилам и рекомендациям из этих документов.

---

## 1. Родительская тема не редактируется

| Требование | Статус | Детали |
|------------|--------|--------|
| Все кастомизации только в child или плагине | ✅ | В репозитории нет папки `wp-content/themes/storefront/`. Все правки в `mnsk7-storefront/` и mu-plugins. |
| Не править файлы в `storefront/` | ✅ | Родительская тема не в репо; при обращении к parent используется только `get_template_directory()` для проверки наличия и fallback (например `archive-product.php` в functions.php стр. 1379) — чтение, не правка. |

---

## 2. Метаданные child-темы (style.css)

| Требование | Статус | Детали |
|------------|--------|--------|
| `Template: storefront` | ✅ | В `mnsk7-storefront/style.css` указано `Template:     storefront`. |
| Указание совместимости с версией Storefront | ✅ | В комментарии: `Compatible with Storefront 4.6. Deploy syncs only this child...`. |

---

## 3. Подключение стилей и проверка parent

| Требование | Статус | Детали |
|------------|--------|--------|
| Функция `mnsk7_parent_storefront_available()` | ✅ | Реализована в functions.php (стр. 112–118): проверка `get_template() === 'storefront'` и `is_readable(get_template_directory() . '/style.css')`. |
| При отсутствии parent не подключать storefront-style | ✅ | В wp_enqueue_scripts (стр. 632–636): при `mnsk7_parent_storefront_available()` подключаются storefront-style и child с зависимостью; иначе только child без зависимости. |
| Порядок: storefront-style → mnsk7-storefront-style → части CSS | ✅ | Стр. 632–638: сначала parent (если есть), затем child с зависимостью от storefront-style, затем массив parts (00-fonts-inter … 24-plp-table) цепочкой от mnsk7-storefront-style. |
| Части CSS 00…24 присутствуют | ✅ | В `assets/css/parts/` есть все 25 файлов, перечисленных в functions.php. |

---

## 4. Overrides WooCommerce

| Требование | Статус | Детали |
|------------|--------|--------|
| Overrides в `mnsk7-storefront/woocommerce/` | ✅ | Шаблоны: archive-product.php, single-product.php, content-single-product.php, global/breadcrumb.php, loop/price.php, loop/no-products-found.php, single-product/related.php, single-product/up-sells.php, cart/*, global/wrapper-start.php, wrapper-end.php, quantity-input.php, content-product-table-row.php и др. |
| Имена и иерархия как в Woo/Storefront | ✅ | Стандартные имена (archive-product, content-single-product, loop/price и т.д.). |

---

## 5. Header и Footer

| Требование | Статус | Детали |
|------------|--------|--------|
| Собственные header.php и footer.php в child | ✅ | `mnsk7-storefront/header.php` и `footer.php` — кастомная разметка (logo, nav, search, account, cart; футер с колонками, cookie bar). |
| Отключение вывода Storefront header/footer при наличии parent | ✅ | В init (стр. 1214–1227): при `mnsk7_parent_storefront_available()` вызываются remove_action для storefront_header (skip_links, site_branding, primary_navigation, header_cart и т.д.) и storefront_footer (footer_widgets, credit). |

---

## 6. Сортировка и пагинация на PLP

| Требование | Статус | Детали |
|------------|--------|--------|
| Убрать верхний блок (before_shop_loop) | ✅ | В wp (приоритет 25): remove_action для storefront_sorting_wrapper, woocommerce_catalog_ordering, woocommerce_result_count, woocommerce_pagination, storefront_sorting_wrapper_close в woocommerce_before_shop_loop. |
| Один блок после цикла (after_shop_loop) | ✅ | add_action woocommerce_result_count приоритет 5; remove_action для storefront_sorting_wrapper и storefront_sorting_wrapper_close в after_shop_loop. ordering и pagination остаются на стандартных хуках Woo (не удаляются глобально). |
| При таблице PLP — только «Pokaż więcej», без пагинации | ✅ | При `$GLOBALS['mnsk7_plp_use_table']` в after_shop_loop (приоритет 1) снимается woocommerce_pagination. |

---

## 7. Деплой

| Требование | Статус | Детали |
|------------|--------|--------|
| Синхронизировать только `mnsk7-storefront/`, не весь `themes/` с --delete | ✅ | **scripts/deploy-rsync.sh:** rsync только `"$ROOT/wp-content/themes/mnsk7-storefront/"` в `.../wp-content/themes/mnsk7-storefront/` (стр. 65). Комментарий: «Deploy only child theme mnsk7-storefront — do NOT sync entire themes/». |
| GitHub Actions — то же | ✅ | **.github/workflows/deploy-staging.yml:** rsync только `wp-content/themes/mnsk7-storefront/` в `.../wp-content/themes/mnsk7-storefront/` (стр. 71). Комментарий (стр. 61): «Deploy only child theme (not entire themes/) so storefront parent is never removed». |

---

## 8. Дополнительные соответствия

| Элемент | Статус | Детали |
|---------|--------|--------|
| Отключение шрифтов Storefront | ✅ | При наличии parent вызывается wp_dequeue_style('storefront-fonts') (стр. 1189–1190). |
| Фильтр storefront_google_font_families | ✅ | `__return_empty_array` (стр. 1248) — свои шрифты в child (00-fonts-inter, токены). |
| Админ-уведомление при отсутствии parent | ✅ | admin_notices (стр. 1233–1238): при отсутствии parent и активной теме mnsk7-storefront выводится предупреждение установить Storefront. |
| Clearfix ul.products::before | ✅ | Inline CSS (стр. 658): отключение ::before у ul.products для корректной работы grid (документировано в THEME-STACK и аудитах). |

---

## 9. Расхождение с .cursorrules (не критично)

| Документ | Формулировка | Факт |
|----------|--------------|------|
| **.cursorrules** | «Родительскую тему Storefront тоже держать в репозитории (wp-content/themes/storefront) и деплоить вместе с дочерней» | В репо только mnsk7-storefront, tech-storefront, best-shop; папки storefront нет. Деплой только child — parent на сервере не трогается. |
| **docs/STOREFRONT.md** | «В репозитории папка storefront не входит в Git… текущая практика — деплоить только child» | Реализация соответствует STOREFRONT.md и THEME-STACK-ROOT-CAUSE-AND-FIX: parent не удаляется при деплое. |

**Рекомендация:** При желании привести .cursorrules в соответствие с практикой — указать, что parent можно по желанию держать в репо; текущая схема — только child в репо, parent на сервере устанавливается отдельно и при деплое не перезаписывается.

---

## 10. Итог

- **Соответствие документации Storefront:** полное.  
- Кастомизации только в child (и mu-plugins), parent не редактируется.  
- Проверка parent, порядок enqueue, отключение header/footer и лишнего сортировочного блока на PLP реализованы по документации.  
- Деплой (локальный и CI) синхронизирует только `mnsk7-storefront/`, целостность parent на сервере не нарушается.  
- Единственное расхождение — формулировка в .cursorrules про «держать Storefront в репо» при текущей практике «только child в репо»; на работу и соответствие остальным документам это не влияет.
