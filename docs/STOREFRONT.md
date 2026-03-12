# Документация по Storefront (mnsk7-tools.pl)

**Дата:** 2026-03-11  
**Назначение:** родительская тема WooCommerce, дочерняя тема — **mnsk7-storefront**. В этом документе — роль Storefront в проекте, совместимость, деплой и ссылки на официальные ресурсы.

---

## 1. Что такое Storefront

- **Storefront** — официальная тема WooCommerce (Automattic). Оптимизирована под интернет-магазины, идёт в паре с WooCommerce.
- В проекте используется как **родительская тема**; активная тема — **дочерняя** `mnsk7-storefront`.
- Все кастомизации (header, footer, PLP, PDP, стили) делаются в child theme; файлы parent **не редактируются**.

Официальные ресурсы (и локальные копии-справки в проекте):

- [Storefront на WordPress.org](https://wordpress.org/themes/storefront/)
- [Документация разработчика Storefront (GitHub Wiki)](https://github.com/woocommerce/storefront/wiki) — локальная копия: **docs/storefront-reference/GITHUB-WIKI-STOREFRONT.md**
- [Storefront Handbook (WooCommerce)](https://woocommerce.com/documentation/products/themes/storefront/) — локальная копия: **docs/storefront-reference/WOOCOMMERCE-STOREFRONT-DOCS.md**

---

## 2. Связка parent / child

| Параметр | Значение |
|----------|----------|
| **Родитель (parent)** | `storefront` — slug папки темы: `wp-content/themes/storefront/` |
| **Дочерняя (child, активная)** | `mnsk7-storefront` — `wp-content/themes/mnsk7-storefront/` |
| **Template** в `style.css` child | `Template: storefront` |
| **Совместимость** | Указано в child: *Compatible with Storefront 4.6* (см. `mnsk7-storefront/style.css`) |

При смене версии Storefront на сервере нужно проверять совместимость с child (хуки, классы, разметка). Рекомендуется зафиксировать одну тестированную версию (например 4.5.x / 4.6) и не обновлять parent без проверки.

---

## 3. Где находится Storefront

- **На сервере:** родительская тема должна быть установлена в `wp-content/themes/storefront/` (через админку WP или вручную).
- **В репозитории:** папка `storefront` **не входит в Git** — в репо только `mnsk7-storefront`, при необходимости другие темы (best-shop, tech-storefront). По правилам проекта (.cursorrules) родительскую тему можно держать в репо и деплоить вместе с child; текущая практика — деплоить только child, parent на сервере не трогаем (см. раздел 6).

---

## 4. Подключение стилей и проверка parent

В `mnsk7-storefront/functions.php`:

- Функция **`mnsk7_parent_storefront_available()`** проверяет, что родитель — `storefront` и что файл `get_template_directory() . '/style.css'` доступен для чтения.
- Если parent отсутствует (например удалён при деплое): child **не** подключает `storefront-style`, подключается только свой `style.css` и части CSS. Сайт остаётся рабочим, но без базовых стилей Storefront.
- Порядок enqueue при **наличии** parent:
  1. `storefront-style` (parent `style.css`)
  2. `mnsk7-storefront-style` (child `style.css`, зависимость от `storefront-style`)
  3. Части CSS child (00-fonts-inter, 01-tokens, 03-storefront-overrides, 04-header, … 24-plp-table) цепочкой от `mnsk7-storefront-style`.

Подробнее: **docs/THEME-STACK-ROOT-CAUSE-AND-FIX.md**.

---

## 5. Overrides и отключение элементов Storefront

- **Шаблоны WooCommerce:** переопределения лежат в `mnsk7-storefront/woocommerce/` (например `archive-product.php`, шаблоны single-product). Имена и иерархия — как в Woo.
- **Header / Footer:** в child свои `header.php` и `footer.php`; вывод Storefront для header/footer отключён через `remove_action` при наличии parent.
- **Сортировка и пагинация на PLP:** Storefront выводит сортировку и result count до и после цикла товаров; в child оставлен один блок (после цикла), верхний убран через `remove_action` для `storefront_sorting_wrapper`, `woocommerce_catalog_ordering`, `woocommerce_result_count`, `woocommerce_pagination` в `woocommerce_before_shop_loop`.

Правило: не править файлы внутри `storefront/`; все изменения — в child или в плагине/mu-plugin.

---

## 6. Деплой и целостность parent на сервере

- **Важно:** при деплое синхронизируется **только** каталог `wp-content/themes/mnsk7-storefront/`. Весь каталог `wp-content/themes/` с флагом `--delete` **не** синхронизируется — иначе папка `storefront` на сервере удалялась бы при каждом деплое (её нет в репо).
- Скрипты: `scripts/deploy-rsync.sh`, GitHub Actions (`.github/workflows/`) — rsync только `mnsk7-storefront/` в `.../wp-content/themes/mnsk7-storefront/`.
- После деплоя parent на сервере остаётся на месте; при ручной переустановке Storefront нужно ставить версию, совместимую с child (см. `style.css` child и при необходимости **docs/DEPLOY_PLAYBOOK.md**).

Подробный разбор инцидента «слетает parent после деплоя» и исправлений: **docs/THEME-STACK-ROOT-CAUSE-AND-FIX.md**.

---

## 7. Theme mods (настройки Customizer)

- У Storefront свои настройки в Customizer (`theme_mods_storefront`). После чистой установки parent они сбрасываются в дефолты — цвета и типографика могут измениться.
- Визуал child в значительной степени задаётся своими токенами и CSS (`01-tokens.css`, `03-storefront-overrides.css`, и т.д.); при переустановке parent для стабильного вида важно либо сохранять theme_mods, либо использовать одну и ту же версию Storefront.

---

## 8. Связанные документы

| Документ | Содержание |
|----------|------------|
| **docs/STOREFRONT-COMPLIANCE-AUDIT.md** | Аудит соответствия файлов проекта документации Storefront (проверка theme, deploy, overrides) |
| **docs/STOREFRONT-CODE-REVIEW-REPORT.md** | Ревью кода на соответствие документации Storefront и ARCHITECTURE (детальный отчёт по коду) |
| **docs/ARCHITECTURE.md** | Подход к теме, overrides Woo, где править |
| **docs/THEME-STACK-ROOT-CAUSE-AND-FIX.md** | Почему parent «слетал» при деплое и как исправлено |
| **docs/DEPLOY_PLAYBOOK.md** | Деплой, в т.ч. темы |
| **docs/WORKFLOW-REPORT-2026-03-10.md** | Стек (Storefront + mnsk7-storefront), header/footer |
| **.cursorrules** | Правила репо: parent Storefront можно держать в репо и деплоить вместе с child |

---

## 9. Краткий чеклист для разработки

- Кастомизации интерфейса и Woo — только в **mnsk7-storefront** (или в плагине/mu-plugin).
- Новые overrides Woo — в `mnsk7-storefront/woocommerce/` с теми же именами/иерархией, что в Woo/Storefront.
- Не менять файлы в `wp-content/themes/storefront/`.
- При обновлении Storefront на сервере — проверить совместимость с child (версия в `mnsk7-storefront/style.css`) и визуал (header, footer, PLP, PDP, корзина).
