---
name: vps-deploy-rsync
description: Команды rsync для деплоя темы/mu-plugins по SSH, с откатом.
---

# VPS deploy (rsync)

## Предпосылки

- На сервере есть путь к WP install
- WP core и uploads не деплоим из git

## Steps

1) Сохранить предыдущую версию темы (до деплоя):

```bash
ssh user@host "cp -a /path/wp-content/themes/<THEME> /path/wp-content/themes/<THEME>_prev"
```

2) rsync theme:

```bash
rsync -az --delete ./wp-content/themes/<THEME>/ user@host:/path/wp-content/themes/<THEME>/
```

3) rsync mu-plugins:

```bash
rsync -az --delete ./wp-content/mu-plugins/ user@host:/path/wp-content/mu-plugins/
```

4) post-deploy wp-cli (на сервере):

```bash
wp cache flush || true
wp rewrite flush --hard
```

## Rollback

1) Вернуть _prev:

```bash
ssh user@host "rm -rf /path/wp-content/themes/<THEME> && mv /path/wp-content/themes/<THEME>_prev /path/wp-content/themes/<THEME>"
```

2) `wp cache flush || true`

