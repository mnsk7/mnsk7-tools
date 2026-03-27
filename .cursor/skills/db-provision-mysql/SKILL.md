---
name: db-provision-mysql
description: Создание БД и пользователя MySQL, права, импорт/экспорт, проверка кодировки.
---

# DB provision (MySQL)

## Создание БД + пользователя

- В панели (DirectAdmin / phpMyAdmin): создать БД (например `mnsk7_stg`), пользователя, выдать полные права на эту БД.
- Либо через SSH (если есть доступ к MySQL):

```sql
CREATE DATABASE mnsk7_stg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL ON mnsk7_stg.* TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

## Импорт / экспорт

- Экспорт: `wp db export /tmp/backup.sql --add-drop-table` или `mysqldump -u user -p dbname > backup.sql`
- Импорт: `wp db import /path/to/backup.sql` или `mysql -u user -p dbname < backup.sql`
- После импорта: search-replace домена + staging-опции (`blog_public`).

## Проверка кодировки

- БД/таблицы: `utf8mb4`, collation `utf8mb4_unicode_ci`.

