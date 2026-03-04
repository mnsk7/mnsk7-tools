---
name: wp_theme_architecture
description: Архитектура темы WP/Woo: child theme, overrides, структура файлов, правила изменений.
---

# WP Theme Architecture

## Правило
- WooCommerce правим через template overrides в теме
- минимум правок ядра и плагинов
- все кастомизации — в теме/мини-плагине проекта

## Структура
- /woocommerce/ (overrides)
- /template-parts/
- /assets/css /assets/js
- functions.php: только подключение модулей, логика в /inc/

## Запреты
- не править файлы плагинов напрямую
- не смешивать бизнес-логику в шаблонах
