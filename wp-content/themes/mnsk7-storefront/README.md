# MNK7 Storefront (child theme)

Дочерняя тема для **Storefront** (официальная тема WooCommerce). Проект: mnsk7-tools.pl.

- **Parent:** Storefront (`Template: storefront` в `style.css`)
- **Совместимость:** Storefront 4.6 (указано в `style.css`)

Полная документация по использованию Storefront в проекте, деплою и overrides: **[docs/STOREFRONT.md](../../../docs/STOREFRONT.md)** (от корня репозитория).

Архитектура темы и шаблонов Woo: **docs/ARCHITECTURE.md**.

**CSS:** Тема подключает стили из `assets/css/parts/*.css`. Если на сервере папка `parts/` отсутствует или пуста, подключается `assets/css/main.css`. Перед деплоем без parts пересобрать main: `bash scripts/build-main-css.sh` (из корня темы). Диагностика футера на staging: **docs/FOOTER-DIAGNOSTIC-STAGING.md**.
