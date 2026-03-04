---
name: db_provision_mysql
description: Создание БД и пользователя MySQL, права, импорт/экспорт, проверка кодировки.
---

# DB provision (MySQL)

## Создание БД + пользователя

- В панели (DirectAdmin / phpMyAdmin): создать БД (например mnsk7_stg), пользователя, выдать полные права на эту БД (GRANT ALL ON mnsk7_stg.* TO user@localhost).
- Либо через SSH (если есть доступ к MySQL):  
  `CREATE DATABASE mnsk7_stg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`  
  `CREATE USER 'user'@'localhost' IDENTIFIED BY 'password';`  
  `GRANT ALL ON mnsk7_stg.* TO 'user'@'localhost';`  
  `FLUSH PRIVILEGES;`

## Импорт / экспорт

- Экспорт: `wp db export /tmp/backup.sql --add-drop-table` (из корня сайта) или `mysqldump -u user -p dbname > backup.sql`
- Импорт: `wp db import /path/to/backup.sql` или `mysql -u user -p dbname < backup.sql`
- После импорта с другого окружения: обязательно search-replace домена и опции (blog_public и т.д.).

## Проверка кодировки

- Таблицы и БД: `utf8mb4`, collation `utf8mb4_unicode_ci`.
- В MySQL: `SHOW VARIABLES LIKE 'character_set%';` и проверка таблиц через информация о таблице.
