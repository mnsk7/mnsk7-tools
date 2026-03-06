# Аудит плагинов — staging.mnsk7-tools.pl

**Дата:** 2026-03-06  
**Метод:** WP-CLI по SSH (креды из `.env`), путь: `domains/mnsk7-tools.pl/public_html/staging`

---

## 1. Список плагинов (на момент аудита)

| Плагин | Статус | Рекомендация |
|--------|--------|--------------|
| classic-widgets | active | Оставить |
| woo-discount-rules | active | Проверить: загрузка переводов до `init` (WP 6.7 notice). Если скидки только через купоны Woo — отключить |
| embed-optimizer | active | **Отключить** — ленивая загрузка iframe есть в ядре WP |
| auto-sizes | active | Оставить (оптимизация изображений) |
| flexible-checkout-fields | active | Оставить (поля чекаута) |
| furgonetka | active | Оставить (доставка) |
| google-listings-and-ads | active | Оставить (канал продаж) |
| duracelltomi-google-tag-manager | active | Оставить (аналитика) |
| dominant-color-images | active | Оставить (LCP/placeholder) |
| image-prioritizer | active | Оставить (приоритет загрузки картинок) |
| inpost-paczkomaty | active | Оставить (доставка) |
| limit-login-attempts-reloaded | active | Оставить (безопасность) |
| webp-uploads | active | Оставить |
| optimization-detective | active | **Отключить** — диагностика/эксперимент, нагружает |
| performance-lab | active | На усмотрение (экспериментальные фичи) |
| woo-product-filter | active | **Отключить** — фильтры реализованы чипами в теме |
| wc-product-table-lite | active | **Отключить** — таблица каталога в теме |
| woo-przelewy24 | active | Оставить (платежи) |
| pwa-for-wp | active | **Отключить** — если PWA не нужен; усложняет стек |
| shopengine | active | Проверить: если страницы не строятся через него — отключить |
| speculation-rules | active | Оставить (prefetch) или отключить для теста скорости |
| sticky-menu-or-anything-on-scroll | active | **Отключить** — липкое меню делается через CSS (`position: sticky`) |
| customize-my-account-for-woocommerce | active | Оставить (UX кабинета) |
| ultimate-member | active | Проверить дубли с WooCommerce My Account и User Role Editor |
| unlimited-elements-for-elementor | active | **Отключить** — Elementor неактивен, аддон бесполезен |
| user-role-editor | active | Оставить (роли) |
| webtoffee-product-feed | active | Оставить (фиды) |
| woocommerce | active | Ядро — не трогать |
| woo-update-manager | active | Оставить (обновления Woo) |
| load-more-products-for-woocommerce | active | **Отключить** — каталог в теме таблицей, без «load more» |
| wp-rocket | active | Оставить, но: при `wp plugin list` даёт OOM (128 MB) — на хосте поднять `memory_limit` или отключить на staging |
| wptelegram | active | **Оставляем** — уведомления в Telegram |
| yith-woocommerce-product-slider-carousel | active | Слайдер товаров на главной/в блоках. Отключить, если нигде не выводится |
| wordpress-seo | active | Оставить (Yoast SEO) |

**MU-plugins (не отключать):** mnsk7-catalog-core, mnsk7-tools, staging-safety.

---

## 2. Зачем нужен каждый активный плагин

| Плагин | Зачем нужен |
|--------|-------------|
| **classic-widgets** | Классические виджеты в админке (блоки vs виджеты) |
| **woo-discount-rules** | Скидки по правилам (категория, кол-во, даты). Если скидки только купонами Woo — не нужен |
| **auto-sizes** | Авто `width`/`height` у картинок (меньше CLS) |
| **flexible-checkout-fields** | Доп. поля на чекауте (NIP, заметки и т.д.) |
| **furgonetka** | Интеграция доставки Furgonetka |
| **google-listings-and-ads** | Товары в Google Merchant / реклама. Нужен только если ведёшь каталог/рекламу в Google |
| **duracelltomi-google-tag-manager** | GTM (счётчики, теги). Нужен, если используешь GTM |
| **dominant-color-images** | Цвет placeholder под LCP, быстрее отрисовка |
| **image-prioritizer** | Приоритет загрузки изображений (LCP и т.д.) |
| **inpost-paczkomaty** | Пункты/пакоматы InPost при доставке |
| **limit-login-attempts-reloaded** | Ограничение попыток входа (защита от брутфорса) |
| **webp-uploads** | Конвертация загружаемых картинок в WebP |
| **performance-lab** | Эксперименты WordPress по производительности (лаборатория) |
| **woo-przelewy24** | Платежи Przelewy24 — **нужен для приёма оплаты** |
| **pwa-for-wp** | PWA (установка сайта как приложение). Часто не нужен |
| **shopengine** | Конструктор страниц/виджетов для Woo. Нужен только если им реально собираешь страницы |
| **speculation-rules** | Prefetch ссылок (быстрее переходы). Можно отключить и проверить скорость |
| **customize-my-account-for-woocommerce** | Вкладки и вид «Моё учётной записи». Удобство, не обязательно |
| **ultimate-member** | Регистрация/профили/роли помимо Woo. Нужен только если используешь |
| **user-role-editor** | Редактирование ролей (кто что видит в админке). Нужен, если настраиваешь роли |
| **webtoffee-product-feed** | Фиды для Google/Facebook и т.д. Нужен только если отдаёшь фиды |
| **woocommerce** | Ядро магазина — **обязателен** |
| **woo-update-manager** | Лицензии/обновления расширений Woo |
| **wp-rocket** | Кеш страниц, минификация. **Важен для скорости**; на staging можно отключить |
| **wptelegram** | Уведомления в Telegram (заказы и т.д.) — **оставляем** |
| **yith-woocommerce-product-slider-carousel** | Слайдер/карусель товаров. Нужен только если блок слайдера где-то выведен |
| **wordpress-seo** | Yoast SEO — мета, sitemap, Open Graph. **Обычно оставляют** |

