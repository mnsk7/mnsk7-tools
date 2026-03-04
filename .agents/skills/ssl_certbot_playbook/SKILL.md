---
name: ssl_certbot_playbook
description: Выпуск и автопродление SSL на VPS через certbot (nginx/apache).
---

# SSL certbot

## Steps (nginx)
1) установить certbot
2) certbot --nginx -d staging.mnsk7-tools.pl
3) проверить авто-renew: systemctl list-timers | grep certbot (или cron)

## Важно
- сначала staging, потом prod
- не включать HSTS до проверки
