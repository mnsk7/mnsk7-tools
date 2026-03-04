---
name: vps_deploy_rsync
description: Конкретные команды rsync для деплоя темы/mu-plugins/плагинов по SSH, с откатом. Чеклист до/после — см. cyberfolks_deploy_playbook.
---

# VPS deploy (rsync)

## Предпосылки
- На сервере есть релизная папка темы и mu-plugins
- WP core и uploads не деплоим из git

## Steps
1) rsync theme:
   rsync -az --delete ./wp-content/themes/<THEME>/ user@host:/var/www/.../wp-content/themes/<THEME>/
2) rsync mu-plugins (staging safety):
   rsync -az --delete ./wp-content/mu-plugins/ user@host:/var/www/.../wp-content/mu-plugins/
3) post-deploy wp-cli:
   wp cache flush || true
   wp rewrite flush --hard

## Rollback
- держать предыдущую папку темы: <THEME>_prev и переключать симлинк или копию
