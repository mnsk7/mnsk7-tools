# MNK7 Storefront (child theme)

Дочерняя тема для **Storefront** (официальная тема WooCommerce). Проект: mnsk7-tools.pl.

- **Parent:** Storefront (`Template: storefront` в `style.css`)
- **Совместимость:** Storefront 4.6 (указано в `style.css`)

Полная документация по использованию Storefront в проекте, деплою и overrides: **[docs/STOREFRONT.md](../../../docs/STOREFRONT.md)** (от корня репозитория).

Архитектура темы и шаблонов Woo: **docs/ARCHITECTURE.md**.

**CSS:** W runtime ładuje się tylko `assets/css/main.css`. Pliki w `assets/css/parts/` to źródło do budowy — po zmianach w parts uruchomić `bash scripts/build-main-css.sh` (z katalogu theme), commit + deploy main.css. Jedna strategia ładowania eliminuje błąd „staging serwuje stary/rozjechany CSS”. Diagnostyka footera na staging: **docs/FOOTER-DIAGNOSTIC-STAGING.md**.
