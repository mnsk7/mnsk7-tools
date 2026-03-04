---
name: ssl_cyberfolks
description: SSL на Cyber_Folks shared hosting через панель DirectAdmin (Let's Encrypt).
---

# SSL Cyber_Folks (shared hosting)

## Shared hosting — панель DirectAdmin

1. Зайти в DirectAdmin.
2. SSL/TLS → Let's Encrypt.
3. Выбрать домен/поддомен (staging.mnsk7-tools.pl и mnsk7-tools.pl).
4. Включить. Продление автоматическое.

## Правила

- Сначала staging, потом prod.
- HSTS не включать до проверки: убедиться, что HTTPS работает на всех страницах.
- CLI-автоматизацию на shared почти всегда нельзя — только через панель.

## VPS

Для VPS (SSH root) → см. skill `ssl_certbot_playbook`.
