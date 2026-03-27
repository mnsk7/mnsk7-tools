---
name: staging-bootstrap
description: Инициализация staging-окружения: поддомен, БД, wp-config, перенос файлов, финализация wp-cli.
---

# Staging bootstrap

## Как поднять staging с нуля

1. Создать поддомен/слот staging (DirectAdmin: `staging.mnsk7-tools.pl`).
2. Создать БД для staging, пользователь, права.
3. Развернуть файлы: тема + mu-plugins (rsync из репо или копия прода).
4. Настроить `wp-config.php`: DB_* + `WP_HOME`, `WP_SITEURL` на staging.
5. Добавить staging-константы (`WP_ENVIRONMENT_TYPE`, `DISALLOW_FILE_EDIT` и т.д.).
6. Убедиться, что MU plugin `staging-safety` активен (блок почты/платежей).
7. Импорт БД с прода → `search-replace` домена → `blog_public=0` → flush.

## Что отключить на staging

- Индексация: `wp option update blog_public 0`
- Письма: MU plugin перехватывает `wp_mail`
- Реальные платёжные гейтвеи: MU plugin или настройки Woo

## Команды wp-cli после поднятия/обновления

- `wp rewrite flush --hard`
- `wp cache flush` (если есть object cache)
- `wp option update blog_public 0`
- При необходимости: `wp search-replace 'https://prod' 'https://staging' --all-tables --precise`

