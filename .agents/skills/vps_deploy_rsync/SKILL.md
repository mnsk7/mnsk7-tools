---
name: vps_deploy_rsync
description: Конкретные команды rsync для деплоя темы/mu-plugins/плагинов по SSH, с откатом. Чеклист до/после — см. cyberfolks_deploy_playbook.
---

# VPS deploy (rsync)

## Предпосылки
- На сервере есть релизная папка темы и mu-plugins
- WP core и uploads не деплоим из git

## Steps
1) Сохранить предыдущую версию темы (до деплоя):
   ssh user@host "cp -a /path/wp-content/themes/<THEME> /path/wp-content/themes/<THEME>_prev"
2) rsync theme:
   rsync -az --delete ./wp-content/themes/<THEME>/ user@host:/path/wp-content/themes/<THEME>/
3) rsync mu-plugins:
   rsync -az --delete ./wp-content/mu-plugins/ user@host:/path/wp-content/mu-plugins/
4) post-deploy wp-cli (на сервере):
   wp cache flush || true
   wp rewrite flush --hard

## Rollback
1) Вернуть _prev:
   ssh user@host "rm -rf /path/wp-content/themes/<THEME> && mv /path/wp-content/themes/<THEME>_prev /path/wp-content/themes/<THEME>"
2) wp cache flush || true