---

## 3. Что отключено скриптом (рекомендованные к деактивации)

Скрипт `scripts/staging-deactivate-plugins-audit.sh` отключает:

- `woo-product-filter` — фильтры в теме (чипы)
- `wc-product-table-lite` — таблица каталога в теме
- `unlimited-elements-for-elementor` — Elementor выключен
- `optimization-detective` — диагностика, лишняя нагрузка
- `sticky-menu-or-anything-on-scroll` — заменяется CSS
- `embed-optimizer` — дублирует ядро WP
- `load-more-products-for-woocommerce` — каталог без «load more»

**Дополнительно (скрипт с флагом `--optional`):** плагины из раздела 4 ниже.

---

## 4. «Не знаю, зачем нужно» — можно отключить опционально

Если этими функциями не пользуешься, их можно выключить одним скриптом:

| Плагин | Зачем мог стоять | Риск отключения |
|--------|-------------------|-----------------|
| **pwa-for-wp** | Установка сайта как приложение | Нет, если PWA не нужен |
| **shopengine** | Сборка страниц виджетами | Проверить: нет ли страниц, собранных через него |
| **performance-lab** | Эксперименты WP по скорости | Нет |
| **speculation-rules** | Prefetch ссылок | Минимальный |
| **yith-woocommerce-product-slider-carousel** | Слайдер товаров | Нет, если слайдер нигде не выводится |
| **ultimate-member** | Соц. сеть/профили/роли | Нет, если только Woo «Моё учётная запись» |
| **customize-my-account-for-woocommerce** | Красивые вкладки в кабинете | Только вид кабинета изменится |
| **google-listings-and-ads** | Товары в Google | Нет, если не ведёшь каталог в Google |
| **webtoffee-product-feed** | XML/CSV фиды | Нет, если фиды не используешь |
| **woo-discount-rules** | Сложные скидки | Нет, если скидки только купонами Woo |

Команда (деактивирует только те, что активны):

```bash
./scripts/staging-deactivate-plugins-audit.sh --optional
```

---

## 5. Запуск аудита и деактивации

```bash
# Список плагинов (WP-CLI по SSH, память 512M)
make plugin-list

# Деактивация обязательного набора (дубли темы, лишняя нагрузка)
./scripts/staging-deactivate-plugins-audit.sh

# Деактивация опциональных («не знаю зачем» — pwa, shopengine, performance-lab и др.)
./scripts/staging-deactivate-plugins-audit.sh --optional
```

Подключение: SSH из `.env` (`cyberfolks_ssh_*`), на сервере PHP 8.2, WP-CLI в `/usr/local/bin/wp`. Для `wp plugin list` нужен повышенный лимит памяти из‑за WP Rocket: `php -d memory_limit=512M /usr/local/bin/wp plugin list`.

---

## 6. Артефакты после отключения плагинов фильтров

На странице категории (например `/kategoria-produktu/frez-z-wymiennymi-plytkami/`) могут остаться:

### 6.1 `[wpf-filters id=7]` (сырой шорткод, дважды)

**Источник:** плагин **Product Filter for WooCommerce (WBW)** / **Filter Everything** или аналог — шорткод `[wpf-filters id=7]` был вставлен в **описание категории** в админке. После деактивации плагина шорткод не обрабатывается и выводится как текст.

**Где править:** Товары → Категории → «Frez z wymiennymi płytkami» (и другие категории) → поле **Описание** — удалить строки `[wpf-filters id=7]`.

**Через WP-CLI (staging):**
```bash
# Сначала посмотреть, какие категории затронуты (без изменений)
./scripts/staging-clean-category-description-shortcodes.sh --dry-run
# Удалить шорткод из описаний категорий
./scripts/staging-clean-category-description-shortcodes.sh
```

### 6.2 Блок «Filtruj: Średnica: 0,2 mm 0,3 mm … Trzpień: …»

**Источник:** виджет **плагина фильтров** (WBW / WOOF — `wpfwoofilterswidget`), выведенный в сайдбаре. Фильтры в теме уже реализованы чипсами в `archive-product.php`.

**Через WP-CLI (staging):**
```bash
# Список виджетов в сайдбаре магазина (sidebar-1)
make widget-list   # или скрипт ниже

# Удалить виджет фильтров из sidebar-1
./scripts/staging-remove-filter-widget.sh
```
Скрипт ищет виджеты с именем `wpfwoofilterswidget` (или `woof`, `filter`) в `sidebar-1` и удаляет их через `wp widget delete <id>`.
