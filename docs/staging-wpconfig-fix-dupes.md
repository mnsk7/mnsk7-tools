# Staging wp-config: пользователь БД как на проде

Если в стейдже указан DB_USER=llojjlcemq_stg, а нужен продовский пользователь, на сервере:

```bash
cd ~/domains/mnsk7-tools.pl/public_html/staging
sed -i "s/'llojjlcemq_stg'/'llojjlcemq_fdvz1'/g" wp-config.php
grep DB_USER wp-config.php
```

Должно быть: `define( 'DB_USER', 'llojjlcemq_fdvz1' );`

---

# Убрать дубли WP_HOME и WP_SITEURL в wp-config стейджа

На сервере в каталоге стейджа выполни (удаляет только второе вхождение этих двух строк):

```bash
cd ~/domains/mnsk7-tools.pl/public_html/staging
awk '/define.*WP_HOME.*staging\.mnsk7-tools\.pl/ { c1++; if (c1>1) next } /define.*WP_SITEURL.*staging\.mnsk7-tools\.pl/ { c2++; if (c2>1) next } { print }' wp-config.php > wp-config.php.tmp && mv wp-config.php.tmp wp-config.php
```

Проверка:
```bash
grep -A7 "DB_HOST" wp-config.php
```
Должно быть ровно 6 строк после DB_HOST: WP_HOME, WP_SITEURL, WP_ENVIRONMENT_TYPE, DISALLOW_FILE_EDIT, WP_DEBUG — по одной.

---

Если после этого остались дубли WP_ENVIRONMENT_TYPE и DISALLOW_FILE_EDIT, выполни ещё раз (удаляет второе вхождение):

```bash
awk '/define.*WP_ENVIRONMENT_TYPE.*staging/ { c1++; if (c1>1) next } /define.*DISALLOW_FILE_EDIT.*true/ { c2++; if (c2>1) next } { print }' wp-config.php > wp-config.php.tmp && mv wp-config.php.tmp wp-config.php
```
