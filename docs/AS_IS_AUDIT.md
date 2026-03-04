# AS-IS Audit — mnsk7-tools.pl

Дата: 2026-03-04  
Агент: 00_as_is_audit  
Метод: сканирование файловой системы (wp-content, плагины, темы, конфиги)

---

## A. Тема и архитектура кода

### Тема
- **Parent:** `best-shop` (gradientthemes.com) — готовая коммерческая WP/Woo тема.
- **Child:** `tech-storefront` — child theme (Template: best-shop), 11 файлов.
- Child-theme **есть** ✅ — хорошо, правки не потеряются при обновлении parent.
- Весь кастомный код child: только переопределение цветов и шрифтов через `add_filter('best_shop_settings', ...)`. Логики — нет.

### Woo overrides
- В `best-shop/woocommerce/`: **1 файл** — `content-product.php`.
- В `tech-storefront/`: Woo overrides **отсутствуют**.
- **Вывод:** кастомизация Woo — минимальная, почти всё идёт из коробки parent-темы.

### Кастомный код проекта
- Отдельного mini-plugin или mu-plugin для бизнес-логики **нет**.
- `mu-plugins/automation-by-installatron.php` — системный от хостинга.
- `mu-plugins/staging-safety.php` — наш (добавили).
- **Вывод:** весь "кастом" размазан по functions.php child-темы; нет изоляции логики.

---

## B. Плагины (64 штуки)

### 🔴 Конфликты / дубли (P0)

| Проблема | Плагины |
|----------|---------|
| 3+ фильтр-плагина одновременно | `filter-everything`, `woo-product-filter`, `woocommerce-products-filter`, `woof-by-category` |
| 2 платёжных плагина Przelewy24 | `przelewy24` + `woo-przelewy24` |
| 2 вишлист-плагина | `flexible-wishlist` + `woo-smart-wishlist` |
| 2 page builder | `elementor` + `beaver-builder-lite-version` |
| 2 плагина профилей/регистрации | `ultimate-member` + `profile-builder` |
| 2 GTM-плагина | `duracelltomi-google-tag-manager` + `gtm-ecommerce-woo` |
| Schema дублируется | `schema-and-structured-data-for-wp` + Yoast SEO (встроенный schema) |
| Несколько Facebook/пикселей | `facebook-for-woocommerce` + `official-facebook-pixel` |

### 🟡 Кеш: 3–4 слоя (P1)
- `litespeed-cache` — активен (htaccess настроен).
- `wp-rocket` — папка конфига есть, но пустая (установлен, не настроен? или неактивен).
- `seraphinite-accelerator` — активен (htaccess).
- `object-cache.php` + `wp-redis` — Redis подключён.
- **Риск:** конфликт кеш-плагинов → двойная отдача кеша, баги чекаута, сломанная инвалидация.

### Критичные (оставить)
WooCommerce, Yoast SEO, Przelewy24 (один), LiteSpeed Cache или WP Rocket (один), InPost/Paczkomaty, limit-login-attempts-reloaded, Google Site Kit, GTM (один).

### Под вопросом / убрать
Дублирующие фильтр-плагины, второй вишлист, второй builder, второй профиль-плагин, второй GTM, schema-плагин (Yoast уже делает), seraphinite если оставляем LiteSpeed.

---

## C. SEO

### Что есть
- `wordpress-seo` (Yoast) ✅
- `robots.txt` настроен вручную ✅: cart, checkout, ?s= заблокированы.
- Sitemap: `https://mnsk7-tools.pl/sitemap_index.xml` указан ✅.
- Google Site Kit подключён.

### 🔴 Проблемы
- `schema-and-structured-data-for-wp` + Yoast = два источника schema.org → дубли JSON-LD, риск ошибок валидации.
- robots.txt: `Disallow: /?` блокирует ВСЕ параметры — в т.ч. полезные (?orderby=, ?min_price= и т.д.). ASSUMPTION: нужно проверить, не заблокированы ли страницы фильтров, которые должны индексироваться.
- `Disallow: /wp-` — блокирует wp-includes, wp-content (но `Allow: */uploads` есть). Нужно проверить, доступны ли JS/CSS.
- Title/H1 на категориях и товарах: **ASSUMPTION** — не видно из файлов, нужно проверить в браузере/Search Console.
- Фильтры (`woo-product-filter` и др.): создают ли индексируемые параметрные URL — **ASSUMPTION**, зависит от настроек плагина.

---

## D. Performance

### 🔴 Изображения — главная проблема LCP

- **~34 000 файлов** в uploads (1 ГБ).
- Топовые файлы: **3–4 МБ PNG** (product images, не сжатые).
- WebP-файлов: **168** из 34 000 (~0.5%) — конвертация не работает или только для новых.
- Имена файлов — хэши (`e9ab53de0b10339952663c5da4a64c80.png`) → alt-атрибуты не берутся из имени, нужно заполнять в медиа-библиотеке.

### Кеш
- LiteSpeed активен (htaccess подтверждает).
- Redis (object-cache.php) подключён — хорошо для DB-кеша.
- WP Rocket: папка пустая — **ASSUMPTION**: плагин установлен но не настроен, или деактивирован. Если активен вместе с LiteSpeed — конфликт.
- `webp-uploads`, `image-prioritizer`, `auto-sizes`, `optimization-detective` — Performance Lab плагины, идут параллельно.

---

## E. Безопасность

| Пункт | Статус |
|-------|--------|
| Ограничение логина | ✅ `limit-login-attempts-reloaded` |
| xmlrpc.php | ⚠️ файл существует и **не заблокирован** (robots.txt закрывает только для ботов, HTTP-доступ открыт) |
| Роли | `user-role-editor` есть — ок |
| Бэкапы | ASSUMPTION: не видно плагина бэкапа в списке (нет UpdraftPlus/BackWPup). Нужно проверить. |
| WP Debug | `WP_DEBUG = false` в wp-config-sample ✅ |
| File edit | ASSUMPTION: `DISALLOW_FILE_EDIT` — не видно в конфиге |

---

## F. Каталог и контент (проверка по БД)

Доступ к БД: **есть** (phpMyAdmin / MySQL, префикс таблиц `cmee_`). Результаты — выполнить запросы ниже (скрипт или phpMyAdmin) и подставить в таблицу.

**Как получить данные:** в каталоге проекта задать пароль БД и запустить:
```bash
export DB_PASS='пароль_от_БД_прода/стейджа'
./scripts/check-db-catalog.sh
```
Либо выполнить SQL из вывода скрипта в phpMyAdmin (база `llojjlcemq_stg` или продовая).

### Результаты проверки (заполнить после выполнения запросов)

| Вопрос | Результат |
|--------|-----------|
| **Атрибуты товаров** (материал, операция, диаметр, хвостовик, покрытие, зубья) | *Запрос: `SELECT attribute_name, attribute_label FROM cmee_woocommerce_attribute_taxonomies;` → вписать список атрибутов или «нет данных»* |
| **SKU** — сколько заполнено, сколько пустых, примеры значений | *Запросы из check-db-catalog.sh (total_sku, empty_sku, sample_sku) → вписать числа и примеры* |
| **Описания товаров** — структура (польза vs параметры) | Ручная выборочная проверка в карточках товаров. |
| **Фото** — сколько вложений с alt, сколько с пустым alt | *Запросы из check-db-catalog.sh (attachments_with_alt, attachments_empty_alt) → вписать числа* |

Фото: ~34 000 файлов, много тяжёлых PNG. После подстановки цифр по alt — при необходимости массовое заполнение или скрипт.
