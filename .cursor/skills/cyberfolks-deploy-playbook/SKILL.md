---
name: cyberfolks-deploy-playbook
description: Чеклист "до/после деплоя" на Cyber_Folks: backup, проверки, финализация, откат.
---

# Cyber_Folks deploy playbook (чеклист)

Этот skill — общий чеклист. Команды rsync → `vps-deploy-rsync`. Инициализация staging → `staging-bootstrap`.

## Перед деплоем

- [ ] Backup файлов: `tar -czf backup-theme-$(date +%Y%m%d).tar.gz wp-content/themes/<THEME>/`
- [ ] Backup DB: `wp db export /tmp/backup-$(date +%Y%m%d).sql`
- [ ] Проверить wp-config (staging != prod: DB_NAME, WP_SITEURL)
- [ ] Убедиться, что на staging включён staging-safety MU plugin

## Деплой

- Выкладывать только: тема, mu-plugins, кастом-плагин.
- Не трогать uploads, core WP.
- Команды: `make deploy-files` (или `make staging-full`).

## После деплоя

- [ ] `wp cache flush || true`
- [ ] `wp rewrite flush --hard`
- [ ] Smoke-тест: добавить в корзину, перейти в чекаут, проверить что письма не уходят на staging.

## Откат

- Вернуть предыдущую папку темы (или симлинк `<THEME>_prev`).
- Восстановить DB из backup при критической проблеме.

