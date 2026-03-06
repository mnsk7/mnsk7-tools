# Backlog (архитектура / инфра / данные)

*(Выход агента 03_wp_architect)*

Задачи уровня архитектура, инфраструктура и данные. Детали и приоритеты P0–P3 — в [AS_IS_BACKLOG.md](AS_IS_BACKLOG.md); эпики и спринты — в [tasks/010_epics.md](../tasks/010_epics.md), [020_sprint_01.md](../tasks/020_sprint_01.md), [030_sprint_02.md](../tasks/030_sprint_02.md).

---

## 1. Тема и код

| ID | Задача | Связь |
|----|--------|--------|
| ARCH-01 | Держать все кастомизации в child-theme `mnsk7-storefront`; не править parent и плагины напрямую | ARCHITECTURE §1, §2 |
| ARCH-02 | ~~Вынести бизнес-логику в mu-plugin~~ | **Частично:** основная логика в mnsk7-tools.php (key params, availability, trust, shortcodes); часть остаётся в theme functions.php (enqueue, hooks Storefront) |
| ARCH-03 | ~~Woo overrides в child: карточка и категория~~ | **Есть:** mnsk7-storefront/woocommerce/ (content-single-product, single-product, archive-product, wrappers, content-product-table-row) |
| ARCH-04 | В child: подключать кастомные CSS/JS из `assets/`; логику в `inc/` или в плагине | wp_theme_architecture |

---

## 2. Данные и каталог

| ID | Задача | Связь |
|----|--------|--------|
| ARCH-05 | Аудит и унификация атрибутов товаров: материал, операция, Ø, хвостовик, длина, покрытие, зубья — заполненность и имена | P1-02, content_catalog_rules, ARCHITECTURE §4.1 |
| ARCH-06 | Решение по структуре категорий: тип инструмента → материал/параметры (без смешения логик); при необходимости реорганизация product_cat | REQUIREMENTS 2.1, SEO_PLAN §2 |
| ARCH-07 | Массовое заполнение alt у изображений товаров (ключевое слово + параметры) | P2-04, SEO_PLAN §6 |
| ARCH-08 | (Опционально) Стандартизация формата SKU для новых товаров (TYPE-Ø-L-COAT-FLUTES-SHANK) | P3-03, content_catalog_rules |

---

## 3. Плагины

| ID | Задача | Связь |
|----|--------|--------|
| ARCH-09 | Оставить один фильтр-плагин; отключить остальные; при смене URL — редиректы или noindex (проверка Search Console) | P1-01, AS_IS_RISKS R02 |
| ARCH-10 | Один источник schema (Yoast); отключить schema-and-structured-data-for-wp | P2-01 |
| ARCH-11 | Один GTM, один Facebook Pixel; отключить дубли | P2-05, P2-06, TRACKING |
| ARCH-12 | Убрать дубли: один вишлист, один page builder, один плагин профилей (после проверки использования) | P1-04 |
| ARCH-13 | Уточнить плагины оплаты: Przelewy24 и Przelewy24 Raty — оба при необходимости; убрать только дубль одного шлюза | P0-01, AS_IS_RISKS R05 |

---

## 4. Кеш и производительность

| ID | Задача | Связь |
|----|--------|--------|
| ARCH-14 | Один страничный кеш (LiteSpeed или WP Rocket); отключить Seraphinite и второй | P0-02, ARCHITECTURE §6 |
| ARCH-15 | Исключить из кеша: cart, checkout, my-account | P1-05 |
| ARCH-16 | План конвертации изображений в WebP и сжатия (массово или плагин); выполнение в спринтах | P1-03, ARCHITECTURE §6 |
| ARCH-17 | Сохранить Redis object cache; не дублировать object cache другим плагином | AS_IS_AUDIT D |

---

## 5. Инфра и безопасность

| ID | Задача | Связь |
|----|--------|--------|
| ARCH-18 | ~~Заблокировать xmlrpc.php~~ | **Сделано:** mu-plugin mnsk7-tools.php возвращает 403 на XMLRPC_REQUEST |
| ARCH-19 | Проверить/настроить бэкапы (БД + файлы) | P0-04 |
| ARCH-20 | Пересмотреть robots.txt после выбора фильтра: не блокировать нужные параметры; noindex для тонких комбинаций фильтров | P2-02, SEO_PLAN §5 |

---

## 6. Порядок выполнения

- **Сначала:** ARCH-13, ARCH-14, ARCH-15, ARCH-18, ARCH-19 (стабильность, деньги, безопасность).
- **Параллельно/сразу после:** ARCH-02, ARCH-03 (код и overrides для спринтов).
- **Для каталога и SEO:** ARCH-05, ARCH-06, ARCH-09, ARCH-10, ARCH-07, ARCH-20.
- **По мере возможности:** ARCH-11, ARCH-12, ARCH-16, ARCH-08.

Детализацию по спринтам см. в tasks/020_sprint_01.md и 030_sprint_02.md.
