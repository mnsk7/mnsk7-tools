---
name: staging_bootstrap
description: Инициализация staging-окружения с нуля: поддомен, БД, wp-config, первый перенос файлов, финализация wp-cli.
---

# Staging bootstrap

## Как поднять staging с нуля

1. Создать поддомен/слот staging (DirectAdmin: поддомен staging.mnsk7-tools.pl).
2. Создать БД для staging (mnsk7_stg), пользователь, права.
3. Развернуть файлы: тема + плагины + mu-plugins (rsync из репо или копия прода).
4. Скопировать wp-config.php, заменить DB_NAME, DB_USER, DB_PASSWORD, WP_HOME, WP_SITEURL на staging.
5. Добавить в wp-config staging-константы (WP_ENVIRONMENT_TYPE, DISALLOW_FILE_EDIT и т.д.).
6. Залить MU plugin staging-safety.php (отключение писем/платежей).
7. Импорт БД с прода → search-replace домена → blog_public 0 → flush.

## Что отключить на staging

- Индексация: `wp option update blog_public 0`
- Письма: MU plugin перехватывает wp_mail, уводит в dev-null
- Реальные платёжные гейтвеи: MU plugin или настройки Woo
- Кеш по ситуации (включён/выключен для отладки)

## Команды wp-cli после поднятия/обновления

- `wp rewrite flush --hard`
- `wp cache flush` (если есть object cache)
- `wp option update blog_public 0`
- При необходимости: `wp search-replace 'https://prod' 'https://staging' --all-tables --precise`
